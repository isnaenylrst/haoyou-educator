<?php

namespace App\Services;

use App\Models\ClassModel;
use Illuminate\Validation\ValidationException;

class ClassCapacityService
{
    /**
     * Jumlah siswa yang benar-benar "menempati slot" kelas ini —
     * Active dan Completed dihitung terisi, Cancelled/Waiting Class tidak.
     */
    public function activeEnrollmentCount(ClassModel $class): int
    {
        return $class->enrollments()
            ->whereIn('status', ['Active', 'Completed'])
            ->count();
    }

    public function remainingSlots(ClassModel $class): int
    {
        return max($class->capacity - $this->activeEnrollmentCount($class), 0);
    }

    public function hasAvailableSlot(ClassModel $class): bool
    {
        return $this->remainingSlots($class) > 0;
    }

    /**
     * Lempar ValidationException kalau kelas sudah penuh — dipanggil di
     * controller sebelum benar-benar membuat/meng-assign class_enrollments.
     */
    public function assertHasAvailableSlot(ClassModel $class): void
    {
        if (! $this->hasAvailableSlot($class)) {
            throw ValidationException::withMessages([
                'class_id' => "Kelas \"{$class->class_name}\" sudah penuh ({$class->capacity} siswa).",
            ]);
        }
    }
}