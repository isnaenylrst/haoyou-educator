<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Services\PaymentInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    private const STAGES = ['Termin 1', 'Termin 2', 'Termin 3', 'Pelunasan'];

    private const METHODS = ['Transfer Bank', 'Cash', 'QRIS'];

    private const BILL_SQL = '(COALESCE(
        (select price from program_packages where program_packages.id = class_enrollments.program_package_id),
        (select price from private_packages where private_packages.id = class_enrollments.private_package_id),
        0) + class_enrollments.registration_fee)';

    private const PAID_SQL = 'COALESCE(
        (select sum(amount_paid) from payments
            where payments.enrollment_id = class_enrollments.id and payments.deleted_at is null),
        0)';

    public function __construct(private PaymentInstallmentService $payments)
    {
    }

    /* ============================================================
       INDEX
    ============================================================ */
    public function index(Request $request)
    {
        $query = ClassEnrollment::query()
            ->select('class_enrollments.*')
            ->selectRaw(self::BILL_SQL . ' as total_bill')
            ->selectRaw(self::PAID_SQL . ' as total_paid')
            ->with([
                'student:id,name',
                'programPackage:id,package_name',
                'privatePackage:id,package_name',
                'payments:id,enrollment_id,payment_stage',
            ])
            ->where('status', '!=', 'Cancelled')
            ->when($request->filled('search'), fn ($q) => $q->whereHas(
                'student',
                fn ($s) => $s->where('name', 'like', '%' . $request->search . '%')
            ))
            ->when($request->type === 'Reguler', fn ($q) => $q->whereNotNull('program_package_id'))
            ->when($request->type === 'Private', fn ($q) => $q->whereNotNull('private_package_id'))
            ->when($request->payment_status === 'lunas', fn ($q) => $q->whereRaw(self::PAID_SQL . ' >= ' . self::BILL_SQL))
            ->when($request->payment_status === 'sebagian', fn ($q) => $q->whereRaw(
                self::PAID_SQL . ' > 0 and ' . self::PAID_SQL . ' < ' . self::BILL_SQL
            ))
            ->when($request->payment_status === 'belum', fn ($q) => $q->whereRaw(self::PAID_SQL . ' = 0'))
            // yang masih punya sisa tagihan tampil duluan
            ->orderByRaw('(' . self::BILL_SQL . ' - ' . self::PAID_SQL . ' > 0) desc')
            ->latest('id');

        $enrollments = $query->paginate(10)->withQueryString();

        $enrollments->getCollection()->each(function ($enrollment) {
            $enrollment->next_stage = $this->suggestNextStage($enrollment);
        });

        /* ---------- Statistik (semua enrollment aktif, tidak terpengaruh filter) ---------- */
        $totals = ClassEnrollment::query()
            ->where('status', '!=', 'Cancelled')
            ->selectRaw(self::BILL_SQL . ' as total_bill')
            ->selectRaw(self::PAID_SQL . ' as total_paid')
            ->get();

        $outstanding = $totals->sum(fn ($e) => max($e->total_bill - $e->total_paid, 0));
        $unpaidCount = $totals->filter(fn ($e) => $e->total_bill - $e->total_paid > 0)->count();
        $paidCount   = $totals->filter(fn ($e) => $e->total_bill > 0 && $e->total_bill - $e->total_paid <= 0)->count();

        $collectedThisMonth = Payment::query()
            ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount_paid');

        return view('admin.pembayaran', [
            'enrollments'        => $enrollments,
            'outstanding'        => $outstanding,
            'unpaidCount'        => $unpaidCount,
            'paidCount'          => $paidCount,
            'collectedThisMonth' => $collectedThisMonth,
            'stages'             => self::STAGES,
            'methods'            => self::METHODS,
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
            'payments'     => $payments->map(fn (Payment $p) => [
                'id'             => $p->id,
                'invoice_number' => $p->invoice_number,
                'stage'          => $p->payment_stage,
                'amount_paid'    => (float) $p->amount_paid,
                'remaining_bill' => (float) $p->remaining_bill,
                'method'         => $p->payment_method,
                'date'           => $p->payment_date?->format('d M Y'),
                'status'         => $p->status,
                // asset('storage/...') = hasil `php artisan storage:link`; dipakai
                // ketimbang Storage::disk()->url() karena yang terakhir cuma
                // "Undefined method" di IDE (kontrak Filesystem tidak mendeklarasikan url()).
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

        // Satu tahap hanya boleh tercatat sekali per enrollment (Termin 1 tidak
        // boleh dobel), sesuai format formulir pendaftaran.
        $usedStages = $enrollment->payments()->pluck('payment_stage')->all();

        $data = $request->validate([
            'payment_stage'  => ['required', Rule::in(self::STAGES), Rule::notIn($usedStages)],
            'amount_paid'    => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'payment_date'   => ['required', 'date'],
            'payment_proof'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'payment_stage.not_in' => 'Tahap pembayaran ini sudah pernah dicatat untuk siswa ini.',
        ], [
            'payment_stage'  => 'tahap pembayaran',
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
                $data['payment_stage'],
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
            ->route('admin.pembayaran', $request->only(['search', 'type', 'payment_status', 'page']))
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    /* ============================================================
       Tahap yang disarankan berikutnya: Termin 1 -> 2 -> 3, lalu Pelunasan
    ============================================================ */
    private function suggestNextStage(ClassEnrollment $enrollment): string
    {
        $used = $enrollment->payments->pluck('payment_stage')->all();

        foreach (['Termin 1', 'Termin 2', 'Termin 3'] as $stage) {
            if (! in_array($stage, $used, true)) {
                return $stage;
            }
        }

        return 'Pelunasan';
    }
}