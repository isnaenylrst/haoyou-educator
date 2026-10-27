<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\HandlesStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\PrivateBooking;
use App\Models\Program;
use App\Models\ScheduleRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAvailableSlot;
use App\Models\TeacherLeave;
use App\Models\User;
use App\Notifications\StudentNotice;
use App\Services\ClassCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    use HandlesStudent;

    /** Slot privat ditampilkan untuk 7 hari ke depan. */
    private const SLOT_DAYS = 7;

    /*
    |--------------------------------------------------------------------------
    | HALAMAN BOOKING
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $student = $this->student();

        // ---- PRIVAT ----
        $privateEnrollment = $this->privateEnrollment($student);
        $sisaPertemuan     = $privateEnrollment ? $this->remainingMeetings($privateEnrollment) : 0;

        $slotPrivat = $privateEnrollment
            ? $this->availableSlots()
            : [];

        $jadwalPrivatSaya = PrivateBooking::with('teacher')
            ->where('student_id', $student->id)
            ->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        // ---- REGULER ----
        $kelasReguler = $this->kelasRegulerTersedia($student);

        // ---- DATA UNTUK FORM REQUEST ----
        $programs = Program::orderBy('program_name')->get(['id', 'program_name']);
        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        return view('Siswa.booking', compact(
            'student',
            'privateEnrollment',
            'sisaPertemuan',
            'slotPrivat',
            'jadwalPrivatSaya',
            'kelasReguler',
            'programs',
            'teachers'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVAT — booking slot
    |--------------------------------------------------------------------------
    */

    public function storePrivate(Request $request)
    {
        $student = $this->student();

        $request->validate([
            'slot'          => ['required', 'string'],
            'delivery_mode' => ['nullable', 'in:Online,Offline'],
        ], [
            'slot.required' => 'Pilih salah satu slot Laoshi terlebih dahulu.',
        ]);

        $enrollment = $this->privateEnrollment($student);

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'slot' => 'Kamu belum memiliki paket kelas privat yang aktif. Hubungi admin.',
            ]);
        }

        if ($this->remainingMeetings($enrollment) <= 0) {
            throw ValidationException::withMessages([
                'slot' => 'Jatah pertemuan paket privat kamu sudah habis.',
            ]);
        }

        $booking = DB::transaction(function () use ($request, $student, $enrollment) {
            $slot = $this->resolveSlot($request->input('slot'));

            $mode = $slot['delivery_mode'] === 'Both'
                ? ($request->input('delivery_mode') ?: 'Online')
                : $slot['delivery_mode'];

            return PrivateBooking::create([
                'student_id'                => $student->id,
                'enrollment_id'             => $enrollment->id,
                'teacher_id'                => $slot['teacher_id'],
                'teacher_available_slot_id' => $slot['slot_id'],
                'session_date'              => $slot['date']->toDateString(),
                'start_time'                => $slot['start'],
                'end_time'                  => $slot['end'],
                'delivery_mode'             => $mode,
                'status'                    => 'Scheduled',
            ]);
        });

        $booking->load('teacher');

        $this->notifyStudent(
            'Booking Kelas Privat Berhasil',
            'Kelas privat ' . $this->tanggalId($booking->session_date) . ' pukul '
                . substr($booking->start_time, 0, 5) . ' bersama ' . $booking->teacher->name . '.',
            route('booking.index')
        );

        $this->notifyAdmins(
            'Booking Privat Baru',
            $student->name . ' membooking kelas privat ' . $this->tanggalId($booking->session_date)
                . ' ' . substr($booking->start_time, 0, 5) . ' (' . $booking->teacher->name . ').'
        );

        return redirect()->route('booking.index')
            ->with('success', 'Booking kelas privat berhasil.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVAT — reschedule (minimal H-1 / 24 jam)
    |--------------------------------------------------------------------------
    */

    public function reschedulePrivate(Request $request, PrivateBooking $booking)
    {
        $student = $this->student();

        abort_unless($booking->student_id === $student->id, 404);

        $request->validate([
            'slot' => ['required', 'string'],
        ], [
            'slot.required' => 'Pilih slot baru untuk reschedule.',
        ]);

        if (! $booking->canReschedule()) {
            throw ValidationException::withMessages([
                'slot' => 'Reschedule hanya bisa dilakukan minimal H-1 (24 jam) sebelum kelas dimulai.',
            ]);
        }

        $booking = DB::transaction(function () use ($request, $booking) {
            $slot = $this->resolveSlot($request->input('slot'));

            $old = $booking->session_date->copy();

            $booking->update([
                'teacher_id'                => $slot['teacher_id'],
                'teacher_available_slot_id' => $slot['slot_id'],
                'session_date'              => $slot['date']->toDateString(),
                'start_time'                => $slot['start'],
                'end_time'                  => $slot['end'],
                'rescheduled_from_date'     => $old->toDateString(),
                'rescheduled_at'            => now(),
                'reschedule_count'          => $booking->reschedule_count + 1,
            ]);

            return $booking;
        });

        $booking->load('teacher');

        $this->notifyStudent(
            'Jadwal Privat Dipindahkan',
            'Kelas privat dipindah ke ' . $this->tanggalId($booking->session_date) . ' pukul '
                . substr($booking->start_time, 0, 5) . ' bersama ' . $booking->teacher->name . '.',
            route('booking.index')
        );

        $this->notifyAdmins(
            'Reschedule Kelas Privat',
            $this->student()->name . ' memindahkan kelas privat ke '
                . $this->tanggalId($booking->session_date) . ' ' . substr($booking->start_time, 0, 5) . '.'
        );

        return redirect()->route('booking.index')
            ->with('success', 'Jadwal kelas privat berhasil dipindahkan.');
    }

    /*
    |--------------------------------------------------------------------------
    | REGULER — daftar ke kelas yang di-plot admin
    |--------------------------------------------------------------------------
    | Siswa memilih kelas untuk paket reguler yang berstatus "Waiting Class"
    | (sama dengan assignClass di admin: class_id diisi, status -> Active).
    */

    public function daftarReguler(ClassModel $class)
    {
        $student = $this->student();

        abort_unless(in_array($class->status, ['Open', 'Running']), 404);

        DB::transaction(function () use ($class, $student) {
            // kunci baris kelas agar kapasitas tidak ter-lompati saat dua siswa daftar bersamaan
            $lockedClass = ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();

            $enrollment = ClassEnrollment::where('student_id', $student->id)
                ->where('status', 'Waiting Class')
                ->whereNull('class_id')
                ->where('program_package_id', $lockedClass->program_package_id)
                ->lockForUpdate()
                ->first();

            if (! $enrollment) {
                throw ValidationException::withMessages([
                    'class_id' => 'Kamu belum punya paket yang sesuai dengan kelas ini. '
                        . 'Hubungi admin atau kirim Request Jadwal Reguler.',
                ]);
            }

            app(ClassCapacityService::class)->assertHasAvailableSlot($lockedClass);

            $enrollment->update([
                'class_id' => $lockedClass->id,
                'status'   => 'Active',
            ]);
        });

        $this->notifyStudent(
            'Berhasil Masuk Kelas',
            'Kamu terdaftar di kelas ' . $class->class_name . '.',
            route('kelassaya.index')
        );

        $this->notifyAdmins(
            'Siswa Memilih Kelas Reguler',
            $student->name . ' masuk ke kelas ' . $class->class_name . '.'
        );

        return redirect()->route('kelassaya.index')
            ->with('success', 'Kamu berhasil terdaftar di kelas ' . $class->class_name . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST JADWAL
    |--------------------------------------------------------------------------
    */

    public function requestPrivate(Request $request)
    {
        $student = $this->student();

        $data = $request->validate([
            'preferred_teacher_id' => ['nullable', 'exists:teachers,id'],
            'preferred_date'       => ['required', 'date', 'after:today'],
            'preferred_start_time' => ['required', 'date_format:H:i'],
            'delivery_mode'        => ['required', 'in:Online,Offline'],
            'note'                 => ['nullable', 'string', 'max:1000'],
        ], [
            'preferred_date.after' => 'Tanggal request harus setelah hari ini.',
        ]);

        ScheduleRequest::create($data + [
            'student_id'   => $student->id,
            'request_type' => 'Private',
            'status'       => 'Pending',
        ]);

        $this->notifyAdmins(
            'Request Jadwal Privat',
            $student->name . ' meminta jadwal privat ' . Carbon::parse($data['preferred_date'])->format('d/m/Y')
                . ' ' . $data['preferred_start_time'] . '.'
        );

        return redirect()->route('booking.index')
            ->with('success', 'Request jadwal privat terkirim. Admin akan mengonfirmasi ketersediaan Laoshi.');
    }

    public function requestReguler(Request $request)
    {
        $student = $this->student();

        $data = $request->validate([
            'program_id'           => ['required', 'exists:programs,id'],
            'preferred_days'       => ['required', 'string', 'max:100'],
            'preferred_start_time' => ['required', 'date_format:H:i'],
            'note'                 => ['nullable', 'string', 'max:1000'],
        ]);

        ScheduleRequest::create($data + [
            'student_id'   => $student->id,
            'request_type' => 'Regular',
            'status'       => 'Pending',
        ]);

        $this->notifyAdmins(
            'Request Jadwal Reguler',
            $student->name . ' mengusulkan jadwal reguler ' . $data['preferred_days']
                . ' pukul ' . $data['preferred_start_time'] . '.'
        );

        return redirect()->route('booking.index')
            ->with('success', 'Request jadwal reguler terkirim. Admin akan menghubungi lewat WhatsApp/Email.');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    /** Enrollment paket privat yang masih aktif. */
    private function privateEnrollment(Student $student): ?ClassEnrollment
    {
        return ClassEnrollment::with('privatePackage')
            ->where('student_id', $student->id)
            ->whereNotNull('private_package_id')
            ->where('status', 'Active')
            ->latest('enrollment_date')
            ->first();
    }

    /** Sisa pertemuan = total_meetings paket - booking yang tidak dibatalkan. */
    private function remainingMeetings(ClassEnrollment $enrollment): int
    {
        $used = PrivateBooking::where('enrollment_id', $enrollment->id)
            ->where('status', '!=', 'Cancelled')
            ->count();

        return max(($enrollment->privatePackage?->total_meetings ?? 0) - $used, 0);
    }

    /** Kelas reguler yang bisa dipilih siswa (sesuai paket Waiting Class miliknya). */
    private function kelasRegulerTersedia(Student $student): array
    {
        $packageIds = ClassEnrollment::where('student_id', $student->id)
            ->where('status', 'Waiting Class')
            ->whereNull('class_id')
            ->pluck('program_package_id')
            ->filter();

        if ($packageIds->isEmpty()) {
            return [];
        }

        $capacity = app(ClassCapacityService::class);

        return ClassModel::with(['schedules', 'teacher', 'programPackage'])
            ->whereIn('program_package_id', $packageIds)
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get()
            ->map(function ($kelas) use ($capacity) {
                $jadwal = $kelas->schedules
                    ->map(fn ($s) => $s->day . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))
                    ->implode(', ');

                $sisa = $capacity->remainingSlots($kelas);

                return [
                    'id'     => $kelas->id,
                    'judul'  => $kelas->class_name
                        . ($kelas->teacher ? ' — ' . $kelas->teacher->name : ''),
                    'jadwal' => ($jadwal ?: 'Jadwal belum ditentukan')
                        . ' · ' . $kelas->delivery_mode . ' · sisa ' . $sisa . ' kursi',
                    'penuh'  => $sisa <= 0,
                ];
            })
            ->all();
    }

    /**
     * Slot kosong Laoshi untuk 7 hari ke depan.
     * Slot "penuh" bila: sudah dibooking, Laoshi cuti (Approved), atau
     * bentrok dengan jadwal kelas reguler Laoshi tsb.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableSlots(): array
    {
        $from = today();
        $to   = today()->addDays(self::SLOT_DAYS - 1);

        $slots = TeacherAvailableSlot::with('teacher')
            ->where('is_active', true)
            ->whereHas('teacher', fn ($q) => $q->where('status', 'Active'))
            ->get();

        if ($slots->isEmpty()) {
            return [];
        }

        $booked = PrivateBooking::where('status', '!=', 'Cancelled')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->get(['teacher_id', 'session_date', 'start_time'])
            ->map(fn ($b) => $b->teacher_id . '|' . $b->session_date->toDateString() . '|' . substr($b->start_time, 0, 5))
            ->flip();

        $leaves = TeacherLeave::where('status', 'Approved')
            ->whereBetween('leave_date', [$from->toDateString(), $to->toDateString()])
            ->get(['teacher_id', 'leave_date'])
            ->map(fn ($l) => $l->teacher_id . '|' . Carbon::parse($l->leave_date)->toDateString())
            ->flip();

        // jadwal reguler Laoshi: teacher_id => [[day, start, end], ...]
        $teaching = ClassSchedule::with('class:id,teacher_id,status')
            ->whereHas('class', fn ($q) => $q->whereIn('status', ['Open', 'Running'])->whereNotNull('teacher_id'))
            ->get()
            ->groupBy(fn ($s) => $s->class->teacher_id);

        $result = [];

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $hari = $this->hariId($d);

            foreach ($slots->where('day', $hari) as $slot) {
                $start = substr($slot->start_time, 0, 5);
                $end   = substr($slot->end_time, 0, 5);
                $at    = Carbon::parse($d->format('Y-m-d') . ' ' . $start);

                if ($at->lte(now())) {
                    continue; // slot yang sudah lewat tidak ditampilkan
                }

                $penuh = isset($booked[$slot->teacher_id . '|' . $d->toDateString() . '|' . $start])
                    || isset($leaves[$slot->teacher_id . '|' . $d->toDateString()])
                    || $this->bentrokKelasReguler($teaching->get($slot->teacher_id), $hari, $start, $end);

                $result[] = [
                    'key'           => $slot->id . '|' . $d->toDateString(),
                    'slot_id'       => $slot->id,
                    'teacher_id'    => $slot->teacher_id,
                    'date'          => $d->copy(),
                    'start'         => $start,
                    'end'           => $end,
                    'delivery_mode' => $slot->delivery_mode,
                    'status'        => $penuh ? 'penuh' : 'tersedia',
                    'label'         => $this->tanggalId($d) . ' · ' . $start . '–' . $end
                        . ' · ' . $slot->teacher->name,
                ];
            }
        }

        usort($result, fn ($a, $b) => [$a['date'], $a['start']] <=> [$b['date'], $b['start']]);

        return $result;
    }

    private function bentrokKelasReguler($schedules, string $hari, string $start, string $end): bool
    {
        if (! $schedules) {
            return false;
        }

        foreach ($schedules as $s) {
            if ($s->day === $hari
                && substr($s->start_time, 0, 5) < $end
                && substr($s->end_time, 0, 5) > $start) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi slot terpilih ("slotId|YYYY-MM-DD") dan pastikan masih tersedia.
     * Dipanggil DI DALAM transaction; baris booking yang bentrok dikunci.
     */
    private function resolveSlot(string $key): array
    {
        $found = collect($this->availableSlots())->firstWhere('key', $key);

        if (! $found || $found['status'] !== 'tersedia') {
            throw ValidationException::withMessages([
                'slot' => 'Slot tersebut sudah tidak tersedia. Silakan pilih slot lain.',
            ]);
        }

        // kunci ulang & cek bentrok di level database
        $taken = PrivateBooking::where('teacher_id', $found['teacher_id'])
            ->whereDate('session_date', $found['date']->toDateString())
            ->whereTime('start_time', $found['start'] . ':00')
            ->where('status', '!=', 'Cancelled')
            ->lockForUpdate()
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'slot' => 'Slot tersebut baru saja diambil siswa lain. Silakan pilih slot lain.',
            ]);
        }

        return $found;
    }

    private function notifyStudent(string $title, string $message, ?string $url = null): void
    {
        Auth::user()->notify(new StudentNotice($title, $message, $url));
    }

    private function notifyAdmins(string $title, string $message): void
    {
        $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))
            ->where('status', 'Active')
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new StudentNotice($title, $message));
        }
    }
}
