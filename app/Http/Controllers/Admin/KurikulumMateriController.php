<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Material;
use App\Models\ProgramLevel;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Services\MissingJournalFinder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tampilan READ-ONLY untuk admin. Materi diunggah tim kurikulum (tabel `materials`),
 * admin hanya melihat materi per sesi/per siswa dan katalog materi per level.
 */
class KurikulumMateriController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'katalog' ? 'katalog' : 'harian';
        $date = $this->parseDate($request->query('date')) ?? now()->startOfDay();

        $data = [
            'tab'      => $tab,
            'date'     => $date,
            'teachers' => Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']),
        ];

        if ($tab === 'harian') {
            $sessions = $this->buildDaily($date, $request);

            $data['sessions'] = $sessions;
            $data['stats'] = [
                'sesi'      => $sessions->count(),
                'siap'      => $sessions->whereIn('state', ['rencana', 'sudah'])->count(),
                'belum'     => $sessions->whereIn('state', ['belum_diupload', 'tanpa_level'])->count(),
                'siswa'     => $sessions->flatMap(fn ($s) => $s->students)->unique()->count(),
            ];
        } else {
            $levels = ProgramLevel::with(['program', 'category'])
                ->withCount('materials')
                ->orderBy('program_id')
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->get();

            $selected = $request->filled('level_id')
                ? $levels->firstWhere('id', $request->integer('level_id'))
                : null;
            $selected ??= $levels->firstWhere('materials_count', '>', 0) ?? $levels->first();

            $data['levels'] = $levels;
            $data['selectedLevel'] = $selected;
            // Admin hanya melihat materi bertujuan Guru (materi Siswa/Huahua tidak ikut)
            $data['materials'] = $selected
                ? Material::query()
                    ->where('audience', 'Guru')
                    ->with(['uploader.curriculum', 'vocabs'])
                    ->where('level_id', $selected->id)
                    ->orderBy('meeting_number')
                    ->get()
                : collect();
        }

        return view('admin.kurikulum-materi', $data);
    }

    public function unduh(Material $material)
    {
        if (! $material->material_file_path || ! Storage::disk('public')->exists($material->material_file_path)) {
            return back()->with('error', 'File materi belum tersedia di penyimpanan (belum diunggah tim kurikulum).');
        }

        $ext  = pathinfo($material->material_file_path, PATHINFO_EXTENSION) ?: 'pdf';
        $name = (Str::slug($material->title) ?: 'materi-' . $material->id) . '.' . $ext;

        return response()->download(storage_path('app/public/' . $material->material_file_path), $name);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Sesi pada tanggal tertentu + "pertemuan ke berapa" & materinya.
     *
     * Aturan pertemuan: guru memilih materi sendiri di jurnal, jadi pertemuan
     * berikutnya = nomor pertemuan tertinggi yang sudah tercatat di jurnal
     * (status Conducted, sebelum tanggal ini) + 1.
     *
     * state: sudah | tidak_berjalan | rencana | belum_diupload | tanpa_level
     */
    private function buildDaily(Carbon $date, Request $request): Collection
    {
        $dayName = MissingJournalFinder::DAYS[$date->dayOfWeekIso];
        $dateStr = $date->toDateString();
        $teacherId = $request->integer('teacher_id') ?: null;
        $search = trim((string) $request->query('search', ''));

        // 1) Ambil class_sessions pada tanggal ini
        $daySessions = ClassSession::with([
                'class.teacher',
                'class.level.program',
                'class.level.category',
                'class.programPackage.program',
                'class.enrollments' => fn ($q) => $q->where('status', 'Active')->with('student:id,name'),
                'schedule',
                'journal',
            ])
            ->whereDate('session_date', $dateStr)
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->whereHas('class', function ($q) use ($teacherId, $search) {
                $q->whereIn('status', ['Open', 'Running', 'Completed'])
                    ->when($teacherId, fn ($t) => $t->where('teacher_id', $teacherId))
                    ->when($search !== '', fn ($s) => $s->where(function ($w) use ($search) {
                        $w->where('class_name', 'like', "%{$search}%")
                            ->orWhereHas('enrollments', fn ($e) => $e->where('status', 'Active')
                                ->whereHas('student', fn ($st) => $st->where('name', 'like', "%{$search}%")));
                    }));
            })
            ->orderBy('start_time')
            ->get();

        $classesWithSessions = $daySessions->pluck('class_id')->unique()->values()->all();

        // Cari kelas yang aslinya hari ini tapi dipindah keluar dari tanggal ini
        $movedOutClassIds = ClassSession::whereDate('original_date', $dateStr)
            ->whereDate('session_date', '!=', $dateStr)
            ->where('status', '!=', 'Cancelled')
            ->pluck('class_id')
            ->unique()
            ->values()
            ->all();

        // 2) Pola mingguan untuk kelas tanpa pertemuan bertanggal hari ini dan tidak dipindah keluar
        $fallbackSchedules = ClassSchedule::query()
            ->with([
                'class.teacher',
                'class.level.program',
                'class.level.category',
                'class.programPackage.program',
                'class.enrollments' => fn ($q) => $q->where('status', 'Active')->with('student:id,name'),
            ])
            ->where('day', $dayName)
            ->whereNull('effective_until')
            ->whereNotIn('class_id', array_merge($classesWithSessions, $movedOutClassIds))
            ->whereHas('class', function ($q) use ($dateStr, $teacherId, $search) {
                $q->whereIn('status', ['Open', 'Running', 'Completed'])
                    ->whereDate('start_date', '<=', $dateStr)
                    ->where(fn ($e) => $e->whereNull('end_date')->orWhereDate('end_date', '>=', $dateStr))
                    ->when($teacherId, fn ($t) => $t->where('teacher_id', $teacherId))
                    ->when($search !== '', fn ($s) => $s->where(function ($w) use ($search) {
                        $w->where('class_name', 'like', "%{$search}%")
                            ->orWhereHas('enrollments', fn ($e) => $e->where('status', 'Active')
                                ->whereHas('student', fn ($st) => $st->where('name', 'like', "%{$search}%")));
                    }));
            })
            ->orderBy('start_time')
            ->get();

        $items = collect();
        foreach ($daySessions as $cs) {
            $class = $cs->class;
            if (! $class) continue;

            $sch = $cs->schedule ?? new ClassSchedule([
                'id'         => $cs->class_schedule_id,
                'class_id'   => $cs->class_id,
                'day'        => $dayName,
                'start_time' => $cs->start_time,
                'end_time'   => $cs->end_time,
                'room'       => $cs->room,
            ]);
            $sch->id = $cs->class_schedule_id ?? $sch->id;
            $sch->setRelation('class', $class);
            $sch->start_time = $cs->start_time;
            $sch->end_time   = $cs->end_time;
            if ($cs->room) {
                $sch->room = $cs->room;
            }

            $items->push((object) [
                'schedule'     => $sch,
                'classSession' => $cs,
                'class'        => $class,
            ]);
        }

        foreach ($fallbackSchedules as $fs) {
            $items->push((object) [
                'schedule'     => $fs,
                'classSession' => null,
                'class'        => $fs->class,
            ]);
        }

        if ($items->isEmpty()) {
            return collect();
        }

        $classIds = $items->pluck('class.id')->unique()->values();
        $levelIds = $items->pluck('class.level_id')->filter()->unique()->values();

        $past = TeachingJournal::with('material:id,meeting_number,title')
            ->whereIn('class_id', $classIds)
            ->whereNotNull('material_id')
            ->where('class_status', 'Conducted')
            ->whereDate('session_date', '<', $dateStr)
            ->get()
            ->groupBy('class_id');

        $todayJournals = TeachingJournal::with('material:id,meeting_number,title')
            ->whereDate('session_date', $dateStr)
            ->get();
        $todayBySession = $todayJournals->whereNotNull('class_session_id')->keyBy('class_session_id');
        $todayBySchedule = $todayJournals->keyBy('class_schedule_id');

        $materials = Material::query()
            ->where('audience', 'Guru')
            ->withCount('vocabs')
            ->whereIn('level_id', $levelIds)
            ->get()
            ->groupBy('level_id')
            ->map(fn ($g) => $g->keyBy('meeting_number'));

        $now = now();

        return $items->map(function ($item) use ($past, $todayBySession, $todayBySchedule, $materials, $dateStr, $now) {
            $schedule = $item->schedule;
            $classSession = $item->classSession;
            $class = $item->class;
            $levelMaterials = $class->level_id ? ($materials->get($class->level_id) ?? collect()) : collect();

            $lastJournal = $past->get($class->id, collect())
                ->sortBy(fn ($j) => $j->material->meeting_number)
                ->last();

            $expected = ($lastJournal?->material?->meeting_number ?? 0) + 1;
            $journal = $classSession
                ? ($todayBySession->get($classSession->id) ?? ($schedule->id ? $todayBySchedule->get($schedule->id) : null))
                : ($schedule->id ? $todayBySchedule->get($schedule->id) : null);

            $state = 'rencana';
            $number = $expected;
            $material = null;

            if ($journal && $journal->class_status !== 'Conducted') {
                $state = 'tidak_berjalan';
            } elseif ($journal && $journal->material) {
                $state = 'sudah';
                $number = $journal->material->meeting_number;
                $material = $levelMaterials->get($number);
            } elseif (! $class->level_id) {
                $state = 'tanpa_level';
            } else {
                $material = $levelMaterials->get($expected);
                $state = $material ? 'rencana' : 'belum_diupload';
            }

            $endsAt = Carbon::parse($dateStr . ' ' . $schedule->end_time);

            return (object) [
                'schedule'       => $schedule,
                'class'          => $class,
                'students'       => $class->enrollments->map(fn ($e) => $e->student?->name)->filter()->values(),
                'state'          => $state,
                'number'         => $number,
                'material'       => $material,
                'total'          => $levelMaterials->count(),
                'journal'        => $journal,
                'last_journal'   => $lastJournal,
                'journal_missing' => ! $journal && $endsAt->lte($now),
            ];
        })->values();
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}