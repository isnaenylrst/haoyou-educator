<?php

namespace App\View\Composers;

use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Menyuplai $adminNotif (total, counts, top) ke layout admin.app untuk dropdown bell.
 * Di-cache 60 detik supaya semua query notifikasi tidak jalan di setiap halaman.
 */
class AdminNotificationComposer
{
    public function compose(View $view): void
    {
        $summary = ['total' => 0, 'counts' => [], 'top' => []];

        if (auth()->check()) {
            $summary = Cache::remember(
                AdminNotificationService::CACHE_KEY,
                60,
                fn () => rescue(fn () => app(AdminNotificationService::class)->summary(), $summary)
            );
        }

        $view->with('adminNotif', $summary);
    }
}