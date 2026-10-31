<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\ClassModel;
use App\Models\ClassTask;
use App\Models\Consultation;
use App\Models\Document;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya sumber data notifikasi guru.
 * Dipakai oleh: halaman Notifikasi, lonceng di layout, dan kartu "Perlu Tindakan" di Dashboard,
 * sehingga angka di ketiga tempat itu selalu sama.
 *
 * Dua jenis notifikasi:
 *  - actionable   : masih menunggu tindakan guru (selalu dihitung selama belum diselesaikan)
 *  - informational: hasil review / surat (dihitung "baru" jika lebih baru dari notifications_seen_at)
 */
class TeacherNotificationService
{
    /** Tipe dokumen yang dianggap surat dari Kurikulum (SOP & dokumen lain TIDAK termasuk). */
    public const LETTER_TYPES = ['SURAT_LIBUR', 'SURAT_DINAS', 'LOA'];

    /** Jika guru belum pernah membuka Notifikasi, informasi lebih lama dari ini tidak dianggap baru. */
    public const FALLBACK_DAYS = 14;

    /** Notifikasi informasi lebih lama dari ini tidak ditampilkan (yang butuh tindakan selalu tampil). */
    public const INFO_WINDOW_DAYS = 90;

    /** Filter tab di halaman Notifikasi. */
    public const FILTERS = ['semua', 'tindakan', 'baru', 'review', 'surat', 'konsultasi', 'tugas'];

    /** Kelompok tiap tipe notifikasi (dipakai tab filter). */
    public const GROUPS = [
        'review' => ['material_approved', 'material_rejected', 'journal_reviewed', 'journal_revision',
                     'report_approved', 'report_returned', 'certificate_approved', 'certificate_rejected', 'leave_result'],
        'surat' => ['document', 'sop_new'],
        'konsultasi' => ['consultation', 'consultation_pending', 'consultation_cancelled'],
        'tugas' => ['task', 'task_done', 'class_assign', 'replacement_request'],
    ];

    private ?Collection $cache = null;

    public function __construct(private readonly Teacher $teacher)
    {
    }

    public static function for(Teacher $teacher): self
    {
        return new self($teacher);
    }

    public function seenAt(): Carbon
    {
        return $this->teacher->notifications_seen_at
            ? Carbon::parse($this->teacher->notifications_seen_at)
            : now()->subDays(self::FALLBACK_DAYS);
    }

    /** Tandai semua informasi sebagai sudah dibaca (tanpa menyentuh updated_at guru). */
    public function markSeen(): void
    {
        DB::table('teachers')->where('id', $this->teacher->id)->update(['notifications_seen_at' => now()]);
        $this->teacher->notifications_seen_at = now();
        $this->cache = null;
    }

    /**
     * Semua notifikasi, terbaru di atas.
     * Setiap item: key, type, title, category, time (Carbon|null), note, action, actionable, is_new
     */
    public function all(): Collection
    {
        return $this->cache ??= $this->build();
    }

    public function actionableCount(): int
    {
        return $this->all()->where('actionable', true)->count();
    }

    /** Data lonceng: daftar baris + total angka merah. */
    public function bell(): array
    {
        $all = $this->all();

        $rows = [
            ['type' => 'class_assign',         'label' => 'Kelas menunggu konfirmasi',     'hint' => 'Terima atau tolak di Schedule',        'url' => route('teacher.schedule')],
            ['type' => 'replacement_request',  'label' => 'Permintaan menggantikan kelas', 'hint' => 'Dari guru lain, jawab di Schedule',     'url' => route('teacher.schedule')],
            ['type' => 'consultation',         'label' => 'Jadwal konsultasi mendatang',   'hint' => 'Hadir sesuai jadwal',                  'url' => route('teacher.notifikasi', ['filter' => 'konsultasi'])],
            ['type' => 'task',                 'label' => 'Tugas dari Kurikulum',          'hint' => 'Tandai selesai di halaman Notifikasi', 'url' => route('teacher.notifikasi')],
            ['type' => 'report_returned',      'label' => 'Progress report dikembalikan',  'hint' => 'Perbaiki lalu ajukan ulang',           'url' => route('teacher.progress-report')],
            ['type' => 'journal_revision',     'label' => 'Jurnal perlu revisi',           'hint' => 'Dikembalikan Kurikulum',               'url' => route('teacher.teaching-log')],
            ['type' => 'material_rejected',    'label' => 'LP / PPT perlu direvisi',       'hint' => 'Upload ulang di menu Kelas',           'url' => route('teacher.kelas')],
            ['type' => 'certificate_rejected', 'label' => 'Sertifikat ditolak',            'hint' => 'Perbaiki di menu Sertifikat',          'url' => route('teacher.sertifikat')],
        ];

        $out = [];
        foreach ($rows as $r) {
            $n = $all->where('type', $r['type'])->where('actionable', true)->count();
            if ($n > 0) {
                $out[] = $r + ['count' => $n];
            }
        }

        $infoNew = $all->where('actionable', false)->where('is_new', true)->count();
        if ($infoNew > 0) {
            $out[] = [
                'type' => 'info', 'label' => 'Hasil review baru & surat', 'hint' => 'Buka halaman Notifikasi',
                'url' => route('teacher.notifikasi'), 'count' => $infoNew,
            ];
        }

        return ['rows' => $out, 'total' => array_sum(array_column($out, 'count'))];
    }


    /** Jumlah item per tab filter (untuk badge di halaman Notifikasi). */
    public function tabCounts(): array
    {
        $all = $this->all();

        return [
            'semua'      => $all->count(),
            'tindakan'   => $all->where('actionable', true)->count(),
            'baru'       => $all->where('is_new', true)->count(),
            'review'     => $all->where('group', 'review')->count(),
            'surat'      => $all->where('group', 'surat')->count(),
            'konsultasi' => $all->where('group', 'konsultasi')->count(),
            'tugas'      => $all->where('group', 'tugas')->count(),
            // informasi yang belum dibaca (di luar yang butuh tindakan) -> untuk tombol "Tandai semua dibaca"
            'info_baru'  => $all->where('actionable', false)->where('is_new', true)->count(),
        ];
    }

    /** Item sesuai tab filter. */
    public function filtered(string $filter): Collection
    {
        $all = $this->all();

        return match ($filter) {
            'tindakan' => $all->where('actionable', true)->values(),
            'baru'     => $all->where('is_new', true)->values(),
            'review', 'surat', 'konsultasi', 'tugas' => $all->where('group', $filter)->values(),
            default    => $all,
        };
    }

    // =====================================================================

    private function build(): Collection
    {
        $t    = $this->teacher;
        $seen = $this->seenAt();
        $n    = collect();

        $windowStart = now()->subDays(self::INFO_WINDOW_DAYS);

        $push = function (array $item) use ($n, $seen, $windowStart) {
            $item += ['note' => null, 'action' => null, 'actionable' => false, 'time' => null];

            if ($item['time'] && !($item['time'] instanceof \Carbon\CarbonInterface)) {
                $item['time'] = Carbon::parse($item['time']);
            }

            // Informasi lama (> INFO_WINDOW_DAYS) disembunyikan; yang butuh tindakan selalu tampil.
            if (!$item['actionable'] && $item['time'] && $item['time']->lt($windowStart)) {
                return;
            }

            $item['group'] = 'lainnya';
            foreach (self::GROUPS as $group => $types) {
                if (in_array($item['type'], $types, true)) {
                    $item['group'] = $group;
                    break;
                }
            }

            // Item yang butuh tindakan selalu dianggap baru; informasi baru jika lebih baru dari terakhir dibuka.
            $item['is_new'] = $item['actionable'] || ($item['time'] && $item['time']->gt($seen));

            $n->push($item);
        };

                // 1. Surat dari Kurikulum yang ditujukan ke guru ini
        Document::letters()
            ->join('document_recipients as r', 'r.document_id', '=', 'documents.id')
            ->where('r.user_id', $t->user_id)
            ->select('documents.*', 'r.read_at as my_read_at')
            ->get()
            ->each(fn ($d) => $push([
                'key'      => "doc-{$d->id}",
                'type'     => 'document',
                'title'    => $d->title,
                'category' => 'Surat Pemberitahuan — ' . $d->type_label,
                'time'     => $d->uploaded_at ?? $d->created_at,
                'note'     => $d->description,
                'unread'   => $d->my_read_at === null,
                'action'   => ['kind' => 'link', 'label' => 'Buka Surat', 'url' => route('teacher.surat.show', $d)],
            ]));

        // 2. LP & PPT: disetujui (info) / ditolak (harus upload ulang = actionable)
        TeacherMaterial::where('teacher_id', $t->id)->whereIn('status', ['Approved', 'Rejected'])->get()
            ->each(fn ($m) => $push([
                'key' => "mat-{$m->id}",
                'type' => $m->status === 'Rejected' ? 'material_rejected' : 'material_approved',
                'title' => $m->status === 'Approved'
                    ? "LP & PPT \"{$m->ppt_title}\" disetujui"
                    : "LP & PPT \"{$m->ppt_title}\" perlu direvisi",
                'category' => 'Hasil Review LP & PPT', 'time' => $m->approved_at ?? $m->updated_at,
                'note' => $m->status === 'Rejected' ? $m->review_note : null,
                'actionable' => $m->status === 'Rejected',
                'action' => $m->status === 'Rejected'
                    ? ['kind' => 'link', 'label' => 'Upload Ulang', 'url' => route('teacher.kelas')] : null,
            ]));

        // 3. Jurnal & absensi
        TeachingJournal::with('class')->where('teacher_id', $t->id)->whereIn('status', ['Reviewed', 'Revision'])->get()
            ->each(function ($j) use ($push) {
                $cn = $j->class->class_name ?? '-';
                $push([
                    'key' => "jur-{$j->id}",
                    'type' => $j->status === 'Revision' ? 'journal_revision' : 'journal_reviewed',
                    'title' => $j->status === 'Reviewed' ? "Jurnal sesi {$cn} sudah direview" : "Jurnal sesi {$cn} perlu direvisi",
                    'category' => 'Hasil Review Jurnal & Absensi', 'time' => $j->updated_at,
                    'actionable' => $j->status === 'Revision',
                    'action' => $j->status === 'Revision'
                        ? ['kind' => 'link', 'label' => 'Buka Teaching Log', 'url' => route('teacher.teaching-log')] : null,
                ]);
            });

        // 4. Progress report: disetujui (info) / dikembalikan (actionable)
        ProgressReport::with('student')->where('teacher_id', $t->id)->approved()->get()
            ->each(fn ($r) => $push([
                'key' => "rep-{$r->id}", 'type' => 'report_approved',
                'title' => "Progress Report {$r->report_period} " . ($r->student->name ?? '-') . ' disetujui',
                'category' => 'Hasil Review Progress Report', 'time' => $r->reviewed_at,
            ]));

        ProgressReport::with('student')->where('teacher_id', $t->id)
            ->where('status', 'Draft')->whereNotNull('returned_at')->get()
            ->each(fn ($r) => $push([
                'key' => "repret-{$r->id}", 'type' => 'report_returned',
                'title' => 'Progress Report ' . ($r->student->name ?? '-') . ' dikembalikan, perlu diperbaiki',
                'category' => 'Hasil Review Progress Report', 'time' => $r->returned_at,
                'note' => $r->review_note, 'actionable' => true,
                'action' => ['kind' => 'link', 'label' => 'Perbaiki', 'url' => $r->enrollment_id
                    ? route('teacher.progress-report.form', $r->enrollment_id) : route('teacher.progress-report')],
            ]));

        // 5. Sertifikat buatan guru yang sudah direview Kurikulum (sebelumnya TIDAK ada notifikasinya)
        Certificate::with('student')->where('teacher_id', $t->id)->whereIn('status', ['Approved', 'Rejected'])->get()
            ->each(fn ($c) => $push([
                'key' => "cert-{$c->id}",
                'type' => $c->status === 'Rejected' ? 'certificate_rejected' : 'certificate_approved',
                'title' => 'Sertifikat ' . ($c->student->name ?? '-') . ($c->status === 'Approved' ? ' disetujui' : ' ditolak'),
                'category' => 'Hasil Review Sertifikat', 'time' => $c->reviewed_at ?? $c->updated_at,
                'note' => $c->status === 'Rejected' ? $c->review_note : null,
                'actionable' => $c->status === 'Rejected',
                'action' => $c->status === 'Rejected'
                    ? ['kind' => 'link', 'label' => 'Perbaiki Sertifikat', 'url' => route('teacher.sertifikat')] : null,
            ]));

        // 6. Cuti / ganti kelas yang sudah diputuskan
        TeacherLeave::with('replacementTeacher')->where('teacher_id', $t->id)->whereIn('status', ['Approved', 'Rejected'])->get()
            ->each(function ($l) use ($push) {
                $pg = $l->replacementTeacher ? " (pengganti: {$l->replacementTeacher->name})" : '';
                $push([
                    'key' => "leave-{$l->id}", 'type' => 'leave_result',
                    'title' => $l->status === 'Approved' ? "Cuti/Ganti kelas disetujui{$pg}" : 'Cuti/Ganti kelas ditolak',
                    'category' => 'Hasil Review Cuti/Ganti Kelas', 'time' => $l->approved_at ?? $l->updated_at,
                    'note' => $l->status === 'Rejected' ? $l->review_note : null,
                ]);
            });

        // 7. Tugas dari Kurikulum (Open = actionable; Done tampil 14 hari)
        ClassTask::with('class')->where('teacher_id', $t->id)
            ->where(fn ($q) => $q->where('status', 'Open')->orWhere('done_at', '>=', now()->subDays(14)))->get()
            ->each(function ($task) use ($push) {
                $cn  = $task->class->class_name ?? '-';
                $due = $task->due_date ? ' · batas ' . $task->due_date->translatedFormat('d M Y') : '';
                $open = $task->status === 'Open';
                $push([
                    'key' => "task-{$task->id}", 'type' => $open ? 'task' : 'task_done',
                    'title' => "Tugas perbaikan kelas {$cn}: {$task->title}",
                    'category' => 'Tugas dari Kurikulum' . $due, 'time' => $task->created_at,
                    'note' => $task->description, 'actionable' => $open,
                    'action' => $open ? ['kind' => 'task', 'url' => route('teacher.tugas.complete', $task)] : ['kind' => 'done'],
                ]);
            });

        // 8. Kelas menunggu konfirmasi: diberikan langsung kepada saya, atau diajukan admin ke beberapa guru
        ClassModel::whereIn('status', ['Open', 'Running'])
        ->where(function ($q) use ($t) {
            $q->where(fn ($q) => $q->where('teacher_id', $t->id)->where('teacher_response', 'Pending'))
            ->orWhereHas('teacherOffers', fn ($o) => $o->where('teacher_id', $t->id)->where('status', 'Pending'));
        })->get()
        ->each(fn ($c) => $push([
            'key' => "class-{$c->id}", 'type' => 'class_assign',
            'title' => (int) $c->teacher_id === (int) $t->id
                ? "Kelas {$c->class_name} diberikan kepada Anda, mohon konfirmasi"
                : "Kelas {$c->class_name} diajukan kepada Anda, mohon konfirmasi",
            // category, time, actionable, action: tetap seperti sebelumnya
        ]));

        // 9. Permintaan dari guru lain agar saya menggantikan kelasnya
        TeacherLeave::with(['teacher', 'classSchedule.class'])
            ->where('replacement_teacher_id', $t->id)->where('status', 'Pending')->where('replacement_response', 'Pending')->get()
            ->each(function ($l) use ($push) {
                $cn   = optional(optional($l->classSchedule)->class)->class_name ?? '-';
                $when = $l->leave_date ? ' pada ' . $l->leave_date->translatedFormat('d M Y') : '';
                $push([
                    'key' => "repl-{$l->id}", 'type' => 'replacement_request',
                    'title' => ($l->teacher->name ?? 'Guru lain') . " meminta Anda menggantikan kelas {$cn}{$when}",
                    'category' => 'Permintaan Menggantikan', 'time' => $l->created_at,
                    'note' => $l->reason, 'actionable' => true,
                    'action' => ['kind' => 'link', 'label' => 'Terima / Tolak', 'url' => route('teacher.schedule')],
                ]);
            });

        // 10. SOP baru dari Kurikulum (punya kategori sendiri, bukan "surat")
        Document::sopForTeachers()->get()
            ->each(fn ($d) => $push([
                'key' => "sop-{$d->id}", 'type' => 'sop_new', 'title' => $d->title,
                'category' => 'SOP Baru', 'time' => $d->uploaded_at ?? $d->created_at,
                // deep link: halaman SOP menyorot kartu SOP ini (#sop-ID)
                'action' => ['kind' => 'link', 'label' => 'Buka SOP', 'url' => route('teacher.sop') . '#sop-' . $d->id],
            ]));

        // 11. Jadwal konsultasi (LP & PPT / Direktur / Trial Teaching)
        Consultation::with('teacherMaterial')->where('teacher_id', $t->id)
            ->whereIn('status', ['Belum Terjadwal', 'Terjadwal', 'Dibatalkan'])->get()
            ->each(function ($c) use ($push) {
                $label   = $c->type_label;
                $related = $c->teacherMaterial?->ppt_title ? 'Terkait pengajuan: ' . $c->teacherMaterial->ppt_title : null;
                $note    = trim(implode("\n", array_filter([$related, $c->notes])));

                if ($c->status === 'Terjadwal' && $c->scheduled_at) {
                    // Masih tampil sampai 2 jam setelah waktu mulai; setelah itu dianggap lewat.
                    if ($c->scheduled_at->lt(now()->subHours(2))) {
                        return;
                    }
                    $push([
                        'key' => "kons-{$c->id}", 'type' => 'consultation',
                        'title' => "{$label}: " . $c->scheduled_at->translatedFormat('l, d F Y') . ' pukul ' . $c->scheduled_at->format('H:i'),
                        'category' => 'Jadwal Konsultasi', 'time' => $c->updated_at, 'note' => $note ?: null,
                        'actionable' => true,
                        'action' => ['kind' => 'link', 'label' => 'Lihat Pengajuan', 'url' => route('teacher.kelas')],
                    ]);
                } elseif ($c->status === 'Belum Terjadwal') {
                    $push([
                        'key' => "kons-{$c->id}", 'type' => 'consultation_pending',
                        'title' => "Pengajuan {$label} menunggu jadwal dari Kurikulum",
                        'category' => 'Jadwal Konsultasi', 'time' => $c->created_at, 'note' => $note ?: null,
                    ]);
                } elseif ($c->status === 'Dibatalkan') {
                    $push([
                        'key' => "kons-{$c->id}", 'type' => 'consultation_cancelled',
                        'title' => "{$label} dibatalkan",
                        'category' => 'Jadwal Konsultasi', 'time' => $c->updated_at, 'note' => $note ?: null,
                    ]);
                }
            });

        // Yang butuh tindakan di atas, lalu terbaru.
        return $n->sort(function ($a, $b) {
            if ($a['actionable'] !== $b['actionable']) {
                return $a['actionable'] ? -1 : 1;
            }
            return ($b['time']?->timestamp ?? 0) <=> ($a['time']?->timestamp ?? 0);
        })->values();
    }
}