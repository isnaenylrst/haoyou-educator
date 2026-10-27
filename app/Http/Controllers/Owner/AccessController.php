<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    /** Daftar guru: Owner memilih guru yang ingin dibuka. */
    public function teachers()
    {
        $teachers = Teacher::withCount('classes')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $current = session('owner_view_teacher_id');

        return view('owner.guru', compact('teachers', 'current'));
    }

    /** Buka area Guru sebagai guru tertentu. */
    public function view(Teacher $teacher)
    {
        session(['owner_view_teacher_id' => $teacher->id]);

        return redirect()->route('teacher.dashboard');
    }

    /** Ganti guru dari bar Owner (tetap di halaman yang sama). */
    public function switch(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
        ]);

        session(['owner_view_teacher_id' => $data['teacher_id']]);

        return back();
    }

    /** Keluar dari mode guru. */
    public function clear()
    {
        session()->forget('owner_view_teacher_id');

        return redirect()->route('owner.dashboard');
    }
}