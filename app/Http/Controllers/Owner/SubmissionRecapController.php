<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerApproveSubmissionRequest;
use App\Models\OperationalExpense;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\Teacher;
use App\Notifications\AppNotification;
use App\Services\SubmissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SubmissionRecapController extends Controller
{
    public function __construct(
        protected SubmissionService $submissionService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Submission::class);

        $status    = $request->query('status');
        $type      = $request->query('type');
        $teacherId = $request->query('teacher_id');

        // Filter periode (berdasarkan tanggal pengajuan)
        $bulan    = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : null;
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_from')) ? $request->query('date_from') : null;
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_to')) ? $request->query('date_to') : null;

        // Base query Owner: HANYA yang sudah ACC Kurikulum (curriculum_approved_at terisi, atau status tertentu)
        $query = Submission::with(['teacher', 'curriculum', 'admin', 'owner'])
            ->where(function ($q) {
                $q->whereNotNull('curriculum_approved_at')
                  ->orWhereIn('status', ['Review Owner', 'Disetujui', 'Dibayar', 'Selesai']);
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
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

        // ---------------------------------------------------------------
        // 4 KARTU RINGKASAN OWNER (TAMPILAN PERSETUJUAN)
        // ---------------------------------------------------------------
        // 1. Total Pengeluaran: SUM nominal status Disetujui, Dibayar, Selesai
        $totalPengeluaran = (float) (clone $query)->whereIn('status', ['Disetujui', 'Dibayar', 'Selesai'])->sum('amount');

        // 2. Total Pengajuan
        $totalCount = (clone $query)->count();

        // 3. Perlu Review Owner: pengajuan berstatus Review Owner
        $perluReviewOwner = (clone $query)->where('status', 'Review Owner')->count();

        // 4. Telah Disetujui: Disetujui, Dibayar, Selesai
        $totalApproved = (clone $query)->whereIn('status', ['Disetujui', 'Dibayar', 'Selesai'])->count();

        $search = $request->query('q');
        if ($search) {
            $query->where(function ($subQ) use ($search) {
                $subQ->where('item_name', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%")
                     ->orWhereHas('teacher', fn ($tQ) => $tQ->where('name', 'like', "%{$search}%"));
            });
        }

        // Urutan prioritas Owner: Review Owner paling atas
        $priority = ['Review Owner', 'Disetujui', 'Review Admin', 'Review Kurikulum', 'Diajukan', 'Dibayar', 'Selesai', 'Ditolak'];
        $orderCase = 'CASE status';
        foreach ($priority as $i => $st) {
            $orderCase .= " WHEN '" . $st . "' THEN " . ($i + 1);
        }
        $orderCase .= ' ELSE 99 END';

        $submissions = $query->orderByRaw($orderCase)->latest('date')->latest('id')->paginate(12)->withQueryString();
        $teachers    = Teacher::where('status', 'Active')->orderBy('name')->get();

        // ---------------------------------------------------------------
        // TAB KAS OPERASIONAL (pengeluaran operasional yang dicatat Admin)
        // Memakai filter periode yang sama dengan tab pengajuan.
        // ---------------------------------------------------------------
        $tab         = $request->query('tab') === 'kas' ? 'kas' : 'pengajuan';
        $kasStatus   = $request->query('kas_status');
        $kasCategory = $request->query('kas_category');
        $kasSearch   = $request->query('kas_q');

        $kasBase = OperationalExpense::query()
            ->when($kasStatus, fn ($q) => $q->where('status', $kasStatus))
            ->when($kasCategory, fn ($q) => $q->where('category', $kasCategory))
            ->when($kasSearch, function ($q) use ($kasSearch) {
                $q->where(function ($sub) use ($kasSearch) {
                    $sub->where('item_name', 'like', "%{$kasSearch}%")
                        ->orWhere('description', 'like', "%{$kasSearch}%");
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

        $kasTotalDisetujui = (float) (clone $kasBase)->where('status', 'Disetujui')->sum('amount');
        $kasMenungguCount  = (clone $kasBase)->where('status', 'Menunggu Owner')->count();
        $kasMenungguTotal  = (float) (clone $kasBase)->where('status', 'Menunggu Owner')->sum('amount');

        // Angka di tab: jumlah yang menunggu keputusan Owner (tidak ikut filter)
        $kasPendingCount       = OperationalExpense::where('status', 'Menunggu Owner')->count();
        // Definisi sama dengan Antrean TTD: sudah diajukan dan belum ditandatangani Owner
        $pengajuanPendingCount = Submission::where(function ($q) {
            $q->where('status', 'Review Owner')
              ->orWhere(function ($sub) {
                  $sub->whereNotNull('curriculum_approved_at')
                      ->whereNull('owner_approved_at')
                      ->whereIn('status', ['Diajukan', 'Review Kurikulum', 'Review Admin', 'Review Owner']);
              });
        })->count();

        $kasOrder = "CASE status WHEN 'Menunggu Owner' THEN 1 WHEN 'Ditolak' THEN 2 WHEN 'Disetujui' THEN 3 ELSE 99 END";

        $kasExpenses = (clone $kasBase)
            ->with(['creator'])
            ->orderByRaw($kasOrder)
            ->latest('date')
            ->latest('id')
            ->paginate(12, ['*'], 'kas_page')
            ->withQueryString();

        // Kas masuk + saldo kas (logika bersama dengan halaman Admin ada di model)
        $cash = OperationalExpense::cashSummary($bulan, $dateFrom, $dateTo);

        return view('owner.pengajuan', compact(
            'submissions', 'teachers', 'totalCount', 'totalPengeluaran',
            'perluReviewOwner', 'totalApproved',
            'status', 'type', 'teacherId', 'bulan', 'dateFrom', 'dateTo', 'search',
            'tab', 'kasExpenses', 'kasTotalDisetujui', 'kasMenungguCount', 'kasMenungguTotal',
            'kasPendingCount', 'pengajuanPendingCount', 'kasStatus', 'kasCategory', 'kasSearch'
        ) + [
            'kasCategories' => OperationalExpense::CATEGORIES,
            'kasStatuses'   => OperationalExpense::STATUSES,
        ] + $cash);
    }

    /** Approval khusus Owner */
    public function approve(OwnerApproveSubmissionRequest $request, Submission $submission)
    {
        Gate::authorize('approveOwner', $submission);

        // Validasi keamanan: Pastikan pengajuan sudah di-ACC Kurikulum
        if (empty($submission->curriculum_approved_at) && !in_array($submission->status, ['Review Owner', 'Disetujui', 'Dibayar', 'Selesai'])) {
            return back()->with('error', 'Pengajuan ini belum di-ACC oleh Kurikulum sehingga tidak dapat diproses oleh Owner.');
        }

        if ($request->decision === 'Approved') {
            $user = Auth::user();
            if (empty($user->signature_path) || !\Illuminate\Support\Facades\Storage::disk('public')->exists($user->signature_path)) {
                return redirect()->route('profil.index')
                    ->with('error', 'Anda harus memiliki tanda tangan digital terlebih dahulu sebelum menyetujui pengajuan. Silakan simpan tanda tangan Anda di menu Profil & Tanda Tangan.');
            }
        }

        $result = $this->submissionService->approveByOwner(
            Auth::user(),
            $submission,
            $request->decision,
            $request->note
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /** Tandai pengajuan sebagai Dibayar oleh Owner */
    public function markPaid(Request $request, Submission $submission)
    {
        Gate::authorize('markPaidOwner', $submission);

        if (empty($submission->curriculum_approved_at) && !in_array($submission->status, ['Review Owner', 'Disetujui', 'Dibayar', 'Selesai'])) {
            return back()->with('error', 'Pengajuan ini belum di-ACC oleh Kurikulum.');
        }

        $result = $this->submissionService->markPaidByOwner(Auth::user(), $submission);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Owner: setujui (TTD) atau tolak pengeluaran Kas Operasional yang dicatat Admin.
     * TTD memakai tanda tangan digital Owner yang tersimpan di Profil (sama seperti approve pengajuan).
     */
    public function signExpense(Request $request, OperationalExpense $expense)
    {
        abort_unless($expense->status === 'Menunggu Owner', 403, 'Pengeluaran ini tidak sedang menunggu tanda tangan Owner.');

        $request->validate([
            'decision' => ['required', 'in:Approved,Rejected'],
            'note'     => ['required_if:decision,Rejected', 'nullable', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        $owner    = Auth::user();
        $approved = $request->decision === 'Approved';

        if ($approved) {
            if (empty($owner->signature_path) || ! Storage::disk('public')->exists($owner->signature_path)) {
                return redirect()->route('profil.index')
                    ->with('error', 'Anda harus memiliki tanda tangan digital terlebih dahulu sebelum menyetujui pengeluaran kas. Silakan simpan tanda tangan Anda di menu Profil & Tanda Tangan.');
            }

            // Menyalin TTD profil sebagai arsip + mencatat riwayat TTD (logika di model)
            $expense->approveByOwner($owner, $request->note);
        } else {
            $expense->update([
                'status'               => 'Ditolak',
                'owner_id'             => $owner->id,
                'owner_note'           => $request->note,
                'owner_signed_at'      => now(),
                'owner_signer_name'    => null,
                'owner_signature_path' => null,
            ]);
        }

        if ($expense->creator) {
            $expense->creator->notify(new AppNotification(
                title: $approved ? 'Pengeluaran Kas Disetujui' : 'Pengeluaran Kas Ditolak',
                message: $approved
                    ? "Owner menyetujui {$expense->item_name} sebesar {$expense->formatted_amount}."
                    : "Owner menolak {$expense->item_name}. Catatan: " . ($request->note ?? '-'),
                url: url('/admin/kas-operasional'),
                type: 'kas',
                icon: $approved ? 'fa-circle-check' : 'fa-circle-xmark'
            ));
        }

        return back()->with('success', $approved
            ? 'Pengeluaran kas disetujui dan ditandatangani.'
            : 'Pengeluaran kas ditolak.');
    }

    /** Cetak / preview formulir lembar persetujuan pengajuan dengan TTD */
    public function print(Submission $submission)
    {
        Gate::authorize('view', $submission);

        return view('kurikulum.cetak-pengajuan', compact('submission'));
    }
}