<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionRekapController extends Controller
{
    public function index(Request $request)
    {
        $status    = $request->query('status');
        $type      = $request->query('type');
        $teacherId = $request->query('teacher_id');

        $query = Submission::with(['teacher', 'curriculum', 'admin', 'owner'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId));

        $totalCount    = (clone $query)->count();
        $totalNominal  = (clone $query)->whereIn('status', ['Disetujui', 'Selesai'])->sum('amount');
        $totalPending  = (clone $query)->whereIn('status', ['Diajukan', 'Review Kurikulum', 'Review Admin', 'Review Owner'])->count();
        $totalApproved = (clone $query)->whereIn('status', ['Disetujui', 'Selesai'])->count();

        $submissions = $query->latest('date')->paginate(12)->withQueryString();
        $teachers    = Teacher::where('status', 'Active')->orderBy('name')->get();

        $isOwner = (Auth::user()->level?->nama_level === 'Owner');
        $layout  = $isOwner ? 'layouts.owner' : 'admin.app';

        return view('admin.pengajuan.index', compact(
            'submissions', 'teachers', 'totalCount', 'totalNominal',
            'totalPending', 'totalApproved', 'status', 'type', 'teacherId', 'isOwner', 'layout'
        ));
    }

    /** Verifikasi berkas reimburse oleh Admin sebelum diteruskan ke Owner */
    public function adminVerify(Request $request, Submission $submission)
    {
        abort_unless(in_array(Auth::user()->level?->nama_level, ['Admin', 'Owner']), 403);

        $request->validate([
            'decision' => ['required', 'in:Approved,Rejected'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = Auth::user();

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
    }

    /** Approval khusus Owner jika pengajuan diteruskan ke Owner */
    public function ownerApprove(Request $request, Submission $submission)
    {
        abort_unless(Auth::user()->level?->nama_level === 'Owner', 403);

        $request->validate([
            'decision' => ['required', 'in:Approved,Rejected'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $owner = Auth::user();

        if ($request->decision === 'Approved') {
            $submission->update([
                'status'               => 'Disetujui',
                'owner_id'             => $owner->id,
                'owner_note'           => $request->note,
                'owner_approved_at'    => now(),
                'owner_signature_path' => $owner->signature_path,
            ]);

            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan Disetujui Owner",
                    message: "Pengajuan {$submission->item_name} sebesar {$submission->formatted_amount} telah disetujui penuh oleh Owner.",
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-check'
                ));
            }

            return back()->with('success', 'Pengajuan berhasil disetujui Owner dan TTD Owner telah ditempel pada dokumen.');
        }

        $submission->update([
            'status'     => 'Ditolak',
            'owner_id'   => $owner->id,
            'owner_note' => $request->note ?? 'Ditolak oleh Owner',
        ]);

        if ($submission->teacher?->user) {
            $submission->teacher->user->notify(new AppNotification(
                title: "Pengajuan Ditolak Owner",
                message: "Pengajuan {$submission->item_name} ditolak oleh Owner. Catatan: " . ($request->note ?? '-'),
                url: url('/guru/pengajuan'),
                type: 'pengajuan',
                icon: 'fa-circle-xmark'
            ));
        }

        return back()->with('success', 'Pengajuan telah ditolak oleh Owner.');
    }
}

