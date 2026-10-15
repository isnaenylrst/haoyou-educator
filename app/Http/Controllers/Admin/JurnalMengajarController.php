<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Notifications\JurnalMengajarReminder;
use App\Services\MissingJournalFinder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class JurnalMengajarController extends Controller
{
    private const MAX_RANGE_DAYS = 62;
    private const REMIND_COOLDOWN_HOURS = 2;

    public function __construct(private MissingJournalFinder $finder)
    {
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request->query('from'), $request->query('to'));

        $tab = $request->query('tab') === 'belum' ? 'belum' : 'masuk';
        $teacherId = $request->integer('teacher_id') ?: null;
        $search = trim((string) $request->query('search', ''));

        $journalBase = TeachingJournal::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($search !== '', fn ($q) => $q->whereHas(
                'class',
                fn ($c) => $c->where('class_name', 'like', "%{$search}%")
            ));

        $stats = [
            'masuk'         => (clone $journalBase)->count(),
            'conducted'     => (clone $journalBase)->where('class_status', 'Conducted')->count(),
            'not_conducted' => (clone $journalBase)->whereIn('class_status', ['Cancelled', 'Rescheduled'])->count(),
        ];

        // Sesi yang sudah lewat tapi jurnalnya belum ada
        $missing = $this->finder->find($from, $to, $teacherId)
            ->when($search !== '', fn ($c) => $c->filter(
                fn ($i) => stripos($i->class->class_name, $search) !== false
            ))
            ->values();
        $this->attachReminders($missing);

        $stats['belum'] = $missing->count();
        $stats['teachers_late'] = $missing->pluck('teacher.id')->unique()->count();

        $byTeacher = $missing
            ->groupBy(fn ($i) => $i->teacher->id)
            ->map(fn ($g) => [
                'teacher'       => $g->first()->teacher,
                'count'         => $g->count(),
                'last_reminded' => $g->pluck('reminded_at')->filter()
                    ->sortByDesc(fn ($d) => $d->timestamp)->first(),
            ])
            ->sortByDesc('count')
            ->values();

        $journals = null;
        $missingPage = null;

        if ($tab === 'masuk') {
            $status = $request->query('class_status');

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
                    in_array($status, ['Conducted', 'Cancelled', 'Rescheduled'], true),
                    fn ($q) => $q->where('class_status', $status)
                )
                ->orderByDesc('session_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        } else {
            $missingPage = $this->paginateCollection($missing, 15, $request);
        }

        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        return view('admin.jurnal-mengajar', compact(
            'tab', 'from', 'to', 'stats', 'journals', 'missingPage', 'byTeacher', 'teachers'
        ));
    }

    /**
     * Kirim pengingat (notifikasi di sistem) ke laoshi.
     * Daftar sesi dihitung ulang di server, jadi tidak bergantung pada data dari browser.
     */
    public function remind(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'from'       => ['required', 'date_format:Y-m-d'],
            'to'         => ['required', 'date_format:Y-m-d'],
            'keys'       => ['nullable', 'array'],
            'keys.*'     => ['string', 'max:40'],
        ]);

        [$from, $to] = $this->resolveRange($data['from'], $data['to']);

        $teacher = Teacher::with('user')->findOrFail($data['teacher_id']);

        if (! $teacher->user) {
            return back()->with('error', "Laoshi {$teacher->name} belum punya akun login, pengingat tidak bisa dikirim.");
        }

        $items = $this->finder->find($from, $to, $teacher->id);

        if (! empty($data['keys'])) {
            $items = $items->whereIn('key', $data['keys'])->values();
        }

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada jurnal tertunggak untuk laoshi ini (mungkin baru saja diisi).');
        }

        $this->attachReminders($items);

        $cooldown = now()->subHours(self::REMIND_COOLDOWN_HOURS);
        $fresh = $items
            ->filter(fn ($i) => ! $i->reminded_at || $i->reminded_at->lt($cooldown))
            ->values();

        if ($fresh->isEmpty()) {
            return back()->with('warning', "Laoshi {$teacher->name} sudah diingatkan kurang dari " . self::REMIND_COOLDOWN_HOURS . ' jam lalu.');
        }

        $sessions = $fresh->map(fn ($i) => [
            'key'        => $i->key,
            'class_name' => $i->class->class_name,
            'date'       => $i->date->format('Y-m-d'),
            'date_label' => $i->date->copy()->locale('id')->translatedFormat('l, d M Y'),
            'time_label' => substr($i->schedule->start_time, 0, 5) . '–' . substr($i->schedule->end_time, 0, 5),
        ])->all();

        $teacher->user->notify(new JurnalMengajarReminder($sessions, 'Admin'));

        return back()->with('success', "Pengingat terkirim ke Laoshi {$teacher->name} ({$fresh->count()} sesi).");
    }

    /* ------------------------------------------------------------------ */

    /** Isi $item->reminded_at dari notifikasi pengingat yang pernah dikirim. */
    private function attachReminders(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $userIds = $items->map(fn ($i) => $i->teacher->user_id)->filter()->unique()->values();

        $map = [];
        DatabaseNotification::query()
            ->where('type', JurnalMengajarReminder::class)
            ->whereIn('notifiable_id', $userIds)
            ->where('created_at', '>=', now()->subDays(self::MAX_RANGE_DAYS))
            ->orderBy('created_at')
            ->get()
            ->each(function ($n) use (&$map) {
                foreach (($n->data['keys'] ?? []) as $key) {
                    $map[$n->notifiable_id . '|' . $key] = $n->created_at;
                }
            });

        foreach ($items as $item) {
            $item->reminded_at = $map[$item->teacher->user_id . '|' . $item->key] ?? null;
        }
    }

    private function resolveRange(?string $fromInput, ?string $toInput): array
    {
        $to = $this->parseDate($toInput) ?? now()->startOfDay();
        $from = $this->parseDate($fromInput) ?? $to->copy()->subDays(13);

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
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
