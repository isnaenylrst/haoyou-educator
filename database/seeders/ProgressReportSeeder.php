<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Models\ProgressReport;
use App\Models\User;
use Database\Factories\ProgressReportFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgressReportSeeder extends Seeder
{
    /**
     * DUMMY DATA untuk halaman admin Progress Report.
     *
     * Sebagian enrollment (~60%, minimal 12) diberi 1 laporan. Tahap proses dibagi merata
     * (bukan acak murni), jadi semua tahap pasti ada:
     *   Draft / Dikembalikan / Menunggu Review / Disetujui
     *
     * Status follow up diturunkan dari kenyataan (sama dengan definisi Retention Rate):
     * "Lanjut" hanya kalau siswa punya enrollment lanjutan DAN sudah ada pembayaran masuk.
     * Kelas yang masih berjalan selalu "Belum".
     *
     * Enrollment yang tidak kebagian laporan dibiarkan kosong. Enrollment yang sudah punya
     * laporan dilewati, jadi seeder aman dijalankan ulang.
     *
     * Jalankan SETELAH PaymentSeeder.
     *
     * Catatan: tab "Belum Diisi" dihitung MissingProgressReportFinder dari jatuh tempo,
     * bukan dari tabel ini.
     */
    public function run(): void
    {
        $sudahPunya = ProgressReport::query()
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id');

        $enrollments = ClassEnrollment::with('class')
            ->whereIn('status', ['Active', 'Completed'])
            ->whereHas('class', fn ($q) => $q->whereNotNull('teacher_id'))
            ->whereNotIn('id', $sudahPunya)
            ->get()
            ->shuffle()
            ->values();

        if ($enrollments->isEmpty()) {
            $this->command?->warn('Tidak ada enrollment Active/Completed (dengan guru) yang belum punya laporan.');
            return;
        }

        $jumlah = min($enrollments->count(), max(12, (int) ceil($enrollments->count() * 0.6)));

        $reviewerId = $this->curriculumUserId();

        $tahap = ['draft', 'returned', 'pending', 'approved'];

        foreach ($enrollments->take($jumlah) as $i => $enrollment) {
            $classEnd = $enrollment->class->end_date ? Carbon::parse($enrollment->class->end_date) : null;
            $ended    = $classEnd && $classEnd->isPast();

            // tanggal laporan: antara tanggal daftar dan (akhir kelas / hari ini)
            $date = Carbon::parse(fake()->dateTimeBetween($enrollment->enrollment_date, $ended ? $classEnd : 'now'));
            $type = 'Bulanan';

            $factory = ProgressReport::factory();
            $factory = match ($tahap[$i % 4]) {
                'draft'    => $factory->draft(),
                'returned' => $factory->returned($date),
                'pending'  => $factory->pendingReview($date),
                default    => $factory->approved($date, $reviewerId),
            };

            // Lanjut = ada enrollment lanjutan DAN sudah ada pembayaran masuk
            $continued = ClassEnrollment::where('student_id', $enrollment->student_id)
                ->where('id', '>', $enrollment->id)
                ->where(fn ($q) => $q->whereNull('class_id')->orWhere('class_id', '!=', $enrollment->class_id))
                ->where('status', '!=', 'Cancelled')
                ->whereIn('id', Payment::select('enrollment_id')->where('amount_paid', '>', 0))
                ->exists();

            $followUp = match (true) {
                ! $ended   => 'Belum',                                                   // kelas masih jalan
                $continued => fake()->boolean(90) ? 'Lanjut' : 'Belum',                   // sebagian belum sempat dicatat admin
                default    => fake()->boolean(75) ? 'Tidak Lanjut' : 'Belum',
            };

            $factory->create([
                'student_id'       => $enrollment->student_id,
                'teacher_id'       => $enrollment->class->teacher_id,
                'enrollment_id'    => $enrollment->id,
                'report_type'      => $type,
                'report_period'    => ProgressReportFactory::periodLabel($type, $date),
                'follow_up_status' => $followUp,
                'followed_up_at'   => $followUp === 'Belum'
                    ? null
                    : $date->copy()->addDays(3)->min(now())->toDateTimeString(),
                'follow_up_note'   => $followUp === 'Belum'
                    ? null
                    : ($followUp === 'Lanjut'
                        ? 'Orang tua berencana melanjutkan ke level berikutnya.'
                        : 'Siswa tidak melanjutkan, alasan jadwal bentrok.'),
            ]);
        }

        $this->command?->info("Progress report dummy dibuat: {$jumlah}.");
    }

    /** User berperan Curriculum sebagai reviewer; fallback ke user pertama. */
    private function curriculumUserId(): ?int
    {
        try {
            $id = DB::table('users')
                ->join('levels', 'levels.id_level', '=', 'users.level_id')
                ->where('levels.nama_level', 'Curriculum')
                ->value('users.id');
        } catch (\Throwable $e) {
            $id = null;
        }

        return $id ?: User::query()->value('id');
    }
}