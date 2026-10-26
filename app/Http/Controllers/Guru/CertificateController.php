<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\ProgressReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Sertifikat oleh Guru
    |--------------------------------------------------------------------------
    | - Tanggal mulai-selesai dan nilai terisi OTOMATIS dari jurnal pertemuan
      dan progress report, tetapi guru boleh mengubahnya.
    | - Guru bisa menambah, mengedit, menghapus, dan mengunduh sertifikat.
    | - Setiap tambah / edit mengirim sertifikat ke Review Pengajuan Kurikulum
    |   dengan status Pending. Siswa baru bisa melihatnya setelah Approved.
    |--------------------------------------------------------------------------
    */

    private function teacher()
    {
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        return $teacher;
    }

    /** Baris pendaftaran + nama siswa & kelas (tanpa cek kepemilikan). */
    private function enrollmentRow(int $id): object
    {
        $e = DB::table('class_enrollments as ce')
            ->join('classes as c', 'c.id', '=', 'ce.class_id')
            ->join('students as s', 's.id', '=', 'ce.student_id')
            ->where('ce.id', $id)
            ->select('ce.*', 'c.class_name', 'c.teacher_id as class_teacher_id', 's.name as student_name')
            ->first();

        abort_if(!$e, 404);

        return $e;
    }

    /** Pendaftaran di kelas milik guru ini, atau 404. */
    private function myEnrollment(int $id): object
    {
        $e = $this->enrollmentRow($id);
        abort_if((int) $e->class_teacher_id !== (int) $this->teacher()->id, 404);

        return $e;
    }

    private function ownCertificate(Certificate $certificate): void
    {
        abort_if((int) $certificate->teacher_id !== (int) $this->teacher()->id, 403);
    }

    /** Simpan data sertifikat, kembalikan ke status Pending, lalu buat ulang PDF-nya. */
    private function persist(Certificate $certificate, object $e, array $data): void
    {
        $certificate->fill([
            'enrollment_id'      => $e->id,
            'student_id'         => $e->student_id,
            'teacher_id'         => $this->teacher()->id,
            'certificate_number' => $certificate->certificate_number
                ?: 'HYE/' . now()->format('Y') . '/' . str_pad($e->id, 4, '0', STR_PAD_LEFT),
            'course_name'        => $data['course_name'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'score'              => $data['score'] ?? null,
            'status'             => 'Pending',
            'review_note'        => null,
            'reviewed_by'        => null,
            'reviewed_at'        => null,
        ])->save();

        $this->render($certificate, $e);
    }

    private function render(Certificate $certificate, object $e): void
    {
        $pdf = Pdf::loadView('guru.certificate-pdf', [
            'e'       => $e,
            'teacher' => $this->teacher(),
            'cert'    => [
                'start'  => $certificate->start_date,
                'end'    => $certificate->end_date,
                'score'  => $certificate->score,
                'course' => $certificate->course_name,
            ],
            'number'  => $certificate->certificate_number,
        ])->setPaper('a4', 'landscape');

        $path = "certificates/certificate-{$certificate->id}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $certificate->update(['file_path' => $path]);
    }

    // ------------------------------------------------------------------ HALAMAN

    public function index()
    {
        $teacher = $this->teacher();

        $certificates = Certificate::with(['student', 'enrollment.class'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();

        // Siswa di kelas guru ini (kelas sudah diterima) yang belum punya sertifikat
        $enrollments = DB::table('class_enrollments as ce')
            ->join('classes as c', 'c.id', '=', 'ce.class_id')
            ->join('students as s', 's.id', '=', 'ce.student_id')
            ->where('c.teacher_id', $teacher->id)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('ce.status', ['Active', 'Completed'])
            ->whereNotIn('ce.id', $certificates->pluck('enrollment_id')->all())
            ->select('ce.*', 'c.class_name', 's.name as student_name')
            ->orderBy('c.class_name')
            ->orderBy('s.name')
            ->get();

        // Isian otomatis: tanggal mulai-selesai & nilai dari data les siswa
        $prefill = [];
        foreach ($enrollments as $e) {
            $auto = ProgressReportService::certificateData($e);

            $prefill[$e->id] = [
                'course' => $e->class_name,
                'start'  => $auto['start']->toDateString(),
                'end'    => $auto['end']->toDateString(),
                'score'  => $auto['score'],
            ];
        }

        return view('guru.sertifikat', compact('certificates', 'enrollments', 'prefill'));
    }

    // ------------------------------------------------------------------ TAMBAH

    public function store(Request $request)
    {
        $data = $request->validate([
            'enrollment_id' => ['required', 'integer'],
            'course_name'   => ['required', 'string', 'max:150'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'score'         => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $e = $this->myEnrollment((int) $data['enrollment_id']);

        if (Certificate::where('enrollment_id', $e->id)->exists()) {
            return back()->withInput()->with('error', 'Siswa ini sudah punya sertifikat. Edit sertifikat yang ada.');
        }

        $this->persist(new Certificate(), $e, $data);

        return back()->with('success', 'Sertifikat dibuat dan dikirim ke Kurikulum untuk di-ACC.');
    }

    /**
     * Tombol cepat di halaman Progress Report: buat sertifikat 100% otomatis.
     * Hanya untuk siswa yang kelasnya selesai dan progress report-nya sudah di-ACC.
     */
    public function quickStore(int $enrollment)
    {
        $e = $this->myEnrollment($enrollment);

        if (!ProgressReportService::certificateEligible($e)) {
            return back()->with('error', 'Sertifikat otomatis bisa dibuat setelah kelas selesai dan progress report disetujui Kurikulum. Untuk kasus lain, tambahkan manual di menu Sertifikat.');
        }

        $certificate = Certificate::where('enrollment_id', $e->id)->first();

        if ($certificate && $certificate->status === 'Approved') {
            return back()->with('error', 'Sertifikat siswa ini sudah disetujui Kurikulum.');
        }

        if ($certificate && $certificate->status === 'Pending') {
            return back()->with('error', 'Sertifikat sudah diajukan dan sedang menunggu Kurikulum.');
        }

        $auto = ProgressReportService::certificateData($e);

        $this->persist($certificate ?? new Certificate(), $e, [
            'course_name' => $certificate->course_name ?? $e->class_name,
            'start_date'  => $auto['start']->toDateString(),
            'end_date'    => $auto['end']->toDateString(),
            'score'       => $auto['score'],
        ]);

        return back()->with('success', 'Sertifikat dibuat dan dikirim ke Kurikulum untuk di-ACC.');
    }

    // ------------------------------------------------------------------ EDIT

    public function update(Request $request, Certificate $certificate)
    {
        $this->ownCertificate($certificate);

        $data = $request->validate([
            'course_name' => ['required', 'string', 'max:150'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'score'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $e = $this->enrollmentRow((int) $certificate->enrollment_id);

        $this->persist($certificate, $e, $data);

        return back()->with('success', 'Sertifikat diperbarui dan dikirim ulang ke Kurikulum untuk di-ACC.');
    }

    // ------------------------------------------------------------------ HAPUS

    public function destroy(Certificate $certificate)
    {
        $this->ownCertificate($certificate);

        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            Storage::disk('public')->delete($certificate->file_path);
        }

        $certificate->delete();

        return back()->with('success', 'Sertifikat dihapus.');
    }

    // ------------------------------------------------------------------ UNDUH

    /** Guru mengunduh sertifikat yang dia buat. */
    public function download(Certificate $certificate)
    {
        $this->ownCertificate($certificate);
        abort_if(!$certificate->file_path, 404, 'File sertifikat belum dibuat.');

        $path = storage_path('app/public/' . $certificate->file_path);
        abort_unless(file_exists($path), 404, 'File sertifikat tidak ditemukan.');

        $name = 'Sertifikat_' . str_replace(' ', '_', optional($certificate->student)->name) . '.pdf';

        return response()->download($path, $name);
    }
}