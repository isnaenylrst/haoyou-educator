<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\TeacherAvailability;
use App\Models\TeacherLeave;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AvailabilityController extends Controller
{
    private function teacher()
    {
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        return $teacher;
    }

    private function rules(): array
    {
        return [
            'day'        => ['required', Rule::in(TeacherAvailability::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'note'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function store(Request $request)
    {
        $teacher = $this->teacher();
        $data = $request->validate($this->rules());

        if (TeacherAvailabilityService::overlaps($teacher->id, $data['day'], $data['start_time'], $data['end_time'])) {
            return back()->withInput()->with('error', 'Slot ini bertumpuk dengan ketersediaan lain di hari yang sama.');
        }

        TeacherAvailability::create($data + [
            'teacher_id' => $teacher->id,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Ketersediaan ditambahkan.');
    }

    public function update(Request $request, TeacherAvailability $availability)
    {
        $teacher = $this->teacher();
        abort_if((int) $availability->teacher_id !== (int) $teacher->id, 403);

        $data = $request->validate($this->rules());

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

    /** Guru menerima atau menolak kelas yang diberikan kepadanya. */
    public function respondClass(Request $request, ClassModel $class)
    {
        $teacher = $this->teacher();

        $isOffer = \App\Models\ClassTeacherOffer::where('class_id', $class->id)
            ->where('teacher_id', $teacher->id)->where('status', 'Pending')->exists();

        abort_if(! $isOffer && (int) $class->teacher_id !== (int) $teacher->id, 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['Accepted', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat menolak kelas.',
        ]);

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

        $class->load('schedules');

        if ($data['decision'] === 'Accepted') {
            $clash = TeacherAvailabilityService::conflictWith($teacher->id, $class);

            if ($clash) {
                return back()->with('error', "Kelas ini bentrok dengan jadwal kelas {$clash} yang sudah Anda terima.");
            }
        }

        $class->update([
            'teacher_response'      => $data['decision'],
            'teacher_response_note' => $data['decision'] === 'Rejected' ? $data['note'] : null,
            'teacher_responded_at'  => now(),
        ]);

        return back()->with('success', $data['decision'] === 'Accepted'
            ? "Kelas {$class->class_name} diterima."
            : "Kelas {$class->class_name} ditolak. Kurikulum akan mencarikan guru lain.");
    }

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

            $leave->update([
                'replacement_response'      => 'Accepted',
                'replacement_response_note' => null,
                'replacement_responded_at'  => now(),
            ]);

            return back()->with('success', "Anda menerima menggantikan {$className}. Menunggu persetujuan Kurikulum.");
        }

        $leave->update([
            'replacement_response'      => 'Rejected',
            'replacement_response_note' => $data['note'],
            'replacement_responded_at'  => now(),
            'status'                    => 'Rejected',
            'review_note'               => "Guru pengganti {$teacher->name} menolak: {$data['note']}",
        ]);

        return back()->with('success', 'Permintaan menggantikan ditolak. Pengaju akan diberi tahu.');
    }
}