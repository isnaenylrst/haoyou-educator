<?php

namespace App\Support;

use App\Models\ClassEnrollment;

/** Level (HSK 1/2/3...) milik siswa yang sedang login. Dipakai Belajar Vocab dan Belajar Grammar. */
class StudentLevel
{
    public static function id(): ?int
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return null;
        }

        if ($student->current_level_id) {
            return (int) $student->current_level_id;
        }

        $enrollments = ClassEnrollment::with(['class.programPackage', 'programPackage'])
            ->where('student_id', $student->id)->where('status', 'Active')
            ->latest('id')->get();

        foreach ($enrollments as $e) {
            $lvl = $e->class?->level_id ?? $e->class?->programPackage?->level_id ?? $e->programPackage?->level_id;
            if ($lvl) {
                return (int) $lvl;
            }
        }

        return null;
    }
}