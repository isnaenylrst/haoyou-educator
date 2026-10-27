<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\SessionFee;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Services\ClassSessionRescheduleService;
use App\Services\ClassSessionService;
use App\Services\SessionFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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

        /* ---------- Pertemuan minggu ini (sumber: class_sessions) ---------- */
        $rows = ClassSession::query()
            ->with([
                'journal',
                'schedule',
                'enrollment.student',
                'fees',
                'class' => fn ($q) => $q
                    ->with(['programPackage.program', 'privatePackage'])
                    ->withCount([
                        'enrollments as students_count' => fn ($e) => $e->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
                    ]),
            ])
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when($filteredClass, fn ($q) => $q->where('class_id', $filteredClass->id))
            ->when($programFilter === 'private', fn ($q) => $q->where(
                fn ($w) => $w->where('kind', 'Replacement')
                    ->orWhereHas('class', fn ($c) => $c->whereNotNull('private_package_id'))
            ))
            ->when($programFilter && $programFilter !== 'private', fn ($q) => $q->whereHas(
                'class.programPackage',
                fn ($p) => $p->where('program_id', $programFilter)
            ))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        /* ---------- Izin guru di minggu ini (untuk guru pengganti) ---------- */
        $leaves = TeacherLeave::query()
            ->whereIn('class_schedule_id', $rows->pluck('class_schedule_id')->filter()->unique())
            ->where('status', 'Approved')
            ->whereBetween('leave_date', [$weekStart->copy()->subWeeks(4)->toDateString(), $weekEnd->copy()->addWeeks(4)->toDateString()])
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

        foreach ($rows as $s) {
            $kelas       = $s->class; // null untuk kelas pengganti
            $isReplace   = $s->kind === 'Replacement';
            $isPrivate   = $isReplace || ($kelas && ! is_null($kelas->private_package_id));
            $programName = $kelas?->programPackage?->program?->program_name ?? '';
            $kind        = $isReplace
                ? 'Replacement'
                : ($isPrivate ? 'Private' : (str_contains(strtolower($programName), 'hsk') ? 'HSK' : 'Daily Activity'));

            $date     = $s->session_date->copy();
            $dayIndex = $date->dayOfWeekIso - 1;
            $journal  = $s->journal;

            // Izin disetujui merujuk tanggal pola asli, jadi cek tanggal sekarang dan tanggal asal
            $leave = $leaves->get($s->class_schedule_id . '|' . $date->toDateString())
                ?? ($s->original_date ? $leaves->get($s->class_schedule_id . '|' . $s->original_date->toDateString()) : null);

            // Guru yang sebenarnya mengajar pada tanggal ini
            $teacherId = $s->teacher_id;
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
                $statusKey = $journal->class_status;
            } elseif ($s->status === 'Cancelled') {
                $statusKey = 'Cancelled';
            } elseif ($s->status === 'Completed') {
                $statusKey = 'Conducted';
            } else {
                $statusKey = $date->isPast() && ! $date->isToday() ? 'NoJournal' : 'Scheduled';
            }

            $statusLabel = match ($statusKey) {
                'NoJournal' => 'Belum ada jurnal',
                'Scheduled' => 'Terjadwal',
                default     => $statusLabels[$statusKey] ?? $statusKey,
            };

            [$sh, $sm] = array_map('intval', explode(':', $s->start_time));
            [$eh, $em] = array_map('intval', explode(':', $s->end_time));
            $startText = substr($s->start_time, 0, 5);
            $endText   = substr($s->end_time, 0, 5);

            $canReschedule = $s->canBeRescheduled();
            $studentName   = $s->enrollment?->student?->name;
            $title         = $kelas?->class_name ?? ('Pengganti' . ($studentName ? ': ' . $studentName : ''));

            $kindNote = $s->kind === 'Replacement' ? 'Pertemuan tambahan (di luar hitungan paket)' : null;

            $fees = $s->fees->map(fn ($f) => [
                'id'      => $f->id,
                'label'   => $f->fee_type === 'Replacement' ? 'Kelas pengganti tambahan' : 'Biaya ke rumah murid',
                'amount'  => 'Rp' . number_format((float) $f->amount, 0, ',', '.'),
                'paid'    => $f->status === 'Paid',
                'paid_at' => $f->paid_at?->format('d M Y'),
                'invoice_number' => $f->invoice_number,
                'invoice_url'    => $f->invoice_number ? route('admin.jadwal.fees.invoice', $f) : null,
            ])->values()->all();

            $byDay[$dayIndex][] = [
                'id'             => 'session-' . $s->id,
                'session_id'     => $s->id,
                'title'          => $title,
                'kind'           => $kind,
                'class_name'     => $title,
                'student_name'   => $studentName,
                'teacher_id'     => $teacherId,
                'teacher_name'   => $teacherId ? ($teacherNames[$teacherId] ?? null) : null,
                'teacher_note'   => $note,
                'start_time'     => $startText,
                'end_time'       => $endText,
                'time_label'     => "{$startText} – {$endText}",
                'start_min'      => $sh * 60 + $sm,
                'end_min'        => $eh * 60 + $em,
                'date_label'     => self::DAY_ORDER[$dayIndex] . ', ' . $date->format('d M Y'),
                'date_value'     => $date->toDateString(),
                'room'           => $s->room ?: ($s->location_type ?? '-'),
                'room_value'     => $s->room,
                'location_type'  => $s->location_type,
                'delivery_mode'  => $kelas?->delivery_mode,
                'students_count' => $kelas?->students_count,
                'session_number' => $s->session_number,
                'kind_note'      => $kindNote,
                'fees'           => $fees,
                'status_key'     => $statusKey,
                'status_label'   => $statusLabel,
                'has_conflict'   => false,
                // dipakai modal reschedule
                'can_complete'             => $s->canBeCompleted(),
                'can_reschedule'           => $canReschedule,
                'can_reschedule_following' => $canReschedule && $s->kind === 'Regular' && (bool) $s->schedule?->isCurrent(),
                'moved_from'               => $s->original_date && ! $s->original_date->isSameDay($date)
                    ? $s->original_date->format('d M Y')
                    : null,
                'change_reason'            => $s->change_reason,
            ];
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
        // Pendaftaran aktif yang sudah punya kelas (reguler maupun private) untuk dipilih sebagai penerima kelas pengganti
        $enrollmentOptions = ClassEnrollment::with(['student:id,name', 'class:id,class_name'])
            ->where('status', 'Active') // sesuai COUNTED_ENROLLMENT_STATUSES, tanpa 'Completed'
            ->whereNotNull('class_id')
            ->get()
            ->map(fn ($e) => ['id' => $e->id, 'label' => ($e->student?->name ?? '-') . ' — ' . ($e->class?->class_name ?? '-')])
            ->sortBy('label')
            ->values();

        $classOptions = ClassModel::whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'private_package_id']);

        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();

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
            'enrollmentOptions',
            'prevWeek',
            'nextWeek',
            'filteredClass',
            'dayRooms'
        ));
    }

    /** Tambah kelas pengganti untuk satu siswa. */
    public function storeSession(Request $request, ClassSessionService $service)
    {
        $data = $request->validate([
            'class_enrollment_id' => ['required', 'exists:class_enrollments,id'],
            'teacher_id'          => ['required', 'exists:teachers,id'],
            'session_date'        => ['required', 'date'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i'],
            'location_type'       => ['required', Rule::in(['Studio', 'Online', 'Home Visit'])],
            'room'                => ['nullable', Rule::in(self::ROOMS)],
        ]);

        $service->createReplacement($data);

        return back()->with('success', 'Pertemuan tambahan ditambahkan. Biaya Rp'
            . number_format(SessionFeeService::FEE_REPLACEMENT, 0, ',', '.') . ' tercatat belum dibayar.');
    }

    /** Tandai biaya tambahan (pengganti tambahan / ke rumah murid) sudah dibayar. */
    public function payFee(Request $request, SessionFee $sessionFee, SessionFeeService $service)
    {
        $data = $request->validate([
            'paid_at'        => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', 'string', 'max:50'],
            'proof'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $data['payment_proof_path'] = $request->file('proof')?->store('session-fee-proofs', 'public');

        $service->markPaid($sessionFee, $data);

        return back()->with('success', 'Biaya ditandai lunas.');
    }

    /** Cetak invoice biaya tambahan (hanya setelah lunas). */
    public function feeInvoice(SessionFee $sessionFee)
    {
        abort_unless($sessionFee->isPaid() && $sessionFee->invoice_number, 404);

        $sessionFee->load(['session.teacher', 'session.class:id,class_name', 'enrollment.student']);

        return view('admin.jadwal.feeinvoice', ['fee' => $sessionFee]);
    }

    /** Tandai pertemuan selesai. */
    public function complete(ClassSession $classSession, ClassSessionService $service)
    {
        $service->complete($classSession);

        return back()->with('success', 'Pertemuan ditandai selesai.');
    }

    /**
     * Ubah jadwal pertemuan: scope "one" (hanya pertemuan ini) atau "following" (ini dan seterusnya).
     */
    public function reschedule(Request $request, ClassSession $classSession, ClassSessionRescheduleService $service)
    {
        $data = $request->validate([
            'scope'                        => ['required', Rule::in(['one', 'following'])],
            'session_date'                 => ['required', 'date'],
            'start_time'                   => ['required', 'date_format:H:i'],
            'room'                         => ['nullable', Rule::in(self::ROOMS)],
            'reason'                       => ['nullable', 'string', 'max:255'],
            'confirm_outside_availability' => ['nullable', 'boolean'],
        ]);

        if ($data['scope'] === 'one') {
            $service->rescheduleOne($classSession, $data);
            $message = 'Jadwal pertemuan berhasil diubah.';
        } else {
            $count   = $service->rescheduleFollowing($classSession, $data);
            $message = "Jadwal {$count} pertemuan (ini dan seterusnya) berhasil diubah.";
        }

        return back()->with('success', $message);
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