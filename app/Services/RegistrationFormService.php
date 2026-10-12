<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\PrintTemplate;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;

class RegistrationFormService
{
    /** Daftar penanda yang boleh dipakai di template (ditampilkan di editor). */
    public const PLACEHOLDERS = [
        'Umum' => [
            'logo'                => 'Logo Haoyou',
            'tanggal_pendaftaran' => 'Tanggal pendaftaran',
            'nominal_pendaftaran' => 'Nominal biaya pendaftaran (dari Pengaturan)',
            'nominal_aktivitas'   => 'Nominal biaya aktivitas (dari Pengaturan)',
        ],
        'Wali Murid' => [
            'nama_orang_tua'    => 'Nama orang tua',
            'alamat'            => 'Alamat',
            'telepon_orang_tua' => 'Nomor telepon',
            'sosial_media'      => 'Sosial media',
        ],
        'Siswa' => [
            'nama_siswa'    => 'Nama siswa',
            'tanggal_lahir' => 'Tanggal lahir',
            'usia'          => 'Usia',
            'alergi'        => 'Alergi makanan',
            'sekolah'       => 'Nama sekolah',
        ],
        'Program' => [
            'level'  => 'Level',
            'paket'  => 'Nama paket',
            'jadwal' => 'Jadwal kelas (kosong kalau belum masuk kelas)',
        ],
        'Biaya (siswa ini)' => [
            'biaya_pendaftaran' => 'Biaya pendaftaran',
            'biaya_aktivitas'   => 'Biaya aktivitas',
            'biaya_les'         => 'Biaya les (harga paket)',
            'total_pembayaran'  => 'Total pembayaran',
            'diskon' => 'Diskon',
        ],
        'Pembayaran' => [
            'dp_lunas_nominal' => 'DP/Lunas pertama: nominal',
            'dp_lunas_tanggal' => 'DP/Lunas pertama: tanggal',
            'dp_nominal'       => 'DP: nominal',
            'dp_tanggal'       => 'DP: tanggal',
            'lunas_nominal'    => 'Lunas/Pelunasan: nominal',
            'lunas_tanggal'    => 'Lunas/Pelunasan: tanggal',
            'termin1_nominal'  => 'Termin 1: nominal',
            'termin1_tanggal'  => 'Termin 1: tanggal',
            'termin2_nominal'  => 'Termin 2: nominal',
            'termin2_tanggal'  => 'Termin 2: tanggal',
            'termin3_nominal'  => 'Termin 3: nominal',
            'termin3_tanggal'  => 'Termin 3: tanggal',
        ],
    ];

    /** Formulir terisi untuk satu siswa (default: enrollment aktif terbaru). */
    public function generate(Student $student, ?ClassEnrollment $enrollment = null): string
    {
        $student->loadMissing('candidateStudent', 'currentLevel.category');

        $enrollment = $enrollment ?? $student->activeEnrollment()->first();

        abort_if(! $enrollment, 404, 'Siswa ini belum punya program/kelas yang bisa dicetak formulirnya.');
        abort_if((int) $enrollment->student_id !== (int) $student->id, 404, 'Enrollment ini bukan milik siswa tersebut.');

        $enrollment->loadMissing([
            'class.schedules',
            'programPackage.program',
            'privatePackage',
            'payments',
        ]);

        $key = $enrollment->private_package_id ? 'form_private' : 'form_regular';
        $template = PrintTemplate::where('key', $key)->firstOrFail();

        return $this->toPdf($this->render($template->content, $this->valuesFor($student, $enrollment)));
    }

    /** Pratinjau dengan data contoh (isi editor yang belum disimpan pun bisa). */
    public function preview(string $content): string
    {
        return $this->toPdf($this->render($content, $this->sampleValues()));
    }

    /* ---------------------------------------------------------- */

    private function render(string $html, array $values): string
    {
        $map = [];

        foreach ($values as $key => $value) {
            // Sel kosong diganti &nbsp; supaya tinggi barisnya tidak menyusut di PDF
            $map['{' . $key . '}'] = ($value === '' || $value === null) ? '&nbsp;' : $value;
        }

        // Hanya mengganti penanda yang dikenal. Isi template tidak pernah dijalankan sebagai kode.
        return strtr($html, $map);
    }

    private function toPdf(string $body): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            @page { margin: 1.5cm; }
            body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.35; color: #000; }
            table, td, th, p, li { font-family: "Times New Roman", Times, serif; }
            table { border-collapse: collapse; }
            ol, ul { margin: 0; }
        </style></head><body>' . $body . '</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4')->output();
    }

    private function valuesFor(Student $student, ClassEnrollment $enrollment): array
    {
        $cs = $student->candidateStudent;
        $package = $enrollment->private_package_id ? $enrollment->privatePackage : $enrollment->programPackage;

        $registrationFee = (float) $enrollment->registration_fee;
        $activityFee = (float) $enrollment->activity_fee;
        $price = (float) ($package?->price ?? 0);
        $discount = (float) $enrollment->discount;

        $values = [
            'logo'                => $this->logoTag(),
            'tanggal_pendaftaran' => e($enrollment->enrollment_date?->format('d/m/Y')),
            'nominal_pendaftaran' => $this->rupiah((float) Setting::get('registration_fee', 0)),
            'nominal_aktivitas'   => $this->rupiah((float) Setting::get('activity_fee', 0)),

            'nama_orang_tua'    => e($cs?->parent_name),
            'alamat'            => e($cs?->address),
            'telepon_orang_tua' => e($cs?->parent_phone),
            'sosial_media'      => e($cs?->source),

            'nama_siswa'    => e($student->name),
            'tanggal_lahir' => e($cs?->birth_date?->format('d/m/Y')),
            'usia'          => $student->age ? e($student->age . ' tahun') : '',
            'alergi'        => e($cs?->allergy),
            'sekolah'       => e($cs?->school),

            'level'  => e($this->levelLabel($student)),
            'paket'  => e($package?->package_name),
            'jadwal' => implode('<br>', array_map('e', $this->scheduleLines($enrollment))),

            'biaya_pendaftaran' => $this->rupiah($registrationFee),
            'biaya_aktivitas'   => $this->rupiah($activityFee),
            'biaya_les'         => $this->rupiah($price),
            'diskon'            => $this->rupiah($discount),
            'total_pembayaran'  => $this->rupiah(max($price + $registrationFee + $activityFee - $discount, 0)),
        ];

        // Pembayaran dipetakan ke kolom berdasarkan tahapnya
        $slots = [];

        foreach ($enrollment->payments->sortBy('id') as $payment) {
            $slot = match ($payment->payment_stage) {
                'DP'                  => 'dp',
                'Lunas', 'Pelunasan'  => 'lunas',
                'Termin 1'            => 'termin1',
                'Termin 2'            => 'termin2',
                'Termin 3'            => 'termin3',
                default               => null,
            };

            if ($slot && ! isset($slots[$slot])) {
                $slots[$slot] = $payment;
            }

            if (in_array($slot, ['dp', 'lunas'], true) && ! isset($slots['dp_lunas'])) {
                $slots['dp_lunas'] = $payment;
            }
        }

        foreach (['dp', 'lunas', 'dp_lunas', 'termin1', 'termin2', 'termin3'] as $slot) {
            $payment = $slots[$slot] ?? null;

            $values[$slot . '_nominal'] = $payment ? $this->rupiah((float) $payment->amount_paid) : '';
            $values[$slot . '_tanggal'] = $payment?->payment_date?->format('d/m/Y') ?? '';
        }

        return $values;
    }

    private function sampleValues(): array
    {
        $values = [];

        foreach (self::PLACEHOLDERS as $group) {
            foreach ($group as $key => $label) {
                $values[$key] = '[' . e($label) . ']';
            }
        }

        $values['logo'] = $this->logoTag();

        return $values;
    }

    private function logoTag(): string
    {
        $path = public_path('assets/img/logo.png');

        if (! is_file($path)) {
            return '';
        }

        return '<img src="data:image/png;base64,' . base64_encode(file_get_contents($path)) . '" style="width:90px;">';
    }

    private function rupiah(float $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    private function levelLabel(Student $student): ?string
    {
        if (! $student->currentLevel) {
            return null;
        }

        $category = $student->currentLevel->category?->category_name;

        return $category
            ? "{$category} — {$student->currentLevel->level_name}"
            : $student->currentLevel->level_name;
    }

    private function scheduleLines(ClassEnrollment $enrollment): array
    {
        $schedules = $enrollment->class?->schedules;

        if (! $schedules || $schedules->isEmpty()) {
            return [];
        }

        return $schedules
            ->map(fn ($s) => substr($s->day, 0, 3) . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))
            ->values()
            ->all();
    }
}