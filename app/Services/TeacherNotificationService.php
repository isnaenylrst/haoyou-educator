<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\ClassModel;
use App\Models\ClassTask;
use App\Models\Document;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherNotificationService
{
    /** Tipe dokumen yang dianggap surat dari Kurikulum (SOP & dokumen lain TIDAK termasuk). */
    public const LETTER_TYPES = ['SURAT_LIBUR', 'SURAT_DINAS', 'LOA'];

    /** Jika guru belum pernah membuka Notifikasi, informasi lebih lama dari ini tidak dianggap baru. */
    public const FALLBACK_DAYS = 14;

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

    public function all(): Collection
    {
        return $this->cache ??= $this->build();
    }

    public function actionableCount(): int
    {
        return $this->all()->where('actionable', true)->count();
    }

    /** Data lonceng & kartu dashboard: daftar baris + total. */
    public function bell(): array
    {
        $all = $this->all();

        $rows = [
            ['type' => 'class_assign',         'label' => 'Kelas menunggu konfirmasi',     'hint' => 'Terima atau tolak di Schedule',        'url' => route('teacher.schedule')],
            ['type' => 'replacement_request',  'label' => 'Permintaan menggantikan kelas', 'hint' => 'Dari guru lain, jawab di Schedule',     'url' => route('teacher.schedule')],
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

    // =====================================================================

    private function build(): Collection
    {
        $t    = $this->teacher;
        $seen = $this->seenAt();
        $n    = collect();

        $push = function (array $item) use ($n, $seen) {
            $item += ['note' => null, 'action' => null, 'actionable' => false, 'time' => null];

            if ($item['time'] && !($item['time'] instanceof \Carbon\CarbonInterface)) {
                $item['time'] = Carbon::parse($item['time']);
            }

            // Item yang butuh tindakan selalu dianggap baru; informasi baru jika lebih baru dari terakhir dibuka.
            $item['is_new'] = $item['actionable'] || ($item['time'] && $item['time']->gt($seen));

            $n->push($item);
        };

        // 1. Surat dari Kurikulum
        Document::whereIn('document_type', self::LETTER_TYPES)
            ->where(function ($q) use ($t) {
                $q->where('visibility', 'Teacher')
                  ->orWhere(fn ($q2) => $q2->where('visibility', 'Private')->where('user_id', $t->user_id));
            })->get()->each(fn ($d) => $push([
                'key' => "doc-{$d->id}", 'type' => 'document', 'title' => $d->title,
                'category' => 'Surat Pemberitahuan', 'time' => $d->uploaded_at ?? $d->created_at,
                'action' => ['kind' => 'link', 'label' => 'Unduh Surat', 'url' => route('teacher.notifikasi.download', $d)],
            ]));

        // 2. LP & PPT
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

        // 4. Progress report
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

        // 5. Sertifikat
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

        // 6. Cuti / ganti kelas
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

        // 7. Tugas dari Kurikulum
        ClassTask::with('class')->where('teacher_id', $t->id)
            ->where(fn ($q) => $q->where('status', 'Open')->orWhere('done_at', '>=', now()->subDays(14)))->get()
            ->each(function ($task) use ($push) {
                $cn   = $task->class->class_name ?? '-';
                $due  = $task->due_date ? ' · batas ' . $task->due_date->translatedFormat('d M Y') : '';
                $open = $task->status === 'Open';
                $push([
                    'key' => "task-{$task->id}", 'type' => $open ? 'task' : 'task_done',
                    'title' => "Tugas perbaikan kelas {$cn}: {$task->title}",
                    'category' => 'Tugas dari Kurikulum' . $due, 'time' => $task->created_at,
                    'note' => $task->description, 'actionable' => $open,
                    'action' => $open ? ['kind' => 'task', 'url' => route('teacher.tugas.complete', $task)] : ['kind' => 'done'],
                ]);
            });

        // 8. Kelas baru menunggu konfirmasi
        ClassModel::where('teacher_id', $t->id)->where('teacher_response', 'Pending')
            ->whereIn('status', ['Open', 'Running'])->get()
            ->each(fn ($c) => $push([
                'key' => "class-{$c->id}", 'type' => 'class_assign',
                'title' => "Kelas {$c->class_name} diberikan kepada Anda, mohon konfirmasi",
                'category' => 'Penugasan Kelas', 'time' => $c->updated_at, 'actionable' => true,
                'action' => ['kind' => 'link', 'label' => 'Terima / Tolak', 'url' => route('teacher.schedule')],
            ]));

        // 9. Permintaan menggantikan dari guru lain
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

        // Yang butuh tindakan di atas, lalu terbaru.
        return $n->sort(function ($a, $b) {
            if ($a['actionable'] !== $b['actionable']) {
                return $a['actionable'] ? -1 : 1;
            }
            return ($b['time']?->timestamp ?? 0) <=> ($a['time']?->timestamp ?? 0);
        })->values();
    }
}