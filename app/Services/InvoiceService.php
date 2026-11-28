<?php

namespace App\Services;

use App\Models\ClassSession;
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
            'watermark'     => 'Watermark logo di latar invoice (public/assets/img/invoice-watermark.png)',
            'nomor_invoice' => 'Nomor invoice',
            'tanggal_bayar'   => 'Tanggal bayar (format lama)',
            'tanggal_tagihan' => 'Tanggal tagihan / jatuh tempo pembayaran yang harus dibayar sekarang',
            'tahap'           => 'Tahap yang harus dibayar sekarang (mis. Biaya Aktivitas & Termin 1)',
            'status'          => 'Status (Lunas / Belum Lunas)',
            'rincian_tagihan' => 'Baris rincian bayar (otomatis: Rincian | Jatuh Tempo | Nominal | Keterangan), baris yang harus dibayar berwarna merah',
        ],

        'Siswa' => [
            'nama_siswa' => 'Nama siswa',
            'level'      => 'Level',
            'kelas'      => 'Nama kelas',
            'jadwal'     => 'Jadwal kelas',
            'lokasi'     => 'Lokasi',
            'daftar_pertemuan' => 'Tanggal pertemuan per bulan',
        ],

        'Paket & biaya' => [
            'paket'            => 'Nama paket',
            'jumlah_pertemuan' => 'Jumlah pertemuan',
            'jumlah_bulan'     => 'Lama kelas (bulan)',
            'harga_per_bulan'  => 'Rata-rata harga per bulan',
            'harga_paket'      => 'Total biaya les',
            'total_tagihan'    => 'Total tagihan enrollment',
            'alamat_home_visit' => 'Alamat Home Visit (baris tampil hanya kalau kelas private Home Visit)',
        ],

        'Pembayaran ini' => [
            'sudah_dibayar'    => 'Sudah dibayar sebelumnya',
            'rincian_dibayar'  => 'Komponen yang dibayar (mis. Biaya Aktivitas & Termin 1)',
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
            'tanggal_bayar' => 'Tanggal pembayaran (format lama)',
            'tanggal_tagihan' => 'Tanggal tagihan / jatuh tempo',
            'status'          => 'Status (Lunas / Belum Lunas)',
            'jenis_biaya'   => 'Jenis biaya tambahan (gabungan kalau Home Visit + Pengganti)',
            'rincian_biaya' => 'Baris rincian biaya format lama, satu baris per jenis (otomatis)',
            'rincian_tagihan' => 'Baris rincian bayar (otomatis: Rincian | Jatuh Tempo | Nominal | Keterangan | Tanggal Lunas)',
            'total_tagihan'   => 'Total tagihan',
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
            // Home Visit yang dibayar saat konversi satu nomor dengan invoice paket: tampilkan invoice paketnya
            $package = Payment::query()
                ->where('enrollment_id', $payment->enrollment_id)
                ->where('invoice_number', $payment->invoice_number)
                ->where('payment_type', Payment::TYPE_PACKAGE)
                ->orderBy('id')
                ->first();

            return $package ? $this->generate($package) : $this->generateExtra($payment);
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

        return $this->toPdf($content, true); // invoice paket: A4 landscape (sesuai template Word)
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

        // Home Visit + Pertemuan Pengganti berbagi satu nomor invoice: tampil sebagai satu invoice
        $group = Payment::query()
            ->where('enrollment_id', $payment->enrollment_id)
            ->where('invoice_number', $payment->invoice_number)
            ->whereIn('payment_type', Payment::EXTRA_TYPES)
            ->orderBy('id')
            ->get();

        if ($group->isEmpty()) {
            $group = collect([$payment]);
        }

        $values = $this->extraValuesFor($payment, $group);

        // Invoice biaya tambahan tidak memakai blok cicilan paket.
        $content = $this->render(
            $template->content,
            $values,
            false,
            false
        );

        return $this->toPdf($content, true);
    }

    /**
     * Invoice tagihan sebelum ada pembayaran: calon siswa belum menjadi siswa dan belum punya enrollment,
     * jadi isinya dihitung dari draf konversi (pilihan program, skema bayar, diskon, jatuh tempo).
     */
    public function generateForDraft(\App\Models\ConvertDraft $draft, \App\Models\CandidateStudent $candidate): string
    {
        abort_if(empty($draft->selection), 404, 'Program belum dipilih.');

        $template = PrintTemplate::where('key', self::TEMPLATE_KEY)->firstOrFail();

        $content = $this->render(
            $template->content,
            $this->valuesForDraft($draft, $candidate),
            true,
            true
        );

        return $this->toPdf($content, true);
    }

    private function valuesForDraft(\App\Models\ConvertDraft $draft, \App\Models\CandidateStudent $candidate): array
    {
        $selection = $draft->selection ?? [];
        $isPrivate = ($selection['package_type'] ?? 'program') === 'private';

        $package = $isPrivate
            ? \App\Models\PrivatePackage::findOrFail($selection['private_package_id'])
            : \App\Models\ProgramPackage::with(['program', 'level'])->findOrFail($selection['program_package_id']);

        // Aturan skema sama dengan ConvertController::resolvePlan
        $scheme = ($draft->payment_scheme === 'Termin' && ! $isPrivate && $package->supportsInstallment())
            ? 'Termin'
            : 'Lunas';

        $terminCount = $scheme === 'Termin'
            ? min(
                max((int) ($package->installmentTermCount() ?? 3), PaymentInstallmentService::TERMIN_MIN),
                PaymentInstallmentService::TERMIN_MAX
            )
            : 3;

        $classId = $selection['join_class_id'] ?? $selection['class_id'] ?? null;
        $kelas   = $classId ? \App\Models\ClassModel::with('schedules')->find($classId) : null;

        // Home Visit: kelas private baru dengan semua pertemuan Home Visit, Rp50.000 per pertemuan
        $draftHomeVisit = $isPrivate && ! empty($selection['home_visit']) && empty($selection['join_class_id']);

        // Enrollment sementara (tidak disimpan) supaya angka tagihan dihitung PaymentInstallmentService
        // dengan rumus yang sama persis dengan invoice setelah DP (termasuk Home Visit lewat home_visit_address).
        $enrollment = new \App\Models\ClassEnrollment([
            'class_id'         => $kelas?->id,
            'private_package_id' => $isPrivate ? $package->id : null,
            'home_visit_address' => $draftHomeVisit ? (trim((string) ($selection['home_visit_address'] ?? '')) ?: null) : null,
            'registration_fee' => (float) \App\Models\Setting::get('registration_fee', 0),
            'activity_fee'     => (float) \App\Models\Setting::get('activity_fee', 0),
            'discount'         => (float) $draft->discount,
            'payment_scheme'   => $scheme,
            'termin_count'     => $terminCount,
            'form_manual'      => $draft->form_manual,
        ]);

        $enrollment->setRelation('programPackage', $isPrivate ? null : $package);
        $enrollment->setRelation('privatePackage', $isPrivate ? $package : null);
        $enrollment->setRelation('class', $kelas);
        $enrollment->setRelation('student', null);

        // Home Visit: kelas private baru dengan semua pertemuan Home Visit, Rp50.000 per pertemuan
        $hvSessions = ($isPrivate && ! empty($selection['home_visit']) && empty($selection['join_class_id']))
            ? (int) ($package->total_meetings ?? 0)
            : 0;

        $plan = $this->billingPlan($enrollment, collect());
        $current = $this->currentDueIndexes($plan);

        $tahap = $current !== []
            ? implode(' & ', array_map(fn (int $i) => $plan[$i]['label'], $current))
            : 'DP';

        $billDate = $current !== [] ? $plan[$current[0]]['due'] : null;

        $months    = $this->installments->monthsFor($enrollment);
        $lesTotal  = (float) ($this->installments->packageTotal($enrollment) ?? 0);
        $totalBill = (float) ($this->installments->totalBillFor($enrollment) ?? 0);

        $levelName = $isPrivate
            ? null
            : ($package->level?->level_name
                ?? (! empty($selection['level_id']) ? \App\Models\ProgramLevel::find($selection['level_id'])?->level_name : null));

        return [
            'logo'          => $this->logoTag(),
            'watermark'     => $this->watermarkTag(),
            'nomor_invoice' => e($draft->invoice_number ?: '-'),

            'tanggal_bayar'   => '',
            'tanggal_tagihan' => e($this->dateId($billDate)),
            'tahap'           => e($tahap),
            'status'          => 'Belum Lunas',
            'rincian_tagihan' => $this->billingRows($plan, $current, 0.0),
            'alamat_home_visit' => ($hvSessions > 0 && filled($selection['home_visit_address'] ?? null))
                ? nl2br(e(trim($selection['home_visit_address'])))
                : '',

            'nama_siswa'       => e($candidate->name),
            'level'            => e($levelName),
            'kelas'            => e($kelas?->class_name ?: 'Belum masuk kelas'),
            'daftar_pertemuan' => $this->sessionDatesByMonth($enrollment) ?: '-',
            'jadwal'           => implode('<br>', array_map('e', $this->scheduleLines($enrollment))) ?: '-',
            'lokasi'           => 'Haoyou Educator',

            'paket'            => e($package->package_name),
            'jumlah_pertemuan' => e($package->total_meetings),
            'jumlah_bulan'     => (string) $months,
            'harga_per_bulan'  => $this->rupiah($months > 0 ? round($lesTotal / $months) : $lesTotal),
            'harga_paket'      => $this->rupiah($lesTotal),
            'total_tagihan'    => $this->rupiah($totalBill),

            'catatan' => filled($draft->notes) ? nl2br(e(trim($draft->notes))) : '',

            'sudah_dibayar'          => $this->rupiah(0),
            'rincian_dibayar'        => '',
            'jumlah_dibayar'         => $this->rupiah(0),
            'sisa_setelah_ini'       => $this->rupiah($totalBill),
            'jatuh_tempo_berikutnya' => '',

            // Contoh keterangan transfer untuk pembayaran pertama (DP)
            'contoh_keterangan' => e(implode('.', array_filter([
                Str::lower(Str::slug((string) $candidate->name, '')),
                str_replace(' ', '', (string) $levelName),
                'DP',
            ], fn ($part) => $part !== ''))),
        ];
    }

    /**
     * Invoice tagihan kelas pengganti sebelum dibayar. Sesi dan pembayarannya belum ada,
     * jadi isinya dibaca dari tagihan (tabel replacement_bills).
     */
    public function generateForReplacementBill(\App\Models\ReplacementBill $bill): string
    {
        $bill->loadMissing('enrollment.student', 'enrollment.class');

        abort_if(! $bill->enrollment, 404, 'Pendaftaran tidak ditemukan.');

        $template = PrintTemplate::where('key', self::TEMPLATE_KEY_EXTRA)->firstOrFail();

        return $this->toPdf(
            $this->render($template->content, $this->valuesForReplacementBill($bill), false, false),
            true
        );
    }

    private function valuesForReplacementBill(\App\Models\ReplacementBill $bill): array
    {
        $session    = $bill->session ?? [];
        $enrollment = $bill->enrollment;
        $due        = $bill->due_date;

        // Belum dibayar: semua baris jatuh tempo di tanggal yang sama, jadi semuanya merah
        $lines = [[
            'label'     => 'Kelas Pengganti',
            'due'       => $due,
            'amount'    => (float) $bill->replacement_amount,
            'paid'      => false,
            'paid_date' => null,
        ]];

        if ($bill->home_visit_amount > 0) {
            $lines[] = [
                'label'     => 'Home Visit (guru ke rumah)',
                'due'       => $due,
                'amount'    => (float) $bill->home_visit_amount,
                'paid'      => false,
                'paid_date' => null,
            ];
        }

        $total = (float) array_sum(array_column($lines, 'amount'));

        $time = substr((string) ($session['start_time'] ?? ''), 0, 5);

        if (! empty($session['end_time'])) {
            $time .= ($time !== '' ? ' - ' : '') . substr((string) $session['end_time'], 0, 5);
        }

        $location = collect([
            $session['location_type'] ?? null,
            $session['room'] ?? null,
            ($session['location_type'] ?? null) === 'Online' ? ($session['meeting_link'] ?? null) : null,
        ])->filter()->unique()->implode(' - ');

        return [
            'logo'          => $this->logoTag(),
            'nomor_invoice' => e($bill->invoice_number),

            'tanggal_bayar'   => '',
            'tanggal_tagihan' => e($this->dateId($due)),
            'status'          => '<span style="color:#EE0000;font-weight:bold;">Belum Lunas</span>',
            'rincian_tagihan' => $this->billingRows($lines, array_keys($lines), 0.0),
            'total_tagihan'   => $this->rupiah($total),

            'jenis_biaya'   => implode('<br>', array_map(fn ($l) => e($l['label']), $lines)),
            'rincian_biaya' => '',

            'nama_siswa' => e($enrollment->student?->name),
            'kelas'      => e($enrollment->class?->class_name ?? ''),

            'tanggal_pertemuan' => e(
                ! empty($session['session_date']) ? $this->dateId($session['session_date']) : ''
            ),
            'jam_pertemuan' => e($time),
            'guru'          => 'Menunggu guru',
            'lokasi'        => e($location !== '' ? $location : 'Haoyou Educator'),

            'jumlah_dibayar' => '',
            'metode_bayar'   => '',
        ];
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
        $values['watermark'] = $this->watermarkTag();

        if ($key === self::TEMPLATE_KEY_EXTRA) {
            $values['rincian_biaya'] = $this->extraRow('[Jenis biaya tambahan]', '[Jumlah]');

            return $this->toPdf(
                $this->render($content, $values, false, false)
            );
        }

        $values['rincian_tambahan'] = '';
        $values['rincian_awal']     = '';
        $values['rincian_diskon']   = '';

        $sampleDate = Carbon::create(2026, 11, 26);
        $samplePlan = [
            ['label' => 'Biaya Pendaftaran', 'due' => $sampleDate, 'amount' => 300000.0, 'paid' => true, 'paid_date' => $sampleDate],
            ['label' => 'Biaya Aktivitas', 'due' => Carbon::create(2026, 12, 5), 'amount' => 300000.0, 'paid' => false, 'paid_date' => null],
            ['label' => 'Termin 1', 'due' => Carbon::create(2026, 12, 5), 'amount' => 1800000.0, 'paid' => false, 'paid_date' => null],
            ['label' => 'Termin 2', 'due' => Carbon::create(2027, 2, 5), 'amount' => 1800000.0, 'paid' => false, 'paid_date' => null],
        ];

        $values['rincian_tagihan'] = $this->billingRows($samplePlan, [1, 2], 0.0);

        return $this->toPdf(
            $this->render($content, $values, true, true),
            true
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
            // Jarak ke pembayaran berikutnya mengikuti paket: 1 bulan untuk termin bulanan,
            // 3 bulan untuk HSK 6 bulan dengan 2 termin, dst. Skema Lunas tetap 1 bulan.
            $nextDueDate = $this->installments->nextDueDate($enrollment, $paymentDate);
        }

        // Invoice tagihan: rincian pendaftaran, aktivitas, dan Lunas / Termin 1..N dengan jatuh tempo dari admin
        $homeVisit = $this->homeVisitAtConversion($enrollment);
        $plan    = $this->billingPlan(
            $enrollment,
            $enrollment->packagePayments()->where('id', '<=', $payment->id)->orderBy('id')->get(),
            $homeVisit
        );
        $current = $this->currentDueIndexes($plan);

        $tahap = $current !== []
            ? implode(' & ', array_map(fn (int $i) => $plan[$i]['label'], $current))
            : (string) $payment->payment_stage;

        // Tanggal tagihan = jatuh tempo yang diinput admin; kalau kosong, pakai jarak otomatis seperti sebelumnya.
        // Kalau sudah lunas semua, tampilkan tanggal pembayaran terakhir ini.
        $billDate = $current !== []
            ? ($plan[$current[0]]['due'] ?? $nextDueDate)
            : ($unpaid ? $nextDueDate : $payment->payment_date);

        $discount = (float) $enrollment->discount;
        $hvAmount = (float) ($homeVisit['amount'] ?? 0);
        $planSum  = array_sum(array_column($plan, 'amount')) - $hvAmount;
        $totalBill = (float) $payment->total_bill;

        // Baris Diskon hanya ditampilkan kalau rincian di atas belum memotong diskon
        $showDiscount = $discount > 0 && abs(($planSum - $totalBill) - $discount) < 1;

        $values = [
            'logo' => $this->logoTag(),

            'watermark' => $this->watermarkTag(),

            // Satu invoice per pendaftaran: nomor = nomor pembayaran pertama (sama dengan invoice tagihan sebelum bayar)
            'nomor_invoice' => e(
                $enrollment->packagePayments()->orderBy('id')->value('invoice_number') ?: $payment->invoice_number
            ),

            // Format lama, dipertahankan untuk template yang sudah diedit admin
            'tanggal_bayar' => e(
                $this->dateId($payment->payment_date)
            ),

            'tanggal_tagihan' => e($this->dateId($billDate)),
            'tahap' => e($tahap),
            'status' => $unpaid ? 'Belum Lunas' : 'Lunas',
            'rincian_tagihan' => $this->billingRows($plan, $current, $showDiscount ? $discount : 0.0),
            'alamat_home_visit' => filled($enrollment->home_visit_address)
                ? nl2br(e(trim($enrollment->home_visit_address)))
                : '',

            'nama_siswa' => e($student?->name),
            'level' => e($levelName),
            'kelas' => e($enrollment->class?->class_name ?: 'Belum masuk kelas'),
            'daftar_pertemuan' => $this->sessionDatesByMonth($enrollment) ?: '-',

            'jadwal' => implode(
                '<br>',
                array_map(
                    'e',
                    $this->scheduleLines($enrollment)
                )
            ) ?: '-',

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

            // Baris di atas Biaya Les: pendaftaran dan aktivitas
            'rincian_awal' =>
                $this->feeRow(
                    'Biaya pendaftaran',
                    (float) $enrollment->registration_fee
                )
                . $this->feeRow(
                    'Biaya aktivitas',
                    (float) $enrollment->activity_fee
                ),

            // Baris di bawah Biaya Les: diskon
            'rincian_diskon' => $this->feeRow(
                'Diskon',
                (float) $enrollment->discount,
                true
            ),

            // Dipertahankan untuk template lama yang masih memakai satu penanda
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

            // Termasuk Home Visit saat konversi (digabung di invoice yang sama)
            'total_tagihan' => $this->rupiah(
                (float) $payment->total_bill + $hvAmount
            ),

            'catatan' => filled($enrollment->notes)
                ? nl2br(e(trim($enrollment->notes)))
                : '',

            'sudah_dibayar' => $this->rupiah($previous),
            'rincian_dibayar' => e($this->paidComponentsLabel($enrollment, $payment, $previous, $paid)),
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
                    $this->nextStage(
                        $payment->payment_stage,
                        $enrollment->payment_scheme,
                        $this->installments->terminCountFor($enrollment),
                    ),
                ], fn ($part) => $part !== '')))
                : '',
        ];

        return [$values, $hasInstallment, $unpaid];
    }

    /**
     * Rencana tagihan enrollment per baris: Biaya Pendaftaran, Biaya Aktivitas, lalu Lunas / Pelunasan
     * atau Termin 1..N. Status lunas dihitung sampai pembayaran invoice ini (id <= payment ini), jadi
     * invoice lama tetap menunjukkan kondisi pada saat itu. Jatuh tempo = tanggal yang diinput admin
     * saat konversi (class_enrollments.form_manual); kosong kalau admin belum mengisinya.
     *
     * @return array<int, array{label: string, due: ?Carbon, amount: float, paid: bool, paid_date: mixed}>
     */
    private function billingPlan(
        \App\Models\ClassEnrollment $enrollment,
        \Illuminate\Support\Collection $ordered,
        ?array $homeVisit = null,
    ): array {
        // $ordered = pembayaran paket sampai invoice ini; kosong = invoice tagihan sebelum ada pembayaran
        // (enrollment belum tersimpan), jadwal dihitung dengan asumsi DP minimal.
        $first      = $ordered->first();
        $firstStage = $first?->payment_stage;

        $manual = is_array($enrollment->form_manual) ? $enrollment->form_manual : [];

        $manualDate = function (string ...$keys) use ($manual): ?Carbon {
            foreach ($keys as $key) {
                $raw = $manual[$key] ?? null;

                if ($raw === null || trim((string) $raw) === '') {
                    continue;
                }

                try {
                    return Carbon::parse($raw);
                } catch (\Throwable) {
                    // tanggal tidak valid: abaikan
                }
            }

            return null;
        };

        // Sebaran uang per pembayaran: pendaftaran, aktivitas, lalu biaya les (urutan sama dengan formulir)
        $due        = $this->installments->componentsDue($enrollment);
        $previous   = 0.0;
        $regPaid    = 0.0;
        $actPaid    = 0.0;
        $regDate    = null;
        $actDate    = null;
        $hvPaid     = 0.0;
        $hvDate     = null;
        $lesByStage = [];

        foreach ($ordered as $paid) {
            $amount = (float) $paid->amount_paid;
            $parts  = $this->installments->paymentComponents($enrollment, $previous, $amount);

            $regPaid += $parts['pendaftaran'];
            $actPaid += $parts['aktivitas'];
            $hvPaid  += $parts['home_visit'];

            if ($parts['pendaftaran'] > 0) {
                $regDate = $paid->payment_date;
            }

            if ($parts['aktivitas'] > 0) {
                $actDate = $paid->payment_date;
            }

            if ($parts['home_visit'] > 0) {
                $hvDate = $paid->payment_date;
            }

            $lesByStage[$paid->payment_stage] = ['amount' => $parts['les'], 'date' => $paid->payment_date];
            $previous += $amount;
        }

        $lines = [];

        if ($due['pendaftaran'] > 0) {
            $lines[] = [
                'label'     => 'Biaya Pendaftaran',
                'due'       => $manualDate('dp_pendaftaran_tanggal'),
                'amount'    => (float) $due['pendaftaran'],
                'paid'      => $regPaid + 0.005 >= $due['pendaftaran'],
                'paid_date' => $regDate,
                'paid_amount' => min($regPaid, (float) $due['pendaftaran']),
            ];
        }

        if ($due['aktivitas'] > 0) {
            $lines[] = [
                'label'     => 'Biaya Aktivitas',
                'due'       => $manualDate('dp_aktivitas_tanggal'),
                'amount'    => (float) $due['aktivitas'],
                'paid'      => $actPaid + 0.005 >= $due['aktivitas'],
                'paid_date' => $actDate,
                'paid_amount' => min($actPaid, (float) $due['aktivitas']),
            ];
        }

        // Home Visit bagian dari tagihan paket (kelas private baru): uang masuk menutup pendaftaran, aktivitas,
        // lalu Home Visit (Rp50.000 per pertemuan) sebelum biaya les. Pertemuan yang belum tertutup tetap Belum Lunas.
        if (($due['home_visit'] ?? 0) > 0) {
            $meetings = $this->installments->homeVisitMeetingsFor($enrollment);
            $perFee   = ExtraFeeService::FEE_HOME_VISIT;
            $hvLunas  = $hvPaid + 0.005 >= $due['home_visit'];
            $closed   = $hvLunas ? $meetings : min($meetings, (int) floor(($hvPaid + 0.005) / $perFee));

            $lines[] = [
                'label'       => 'Biaya Home Visit (' . $meetings . ' pertemuan)',
                'due'         => $manualDate('dp_home_visit_tanggal', 'dp_pendaftaran_tanggal'),
                'amount'      => (float) $due['home_visit'],
                'paid'        => $hvLunas,
                'paid_date'   => $hvDate,
                'paid_amount' => min($hvPaid, (float) $due['home_visit']),
                'danger'      => true,
                'detail'      => $closed . ' dari ' . $meetings . ' pertemuan lunas',
            ];
        }

        // Home Visit lama (dicatat per pertemuan bersama DP): dibayar bersamaan dengan DP, jatuh tempo mengikuti tanggal DP
        if ($homeVisit && $homeVisit['amount'] > 0) {
            $lines[] = [
                'label'     => 'Biaya Home Visit' . ($homeVisit['sessions'] ? ' (' . $homeVisit['sessions'] . ' pertemuan)' : ''),
                'due'       => $manualDate('dp_home_visit_tanggal', 'dp_pendaftaran_tanggal'),
                'amount'    => (float) $homeVisit['amount'],
                'paid'      => (bool) $homeVisit['paid'],
                'paid_date' => $homeVisit['paid_date'],
            ];
        }

        $dp = $first
            ? (float) $first->amount_paid
            : $this->installments->minimumDownPayment(
                (float) $enrollment->registration_fee,
                (float) $enrollment->activity_fee,
                (float) ($this->installments->totalBillFor($enrollment) ?? 0),
            );

        if ($firstStage === 'Lunas') {
            // Dibayar sekaligus: biaya les masuk baris Lunas
            $les = $lesByStage['Lunas'] ?? null;

            if ($les && $les['amount'] > 0) {
                $lines[] = [
                    'label'     => 'Lunas',
                    'due'       => $manualDate('termin1_lunas_tanggal', 'termin1_tanggal'),
                    'amount'    => (float) $les['amount'],
                    'paid'      => true,
                    'paid_date' => $les['date'],
                ];
            }
        } elseif ($enrollment->payment_scheme === 'Termin') {
            // Termin 1..N sebanyak jumlah termin program; yang sudah dibayar memakai nominal sebenarnya
            $shares = $this->installments->terminLesShares($enrollment, $dp)['shares'];

            foreach (range(1, count($shares)) as $n) {
                $paidLes = $lesByStage['Termin ' . $n] ?? null;
                $isPaid  = $paidLes !== null && $paidLes['amount'] > 0;

                $lines[] = [
                    'label'     => 'Termin ' . $n,
                    'due'       => $n === 1
                        ? $manualDate('termin1_lunas_tanggal', 'termin1_tanggal')
                        : $manualDate('termin' . $n . '_tanggal'),
                    'amount'    => (float) ($isPaid ? $paidLes['amount'] : ($shares[$n - 1] ?? 0)),
                    'paid'      => $isPaid,
                    'paid_date' => $isPaid ? $paidLes['date'] : null,
                ];
            }
        } else {
            // Skema Lunas dengan DP: pelunasan membayar sisa biaya les
            $paidLes = $lesByStage['Pelunasan'] ?? null;
            $isPaid  = $paidLes !== null && $paidLes['amount'] > 0;
            $amount  = $isPaid
                ? $paidLes['amount']
                : $this->installments->terminLesShares($enrollment, $dp)['les_remaining'];

            if ($amount > 0) {
                $lines[] = [
                    'label'     => 'Lunas',
                    'due'       => $manualDate('termin1_lunas_tanggal', 'termin1_tanggal'),
                    'amount'    => (float) $amount,
                    'paid'      => $isPaid,
                    'paid_date' => $isPaid ? $paidLes['date'] : null,
                ];
            }
        }

        return $lines;
    }

    /**
     * Biaya Home Visit yang dibayar saat konversi (dicatat bersamaan dengan DP, per pertemuan), digabung
     * ke invoice tagihan. Home Visit yang ditagih belakangan per sesi tetap punya invoice sendiri
     * (invoice biaya tambahan): hanya pembayaran yang dibuat dalam transaksi konversi yang sama yang dihitung.
     *
     * @return array{amount: float, paid: bool, paid_date: mixed, sessions: int}|null
     */
    private function homeVisitAtConversion(\App\Models\ClassEnrollment $enrollment): ?array
    {
        $first = $enrollment->packagePayments()->orderBy('id')->first();

        if (! $first || ! $first->created_at) {
            return null;
        }

        $charges = Payment::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('payment_type', Payment::TYPE_HOME_VISIT)
            ->where(function ($q) use ($first) {
                // Satu nomor invoice dengan DP; data lama (nomor terpisah) dikenali lewat waktu pembuatan
                $q->where('invoice_number', $first->invoice_number)
                    ->orWhereBetween('created_at', [
                        $first->created_at->copy()->subMinutes(2),
                        $first->created_at->copy()->addMinutes(2),
                    ]);
            })
            ->get();

        if ($charges->isEmpty()) {
            return null;
        }

        return [
            'amount'    => (float) $charges->sum('amount_paid'),
            'paid'      => true,
            'paid_date' => $charges->first()->payment_date,
            'sessions'  => $charges->count(),
        ];
    }

    /**
     * Baris yang harus dibayar sekarang: baris belum lunas yang pertama, ditambah baris belum lunas
     * berikutnya yang jatuh tempo di tanggal yang sama (mis. Aktivitas bersama Termin 1).
     *
     * @return array<int, int> indeks baris di $plan
     */
    private function currentDueIndexes(array $plan): array
    {
        $indexes = [];
        $dueDate = null;

        foreach ($plan as $i => $line) {
            if ($line['paid']) {
                continue;
            }

            $date = $line['due']?->toDateString();

            if ($indexes === []) {
                $indexes[] = $i;
                $dueDate   = $date;

                continue;
            }

            if ($dueDate !== null && $date === $dueDate) {
                $indexes[] = $i;
            }
        }

        return $indexes;
    }

    /**
     * Baris tabel rincian bayar invoice tagihan (header dan baris Total tagihan ada di template).
     * Baris yang harus dibayar sekarang berwarna merah. Keterangan: "Lunas" (hijau) atau "Belum Lunas", tanggal lunas di kolom sendiri.
     */
    private function billingRows(array $plan, array $current, float $discount): string
    {
        $td = 'border:1px solid #000;padding:3px 6px;vertical-align:top;';

        $html = '';

        foreach ($plan as $i => $line) {
            $row = $td . (in_array($i, $current, true)
                ? 'color:#EE0000;font-weight:bold;background:#FDECEC;'
                : '');

            // Keterangan: Lunas (hijau) atau Belum Lunas; tanggal lunas di kolom terpisah
            // Home Visit yang belum lunas selalu merah
            $note = $line['paid']
                ? '<span style="color:#1E8E3E;font-weight:bold;">Lunas</span>'
                : (! empty($line['danger'])
                    ? '<span font-weight:bold;">Belum Lunas</span>'
                    : 'Belum Lunas');

            // Nominal tagihan ada di kolom Nominal; dibayar dan sisa ditulis di bawah status
            $paidAmount = (float) ($line['paid_amount'] ?? ($line['paid'] ? $line['amount'] : 0.0));
            $sisa       = max((float) $line['amount'] - $paidAmount, 0.0);

            $paidDate = ($line['paid'] && $line['paid_date']) ? e($this->dateId($line['paid_date'])) : '-';

            $html .= '<tr>'
                . '<td style="' . $row . '">' . e($line['label']) . '</td>'
                . '<td style="' . $row . 'text-align:center;">' . ($line['due'] ? e($this->dateId($line['due'])) : '-') . '</td>'
                . '<td style="' . $row . 'text-align:right;white-space:nowrap;">' . $this->rupiah($line['amount']) . '</td>'
                . '<td style="' . $row . 'text-align:center;">' . $note . '</td>'
                . '<td style="' . $row . 'text-align:center;">' . $paidDate . '</td>'
                . '</tr>';
        }

        if ($discount > 0) {
            $html .= '<tr>'
                . '<td colspan="2" style="' . $td . '">Diskon</td>'
                . '<td style="' . $td . 'text-align:right;white-space:nowrap;">- ' . $this->rupiah($discount) . '</td>'
                . '<td colspan="2" style="' . $td . '">&nbsp;</td>'
                . '</tr>';
        }

        return $html;
    }

    /* ==========================================================
     * INVOICE BIAYA TAMBAHAN
     * ========================================================== */

    private function extraValuesFor(Payment $payment, ?\Illuminate\Support\Collection $group = null): array
    {
        $group = ($group === null || $group->isEmpty()) ? collect([$payment]) : $group;

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

        $label = fn (Payment $p) => match ($p->payment_type) {
            Payment::TYPE_REPLACEMENT =>
                'Pertemuan tambahan (kelas pengganti)',

            Payment::TYPE_HOME_VISIT =>
                'Biaya guru mengajar ke rumah murid',

            default => (string) $p->payment_type,
        };

        $rows = $group->map(fn (Payment $p) => $this->extraRow(
            $label($p),
            $this->rupiah((float) $p->amount_paid)
        ))->implode('');

        $jenisBiaya = $group->map(fn (Payment $p) => e($label($p)))->implode('<br>');
        $total      = (float) $group->sum('amount_paid');

        // Jatuh tempo diambil dari tagihan dengan nomor invoice yang sama; invoice lama tanpa tagihan memakai tanggal bayar
        $billDue = \App\Models\ReplacementBill::where('invoice_number', $payment->invoice_number)->value('due_date')
            ?: $payment->payment_date;

        $lines = $group->map(fn (Payment $p) => [
            'label'     => $p->payment_type === Payment::TYPE_HOME_VISIT ? 'Home Visit (guru ke rumah)' : 'Kelas Pengganti',
            'due'       => $billDue ? Carbon::parse($billDue) : null,
            'amount'    => (float) $p->amount_paid,
            'paid'      => true,
            'paid_date' => $p->payment_date,
        ])->values()->all();

        $location = collect([
            $session->location_type,
            $session->room,
            $session->location_type === 'Online' ? $session->meeting_link : null,
        ])->filter()->unique()->implode(' - ');

        return [
            'logo' => $this->logoTag(),

            'nomor_invoice' => e($payment->invoice_number),

            'tanggal_bayar' => e(
                $this->dateId($payment->payment_date)
            ),

            'jenis_biaya' => $jenisBiaya,
            'rincian_biaya' => $rows,

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

            'jumlah_dibayar' => $this->rupiah($total),

            'metode_bayar' => e(
                $payment->payment_method
            ),

            // Invoice tagihan: jatuh tempo dari tagihan yang terbit sebelum bayar (kalau ada)
            'tanggal_tagihan' => e($this->dateId($billDue)),
            'status'          => '<span style="color:#1E8E3E;font-weight:bold;">Lunas</span>',
            'rincian_tagihan' => $this->billingRows($lines, [], 0.0),
            'total_tagihan'   => $this->rupiah($total),
        ];
    }

    /**
     * Daftar tanggal pertemuan kelas per bulan, mis. "November 2026: 3, 10, 17, 24".
     * Kosong kalau siswa belum masuk kelas.
     */
    private function sessionDatesByMonth(\App\Models\ClassEnrollment $enrollment): string
    {
        if (! $enrollment->class_id) {
            return '';
        }

        $dates = ClassSession::where('class_id', $enrollment->class_id)
            ->where('kind', 'Regular')
            ->orderBy('session_date')
            ->pluck('session_date');

        if ($dates->isEmpty()) {
            return '';
        }

        $lines = $dates
            ->map(fn ($d) => Carbon::parse($d))
            ->groupBy(fn (Carbon $d) => $d->format('Y-m'))
            ->map(fn ($group) => $group->first()->copy()->locale('id')->translatedFormat('F Y')
                . ': ' . $group->map(fn (Carbon $d) => $d->format('j'))->implode(', '));

        return implode('<br>', $lines->map(fn ($l) => e($l))->all());
    }

    /**
     * Label komponen yang dibayar pada invoice ini, mis. "Biaya Aktivitas & Termin 1",
     * "Biaya Pendaftaran", atau "Pelunasan". Dihitung dari urutan penyebaran uang
     * (pendaftaran, aktivitas, biaya les).
     */
    private function paidComponentsLabel(\App\Models\ClassEnrollment $enrollment, Payment $payment, float $previous, float $paid): string
    {
        $parts = $this->installments->paymentComponents($enrollment, $previous, $paid);

        $labels = [];

        if ($parts['pendaftaran'] > 0) {
            $labels[] = 'Biaya Pendaftaran';
        }

        if ($parts['aktivitas'] > 0) {
            $labels[] = 'Biaya Aktivitas';
        }

        if (($parts['home_visit'] ?? 0) > 0) {
            $labels[] = 'Biaya Home Visit';
        }

        if ($parts['les'] > 0) {
            $labels[] = match ($payment->payment_stage) {
                'DP'     => 'Biaya Les (DP)',
                'Lunas'  => 'Biaya Les (Lunas)',
                default  => $payment->payment_stage,   // Termin 1..N / Pelunasan
            };
        }

        return $labels !== [] ? implode(' & ', $labels) : (string) $payment->payment_stage;
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
            [
                '<!--RINCIAN_AWAL-->',
                '<!--RINCIAN_DISKON-->',
                '<!--RINCIAN_TAMBAHAN-->',
            ],
            [
                $values['rincian_awal'] ?? '',
                $values['rincian_diskon'] ?? '',
                $values['rincian_tambahan'] ?? '',
            ],
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

        $html = $this->block(
            $html,
            'JIKA_HOME_VISIT',
            ($values['alamat_home_visit'] ?? '') !== ''
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

    private function toPdf(string $body, bool $landscape = false): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                @page {
                    margin: ' . ($landscape ? '1.2cm 2cm' : '1.6cm') . ';
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
            ->setPaper('a4', $landscape ? 'landscape' : 'portrait')
            ->output();
    }

    /* ==========================================================
     * HELPER
     * ========================================================== */

    /**
     * Contoh keterangan transfer untuk tahap berikutnya.
     * Skema Termin: DP lalu Termin 1, Termin 2, ... sampai jumlah termin enrollment, lalu Pelunasan.
     * Skema Lunas: DP lalu Pelunasan.
     */
    private function nextStage(?string $stage, ?string $scheme = null, int $terminCount = 3): string
    {
        if ($stage === 'DP') {
            return $scheme === 'Termin' ? 'Termin1' : 'Pelunasan';
        }

        if (preg_match('/^Termin (\d+)$/', (string) $stage, $m) === 1) {
            $n = (int) $m[1];

            return $n < $terminCount ? 'Termin' . ($n + 1) : 'Pelunasan';
        }

        return 'Termin';
    }

    /** Satu baris tabel Rincian Biaya invoice biaya tambahan. */
    private function extraRow(string $label, string $amount): string
    {
        $td = 'border:1px solid #000;padding:3px 6px;vertical-align:top;';

        return '<tr><td style="' . $td . '">' . e($label) . '</td>'
            . '<td style="' . $td . 'text-align:right;white-space:nowrap;">'
            . e($amount) . '</td></tr>';
    }

    private function feeRow(
        string $label,
        float $amount,
        bool $negative = false
    ): string {
        if ($amount <= 0) {
            return '';
        }

        $td = 'border:1px solid #000;'
            . 'padding:3px 6px;'
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

    /** Watermark logo di latar invoice paket. Kosong kalau file gambarnya belum ada. */
    private function watermarkTag(): string
    {
        $path = public_path('assets/img/invoice-watermark.png');

        if (! is_file($path)) {
            return '';
        }

        // Posisi tengah halaman A4 landscape (lebar isi kira-kira 728pt)
        return '<img src="data:image/png;base64,'
            . base64_encode(file_get_contents($path))
            . '" style="position:absolute;left:202pt;top:100pt;width:324pt;height:324pt;">';
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