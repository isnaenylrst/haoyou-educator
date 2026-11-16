<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\PrintTemplate;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

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
            'total_pertemuan'   => 'Total pertemuan paket',
            'pertemuan_pertama' => 'Tanggal pertemuan pertama siswa ini',
            'expired_date'      => 'Expired date (pertemuan pertama + 30 hari, untuk private)',
            'materi'            => 'Materi yang diminta siswa (private)',
        ],
        'Biaya (siswa ini)' => [
            'biaya_pendaftaran' => 'Biaya pendaftaran',
            'biaya_aktivitas'   => 'Biaya aktivitas',
            'biaya_les'         => 'Biaya les (harga paket)',
            'total_pembayaran'  => 'Total pembayaran',
            'diskon' => 'Diskon',
        ],
        'Pembayaran' => [
            'dp_pendaftaran_nominal' => 'Biaya pendaftaran: nominal',
            'dp_pendaftaran_tanggal' => 'Biaya pendaftaran: tanggal dibayar',
            'dp_aktivitas_nominal'   => 'Biaya aktivitas: nominal',
            'dp_aktivitas_tanggal'   => 'Biaya aktivitas: tanggal dibayar (kosong sampai lunas)',
            'dp_awal_tanggal'        => 'Pembayaran pertama: tanggal',
            'termin1_lunas_nominal'  => 'Termin 1 / Lunas / Pelunasan: nominal',
            'termin1_lunas_tanggal'  => 'Termin 1 / Lunas / Pelunasan: tanggal',
            'dp_lunas_nominal' => 'DP/Lunas pertama: nominal',
            'dp_lunas_tanggal' => 'DP/Lunas pertama: tanggal',
            'dp_nominal'       => 'DP: nominal',
            'dp_tanggal'       => 'DP: tanggal',
            'lunas_nominal'    => 'Lunas/Pelunasan: nominal',
            'lunas_tanggal'    => 'Lunas/Pelunasan: tanggal',
            'termin1_nominal'  => 'Termin 1: nominal (rencana jadwal, atau yang sudah dibayar)',
            'termin1_tanggal'  => 'Termin 1: tanggal',
            'termin2_nominal'  => 'Termin 2: nominal (rencana jadwal, atau yang sudah dibayar)',
            'termin2_tanggal'  => 'Termin 2: tanggal',
            'termin3_nominal'  => 'Termin 3: nominal (rencana jadwal, atau yang sudah dibayar)',
            'termin3_tanggal'  => 'Termin 3: tanggal',
        ],
    ];

    /**
     * PaymentInstallmentService opsional supaya tempat yang masih memakai
     * `new RegistrationFormService()` tidak error; lewat container otomatis terisi.
     */
    public function __construct(private ?PaymentInstallmentService $installments = null)
    {
    }

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
            'packagePayments',
        ]);

        $key = $enrollment->private_package_id ? 'form_private' : 'form_regular';
        $template = PrintTemplate::where('key', $key)->firstOrFail();

        // Tabel pembayaran formulir reguler mengikuti skema siswa (Termin atau Lunas)
        return $this->toPdf($this->render(
            $template->content,
            $this->valuesFor($student, $enrollment),
            $enrollment->payment_scheme === 'Termin',
        ));
    }

    /** Pratinjau dengan data contoh (isi editor yang belum disimpan pun bisa). */
    public function preview(string $content): string
    {
        return $this->toPdf($this->render($content, $this->sampleValues()));
    }

    /* ---------------------------------------------------------- */

    private function installments(): PaymentInstallmentService
    {
        return $this->installments ??= new PaymentInstallmentService();
    }

    /**
     * @param  bool|null  $termin  true = skema Termin, false = skema Lunas, null = tampilkan keduanya (pratinjau editor)
     */
    private function render(string $html, array $values, ?bool $termin = null): string
    {
        // Bagian template khusus skema: <!--JIKA_TERMIN-->...<!--/JIKA_TERMIN--> dan <!--JIKA_LUNAS-->...<!--/JIKA_LUNAS-->
        $html = $this->block($html, 'JIKA_TERMIN', $termin !== false);
        $html = $this->block($html, 'JIKA_LUNAS', $termin !== true);

        $map = [];

        foreach ($values as $key => $value) {
            // Sel kosong diganti &nbsp; supaya tinggi barisnya tidak menyusut di PDF
            $map['{' . $key . '}'] = ($value === '' || $value === null) ? '&nbsp;' : $value;
        }

        // Hanya mengganti penanda yang dikenal. Isi template tidak pernah dijalankan sebagai kode.
        return strtr($html, $map);
    }

    private function block(string $html, string $tag, bool $keep): string
    {
        return preg_replace_callback(
            '/<!--\s*' . $tag . '\s*-->(.*?)<!--\s*\/' . $tag . '\s*-->/s',
            fn ($matches) => $keep ? $matches[1] : '',
            $html
        );
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
        // Total biaya les = harga paket (sama dengan yang dipakai total_bill)
        $lesTotal = (float) ($this->installments()->packageTotal($enrollment) ?? 0);

        // Paket private: kalau service belum menghitung total, pakai harga paket langsung
        if ($lesTotal <= 0 && $package) {
            $lesTotal = (float) ($package->price ?? 0);
        }

        $discount = (float) $enrollment->discount;

        $firstSession = $this->firstSessionFor($enrollment);

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

            'total_pertemuan'   => e($package?->total_meetings),
            'pertemuan_pertama' => $firstSession ? e(Carbon::parse($firstSession->session_date)->format('d/m/Y')) : '',
            // Penggantian hari private berlaku maksimal 30 hari sejak pertemuan pertama
            'expired_date'      => ($firstSession && $enrollment->private_package_id)
                ? e(Carbon::parse($firstSession->session_date)->addDays(30)->format('d/m/Y'))
                : '',
            'materi'            => $enrollment->private_package_id && filled($enrollment->requested_material)
                ? nl2br(e(trim($enrollment->requested_material)))
                : '',

            'biaya_pendaftaran' => $this->rupiah($registrationFee),
            'biaya_aktivitas'   => $this->rupiah($activityFee),
            'biaya_les'         => $this->rupiah($lesTotal),
            'diskon'            => $this->rupiah($discount),
            'total_pembayaran'  => $this->rupiah(max($lesTotal + $registrationFee + $activityFee - $discount, 0)),
        ];

        // Pembayaran dipetakan ke kolom berdasarkan tahapnya
        $slots = [];

        foreach ($enrollment->packagePayments->sortBy('id') as $payment) {
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

        // Tabel pembayaran formulir reguler, satu kolom per komponen:
        //   Pendaftaran | Aktivitas | Termin 1 / Lunas | Termin 2 | Termin 3
        // Nominal komponen langsung diisi (termin: sisa biaya les dibagi 3). Tanggal diisi dari
        // pembayaran yang benar-benar tercatat, jadi bisa berbeda per kolom (mis. aktivitas dibayar
        // bersama Termin 1). Tanggal dikosongkan sampai komponennya lunas, diisi tangan di formulir.
        $ordered = $enrollment->packagePayments->sortBy('id')->values();
        $first   = $ordered->get(0);
        $due     = $this->installments()->componentsDue($enrollment);
        $fmt     = fn ($date) => $date?->format('d/m/Y') ?? '';

        $previous = 0.0;
        $regPaid  = 0.0;
        $actPaid  = 0.0;
        $regDate  = null;
        $actDate  = null;
        $lesByStage = [];

        foreach ($ordered as $payment) {
            $amount = (float) $payment->amount_paid;
            $parts  = $this->installments()->paymentComponents($enrollment, $previous, $amount);

            $regPaid += $parts['pendaftaran'];
            $actPaid += $parts['aktivitas'];

            if ($parts['pendaftaran'] > 0) {
                $regDate = $payment->payment_date;
            }

            if ($parts['aktivitas'] > 0) {
                $actDate = $payment->payment_date;
            }

            $lesByStage[$payment->payment_stage] = ['amount' => $parts['les'], 'date' => $payment->payment_date];
            $previous += $amount;
        }

        $values['dp_awal_tanggal']        = $fmt($first?->payment_date);
        $values['dp_pendaftaran_nominal'] = $due['pendaftaran'] > 0 ? $this->rupiah($due['pendaftaran']) : '';
        $values['dp_pendaftaran_tanggal'] = ($due['pendaftaran'] > 0 && $regPaid + 0.005 >= $due['pendaftaran']) ? $fmt($regDate) : '';
        $values['dp_aktivitas_nominal']   = $due['aktivitas'] > 0 ? $this->rupiah($due['aktivitas']) : '';
        $values['dp_aktivitas_tanggal']   = ($due['aktivitas'] > 0 && $actPaid + 0.005 >= $due['aktivitas']) ? $fmt($actDate) : '';

        $values['termin1_lunas_nominal'] = '';
        $values['termin1_lunas_tanggal'] = '';

        if ($first) {
            $dp = (float) $first->amount_paid;

            if ($first->payment_stage === 'Lunas') {
                // Dibayar sekaligus: biaya les masuk kolom Lunas
                $les = $lesByStage['Lunas'] ?? null;

                if ($les && $les['amount'] > 0) {
                    $values['termin1_lunas_nominal'] = $this->rupiah($les['amount']);
                    $values['termin1_lunas_tanggal'] = $fmt($les['date']);
                }
            } elseif ($enrollment->payment_scheme === 'Termin') {
                // Termin 1-3: nominal sesuai kesepakatan (sisa biaya les dibagi 3); yang sudah dibayar memakai
                // nominal sebenarnya. Aktivitas yang belum lunas ada di kolom Aktivitas, bukan di Termin 1.
                $shares = $this->installments()->terminLesShares($enrollment, $dp)['shares'];

                foreach ([1, 2, 3] as $n) {
                    $paid   = $lesByStage['Termin ' . $n] ?? null;
                    $amount = $paid ? $paid['amount'] : $shares[$n - 1];
                    $text   = $amount > 0 ? $this->rupiah($amount) : '';
                    $date   = $paid ? $fmt($paid['date']) : '';

                    $values['termin' . $n . '_nominal'] = $text;
                    $values['termin' . $n . '_tanggal'] = $date;

                    if ($n === 1) {
                        $values['termin1_lunas_nominal'] = $text;
                        $values['termin1_lunas_tanggal'] = $date;
                    }
                }
            } else {
                // Skema Lunas dengan DP: pelunasan membayar sisa (aktivitas + biaya les)
                $paid   = $lesByStage['Pelunasan'] ?? null;
                $amount = $paid
                    ? $paid['amount']
                    : $this->installments()->terminLesShares($enrollment, $dp)['les_remaining'];

                if ($amount > 0) {
                    $values['termin1_lunas_nominal'] = $this->rupiah($amount);
                    $values['termin1_lunas_tanggal'] = $paid ? $fmt($paid['date']) : '';
                }
            }
        }

        // Formulir private (kolom DP / Lunas): nominal terisi otomatis dari tagihan,
        // tanggal tetap kosong sampai benar-benar dibayar.
        if ($enrollment->private_package_id) {
            $total  = max($lesTotal + $registrationFee + $activityFee - $discount, 0);
            $dpPaid = (float) ($slots['dp']->amount_paid ?? 0);

            if (! isset($slots['lunas'])) {
                $remaining = $total - $dpPaid;

                $values['lunas_nominal'] = $remaining > 0 ? $this->rupiah($remaining) : '';
                $values['lunas_tanggal'] = '';
            }
        }

        return $values;
    }

    /**
     * Pertemuan pertama siswa ini: pertemuan reguler pertama kelas pada/setelah tanggal pendaftaran
     * (siswa yang masuk di tengah kelas dihitung dari pertemuan berikutnya). Jatuh ke pertemuan
     * pertama kelas kalau tidak ada yang lebih baru.
     */
    private function firstSessionFor(ClassEnrollment $enrollment): ?ClassSession
    {
        if (! $enrollment->class_id) {
            return null;
        }

        $base = ClassSession::where('class_id', $enrollment->class_id)->where('kind', 'Regular');

        return (clone $base)
            ->whereDate('session_date', '>=', $enrollment->enrollment_date?->toDateString() ?? now()->toDateString())
            ->orderBy('session_date')
            ->first()
            ?? $base->orderBy('session_date')->first();
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