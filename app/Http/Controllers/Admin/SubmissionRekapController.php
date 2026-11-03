<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerApproveSubmissionRequest;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmissionRekapController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Submission::class);

        $status    = $request->query('status');
        $type      = $request->query('type');
        $teacherId = $request->query('teacher_id');

        $isOwner = (Auth::user()->level?->nama_level === 'Owner');
        $layout  = $isOwner ? 'layouts.owner' : 'admin.app';

        $query = Submission::with(['teacher', 'curriculum', 'admin', 'owner'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId));

        if ($isOwner) {
            // Hanya yang sudah ACC Kurikulum yang muncul untuk Owner
            $query->where(function ($q) {
                $q->whereNotNull('curriculum_approved_at')
                  ->orWhereIn('status', ['Review Owner', 'Disetujui', 'Dibayar', 'Selesai']);
            });
        }

        $totalCount       = (clone $query)->count();
        $totalNominal     = (clone $query)->whereIn('status', ['Disetujui', 'Dibayar', 'Selesai'])->sum('amount');
        $totalPending     = (clone $query)->whereIn('status', ['Diajukan', 'Review Kurikulum', 'Review Admin', 'Review Owner'])->count();
        $totalApproved    = (clone $query)->whereIn('status', ['Disetujui', 'Dibayar', 'Selesai'])->count();

        // Kartu Keuangan: Total Pemasukan, Total Pengeluaran, dan Saldo
        $totalPemasukan   = (float) \App\Models\Payment::whereIn('status', ['Paid', 'Partial'])->sum('amount_paid');
        $totalPengeluaran = (float) $totalNominal;
        $saldo            = $totalPemasukan - $totalPengeluaran;

        $submissions = $query->latest('date')->paginate(12)->withQueryString();
        $teachers    = Teacher::where('status', 'Active')->orderBy('name')->get();

        return view('admin.pengajuan.index', compact(
            'submissions', 'teachers', 'totalCount', 'totalNominal',
            'totalPending', 'totalApproved', 'totalPemasukan', 'totalPengeluaran', 'saldo',
            'status', 'type', 'teacherId', 'isOwner', 'layout'
        ));
    }

    /** Verifikasi berkas reimburse oleh Admin sebelum diteruskan ke Owner */
    public function adminVerify(Request $request, Submission $submission)
    {
        Gate::authorize('verifyAdmin', $submission);

        $request->validate([
            'decision' => ['required', 'in:Approved,Rejected'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = Auth::user();

        return DB::transaction(function () use ($request, $submission, $admin) {
            if ($request->decision === 'Approved') {
                $submission->update([
                    'status'            => 'Review Owner',
                    'admin_id'          => $admin->id,
                    'admin_note'        => $request->note,
                    'admin_verified_at' => now(),
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
