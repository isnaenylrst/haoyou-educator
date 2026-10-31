<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ConsultationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConsultationResultController extends Controller
{
    /**
     * Tampilkan hasil konsultasi untuk guru yang sedang login (Read-Only)
     */
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Profil guru belum terhubung dengan akun Anda.');

        $query = ConsultationResult::where('teacher_id', $teacher->id)
            ->with(['curriculumUser', 'observation.class'])
            ->latest('consultation_date');

        if ($request->filled('date_from')) {
            $query->whereDate('consultation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('consultation_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('topic', 'like', $search)
                  ->orWhere('follow_up', 'like', $search)
                  ->orWhere('improvement_points', 'like', $search);
            });
        }

        $consultations = $query->paginate(10)->withQueryString();

        return view('guru.hasil-konsultasi', compact('consultations', 'teacher'));
    }

    /**
     * Unduh lampiran berkas hasil konsultasi
     */
    public function download($id)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Akses tidak diizinkan.');

        $consultation = ConsultationResult::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        abort_unless($consultation->file_path && Storage::disk('public')->exists($consultation->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($consultation->file_path, $consultation->file_name);
    }
}

