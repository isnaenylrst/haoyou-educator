<?php

namespace App\Policies;

use App\Models\TrainingMaterial;
use App\Models\User;

class TrainingMaterialPolicy
{
    /**
     * Boleh melihat daftar materi training: Owner, Curriculum, Teacher/Guru.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum', 'Teacher', 'Guru']);
    }

    /**
     * Boleh melihat detail materi training.
     */
    public function view(User $user, TrainingMaterial $material): bool
    {
        return $this->viewAny($user);
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

