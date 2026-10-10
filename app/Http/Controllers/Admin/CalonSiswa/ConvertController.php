<?php

namespace App\Http\Controllers\Admin\CalonSiswa;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\PrivatePackage;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\PaymentInstallmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ConvertController extends Controller
{
    /** Kunci session untuk menyimpan pilihan step 1 (ditambah id calon siswa). */
    private const SESSION_KEY = 'convert_draft.';

    public function __construct(private PaymentInstallmentService $payments)
    {
    }

    /* =====================================================================
     |  STEP 1: Data diri & program (tampilan)
     * ===================================================================== */
    public function create(CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $candidateStudent->load(['program', 'privatePackage', 'availableSchedules']);
        $packages = collect();
        $levels = collect();
        $classes = collect();

        if ($candidateStudent->program_id) {
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

            $levels = ProgramLevel::where('program_levels.program_id', $candidateStudent->program_id)
                ->leftJoin('program_categories', 'program_categories.id', '=', 'program_levels.category_id')
                ->select('program_levels.*', 'program_categories.category_name')
                ->orderBy('program_levels.category_id')
                ->orderBy('program_levels.sort_order')
                ->get();

            $classes = ClassModel::whereIn('program_package_id', $packages->pluck('id'))
                ->whereIn('status', ['Open', 'Running'])
                ->orderBy('class_name')
                ->get(['id', 'class_name', 'program_package_id', 'level_id', 'delivery_mode', 'status']);
        }

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

        $defaultPackageType = $candidateStudent->private_package_id ? 'private' : 'program';

        // Pilihan step 1 sebelumnya (kalau admin kembali dari step 2)
        $draft = session(self::SESSION_KEY . $candidateStudent->id, []);

        return view('admin.calon-siswa.convert', compact(
            'candidateStudent',
            'packages',
            'levels',
            'classes',
            'privatePackages',
            'candidateAge',
            'recommendedCategory',
            'defaultPackageType',
            'draft'
        ))->with('step', 1);
    }

    /* =====================================================================
     |  STEP 1: submit, simpan pilihan ke session, lanjut ke pembayaran
     * ===================================================================== */
    public function storeStep1(Request $request, CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $validated = $request->validate([
            'package_type'       => 'required|in:program,private',
            'program_package_id' => 'required_if:package_type,program|nullable|exists:program_packages,id',
            'private_package_id' => 'required_if:package_type,private|nullable|exists:private_packages,id',
            'level_id'           => 'required_if:package_type,program|nullable|exists:program_levels,id',
            'class_id'           => 'nullable|exists:classes,id',
        ], [
            'level_id.required_if' => 'Level wajib dipilih.',
        ]);

        // Validasi kecocokan paket / level / kelas
        $this->resolveSelection($validated, $candidateStudent);

        session([self::SESSION_KEY . $candidateStudent->id => $validated]);

        return redirect()->route('admin.calon-siswa.convert.step2', $candidateStudent);
    }

    /* =====================================================================
     |  STEP 2: Pembayaran & upload bukti (tampilan)
     * ===================================================================== */
    public function step2(CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $draft = session(self::SESSION_KEY . $candidateStudent->id);

        if (! $draft) {
            return redirect()
                ->route('admin.calon-siswa.convert', $candidateStudent)
                ->with('error', 'Lengkapi data diri dan program dulu.');
        }

        [$isPrivate, $package, $level] = $this->resolveSelection($draft, $candidateStudent);

        // Aturan cicilan mengikuti PaymentInstallmentService (private = wajib lunas)
        $canInstallment = $this->payments->allowsInstallment(
            $isPrivate,
            $candidateStudent->program?->program_name,
            $level?->level_name,
        );

        // tagihan sebelum diskon = harga paket + biaya pendaftaran + biaya aktivitas
        $registrationFee = (float) Setting::get('registration_fee', 0);
        $activityFee     = (float) Setting::get('activity_fee', 0);
        $totalBill       = (float) $package->price + $registrationFee + $activityFee;

        return view('admin.calon-siswa.convert', compact(
            'candidateStudent',
            'package',
            'isPrivate',
            'canInstallment',
            'registrationFee',
            'activityFee',
            'totalBill'
        ))->with('step', 2);
    }

    /* =====================================================================
     |  STEP 2: submit, buat user, siswa, enrollment, pembayaran + bukti
     * ===================================================================== */
    public function store(Request $request, CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $draft = session(self::SESSION_KEY . $candidateStudent->id);

        if (! $draft) {
            return redirect()
                ->route('admin.calon-siswa.convert', $candidateStudent)
                ->with('error', 'Sesi habis, lengkapi data diri dan program lagi.');
        }

        $validated = $request->validate([
            'discount'       => 'nullable|numeric|min:0',
            'amount_paid'    => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:100',
            'payment_date'   => 'required|date|before_or_equal:today',
            'proof'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ], [
            'proof.required'             => 'Bukti pembayaran wajib diupload.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh di masa depan.',
        ]);

        [$isPrivate, $package, $level, $levelId] = $this->resolveSelection($draft, $candidateStudent);

        $registrationFee = (float) Setting::get('registration_fee', 0);
        $activityFee     = (float) Setting::get('activity_fee', 0);
        $discount        = round((float) ($validated['discount'] ?? 0), 2);
        $baseTotal       = (float) $package->price + $registrationFee + $activityFee;

        if ($discount >= $baseTotal) {
            throw ValidationException::withMessages([
                'discount' => 'Diskon harus lebih kecil dari total tagihan (Rp' . number_format($baseTotal, 0, ',', '.') . ').',
            ]);
        }

        $totalBill = $baseTotal - $discount;

        $canInstallment = $this->payments->allowsInstallment(
            $isPrivate,
            $candidateStudent->program?->program_name,
            $level?->level_name,
        );

        if (! $canInstallment && round((float) $validated['amount_paid'], 2) < round($totalBill, 2)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Paket ini wajib dibayar lunas sebelum program dimulai (Rp' . number_format($totalBill, 0, ',', '.') . '), tidak bisa dicicil/DP.',
            ]);
        }

        $proofPath = $request->file('proof')->store('bukti-pembayaran', 'public');

        try {
            $enrollment = DB::transaction(function () use ($validated, $draft, $candidateStudent, $isPrivate, $levelId, $proofPath, $registrationFee, $activityFee, $discount, $totalBill) {

                $studentLevel = Level::where('nama_level', 'Student')->firstOrFail();

                $user = User::create([
                    'level_id' => $studentLevel->id_level,
                    'username' => $this->generateUsername($candidateStudent->phone),
                    'password' => Hash::make('haoyou123'),
                    'status'   => 'Active',
                ]);

                $student = Student::create([
                    'candidate_student_id' => $candidateStudent->id,
                    'user_id'              => $user->id,
                    'current_level_id'     => $levelId,
                    'name'                 => $candidateStudent->name,
                    'points'               => 0,
                    'join_date'            => now(),
                    'status'               => 'Active',
                ]);

                $classId = $isPrivate ? null : ($draft['class_id'] ?? null);

                $enrollment = ClassEnrollment::create([
                    'student_id'         => $student->id,
                    'class_id'           => $classId,
                    'program_package_id' => $isPrivate ? null : $draft['program_package_id'],
                    'private_package_id' => $isPrivate ? $draft['private_package_id'] : null,
                    'enrollment_date'    => Carbon::parse($validated['payment_date'])->toDateString(),
                    'status'             => (! $isPrivate && ! $classId) ? 'Waiting Class' : 'Active',
                    // registration_fee dipakai service untuk total tagihan, activity_fee untuk formulir
                    'registration_fee' => $registrationFee,
                    'activity_fee'     => $activityFee,
                    'discount'         => $discount,
                ]);

                $amountPaid = (float) $validated['amount_paid'];

                // Service menghitung remaining_bill, status Paid/Partial, dan nomor invoice
                $this->payments->recordPayment(
                    $enrollment->load(['programPackage.program', 'privatePackage']),
                    round($amountPaid, 2) >= round($totalBill, 2) ? 'Lunas' : 'DP',
                    $amountPaid,
                    $validated['payment_method'],
                    $validated['payment_date'],
                    $proofPath, // disimpan ke kolom payment_proof_path
                );

                return $enrollment;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        session()->forget(self::SESSION_KEY . $candidateStudent->id);

        return redirect()->route('admin.calon-siswa.convert.step3', $enrollment);
    }

    /* =====================================================================
     |  STEP 3: Download invoice & formulir perjanjian
     * ===================================================================== */
    public function step3(ClassEnrollment $enrollment)
    {
        $enrollment->load(['student.candidateStudent', 'class', 'privatePackage']);
        $student = $enrollment->student;

        return view('admin.calon-siswa.convert', compact('enrollment', 'student'))->with('step', 3);
    }

    /* =====================================================================
     |  Masukkan siswa "Waiting Class" ke kelas (tidak berubah)
     * ===================================================================== */
    public function assignClass(Request $request, ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status !== 'Waiting Class', 404, 'Enrollment ini tidak sedang menunggu kelas.');

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        // pastikan kelas yang dipilih sesuai program_package & level enrollment ini
        $classBelongsToPackage = ClassModel::where('id', $validated['class_id'])
            ->where('program_package_id', $enrollment->program_package_id)
            ->when($enrollment->student?->current_level_id, fn ($q, $levelId) => $q->where('level_id', $levelId))
            ->exists();

        abort_unless($classBelongsToPackage, 422, 'Kelas tidak sesuai dengan paket program siswa ini.');

        $enrollment->update([
            'class_id' => $validated['class_id'],
            'status'   => 'Active',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Siswa berhasil dimasukkan ke kelas.');
    }

    /* =====================================================================
     |  Helper
     * ===================================================================== */

    /**
     * Validasi pilihan paket/level/kelas. Dipakai di step 1 dan step 2.
     *
     * @return array [$isPrivate, $package, $level, $levelId]
     */
    private function resolveSelection(array $data, CandidateStudent $candidateStudent): array
    {
        $isPrivate = $data['package_type'] === 'private';

        $package = $isPrivate
            ? PrivatePackage::findOrFail($data['private_package_id'])
            : ProgramPackage::findOrFail($data['program_package_id']);

        $level = null;
        $levelId = null;

        if (! $isPrivate) {
            $level = ProgramLevel::findOrFail($data['level_id']);

            if ((int) $level->program_id !== (int) $candidateStudent->program_id) {
                throw ValidationException::withMessages([
                    'level_id' => 'Level tidak sesuai dengan program calon siswa ini.',
                ]);
            }

            $levelId = $package->level_id ?: $level->id;

            if (! empty($data['class_id'])) {
                $classMatches = ClassModel::where('id', $data['class_id'])
                    ->where('program_package_id', $data['program_package_id'])
                    ->where('level_id', $levelId)
                    ->exists();

                if (! $classMatches) {
                    throw ValidationException::withMessages([
                        'class_id' => 'Kelas yang dipilih tidak sesuai dengan paket/level yang dipilih.',
                    ]);
                }
            }
        }

        return [$isPrivate, $package, $level, $levelId];
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

    private function programAllowsInstallment(ProgramLevel $level): bool
    {
        if (! str_starts_with($level->level_name, 'HSK ')) {
            return true;
        }

        $levelNumber = (int) trim(substr($level->level_name, 4));

        return $levelNumber >= 3;
    }

    private function recommendCategoryByAge(int $age): ?string
    {
        return match (true) {
            $age >= 3 && $age <= 6   => 'Maochong',
            $age >= 7 && $age <= 9   => 'Jianer',
            $age >= 10 && $age <= 14 => 'Hudie',
            $age >= 15               => 'Feixiang',
            default                  => null,
        };
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
}