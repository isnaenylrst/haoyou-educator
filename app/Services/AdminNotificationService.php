<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\ProgressReport;
use App\Models\Student;
use App\Models\Submission;
use App\Models\TrainingMaterial;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Notifikasi Admin (turunan, tanpa tabel).
 *
 * Semua notifikasi dihitung dari kondisi data saat ini, sama seperti
 * TeacherNotificationService. Notifikasi hilang sendiri begitu kondisinya
 * terpenuhi (jurnal terisi, pembayaran dilakukan, status follow up diisi, dst).
 */
class AdminNotificationService
{
    public const CACHE_KEY = 'admin_notif_summary';

    public const SECTIONS = [
        'siswa'      => 'Siswa',
        'kelas'      => 'Kelas',
        'jurnal'     => 'Jurnal Mengajar',
        'pembayaran' => 'Pembayaran',
        'progress'   => 'Progress Report',
        'pengajuan'  => 'Pengajuan Biaya',
        'sop'        => 'SOP',
    ];

    /** Tujuan klik default per section (halaman daftar). Notifikasi tertentu mengarah lebih spesifik, lihat to(). */
    public const ROUTES = [
        'siswa'      => 'admin.siswa',
        'kelas'      => 'admin.kelas',
        'jurnal'     => 'admin.jurnal-mengajar',
        'pembayaran' => 'admin.pembayaran',
        'progress'   => 'admin.progress-report',
        'pengajuan'  => 'admin.pengajuan.index',
        'sop'        => 'admin.training.index',
    ];

    public const ACTIVE_ENROLLMENT         = ['Active', 'Waiting Class'];
    public const JOURNAL_GRACE_MINUTES     = 30;   // jurnal dianggap telat setelah kelas selesai + 30 menit
    public const JOURNAL_LOOKBACK_DAYS     = 14;   // sesi lebih lama dari ini tidak ditampilkan
    public const FOLLOW_UP_ESCALATE_DAYS   = 7;    // follow up dianggap mendesak jika menggantung lebih dari ini
    public const SUBMISSION_ESCALATE_DAYS  = 3;    // pengajuan menunggu verifikasi lebih dari ini = mendesak
    public const SOP_VISIBLE_DAYS          = 7;    // tanpa tabel tidak ada status "sudah dibaca", jadi SOP tampil N hari
    public const INCLUDE_MID_CYCLE_REPORTS = true; // false = hanya progress report di akhir kelas

    private const BUILDERS = [
        'birthdays',
        'followUps',
        'needDirectClass',
        'classesEnding',
        'missingJournals',
        'payments',
        'missingProgressReports',
        'submissions',
        'newSops',
    ];

    /** Semua notifikasi, urut: paling mendesak dulu, lalu terbaru. */
    public function all(): Collection
    {
        return collect(self::BUILDERS)
            // satu section error tidak boleh mematikan seluruh halaman/bell
            ->flatMap(fn ($method) => rescue(fn () => $this->{$method}(), collect()))
            ->sortBy([['priority', 'desc'], ['date', 'desc']])
            ->values();
    }

    /** Ringkasan untuk icon bell: total, jumlah per section, dan 5 notifikasi teratas. */
    public function summary(?Collection $all = null): array
    {
        $all ??= $this->all();

        return [
            'total'  => $all->count(),
            'counts' => $all->countBy('section')->all(),
            'urgent' => $all->where('priority', 3)->countBy('section')->all(),
            // tanggal disimpan sebagai string: objek Carbon tidak aman untuk di-cache
            'top'    => $all->take(5)
                ->map(fn ($n) => ['date' => $n['date']->toIso8601String()] + $n)
                ->values()->all(),
        ];
    }

    /* ======================================================================
     |  SISWA
     * ====================================================================== */

    /** Reminder ulang tahun murid: H-1, Hari H, H+1. */
    protected function birthdays(): Collection
    {
        // offset: tanggal ulang tahun = hari ini + offset
        $offsets = collect([1, 0, -1])
            ->mapWithKeys(fn ($o) => [today()->addDays($o)->format('m-d') => $o]);

        return Student::query()
            ->with('candidateStudent')
            ->whereHas('activeEnrollment')
            ->whereHas('candidateStudent', fn ($q) => $q->whereRaw(
                "DATE_FORMAT(birth_date, '%m-%d') IN (?, ?, ?)",
                $offsets->keys()->all()
            ))
            ->get()
            ->map(function ($s) use ($offsets) {
                $birth  = Carbon::parse($s->candidateStudent->birth_date);
                $offset = $offsets[$birth->format('m-d')];
                $target = today()->addDays($offset);

                $when  = [1 => 'besok', 0 => 'hari ini', -1 => 'kemarin'][$offset];
                $label = [1 => 'H-1', 0 => 'Hari H', -1 => 'H+1'][$offset];
                $age   = $target->year - $birth->year;

                return $this->item(
                    'siswa',
                    "Ulang tahun murid ({$label})",
                    "{$s->name} berulang tahun {$when} (menjadi {$age} tahun).",
                    $offset === 0 ? 2 : 1,
                    $target,
                    $this->to('admin.siswa.detail', $s->id, 'siswa')
                );
            });
    }

    /**
     * Follow up siswa yang belum memutuskan lanjut.
     * Muncul sejak progress report terbit, hilang saat status Lanjut/Tidak Lanjut.
     */
    protected function followUps(): Collection
    {
        return ProgressReport::query()
            ->approved()
            ->with('student')
            ->whereNotNull('published_at')
            ->where(fn ($q) => $q->whereNull('follow_up_status')
                ->orWhereIn('follow_up_status', ['Belum', 'Menunggu Jawaban']))
            ->get()
            ->map(function ($r) {
                $days = (int) $r->published_at->copy()->startOfDay()->diffInDays(today(), true);

                return $this->item(
                    'siswa',
                    'Follow up lanjut program',
                    "Tanyakan kelanjutan {$r->student?->name}. Status follow up: " . ($r->follow_up_status ?: 'Belum')
                        . " (progress report terbit {$days} hari lalu).",
                    $days >= self::FOLLOW_UP_ESCALATE_DAYS ? 3 : 2,
                    $r->published_at,
                    $this->to('admin.progress-report', [], 'siswa')
                );
            });
    }

    /**
     * Siswa berstatus Lanjut tapi belum punya enrollment baru: admin perlu direct kelas.
     * Hilang begitu ada enrollment baru (Active / Waiting Class) setelah report itu.
     */
    protected function needDirectClass(): Collection
    {
        $reports = ProgressReport::query()
            ->approved()
            ->with('student')
            ->where('follow_up_status', 'Lanjut')
            ->orderByDesc('id')
            ->get()
            ->unique('student_id');          // satu notifikasi per siswa (report terbaru)

        if ($reports->isEmpty()) {
            return collect();
        }

        $latestEnrollment = ClassEnrollment::query()
            ->whereIn('student_id', $reports->pluck('student_id'))
            ->whereIn('status', self::ACTIVE_ENROLLMENT)
            ->selectRaw('student_id, MAX(id) as max_id')
            ->groupBy('student_id')
            ->pluck('max_id', 'student_id');

        return $reports
            ->reject(fn ($r) => ($latestEnrollment[$r->student_id] ?? 0) > (int) $r->enrollment_id)
            ->map(fn ($r) => $this->item(
                'siswa',
                'Siswa lanjut: direct kelas',
                "{$r->student?->name} memilih lanjut. Segera tentukan kelas berikutnya.",
                3,
                $r->followed_up_at ?? $r->published_at ?? now(),
                $this->to('admin.siswa.continue.program', $r->student_id, 'siswa')
            ));
    }

    /* ======================================================================
     |  KELAS
     * ====================================================================== */

    /** Kelas berakhir H-1, Hari H, H+1 (berdasarkan pertemuan terakhir, bukan hanya end_date). */
    protected function classesEnding(): Collection
    {
        $from = today()->subDay();
        $to   = today()->addDay();

        $classes = ClassModel::query()
            ->with(['teacher', 'level', 'programPackage', 'privatePackage'])
            ->where('status', '!=', 'Closed')
            ->get();

        if ($classes->isEmpty()) {
            return collect();
        }

        $lastSession = ClassSession::query()
            ->where('kind', 'Regular')
            ->where('status', '!=', 'Cancelled')
            ->whereIn('class_id', $classes->pluck('id'))
            ->selectRaw('class_id, MAX(session_date) as last_date')
            ->groupBy('class_id')
            ->pluck('last_date', 'class_id');

        return $classes
            ->map(function ($c) use ($lastSession) {
                $end = isset($lastSession[$c->id])
                    ? Carbon::parse($lastSession[$c->id])->startOfDay()
                    : ($c->end_date ? Carbon::parse($c->end_date)->startOfDay() : null);

                return $end ? [$c, $end] : null;
            })
            ->filter(fn ($x) => $x && $x[1]->between($from, $to))
            ->map(function ($x) {
                [$c, $end] = $x;

                return $this->item(
                    'kelas',
                    'Kelas akan berakhir (' . $this->dayLabel($end) . ')',
                    $this->className($c) . ($c->teacher ? " — {$c->teacher->name}" : '')
                        . ' ' . $this->endsText($end) . '.',
                    $end->isToday() ? 3 : 2,
                    $end,
                    $this->to('admin.kelas.ubah-jadwal', $c->id, 'kelas')
                );
            });
    }

    /* ======================================================================
     |  JURNAL MENGAJAR
     * ====================================================================== */

    /** Jurnal belum diisi sejak kelas selesai + 30 menit. Hilang saat jurnal sudah diisi. */
    protected function missingJournals(): Collection
    {
        return app(MissingJournalFinder::class)
            ->find(today()->subDays(self::JOURNAL_LOOKBACK_DAYS), today(), null, true)
            ->filter(function ($i) {
                $end = Carbon::parse($i->date->format('Y-m-d') . ' ' . $i->schedule->end_time);

                return $end->addMinutes(self::JOURNAL_GRACE_MINUTES)->lte(now());
            })
            ->map(fn ($i) => $this->item(
                'jurnal',
                'Jurnal mengajar belum diisi',
                "{$i->teacher->name} belum mengisi jurnal " . $this->className($i->class)
                    . ' (' . $i->date->translatedFormat('d M Y') . ', selesai ' . substr($i->schedule->end_time, 0, 5) . ')'
                    . ($i->is_substitute
                        ? ' sebagai guru pengganti' . ($i->awaiting_curriculum ? ' (cuti menunggu ACC Kurikulum)' : '') . '.'
                        : '.'),
                $i->days_late >= 2 ? 3 : 2,
                Carbon::parse($i->date->format('Y-m-d') . ' ' . $i->schedule->end_time)
            ));
    }

    /* ======================================================================
     |  PEMBAYARAN
     * ====================================================================== */

    /**
     * Penagihan: mulai H-1 sebelum jatuh tempo sampai pembayaran dilakukan.
     * Jatuh tempo = tanggal 5 bulan berikutnya dari tanggal bayar terakhir
     * (aturan yang sama dengan invoice). Cara B: tiap transaksi = row baru,
     * jadi hanya row TERAKHIR per enrollment yang dilihat.
     */
    protected function payments(): Collection
    {
        $latestIds = Payment::query()->selectRaw('MAX(id)')->groupBy('enrollment_id');

        $payments = Payment::query()
            ->whereIn('id', $latestIds)
            ->where('remaining_bill', '>', 0)
            ->get();

        if ($payments->isEmpty()) {
            return collect();
        }

        $enrollments = ClassEnrollment::query()
            ->with('student')
            ->whereIn('id', $payments->pluck('enrollment_id'))
            ->whereIn('status', self::ACTIVE_ENROLLMENT)
            ->get()
            ->keyBy('id');

        return $payments
            ->map(function ($p) use ($enrollments) {
                $enrollment = $enrollments->get($p->enrollment_id);
                if (! $enrollment) {
                    return null;
                }

                $due = Carbon::parse($p->payment_date)->addMonthNoOverflow()->startOfMonth()->addDays(4);

                return today()->gte($due->copy()->subDay()) ? [$p, $enrollment, $due] : null;
            })
            ->filter()
            ->map(function ($x) {
                [$p, $enrollment, $due] = $x;

                return $this->item(
                    'pembayaran',
                    'Penagihan pembayaran',
                    "{$enrollment->student?->name}: sisa tagihan Rp " . number_format($p->remaining_bill, 0, ',', '.')
                        . ', ' . $this->dueText($due) . '.',
                    today()->gt($due) ? 3 : 2,
                    $due
                );
            });
    }

    /* ======================================================================
     |  PROGRESS REPORT
     * ====================================================================== */

    /** Progress report yang sudah jatuh tempo tapi belum Submitted. */
    protected function missingProgressReports(): Collection
    {
        $items = app(MissingProgressReportFinder::class)->find();

        if (! self::INCLUDE_MID_CYCLE_REPORTS) {
            $items = $items->where('is_final', true);
        }

        return $items->map(fn ($i) => $this->item(
            'progress',
            $i->state === 'Dikembalikan' ? 'Progress report dikembalikan' : 'Progress report belum diisi',
            "{$i->student?->name} — " . $this->className($i->class) . ". {$i->kind_label}; "
                . "status: {$i->state}; " . $this->dueText($i->due) . '.',
            $i->overdue ? 3 : 2,
            $i->due
        ));
    }

    /* ======================================================================
     |  PENGAJUAN BIAYA
     * ====================================================================== */

    /** Pengajuan yang menunggu verifikasi Admin. */
    protected function submissions(): Collection
    {
        return Submission::query()
            ->with('teacher')
            ->where('status', 'Verifikasi Admin')
            ->get()
            ->map(function ($s) {
                $since = $s->curriculum_approved_at ?? $s->created_at ?? now();
                $days  = (int) $since->copy()->startOfDay()->diffInDays(today(), true);

                return $this->item(
                    'pengajuan',
                    'Pengajuan perlu diverifikasi',
                    ($s->teacher?->name ?? 'Guru') . " — {$s->type}: {$s->item_name} ({$s->formatted_amount}).",
                    $days >= self::SUBMISSION_ESCALATE_DAYS ? 3 : 2,
                    $since
                );
            });
    }

    /* ======================================================================
     |  SOP
     * ====================================================================== */

    /**
     * SOP / materi pelatihan baru dari Owner untuk Admin.
     * Tampil SOP_VISIBLE_DAYS hari sejak diunggah (tanpa tabel tidak ada status "sudah dibaca").
     */
    protected function newSops(): Collection
    {
        return TrainingMaterial::query()
            ->forRole('Admin')
            ->where('created_at', '>=', now()->subDays(self::SOP_VISIBLE_DAYS))
            ->get()
            ->map(fn ($t) => $this->item(
                'sop',
                'SOP baru dari Owner',
                $t->title . ($t->category ? " ({$t->category})" : '') . ($t->version ? ", versi {$t->version}" : '') . '.',
                1,
                $t->created_at
            ));
    }

    /* ======================================================================
     |  HELPER
     * ====================================================================== */

    protected function item(string $section, string $title, string $message, int $priority, $date, ?string $url = null): array
    {
        return [
            'section'  => $section,
            'title'    => $title,
            'message'  => $message,
            'url'      => $url ?? $this->link($section),
            'priority' => $priority,                       // 3 = mendesak, 2 = perlu tindakan, 1 = info
            'date'     => $date instanceof Carbon ? $date : Carbon::parse($date),
        ];
    }

    /** URL ke route tertentu; kalau route-nya tidak ada, jatuh ke halaman daftar section. */
    protected function to(string $name, $params, string $section): string
    {
        return Route::has($name) ? route($name, $params) : $this->link($section);
    }

    protected function link(string $section): string
    {
        $name = self::ROUTES[$section] ?? null;

        return $name && Route::has($name) ? route($name) : '#';
    }

    protected function diffFromToday(Carbon $target): int
    {
        // positif = di masa depan
        return (int) today()->diffInDays($target->copy()->startOfDay(), false);
    }

    protected function dayLabel(Carbon $target): string
    {
        $d = $this->diffFromToday($target);

        return $d === 0 ? 'Hari H' : ($d > 0 ? "H-{$d}" : 'H+' . abs($d));
    }

    protected function endsText(Carbon $end): string
    {
        $d = $this->diffFromToday($end);

        return match (true) {
            $d > 0  => 'berakhir besok',
            $d === 0 => 'berakhir hari ini',
            default => 'sudah berakhir kemarin',
        };
    }

    protected function dueText(Carbon $due): string
    {
        $d = $this->diffFromToday($due);

        return match (true) {
            $d > 0   => $d === 1 ? 'jatuh tempo besok' : "jatuh tempo {$d} hari lagi",
            $d === 0 => 'jatuh tempo hari ini',
            default  => 'terlambat ' . abs($d) . ' hari',
        };
    }

    protected function className($class): string
    {
        $parts = array_filter([
            $class->programPackage?->package_name ?? $class->privatePackage?->package_name,
            $class->level?->level_name,
        ]);

        return $class->getAttribute('class_name')
            ?: ($parts ? implode(' ', $parts) : 'Kelas #' . $class->id);
    }
}