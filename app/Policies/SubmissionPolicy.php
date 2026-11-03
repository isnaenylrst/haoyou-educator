<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Boleh melihat daftar pengajuan: Owner, Admin, Curriculum, Teacher.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->level?->nama_level, ['Owner', 'Admin', 'Curriculum', 'Kurikulum', 'Teacher', 'Guru']);
    }

    /**
     * Boleh melihat detail / dokumen pengajuan jika pengaju atau berwenang mereview.
     */
    public function view(User $user, Submission $submission): bool
    {
        $role = $user->level?->nama_level;

        if (in_array($role, ['Owner', 'Admin', 'Curriculum', 'Kurikulum'])) {
            return true;
        }

        // Jika Guru, hanya pengajuan miliknya
        if (in_array($role, ['Teacher', 'Guru'])) {
            return $user->teacher && $submission->teacher_id === $user->teacher->id;
        }

        return false;
    }

    /**
     * Guru boleh membuat pengajuan.
     */
    public function create(User $user): bool
    {
        return in_array($user->level?->nama_level, ['Teacher', 'Guru']) && $user->teacher !== null;
    }

    /**
     * Kurikulum boleh mereview pengajuan.
     */
    public function reviewCurriculum(User $user, Submission $submission): bool
    {
        return in_array($user->level?->nama_level, ['Curriculum', 'Kurikulum', 'Owner']);
    }

    /**
     * Admin boleh memverifikasi berkas pengajuan.
     */
    public function verifyAdmin(User $user, Submission $submission): bool
    {
        return in_array($user->level?->nama_level, ['Admin', 'Owner']);
    }

    /**
     * Owner berhak menyetujui tahap akhir pengajuan jika sudah di-ACC Kurikulum.
     */
    public function approveOwner(User $user, Submission $submission): bool
    {
        if ($user->level?->nama_level !== 'Owner') {
            return false;
        }

        return !empty($submission->curriculum_approved_at) || in_array($submission->status, ['Review Owner', 'Review Admin']);
    }

    /**
     * Owner berhak menandai pengajuan sebagai Dibayar.
     */
    public function markPaidOwner(User $user, Submission $submission): bool
    {
        return $user->level?->nama_level === 'Owner' && in_array($submission->status, ['Disetujui', 'Review Owner']);
    }
}

