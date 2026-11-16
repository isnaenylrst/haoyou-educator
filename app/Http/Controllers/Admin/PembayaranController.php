<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Models\ProgramCategory;
use App\Services\PaymentInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    private const STAGES = ['Termin 1', 'Termin 2', 'Termin 3', 'Pelunasan'];

    private const METHODS = ['Transfer Bank', 'Cash', 'QRIS'];

    // Nilai filter "type" -> payments.payment_type untuk biaya tambahan.
    // Di database saat ini: 'Package' dan 'Replacement' ('Home Visit' belum ada barisnya).
    private const EXTRA_TYPES = [
        'kelas_pengganti' => Payment::TYPE_REPLACEMENT,
        'home_visit'      => Payment::TYPE_HOME_VISIT,
    ];

    // Sama dengan PaymentInstallmentService::totalBillFor():
    // harga paket (termin: installment_price x lama paket) + pendaftaran + aktivitas - diskon, minimal 0.
    private const BILL_SQL = 'GREATEST(COALESCE(
        (select case
            when class_enrollments.payment_scheme = \'Termin\' and program_packages.installment_price > 0
                then program_packages.installment_price * GREATEST(COALESCE(program_packages.duration_months, 1), 1)
            else program_packages.price
         end
         from program_packages where program_packages.id = class_enrollments.program_package_id),
        (select price from private_packages where private_packages.id = class_enrollments.private_package_id),
        0)
        + class_enrollments.registration_fee
        + class_enrollments.activity_fee
        - class_enrollments.discount, 0)';

    // Hanya pembayaran paket yang mengurangi tagihan paket.
    private const PAID_SQL = 'COALESCE(
        (
            select sum(amount_paid)
            from payments
            where payments.enrollment_id = class_enrollments.id
                and payments.payment_type = \'Package\'
                and payments.deleted_at is null
        ),
        0
    )';

    // Kategori enrollment: paket HSK punya category_id sendiri; paket Daily Activity category_id-nya null,
    // jadi diambil dari level kelas yang diikuti. Enrollment "Waiting Class" (belum ada kelas) => NULL.
    private const CATEGORY_SQL = 'COALESCE(
        (select program_packages.category_id
           from program_packages
          where program_packages.id = class_enrollments.program_package_id),
        (select program_levels.category_id
           from classes
           join program_levels on program_levels.id = classes.level_id
          where classes.id = class_enrollments.class_id)
    )';

    public function __construct(private PaymentInstallmentService $payments)
    {
    }

    /* ============================================================
       INDEX
    ============================================================ */
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $period = $this->describePeriod($from, $to);
        [$type] = $this->parseType($request);

        // Replacement / Home Visit hanya masuk akal sebagai daftar transaksi (satu baris per invoice)
        $view = ($request->input('view') === 'transaksi' || isset(self::EXTRA_TYPES[$type]))
            ? 'transaksi'
            : 'tagihan';

        $enrollments  = null;
        $transactions = null;

        if ($view === 'tagihan') {
            /* ---------- Tabel tagihan per enrollment ---------- */
            $enrollments = $this->scopedEnrollments($request, $from, $to)
                ->select('class_enrollments.*')
                ->selectRaw(self::BILL_SQL . ' as total_bill')
                ->selectRaw(self::PAID_SQL . ' as total_paid')
                ->selectRaw('(select category_name from program_categories where program_categories.id = '
                    . self::CATEGORY_SQL . ') as category_name')
                ->with([
                    'student:id,name',
                    'programPackage',   // kolom harga dibutuhkan untuk menghitung pembayaran berikutnya
                    'privatePackage',
                    'packagePayments:id,enrollment_id,payment_stage',
                ])
                ->when($request->payment_status === 'lunas', fn ($q) => $q->whereRaw(self::PAID_SQL . ' >= ' . self::BILL_SQL))
                ->when($request->payment_status === 'sebagian', fn ($q) => $q->whereRaw(
                    self::PAID_SQL . ' > 0 and ' . self::PAID_SQL . ' < ' . self::BILL_SQL
                ))
                ->when($request->payment_status === 'belum', fn ($q) => $q->whereRaw(self::PAID_SQL . ' = 0'))
                // yang masih punya sisa tagihan tampil duluan
                ->orderByRaw('(' . self::BILL_SQL . ' - ' . self::PAID_SQL . ' > 0) desc')
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

            // Pembayaran berikutnya menurut aturan DP / Lunas / Termin (null kalau sudah lunas)
            $enrollments->getCollection()->each(function ($enrollment) {
                $next = $this->payments->nextPayment($enrollment);

                $enrollment->next_stage  = $next['stage'] ?? null;
                $enrollment->next_amount = $next['amount'] ?? null;   // null = DP (nominal bebas)
                $enrollment->next_min    = $next['min'] ?? null;
                $enrollment->next_exact  = $next['exact'] ?? false;
            });
        } else {
            /* ---------- Tabel transaksi (semua jenis pembayaran) ---------- */
            $transactions = $this->transactionsQuery($request, $from, $to)
                ->paginate(10)
                ->withQueryString();
        }

        /* ---------- Kartu statistik ----------
           Tagihan: enrollment yang daftar di rentang, ATAU ada pembayaran di rentang,
           ATAU masih punya sisa tagihan (utang tidak hilang saat ganti bulan).
           Terkumpul: semua jenis pembayaran (paket + biaya tambahan) menurut tanggal bayar,
           mengikuti filter cari dan jenis. */
        $rows = $this->scopedEnrollments($request, $from, $to)
            ->select('class_enrollments.id', 'class_enrollments.student_id')
            ->selectRaw(self::BILL_SQL . ' as total_bill')
            ->selectRaw(self::PAID_SQL . ' as total_paid')
            ->get();

        $unpaidRows = $rows->filter(fn ($e) => $e->total_bill - $e->total_paid > 0);

        $paidCount   = $rows->filter(fn ($e) => $e->total_bill > 0 && $e->total_bill - $e->total_paid <= 0)->count();
        $outstanding = $unpaidRows->sum(fn ($e) => $e->total_bill - $e->total_paid);
        $unpaidCount = $unpaidRows->pluck('student_id')->unique()->count();

        $collected = $this->transactionsQuery($request, $from, $to)
            ->reorder()
            ->sum('amount_paid');

        return view('admin.pembayaran', [
            'view'         => $view,
            'enrollments'  => $enrollments,
            'transactions' => $transactions,
            'categories'   => ProgramCategory::with('program:id,program_name')
                                ->orderBy('program_id')
                                ->orderBy('id')
                                ->get(['id', 'program_id', 'category_name']),
            'period'       => $period,
            'from'         => $from?->toDateString(),
            'to'           => $to?->toDateString(),
            'paidCount'    => $paidCount,
            'outstanding'  => $outstanding,
            'unpaidCount'  => $unpaidCount,
            'collected'    => $collected,
            'stages'       => self::STAGES,
            'methods'      => self::METHODS,
        ]);
    }

    /* ============================================================
       SHOW — riwayat pembayaran satu enrollment (JSON untuk modal)
    ============================================================ */
    public function show(ClassEnrollment $enrollment)
    {
        $enrollment->load(['student:id,name', 'programPackage', 'privatePackage']);

        $payments = $enrollment->payments()
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'student_name' => $enrollment->student?->name,
            'package_name' => $enrollment->programPackage?->package_name
                ?? $enrollment->privatePackage?->package_name,
            'total_bill'   => $this->payments->totalBillFor($enrollment),
            'total_paid'   => $this->payments->totalPaid($enrollment),
            'remaining'    => $this->payments->remainingBill($enrollment),
            'next_payment' => $this->payments->nextPayment($enrollment),
            'payments' => $payments->map(fn (Payment $p) => [
                'id'             => $p->id,
                'invoice_number' => $p->invoice_number,
                'payment_type'   => $p->payment_type,
                'stage'          => $p->payment_stage,
                'amount_paid'    => (float) $p->amount_paid,
                'remaining_bill' => (float) $p->remaining_bill,
                'method'         => $p->payment_method,
                'date'           => $p->payment_date?->format('d M Y'),
                'status'         => $p->status,
                'proof_url'      => $p->payment_proof_path
                    ? asset('storage/' . $p->payment_proof_path)
                    : null,
            ]),
        ]);
    }

    /* ============================================================
       STORE — catat satu transaksi termin lanjutan
    ============================================================ */
    public function store(Request $request, ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status === 'Cancelled', 422, 'Enrollment yang dibatalkan tidak bisa menerima pembayaran.');

        $enrollment->loadMissing(['programPackage', 'privatePackage']);

        if ($this->payments->totalBillFor($enrollment) !== null && $this->payments->isFullyPaid($enrollment)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Tagihan enrollment ini sudah lunas.',
            ]);
        }

        // Tahap dan nominal ditentukan PaymentInstallmentService::nextPayment (Pelunasan atau Termin 1..3,
        // nominal harus sesuai jadwal). Input payment_stage dari form lama diabaikan.
        $next = $this->payments->nextPayment($enrollment);

        $data = $request->validate([
            'amount_paid'    => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'payment_date'   => ['required', 'date'],
            'payment_proof'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [], [
            'amount_paid'    => 'nominal',
            'payment_method' => 'metode pembayaran',
            'payment_date'   => 'tanggal pembayaran',
            'payment_proof'  => 'bukti pembayaran',
        ]);

        $proofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('payment-proofs', 'public')
            : null;

        try {
            DB::transaction(fn () => $this->payments->recordPayment(
                $enrollment,
                $next['stage'] ?? 'Pelunasan',
                (float) $data['amount_paid'],
                $data['payment_method'],
                $data['payment_date'],
                $proofPath,
            ));
        } catch (ValidationException $e) {
            // service menolak (mis. melebihi sisa tagihan) — jangan tinggalkan file yatim
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }
            throw $e;
        }

        return redirect()
            ->route('admin.pembayaran', $request->only(['search', 'type', 'payment_status', 'view', 'from', 'to', 'page']))
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    /* ============================================================
       PEMBAYARAN LANJUTAN (halaman penuh, sama seperti alur Siswa)
       step 1: pembayaran & bukti, step 2: invoice & formulir terbaru
    ============================================================ */
    public function settleForm(ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status === 'Cancelled', 404);

        $enrollment->loadMissing([
            'student.currentLevel',
            'programPackage.program',
            'privatePackage',
            'class.programPackage',
        ]);

        if ($this->payments->totalBillFor($enrollment) === null || $this->payments->isFullyPaid($enrollment)) {
            return redirect()->route('admin.pembayaran')
                ->with('success', 'Tagihan enrollment ini sudah lunas.');
        }

        $package = $enrollment->programPackage
            ?? $enrollment->class?->programPackage
            ?? $enrollment->privatePackage;

        $total     = (float) $this->payments->totalBillFor($enrollment);
        $paid      = (float) $this->payments->totalPaid($enrollment);
        $remaining = (float) $this->payments->remainingBill($enrollment);

        return view('admin.calon-siswa.convert', [
            'step'           => 2,
            'personName'     => $enrollment->student?->name,
            'personLabel'    => 'Siswa',
            'eyebrow'        => 'Pelunasan:',
            'subtitle'       => 'Catat pembayaran lanjutan untuk tagihan program ini.',
            'formAction'     => route('admin.pembayaran.settle.store', $enrollment),
            'backUrl'        => route('admin.pembayaran'),
            'cancelUrl'      => route('admin.pembayaran'),
            'package'        => $package,
            'isPrivate'      => (bool) $enrollment->private_package_id,
            'totalBill'      => $remaining,
            'canInstallment' => $this->payments->enrollmentAllowsInstallment($enrollment),
            'settlement'     => ['total' => $total, 'paid' => $paid, 'remaining' => $remaining],
            'nextPayment'    => $this->payments->nextPayment($enrollment),
            'stepLabels'     => [1 => 'Pembayaran & Bukti', 2 => 'Invoice & Formulir'],
            'stepDisplay'    => 1,
        ]);
    }

    public function settleStore(Request $request, ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status === 'Cancelled', 404);

        $enrollment->loadMissing(['student', 'programPackage', 'privatePackage']);

        if ($this->payments->totalBillFor($enrollment) === null || $this->payments->isFullyPaid($enrollment)) {
            return redirect()->route('admin.pembayaran')->with('success', 'Tagihan enrollment ini sudah lunas.');
        }

        $remaining = (float) $this->payments->remainingBill($enrollment);

        // Tahap dan nominal ditentukan PaymentInstallmentService::nextPayment; service menolak nominal yang tidak sesuai.
        $data = $request->validate([
            'amount_paid'    => ['required', 'numeric', 'min:1', 'max:' . $remaining],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'payment_date'   => ['required', 'date', 'before_or_equal:today'],
            'proof'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'amount_paid.max' => 'Nominal melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').',
        ]);

        $amount = (float) $data['amount_paid'];
        $stage  = $this->payments->nextPayment($enrollment)['stage'] ?? 'Pelunasan';

        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        try {
            DB::transaction(fn () => $this->payments->recordPayment(
                $enrollment,
                $stage,
                $amount,
                $data['payment_method'],
                $data['payment_date'],
                $proofPath,
            ));
        } catch (ValidationException $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        $after = max($remaining - $amount, 0);

        // Halaman akhir (invoice + formulir pendaftaran terbaru) dipakai bersama dengan alur Siswa
        return redirect()
            ->route('admin.siswa.continue.done', [
                $enrollment->student_id,
                $enrollment,
                'jenis' => 'pelunasan',
                'dari'  => 'pembayaran',
            ])
            ->with('success', $after <= 0
                ? 'Pembayaran ' . $enrollment->student?->name . ' sudah lunas.'
                : 'Pembayaran dicatat. Sisa tagihan Rp ' . number_format($after, 0, ',', '.') . '.');
    }

    /* ============================================================
       Filter & rentang tanggal
    ============================================================ */

    /**
     * Enrollment aktif + filter cari & jenis, dan salah satu dari:
     *  (a) daftar di rentang, (b) ada pembayaran di rentang, (c) masih punya sisa tagihan.
     */
    private function scopedEnrollments(Request $request, ?Carbon $from, ?Carbon $to)
    {
        $query = ClassEnrollment::query()->where('status', '!=', 'Cancelled');

        $this->applyEnrollmentFilters($query, $request);

        return $query->where(function ($w) use ($from, $to) {
            $w->where(function ($q) use ($from, $to) {
                $q->when($from, fn ($x) => $x->whereDate('class_enrollments.enrollment_date', '>=', $from->toDateString()))
                  ->when($to, fn ($x) => $x->whereDate('class_enrollments.enrollment_date', '<=', $to->toDateString()));
            })
            ->orWhereHas('payments', function ($p) use ($from, $to) {
                $p->when($from, fn ($x) => $x->whereDate('payment_date', '>=', $from->toDateString()))
                  ->when($to, fn ($x) => $x->whereDate('payment_date', '<=', $to->toDateString()));
            })
            ->orWhereRaw(self::BILL_SQL . ' - ' . self::PAID_SQL . ' > 0');
        });
    }

    /** Filter cari nama siswa + jenis paket (reguler / kategori tertentu / private). */
    private function applyEnrollmentFilters($query, Request $request): void
    {
        [$type, $categoryId] = $this->parseType($request);

        $query
            ->when($request->filled('search'), fn ($q) => $q->whereHas(
                'student',
                fn ($s) => $s->where('name', 'like', '%' . $request->search . '%')
            ))
            ->when($type === 'reguler', fn ($q) => $q->whereNotNull('program_package_id'))
            ->when($type === 'private', fn ($q) => $q->whereNotNull('private_package_id'))
            ->when($categoryId, fn ($q) => $q
                ->whereNotNull('program_package_id')
                ->whereRaw(self::CATEGORY_SQL . ' = ?', [$categoryId]));
    }

    /**
     * Nilai ?type= : '' | reguler | kategori:ID | private | kelas_pengganti | home_visit
     *
     * @return array{0: string, 1: ?int}  [jenis, id kategori]
     */
    private function parseType(Request $request): array
    {
        $t = (string) $request->input('type', '');

        if (str_starts_with($t, 'kategori:')) {
            $id = (int) substr($t, strlen('kategori:'));

            return ['reguler', $id > 0 ? $id : null];
        }

        return [$t, null];
    }

    /**
     * Satu baris per pembayaran (semua jenis: Package, Replacement, Home Visit),
     * ikut filter cari, jenis, dan rentang tanggal bayar.
     */
    private function transactionsQuery(Request $request, ?Carbon $from, ?Carbon $to)
    {
        [$type, $categoryId] = $this->parseType($request);

        return Payment::query()
            ->with(['enrollment.student:id,name', 'enrollment.programPackage', 'enrollment.privatePackage'])
            ->whereHas('enrollment', function ($q) use ($request) {
                $q->where('status', '!=', 'Cancelled');
                $this->applyEnrollmentFilters($q, $request);
            })
            ->when(
                isset(self::EXTRA_TYPES[$type]),
                fn ($q) => $q->where('payment_type', self::EXTRA_TYPES[$type])
            )
            ->when(
                in_array($type, ['reguler', 'private'], true) || $categoryId !== null,
                fn ($q) => $q->where('payment_type', Payment::TYPE_PACKAGE)
            )
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to->toDateString()))
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }

    /**
     * Rentang tanggal untuk tabel dan kartu statistik.
     *  1. Tidak ada filter tanggal  -> bulan ini (tanggal 1 sampai akhir bulan)
     *  2. Ada filter (?from=&to=)   -> sesuai filter
     * Tidak ada mode "semua waktu".
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $from = $this->parseDate($request->input('from'));
        $to   = $this->parseDate($request->input('to'));

        if (! $from && ! $to) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        // kalau hanya satu tanggal terisi, anggap rentang satu hari
        $from ??= $to->copy();
        $to   ??= $from->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Label kartu mengikuti rentang yang dipilih:
     * satu hari -> "Hari", satu minggu penuh -> "Minggu", satu bulan penuh -> "Bulan", selain itu "Periode".
     *
     * @return array{key: ?string, current: bool, label: string, note: string}
     */
    private function describePeriod(?Carbon $from, ?Carbon $to): array
    {
        $fmt = fn (Carbon $d) => $d->copy()->locale('id')->translatedFormat('j M Y');

        if (! $from || ! $to) {
            return [
                'key'     => 'periode',
                'current' => false,
                'label'   => 'Periode Terpilih',
                'note'    => $from ? 'Sejak ' . $fmt($from) : 'Sampai ' . $fmt($to),
            ];
        }

        $now = now();
        $f = $from->toDateString();
        $t = $to->toDateString();

        if ($f === $t) {
            $key = 'hari';
            $current = $f === $now->toDateString();
            $note = $fmt($from);
        } elseif ($f === $from->copy()->startOfWeek()->toDateString() && $t === $from->copy()->endOfWeek()->toDateString()) {
            $key = 'minggu';
            $current = $f === $now->copy()->startOfWeek()->toDateString();
            $note = $fmt($from) . ' – ' . $fmt($to);
        } elseif ($f === $from->copy()->startOfMonth()->toDateString() && $t === $from->copy()->endOfMonth()->toDateString()) {
            $key = 'bulan';
            $current = $f === $now->copy()->startOfMonth()->toDateString();
            $note = $from->copy()->locale('id')->translatedFormat('F Y');
        } else {
            $key = 'periode';
            $current = false;
            $note = $fmt($from) . ' – ' . $fmt($to);
        }

        $names = ['hari' => 'Hari', 'minggu' => 'Minggu', 'bulan' => 'Bulan', 'periode' => 'Periode'];

        $label = match (true) {
            $key === 'periode' => 'Periode Terpilih',
            $current           => $names[$key] . ' Ini',
            default            => $names[$key] . ' Terpilih',
        };

        return ['key' => $key, 'current' => $current, 'label' => $label, 'note' => $note];
    }
}