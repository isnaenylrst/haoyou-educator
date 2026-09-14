<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\Payment;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder siswa DUMMY (bukan dari CSV) untuk keperluan development/testing.
 *
 * Membuat sejumlah siswa acak yang tersebar di:
 *  - Program Reguler Daily Activity (dengan kelas ATAU tanpa kelas -> status "Waiting Class")
 *  - Program Reguler HSK
 *  - Private
 *
 * Setiap siswa dapat:
 *  - 1 akun User (level "Student")
 *  - 1 baris Student (current_level_id diisi kalau relevan, null untuk Private)
 *  - 1 ClassEnrollment (program_package_id ATAU private_package_id, class_id kalau ada kelas)
 *  - 1 Payment tahap DP (Paid atau Partial secara acak)
 *
 * WAJIB DIJALANKAN SETELAH:
 *   LevelSeeder -> ProgramSeeder -> ProgramCategoryLevelSeeder
 *   -> ProgramPackageSeeder -> PrivatePackageSeeder
 * (dan idealnya setelah ada beberapa baris `classes` berstatus Open/Running,
 * supaya sebagian siswa reguler bisa langsung dapat kelas).
 */
class StudentSeeder extends Seeder
{
    /** Jumlah siswa dummy yang mau dibuat */
    private int $totalStudents = 30;

    /** Peluang siswa masuk Private vs Reguler (dalam persen, dari 100) */
    private int $privateChancePercent = 25;

    /**
     * Dari siswa yang masuk Reguler, peluang dia langsung dapat kelas
     * (sisanya jadi "Waiting Class" untuk menguji alur assign-kelas belakangan)
     */
    private int $directClassChancePercent = 70;

    private array $paymentMethods = ['Transfer Bank', 'Cash', 'QRIS', 'E-Wallet'];

    private ?int $studentLevelId = null;

    public function run(): void
    {
        $this->studentLevelId = Level::where('nama_level', 'Student')->value('id_level');

        if (!$this->studentLevelId) {
            $this->command->error('Level "Student" belum ada di tabel levels. Jalankan seeder Level dulu.');
            return;
        }

        $programPackages = ProgramPackage::all();
        $privatePackages = PrivatePackage::where('is_active', true)->get();

        if ($programPackages->isEmpty() && $privatePackages->isEmpty()) {
            $this->command->error('Belum ada program_packages atau private_packages. Jalankan ProgramPackageSeeder/PrivatePackageSeeder dulu.');
            return;
        }

        // Cache kelas Open/Running per program_package_id, supaya query tidak berulang
        $classesByPackage = ClassModel::whereIn('status', ['Open', 'Running'])
            ->get()
            ->groupBy('program_package_id');

        $created = 0;
        $waitingClassCount = 0;

        for ($i = 1; $i <= $this->totalStudents; $i++) {
            $isPrivate = $privatePackages->isNotEmpty()
                && (random_int(1, 100) <= $this->privateChancePercent || $programPackages->isEmpty());

            if ($isPrivate) {
                $this->createPrivateStudent($privatePackages, $i);
            } else {
                $waitingClassCount += $this->createRegularStudent($programPackages, $classesByPackage, $i) ? 1 : 0;
            }

            $created++;
        }

        $this->command->info("Selesai: {$created} siswa dummy berhasil dibuat ({$waitingClassCount} di antaranya berstatus 'Waiting Class').");
    }

    private function createPrivateStudent($privatePackages, int $seq): void
    {
        $package = $privatePackages->random();
        $name = fake()->name();

        $user = $this->createUserAccount($name, $seq);

        $student = Student::create([
            'candidate_student_id' => null,
            'user_id' => $user->id,
            'current_level_id' => null, // Private tidak memakai konsep level
            'name' => $name,
            'points' => random_int(0, 50),
            'join_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'Active',
        ]);

        $enrollment = ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => null,
            'program_package_id' => null,
            'private_package_id' => $package->id,
            'enrollment_date' => $student->join_date,
            'status' => 'Active',
        ]);

        $this->createDpPayment($enrollment, $package->price);
    }

    /**
     * @return bool true kalau siswa ini jadi 'Waiting Class' (tidak dapat kelas langsung)
     */
    private function createRegularStudent($programPackages, $classesByPackage, int $seq): bool
    {
        $package = $programPackages->random();
        $name = fake()->name();

        $user = $this->createUserAccount($name, $seq);

        $currentLevelId = $this->resolveCurrentLevelId($package);

        $availableClasses = $classesByPackage->get($package->id, collect());
        $getsClassDirectly = $availableClasses->isNotEmpty()
            && random_int(1, 100) <= $this->directClassChancePercent;

        $classId = $getsClassDirectly ? $availableClasses->random()->id : null;
        $isWaitingClass = !$getsClassDirectly;

        $student = Student::create([
            'candidate_student_id' => null,
            'user_id' => $user->id,
            'current_level_id' => $currentLevelId,
            'name' => $name,
            'points' => random_int(0, 50),
            'join_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'Active',
        ]);

        $enrollment = ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classId,
            'program_package_id' => $package->id,
            'private_package_id' => null,
            'enrollment_date' => $student->join_date,
            'status' => $isWaitingClass ? 'Waiting Class' : 'Active',
        ]);

        $this->createDpPayment($enrollment, $package->price);

        return $isWaitingClass;
    }

    private function createUserAccount(string $name, int $seq): User
    {
        return User::create([
            'level_id' => $this->studentLevelId,
            'username' => $this->generateUsername($name, $seq),
            'password' => Hash::make('haoyou123'),
            'status' => 'Active',
        ]);
    }

    private function generateUsername(string $name, int $seq): string
    {
        $slug = Str::slug($name, '');
        $slug = $slug !== '' ? strtolower($slug) : 'siswa';

        $username = $slug . $seq;
        $base = $username;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }

    /**
     * Tentukan current_level_id siswa berdasarkan paket yang diambil:
     *  - HSK: paket sudah punya level_id sendiri -> pakai langsung
     *  - Daily Activity: paket tidak terikat level, jadi pilih kategori+level acak
     *    dari kategori-kategori di bawah program Daily Activity
     */
    private function resolveCurrentLevelId(ProgramPackage $package): ?int
    {
        if ($package->level_id) {
            return $package->level_id;
        }

        $categories = ProgramCategory::where('program_id', $package->program_id)->pluck('id');

        if ($categories->isEmpty()) {
            return null;
        }

        return ProgramLevel::whereIn('category_id', $categories)
            ->inRandomOrder()
            ->value('id');
    }

    private function createDpPayment(ClassEnrollment $enrollment, float $totalBill): void
    {
        $isFullyPaid = random_int(1, 100) <= 60; // 60% lunas, sisanya DP sebagian
        $amountPaid = $isFullyPaid ? $totalBill : round($totalBill * (random_int(30, 70) / 100), -3);
        $remaining = max($totalBill - $amountPaid, 0);

        Payment::create([
            'enrollment_id' => $enrollment->id,
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'invoice_file_path' => null,
            'payment_stage' => 'DP',
            'total_bill' => $totalBill,
            'amount_paid' => $amountPaid,
            'remaining_bill' => $remaining,
            'payment_method' => fake()->randomElement($this->paymentMethods),
            'payment_date' => $enrollment->enrollment_date,
            'payment_proof_path' => null,
            'status' => $remaining <= 0 ? 'Paid' : 'Partial',
        ]);
    }
}