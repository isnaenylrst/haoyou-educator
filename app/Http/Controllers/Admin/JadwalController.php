<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    /**
     * Menampilkan halaman dashboard Admin.
     */
    public function index()
    {
        return view('admin.jadwal');
    }
}