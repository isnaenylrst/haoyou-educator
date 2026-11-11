<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentInstallmentService
{
    /** Jumlah termin untuk skema Termin. */
    public const TERMIN_STAGES = ['Termin 1', 'Termin 2', 'Termin 3'];

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
                'invoice_number' => $this->generateInvoiceNumber(),
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
     * Sisa biaya les dibagi 3 (Termin 1..3, selisih pembulatan di termin terakhir);
     * biaya aktivitas yang belum tertutup DP ditambahkan ke Termin 1.
     *
     * @return array<int, array{stage:string, amount:float}>  hanya termin yang nominalnya > 0
     */
    public function terminPlan(ClassEnrollment $enrollment, float $downPayment): array
    {
        $split   = $this->terminLesShares($enrollment, $downPayment);
        $amounts = $split['shares'];
        $amounts[0] = round($amounts[0] + $split['activity_unpaid'], 2);

        $plan = [];

        foreach (self::TERMIN_STAGES as $i => $stage) {
            if ($amounts[$i] > 0) {
                $plan[] = ['stage' => $stage, 'amount' => (float) $amounts[$i]];
            }
        }

        return $plan;
    }

    /**
     * Pembagian biaya les ke 3 termin setelah DP, TANPA biaya aktivitas (dipakai formulir yang
     * menampilkan pendaftaran, aktivitas, dan termin sebagai kolom terpisah).
     * Sisa biaya les dibagi 3, selisih pembulatan di termin terakhir.
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
        );

        $les  = $alloc['les_remaining'];
        $base = floor($les / 3);

        return [
            'shares'          => [(float) $base, (float) $base, round($les - 2 * $base, 2)],
            'activity_unpaid' => $alloc['activity_unpaid'],
            'les_remaining'   => $les,
        ];
    }

    /**
     * Nominal tiap komponen tagihan: pendaftaran, aktivitas, dan biaya les (setelah diskon).
     * Jumlah ketiganya sama dengan total tagihan.
     *
     * @return array{pendaftaran: float, aktivitas: float, les: float}
     */
    public function componentsDue(ClassEnrollment $enrollment): array
    {
        $total = (float) ($this->totalBillFor($enrollment) ?? 0);
        $reg   = (float) $enrollment->registration_fee;
        $act   = (float) $enrollment->activity_fee;

        $lesNet = max($total - $reg - $act, 0);
        $feeDue = $total - $lesNet;
        $regDue = min($reg, $feeDue);

        return [
            'pendaftaran' => round($regDue, 2),
            'aktivitas'   => round($feeDue - $regDue, 2),
            'les'         => round($lesNet, 2),
        ];
    }

    /**
     * Pecah satu pembayaran ke komponen tagihan (untuk formulir dan invoice).
     * Uang disebar berurutan: pendaftaran, aktivitas, lalu biaya les, dimulai dari titik
     * "sudah dibayar sebelumnya" ($previousPaid), jadi pembayaran kedua dst. ikut menutup sisa komponen.
     *
     * @return array{pendaftaran:float, aktivitas:float, les:float}
     */
    public function paymentComponents(ClassEnrollment $enrollment, float $previousPaid, float $amount): array
    {
        $total = (float) ($this->totalBillFor($enrollment) ?? 0);
        $reg   = (float) $enrollment->registration_fee;
        $act   = (float) $enrollment->activity_fee;

        $lesNet = max($total - $reg - $act, 0);
        $feeDue = $total - $lesNet;
        $regDue = min($reg, $feeDue);
        $actDue = $feeDue - $regDue;

        // Irisan rentang uang [from, to] dengan rentang komponen [start, end]
        $overlap = fn (float $from, float $to, float $start, float $end): float
            => max(min($to, $end) - max($from, $start), 0);

        $from = $previousPaid;
        $to   = $previousPaid + $amount;

        $toReg = $overlap($from, $to, 0, $regDue);
        $toAct = $overlap($from, $to, $regDue, $regDue + $actDue);

        return [
            'pendaftaran' => round($toReg, 2),
            'aktivitas'   => round($toAct, 2),
            'les'         => round(max($amount - $toReg - $toAct, 0), 2),
        ];
    }

    /**
     * Sebar sejumlah uang ke komponen tagihan: pendaftaran, aktivitas, lalu biaya les (setelah diskon).
     * Diskon yang lebih besar dari biaya les mengurangi biaya tetap, jadi jumlah semua komponen
     * selalu sama dengan total tagihan.
     *
     * @return array{les_net:float, activity_unpaid:float, les_remaining:float}
     */
    private function allocate(float $amount, float $registrationFee, float $activityFee, float $totalBill): array
    {
        $lesNet = max($totalBill - $registrationFee - $activityFee, 0);
        $feeDue = $totalBill - $lesNet;

        $regDue = min($registrationFee, $feeDue);
        $actDue = $feeDue - $regDue;

        $toReg = min($amount, $regDue);
        $toAct = min($amount - $toReg, $actDue);
        $toLes = $amount - $toReg - $toAct;

        return [
            'les_net'         => round($lesNet, 2),
            'activity_unpaid' => round($actDue - $toAct, 2),
            'les_remaining'   => round(max($lesNet - $toLes, 0), 2),
        ];
    }

    /**
     * Total tagihan enrollment = harga paket + biaya pendaftaran
     * + biaya aktivitas - diskon.
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
            - (float) $enrollment->discount;

        return max($total, 0);
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
            && $package?->installment_price
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

    public function allowsInstallment(
        bool $isPrivate,
        ?string $programName,
        ?string $levelName
    ): bool {
        if ($isPrivate) {
            return false;
        }

        if ($programName === 'Daily Activity') {
            return true;
        }

        if (! str_starts_with((string) $levelName, 'HSK ')) {
            return false;
        }

        $level = (int) trim(substr($levelName, 4));

        // Termin hanya untuk HSK 3 ke atas (HSK 1 dan 2 dibayar lunas / DP lalu pelunasan)
        return $level >= 3;
    }

    public function enrollmentAllowsInstallment(ClassEnrollment $e): bool
    {
        return $this->allowsInstallment(
            (bool) $e->private_package_id,
            $e->programPackage?->program?->program_name,
            $this->levelNameFor($e),
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