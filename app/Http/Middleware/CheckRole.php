<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Pastikan user yang login memiliki salah satu role yang diizinkan.
     * Contoh pemakaian di route: ->middleware('role:Curriculum')
     * Bisa lebih dari satu role: ->middleware('role:Owner,Admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user || !$user->level || !in_array($user->level->nama_level, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}