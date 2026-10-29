<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PrintTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvoiceService
{
    public const TEMPLATE_KEY = 'invoice';

    public const TEMPLATE_KEY_EXTRA = 'invoice_extra';

    /**
     * Placeholder untuk invoice paket.
     */
    public const PLACEHOLDERS = [
        'Invoice' => [
            'logo'          => 'Logo Haoyou',
            'nomor_invoice' => 'Nomor invoice',
            'tanggal_bayar' => 'Tanggal bayar',
            'tahap'         => 'Tahap pembayaran (DP / Termin 1 / Pelunasan)',
            'status'        => 'Status (Lunas / Belum Lunas)',
        ],

        'Siswa' => [
            'nama_siswa' => 'Nama siswa',
            'level'      => 'Level',
            'jadwal'     => 'Jadwal kelas',
            'lokasi'     => 'Lokasi',
        ],

        'Paket & biaya' => [
            'paket'            => 'Nama paket',
            'jumlah_pertemuan' => 'Jumlah pertemuan',
            'jumlah_bulan'     => 'Lama kelas (bulan)',
            'harga_per_bulan'  => 'Rata-rata harga per bulan',
            'harga_paket'      => 'Total biaya les',
            'total_tagihan'    => 'Total tagihan enrollment',
        ],

        'Pembayaran ini' => [
            'sudah_dibayar'    => 'Sudah dibayar sebelumnya',
            'jumlah_dibayar'   => 'Dibayar pada invoice ini',
            'sisa_setelah_ini' => 'Sisa tagihan setelah pembayaran ini',
        ],

        'Pembayaran berikutnya' => [
            'jatuh_tempo_berikutnya' => 'Jatuh tempo berikutnya',
            'contoh_keterangan'      => 'Contoh keterangan transfer',
        ],

        'Lainnya' => [
            'catatan' => 'Catatan enrollment',
        ],
    ];

    /**
     * Placeholder untuk invoice biaya tambahan.
     */
    public const PLACEHOLDERS_EXTRA = [
        'Invoice' => [
            'logo'          => 'Logo Haoyou',
            'nomor_invoice' => 'Nomor invoice',
            'tanggal_bayar' => 'Tanggal pembayaran',
            'jenis_biaya'   => 'Jenis biaya tambahan',
        ],

        'Siswa' => [
            'nama_siswa' => 'Nama siswa',
            'kelas'      => 'Nama kelas',
        ],

        'Pertemuan' => [
            'tanggal_pertemuan' => 'Tanggal pertemuan',
            'jam_pertemuan'     => 'Jam pertemuan',
            'guru'              => 'Nama guru',
            'lokasi'            => 'Lokasi pertemuan',
        ],

        'Pembayaran' => [
            'jumlah_dibayar' => 'Jumlah yang dibayarkan',
            'metode_bayar'   => 'Metode pembayaran',
        ],
    ];

    public function __construct(
        private PaymentInstallmentService $installments
    ) {
    }

    /**
     * Membuat PDF invoice berdasarkan jenis pembayaran.
     */
    public function generate(Payment $payment): string
    {
        if ($payment->isExtra()) {
            return $this->generateExtra($payment);
        }

        $payment->loadMissing([
            'enrollment.student.currentLevel.category',
            'enrollment.class.schedules',
            'enrollment.programPackage.program',
            'enrollment.programPackage.level',
            'enrollment.privatePackage',
        ]);

        abort_if(
            ! $payment->enrollment,
            404,
            'Pembayaran ini tidak memiliki enrollment.'
        );

        $template = PrintTemplate::where(
            'key',
            self::TEMPLATE_KEY
        )->firstOrFail();

        [$values, $hasInstallment, $unpaid] =
            $this->valuesFor($payment);

        $content = $this->render(
            $template->content,
            $values,
            $hasInstallment,
            $unpaid
        );

        return $this->toPdf($content);
    }

    /**
     * Membuat PDF invoice biaya tambahan.
     */
    public function generateExtra(Payment $payment): string
    {
        abort_if(
            ! $payment->isExtra(),
            404,
            'Pembayaran bukan merupakan biaya tambahan.'
        );

        $payment->loadMissing([
            'session.teacher',
            'session.class',
            'enrollment.student',
            'enrollment.class',
        ]);

        abort_if(
            ! $payment->session || ! $payment->enrollment,
            404,
            'Data pertemuan atau enrollment tidak ditemukan.'
        );

        $template = PrintTemplate::where(
            'key',
            self::TEMPLATE_KEY_EXTRA
        )->firstOrFail();

        $values = $this->extraValuesFor($payment);

        // Invoice biaya tambahan tidak memakai blok cicilan paket.
        $content = $this->render(
            $template->content,
            $values,
            false,
            false
        );

        return $this->toPdf($content);
    }

    /**
     * Pratinjau template dengan data contoh.
     */
    public function preview(
        string $content,
        string $key = self::TEMPLATE_KEY
    ): string {
        $placeholders = match ($key) {
            self::TEMPLATE_KEY_EXTRA => self::PLACEHOLDERS_EXTRA,
            default => self::PLACEHOLDERS,
        };

        $values = [];

        foreach ($placeholders as $group) {
            foreach ($group as $placeholder => $label) {
                $values[$placeholder] = '[' . e($label) . ']';
            }
        }

        $values['logo'] = $this->logoTag();

        if ($key === self::TEMPLATE_KEY_EXTRA) {
            return $this->toPdf(
                $this->render($content, $values, false, false)
            );
        }

        $values['rincian_tambahan'] = '';

        return $this->toPdf(
            $this->render($content, $values, true, true)
        );
    }

    /* ==========================================================
     * INVOICE PAKET
     * ========================================================== */

    private function valuesFor(Payment $payment): array
    {
        $enrollment = $payment->enrollment;
        $student = $enrollment->student;

        $package = $enrollment->private_package_id
            ? $enrollment->privatePackage
            : $enrollment->programPackage;

        $months = $this->installments->monthsFor($enrollment);

        $lesTotal = (float) (
            $this->installments->packageTotal($enrollment) ?? 0
        );

        $paid = (float) $payment->amount_paid;
        $remaining = (float) $payment->remaining_bill;

        // Hanya menghitung pembayaran paket sebelumnya.
        $previous = (float) $enrollment->packagePayments()
            ->where('id', '<', $payment->id)
            ->sum('amount_paid');

        $unpaid = $remaining > 0;
        $hasInstallment = $unpaid || $previous > 0;

        $levelName = $this->installments->levelNameFor($enrollment);

        $paymentDate = $payment->payment_date;

        $nextDueDate = null;

        if ($unpaid && $paymentDate) {
            $nextDueDate = Carbon::parse($paymentDate)
                ->addMonthNoOverflow()
                ->day(5);
        }

        $values = [
            'logo' => $this->logoTag(),

            'nomor_invoice' => e($payment->invoice_number),
            'tanggal_bayar' => e(
                $this->dateId($payment->payment_date)
            ),
            'tahap' => e($payment->payment_stage),
            'status' => $unpaid ? 'Belum Lunas' : 'Lunas',

            'nama_siswa' => e($student?->name),
            'level' => e($levelName),

            'jadwal' => implode(
                '<br>',
                array_map(
                    'e',
                    $this->scheduleLines($enrollment)
                )
            ),

            'lokasi' => 'Haoyou Educator',

            'paket' => e($package?->package_name),
            'jumlah_pertemuan' => e($package?->total_meetings),
            'jumlah_bulan' => (string) $months,

            'harga_per_bulan' => $this->rupiah(
                $months > 0
                    ? round($lesTotal / $months)
                    : $lesTotal
            ),

            'harga_paket' => $this->rupiah($lesTotal),

            'rincian_tambahan' =>
                $this->feeRow(
                    'Biaya pendaftaran',
                    (float) $enrollment->registration_fee
                )
                . $this->feeRow(
                    'Biaya aktivitas',
                    (float) $enrollment->activity_fee
                )
                . $this->feeRow(
                    'Diskon',
                    (float) $enrollment->discount,
                    true
                ),

            'total_tagihan' => $this->rupiah(
                (float) $payment->total_bill
            ),

            'catatan' => filled($enrollment->notes)
                ? nl2br(e(trim($enrollment->notes)))
                : '',

            'sudah_dibayar' => $this->rupiah($previous),
            'jumlah_dibayar' => $this->rupiah($paid),
            'sisa_setelah_ini' => $this->rupiah($remaining),

            'jatuh_tempo_berikutnya' => $nextDueDate
                ? e($this->dateId($nextDueDate))
                : '',

            // Bagian kosong (mis. private tanpa level) dibuang supaya tidak ada titik ganda.
            'contoh_keterangan' => $unpaid
                ? e(implode('.', array_filter([
                    Str::lower(Str::slug((string) $student?->name, '')),
                    str_replace(' ', '', (string) $levelName),
                    $this->nextStage($payment->payment_stage),
                ], fn ($part) => $part !== '')))
                : '',
        ];

        return [$values, $hasInstallment, $unpaid];
    }

    /* ==========================================================
     * INVOICE BIAYA TAMBAHAN
     * ========================================================== */

    private function extraValuesFor(Payment $payment): array
    {
        $session = $payment->session;
        $enrollment = $payment->enrollment;

        // Siswa harus berasal dari enrollment pembayar.
        $student = $enrollment->student;

        // Kelas diambil dari enrollment karena sesi pengganti
        // dapat memiliki class_id = null.
        $className = $enrollment->class?->class_name ?? '';

        $sessionDate = $session->session_date;

        $startTime = $session->start_time;
        $endTime = $session->end_time;

        $time = $startTime
            ? substr((string) $startTime, 0, 5)
            : '';

        if ($endTime) {
            $time .= ($time !== '' ? ' - ' : '')
                . substr((string) $endTime, 0, 5);
        }

        $jenisBiaya = match ($payment->payment_type) {
            Payment::TYPE_REPLACEMENT =>
                'Pertemuan tambahan (kelas pengganti)',

            Payment::TYPE_HOME_VISIT =>
                'Biaya guru mengajar ke rumah murid',

            default => (string) $payment->payment_type,
        };

        $location = collect([
            $session->location_type,
            $session->room,
        ])->filter()->unique()->implode(' - ');

        return [
            'logo' => $this->logoTag(),

            'nomor_invoice' => e($payment->invoice_number),

            'tanggal_bayar' => e(
                $this->dateId($payment->payment_date)
            ),

            'jenis_biaya' => e($jenisBiaya),

            'nama_siswa' => e($student?->name),
            'kelas' => e($className),

            'tanggal_pertemuan' => e(
                $this->dateId($sessionDate)
            ),

            'jam_pertemuan' => e($time),

            'guru' => e($session->teacher?->name),

            'lokasi' => e(
                $location !== ''
                    ? $location
                    : 'Haoyou Educator'
            ),

            'jumlah_dibayar' => $this->rupiah(
                (float) $payment->amount_paid
            ),

            'metode_bayar' => e(
                $payment->payment_method
            ),
        ];
    }

    /* ==========================================================
     * RENDER DAN PDF
     * ========================================================== */

    private function render(
        string $html,
        array $values,
        bool $hasInstallment,
        bool $unpaid
    ): string {
        $html = str_replace(
            '<!--RINCIAN_TAMBAHAN-->',
            $values['rincian_tambahan'] ?? '',
            $html
        );

        $html = $this->block(
            $html,
            'JIKA_ADA_CICILAN',
            $hasInstallment
        );

        $html = $this->block(
            $html,
            'JIKA_BELUM_LUNAS',
            $unpaid
        );

        $html = $this->block(
            $html,
            'JIKA_ADA_CATATAN',
            ($values['catatan'] ?? '') !== ''
        );

        $map = [];

        foreach ($values as $key => $value) {
            $map['{' . $key . '}'] =
                ($value === '' || $value === null)
                    ? '&nbsp;'
                    : (string) $value;
        }

        // Template hanya diproses sebagai teks HTML.
        return strtr($html, $map);
    }

    private function block(
        string $html,
        string $tag,
        bool $keep
    ): string {
        return preg_replace_callback(
            '/<!--\s*' . $tag . '\s*-->(.*?)<!--\s*\/'
                . $tag . '\s*-->/s',
            fn ($matches) => $keep ? $matches[1] : '',
            $html
        );
    }

    private function toPdf(string $body): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                @page {
                    margin: 1.6cm;
                }

                body {
                    font-family: Helvetica, Arial, sans-serif;
                    font-size: 11pt;
                    color: #1f1a17;
                }

                table {
                    border-collapse: collapse;
                }
            </style>
        </head>
        <body>'
            . $body
            . '</body>
        </html>';

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->output();
    }

    /* ==========================================================
     * HELPER
     * ========================================================== */

    private function nextStage(?string $stage): string
    {
        return match ($stage) {
            'DP'       => 'Pelunasan',
            'Termin 1' => 'Termin2',
            'Termin 2' => 'Termin3',
            default    => 'Termin',
        };
    }

    private function feeRow(
        string $label,
        float $amount,
        bool $negative = false
    ): string {
        if ($amount <= 0) {
            return '';
        }

        $td = 'padding:9px 8px;'
            . 'border-bottom:1px solid #d9d2ca;'
            . 'vertical-align:top;';

        return '<tr><td style="' . $td . '">'
            . e($label)
            . '</td>'
            . '<td style="' . $td
            . 'text-align:right;white-space:nowrap;">'
            . ($negative ? '- ' : '')
            . $this->rupiah($amount)
            . '</td></tr>';
    }

    private function dateId($date): string
    {
        if (! $date) {
            return '';
        }

        return Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('j F Y');
    }

    private function logoTag(): string
    {
        $path = public_path('assets/img/logo.png');

        if (! is_file($path)) {
            return '';
        }

        return '<img src="data:image/png;base64,'
            . base64_encode(file_get_contents($path))
            . '" style="width:56px;">';
    }

    private function rupiah(float $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    private function scheduleLines($enrollment): array
    {
        $schedules = $enrollment->class?->schedules;

        if (! $schedules || $schedules->isEmpty()) {
            return [];
        }

        return $schedules
            ->map(
                fn ($schedule) => $schedule->day
                    . ' '
                    . substr((string) $schedule->start_time, 0, 5)
            )
            ->values()
            ->all();
    }
}