<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateRule;
use App\Models\ProgramLevel;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    /** students.id dari user yang login. */
    protected function studentId(User $user): ?int
    {
        return DB::table('students')->where('user_id', $user->id)->value('id');
    }

    /** Nama siswa untuk dicetak di sertifikat (diambil dari tabel students). */
    protected function studentName(User $user): string
    {
        return DB::table('students')->where('user_id', $user->id)->value('name')
            ?: ($user->name ?? 'Siswa');
    }

    /** Enrollment siswa beserta level-nya (dari kelas, atau dari paket bila belum punya kelas). */
    protected function enrollmentQuery(int $studentId)
    {
        return DB::table('class_enrollments as ce')
            ->leftJoin('classes as c', 'c.id', '=', 'ce.class_id')
            ->leftJoin('program_packages as pp', 'pp.id', '=', 'ce.program_package_id')
            ->where('ce.student_id', $studentId)
            ->whereNull('ce.deleted_at');
    }

    /** Level (program_levels) yang pernah diikuti siswa. */
    protected function enrolledLevelIds(User $user): Collection
    {
        $studentId = $this->studentId($user);

        if (! $studentId) {
            return collect();
        }

        return $this->enrollmentQuery($studentId)
            ->selectRaw('COALESCE(c.level_id, pp.level_id) as level_id')
            ->pluck('level_id')
            ->filter()
            ->unique()
            ->values();
    }

    /** Apakah masa les siswa di level ini sudah selesai? */
    protected function hasCompletedCourse(User $user, ProgramLevel $level): bool
    {
        $studentId = $this->studentId($user);

        if (! $studentId) {
            return false;
        }

        return $this->enrollmentQuery($studentId)
            ->where('ce.status', 'Active')
            ->whereRaw('COALESCE(c.level_id, pp.level_id) = ?', [$level->id])
            ->whereNotNull('c.end_date')
            ->whereDate('c.end_date', '<=', today())
            ->exists();
    }

    /** Progress report yang sudah dikirim (Submitted) untuk siswa & level ini. */
    protected function publishedReport(User $user, ProgramLevel $level): ?object
    {
        $studentId = $this->studentId($user);

        if (! $studentId) {
            return null;
        }

        return DB::table('progress_reports as pr')
            ->join('class_enrollments as ce', 'ce.id', '=', 'pr.enrollment_id')
            ->leftJoin('classes as c', 'c.id', '=', 'ce.class_id')
            ->leftJoin('program_packages as pp', 'pp.id', '=', 'ce.program_package_id')
            ->where('pr.student_id', $studentId)
            ->where('pr.status', 'Submitted')
            ->whereRaw('COALESCE(c.level_id, pp.level_id) = ?', [$level->id])
            ->orderByDesc('pr.updated_at')
            ->select('pr.*')
            ->first();
    }

    public function isHsk(ProgramLevel $level): bool
    {
        $name = trim($level->program?->program_name ?? '');

        return strcasecmp($name, config('certificate.program_name')) === 0;
    }

    public function levelNumber(ProgramLevel $level): ?int
    {
        // "HSK 4" -> 4
        return preg_match('/(\d+)/', (string) $level->level_name, $m) ? (int) $m[1] : null;
    }

    /** Level HSK yang diikuti siswa (untuk ditampilkan di halaman Sertifikat). */
    public function candidateLevels(User $user): Collection
    {
        return ProgramLevel::with('program')
            ->whereIn('id', $this->enrolledLevelIds($user))
            ->get()
            ->filter(fn (ProgramLevel $l) => $this->isHsk($l))
            ->sortBy(fn (ProgramLevel $l) => $this->levelNumber($l))
            ->values();
    }

    /**
     * Cek syarat penerbitan.
     * Return: ['ok' => bool, 'reason' => ?string, 'rule', 'report', 'scores']
     */
    public function check(User $user, ProgramLevel $level): array
    {
        if (! $this->isHsk($level)) {
            return $this->fail('Sertifikat hanya tersedia untuk program HSK.');
        }

        $number = $this->levelNumber($level);
        $rule   = $number ? CertificateRule::where('level_number', $number)->first() : null;

        if (! $rule) {
            return $this->fail('Standar kelulusan untuk level ini belum diatur oleh tim kurikulum.');
        }

        if (! $this->hasCompletedCourse($user, $level)) {
            return $this->fail('Masa les kamu di level ini belum selesai.');
        }

        $report = $this->publishedReport($user, $level);

        if (! $report) {
            return $this->fail('Progress report kamu belum diterbitkan oleh tim kurikulum.');
        }

        // Ikut ujian = semua nilai komponen yang dibutuhkan level ini sudah terisi.
        // Kurikulum harus mengosongkan (NULL) nilai siswa yang tidak ikut ujian, jangan diisi 0.
        $scores = [];
        foreach ($rule->components as $component) {
            $value = $report->{$component . '_score'} ?? null;

            if ($value === null) {
                return $this->fail('Kamu belum mengikuti ujian akhir, sehingga sertifikat belum bisa diterbitkan.');
            }

            $scores[$component] = (int) $value;
        }

        return ['ok' => true, 'reason' => null, 'rule' => $rule, 'report' => $report, 'scores' => $scores];
    }

    /** Buat sertifikat (maksimal satu kali per siswa per level). */
    public function generate(User $user, ProgramLevel $level): Certificate
    {
        $existing = Certificate::where('user_id', $user->id)
            ->where('program_level_id', $level->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $check = $this->check($user, $level);

        if (! $check['ok']) {
            throw new RuntimeException($check['reason']);
        }

        $rule        = $check['rule'];
        $scores      = $check['scores'];
        $total       = array_sum($scores);
        $studentName = $this->studentName($user);

        $certificate = DB::transaction(function () use ($user, $level, $rule, $scores, $total, $check, $studentName) {
            $year = now()->year;

            DB::table('certificate_sequences')->insertOrIgnore([
                'year' => $year, 'level_number' => $rule->level_number, 'last_number' => 0,
            ]);

            $seq = DB::table('certificate_sequences')
                ->where('year', $year)
                ->where('level_number', $rule->level_number)
                ->lockForUpdate()
                ->first();

            $next = $seq->last_number + 1;

            DB::table('certificate_sequences')->where('id', $seq->id)->update(['last_number' => $next]);

            return Certificate::create([
                'user_id'            => $user->id,
                'program_level_id'   => $level->id,
                'progress_report_id' => $check['report']->id ?? null,
                'certificate_number' => sprintf('HY/HSK%d/%d/%04d', $rule->level_number, $year, $next),
                'verification_code'  => (string) Str::uuid(),
                'student_name'       => $studentName,
                'level_name'         => $level->level_name,
                'level_number'       => $rule->level_number,
                'scores'             => $scores,
                'total_score'        => $total,
                'max_total'          => $rule->max_total,
                'passing_score'      => $rule->passing_score,
                'result'             => $total >= $rule->passing_score ? 'passed' : 'not_passed',
                'issued_at'          => now(),
            ]);
        });

        $this->ensurePdf($certificate);

        return $certificate;
    }

    /** Pastikan file PDF ada; dibuat ulang bila hilang. Return path di disk 'public'. */
    public function ensurePdf(Certificate $certificate): string
    {
        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            return $certificate->file_path;
        }

        $verifyUrl = route('certificates.verify', $certificate->verification_code);
        $qr = 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::format('svg')->size(140)->margin(0)->generate($verifyUrl)
        );

        $pdf = Pdf::loadView('certificates.pdf', [
            'c'         => $certificate,
            'logo'      => $this->dataUri(config('certificate.logo_path')),
            'signature' => $this->dataUri(config('certificate.signature_path')),
            'qr'        => $qr,
        ])->setPaper('a4', 'landscape');

        $path = 'certificates/' . Str::slug(str_replace('/', '-', $certificate->certificate_number)) . '.pdf';

        Storage::disk('public')->put($path, $pdf->output());
        $certificate->update(['file_path' => $path]);

        return $path;
    }

    protected function dataUri(string $path): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    protected function fail(string $reason): array
    {
        return ['ok' => false, 'reason' => $reason];
    }
}