<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentInstallmentService
{
    /** Batas jumlah termin yang bisa dipilih admin untuk skema Termin. */
    public const TERMIN_MIN = 2;
    public const TERMIN_MAX = 10;

    /** Semua tahap termin yang mungkin (Termin 1..TERMIN_MAX). */
    public const TERMIN_STAGES = [
        'Termin 1', 'Termin 2', 'Termin 3', 'Termin 4', 'Termin 5',
        'Termin 6', 'Termin 7', 'Termin 8', 'Termin 9', 'Termin 10',
    ];

    /**
     * Jumlah termin yang dipilih admin untuk enrollment ini (TERMIN_MIN..TERMIN_MAX, default 3).
     * Disimpan di kolom class_enrollments.termin_count.
     */
    public function terminCountFor(ClassEnrollment $enrollment): int
    {
        $default = (int) ($enrollment->programPackage?->installmentTermCount() ?? 3);
        $count   = (int) ($enrollment->termin_count ?: $default);

        if ($count >= self::TERMIN_MIN && $count <= self::TERMIN_MAX) {
            return $count;
        }

        return ($default >= self::TERMIN_MIN && $default <= self::TERMIN_MAX) ? $default : 3;
    }

    /**
     * Jarak antar termin (bulan) untuk enrollment ini = lama kelas / jumlah termin.
     * Skema Lunas, paket private, atau paket tanpa data durasi memakai 1 bulan.
     */
    public function installmentIntervalMonthsFor(ClassEnrollment $enrollment): int
    {
        $enrollment->loadMissing('programPackage');

        $package = $enrollment->programPackage;

        if ($enrollment->payment_scheme !== 'Termin' || ! $package) {
            return 1;
        }

        return $package->installmentIntervalMonths($this->terminCountFor($enrollment));
    }

    /**
     * Jatuh tempo pembayaran berikutnya = tanggal 5, sejauh jarak termin paket dari tanggal bayar.
     * Dipakai invoice dan notifikasi penagihan supaya keduanya selalu sama.
     */
    public function nextDueDate(ClassEnrollment $enrollment, $paymentDate): Carbon
    {
        return Carbon::parse($paymentDate)
            ->addMonthsNoOverflow($this->installmentIntervalMonthsFor($enrollment))
            ->day(5)
            ->startOfDay();
    }

    /**
     * Catat SATU transaksi pembayaran paket.
     * Selalu membuat baris Payment baru, tidak mengubah transaksi lama.
     * remaining_bill dihitung dari total tagihan dikurangi seluruh
     * pembayaran paket sebelumnya dan pembayaran saat ini.
     *
     * ATURAN PEMBAYARAN (semua dipaksakan di sini, bukan di controller):
     *  - Pembayaran pertama = DP: nominal manual, minimal = biaya pendaftaran, maksimal = total tagihan.
     *    Kalau nominalnya menutup seluruh tagihan, tahapnya "Lunas".
     *  - Skema Lunas: pembayaran berikutnya = "Pelunasan", harus sebesar seluruh sisa tagihan.
     *  - Skema Termin: pembayaran berikutnya mengikuti jadwal termin (lihat nextPayment()).
     *
     * $invoiceNumber (opsional): nomor invoice tagihan yang sudah terbit sebelum pembayaran pertama.
     *
     * Parameter $stage tetap ada demi kompatibilitas pemanggil lama, tetapi TAHAP SELALU
     * ditentukan service (DP/Lunas untuk pembayaran pertama, selanjutnya dari nextPayment()).
     *
     * Berjalan dalam transaksi dengan lock pada baris enrollment, supaya dua
     * pembayaran yang masuk bersamaan tidak sama-sama lolos cek sisa tagihan.
     */
    public function recordPayment(
        ClassEnrollment $enrollment,
        string $stage,
        float $amount,
        string $paymentMethod,
        ?string $paymentDate = null,
        ?string $proofPath = null,
        ?string $invoiceNumber = null,
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Nominal bayar harus lebih dari 0.',
            ]);
        }

        return DB::transaction(function () use (
            $enrollment,
            $stage,
            $amount,
            $paymentMethod,
            $paymentDate,
            $proofPath,
            $invoiceNumber,
        ) {
            // Kunci enrollment dulu sebelum membaca total pembayaran.
            ClassEnrollment::query()
                ->whereKey($enrollment->getKey())
                ->lockForUpdate()
                ->first(['id']);

            $totalBill = $this->totalBillFor($enrollment);

            if (! $totalBill) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'Enrollment ini tidak punya paket (program/private) yang valid, tidak bisa hitung tagihan.',
                ]);
            }

            $date = $paymentDate ? Carbon::parse($paymentDate) : now();
            $enrollmentDate = Carbon::parse($enrollment->enrollment_date);

            if ($date->isFuture()) {
                throw ValidationException::withMessages([
                    'payment_date' => 'Tanggal bayar tidak boleh di masa depan.',
                ]);
            }

            if ($date->lt($enrollmentDate)) {
                throw ValidationException::withMessages([
                    'payment_date' => 'Tanggal bayar tidak boleh sebelum tanggal enrollment (' . $enrollmentDate->toDateString() . ').',
                ]);
            }

            // Total pembayaran paket sebelum transaksi baru.
            $alreadyPaid = $this->totalPaid($enrollment);
            $remaining = round($totalBill - $alreadyPaid - $amount, 2);

            if ($remaining < 0) {
                $sisaSaatIni = number_format($totalBill - $alreadyPaid, 0, ',', '.');

                throw ValidationException::withMessages([
                    'amount_paid' => "Jumlah bayar melebihi sisa tagihan (Rp{$sisaSaatIni}).",
                ]);
            }

            // Aturan nominal & tahap (dibaca setelah enrollment terkunci)
            $next = $this->nextPayment($enrollment);

            if ($next === null) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Tagihan enrollment ini sudah lunas.',
                ]);
            }

            if ($next['exact']) {
                if (abs($amount - $next['amount']) > 0.01) {
                    throw ValidationException::withMessages([
                        'amount_paid' => "Pembayaran {$next['stage']} harus sebesar Rp"
                            . number_format($next['amount'], 0, ',', '.') . '.',
                    ]);
                }

                $stage = $next['stage'];
            } else {
                // Pembayaran pertama (DP)
                if ($amount + 0.005 < $next['min']) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Minimal DP Rp' . number_format($next['min'], 0, ',', '.')
                            . ' (biaya pendaftaran).',
                    ]);
                }

                $stage = $remaining <= 0 ? 'Lunas' : 'DP';
            }

            return Payment::create([
                'enrollment_id' => $enrollment->id,
                // Pembayaran pertama memakai nomor invoice tagihan yang sudah diterbitkan sebelum bayar (kalau ada)
                'invoice_number' => $invoiceNumber ?: $this->generateInvoiceNumber(),
                'payment_type' => Payment::TYPE_PACKAGE,
                'payment_stage' => $stage,
                'total_bill' => $totalBill,
                'amount_paid' => $amount,
                'remaining_bill' => $remaining,
                'payment_method' => $paymentMethod,
                'payment_date' => $date->toDateString(),
                'payment_proof_path' => $proofPath,
                'status' => $remaining <= 0 ? 'Paid' : 'Partial',
            ]);
        });
    }

    /**
     * Minimal DP = biaya pendaftaran (wajib). Kalau tidak ada biaya pendaftaran
     * (siswa lama yang kembali dalam 30 hari), minimalnya biaya aktivitas.
     * Tidak pernah melebihi total tagihan.
     */
    public function minimumDownPayment(float $registrationFee, float $activityFee, float $totalBill): float
    {
        $min = $registrationFee > 0 ? $registrationFee : $activityFee;

        return round(min(max($min, 1), max($totalBill, 1)), 2);
    }

    /**
     * Pembayaran berikutnya yang harus dicatat untuk satu enrollment, atau null kalau sudah lunas.
     *
     * Bentuk hasil:
     *  - stage     : tahap yang akan dicatat (DP, Pelunasan, Termin 1..3)
     *  - exact     : true = nominal harus persis $amount; false = nominal bebas antara min dan max (DP)
     *  - amount    : nominal yang diminta (null untuk DP)
     *  - min / max : batas nominal
     *  - remaining : sisa tagihan sekarang
     */
    public function nextPayment(ClassEnrollment $enrollment): ?array
    {
        $enrollment->loadMissing(['programPackage', 'privatePackage']);

        $total = $this->totalBillFor($enrollment);

        if ($total === null) {
            return null;
        }

        $remaining = round($total - $this->totalPaid($enrollment), 2);

        if ($remaining <= 0) {
            return null;
        }

        $first = $enrollment->packagePayments()->orderBy('id')->first();

        // Belum ada pembayaran: DP, nominal manual
        if (! $first) {
            $min = $this->minimumDownPayment(
                (float) $enrollment->registration_fee,
                (float) $enrollment->activity_fee,
                $total,
            );

            return [
                'stage'     => 'DP',
                'exact'     => false,
                'amount'    => null,
                'min'       => $min,
                'max'       => (float) $total,
                'remaining' => $remaining,
            ];
        }

        // Skema Lunas: sisanya dilunasi sekaligus
        if ($enrollment->payment_scheme !== 'Termin') {
            return [
                'stage'     => 'Pelunasan',
                'exact'     => true,
                'amount'    => $remaining,
                'min'       => $remaining,
                'max'       => $remaining,
                'remaining' => $remaining,
            ];
        }

        // Skema Termin: jadwal dihitung dari nominal DP
        $plan = $this->terminPlan($enrollment, (float) $first->amount_paid);
        $used = $enrollment->packagePayments()->pluck('payment_stage')->all();

        $pending = array_values(array_filter(
            $plan,
            fn (array $row) => ! in_array($row['stage'], $used, true)
        ));

        if ($pending === []) {
            // Semua termin sudah tercatat tetapi masih ada sisa (data lama): lunasi sisanya
            return [
                'stage'     => 'Pelunasan',
                'exact'     => true,
                'amount'    => $remaining,
                'min'       => $remaining,
                'max'       => $remaining,
                'remaining' => $remaining,
            ];
        }

        $row = $pending[0];

        // Termin terakhir menyerap selisih pembulatan
        $amount = count($pending) === 1 ? $remaining : min($row['amount'], $remaining);

        return [
            'stage'     => $row['stage'],
            'exact'     => true,
            'amount'    => round($amount, 2),
            'min'       => round($amount, 2),
            'max'       => round($amount, 2),
            'remaining' => $remaining,
        ];
    }

    /**
     * Jadwal termin setelah DP.
     * Uang masuk disebar berurutan: pendaftaran, aktivitas, lalu biaya les.
     * Sisa biaya les dibagi sesuai jumlah termin pilihan admin (2 sampai 10; selisih pembulatan
     * di termin terakhir); biaya aktivitas yang belum tertutup DP ditambahkan ke Termin 1.
     *
     * @return array<int, array{stage:string, amount:float}>  hanya termin yang nominalnya > 0
     */
    public function terminPlan(ClassEnrollment $enrollment, float $downPayment): array
    {
        $split   = $this->terminLesShares($enrollment, $downPayment);
        $amounts = $split['shares'];
        $amounts[0] = round($amounts[0] + $split['activity_unpaid'], 2);

        $plan = [];

        foreach ($amounts as $i => $amount) {
            if ($amount > 0) {
                $plan[] = ['stage' => 'Termin ' . ($i + 1), 'amount' => (float) $amount];
            }
        }

        return $plan;
    }

    /**
     * Pembagian biaya les ke termin setelah DP, TANPA biaya aktivitas (dipakai formulir yang
     * menampilkan pendaftaran, aktivitas, dan termin sebagai kolom terpisah).
     * Sisa biaya les dibagi sesuai jumlah termin (2 sampai 10), selisih pembulatan di termin terakhir.
     * Hasil `shares` berisi satu nilai per termin (sebanyak jumlah termin).
     *
     * @return array{shares: array<int,float>, activity_unpaid: float, les_remaining: float}
     */
    public function terminLesShares(ClassEnrollment $enrollment, float $downPayment): array
    {
        $alloc = $this->allocate(
            $downPayment,
            (float) $enrollment->registration_fee,
            (float) $enrollment->activity_fee,
            (float) ($this->totalBillFor($enrollment) ?? 0),
            $this->homeVisitFeeFor($enrollment),
        );

        $les   = $alloc['les_remaining'];
        $count = $this->terminCountFor($enrollment);
        $base  = floor($les / $count);

        $shares = array_fill(0, $count, (float) $base);

        // Termin terakhir menyerap selisih pembulatan
        $shares[$count - 1] = round($les - ($count - 1) * $base, 2);

        return [
            'shares'          => $shares,
            'activity_unpaid' => $alloc['activity_unpaid'],
            'les_remaining'   => $les,
        ];
    }

    /**
     * Nominal tiap komponen tagihan: pendaftaran, aktivitas, Home Visit, dan biaya les (setelah diskon).
     * Jumlah keempatnya sama dengan total tagihan.
     *
     * @return array{pendaftaran: float, aktivitas: float, home_visit: float, les: float}
     */
    public function componentsDue(ClassEnrollment $enrollment): array
    {
        $d = $this->splitDue(
            (float) ($this->totalBillFor($enrollment) ?? 0),
            (float) $enrollment->registration_fee,
            (float) $enrollment->activity_fee,
            $this->homeVisitFeeFor($enrollment),
        );

        return [
            'pendaftaran' => round($d['reg'], 2),
            'aktivitas'   => round($d['act'], 2),
            'home_visit'  => round($d['hv'], 2),
            'les'         => round($d['les'], 2),
        ];
    }


    /**
     * Pecah satu pembayaran ke komponen tagihan (untuk formulir dan invoice).
     * Uang disebar berurutan: pendaftaran, aktivitas, Home Visit, lalu biaya les, dimulai dari titik
     * "sudah dibayar sebelumnya" ($previousPaid), jadi pembayaran kedua dst. ikut menutup sisa komponen.
     *
     * @return array{pendaftaran:float, aktivitas:float, home_visit:float, les:float}
     */
    public function paymentComponents(ClassEnrollment $enrollment, float $previousPaid, float $amount): array
    {
        $d = $this->splitDue(
            (float) ($this->totalBillFor($enrollment) ?? 0),
            (float) $enrollment->registration_fee,
            (float) $enrollment->activity_fee,
            $this->homeVisitFeeFor($enrollment),
        );

        // Irisan rentang uang [from, to] dengan rentang komponen [start, end]
        $overlap = fn (float $from, float $to, float $start, float $end): float
            => max(min($to, $end) - max($from, $start), 0);

        $from = $previousPaid;
        $to   = $previousPaid + $amount;

        $toReg = $overlap($from, $to, 0, $d['reg']);
        $toAct = $overlap($from, $to, $d['reg'], $d['reg'] + $d['act']);
        $toHv  = $overlap($from, $to, $d['reg'] + $d['act'], $d['reg'] + $d['act'] + $d['hv']);

        return [
            'pendaftaran' => round($toReg, 2),
            'aktivitas'   => round($toAct, 2),
            'home_visit'  => round($toHv, 2),
            'les'         => round(max($amount - $toReg - $toAct - $toHv, 0), 2),
        ];
    }


    /**
     * Sebar sejumlah uang ke komponen tagihan: pendaftaran, aktivitas, Home Visit, lalu biaya les (setelah diskon).
     * Diskon yang lebih besar dari biaya les mengurangi biaya tetap, jadi jumlah semua komponen
     * selalu sama dengan total tagihan.
     *
     * @return array{les_net:float, activity_unpaid:float, home_visit_unpaid:float, les_remaining:float}
     */
    private function allocate(float $amount, float $registrationFee, float $activityFee, float $totalBill, float $homeVisitFee = 0.0): array
    {
        $d = $this->splitDue($totalBill, $registrationFee, $activityFee, $homeVisitFee);

        $toReg = min($amount, $d['reg']);
        $toAct = min($amount - $toReg, $d['act']);
        $toHv  = min($amount - $toReg - $toAct, $d['hv']);
        $toLes = $amount - $toReg - $toAct - $toHv;

        return [
            'les_net'           => round($d['les'], 2),
            'activity_unpaid'   => round($d['act'] - $toAct, 2),
            'home_visit_unpaid' => round($d['hv'] - $toHv, 2),
            'les_remaining'     => round(max($d['les'] - $toLes, 0), 2),
        ];
    }


    /**
     * Total tagihan enrollment = harga paket + biaya pendaftaran
     * + biaya aktivitas + biaya Home Visit (pertemuan x tarif) - diskon.
     */
    public function totalBillFor(ClassEnrollment $enrollment): ?float
    {
        $packageTotal = $this->packageTotal($enrollment);

        if ($packageTotal === null) {
            return null;
        }

        $total = $packageTotal
            + (float) $enrollment->registration_fee
            + (float) $enrollment->activity_fee
            + $this->homeVisitFeeFor($enrollment)
            - (float) $enrollment->discount;

        return max($total, 0);
    }

    /* ==========================================================
     * HOME VISIT (private) DALAM TAGIHAN PAKET
     * ==========================================================
     * Home Visit dari konversi ditagihkan sebagai bagian dari total tagihan paket dan dibayar lewat
     * nominal pembayaran yang sama (tanpa transaksi terpisah, tanpa kolom baru). Urutan alokasi uang:
     * pendaftaran -> aktivitas -> Home Visit (Rp50.000 menutup satu pertemuan) -> biaya les.
     * Status lunas per pertemuan TIDAK disimpan, selalu dihitung dari total uang yang benar-benar masuk
     * (SUM payments paket), jadi tidak mungkin ada alokasi ganda.
     */

    /**
     * Pembagian total tagihan ke empat komponen (setelah diskon). Diskon mengurangi biaya les dulu,
     * lalu Home Visit, aktivitas, dan pendaftaran, sehingga jumlah komponen selalu sama dengan total.
     *
     * @return array{les:float, reg:float, act:float, hv:float}
     */
    private function splitDue(float $total, float $reg, float $act, float $hv): array
    {
        $lesNet = max($total - $reg - $act - $hv, 0);
        $fixed  = $total - $lesNet;
        $regDue = min($reg, $fixed);
        $actDue = min($act, $fixed - $regDue);
        $hvDue  = max($fixed - $regDue - $actDue, 0);

        return ['les' => $lesNet, 'reg' => $regDue, 'act' => $actDue, 'hv' => $hvDue];
    }

    /**
     * Jumlah pertemuan Home Visit yang ditagihkan di paket private enrollment ini (0 kalau tidak ada).
     *
     * Penanda: paket private + class_enrollments.home_visit_address terisi (hanya diisi ConvertController
     * saat kelas private baru dibuat sebagai Home Visit). Data lama yang Home Visit-nya sudah dicatat
     * per pertemuan dengan nomor invoice yang sama dengan pembayaran paket tidak ikut dihitung lagi.
     */
    public function homeVisitMeetingsFor(ClassEnrollment $enrollment): int
    {
        if (! $enrollment->private_package_id || ! filled($enrollment->home_visit_address)) {
            return 0;
        }

        if ($enrollment->exists && $this->hasLegacyHomeVisitCharges($enrollment)) {
            return 0;
        }

        $enrollment->loadMissing('privatePackage');

        return max((int) ($enrollment->privatePackage?->total_meetings ?? 0), 0);
    }

    public function homeVisitFeeFor(ClassEnrollment $enrollment): float
    {
        return (float) ($this->homeVisitMeetingsFor($enrollment) * ExtraFeeService::FEE_HOME_VISIT);
    }

    /** Home Visit lama yang dicatat per pertemuan bersama DP (satu nomor invoice dengan pembayaran paket). */
    private function hasLegacyHomeVisitCharges(ClassEnrollment $enrollment): bool
    {
        return Payment::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('payment_type', Payment::TYPE_HOME_VISIT)
            ->whereIn('invoice_number', Payment::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('payment_type', Payment::TYPE_PACKAGE)
                ->select('invoice_number'))
            ->exists();
    }

    /**
     * Status Home Visit berdasarkan uang yang benar-benar masuk.
     *
     * @return array{meetings:int, fee:int, due:float, paid:float, remaining:float, closed:int, lunas:bool}
     */
    public function homeVisitStatus(ClassEnrollment $enrollment, ?float $paidTotal = null): array
    {
        $meetings = $this->homeVisitMeetingsFor($enrollment);
        $fee      = ExtraFeeService::FEE_HOME_VISIT;

        if ($meetings === 0) {
            return ['meetings' => 0, 'fee' => $fee, 'due' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'closed' => 0, 'lunas' => true];
        }

        $due  = $this->componentsDue($enrollment)['home_visit'];
        $paid = $this->paymentComponents($enrollment, 0.0, $paidTotal ?? $this->totalPaid($enrollment))['home_visit'];

        $lunas  = $paid + 0.005 >= $due;
        $closed = $lunas ? $meetings : min($meetings, (int) floor(($paid + 0.005) / $fee));

        return [
            'meetings'  => $meetings,
            'fee'       => $fee,
            'due'       => $due,
            'paid'      => $paid,
            'remaining' => round(max($due - $paid, 0), 2),
            'closed'    => $closed,
            'lunas'     => $lunas,
        ];
    }

    /**
     * ID pertemuan yang sudah tertutup pembayaran Home Visit: pertemuan reguler kelas (tanpa yang dibatalkan)
     * berurutan dari yang paling awal, sebanyak pertemuan yang tertutup.
     *
     * @return array<int, int>
     */
    public function homeVisitCoveredSessionIds(ClassEnrollment $enrollment): array
    {
        $closed = $this->homeVisitStatus($enrollment)['closed'];

        if ($closed < 1 || ! $enrollment->class_id) {
            return [];
        }

        return ClassSession::query()
            ->where('class_id', $enrollment->class_id)
            ->where('kind', 'Regular')
            ->where('status', '!=', 'Cancelled')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->limit($closed)
            ->pluck('id')
            ->all();
    }

    /** True kalau pertemuan ini sudah tertutup pembayaran Home Visit lewat tagihan paket. */
    public function sessionHomeVisitCovered(ClassSession $session): bool
    {
        if (! $session->class_id || $session->kind !== 'Regular') {
            return false;
        }

        return ClassEnrollment::query()
            ->where('class_id', $session->class_id)
            ->whereNotNull('private_package_id')
            ->whereNotNull('home_visit_address')
            ->whereIn('status', ['Active', 'Completed'])
            ->get()
            ->contains(fn (ClassEnrollment $e) => in_array($session->id, $this->homeVisitCoveredSessionIds($e), true));
    }

    /**
     * Lama kelas (bulan), hanya informasi.
     * Tidak dipakai untuk mengalikan harga.
     */
    public function monthsFor(ClassEnrollment $e): int
    {
        $package = $e->programPackage ?? $e->privatePackage;

        return $this->monthsForPackage($package, ! $e->programPackage);
    }

    /**
     * Total biaya les dari enrollment.
     * Paket reguler dengan skema Termin menggunakan totalFor('Termin').
     * Selain itu, gunakan harga paket apa adanya.
     */
    public function packageTotal(ClassEnrollment $e): ?float
    {
        $isPrivate = ! $e->programPackage;
        $package = $e->programPackage ?? $e->privatePackage;

        if (
            ! $isPrivate
            && $e->payment_scheme === 'Termin'
            && $package?->supportsInstallment()
        ) {
            return $package->totalFor('Termin');
        }

        return $this->packageTotalFor($package, $isPrivate);
    }

    /**
     * Lama kelas (bulan) dari paket.
     * Fallback 3 bulan untuk program dan 1 bulan untuk private.
     */
    public function monthsForPackage(mixed $package, bool $isPrivate): int
    {
        return (int) ($package?->duration_months ?: ($isPrivate ? 1 : 3));
    }

    /**
     * Total biaya paket sesuai harga yang tersimpan.
     * Parameter $isPrivate dipertahankan untuk kompatibilitas.
     */
    public function packageTotalFor(mixed $package, bool $isPrivate): ?float
    {
        if (! $package || $package->price === null) {
            return null;
        }

        return (float) $package->price;
    }

    /**
     * Termin diizinkan kalau paket reguler (bukan private) punya Jumlah Termin
     * atau Harga Termin. Admin mengatur lewat halaman Program & Level: kosongkan
     * keduanya untuk paket yang wajib Lunas (mis. HSK 4 ke atas).
     */
    public function allowsInstallment(bool $isPrivate, mixed $package = null): bool
    {
        if ($isPrivate || ! is_object($package)) {
            return false;
        }

        return method_exists($package, 'supportsInstallment')
            ? $package->supportsInstallment()
            : (float) ($package->installment_price ?? 0) > 0;
    }

    public function enrollmentAllowsInstallment(ClassEnrollment $e): bool
    {
        return $this->allowsInstallment(
            (bool) $e->private_package_id,
            $e->programPackage,
        );
    }

    /**
     * Nama level menurut PAKET enrollment, bukan level siswa saat ini, supaya
     * tidak ikut berubah ketika siswa dinaikkan level. Paket tanpa level
     * (Daily Activity, private) jatuh ke level siswa.
     */
    public function levelNameFor(ClassEnrollment $e): ?string
    {
        return $e->programPackage?->level?->level_name
            ?? $e->student?->currentLevel?->level_name;
    }

    /**
     * Total pembayaran khusus paket.
     * Pembayaran biaya tambahan tidak dihitung ke tagihan paket.
     */
    public function totalPaid(ClassEnrollment $enrollment): float
    {
        return (float) $enrollment->packagePayments()->sum('amount_paid');
    }

    public function remainingBill(ClassEnrollment $enrollment): float
    {
        $totalBill = $this->totalBillFor($enrollment) ?? 0.0;

        return max($totalBill - $this->totalPaid($enrollment), 0);
    }

    public function isFullyPaid(ClassEnrollment $enrollment): bool
    {
        return $this->remainingBill($enrollment) <= 0;
    }

    /**
     * Membuat nomor invoice dengan format yang sama untuk semua jenis pembayaran.
     * Format: INV-YYYYMMDD-XXXXXX
     */
    public function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Payment::where('invoice_number', $number)->exists());

        return $number;
    }
}