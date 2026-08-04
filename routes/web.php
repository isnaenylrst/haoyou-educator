<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CalonSiswaController;


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


Route::middleware(['auth']) ->prefix('admin') ->name('admin.') ->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/calon-siswa', [CalonSiswaController::class, 'index'])
        ->name('calon-siswa');
    Route::post('/calon-siswa', [CalonSiswaController::class, 'store'])
        ->name('calon-siswa.store');

    // Route spesifik (/edit) HARUS didaftarkan sebelum route wildcard generik,
    // supaya tidak ketimpa kalau nanti wildcard-nya diubah jadi lebih longgar.
    Route::get('/calon-siswa/{candidateStudent}/edit', [CalonSiswaController::class, 'edit'])
        ->name('calon-siswa.edit');
    Route::put('/calon-siswa/{candidateStudent}', [CalonSiswaController::class, 'update'])
        ->name('calon-siswa.update');

    Route::delete('/calon-siswa/{candidateStudent}', [CalonSiswaController::class, 'destroy'])
        ->name('calon-siswa.destroy');
});