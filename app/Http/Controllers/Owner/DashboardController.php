<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Versi ringkas: menampilkan semua fitur per role.
     * (Versi lengkap dengan grafik keuangan ada di dokumen PASANG-OWNER.md, tahap 2.)
     */
    public function index()
    {
        return view('owner.dashboard');
    }
}