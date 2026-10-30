<?php

namespace App\Providers;

use App\Models\ClassEnrollment;
use App\Models\Student;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        Paginator::useBootstrapFive();

        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $user = $request->user();

            $routeName = $user?->homeRouteName();

            // Role belum punya modul (Owner/Admin) atau tidak dikenali:
            // paksa logout supaya tidak nyangkut di redirect loop.
            if (!$routeName) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return route('login');
            }

            return route($routeName);
        });

        // Level kelas aktif terbaru untuk dropdown profil di layout dashboard
        View::composer('layouts.dashboard', function ($view) {
            $levelAktif = null;

            if (Auth::check()) {
                $student = Student::where('user_id', Auth::id())->first();

                if ($student) {
                    $kelas = ClassEnrollment::with('class')
                        ->where('student_id', $student->id)
                        ->where('status', 'Active')
                        ->whereNotNull('class_id')
                        ->orderByDesc('id')
                        ->first()?->class;

                    if ($kelas) {
                        $nama = $kelas->class_name ?? '';

                        // "HSK Class - HSK 3 - Kelas A" -> "HSK 3"
                        if (preg_match('/HSK\s*\d+/i', $nama, $m)) {
                            $levelAktif = strtoupper(preg_replace('/\s+/', ' ', $m[0]));
                        } else {
                            $levelAktif = $nama ?: null;
                        }
                    }
                }
            }

            $view->with('levelAktif', $levelAktif);
        });
    }
}