<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ConsultationResult;
use App\Models\RevisionConsultation;
use App\Models\Teacher;
use App\Models\TeacherObservation;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConsultationResultController extends Controller
{
    /**
     * Tampilkan riwayat hasil konsultasi kurikulum
     */
    public function index(Request $request)
    {
        $query = ConsultationResult::with(['teacher.user', 'curriculumUser', 'observation.class'])
            ->latest('consultation_date');

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('consultation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('consultation_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('topic', 'like', $search)
                  ->orWhere('follow_up', 'like', $search)
                  ->orWhere('improvement_points', 'like', $search);
            });
        }

        $consultations = $query->paginate(10)->withQueryString();
        $teachers = Teacher::orderBy('name')->get();
        $observations = TeacherObservation::with(['teacher', 'class'])->latest('observation_date')->get();

        $revisionConsultations = RevisionConsultation::with(['teacher.user', 'class', 'curriculum'])
            ->latest()
            ->paginate(10, ['*'], 'revisi_page')
            ->withQueryString();

        $activeTab = $request->query('tab', 'hasil');

        return view('kurikulum.hasil-konsultasi', compact('consultations', 'teachers', 'observations', 'revisionConsultations', 'activeTab'));
    }

    /**
     * Simpan hasil konsultasi baru dan kirim notifikasi ke guru terkait
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id'           => ['required', 'exists:teachers,id'],
            'consultation_date'    => ['required', 'date'],
            'topic'                => ['required', 'string', 'max:255'],
            'improvement_points'   => ['required', 'array', 'min:1'],
            'improvement_points.*' => ['required', 'string', 'max:500'],
            'follow_up'            => ['nullable', 'string'],
            'observation_id'       => ['nullable', 'exists:teacher_observations,id'],
            'file'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:20480'],
        ], [
            'teacher_id.required'           => 'Pilih guru yang berkonsultasi.',
            'consultation_date.required'    => 'Tanggal konsultasi wajib diisi.',
            'topic.required'                => 'Topik konsultasi wajib diisi.',
            'improvement_points.required'   => 'Minimal masukkan satu poin yang perlu diperbaiki.',
            'improvement_points.min'        => 'Minimal masukkan satu poin yang perlu diperbaiki.',
            'file.max'                      => 'Ukuran file lampiran maksimal 20 MB.',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('consultation-results', 'public');
        }

        // Bersihkan poin perbaikan kosong
        $cleanedPoints = array_values(array_filter(array_map('trim', $validated['improvement_points'])));

        $consultation = ConsultationResult::create([
            'teacher_id'         => $validated['teacher_id'],
            'curriculum_user_id' => Auth::id(),
            'observation_id'     => $validated['observation_id'] ?: null,
            'consultation_date'  => $validated['consultation_date'],
            'topic'              => trim($validated['topic']),
            'improvement_points' => $cleanedPoints,
            'follow_up'          => $validated['follow_up'] ?? null,
            'file_path'          => $filePath,
        ]);

        // Kirim notifikasi ke Guru terkait
        $teacher = Teacher::with('user')->find($validated['teacher_id']);
        if ($teacher?->user) {
            $formattedDate = Carbon::parse($consultation->consultation_date)->format('d/m/Y');
            $teacher->user->notify(new AppNotification(
                title: 'Hasil Konsultasi Kurikulum Baru',
                message: "Kurikulum telah mencatat Hasil Konsultasi tanggal {$formattedDate} mengenai topik: \"{$consultation->topic}\". Silakan periksa poin perbaikan dan tindak lanjutnya.",
                url: url('/guru/hasil-konsultasi'),
                type: 'konsultasi',
                icon: 'fa-comments'
            ));
        }

        return redirect()->route('kurikulum.hasil-konsultasi.index')
            ->with('success', 'Hasil konsultasi berhasil disimpan dan notifikasi telah dikirim ke guru.');
    }

    /**
     * Perbarui data hasil konsultasi
     */
    public function update(Request $request, $id)
    {
        $consultation = ConsultationResult::findOrFail($id);

        $validated = $request->validate([
            'teacher_id'           => ['required', 'exists:teachers,id'],
            'consultation_date'    => ['required', 'date'],
            'topic'                => ['required', 'string', 'max:255'],
            'improvement_points'   => ['required', 'array', 'min:1'],
            'improvement_points.*' => ['required', 'string', 'max:500'],
            'follow_up'            => ['nullable', 'string'],
            'observation_id'       => ['nullable', 'exists:teacher_observations,id'],
            'file'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:20480'],
        ]);

        $data = [
            'teacher_id'         => $validated['teacher_id'],
            'observation_id'     => $validated['observation_id'] ?: null,
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

        return redirect()->route('kurikulum.hasil-konsultasi.index')
            ->with('success', 'Hasil konsultasi berhasil diperbarui.');
    }

    /**
     * Hapus data hasil konsultasi
     */
    public function destroy($id)
    {
        $consultation = ConsultationResult::findOrFail($id);

        if ($consultation->file_path && Storage::disk('public')->exists($consultation->file_path)) {
            Storage::disk('public')->delete($consultation->file_path);
        }

        $consultation->delete();

        return redirect()->route('kurikulum.hasil-konsultasi.index')
            ->with('success', 'Hasil konsultasi berhasil dihapus.');
    }

    /**
     * Unduh file lampiran konsultasi
     */
    public function download($id)
    {
        $consultation = ConsultationResult::findOrFail($id);

        abort_unless($consultation->file_path && Storage::disk('public')->exists($consultation->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($consultation->file_path, $consultation->file_name);
    }

    /**
     * Endpoint API JSON untuk mengambil daftar observasi berdasarkan guru yang dipilih
     */
    public function getTeacherObservations(Teacher $teacher)
    {
        $observations = TeacherObservation::where('teacher_id', $teacher->id)
            ->with('class')
            ->latest('observation_date')
            ->get()
            ->map(fn ($o) => [
                'id'               => $o->id,
                'date'             => $o->observation_date->format('d/m/Y'),
                'class_name'       => $o->class?->class_name ?? 'Kelas tidak spesifik',
                'sop_compliance'   => $o->sop_compliance,
            ]);

        return response()->json($observations);
    }

    /**
     * Kurikulum menerima konsultasi revisi dari guru
     */
    public function acceptRevision(RevisionConsultation $revisionConsultation)
    {
        $revisionConsultation->update([
            'status'         => 'Diterima',
            'curriculum_id'  => Auth::id(),
            'accepted_at'    => now(),
        ]);

        if ($revisionConsultation->teacher?->user) {
            $revisionConsultation->teacher->user->notify(new AppNotification(
                title: "Konsultasi Revisi Diterima Kurikulum",
                message: "Permintaan konsultasi revisi {$revisionConsultation->document_type} Anda telah diterima Kurikulum dan sedang diproses.",
                url: url('/guru/hasil-konsultasi?tab=revisi'),
                type: 'konsultasi_revisi',
                icon: 'fa-comments'
            ));
        }

        return back()->with('success', 'Permintaan konsultasi revisi telah diterima.');
    }

    /**
     * Kurikulum memberikan balasan / solusi dan menandai konsultasi revisi selesai
     */
    public function completeRevision(Request $request, RevisionConsultation $revisionConsultation)
    {
        $request->validate([
            'curriculum_reply' => ['required', 'string', 'max:3000'],
        ], [
            'curriculum_reply.required' => 'Balasan arahan / solusi dari Kurikulum wajib diisi.',
        ]);

        $revisionConsultation->update([
            'status'           => 'Selesai',
            'curriculum_id'    => Auth::id(),
            'curriculum_reply' => $request->curriculum_reply,
            'completed_at'     => now(),
        ]);

        if ($revisionConsultation->teacher?->user) {
            $revisionConsultation->teacher->user->notify(new AppNotification(
                title: "Konsultasi Revisi Selesai: {$revisionConsultation->document_type}",
                message: "Kurikulum telah memberikan balasan & solusi untuk konsultasi revisi {$revisionConsultation->document_type}. Klik untuk membaca arahan.",
                url: url('/guru/hasil-konsultasi?tab=revisi'),
                type: 'konsultasi_revisi',
                icon: 'fa-circle-check'
            ));
        }

        return back()->with('success', 'Konsultasi revisi berhasil diselesaikan dan balasan telah terkirim ke Guru.');
    }

    /**
     * Unduh lampiran dari pengajuan konsultasi revisi guru
     */
    public function downloadRevision(RevisionConsultation $revisionConsultation)
    {
        abort_unless($revisionConsultation->attachment_path && Storage::disk('public')->exists($revisionConsultation->attachment_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download(
            $revisionConsultation->attachment_path,
            $revisionConsultation->attachment_name ?: basename($revisionConsultation->attachment_path)
        );
    }
}

