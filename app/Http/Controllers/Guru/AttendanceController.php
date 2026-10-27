<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    private const DAYS = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    private function teacher(): Teacher
    {
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        return $teacher;
    }

    private function dayName(Carbon $date): string
    {
        return self::DAYS[$date->format('l')];
    }

    /** Daftar level valid untuk jenis kelas (Regular = gabungan semua kelas). */
    private function levelsFor(?string $type): array
    {
        $data = config("journal.courses.$type", []);

        return $type === 'Regular' ? array_merge(...array_values($data)) : $data;
    }

    // ------------------------------------------------------------------ HALAMAN

    public function index(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date']]);

        $teacher = $this->teacher();
        $date    = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $canFill = !$date->isFuture();

        $schedules = ClassSchedule::with([
                'class.enrollments' => fn ($q) => $q->where('status', 'Active'),
                'class.enrollments.student',
            ])
            ->where('day', $this->dayName($date))
            ->orderBy('start_time')
            ->get();

        $journals = TeachingJournal::with(['attendances.student', 'teacher', 'substituteTeacher'])
            ->whereDate('session_date', $date->toDateString())
            ->whereIn('class_schedule_id', $schedules->pluck('id'))
            ->get()
            ->keyBy('class_schedule_id');

        $build = function ($schedule) use ($journals) {
            $journal = $journals->get($schedule->id);

            return [
                'schedule' => $schedule,
                'journal'  => $journal,
                'status'   => !$journal
                    ? 'belum_diisi'
                    : match ($journal->status) {
                        'Reviewed' => 'dikunci',
                        'Revision' => 'revisi',
                        default    => 'sudah_diisi',
                    },
            ];
        };

        [$own, $others] = $schedules->partition(
            fn ($s) => (int) $s->class->teacher_id === $teacher->id
        );

        $own = $own->filter(fn ($s) => $s->class->teacher_response === 'Accepted');

        $sessions = $own->map($build)->values();

        $substituteSessions = $others
            ->filter(fn ($s) => !$journals->has($s->id)
                || (int) $journals[$s->id]->teacher_id === $teacher->id
                || (int) $journals[$s->id]->filled_by === $teacher->id)
            ->map($build)
            ->values();

        $totalSessionToday = $sessions->count();
        $filledCount       = $sessions->where('status', '!=', 'belum_diisi')->count();
        $unfilledCount     = $sessions->where('status', 'belum_diisi')->count();
        $revisionCount     = $sessions->where('status', 'revisi')->count()
            + $substituteSessions->where('status', 'revisi')->count();
        $totalHadirHariIni = $sessions->sum(
            fn ($i) => $i['journal'] ? $i['journal']->attendances->where('status', 'Present')->count() : 0
        );

        $teacherNames = Teacher::orderBy('name')->pluck('name', 'id');

        return view('guru.attendance', compact(
            'sessions', 'substituteSessions', 'date', 'canFill', 'teacherNames',
            'totalSessionToday', 'filledCount', 'unfilledCount', 'revisionCount', 'totalHadirHariIni'
        ));
    }

    // ------------------------------------------------------------------ VALIDASI & HELPER BERSAMA

    private function validated(Request $request): array
    {
        $running = $request->input('class_running') === 'Berjalan';
        $type    = $request->input('course_type');
        $levels  = $this->levelsFor($type);
        $req     = $running ? 'required' : 'nullable';

        return $request->validate([
            'teaching_teacher_id'   => ['required', 'exists:teachers,id'],
            'is_substitute'         => ['required', 'in:Ya,Tidak'],
            'substitute_teacher_id' => ['nullable', 'required_if:is_substitute,Ya', 'exists:teachers,id', 'different:teaching_teacher_id'],
            'student_names'         => ['required', 'string', 'max:500'],
            'session_date'          => ['required', 'date', 'before_or_equal:today'],
            'is_last_meeting'       => ['required', 'in:Ya,Tidak'],
            'meeting_number'        => ['required', 'integer', 'min:1', 'max:999'],
            'start_time'            => ['required', 'date_format:H:i'],
            'end_time'              => ['required', 'date_format:H:i', 'after:start_time'],
            'course_type'           => ['required', Rule::in(array_keys(config('journal.courses')))],
            'course_level'          => [Rule::requiredIf(!empty($levels)), 'nullable', Rule::in($levels)],
            'class_running'         => ['required', 'in:Berjalan,Tidak Berjalan'],
            'focus_materials'       => [$req, 'array'],
            'focus_materials.*'     => [Rule::in(config('journal.focus'))],
            'material_taught'       => [$req, 'string'],
            'learning_activities'   => [$req, 'string'],
            'problems'              => ['required', 'string'],   // kalau tidak berjalan: isi alasannya
            'solutions'             => [$req, 'string'],
            'results'               => [$req, 'string'],
            'session_score'         => [$req, 'in:10,5'],
            'attendance'            => ['nullable', 'array'],
            'attendance.*'          => [Rule::in(array_keys(config('journal.attendance_status')))],
            'attendance_note'       => ['nullable', 'array'],
            'attendance_note.*'     => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** Kolom jurnal yang berasal dari form (dipakai simpan baru & revisi). */
    private function journalAttributes(array $data, bool $running, bool $isSubstitute): array
    {
        return [
            'teacher_id'            => $data['teaching_teacher_id'],   // guru yang mengajar
            'start_time'            => $data['start_time'],
            'end_time'              => $data['end_time'],
            'is_substitute'         => $isSubstitute,
            'substitute_teacher_id' => $isSubstitute ? $data['substitute_teacher_id'] : null,
            'student_names'         => $data['student_names'],
            'is_last_meeting'       => $data['is_last_meeting'] === 'Ya',
            'meeting_number'        => $data['meeting_number'],
            'course_type'           => $data['course_type'],
            'course_level'          => $data['course_level'] ?? null,
            'class_status'          => $running ? 'Conducted' : 'Cancelled',
            'focus_materials'       => $data['focus_materials'] ?? [],
            'material_taught'       => $data['material_taught'] ?? null,
            'learning_activities'   => $data['learning_activities'] ?? '-',
            'problems'              => $data['problems'],
            'solutions'             => $data['solutions'] ?? null,
            'results'               => $data['results'] ?? null,
            'session_score'         => $running ? (int) $data['session_score'] : null,
        ];
    }

    private function saveAttendances(TeachingJournal $journal, iterable $studentIds, array $data): void
    {
        foreach ($studentIds as $sid) {
            Attendance::create([
                'teaching_journal_id' => $journal->id,
                'student_id'          => $sid,
                'attendance_date'     => $data['session_date'],
                'session_date'        => $data['session_date'],
                'status'              => $data['attendance'][$sid] ?? 'Absent',
                'note'                => $data['attendance_note'][$sid] ?? null,
            ]);
        }
    }

    // ------------------------------------------------------------------ SIMPAN BARU

    public function store(Request $request, ClassSchedule $classSchedule)
    {
        $me      = $this->teacher();
        $class   = $classSchedule->class;
        $data    = $this->validated($request);
        $running = $data['class_running'] === 'Berjalan';

        $isSubstitute = $data['is_substitute'] === 'Ya';
        $date         = Carbon::parse($data['session_date']);

        if ($this->dayName($date) !== $classSchedule->day) {
            return back()->withInput()->with('error', "Tanggal tersebut bukan hari {$classSchedule->day}, sesuai jadwal kelas.");
        }

        $alreadyFilled = TeachingJournal::where('class_schedule_id', $classSchedule->id)
            ->whereDate('session_date', $date->toDateString())
            ->exists();

        if ($alreadyFilled) {
            return back()->withInput()->with('error', 'Jurnal untuk sesi ini sudah diisi.');
        }

        try {
            $journal = DB::transaction(function () use ($data, $classSchedule, $class, $me, $isSubstitute, $running) {
                $journal = TeachingJournal::create(
                    $this->journalAttributes($data, $running, $isSubstitute) + [
                        'filled_by'         => $me->id,                 // guru yang mengisi form
                        'class_id'          => $classSchedule->class_id,
                        'class_schedule_id' => $classSchedule->id,
                        'session_date'      => $data['session_date'],
                        'status'            => 'Pending',
                    ]
                );

                if ($running) {
                    $studentIds = $class->enrollments()->where('status', 'Active')->pluck('student_id');
                    $this->saveAttendances($journal, $studentIds, $data);
                }

                return $journal;
            });
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'Jurnal gagal disimpan. Pastikan sesi ini belum pernah diisi, lalu coba lagi.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan jurnal. Silakan coba lagi.');
        }

        return redirect()
            ->route('teacher.attendance', ['date' => $data['session_date']])
            ->with('success', 'Jurnal berhasil disimpan.')
            ->with('pdf_url', route('teacher.attendance.pdf', $journal));
    }

    // ------------------------------------------------------------------ REVISI (jurnal dikembalikan Kurikulum)

    public function update(Request $request, TeachingJournal $journal)
    {
        $me    = $this->teacher();
        $class = $journal->class;

        $allowed = in_array($me->id, array_filter([
            (int) $journal->teacher_id,
            (int) $journal->filled_by,
            (int) $class?->teacher_id,
        ]), true);

        abort_unless($allowed, 403);

        if ($journal->status !== 'Revision') {
            return back()->with('error', 'Jurnal ini tidak sedang diminta revisi.');
        }

        // Tanggal sesi tidak boleh diubah saat revisi
        $request->merge(['session_date' => $journal->session_date->toDateString()]);

        $data         = $this->validated($request);
        $running      = $data['class_running'] === 'Berjalan';
        $isSubstitute = $data['is_substitute'] === 'Ya';

        try {
            DB::transaction(function () use ($journal, $class, $data, $running, $isSubstitute) {
                $journal->update(
                    $this->journalAttributes($data, $running, $isSubstitute) + [
                        'status'      => 'Pending',   // kembali masuk antrian review Kurikulum
                        'review_note' => null,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                    ]
                );

                // Pakai daftar murid dari absensi awal; jika kosong (sebelumnya kelas tidak berjalan) pakai murid aktif
                $studentIds = $journal->attendances()->pluck('student_id');
                if ($studentIds->isEmpty()) {
                    $studentIds = $class->enrollments()->where('status', 'Active')->pluck('student_id');
                }

                $journal->attendances()->delete();

                if ($running) {
                    $this->saveAttendances($journal, $studentIds, $data);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Revisi gagal disimpan. Silakan coba lagi.');
        }

        return redirect()
            ->route('teacher.attendance', ['date' => $journal->session_date->toDateString()])
            ->with('success', 'Revisi jurnal berhasil dikirim ulang ke Kurikulum.')
            ->with('pdf_url', route('teacher.attendance.pdf', $journal));
    }

    // ------------------------------------------------------------------ PDF

    public function pdf(TeachingJournal $journal)
    {
        $teacher = $this->teacher();

        $journal->load(['teacher', 'substituteTeacher', 'class', 'attendances.student']);

        $allowed = in_array($teacher->id, array_filter([
            (int) $journal->teacher_id,
            (int) $journal->substitute_teacher_id,
            (int) $journal->filled_by,
            (int) $journal->class?->teacher_id,
        ]), true);

        abort_unless($allowed, 403);

        $file = sprintf(
            'jurnal-%s-%s.pdf',
            Str::slug($journal->class->class_name ?? 'kelas'),
            Carbon::parse($journal->session_date)->format('Ymd')
        );

        return Pdf::loadView('guru.pdf.journal', compact('journal'))
            ->setPaper('a4')
            ->download($file);
    }
}