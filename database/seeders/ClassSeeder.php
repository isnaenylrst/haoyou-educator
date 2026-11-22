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
use Illuminate\Support\Collection;

/**
 * Kelas dummy:
 *  - Daily Activity: beberapa level pertama dari tiap kategori (Anak / Dewasa sesuai kategori)
 *  - HSK Class: level 1-3 (kelas HSK 1 dibuat "Open" dan baru mulai 2 minggu lagi)
 *  - Private Exclusive (paket max_students > 1): satu kelas berjalan per paket
 *  - RIWAYAT: kelas Completed yang berakhir tersebar di HISTORY_MONTHS bulan terakhir,
 *    dipakai untuk data Retention Rate (jumlah kelas per bulan berbeda tiap guru).
 *
 * Jadwal mingguan dibuat oleh ClassScheduleSeeder (jalankan setelah seeder ini).
 * Aman dijalankan ulang: kelas dicari berdasarkan class_name.
 */
class ClassSeeder extends Seeder
{
    /** Berapa level pertama per kategori Daily Activity yang dibuatkan kelas. */
    private const LEVELS_PER_CATEGORY = 2;

    /** Berapa bulan ke belakang kelas riwayat dibuat (termasuk bulan ini). */
    private const HISTORY_MONTHS = 6;

    /** true = guru hanya boleh mengajar kelas yang mulai SETELAH tanggal bergabungnya. */
    private const RESPECT_JOIN_DATE = true;

    /** Rata-rata kelas riwayat yang berakhir per bulan, per guru (beban mengajar berbeda). */
    private const CLASSES_PER_MONTH = [
        'Fitri Maulidah'                  => 3,
        'Teresa Liaunardo Tju'            => 3,
        'Natalia Repiuli Dertina Sitorus' => 2,
        'Kezia Sophi Adventa Ratta'       => 2,
        'Nathania Jovita'                 => 2,
        'Vanessa Tan'                     => 2,
        'Marsya Amelia'                   => 1,
        'Dwi Ayu Wulandari'               => 1,
    ];

    private const DEFAULT_PER_MONTH = 1;

    public function run(): void
    {
        $teachers = Teacher::where('status', 'Active')
            ->orderBy('id')
            ->get(['id', 'name', 'join_date']);

        if ($teachers->isEmpty()) {
            $this->command?->warn('ClassSeeder dilewati: belum ada guru aktif. Jalankan TeacherSeeder dulu.');

            return;
        }

        $today   = Carbon::today();
        $n       = 0;   // penghitung untuk membagi guru (round robin) dan memvariasikan data
        $catalog = [];  // daftar "cetakan" kelas reguler, dipakai lagi untuk kelas riwayat

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

                    $identity = [
                        'program_package_id' => $package->id,
                        'private_package_id' => null,
                        'level_id'           => $level->id,
                        'class_name'         => "{$label} - Kelas A",
                    ];

                    $this->createClass($identity, $package, $teachers, $n, $today, false);
                    $catalog[] = ['identity' => $identity, 'package' => $package, 'label' => $label];

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

                $label = "HSK Class - {$level->level_name}";

                $identity = [
                    'program_package_id' => $package->id,
                    'private_package_id' => null,
                    'level_id'           => $level->id,
                    'class_name'         => "{$label} - Kelas A",
                ];

                $this->createClass($identity, $package, $teachers, $n, $today, $i === 0);
                $catalog[] = ['identity' => $identity, 'package' => $package, 'label' => $label];

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
            ], $package, $teachers, $n, $today, false);

            $n++;
        }

        /* ---------------- Riwayat kelas (untuk Retention Rate) ---------------- */
        $this->seedHistory($teachers, $catalog, $today);
    }

    /**
     * Kelas Completed yang berakhir di bulan-bulan sebelumnya.
     * Jumlahnya per guru per bulan berbeda, dengan peluang 20% bulan itu libur satu kelas.
     */
    private function seedHistory(Collection $teachers, array $catalog, Carbon $today): void
    {
        if (empty($catalog)) {
            return;
        }

        $counter = 0;
        $cutoff  = $today->copy()->subDay(); // kelas riwayat harus sudah berakhir sebelum hari ini

        foreach ($teachers as $teacher) {
            $perMonth = self::CLASSES_PER_MONTH[$teacher->name] ?? self::DEFAULT_PER_MONTH;
            $joined   = $teacher->join_date ? Carbon::parse($teacher->join_date)->startOfDay() : null;

            for ($back = self::HISTORY_MONTHS - 1; $back >= 0; $back--) {
                $monthStart = $today->copy()->startOfMonth()->subMonths($back);

                // hari terakhir yang boleh dipakai di bulan ini
                $lastDay = $monthStart->daysInMonth;

                if ($monthStart->isSameMonth($cutoff)) {
                    $lastDay = $cutoff->day;
                } elseif ($monthStart->gt($cutoff)) {
                    continue;
                }

                $count = $perMonth;

                if (mt_rand(1, 100) <= 20) {
                    $count = max(0, $count - 1);
                }

                for ($i = 0; $i < $count; $i++) {
                    $blueprint = $catalog[array_rand($catalog)];
                    $package   = $blueprint['package'];
                    $months    = max((int) ($package->duration_months ?: 1), 1);

                    $end   = $monthStart->copy()->day(mt_rand(1, $lastDay));
                    $start = $end->copy()->subMonths($months);

                    if (self::RESPECT_JOIN_DATE && $joined && $start->lt($joined)) {
                        continue;
                    }

                    $counter++;
                    $name = "{$blueprint['label']} - Kelas {$end->format('m/Y')} #{$counter}";

                    ClassModel::unguarded(fn () => ClassModel::firstOrCreate(
                        ['class_name' => $name],
                        [
                            'program_package_id'   => $blueprint['identity']['program_package_id'],
                            'private_package_id'   => null,
                            'level_id'             => $blueprint['identity']['level_id'],
                            'teacher_id'           => $teacher->id,
                            'delivery_mode'        => $counter % 4 === 3 ? 'Online' : 'Offline',
                            'start_date'           => $start->toDateString(),
                            'end_date'             => $end->toDateString(),
                            'capacity'             => (int) ($package->max_students ?: 1),
                            'status'               => 'Completed',
                            'teacher_response'     => 'Accepted',
                            'teacher_responded_at' => $start->copy()->subDays(3),
                        ]
                    ));
                }
            }
        }

        $this->command?->info("Kelas riwayat dibuat: {$counter}.");
    }

    /** Pilih guru bergilir, tapi hanya yang sudah bergabung saat kelas mulai. */
    private function pickTeacher(Collection $teachers, int $n, Carbon $start): int
    {
        $eligible = self::RESPECT_JOIN_DATE
            ? $teachers->filter(fn ($t) => ! $t->join_date || Carbon::parse($t->join_date)->lte($start))
            : $teachers;

        if ($eligible->isEmpty()) {
            $eligible = $teachers;
        }

        return $eligible->values()[$n % $eligible->count()]->id;
    }

    /**
     * @param  array  $identity  program_package_id, private_package_id, level_id, class_name
     * @param  mixed  $package   ProgramPackage atau PrivatePackage (sumber kapasitas & durasi)
     */
    private function createClass(array $identity, $package, Collection $teachers, int $n, Carbon $today, bool $open): void
    {
        $months = max((int) ($package->duration_months ?: 1), 1);

        // Kelas "Open" belum mulai. Kelas "Running" dibuat sedang berjalan di tengah jalan
        // (sekitar 25% / 40% / 60% pertemuan sudah lewat), supaya status Running sesuai
        // dengan jumlah pertemuan yang sudah terjadi (rumus per-minggu sama dengan ClassScheduleSeeder).
        $perWeek    = (int) max(1, min(3, round(((int) $package->total_meetings) / ($months * 4))));
        $totalWeeks = (int) ceil(max((int) $package->total_meetings, 1) / $perWeek);
        $elapsed    = max(1, (int) round($totalWeeks * [0.25, 0.4, 0.6][$n % 3]));

        $start = $open
            ? $today->copy()->addWeeks(2)
            : $today->copy()->subWeeks($elapsed);

        $teacherId = $this->pickTeacher($teachers, $n, $start);

        // unguarded: teacher_response dkk. tidak bergantung pada $fillable ClassModel
        ClassModel::unguarded(fn () => ClassModel::firstOrCreate(
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
                // Kelas Open: guru baru "diberi" kelas dan harus konfirmasi. Kelas Running: sudah diterima guru.
                'teacher_response'     => $open ? 'Pending' : 'Accepted',
                'teacher_responded_at' => $open ? null : $start->copy()->subDays(3),
            ]
        ));
    }
}