<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;


/*
|--------------------------------------------------------------------------
| LANDING PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])
    ->name('landing');

Route::get('/konsultasi-gratis', [LandingController::class, 'konsultasiGratis'])
    ->name('konsultasi.gratis');


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
| Untuk pengembangan, halaman login tetap bisa dibuka.
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'index'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.process');


/*
|--------------------------------------------------------------------------
| PENDAFTARAN CALON SISWA
|--------------------------------------------------------------------------
| Bisa diakses tanpa login.
|--------------------------------------------------------------------------
*/

Route::get('/daftar', [PendaftaranController::class, 'create'])
    ->name('pendaftaran.create');

Route::post('/daftar', [PendaftaranController::class, 'store'])
    ->name('pendaftaran.store');

Route::get('/daftar/sukses', [PendaftaranController::class, 'sukses'])
    ->name('pendaftaran.sukses');


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
| Untuk pengembangan tidak perlu auth middleware.
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');


/*
|--------------------------------------------------------------------------
| DASHBOARD SISWA
|--------------------------------------------------------------------------
| TANPA LOGIN SEMENTARA UNTUK PENGEMBANGAN
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [SiswaDashboardController::class, 'dashboard'])
    ->name('dashboard');

Route::get('/program', [SiswaDashboardController::class, 'program'])
    ->name('program.index');

Route::get('/booking', [SiswaDashboardController::class, 'booking'])
    ->name('booking.index');

Route::get('/kelas-saya', [SiswaDashboardController::class, 'kelasSaya'])
    ->name('kelassaya.index');

Route::get('/profil', [SiswaDashboardController::class, 'profil'])
    ->name('profil.index');

Route::get('/notifikasi', [SiswaDashboardController::class, 'notifikasi'])
    ->name('notifikasi.index');

Route::get('/sertifikat', [SiswaDashboardController::class, 'sertifikat'])
    ->name('sertifikat.index');

Route::get('/progress-report', [SiswaDashboardController::class, 'progresReport'])
    ->name('progresreport.index');