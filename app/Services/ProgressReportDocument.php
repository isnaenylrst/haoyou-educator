<?php

namespace App\Services;

use App\Models\ProgressReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Satu-satunya sumber data untuk dokumen Progress Report.
 * Dokumen HTML (pratinjau/cetak) dan PDF dibuat dari data yang sama,
 * sehingga yang dilihat guru / Kurikulum / Owner / siswa selalu identik.
 */
class ProgressReportDocument
{
    /** Disk privat untuk PDF progress report (tidak bisa dibuka lewat URL /storage). */
    public const DISK = 'local';
    public const DIR  = 'progress-reports';

    /** Data terstruktur yang dipakai view dokumen. */
    public static function data(ProgressReport $report): array
    {
        $report->loadMissing(['student', 'teacher.user', 'enrollment.class', 'ownerSigner']);

        $h = (array) ($report->header_data ?? []);

        $title = trim((string) ($h['title'] ?? '')) ?: (trim((string) $report->report_type) ?: config('progress_report.default_title'));

        $teacherNames = trim((string) ($h['teacher'] ?? '')) ?: ($report->teacher?->name ?? '-');
        $present = trim((string) ($h['present'] ?? ''))
            ?: ($report->attendance_total ? ($report->attendance_present . '/' . $report->attendance_total) : '-');

        $rows = collect($report->content ?? [])->values()->map(function ($r, $i) {
            return [
                'no'          => ($r['meeting'] ?? '') !== '' && $r['meeting'] !== null ? $r['meeting'] : ($i + 1),
                'material'    => (string) ($r['material'] ?? ($r['competency'] ?? '')),
                'level'       => (string) ($r['level'] ?? ''),
                'description' => (string) ($r['description'] ?? ''),
            ];
        })->all();

        $assessments = collect($report->assessment_data ?? [])->values()->map(fn ($a) => [
            'assessment' => (string) ($a['assessment'] ?? ''),
            'score'      => (string) ($a['score'] ?? ''),
            'grade'      => (string) ($a['grade'] ?? ''),
            'note'       => (string) ($a['note'] ?? ''),
        ])->filter(fn ($a) => $a['assessment'] !== '' || $a['score'] !== '' || $a['note'] !== '')->values()->all();

        $ownerSigned = $report->owner_signed_at !== null && $report->owner_signature_path;

        return [
            'title'    => mb_strtoupper($title),
            'period'   => (string) $report->report_period,
            'teacher'  => $teacherNames,
            'student'  => trim((string) ($h['student'] ?? '')) ?: ($report->student?->name ?? '-'),
            'class'    => trim((string) ($h['class'] ?? '')) ?: ($report->enrollment?->class?->class_name ?? '-'),
            'present'  => $present,
            'rows'     => $rows,
            'assessments' => $assessments,
            'overall'  => (string) $report->overall_achievement_level,
            'suggestion' => (string) $report->suggestion_box,
            'legend'   => self::legend(),
            'closing'  => config('progress_report.closing_note'),
            // Halaman Achievement Level: diketik guru per report. Bila belum pernah diisi, teks standar jadi titik awal.
            'achievement_html' => array_key_exists('achievement_page', $h) ? (string) $h['achievement_page'] : self::defaultAchievementHtml(),
            'teacher_sign_name' => trim((string) ($h['teacher_sign'] ?? '')) ?: $teacherNames,
            'teacher_signature' => self::signatureFile($report->teacher?->user?->signature_path),
            'city'     => config('progress_report.city'),
            'date'     => ($report->owner_signed_at ?? $report->published_at ?? $report->reviewed_at ?? now()),
            'director_title' => config('progress_report.director_title'),
            'owner_name' => self::ownerName($report),
            'owner_signature' => $ownerSigned ? self::signatureFile($report->owner_signature_path) : null,
            'is_signed' => (bool) $ownerSigned,
            'status'   => $report->status,
        ];
    }

    /** Penandatangan tetap: Joy Limmunandar (bukan nama guru). */
    public static function ownerName(?ProgressReport $report = null): string
    {
        return config('progress_report.signer_name') ?: 'Joy Limmunandar';
    }

    /** Legenda Achievement Level (halaman terakhir PDF): teks baku dari config/progress_report.php. */
    public static function legend(): array
    {
        $out = [];
        $i = 1;
        foreach (config('progress_report.legend') as $level => $def) {
            $out[] = [
                'no'          => $i++,
                'level'       => $level,
                'description' => $def['description'],
                'criteria'    => $def['criteria'],
            ];
        }

        return $out;
    }

        /** Teks standar halaman Achievement Level (HTML) sebagai titik awal yang bisa diedit guru. */
    public static function defaultAchievementHtml(): string
    {
        $html = '<p><strong>Achievement Level :</strong></p><ol>';
        foreach (self::legend() as $lv) {
            $html .= '<li><strong>' . e($lv['level']) . '</strong><br>' . e($lv['description']) . '<br>Criteria:<ul>';
            foreach ($lv['criteria'] as $name => $text) {
                $html .= '<li><strong>' . e($name) . '</strong><br>' . e($text) . '</li>';
            }
            $html .= '</ul></li>';
        }
        $html .= '</ol><p>' . e((string) config('progress_report.closing_note')) . '</p>';

        return $html;
    }

    /** Path absolut file tanda tangan (untuk <img> di PDF) bila ada. */
    public static function signatureFile(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $disk = Storage::disk('public');

        return $disk->exists($path) ? $disk->path($path) : null;
    }

    public static function renderPdf(ProgressReport $report): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('partials.progress-report-document', [
            'doc'  => self::data($report),
            'mode' => 'pdf',
        ])->setPaper('a4')->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'isFontSubsettingEnabled' => true]);
    }

    /** Buat ulang PDF dan simpan ke disk privat; file_path diperbarui. */
    public static function store(ProgressReport $report): bool
    {
        try {
            $path = self::DIR . "/report-{$report->id}.pdf";
            Storage::disk(self::DISK)->put($path, self::renderPdf($report)->output());

            // hapus salinan lama di disk publik (versi sebelumnya) supaya tidak bisa diakses lewat /storage
            if ($report->file_path && $report->file_path !== $path && Storage::disk('public')->exists($report->file_path)) {
                Storage::disk('public')->delete($report->file_path);
            }

            $report->file_path = $path;
            $report->saveQuietly();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Lokasi file PDF (disk privat dulu, lalu disk publik untuk data lama). */
    public static function absolutePath(ProgressReport $report): ?string
    {
        if (!$report->file_path) {
            return null;
        }
        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($report->file_path)) {
                return Storage::disk($disk)->path($report->file_path);
            }
        }

        return null;
    }

    /** Siapa yang boleh membuka dokumen. */
    public static function canView(?User $user, ProgressReport $report): bool
    {
        if (!$user) {
            return false;
        }
        $level = $user->level?->nama_level;

        if (in_array($level, ['Owner', 'Admin', 'Curriculum', 'Kurikulum'], true)) {
            return true;
        }
        if ($level === 'Teacher') {
            return $user->teacher && (int) $user->teacher->id === (int) $report->teacher_id;
        }
        if ($level === 'Student') {
            return $user->student
                && (int) $user->student->id === (int) $report->student_id
                && $report->is_approved;
        }

        return false;
    }

    /** Hanya Guru pemilik (selama Draft) atau Kurikulum/Owner (selama Submitted) yang boleh mengedit teks. */
    public static function canEdit(?User $user, ProgressReport $report): bool
    {
        if (!$user) {
            return false;
        }
        $level = $user->level?->nama_level;

        if ($level === 'Teacher') {
            return $report->status === 'Draft'
                && $user->teacher && (int) $user->teacher->id === (int) $report->teacher_id;
        }
        if (in_array($level, ['Curriculum', 'Kurikulum', 'Owner'], true)) {
            return $report->status === 'Submitted' && !$report->reviewed_at;
        }

        return false;
    }

    // ------------------------------------------------------------------ INPUT FORM

    /** Aturan validasi editor dokumen. $strict = saat diajukan ke Kurikulum. */
    public static function rules(bool $strict): array
    {
        $levels = ['Sufficient', 'Proficient', 'Excellent', 'Good', 'Needs Improvement'];
        $req = $strict ? 'required' : 'nullable';

        return [
            'header'                    => 'nullable|array',
            'header.title'              => 'nullable|string|max:150',
            'header.teacher'            => 'nullable|string|max:255',
            'header.student'            => 'nullable|string|max:255',
            'header.class'              => 'nullable|string|max:255',
            'header.present'            => 'nullable|string|max:50',
            'header.teacher_sign'       => 'nullable|string|max:255',
            'report_period'             => 'nullable|string|max:100',
            'materials'                 => $strict ? 'required|array|min:1' : 'nullable|array',
            'materials.*.meeting'       => 'nullable|string|max:10',
            'materials.*.material'      => ($strict ? 'required' : 'nullable') . '|string|max:20000',
            'materials.*.level'         => [$req, \Illuminate\Validation\Rule::in($levels)],
            'materials.*.description'   => $req . '|string|max:10000',
            'assessments'               => 'nullable|array',
            'assessments.*.assessment'  => 'nullable|string|max:255',
            'assessments.*.score'       => 'nullable|string|max:300',
            'assessments.*.grade'       => 'nullable|string|max:10',
            'assessments.*.note'        => 'nullable|string|max:500',
            'overall_achievement_level' => [$req, \Illuminate\Validation\Rule::in(['Sufficient', 'Proficient', 'Excellent'])],
            'suggestion_box'            => 'nullable|string|max:2000',
            'achievement_page'          => 'nullable|string|max:30000',
        ];
    }

    public static function messages(): array
    {
        return [
            'materials.required'                 => 'Isi minimal 1 pertemuan pada tabel Learning Materials.',
            'materials.*.material.required'      => 'Teks Learning Materials pada setiap pertemuan wajib diisi.',
            'materials.*.level.required'         => 'Pilih Achievement Level untuk setiap pertemuan.',
            'materials.*.description.required'   => 'Description pada setiap pertemuan wajib diisi.',
            'overall_achievement_level.required' => 'Overall Achievement Level wajib dipilih.',
        ];
    }

    /**
     * Bersihkan HTML editor pada baris materi SEBELUM validasi/penyaringan,
     * supaya "<p><br></p>" (kosong) dianggap kosong dan skrip berbahaya terbuang.
     */
    public static function cleanRows(array $materials): array
    {
        foreach ($materials as $i => $m) {
            if (!is_array($m)) {
                unset($materials[$i]);
                continue;
            }
            foreach (['material', 'description'] as $k) {
                if (array_key_exists($k, $m)) {
                    $materials[$i][$k] = \App\Support\RichText::clean((string) $m[$k]);
                }
            }
        }

        return $materials;
    }

    /** Isi kolom dokumen dari input yang sudah tervalidasi (tanpa menyimpan). */
    public static function fillFrom(ProgressReport $report, array $data): void
    {
        $assessments = collect($data['assessments'] ?? [])->map(function ($a) {
            $score = trim((string) ($a['score'] ?? ''));
            $grade = trim((string) ($a['grade'] ?? ''));
            if ($grade === '' && $score !== '' && is_numeric($score)) {
                $sc = (float) $score;
                $grade = $sc >= 85 ? 'A' : ($sc >= 75 ? 'B' : ($sc >= 60 ? 'C' : 'D'));
            }

            return [
                'assessment' => trim((string) ($a['assessment'] ?? '')),
                'score'      => $score,
                'grade'      => $grade,
                'note'       => trim((string) ($a['note'] ?? '')),
            ];
        })->filter(fn ($a) => $a['assessment'] !== '' || $a['score'] !== '')->values()->all();

        $rows = collect($data['materials'] ?? [])
            ->filter(fn ($m) => trim((string) ($m['material'] ?? '')) !== '')
            ->map(fn ($m) => [
                'meeting'     => trim((string) ($m['meeting'] ?? '')),
                'material'    => \App\Support\RichText::clean((string) $m['material']),
                'level'       => (string) ($m['level'] ?? ''),
                'description' => \App\Support\RichText::clean((string) ($m['description'] ?? '')),
            ])->values()->all();

        $h = (array) ($data['header'] ?? []);
        $header = [];
        foreach (['title', 'teacher', 'student', 'class', 'present', 'teacher_sign'] as $k) {
            $v = trim((string) ($h[$k] ?? ''));
            if ($v !== '') {
                $header[$k] = $v;
            }
        }

                // Halaman Achievement Level diketik guru (dikosongkan = halaman tidak dicetak)
        if (array_key_exists('achievement_page', $data)) {
            $header['achievement_page'] = \App\Support\RichText::clean((string) $data['achievement_page']);
        } elseif (array_key_exists('achievement_page', (array) $report->header_data)) {
            $header['achievement_page'] = $report->header_data['achievement_page'];
        }

        $report->fill([
            'report_type'               => $header['title'] ?? config('progress_report.default_title'),
            'report_period'             => (string) ($data['report_period'] ?? ''),
            'header_data'               => $header,
            'content'                   => $rows,
            'assessment_data'           => $assessments,
            'overall_achievement_level' => $data['overall_achievement_level'] ?? ($report->overall_achievement_level ?: 'Proficient'),
            'suggestion_box'            => $data['suggestion_box'] ?? null,
            'document_edited_at'        => now(),
            'document_edited_by'        => \Illuminate\Support\Facades\Auth::id(),
        ]);
        if ($report->file_path === null) {
            $report->file_path = '';
        }
    }
}