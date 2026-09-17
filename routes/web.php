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
use App\Http\Controllers\Kurikulum\MonitoringController;
use App\Http\Controllers\Kurikulum\JadwalKonsultasiController;
use App\Http\Controllers\Kurikulum\ReviewPengajuanController;

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

Route::middleware(['auth', 'role:Student'])->group(function () {

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

Route::middleware(['auth', 'role:Curriculum'])
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
        Route::put('/sop/{document}', [SopController::class, 'update'])
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

    //Monitoring Guru
    Route::get('/monitoring', [MonitoringController::class, 'index'])
    ->name('monitoring');

// JADWAL KONSULTASI
Route::get('/jadwal-konsultasi', [JadwalKonsultasiController::class, 'index'])
    ->name('jadwal-konsultasi');

Route::post('/jadwal-konsultasi', [JadwalKonsultasiController::class, 'store'])
    ->name('jadwal-konsultasi.store');

Route::patch('/jadwal-konsultasi/{consultation}', [JadwalKonsultasiController::class, 'update'])
    ->name('jadwal-konsultasi.update');

Route::patch('/jadwal-konsultasi/{consultation}/complete', [JadwalKonsultasiController::class, 'complete'])
    ->name('jadwal-konsultasi.complete');

Route::patch('/jadwal-konsultasi/{consultation}/cancel', [JadwalKonsultasiController::class, 'cancel'])
    ->name('jadwal-konsultasi.cancel');

Route::delete('/jadwal-konsultasi/{consultation}', [JadwalKonsultasiController::class, 'destroy'])
    ->name('jadwal-konsultasi.destroy');

//REVIEW PENGAJUAN
/*
| REVIEW PENGAJUAN
*/

Route::get('/review-pengajuan', [ReviewPengajuanController::class, 'index'])
    ->name('review-pengajuan');

Route::patch('/review-pengajuan/sesi/{teachingJournal}/ack', [ReviewPengajuanController::class, 'ackSession'])
    ->name('review-pengajuan.ack-session');

Route::post('/review-pengajuan/sesi/{classSchedule}/reminder', [ReviewPengajuanController::class, 'sendReminder'])
    ->name('review-pengajuan.reminder');

Route::patch('/review-pengajuan/material/{teacherMaterial}', [ReviewPengajuanController::class, 'reviewMaterial'])
    ->name('review-pengajuan.material');

Route::patch('/review-pengajuan/jurnal/{teachingJournal}', [ReviewPengajuanController::class, 'reviewJournal'])
    ->name('review-pengajuan.journal');

Route::patch('/review-pengajuan/progress-report/{progressReport}', [ReviewPengajuanController::class, 'reviewReport'])
    ->name('review-pengajuan.report');

Route::patch('/review-pengajuan/cuti/{teacherLeave}', [ReviewPengajuanController::class, 'reviewLeave'])
    ->name('review-pengajuan.leave');

    
});

/*
|--------------------------------------------------------------------------
| GURU
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Teacher'])
    ->prefix('guru')
    ->name('teacher.')
    ->group(function () {

        // NOTE: sementara masih Route::view (data dummy di Blade).
        // Akan diganti ke Controller + data asli dari DB pada tahap berikutnya.

        Route::view('/dashboard', 'guru.dashboard')
            ->name('dashboard');

        Route::view('/notifikasi', 'guru.notifikasi')
            ->name('notifikasi');

        Route::view('/sop', 'guru.sop')
            ->name('sop');

        Route::view('/materi', 'guru.materi')
            ->name('materi');

        Route::view('/kelas', 'guru.kelas')
            ->name('kelas');

        Route::view('/attendance', 'guru.attendance')
            ->name('attendance');

        Route::view('/progress-report', 'guru.progress-report')
            ->name('progress-report');

        Route::view('/schedule', 'guru.schedule')
            ->name('schedule');

        Route::view('/teaching-log', 'guru.teaching-log')
            ->name('teaching-log');

        Route::view('/cuti', 'guru.cuti')
            ->name('cuti');
    });