<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Kurikulum\DashboardController;
use App\Http\Controllers\Kurikulum\SopController;

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
*/

Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'index'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');

});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| PENDAFTARAN CALON SISWA
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
| DASHBOARD SISWA
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

/*
|--------------------------------------------------------------------------
| KURIKULUM
|--------------------------------------------------------------------------
*/

Route::prefix('kurikulum')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('kurikulum.dashboard');

    Route::get('/sop', [SopController::class, 'index'])
        ->name('kurikulum.sop');

    Route::post('/sop', [SopController::class, 'store'])
        ->name('kurikulum.sop.store');

    Route::put('/sop/{document}', [SopController::class, 'update'])
        ->name('kurikulum.sop.update');

    Route::delete('/sop/{document}', [SopController::class, 'destroy'])
        ->name('kurikulum.sop.destroy');

    Route::get('/sop/{document}/download', [SopController::class, 'download'])
        ->name('kurikulum.sop.download');

    Route::get('/sop/{document}/edit', [SopController::class, 'edit'])
        ->name('kurikulum.sop.edit');

});

Route::view('/kurikulum/materi', 'kurikulum.materi');
Route::view('/kurikulum/jadwal-konsultasi', 'kurikulum.jadwal-konsultasi');
Route::view('/kurikulum/review-pengajuan', 'kurikulum.review-pengajuan');
Route::view('/kurikulum/monitoring', 'kurikulum.monitoring');
Route::view('/kurikulum/surat', 'kurikulum.surat');

/*
|--------------------------------------------------------------------------
| GURU
|--------------------------------------------------------------------------
*/

Route::view('/guru/dashboard', 'guru.dashboard');
Route::view('/guru/notifikasi', 'guru.notifikasi');
Route::view('/guru/sop', 'guru.sop');
Route::view('/guru/materi', 'guru.materi');
Route::view('/guru/kelas', 'guru.kelas');
Route::view('/guru/attendance', 'guru.attendance');
Route::view('/guru/progress-report', 'guru.progress-report');
Route::view('/guru/schedule', 'guru.schedule');
Route::view('/guru/teaching-log', 'guru.teaching-log');
Route::view('/guru/cuti', 'guru.cuti');