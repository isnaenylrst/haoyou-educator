<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Kurikulum\DashboardController;


Route::view('/', 'dashboard');

Route::view('/dashboard', 'dashboard');

//KURIKULUM
Route::get(
    '/kurikulum/dashboard',
    [DashboardController::class,'index']
)->name('kurikulum.dashboard');

Route::view('/kurikulum/sop', 'kurikulum.sop');

Route::view('/kurikulum/materi', 'kurikulum.materi');

Route::view('/kurikulum/jadwal-konsultasi', 'kurikulum.jadwal-konsultasi');

Route::view('/kurikulum/review-pengajuan', 'kurikulum.review-pengajuan');

Route::view('/kurikulum/monitoring', 'kurikulum.monitoring');

Route::view('/kurikulum/surat', 'kurikulum.surat');

//GURU
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