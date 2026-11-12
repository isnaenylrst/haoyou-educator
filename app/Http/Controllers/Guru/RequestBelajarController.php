<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\StudentLearningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestBelajarController extends Controller
{
    private function teacher()
    {
        return Auth::user()->teacher;
    }

    /**
     * Tampilkan daftar request belajar siswa untuk kelas-kelas yang diampu oleh Guru
     */
    public function index(Request $request)
    {
        $teacher = $this->teacher();
        abort_unless($teacher, 403, 'Profil guru belum terhubung dengan akun Anda.');

        // Ambil ID kelas-kelas yang diampu oleh guru ini
        $myClasses = ClassModel::where('teacher_id', $teacher->id)
            ->where('teacher_response', 'Accepted')
            ->orderBy('class_name')
            ->get();

        $myClassIds = $myClasses->pluck('id');
        $selectedClassId = $request->query('class_id');
        $q = trim((string) $request->query('q', ''));

        $query = StudentLearningRequest::with(['student', 'class', 'creator'])
            ->whereIn('class_id', $myClassIds);

        if ($selectedClassId && $myClassIds->contains($selectedClassId)) {
            $query->where('class_id', $selectedClassId);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('topic', 'like', "%{$q}%")
                  ->orWhere('consultation_notes', 'like', "%{$q}%")
                  ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', "%{$q}%"));
            });
        }

        $requests = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $totalRequests = StudentLearningRequest::whereIn('class_id', $myClassIds)->count();

        return view('guru.request-belajar', compact(
            'requests',
            'myClasses',
            'selectedClassId',
            'q',
            'totalRequests'
        ));
    }
}

