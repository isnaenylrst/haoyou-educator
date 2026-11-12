<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\ClassSessionTeacherOffer;
use App\Models\TeacherAvailability;
use App\Models\TeacherLeave;
use App\Services\ClassScheduleService;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    private const DAYS = [
        'Senin' => 0, 'Selasa' => 1, 'Rabu' => 2, 'Kamis' => 3,
        'Jumat' => 4, 'Sabtu' => 5, 'Minggu' => 6,
    ];

    private function colorFor(string $className): string
    {
        $n = strtolower($className);
        return match (true) {
            str_contains($n, 'maochong') => 'orange',
            str_contains($n, 'jianer'), str_contains($n, 'jian er') => 'blue',
            str_contains($n, 'hudie')    => 'purple',
            str_contains($n, 'feixiang') => 'red',
            default                      => 'green', // HSK / Private / NSP
        };
    }

    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        try {
            $weekStart = $request->filled('week')
                ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $e) {
            $weekStart = now()->startOfWeek(Carbon::MONDAY);
        }
        $weekEnd = $weekStart->copy()->addDays(6);

        $scope           = $request->get('scope', 'all');   // all | mine
        $teacherFilterId = $request->get('teacher_id');

        $base = DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->leftJoin('teachers as t', 't.id', '=', 'c.teacher_id')
            ->whereIn('c.status', ['Open', 'Running'])
            ->select(
                'cs.id', 'cs.class_id', 'cs.day', 'cs.start_time', 'cs.end_time', 'cs.room',
                'c.class_name', 'c.delivery_mode', 'c.teacher_id', 'c.teacher_response', 't.name as teacher_name',
                DB::raw("(select count(*) from class_enrollments ce
                          where ce.class_id = c.id and ce.status = 'Active') as students")
            );

        $q = clone $base;
        if ($scope === 'mine') {
            $repScheduleIds = DB::table('teacher_leaves')
                ->where('replacement_teacher_id', $teacher->id)
                ->where('status', 'Approved')
                ->whereBetween('leave_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->pluck('class_schedule_id')
                ->all();

            $q->where(function ($sub) use ($teacher, $repScheduleIds) {
                $sub->where(function ($own) use ($teacher) {
                    $own->where('c.teacher_id', $teacher->id)->where('c.teacher_response', 'Accepted');
                });
                if (!empty($repScheduleIds)) {
                    $sub->orWhereIn('cs.id', $repScheduleIds);
                }
            });
        } elseif ($teacherFilterId) {
            $q->where('c.teacher_id', $teacherFilterId);
        }
        $rows = $q->orderBy('cs.start_time')->get();

        // Cek data cuti & guru pengganti minggu ini
        $leavesThisWeek = DB::table('teacher_leaves')
            ->where('status', 'Approved')
            ->whereBetween('leave_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->keyBy(fn ($l) => $l->class_schedule_id . '|' . Carbon::parse($l->leave_date)->toDateString());

        // jurnal yang sudah diisi minggu ini (untuk tanda "terisi")
        $filled = DB::table('teaching_journals')
            ->where('teacher_id', $teacher->id)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['class_schedule_id', 'session_date'])
            ->mapWithKeys(fn ($j) => [$j->class_schedule_id . '|' . Carbon::parse($j->session_date)->toDateString() => true]);

        // Entri jadwal minggu ini. Sumber kebenaran = pertemuan nyata (class_sessions), supaya perubahan dari
        // admin (Ubah Jadwal) ikut terlihat. Kelas yang belum punya pertemuan memakai pola mingguan (class_schedules).
        $entries = $this->weekEntries($rows, $weekStart, $weekEnd, $teacher->id);

        foreach ($entries as $e) {
            $e->is_filled = isset($filled[$e->id . '|' . $e->date]);
        }

        // susun grid [jam][hari][]
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if ($entries->contains('day', 'Minggu')) $dayNames[] = 'Minggu';

        $slots = $entries->map(fn ($r) => substr($r->start_time, 0, 5))->unique()->sort()->values();

        $cells = [];
        foreach ($entries as $e) {
            // Penanda cuti / guru pengganti (dari Kurikulum)
            $leave = $leavesThisWeek->get($e->id . '|' . $e->date);

            $e->is_replacement = false;
            $e->is_on_leave    = false;

            if ($leave) {
                if ((int) $leave->replacement_teacher_id === (int) $teacher->id) {
                    $e->is_replacement = true;
                    $e->is_mine        = true;
                } elseif ((int) $e->teacher_id === (int) $teacher->id) {
                    $e->is_on_leave = true;
                }
            }

            $cells[substr($e->start_time, 0, 5)][$e->day][] = $e;
        }

        $dayHeaders = collect($dayNames)->map(fn ($d) => [
            'name' => $d,
            'date' => $weekStart->copy()->addDays(self::DAYS[$d]),
        ]);

        $teachers = DB::table('teachers')->where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        $next = $this->upNext($teacher->id);

        // Ketersediaan saya (bisa diedit sendiri)
        $myAvailabilities = TeacherAvailabilityService::sortSlots(
            TeacherAvailability::where('teacher_id', $teacher->id)->get()
        );
        $days = TeacherAvailability::DAYS;

        // Kelas yang menunggu jawaban saya: ditugaskan langsung, diajukan Kurikulum, atau tawaran broadcast
        $pendingClasses = ClassModel::with('schedules')
            ->whereIn('status', ['Open', 'Running'])
            ->where(function ($q) use ($teacher) {
                $q->where(fn ($q) => $q->where('teacher_id', $teacher->id)->where('teacher_response', 'Pending'))
                  ->orWhereHas('teacherOffers', fn ($o) => $o->where('teacher_id', $teacher->id)->where('status', 'Pending'))
                  ->orWhere(fn ($q) => $q->where('broadcast_to_all', true)->whereNull('teacher_id'));
            })
            ->orderBy('class_name')
            ->get()
            ->map(function ($c) use ($myAvailabilities, $teacher) {
                $c->fits     = TeacherAvailabilityService::coversClass($myAvailabilities, $c);
                $c->conflict = TeacherAvailabilityService::conflictWith($teacher->id, $c);
                return $c;
            });

        // Kelas pengganti (satu pertemuan untuk satu siswa) yang diajukan kepada saya. Siapa cepat dia dapat.
        $scheduleSvc = app(ClassScheduleService::class);

        $replacementOffers = ClassSessionTeacherOffer::with(['session.enrollment.student', 'session.enrollment.class'])
            ->where('teacher_id', $teacher->id)
            ->where('status', 'Pending')
            ->whereHas('session', fn ($q) => $q
                ->where('kind', 'Replacement')->where('status', 'Scheduled')
                ->whereNull('teacher_id')->whereDate('session_date', '>=', today()))
            ->get()
            ->sortBy(fn ($o) => $o->session->session_date->toDateString() . ' ' . $o->session->start_time)
            ->values()
            ->map(function ($o) use ($teacher, $scheduleSvc) {
                $s = $o->session;

                $o->clash = false;
                try {
                    $scheduleSvc->assertTeacherFreeOn($teacher->id, $s->session_date, $s->start_time, $s->end_time, $s->id);
                } catch (ValidationException) {
                    $o->clash = true;
                }

                $o->fits = $scheduleSvc->isTeacherAvailable(
                    $teacher->id,
                    ClassScheduleService::DAYS[$s->session_date->dayOfWeekIso - 1],
                    $s->start_time,
                    $s->end_time
                );

                return $o;
            });

        // Permintaan dari guru lain agar saya menggantikan kelasnya
        $replacementRequests = TeacherLeave::with(['teacher', 'classSchedule.class'])
            ->where('replacement_teacher_id', $teacher->id)
            ->where('status', 'Pending')
            ->where('replacement_response', 'Pending')
            ->whereDate('leave_date', '>=', now()->toDateString())
            ->orderBy('leave_date')
            ->get();

        // Kalender ketersediaan SEMUA guru aktif (termasuk saya), untuk tahu siapa bisa menggantikan / diberi kelas baru
        $availabilityGrid = TeacherAvailabilityService::weeklyGrid(
            TeacherAvailability::with('teacher')
                ->whereHas('teacher', fn ($q) => $q->where('status', 'Active'))
                ->get()
        );
        $myTeacherId = $teacher->id;

        return view('guru.schedule', compact(
            'weekStart', 'weekEnd', 'scope', 'teacherFilterId', 'teachers',
            'slots', 'cells', 'dayHeaders', 'next',
            'myAvailabilities', 'days', 'pendingClasses',
            'replacementRequests', 'replacementOffers', 'availabilityGrid', 'myTeacherId'
        ));
    }

    /**
     * Entri grid untuk satu minggu.
     * - Kelas yang punya pertemuan Regular: pakai class_sessions (tanggal/jam/ruang nyata, termasuk yang dipindah admin).
     * - Kelas tanpa pertemuan sama sekali: pakai pola mingguan (class_schedules), seperti sebelumnya.
     */
    private function weekEntries($rows, Carbon $weekStart, Carbon $weekEnd, int $myId)
    {
        $classIds     = $rows->pluck('class_id')->unique()->values()->all();
        $classInfo    = $rows->groupBy('class_id')->map->first();
        $withSessions = ClassSession::whereIn('class_id', $classIds)
            ->where('kind', 'Regular')->distinct()->pluck('class_id')->flip();
        $teacherNames = DB::table('teachers')->pluck('name', 'id');

        $entries = collect();

        // 1) Kelas tanpa pertemuan: pola mingguan
        foreach ($rows as $r) {
            if ($withSessions->has($r->class_id) || ! isset(self::DAYS[$r->day])) continue;

            $date = $weekStart->copy()->addDays(self::DAYS[$r->day])->toDateString();
            $entries->push($this->entry($r, $r->id, $date, $r->start_time, $r->end_time, $r->room,
                $r->teacher_id, $r->teacher_name, $myId));
        }

        // 2) Kelas dengan pertemuan: pertemuan minggu ini (Cancelled disembunyikan)
        $sessions = ClassSession::whereIn('class_id', $classIds)
            ->where('kind', 'Regular')
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('start_time')
            ->get();

        foreach ($sessions as $s) {
            $info = $classInfo->get($s->class_id);
            if (! $info) continue;

            $tid = $s->teacher_id ?? $info->teacher_id;

            $e = $this->entry($info, $s->class_schedule_id ?? $info->id, $s->session_date->toDateString(),
                $s->start_time, $s->end_time,
                $s->location_type === 'Home Visit' ? 'Home Visit' : $s->room,
                $tid, $teacherNames[$tid] ?? null, $myId);

            // Penanda kalau pertemuan ini pernah dipindah admin
            $e->moved_note = null;
            if ($s->original_date) {
                $e->moved_note = $s->original_date->isSameDay($s->session_date)
                    ? 'Jam/ruang diubah'
                    : 'Dipindah dari ' . $s->original_date->translatedFormat('D, d M');
            }

            $entries->push($e);
        }

        return $entries->values();
    }

    /** Satu kartu di grid: salinan data kelas + tanggal/jam/ruang/guru pertemuannya. */
    private function entry(object $info, $scheduleId, string $date, string $start, string $end, ?string $room, $teacherId, ?string $teacherName, int $myId): object
    {
        $e = clone $info;

        $e->id           = $scheduleId;
        $e->day          = array_search(Carbon::parse($date)->dayOfWeekIso - 1, self::DAYS, true);
        $e->start_time   = $start;
        $e->end_time     = $end;
        $e->room         = $room;
        $e->teacher_id   = $teacherId;
        $e->teacher_name = $teacherName;
        $e->color        = $this->colorFor($info->class_name);
        $e->is_mine      = (int) $teacherId === $myId && $info->teacher_response === 'Accepted';
        $e->date         = $date;
        $e->time         = substr($start, 0, 5) . ' – ' . substr($end, 0, 5);
        $e->is_filled    = false;
        $e->moved_note   = null;

        return $e;
    }

    /**
     * Sesi mengajar guru ini yang paling dekat dari sekarang.
     * Pertemuan nyata (class_sessions) dulu, jadi perubahan jadwal dari admin ikut terlihat;
     * pola mingguan hanya untuk kelas yang belum punya pertemuan.
     */
    private function upNext(int $teacherId)
    {
        $best = null;

        // 1) Pertemuan nyata
        $sessions = ClassSession::with('class:id,class_name,delivery_mode')
            ->where('teacher_id', $teacherId)
            ->where('kind', 'Regular')
            ->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today())
            ->whereHas('class', fn ($q) => $q->where('teacher_response', 'Accepted')->whereIn('status', ['Open', 'Running']))
            ->orderBy('session_date')->orderBy('start_time')
            ->get();

        foreach ($sessions as $s) {
            $date  = $s->session_date->toDateString();
            $start = Carbon::parse($date . ' ' . $s->start_time);
            $end   = Carbon::parse($date . ' ' . $s->end_time);
            if ($end->lt(now())) continue;

            $best = (object) [
                's' => (object) [
                    'id'            => $s->class_schedule_id,
                    'class_id'      => $s->class_id,
                    'class_name'    => $s->class->class_name ?? '-',
                    'delivery_mode' => $s->class->delivery_mode ?? '-',
                    'room'          => $s->location_type === 'Home Visit' ? 'Home Visit' : $s->room,
                ],
                'start' => $start, 'end' => $end, 'date' => $date,
            ];
            break;
        }

        // 2) Kelas yang belum punya pertemuan: pola mingguan
        $withSessions = ClassSession::where('kind', 'Regular')->distinct()->pluck('class_id')->all();

        $schedules = DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('c.teacher_id', $teacherId)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('c.status', ['Open', 'Running'])
            ->whereNotIn('c.id', $withSessions)
            ->select('cs.*', 'c.class_name', 'c.delivery_mode')
            ->get();

        $monday = now()->startOfWeek(Carbon::MONDAY);

        foreach ($schedules as $s) {
            if (!isset(self::DAYS[$s->day])) continue;
            foreach ([0, 7] as $plus) {
                $date  = $monday->copy()->addDays(self::DAYS[$s->day] + $plus);
                $start = Carbon::parse($date->toDateString() . ' ' . $s->start_time);
                $end   = Carbon::parse($date->toDateString() . ' ' . $s->end_time);
                if ($end->lt(now())) continue;
                if (!$best || $start->lt($best->start)) {
                    $best = (object) ['s' => $s, 'start' => $start, 'end' => $end, 'date' => $date->toDateString()];
                }
            }
        }

        if (!$best) return null;

        $students = DB::table('class_enrollments as ce')
            ->join('students as st', 'st.id', '=', 'ce.student_id')
            ->where('ce.class_id', $best->s->class_id)->where('ce.status', 'Active')
            ->orderBy('st.name')->get(['st.id', 'st.name']);

        $att = DB::table('attendances as a')
            ->join('teaching_journals as j', 'j.id', '=', 'a.teaching_journal_id')
            ->where('j.class_schedule_id', $best->s->id ?? 0)
            ->where('j.session_date', $best->date)
            ->pluck('a.status', 'a.student_id');

        $best->students = $students->map(function ($st) use ($att) {
            $st->status = $att[$st->id] ?? null;
            return $st;
        });

        return $best;
    }
}