<?php

namespace App\Policies;

use App\Models\TrainingMaterial;
use App\Models\User;

class TrainingMaterialPolicy
{
    /**
     * Boleh melihat daftar materi training: Owner, Admin, Curriculum, Teacher/Guru. Siswa DITOLAK.
     */
    public function viewAny(User $user): bool
    {
        $roleName = $user->level?->nama_level;

        return in_array($roleName, ['Owner', 'Admin', 'Curriculum', 'Kurikulum', 'Teacher', 'Guru'], true);
    }

    /**
     * Boleh melihat detail / file / video materi training:
     * - Owner: boleh semua
     * - Admin: hanya jika materi ditujukan ke Admin
     * - Curriculum: hanya jika materi ditujukan ke Curriculum
     * - Teacher: hanya jika materi ditujukan ke Teacher
     * - Lainnya / Siswa: Ditolak (false)
     */
    public function view(User $user, TrainingMaterial $material): bool
    {
        $roleName = $user->level?->nama_level;

        if ($roleName === 'Owner') {
            return true;
        }

        if (in_array($roleName, ['Teacher', 'Guru'], true)) {
            return $material->hasRole('Teacher');
        }

        if (in_array($roleName, ['Curriculum', 'Kurikulum'], true)) {
            return $material->hasRole('Curriculum');
        }

        if ($roleName === 'Admin') {
            return $material->hasRole('Admin');
        }

        return false;
    }

    /**
     * Hanya Owner yang boleh mengunggah materi training pengajar.
     */
    public function create(User $user): bool
    {
        return $user->level?->nama_level === 'Owner';
    }

    /**
     * Hanya Owner yang boleh memperbarui materi training.
     */
    public function update(User $user, TrainingMaterial $material): bool
    {
        return $user->level?->nama_level === 'Owner';
    }

    /**
     * Hanya Owner yang boleh menghapus materi training.
     */
    public function delete(User $user, TrainingMaterial $material): bool
    {
        return $user->level?->nama_level === 'Owner';
    }
}
