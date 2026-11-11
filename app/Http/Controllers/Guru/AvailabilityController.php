<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\TeacherAvailability;
use App\Models\TeacherLeave;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    private const MESSAGES = [
        'days.required'          => 'Pilih minimal satu hari.',
        'days.min'               => 'Pilih minimal satu hari.',
        'day.required'           => 'Pilih hari.',
        'start_time.required'    => 'Jam mulai wajib diisi.',
        'start_time.date_format' => 'Format jam mulai tidak valid.',
        'end_time.required'      => 'Jam selesai wajib diisi.',
        'end_time.date_format'   => 'Format jam selesai tidak valid.',
        'end_time.after'         => 'Jam selesai harus setelah jam mulai.',
    ];

    private function teacher()
    {
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        return $teacher;
    }

    // ------------------------------------------------------------------ KETERSEDIAAN SAYA

    /** Tambah ketersediaan untuk satu atau beberapa hari sekaligus. */
    public function store(Request $request)
    {
        $teacher = $this->teacher();

        $data = $request->validate([
            'days'       => ['required', 'array', 'min:1'],
            'days.*'     => ['required', Rule::in(TeacherAvailability::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'note'       => ['nullable', 'string', 'max:255'],
        ], self::MESSAGES);

        [$created, $skipped] = TeacherAvailabilityService::addSlots(
            $teacher->id, $data['days'], $data['start_time'], $data['end_time'],
            $data['note'] ?? null, Auth::id()
        );

        if (!$created) {
            return back()->withInput()->with('error', 'Semua hari yang dipilih bertumpuk dengan ketersediaan Anda yang sudah ada.');
        }

        $message = count($created) . ' slot ketersediaan ditambahkan (' . implode(', ', $created) . ').';
        if ($skipped) {
            $message .= ' Dilewati karena bertumpuk: ' . implode(', ', $skipped) . '.';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, TeacherAvailability $availability)
    {
        $teacher = $this->teacher();
        abort_if((int) $availability->teacher_id !== (int) $teacher->id, 403);

        $data = $request->validate([
            'day'        => ['required', Rule::in(TeacherAvailability::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'note'       => ['nullable', 'string', 'max:255'],
        ], self::MESSAGES);

        if (TeacherAvailabilityService::overlaps($teacher->id, $data['day'], $data['start_time'], $data['end_time'], $availability->id)) {
            return back()->withInput()->with('error', 'Slot ini bertumpuk dengan ketersediaan lain di hari yang sama.');
        }

        $availability->update($data);

        return back()->with('success', 'Ketersediaan diperbarui.');
    }

    public function destroy(TeacherAvailability $availability)
    {
        abort_if((int) $availability->teacher_id !== (int) $this->teacher()->id, 403);

        $availability->delete();

        return back()->with('success', 'Ketersediaan dihapus.');
    }

    // ------------------------------------------------------------------ TERIMA / TOLAK KELAS

    /** Guru menerima atau menolak kelas yang diajukan kepadanya atau yang ditugaskan kepadanya. */
        /** Guru menerima atau menolak kelas: pengajuan, tawaran broadcast, atau penugasan langsung. */
    public function respondClass(Request $request, ClassModel $class)
    {
        $teacher = $this->teacher();

        $isOffer = \App\Models\ClassTeacherOffer::where('class_id', $class->id)
            ->where('teacher_id', $teacher->id)->where('status', 'Pending')->exists();

        $isBroadcast = ! $isOffer && $class->broadcast_to_all && $class->teacher_id === null;

        abort_if(! $isOffer && ! $isBroadcast && (int) $class->teacher_id !== (int) $teacher->id, 403);

        if (!in_array($class->status, ['Open', 'Running'], true)) {
            return back()->with('error', 'Kelas ini sudah tidak berjalan.');
        }

        // ---- 1. Kelas broadcast (terbuka untuk semua guru aktif) ----
        if ($isBroadcast) {
            $data = $request->validate([
                'decision' => ['required', Rule::in(['Accepted', 'Rejected'])],
            ]);

            if ($data['decision'] === 'Accepted') {
                return DB::transaction(function () use ($class, $teacher) {
                    $locked = ClassModel::where('id', $class->id)->lockForUpdate()->first();

                    if (!$locked || !$locked->broadcast_to_all || $locked->teacher_id !== null || $locked->teacher_response === 'Accepted') {
                        return back()->with('error', 'Maaf, tawaran kelas ini sudah diambil oleh guru lain.');
                    }

                    $locked->load('schedules');
                    $clash = TeacherAvailabilityService::conflictWith($teacher->id, $locked);
                    if ($clash) {
                        return back()->with('error', "Kelas ini bentrok dengan jadwal kelas {$clash} yang sudah Anda miliki.");
                    }

                    $locked->update([
                        'teacher_id'           => $teacher->id,
                        'broadcast_to_all'     => false,
                        'teacher_response'     => 'Accepted',
                        'teacher_responded_at' => now(),
                    ]);

                    // Notifikasi ke Admin
                    $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new AppNotification(
                            title: "Tawaran Kelas Diambil",
                            message: "Guru {$teacher->name} telah mengambil dan menerima tawaran kelas broadcast '{$locked->class_name}'.",
                            url: url('/admin/kelas'),
                            type: 'jadwal',
                            icon: 'fa-calendar-check'
                        ));
                    }

                    return back()->with('success', "Selamat! Anda berhasil mengambil dan mengunci jadwal kelas {$locked->class_name}.");
                });
            }

            return back()->with('info', "Anda telah melewati tawaran kelas {$class->class_name}.");
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['Accepted', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Alasan wajib diisi saat menolak jadwal kelas.',
        ]);

        // ---- 2. Pengajuan dari Kurikulum/admin (sistem pengajuan) ----
        if ($isOffer) {
            try {
                $data['decision'] === 'Accepted'
                    ? app(\App\Services\ClassOfferService::class)->accept($class, $teacher)
                    : app(\App\Services\ClassOfferService::class)->reject($class, $teacher, $data['note']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->with('error', collect($e->errors())->flatten()->first());
            }

            return back()->with('success', $data['decision'] === 'Accepted'
                ? "Kelas {$class->class_name} diterima."
                : "Pengajuan kelas {$class->class_name} ditolak.");
        }

        // ---- 3. Penugasan langsung ke guru ini ----
        if ($class->teacher_response !== 'Pending') {
            return back()->with('error', 'Kelas ini sudah Anda jawab. Hubungi Kurikulum/Admin jika perlu mengubahnya.');
        }

        $class->load('schedules');

        if ($data['decision'] === 'Accepted') {
            $clash = TeacherAvailabilityService::conflictWith($teacher->id, $class);

            if ($clash) {
                return back()->with('error', "Kelas ini bentrok dengan jadwal kelas {$clash} yang sudah Anda terima.");
            }

            $class->update([
                'teacher_response'     => 'Accepted',
                'teacher_responded_at' => now(),
            ]);

            $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
            foreach ($admins as $admin) {
                $admin->notify(new AppNotification(
                    title: "Jadwal Kelas Diterima",
                    message: "Guru {$teacher->name} telah menerima penugasan jadwal kelas {$class->class_name}.",
                    url: url('/admin/kelas'),
                    type: 'jadwal',
                    icon: 'fa-calendar-check'
                ));
            }

            return back()->with('success', "Kelas {$class->class_name} diterima.");
        }

        $class->update([
            'teacher_response'      => 'Rejected',
            'teacher_response_note' => $data['note'],
            'teacher_responded_at'  => now(),
        ]);

        $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
        foreach ($admins as $admin) {
            $admin->notify(new AppNotification(
                title: "Jadwal Kelas Ditolak",
                message: "Guru {$teacher->name} menolak jadwal kelas {$class->class_name}. Alasan: {$data['note']}.",
                url: url('/admin/kelas'),
                type: 'jadwal',
                icon: 'fa-calendar-xmark'
            ));
        }

        return back()->with('success', "Kelas {$class->class_name} ditolak. Admin telah menerima pemberitahuan beserta alasan Anda.");
    }

    // ------------------------------------------------------------------ GURU PENGGANTI

    /**
     * Guru pengganti menerima / menolak permintaan menggantikan kelas guru lain.
     * Diterima  -> menunggu persetujuan Kurikulum.
     * Ditolak   -> pengajuan otomatis berstatus Ditolak (pengaju bisa mengajukan ulang).
     */
    public function respondReplacement(Request $request, TeacherLeave $leave)
    {
        $teacher = $this->teacher();

        abort_if((int) $leave->replacement_teacher_id !== (int) $teacher->id, 403);

        if ($leave->status !== 'Pending' || $leave->replacement_response !== 'Pending') {
            return back()->with('error', 'Permintaan ini sudah tidak bisa dijawab.');
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['Accepted', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat menolak.',
        ]);

        $leave->load(['classSchedule.class', 'teacher']);

        $schedule  = $leave->classSchedule;
        $className = optional(optional($schedule)->class)->class_name ?? 'kelas';

        if ($data['decision'] === 'Accepted') {
            if ($schedule) {
                $clash = TeacherAvailabilityService::busyAt(
                    $teacher->id,
                    $schedule->day,
                    $schedule->start_time,
                    $schedule->end_time
                );

                if ($clash) {
                    return back()->with('error', "Anda bentrok dengan kelas {$clash} di jam tersebut.");
                }
            }

            $curriculumAccepted = ($leave->curriculum_response === 'Accepted');
            $finalStatus = $curriculumAccepted ? 'Approved' : 'Pending';

            $leave->update([
                'replacement_response'      => 'Accepted',
                'replacement_response_note' => null,
                'replacement_responded_at'  => now(),
                'status'                    => $finalStatus,
            ]);

            // Notifikasi ke guru pemohon
            if ($leave->teacher?->user) {
                $msg = $curriculumAccepted
                    ? "Guru pengganti {$teacher->name} telah menerima permintaan menggantikan {$className}. Seluruh persetujuan selesai (Approved)."
                    : "Guru pengganti {$teacher->name} telah bersedia menggantikan {$className}. Menunggu persetujuan dari Kurikulum.";

                $leave->teacher->user->notify(new AppNotification(
                    title: "Guru Pengganti Bersedia: {$className}",
                    message: $msg,
                    url: url('/guru/cuti'),
                    type: 'cuti',
                    icon: 'fa-user-check'
                ));
            }

            return back()->with('success', "Anda bersedia menggantikan {$className}." . ($curriculumAccepted ? " Pengajuan telah disetujui penuh." : " Menunggu konfirmasi Kurikulum."));
        }

        $leave->update([
            'replacement_response'      => 'Rejected',
            'replacement_response_note' => $data['note'],
            'replacement_responded_at'  => now(),
            'status'                    => 'Rejected',
            'review_note'               => "Guru pengganti {$teacher->name} menolak: {$data['note']}",
        ]);

        // Notifikasi penolakan ke guru pemohon
        if ($leave->teacher?->user) {
            $leave->teacher->user->notify(new AppNotification(
                title: "Permintaan Pengganti Ditolak: {$className}",
                message: "Guru {$teacher->name} tidak dapat menggantikan kelas {$className}. Alasan: {$data['note']}",
                url: url('/guru/cuti'),
                type: 'cuti',
                icon: 'fa-user-xmark'
            ));
        }

        return back()->with('success', 'Permintaan menggantikan ditolak. Guru pemohon telah diberi notifikasi.');
    }
}