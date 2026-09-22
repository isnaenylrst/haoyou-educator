<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\PrivatePackage;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()
            ->with([
                'user',
                'candidateStudent',
                'enrollments.class',
                'activeEnrollment.class.programPackage.program',
                'activeEnrollment.programPackage.program',
                'activeEnrollment.privatePackage',
            ]);

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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        $students = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        $classes = ClassModel::orderBy('class_name')->get(['id', 'class_name']);

        $stats = $this->getStats();

        return view('admin.siswa', compact('students', 'classes', 'stats'));
    }

    private function getStats(): array
    {
        $totalActive = Student::where('status', 'Active')->count();

        $totalHsk = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->whereIn('status', ['Active', 'Waiting Class'])
                  ->where(function ($q2) {
                      $q2->whereHas('programPackage.program', fn ($p) => $p->where('program_name', 'HSK'))
                         ->orWhereHas('class.programPackage.program', fn ($p) => $p->where('program_name', 'HSK'));
                  });
            })->count();

        $totalDailyActivity = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->whereIn('status', ['Active', 'Waiting Class'])
                  ->where(function ($q2) {
                      $q2->whereHas('programPackage.program', fn ($p) => $p->where('program_name', 'Daily Activity'))
                         ->orWhereHas('class.programPackage.program', fn ($p) => $p->where('program_name', 'Daily Activity'));
                  });
            })->count();

        $totalPrivate = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->where('status', 'Active')
                  ->whereNotNull('private_package_id');
            })->count();

        return [
            'total_active' => $totalActive,
            'total_hsk' => $totalHsk,
            'total_daily_activity' => $totalDailyActivity,
            'total_private' => $totalPrivate,
        ];
    }

    public function backfillProgramPackages()
    {
        $enrollments = ClassEnrollment::whereNotNull('class_id')
            ->whereNull('program_package_id')
            ->with('class')
            ->get();

        $updated = 0;

        foreach ($enrollments as $enrollment) {
            if ($enrollment->class && $enrollment->class->program_package_id) {
                $enrollment->update([
                    'program_package_id' => $enrollment->class->program_package_id,
                ]);
                $updated++;
            }
        }

        return response()->json([
            'message' => "Backfill selesai. {$updated} enrollment diperbarui.",
            'total_checked' => $enrollments->count(),
        ]);
    }

    public function edit(Student $siswa)
    {
        $siswa->load('activeEnrollment.class', 'activeEnrollment.privatePackage');

        $activeEnrollment = $siswa->activeEnrollment;
        $isPrivate = (bool) $activeEnrollment?->private_package_id;

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'status' => $siswa->status,
            'points' => $siswa->points,
            'join_date' => optional($siswa->join_date)->format('Y-m-d'),
            'is_private' => $isPrivate,
            'private_package_name' => $isPrivate
                ? ($activeEnrollment->privatePackage->package_name ?? 'Private')
                : null,
        ]);
    }

    public function update(Request $request, Student $siswa)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Graduated'],
            'points' => ['required', 'integer', 'min:0'],
        ]);

        $siswa->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'points' => $validated['points'],
        ]);

        $currentEnrollment = $siswa->activeEnrollment;

        if ($currentEnrollment && $currentEnrollment->private_package_id) {
            return redirect()
                ->route('admin.siswa')
                ->with('success', "Data siswa {$siswa->name} berhasil diperbarui. Kelas tidak diubah karena siswa sedang mengikuti paket Private — gunakan \"Lanjut Program Berikutnya\" untuk menggantinya.");
        }

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Data siswa {$siswa->name} berhasil diperbarui.");
    }

    public function show(Student $siswa)
    {
        $siswa->load([
            'candidateStudent',
            'user',
            'enrollments.class.programPackage.program',
            'enrollments.programPackage.program',
            'enrollments.privatePackage',
            'activeEnrollment.class.schedules',
            'progressReports',
            'payments.enrollment.class.programPackage.program',
            'payments.enrollment.programPackage.program',
            'payments.enrollment.privatePackage',
        ]);

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'phone' => $siswa->user->phone ?? $siswa->candidateStudent->phone ?? '-',
            'age' => $siswa->age,
            'status' => $siswa->status,

            'schedules' => $siswa->activeEnrollment?->class?->schedules->map(fn ($s) => [
                'day' => $s->day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
            ]) ?? [],

            'programs' => $siswa->enrollments->map(function ($e) {
                if ($e->private_package_id) {
                    return [
                        'class_name' => $e->class->class_name ?? '-',
                        'program_name' => $e->privatePackage->package_name ?? '-',
                        'course_type' => 'Private',
                        'enrollment_status' => $e->status,
                        'enrollment_date' => optional($e->enrollment_date)->format('d M Y'),
                    ];
                }

                $programPackage = $e->programPackage ?? $e->class->programPackage ?? null;

                return [
                    'class_name' => $e->class->class_name ?? '-',
                    'program_name' => $programPackage->program->program_name ?? '-',
                    'course_type' => 'Reguler',
                    'enrollment_status' => $e->status,
                    'enrollment_date' => optional($e->enrollment_date)->format('d M Y'),
                ];
            }),

            'progress_reports' => $siswa->progressReports->map(fn ($r) => [
                'report_type' => $r->report_type,
                'report_period' => $r->report_period,
                'status' => $r->status,
                'uploaded_at' => optional($r->uploaded_at)->format('d M Y'),
                'file_path' => $r->file_path,
            ]),

            'payments' => $siswa->payments->map(function ($p) {
                if ($p->enrollment->private_package_id) {
                    $programName = $p->enrollment->privatePackage->package_name ?? '-';
                } else {
                    $programPackage = $p->enrollment->programPackage ?? $p->enrollment->class->programPackage ?? null;
                    $programName = $programPackage->program->program_name ?? '-';
                }

                return [
                    'invoice_number' => $p->invoice_number,
                    'program_name' => $programName,
                    'total_bill' => $p->total_bill,
                    'amount_paid' => $p->amount_paid,
                    'remaining_bill' => $p->remaining_bill,
                    'status' => $p->status,
                    'payment_date' => optional($p->payment_date)->format('d M Y'),
                ];
            }),
        ]);
    }

    private function buildRecommendation(?ClassEnrollment $currentEnrollment): array
    {
        if (!$currentEnrollment) {
            return ['type' => null];
        }

        if ($currentEnrollment->private_package_id) {
            return [
                'type' => 'private',
                'private_package_id' => $currentEnrollment->private_package_id,
                'label' => 'Rekomendasi: lanjutkan paket Private yang sama — '
                    . ($currentEnrollment->privatePackage->package_name ?? '-'),
            ];
        }

        $currentPackage = $currentEnrollment->class->programPackage ?? $currentEnrollment->programPackage ?? null;

        if (!$currentPackage || !$currentPackage->level_id) {
            return ['type' => null];
        }

        $currentLevel = ProgramLevel::find($currentPackage->level_id);

        if (!$currentLevel) {
            return ['type' => null];
        }

        $nextLevel = ProgramLevel::where('category_id', $currentLevel->category_id)
            ->where('sort_order', '>', $currentLevel->sort_order)
            ->orderBy('sort_order')
            ->first();

        if (!$nextLevel) {
            return ['type' => null]; // sudah di level tertinggi kategori ini
        }

        $nextPackage = ProgramPackage::where('program_id', $currentPackage->program_id)
            ->where('category_id', $currentLevel->category_id)
            ->where('level_id', $nextLevel->id)
            ->first();

        if (!$nextPackage) {
            return ['type' => null]; // paket level berikutnya belum dibuat
        }

        $recommendedClass = ClassModel::where('program_package_id', $nextPackage->id)
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->first();

        return [
            'type' => 'program',
            'program_package_id' => $nextPackage->id,
            'class_id' => $recommendedClass->id ?? null,
            'label' => 'Rekomendasi: lanjut ke — ' . $nextPackage->package_name,
        ];
    }

    private function hasOutstandingBalance(?ClassEnrollment $enrollment): bool
    {
        if (!$enrollment) {
            return false;
        }

        $latestPayment = $enrollment->payments()->latest('payment_date')->latest('id')->first();

        return (bool) ($latestPayment && $latestPayment->remaining_bill > 0);
    }

    public function continueProgramForm(Student $siswa)
    {
        $siswa->load(
            'activeEnrollment.class.programPackage.program',
            'activeEnrollment.programPackage.program',
            'activeEnrollment.privatePackage'
        );

        $activeEnrollment = $siswa->activeEnrollment;
        $isCurrentlyPrivate = (bool) $activeEnrollment?->private_package_id;
        $hasOutstandingBalance = $this->hasOutstandingBalance($activeEnrollment);

        $classOptions = ClassModel::with('programPackage.program')
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get()
            ->map(fn ($class) => [
                'id' => $class->id,
                'class_name' => $class->class_name,
                'program_name' => $class->programPackage->program->program_name ?? '-',
                'package_name' => $class->programPackage->package_name ?? '-',
                'program_package_id' => $class->program_package_id,
                'price' => $class->programPackage->price ?? 0,
            ]);

        $privateOptions = PrivatePackage::where('is_active', true)
            ->orderBy('package_name')
            ->get(['id', 'package_name', 'price']);

        $currentProgram = $isCurrentlyPrivate
            ? ($activeEnrollment->privatePackage->package_name ?? '-')
            : ($activeEnrollment?->class?->programPackage?->program?->program_name
                ?? $activeEnrollment?->programPackage?->program?->program_name
                ?? '-');

        $currentClass = $isCurrentlyPrivate
            ? 'Private'
            : ($activeEnrollment?->class?->class_name ?? 'Belum ditentukan');

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'current_class' => $currentClass,
            'current_program' => $currentProgram,
            'is_currently_private' => $isCurrentlyPrivate,
            'class_options' => $classOptions,
            'private_options' => $privateOptions,
            'recommendation' => $this->buildRecommendation($activeEnrollment),
            'has_outstanding_balance' => $hasOutstandingBalance,
            'outstanding_message' => $hasOutstandingBalance
                ? 'Siswa masih memiliki tagihan yang belum lunas pada program saat ini. Lunasi pembayaran terlebih dahulu sebelum melanjutkan ke program berikutnya.'
                : null,
        ]);
    }

    public function continueProgram(Request $request, Student $siswa)
    {
        session(['continue_student_id' => $siswa->id]);

        $currentEnrollment = $siswa->activeEnrollment;

        if ($this->hasOutstandingBalance($currentEnrollment)) {
            return redirect()
                ->route('admin.siswa')
                ->with('error', "Siswa {$siswa->name} masih memiliki tagihan yang belum lunas pada program saat ini. Lunasi pembayaran sebelum melanjutkan program.");
        }
        
        $validated = $request->validate([
            'package_type' => ['required', 'in:program,private'],
            'program_package_id' => ['required_if:package_type,program', 'nullable', 'exists:program_packages,id'],
            'private_package_id' => ['required_if:package_type,private', 'nullable', 'exists:private_packages,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'payment_stage' => ['required', 'string', 'max:255'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
        ]);

        $isPrivate = $validated['package_type'] === 'private';

        $totalBill = $isPrivate
            ? (PrivatePackage::find($validated['private_package_id'])->price ?? 0)
            : (ProgramPackage::find($validated['program_package_id'])->price ?? 0);

        $amountPaid = $validated['amount_paid'];
        $remainingBill = max($totalBill - $amountPaid, 0);

        $status = match (true) {
            $amountPaid >= $totalBill && $totalBill > 0 => 'Paid',
            $amountPaid > 0 => 'Partial',
            default => 'Pending',
        };

        if ($currentEnrollment) {
            $currentEnrollment->update(['status' => 'Completed']);
        }

        // Buat enrollment baru — Reguler atau Private
        $newEnrollment = $siswa->enrollments()->create([
            'class_id' => $isPrivate ? null : ($validated['class_id'] ?? null),
            'program_package_id' => $isPrivate ? null : $validated['program_package_id'],
            'private_package_id' => $isPrivate ? $validated['private_package_id'] : null,
            'enrollment_date' => now()->toDateString(),
            'status' => 'Active',
        ]);

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

        $siswa->update([
            'status' => 'Active',
            'join_date' => $validated['payment_date'],
        ]);

        $programLabel = $isPrivate
            ? (PrivatePackage::find($validated['private_package_id'])->package_name ?? 'Private')
            : (ClassModel::find($validated['class_id'])->class_name ?? 'program berikutnya');

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Siswa {$siswa->name} berhasil dilanjutkan ke {$programLabel}.");
    }

    public function destroy(Student $siswa)
    {
        $name = $siswa->name;
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

        $siswa->enrollments()->delete();
        $siswa->delete();

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Data siswa {$name} berhasil dihapus.");
    }

    public function waitingClassOptions(ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status !== 'Waiting Class', 404, 'Enrollment ini tidak sedang menunggu kelas.');

        $classOptions = ClassModel::where('program_package_id', $enrollment->program_package_id)
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'delivery_mode', 'status']);

        return response()->json([
            'class_options' => $classOptions,
        ]);
    }
}