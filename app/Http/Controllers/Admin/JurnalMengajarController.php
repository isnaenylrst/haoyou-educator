<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Notifications\JurnalMengajarReminder;
use App\Services\MissingJournalFinder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class JurnalMengajarController extends Controller
{
    private const MAX_RANGE_DAYS = 62;
    private const REMIND_COOLDOWN_HOURS = 2;

    public function __construct(
        private MissingJournalFinder $finder
    ) {
    }

    /**
     * Daftar jurnal mengajar, statistik, dan jurnal yang belum diisi.
     */
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange(
            $request->query('from'),
            $request->query('to')
        );

        $tab = $request->query('tab') === 'belum'
            ? 'belum'
            : 'masuk';

        $teacherId = $request->integer('teacher_id') ?: null;
        $classId = $request->integer('class_id') ?: null;
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('class_status');

        $journalBase = TeachingJournal::query()
            ->whereBetween('session_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->when($teacherId, fn ($q) => $q->where(
                fn ($w) => $w
                    ->where('teacher_id', $teacherId)
                    ->orWhere('substitute_teacher_id', $teacherId)
            ))
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when(
                $search !== '',
                fn ($q) => $q->where(function ($w) use ($search) {
                    $w->whereHas(
                        'class',
                        fn ($c) => $c->where(
                            'class_name',
                            'like',
                            "%{$search}%"
                        )
                    )->orWhereHas(
                        'teacher',
                        fn ($t) => $t->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                    )->orWhereHas(
                        'substituteTeacher',
                        fn ($t) => $t->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                    );
                })
            );

        $stats = [
            'masuk' => (clone $journalBase)->count(),

            'conducted' => (clone $journalBase)
                ->where('class_status', 'Conducted')
                ->count(),

            'not_conducted' => (clone $journalBase)
                ->whereIn('class_status', ['Cancelled', 'Rescheduled'])
                ->count(),

            'substitute' => (clone $journalBase)
                ->where('is_substitute', true)
                ->count(),
        ];

        // Cari sesi yang sudah lewat tetapi jurnalnya belum ada.
        $missing = $this->finder
            ->find($from, $to, $teacherId)
            ->when(
                $classId,
                fn ($items) => $items
                    ->filter(fn ($item) => $item->class->id == $classId)
            )
            ->when(
                $search !== '',
                fn ($items) => $items->filter(
                    fn ($item) =>
                        stripos(
                            $item->class->class_name,
                            $search
                        ) !== false
                        || stripos(
                            $item->teacher->name ?? '',
                            $search
                        ) !== false
                )
            )
            ->values();

        $this->attachReminders($missing);

        $stats['belum'] = $missing->count();

        $stats['teachers_late'] = $missing
            ->pluck('teacher.id')
            ->unique()
            ->count();

        $byTeacher = $missing
            ->groupBy(fn ($item) => $item->teacher->id)
            ->map(fn ($group) => [
                'teacher' => $group->first()->teacher,
                'count' => $group->count(),
                'last_reminded' => $group
                    ->pluck('reminded_at')
                    ->filter()
                    ->sortByDesc(fn ($date) => $date->timestamp)
                    ->first(),
            ])
            ->sortByDesc('count')
            ->values();

        $journals = null;
        $missingPage = null;

        if ($tab === 'masuk') {
            $journals = (clone $journalBase)
                ->with([
                    'teacher',
                    'substituteTeacher',
                    'class.level.program',
                    'class.level.category',
                    'class.programPackage.program',
                    'classSchedule',
                    'material',
                    'attendances.student',
                ])
                ->when(
                    in_array(
                        $status,
                        ['Conducted', 'Cancelled', 'Rescheduled'],
                        true
                    ),
                    fn ($q) => $q->where('class_status', $status)
                )
                ->orderByDesc('session_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        } else {
            $missingPage = $this->paginateCollection(
                $missing,
                15,
                $request
            );
        }

        $teachers = Teacher::where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $classes = ClassModel::orderBy('class_name')
            ->get(['id', 'class_name']);

        return view('admin.jurnal-mengajar', compact(
            'tab',
            'from',
            'to',
            'stats',
            'journals',
            'missingPage',
            'byTeacher',
            'teachers',
            'classes'
        ));
    }

    /**
     * Detail jurnal untuk modal melalui JSON.
     */
    public function show(TeachingJournal $journal): JsonResponse
    {
        $journal->load([
            'class',
            'teacher',
            'substituteTeacher',
            'material',
            'attendances.student',
        ]);

        return response()->json([
            'id' => $journal->id,
            'class_name' => $journal->class?->class_name,
            'is_private' => (bool) $journal->class?->private_package_id,
            'session_date' => $journal->session_date?->format('Y-m-d'),
            'class_status' => $journal->class_status,
            'teacher_name' => $journal->teacher?->name,
            'substitute_name' => $journal->substituteTeacher?->name,
            'material_title' => $journal->material?->title,
            'learning_activities' => $journal->learning_activities,
            'problems' => $journal->problems,
            'solutions' => $journal->solutions,
            'results' => $journal->results,
            'notes' => $journal->notes,
            'attendances' => $journal->attendances
                ->sortBy(fn ($attendance) => $attendance->student?->name)
                ->map(fn ($attendance) => [
                    'student_id' => $attendance->student_id,
                    'name' => $attendance->student?->name,
                    'status' => $attendance->status,
                    'note' => $attendance->note,
                ])
                ->values(),
        ]);
    }

    /**
     * Kirim pengingat jurnal yang belum diisi kepada guru.
     */
    public function remind(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id',
            ],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'keys' => ['nullable', 'array'],
            'keys.*' => ['string', 'max:40'],
        ]);

        [$from, $to] = $this->resolveRange(
            $data['from'],
            $data['to']
        );

        $teacher = Teacher::with('user')
            ->findOrFail($data['teacher_id']);

        if (! $teacher->user) {
            return back()->with(
                'error',
                "Laoshi {$teacher->name} belum punya akun login, pengingat tidak bisa dikirim."
            );
        }

        $items = $this->finder->find($from, $to, $teacher->id);

        if (! empty($data['keys'])) {
            $items = $items
                ->whereIn('key', $data['keys'])
                ->values();
        }

        if ($items->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada jurnal tertunggak untuk laoshi ini (mungkin baru saja diisi).'
            );
        }

        $this->attachReminders($items);

        $cooldown = now()->subHours(self::REMIND_COOLDOWN_HOURS);

        $fresh = $items
            ->filter(
                fn ($item) =>
                    ! $item->reminded_at
                    || $item->reminded_at->lt($cooldown)
            )
            ->values();

        if ($fresh->isEmpty()) {
            return back()->with(
                'warning',
                "Laoshi {$teacher->name} sudah diingatkan kurang dari "
                . self::REMIND_COOLDOWN_HOURS
                . ' jam lalu.'
            );
        }

        $sessions = $fresh->map(fn ($item) => [
            'key' => $item->key,
            'class_name' => $item->class->class_name,
            'date' => $item->date->format('Y-m-d'),
            'date_label' => $item->date
                ->copy()
                ->locale('id')
                ->translatedFormat('l, d M Y'),
            'time_label' => substr(
                $item->schedule->start_time,
                0,
                5
            ) . '–' . substr(
                $item->schedule->end_time,
                0,
                5
            ),
        ])->all();

        $teacher->user->notify(
            new JurnalMengajarReminder($sessions, 'Admin')
        );

        return back()->with(
            'success',
            "Pengingat terkirim ke Laoshi {$teacher->name} ({$fresh->count()} sesi)."
        );
    }

    /**
     * Isi waktu pengingat terakhir berdasarkan notifikasi yang tersimpan.
     */
    private function attachReminders(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $userIds = $items
            ->map(fn ($item) => $item->teacher->user_id)
            ->filter()
            ->unique()
            ->values();

        $map = [];

        DatabaseNotification::query()
            ->where('type', JurnalMengajarReminder::class)
            ->whereIn('notifiable_id', $userIds)
            ->where(
                'created_at',
                '>=',
                now()->subDays(self::MAX_RANGE_DAYS)
            )
            ->orderBy('created_at')
            ->get()
            ->each(function ($notification) use (&$map) {
                foreach (($notification->data['keys'] ?? []) as $key) {
                    $map[
                        $notification->notifiable_id . '|' . $key
                    ] = $notification->created_at;
                }
            });

        foreach ($items as $item) {
            $item->reminded_at =
                $map[$item->teacher->user_id . '|' . $item->key]
                ?? null;
        }
    }

    private function resolveRange(
        ?string $fromInput,
        ?string $toInput
    ): array {
        $to = $this->parseDate($toInput) ?? now()->startOfDay();

        $from = $this->parseDate($fromInput)
            ?? $to->copy()->subDays(13);

        if ($to->gt(now())) {
            $to = now()->startOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ((int) $from->diffInDays($to, true) > self::MAX_RANGE_DAYS) {
            $from = $to->copy()->subDays(self::MAX_RANGE_DAYS);
        }

        return [$from, $to];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)
                ->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function paginateCollection(
        Collection $items,
        int $perPage,
        Request $request
    ): LengthAwarePaginator {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
