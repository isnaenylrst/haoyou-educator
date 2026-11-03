<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationResultRequest;
use App\Http\Requests\UpdateConsultationResultRequest;
use App\Models\Consultation;
use App\Models\ConsultationResult;
use App\Models\RevisionConsultation;
use App\Models\Teacher;
use App\Models\TeacherObservation;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ConsultationController extends Controller
{
    /**
     * Tampilkan data konsultasi lengkap untuk Owner:
     * - Tab 1: Hasil Konsultasi
     * - Tab 2: Jadwal Konsultasi
     * - Tab 3: Konsultasi Revisi
     */
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'hasil');
        $teacherId = $request->query('teacher_id');
        $dateFrom  = $request->query('date_from');
        $dateTo    = $request->query('date_to');
        $status    = $request->query('status');
        $q         = trim($request->query('q', ''));

        // 1. Query Hasil Konsultasi
        $resultsQuery = ConsultationResult::with(['teacher.user', 'curriculumUser', 'observation.class'])
            ->latest('consultation_date');

        if ($teacherId) {
            $resultsQuery->where('teacher_id', $teacherId);
        }
        if ($dateFrom) {
            $resultsQuery->whereDate('consultation_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $resultsQuery->whereDate('consultation_date', '<=', $dateTo);
        }
        if ($q !== '') {
            $search = '%' . $q . '%';
            $resultsQuery->where(function ($sub) use ($search) {
                $sub->where('topic', 'like', $search)
                    ->orWhere('participant_names', 'like', $search)
                    ->orWhere('follow_up', 'like', $search)
                    ->orWhere('improvement_points', 'like', $search);
            });
        }
        $consultations = $resultsQuery->paginate(12, ['*'], 'hasil_page')->withQueryString();

        // 2. Query Jadwal Konsultasi
        $scheduleQuery = Consultation::with(['teacher', 'teacherMaterial', 'class'])
            ->latest('scheduled_at');

        if ($teacherId) {
            $scheduleQuery->where('teacher_id', $teacherId);
        }
        if ($status) {
            $scheduleQuery->where('status', $status);
        }
        if ($dateFrom) {
            $scheduleQuery->whereDate('scheduled_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $scheduleQuery->whereDate('scheduled_at', '<=', $dateTo);
        }
        $scheduledConsultations = $scheduleQuery->paginate(12, ['*'], 'jadwal_page')->withQueryString();

        // 3. Query Konsultasi Revisi Guru
        $revisionQuery = RevisionConsultation::with(['teacher.user', 'class', 'curriculum'])
            ->latest();

        if ($teacherId) {
            $revisionQuery->where('teacher_id', $teacherId);
        }
        if ($status) {
            $revisionQuery->where('status', $status);
        }
        $revisionConsultations = $revisionQuery->paginate(12, ['*'], 'revisi_page')->withQueryString();

        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get();
        $observations = TeacherObservation::with(['teacher', 'class'])->latest('observation_date')->get();

        return view('owner.konsultasi', compact(
            'consultations',
            'scheduledConsultations',
            'revisionConsultations',
            'teachers',
            'observations',
            'activeTab',
            'teacherId',
            'dateFrom',
            'dateTo',
            'status',
            'q'
        ));
    }

    /**
     * Owner membuat hasil konsultasi baru
     */
    public function store(StoreConsultationResultRequest $request)
    {
        Gate::authorize('create', ConsultationResult::class);

        $validated = $request->validated();

        $consultation = DB::transaction(function () use ($request, $validated) {
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('consultation-results', 'public');
            }

            $cleanedPoints = array_values(array_filter(array_map('trim', $validated['improvement_points'])));

            $res = ConsultationResult::create([
                'teacher_id'         => $validated['teacher_id'],
                'participant_names'  => !empty($validated['participant_names']) ? trim($validated['participant_names']) : null,
                'curriculum_user_id' => Auth::id(), // ID Owner
                'observation_id'     => !empty($validated['observation_id']) ? $validated['observation_id'] : null,
                'consultation_date'  => $validated['consultation_date'],
                'topic'              => trim($validated['topic']),
                'improvement_points' => $cleanedPoints,
                'follow_up'          => $validated['follow_up'] ?? null,
                'file_path'          => $filePath,
            ]);

            // Notifikasi ke Guru terkait
            $teacher = Teacher::with('user')->find($validated['teacher_id']);
            if ($teacher?->user) {
                $formattedDate = Carbon::parse($res->consultation_date)->format('d/m/Y');
                $teacher->user->notify(new AppNotification(
                    title: 'Hasil Konsultasi Baru dari Owner',
                    message: "Owner telah mencatat Hasil Konsultasi tanggal {$formattedDate} mengenai topik: \"{$res->topic}\". Silakan pelajari poin evaluasi dan tindak lanjut.",
                    url: url('/guru/hasil-konsultasi'),
                    type: 'konsultasi',
                    icon: 'fa-comments'
                ));
            }

            // Notifikasi ke Kurikulum
            $kurikulums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
            foreach ($kurikulums as $kur) {
                $kur->notify(new AppNotification(
                    title: 'Owner Mencatat Hasil Konsultasi Guru',
                    message: "Owner mencatat hasil konsultasi bersama {$teacher?->name} perihal \"{$res->topic}\".",
                    url: url('/kurikulum/hasil-konsultasi'),
                    type: 'konsultasi',
                    icon: 'fa-comments'
                ));
            }

            return $res;
        });

        return redirect()->route('owner.konsultasi.index', ['tab' => 'hasil'])
            ->with('success', 'Hasil konsultasi berhasil disimpan oleh Owner dan notifikasi telah dikirim ke pihak terkait.');
    }

    /**
     * Update hasil konsultasi
     */
    public function update(UpdateConsultationResultRequest $request, $id)
    {
        $consultation = ConsultationResult::findOrFail($id);
        Gate::authorize('update', $consultation);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $consultation, $validated) {
            $data = [
                'teacher_id'         => $validated['teacher_id'],
                'participant_names'  => !empty($validated['participant_names']) ? trim($validated['participant_names']) : null,
                'observation_id'     => !empty($validated['observation_id']) ? $validated['observation_id'] : null,
                'consultation_date'  => $validated['consultation_date'],
                'topic'              => trim($validated['topic']),
                'improvement_points' => array_values(array_filter(array_map('trim', $validated['improvement_points']))),
                'follow_up'          => $validated['follow_up'] ?? null,
            ];

            if ($request->hasFile('file')) {
                if ($consultation->file_path && Storage::disk('public')->exists($consultation->file_path)) {
                    Storage::disk('public')->delete($consultation->file_path);
                }
                $data['file_path'] = $request->file('file')->store('consultation-results', 'public');
            }

            $consultation->update($data);
        });

        return redirect()->route('owner.konsultasi.index', ['tab' => 'hasil'])
            ->with('success', 'Hasil konsultasi berhasil diperbarui.');
    }

    /**
     * Hapus hasil konsultasi
     */
    public function destroy($id)
    {
        $consultation = ConsultationResult::findOrFail($id);
        Gate::authorize('delete', $consultation);

        DB::transaction(function () use ($consultation) {
            if ($consultation->file_path && Storage::disk('public')->exists($consultation->file_path)) {
                Storage::disk('public')->delete($consultation->file_path);
            }

            $consultation->delete();
        });

        return redirect()->route('owner.konsultasi.index', ['tab' => 'hasil'])
            ->with('success', 'Hasil konsultasi berhasil dihapus.');
    }

    /**
     * Unduh lampiran hasil konsultasi
     */
    public function download($id)
    {
        $consultation = ConsultationResult::findOrFail($id);
        Gate::authorize('view', $consultation);

        abort_unless($consultation->file_path && Storage::disk('public')->exists($consultation->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($consultation->file_path, $consultation->file_name);
    }

    /**
     * Terima konsultasi revisi dari guru
     */
    public function acceptRevision(RevisionConsultation $revisionConsultation)
    {
        abort_unless(Auth::user()->level?->nama_level === 'Owner', 403);

        $revisionConsultation->update([
            'status'      => 'Accepted',
            'accepted_at' => now(),
        ]);

        if ($revisionConsultation->teacher?->user) {
            $revisionConsultation->teacher->user->notify(new AppNotification(
                title: 'Permintaan Revisi Diterima Owner',
                message: "Owner telah menerima permintaan konsultasi revisi Anda untuk kelas {$revisionConsultation->class?->class_name}.",
                url: url('/guru/hasil-konsultasi'),
                type: 'konsultasi',
                icon: 'fa-check'
            ));
        }

        return back()->with('success', 'Permintaan konsultasi revisi berhasil diterima.');
    }

    /**
     * Selesaikan konsultasi revisi guru
     */
    public function completeRevision(Request $request, RevisionConsultation $revisionConsultation)
    {
        abort_unless(Auth::user()->level?->nama_level === 'Owner', 403);

        $request->validate([
            'curriculum_reply' => ['required', 'string', 'max:1000'],
        ]);

        $revisionConsultation->update([
            'status'           => 'Completed',
            'curriculum_reply' => $request->curriculum_reply,
            'completed_at'     => now(),
        ]);

        if ($revisionConsultation->teacher?->user) {
            $revisionConsultation->teacher->user->notify(new AppNotification(
                title: 'Konsultasi Revisi Selesai (Owner)',
                message: "Owner telah menyelesaikan konsultasi revisi Anda dengan catatan: \"{$request->curriculum_reply}\".",
                url: url('/guru/hasil-konsultasi'),
                type: 'konsultasi',
                icon: 'fa-check-double'
            ));
        }

        return back()->with('success', 'Konsultasi revisi telah diselesaikan.');
    }

    /**
     * Unduh lampiran konsultasi revisi
     */
    public function downloadRevision(RevisionConsultation $revisionConsultation)
    {
        abort_unless($revisionConsultation->attachment_path && Storage::disk('public')->exists($revisionConsultation->attachment_path), 404, 'File lampiran revisi tidak ditemukan.');

        return Storage::disk('public')->download($revisionConsultation->attachment_path, $revisionConsultation->attachment_name);
    }
}

