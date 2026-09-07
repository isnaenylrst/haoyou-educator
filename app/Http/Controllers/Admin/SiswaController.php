<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $students = $query->latest('id')
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

        // Siswa berstatus Active DAN punya enrollment aktif pada kelas program 'HSK'
        $totalHsk = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->where('status', 'Active')
                  ->whereHas('class.programPackage.program', function ($p) {
                      $p->where('program_name', 'HSK');
                  });
            })->count();
 
        // Siswa berstatus Active DAN punya enrollment aktif pada kelas program 'Daily Activity'
        $totalDailyActivity = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
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

    /**
     * Detail siswa (dipanggil via AJAX untuk mengisi modal Detail):
     * no HP, jadwal, progress report, program yang diikuti, usia, status pembayaran.
     */
    public function show(Student $siswa)
    {
        $siswa->load([
            'candidateStudent',
            'user',
            'enrollments.class.programPackage.program',
            'activeEnrollment.class.schedules',
            'progressReports',
            'payments.enrollment.class.programPackage.program',
        ]);

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'phone' => $siswa->user->phone ?? $siswa->candidateStudent->phone ?? '-',
            'age' => $siswa->age,
            'status' => $siswa->status,

            // Jadwal kelas yang sedang aktif
            'schedules' => $siswa->activeEnrollment?->class?->schedules->map(fn ($s) => [
                'day' => $s->day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
            ]) ?? [],

            // Semua program yang pernah/sedang diikuti
            'programs' => $siswa->enrollments->map(fn ($e) => [
                'class_name' => $e->class->class_name ?? '-',
                'program_name' => $e->class->programPackage->program->program_name ?? '-',
                'course_type' => $e->class->programPackage->course_type ?? '-',
                'enrollment_status' => $e->status,
                'enrollment_date' => optional($e->enrollment_date)->format('d M Y'),
            ]),

            // Laporan progress
            'progress_reports' => $siswa->progressReports->map(fn ($r) => [
                'report_type' => $r->report_type,
                'report_period' => $r->report_period,
                'status' => $r->status,
                'uploaded_at' => optional($r->uploaded_at)->format('d M Y'),
                'file_path' => $r->file_path,
            ]),

            // Riwayat & status pembayaran
            'payments' => $siswa->payments->map(fn ($p) => [
                'invoice_number' => $p->invoice_number,
                'program_name' => $p->enrollment->class->programPackage->program->program_name ?? '-',
                'total_bill' => $p->total_bill,
                'amount_paid' => $p->amount_paid,
                'remaining_bill' => $p->remaining_bill,
                'status' => $p->status,
                'payment_date' => optional($p->payment_date)->format('d M Y'),
            ]),
        ]);
    }

    /**
     * Data untuk mengisi modal "Lanjut Program Berikutnya":
     * daftar kelas/program yang bisa dipilih beserta harganya.
     */
    public function continueProgramForm(Student $siswa)
    {
        $siswa->load('activeEnrollment.class.programPackage.program');

        $classOptions = ClassModel::with('programPackage.program')
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get()
            ->map(fn ($class) => [
                'id' => $class->id,
                'class_name' => $class->class_name,
                'program_name' => $class->programPackage->program->program_name ?? '-',
                'package_name' => $class->programPackage->package_name ?? '-',
                'price' => $class->programPackage->price ?? 0,
            ]);

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'current_class' => $siswa->activeEnrollment->class->class_name ?? '-',
            'current_program' => $siswa->activeEnrollment->class->programPackage->program->program_name ?? '-',
            'class_options' => $classOptions,
        ]);
    }

    /**
     * Proses "Lanjut Program Berikutnya": tutup enrollment lama, buat
     * enrollment baru pada kelas/program yang dipilih, sekaligus catat
     * pembayarannya — meniru alur konversi calon siswa jadi siswa.
     */
    public function continueProgram(Request $request, Student $siswa)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'payment_stage' => ['required', 'string', 'max:255'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
        ]);

        $class = ClassModel::with('programPackage')->findOrFail($validated['class_id']);
        $totalBill = $class->programPackage->price ?? 0;
        $amountPaid = $validated['amount_paid'];
        $remainingBill = max($totalBill - $amountPaid, 0);

        $status = match (true) {
            $amountPaid >= $totalBill && $totalBill > 0 => 'Paid',
            $amountPaid > 0 => 'Partial',
            default => 'Pending',
        };

        // Tutup enrollment yang sedang aktif (kalau ada)
        if ($currentEnrollment = $siswa->activeEnrollment) {
            $currentEnrollment->update(['status' => 'Completed']);
        }

        // Buat enrollment baru untuk program/kelas berikutnya
        $newEnrollment = $siswa->enrollments()->create([
            'class_id' => $class->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'Active',
        ]);

        // Catat pembayarannya
        $newEnrollment->payments()->create([
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($siswa->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)),
            'payment_stage' => $validated['payment_stage'],
            'total_bill' => $totalBill,
            'amount_paid' => $amountPaid,
            'remaining_bill' => $remainingBill,
            'payment_method' => $validated['payment_method'],
            'payment_date' => $validated['payment_date'],
            'status' => $status,
        ]);

        // Pastikan status siswa aktif lagi kalau sebelumnya cuti/lulus
        $siswa->update(['status' => 'Active']);

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Siswa {$siswa->name} berhasil dilanjutkan ke program {$class->class_name}.");
    }

    public function destroy(Student $siswa)
    {
        $name = $siswa->name;

        // Siswa yang sudah punya riwayat akademik (absensi, laporan progress,
        // atau riwayat pembayaran) tidak boleh dihapus permanen — FK di DB
        // sengaja RESTRICT supaya data histori gak ikut hilang/rusak.
        $hasAttendance = $siswa->attendances()->exists();
        $hasProgressReports = $siswa->progressReports()->exists();
        $hasPayments = $siswa->enrollments()
            ->whereHas('payments')
            ->exists();

        if ($hasAttendance || $hasProgressReports || $hasPayments) {
            return redirect()
                ->route('admin.siswa')
                ->with('error', "Data siswa {$name} tidak bisa dihapus karena sudah punya riwayat absensi/laporan/pembayaran. Ubah status siswa menjadi Cuti atau Lulus sebagai gantinya.");
        }

        // Aman dihapus: belum ada riwayat sama sekali, boleh bersihkan enrollment dulu
        $siswa->enrollments()->delete();
        $siswa->delete();

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Data siswa {$name} berhasil dihapus.");
    }
}