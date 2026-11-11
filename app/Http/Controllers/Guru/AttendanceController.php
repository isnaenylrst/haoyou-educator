<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\ScheduleJournalUnlock;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Notifications\AppNotification;
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
                'class.programPackage.program',
                'class.level',
            ])
            ->where('day', $this->dayName($date))
            ->orderBy('start_time')
            ->get();

        $journals = TeachingJournal::with(['attendances.student', 'teacher', 'substituteTeacher'])
            ->whereDate('session_date', $date->toDateString())
            ->whereIn('class_schedule_id', $schedules->pluck('id'))
            ->get()
            ->keyBy('class_schedule_id');

        $unlocks = ScheduleJournalUnlock::whereIn('class_schedule_id', $schedules->pluck('id'))
            ->whereDate('session_date', $date->toDateString())
            ->get()
            ->keyBy('class_schedule_id');

        $build = function ($schedule) use ($journals, $unlocks, $date) {
            $journal       = $journals->get($schedule->id);
            $unlockRecord  = $unlocks->get($schedule->id);
            $sessionEnd    = Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);
            $deadline      = $sessionEnd->copy()->addHours(24);
            $isPast        = now()->gt($deadline);
            $isUnlocked    = ($unlockRecord && $unlockRecord->is_open);
            $isLocked      = $isPast && !$isUnlocked && !$journal;

            $status = !$journal
                ? ($isLocked ? 'terlambat' : 'belum_diisi')
                : match ($journal->status) {
                    'Reviewed' => 'dikunci',
                    'Revision' => 'revisi',
                    default    => 'sudah_diisi',
                };

            // Hitung nomor pertemuan otomatis
            $autoMeetingNumber = TeachingJournal::where('class_id', $schedule->class_id)
                ->where('class_status', 'Conducted')
                ->count() + ($journal ? 0 : 1);

            return [
                'schedule'            => $schedule,
                'journal'             => $journal,
                'status'              => $status,
                'deadline'            => $deadline,
                'is_past_deadline'    => $isPast,
                'is_unlocked'         => $isUnlocked,
                'unlocked_until'      => $unlockRecord?->unlocked_until,
                'unlock_record'       => $unlockRecord,
                'auto_meeting_number' => max(1, $autoMeetingNumber),
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
        $filledCount       = $sessions->where('status', '!=', 'belum_diisi')->where('status', '!=', 'terlambat')->count();
        $unfilledCount     = $sessions->where('status', 'belum_diisi')->count();
        $lateCount         = $sessions->where('status', 'terlambat')->count() + $substituteSessions->where('status', 'terlambat')->count();
        $revisionCount     = $sessions->where('status', 'revisi')->count()
            + $substituteSessions->where('status', 'revisi')->count();
        $totalHadirHariIni = $sessions->sum(
            fn ($i) => $i['journal'] ? $i['journal']->attendances->where('status', 'Present')->count() : 0
        );

        $teacherNames = Teacher::orderBy('name')->pluck('name', 'id');

        return view('guru.attendance', compact(
            'sessions', 'substituteSessions', 'date', 'canFill', 'teacherNames',
            'totalSessionToday', 'filledCount', 'unfilledCount', 'lateCount', 'revisionCount', 'totalHadirHariIni'
        ));
    }

    // ------------------------------------------------------------------ VALIDASI & HELPER BERSAMA

    private function validated(Request $request, ?ClassSchedule $classSchedule = null): array
    {
        $running = $request->input('class_running') === 'Berjalan';
        $req     = $running ? 'required' : 'nullable';

        // Auto fallback dari data kelas / jadwal jika tidak dikirim form
        $class = $classSchedule?->class;
        if ($class) {
            $defaultCourseType = $class->private_package_id ? 'Private' : ($class->programPackage?->package_name ?? $class->programPackage?->program?->program_name ?? 'Regular');
            $defaultCourseLevel = $class->level?->level_name ?? $class->programLevel?->level_name ?? null;

            if (!$request->filled('course_type')) {
                $request->merge(['course_type' => $defaultCourseType]);
            }
            if (!$request->filled('course_level') && $defaultCourseLevel) {
                $request->merge(['course_level' => $defaultCourseLevel]);
            }
        }

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
            'course_type'           => ['nullable', 'string', 'max:100'],
            'course_level'          => ['nullable', 'string', 'max:100'],
            'class_running'         => ['required', 'in:Berjalan,Tidak Berjalan'],
            'focus_materials'       => [$req, 'array'],
            'focus_materials.*'     => ['string', 'max:100'],
            'material_taught'       => [$req, 'string', 'max:500'],
            'learning_activities'   => [$req, 'string', 'max:2000'],
            'problems'              => ['required', 'string', 'max:2000'],   // kalau tidak berjalan: isi alasannya
            'solutions'             => [$req, 'string', 'max:2000'],
            'results'               => [$req, 'string', 'max:2000'],
            'session_score'         => [$req, 'in:10,5'],
            'attendance'            => ['nullable', 'array'],
            'attendance.*'          => [Rule::in(array_keys(config('journal.attendance_status', [
                'Present' => 'Hadir', 'Sick' => 'Sakit', 'Permission' => 'Izin', 'Absent' => 'Alpha',
            ])))],
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
            'course_type'           => $data['course_type'] ?? 'Regular',
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
        $data    = $this->validated($request, $classSchedule);
        $running = $data['class_running'] === 'Berjalan';

        $isSubstitute = $data['is_substitute'] === 'Ya';
        $date         = Carbon::parse($data['session_date']);

        if ($this->dayName($date) !== $classSchedule->day) {
            return back()->withInput()->with('error', "Tanggal tersebut bukan hari {$classSchedule->day}, sesuai jadwal kelas.");
        }

        // Cek batas 24 jam setelah sesi selesai
        $sessionEnd = Carbon::parse($data['session_date'] . ' ' . $classSchedule->end_time);
        $deadline   = $sessionEnd->copy()->addHours(24);
        $isPast     = now()->gt($deadline);

        $unlocked = ScheduleJournalUnlock::where('class_schedule_id', $classSchedule->id)
            ->whereDate('session_date', $data['session_date'])
            ->where('status', 'Unlocked')
            ->where('unlocked_until', '>', now())
            ->first();

        if ($isPast && !$unlocked) {
            return back()->withInput()->with('error', 'Batas waktu pengisian jurnal (24 jam setelah jam mengajar selesai) telah lewat. Form terkunci. Silakan ajukan permohonan buka kunci kepada Kurikulum.');
        }

        $alreadyFilled = TeachingJournal::where('class_schedule_id', $classSchedule->id)
            ->whereDate('session_date', $date->toDateString())
            ->exists();

        if ($alreadyFilled) {
            return back()->withInput()->with('error', 'Jurnal untuk sesi ini sudah diisi.');
        }

        try {
            $journal = DB::transaction(function () use ($data, $classSchedule, $class, $me, $isSubstitute, $running, $isPast, $unlocked) {
                $journal = TeachingJournal::create(
                    $this->journalAttributes($data, $running, $isSubstitute) + [
                        'filled_by'         => $me->id,                 // guru yang mengisi form
                        'class_id'          => $classSchedule->class_id,
                        'class_schedule_id' => $classSchedule->id,
                        'session_date'      => $data['session_date'],
                        'status'            => 'Pending',
                        'is_late'           => ($isPast && $unlocked !== null),
                    ]
                );

                // Absensi siswa tetap disimpan terlepas dari berapa yang hadir
                $studentIds = $class->enrollments()->where('status', 'Active')->pluck('student_id');
                if ($studentIds->isNotEmpty()) {
                    $this->saveAttendances($journal, $studentIds, $data);
                }

                return $journal;
            });

            // Notifikasi database ke seluruh tim Kurikulum
            $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
            foreach ($curriculums as $cUser) {
                $cUser->notify(new AppNotification(
                    title: "Jurnal Mengajar Masuk: {$class->class_name}",
                    message: "Guru {$me->name} telah mengisi jurnal pertemuan ke-{$journal->meeting_number} untuk kelas {$class->class_name}. Silakan review jurnal.",
                    url: url('/kurikulum/review-pengajuan?tab=jurnal'),
                    type: 'jurnal',
                    icon: 'fa-book-open'
                ));
            }

        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'Jurnal gagal disimpan. Pastikan sesi ini belum pernah diisi, lalu coba lagi.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan jurnal: ' . $e->getMessage());
        }

        return redirect()
            ->route('teacher.attendance', ['date' => $data['session_date']])
            ->with('success', 'Jurnal berhasil disimpan dan dikirim ke Kurikulum untuk direview.')
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

        $data         = $this->validated($request, $journal->schedule);
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

                $studentIds = $journal->attendances()->pluck('student_id');
                if ($studentIds->isEmpty() && $class) {
                    $studentIds = $class->enrollments()->where('status', 'Active')->pluck('student_id');
                }

                $journal->attendances()->delete();

                if ($studentIds->isNotEmpty()) {
                    $this->saveAttendances($journal, $studentIds, $data);
                }
            });

            // Notifikasi ke Kurikulum
            $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
            foreach ($curriculums as $cUser) {
                $cUser->notify(new AppNotification(
                    title: "Revisi Jurnal Dikirim Ulang: {$class->class_name}",
                    message: "Guru {$me->name} telah merevisi jurnal pertemuan ke-{$journal->meeting_number} kelas {$class->class_name}.",
                    url: url('/kurikulum/review-pengajuan?tab=jurnal'),
                    type: 'jurnal',
                    icon: 'fa-book-open'
                ));
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Revisi gagal disimpan: ' . $e->getMessage());
        }

        return redirect()
            ->route('teacher.attendance', ['date' => $journal->session_date->toDateString()])
            ->with('success', 'Revisi jurnal berhasil dikirim ulang ke Kurikulum.')
            ->with('pdf_url', route('teacher.attendance.pdf', $journal));
    }

    // ------------------------------------------------------------------ PERMOHONAN BUKA KUNCI JURNAL

    public function requestUnlock(Request $request)
    {
        $data = $request->validate([
            'class_schedule_id' => ['required', 'exists:class_schedules,id'],
            'session_date'      => ['required', 'date'],
            'request_reason'    => ['required', 'string', 'max:1000'],
        ], [
            'request_reason.required' => 'Alasan keterlambatan wajib diisi agar Kurikulum dapat mempertimbangkan pembukaan kunci.',
        ]);

        $schedule = ClassSchedule::with('class.teacher')->findOrFail($data['class_schedule_id']);
        $teacher = $this->teacher();
        $user = Auth::user();

        // Pastikan guru bersangkutan yang mengajar atau berhak atas sesi ini
        $isTeacher = (int) $schedule->class->teacher_id === $teacher->id;
        if (! $isTeacher) {
            // Bisa jadi dia guru pengganti
            $isSub = \App\Models\TeacherLeave::where('class_schedule_id', $schedule->id)
                ->whereDate('leave_date', $data['session_date'])
                ->where('replacement_teacher_id', $teacher->id)
                ->where('status', 'Approved')
                ->exists();

            if (! $isSub) {
                return back()->with('error', 'Anda tidak memiliki hak akses untuk meminta buka kunci kelas ini.');
            }
        }

        $unlock = ScheduleJournalUnlock::updateOrCreate(
            [
                'class_schedule_id' => $schedule->id,
                'session_date'      => $data['session_date'],
            ],
            [
                'status'         => 'Pending',
                'request_reason' => $data['request_reason'],
                'requested_at'   => now(),
                'requested_by'   => $user->id,
                'rejection_note' => null,
            ]
        );

        // Kirim notifikasi ke tim Kurikulum
        $curriculums = \App\Models\User::whereHas('level', fn ($q) => $q->where('nama_level', 'Kurikulum'))->get();
        $className = $schedule->class->class_name ?? 'Kelas';
        $sessionFormatted = Carbon::parse($data['session_date'])->translatedFormat('d M Y');

        foreach ($curriculums as $curr) {
            $curr->notify(new \App\Notifications\AppNotification(
                title: "Permohonan Buka Kunci Jurnal: {$className}",
                message: "Guru {$teacher->name} meminta pembukaan kunci jurnal kelas {$className} untuk sesi {$sessionFormatted}. Alasan: {$data['request_reason']}",
                url: url('/kurikulum/review-pengajuan?tab=jurnal'),
                type: 'jurnal',
                icon: 'fa-lock-open',
                actionLabel: 'Review & Buka Kunci'
            ));
        }

        return back()->with('success', 'Permohonan buka kunci jurnal berhasil dikirim ke Kurikulum. Mohon menunggu konfirmasi/ACC dari Kurikulum.');
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
