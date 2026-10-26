<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassModel;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JadwalController extends Controller
{
    private const DAY_ORDER = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private const COUNTED_ENROLLMENT_STATUSES = ['Active', 'Completed'];

    // Ruang fisik sesuai sheet jadwal. "Online" ikut ditampilkan sebagai kolom
    // sendiri (bukan ruang fisik, tapi tetap satu "jalur" seperti di sheet).
    private const ROOMS = ['Beijing', 'Basement', 'Atas', 'Kaca', 'Online'];

    // Satu baris di grid = 30 menit
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
            $end   = $filteredClass->end_date ? Carbon::parse($filteredClass->end_date) : null;

            $anchor = $today->lt($start) ? $start : ($end && $today->gt($end) ? $end : $today);
        } else {
            $anchor = Carbon::today();
        }
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd   = $weekStart->copy()->addDays(6);

        $teacherFilter = $request->filled('teacher_id') ? (int) $request->teacher_id : null;
        $programFilter = $request->filled('program_id') ? $request->program_id : null; // 'private' atau id program
        $roomFilter    = $request->filled('room') && in_array($request->room, self::ROOMS, true)
            ? $request->room
            : null;

        /* ---------- Kelas yang relevan di minggu ini ---------- */
        $classes = ClassModel::query()
            ->with(['schedules', 'programPackage.program', 'privatePackage', 'teacher'])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
            ])
            ->where('status', '!=', 'Closed')
            ->where('start_date', '<=', $weekEnd->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $weekStart->toDateString()))
            // kelas Completed tanpa end_date tidak ditampilkan terus-menerus
            ->where(fn ($q) => $q->whereIn('status', ['Open', 'Running'])->orWhereNotNull('end_date'))
            ->when($programFilter === 'private', fn ($q) => $q->whereNotNull('private_package_id'))
            ->when($programFilter && $programFilter !== 'private', fn ($q) => $q->whereHas(
                'programPackage',
                fn ($p) => $p->where('program_id', $programFilter)
            ))
            ->when($filteredClass, fn ($q) => $q->where('id', $filteredClass->id))
            ->get();

        $scheduleIds = $classes->flatMap(fn ($k) => $k->schedules->pluck('id'))->all();

        /* ---------- Jurnal & izin guru di minggu ini (untuk status & guru pengganti) ---------- */
        $journals = TeachingJournal::query()
            ->whereIn('class_schedule_id', $scheduleIds)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['id', 'class_schedule_id', 'session_date', 'class_status', 'is_substitute', 'substitute_teacher_id'])
            ->keyBy(fn ($j) => $j->class_schedule_id . '|' . Carbon::parse($j->session_date)->toDateString());

        $leaves = TeacherLeave::query()
            ->whereIn('class_schedule_id', $scheduleIds)
            ->whereBetween('leave_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('status', 'Approved')
            ->get(['id', 'class_schedule_id', 'leave_date', 'replacement_teacher_id'])
            ->keyBy(fn ($l) => $l->class_schedule_id . '|' . Carbon::parse($l->leave_date)->toDateString());

        $teachers     = Teacher::orderBy('name')->get(['id', 'name', 'status']);
        $teacherNames = $teachers->pluck('name', 'id');
        $statusLabels = [
            'Conducted'   => 'Terlaksana',
            'Cancelled'   => 'Dibatalkan',
            'Rescheduled' => 'Dijadwal ulang',
        ];

        /* ---------- Bangun sesi per hari ---------- */
        $byDay = []; // [dayIndex => [session, ...]]

        foreach ($classes as $kelas) {
            $isPrivate   = ! is_null($kelas->private_package_id);
            $programName = $kelas->programPackage?->program?->program_name ?? '';
            $kind        = $isPrivate
                ? 'Private'
                : (str_contains(strtolower($programName), 'hsk') ? 'HSK' : 'Daily Activity');

            foreach ($kelas->schedules as $sch) {
                $dayIndex = array_search($sch->day, self::DAY_ORDER, true);
                if ($dayIndex === false) {
                    continue;
                }

                $date = $weekStart->copy()->addDays($dayIndex);

                if ($date->lt(Carbon::parse($kelas->start_date)->startOfDay())) {
                    continue;
                }
                if ($kelas->end_date && $date->gt(Carbon::parse($kelas->end_date)->endOfDay())) {
                    continue;
                }

                $key     = $sch->id . '|' . $date->toDateString();
                $journal = $journals->get($key);
                $leave   = $leaves->get($key);

                // Guru yang sebenarnya mengajar pada tanggal ini
                $teacherId = $kelas->teacher_id;
                $note      = null;

                if ($journal && $journal->is_substitute && $journal->substitute_teacher_id) {
                    $teacherId = $journal->substitute_teacher_id;
                    $note      = 'Guru pengganti';
                } elseif ($leave && $leave->replacement_teacher_id) {
                    $teacherId = $leave->replacement_teacher_id;
                    $note      = 'Guru pengganti (izin disetujui)';
                }

                if ($teacherFilter && (int) $teacherId !== $teacherFilter) {
                    continue;
                }

                if ($journal) {
                    $statusKey   = $journal->class_status;
                    $statusLabel = $statusLabels[$journal->class_status] ?? $journal->class_status;
                } else {
                    $statusKey   = $date->isPast() && ! $date->isToday() ? 'NoJournal' : 'Scheduled';
                    $statusLabel = $statusKey === 'NoJournal' ? 'Belum ada jurnal' : 'Terjadwal';
                }

                [$sh, $sm] = array_map('intval', explode(':', $sch->start_time));
                [$eh, $em] = array_map('intval', explode(':', $sch->end_time));
                $startMin  = $sh * 60 + $sm;
                $endMin    = $eh * 60 + $em;
                $startText = substr($sch->start_time, 0, 5);
                $endText   = substr($sch->end_time, 0, 5);

                $byDay[$dayIndex][] = [
                    'id'             => $key,
                    'title'          => $kelas->class_name,
                    'kind'           => $kind,
                    'class_name'     => $kelas->class_name,
                    'teacher_id'     => $teacherId,
                    'teacher_name'   => $teacherId ? ($teacherNames[$teacherId] ?? null) : null,
                    'teacher_note'   => $note,
                    'start_time'     => $startText,
                    'end_time'       => $endText,
                    'time_label'     => "{$startText} – {$endText}",
                    'start_min'      => $startMin,
                    'end_min'        => $endMin,
                    'date_label'     => self::DAY_ORDER[$dayIndex] . ', ' . $date->format('d M Y'),
                    'room'           => $sch->room ?: ($kelas->delivery_mode ?? '-'),
                    'delivery_mode'  => $kelas->delivery_mode,
                    'students_count' => $kelas->students_count,
                    'status_key'     => $statusKey,
                    'status_label'   => $statusLabel,
                    'has_conflict'   => false,
                ];
            }
        }

        /* ---------- Deteksi bentrok guru (guru sama, hari sama, jam beririsan) ---------- */
        $conflictCount = 0;
        foreach ($byDay as $dayIndex => $items) {
            $n = count($items);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $a = $items[$i];
                    $b = $items[$j];

                    if (! $a['teacher_id'] || (int) $a['teacher_id'] !== (int) $b['teacher_id']) {
                        continue;
                    }
                    if ($a['status_key'] === 'Cancelled' || $b['status_key'] === 'Cancelled') {
                        continue;
                    }
                    if ($a['start_min'] < $b['end_min'] && $b['start_min'] < $a['end_min']) {
                        $items[$i]['has_conflict'] = true;
                        $items[$j]['has_conflict'] = true;
                        $conflictCount++;
                    }
                }
            }
            $byDay[$dayIndex] = $items;
        }

        /* ---------- Trial calon siswa (hanya punya tanggal, tanpa jam & guru) ---------- */
        $trialsByDay = [];

        if (! $teacherFilter && ! $filteredClass) {
            $programNames = Program::pluck('program_name', 'id');
            $privateNames = PrivatePackage::pluck('package_name', 'id');

            CandidateStudent::query()
                ->whereBetween('trial_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->when($programFilter === 'private', fn ($q) => $q->whereNotNull('private_package_id'))
                ->when($programFilter && $programFilter !== 'private', fn ($q) => $q->where('program_id', $programFilter))
                ->orderBy('trial_date')
                ->get(['id', 'name', 'program_id', 'private_package_id', 'trial_date', 'trial_status'])
                ->each(function ($c) use (&$trialsByDay, $programNames, $privateNames) {
                    $date     = Carbon::parse($c->trial_date);
                    $dayIndex = $date->dayOfWeekIso - 1;
                    $label    = $c->private_package_id
                        ? ($privateNames[$c->private_package_id] ?? 'Private')
                        : ($programNames[$c->program_id] ?? '-');

                    $trialStatus = ['Pending' => 'Menunggu', 'Completed' => 'Selesai', 'Cancelled' => 'Dibatalkan'];

                    $trialsByDay[$dayIndex][] = [
                        'id'             => 'trial-' . $c->id,
                        'title'          => 'Trial - ' . $c->name,
                        'candidate_name' => $c->name,
                        'kind'           => 'Trial',
                        'class_name'     => 'Trial Calon Siswa (' . $label . ')',
                        'teacher_name'   => null,
                        'teacher_note'   => null,
                        'time_label'     => 'Jam menyusul',
                        'date_label'     => self::DAY_ORDER[$dayIndex] . ', ' . $date->format('d M Y'),
                        'room'           => '-',
                        'students_count' => null,
                        'status_key'     => $c->trial_status,
                        'status_label'   => $trialStatus[$c->trial_status] ?? $c->trial_status,
                        'has_conflict'   => false,
                    ];
                });
        }

        /* ---------- Rentang jam (baris per 30 menit) & hari yang ditampilkan ---------- */
        $allItems = collect($byDay)->flatten(1);

        $startHour = 9;
        $endHour   = 21;
        if ($allItems->isNotEmpty()) {
            $startHour = min($startHour, (int) floor($allItems->min('start_min') / 60));
            $endHour   = max($endHour, (int) ceil($allItems->max('end_min') / 60));
        }

        $slots = [];
        for ($m = $startHour * 60; $m < $endHour * 60; $m += self::SLOT_MINUTES) {
            $slots[] = [
                'min'     => $m,
                'label'   => sprintf('%02d:%02d', intdiv($m, 60), $m % 60),
                'is_hour' => $m % 60 === 0,
            ];
        }

        $dayCount = (isset($byDay[6]) || isset($trialsByDay[6])) ? 7 : 6;
        $days     = array_slice(self::DAY_ORDER, 0, $dayCount);

        if ($roomFilter) {
            $roomColumns = [$roomFilter];
        } elseif ($filteredClass && $allItems->isNotEmpty()) {
            // Hanya ruang yang dipakai kelas ini, urutan tetap mengikuti ROOMS
            $roomColumns = array_values(array_filter(
                self::ROOMS,
                fn ($r) => $allItems->contains('room', $r)
            ));

            if ($allItems->contains(fn ($s) => ! in_array($s['room'], self::ROOMS, true))) {
                $roomColumns[] = 'Lainnya';
            }
        } else {
            $hasOtherRoom = $allItems->contains(
                fn ($s) => ! in_array($s['room'], self::ROOMS, true)
            );
            $roomColumns = $hasOtherRoom ? [...self::ROOMS, 'Lainnya'] : self::ROOMS;
        }

        // Menampilkan ruangan perkelas pada jadwal kelas
        $dayRooms = [];
        foreach ($days as $i => $dayName) {
            if ($filteredClass && ! $roomFilter) {
                $used = collect($byDay[$i] ?? [])
                    ->pluck('room')
                    ->map(fn ($r) => in_array($r, self::ROOMS, true) ? $r : 'Lainnya')
                    ->unique();

                $cols = array_values(array_filter(
                    [...self::ROOMS, 'Lainnya'],
                    fn ($r) => $used->contains($r)
                ));

                $dayRooms[$dayName] = $cols ?: [''];
            } else {
                $dayRooms[$dayName] = $roomColumns;
            }
        }

        $sessions = [];
        $trials   = [];
        $grid     = [];

        foreach ($days as $i => $dayName) {
            $trials[$dayName] = $trialsByDay[$i] ?? [];

            $perRoom = [];
            foreach ($byDay[$i] ?? [] as $item) {
                $roomKey = in_array($item['room'], self::ROOMS, true) ? $item['room'] : 'Lainnya';

                if (! in_array($roomKey, $dayRooms[$dayName], true)) {
                    continue; // ruang disaring / tidak ada kolomnya
                }

                $perRoom[$roomKey][] = $item;
            }

            $sessions[$dayName] = [];

                foreach ($dayRooms[$dayName] as $room) {
                    $laid = isset($perRoom[$room]) ? $this->layoutDay($perRoom[$room]) : [];

                $grid[$dayName][$room] = [
                    'lanes'    => $laid ? (int) max(array_column($laid, 'lanes')) : 1,
                    'sessions' => $laid,
                ];

                array_push($sessions[$dayName], ...$laid);
            }
        }

        /* ---------- Data filter & form ---------- */
        $programs     = Program::orderBy('id')->get(['id', 'program_name']);
        $roomOptions  = self::ROOMS;
        $classOptions = ClassModel::whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'private_package_id']);

        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();

        return view('admin.jadwal', compact(
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
        usort($items, fn ($a, $b) => [$a['start_min'], $a['end_min']] <=> [$b['start_min'], $b['end_min']]);

        $result     = [];
        $cluster    = [];
        $laneEnds   = [];
        $clusterEnd = -1;

        $flush = function () use (&$result, &$cluster, &$laneEnds) {
            $lanes = max(1, count($laneEnds));
            foreach ($cluster as $item) {
                $item['lanes'] = $lanes;
                $result[]      = $item;
            }
            $cluster  = [];
            $laneEnds = [];
        };

        foreach ($items as $item) {
            if ($cluster && $item['start_min'] >= $clusterEnd) {
                $flush();
                $clusterEnd = -1;
            }

            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $item['start_min']) {
                $lane++;
            }

            $laneEnds[$lane] = $item['end_min'];
            $item['lane']    = $lane;
            $cluster[]       = $item;
            $clusterEnd      = max($clusterEnd, $item['end_min']);
        }

        $flush();

        return $result;
    }
}