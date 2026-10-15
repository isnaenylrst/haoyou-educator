<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
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
            $data['materials'] = $selected
                ? Material::with(['uploader.curriculum', 'vocabs'])
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

        $ext = pathinfo($material->material_file_path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('public')->download(
            $material->material_file_path,
            Str::slug($material->title) . '.' . $ext
        );
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

        $schedules = ClassSchedule::query()
            ->with([
                'class.teacher',
                'class.level.program',
                'class.level.category',
                'class.programPackage.program',
                'class.enrollments' => fn ($q) => $q->where('status', 'Active')->with('student:id,name'),
            ])
            ->where('day', $dayName)
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

        if ($schedules->isEmpty()) {
            return collect();
        }

        $classIds = $schedules->pluck('class_id')->unique()->values();
        $levelIds = $schedules->pluck('class.level_id')->filter()->unique()->values();

        $past = TeachingJournal::with('material:id,meeting_number,title')
            ->whereIn('class_id', $classIds)
            ->whereNotNull('material_id')
            ->where('class_status', 'Conducted')
            ->whereDate('session_date', '<', $dateStr)
            ->get()
            ->groupBy('class_id');

        $todayJournals = TeachingJournal::with('material:id,meeting_number,title')
            ->whereIn('class_schedule_id', $schedules->pluck('id'))
            ->whereDate('session_date', $dateStr)
            ->get()
            ->keyBy('class_schedule_id');

        $materials = Material::withCount('vocabs')
            ->whereIn('level_id', $levelIds)
            ->get()
            ->groupBy('level_id')
            ->map(fn ($g) => $g->keyBy('meeting_number'));

        $now = now();

        return $schedules->map(function ($schedule) use ($past, $todayJournals, $materials, $dateStr, $now) {
            $class = $schedule->class;
            $levelMaterials = $class->level_id ? ($materials->get($class->level_id) ?? collect()) : collect();

            $lastJournal = $past->get($class->id, collect())
                ->sortBy(fn ($j) => $j->material->meeting_number)
                ->last();

            $expected = ($lastJournal?->material?->meeting_number ?? 0) + 1;
            $journal = $todayJournals->get($schedule->id);

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
