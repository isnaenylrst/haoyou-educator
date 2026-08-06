<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Kurikulum\DashboardController;
use App\Http\Controllers\Kurikulum\SopController;
use App\Http\Controllers\Kurikulum\DocumentTemplateController;
use App\Http\Controllers\Kurikulum\MaterialController;
use App\Http\Controllers\Kurikulum\LetterController;

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

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});

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

Route::middleware('auth')->group(function () {

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
});

/*
|--------------------------------------------------------------------------
| KURIKULUM
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('kurikulum')
    ->name('kurikulum.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | SOP
        |--------------------------------------------------------------------------
        */

        // Halaman SOP
        Route::get('/sop', [SopController::class, 'index'])
            ->name('sop');

        // Upload SOP Baru
        Route::post('/sop', [SopController::class, 'store'])
            ->name('sop.store');

        // Lihat SOP di Browser
        Route::get('/sop/{document}', [SopController::class, 'show'])
            ->name('sop.show');

        // Upload Ulang SOP
        Route::patch('/sop/{document}', [SopController::class, 'update'])
            ->name('sop.update');

        // Download SOP
        Route::get('/sop/{document}/download', [SopController::class, 'download'])
            ->name('sop.download');

        // Hapus SOP
        Route::delete('/sop/{document}', [SopController::class, 'destroy'])
            ->name('sop.destroy');
    
        /*
        |--------------------------------------------------------------------------
        | TEMPLATE PROGRESS REPORT
        |--------------------------------------------------------------------------
        */

        // Upload Template Progress Report
        Route::post(
            '/template/upload',
            [DocumentTemplateController::class, 'store']
            )->name('template.upload');

        // Update Template
        Route::put(
            '/template/{documentTemplate}',
            [DocumentTemplateController::class, 'update']
            )->name('template.update');

        // Download Template
        Route::get(
            '/template/{documentTemplate}/download',
            [DocumentTemplateController::class, 'download']
            )->name('template.download');

        // Hapus Template
        Route::delete(
            '/template/{documentTemplate}',
            [DocumentTemplateController::class, 'destroy']
        )->name('template.destroy');
    
/*
|--------------------------------------------------------------------------
| MATERIAL
|--------------------------------------------------------------------------
*/

Route::get('/materi', [MaterialController::class, 'index'])
    ->name('materi');

Route::post('/materi', [MaterialController::class, 'store'])
    ->name('materi.store');

Route::put('/materi/{material}', [MaterialController::class, 'update'])
    ->name('materi.update');

Route::delete('/materi/{material}', [MaterialController::class, 'destroy'])
    ->name('materi.destroy');


    //Pemberitahuan Surat
    Route::get('/surat', [LetterController::class, 'index'])
    ->name('surat');

    Route::post('/surat', [LetterController::class, 'store'])
    ->name('surat.store');

    Route::delete('/surat/{document}', [LetterController::class, 'destroy'])
    ->name('surat.destroy');
/*
|--------------------------------------------------------------------------
| HALAMAN KURIKULUM LAINNYA
|--------------------------------------------------------------------------
*/


    Route::view('/jadwal-konsultasi', 'kurikulum.jadwal-konsultasi')
    ->name('jadwal-konsultasi');

    Route::view('/review-pengajuan', 'kurikulum.review-pengajuan')
    ->name('review-pengajuan');

    Route::view('/monitoring', 'kurikulum.monitoring')
    ->name('monitoring');

    
});

/*
|--------------------------------------------------------------------------
| GURU
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

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
});