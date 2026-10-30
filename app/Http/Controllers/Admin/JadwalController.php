<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JadwalController extends Controller
{
    private const DAY_ORDER = [
        'Senin', 'Selasa', 'Rabu', 'Kamis',
        'Jumat', 'Sabtu', 'Minggu',
    ];

    private const COUNTED_ENROLLMENT_STATUSES = [
        'Active', 'Completed',
    ];

    private const ROOMS = [
        'Beijing', 'Basement', 'Atas', 'Kaca', 'Online',
    ];

    private const SLOT_MINUTES = 30;

    public function index(Request $request)
    {
        $request->validate([
            'week'       => ['nullable', 'date'],
            'teacher_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'string'],
            'room'       => ['nullable', 'string'],
            'class_id'   => ['nullable', 'integer'],
        ]);

        $filteredClass = $request->filled('class_id')
            ? ClassModel::find($request->class_id)
            : null;

        if ($request->filled('week')) {
            $anchor = Carbon::parse($request->week);
        } elseif ($filteredClass) {
            $today = Carbon::today();
            $start = Carbon::parse($filteredClass->start_date);
            $end = $filteredClass->end_date
                ? Carbon::parse($filteredClass->end_date)
                : null;

            $anchor = $today->lt($start)
                ? $start
                : ($end && $today->gt($end) ? $end : $today);
        } else {
            $anchor = Carbon::today();
        }

        $weekStart = $anchor->copy()
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();

        $weekEnd = $weekStart->copy()
            ->addDays(6)
            ->endOfDay();

        $teacherFilter = $request->filled('teacher_id')
            ? (int) $request->teacher_id
            : null;

        $programFilter = $request->filled('program_id')
            ? $request->program_id
            : null;

        $roomFilter = $request->filled('room')
            && in_array($request->room, self::ROOMS, true)
                ? $request->room
                : null;

        /*
        |--------------------------------------------------------------------------
        | 1. Ambil pola jadwal rutin dari class_schedules
        |--------------------------------------------------------------------------
        |
        | Ambil pola yang masa berlakunya beririsan dengan minggu yang dipilih.
        | Jangan hanya memakai activeOn($weekEnd), karena pola yang berakhir
        | di tengah minggu tetap dapat berlaku pada hari-hari sebelumnya.
        */

        $schedules = ClassSchedule::query()
            ->with([
                'class' => fn ($q) => $q
                    ->with([
                        'programPackage.program',
                        'privatePackage',
                    ])
                    ->withCount([
                        'enrollments as students_count' => fn ($e) =>
                            $e->whereIn(
                                'status',
                                self::COUNTED_ENROLLMENT_STATUSES
                            ),
                    ]),
            ])
            ->where(function ($q) use ($weekEnd) {
                $q->whereNull('effective_from')
                    ->orWhereDate(
                        'effective_from',
                        '<=',
                        $weekEnd->toDateString()
                    );
            })
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('effective_until')
                    ->orWhereDate(
                        'effective_until',
                        '>=',
                        $weekStart->toDateString()
                    );
            })
            ->when(
                $filteredClass,
                fn ($q) => $q->where(
                    'class_id',
                    $filteredClass->id
                )
            )
            ->when(
                $programFilter === 'private',
                fn ($q) => $q->whereHas(
                    'class',
                    fn ($c) => $c->whereNotNull('private_package_id')
                )
            )
            ->when(
                $programFilter && $programFilter !== 'private',
                fn ($q) => $q->whereHas(
                    'class.programPackage',
                    fn ($p) => $p->where(
                        'program_id',
                        $programFilter
                    )
                )
            )
            ->when(
                $roomFilter,
                fn ($q) => $q->where('room', $roomFilter)
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. Ambil sesi perubahan dan sesi tambahan dari class_sessions
        |--------------------------------------------------------------------------
        |
        | original_date ikut dicari supaya jadwal asal yang dipindahkan
        | ke minggu lain dapat dikenali dan tidak ditampilkan kembali.
        */

        $rows = ClassSession::query()
            ->with([
                'journal',
                'schedule',
                'enrollment.student',
                'payments',
                'teacher',
                'class' => fn ($q) => $q
                    ->with([
                        'programPackage.program',
                        'privatePackage',
                    ])
                    ->withCount([
                        'enrollments as students_count' => fn ($e) =>
                            $e->whereIn(
                                'status',
                                self::COUNTED_ENROLLMENT_STATUSES
                            ),
                    ]),
            ])
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('session_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ])->orWhereBetween('original_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ]);
            })
            ->when(
                $filteredClass,
                fn ($q) => $q->where(
                    'class_id',
                    $filteredClass->id
                )
            )
            ->when(
                $programFilter === 'private',
                fn ($q) => $q->where(
                    fn ($w) => $w
                        ->where('kind', 'Replacement')
                        ->orWhereHas(
                            'class',
                            fn ($c) => $c->whereNotNull('private_package_id')
                        )
                )
            )
            ->when(
                $programFilter && $programFilter !== 'private',
                fn ($q) => $q->whereHas(
                    'class.programPackage',
                    fn ($p) => $p->where(
                        'program_id',
                        $programFilter
                    )
                )
            )
            ->when(
                $roomFilter,
                fn ($q) => $q->where('room', $roomFilter)
            )
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. Izin guru untuk sesi yang berkaitan dengan jadwal
        |--------------------------------------------------------------------------
        */

        $scheduleIds = $schedules->pluck('id')
            ->merge($rows->pluck('class_schedule_id'))
            ->filter()
            ->unique()
            ->values();

        $leaves = TeacherLeave::query()
            ->whereIn('class_schedule_id', $scheduleIds)
            ->where('status', 'Approved')
            ->whereBetween('leave_date', [
                $weekStart->copy()->subWeeks(4)->toDateString(),
                $weekEnd->copy()->addWeeks(4)->toDateString(),
            ])
            ->get([
                'id',
                'class_schedule_id',
                'leave_date',
                'replacement_teacher_id',
            ])
            ->keyBy(
                fn ($leave) => $leave->class_schedule_id
                    . '|'
                    . Carbon::parse($leave->leave_date)->toDateString()
            );

        $teachers = Teacher::orderBy('name')
            ->get(['id', 'name', 'status']);

        $teacherNames = $teachers->pluck('name', 'id');

        $statusLabels = [
            'Conducted'   => 'Terlaksana',
            'Cancelled'   => 'Dibatalkan',
            'Rescheduled' => 'Dijadwal ulang',
        ];

        /*
        |--------------------------------------------------------------------------
        | 4. Bangkitkan jadwal rutin per tanggal
        |--------------------------------------------------------------------------
        |
        | Pola mingguan dibuat menjadi item kalender untuk setiap hari
        | yang cocok pada minggu terpilih.
        */

        $byDay = [];

        foreach ($schedules as $schedule) {
            $kelas = $schedule->class;

            if (! $kelas) {
                continue;
            }

            $dayIndex = array_search(
                $schedule->day,
                self::DAY_ORDER,
                true
            );

            if ($dayIndex === false) {
                continue;
            }

            $date = $weekStart->copy()
                ->addDays($dayIndex)
                ->startOfDay();

            if (
                $schedule->effective_from
                && $date->lt(
                    $schedule->effective_from->copy()->startOfDay()
                )
            ) {
                continue;
            }

            if (
                $schedule->effective_until
                && $date->gt(
                    $schedule->effective_until->copy()->startOfDay()
                )
            ) {
                continue;
            }

            /*
             * Periksa apakah ada sesi yang menggantikan jadwal pada tanggal
             * asal ini. original_date digunakan untuk sesi yang dipindahkan.
             */
            $replacementSession = $rows->first(
                fn ($session) =>
                    (int) $session->class_schedule_id === (int) $schedule->id
                    && (
                        $session->original_date?->toDateString()=== $date->toDateString()
                        || (
                            $session->kind !== 'Replacement'
                            && $session->session_date->toDateString() === $date->toDateString()
                        )
                    )
            );

            /*
             * Jika ada sesi pengganti, jangan tampilkan jadwal rutin
             * pada slot asalnya. Sesi aktual akan diproses di bagian berikut.
             */
            if ($replacementSession) {
                continue;
            }

            $isPrivate = ! is_null($kelas->private_package_id);

            $programName = $kelas->programPackage
                ?->program
                ?->program_name ?? '';

            $kind = $isPrivate
                ? 'Private'
                : (
                    str_contains(strtolower($programName), 'hsk')
                        ? 'HSK'
                        : 'Daily Activity'
                );

            /*
             * ClassSchedule tidak mempunyai teacher_id pada model yang
             * diberikan. Baca teacher_id dari kelas jika kolom itu tersedia.
             */
            $teacherId = $kelas->getAttribute('teacher_id');

            if ($teacherFilter && (int) $teacherId !== $teacherFilter) {
                continue;
            }

            $startText = substr($schedule->start_time, 0, 5);
            $endText = substr($schedule->end_time, 0, 5);

            [$sh, $sm] = array_map(
                'intval',
                explode(':', $startText)
            );

            [$eh, $em] = array_map(
                'intval',
                explode(':', $endText)
            );

            $byDay[$dayIndex][] = [
                'id' => 'schedule-' . $schedule->id . '-' . $date->toDateString(),
                'session_id' => null,
                'schedule_id' => $schedule->id,
                'title' => $kelas->class_name,
                'kind' => $kind,
                'class_name' => $kelas->class_name,
                'student_name' => null,
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherId
                    ? ($teacherNames[$teacherId] ?? null)
                    : null,
                'teacher_note' => null,
                'start_time' => $startText,
                'end_time' => $endText,
                'time_label' => "{$startText} – {$endText}",
                'start_min' => $sh * 60 + $sm,
                'end_min' => $eh * 60 + $em,
                'date_label' => self::DAY_ORDER[$dayIndex]
                    . ', '
                    . $date->format('d M Y'),
                'date_value' => $date->toDateString(),
                'room' => $schedule->room ?: '-',
                'room_value' => $schedule->room,
                'location_type' => null,
                'delivery_mode' => $kelas->delivery_mode,
                'students_count' => $kelas->students_count,
                'session_number' => null,
                'kind_note' => null,
                'fees' => [],
                'status_key' => 'Scheduled',
                'status_label' => 'Terjadwal',
                'has_conflict' => false,
                'can_complete' => false,
                'can_reschedule' => false,
                'can_reschedule_following' => false,
                'moved_from' => null,
                'change_reason' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Masukkan sesi perubahan dan sesi tambahan
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $s) {
            $date = $s->session_date->copy();

            /*
             * Baris dengan session_date di luar minggu hanya diambil untuk
             * mengenali jadwal asal yang dipindahkan. Jangan tampilkan
             * sesi tersebut di minggu yang salah.
             */
            if (
                $date->lt($weekStart)
                || $date->gt($weekEnd)
            ) {
                continue;
            }

            $kelas = $s->class;
            $isReplace = $s->kind === 'Replacement';

            $isPrivate = $isReplace
                || ($kelas && ! is_null($kelas->private_package_id));

            $programName = $kelas
                ?->programPackage
                ?->program
                ?->program_name ?? '';

            $kind = $isReplace
                ? 'Replacement'
                : (
                    $isPrivate
                        ? 'Private'
                        : (
                            str_contains(strtolower($programName), 'hsk')
                                ? 'HSK'
                                : 'Daily Activity'
                        )
                );

            $dayIndex = $date->dayOfWeekIso - 1;
            $journal = $s->journal;

            $leave = $leaves->get(
                $s->class_schedule_id . '|' . $date->toDateString()
            ) ?? (
                $s->original_date
                    ? $leaves->get(
                        $s->class_schedule_id
                        . '|'
                        . $s->original_date->toDateString()
                    )
                    : null
            );

            $teacherId = $s->teacher_id;
            $note = null;

            if (
                $journal
                && $journal->is_substitute
                && $journal->substitute_teacher_id
            ) {
                $teacherId = $journal->substitute_teacher_id;
                $note = 'Guru pengganti';
            } elseif ($leave && $leave->replacement_teacher_id) {
                $teacherId = $leave->replacement_teacher_id;
                $note = 'Guru pengganti (izin disetujui)';
            }

            if (
                $teacherFilter
                && (int) $teacherId !== $teacherFilter
            ) {
                continue;
            }

            if ($journal) {
                $statusKey = $journal->class_status;
            } elseif ($s->status === 'Cancelled') {
                $statusKey = 'Cancelled';
            } elseif ($s->status === 'Completed') {
                $statusKey = 'Conducted';
            } else {
                $statusKey = $date->isPast() && ! $date->isToday()
                    ? 'NoJournal'
                    : 'Scheduled';
            }

            $statusLabel = match ($statusKey) {
                'NoJournal' => 'Belum ada jurnal',
                'Scheduled' => 'Terjadwal',
                default => $statusLabels[$statusKey] ?? $statusKey,
            };

            $startText = substr($s->start_time, 0, 5);
            $endText = substr($s->end_time, 0, 5);

            [$sh, $sm] = array_map(
                'intval',
                explode(':', $startText)
            );

            [$eh, $em] = array_map(
                'intval',
                explode(':', $endText)
            );

            $canReschedule = $s->canBeRescheduled();

            $studentName = $s->enrollment?->student?->name;

            $title = $kelas?->class_name
                ?? ('Pengganti' . ($studentName ? ': ' . $studentName : ''));

            $kindNote = $isReplace
                ? 'Pertemuan tambahan (di luar hitungan paket)'
                : null;

            $fees = $s->payments->map(
                fn ($payment) => [
                    'id' => $payment->id,
                    'label' => $payment->payment_type,
                    'amount' => 'Rp' . number_format(
                        (float) $payment->amount_paid,
                        0,
                        ',',
                        '.'
                    ),
                    'paid' => $payment->status === 'Paid',
                    'paid_at' => $payment->payment_date?->format('d M Y'),
                    'invoice_number' => $payment->invoice_number,
                    'invoice_url' => $payment->invoice_number
                        ? route('admin.pembayaran.invoice', $payment->id)
                        : null,
                ]
            )->values()->all();

            $byDay[$dayIndex][] = [
                'id' => 'session-' . $s->id,
                'session_id' => $s->id,
                'schedule_id' => $s->class_schedule_id,
                'title' => $title,
                'kind' => $kind,
                'class_name' => $title,
                'student_name' => $studentName,
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherId
                    ? ($teacherNames[$teacherId] ?? null)
                    : null,
                'teacher_note' => $note,
                'start_time' => $startText,
                'end_time' => $endText,
                'time_label' => "{$startText} – {$endText}",
                'start_min' => $sh * 60 + $sm,
                'end_min' => $eh * 60 + $em,
                'date_label' => self::DAY_ORDER[$dayIndex]
                    . ', '
                    . $date->format('d M Y'),
                'date_value' => $date->toDateString(),
                'room' => $s->room ?: ($s->location_type ?? '-'),
                'room_value' => $s->room,
                'location_type' => $s->location_type,
                'delivery_mode' => $kelas?->delivery_mode,
                'students_count' => $kelas?->students_count,
                'session_number' => $s->session_number,
                'kind_note' => $kindNote,
                'fees' => $fees,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'has_conflict' => false,
                'can_complete' => $s->canBeCompleted(),
                'can_reschedule' => $canReschedule,
                'can_reschedule_following' => $canReschedule
                    && $s->kind === 'Regular'
                    && (bool) $s->schedule?->isCurrent(),
                'moved_from' => $s->original_date
                    && ! $s->original_date->isSameDay($date)
                        ? $s->original_date->format('d M Y')
                        : null,
                'change_reason' => $s->change_reason,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Deteksi bentrok guru
        |--------------------------------------------------------------------------
        */

        $conflictCount = 0;

        foreach ($byDay as $dayIndex => $items) {
            $n = count($items);

            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $a = $items[$i];
                    $b = $items[$j];

                    if (
                        ! $a['teacher_id']
                        || (int) $a['teacher_id'] !== (int) $b['teacher_id']
                    ) {
                        continue;
                    }

                    if (
                        $a['status_key'] === 'Cancelled'
                        || $b['status_key'] === 'Cancelled'
                    ) {
                        continue;
                    }

                    if (
                        $a['start_min'] < $b['end_min']
                        && $b['start_min'] < $a['end_min']
                    ) {
                        $items[$i]['has_conflict'] = true;
                        $items[$j]['has_conflict'] = true;
                        $conflictCount++;
                    }
                }
            }

            $byDay[$dayIndex] = $items;
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Trial calon siswa
        |--------------------------------------------------------------------------
        */

        $trialsByDay = [];

        if (! $teacherFilter && ! $filteredClass) {
            $programNames = Program::pluck('program_name', 'id');
            $privateNames = PrivatePackage::pluck('package_name', 'id');

            CandidateStudent::query()
                ->whereBetween('trial_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ])
                ->when(
                    $programFilter === 'private',
                    fn ($q) => $q->whereNotNull('private_package_id')
                )
                ->when(
                    $programFilter && $programFilter !== 'private',
                    fn ($q) => $q->where(
                        'program_id',
                        $programFilter
                    )
                )
                ->orderBy('trial_date')
                ->get([
                    'id',
                    'name',
                    'program_id',
                    'private_package_id',
                    'trial_date',
                    'trial_status',
                ])
                ->each(function ($c) use (
                    &$trialsByDay,
                    $programNames,
                    $privateNames
                ) {
                    $date = Carbon::parse($c->trial_date);
                    $dayIndex = $date->dayOfWeekIso - 1;

                    $label = $c->private_package_id
                        ? ($privateNames[$c->private_package_id] ?? 'Private')
                        : ($programNames[$c->program_id] ?? '-');

                    $trialStatus = [
                        'Pending' => 'Menunggu',
                        'Completed' => 'Selesai',
                        'Cancelled' => 'Dibatalkan',
                    ];

                    $trialsByDay[$dayIndex][] = [
                        'id' => 'trial-' . $c->id,
                        'title' => 'Trial - ' . $c->name,
                        'candidate_name' => $c->name,
                        'kind' => 'Trial',
                        'class_name' => 'Trial Calon Siswa (' . $label . ')',
                        'teacher_name' => null,
                        'teacher_note' => null,
                        'time_label' => 'Jam menyusul',
                        'date_label' => self::DAY_ORDER[$dayIndex]
                            . ', '
                            . $date->format('d M Y'),
                        'room' => '-',
                        'students_count' => null,
                        'status_key' => $c->trial_status,
                        'status_label' => $trialStatus[$c->trial_status]
                            ?? $c->trial_status,
                        'has_conflict' => false,
                    ];
                });
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Slot waktu dan konfigurasi grid
        |--------------------------------------------------------------------------
        */

        $allItems = collect($byDay)->flatten(1);

        $startHour = 9;
        $endHour = 21;

        if ($allItems->isNotEmpty()) {
            $startHour = min(
                $startHour,
                (int) floor($allItems->min('start_min') / 60)
            );

            $endHour = max(
                $endHour,
                (int) ceil($allItems->max('end_min') / 60)
            );
        }

        $slots = [];

        for (
            $m = $startHour * 60;
            $m < $endHour * 60;
            $m += self::SLOT_MINUTES
        ) {
            $slots[] = [
                'min' => $m,
                'label' => sprintf(
                    '%02d:%02d',
                    intdiv($m, 60),
                    $m % 60
                ),
                'is_hour' => $m % 60 === 0,
            ];
        }

        $dayCount = (
            isset($byDay[6])
            || isset($trialsByDay[6])
        ) ? 7 : 6;

        $days = array_slice(self::DAY_ORDER, 0, $dayCount);

        if ($roomFilter) {
            $roomColumns = [$roomFilter];
        } elseif ($filteredClass && $allItems->isNotEmpty()) {
            $roomColumns = array_values(array_filter(
                self::ROOMS,
                fn ($room) => $allItems->contains('room', $room)
            ));

            if ($allItems->contains(
                fn ($item) => ! in_array($item['room'], self::ROOMS, true)
            )) {
                $roomColumns[] = 'Lainnya';
            }
        } else {
            $hasOtherRoom = $allItems->contains(
                fn ($item) => ! in_array($item['room'], self::ROOMS, true)
            );

            $roomColumns = $hasOtherRoom
                ? [...self::ROOMS, 'Lainnya']
                : self::ROOMS;
        }

        $dayRooms = [];

        foreach ($days as $i => $dayName) {
            if ($filteredClass && ! $roomFilter) {
                $used = collect($byDay[$i] ?? [])
                    ->pluck('room')
                    ->map(
                        fn ($room) => in_array($room, self::ROOMS, true)
                            ? $room
                            : 'Lainnya'
                    )
                    ->unique();

                $cols = array_values(array_filter(
                    [...self::ROOMS, 'Lainnya'],
                    fn ($room) => $used->contains($room)
                ));

                $dayRooms[$dayName] = $cols ?: [''];
            } else {
                $dayRooms[$dayName] = $roomColumns;
            }
        }

        $sessions = [];
        $trials = [];
        $grid = [];

        foreach ($days as $i => $dayName) {
            $trials[$dayName] = $trialsByDay[$i] ?? [];

            $perRoom = [];

            foreach ($byDay[$i] ?? [] as $item) {
                $roomKey = in_array($item['room'], self::ROOMS, true)
                    ? $item['room']
                    : 'Lainnya';

                if (! in_array($roomKey, $dayRooms[$dayName], true)) {
                    continue;
                }

                $perRoom[$roomKey][] = $item;
            }

            $sessions[$dayName] = [];

            foreach ($dayRooms[$dayName] as $room) {
                $laid = isset($perRoom[$room])
                    ? $this->layoutDay($perRoom[$room])
                    : [];

                $grid[$dayName][$room] = [
                    'lanes' => $laid
                        ? (int) max(array_column($laid, 'lanes'))
                        : 1,
                    'sessions' => $laid,
                ];

                array_push($sessions[$dayName], ...$laid);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Data filter dan view
        |--------------------------------------------------------------------------
        */

        $programs = Program::orderBy('id')
            ->get(['id', 'program_name']);

        $roomOptions = self::ROOMS;

        $classOptions = ClassModel::whereIn(
            'status',
            ['Open', 'Running']
        )
            ->orderBy('class_name')
            ->get([
                'id',
                'class_name',
                'private_package_id',
            ]);

        $prevWeek = $weekStart->copy()
            ->subWeek()
            ->toDateString();

        $nextWeek = $weekStart->copy()
            ->addWeek()
            ->toDateString();

        return view('admin.jadwal.index', compact(
            'weekStart',
            'days',
            'sessions',
            'trials',
            'slots',
            'startHour',
            'endHour',
            'grid',
            'roomColumns',
            'roomOptions',
            'conflictCount',
            'teachers',
            'programs',
            'classOptions',
            'prevWeek',
            'nextWeek',
            'filteredClass',
            'dayRooms'
        ));
    }

    private function layoutDay(array $items): array
    {
        usort(
            $items,
            fn ($a, $b) => [
                $a['start_min'],
                $a['end_min'],
            ] <=> [
                $b['start_min'],
                $b['end_min'],
            ]
        );

        $result = [];
        $cluster = [];
        $laneEnds = [];
        $clusterEnd = -1;

        $flush = function () use (
            &$result,
            &$cluster,
            &$laneEnds
        ) {
            $lanes = max(1, count($laneEnds));

            foreach ($cluster as $item) {
                $item['lanes'] = $lanes;
                $result[] = $item;
            }

            $cluster = [];
            $laneEnds = [];
        };

        foreach ($items as $item) {
            if ($cluster && $item['start_min'] >= $clusterEnd) {
                $flush();
                $clusterEnd = -1;
            }

            $lane = 0;

            while (
                isset($laneEnds[$lane])
                && $laneEnds[$lane] > $item['start_min']
            ) {
                $lane++;
            }

            $laneEnds[$lane] = $item['end_min'];
            $item['lane'] = $lane;

            $cluster[] = $item;
            $clusterEnd = max($clusterEnd, $item['end_min']);
        }

        $flush();

        return $result;
    }
}
