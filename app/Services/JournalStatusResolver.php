<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ScheduleJournalUnlock;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JournalStatusResolver
{
    public const STATUS_BELUM_DIISI = 'belum_diisi';
    public const STATUS_TERLAMBAT   = 'terlambat';
    public const STATUS_TERKUNCI    = 'terkunci';
    public const STATUS_SUDAH_DIISI = 'sudah_diisi';
    public const STATUS_DIKUNCI     = 'dikunci';
    public const STATUS_REVISI      = 'revisi';

    /**
     * Tentukan status jurnal suatu sesi kelas.
     * Mengembalikan salah satu dari:
     * - belum_diisi
     * - terlambat (lewat 24 jam tapi form dibuka kembali/unlocked oleh kurikulum)
     * - terkunci (lewat 24 jam dan form terkunci, perlu permohonan buka kunci)
     * - sudah_diisi (menunggu review kurikulum)
     * - dikunci (sudah direview dan dikunci kurikulum)
     * - revisi (dikembalikan kurikulum untuk direvisi)
     */
    public static function resolveStatus(
        ?TeachingJournal $journal,
        Carbon $deadline,
        bool $isPastDeadline,
        bool $isUnlocked
    ): string {
        if ($journal) {
            return match ($journal->status) {
                'Reviewed' => self::STATUS_DIKUNCI,
                'Revision' => self::STATUS_REVISI,
                default    => self::STATUS_SUDAH_DIISI,
            };
        }

        if ($isPastDeadline) {
            return $isUnlocked ? self::STATUS_TERLAMBAT : self::STATUS_TERKUNCI;
        }

        return self::STATUS_BELUM_DIISI;
    }

    /**
     * Label human-readable untuk status.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            self::STATUS_BELUM_DIISI => 'Belum Diisi',
            self::STATUS_TERLAMBAT   => 'Terlambat (Buka Kunci)',
            self::STATUS_TERKUNCI    => 'Terkunci',
            self::STATUS_SUDAH_DIISI => 'Sudah Diisi',
            self::STATUS_DIKUNCI     => 'Dikunci Kurikulum',
            self::STATUS_REVISI      => 'Perlu Revisi',
            default                  => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Badge CSS class untuk status.
     */
    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_BELUM_DIISI => 'bg-warning text-dark',
            self::STATUS_TERLAMBAT, self::STATUS_TERKUNCI, self::STATUS_REVISI => 'bg-danger',
            self::STATUS_SUDAH_DIISI => 'bg-info text-dark',
            self::STATUS_DIKUNCI     => 'bg-success',
            default                  => 'bg-secondary',
        };
    }

    /**
     * Hitung deadline 24 jam dari jam selesai sesi.
     */
    public static function calculateDeadline(string $dateStr, string $endTime): Carbon
    {
        $sessionEnd = Carbon::parse($dateStr . ' ' . substr($endTime, 0, 5));
        return $sessionEnd->copy()->addHours(24);
    }

    /**
     * Ambil semua sesi (class_sessions nyata + fallback schedule mingguan) untuk seorang guru lintas tanggal atau per tanggal tertentu.
     * Mengembalikan collection sesi yang siap ditampilkan di view.
     */
    public static function getTeacherSessions(Teacher $teacher, ?string $dateFilter = null): Collection
    {
        // 1. Ambil semua kelas aktif guru yang diterima
        $myClasses = ClassModel::with([
                'enrollments' => fn ($q) => $q->where('status', 'Active'),
                'enrollments.student',
                'programPackage.program',
                'level',
                'schedules' => fn ($q) => $q->whereNull('effective_until'),
            ])
            ->where('teacher_id', $teacher->id)
            ->where('teacher_response', 'Accepted')
            ->whereIn('status', ['Open', 'Running'])
            ->get();

        $myClassIds = $myClasses->pluck('id')->all();

        if (empty($myClassIds)) {
            return collect();
        }

        // 2. Query class_sessions nyata
        $sessionQuery = ClassSession::with([
                'class.enrollments' => fn ($q) => $q->where('status', 'Active'),
                'class.enrollments.student',
                'class.programPackage.program',
                'class.level',
                'schedule',
                'journal.attendances.student',
                'journal.teacher',
                'journal.substituteTeacher',
            ])
            ->whereIn('class_id', $myClassIds)
            ->whereIn('status', ['Scheduled', 'Completed']);

        if ($dateFilter) {
            $sessionQuery->whereDate('session_date', $dateFilter);
        }

        $classSessions = $sessionQuery->orderBy('session_date', 'desc')->orderBy('start_time')->get();

        // Cari juga sesi yang dipindah keluar dari tanggal filter
        $movedOutClassDatePairs = ClassSession::whereIn('class_id', $myClassIds)
            ->where('status', '!=', 'Cancelled')
            ->whereNotNull('original_date')
            ->whereColumn('session_date', '!=', 'original_date')
            ->get(['class_id', 'original_date'])
            ->map(fn ($item) => $item->class_id . '_' . Carbon::parse($item->original_date)->toDateString())
            ->flip();

        $existingClassDates = $classSessions->map(fn ($s) => $s->class_id . '_' . $s->session_date->toDateString())->flip();

        // 3. Fallback schedules jika ada dateFilter
        $fallbackSchedules = collect();
        if ($dateFilter) {
            $dateObj = Carbon::parse($dateFilter);
            $dayName = match ($dateObj->format('l')) {
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                default => 'Senin',
            };

            foreach ($myClasses as $cls) {
                $classStartDate = $cls->start_date ? Carbon::parse($cls->start_date)->startOfDay() : null;
                $classEndDate = $cls->end_date ? Carbon::parse($cls->end_date)->endOfDay() : null;

                if ($classStartDate && $dateObj->lt($classStartDate)) continue;
                if ($classEndDate && $dateObj->gt($classEndDate)) continue;

                $classDateKey = $cls->id . '_' . $dateFilter;
                if ($existingClassDates->has($classDateKey) || $movedOutClassDatePairs->has($classDateKey)) {
                    continue;
                }

                foreach ($cls->schedules as $sch) {
                    if ($sch->day === $dayName) {
                        $fallbackSchedules->push([
                            'class'    => $cls,
                            'schedule' => $sch,
                            'date'     => $dateObj->copy(),
                        ]);
                    }
                }
            }
        }

        // 4. Load all journals & unlocks
        $allScheduleIds = $classSessions->pluck('class_schedule_id')
            ->merge($fallbackSchedules->pluck('schedule.id'))
            ->filter()->unique()->values()->all();

        $unlockQuery = ScheduleJournalUnlock::whereIn('class_schedule_id', $allScheduleIds);
        if ($dateFilter) {
            $unlockQuery->whereDate('session_date', $dateFilter);
        }
        $unlocks = $unlockQuery->get();

        $journalQuery = TeachingJournal::with(['attendances.student', 'teacher', 'substituteTeacher'])
            ->whereIn('class_id', $myClassIds);
        if ($dateFilter) {
            $journalQuery->whereDate('session_date', $dateFilter);
        }
        $journals = $journalQuery->get();

        $journalsBySession = $journals->whereNotNull('class_session_id')->keyBy('class_session_id');

        $result = collect();

        // Map class_sessions nyata
        foreach ($classSessions as $cs) {
            $class = $cs->class;
            if (!$class) continue;

            $dateStr = $cs->session_date->toDateString();
            $dayName = match ($cs->session_date->format('l')) {
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                default => 'Senin',
            };

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

            $journal = $journalsBySession->get($cs->id)
                ?? $journals->first(fn ($j) => (int) $j->class_id === (int) $cs->class_id && $j->session_date->format('Y-m-d') === $dateStr);

            $unlockRecord = $unlocks->first(fn ($u) => (int) $u->class_schedule_id === (int) $sch->id && $u->session_date->format('Y-m-d') === $dateStr);

            $deadline = self::calculateDeadline($dateStr, $sch->end_time);
            $isPast = now()->gt($deadline);
            $isUnlocked = $unlockRecord ? $unlockRecord->is_open : false;

            $status = self::resolveStatus($journal, $deadline, $isPast, $isUnlocked);

            // Hitung nomor pertemuan otomatis
            $autoMeetingNumber = TeachingJournal::where('class_id', $sch->class_id)
                ->where('class_status', 'Conducted')
                ->count() + ($journal ? 0 : 1);

            $result->push([
                'schedule'            => $sch,
                'class_session'       => $cs,
                'journal'             => $journal,
                'status'              => $status,
                'session_date'        => $cs->session_date->copy(),
                'deadline'            => $deadline,
                'is_past_deadline'    => $isPast,
                'is_unlocked'         => $isUnlocked,
                'unlocked_until'      => $unlockRecord?->unlocked_until,
                'unlock_record'       => $unlockRecord,
                'is_from_session'     => true,
                'auto_meeting_number' => max(1, $autoMeetingNumber),
                'point_locked'        => false,
                'point_logs'          => collect(),
            ]);
        }

        // Map fallback schedules
        foreach ($fallbackSchedules as $fb) {
            $class = $fb['class'];
            $sch = $fb['schedule'];
            $dateObj = $fb['date'];
            $dateStr = $dateObj->toDateString();

            $journal = $journals->first(fn ($j) => (int) $j->class_schedule_id === (int) $sch->id && $j->session_date->format('Y-m-d') === $dateStr);
            $unlockRecord = $unlocks->first(fn ($u) => (int) $u->class_schedule_id === (int) $sch->id && $u->session_date->format('Y-m-d') === $dateStr);

            $deadline = self::calculateDeadline($dateStr, $sch->end_time);
            $isPast = now()->gt($deadline);
            $isUnlocked = $unlockRecord ? $unlockRecord->is_open : false;

            $status = self::resolveStatus($journal, $deadline, $isPast, $isUnlocked);

            $autoMeetingNumber = TeachingJournal::where('class_id', $sch->class_id)
                ->where('class_status', 'Conducted')
                ->count() + ($journal ? 0 : 1);

            $result->push([
                'schedule'            => $sch,
                'class_session'       => null,
                'journal'             => $journal,
                'status'              => $status,
                'session_date'        => $dateObj,
                'deadline'            => $deadline,
                'is_past_deadline'    => $isPast,
                'is_unlocked'         => $isUnlocked,
                'unlocked_until'      => $unlockRecord?->unlocked_until,
                'unlock_record'       => $unlockRecord,
                'is_from_session'     => false,
                'auto_meeting_number' => max(1, $autoMeetingNumber),
                'point_locked'        => false,
                'point_logs'          => collect(),
            ]);
        }

        return $result;
    }

    /**
     * Hitung total per status jurnal untuk seorang guru lintas tanggal (semua waktu).
     */
    public static function countPerStatus(Teacher $teacher): array
    {
        $allSessions = self::getTeacherSessions($teacher);

        $unfilled = $allSessions->where('status', self::STATUS_BELUM_DIISI)->count();
        $lateOrLocked = $allSessions->filter(fn ($i) => in_array($i['status'], [self::STATUS_TERLAMBAT, self::STATUS_TERKUNCI]))->count();
        $filled = $allSessions->where('status', self::STATUS_SUDAH_DIISI)->count();
        $lockedByCurriculum = $allSessions->where('status', self::STATUS_DIKUNCI)->count();
        $revision = $allSessions->where('status', self::STATUS_REVISI)->count();

        return [
            'unfilled'              => $unfilled,
            'late_or_locked'        => $lateOrLocked,
            'filled'                => $filled,
            'locked_by_curriculum'  => $lockedByCurriculum,
            'revision'              => $revision,
            'total'                 => $allSessions->count(),
        ];
    }

    /**
     * Dapatkan sesi yang paling mendesak untuk diisi oleh guru.
     * Prioritas:
     * 1. Perlu Revisi
     * 2. Belum Diisi (deadline paling dekat dengan saat ini)
     * 3. Terlambat / Terkunci
     */
    public static function getMostUrgentSession(Teacher $teacher): ?array
    {
        $allSessions = self::getTeacherSessions($teacher);

        // 1. Perlu Revisi
        $revision = $allSessions->where('status', self::STATUS_REVISI)
            ->sortBy('session_date')
            ->first();
        if ($revision) {
            return $revision;
        }

        // 2. Belum Diisi (terdekat deadline)
        $unfilled = $allSessions->where('status', self::STATUS_BELUM_DIISI)
            ->sortBy('deadline')
            ->first();
        if ($unfilled) {
            return $unfilled;
        }

        // 3. Terlambat / Terkunci
        $late = $allSessions->filter(fn ($i) => in_array($i['status'], [self::STATUS_TERLAMBAT, self::STATUS_TERKUNCI]))
            ->sortByDesc('session_date')
            ->first();

        return $late;
    }
}
