<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Submission;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use App\Models\ProgressReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Riwayat SEMUA pengajuan guru (LP & PPT, jurnal, progress report, cuti / ganti kelas,
 * sertifikat, reimburse & bahan) dalam satu daftar. Tidak ada yang dihapus / disembunyikan
 * setelah di-ACC atau TTD lengkap, jadi Kurikulum dan guru selalu bisa melacak apa saja
 * yang pernah diajukan beserta progres tanda tangannya.
 *
 * Cara kerja (hemat memori): tiap jenis dibaca dulu secara ringan (id, tanggal, status),
 * digabung, diurutkan, baru data halaman yang tampil saja yang diambil lengkap.
 */
class SubmissionHistoryService
{
    public const TYPES = [
        'material'   => ['label' => 'LP & PPT',           'icon' => 'fa-file-powerpoint'],
        'jurnal'     => ['label' => 'Jurnal',             'icon' => 'fa-book-open'],
        'report'     => ['label' => 'Progress Report',    'icon' => 'fa-chart-column'],
        'cuti'       => ['label' => 'Cuti / Ganti Kelas', 'icon' => 'fa-calendar-xmark'],
        'sertifikat' => ['label' => 'Sertifikat',         'icon' => 'fa-certificate'],
        'pengajuan'  => ['label' => 'Reimburse & Bahan',  'icon' => 'fa-receipt'],
    ];

    public const GROUPS = [
        'menunggu'     => 'Menunggu Review',
        'proses'       => 'Dalam Proses TTD',
        'selesai'      => 'Selesai',
        'dikembalikan' => 'Dikembalikan / Ditolak',
    ];

    // ------------------------------------------------------------------ FILTER

    /**
     * jenis | guru | bulan (YYYY-MM) | dari | sampai | status | jurnal_late (khusus jenis=jurnal)
     */
    public static function parseFilters(Request $request): array
    {
        $jenis = $request->query('jenis');
        $jenis = (is_string($jenis) && isset(self::TYPES[$jenis])) ? $jenis : null;

        $status = $request->query('status');
        $status = (is_string($status) && isset(self::GROUPS[$status])) ? $status : null;

        $from = $to = null;
        $month = (string) $request->query('bulan', '');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $from = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
            $to   = $from->copy()->endOfMonth()->startOfDay();
        } else {
            $month = '';
            $parse = function ($value): ?Carbon {
                if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return null;
                }
                try {
                    return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
                } catch (\Throwable $e) {
                    return null;
                }
            };
            $from = $parse($request->query('dari'));
            $to   = $parse($request->query('sampai'));

            if ($from && $to && $from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
        }

        $late = ($jenis === 'jurnal' && in_array($request->query('jurnal_late'), ['1', '0'], true))
            ? (string) $request->query('jurnal_late')
            : null;

        return [
            'jenis'   => $jenis,
            'status'  => $status,
            'teacher' => $request->integer('guru') ?: null,
            'month'   => $month,
            'from'    => $from,
            'to'      => $to,
            'late'    => $late,
        ];
    }

    // ------------------------------------------------------------------ UTAMA

    /**
     * @param  array|null $scope  null = semua guru (Kurikulum); ['teacher_id' => x, 'user_id' => y] = hanya milik guru itu
     * @return array{rows: LengthAwarePaginator, counts: array, typeCounts: array, total: int}
     */
    public function paginate(array $f, ?array $scope, int $perPage, string $path, array $query = []): array
    {
        $all = collect();
        foreach (array_keys(self::TYPES) as $type) {
            $all = $all->concat($this->{'light' . ucfirst($type)}($f, $scope));
        }

        $typeCounts = $all->groupBy('type')->map->count()->all();

        $byType = $f['jenis'] ? $all->where('type', $f['jenis']) : $all;

        $counts = array_fill_keys(array_keys(self::GROUPS), 0);
        foreach ($byType as $r) {
            $counts[$r['group']]++;
        }

        $filtered = $f['status'] ? $byType->where('group', $f['status']) : $byType;
        $sorted   = $filtered->sortByDesc(fn ($r) => $r['date']->timestamp)->values();

        $page  = max(1, (int) request()->query('page', 1));
        $slice = $sorted->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $this->hydrate($slice),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $path, 'query' => $query]
        );

        return [
            'rows'       => $paginator,
            'counts'     => $counts,
            'typeCounts' => $typeCounts,
            'total'      => $byType->count(),
        ];
    }

    private function between($query, string $column, array $f)
    {
        return $query
            ->when($f['from'], fn ($q) => $q->whereDate($column, '>=', $f['from']->toDateString()))
            ->when($f['to'], fn ($q) => $q->whereDate($column, '<=', $f['to']->toDateString()));
    }

    private function teacherId(array $f, ?array $scope): ?int
    {
        return $scope['teacher_id'] ?? $f['teacher'];
    }

    private function light(string $type, $row, $date, string $group): array
    {
        return ['type' => $type, 'id' => $row->id, 'date' => Carbon::parse($date), 'group' => $group];
    }

    // ------------------------------------------------------------------ QUERY RINGAN PER JENIS

    private function lightMaterial(array $f, ?array $scope): Collection
    {
        $tid = $this->teacherId($f, $scope);

        return $this->between(TeacherMaterial::query()->when($tid, fn ($q) => $q->where('teacher_id', $tid)), 'created_at', $f)
            ->get(['id', 'status', 'created_at'])
            ->map(fn ($r) => $this->light('material', $r, $r->created_at, match ($r->status) {
                'Approved' => 'selesai',
                'Rejected' => 'dikembalikan',
                default    => 'menunggu',
            }));
    }

    private function lightJurnal(array $f, ?array $scope): Collection
    {
        $tid = $this->teacherId($f, $scope);

        return $this->between(
            TeachingJournal::query()
                ->when($tid, fn ($q) => $q->where(fn ($w) => $w->where('teacher_id', $tid)->orWhere('filled_by', $tid)))
                ->when($f['late'] !== null, fn ($q) => $q->where('is_late', $f['late'] === '1')),
            'created_at', $f
        )
            ->get(['id', 'status', 'created_at'])
            ->map(fn ($r) => $this->light('jurnal', $r, $r->created_at, match ($r->status) {
                'Reviewed' => 'selesai',
                'Revision' => 'dikembalikan',
                default    => 'menunggu',
            }));
    }

    private function lightReport(array $f, ?array $scope): Collection
    {
        $tid = $this->teacherId($f, $scope);

        // hanya yang pernah dikirim (draft murni belum dihitung sebagai pengajuan)
        return $this->between(
            ProgressReport::query()->whereNotNull('submitted_at')->when($tid, fn ($q) => $q->where('teacher_id', $tid)),
            'submitted_at', $f
        )
            ->get(['id', 'status', 'submitted_at', 'reviewed_at', 'owner_signed_at'])
            ->map(function ($r) {
                $group = match (true) {
                    $r->owner_signed_at !== null => 'selesai',          // 3 TTD lengkap
                    $r->reviewed_at !== null     => 'proses',           // di-ACC, menunggu TTD Owner
                    $r->status === 'Submitted'   => 'menunggu',
                    default                      => 'dikembalikan',     // Draft setelah dikembalikan
                };

                return $this->light('report', $r, $r->submitted_at, $group);
            });
    }

    private function lightCuti(array $f, ?array $scope): Collection
    {
        $tid = $this->teacherId($f, $scope);

        return $this->between(TeacherLeave::query()->when($tid, fn ($q) => $q->where('teacher_id', $tid)), 'created_at', $f)
            ->get(['id', 'status', 'created_at'])
            ->map(fn ($r) => $this->light('cuti', $r, $r->created_at, match ($r->status) {
                'Approved' => 'selesai',
                'Rejected' => 'dikembalikan',
                default    => 'menunggu',
            }));
    }

    private function lightSertifikat(array $f, ?array $scope): Collection
    {
        $tid = $this->teacherId($f, $scope);

        // hanya sertifikat kelas yang diajukan guru (teacher_id terisi)
        return $this->between(
            Certificate::query()->whereNotNull('teacher_id')->when($tid, fn ($q) => $q->where('teacher_id', $tid)),
            'created_at', $f
        )
            ->get(['id', 'status', 'owner_signed_at', 'created_at'])
            ->map(fn ($r) => $this->light('sertifikat', $r, $r->created_at, match (true) {
                $r->status === 'Rejected'                                => 'dikembalikan',
                $r->status === 'Approved' && $r->owner_signed_at !== null => 'selesai',
                $r->status === 'Approved'                                => 'proses',
                default                                                  => 'menunggu',
            }));
    }

    private function lightPengajuan(array $f, ?array $scope): Collection
    {
        $query = Submission::query();

        if ($scope) {
            $query->where(fn ($w) => $w->where('teacher_id', $scope['teacher_id'])->orWhere('created_by', $scope['user_id']));
        } elseif ($f['teacher']) {
            $query->where('teacher_id', $f['teacher']);
        }

        return $this->between($query, 'created_at', $f)
            ->get(['id', 'status', 'created_at'])
            ->map(fn ($r) => $this->light('pengajuan', $r, $r->created_at, match ($r->status) {
                'Selesai'                                                 => 'selesai',
                'Ditolak'                                                 => 'dikembalikan',
                'Verifikasi Admin', 'Review Owner', 'Disetujui', 'Dibayar' => 'proses',
                default                                                   => 'menunggu',
            }));
    }

    // ------------------------------------------------------------------ HALAMAN LENGKAP

    private function hydrate(Collection $slice): Collection
    {
        $built = [];

        foreach ($slice->groupBy('type') as $type => $items) {
            $ids = $items->pluck('id')->all();
            $built += $this->{'build' . ucfirst($type)}($ids);
        }

        return $slice->map(fn ($r) => $built[$r['type'] . ':' . $r['id']] ?? null)->filter()->values();
    }

    private function row(string $type, $id, array $data): array
    {
        return array_merge([
            'type'        => $type,
            'type_label'  => self::TYPES[$type]['label'],
            'icon'        => self::TYPES[$type]['icon'],
            'id'          => $id,
            'subtitle'    => null,
            'teacher'     => '-',
            'note'        => null,
            'reviewer'    => null,
            'reviewed_at' => null,
            'is_late'     => false,
            'steps'       => [],
            'url'         => null,
        ], $data);
    }

    private function step(string $label, bool $done, $at = null, ?string $by = null): array
    {
        return ['label' => $label, 'done' => $done, 'at' => $at ? Carbon::parse($at) : null, 'by' => $by];
    }

    private function buildMaterial(array $ids): array
    {
        $out = [];
        $rows = TeacherMaterial::with(['teacher', 'class', 'material', 'approver'])->whereIn('id', $ids)->get();

        foreach ($rows as $m) {
            [$group, $label] = match ($m->status) {
                'Approved' => ['selesai', 'Disetujui Kurikulum'],
                'Rejected' => ['dikembalikan', 'Dikembalikan ke Guru'],
                default    => ['menunggu', 'Menunggu Review Kurikulum'],
            };

            $out['material:' . $m->id] = $this->row('material', $m->id, [
                'title'       => $m->ppt_title ?: ($m->material?->title ?? 'LP & PPT'),
                'subtitle'    => trim(($m->class?->class_name ? 'Kelas ' . $m->class->class_name . ' · ' : '') . 'Versi ' . ($m->current_version ?? 1)),
                'teacher'     => $m->teacher?->name ?? '-',
                'date'        => $m->created_at,
                'group'       => $group,
                'status_label' => $label,
                'note'        => $m->review_note,
                'reviewer'    => $m->approver?->name,
                'reviewed_at' => $m->approved_at,
                'steps'       => [
                    $this->step('Diajukan Guru', true, $m->created_at),
                    $this->step('Review Kurikulum', $m->status === 'Approved', $m->approved_at, $m->approver?->name),
                ],
            ]);
        }

        return $out;
    }

    private function buildJurnal(array $ids): array
    {
        $out  = [];
        $rows = TeachingJournal::with(['teacher', 'class', 'substituteTeacher'])->whereIn('id', $ids)->get();
        $names = User::whereIn('id', $rows->pluck('reviewed_by')->filter()->unique())->pluck('name', 'id');

        foreach ($rows as $j) {
            [$group, $label] = match ($j->status) {
                'Reviewed' => ['selesai', 'Sudah Direview Kurikulum'],
                'Revision' => ['dikembalikan', 'Dikembalikan untuk Revisi'],
                default    => ['menunggu', 'Menunggu Review Kurikulum'],
            };
            $reviewer = $names[$j->reviewed_by] ?? null;

            $out['jurnal:' . $j->id] = $this->row('jurnal', $j->id, [
                'title'        => 'Jurnal ' . ($j->class?->class_name ?? '-'),
                'subtitle'     => 'Sesi ' . ($j->session_date?->translatedFormat('d M Y') ?? '-')
                                  . ($j->meeting_number ? ' · Pertemuan ke-' . $j->meeting_number : '')
                                  . ($j->is_substitute ? ' · oleh guru pengganti' : ''),
                'teacher'      => $j->teacher?->name ?? '-',
                'date'         => $j->created_at,
                'group'        => $group,
                'status_label' => $label,
                'note'         => $j->review_note,
                'reviewer'     => $reviewer,
                'reviewed_at'  => $j->reviewed_at,
                'is_late'      => (bool) $j->is_late,
                'steps'        => [
                    $this->step($j->is_late ? 'Diisi Guru (terlambat)' : 'Diisi Guru', true, $j->created_at),
                    $this->step('Review Kurikulum', $j->status === 'Reviewed', $j->reviewed_at, $reviewer),
                ],
            ]);
        }

        return $out;
    }

    private function buildReport(array $ids): array
    {
        $out  = [];
        $rows = ProgressReport::with(['student', 'teacher', 'enrollment.class', 'reviewer', 'ownerSigner'])->whereIn('id', $ids)->get();

        foreach ($rows as $r) {
            [$group, $label] = match (true) {
                $r->owner_signed_at !== null => ['selesai', 'Selesai — 3 TTD lengkap'],
                $r->reviewed_at !== null     => ['proses', 'Di-ACC Kurikulum — menunggu TTD Owner'],
                $r->status === 'Submitted'   => ['menunggu', 'Menunggu Review Kurikulum'],
                default                      => ['dikembalikan', 'Dikembalikan ke Guru'],
            };

            $out['report:' . $r->id] = $this->row('report', $r->id, [
                'title'        => 'Progress Report — ' . ($r->student?->name ?? '-'),
                'subtitle'     => trim(($r->enrollment?->class?->class_name ? 'Kelas ' . $r->enrollment->class->class_name . ' · ' : '')
                                  . ($r->report_period ?: $r->report_type)),
                'teacher'      => $r->teacher?->name ?? '-',
                'date'         => $r->submitted_at,
                'group'        => $group,
                'status_label' => $label,
                'note'         => $r->review_note,
                'reviewer'     => $r->reviewer?->name,
                'reviewed_at'  => $r->reviewed_at,
                'url'          => route('progress-report.document', $r->id),
                'steps'        => [
                    $this->step('TTD Guru', $r->teacher_signed_at !== null, $r->teacher_signed_at, $r->teacher?->name),
                    $this->step('TTD Kurikulum', $r->reviewed_at !== null, $r->reviewed_at, $r->reviewer?->name),
                    $this->step('TTD Owner', $r->owner_signed_at !== null, $r->owner_signed_at, $r->ownerSigner?->name),
                ],
            ]);
        }

        return $out;
    }

    private function buildCuti(array $ids): array
    {
        $out  = [];
        $rows = TeacherLeave::with(['teacher', 'classSchedule.class', 'replacementTeacher', 'approver'])->whereIn('id', $ids)->get();

        foreach ($rows as $l) {
            [$group, $label] = match ($l->status) {
                'Approved' => ['selesai', 'Disetujui Kurikulum'],
                'Rejected' => ['dikembalikan', 'Ditolak Kurikulum'],
                default    => ['menunggu', 'Menunggu Review Kurikulum'],
            };

            $type = $l->request_type ?: 'Cuti';

            $out['cuti:' . $l->id] = $this->row('cuti', $l->id, [
                'title'        => $type . ' — ' . ($l->classSchedule?->class?->class_name ?? '-'),
                'subtitle'     => 'Tanggal ' . ($l->leave_date?->translatedFormat('d M Y') ?? '-')
                                  . ($l->reason ? ' · ' . \Illuminate\Support\Str::limit($l->reason, 80) : '')
                                  . ($l->replacementTeacher ? ' · Pengganti: ' . $l->replacementTeacher->name : ''),
                'teacher'      => $l->teacher?->name ?? '-',
                'date'         => $l->created_at,
                'group'        => $group,
                'status_label' => $label,
                'note'         => $l->review_note,
                'reviewer'     => $l->approver?->name,
                'reviewed_at'  => $l->approved_at,
                'steps'        => [
                    $this->step('Diajukan Guru', true, $l->created_at),
                    $this->step('Review Kurikulum', $l->status === 'Approved', $l->approved_at, $l->approver?->name),
                ],
            ]);
        }

        return $out;
    }

    private function buildSertifikat(array $ids): array
    {
        $out   = [];
        $rows  = Certificate::with(['student', 'teacher', 'reviewer'])->whereIn('id', $ids)->get();
        $names = User::whereIn('id', $rows->pluck('owner_signed_by')->filter()->unique())->pluck('name', 'id');

        foreach ($rows as $c) {
            [$group, $label] = match (true) {
                $c->status === 'Rejected'                                => ['dikembalikan', 'Dikembalikan ke Guru'],
                $c->status === 'Approved' && $c->owner_signed_at !== null => ['selesai', 'Selesai — TTD lengkap'],
                $c->status === 'Approved'                                => ['proses', 'Di-ACC Kurikulum — menunggu TTD Owner'],
                default                                                  => ['menunggu', 'Menunggu Review Kurikulum'],
            };

            $out['sertifikat:' . $c->id] = $this->row('sertifikat', $c->id, [
                'title'        => 'Sertifikat — ' . ($c->student?->name ?? $c->student_name ?? '-'),
                'subtitle'     => $c->course_name,
                'teacher'      => $c->teacher?->name ?? '-',
                'date'         => $c->created_at,
                'group'        => $group,
                'status_label' => $label,
                'note'         => $c->review_note,
                'reviewer'     => $c->reviewer?->name,
                'reviewed_at'  => $c->reviewed_at,
                'steps'        => [
                    $this->step('Diajukan Guru', true, $c->created_at),
                    $this->step('TTD Kurikulum', $c->status === 'Approved', $c->reviewed_at, $c->reviewer?->name),
                    $this->step('TTD Owner', $c->owner_signed_at !== null, $c->owner_signed_at, $names[$c->owner_signed_by] ?? null),
                ],
            ]);
        }

        return $out;
    }

    private function buildPengajuan(array $ids): array
    {
        $out  = [];
        $rows = Submission::with(['teacher', 'creator', 'curriculum', 'admin', 'owner'])->whereIn('id', $ids)->get();

        foreach ($rows as $s) {
            [$group, $label] = match ($s->status) {
                'Selesai'                                                  => ['selesai', 'Selesai'],
                'Ditolak'                                                  => ['dikembalikan', 'Ditolak'],
                'Verifikasi Admin', 'Review Owner', 'Disetujui', 'Dibayar' => ['proses', $s->status],
                default                                                    => ['menunggu', 'Menunggu Review Kurikulum'],
            };

            $out['pengajuan:' . $s->id] = $this->row('pengajuan', $s->id, [
                'title'        => $s->type . ' — ' . $s->item_name,
                'subtitle'     => 'Rp ' . number_format((float) $s->amount, 0, ',', '.'),
                'teacher'      => $s->teacher?->name ?? $s->creator?->name ?? '-',
                'date'         => $s->created_at,
                'group'        => $group,
                'status_label' => $label,
                'note'         => $s->owner_note ?: ($s->admin_note ?: $s->curriculum_note),
                'reviewer'     => $s->curriculum?->name,
                'reviewed_at'  => $s->curriculum_approved_at,
                'url'          => route('kurikulum.review-pengajuan.print-submission', $s->id),
                'steps'        => [
                    $this->step('Diajukan', true, $s->created_at),
                    $this->step('Kurikulum', $s->curriculum_approved_at !== null, $s->curriculum_approved_at, $s->curriculum?->name),
                    $this->step('Admin', $s->admin_verified_at !== null, $s->admin_verified_at, $s->admin?->name),
                    $this->step('Owner', $s->owner_approved_at !== null, $s->owner_approved_at, $s->owner?->name),
                ],
            ]);
        }

        return $out;
    }
}
