<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassEnrollmentSeeder extends Seeder
{
    /** Peluang siswa lanjut ke program berikutnya, per guru (nama => 0..1). */
    private const CONTINUE_RATE = [
        'Teresa Liaunardo Tju'            => 0.80,
        'Fitri Maulidah'                  => 0.75,
        'Kezia Sophi Adventa Ratta'       => 0.70,
        'Vanessa Tan'                     => 0.60,
        'Nathania Jovita'                 => 0.55,
        'Natalia Repiuli Dertina Sitorus' => 0.45,
        'Marsya Amelia'                   => 0.30,
        'Dwi Ayu Wulandari'               => 0.20,
    ];

    private const DEFAULT_RATE = 0.5;

    /** Porsi siswa bebas yang boleh dipakai kelas riwayat; sisanya untuk kelas yang sedang berjalan. */
    private const HISTORY_SHARE = 0.65;

    /** Siswa baru yang dibuat seeder ini (kalau siswa bebas tidak cukup). */
    private int $createdStudents = 0;

    public function run(): void
    {
        if (ClassModel::count() === 0) {
            $this->command->error('Belum ada data classes. Jalankan seeder kelas dulu.');
            return;
        }

        $this->seedHistory();
        $this->seedCurrent();
    }

    /* ------------------------------------------------------------------
     | 1. Kelas yang sudah selesai: daftar, selesai, lanjut / berhenti
     ------------------------------------------------------------------ */
    private function seedHistory(): void
    {
        $classes = ClassModel::where('status', 'Completed')
            ->whereNotNull('teacher_id')
            ->whereDoesntHave('enrollments')       // aman dijalankan ulang
            ->orderBy('end_date')
            ->get();

        if ($classes->isEmpty()) {
            $this->command->warn('Tidak ada kelas Completed. Jalankan ClassSeeder versi baru.');
            return;
        }

        $teacherNames = Teacher::pluck('name', 'id');

        $all  = Student::doesntHave('enrollments')->pluck('id')->shuffle();
        $pool = $all->take((int) ceil($all->count() * self::HISTORY_SHARE))->all();

        $retired  = [];   // siswa yang memutuskan berhenti
        $freeFrom = [];   // siswa => tanggal kelas terakhirnya selesai
        $pending  = [];   // siswa => enrollment terakhir yang ingin lanjut tapi belum punya lanjutan

        $made = 0;

        foreach ($classes as $class) {
            $start = Carbon::parse($class->start_date)->startOfDay();
            $end   = Carbon::parse($class->end_date)->startOfDay();

            $rate     = self::CONTINUE_RATE[$teacherNames[$class->teacher_id] ?? ''] ?? self::DEFAULT_RATE;
            $capacity = max((int) $class->capacity, 1);
            $target   = (int) max(1, round($capacity * fake()->randomFloat(2, 0.5, 1)));

            // siswa yang boleh ikut: belum berhenti dan sudah bebas dari kelas sebelumnya
            $returning = [];
            $fresh     = [];

            foreach ($pool as $id) {
                if (isset($retired[$id])) {
                    continue;
                }

                if (isset($freeFrom[$id]) && $freeFrom[$id] > $start->toDateString()) {
                    continue;
                }

                if (isset($pending[$id])) {
                    $returning[] = $id;
                } else {
                    $fresh[] = $id;
                }
            }

            shuffle($returning);
            shuffle($fresh);

            // siswa yang ingin lanjut diprioritaskan separuh kursi, sisanya siswa baru
            $picked = array_merge(
                array_slice($returning, 0, (int) ceil($target / 2)),
                $fresh
            );
            $picked = array_slice($picked, 0, $target);

            if (count($picked) < $target) {
                $rest   = array_values(array_diff($returning, $picked));
                $picked = array_merge($picked, array_slice($rest, 0, $target - count($picked)));
            }

            // masih kurang: buat siswa baru supaya kelas riwayat tidak kosong
            while (count($picked) < $target) {
                $newId    = $this->createStudent();
                $pool[]   = $newId;
                $picked[] = $newId;
            }

            foreach ($picked as $studentId) {
                $enrollment = ClassEnrollment::factory()->create([
                    'student_id'         => $studentId,
                    'class_id'           => $class->id,
                    'program_package_id' => $class->program_package_id,
                    'private_package_id' => $class->private_package_id,
                    'enrollment_date'    => $start->copy()->subDays(mt_rand(0, 7))->toDateString(),
                    'status'             => 'Completed',
                ]);
                $made++;

                // enrollment ini sendiri menjadi "lanjutan" dari kelas sebelumnya (kalau ada)
                unset($pending[$studentId]);

                // kelas selesai: lanjut atau berhenti?
                if (mt_rand() / mt_getrandmax() < $rate) {
                    $pending[$studentId]  = $enrollment;
                    $freeFrom[$studentId] = $end->toDateString();
                } else {
                    $retired[$studentId] = true;
                    unset($freeFrom[$studentId]);
                }
            }
        }

        // Yang ingin lanjut tapi belum dapat kelas berikutnya: jadi Waiting Class
        $waiting = 0;

        foreach ($pending as $studentId => $enrollment) {
            $date = Carbon::parse($freeFrom[$studentId])->addDays(mt_rand(-10, 21));

            if ($date->isFuture()) {
                $date = today();
            }

            $next = $enrollment->replicate();
            $next->class_id        = null;
            $next->status          = 'Waiting Class';
            $next->enrollment_date = $date->toDateString();
            $next->notes           = null;
            $next->save();
            $waiting++;
        }

        $this->command->info(
            "Riwayat: {$made} enrollment di kelas selesai, {$waiting} lanjutan Waiting Class, "
            . "{$this->createdStudents} siswa baru dibuat."
        );
    }

    /* ------------------------------------------------------------------
     | 2. Siswa yang tersisa masuk ke kelas yang masih berjalan / dibuka
     ------------------------------------------------------------------ */
    private function seedCurrent(): void
    {
        $classes = ClassModel::withCount([
            'enrollments as active_enrollments_count' => fn ($q) => $q->whereIn('status', ['Active', 'Completed']),
        ])->where('status', '!=', 'Completed')->get();

        if ($classes->isEmpty()) {
            $this->command->warn('Tidak ada kelas Running/Open untuk siswa yang tersisa.');
            return;
        }

        $studentIds = Student::doesntHave('enrollments')->pluck('id');

        foreach ($studentIds as $studentId) {
            $availableClasses = $classes->filter(
                fn ($class) => $class->active_enrollments_count < $class->capacity
            );

            if ($availableClasses->isEmpty()) {
                $this->command->warn('Semua kelas sudah penuh, sisa siswa tidak ikut di-enroll.');
                break;
            }

            $class = $availableClasses->random();

            // enrollment_date di antara kelas mulai dan hari ini (konsisten dengan AttendanceSeeder)
            $rangeStart = Carbon::parse($class->start_date);

            if ($rangeStart->isFuture()) {
                $rangeStart = now();
            }

            $enrollmentDate = fake()->dateTimeBetween($rangeStart, now())->format('Y-m-d');

            ClassEnrollment::factory()->create([
                'student_id'         => $studentId,
                'class_id'           => $class->id,
                'program_package_id' => $class->program_package_id,
                'private_package_id' => $class->private_package_id,
                'enrollment_date'    => $enrollmentDate,
            ]);

            $class->active_enrollments_count++;
        }
    }

    /* ------------------------------------------------------------------
     | Pembuat siswa baru (akun user + calon siswa + siswa)
     ------------------------------------------------------------------ */

    private function createStudent(): int
    {
        $studentLevelId = DB::table('levels')->where('nama_level', 'Student')->value('id_level');

        $user = $this->makeWithDefaults(User::class, 'users', $studentLevelId ? ['level_id' => $studentLevelId] : []);

        $attrs = ['user_id' => $user->id];
        $name  = fake()->name();

        if (Schema::hasColumn('students', 'candidate_student_id')) {
            $candidate = $this->makeWithDefaults(CandidateStudent::class, 'candidate_students');
            $attrs['candidate_student_id'] = $candidate->id;
            $name = $candidate->name ?? $name;
        }

        if (Schema::hasColumn('students', 'name')) {
            $attrs['name'] = $name;
        }

        $student = $this->makeWithDefaults(Student::class, 'students', $attrs);
        $this->createdStudents++;

        return $student->id;
    }

    /**
     * Buat model lewat factory, lalu isi otomatis kolom wajib (NOT NULL tanpa default)
     * yang masih kosong. Nilai dari factory dan $attrs tidak pernah ditimpa.
     */
    private function makeWithDefaults(string $modelClass, string $table, array $attrs = [])
    {
        $model = $modelClass::factory()->make($attrs);

        foreach (Schema::getColumns($table) as $col) {
            $name = $col['name'];

            if (
                $col['auto_increment']
                || $col['nullable']
                || $col['default'] !== null
                || in_array($name, ['created_at', 'updated_at', 'deleted_at'], true)
                || array_key_exists($name, $model->getAttributes())
            ) {
                continue;
            }

            $model->setAttribute($name, $this->fakeValue($col));
        }

        $model->save();

        return $model;
    }

    private function fakeValue(array $col)
    {
        $type = strtolower($col['type_name'] ?? '');

        if (str_contains($type, 'enum')) {
            preg_match("/'([^']*)'/", $col['type'] ?? '', $mt);
            return $mt[1] ?? null;
        }

        if (str_contains($type, 'int') || in_array($type, ['decimal', 'float', 'double', 'numeric', 'bool', 'boolean'], true)) {
            return 0;
        }

        if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
            return now()->toDateTimeString();
        }

        if (str_contains($type, 'date')) {
            return now()->toDateString();
        }

        if (str_contains($type, 'time')) {
            return '00:00:00';
        }

        if (str_contains($type, 'json')) {
            return '[]';
        }

        return 'Demo';
    }
}