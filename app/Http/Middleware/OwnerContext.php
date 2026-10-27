<?php

namespace App\Http\Middleware;

use App\Models\Teacher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Konteks khusus Owner.
 *
 * Halaman Guru bekerja untuk seorang guru ( Auth::user()->teacher ).
 * Owner tidak punya data guru, jadi Owner memilih guru yang ingin dilihat
 * (disimpan di session "owner_view_teacher_id"). Middleware ini memasang guru
 * pilihan itu sebagai relasi "teacher" milik user yang sedang login, sehingga
 * seluruh controller dan view Guru berjalan tanpa perlu diubah.
 */
class OwnerContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || ($user->level?->nama_level) !== 'Owner') {
            return $next($request);
        }

        $teacherId = $request->session()->get('owner_view_teacher_id');
        $teacher   = $teacherId ? Teacher::find($teacherId) : null;

        if ($teacher) {
            $user->setRelation('teacher', $teacher);
        } elseif ($teacherId) {
            $request->session()->forget('owner_view_teacher_id'); // guru sudah dihapus
        }

        // Semua halaman /guru/* butuh seorang guru: arahkan Owner memilih dulu
        if (!$teacher && $request->is('guru', 'guru/*')) {
            return redirect()
                ->route('owner.guru')
                ->with('error', 'Pilih guru terlebih dahulu untuk membuka area Guru.');
        }

        return $next($request);
    }
}