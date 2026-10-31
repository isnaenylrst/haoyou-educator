<?php

namespace App\Providers;

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

        // Share unread notifications to all views
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $authUser = Auth::user();
                $unreadNotifications = $authUser->unreadNotifications()->take(10)->get();
                $unreadCount = $authUser->unreadNotifications()->count();
                $view->with('unreadNotifications', $unreadNotifications);
                $view->with('unreadNotificationsCount', $unreadCount);
            }
        });

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
    }
}