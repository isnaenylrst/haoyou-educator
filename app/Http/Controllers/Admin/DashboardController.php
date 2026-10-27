<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ProgressReport;
use App\Models\Student;
use App\Models\TeacherAvailability;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /* ---- SESUAIKAN nilai status ini dengan data di databasemu ---- */
    private const STUDENT_ACTIVE     = 'Active';
    private const CLASS_RUNNING      = 'Running';    // status kelas berjalan (Open, Running, Completed, Closed)
    /** Status enrollment yang dihitung sebagai siswa kelas (sama dengan halaman Kelas). */
    private const COUNTED_ENROLLMENT_STATUSES = ['Active', 'Completed'];

    private const CLASS_OPEN         = 'Open';       // belum mulai
    private const CLASS_COMPLETED    = 'Completed';  // sudah selesai
    private const CLASS_CLOSED       = 'Closed';     // kelas dibatalkan/ditutup, tidak ikut notifikasi
    private const ENROLL_WAITING     = 'Waiting Class';
    private const REPORT_VERIFIED    = 'Submitted';  // diset oleh kurikulum = sudah terverifikasi, admin hanya menerima notifikasi

    /** Kelas dianggap "akan berakhir" bila end_date jatuh H-1 s/d H-N. */
    private const DAYS_BEFORE_END = 3;

    /** Setelah berakhir, kelas tetap tampil H+1 s/d H+N untuk konfirmasi ke guru, lalu hilang sendiri. */
    private const DAYS_AFTER_END = 3;

    /** Progress report terverifikasi tampil N hari sejak updated_at (waktu status berubah jadi Submitted) untuk follow up siswa, lalu hilang sendiri. */
    private const FOLLOW_UP_DAYS = 7;

    /** Program Kelas Aktif: kelas ditandai "Akan Berakhir" bila sisa sesi <= N, dan maksimal N kelas ditampilkan. */
    private const SESSIONS_LEFT_ENDING = 3;
    private const PROGRAM_LIST_LIMIT   = 30;

    /** Jadwal hari ini: sesi ditandai "segera" bila mulai dalam N menit. */
    private const SOON_MINUTES = 60;

    /** Guru Tersedia Hari Ini: nama hari sesuai data (Senin=1 ... Minggu=7), celah kosong < N menit diabaikan. */
    private const DAY_NAMES = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    private const MIN_FREE_MINUTES = 30;

    public function index()
    {
        $today = today();

        return view('admin.dashboard', [
            'stats'     => $this->stats($today),
            'prioritas' => $this->prioritas($today),
            'programAktif'  => $this->programAktif(),
            'jadwalHariIni' => $this->jadwalHariIni($today),
            'guruTersedia'  => $this->guruTersedia($today),
        ]);
    }

    /**
     * Program Kelas Aktif: kelas (Running, Completed, Open, Closed, urut sesuai itu) beserta progres pertemuannya.
     */
    private function programAktif()
    {
        $classes = ClassModel::with(['teacher', 'programPackage', 'privatePackage'])
            ->whereIn('status', [self::CLASS_RUNNING, self::CLASS_COMPLETED, self::CLASS_OPEN, self::CLASS_CLOSED])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
            ])
            ->get();

        // Pertemuan yang sudah berlangsung: dari jurnal mengajar berstatus Conducted (sama dengan halaman Kelas)
        $doneByClass = TeachingJournal::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('class_status', 'Conducted')
            ->selectRaw('class_id, COUNT(DISTINCT COALESCE(class_session_id, id)) AS done')
            ->groupBy('class_id')
            ->pluck('done', 'class_id');

        return $classes
            ->each(function ($k) use ($doneByClass) {
                // Total pertemuan mengikuti paket (total_meetings), sama dengan halaman Kelas
                $k->total_sessions = (int) ($k->programPackage?->total_meetings ?? $k->privatePackage?->total_meetings ?? 0);
                $k->done_sessions  = (int) ($doneByClass[$k->id] ?? 0);
                $k->sessions_left  = max($k->total_sessions - $k->done_sessions, 0);
                $k->progress       = $k->total_sessions > 0
                    ? min(100, (int) round($k->done_sessions / $k->total_sessions * 100))
                    : 0;

                // urutan tampil: Running, Completed, Open, Closed
                $k->rank = match ($k->status) {
                    self::CLASS_RUNNING   => 0,
                    self::CLASS_COMPLETED => 1,
                    self::CLASS_OPEN      => 2,
                    default               => 3,
                };

                if ($k->status === self::CLASS_CLOSED) {
                    $k->state = 'closed';         // Ditutup
                } elseif ($k->status === self::CLASS_COMPLETED) {
                    $k->state = 'done';           // Selesai
                } elseif ($k->status === self::CLASS_OPEN) {
                    $k->state = 'scheduled';      // Belum Mulai
                } elseif ($k->total_sessions > 0 && $k->sessions_left === 0) {
                    $k->state = 'done';           // Running, tapi semua pertemuan sudah selesai
                } elseif ($k->total_sessions > 0 && $k->sessions_left <= self::SESSIONS_LEFT_ENDING) {
                    $k->state = 'ending';         // Akan Berakhir
                } else {
                    $k->state = 'live';           // Berjalan
                }
            })
            ->sort(fn ($a, $b) => [$a->rank, $a->sessions_left, -($a->end_date?->timestamp ?? 0)]
                              <=> [$b->rank, $b->sessions_left, -($b->end_date?->timestamp ?? 0)])
            ->values()
            ->take(self::PROGRAM_LIST_LIMIT);
    }

    /**
     * Jadwal Kelas Hari Ini: class_sessions hari ini (selain Cancelled), urut jam mulai.
     */

    private function jadwalHariIni(Carbon $today)
    {
        $now = now();
        $today = $today->copy()->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | 1. Ambil semua sesi yang sudah tercatat untuk hari ini
        |--------------------------------------------------------------------------
        | Sesi Cancelled tetap diambil sementara untuk mencegah jadwal rutin
        | yang sudah dibatalkan muncul kembali.
        */
        $allSessions = ClassSession::onDate($today)
            ->with([
                'journal',
                'teacher',
                'class.teacher',
                'class' => fn ($q) => $q->withCount([
                    'enrollments as students_count' => fn ($e) => $e
                        ->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
                ]),
            ])
            ->get();

        // Jadwal rutin yang sudah memiliki sesi pada tanggal ini.
        $overriddenScheduleIds = $allSessions
            ->pluck('class_schedule_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Hanya tampilkan sesi yang tidak dibatalkan.
        $items = $allSessions
            ->reject(fn ($s) => $s->status === 'Cancelled')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 2. Ambil jadwal rutin yang berlaku hari ini
        |--------------------------------------------------------------------------
        */
        $day = \App\Services\ClassScheduleService::DAYS[
            $today->dayOfWeekIso - 1
        ];

        $regularSchedules = \App\Models\ClassSchedule::query()
            ->activeOn($today)
            ->where('day', $day)
            ->when(
                ! empty($overriddenScheduleIds),
                fn ($q) => $q->whereNotIn(
                    'id',
                    $overriddenScheduleIds
                )
            )
            ->with([
                'class.teacher',
                'class' => fn ($q) => $q->withCount([
                    'enrollments as students_count' => fn ($e) => $e
                        ->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
                ]),
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. Bentuk jadwal rutin menjadi objek untuk ditampilkan di Blade
        |--------------------------------------------------------------------------
        */
        foreach ($regularSchedules as $schedule) {
            $class = $schedule->class;

            if (! $class) {
                continue;
            }

            $session = new ClassSession([
                'class_id'          => $schedule->class_id,
                'class_schedule_id' => $schedule->id,
                'session_date'      => $today->toDateString(),
                'start_time'        => $schedule->start_time,
                'end_time'          => $schedule->end_time,
                'room'              => $schedule->room,
                'teacher_id'        => $class->teacher_id,
                'location_type'     => $class->delivery_mode,
                'status'            => 'Scheduled',
                'kind'              => 'Regular',
            ]);

            $session->setRelation('class', $class);
            $session->setRelation('teacher', $class->teacher);
            $session->setRelation('journal', null);

            $items->push($session);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Hitung status waktu dan label jadwal
        |--------------------------------------------------------------------------
        */
        return $items
            ->each(function ($s) use ($today, $now) {
                $start = Carbon::parse(
                    $today->toDateString() . ' ' . $s->start_time
                );

                $end = Carbon::parse(
                    $today->toDateString() . ' ' . $s->end_time
                );

                // Sesi sudah selesai berdasarkan status atau jurnal.
                if ($s->status === 'Completed' || $s->journal) {
                    $s->state = 'completed';
                }

                // Sesi sedang berlangsung.
                elseif ($now->betweenIncluded($start, $end)) {
                    $s->state = 'ongoing';
                }

                // Sesi belum dimulai.
                elseif ($start->gt($now)) {
                    $minutesTo = (int) ceil(
                        $now->diffInSeconds($start) / 60
                    );

                    if ($minutesTo <= self::SOON_MINUTES) {
                        $s->state = 'soon';
                        $s->minutes_to = $minutesTo;
                    } else {
                        $s->state = 'upcoming';
                    }
                }

                // Jam selesai sudah lewat, tetapi sesi belum berstatus Completed.
                else {
                    $s->state = 'past';
                }

                $s->start_label = $start->format('H:i');
                $s->end_label = $end->format('H:i');
            })
            ->sortBy('start_time')
            ->values();
    }


    /**
     * Guru Tersedia Hari Ini: guru yang menyatakan tersedia hari ini (TeacherAvailability),
     * dikurangi jam mengajarnya dari pola mingguan kelas Open/Running (ClassSchedule).
     */
    private function guruTersedia(Carbon $today)
    {
        $day = self::DAY_NAMES[$today->dayOfWeekIso - 1];

        $availabilities = TeacherAvailability::with('teacher')
            ->where('day', $day)
            ->orderBy('start_time')
            ->get()
            ->groupBy('teacher_id');

        if ($availabilities->isEmpty()) {
            return collect();
        }

        $teaching = ClassSchedule::query()
            ->current()
            ->activeOn($today)
            ->where('day', $day)
            ->whereHas('class', fn ($q) => $q
                ->whereIn('status', [self::CLASS_RUNNING, self::CLASS_OPEN])
                ->whereIn('teacher_id', $availabilities->keys()))
            ->with('class:id,teacher_id')
            ->get()
            ->groupBy(fn ($s) => $s->class->teacher_id);

        return $availabilities
            ->map(function ($slots, $teacherId) use ($teaching) {
                $teacher = $slots->first()->teacher;
                if (! $teacher) {
                    return null;
                }

                $avail = $this->mergeIntervals(
                    $slots->map(fn ($s) => [$this->toMinutes($s->start_time), $this->toMinutes($s->end_time)])->all()
                );
                $busy = $this->mergeIntervals(
                    $teaching->get($teacherId, collect())
                        ->map(fn ($s) => [$this->toMinutes($s->start_time), $this->toMinutes($s->end_time)])
                        ->all()
                );

                $free = array_values(array_filter(
                    $this->subtractIntervals($avail, $busy),
                    fn ($iv) => $iv[1] - $iv[0] >= self::MIN_FREE_MINUTES
                ));
                $freeText = implode(', ', array_map(fn ($iv) => $this->fromMinutes($iv[0]) . '–' . $this->fromMinutes($iv[1]), $free));

                $hasTeaching = $busy !== [];

                if ($hasTeaching) {
                    $meta = 'Mengajar sampai ' . $this->fromMinutes(max(array_column($busy, 1)))
                        . ($freeText !== '' ? ' · Kosong ' . $freeText : '');
                } else {
                    $meta = 'Kosong ' . ($freeText !== '' ? $freeText : 'sepanjang jam tersedia');
                }

                return (object) [
                    'name'         => $teacher->name,
                    'initials'     => Str::of($teacher->name)->explode(' ')->filter()->take(2)
                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
                    'has_teaching' => $hasTeaching,
                    'meta'         => $meta,
                ];
            })
            ->filter()
            ->sortBy([['has_teaching', 'asc'], ['name', 'asc']])
            ->values();
    }

    /* ---- helper interval waktu (menit sejak 00:00) ---- */

    private function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $h * 60 + $m;
    }

    private function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function mergeIntervals(array $intervals): array
    {
        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($intervals as [$start, $end]) {
            if ($merged && $start <= $merged[count($merged) - 1][1]) {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        return $merged;
    }

    private function subtractIntervals(array $avail, array $busy): array
    {
        $free = [];
        foreach ($avail as [$start, $end]) {
            $cursor = $start;
            foreach ($busy as [$bStart, $bEnd]) {
                if ($bEnd <= $cursor) {
                    continue;
                }
                if ($bStart >= $end) {
                    break;
                }
                if ($bStart > $cursor) {
                    $free[] = [$cursor, $bStart];
                }
                $cursor = max($cursor, $bEnd);
            }
            if ($cursor < $end) {
                $free[] = [$cursor, $end];
            }
        }

        return $free;
    }

    private function stats(Carbon $today): array
    {
        return [
            'siswa_aktif' => Student::where('status', self::STUDENT_ACTIVE)->count(),

            'calon_siswa_minggu_ini' => CandidateStudent::whereBetween('created_at', [
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek(),
            ])->count(),

            'waiting_trial_hari_ini' => CandidateStudent::whereDate('trial_date', $today)->count(),

            'konsultasi_hari_ini' => CandidateStudent::whereDate('consultation_date', $today)->count(),

            'menunggu_jadwal_kelas' => ClassEnrollment::where('status', self::ENROLL_WAITING)->count(),

            'kelas_berjalan' => ClassModel::where('status', self::CLASS_RUNNING)->count(),
        ];
    }

    private function prioritas(Carbon $today): array
    {
        $kelas = fn () => ClassModel::with('teacher')
            ->withCount(['enrollments as students_count' => fn ($q) =>
                $q->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES)]);

        $sebelum = $kelas()
            ->where('status', '!=', self::CLASS_CLOSED)
            ->whereBetween('end_date', [
                $today->copy()->addDay(),
                $today->copy()->addDays(self::DAYS_BEFORE_END),
            ])
            ->orderBy('end_date')
            ->get();

        $hariH = $kelas()
            ->where('status', '!=', self::CLASS_CLOSED)
            ->whereDate('end_date', $today)
            ->get();

        $setelah = $kelas()
            ->where('status', '!=', self::CLASS_CLOSED)
            ->whereBetween('end_date', [
                $today->copy()->subDays(self::DAYS_AFTER_END),
                $today->copy()->subDay(),
            ])
            ->orderByDesc('end_date')
            ->get();

        $followUp = ProgressReport::with(['student', 'enrollment.class'])
            ->where('status', self::REPORT_VERIFIED)
            ->where('updated_at', '>=', $today->copy()->subDays(self::FOLLOW_UP_DAYS))
            ->orderBy('updated_at')
            ->get();

        return [
            'sebelum'   => $sebelum,
            'hari_h'    => $hariH,
            'setelah'   => $setelah,
            'follow_up' => $followUp,
            'total'     => $sebelum->count() + $hariH->count() + $setelah->count() + $followUp->count(),
        ];
    }
}