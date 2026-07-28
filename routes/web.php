<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PendaftaranController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BAGIAN 0: LANDING PAGE (PUBLIK, BELUM LOGIN)
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/konsultasi-gratis', [LandingController::class, 'konsultasiGratis'])->name('konsultasi.gratis');

/*
|--------------------------------------------------------------------------
| BAGIAN 1: LOGIN & PENDAFTARAN (CALON SISWA / SISWA SUDAH DIBERI AKSES)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Jalur: Daftar Sekarang (calon siswa baru -> tabel candidate_students)
    Route::get('/daftar', [PendaftaranController::class, 'create'])->name('pendaftaran.create');
    Route::post('/daftar', [PendaftaranController::class, 'store'])->name('pendaftaran.store');
    Route::get('/daftar/sukses', [PendaftaranController::class, 'sukses'])->name('pendaftaran.sukses');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Catatan: route Dashboard & modul setelah login (Booking, Kelas Saya,
| Sertifikat, dst) SENGAJA belum dibuat di sini — menyusul setelah
| Landing Page & Login/Pendaftaran ini selesai direview.
|--------------------------------------------------------------------------
*/