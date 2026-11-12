<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\PrivatePackage;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Siswa ASLI (dari DATA_INTERNAL_CLIENT_HAOYOU - Sheet10-2.pdf) + enrollment-nya.
 *
 * Urutan: setelah LevelSeeder, TeacherSeeder, ProgramPackageSeeder, PrivatePackageSeeder
 * dan ClassSeeder; sebelum ClassScheduleSeeder.
 *
 * Aturan:
 * - Username  : slug nama, pemisah "_" (nama kembar diberi _2, _3, dst.)
 * - Password  : sama untuk semua (DEFAULT_PASSWORD), diedit sendiri setelah login.
 * - Reguler   : dicocokkan ke kelas yang sudah ada (nama kelas mengandung mis. "Fei Xiang 1B").
 *               Kalau kelas tidak ketemu / penuh -> status 'Waiting Class'.
 * - Private   : 1 ON 1 => kelas pribadi sendiri; 2 ON 1 => 1 kelas dipakai bersama (lihat kolom group).
 *               Private tanpa keterangan => dianggap 1 ON 1 dan 'Waiting Class' (belum ada kelas).
 * - Pembayaran TIDAK dibuat di sini.
 */
class StudentSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'haoyou123';

    private ?int $studentLevelId = null;

    /** username yang sudah dipakai selama seeder ini berjalan */
    private array $usedUsernames = [];

    /** kelas private 2 ON 1 yang sudah dibuat, per group */
    private array $groupClasses = [];

    private int $teacherCursor = 0;

    /**
     * Format: [nama, program, kelas, group(2 ON 1)]
     * program: Maochong | Jianer | Hudie | Feixiang | HSK 1 | HSK 2 | HSK 3 | Private
     */
    private function students(): array
    {
        return [
            ['Nathanael Kenneth Brian Akulah', 'Feixiang', '1B'],
            ['Adhyaksa M. F.', 'Hudie', '2C'],
            ['Ibrahim', 'HSK 3', null],
            ['Sebastian Benjamin Santoso', 'Private', '1 ON 1'],
            ['Danu Athaya', 'Hudie', '2D'],
            ['Safri Maulana', 'HSK 3', null],
            ['Hana Almahyra', 'Private', '1 ON 1'],
            ['Christian Farrel', 'HSK 3', 'Termin 2'],
            ['James Linata', 'Private', null],
            ['Dinda Kamila', 'HSK 2', null],
            ['Zoey Alisha', 'Private', null],
            ['Jessen Jordan', 'Jianer', '3A'],
            ['Taruna Patria', 'Private', '1 ON 1'],
            ['Samantha Clarissa', 'Hudie', '2D'],
            ['Utsman Hidayat', 'Private', '1 ON 1'],
            ['Gwen', 'Maochong', '2B'],
            ['Sharon Giovanni Tejo', 'Private', '1 ON 1'],
            ['Agnes Wibowo', 'Private', '1 ON 1'],
            ['Tiffanny Christy Kurniawan', 'HSK 2', null],
            ['Adinda Salsabilla Dhea Rahmanda', 'HSK 3', 'Termin 1'],
            ['Ayline Queenie Margaretha', 'HSK 3', 'Termin 1'],
            ['Axel Alvindra', 'HSK 3', 'Termin 2'],
            ['Kayla Ellen', 'Private', '1 ON 1'],
            ['Embun', 'Private', '1 ON 1'],
            ['Elya Tri', 'Feixiang', '1B'],
            ['Adinda Firda', 'Feixiang', '1B'],
            ['Shintya Fuadiah', 'Private', '1 ON 1'],
            ['Ken Christian', 'Feixiang', '1D'],
            ['Hana Fitriyah', 'Feixiang', '1C'],
            ['Nabila Zahidah', 'HSK 2', null],
            ['Claire Alexa Lee', 'HSK 3', null],
            ['Theresa Avila', 'Private', '1 ON 1'],
            ['Khusnul Maarif', 'HSK 2', null],
            ['Jovita Vania', 'Feixiang', '1B'],
            ['Bernadheta Michiko Hiltraud Muijs', 'Feixiang', '1B'],
            ['Alifson Aquirera', 'HSK 2', null],
            ['Retno Ambar Arum', 'Feixiang', '1A'],
            ['Kim Wijaya', 'Jianer', '1B'],
            ['Syarif Hidayatullah', 'Hudie', '1B'],
            ['Syafiq Ibrahim', 'Hudie', '1B'],
            ['Diego Gracio', 'Hudie', '1B'],
            ['M. Bagas', 'HSK 2', null],
            ['Abiyoga Permana C', 'Feixiang', '1B'],
            ['Cheryl Hannesa Nathania', 'HSK 2', null],
            ['Yofi Nanda P', 'HSK 2', null],
            ['Olivia', 'HSK 2', null],
            ['Gregoria Alexa', 'Hudie', '1A'],
            ['Yulia Ananda', 'Feixiang', '1B'],
            ['Darren', 'Private', '1 ON 1'],
            ['Saina Kasya Mairani', 'Hudie', '1B'],
            ['Dimas Arya Satria Priyandia', 'HSK 2', null],
            ['Phoenix Kai Atlantis', 'Hudie', '1B'],
            ['Gregorius Raphael', 'Jianer', '1B'],
            ['Andrew Farrel Tjia', 'Private', '2 ON 1', 'tjia'],
            ['Betrand Sebastian Tjia', 'Private', '2 ON 1', 'tjia'],
            ['Nisrina Nabila Mustika Sakti', 'HSK 2', null],
            ['Gabriella Yovita', 'Jianer', '1A'],
            ['Valdy Daniel', 'HSK 2', null],
            ['Jesslyn Dibyo Vannopabbo', 'Hudie', '1B'],
            ['Keith Ethan Setiawan', 'Hudie', '1A'],
            ['Bryan', 'HSK 2', null],
            ['Airlangga Aditama', 'Hudie', '1A'],
            ['Lily Middleton Wijaya', 'Hudie', '1B'],
            ['Bara Sang Timur', 'HSK 2', null],
            ['Iola Marsha', 'Private', '1 ON 1'],
            ['Adrian Atsil G.', 'Private', '1 ON 1'],
            ['Sandrica', 'HSK 2', null],
            ['Lionel Richie Lorenzo', 'HSK 3', null],
            ['Lulus', 'HSK 2', null],
            ['Josephine Antonia', 'HSK 2', null],
            ['Zahra Aulia Fitri', 'Feixiang', '1A'],
            ['Yobel Wu', 'Feixiang', '1C'],
            ['Elizabeth Renata', 'Private', '2 ON 1', 'renata-yap'],
            ['Maria Elena Yap', 'Private', '2 ON 1', 'renata-yap'],
            ['Ary Ardiansyah', 'Feixiang', '1A'],
            ['Jonathan Imanuel', 'Feixiang', '1C'],
            ['Theophilus Imanuel', 'Feixiang', '1D'],
            ['Hana Rosita', 'HSK 2', null],
            ['Almira Rusyda Haflani', 'Jianer', '1A'],
            ['Kaira', 'HSK 2', null],
            ['Keyzia Liemmisela', 'HSK 2', null],
            ['Anisa Joy', 'Feixiang', '1A'],
            ['Icha', 'Feixiang', '1A'],
            ['Rohmawan Mujadah W', 'HSK 2', null],
            ['Nathaniel Sugiarto', 'Feixiang', '1C'],
            ['Khanza Sakhi N.K', 'Hudie', '1A'],
            ['Aurelia Keyko Putri W', 'Feixiang', '1A'],
            ['Aisyah Azzahra', 'Feixiang', '1A'],
            ['Ghania Putri Nazira R', 'Private', '1 ON 1'],
            ['Fariza Annida Kamilia', 'HSK 1', null],
            ['Alicia Handoko Putri', 'HSK 1', null],
            ['Evi Eka Novia', 'HSK 1', null],
            ['Sarah', 'Private', '2 ON 1', 'sarah-abel'],
            ['Abel', 'Private', '2 ON 1', 'sarah-abel'],
            ['Lissa Dixie Kania', 'HSK 2', null],
            ['Marcia Angelia Josephine', 'HSK 2', null],
            ['Afif Malik Gunawan', 'Private', '1 ON 1'],
            ['Patrick F. Santoso', 'Feixiang', '1A'],
            ['Clement Richard Santoso', 'Feixiang', '1A'],
            ['Mira Tania', 'HSK 1', null],
            ['Ferdyansyah', 'HSK 1', null],
            ['Justin Kelvin', 'HSK 1', null],
            ['Neola Nara Joenata', 'HSK 2', null],
            ['Gabriella Timothy Junifertya A', 'HSK 2', null],
            ['Nova Nadia Faradiba', 'Private', '1 ON 1'],
            ['Farah', 'Private', '1 ON 1'],
            ['Yeni Patricia', 'HSK 2', null],
            ['Nana', 'HSK 1', null],
            ['Melani Ling', 'Feixiang', '1A'],
            ['M. Athaya Tsany Farhan', 'HSK 1', null],
            ['Sekar Lintang K', 'HSK 1', null],
            ['Danisha Nur Rashaqa Alya', 'Private', '1 ON 1'],
            ['Firdaus Wahyu S', 'Feixiang', '1A'],
            ['Azwa Safrina', 'HSK 2', null],
            ['Nur Rahma Aulia R', 'HSK 1', null],
            ['Daven Georgian', 'Private', '1 ON 1'],
            ['Adzhar Syafiq', 'HSK 1', null],
            ['Angellica', 'HSK 1', null],
            ['Evan Everest Bek', 'Hudie', '1A'],
            ['Luthfi Sevilla', 'Private', '1 ON 1'],
            ['Vania Salsa N. H', 'HSK 1', null],
            ['Mira', 'Private', '1 ON 1'],
            ['Seren Prascika Labi', 'Feixiang', '1A'],
            ['Tanisha', 'Private', '2 ON 1', 'tanisha-fazila'],
            ['Fazila', 'Private', '2 ON 1', 'tanisha-fazila'],
            ['Michael', 'HSK 1', null],
            ['Fedora Oriana Waldy', 'HSK 3', null],
            ['Diffa Zulaikha', 'HSK 3', null],
            ['Phoebe', 'HSK 1', null],
            ['Stevania Sharon', 'HSK 1', null],
            ['Ahmad', 'HSK 1', null],
            ['Apsari Meirani', 'HSK 1', null],
            ['Cheryl Caroline R. S', 'Private', '2 ON 1', 'cheryl-claudea-roshaura'],
            ['Claudea Wijaya', 'Private', '2 ON 1', 'cheryl-claudea-roshaura'],
            ['Roshaura Dewi Ryani Zahra', 'Private', '2 ON 1', 'cheryl-claudea-roshaura'],
            ['Josephine Marcia Leonita', 'HSK 1', null],
            ['Nursyafiqah Hazimah D', 'HSK 1', null],
            ['Allegrio Putra', 'Feixiang', '1B'],
            ['M. Jennifer Callysta P', 'Feixiang', '1A'],
            ['M. Rizki Zainuri', 'HSK 1', null],
            ['Carmen Ashley Kusuma', 'Private', null],
            ['Aufaa Odellia Sabrina', 'Feixiang', '1A'],
            ['Steven', 'Private', null],
            ['Michael', 'Private', null],
            ['Runna Theda M', 'Private', null],
            ['Mario Marcellino', 'Feixiang', '1A'],
            ['Kezia', 'HSK 1', null],
            ['Dirga', 'Feixiang', '1A'],
            ['Karina Lestari', 'HSK 1', null],
            ['Julia Tri E.', 'HSK 1', null],
            ['Cherin Dharani', 'Feixiang', '1A'],
            ['Theresia Angel Sanjaya', 'HSK 1', null],
            ['Intan', 'HSK 1', null],
            ['Jona', 'HSK 1', null],
            ['Sekar Ayu', 'HSK 1', null],
            ['Gusti', 'HSK 1', null],
            ['Farhan M. K', 'Private', '1 ON 1'],
            ['Fanny Tiara Indah P', 'HSK 1', null],
            ['Rida', 'Private', '2 ON 1', 'rida-jose'],
            ['Jose Alfonso S.', 'Private', '2 ON 1', 'rida-jose'],
            ['Jessica', 'Private', '1 ON 1'],
            ['April', 'Feixiang', '1A'],
            ['Reci', 'HSK 1', null],
            ['Oktavian Aidar Yoga', 'Private', '1 ON 1'],
            ['Riva', 'Private', '1 ON 1'],
        ];
    }

    public function run(): void
    {
        $this->studentLevelId = Level::where('nama_level', 'Student')->value('id_level');

        if (! $this->studentLevelId) {
            $this->command->error('Level "Student" belum ada di tabel levels. Jalankan LevelSeeder dulu.');

            return;
        }

        $programPackages = ProgramPackage::all();
        $privatePackages = PrivatePackage::where('is_active', true)->get();
        $teacherIds      = Teacher::where('status', 'Active')->orderBy('id')->pluck('id')->values();

        $regularClasses = $this->loadClasses('program_package_id');

        $stats = ['kelas' => 0, 'waiting' => 0, 'skip' => 0];
        $warnings = [];

       foreach ($this->students() as $row) {
        $name    = $row[0];
        $program = $row[1];
        $kelas   = $row[2] ?? null;
        $group   = $row[3] ?? null;

            $user = User::firstOrCreate(
                ['username' => $this->uniqueUsername($name)],
                [
                    'level_id' => $this->studentLevelId,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'status'   => 'Active',
                ]
            );

            $isPrivate = $program === 'Private';

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'candidate_student_id' => null,
                    'current_level_id'     => $isPrivate ? null : $this->resolveStudentLevelId($program, $kelas, $programPackages),
                    'name'                 => $name,
                    'points'               => 0,
                    'join_date'            => Carbon::today()->toDateString(),
                    'status'               => 'Active',
                ]
            );

            // Seeder aman dijalankan ulang: enrollment yang sudah ada tidak diubah
            if (ClassEnrollment::where('student_id', $student->id)->exists()) {
                $stats['skip']++;
                continue;
            }

            $placed = $isPrivate
                ? $this->enrollPrivate($student, $kelas, $group, $privatePackages, $teacherIds)
                : $this->enrollRegular($student, $program, $kelas, $programPackages, $regularClasses, $warnings);

            $placed ? $stats['kelas']++ : $stats['waiting']++;
        }

        $this->command->info("Selesai: {$stats['kelas']} siswa masuk kelas, {$stats['waiting']} 'Waiting Class', {$stats['skip']} dilewati (sudah punya enrollment).");

        foreach (array_unique($warnings) as $warning) {
            $this->command->warn($warning);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Reguler (Maochong, Jianer, Hudie, Feixiang, HSK)                    */
    /* ------------------------------------------------------------------ */

    private function enrollRegular(Student $student, string $program, ?string $kelas, $programPackages, $regularClasses, array &$warnings): bool
    {
        $class = $this->findRegularClass($regularClasses, $program, $kelas);

        $package = $class
            ? $programPackages->firstWhere('id', $class->program_package_id)
            : $this->resolvePackage($program, $programPackages);

        if (! $package) {
            $warnings[] = "Paket program untuk '{$program}' tidak ditemukan; siswa '{$student->name}' dan sejenisnya dibuat tanpa enrollment.";

            return false;
        }

        if ($class) {
            $class->seats_taken++;

            if ($class->level_id) {
                $student->update(['current_level_id' => $class->level_id]);
            }
        } else {
            $warnings[] = "Kelas untuk '{$program} {$kelas}' tidak ditemukan/penuh: siswa dimasukkan sebagai 'Waiting Class'.";
        }

        ClassEnrollment::create([
            'student_id'         => $student->id,
            'class_id'           => $class?->id,
            'program_package_id' => $package->id,
            'private_package_id' => null,
            'enrollment_date'    => $student->join_date,
            'status'             => $class ? 'Active' : 'Waiting Class',
            'registration_fee'   => 0,
            'activity_fee'       => 0,
            'discount'           => 0,
        ]);

        return $class !== null;
    }

    /** Kelas yang namanya mengandung mis. "feixiang1b" (spasi/tanda baca diabaikan) dan masih punya kursi. */
    private function findRegularClass($classes, string $program, ?string $kelas): ?ClassModel
    {
        $needle = $this->norm($program . ($kelas && ! str_starts_with(strtolower($kelas), 'termin') ? $kelas : ''));
        $termin = $kelas && str_starts_with(strtolower($kelas), 'termin') ? $this->norm($kelas) : null;

        $candidates = $classes->filter(
            fn ($c) => $c->seats_taken < $c->capacity && str_contains($this->norm($c->class_name), $needle)
        );

        if ($termin) {
            $withTermin = $candidates->filter(fn ($c) => str_contains($this->norm($c->class_name), $termin));
            $candidates = $withTermin->isNotEmpty() ? $withTermin : $candidates;
        }

        return $candidates->first();
    }

    private function resolvePackage(string $program, $programPackages): ?ProgramPackage
    {
        $normProgram = $this->norm($program);

        // 1) nama paket mengandung nama program (mis. "HSK 2", "Hudie")
        $byName = $programPackages->first(fn ($p) => str_contains($this->norm($p->package_name), $normProgram));
        if ($byName) {
            return $byName;
        }

        // 2) HSK: paket apa pun yang namanya mengandung "hsk"
        if (str_starts_with($normProgram, 'hsk')) {
            return $programPackages->first(fn ($p) => str_contains($this->norm($p->package_name), 'hsk'));
        }

        // 3) Anak (Maochong/Jianer) vs Dewasa/Remaja (Hudie/Feixiang), sama seperti seeder dummy lama
        $wantAnak = in_array($normProgram, ['maochong', 'jianer'], true);

        return $programPackages->first(
            fn ($p) => str_contains($p->package_name, 'Anak') === $wantAnak
        );
    }

    private function resolveStudentLevelId(string $program, ?string $kelas, $programPackages): ?int
    {
        // HSK: ambil level dari paketnya (kalau paket terikat ke level)
        if (str_starts_with($this->norm($program), 'hsk')) {
            return $this->resolvePackage($program, $programPackages)?->level_id;
        }

        // Kids: kategori = nama program, level ke-N berdasarkan angka di kelas ("2C" => level ke-2)
        $categoryIds = ProgramCategory::all()
            ->filter(fn ($c) => $this->norm($c->category_name) === $this->norm($program))
            ->pluck('id');

        if ($categoryIds->isEmpty()) {
            return null;
        }

        $order = max((int) $kelas, 1);

        return ProgramLevel::whereIn('category_id', $categoryIds)
            ->orderBy('id')
            ->skip($order - 1)
            ->value('id')
            ?? ProgramLevel::whereIn('category_id', $categoryIds)->orderBy('id')->value('id');
    }

    /* ------------------------------------------------------------------ */
    /* Private                                                             */
    /* ------------------------------------------------------------------ */

    private function enrollPrivate(Student $student, ?string $kelas, ?string $group, $privatePackages, $teacherIds): bool
    {
        $isTwoOnOne = $kelas === '2 ON 1';
        $wantedMax  = $isTwoOnOne ? 2 : 1;

        $package = $privatePackages->first(fn ($p) => (int) $p->max_students === $wantedMax)
            ?? $privatePackages->first();

        if (! $package) {
            return false;
        }

        // "PRIVATE" tanpa keterangan: belum ada kelas, admin yang menentukan nanti
        $classId = $kelas ? $this->privateClassId($student, $isTwoOnOne, $group, $package, $teacherIds) : null;

        ClassEnrollment::create([
            'student_id'         => $student->id,
            'class_id'           => $classId,
            'program_package_id' => null,
            'private_package_id' => $package->id,
            'enrollment_date'    => $student->join_date,
            'status'             => $classId ? 'Active' : 'Waiting Class',
            'registration_fee'   => 0,
            'activity_fee'       => 0,
            'discount'           => 0,
        ]);

        return $classId !== null;
    }

    private function privateClassId(Student $student, bool $isTwoOnOne, ?string $group, PrivatePackage $package, $teacherIds): ?int
    {
        // 2 ON 1: satu kelas dipakai bersama anggota group yang sama
        if ($isTwoOnOne && $group && isset($this->groupClasses[$group])) {
            return $this->groupClasses[$group];
        }

        $start = Carbon::today();

        $class = ClassModel::create([
            'program_package_id' => null,
            'private_package_id' => $package->id,
            'level_id'           => null,
            // Guru sementara (bergiliran); ganti di menu kelas sesuai kenyataan
            'teacher_id'         => $teacherIds->isNotEmpty() ? $teacherIds[$this->teacherCursor++ % $teacherIds->count()] : null,
            'class_name'         => $isTwoOnOne && $group ? 'Private 2 ON 1 - ' . $group : "Private - {$student->name}",
            'delivery_mode'      => 'Offline',
            'start_date'         => $start->toDateString(),
            'end_date'           => $start->copy()->addMonths(max((int) ($package->duration_months ?: 1), 1))->toDateString(),
            // group Cheryl/Claudea/Roshaura berisi 3 orang, jadi kapasitas minimal 3
            'capacity'           => max((int) ($package->max_students ?: 1), $group === 'cheryl-claudea-roshaura' ? 3 : 1),
            'status'             => 'Running',
        ]);

        if ($isTwoOnOne && $group) {
            $this->groupClasses[$group] = $class->id;
        }

        return $class->id;
    }

    /* ------------------------------------------------------------------ */
    /* Helper                                                              */
    /* ------------------------------------------------------------------ */

    private function loadClasses(string $packageColumn)
    {
        return ClassModel::whereNotNull($packageColumn)
            ->whereIn('status', ['Open', 'Running'])
            ->withCount([
                'enrollments as seats_taken' => fn ($q) => $q->whereIn('status', ClassModel::SEAT_STATUSES),
            ])
            ->get();
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'siswa';
        $username = $base;
        $counter = 2;

        while (isset($this->usedUsernames[$username])) {
            $username = $base . '_' . $counter++;
        }

        $this->usedUsernames[$username] = true;

        return $username;
    }

    private function norm(?string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $value));
    }
}