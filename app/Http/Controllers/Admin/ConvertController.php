<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\Payment;
use App\Models\PrivatePackage;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConvertController extends Controller
{
    public function create(CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $candidateStudent->load('program');

        $packages = ProgramPackage::where('program_packages.program_id', $candidateStudent->program_id)
            ->leftJoin('program_categories', 'program_categories.id', '=', 'program_packages.category_id')
            ->leftJoin('program_levels', 'program_levels.id', '=', 'program_packages.level_id')
            ->select(
                'program_packages.*',
                'program_categories.category_name',
                'program_levels.level_name'
            )
            ->get();

        $packages = $this->sortPackagesByCategoryAndLevel($packages);

        $classes = ClassModel::whereIn('program_package_id', $packages->pluck('id'))
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'program_package_id', 'delivery_mode', 'status']);

        $privatePackages = PrivatePackage::where('is_active', true)
            ->orderBy('package_name')
            ->get();

        // --- Rekomendasi kategori berdasarkan umur (khusus Daily Activity) ---
        $candidateAge = null;
        $recommendedCategory = null;

        if ($candidateStudent->birth_date) {
            $candidateAge = Carbon::parse($candidateStudent->birth_date)->age;

            if ($candidateStudent->program?->program_name === 'Daily Activity') {
                $recommendedCategory = $this->recommendCategoryByAge($candidateAge);
            }
        }

        return view('admin.siswaconvert', compact(
            'candidateStudent',
            'packages',
            'classes',
            'privatePackages',
            'candidateAge',
            'recommendedCategory'
        ));
    }

    private function sortPackagesByCategoryAndLevel($packages)
    {
        $categoryOrder = [
            // Daily Activity
            'Maochong' => 1,
            'Jianer'   => 2,
            'Hudie'    => 3,
            'Feixiang' => 4,
            // HSK
            'HSK Class'       => 1,
            'HSK Preparation' => 2,
        ];

        return $packages->sort(function ($a, $b) use ($categoryOrder) {
            $rankA = $categoryOrder[$a->category_name] ?? 99;
            $rankB = $categoryOrder[$b->category_name] ?? 99;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strnatcmp($a->level_name ?? '', $b->level_name ?? '')
                ?: strcmp($a->package_name, $b->package_name);
        })->values();
    }

    private function recommendCategoryByAge(int $age): ?string
    {
        return match (true) {
            $age >= 3 && $age <= 6 => 'Maochong',
            $age >= 7 && $age <= 9 => 'Jianer',
            $age >= 10 && $age <= 14 => 'Hudie',
            $age >= 15 => 'Feixiang',
            default => null,
        };
    }  

    public function store(Request $request, CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $validated = $request->validate([
            'package_type'        => 'required|in:program,private',
            'program_package_id'  => 'required_if:package_type,program|nullable|exists:program_packages,id',
            'private_package_id'  => 'required_if:package_type,private|nullable|exists:private_packages,id',
            'class_id'            => 'nullable|exists:classes,id', // <-- required_if dihapus
            'amount_paid'         => 'required|numeric|min:1',
            'payment_method'      => 'required|string|max:100',
            'payment_date'        => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $candidateStudent) {

            $isPrivate = $validated['package_type'] === 'private';

            $package = $isPrivate
                ? PrivatePackage::findOrFail($validated['private_package_id'])
                : ProgramPackage::findOrFail($validated['program_package_id']);

            $studentLevel = Level::where('nama_level', 'Student')->firstOrFail();

            $username = $this->generateUsername($candidateStudent->phone);

            $user = User::create([
                'level_id' => $studentLevel->id_level,
                'username' => $username,
                'password' => Hash::make('haoyou123'),
                'status' => 'Active',
            ]);

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
            | Buat Class Enrollment
            | - Reguler + class_id diisi   -> status Active
            | - Reguler + class_id kosong  -> status Waiting Class (assign nanti)
            | - Private                    -> status Active (class_id selalu null)
            |--------------------------------------------------------------------
            */
            $classId = $isPrivate ? null : ($validated['class_id'] ?? null);

            $enrollment = ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $classId,
                'program_package_id' => $isPrivate ? null : $validated['program_package_id'],
                'private_package_id' => $isPrivate ? $validated['private_package_id'] : null,
                'enrollment_date' => now(),
                'status' => (!$isPrivate && !$classId) ? 'Waiting Class' : 'Active',
            ]);

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

    public function assignClass(Request $request, ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status !== 'Waiting Class', 404, 'Enrollment ini tidak sedang menunggu kelas.');

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        // pastikan kelas yang dipilih sesuai program_package enrollment ini
        $classBelongsToPackage = ClassModel::where('id', $validated['class_id'])
            ->where('program_package_id', $enrollment->program_package_id)
            ->exists();

        abort_unless($classBelongsToPackage, 422, 'Kelas tidak sesuai dengan paket program siswa ini.');

        $enrollment->update([
            'class_id' => $validated['class_id'],
            'status' => 'Active',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Siswa berhasil dimasukkan ke kelas.');
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