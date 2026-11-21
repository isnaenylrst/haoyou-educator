<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Data uji DETERMINISTIK untuk Retention Rate (angka bisa dicocokkan dengan tangan).
 * Untuk data yang realistis, pakai ClassSeeder + ClassEnrollmentSeeder lewat DatabaseSeeder.
 * Jangan jalankan keduanya sekaligus kalau tidak ingin angkanya bercampur.
 */
class RetentionDemoSeeder extends Seeder
{
    /** Harus sama dengan konstanta di RetentionService */
    private const TAUGHT_COLUMN = 'class_status';
    private const TAUGHT_VALUE  = 'Conducted';

    /**
     * Profil per guru untuk 6 bulan (5 bulan lalu sampai bulan ini).
     * Format: [siswa_selesai, siswa_lanjut_sudah_bayar, pertemuan, (opsional) lanjut_belum_bayar]
     *         null = tidak ada kelas berakhir
     */
    private array $profiles = [
        1 => [[6,5,24],[7,6,26],[5,4,22],[6,5,25],[7,6,26],[5,4,20,1]],   // konsisten tinggi
        2 => [[6,2,20],[6,2,22],[5,3,20],[6,4,24],[7,5,24],[5,4,22,1]],   // naik
        3 => [[6,5,22],[7,6,24],[6,4,22],[5,3,20],[6,2,18],[5,2,16,1]],   // turun
        4 => [[8,3,34],[9,3,36],[8,2,34],[9,3,38],[8,2,36],[7,2,32]],     // volume tinggi, retention rendah
        5 => [[1,1,8],null,[2,2,10],null,[1,1,8],[2,2,10]],               // sampel kecil, selalu 100%
        6 => [null,[4,2,14],null,[3,1,12],[4,3,14],null],                 // sporadis
    ];

    private array $created = [
        'classes' => [], 'enrollments' => [], 'journals' => [],
        'students' => [], 'users' => [], 'candidates' => [], 'payments' => [],
    ];

    private array $studentPool = [];

    public function run(): void
    {
        if (!$this->taughtValueAllowed()) {
            $this->command->error(
                "Nilai '" . self::TAUGHT_VALUE . "' tidak diizinkan kolom teaching_journals." . self::TAUGHT_COLUMN . '.'
            );
            return;
        }

        $teacherIds = Teacher::orderBy('id')->take(count($this->profiles))->pluck('id')->values();

        if ($teacherIds->isEmpty()) {
            $this->command->error('Tidak ada data guru. Jalankan seeder guru dulu.');
            return;
        }

        if ($teacherIds->count() < count($this->profiles)) {
            $this->command->warn("Guru hanya {$teacherIds->count()} dari " . count($this->profiles) . ' profil. Profil sisanya dilewati.');
        }

        // template enrollment: reguler, sudah punya kelas dan paket
        $tplEnroll = ClassEnrollment::with('class')
            ->whereNotNull('class_id')
            ->whereNotNull('program_package_id')
            ->first();

        // template jurnal: jurnal apa saja
        $tplJournal = DB::table('teaching_journals')->first();

        if (!$tplEnroll || !$tplEnroll->class) {
            $this->command->error('Belum ada enrollment reguler yang punya kelas. Jalankan ClassSeeder dan ClassEnrollmentSeeder dulu.');
            return;
        }

        if (!$tplJournal) {
            $this->command->error('Belum ada jurnal mengajar. Jalankan seeder pertemuan/jurnal dulu.');
            return;
        }

        $scheduleIds = DB::table('class_schedules')->pluck('id')->all();

        // hanya siswa yang belum punya enrollment sama sekali, supaya tidak ada "lanjut" palsu
        $usedStudentIds = DB::table('class_enrollments')->whereNotNull('student_id')->pluck('student_id')->all();
        $this->studentPool = Student::whereNotIn('id', $usedStudentIds)->pluck('id')->shuffle()->all();

        ClassEnrollment::withoutEvents(function () use ($teacherIds, $tplEnroll, $tplJournal, $scheduleIds) {
            DB::transaction(function () use ($teacherIds, $tplEnroll, $tplJournal, $scheduleIds) {
                foreach ($teacherIds as $idx => $teacherId) {
                    foreach ($this->profiles[$idx + 1] as $m => $cell) {
                        if ($cell === null) {
                            continue;
                        }

                        [$done, $cont, $meetings] = $cell;
                        $unpaid = $cell[3] ?? 0;

                        $this->makeClass($tplEnroll, $teacherId, $m, $done, $cont, $unpaid);
                        $this->makeJournals($tplJournal, $scheduleIds, $teacherId, $m, $meetings);
                    }
                }

                // kelas tanpa guru di bulan ini: 3 selesai, 1 lanjut (sudah bayar)
                $this->makeClass($tplEnroll, null, 5, 3, 1);
            });
        });

        Storage::put('retention_demo.json', json_encode($this->created));

        $this->command->info(
            'Dibuat: ' . count($this->created['classes']) . ' kelas, '
            . count($this->created['enrollments']) . ' enrollment, '
            . count($this->created['payments']) . ' pembayaran, '
            . count($this->created['journals']) . ' jurnal, '
            . count($this->created['students']) . ' siswa baru.'
        );

        $this->printExpected($teacherIds);
    }

    /* ------------------------------------------------------------------ */

    /** Cek apakah nilai diizinkan oleh enum kolom (kalau kolom bertipe enum) */
    private function taughtValueAllowed(): bool
    {
        try {
            $col  = DB::selectOne("SHOW COLUMNS FROM teaching_journals WHERE Field = '" . self::TAUGHT_COLUMN . "'");
            $type = $col->Type ?? $col->type ?? '';

            if (str_starts_with($type, 'enum')) {
                return str_contains($type, "'" . self::TAUGHT_VALUE . "'");
            }
        } catch (\Throwable $e) {
            // bukan MySQL / tidak bisa dicek: anggap boleh
        }

        return true;
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
            // ambil nilai pertama dari enum('A','B',...)
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

        return 'Demo Retention';
    }

    private function monthStart(int $m)
    {
        return now()->startOfMonth()->subMonths(5 - $m);
    }

    /** Hari terakhir yang aman dipakai: bulan ini dibatasi sampai kemarin, supaya tidak ada tanggal masa depan */
    private function maxDay(int $m): int
    {
        return $m === 5 ? max(now()->day - 1, 1) : $this->monthStart($m)->daysInMonth;
    }

    /** Ambil siswa kosong yang ada, atau buat siswa baru lengkap dengan akun user */
    private function nextStudentId(): int
    {
        if (!empty($this->studentPool)) {
            return array_pop($this->studentPool);
        }

        // siswa harus punya akun user dengan role Student
        $studentLevelId = DB::table('levels')->where('nama_level', 'Student')->value('id_level');

        $user = $this->makeWithDefaults(User::class, 'users', $studentLevelId ? ['level_id' => $studentLevelId] : []);
        $this->created['users'][] = $user->id;

        $attrs = ['user_id' => $user->id];
        $name  = fake()->name();

        // siswa terhubung ke calon siswa (umur dibaca dari sana)
        if (Schema::hasColumn('students', 'candidate_student_id')) {
            $candidate = $this->makeWithDefaults(CandidateStudent::class, 'candidate_students');
            $this->created['candidates'][] = $candidate->id;

            $attrs['candidate_student_id'] = $candidate->id;
            $name = $candidate->name ?? $name;
        }

        if (Schema::hasColumn('students', 'name')) {
            $attrs['name'] = $name;
        }

        $student = $this->makeWithDefaults(Student::class, 'students', $attrs);
        $this->created['students'][] = $student->id;

        return $student->id;
    }

    /**
     * @param int $cont    jumlah siswa lanjut yang SUDAH membayar (dihitung retention)
     * @param int $unpaid  jumlah siswa yang daftar lanjut tapi BELUM membayar (tidak dihitung)
     */
    private function makeClass(ClassEnrollment $tpl, ?int $teacherId, int $m, int $done, int $cont, int $unpaid = 0): void
    {
        $end   = $this->monthStart($m)->copy()->day(rand(1, $this->maxDay($m)));
        $start = $end->copy()->subMonths(3);

        $class = $tpl->class->replicate();
        $class->teacher_id = $teacherId;
        $class->start_date = $start->toDateString();
        $class->end_date   = $end->toDateString();
        $class->status     = 'Completed';
        $class->save();
        $this->created['classes'][] = $class->id;

        for ($i = 0; $i < $done; $i++) {
            $studentId = $this->nextStudentId();

            $e = $tpl->replicate();
            $e->class_id        = $class->id;
            $e->student_id      = $studentId;
            $e->status          = 'Completed';
            $e->enrollment_date = $start->toDateString();
            $e->save();
            $this->created['enrollments'][] = $e->id;

            // siswa yang daftar lanjut: dibuatkan enrollment baru setelah kelas berakhir
            if ($i < $cont + $unpaid) {
                $next = $tpl->replicate();
                $next->class_id        = null;
                $next->student_id      = $studentId;
                $next->status          = 'Waiting Class';
                $next->enrollment_date = $end->copy()->addDay()->toDateString();
                $next->save();
                $this->created['enrollments'][] = $next->id;

                // hanya yang SUDAH bayar yang mendapat pembayaran (DP) -> terhitung lanjut
                if ($i < $cont) {
                    $payDate = $end->copy()->addDays(rand(1, 5));

                    if ($payDate->isFuture()) {
                        $payDate = today();
                    }

                    $payment = Payment::factory()
                        ->installment('DP', 1500000, 300000, 1200000, $payDate->toDateString())
                        ->create(['enrollment_id' => $next->id]);

                    $this->created['payments'][] = $payment->id;
                }
            }
        }
    }

    private function makeJournals(object $tpl, array $scheduleIds, int $teacherId, int $m, int $count): void
    {
        $row = (array) $tpl;
        unset($row['id']);

        $inserted = 0;
        $tries    = 0;

        while ($inserted < $count && $tries < $count * 10) {
            $tries++;

            $new = $row;
            $new['teacher_id']        = $teacherId;
            $new[self::TAUGHT_COLUMN] = self::TAUGHT_VALUE;   // class_status = Conducted
            $new['status']            = 'Pending';            // status review Kurikulum, biarkan default
            $new['session_date']      = $this->monthStart($m)->copy()->day(rand(1, $this->maxDay($m)))->toDateString();

            foreach (['substitute_teacher_id', 'teacher_leave_id'] as $col) {
                if (array_key_exists($col, $new)) {
                    $new[$col] = null;
                }
            }

            if (array_key_exists('class_schedule_id', $new) && $scheduleIds) {
                $new['class_schedule_id'] = $scheduleIds[array_rand($scheduleIds)];
            }

            try {
                $this->created['journals'][] = DB::table('teaching_journals')->insertGetId($new);
                $inserted++;
            } catch (QueryException $e) {
                // bentrok unique key (jadwal + tanggal): coba lagi dengan kombinasi lain
                continue;
            }
        }

        if ($inserted < $count) {
            $this->command->warn("Guru #{$teacherId} bulan ke-{$m}: hanya {$inserted} dari {$count} jurnal berhasil dibuat.");
        }
    }

    /** Cetak hasil yang HARUS muncul di halaman untuk bulan ini */
    private function printExpected($teacherIds): void
    {
        $loads = [];
        foreach ($teacherIds as $idx => $tid) {
            $cell = $this->profiles[$idx + 1][5] ?? null;
            if ($cell) {
                $loads[$idx] = $cell[2];
            }
        }

        $max  = max($loads ?: [1]);
        $rows = [];

        foreach ($loads as $idx => $load) {
            $cell   = $this->profiles[$idx + 1][5];
            $done   = $cell[0];
            $cont   = $cell[1];
            $unpaid = $cell[3] ?? 0;

            $rr = $cont / $done * 100;
            $tl = $load / $max * 100;

            $rows[] = [
                'Guru ' . ($idx + 1),
                "$cont/$done",
                $unpaid,
                round($rr, 1) . '%',
                $load,
                round($tl, 1) . '%',
                round($rr * 0.7 + $tl * 0.3, 1),
            ];
        }

        usort($rows, fn ($a, $b) => $b[6] <=> $a[6]);

        $this->command->info('Hasil yang HARUS muncul untuk ' . now()->translatedFormat('F Y') . ':');
        $this->command->table(['Guru', 'Lanjut/Selesai', 'Menunggu bayar', 'RR', 'Pertemuan', 'TL', 'Score'], $rows);
        $this->command->info('Siswa "menunggu bayar" TIDAK dihitung lanjut, hanya tampil sebagai catatan.');
        $this->command->info('Plus 1 baris "Belum ada guru": 1/3 = 33,3%, pertemuan 0, score 23,3.');
        $this->command->info('Urutan guru = urutan id di tabel teachers (Guru 1 = id terkecil).');
    }
}