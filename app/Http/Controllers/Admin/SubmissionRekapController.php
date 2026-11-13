<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerApproveSubmissionRequest;
use App\Models\Payment;
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

        // Filter periode (berdasarkan tanggal pengajuan). Rentang tanggal lebih diutamakan daripada bulan.
        $bulan    = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : null;
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_from')) ? $request->query('date_from') : null;
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_to')) ? $request->query('date_to') : null;

        $isOwner = (Auth::user()->level?->nama_level === 'Owner');

        $query = Submission::with(['teacher', 'curriculum', 'admin', 'owner'])
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

        if ($isOwner) {
            // Hanya yang sudah ACC Kurikulum yang muncul untuk Owner
            $query->where(function ($q) {
                $q->whereNotNull('curriculum_approved_at')
                  ->orWhereIn('status', ['Review Owner', 'Disetujui', 'Dibayar', 'Selesai']);
            });
        }

        // ---------------------------------------------------------------
        // KARTU RINGKASAN
        // Semua kartu dihitung dari $query yang sama, jadi ikut berubah mengikuti filter
        // ---------------------------------------------------------------

        // Dipakai KEDUA role (Admin: 4 kartu, Owner: 6 kartu)
        $totalCount       = (clone $query)->count();
        $totalPengeluaran = (float) (clone $query)->whereIn('status', ['Disetujui', 'Dibayar', 'Selesai'])->sum('amount');
        $totalVerifikasi  = (clone $query)->where('status', 'Review Admin')->count();   // kartu Admin: "Perlu Verifikasi"
        $totalApproved    = (clone $query)->whereIn('status', ['Disetujui', 'Dibayar', 'Selesai'])->count();

        // Khusus Owner (Admin tidak butuh angka ini, jadi tidak di-query)
        $totalPemasukan = null;
        $totalMenunggu  = null;
        $saldo          = null;

        if ($isOwner) {
            // Menunggu Review = semua yang belum final: Diajukan s/d Review Owner
            $totalMenunggu = (clone $query)
                ->whereIn('status', ['Diajukan', 'Review Kurikulum', 'Review Admin', 'Review Owner'])
                ->count();

            // Pemasukan = pembayaran siswa (Paid/Partial), mengikuti filter periode yang sama
            // tetapi berdasarkan tanggal pembayaran (payment_date).
            $totalPemasukan = (float) Payment::whereIn('status', ['Paid', 'Partial'])
                ->when(
                    $dateFrom || $dateTo,
                    function ($q) use ($dateFrom, $dateTo) {
                        $q->when($dateFrom, fn ($qq) => $qq->whereDate('payment_date', '>=', $dateFrom))
                          ->when($dateTo, fn ($qq) => $qq->whereDate('payment_date', '<=', $dateTo));
                    },
                    function ($q) use ($bulan) {
                        $q->when($bulan, function ($qq) use ($bulan) {
                            [$y, $m] = explode('-', $bulan);
                            $qq->whereYear('payment_date', (int) $y)->whereMonth('payment_date', (int) $m);
                        });
                    }
                )
                ->sum('amount_paid');

            $saldo = $totalPemasukan - $totalPengeluaran;
        }

        // Label periode untuk ditampilkan di atas kartu
        if ($dateFrom || $dateTo) {
            $periodLabel = ($dateFrom ? Carbon::parse($dateFrom)->translatedFormat('d M Y') : '...')
                . ' - '
                . ($dateTo ? Carbon::parse($dateTo)->translatedFormat('d M Y') : '...');
        } elseif ($bulan) {
            $periodLabel = Carbon::createFromFormat('!Y-m', $bulan)->translatedFormat('F Y');
        } else {
            $periodLabel = 'Semua periode';
        }

        // Urutan tabel: yang perlu ditindaklanjuti peran ini paling atas, sisanya mengikuti alur
        $priority = $isOwner
            ? ['Review Owner', 'Disetujui', 'Review Admin', 'Review Kurikulum', 'Diajukan', 'Dibayar', 'Selesai', 'Ditolak']
            : ['Review Admin', 'Review Kurikulum', 'Diajukan', 'Review Owner', 'Disetujui', 'Dibayar', 'Selesai', 'Ditolak'];

        $orderCase = 'CASE status';
        foreach ($priority as $i => $st) {
            $orderCase .= " WHEN '" . $st . "' THEN " . ($i + 1);
        }
        $orderCase .= ' ELSE 99 END';

        $submissions = $query->orderByRaw($orderCase)->latest('date')->latest('id')->paginate(12)->withQueryString();
        $teachers    = Teacher::where('status', 'Active')->orderBy('name')->get();

        return view('admin.pengajuan.index', compact(
            'submissions', 'teachers', 'totalCount', 'totalPengeluaran',
            'totalVerifikasi', 'totalApproved', 'periodLabel',
            'totalPemasukan', 'totalMenunggu', 'saldo',
            'status', 'type', 'teacherId', 'bulan', 'dateFrom', 'dateTo', 'isOwner'
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

    /** Approval khusus Owner jika pengajuan diteruskan ke Owner */
    public function ownerApprove(OwnerApproveSubmissionRequest $request, Submission $submission)
    {
        Gate::authorize('approveOwner', $submission);

        $owner = Auth::user();

        return DB::transaction(function () use ($request, $submission, $owner) {
            if ($request->decision === 'Approved') {
                $submission->update([
                    'status'               => 'Disetujui',
                    'owner_id'             => $owner->id,
                    'owner_note'           => $request->note,
                    'owner_approved_at'    => now(),
                    'owner_signature_path' => $owner->signature_path,
                ]);

                // Notifikasi Guru
                if ($submission->teacher?->user) {
                    $submission->teacher->user->notify(new AppNotification(
                        title: "Pengajuan Disetujui Owner",
                        message: "Pengajuan {$submission->item_name} sebesar {$submission->formatted_amount} telah disetujui penuh oleh Owner.",
                        url: url('/guru/pengajuan'),
                        type: 'pengajuan',
                        icon: 'fa-circle-check'
                    ));
                }

                // Notifikasi Kurikulum
                $kurikulums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
                foreach ($kurikulums as $kur) {
                    $kur->notify(new AppNotification(
                        title: "Pengajuan Disetujui Owner",
                        message: "Owner telah menyetujui pengajuan {$submission->item_name} ({$submission->type}) dari {$submission->teacher?->name}.",
                        url: url('/kurikulum/review-pengajuan?tab=pengajuan'),
                        type: 'pengajuan',
                        icon: 'fa-circle-check'
                    ));
                }

                // Notifikasi Admin
                $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
                foreach ($admins as $adm) {
                    $adm->notify(new AppNotification(
                        title: "Pengajuan Disetujui Owner",
                        message: "Owner telah menyetujui pengajuan {$submission->item_name} sebesar {$submission->formatted_amount}. Siap diproses pembayaran.",
                        url: url('/admin/pengajuan'),
                        type: 'pengajuan',
                        icon: 'fa-receipt'
                    ));
                }

                $msg = 'Pengajuan berhasil disetujui Owner' . ($owner->signature_path ? ' dan TTD Owner telah ditempel otomatis pada dokumen.' : ' (Catatan: Anda belum menyimpan TTD digital di Profil).');
                return back()->with('success', $msg);
            }

            $submission->update([
                'status'     => 'Ditolak',
                'owner_id'   => $owner->id,
                'owner_note' => $request->note ?? 'Ditolak oleh Owner',
            ]);

            // Notifikasi Guru
            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan Ditolak Owner",
                    message: "Pengajuan {$submission->item_name} ditolak oleh Owner. Catatan: " . ($request->note ?? '-'),
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-xmark'
                ));
            }

            // Notifikasi Kurikulum
            $kurikulums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
            foreach ($kurikulums as $kur) {
                $kur->notify(new AppNotification(
                    title: "Pengajuan Ditolak Owner",
                    message: "Pengajuan {$submission->item_name} dari {$submission->teacher?->name} ditolak Owner.",
                    url: url('/kurikulum/review-pengajuan?tab=pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-xmark'
                ));
            }

            // Notifikasi Admin
            $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
            foreach ($admins as $adm) {
                $adm->notify(new AppNotification(
                    title: "Pengajuan Ditolak Owner",
                    message: "Pengajuan {$submission->item_name} dari {$submission->teacher?->name} ditolak Owner.",
                    url: url('/admin/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-xmark'
                ));
            }

            return back()->with('success', 'Pengajuan telah ditolak oleh Owner.');
        });
    }

    /** Tandai pengajuan sebagai Dibayar oleh Owner */
    public function ownerMarkPaid(Request $request, Submission $submission)
    {
        Gate::authorize('markPaidOwner', $submission);

        return DB::transaction(function () use ($submission) {
            $submission->update([
                'status' => 'Dibayar',
            ]);

            // Notifikasi Guru
            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan Telah Dibayar",
                    message: "Pengajuan {$submission->item_name} sebesar {$submission->formatted_amount} telah ditandai Dibayar oleh Owner.",
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-money-bill-wave'
                ));
            }

            // Notifikasi Kurikulum
            $kurikulums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
            foreach ($kurikulums as $kur) {
                $kur->notify(new AppNotification(
                    title: "Pengajuan Telah Dibayar",
                    message: "Pengajuan {$submission->item_name} dari {$submission->teacher?->name} telah dibayarkan.",
                    url: url('/kurikulum/review-pengajuan?tab=pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-money-bill-wave'
                ));
            }

            // Notifikasi Admin
            $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
            foreach ($admins as $adm) {
                $adm->notify(new AppNotification(
                    title: "Pengajuan Telah Dibayar",
                    message: "Pengajuan {$submission->item_name} sebesar {$submission->formatted_amount} telah dibayarkan oleh Owner.",
                    url: url('/admin/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-money-bill-wave'
                ));
            }

            return back()->with('success', "Pengajuan {$submission->item_name} berhasil ditandai Dibayar.");
        });
    }

    /** Cetak / preview formulir lembar persetujuan pengajuan dengan TTD */
    public function printSubmission(Submission $submission)
    {
        Gate::authorize('view', $submission);

        return view('kurikulum.cetak-pengajuan', compact('submission'));
    }
}