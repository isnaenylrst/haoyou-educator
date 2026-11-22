<?php

namespace App\Support;

use App\Models\Program;
use App\Models\ProgramLevel;

class SelfStudyAccess
{
    private static array $memo = [];

    /** Level HSK (1-3) siswa yang login, atau null kalau tidak berhak. */
    public static function hsk(): ?int
    {
        $uid = auth()->id();
        if (! $uid) {
            return null;
        }

        return self::$memo[$uid] ??= self::resolve();
    }

    public static function allowed(): bool
    {
        return self::hsk() !== null;
    }

    private static function resolve(): ?int
    {
        $levelId = StudentLevel::id();
        $pl = $levelId ? ProgramLevel::find($levelId) : null;
        if (! $pl) {
            return null;
        }

        $programName = $pl->program_id ? Program::find($pl->program_id)?->program_name : '';
        $text = trim(($programName ?? '') . ' ' . ($pl->level_name ?? ''));

        // "HSK 2", "HSK2", "HSK Level 2" cocok; HSK 4 ke atas dan program lain tidak
        return preg_match('/HSK\D{0,10}([1-3])(?!\d)/i', $text, $m) ? (int) $m[1] : null;
    }
}