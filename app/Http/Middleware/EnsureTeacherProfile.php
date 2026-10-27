<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->teacher) {
            abort(403, 'Akun Anda belum terhubung dengan data guru. Hubungi Kepala Kurikulum.');
        }

        return $next($request);
    }
}