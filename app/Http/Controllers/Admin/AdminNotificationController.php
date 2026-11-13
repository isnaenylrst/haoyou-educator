<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminNotificationController extends Controller
{
    public function index(Request $request, AdminNotificationService $service)
    {
        $tab = $request->query('tab', 'all');

        abort_unless($tab === 'all' || isset(AdminNotificationService::SECTIONS[$tab]), 404);

        $sort = in_array($request->query('sort'), ['priority', 'newest', 'oldest'], true)
            ? $request->query('sort')
            : 'priority';

        $from = rescue(fn () => $request->date('from'), null, false);
        $to   = rescue(fn () => $request->date('to'), null, false);

        // Dihitung sekali; tab dan filter hanya menyaring hasilnya.
        $all    = $service->all();
        $counts = $all->countBy('section');

        // Isi dropdown bell ikut segar setiap halaman ini dibuka.
        Cache::put(AdminNotificationService::CACHE_KEY, $service->summary($all), 60);

        $items = $tab === 'all' ? $all : $all->where('section', $tab);

        if ($from) {
            $items = $items->filter(fn ($n) => $n['date']->gte($from->copy()->startOfDay()));
        }
        if ($to) {
            $items = $items->filter(fn ($n) => $n['date']->lte($to->copy()->endOfDay()));
        }

        $items = match ($sort) {
            'newest' => $items->sortByDesc('date'),
            'oldest' => $items->sortBy('date'),
            default  => $items,                      // sudah urut prioritas dari service
        };

        return view('admin.notifikasi.index', [
            'tab'      => $tab,
            'sort'     => $sort,
            'from'     => $from?->format('Y-m-d'),
            'to'       => $to?->format('Y-m-d'),
            'sections' => AdminNotificationService::SECTIONS,
            'total'    => $all->count(),
            'counts'   => $counts,
            'urgent'   => $all->where('priority', 3)->countBy('section'),
            'items'    => $items->values(),
        ]);
    }
}