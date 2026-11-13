<?php
 
namespace App\Http\Controllers\Guru;
 
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Services\ProgressReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
 
class CertificateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Sertifikat Les Mandarin oleh Guru
    |--------------------------------------------------------------------------
    | - Nilai Listening, Reading, Speaking, Writing + tanggal terisi OTOMATIS
    |   dari progress report / jurnal, guru boleh mengubahnya.
    | - Nilai akhir = rata-rata keempat nilai.
    | - Tanda tangan: Kepala Kurikulum + Owner.
    | - Setiap tambah / edit dikirim ke Review Pengajuan Kurikulum (Pending).
    |--------------------------------------------------------------------------
    */
 
    /** Nilai minimal untuk dinyatakan memenuhi standar kelulusan. */
    private const PASSING_SCORE = 60;
 
    private const SKILLS = ['listening', 'reading', 'speaking', 'writing'];
 
    private function teacher()
    {
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Akun Anda belum terhubung dengan data guru.');
 
        return $teacher;
    }
 
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
 
    private function rules(bool $withEnrollment = false): array
    {
        $rules = [
            'course_name'     => ['required', 'string', 'max:150'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['required', 'date', 'after_or_equal:start_date'],
            'listening_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'reading_score'   => ['required', 'numeric', 'min:0', 'max:100'],
            'speaking_score'  => ['required', 'numeric', 'min:0', 'max:100'],
            'writing_score'   => ['required', 'numeric', 'min:0', 'max:100'],
        ];
 
        if ($withEnrollment) {
            $rules = ['enrollment_id' => ['required', 'integer']] + $rules;
        }
 
        return $rules;
    }
 
    /** Nilai akhir = rata-rata 4 keterampilan. */
    private function average(array $data): float
    {
        $sum = 0;
        foreach (self::SKILLS as $s) {
            $sum += (float) $data[$s . '_score'];
        }
 
        return round($sum / count(self::SKILLS), 1);
    }
 
    /** Simpan data, kembalikan ke Pending, lalu buat ulang PDF. */
    private function persist(Certificate $certificate, object $e, array $data): void
    {
        $certificate->fill([
            'enrollment_id'      => $e->id,
            'student_id'         => $e->student_id,
            'teacher_id'         => $this->teacher()->id,
            'certificate_number' => $certificate->certificate_number
                ?: 'HY/LES/' . now()->format('Y') . '/' . str_pad($e->id, 4, '0', STR_PAD_LEFT),
            'verification_code'  => $certificate->verification_code ?: Str::random(32),
            'course_name'        => $data['course_name'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'listening_score'    => $data['listening_score'],
            'reading_score'      => $data['reading_score'],
            'speaking_score'     => $data['speaking_score'],
            'writing_score'      => $data['writing_score'],
            'score'              => $this->average($data),
            'status'             => 'Pending',
            'review_note'        => null,
            'reviewed_by'        => null,
            'reviewed_at'        => null,
        ])->save();
 
        $this->render($certificate, $e);
    }
 
    private function render(Certificate $certificate, object $e): void
    {
        $owner = User::whereHas('level', fn ($q) => $q->where('nama_level', 'like', '%wner%'))->first();
        $kurikulum = User::whereHas('level', fn ($q) => $q->where('nama_level', 'like', '%urikulum%')
                ->orWhere('nama_level', 'like', '%urriculum%'))->first();
 
        $pdf = Pdf::loadView('guru.certificate-pdf', [
            'e'      => $e,
            'number' => $certificate->certificate_number,
            'cert'   => [
                'course'    => $certificate->course_name ?: $e->class_name,
                'start'     => $certificate->start_date,
                'end'       => $certificate->end_date,
                'listening' => $certificate->listening_score,
                'reading'   => $certificate->reading_score,
                'speaking'  => $certificate->speaking_score,
                'writing'   => $certificate->writing_score,
                'score'     => $certificate->score,
                'passing'   => self::PASSING_SCORE,
            ],
            'owner' => [
                'name'      => $owner?->name ?: 'Joy Limmunandar',
                'signature' => $certificate->owner_signature_path ?: $owner?->signature_path,
            ],
            'kurikulum' => [
                'name'      => $kurikulum?->name ?: '',
                'signature' => $certificate->curriculum_signature_path ?: $kurikulum?->signature_path,
            ],
        ])->setPaper('a4', 'landscape');
 
        $path = "certificates/certificate-{$certificate->id}.pdf";
        Storage::disk('public')->put($path, $pdf->output());
 
        $certificate->update(['file_path' => $path]);
    }
 
    /** Isian otomatis dari ProgressReportService (key yang tidak ada dibiarkan kosong). */
    private function autoFill(object $e): array
    {
        $auto = ProgressReportService::certificateData($e);
 
        return [
            'course'    => $e->class_name,
            'start'     => $auto['start']->toDateString(),
            'end'       => $auto['end']->toDateString(),
            'listening' => $auto['listening'] ?? null,
            'reading'   => $auto['reading'] ?? null,
            'speaking'  => $auto['speaking'] ?? null,
            'writing'   => $auto['writing'] ?? null,
        ];
    }
 
    // ------------------------------------------------------------------ HALAMAN
 
    public function index()
    {
        $teacher = $this->teacher();
 
        $certificates = Certificate::with(['student', 'enrollment.class'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();
 
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
 
        $prefill = [];
        foreach ($enrollments as $e) {
            $prefill[$e->id] = $this->autoFill($e);
        }
 
        return view('guru.sertifikat', compact('certificates', 'enrollments', 'prefill'));
    }
 
    // ------------------------------------------------------------------ TAMBAH
 
    public function store(Request $request)
    {
        $data = $request->validate($this->rules(true));
 
        $e = $this->myEnrollment((int) $data['enrollment_id']);
 
        if (Certificate::where('enrollment_id', $e->id)->exists()) {
            return back()->withInput()->with('error', 'Siswa ini sudah punya sertifikat. Edit sertifikat yang ada.');
        }
 
        $this->persist(new Certificate(), $e, $data);
 
        return back()->with('success', 'Sertifikat dibuat dan dikirim ke Kurikulum untuk di-ACC.');
    }
 
    /**
     * Tombol cepat di halaman Progress Report.
     * Hanya jika kelas selesai, report di-ACC, dan keempat nilai tersedia.
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
 
        $auto = $this->autoFill($e);
 
        foreach (self::SKILLS as $s) {
            if ($auto[$s] === null || $auto[$s] === '') {
                return back()->with('error', 'Nilai ' . ucfirst($s) . ' belum tersedia di progress report. Buat sertifikat manual di menu Sertifikat.');
            }
        }
 
        $this->persist($certificate ?? new Certificate(), $e, [
            'course_name'     => $certificate->course_name ?? $e->class_name,
            'start_date'      => $auto['start'],
            'end_date'        => $auto['end'],
            'listening_score' => $auto['listening'],
            'reading_score'   => $auto['reading'],
            'speaking_score'  => $auto['speaking'],
            'writing_score'   => $auto['writing'],
        ]);
 
        return back()->with('success', 'Sertifikat dibuat dan dikirim ke Kurikulum untuk di-ACC.');
    }
 
    // ------------------------------------------------------------------ EDIT
 
    public function update(Request $request, Certificate $certificate)
    {
        $this->ownCertificate($certificate);
 
        $data = $request->validate($this->rules());
 
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
 