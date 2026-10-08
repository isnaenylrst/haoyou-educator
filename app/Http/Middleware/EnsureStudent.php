<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya user dengan level "Student" (levels.nama_level) yang boleh
 * membuka halaman siswa.
 */
class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->level?->nama_level !== 'Student') {
            abort(403, 'Halaman ini khusus siswa.');
        }

        return $next($request);
    }
}
