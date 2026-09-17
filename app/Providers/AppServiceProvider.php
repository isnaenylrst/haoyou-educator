<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | FIX ALUR LOGIN:
        |--------------------------------------------------------------------------
        | Secara default, middleware 'guest' bawaan Laravel akan mengarahkan
        | SEMUA user yang sudah login (dari role apapun) ke route bernama
        | "dashboard" kalau dia coba buka /login lagi.
        |
        | Masalahnya: route "dashboard" di project ini KHUSUS untuk role
        | Student (dilindungi middleware role:Student). Jadi kalau user
        | Teacher/Curriculum yang sudah login membuka /login lagi, dia akan
        | ke-redirect ke /dashboard lalu langsung kena 403 Forbidden.
        |
        | Fix: override tujuan redirect itu supaya sesuai role user yang
        | sedang login, memakai method homeRouteName() di model User.
        |--------------------------------------------------------------------------
        */
        RedirectIfAuthenticated::redirectUsing(function ($request) {

            $user = $request->user();

            $routeName = $user?->homeRouteName();

            // Kalau role belum punya modul (Owner/Admin) atau tidak dikenali,
            // paksa logout supaya tidak nyangkut di redirect loop.
            if (!$routeName) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return route('login');
            }

            return route($routeName);
        });
    }
}