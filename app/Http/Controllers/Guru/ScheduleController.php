<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\TeacherAvailability;
use App\Models\TeacherLeave;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $q->where('c.teacher_id', $teacher->id)->where('c.teacher_response', 'Accepted');
        } elseif ($teacherFilterId) {
            $q->where('c.teacher_id', $teacherFilterId);
        }
        $rows = $q->orderBy('cs.start_time')->get();

        // jurnal yang sudah diisi minggu ini (untuk tanda "terisi")
        $filled = DB::table('teaching_journals')
            ->where('teacher_id', $teacher->id)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['class_schedule_id', 'session_date'])
            ->mapWithKeys(fn ($j) => [$j->class_schedule_id . '|' . Carbon::parse($j->session_date)->toDateString() => true]);

        // susun grid [jam][hari][]
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if ($rows->contains('day', 'Minggu')) $dayNames[] = 'Minggu';

        $slots = $rows->map(fn ($r) => substr($r->start_time, 0, 5))->unique()->sort()->values();

        $cells = [];
        foreach ($rows as $r) {
            if (!isset(self::DAYS[$r->day])) continue;
            $date = $weekStart->copy()->addDays(self::DAYS[$r->day]);

            $r->color    = $this->colorFor($r->class_name);
            $r->is_mine  = (int) $r->teacher_id === (int) $teacher->id && $r->teacher_response === 'Accepted';
            $r->date     = $date->toDateString();
            $r->time     = substr($r->start_time, 0, 5) . ' – ' . substr($r->end_time, 0, 5);
            $r->is_filled = isset($filled[$r->id . '|' . $r->date]);

            $cells[substr($r->start_time, 0, 5)][$r->day][] = $r;
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

        // Kelas yang diberikan Kurikulum dan menunggu jawaban saya
        $pendingClasses = ClassModel::with('schedules')
            ->where('teacher_id', $teacher->id)
            ->where('teacher_response', 'Pending')
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get()
            ->map(function ($c) use ($myAvailabilities, $teacher) {
                $c->fits     = TeacherAvailabilityService::coversClass($myAvailabilities, $c);
                $c->conflict = TeacherAvailabilityService::conflictWith($teacher->id, $c);
                return $c;
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
            'replacementRequests', 'availabilityGrid', 'myTeacherId'
        ));
    }

    /** Sesi mengajar guru ini yang paling dekat dari sekarang (2 minggu ke depan) */
    private function upNext(int $teacherId)
    {
        $schedules = DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('c.teacher_id', $teacherId)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('c.status', ['Open', 'Running'])
            ->select('cs.*', 'c.class_name', 'c.delivery_mode')
            ->get();

        $best = null;
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
            ->where('j.class_schedule_id', $best->s->id)
            ->where('j.session_date', $best->date)
            ->pluck('a.status', 'a.student_id');

        $best->students = $students->map(function ($st) use ($att) {
            $st->status = $att[$st->id] ?? null;
            return $st;
        });

        return $best;
    }
}