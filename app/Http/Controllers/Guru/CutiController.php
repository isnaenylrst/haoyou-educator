<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\TeacherAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CutiController extends Controller
{
    private const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    private function teacher() { return Auth::user()->teacher; }

    /** Slot jadwal milik guru ini */
    private function mySchedules(int $teacherId)
    {
        return DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('c.teacher_id', $teacherId)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('c.status', ['Open', 'Running'])
            ->select('cs.id', 'cs.day', 'cs.start_time', 'cs.end_time', 'c.class_name')
            ->orderBy('cs.start_time')->get();
    }

    /**
     * Guru aktif yang tidak punya kelas bentrok di hari & jam yang sama.
     * Tiap guru diberi penanda `fits` = jam kelas tertutup oleh ketersediaan
     * yang dia isi di menu Schedule. Yang `fits` ditampilkan paling atas.
     */
    private function availableTeachers(object $schedule, int $excludeTeacherId)
    {
        $busy = DB::table('class_schedules as o')
            ->join('classes as oc', 'oc.id', '=', 'o.class_id')
            ->where('o.day', $schedule->day)
            ->where('o.start_time', '<', $schedule->end_time)
            ->where('o.end_time', '>', $schedule->start_time)
            ->whereIn('oc.status', ['Open', 'Running'])
            ->whereNotNull('oc.teacher_id')
            ->where('oc.teacher_response', 'Accepted')
            ->pluck('oc.teacher_id')->unique()->all();

        $teachers = DB::table('teachers')
            ->where('status', 'Active')
            ->where('id', '!=', $excludeTeacherId)
            ->whereNotIn('id', $busy)
            ->orderBy('name')->get(['id', 'name']);

        $slots = TeacherAvailability::whereIn('teacher_id', $teachers->pluck('id'))
            ->where('day', $schedule->day)
            ->get()
            ->groupBy('teacher_id');

        return $teachers->map(function ($t) use ($slots, $schedule) {
            $t->fits = ($slots[$t->id] ?? collect())
                ->contains(fn ($a) => $a->covers($schedule->day, $schedule->start_time, $schedule->end_time));

            return $t;
        })->sortBy([['fits', 'desc'], ['name', 'asc']])->values();
    }

    public function index()
    {
        $teacher   = $this->teacher();
        $schedules = $this->mySchedules($teacher->id);

        $scheduleOptions = $schedules->map(fn ($s) => [
            'id'    => $s->id,
            'day'   => $s->day,
            'label' => $s->class_name . ' · ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5),
        ])->values();

        $available = [];
        foreach ($schedules as $s) {
            $available[$s->id] = $this->availableTeachers($s, $teacher->id)
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'fits' => (bool) $t->fits])->values();
        }

        $history = DB::table('teacher_leaves as tl')
            ->join('class_schedules as cs', 'cs.id', '=', 'tl.class_schedule_id')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->leftJoin('teachers as rt', 'rt.id', '=', 'tl.replacement_teacher_id')
            ->where('tl.teacher_id', $teacher->id)
            ->select('tl.*', 'c.class_name', 'rt.name as replacement_name')
            ->orderByDesc('tl.id')
            ->paginate(10);

        return view('guru.cuti', compact('scheduleOptions', 'available', 'history'));
    }

    public function store(Request $request)
    {
        $teacher = $this->teacher();

        $data = $request->validate([
            'request_type'           => 'required|in:Cuti,Ganti Kelas',
            'leave_date'             => 'required|date|after_or_equal:today',
            'class_schedule_id'      => 'required|integer',
            'leave_type'             => 'nullable|in:Sick,Permission',
            'replacement_teacher_id' => 'nullable|integer|exists:teachers,id',
            'reason'                 => 'required|string|min:5|max:1000',
            'supporting_document'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // 1. jadwal harus milik guru ini
        $schedule = $this->mySchedules($teacher->id)->firstWhere('id', (int) $data['class_schedule_id']);
        if (!$schedule) {
            return back()->withInput()->withErrors(['class_schedule_id' => 'Kelas tidak valid.']);
        }

        // 2. hari pada tanggal harus sama dengan hari jadwal
        $dayName = self::DAYS[Carbon::parse($data['leave_date'])->dayOfWeekIso];
        if ($dayName !== $schedule->day) {
            return back()->withInput()->withErrors([
                'leave_date' => "Kelas ini berjadwal hari {$schedule->day}, bukan {$dayName}.",
            ]);
        }

        // 3. Ganti Kelas wajib punya pengganti; pengganti harus tersedia
        $replacementId = $data['replacement_teacher_id'] ?? null;
        if ($data['request_type'] === 'Ganti Kelas' && !$replacementId) {
            return back()->withInput()->withErrors(['replacement_teacher_id' => 'Pilih guru pengganti untuk Ganti Kelas.']);
        }
        if ($replacementId) {
            $ok = $this->availableTeachers($schedule, $teacher->id)->contains('id', (int) $replacementId);
            if (!$ok) {
                return back()->withInput()->withErrors(['replacement_teacher_id' => 'Guru pengganti tidak tersedia di jam tersebut.']);
            }
        }

        // 4. cegah pengajuan ganda
        $dup = DB::table('teacher_leaves')
            ->where('teacher_id', $teacher->id)
            ->where('class_schedule_id', $schedule->id)
            ->where('leave_date', $data['leave_date'])
            ->whereIn('status', ['Pending', 'Approved'])->exists();
        if ($dup) {
            return back()->withInput()->withErrors(['class_schedule_id' => 'Pengajuan untuk kelas dan tanggal ini sudah ada.']);
        }

        $docPath = $request->hasFile('supporting_document')
            ? $request->file('supporting_document')->store('leaves', 'public')
            : null;

        DB::table('teacher_leaves')->insert([
            'teacher_id'             => $teacher->id,
            'class_schedule_id'      => $schedule->id,
            'request_type'           => $data['request_type'],
            'leave_date'             => $data['leave_date'],
            'leave_type'             => $data['leave_type'] ?? 'Permission',
            'reason'                 => $data['reason'],
            'supporting_document'    => $docPath,
            'replacement_teacher_id' => $replacementId,
            'replacement_response'   => $replacementId ? 'Pending' : null,
            'status'                 => 'Pending',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return redirect()->route('teacher.cuti')->with('success', $replacementId
            ? 'Pengajuan dikirim. Guru pengganti perlu menerima dulu, setelah itu Kepala Kurikulum memprosesnya.'
            : 'Pengajuan dikirim ke Kepala Kurikulum.');
    }
}