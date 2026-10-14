<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PrintTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InvoiceService
{
    public const TEMPLATE_KEY = 'invoice';

    /** Penanda untuk editor template (Pengaturan » Print Dokumen). */
    public const PLACEHOLDERS = [
        'Invoice' => [
            'logo'          => 'Logo Haoyou',
            'nomor_invoice' => 'Nomor invoice',
            'tanggal_bayar' => 'Tanggal bayar',
            'tahap'         => 'Tahap pembayaran (DP / Termin 1 / Pelunasan ...)',
            'status'        => 'Status (Lunas / Belum Lunas)',
        ],
        'Siswa' => [
            'nama_siswa' => 'Nama siswa',
            'level'      => 'Level',
            'jadwal'     => 'Jadwal kelas',
            'lokasi'     => 'Lokasi',
        ],
        'Paket & biaya' => [
            'paket'             => 'Nama paket',
            'jumlah_pertemuan'  => 'Jumlah pertemuan',
            'jumlah_bulan'      => 'Lama kelas (bulan)',
            'harga_per_bulan'   => 'Rata-rata harga per bulan (harga paket ÷ lama kelas)',
            'harga_paket'       => 'Total biaya les (harga paket)',
            'total_tagihan'     => 'Total tagihan enrollment',
        ],
        'Pembayaran ini' => [
            'sudah_dibayar'    => 'Sudah dibayar sebelumnya',
            'jumlah_dibayar'   => 'Dibayar pada invoice ini',
            'sisa_setelah_ini' => 'Sisa tagihan setelah pembayaran ini',
        ],
        'Pembayaran berikutnya' => [
            'jatuh_tempo_berikutnya' => 'Jatuh tempo (tanggal bayar + 1 bulan)',
            'contoh_keterangan'      => 'Contoh keterangan transfer',
        ],
    ];

    /**
     * Penanda khusus di template (berupa komentar HTML supaya aman di editor TinyMCE):
     *   <!--JIKA_ADA_CICILAN--> ... <!--/JIKA_ADA_CICILAN-->  tampil jika ada cicilan (bukan bayar tunggal langsung lunas)
     *   <!--JIKA_BELUM_LUNAS--> ... <!--/JIKA_BELUM_LUNAS-->  tampil jika sisa tagihan > 0
     *   <!--RINCIAN_TAMBAHAN-->                              diganti baris biaya pendaftaran/aktivitas/diskon (hanya yang > 0)
     */
    public function __construct(private PaymentInstallmentService $installments)
    {
    }

    public function generate(Payment $payment): string
    {
        $payment->loadMissing([
            'enrollment.student.currentLevel.category',
            'enrollment.class.schedules',
            'enrollment.programPackage.program',
            'enrollment.privatePackage',
        ]);

        abort_if(! $payment->enrollment, 404, 'Pembayaran ini tidak punya enrollment.');

        $template = PrintTemplate::where('key', self::TEMPLATE_KEY)->firstOrFail();

        [$values, $hasInstallment, $unpaid] = $this->valuesFor($payment);

        return $this->toPdf($this->render($template->content, $values, $hasInstallment, $unpaid));
    }

    /** Pratinjau dengan data contoh (isi editor yang belum disimpan pun bisa). */
    public function preview(string $content): string
    {
        $values = [];

        foreach (self::PLACEHOLDERS as $group) {
            foreach ($group as $key => $label) {
                $values[$key] = '[' . e($label) . ']';
            }
        }

        $values['logo'] = $this->logoTag();
        $values['rincian_tambahan'] = '';

        return $this->toPdf($this->render($content, $values, true, true));
    }

    /* ---------------------------------------------------------- */

    private function valuesFor(Payment $payment): array
    {
        $enrollment = $payment->enrollment;
        $student = $enrollment->student;
        $package = $enrollment->private_package_id ? $enrollment->privatePackage : $enrollment->programPackage;

        $months = $this->installments->monthsFor($enrollment);
        // Total biaya les = harga paket apa adanya (bukan per bulan)
        $lesTotal = (float) ($this->installments->packageTotal($enrollment) ?? 0);

        $paid = (float) $payment->amount_paid;
        $remaining = (float) $payment->remaining_bill;
        $previous = (float) $enrollment->payments()->where('id', '<', $payment->id)->sum('amount_paid');

        $unpaid = $remaining > 0;
        $hasInstallment = $unpaid || $previous > 0;

        $levelName = $student?->currentLevel?->level_name;

        $values = [
            'logo'          => $this->logoTag(),
            'nomor_invoice' => e($payment->invoice_number),
            'tanggal_bayar' => e($this->dateId($payment->payment_date)),
            'tahap'         => e($payment->payment_stage),
            'status'        => $unpaid ? 'Belum Lunas' : 'Lunas',

            'nama_siswa' => e($student?->name),
            'level'      => e($levelName),
            'jadwal'     => implode('<br>', array_map('e', $this->scheduleLines($enrollment))),
            'lokasi'     => 'Haoyou Educator',

            'paket'            => e($package?->package_name),
            'jumlah_pertemuan' => e($package?->total_meetings),
            'jumlah_bulan'     => (string) $months,
            'harga_per_bulan'  => $this->rupiah($months > 0 ? round($lesTotal / $months) : $lesTotal),
            'harga_paket'      => $this->rupiah($lesTotal),
            'rincian_tambahan' =>
                $this->feeRow('Biaya pendaftaran', (float) $enrollment->registration_fee)
                . $this->feeRow('Biaya aktivitas', (float) $enrollment->activity_fee)
                . $this->feeRow('Diskon', (float) $enrollment->discount, true),
            'total_tagihan'    => $this->rupiah((float) $payment->total_bill),

            'sudah_dibayar'    => $this->rupiah($previous),
            'jumlah_dibayar'   => $this->rupiah($paid),
            'sisa_setelah_ini' => $this->rupiah($remaining),

            'jatuh_tempo_berikutnya' => $unpaid
                ? e($this->dateId($payment->payment_date->copy()->addMonthNoOverflow()))
                : '',
            'contoh_keterangan' => $unpaid
                ? e(Str::lower(Str::slug((string) $student?->name, '')) . '.' . str_replace(' ', '', (string) $levelName) . '.' . $this->nextStage($payment->payment_stage))
                : '',
        ];

        return [$values, $hasInstallment, $unpaid];
    }

    private function render(string $html, array $values, bool $hasInstallment, bool $unpaid): string
    {
        $html = str_replace('<!--RINCIAN_TAMBAHAN-->', $values['rincian_tambahan'] ?? '', $html);
        $html = $this->block($html, 'JIKA_ADA_CICILAN', $hasInstallment);
        $html = $this->block($html, 'JIKA_BELUM_LUNAS', $unpaid);

        $map = [];

        foreach ($values as $key => $value) {
            $map['{' . $key . '}'] = ($value === '' || $value === null) ? '&nbsp;' : $value;
        }

        // Hanya mengganti penanda yang dikenal. Isi template tidak pernah dijalankan sebagai kode.
        return strtr($html, $map);
    }

    private function block(string $html, string $tag, bool $keep): string
    {
        return preg_replace_callback(
            '/<!--\s*' . $tag . '\s*-->(.*?)<!--\s*\/' . $tag . '\s*-->/s',
            fn ($m) => $keep ? $m[1] : '',
            $html
        );
    }

    private function toPdf(string $body): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            @page { margin: 1.6cm; }
            body { font-family: Helvetica, Arial, sans-serif; font-size: 11pt; color: #1f1a17; }
            table { border-collapse: collapse; }
        </style></head><body>' . $body . '</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4')->output();
    }

    private function nextStage(?string $stage): string
    {
        return match ($stage) {
            'DP'       => 'Pelunasan',
            'Termin 1' => 'Termin2',
            'Termin 2' => 'Termin3',
            default    => 'Termin',
        };
    }

    private function feeRow(string $label, float $amount, bool $negative = false): string
    {
        if ($amount <= 0) {
            return '';
        }

        $td = 'padding:9px 8px;border-bottom:1px solid #d9d2ca;vertical-align:top;';

        return '<tr><td style="' . $td . '">' . e($label) . '</td>'
            . '<td style="' . $td . 'text-align:right;white-space:nowrap;">'
            . ($negative ? '- ' : '') . $this->rupiah($amount) . '</td></tr>';
    }

    private function dateId($date): string
    {
        return $date ? $date->copy()->locale('id')->translatedFormat('j F Y') : '';
    }

    private function logoTag(): string
    {
        $path = public_path('assets/img/logo.png');

        if (! is_file($path)) {
            return '';
        }

        return '<img src="data:image/png;base64,' . base64_encode(file_get_contents($path)) . '" style="width:56px;">';
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
            ->map(fn ($s) => $s->day . ' ' . substr($s->start_time, 0, 5))
            ->values()
            ->all();
    }
}