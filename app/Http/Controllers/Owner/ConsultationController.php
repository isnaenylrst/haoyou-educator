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

        // 4. Slot Available Owner & Grid Mingguan (Owner melihat slot sendiri & slot Kurikulum)
        $weekParam = $request->query('week');
        try {
            $startOfWeek = $weekParam ? Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY) : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            $startOfWeek = now()->startOfWeek(Carbon::MONDAY);
        }
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        $weekSlots = \App\Models\CurriculumConsultationSlot::with(['curriculumUser', 'teacher', 'class', 'subBookings.teacher'])
            ->whereNull('parent_slot_id')
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->where('status', '!=', 'Cancelled')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $consultationGrid = \App\Services\ConsultationSlotGridService::buildWeeklyGrid(
            startOfWeek: $startOfWeek,
            slots: $weekSlots,
            viewerUserId: Auth::id(),
            viewerRole: 'Owner'
        );

        // 5. Daftar Pengajuan Guru khusus ke Owner (status: Pending/Confirmed/Rejected)
        $ownerBookingRequests = \App\Models\CurriculumConsultationSlot::with(['teacher', 'class'])
            ->where('owner_role', 'Owner')
            ->whereNotNull('booked_by_teacher_id')
            ->when($status, function ($q) use ($status) {
                $q->where('teacher_booking_status', $status);
            })
            ->latest('booked_at')
            ->paginate(10, ['*'], 'pengajuan_page');

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
            'q',
            'consultationGrid',
            'ownerBookingRequests'
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

    // ------------------------------------------------------------------ SLOT AVAILABLE OWNER

    /**
     * Owner menambah slot available.
     */
    public function storeSlot(Request $request)
    {
        $data = $request->validate([
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        $startTime = substr($data['start_time'], 0, 5);
        $endTime   = substr($data['end_time'], 0, 5);

        // Cegah tabrakan slot pada Owner yang sama
        $overlap = \App\Models\CurriculumConsultationSlot::where('curriculum_user_id', $userId)
            ->whereDate('date', $data['date'])
            ->where('status', '!=', 'Cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q1) use ($startTime, $endTime) {
                    $q1->where('start_time', '<', $endTime)
                       ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        if ($overlap) {
            return back()->withInput()->withErrors([
                'start_time' => 'Anda sudah memiliki slot konsultasi aktif pada rentang jam tersebut.',
            ]);
        }

        \App\Models\CurriculumConsultationSlot::create([
            'curriculum_user_id' => $userId,
            'owner_role'         => 'Owner',
            'date'               => $data['date'],
            'start_time'         => $data['start_time'],
            'end_time'           => $data['end_time'],
            'notes'              => $data['notes'] ?? null,
            'status'             => 'Available',
        ]);

        return redirect()->route('owner.konsultasi.index', ['tab' => 'slot'])
            ->with('success', 'Slot konsultasi Owner berhasil ditambahkan.');
    }

    /**
     * Owner mengedit slot miliknya yang belum dipesan.
     */
    public function updateSlot(Request $request, \App\Models\CurriculumConsultationSlot $slot)
    {
        // Owner tidak boleh mengubah slot Kurikulum atau slot milik user lain
        if ($slot->effective_role !== 'Owner' || $slot->curriculum_user_id != Auth::id()) {
            return back()->with('error', 'Anda tidak memiliki hak untuk mengubah slot ini.');
        }

        if ($slot->status === 'Booked') {
            return back()->with('error', 'Slot yang sudah dipesan tidak dapat diubah jamnya secara langsung.');
        }

        $data = $request->validate([
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'notes'      => ['nullable', 'string', 'max:500'],
            'status'     => ['required', 'in:Available,Cancelled'],
        ]);

        $slot->update($data);

        return redirect()->route('owner.konsultasi.index', ['tab' => 'slot'])
            ->with('success', 'Slot konsultasi Owner berhasil diperbarui.');
    }

    /**
     * Owner menghapus slot miliknya.
     */
    public function destroySlot(\App\Models\CurriculumConsultationSlot $slot)
    {
        if ($slot->effective_role !== 'Owner' || $slot->curriculum_user_id != Auth::id()) {
            return back()->with('error', 'Anda tidak memiliki hak untuk menghapus slot ini.');
        }

        if ($slot->status === 'Booked') {
            return back()->with('error', 'Slot yang sudah dipesan tidak boleh dihapus. Batalkan konfirmasinya terlebih dahulu.');
        }

        $slot->delete();

        return redirect()->route('owner.konsultasi.index', ['tab' => 'slot'])
            ->with('success', 'Slot konsultasi Owner berhasil dihapus.');
    }

    /**
     * Owner mengonfirmasi atau menolak pengajuan konsultasi dari Guru.
     */
    public function confirmBooking(Request $request, \App\Models\CurriculumConsultationSlot $slot)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:confirm,reject'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($slot, $validated) {
            $lockedSlot = \App\Models\CurriculumConsultationSlot::where('id', $slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->is_past) {
                return back()->with('error', 'Slot konsultasi ini sudah lewat batas waktunya.');
            }

            $teacher = $lockedSlot->teacher;

            if ($validated['action'] === 'confirm') {
                $lockedSlot->update([
                    'status'                 => 'Booked',
                    'teacher_booking_status' => 'Confirmed',
                    'booking_notes'          => $validated['notes'] ? ($lockedSlot->booking_notes . "\nCatatan Owner: " . $validated['notes']) : $lockedSlot->booking_notes,
                ]);

                // Buat entri jadwal di consultations
                Consultation::create([
                    'teacher_id'   => $lockedSlot->booked_by_teacher_id,
                    'class_id'     => $lockedSlot->class_id,
                    'type'         => 'Evaluasi',
                    'scheduled_at' => Carbon::parse($lockedSlot->date->toDateString() . ' ' . $lockedSlot->start_time),
                    'status'       => 'Terjadwal',
                    'notes'        => 'Dipesan via Slot Konsultasi Owner. Topik: ' . ($lockedSlot->topic ?? '-') . '. ' . ($validated['notes'] ?? ''),
                    'created_by'   => Auth::id(),
                ]);

                // Notifikasi ke Guru
                if ($teacher?->user) {
                    $teacher->user->notify(new AppNotification(
                        title: 'Pengajuan Konsultasi Dikonfirmasi Owner',
                        message: "Pengajuan konsultasi Anda untuk tanggal {$lockedSlot->date->translatedFormat('d M Y')} jam " . substr($lockedSlot->start_time, 0, 5) . " telah dikonfirmasi oleh Owner.",
                        url: url('/guru/jadwal-konsultasi'),
                        type: 'konsultasi',
                        icon: 'fa-calendar-check'
                    ));
                }

                $msg = 'Pengajuan konsultasi guru berhasil dikonfirmasi.';
            } else {
                $prevTeacherId = $lockedSlot->booked_by_teacher_id;
                $lockedSlot->update([
                    'status'                 => 'Available',
                    'teacher_booking_status' => 'Rejected',
                    'booked_by_teacher_id'   => null,
                    'class_id'               => null,
                    'booking_notes'          => $validated['notes'] ? "Ditolak: " . $validated['notes'] : null,
                ]);

                // Notifikasi penolakan ke guru
                if ($teacher?->user) {
                    $teacher->user->notify(new AppNotification(
                        title: 'Pengajuan Konsultasi Ditolak Owner',
                        message: "Pengajuan konsultasi Anda untuk tanggal {$lockedSlot->date->translatedFormat('d M Y')} ditolak oleh Owner. Catatan: " . ($validated['notes'] ?? '-'),
                        url: url('/guru/jadwal-konsultasi'),
                        type: 'konsultasi',
                        icon: 'fa-circle-xmark'
                    ));
                }

                $msg = 'Pengajuan booking slot ditolak. Slot kini kembali tersedia.';
            }

            return redirect()->route('owner.konsultasi.index', ['tab' => 'pengajuan'])->with('success', $msg);
        });
    }
}

