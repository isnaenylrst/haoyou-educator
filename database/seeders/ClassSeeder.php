<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Kelas dummy:
 *  - Daily Activity: beberapa level pertama dari tiap kategori (Anak / Dewasa sesuai kategori)
 *  - HSK Class: level 1-3 (kelas HSK 1 dibuat "Open" dan baru mulai 2 minggu lagi)
 *  - Private Exclusive (paket max_students > 1): satu kelas berjalan per paket
 * Kelas private 1-on-1 tidak di-seed karena dibuat otomatis saat calon siswa dikonversi.
 *
 * Jadwal mingguan dibuat oleh ClassScheduleSeeder (jalankan setelah seeder ini).
 * Aman dijalankan ulang: kelas dicari berdasarkan class_name.
 */
class ClassSeeder extends Seeder
{
    /** Berapa level pertama per kategori Daily Activity yang dibuatkan kelas. */
    private const LEVELS_PER_CATEGORY = 2;

    public function run(): void
    {
        $teacherIds = Teacher::where('status', 'Active')->orderBy('id')->pluck('id')->values();

        if ($teacherIds->isEmpty()) {
            $this->command?->warn('ClassSeeder dilewati: belum ada guru aktif. Jalankan TeacherSeeder dulu.');

            return;
        }

        $today = Carbon::today();
        $n = 0; // penghitung untuk membagi guru (round robin) dan memvariasikan data

        /* ---------------- Daily Activity ---------------- */
        $daily = Program::where('program_name', 'Daily Activity')->first();

        if ($daily) {
            $levelsByCategory = ProgramLevel::where('program_id', $daily->id)
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category_id');

            foreach ($levelsByCategory as $categoryId => $levels) {
                $categoryName = $categoryId
                    ? ProgramCategory::whereKey($categoryId)->value('category_name')
                    : null;

                // Feixiang (15+ tahun) memakai paket Dewasa, sisanya Anak
                $audience = $categoryName === 'Feixiang' ? 'Dewasa' : 'Anak';

                foreach ($levels->take(self::LEVELS_PER_CATEGORY) as $level) {
                    $perWeek = $n % 2 === 0 ? '1x' : '2x';

                    $package = ProgramPackage::where('program_id', $daily->id)
                        ->where('package_name', 'like', "Regular Class {$audience} - {$perWeek}/Minggu%")
                        ->first()
                        ?? ProgramPackage::where('program_id', $daily->id)
                            ->where('package_name', 'like', "Regular Class {$audience}%")
                            ->first();

                    if (! $package) {
                        continue;
                    }

                    $label = trim(($categoryName ? $categoryName . ' ' : '') . $level->level_name);

                    $this->createClass([
                        'program_package_id' => $package->id,
                        'private_package_id' => null,
                        'level_id'           => $level->id,
                        'class_name'         => "{$label} - Kelas A",
                    ], $package, $teacherIds[$n % $teacherIds->count()], $n, $today, false);

                    $n++;
                }
            }
        }

        /* ---------------- HSK Class (level 1-3) ---------------- */
        $hsk = Program::where('program_name', 'HSK')->first();

        if ($hsk) {
            $hskCategoryId = ProgramCategory::where('program_id', $hsk->id)
                ->where('category_name', 'HSK Class')
                ->value('id');

            $packages = ProgramPackage::where('program_id', $hsk->id)
                ->where('category_id', $hskCategoryId)
                ->orderBy('level_id')
                ->take(3)
                ->get();

            foreach ($packages as $i => $package) {
                $level = ProgramLevel::find($package->level_id);

                if (! $level) {
                    continue;
                }

                $this->createClass([
                    'program_package_id' => $package->id,
                    'private_package_id' => null,
                    'level_id'           => $level->id,
                    'class_name'         => "HSK Class - {$level->level_name} - Kelas A",
                ], $package, $teacherIds[$n % $teacherIds->count()], $n, $today, $i === 0);

                $n++;
            }
        }

        /* ---------------- Private Exclusive (kelas gabungan) ---------------- */
        $exclusivePackages = PrivatePackage::where('is_active', true)
            ->where('max_students', '>', 1)
            ->orderBy('id')
            ->get();

        foreach ($exclusivePackages as $package) {
            $this->createClass([
                'program_package_id' => null,
                'private_package_id' => $package->id,
                'level_id'           => null,
                'class_name'         => "Exclusive - {$package->package_name}",
            ], $package, $teacherIds[$n % $teacherIds->count()], $n, $today, false);

            $n++;
        }
    }

    /**
     * @param  array  $identity  program_package_id, private_package_id, level_id, class_name
     * @param  mixed  $package   ProgramPackage atau PrivatePackage (sumber kapasitas & durasi)
     */
    private function createClass(array $identity, $package, int $teacherId, int $n, Carbon $today, bool $open): void
    {
        // Kelas "Open" belum mulai; sisanya sudah berjalan 4-8 minggu
        $start = $open
            ? $today->copy()->addWeeks(2)
            : $today->copy()->subWeeks(8 - ($n % 3) * 2);

        $months = max((int) ($package->duration_months ?: 1), 1);

        ClassModel::firstOrCreate(
            ['class_name' => $identity['class_name']],
            [
                'program_package_id' => $identity['program_package_id'],
                'private_package_id' => $identity['private_package_id'],
                'level_id'           => $identity['level_id'],
                'teacher_id'         => $teacherId,
                'delivery_mode'      => $n % 4 === 3 ? 'Online' : 'Offline',
                'start_date'         => $start->toDateString(),
                'end_date'           => $start->copy()->addMonths($months)->toDateString(),
                'capacity'           => (int) ($package->max_students ?: 1),
                'status'             => $open ? 'Open' : 'Running',
            ]
        );
    }
}