<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\TeacherObservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObservationController extends Controller
{
    private function teacher()
    {
        return Auth::user()->teacher;
    }

    public function index(Request $request)
    {
        $teacher = $this->teacher();
        abort_unless($teacher, 403, 'Akses khusus guru.');

        $sopCompliance = $request->query('sop_compliance');

        $observations = TeacherObservation::where('teacher_id', $teacher->id)
            ->with(['class', 'curriculumUser'])
            ->when($sopCompliance, fn ($q) => $q->where('sop_compliance', $sopCompliance))
            ->latest('observation_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $totalCount     = TeacherObservation::where('teacher_id', $teacher->id)->count();
        $totalSesuai    = TeacherObservation::where('teacher_id', $teacher->id)->where('sop_compliance', 'Sesuai SOP')->count();
        $totalPerbaikan = TeacherObservation::where('teacher_id', $teacher->id)->where('sop_compliance', 'Perlu Perbaikan')->count();
        $totalTidak     = TeacherObservation::where('teacher_id', $teacher->id)->where('sop_compliance', 'Tidak Sesuai SOP')->count();

        return view('guru.observasi', compact(
            'observations',
            'teacher',
            'totalCount',
            'totalSesuai',
            'totalPerbaikan',
            'totalTidak',
            'sopCompliance'
        ));
    }
}

