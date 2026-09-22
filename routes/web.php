<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;

// ===== KURIKULUM (punya Anda) =====
use App\Http\Controllers\Kurikulum\DashboardController;
use App\Http\Controllers\Kurikulum\SopController;
use App\Http\Controllers\Kurikulum\DocumentTemplateController;
use App\Http\Controllers\Kurikulum\MaterialController;
use App\Http\Controllers\Kurikulum\LetterController;
use App\Http\Controllers\Kurikulum\MonitoringController;
use App\Http\Controllers\Kurikulum\JadwalKonsultasiController;
use App\Http\Controllers\Kurikulum\ReviewPengajuanController;

// ===== ADMIN (punya teman) =====
// PENTING: dikasih alias "Admin..." di depan supaya TIDAK BENTROK
// dengan DashboardController milik Kurikulum di atas.
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\ConvertController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Admin\ProgramLevelController;

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

        Route::post(
            '/template/upload',
            [DocumentTemplateController::class, 'store']
            )->name('template.upload');

        Route::put(
            '/template/{documentTemplate}',
            [DocumentTemplateController::class, 'update']
            )->name('template.update');

        Route::get(
            '/template/{documentTemplate}/download',
            [DocumentTemplateController::class, 'download']
            )->name('template.download');

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

/*
|--------------------------------------------------------------------------
| ADMIN (punya teman)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // DASHBOARD -- pakai alias AdminDashboardController!
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('dashboard');

    //CALON SISWA
    Route::get('/calon-siswa', [CalonSiswaController::class, 'index'])
        ->name('calon-siswa');
    Route::post('/calon-siswa', [CalonSiswaController::class, 'store'])
        ->name('calon-siswa.store');
    Route::get('/calon-siswa/{candidateStudent}/edit', [CalonSiswaController::class, 'edit'])
        ->name('calon-siswa.edit');
    Route::put('/calon-siswa/{candidateStudent}', [CalonSiswaController::class, 'update'])
        ->name('calon-siswa.update');
    Route::delete('/calon-siswa/{candidateStudent}', [CalonSiswaController::class, 'destroy'])
        ->name('calon-siswa.destroy');

    //SISWA
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa');
    Route::get('/siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
    Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::get('/siswa/{siswa}', [SiswaController::class, 'show'])->name('siswa.show');
    Route::get('/siswa/{siswa}/continue', [SiswaController::class, 'continueProgramForm'])->name('siswa.continue.form');
    Route::post('/siswa/{siswa}/continue', [SiswaController::class, 'continueProgram'])->name('siswa.continue');

    //CONVERT CALON SISWA MENJADI SISWA
    Route::get('calon-siswa/{candidateStudent}/convert', [ConvertController::class, 'create'])
        ->name('calon-siswa.convert');
    Route::post('calon-siswa/{candidateStudent}/convert', [ConvertController::class, 'store'])
        ->name('calon-siswa.convert.store');

    Route::patch('enrollments/{enrollment}/assign-class', [ConvertController::class, 'assignClass'])
        ->name('enrollments.assign-class');

    Route::get('enrollments/{enrollment}/waiting-class-options', [SiswaController::class, 'waitingClassOptions'])
        ->name('enrollments.waiting-class-options');

    Route::get('/jadwal', [JadwalController::class, 'index'])
        ->name('jadwal');

    // pakai alias AdminKelasController!
    Route::get('/kelas', [AdminKelasController::class, 'index'])
        ->name('kelas');

    //PROGRAM & LEVEL
    Route::get('/program-level', [ProgramLevelController::class, 'index'])
        ->name('program-level');

    Route::get('/program-level/struktur', [ProgramLevelController::class, 'struktur'])
        ->name('program-level.struktur');

    Route::post('/program-level/program', [ProgramLevelController::class, 'storeProgram'])
        ->name('program-level.program.store');
    Route::get('/program-level/program/{program}/edit', [ProgramLevelController::class, 'editProgram'])
        ->name('program-level.program.edit');
    Route::put('/program-level/program/{program}', [ProgramLevelController::class, 'updateProgram'])
        ->name('program-level.program.update');
    Route::delete('/program-level/program/{program}', [ProgramLevelController::class, 'destroyProgram'])
        ->name('program-level.program.destroy');

    Route::post('/program-level/category', [ProgramLevelController::class, 'storeCategory'])
        ->name('program-level.category.store');
    Route::get('/program-level/category/{category}/edit', [ProgramLevelController::class, 'editCategory'])
        ->name('program-level.category.edit');
    Route::put('/program-level/category/{category}', [ProgramLevelController::class, 'updateCategory'])
        ->name('program-level.category.update');
    Route::delete('/program-level/category/{category}', [ProgramLevelController::class, 'destroyCategory'])
        ->name('program-level.category.destroy');

    Route::post('/program-level/level', [ProgramLevelController::class, 'storeLevel'])
        ->name('program-level.level.store');
    Route::get('/program-level/level/{level}/edit', [ProgramLevelController::class, 'editLevel'])
        ->name('program-level.level.edit');
    Route::put('/program-level/level/{level}', [ProgramLevelController::class, 'updateLevel'])
        ->name('program-level.level.update');
    Route::delete('/program-level/level/{level}', [ProgramLevelController::class, 'destroyLevel'])
        ->name('program-level.level.destroy');

    //PAKET REGULER
    Route::post('/program-level/paket', [ProgramLevelController::class, 'storePackage'])
        ->name('program-level.paket.store');
    Route::get('/program-level/paket/{package}/edit', [ProgramLevelController::class, 'editPackage'])
        ->name('program-level.paket.edit');
    Route::put('/program-level/paket/{package}', [ProgramLevelController::class, 'updatePackage'])
        ->name('program-level.paket.update');
    Route::delete('/program-level/paket/{package}', [ProgramLevelController::class, 'destroyPackage'])
        ->name('program-level.paket.destroy');

    //PAKET PRIVATE
    Route::post('/program-level/private', [ProgramLevelController::class, 'storePrivate'])
        ->name('program-level.private.store');
    Route::get('/program-level/private/{privatePackage}/edit', [ProgramLevelController::class, 'editPrivate'])
        ->name('program-level.private.edit');
    Route::put('/program-level/private/{privatePackage}', [ProgramLevelController::class, 'updatePrivate'])
        ->name('program-level.private.update');
    Route::delete('/program-level/private/{privatePackage}', [ProgramLevelController::class, 'destroyPrivate'])
        ->name('program-level.private.destroy');
});