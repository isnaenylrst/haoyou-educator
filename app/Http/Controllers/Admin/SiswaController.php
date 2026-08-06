<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()
            ->with(['user', 'candidateStudent', 'enrollments.class', 'activeEnrollment.class']);

        // --- Filter: pencarian nama / telepon / sekolah ---
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('candidateStudent', function ($c) use ($search) {
                      $c->where('school', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // --- Filter: Status siswa (Active/Aktif, Inactive/Cuti, Graduated/Lulus) ---
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // --- Filter: Kelas siswa ---
        if ($request->filled('class_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        $students = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Untuk populate dropdown "Kelas Siswa"
        $classes = ClassModel::orderBy('class_name')->get(['id', 'class_name']);

        $stats = $this->getStats();

        return view('admin.siswa', compact('students', 'classes', 'stats'));
    }

    /**
     * Hitung angka-angka untuk kartu statistik di atas tabel siswa.
     */
    private function getStats(): array
    {
        $totalActive = Student::where('status', 'Active')->count();

        // Siswa dengan enrollment aktif pada kelas dari program 'HSK'
        $totalHsk = Student::whereHas('enrollments', function ($q) {
            $q->where('status', 'Active')
              ->whereHas('class.programPackage.program', function ($p) {
                  $p->where('program_name', 'HSK');
              });
        })->count();

        // Siswa dengan enrollment aktif pada kelas dari program 'Daily Activity'
        $totalDailyActivity = Student::whereHas('enrollments', function ($q) {
            $q->where('status', 'Active')
              ->whereHas('class.programPackage.program', function ($p) {
                  $p->where('program_name', 'Daily Activity');
              });
        })->count();

        $totalOnLeave = Student::where('status', 'Inactive')->count();

        return [
            'total_active' => $totalActive,
            'total_hsk' => $totalHsk,
            'total_daily_activity' => $totalDailyActivity,
            'total_on_leave' => $totalOnLeave,
        ];
    }

    /**
     * Ambil data siswa untuk mengisi form di modal edit (dipanggil via AJAX
     * dari openEditModal() di blade).
     */
    public function edit(Student $siswa)
    {
        $siswa->load('activeEnrollment.class');

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'status' => $siswa->status,
            'points' => $siswa->points,
            'join_date' => optional($siswa->join_date)->format('Y-m-d'),
            'class_id' => $siswa->activeEnrollment->class_id ?? null,
        ]);
    }

    public function update(Request $request, Student $siswa)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Graduated'],
            'points' => ['required', 'integer', 'min:0'],
            'join_date' => ['required', 'date'],
            'class_id' => ['nullable', 'exists:classes,id'],
        ]);

        $siswa->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'points' => $validated['points'],
            'join_date' => $validated['join_date'],
        ]);

        // Kalau kelas diganti, tutup enrollment aktif yang lama lalu buat yang baru
        if ($request->filled('class_id')) {
            $currentEnrollment = $siswa->activeEnrollment;

            if (!$currentEnrollment || $currentEnrollment->class_id != $validated['class_id']) {
                if ($currentEnrollment) {
                    $currentEnrollment->update(['status' => 'Completed']);
                }

                $siswa->enrollments()->create([
                    'class_id' => $validated['class_id'],
                    'enrollment_date' => now()->toDateString(),
                    'status' => 'Active',
                ]);
            }
        }

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Data siswa {$siswa->name} berhasil diperbarui.");
    }

    // public function destroy(Student $siswa)
    // {
    //     $name = $siswa->name;

    //     $siswa->enrollments()->delete();
    //     $siswa->delete();

    //     return redirect()
    //         ->route('admin.siswa')
    //         ->with('success', "Data siswa {$name} berhasil dihapus.");
    // }
}