<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TugasKelasController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Tugas perbaikan kelas
    |--------------------------------------------------------------------------
    | Kurikulum membuat tugas untuk kelas yang perlu diperbaiki. Tugas
    | otomatis masuk ke Notifikasi guru pengampu kelas tersebut.
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $status = $request->get('status');

        $tasks = ClassTask::with(['class', 'teacher', 'creator'])
            ->when(in_array($status, ['Open', 'Done'], true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'Open' desc")
            ->orderBy('due_date')
            ->latest('id')
            ->get();

        // Hanya kelas yang sudah punya guru yang bisa diberi tugas
        $classes = ClassModel::with('teacher')
            ->whereNotNull('teacher_id')
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get();

        $totalOpen = ClassTask::where('status', 'Open')->count();

        return view('kurikulum.tugas-kelas', compact('tasks', 'classes', 'totalOpen', 'status'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'    => ['required', 'integer', 'exists:classes,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $class = ClassModel::findOrFail($data['class_id']);

        if (!$class->teacher_id) {
            return back()->withInput()->withErrors([
                'class_id' => 'Kelas ini belum punya guru pengampu, tugas tidak bisa dikirim.',
            ]);
        }

        ClassTask::create([
            'class_id'    => $class->id,
            'teacher_id'  => $class->teacher_id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?? null,
            'status'      => 'Open',
            'created_by'  => Auth::id(),
        ]);

        return redirect()
            ->route('kurikulum.tugas-kelas')
            ->with('success', 'Tugas dikirim ke notifikasi guru pengampu.');
    }

    public function destroy(ClassTask $classTask)
    {
        $classTask->delete();

        return redirect()
            ->route('kurikulum.tugas-kelas')
            ->with('success', 'Tugas dihapus.');
    }
}