<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Siswa\SiswaLearningController;

// ===== KURIKULUM =====
use App\Http\Controllers\Kurikulum\DashboardController;
use App\Http\Controllers\Kurikulum\SopController;
use App\Http\Controllers\Kurikulum\DocumentTemplateController;
use App\Http\Controllers\Kurikulum\MaterialController;
use App\Http\Controllers\Kurikulum\LetterController;
use App\Http\Controllers\Kurikulum\MonitoringController;
use App\Http\Controllers\Kurikulum\JadwalKonsultasiController;
use App\Http\Controllers\Kurikulum\ReviewPengajuanController;
use App\Http\Controllers\Kurikulum\TugasKelasController as KurikulumTugasKelasController;
use App\Http\Controllers\Kurikulum\KompetensiController;
use App\Http\Controllers\Kurikulum\KetersediaanGuruController;
use App\Http\Controllers\Kurikulum\VideoController as KurikulumVideoController;

// ===== ADMIN =====
// PENTING: dikasih alias "Admin..." di depan supaya TIDAK BENTROK
// dengan DashboardController milik Kurikulum di atas.
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\ConvertController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Admin\ProgramLevelController;

// ===== GURU =====
use App\Http\Controllers\Guru\NotifikasiController as GuruNotifikasiController;
use App\Http\Controllers\Guru\SopController as GuruSopController;
use App\Http\Controllers\Guru\MaterialController as GuruMaterialController; 
use App\Http\Controllers\Guru\KelasController as GuruKelasController;
use App\Http\Controllers\Guru\AttendanceController as GuruAttendanceController;
use App\Http\Controllers\Guru\ProgressReportController as GuruProgressReportController;
use App\Http\Controllers\Guru\ScheduleController as GuruScheduleController;
use App\Http\Controllers\Guru\TeachingLogController as GuruTeachingLogController;
use App\Http\Controllers\Guru\CutiController as GuruCutiController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\TugasKelasController as GuruTugasKelasController;
use App\Http\Controllers\Guru\AvailabilityController as GuruAvailabilityController;
use App\Http\Controllers\Guru\CertificateController as GuruCertificateController;

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

    Route::get('/sertifikat', [SiswaLearningController::class, 'sertifikat'])
        ->name('sertifikat.index');

    Route::get('/sertifikat/{certificate}/download', [SiswaLearningController::class, 'downloadSertifikat'])
        ->name('sertifikat.download');

    Route::get('/progress-report', [SiswaLearningController::class, 'progresReport'])
        ->name('progresreport.index');

    Route::get('/progress-report/{report}/download', [SiswaLearningController::class, 'downloadProgressReport'])
        ->name('progresreport.download');

    Route::get('/video', [SiswaLearningController::class, 'video'])
        ->name('video.index');

    Route::get('/video/{video}/stream', [SiswaLearningController::class, 'streamVideo'])
        ->name('video.stream');
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
        
        Route::get('/materi/{material}/download', [MaterialController::class, 'download'])
            ->name('materi.download');

        Route::get('/materi/{material}/preview', [MaterialController::class, 'preview'])
            ->name('materi.preview');

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

        // Review sertifikat buatan guru (ACC / tolak)
        Route::patch('/review-pengajuan/sertifikat/{certificate}', [ReviewPengajuanController::class, 'reviewCertificate'])
            ->name('review-pengajuan.certificate');

        // Buka kembali progress report yang lewat batas H+3
        Route::post('/review-pengajuan/enrollment/{enrollment}/reopen-report', [ReviewPengajuanController::class, 'reopenReport'])
            ->name('review-pengajuan.reopen-report');

        /*
        |--------------------------------------------------------------------------
        | TUGAS PERBAIKAN KELAS (masuk ke notifikasi guru)
        |--------------------------------------------------------------------------
        */

        Route::get('/tugas-kelas', [KurikulumTugasKelasController::class, 'index'])
            ->name('tugas-kelas');

        Route::post('/tugas-kelas', [KurikulumTugasKelasController::class, 'store'])
            ->name('tugas-kelas.store');

        Route::delete('/tugas-kelas/{classTask}', [KurikulumTugasKelasController::class, 'destroy'])
            ->name('tugas-kelas.destroy');

        /*
        |--------------------------------------------------------------------------
        | KOMPETENSI PROGRESS REPORT & DESKRIPSI ACHIEVEMENT LEVEL
        |--------------------------------------------------------------------------
        */

        Route::get('/kompetensi', [KompetensiController::class, 'index'])
            ->name('kompetensi');

        Route::post('/kompetensi', [KompetensiController::class, 'store'])
            ->name('kompetensi.store');

        Route::put('/kompetensi-deskripsi', [KompetensiController::class, 'updateDescriptions'])
            ->name('kompetensi.descriptions');

        Route::put('/kompetensi/{competency}', [KompetensiController::class, 'update'])
            ->name('kompetensi.update');

        Route::delete('/kompetensi/{competency}', [KompetensiController::class, 'destroy'])
            ->name('kompetensi.destroy');

        /*
        |--------------------------------------------------------------------------
        | KETERSEDIAAN GURU
        |--------------------------------------------------------------------------
        */

        Route::get('/ketersediaan-guru', [KetersediaanGuruController::class, 'index'])
            ->name('ketersediaan');

        Route::post('/ketersediaan-guru', [KetersediaanGuruController::class, 'store'])
            ->name('ketersediaan.store');

        Route::patch('/ketersediaan-guru/kelas/{class}/assign', [KetersediaanGuruController::class, 'assign'])
            ->name('ketersediaan.assign');

        Route::put('/ketersediaan-guru/{availability}', [KetersediaanGuruController::class, 'update'])
            ->name('ketersediaan.update');

        Route::delete('/ketersediaan-guru/{availability}', [KetersediaanGuruController::class, 'destroy'])
            ->name('ketersediaan.destroy');

        /*
        |--------------------------------------------------------------------------
        | VIDEO PEMBELAJARAN (upload -> siswa bisa menonton)
        |--------------------------------------------------------------------------
        */

        Route::get('/video', [KurikulumVideoController::class, 'index'])
            ->name('video');

        Route::post('/video', [KurikulumVideoController::class, 'store'])
            ->name('video.store');

        Route::put('/video/{video}', [KurikulumVideoController::class, 'update'])
            ->name('video.update');

        Route::delete('/video/{video}', [KurikulumVideoController::class, 'destroy'])
            ->name('video.destroy');


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


        // Notifikasi
        Route::get('/notifikasi', [GuruNotifikasiController::class, 'index'])
            ->name('notifikasi');

        Route::get('/notifikasi/{document}/download', [GuruNotifikasiController::class, 'download'])
            ->name('notifikasi.download');

        // SOP
        Route::get('/sop', [GuruSopController::class, 'index'])
            ->name('sop');

        Route::get('/sop/{document}', [GuruSopController::class, 'show'])
            ->name('sop.show');

        Route::get('/sop/{document}/download', [GuruSopController::class, 'download'])
            ->name('sop.download');

        // Materi
        Route::get('/materi', [GuruMaterialController::class, 'index'])
            ->name('materi');
        Route::get('/materi/{material}', [GuruMaterialController::class, 'show'])
            ->name('materi.show');
        Route::get('/materi/{material}/download', [GuruMaterialController::class, 'download'])
            ->name('materi.download');







        //Kelas
        Route::get('/kelas', [GuruKelasController::class, 'index'])
            ->name('kelas');

        Route::post('/kelas', [GuruKelasController::class, 'store'])
            ->name('kelas.store');

        Route::post('/kelas/{teacherMaterial}/reupload', [GuruKelasController::class, 'reupload'])
            ->name('kelas.reupload');
        Route::post('/kelas/{teacherMaterial}/request-consultation', [GuruKelasController::class, 'requestConsultation'])
            ->name('kelas.request-consultation');

        // Attendance
        Route::get('/attendance', [GuruAttendanceController::class, 'index'])
            ->name('attendance');

        Route::post('/attendance/{classSchedule}', [GuruAttendanceController::class, 'store'])
            ->name('attendance.store');

        Route::get('/attendance/{journal}/pdf', [GuruAttendanceController::class, 'pdf'])
            ->name('attendance.pdf');

        // Progress Report
        Route::get('/progress-report', [GuruProgressReportController::class, 'index'])->name('progress-report');
        Route::get('/progress-report/enrollment/{enrollment}', [GuruProgressReportController::class, 'form'])->name('progress-report.form');
        Route::post('/progress-report/enrollment/{enrollment}', [GuruProgressReportController::class, 'store'])->name('progress-report.store');
        Route::get('/progress-report/{report}/download', [GuruProgressReportController::class, 'download'])->name('progress-report.download');
        Route::post('/progress-report/submit-all', [GuruProgressReportController::class, 'submitAll'])->name('progress-report.submit-all');
        // Sertifikat: guru membuat -> Kurikulum ACC -> tampil di siswa
        Route::post('/progress-report/enrollment/{enrollment}/certificate', [GuruCertificateController::class, 'quickStore'])->name('progress-report.certificate.store');
        Route::get('/sertifikat', [GuruCertificateController::class, 'index'])->name('sertifikat');
        Route::post('/sertifikat', [GuruCertificateController::class, 'store'])->name('sertifikat.store');
        Route::put('/sertifikat/{certificate}', [GuruCertificateController::class, 'update'])->name('sertifikat.update');
        Route::delete('/sertifikat/{certificate}', [GuruCertificateController::class, 'destroy'])->name('sertifikat.destroy');
        Route::get('/sertifikat/{certificate}/download', [GuruCertificateController::class, 'download'])->name('sertifikat.download');

        // Schedule
        Route::get('/schedule', [GuruScheduleController::class, 'index'])->name('schedule');

        // Teaching Log
        Route::get('/teaching-log', [GuruTeachingLogController::class, 'index'])->name('teaching-log');

        // Cuti
        Route::get('/cuti',  [GuruCutiController::class, 'index'])->name('cuti');
        Route::post('/cuti', [GuruCutiController::class, 'store'])->name('cuti.store');

        // Dashboard
        Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');

        // Tugas perbaikan kelas dari Kurikulum (ditandai selesai dari Notifikasi)
        Route::post('/tugas/{classTask}/selesai', [GuruTugasKelasController::class, 'complete'])->name('tugas.complete');

        // Ketersediaan guru (diisi & diedit guru di halaman Schedule)
        // Guru pengganti menerima / menolak permintaan menggantikan kelas
        Route::post('/permintaan-pengganti/{leave}/respond', [GuruAvailabilityController::class, 'respondReplacement'])->name('replacement.respond');

        Route::post('/availability', [GuruAvailabilityController::class, 'store'])->name('availability.store');
        Route::put('/availability/{availability}', [GuruAvailabilityController::class, 'update'])->name('availability.update');
        Route::delete('/availability/{availability}', [GuruAvailabilityController::class, 'destroy'])->name('availability.destroy');

        // Terima / tolak kelas yang diberikan Kurikulum
        Route::post('/kelas-diberikan/{class}/respond', [GuruAvailabilityController::class, 'respondClass'])->name('class.respond');
    });

/*
|--------------------------------------------------------------------------
| ADMIN (punya teman)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {

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