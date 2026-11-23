<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ScheduleJournalUnlock;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Models\PointLog;
use App\Services\JournalStatusResolver;
use App\Services\PointService;
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
        $request->validate([
            'date'   => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:all,semua,belum_diisi,terlambat,terkunci,sudah_diisi,dikunci,revisi'],
        ]);

        $teacher = $this->teacher();
        $date    = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $canFill = !$date->isFuture();
        $dateStr = $date->toDateString();
        $dayName = $this->dayName($date);
        $filterStatus = $request->input('status', 'all');
        if ($filterStatus === 'semua') {
            $filterStatus = 'all';
        }

        // Hitung badge angka untuk setiap tab (lintas tanggal / semua waktu untuk guru ini)
        $statusCounts = JournalStatusResolver::countPerStatus($teacher);

        // Jika filterStatus !== 'all', kita muat sesi guru lintas tanggal sesuai filter tersebut
        // Jika filterStatus === 'all', kita muat sesi pada tanggal yang dipilih ($dateStr)
        $isCrossDate = ($filterStatus !== 'all');

        if ($isCrossDate) {
            $allCrossSessions = JournalStatusResolver::getTeacherSessions($teacher, null);

            // Filter status yang diminta
            if (in_array($filterStatus, ['terlambat', 'terkunci'])) {
                $filteredCross = $allCrossSessions->filter(fn ($i) => in_array($i['status'], ['terlambat', 'terkunci']));
                // Urutkan dari yang paling mendesak/terdekat
                $filteredCross = $filteredCross->sortBy('deadline')->values();
            } elseif ($filterStatus === 'belum_diisi') {
                $filteredCross = $allCrossSessions->filter(fn ($i) => $i['status'] === 'belum_diisi')
                    ->sortBy('deadline')->values();
            } elseif ($filterStatus === 'revisi') {
                $filteredCross = $allCrossSessions->filter(fn ($i) => $i['status'] === 'revisi')
                    ->sortByDesc('session_date')->values();
            } else {
                $filteredCross = $allCrossSessions->filter(fn ($i) => $i['status'] === $filterStatus)
                    ->sortByDesc('session_date')->values();
            }

            // Ambil pointLogs untuk sesi-sesi ini
            $sessionDatesList = $filteredCross->pluck('session_date')->map(fn ($d) => $d->toDateString())->unique()->values();
            $pointLogsCross = PointLog::with(['inputBy', 'student'])
                ->whereIn('session_date', $sessionDatesList)
                ->get()
                ->groupBy(fn ($p) => $p->session_date->toDateString() . '_' . $p->student_id);

            $sessions = $filteredCross->map(function ($item) use ($pointLogsCross) {
                $sch = $item['schedule'];
                $dStr = $item['session_date']->toDateString();
                $classStudentIds = $sch->class?->enrollments?->pluck('student_id') ?? collect();
                $existingPoints = $classStudentIds
                    ->map(fn ($id) => $pointLogsCross->get($dStr . '_' . $id)?->first())
                    ->filter()
                    ->values();
                $pointLocked = $classStudentIds->isNotEmpty()
                    && $existingPoints->count() === $classStudentIds->count();

                $item['point_locked'] = $pointLocked;
                $item['point_logs']   = $existingPoints;
                return $item;
            })->values();

            $substituteSessions = collect();
        } else {
            // Tab "Semua": Mengikuti tanggal terpilih ($dateStr)
            // 1) Ambil class_sessions pada tanggal ini
            $daySessions = ClassSession::with([
                    'class.enrollments' => fn ($q) => $q->where('status', 'Active'),
                    'class.enrollments.student',
                    'class.programPackage.program',
                    'class.level',
                    'schedule',
                    'journal',
                ])
                ->whereDate('session_date', $dateStr)
                ->whereIn('status', ['Scheduled', 'Completed'])
                ->orderBy('start_time')
                ->get();

            $classesWithSessions = $daySessions->pluck('class_id')->unique()->values()->all();

            // Cari juga kelas yang aslinya hari ini tapi sudah dipindah keluar dari tanggal ini
            $movedOutClassIds = ClassSession::whereDate('original_date', $dateStr)
                ->whereDate('session_date', '!=', $dateStr)
                ->where('status', '!=', 'Cancelled')
                ->pluck('class_id')
                ->unique()
                ->values()
                ->all();

            // 2) Pola jadwal mingguan untuk kelas yang BELUM punya sesi bertanggal hari ini dan tidak dipindah keluar
            $fallbackSchedules = ClassSchedule::with([
                    'class.enrollments' => fn ($q) => $q->where('status', 'Active'),
                    'class.enrollments.student',
                    'class.programPackage.program',
                    'class.level',
                ])
                ->where('day', $dayName)
                ->whereNull('effective_until')
                ->whereNotIn('class_id', array_merge($classesWithSessions, $movedOutClassIds))
                ->whereHas('class', fn ($q) => $q->whereIn('status', ['Open', 'Running']))
                ->orderBy('start_time')
                ->get();

            // Bentuk item jadwal dari daySessions
            $sessionItems = collect();
            foreach ($daySessions as $cs) {
                $class = $cs->class;
                if (! $class) continue;

                $sch = $cs->schedule ?? new ClassSchedule([
                    'id'         => $cs->class_schedule_id,
                    'class_id'   => $cs->class_id,
                    'day'        => $dayName,
                    'start_time' => $cs->start_time,
                    'end_time'   => $cs->end_time,
                    'room'       => $cs->room,
                ]);
                $sch->id = $cs->class_schedule_id ?? $sch->id;
                $sch->setRelation('class', $class);
                $sch->start_time = $cs->start_time;
                $sch->end_time   = $cs->end_time;
                if ($cs->room) {
                    $sch->room = $cs->room;
                }

                $sessionItems->push([
                    'schedule'     => $sch,
                    'classSession' => $cs,
                    'isFromSession'=> true,
                ]);
            }

            foreach ($fallbackSchedules as $fs) {
                $sessionItems->push([
                    'schedule'     => $fs,
                    'classSession' => null,
                    'isFromSession'=> false,
                ]);
            }

            $allScheduleIds = $sessionItems->pluck('schedule.id')->filter()->unique()->values()->all();

            $journals = TeachingJournal::with(['attendances.student', 'teacher', 'substituteTeacher'])
                ->whereDate('session_date', $dateStr)
                ->get();
            $journalsBySession = $journals->whereNotNull('class_session_id')->keyBy('class_session_id');
            $journalsBySchedule = $journals->keyBy('class_schedule_id');

            $unlocks = ScheduleJournalUnlock::whereIn('class_schedule_id', $allScheduleIds)
                ->whereDate('session_date', $dateStr)
                ->get()
                ->keyBy('class_schedule_id');

            // Riwayat poin pada tanggal ini (dikelompokkan per siswa)
            $pointLogs = PointLog::with(['inputBy', 'student'])
                ->whereDate('session_date', $dateStr)
                ->get()
                ->groupBy('student_id');

            $build = function ($item) use ($journalsBySession, $journalsBySchedule, $unlocks, $dateStr, $pointLogs, $date) {
                $schedule     = $item['schedule'];
                $classSession = $item['classSession'];
                $journal      = $classSession ? ($journalsBySession->get($classSession->id) ?? $journalsBySchedule->get($schedule->id)) : $journalsBySchedule->get($schedule->id);
                $unlockRecord = $unlocks->get($schedule->id);
                $deadline     = JournalStatusResolver::calculateDeadline($dateStr, $schedule->end_time);
                $isPast       = now()->gt($deadline);
                $isUnlocked   = ($unlockRecord && $unlockRecord->is_open);

                $status = JournalStatusResolver::resolveStatus($journal, $deadline, $isPast, $isUnlocked);

                // Hitung nomor pertemuan otomatis
                $autoMeetingNumber = TeachingJournal::where('class_id', $schedule->class_id)
                    ->where('class_status', 'Conducted')
                    ->count() + ($journal ? 0 : 1);

                // Status poin siswa kelas ini pada tanggal tersebut
                $classStudentIds = $schedule->class?->enrollments?->pluck('student_id') ?? collect();
                $existingPoints  = $classStudentIds
                    ->map(fn ($id) => $pointLogs->get($id)?->first())
                    ->filter()
                    ->values();
                $pointLocked = $classStudentIds->isNotEmpty()
                    && $existingPoints->count() === $classStudentIds->count();

                // Dipertahankan dari versi HEAD: [student_id => PointLog]
                $pointByStudent = $classStudentIds
                    ->mapWithKeys(fn ($id) => [$id => $pointLogs->get($id)?->first()])
                    ->filter();

                return [
                    'schedule'            => $schedule,
                    'class_session'       => $classSession,
                    'journal'             => $journal,
                    'status'              => $status,
                    'session_date'        => $date->copy(),
                    'deadline'            => $deadline,
                    'is_past_deadline'    => $isPast,
                    'is_unlocked'         => $isUnlocked,
                    'unlocked_until'      => $unlockRecord?->unlocked_until,
                    'unlock_record'       => $unlockRecord,
                    'auto_meeting_number' => max(1, $autoMeetingNumber),
                    'point_locked'        => $pointLocked,
                    'point_logs'          => $existingPoints,
                    'point_by_student'    => $pointByStudent,
                ];
            };

            [$ownItems, $otherItems] = $sessionItems->partition(function ($item) use ($teacher) {
                $cs = $item['classSession'];
                $tid = $cs ? ($cs->teacher_id ?? $cs->class?->teacher_id) : $item['schedule']->class?->teacher_id;
                return (int) $tid === $teacher->id;
            });

            $ownItems = $ownItems->filter(function ($item) {
                $cs = $item['classSession'];
                $cls = $cs ? $cs->class : $item['schedule']->class;
                return $cls && $cls->teacher_response === 'Accepted';
            });

            $sessions = $ownItems->map($build)->values();

            $substituteSessions = $otherItems
                ->map($build)
                ->filter(function ($i) use ($teacher) {
                    $j = $i['journal'];
                    return ! $j
                        || (int) $j->teacher_id === $teacher->id
                        || (int) $j->filled_by === $teacher->id;
                })
                ->values();
        }

        // Stat cards hari ini (sesi hari terpilih)
        // Jika sedang di tab filter bukan 'all', hitungan hari ini tetap dihitung dari tanggal terpilih agar stat cards stabil
        $todaySessions = JournalStatusResolver::getTeacherSessions($teacher, $dateStr);
        $totalSessionToday = $todaySessions->count();
        $filledCount       = $todaySessions->whereIn('status', [JournalStatusResolver::STATUS_SUDAH_DIISI, JournalStatusResolver::STATUS_DIKUNCI])->count();
        $unfilledCount     = $todaySessions->where('status', JournalStatusResolver::STATUS_BELUM_DIISI)->count();
        $lateCount         = $todaySessions->filter(fn ($i) => in_array($i['status'], [JournalStatusResolver::STATUS_TERLAMBAT, JournalStatusResolver::STATUS_TERKUNCI]))->count();
        $revisionCount     = $todaySessions->where('status', JournalStatusResolver::STATUS_REVISI)->count();

        $totalHadirHariIni = $todaySessions->sum(
            fn ($i) => $i['journal'] ? $i['journal']->attendances->where('status', 'Present')->count() : 0
        );

        $teacherNames = Teacher::orderBy('name')->pluck('name', 'id');

        return view('guru.attendance', compact(
            'sessions', 'substituteSessions', 'date', 'canFill', 'teacherNames',
            'totalSessionToday', 'filledCount', 'unfilledCount', 'lateCount', 'revisionCount', 'totalHadirHariIni',
            'filterStatus', 'statusCounts', 'isCrossDate'
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
            // Poin per siswa: point[student_id] = kuning | merah (kosong = tidak dapat poin)
            'point'                 => ['nullable', 'array'],
            'point.*'               => ['nullable', Rule::in(['kuning', 'merah'])],
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
        $dateStr      = $date->toDateString();

        // Cari session terkait pada tanggal ini
        $classSession = ClassSession::where('class_id', $classSchedule->class_id)
            ->whereDate('session_date', $dateStr)
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->first();

        // Jika tidak ada class_session pada tanggal ini, periksa apakah hari cocok dengan class_schedules
        if (! $classSession && $this->dayName($date) !== $classSchedule->day) {
            return back()->withInput()->with('error', "Tanggal tersebut bukan hari {$classSchedule->day}, sesuai jadwal kelas.");
        }

        $sessionEndTime = $classSession ? $classSession->end_time : $classSchedule->end_time;

        // Cek batas 24 jam setelah sesi selesai
        $sessionEnd = Carbon::parse($data['session_date'] . ' ' . $sessionEndTime);
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

        $alreadyFilledQuery = TeachingJournal::query();
        if ($classSession) {
            $alreadyFilledQuery->where(function ($q) use ($classSession, $classSchedule, $dateStr) {
                $q->where('class_session_id', $classSession->id)
                  ->orWhere(fn ($sub) => $sub->where('class_schedule_id', $classSchedule->id)->whereDate('session_date', $dateStr));
            });
        } else {
            $alreadyFilledQuery->where('class_schedule_id', $classSchedule->id)
                ->whereDate('session_date', $dateStr);
        }

        if ($alreadyFilledQuery->exists()) {
            return back()->withInput()->with('error', 'Jurnal untuk sesi ini sudah diisi.');
        }

        // Batas poin: maksimal 1 Kuning + 1 Merah per kelas per tanggal sesi (termasuk poin dari Admin)
        $classStudentIds = $class->enrollments()->where('status', 'Active')->pluck('student_id')->map(fn ($i) => (int) $i);
        $selectedPoints  = collect($data['point'] ?? [])
            ->filter()
            ->filter(fn ($t, $sid) => $classStudentIds->contains((int) $sid));

        if ($running && $selectedPoints->isNotEmpty()) {
            $logs    = app(PointService::class)->takenLogs($classStudentIds, $dateStr);
            $hasOwn  = $logs->pluck('student_id')->map(fn ($i) => (int) $i);

            foreach (['kuning', 'merah'] as $ptype) {
                $new = $selectedPoints->filter(fn ($t, $sid) => $t === $ptype && ! $hasOwn->contains((int) $sid));
                if ($new->isEmpty()) {
                    continue;
                }

                $label = ucfirst($ptype);
                $used  = $logs->where('type', $ptype);

                if ($new->count() > PointService::LIMIT_PER_TYPE) {
                    return back()->withInput()->with('error', 'Maksimal ' . PointService::LIMIT_PER_TYPE . " siswa untuk poin {$label} per sesi.");
                }

                if ($used->count() >= PointService::LIMIT_PER_TYPE) {
                    return back()->withInput()->with('error', "Poin {$label} di kelas ini pada " . $date->format('d/m/Y')
                        . ' sudah diberikan kepada ' . ($used->first()->student->name ?? 'siswa lain')
                        . ' (' . $used->first()->inputter_label . ').');
                }
            }
        }

        $pointResult = ['awarded' => 0, 'skipped' => []];

        try {
            $journal = DB::transaction(function () use ($data, $classSchedule, $classSession, $class, $me, $isSubstitute, $running, $isPast, $unlocked, &$pointResult) {
                $journal = TeachingJournal::create(
                    $this->journalAttributes($data, $running, $isSubstitute) + [
                        'filled_by'         => $me->id,                 // guru yang mengisi form
                        'class_id'          => $classSchedule->class_id,
                        'class_schedule_id' => $classSchedule->id,
                        'class_session_id'  => $classSession?->id,
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

                // Poin per siswa: hanya siswa yang dipilih guru. Yang sudah punya poin di tanggal ini dilewati.
                $selected = collect($data['point'] ?? [])->filter();
                if ($running && $selected->isNotEmpty() && $studentIds->isNotEmpty()) {
                    $validIds = $studentIds->map(fn ($i) => (int) $i)->all();

                    foreach (['kuning', 'merah'] as $type) {
                        $ids = $selected
                            ->filter(fn ($t, $sid) => $t === $type && in_array((int) $sid, $validIds, true))
                            ->keys()
                            ->map(fn ($k) => (int) $k);

                        if ($ids->isEmpty()) {
                            continue;
                        }

                        $r = app(PointService::class)->giveBulk(
                            $ids,
                            $data['session_date'],
                            $type,
                            Auth::id(),
                            'guru',
                            $journal->id,
                            $studentIds
                        );

                        $pointResult['awarded'] += $r['awarded'];
                        $pointResult['skipped']  = array_merge($pointResult['skipped'], $r['skipped']);
                    }
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

        } catch (\DomainException $e) {
            // batas poin terlewati (mis. admin menginput bersamaan): jurnal dibatalkan, tidak ada yang tersimpan
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'Jurnal gagal disimpan. Pastikan sesi ini belum pernah diisi, lalu coba lagi.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan jurnal: ' . $e->getMessage());
        }

        $msg = 'Jurnal berhasil disimpan dan dikirim ke Kurikulum untuk direview.';
        if (! empty($pointResult['skipped'])) {
            $msg .= ' Poin tidak ditambahkan untuk ' . implode(', ', $pointResult['skipped']) . ' karena sudah diinput di tanggal ini.';
        }

        return redirect()
            ->route('teacher.attendance', ['date' => $data['session_date']])
            ->with('success', $msg)
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

        // Poin tidak diubah saat revisi (sudah tercatat di riwayat, koreksi lewat Admin)
        $data         = $this->validated($request, $journal->schedule);
        $running      = $data['class_running'] === 'Berjalan';
        $isSubstitute = $data['is_substitute'] === 'Ya';

        try {
            DB::transaction(function () use ($journal, $class, $data, $running, $isSubstitute) {
                $journal->update($this->journalAttributes($data, $running, $isSubstitute) + [
                    'status'      => 'Pending',   // kembali masuk antrian review Kurikulum
                    'review_note' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);

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
        $curriculums = \App\Models\User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
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