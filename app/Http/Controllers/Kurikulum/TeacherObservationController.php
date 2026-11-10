<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\TeacherObservation;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeacherObservationController extends Controller
{
    public function index(Request $request)
    {
        $teacherId     = $request->query('teacher_id');
        $sopCompliance = $request->query('sop_compliance');
        $date          = $request->query('date');

        $query = TeacherObservation::with(['teacher', 'class', 'curriculumUser'])
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($sopCompliance, fn ($q) => $q->where('sop_compliance', $sopCompliance))
            ->when($date, fn ($q) => $q->whereDate('observation_date', $date));

        $totalCount    = (clone $query)->count();
        $totalSesuai   = (clone $query)->where('sop_compliance', 'Sesuai SOP')->count();
        $totalPerbaikan= (clone $query)->where('sop_compliance', 'Perlu Perbaikan')->count();
        $totalTidak    = (clone $query)->where('sop_compliance', 'Tidak Sesuai SOP')->count();

        $observations = $query->latest('observation_date')->latest('id')->paginate(10)->withQueryString();

        $teachers = Teacher::where('status', 'Active')
            ->with(['classes' => fn ($q) => $q->whereIn('status', ['Open', 'Running'])])
            ->orderBy('name')
            ->get();

        return view('kurikulum.observasi', compact(
            'observations',
            'teachers',
            'totalCount',
            'totalSesuai',
            'totalPerbaikan',
            'totalTidak',
            'teacherId',
            'sopCompliance',
            'date'
        ));
    }

    public function getTeacherClasses(Teacher $teacher)
    {
        $classes = $teacher->classes()
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name']);

        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id'         => ['required', 'exists:teachers,id'],
            'class_id'           => ['required', 'exists:classes,id'],
            'observation_date'   => ['required', 'date'],
            'sop_compliance'     => ['required', Rule::in(['Sesuai SOP', 'Perlu Perbaikan', 'Tidak Sesuai SOP'])],
            'observation_result' => ['required', 'string', 'max:5000'],
            'feedback_solution'  => ['required', 'string', 'max:5000'],
        ]);

        $teacher = Teacher::with('user')->findOrFail($data['teacher_id']);
        $class   = ClassModel::findOrFail($data['class_id']);

        $observation = TeacherObservation::create([
            'teacher_id'         => $teacher->id,
            'class_id'           => $class->id,
            'curriculum_user_id' => Auth::id(),
            'observation_date'   => $data['observation_date'],
            'sop_compliance'     => $data['sop_compliance'],
            'observation_result' => $data['observation_result'],
            'feedback_solution'  => $data['feedback_solution'],
        ]);

        // Notifikasi ke guru bersangkutan
        if ($teacher->user) {
            $teacher->user->notify(new AppNotification(
                title: "Hasil Observasi Mengajar",
                message: "Kurikulum telah mencatat hasil observasi untuk kelas {$class->class_name} ({$data['sop_compliance']}). Klik untuk melihat detail & solusi.",
                url: url('/guru/observasi'),
                type: 'observasi',
                icon: 'fa-magnifying-glass-chart'
            ));
        }

        return back()->with('success', "Observasi mengajar untuk Guru {$teacher->name} berhasil disimpan dan notifikasi telah dikirimkan.");
    }

    public function update(Request $request, TeacherObservation $observation)
    {
        $data = $request->validate([
            'class_id'           => ['required', 'exists:classes,id'],
            'observation_date'   => ['required', 'date'],
            'sop_compliance'     => ['required', Rule::in(['Sesuai SOP', 'Perlu Perbaikan', 'Tidak Sesuai SOP'])],
            'observation_result' => ['required', 'string', 'max:5000'],
            'feedback_solution'  => ['required', 'string', 'max:5000'],
        ]);

        $observation->update($data);

        return back()->with('success', 'Data observasi mengajar berhasil diperbarui.');
    }

    public function destroy(TeacherObservation $observation)
    {
        $observation->delete();

        return back()->with('success', 'Data observasi mengajar berhasil dihapus.');
    }
}

