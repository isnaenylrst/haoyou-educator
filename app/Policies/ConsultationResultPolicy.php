<?php

namespace App\Policies;

use App\Models\ConsultationResult;
use App\Models\User;

class ConsultationResultPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum', 'Teacher', 'Guru']);
    }

    public function view(User $user, ConsultationResult $consultation): bool
    {
        if (in_array($user->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum'])) {
            return true;
        }

        return $user->teacher && $consultation->teacher_id === $user->teacher->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum']);
    }

    public function update(User $user, ConsultationResult $consultation): bool
    {
        return in_array($user->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum']);
    }

    public function delete(User $user, ConsultationResult $consultation): bool
    {
        return in_array($user->level?->nama_level, ['Owner', 'Curriculum', 'Kurikulum']);
    }
}

