<?php

namespace App\Http\Middleware;

use App\Support\SelfStudyAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureSelfStudyAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            SelfStudyAccess::allowed(),
            403,
            'Belajar mandiri hanya untuk siswa program HSK 1, 2, dan 3.'
        );

        return $next($request);
    }
}