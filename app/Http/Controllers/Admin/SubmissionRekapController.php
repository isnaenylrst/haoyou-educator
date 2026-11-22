<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AppNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmissionRekapController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Submission::class);

        $status    = $request->query('status');
        $type      = $request->query('type');
        $teacherId = $request->query('teacher_id');
        $search    = $request->query('q');

        // Filter periode (berdasarkan tanggal pengajuan)
        $bulan    = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : null;
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_from')) ? $request->query('date_from') : null;
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_to')) ? $request->query('date_to') : null;

        // Default periode = bulan ini. Hanya "Semua Periode" (?periode=all) atau
        // rentang tanggal yang mematikan default ini.
        $semuaPeriode = $request->query('periode') === 'all';
        if (! $bulan && ! $dateFrom && ! $dateTo && ! $semuaPeriode) {
            $bulan = now()->format('Y-m');
        }

        // Base query Admin: Menampilkan SEMUA pengajuan (termasuk Diajukan Kurikulum)
        $query = Submission::with(['teacher', 'curriculum', 'admin', 'owner'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('item_name', 'like', "%{$search}%")
                         ->orWhere('description', 'like', "%{$search}%")
                         ->orWhereHas('teacher', fn ($tQ) => $tQ->where('name', 'like', "%{$search}%"));
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

        // ---------------------------------------------------------------
        // 4 KARTU RINGKASAN (mengikuti filter yang aktif)
        // ---------------------------------------------------------------
        $statusDisetujui = ['Disetujui', 'Dibayar', 'Selesai'];

        // a. Total Pengeluaran: nominal pengajuan yang sudah disetujui (Disetujui, Dibayar, Selesai)
        $totalPengeluaran = (float) (clone $query)->whereIn('status', $statusDisetujui)->sum('amount');

        // b. Total Pengajuan: pengajuan yang sudah lolos ACC Kurikulum
        $totalPengajuan = (clone $query)
            ->whereIn('status', array_merge(['Review Admin', 'Review Owner'], $statusDisetujui))
            ->count();

        // c. Perlu Review Owner
        $perluReviewOwner = (clone $query)->where('status', 'Review Owner')->count();

        // d. Telah Disetujui
        $telahDisetujui = (clone $query)->whereIn('status', $statusDisetujui)->count();

        // Label periode untuk ditampilkan di atas kartu
        if ($dateFrom || $dateTo) {
            $periodLabel = ($dateFrom ? Carbon::parse($dateFrom)->translatedFormat('d M Y') : '...')
                . ' - '
                . ($dateTo ? Carbon::parse($dateTo)->translatedFormat('d M Y') : '...');
        } elseif ($bulan) {
            $periodLabel = Carbon::createFromFormat('!Y-m', $bulan)->translatedFormat('F Y');
        } else {
            $periodLabel = 'Semua Periode';
        }

        // Urutan tabel untuk Admin: Review Admin paling atas
        $priority = ['Review Admin', 'Review Kurikulum', 'Diajukan', 'Review Owner', 'Disetujui', 'Dibayar', 'Selesai', 'Ditolak'];

        $orderCase = 'CASE status';
        foreach ($priority as $i => $st) {
            $orderCase .= " WHEN '" . $st . "' THEN " . ($i + 1);
        }
        $orderCase .= ' ELSE 99 END';

        $submissions = $query->orderByRaw($orderCase)->latest('date')->latest('id')->paginate(12)->withQueryString();
        $teachers    = Teacher::where('status', 'Active')->orderBy('name')->get();

        return view('admin.pengajuan.index', compact(
            'submissions', 'teachers', 'periodLabel', 'semuaPeriode',
            'totalPengeluaran', 'totalPengajuan', 'perluReviewOwner', 'telahDisetujui',
            'status', 'type', 'teacherId', 'bulan', 'dateFrom', 'dateTo', 'search'
        ));
    }

    /** Verifikasi berkas reimburse oleh Admin sebelum diteruskan ke Owner */
    public function adminVerify(Request $request, Submission $submission)
    {
        Gate::authorize('verifyAdmin', $submission);

        $request->validate([
            'decision'  => ['required', 'in:Approved,Rejected'],
            'note'      => ['nullable', 'string', 'max:1000'],
            // TTD digambar langsung per pengajuan (data URL PNG dari canvas)
            'signature' => ['required_if:decision,Approved', 'nullable', 'string', 'max:700000'],
            // Nama yang dicetak di bawah TTD pada dokumen
            'signer_name' => ['required_if:decision,Approved', 'nullable', 'string', 'max:120'],
        ], [
            'signer_name.required_if' => 'Nama penandatangan wajib diisi.',
            'signature.required_if' => 'Tanda tangan Admin wajib diisi untuk memverifikasi pengajuan ini.',
        ]);

        $admin = Auth::user();

        return DB::transaction(function () use ($request, $submission, $admin) {
            if ($request->decision === 'Approved') {
                $signaturePath = $this->storeAdminSignature($request->signature, $submission);

                $submission->update([
                    'status'               => 'Review Owner',
                    'admin_id'             => $admin->id,
                    'admin_note'           => $request->note,
                    'admin_verified_at'    => now(),
                    'admin_signature_path' => $signaturePath,
                    'admin_signer_name'    => trim($request->signer_name),
                ]);

                // Notifikasi ke seluruh tim Owner
                $owners = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Owner'))->get();
                foreach ($owners as $owner) {
                    $owner->notify(new AppNotification(
                        title: "Pengajuan Siap Disetujui Owner",
                        message: "Admin telah memverifikasi pengajuan {$submission->item_name} sebesar {$submission->formatted_amount} dari {$submission->teacher?->name}. Menunggu persetujuan Owner.",
                        url: url('/owner/pengajuan'),
                        type: 'pengajuan',
                        icon: 'fa-receipt'
                    ));
                }

                return back()->with('success', 'Pengajuan berhasil diverifikasi Admin dan diteruskan ke Owner untuk persetujuan akhir.');
            }

            $submission->update([
                'status'            => 'Ditolak',
                'admin_id'          => $admin->id,
                'admin_note'        => $request->note ?? 'Ditolak pada tahap verifikasi administrasi.',
                'admin_verified_at' => now(),
            ]);

            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan Ditolak Admin",
                    message: "Pengajuan {$submission->item_name} ditolak Admin. Catatan: " . ($request->note ?? '-'),
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-xmark'
                ));
            }

            return back()->with('success', 'Pengajuan telah ditolak oleh Admin.');
        });
    }

    /**
     * Simpan TTD Admin (data URL PNG dari canvas) sebagai file khusus pengajuan ini.
     * Mengembalikan path relatif di disk "public".
     */
    private function storeAdminSignature(?string $dataUrl, Submission $submission): string
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

        $path = 'signatures/submissions/' . $submission->id . '-admin-' . now()->format('YmdHis') . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /** Cetak / preview formulir lembar persetujuan pengajuan dengan TTD */
    public function printSubmission(Submission $submission)
    {
        Gate::authorize('view', $submission);

        return view('kurikulum.cetak-pengajuan', compact('submission'));
    }
}