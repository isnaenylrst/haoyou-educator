<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationalExpense;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Kas Operasional: uang kas dari Owner (dicatat Admin) dan pengeluaran operasional
 * di luar pengajuan guru (kertas, spidol, galon, tisu, dll.). Pengeluaran dicatat Admin,
 * disahkan Owner, dan setelah disetujui mengurangi saldo kas.
 * Controller ini khusus Admin.
 *
 * ROUTES: group Admin di web.php sudah ber-prefix "admin" dan name "admin." (Owner: "owner"),
 * jadi di dalamnya CUKUP:
 *
 *   // di dalam group Admin
 *   Route::prefix('kas-operasional')->name('kas.')->group(function () {
 *       Route::get('/', [OperationalExpenseController::class, 'index'])->name('index');
 *       Route::post('/', [OperationalExpenseController::class, 'store'])->name('store');
 *       Route::put('/{expense}', [OperationalExpenseController::class, 'update'])->name('update');
 *       Route::delete('/{expense}', [OperationalExpenseController::class, 'destroy'])->name('destroy');
 *       Route::post('/kas-masuk', [OperationalExpenseController::class, 'storeCash'])->name('cash.store');
 *       Route::delete('/kas-masuk/{cashFund}', [OperationalExpenseController::class, 'destroyCash'])
 *           ->whereNumber('cashFund')->name('cash.destroy');
 *   });
 *
 * Sisi Owner (lihat daftar + TTD) ada di Owner\SubmissionRecapController, tab "Kas Operasional".
 */
class OperationalExpenseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRole(['Admin']);

        $status   = $request->query('status');
        $category = $request->query('category');
        $search   = $request->query('q');

        $bulan    = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : null;
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_from')) ? $request->query('date_from') : null;
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_to')) ? $request->query('date_to') : null;

        // Default periode = bulan ini. Hanya "Semua Periode" (?periode=all) atau
        // rentang tanggal yang mematikan default ini.
        $semuaPeriode = $request->query('periode') === 'all';
        if (! $bulan && ! $dateFrom && ! $dateTo && ! $semuaPeriode) {
            $bulan = now()->format('Y-m');
        }

        $base = OperationalExpense::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($s) use ($search) {
                    $s->where('item_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                $dateFrom || $dateTo,
                function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, fn ($qq) => $qq->whereDate('date', '>=', $dateFrom))
                      ->when($dateTo, fn ($qq) => $qq->whereDate('date', '<=', $dateTo));
                },
                function ($q) use ($bulan) {
                    $q->when($bulan, function ($qq) use ($bulan) {
                        [$y, $m] = explode('-', $bulan);
                        $qq->whereYear('date', (int) $y)->whereMonth('date', (int) $m);
                    });
                }
            );

        // Kartu ringkasan: yang dihitung sebagai pengeluaran hanya yang sudah Disetujui Owner
        $totalDisetujui = (float) (clone $base)->where('status', 'Disetujui')->sum('amount');
        $menungguCount  = (clone $base)->where('status', 'Menunggu Owner')->count();
        $menungguTotal  = (float) (clone $base)->where('status', 'Menunggu Owner')->sum('amount');
        $ditolakCount   = (clone $base)->where('status', 'Ditolak')->count();

        // Rekap per kategori (hanya yang Disetujui)
        $rekapKategori = (clone $base)->where('status', 'Disetujui')
            ->selectRaw('category, COUNT(*) AS jumlah, SUM(amount) AS total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        if ($dateFrom || $dateTo) {
            $periodLabel = ($dateFrom ? \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') : '...')
                . ' - '
                . ($dateTo ? \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') : '...');
        } elseif ($bulan) {
            $periodLabel = \Carbon\Carbon::createFromFormat('!Y-m', $bulan)->translatedFormat('F Y');
        } else {
            $periodLabel = 'Semua Periode';
        }

        // Kas masuk & saldo kas (logika bersama Admin/Owner ada di model)
        $cash = OperationalExpense::cashSummary($bulan, $dateFrom, $dateTo);

        // Yang menunggu Owner ditaruh paling atas
        $order = "CASE status WHEN 'Menunggu Owner' THEN 1 WHEN 'Ditolak' THEN 2 WHEN 'Disetujui' THEN 3 ELSE 99 END";

        $expenses = (clone $base)
            ->with(['creator', 'owner'])
            ->orderByRaw($order)
            ->latest('date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.kas.index', [
            'expenses'       => $expenses,
            'categories'     => OperationalExpense::CATEGORIES,
            'statuses'       => OperationalExpense::STATUSES,
            'isAdmin'        => $this->role() === 'Admin',
            'periodLabel'    => $periodLabel,
            'totalDisetujui' => $totalDisetujui,
            'menungguCount'  => $menungguCount,
            'menungguTotal'  => $menungguTotal,
            'ditolakCount'   => $ditolakCount,
            'rekapKategori'  => $rekapKategori,
            'status'         => $status,
            'category'       => $category,
            'search'         => $search,
            'bulan'          => $bulan,
            'semuaPeriode'   => $semuaPeriode,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
        ] + $cash);
    }

    /** Admin: catat pengeluaran baru + TTD, lalu teruskan ke Owner */
    public function store(Request $request)
    {
        $this->authorizeRole(['Admin']);

        $data = $this->validated($request, receiptRequired: true);

        $expense = OperationalExpense::create($this->payload($data) + [
            'receipt_path'         => $request->file('receipt')->store('receipts/operational', 'public'),
            'admin_signature_path' => $this->storeSignature($data['signature'], 'signatures/operational', 'admin'),
            'admin_signer_name'    => trim($data['signer_name']),
            'admin_signed_at'      => now(),
            'created_by'           => Auth::id(),
            'status'               => 'Menunggu Owner',
        ]);

        $this->notifyOwners($expense);

        return back()->with('success', 'Pengeluaran kas dicatat dan diteruskan ke Owner untuk ditandatangani.');
    }

    /** Admin: edit (selama belum Disetujui). Wajib TTD ulang dan dikirim ulang ke Owner. */
    public function update(Request $request, OperationalExpense $expense)
    {
        $this->authorizeRole(['Admin']);
        abort_unless($expense->isEditable(), 403, 'Pengeluaran yang sudah disetujui tidak bisa diubah.');

        $data = $this->validated($request, receiptRequired: false);

        $attrs = $this->payload($data) + [
            'admin_signature_path' => $this->storeSignature($data['signature'], 'signatures/operational', 'admin'),
            'admin_signer_name'    => trim($data['signer_name']),
            'admin_signed_at'      => now(),
            // kirim ulang ke Owner, keputusan sebelumnya dikosongkan
            'status'               => 'Menunggu Owner',
            'owner_id'             => null,
            'owner_signature_path' => null,
            'owner_signer_name'    => null,
            'owner_note'           => null,
            'owner_signed_at'      => null,
        ];

        $oldReceipt = null;
        if ($request->hasFile('receipt')) {
            $oldReceipt = $expense->receipt_path;
            $attrs['receipt_path'] = $request->file('receipt')->store('receipts/operational', 'public');
        }

        $expense->update($attrs);

        if ($oldReceipt) {
            Storage::disk('public')->delete($oldReceipt);
        }

        $this->notifyOwners($expense);

        return back()->with('success', 'Data diperbarui dan dikirim ulang ke Owner.');
    }

    public function destroy(OperationalExpense $expense)
    {
        $this->authorizeRole(['Admin']);
        abort_unless($expense->isEditable(), 403, 'Pengeluaran yang sudah disetujui tidak bisa dihapus.');

        // Soft delete: file nota & TTD dibiarkan agar jejak audit tetap ada
        $expense->delete();

        return back()->with('success', 'Data pengeluaran dihapus.');
    }

    /** Admin: catat uang kas masuk (dari Owner) */
    public function storeCash(Request $request)
    {
        $this->authorizeRole(['Admin']);

        $data = $request->validate([
            'date'   => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'note'   => ['nullable', 'string', 'max:255'],
        ], [
            'date.required'   => 'Tanggal kas masuk wajib diisi.',
            'amount.required' => 'Nominal kas masuk wajib diisi.',
            'amount.min'      => 'Nominal kas masuk minimal 1.',
        ]);

        DB::table('cash_funds')->insert([
            'date'       => $data['date'],
            'amount'     => $data['amount'],
            'note'       => $data['note'] ?? null,
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Kas masuk berhasil dicatat dan menambah saldo kas.');
    }

    /** Admin: hapus catatan kas masuk yang salah input (soft delete) */
    public function destroyCash(int $cashFund)
    {
        $this->authorizeRole(['Admin']);

        $deleted = DB::table('cash_funds')
            ->where('id', $cashFund)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        abort_unless($deleted, 404);

        return back()->with('success', 'Catatan kas masuk dihapus dan saldo kas disesuaikan.');
    }

    // ------------------------------------------------------------------
    // Otorisasi sederhana berbasis role (users.level -> levels.nama_level)
    // ------------------------------------------------------------------

    private function role(): ?string
    {
        return Auth::user()?->level?->nama_level;
    }

    private function authorizeRole(array $roles): void
    {
        abort_unless(in_array($this->role(), $roles, true), 403);
    }

    /**
     * Simpan TTD (data URL PNG dari canvas) sebagai file khusus pengeluaran ini.
     * Mengembalikan path relatif di disk "public".
     */
    private function storeSignature(?string $dataUrl, string $dir, string $prefix): string
    {
        if (! $dataUrl || ! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            throw ValidationException::withMessages(['signature' => 'Format tanda tangan tidak valid.']);
        }

        $binary = base64_decode($m[1], true);

        // Pastikan benar-benar PNG dan ukurannya wajar (maks 500 KB)
        if ($binary === false
            || strncmp($binary, "\x89PNG\r\n\x1a\n", 8) !== 0
            || strlen($binary) > 500 * 1024) {
            throw ValidationException::withMessages(['signature' => 'File tanda tangan tidak valid atau terlalu besar.']);
        }

        $path = trim($dir, '/') . '/' . $prefix . '-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function validated(Request $request, bool $receiptRequired): array
    {
        return $request->validate([
            'date'            => ['required', 'date', 'before_or_equal:today'],
            'item_name'       => ['required', 'string', 'max:255'],
            'category'        => ['required', Rule::in(OperationalExpense::CATEGORIES)],
            'amount'          => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'purchase_method' => ['required', 'in:Offline,Online'],
            // hanya http/https agar tidak bisa disisipi javascript: dsb.
            'purchase_link'   => ['required_if:purchase_method,Online', 'nullable', 'url:http,https', 'max:2048'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'receipt'         => [$receiptRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'signature'       => ['required', 'string', 'max:700000'],
            'signer_name'     => ['required', 'string', 'max:120'],
        ], [
            'purchase_link.required_if' => 'Link pembelian wajib diisi untuk belanja online.',
            'purchase_link.url'         => 'Link pembelian harus diawali http:// atau https://.',
            'receipt.required'          => 'Nota / bukti pembelian wajib diunggah.',
            'signature.required'        => 'Tanda tangan Admin wajib diisi.',
            'signer_name.required'      => 'Nama penandatangan wajib diisi.',
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'date'            => $data['date'],
            'item_name'       => trim($data['item_name']),
            'category'        => $data['category'],
            'amount'          => $data['amount'],
            'purchase_method' => $data['purchase_method'],
            'purchase_link'   => $data['purchase_method'] === 'Online' ? ($data['purchase_link'] ?? null) : null,
            'description'     => $data['description'] ?? null,
        ];
    }

    private function notifyOwners(OperationalExpense $expense): void
    {
        $owners = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Owner'))->get();

        foreach ($owners as $owner) {
            $owner->notify(new AppNotification(
                title: 'Pengeluaran Kas Menunggu TTD Owner',
                message: "Admin mencatat {$expense->item_name} sebesar {$expense->formatted_amount}. Menunggu tanda tangan Owner.",
                url: url('/owner/pengajuan?tab=kas'),
                type: 'kas',
                icon: 'fa-receipt'
            ));
        }
    }
}