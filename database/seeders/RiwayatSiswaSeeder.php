<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\Level;
use App\Models\PrivatePackage;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Siswa ASLI dari Data_Siswa.xlsx (data sudah dibersihkan dan ditanam langsung di rows()).
 *
 * Yang dicatat per siswa: nama, program yang diikuti, dan tanggal pertemuan terakhir.
 *   - 1 baris data = 1 enrollment (class_enrollments) TANPA kelas (class_id = null)
 *   - status enrollment = Completed (program dianggap sudah selesai)
 *   - class_enrollments.last_class_date = PERTEMUAN TERAKHIR -> dasar retention rate & aturan H+30
 *   - Pembayaran TIDAK dibuat.
 *
 * Butuh migration: add_last_class_date_to_class_enrollments_table.
 * Urutan    : setelah LevelSeeder, program/kategori/level, ProgramPackageSeeder, PrivatePackageSeeder.
 *             JANGAN jalankan seeder dummy lama (StudentSeeder/ClassSeeder dummy) bersamaan.
 * Aman dijalankan ulang: baris yang sudah masuk dilewati (ditandai di kolom notes enrollment).
 */
class RiwayatSiswaSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'haoyou123';   // ganti sesudah login pertama
    private const GRACE_DAYS       = 30;            // H+30 setelah pertemuan terakhir

    private array $errors = [];

    public function run(): void
    {
        $studentRoleId = Level::where('nama_level', 'Student')->value('id_level');

        if (! $studentRoleId) {
            $this->command->error('Level "Student" belum ada. Jalankan LevelSeeder dulu.');

            return;
        }

        $today = Carbon::today();
        $stats = ['siswa' => 0, 'baru' => 0, 'skip' => 0, 'gagal' => 0];
        $bucket = ['berjalan' => 0, 'masa tenggang (H+1..H+30)' => 0, 'expired (>H+30)' => 0];

        Model::unguard();

        foreach (collect($this->rows())->groupBy('kode_siswa') as $kode => $history) {
            $history = $history->sortBy('tgl_mulai')->values();

            try {
                DB::transaction(function () use ($kode, $history, $studentRoleId, $today, &$stats) {
                    $student = $this->findOrCreateStudent($kode, $history, $studentRoleId);

                    foreach ($history as $row) {
                        $marker = "import:{$kode}:{$row['tgl_mulai']}";

                        if (ClassEnrollment::where('notes', 'like', $marker . '%')->exists()) {
                            $stats['skip']++;

                            continue;
                        }

                        $this->importRow($student, $row, $marker);
                        $stats['baru']++;
                    }

                    $this->syncStudent($student, $history, $today);
                    $stats['siswa']++;
                });
            } catch (\Throwable $e) {
                $stats['gagal']++;
                $this->errors[] = "{$kode} ({$history->first()['nama']}): " . $e->getMessage();

                continue;
            }

            $lastEnd = Carbon::parse($history->max('tgl_selesai'));

            if ($lastEnd->gte($today)) {
                $bucket['berjalan']++;
            } elseif ($lastEnd->diffInDays($today) <= self::GRACE_DAYS) {
                $bucket['masa tenggang (H+1..H+30)']++;
            } else {
                $bucket['expired (>H+30)']++;
            }
        }

        Model::reguard();

        $this->command->info("Siswa: {$stats['siswa']} | enrollment baru: {$stats['baru']} | dilewati: {$stats['skip']} | gagal: {$stats['gagal']}");

        foreach ($bucket as $label => $n) {
            $this->command->line("  {$label}: {$n}");
        }

        foreach ($this->errors as $error) {
            $this->command->warn($error);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Siswa                                                               */
    /* ------------------------------------------------------------------ */

    private function findOrCreateStudent(string $kode, $history, int $studentRoleId): Student
    {
        // Seeder dijalankan ulang: pakai siswa yang sudah dibuat untuk kode ini
        $existing = ClassEnrollment::where('notes', 'like', "import:{$kode}:%")->first();

        if ($existing && ($student = Student::find($existing->student_id))) {
            return $student;
        }

        $first = $history->first();

        $user = User::create([
            'level_id' => $studentRoleId,
            'username' => $this->uniqueUsername($first['nama']),
            'name'     => $first['nama'],
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'status'   => 'Active',
        ]);

        return Student::create([
            'candidate_student_id' => null,
            'user_id'              => $user->id,
            'current_level_id'     => null,
            'name'                 => $first['nama'],
            'points'               => 0,
            'join_date'            => $history->min('tgl_mulai'),
            'status'               => 'Active',
        ]);
    }

    /** Aktif selama masih ada kelas berjalan atau belum lewat H+30 dari pertemuan terakhir. */
    private function syncStudent(Student $student, $history, Carbon $today): void
    {
        $lastEnd = Carbon::parse($history->max('tgl_selesai'));
        $active  = $lastEnd->copy()->addDays(self::GRACE_DAYS)->gte($today);

        $levelId = $student->current_level_id;

        foreach ($history->sortByDesc('tgl_mulai') as $row) {
            if ($row['jenis'] === 'reguler') {
                $levelId = $this->resolveRegular($row)[1]->id;

                break;
            }
        }

        $student->update([
            'current_level_id' => $levelId,
            'join_date'        => $history->min('tgl_mulai'),
            'status'           => $active ? 'Active' : 'Inactive',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Enrollment (program terakhir, tanpa kelas)                          */
    /* ------------------------------------------------------------------ */

    private function importRow(Student $student, array $row, string $marker): void
    {
        $isPrivate = $row['jenis'] === 'private';

        $package = $isPrivate
            ? PrivatePackage::where(
                'package_name', 'like', "Private {$row['private_paket']} Class - {$row['pertemuan']} Pertemuan"
            )->firstOrFail()
            : $this->resolveRegular($row)[0];

        ClassEnrollment::create([
            'student_id'         => $student->id,
            'class_id'           => null,   // tidak ada kelas; riwayat saja
            'program_package_id' => $isPrivate ? null : $package->id,
            'private_package_id' => $isPrivate ? $package->id : null,
            'enrollment_date'    => $row['tgl_mulai'],
            'last_class_date'    => $row['tgl_selesai'],
            'registration_fee'   => 0,
            'activity_fee'       => 0,
            'discount'           => 0,
            'payment_scheme'     => $row['skema_bayar'] ?: 'Lunas',
            'status'             => 'Completed',
            'notes'              => $marker . ' | Data_Siswa.xlsx',
        ]);
    }

    /** @return array{0: ProgramPackage, 1: ProgramLevel} */
    private function resolveRegular(array $row): array
    {
        $category = ProgramCategory::where('category_name', $row['kategori'])->firstOrFail();

        $level = ProgramLevel::where('category_id', $category->id)
            ->where('level_name', $row['level'])
            ->firstOrFail();

        $package = $row['kategori'] === 'HSK Class'
            ? ProgramPackage::where('category_id', $category->id)->where('level_id', $level->id)->firstOrFail()
            : ProgramPackage::where('program_id', $category->program_id)
                ->whereNull('category_id')
                ->where('package_name', 'like', "Regular Class {$row['tipe']} - {$row['frekuensi']}/Minggu%")
                ->firstOrFail();

        return [$package, $level];
    }

    /* ------------------------------------------------------------------ */
    /* Helper                                                              */
    /* ------------------------------------------------------------------ */

    private function uniqueUsername(string $name): string
    {
        $base     = Str::slug($name, '_') ?: 'siswa';
        $username = $base;
        $counter  = 2;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $counter++;
        }

        return $username;
    }

    private const COLUMNS = [
        'kode_siswa', 'nama', 'jenis', 'kategori', 'level', 'tipe', 'frekuensi',
        'private_paket', 'pertemuan', 'delivery', 'skema_bayar', 'tgl_mulai', 'tgl_selesai',
    ];

    /**
     * Satu baris = satu program yang diikuti siswa.
     * jenis: reguler | private. tgl_selesai = pertemuan terakhir.
     * kode_siswa yang sama = siswa yang sama (nama kembar sengaja diberi kode berbeda;
     * samakan kodenya kalau ternyata orang yang sama).
     */
    private function rows(): array
    {
        $data = [
            ['S001', 'Kenneth', 'reguler', 'Feixiang', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-15', '2026-12-03'],
            ['S002', 'Ibrahim', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Termin', '2026-08-05', '2026-10-24'],
            ['S003', 'Sebastian', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-25', '2026-12-16'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S004', 'Danu', 'reguler', 'Hudie', '2D', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-07-29', '2026-10-15'],
            ['S005', 'Safri', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Lunas', '2026-11-16', '2027-02-08'],
            ['S006', 'Hana Almahyra', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-10', '2026-12-08'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S007', 'Christian Farrel', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Termin', '2026-08-05', '2026-10-24'],
            ['S008', 'James', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-07', '2026-11-28'],
            ['S009', 'Dinda Kamila', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-09-22', '2026-12-10'],
            ['S010', 'Zoey Alisha', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-10-20', '2026-11-14'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S011', 'Jessen', 'reguler', 'Jianer', '3A', 'Anak', '1x', '', '', 'Offline', 'Lunas', '2026-07-25', '2026-10-10'],
            ['S012', 'Taruna Patria', 'private', '', '', '', '', 'VIP', '4', 'Online', 'Lunas', '2026-11-05', '2026-11-26'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S013', 'Samantha', 'reguler', 'Hudie', '2D', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-07-29', '2026-10-14'],
            ['S014', 'Utsman', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-10-16', '2026-11-06'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S015', 'Gwen', 'reguler', 'Maochong', '2B', 'Anak', '1x', '', '', 'Offline', 'Lunas', '2026-07-31', '2026-10-19'],
            ['S016', 'Sharon', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-02', '2026-11-23'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S017', 'Agnes Wibowo', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-11-04', '2026-12-05'],
            ['S018', 'Tiffanny', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-23', '2027-01-24'],
            ['S019', 'Adinda', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Termin', '2026-11-04', '2027-01-27'],
            ['S020', 'Ayline', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Termin', '2026-11-04', '2027-01-27'],
            ['S021', 'Axel', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Termin', '2026-11-04', '2027-01-27'],
            ['S022', 'Kayla', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-10-16', '2026-11-13'],
            ['S023', 'Embun', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-10', '2026-11-26'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S024', 'Shintya', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-10', '2026-12-01'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S025', 'Ken', 'reguler', 'Feixiang', '1D', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-11-04', '2027-01-20'],
            ['S026', 'Hana', 'reguler', 'Feixiang', '1C', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-11-09', '2027-02-02'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S027', 'Nabila', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-09-22', '2026-12-10'],
            ['S028', 'Alexa', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Lunas', '2026-09-23', '2026-12-10'],
            ['S029', 'Arif', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-08-14', '2026-10-26'],
            ['S030', 'Jovita', 'reguler', 'Feixiang', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-15', '2026-12-03'],
            ['S031', 'Michiko', 'reguler', 'Feixiang', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-15', '2026-12-03'],
            ['S032', 'Rera', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-07', '2026-12-26'],
            ['S033', 'Arum', 'reguler', 'Feixiang', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-08-11', '2026-11-03'],
            ['S034', 'Kim Wijaya', 'reguler', 'Jianer', '1B', 'Anak', '2x', '', '', 'Offline', 'Lunas', '2026-11-04', '2027-02-05'],
            ['S035', 'Syarif', 'reguler', 'Hudie', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-03', '2026-11-20'],
            ['S036', 'Syafiq', 'reguler', 'Hudie', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-03', '2026-11-20'],
            ['S037', 'Diego', 'reguler', 'Hudie', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-03', '2026-11-23'],
            ['S038', 'M. Bagas', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Lunas', '2026-11-16', '2027-02-08'],
            ['S039', 'Abiyoga', 'reguler', 'Feixiang', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-06', '2026-12-24'],
            ['S040', 'Cheryl', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-09-22', '2026-12-10'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S041', 'Nanda', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-09-22', '2026-12-10'],
            ['S042', 'Olivia', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Online', 'Lunas', '2026-11-12', '2027-02-11'],
            ['S043', 'Alexa Hudie', 'reguler', 'Hudie', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-07-30', '2026-11-14'],
            ['S044', 'Yulia', 'reguler', 'Feixiang', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-06', '2026-12-24'],
            ['S045', 'Darren', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-09', '2026-11-30'],
            ['S046', 'Saina', 'reguler', 'Hudie', '1B', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-10-27', '2027-01-19'],
            ['S047', 'Arya', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-13', '2026-12-24'],
            ['S048', 'Phoenix', 'reguler', 'Hudie', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-27', '2027-01-21'],
            ['S049', 'Raphael', 'reguler', 'Jianer', '1B', 'Anak', '2x', '', '', 'Offline', 'Lunas', '2026-11-03', '2027-02-05'],
            ['S050', 'Andrew', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-10-26', '2026-11-16'],
            ['S051', 'Betrand', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-10-26', '2026-11-16'],
            ['S052', 'Nisrina', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-09-22', '2026-12-10'],
            ['S053', 'Gabby', 'reguler', 'Jianer', '1A', 'Anak', '1x', '', '', 'Offline', 'Lunas', '2026-09-19', '2026-12-05'],
            ['S054', 'Valdy', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-08-03', '2026-10-26'],
            ['S055', 'Jesslyn', 'reguler', 'Hudie', '1B', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-27', '2027-01-21'],
            ['S056', 'Bryan', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-06', '2026-12-24'],
            ['S057', 'Airlangga', 'reguler', 'Hudie', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-08-24', '2026-11-09'],
            ['S058', 'Lily', 'reguler', 'Hudie', '1B', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-10-27', '2027-01-19'],
            ['S059', 'Bara', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-06', '2026-12-24'],
            ['S060', 'Adrian', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-11-11', '2026-12-02'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S061', 'Sandrica', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-20', '2027-01-19'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-19 -> 2027-01-19
            ['S062', 'Lionel', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Lunas', '2026-09-23', '2026-12-10'],
            ['S063', 'Lulus', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-08-10', '2026-11-02'],  // tanggal teks '10 Agsutus 2026' dibaca otomatis
            ['S064', 'Josephine', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-27', '2027-01-19'],
            ['S065', 'Zahra Aulia', 'reguler', 'Feixiang', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-08-11', '2026-11-03'],
            ['S066', 'Renata/Tata', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-10', '2026-12-01'],
            ['S067', 'Elena', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-10', '2026-12-01'],
            ['S068', 'Ary', 'reguler', 'Feixiang', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-10-20', '2027-01-05'],
            ['S069', 'Theophilus', 'reguler', 'Feixiang', '1D', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-11-04', '2027-01-25'],
            ['S070', 'Hana', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-23', '2027-01-24'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S071', 'Hafla', 'reguler', 'Jianer', '1A', 'Anak', '1x', '', '', 'Offline', 'Lunas', '2026-09-19', '2026-12-05'],
            ['S072', 'Kaira', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-23', '2027-01-24'],
            ['S073', 'Keyzia', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-13', '2026-12-24'],
            ['S074', 'Anisa', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-08-11', '2026-11-03'],
            ['S075', 'Icha', 'reguler', 'Feixiang', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-08-11', '2026-11-03'],
            ['S076', 'Uja', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-10-20', '2027-01-19'],
            ['S077', 'Nathaniel', 'reguler', 'Feixiang', '1C', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-11-09', '2027-02-02'],
            ['S078', 'Khanza', 'reguler', 'Hudie', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-12-05', '2027-02-20'],
            ['S079', 'Keyko', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-20', '2026-12-28'],
            ['S080', 'Aisyah Azzahra', 'reguler', 'Feixiang', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-08-22', '2026-10-31'],
            ['S081', 'Ghania', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-10-29', '2026-11-21'],  // paket tertulis 'Private (Class)' tanpa VIP/Exclusive, dianggap VIP
            ['S082', 'Riza', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-25', '2026-11-17'],
            ['S083', 'Alicia', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-25', '2026-11-17'],
            ['S084', 'Evi', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-25', '2026-11-17'],
            ['S085', 'Sarah', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-11', '2026-12-02'],
            ['S086', 'Abel', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-11', '2026-12-02'],
            ['S087', 'Lissa', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-11-06', '2027-02-01'],
            ['S088', 'Marcia', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-11-06', '2027-02-01'],
            ['S089', 'Afif', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-03', '2026-11-24'],
            ['S090', 'Patrick', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-03', '2026-11-21'],
            ['S091', 'Clement', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-03', '2026-11-21'],
            ['S092', 'Tania', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Online', 'Lunas', '2026-09-10', '2026-10-30'],
            ['S093', 'Ferdy', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-07', '2026-11-27'],
            ['S094', 'Justin', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-07', '2026-11-27'],
            ['S095', 'Neola', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-11-06', '2027-02-01'],
            ['S096', 'Gaby', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-11-06', '2027-02-01'],
            ['S097', 'Farah', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-10-17', '2026-11-14'],
            ['S098', 'Yeni', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Offline', 'Lunas', '2026-11-06', '2027-02-01'],
            ['S099', 'Nana', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-04', '2026-10-27'],
            ['S101', 'Farhan', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-21', '2026-11-11'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S102', 'Lintang', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-25', '2026-11-17'],
            ['S103', 'Danisha', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-11-11', '2026-12-07'],
            ['S104', 'Daus', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-10', '2026-11-21'],
            ['S105', 'Azwa', 'reguler', 'HSK Class', 'HSK 2', '', '', '', '', 'Online', 'Lunas', '2026-11-12', '2027-02-11'],
            ['S106', 'Aulia', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-25', '2026-11-17'],
            ['S107', 'Daven', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-09-23', '2026-10-09'],
            ['S108', 'Adzhar', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-21', '2026-11-11'],
            ['S109', 'Angellica', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-21', '2026-11-11'],
            ['S110', 'Evan', 'reguler', 'Hudie', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-09-12', '2026-11-14'],
            ['S111', 'Luthfi', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-11-15', '2026-11-30'],
            ['S112', 'Vania', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-21', '2026-11-11'],
            ['S113', 'Serena', 'reguler', 'Feixiang', '1A', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-10-19', '2027-01-04'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-04 -> 2027-01-04
            ['S114', 'Tanisha', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-09-20', '2026-11-01'],
            ['S115', 'Fazila', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-09-20', '2026-11-01'],
            ['S116', 'Michael', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-09-21', '2026-11-11'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S117', 'Fedora', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Lunas', '2026-10-02', '2026-12-19'],
            ['S118', 'Diffa', 'reguler', 'HSK Class', 'HSK 3', '', '', '', '', 'Offline', 'Lunas', '2026-10-02', '2026-12-19'],
            ['S119', 'Phoebe', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-07', '2026-11-27'],
            ['S120', 'Sharon', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-07', '2026-11-27'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S121', 'Ahmad', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-07', '2026-11-27'],
            ['S122', 'Rani', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-13', '2026-12-03'],
            ['S123', 'Cheryl', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-12', '2026-12-03'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S124', 'Claudia', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-12', '2026-12-03'],
            ['S125', 'Yara', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-11-12', '2026-12-03'],
            ['S126', 'Nita', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-13', '2026-12-03'],
            ['S127', 'Fiqah', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-13', '2026-12-03'],
            ['S128', 'Geo', 'reguler', 'Feixiang', '1B', 'Dewasa', '1x', '', '', 'Offline', 'Lunas', '2026-10-06', '2026-12-22'],
            ['S129', 'Jennifer', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-19', '2027-01-05'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-05 -> 2027-01-05
            ['S130', 'Zain', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-10-13', '2026-12-03'],
            ['S131', 'Carmen Ashley', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-13', '2026-12-04'],
            ['S132', 'Odellia', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-19', '2027-01-05'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-05 -> 2027-01-05
            ['S133', 'Steven', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-10-29', '2026-11-19'],
            ['S134', 'Michael', 'private', '', '', '', '', 'Exclusive', '4', 'Offline', 'Lunas', '2026-10-29', '2026-11-19'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S135', 'Runna', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-11-11', '2026-12-07'],
            ['S136', 'Marcell', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-19', '2027-01-05'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-05 -> 2027-01-05
            ['S137', 'Kezia', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-05', '2026-12-30'],  // tanggal teks '5 Noveber 2026' dibaca otomatis
            ['S138', 'Dirga', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-26', '2027-01-05'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-05 -> 2027-01-05
            ['S139', 'Karina', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-05', '2026-12-30'],  // tanggal teks '5 Noveber 2026' dibaca otomatis
            ['S140', 'Julia', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-05', '2026-12-30'],
            ['S141', 'Cherin', 'reguler', 'Feixiang', '1A', 'Dewasa', '2x', '', '', 'Offline', 'Lunas', '2026-10-26', '2027-01-05'],  // tahun pertemuan terakhir diperbaiki otomatis 2026-01-05 -> 2027-01-05
            ['S142', 'Angel', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-05', '2026-12-30'],
            ['S143', 'Intan', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-05', '2026-12-30'],
            ['S144', 'Jona', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-05', '2026-12-30'],
            ['S145', 'Sekar', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-02', '2026-12-28'],
            ['S146', 'Gusti', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-02', '2026-12-28'],
            ['S147', 'Farhan', 'private', '', '', '', '', 'VIP', '8', 'Offline', 'Lunas', '2026-11-03', '2026-11-26'],  // nama kembar: jika orang yang sama dengan baris lain, samakan kode_siswa
            ['S148', 'Fanny', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-02', '2026-12-28'],
            ['S149', 'Rida', 'private', '', '', '', '', 'Exclusive', '8', 'Offline', 'Lunas', '2026-11-10', '2026-12-02'],
            ['S150', 'Jose', 'private', '', '', '', '', 'Exclusive', '8', 'Offline', 'Lunas', '2026-11-10', '2026-12-02'],
            ['S151', 'Jessica', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-13', '2026-12-04'],
            ['S153', 'Reci', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-02', '2026-12-28'],
            ['S154', 'Aidar', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-10', '2026-11-14'],
            ['S155', 'Riva', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-21', '2026-12-12'],
            ['S156', 'Alisya', 'reguler', 'HSK Class', 'HSK 1', '', '', '', '', 'Offline', 'Lunas', '2026-11-16', '2026-12-28'],
            ['S158', 'Alfatih', 'private', '', '', '', '', 'VIP', '4', 'Offline', 'Lunas', '2026-11-27', '2026-12-18'],
        ];

        return array_map(fn ($row) => array_combine(self::COLUMNS, $row), $data);
    }
}