<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Area yang boleh diakses penuh oleh Owner.
     * (Student sengaja tidak ikut: halaman siswa terikat ke akun siswa itu sendiri.)
     */
    private const OWNER_AREAS = ['Admin', 'Teacher', 'Curriculum'];

    /**
     * Pastikan user yang login memiliki salah satu role yang diizinkan.
     * Contoh pemakaian di route: ->middleware('role:Curriculum')
     * Bisa lebih dari satu role: ->middleware('role:Owner,Admin')
     *
     * Owner otomatis lolos untuk area Admin, Teacher (Guru), dan Curriculum (Kurikulum).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user || !$user->level) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $level = $user->level->nama_level;

        if ($level === 'Owner' && array_intersect($roles, self::OWNER_AREAS)) {
            return $next($request);
        }

        if (!in_array($level, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}