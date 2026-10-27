<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KetersediaanGuruController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with('availabilities')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        foreach ($teachers as $t) {
            $t->setRelation('availabilities', TeacherAvailabilityService::sortSlots($t->availabilities));
        }

        // Kelas yang butuh tindakan: ditolak guru, atau belum punya guru
        $needsAction = ClassModel::with(['teacher', 'schedules'])
            ->whereIn('status', ['Open', 'Running'])
            ->where(function ($q) {
                $q->where('teacher_response', 'Rejected')->orWhereNull('teacher_id');
            })
            ->orderBy('class_name')
            ->get();

        // Kelas yang menunggu jawaban guru
        $waiting = ClassModel::with(['teacher', 'schedules'])
            ->whereIn('status', ['Open', 'Running'])
            ->whereNotNull('teacher_id')
            ->where('teacher_response', 'Pending')
            ->orderBy('class_name')
            ->get();

        // Untuk tiap kelas yang butuh tindakan: guru mana yang cocok (jam tertutup ketersediaan & tidak bentrok)
        $suitable = [];
        foreach ($needsAction as $class) {
            $suitable[$class->id] = $teachers
                ->filter(fn ($t) => TeacherAvailabilityService::coversClass($t->availabilities, $class)
                    && TeacherAvailabilityService::conflictWith($t->id, $class) === null)
                ->pluck('id')
                ->all();
        }

        $days = TeacherAvailability::DAYS;

        // Kalender mingguan: semua slot semua guru aktif
        $allSlots = collect();
        foreach ($teachers as $t) {
            foreach ($t->availabilities as $a) {
                $a->setRelation('teacher', $t);
                $allSlots->push($a);
            }
        }
        $availabilityGrid = TeacherAvailabilityService::weeklyGrid($allSlots);

        // Ringkasan: siapa yang sudah / belum mengisi
        $notFilled = $teachers->filter(fn ($t) => $t->availabilities->isEmpty())->values();
        $filledCount = $teachers->count() - $notFilled->count();

        return view('kurikulum.ketersediaan-guru', compact(
            'teachers', 'needsAction', 'waiting', 'suitable', 'days',
            'availabilityGrid', 'notFilled', 'filledCount'
        ));
    }

    private function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'day'        => ['required', Rule::in(TeacherAvailability::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'note'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        if (TeacherAvailabilityService::overlaps($data['teacher_id'], $data['day'], $data['start_time'], $data['end_time'])) {
            return back()->withInput()->with('error', 'Slot bertumpuk dengan ketersediaan guru tersebut di hari yang sama.');
        }

        TeacherAvailability::create($data + ['created_by' => Auth::id()]);

        return back()->with('success', 'Ketersediaan guru ditambahkan.');
    }

    public function update(Request $request, TeacherAvailability $availability)
    {
        $data = $request->validate($this->rules());

        if (TeacherAvailabilityService::overlaps($data['teacher_id'], $data['day'], $data['start_time'], $data['end_time'], $availability->id)) {
            return back()->withInput()->with('error', 'Slot bertumpuk dengan ketersediaan guru tersebut di hari yang sama.');
        }

        $availability->update($data);

        return back()->with('success', 'Ketersediaan diperbarui.');
    }

    public function destroy(TeacherAvailability $availability)
    {
        $availability->delete();

        return back()->with('success', 'Ketersediaan dihapus.');
    }

    /** Tugaskan (atau tugaskan ulang) guru ke kelas; guru harus menjawab lagi. */
    public function assign(Request $request, ClassModel $class)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
        ]);

        $class->update([
            'teacher_id'            => $data['teacher_id'],
            'teacher_response'      => 'Pending',
            'teacher_response_note' => null,
            'teacher_responded_at'  => null,
        ]);

        return back()->with('success', "Kelas {$class->class_name} dikirim ke guru untuk dikonfirmasi.");
    }
}