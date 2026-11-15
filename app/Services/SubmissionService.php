<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    /**
     * Setujui atau tolak pengajuan oleh Owner.
     */
    public function approveByOwner(User $owner, Submission $submission, string $decision, ?string $note = null): array
    {
        return DB::transaction(function () use ($owner, $submission, $decision, $note) {
            if ($decision === 'Approved') {
                $submission->update([
                    'status'               => 'Disetujui',
                    'owner_id'             => $owner->id,
                    'owner_note'           => $note,
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
                return ['success' => true, 'message' => $msg];
            }

            $submission->update([
                'status'     => 'Ditolak',
                'owner_id'   => $owner->id,
                'owner_note' => $note ?? 'Ditolak oleh Owner',
            ]);

            // Notifikasi Guru
            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan Ditolak Owner",
                    message: "Pengajuan {$submission->item_name} ditolak oleh Owner. Catatan: " . ($note ?? '-'),
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

            return ['success' => true, 'message' => 'Pengajuan telah ditolak oleh Owner.'];
        });
    }

    /**
     * Tandai pengajuan sebagai Dibayar oleh Owner.
     */
    public function markPaidByOwner(User $owner, Submission $submission): array
    {
        return DB::transaction(function () use ($owner, $submission) {
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

            return ['success' => true, 'message' => "Pengajuan {$submission->item_name} berhasil ditandai Dibayar."];
        });
    }
}

