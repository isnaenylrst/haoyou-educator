<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentLearningRequest;
use Illuminate\Http\Request;

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

        return view('kurikulum.request-belajar', compact('requests', 'students', 'classes', 'studentId', 'classId', 'q'));
    }
}
