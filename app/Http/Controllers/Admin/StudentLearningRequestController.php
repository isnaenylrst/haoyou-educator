<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentLearningRequest;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StudentLearningRequestController extends Controller
{
    public function index(Request $request)
    {
        $studentId = $request->query('student_id');
        $classId   = $request->query('class_id');
        $q         = trim((string) $request->query('q', ''));

        $requests = StudentLearningRequest::with(['student', 'class.teacher', 'creator'])
            ->when($studentId, fn ($w) => $w->where('student_id', $studentId))
            ->when($classId, fn ($w) => $w->where('class_id', $classId))
            ->when($q !== '', fn ($w) => $w->where('topic', 'like', "%{$q}%")->orWhere('consultation_notes', 'like', "%{$q}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $students = Student::where('status', 'Active')->orderBy('name')->get();
        $classes  = ClassModel::whereIn('status', ['Open', 'Running'])->orderBy('class_name')->get();

        return view('admin.request-belajar.index', compact('requests', 'students', 'classes', 'studentId', 'classId', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'         => ['required', 'exists:students,id'],
            'class_id'           => ['nullable', 'exists:classes,id'],
            'topic'              => ['required', 'string', 'max:255'],
            'consultation_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $student = Student::findOrFail($data['student_id']);
        $class   = !empty($data['class_id']) ? ClassModel::with('teacher.user')->find($data['class_id']) : null;

        $learningRequest = StudentLearningRequest::create([
            'student_id'         => $student->id,
            'class_id'           => $class?->id,
            'topic'              => $data['topic'],
            'consultation_notes' => $data['consultation_notes'] ?? null,
            'created_by'         => Auth::id(),
        ]);

        $notifTitle   = "Request Belajar Siswa: {$student->name}";
        $notifMessage = "Siswa {$student->name} menginginkan topik: '{$data['topic']}'. Catatan Admin: " . Str::limit($data['consultation_notes'] ?? '-', 100);

        // 1. Notifikasi ke Kurikulum
        $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
        foreach ($curriculums as $curr) {
            $curr->notify(new AppNotification(
                title: $notifTitle,
                message: $notifMessage,
                url: url('/kurikulum/request-belajar'),
                type: 'request_belajar',
                icon: 'fa-book-open-reader'
            ));
        }

        // 2. Notifikasi ke Guru pengajar jika kelas ditentukan
        if ($class?->teacher?->user) {
            $class->teacher->user->notify(new AppNotification(
                title: $notifTitle,
                message: $notifMessage,
                url: url('/guru/request-belajar'),
                type: 'request_belajar',
                icon: 'fa-book-open-reader'
            ));
        }

        return back()->with('success', "Request belajar siswa {$student->name} berhasil disimpan dan notifikasi telah dikirim ke Kurikulum dan Guru.");
    }

    public function destroy(StudentLearningRequest $studentLearningRequest)
    {
        $studentLearningRequest->delete();

        return back()->with('success', 'Data request belajar siswa berhasil dihapus.');
    }
}
