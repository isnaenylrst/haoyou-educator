<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\PrivatePackage;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Siswa dummy + enrollment-nya.
 *
 * Jalankan SETELAH ClassSeeder (butuh kelas yang sudah ada) dan SEBELUM ClassScheduleSeeder
 * (kelas private 1-on-1 dibuat di sini, jadwalnya diisi ClassScheduleSeeder).
 * Pembayaran TIDAK dibuat di sini: PaymentSeeder yang membuatnya, supaya total tagihan
 * konsisten dengan PaymentInstallmentService (harga sesuai skema + biaya - diskon).
 */
class StudentSeeder extends Seeder
{
    /** Jumlah siswa dummy yang mau dibuat */
    private int $totalStudents = 30;

    /** Peluang siswa masuk Private vs Reguler (dalam persen, dari 100) */
    private int $privateChancePercent = 25;

    /**
     * Dari siswa Reguler, peluang dia langsung dimasukkan ke kelas yang masih punya kursi
     * (sisanya jadi "Waiting Class" untuk menguji alur assign-kelas belakangan)
     */
    private int $directClassChancePercent = 70;

    private ?int $studentLevelId = null;
    private float $registrationFee = 0;
    private float $activityFee = 0;

    public function run(): void
    {
        $this->studentLevelId = Level::where('nama_level', 'Student')->value('id_level');

        if (! $this->studentLevelId) {
            $this->command->error('Level "Student" belum ada di tabel levels. Jalankan seeder Level dulu.');

            return;
        }

        $programPackages = ProgramPackage::all();
        $privatePackages = PrivatePackage::where('is_active', true)->get();

        if ($programPackages->isEmpty() && $privatePackages->isEmpty()) {
            $this->command->error('Belum ada program_packages atau private_packages. Jalankan ProgramPackageSeeder/PrivatePackageSeeder dulu.');

            return;
        }

        $this->registrationFee = (float) Setting::get('registration_fee', 0);
        $this->activityFee     = (float) Setting::get('activity_fee', 0);

        $teacherIds = Teacher::where('status', 'Active')->orderBy('id')->pluck('id')->values();

        // Kelas yang sudah dibuat ClassSeeder, lengkap dengan jumlah kursi terpakai,
        // supaya classes.capacity dihormati dan kelas penuh tidak diisi lagi.
        $regularClasses   = $this->loadClasses('program_package_id');
        $exclusiveClasses = $this->loadClasses('private_package_id');

        $created = 0;
        $waitingClassCount = 0;
        $placedCount = 0;

        for ($i = 1; $i <= $this->totalStudents; $i++) {
            $isPrivate = $privatePackages->isNotEmpty()
                && (random_int(1, 100) <= $this->privateChancePercent || $programPackages->isEmpty());

            $placed = $isPrivate
                ? $this->createPrivateStudent($privatePackages, $i, $teacherIds, $exclusiveClasses)
                : $this->createRegularStudent($programPackages, $regularClasses, $i);

            $placed ? $placedCount++ : $waitingClassCount++;
            $created++;
        }

        $this->command->info("Selesai: {$created} siswa dummy dibuat ({$placedCount} masuk kelas, {$waitingClassCount} berstatus 'Waiting Class').");
    }

    /** Kelas Open/Running yang terikat ke kolom paket tertentu, beserta kursi terpakai. */
    private function loadClasses(string $packageColumn)
    {
        return ClassModel::whereNotNull($packageColumn)
            ->whereIn('status', ['Open', 'Running'])
            ->withCount([
                'enrollments as seats_taken' => fn ($q) => $q->whereIn('status', ClassModel::SEAT_STATUSES),
            ])
            ->get();
    }

    /**
     * @return bool true kalau siswa langsung masuk kelas
     */
    private function createPrivateStudent($privatePackages, int $seq, $teacherIds, $exclusiveClasses): bool
    {
        $package = $privatePackages->random();
        $name = fake()->name();

        $user = $this->createUserAccount($name, $seq);

        // Join 2 bulan terakhir supaya tumpang tindih dengan jurnal & absensi dummy
        $joinDate = fake()->dateTimeBetween('-2 months', 'now');

        $classId = null;

        if ((int) $package->max_students > 1) {
            // Private gabungan (Exclusive): gabung kelas yang masih punya kursi
            $class = $exclusiveClasses->first(
                fn ($c) => (int) $c->private_package_id === (int) $package->id && $c->seats_taken < $c->capacity
            );

            if ($class) {
                $classId = $class->id;
                $class->seats_taken++;
            }
        } elseif ($teacherIds->isNotEmpty()) {
            // Private 1-on-1: kelas pribadi dibuat langsung (sama seperti alur convert)
            $start = Carbon::instance($joinDate)->startOfDay();

            $class = ClassModel::create([
                'program_package_id' => null,
                'private_package_id' => $package->id,
                'level_id'           => null,
                'teacher_id'         => $teacherIds[$seq % $teacherIds->count()],
                'class_name'         => "Private - {$name}",
                'delivery_mode'      => $seq % 4 === 3 ? 'Online' : 'Offline',
                'start_date'         => $start->toDateString(),
                'end_date'           => $start->copy()->addMonths(max((int) ($package->duration_months ?: 1), 1))->toDateString(),
                'capacity'           => (int) ($package->max_students ?: 1),
                'status'             => 'Running',
            ]);

            $classId = $class->id;
        }

        $student = Student::create([
            'candidate_student_id' => null,
            'user_id' => $user->id,
            'current_level_id' => null, // Private tidak memakai konsep level
            'name' => $name,
            'points' => random_int(0, 50),
            'join_date' => $joinDate,
            'status' => 'Active',
        ]);

        ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classId,
            'program_package_id' => null,
            'private_package_id' => $package->id,
            'enrollment_date' => $student->join_date,
            'status' => $classId ? 'Active' : 'Waiting Class',
            'registration_fee' => $this->registrationFee,
            'activity_fee' => $this->activityFee,
            'discount' => 0,
        ]);

        return $classId !== null;
    }

    /**
     * @return bool true kalau siswa langsung masuk kelas
     */
    private function createRegularStudent($programPackages, $regularClasses, int $seq): bool
    {
        $name = fake()->name();
        $user = $this->createUserAccount($name, $seq);

        // Kelas yang masih punya kursi
        $availableClasses = $regularClasses->filter(fn ($class) => $class->seats_taken < $class->capacity);

        $getsClassDirectly = $availableClasses->isNotEmpty()
            && random_int(1, 100) <= $this->directClassChancePercent;

        $classId = null;
        $joinDate = fake()->dateTimeBetween('-6 months', 'now');

        if ($getsClassDirectly) {
            // Paket dan level mengikuti kelas yang dipilih, supaya siswa selalu cocok dengan kelasnya
            $chosenClass = $availableClasses->random();
            $package = $programPackages->firstWhere('id', $chosenClass->program_package_id);

            if (! $package) {
                $getsClassDirectly = false;
            } else {
                $classId = $chosenClass->id;
                $currentLevelId = $chosenClass->level_id ?: $this->resolveCurrentLevelId($package);

                // Update hitungan di collection supaya siswa berikutnya tidak mengisi kelas yang sudah penuh
                $chosenClass->seats_taken++;

                // Bergabung mulai 2 minggu sebelum kelas dimulai sampai hari ini
                $classStart = $chosenClass->start_date ? Carbon::parse($chosenClass->start_date) : Carbon::today();
                $from = $classStart->lte(Carbon::today()) ? $classStart->copy()->subWeeks(2) : Carbon::today()->subWeeks(2);
                $joinDate = fake()->dateTimeBetween($from, 'now');
            }
        }

        if (! $getsClassDirectly) {
            $package = $programPackages->random();
            $currentLevelId = $this->resolveCurrentLevelId($package);
        }

        $student = Student::create([
            'candidate_student_id' => null,
            'user_id' => $user->id,
            'current_level_id' => $currentLevelId,
            'name' => $name,
            'points' => random_int(0, 50),
            'join_date' => $joinDate,
            'status' => 'Active',
        ]);

        ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classId,
            'program_package_id' => $package->id,
            'private_package_id' => null,
            'enrollment_date' => $student->join_date,
            'status' => $getsClassDirectly ? 'Active' : 'Waiting Class',
            'registration_fee' => $this->registrationFee,
            'activity_fee' => $this->activityFee,
            'discount' => 0,
        ]);

        return $getsClassDirectly;
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

    private function resolveCurrentLevelId(ProgramPackage $package): ?int
    {
        if ($package->level_id) {
            return $package->level_id;
        }

        $isAnak = str_contains($package->package_name, 'Anak');
        $categoryNames = $isAnak ? ['Maochong', 'Jianer'] : ['Hudie', 'Feixiang'];

        $categories = ProgramCategory::where('program_id', $package->program_id)
            ->whereIn('category_name', $categoryNames)
            ->pluck('id');

        if ($categories->isEmpty()) {
            return null;
        }

        return ProgramLevel::whereIn('category_id', $categories)
            ->inRandomOrder()
            ->value('id');
    }
}