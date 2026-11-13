<?php

namespace App\Support;

/**
 * Aturan tampil cuti / guru pengganti di sisi ADMIN.
 *
 * Alur: guru mengajukan cuti + memilih pengganti -> guru pengganti ACC -> Kurikulum ACC.
 * Admin sudah boleh melihat guru pengganti begitu guru pengganti ACC, tidak menunggu
 * Kurikulum (supaya tidak terlewat bila Kurikulum lupa). Yang ditolak tidak tampil.
 *
 *  - status Approved                                       -> tampil (disetujui penuh)
 *  - status Pending + pengganti Accepted + Kurikulum belum menolak -> tampil ("menunggu ACC Kurikulum")
 */
class AdminLeaveVisibility
{
    /** Batasi query TeacherLeave ke cuti yang boleh tampil di admin. */
    public static function apply($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'Approved')
                ->orWhere(fn ($w) => $w->where('status', 'Pending')
                    ->whereNotNull('replacement_teacher_id')
                    ->where('replacement_response', 'Accepted')
                    ->where('curriculum_response', '!=', 'Rejected'));
        });
    }

    /** True bila guru pengganti sudah ACC tetapi Kurikulum belum menyetujui. */
    public static function awaitingCurriculum($leave): bool
    {
        return $leave->status !== 'Approved';
    }
}