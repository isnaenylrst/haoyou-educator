<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TugasKelasController extends Controller
{
    /** Guru menandai tugas perbaikan kelas dari Kurikulum sebagai selesai. */
    public function complete(Request $request, ClassTask $classTask)
    {
        $teacher = Auth::user()->teacher;

        abort_if(!$teacher || (int) $classTask->teacher_id !== (int) $teacher->id, 403);

        if ($classTask->status === 'Done') {
            return back()->with('error', 'Tugas ini sudah ditandai selesai.');
        }

        $data = $request->validate([
            'teacher_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $classTask->update([
            'status'       => 'Done',
            'done_at'      => now(),
            'teacher_note' => $data['teacher_note'] ?? null,
        ]);

        return back()->with('success', 'Tugas ditandai selesai. Kurikulum akan memeriksanya.');
    }
}