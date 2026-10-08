<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Siswa\BookingController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\ConvertController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KelasController;
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
| AREA SISWA
|--------------------------------------------------------------------------
| Wajib login + level "Student" (middleware alias: student).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'student'])->group(function () {

    Route::get('/dashboard', [SiswaDashboardController::class, 'dashboard'])
        ->name('student.dashboard');

    Route::get('/program', [SiswaDashboardController::class, 'program'])
        ->name('program.index');

    Route::get('/kelas-saya', [SiswaDashboardController::class, 'kelasSaya'])
        ->name('kelassaya.index');

    Route::get('/progress-report', [SiswaDashboardController::class, 'progressReport'])
        ->name('progresreport.index');

    Route::get('/sertifikat', [SiswaDashboardController::class, 'sertifikat'])
        ->name('sertifikat.index');

    Route::get('/notifikasi', [SiswaDashboardController::class, 'notifikasi'])
        ->name('notifikasi.index');

    Route::post('/notifikasi/tandai-dibaca', [SiswaDashboardController::class, 'notifikasiTandaiDibaca'])
        ->name('notifikasi.read-all');

    // BOOKING KELAS
    Route::get('/booking', [BookingController::class, 'index'])
        ->name('booking.index');

    Route::post('/booking/privat', [BookingController::class, 'storePrivate'])
        ->name('booking.privat.store');

    Route::patch('/booking/privat/{booking}/reschedule', [BookingController::class, 'reschedulePrivate'])
        ->name('booking.privat.reschedule');

    Route::post('/booking/reguler/{class}', [BookingController::class, 'daftarReguler'])
        ->name('booking.reguler.daftar');

    Route::post('/booking/request-privat', [BookingController::class, 'requestPrivate'])
        ->name('booking.request.privat');

    Route::post('/booking/request-reguler', [BookingController::class, 'requestReguler'])
        ->name('booking.request.reguler');
});

    // PROFIL
    Route::middleware(['auth'])->group(function () {
    Route::get('/profil', [ProfilController::class, 'index'])
        ->name('profil.index');

    Route::put('/profil', [ProfilController::class, 'update'])
        ->name('profil.update');

    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])
        ->name('profil.password');

    Route::delete('/profil/foto', [ProfilController::class, 'destroyFoto'])
        ->name('profil.foto.destroy');
});

    // ADMIN
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
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

    //ASSIGN KELAS UNTUK ENROLLMENT YANG MASIH WAITING CLASS
    Route::patch('enrollments/{enrollment}/assign-class', [ConvertController::class, 'assignClass'])
        ->name('enrollments.assign-class');
        
    //SISWA — ASSIGN KELAS UNTUK ENROLLMENT WAITING CLASS
    Route::get('enrollments/{enrollment}/waiting-class-options', [SiswaController::class, 'waitingClassOptions'])
        ->name('enrollments.waiting-class-options');        
    Route::get('/jadwal', [JadwalController::class, 'index'])
        ->name('jadwal');
    Route::get('/kelas', [KelasController::class, 'index'])
        ->name('kelas');

    //PROGRAM & LEVEL (index + paket reguler program_packages + paket private private_packages)
    Route::get('/program-level', [ProgramLevelController::class, 'index'])
        ->name('program-level');
        
    Route::get('/program-level/struktur', [ProgramLevelController::class, 'struktur'])
        ->name('program-level.struktur');
    //PROGRAM, KATEGORI, LEVEL (CRUD)
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