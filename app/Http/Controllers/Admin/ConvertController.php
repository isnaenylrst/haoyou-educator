<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\Payment;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ConvertController extends Controller
{
    public function create(CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $candidateStudent->load('program');

        $packages = ProgramPackage::where('program_id', $candidateStudent->program_id)
            ->orderBy('course_type')
            ->get();

        $classes = ClassModel::whereIn('program_package_id', $packages->pluck('id'))
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'program_package_id', 'delivery_mode', 'status']);

        return view('admin.siswaconvert', compact('candidateStudent', 'packages', 'classes'));
    }

    public function store(Request $request, CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $validated = $request->validate([
            'program_package_id' => 'required|exists:program_packages,id',
            'class_id' => 'nullable|exists:classes,id',
            'amount_paid' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:100',
            'payment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $candidateStudent) {

            $package = ProgramPackage::findOrFail($validated['program_package_id']);
            $studentLevel = Level::where('nama_level', 'Student')->firstOrFail();

            /*
            |--------------------------------------------------------------------
            | Buat akun User untuk siswa
            |--------------------------------------------------------------------
            */
            $username = $this->generateUsername($candidateStudent->phone);

            $user = User::create([
                'level_id' => $studentLevel->id_level,
                'username' => $username,
                'password' => Hash::make('haoyou123'),
                'status' => 'Active',
            ]);

            /*
            |--------------------------------------------------------------------
            | Buat Student
            |--------------------------------------------------------------------
            */
            $student = Student::create([
                'candidate_student_id' => $candidateStudent->id,
                'user_id' => $user->id,
                'name' => $candidateStudent->name,
                'points' => 0,
                'join_date' => now(),
                'status' => 'Active',
            ]);

            /*
            |--------------------------------------------------------------------
            | Buat Class Enrollment (class_id boleh kosong)
            |--------------------------------------------------------------------
            */
            $enrollment = ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $validated['class_id'] ?? null,
                'enrollment_date' => now(),
                'status' => 'Active',
            ]);

            /*
            |--------------------------------------------------------------------
            | Buat Payment (DP)
            |--------------------------------------------------------------------
            */
            $totalBill = $package->price;
            $amountPaid = $validated['amount_paid'];
            $remaining = max($totalBill - $amountPaid, 0);

            Payment::create([
                'enrollment_id' => $enrollment->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_file_path' => null,
                'payment_stage' => 'DP',
                'total_bill' => $totalBill,
                'amount_paid' => $amountPaid,
                'remaining_bill' => $remaining,
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'payment_proof_path' => null,
                'status' => $remaining <= 0 ? 'Paid' : 'Partial',
            ]);
        });

        return redirect()
            ->route('admin.calon-siswa')
            ->with('success', 'Calon siswa berhasil dikonversi menjadi siswa aktif.');
    }

    private function generateUsername(string $phone): string
    {
        $base = preg_replace('/\D/', '', $phone);
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }

    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . str_pad((Payment::max('id') + 1), 4, '0', STR_PAD_LEFT);
    }
}