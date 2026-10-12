<?php

namespace App\Http\Controllers\Admin\Pengaturan;

use App\Http\Controllers\Controller;

class PengaturanController extends Controller
{
    public function index()
    {
        return view('admin.pengaturan.index');
    }
}