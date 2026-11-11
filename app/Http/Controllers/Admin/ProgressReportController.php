<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Notifications\ProgressReportReminder;
use App\Services\MissingProgressReportFinder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Progress report dibuat oleh laoshi, di-review Kurikulum.
 *
 * Admin memantau dua hal:
 *  1. Tab "Laporan": semua laporan beserta tahap prosesnya (Draft, Dikembalikan, Menunggu Review, Disetujui).
 *     Status follow up (lanjut les atau tidak) bisa dicatat untuk SEMUA laporan. Lihat/Unduh PDF hanya
 *     untuk laporan yang sudah disetujui Kurikulum (status = Submitted + reviewed_at terisi).
 *  2. Tab "Belum Diisi": progress report yang sudah jatuh tempo (tiap 3 bulan sejak siswa masuk kelas,
 *     atau pertemuan terakhir kalau kelas kurang dari 3 bulan) tetapi belum dikirim laoshi.
 *     Laoshi bisa diingatkan lewat notifikasi, sama seperti Jurnal Mengajar.
 */
class ProgressReportController extends Controller
{
    /** Tahap proses sebuah laporan, sama dengan label di halaman laoshi. */
    public const PROSES = ['Draft', 'Dikembalikan', 'Menunggu Review', 'Disetujui'];

    private const REMIND_COOLDOWN_HOURS  = 2;
    private const REMINDER_LOOKBACK_DAYS = 180;

    public function __construct(
        private MissingProgressReportFinder $finder
    ) {
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'belum' ? 'belum' : 'laporan';

        $search    = trim((string) $request->query('search', ''));
        $teacherId = $request->integer('teacher_id') ?: null;

        $fu = $request->query('follow_up');
        if (! in_array($fu, ProgressReport::FOLLOW_UPS, true)) {
            $fu = null;
        }

        $proses = $request->query('proses');
        if (! in_array($proses, self::PROSES, true)) {
            $proses = null;
        }

        // ---------- Belum diisi: sudah jatuh tempo, belum dikirim laoshi ----------
        $missing = $this->finder
            ->find(null, $teacherId)
            ->when(
                $search !== '',
                fn ($items) => $items->filter(
                    fn ($i) => stripos($i->student->name ?? '', $search) !== false
                        || stripos($i->class->class_name ?? '', $search) !== false
                )
            )
            ->values();

        $this->attachReminders($missing);

        $byTeacher = $missing
            ->groupBy(fn ($i) => $i->teacher->id)
            ->map(fn ($group) => [
                'teacher'       => $group->first()->teacher,
                'count'         => $group->count(),
                'last_reminded' => $group
                    ->pluck('reminded_at')
                    ->filter()
                    ->sortByDesc(fn ($date) => $date->timestamp)
                    ->first(),
            ])
            ->sortByDesc('count')
            ->values();

        $approved = fn () => ProgressReport::where('status', ProgressReport::SUBMITTED)->whereNotNull('reviewed_at');

        $stats = [
            'terbit'        => $approved()->count(),
            'belum'         => $missing->count(),
            'teachers_late' => $missing->pluck('teacher.id')->unique()->count(),
            'belum_fu'      => ProgressReport::where('follow_up_status', 'Belum')->count(),
            'lanjut'        => ProgressReport::where('follow_up_status', 'Lanjut')->count(),
            'tidak'         => ProgressReport::where('follow_up_status', 'Tidak Lanjut')->count(),
            'menunggu'      => ProgressReport::where('status', ProgressReport::SUBMITTED)->whereNull('reviewed_at')->count(),
            'draft'         => ProgressReport::where('status', 'Draft')->count(),
            'total'         => ProgressReport::count(),
        ];

        $reports     = null;
        $missingPage = null;

        if ($tab === 'laporan') {
            $reports = ProgressReport::query()
                ->with([
                    'student', 'teacher',
                    'enrollment.class.programPackage.program',
                    'enrollment.programPackage.program',
                    'enrollment.privatePackage',
                ])
                ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                    $w->where('report_period', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                }))
                ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
                ->when($proses, fn ($q) => match ($proses) {
                    'Draft'           => $q->where('status', 'Draft')->whereNull('returned_at'),
                    'Dikembalikan'    => $q->where('status', 'Draft')->whereNotNull('returned_at'),
                    'Menunggu Review' => $q->where('status', ProgressReport::SUBMITTED)->whereNull('reviewed_at'),
                    default           => $q->where('status', ProgressReport::SUBMITTED)->whereNotNull('reviewed_at'),
                })
                ->when($fu, fn ($q) => $q->where('follow_up_status', $fu))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        } else {
            $missingPage = $this->paginateCollection($missing, 15, $request);
        }

        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        return view('admin.progress-report', compact(
            'tab', 'reports', 'missingPage', 'byTeacher', 'stats', 'teachers'
        ));
    }

    /** Catat status follow up. Berlaku untuk semua laporan, apa pun tahap prosesnya. */
    public function followUp(Request $request, ProgressReport $report)
    {
        $data = $request->validate([
            'follow_up_status' => ['required', 'in:' . implode(',', ProgressReport::FOLLOW_UPS)],
            'follow_up_note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update($data + [
            'followed_up_at' => $data['follow_up_status'] === 'Belum' ? null : now(),
        ]);

        return back()->with('success', 'Status follow up diperbarui.');
    }

    /**
     * Kirim pengingat progress report yang belum diisi kepada laoshi.
     * Cooldown per laporan: tidak mengingatkan lagi dalam REMIND_COOLDOWN_HOURS jam.
     */
    public function remind(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'keys'       => ['nullable', 'array'],
            'keys.*'     => ['string', 'max:40'],
        ]);

        $teacher = Teacher::with('user')->findOrFail($data['teacher_id']);

        if (! $teacher->user) {
            return back()->with(
                'error',
                "Laoshi {$teacher->name} belum punya akun login, pengingat tidak bisa dikirim."
            );
        }

        $items = $this->finder->find(null, $teacher->id);

        if (! empty($data['keys'])) {
            $items = $items->whereIn('key', $data['keys'])->values();
        }

        if ($items->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada progress report tertunggak untuk laoshi ini (mungkin baru saja dikirim).'
            );
        }

        $this->attachReminders($items);

        $cooldown = now()->subHours(self::REMIND_COOLDOWN_HOURS);

        $fresh = $items
            ->filter(fn ($i) => ! $i->reminded_at || $i->reminded_at->lt($cooldown))
            ->values();

        if ($fresh->isEmpty()) {
            return back()->with(
                'warning',
                "Laoshi {$teacher->name} sudah diingatkan kurang dari "
                . self::REMIND_COOLDOWN_HOURS . ' jam lalu.'
            );
        }

        $payload = $fresh->map(fn ($i) => [
            'key'           => $i->key,
            'enrollment_id' => $i->enrollment->id,
            'student'       => $i->student->name ?? '-',
            'class_name'    => $i->class->class_name,
            'kind'          => $i->kind_label,
            'state'         => $i->state,
            'due_date'      => $i->due->format('Y-m-d'),
            'due_label'     => $i->due->copy()->locale('id')->translatedFormat('d M Y'),
        ])->all();

        $teacher->user->notify(new ProgressReportReminder($payload, 'Admin'));

        return back()->with(
            'success',
            "Pengingat terkirim ke Laoshi {$teacher->name} ({$fresh->count()} progress report)."
        );
    }

    /** Buka PDF di tab baru */
    public function lihat(ProgressReport $report)
    {
        if (! $this->resolveFile($report)) {
            return $this->missingFile();
        }

        return $this->disk()->response($report->file_path, $this->fileName($report));
    }

    /** Unduh PDF */
    public function unduh(ProgressReport $report)
    {
        if (! $this->resolveFile($report)) {
            return $this->missingFile();
        }

        return $this->disk()->download($report->file_path, $this->fileName($report));
    }

    /** Isi waktu pengingat terakhir berdasarkan notifikasi yang tersimpan. */
    private function attachReminders(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $userIds = $items
            ->map(fn ($i) => $i->teacher->user_id)
            ->filter()
            ->unique()
            ->values();

        $map = [];

        DatabaseNotification::query()
            ->where('type', ProgressReportReminder::class)
            ->whereIn('notifiable_id', $userIds)
            ->where('created_at', '>=', now()->subDays(self::REMINDER_LOOKBACK_DAYS))
            ->orderBy('created_at')
            ->get()
            ->each(function ($notification) use (&$map) {
                foreach (($notification->data['keys'] ?? []) as $key) {
                    $map[$notification->notifiable_id . '|' . $key] = $notification->created_at;
                }
            });

        foreach ($items as $item) {
            $item->reminded_at = $map[$item->teacher->user_id . '|' . $item->key] ?? null;
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
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /** Disk public bertipe FilesystemAdapter (ada response() & download()), supaya editor tidak menandai "unknown method". */
    private function disk(): FilesystemAdapter
    {
        return Storage::disk('public');
    }

    private function resolveFile(ProgressReport $report): bool
    {
        return $report->status === 'Submitted'
            && $report->reviewed_at !== null
            && $report->file_path
            && $this->disk()->exists($report->file_path);
    }

    private function missingFile()
    {
        return redirect()->route('admin.progress-report')
            ->with('error', 'File laporan tidak ditemukan atau laporan belum disetujui Kurikulum.');
    }

    private function fileName(ProgressReport $report): string
    {
        $ext = pathinfo($report->file_path, PATHINFO_EXTENSION) ?: 'pdf';

        return Str::slug($report->student?->name ?? 'siswa')
            . '-progress-report-' . Str::slug($report->report_period)
            . '.' . $ext;
    }
}