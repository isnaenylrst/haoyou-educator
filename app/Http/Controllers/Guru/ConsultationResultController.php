<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ConsultationResult;
use App\Models\RevisionConsultation;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConsultationResultController extends Controller
{
    /**
     * Tampilkan hasil konsultasi dari Kurikulum & daftar pengajuan konsultasi revisi
     */
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Profil guru belum terhubung dengan akun Anda.');

        $activeTab = $request->query('tab', 'hasil');

        // 1. DATA HASIL KONSULTASI DARI KURIKULUM (READ-ONLY)
        $queryHasil = ConsultationResult::where('teacher_id', $teacher->id)
            ->with(['curriculumUser', 'observation.class'])
            ->latest('consultation_date');

        if ($request->filled('date_from')) {
            $queryHasil->whereDate('consultation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $queryHasil->whereDate('consultation_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $queryHasil->where(function ($q) use ($search) {
                $q->where('topic', 'like', $search)
                  ->orWhere('follow_up', 'like', $search)
                  ->orWhere('improvement_points', 'like', $search);
            });
        }

        $consultations = $queryHasil->paginate(10, ['*'], 'hasil_page')->withQueryString();

        // 2. DATA KONSULTASI REVISI GURU (DOKUMEN: MATERI, PROGRESS REPORT, LP, PPT)
        $revisionConsultations = RevisionConsultation::where('teacher_id', $teacher->id)
            ->with(['class', 'curriculum'])
            ->latest()
            ->paginate(10, ['*'], 'revisi_page')
            ->withQueryString();

        // Kelas aktif guru ini untuk dropdown form
        $myClasses = ClassModel::where('teacher_id', $teacher->id)
            ->where('teacher_response', 'Accepted')
            ->orderBy('class_name')
            ->get(['id', 'class_name']);

        return view('guru.hasil-konsultasi', compact(
            'consultations',
            'revisionConsultations',
            'myClasses',
            'teacher',
            'activeTab'
        ));
    }

    /**
     * Guru mengajukan konsultasi revisi (Materi, Progress Report, LP, PPT)
     */
    public function storeRevision(Request $request)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Profil guru belum terhubung dengan akun Anda.');

        $validated = $request->validate([
            'class_id'      => ['nullable', 'exists:classes,id'],
            'document_type' => ['required', Rule::in(['Materi', 'Progress Report', 'LP', 'PPT'])],
            'obstacle'      => ['required', 'string', 'min:10', 'max:3000'],
            'attachment'    => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:20480'],
        ], [
            'document_type.required' => 'Pilih jenis dokumen yang ingin dikonsultasikan.',
            'obstacle.required'      => 'Jelaskan kendala atau pertanyaan revisi yang Anda hadapi.',
            'obstacle.min'           => 'Deskripsi kendala minimal 10 karakter.',
            'attachment.max'         => 'Ukuran lampiran maksimal 20 MB.',
        ]);

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('revision-consultations', 'public');
        }

        $class = !empty($validated['class_id']) ? ClassModel::find($validated['class_id']) : null;

        $consultation = RevisionConsultation::create([
            'teacher_id'      => $teacher->id,
            'class_id'        => $class?->id,
            'document_type'   => $validated['document_type'],
            'obstacle'        => $validated['obstacle'],
            'attachment_path' => $filePath,
            'attachment_name' => $fileName,
            'status'          => 'Menunggu',
        ]);

        // Notifikasi ke seluruh tim Kurikulum
        $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
        foreach ($curriculums as $cUser) {
            $className = $class ? " ({$class->class_name})" : '';
            $cUser->notify(new AppNotification(
                title: "Pengajuan Konsultasi Revisi: {$validated['document_type']}",
                message: "Guru {$teacher->name} mengajukan konsultasi revisi {$validated['document_type']}{$className}. Kendala: " . \Illuminate\Support\Str::limit($validated['obstacle'], 100),
                url: url('/kurikulum/hasil-konsultasi?tab=revisi'),
                type: 'konsultasi_revisi',
                icon: 'fa-comments'
            ));
        }

        return redirect()->route('teacher.hasil-konsultasi', ['tab' => 'revisi'])
            ->with('success', 'Pengajuan konsultasi revisi berhasil dikirim ke Kurikulum. Notifikasi telah terkirim.');
    }

    /**
     * Unduh lampiran berkas hasil konsultasi Kurikulum
     */
    public function download($id)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Akses tidak diizinkan.');

        $consultation = ConsultationResult::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        abort_unless($consultation->file_path && Storage::disk('public')->exists($consultation->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($consultation->file_path, $consultation->file_name);
    }

    /**
     * Unduh lampiran berkas konsultasi revisi
     */
    public function downloadRevisionAttachment($id)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Akses tidak diizinkan.');

        $rev = RevisionConsultation::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        abort_unless($rev->attachment_path && Storage::disk('public')->exists($rev->attachment_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($rev->attachment_path, $rev->attachment_name ?: basename($rev->attachment_path));
    }
}
