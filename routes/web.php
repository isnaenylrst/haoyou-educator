<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Siswa\SiswaLearningController;
use App\Http\Controllers\Siswa\BookingController;
use App\Http\Controllers\Siswa\StudentCertificateController;
use App\Http\Controllers\Siswa\VocabController;
use App\Http\Controllers\ProfilController;

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
// Dashboard pakai alias supaya tidak bentrok dengan Kurikulum\DashboardController
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CalonSiswa\CalonSiswaController;
use App\Http\Controllers\Admin\CalonSiswa\ConvertController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JurnalMengajarController;
use App\Http\Controllers\Admin\ProgressReportController;
use App\Http\Controllers\Admin\KurikulumMateriController;
use App\Http\Controllers\Admin\DokumenController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\ProgramLevelController;
use App\Http\Controllers\Admin\Pengaturan\PengaturanController;
use App\Http\Controllers\Admin\Pengaturan\TemplatePrintController;
use App\Http\Controllers\Admin\PaymentInvoiceController;

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

// ===== OWNER =====
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\AccessController as OwnerAccessController;

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
| VERIFIKASI SERTIFIKAT (publik, tujuan QR code di sertifikat)
|--------------------------------------------------------------------------
*/

Route::get('/verifikasi-sertifikat/{code}', [StudentCertificateController::class, 'verify'])
    ->name('certificates.verify');

/*
|--------------------------------------------------------------------------
| AREA SISWA
|--------------------------------------------------------------------------
| Wajib login + role Student.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Student'])->group(function () {

    Route::get('/dashboard', [SiswaDashboardController::class, 'dashboard'])
        ->name('dashboard');

    Route::get('/program-belajar', [SiswaDashboardController::class, 'program'])
        ->name('program.index');

    Route::get('/kelas-saya', [SiswaDashboardController::class, 'kelasSaya'])
        ->name('kelassaya.index');

    // NOTIFIKASI
    Route::get('/notifikasi', [SiswaDashboardController::class, 'notifikasi'])
        ->name('notifikasi.index');

    Route::post('/notifikasi/tandai-dibaca', [SiswaDashboardController::class, 'notifikasiTandaiDibaca'])
        ->name('notifikasi.read-all');

    // PROGRESS REPORT (disetujui Kurikulum)
    Route::get('/progress-report', [SiswaLearningController::class, 'progresReport'])
        ->name('progresreport.index');

    Route::get('/progress-report/{report}/download', [SiswaLearningController::class, 'downloadProgressReport'])
        ->name('progresreport.download');

    // SERTIFIKAT (disetujui Kurikulum)
    Route::get('/sertifikat', [SiswaLearningController::class, 'sertifikat'])
        ->name('sertifikat.index');

    Route::get('/sertifikat/{certificate}/download', [SiswaLearningController::class, 'downloadSertifikat'])
        ->name('sertifikat.download');

    // VIDEO
    Route::get('/video', [SiswaLearningController::class, 'video'])
        ->name('video.index');

    Route::get('/video/{video}/stream', [SiswaLearningController::class, 'streamVideo'])
        ->name('video.stream');

    // BELAJAR VOCAB
    Route::prefix('student/belajar-vocab')->name('student.vocab.')->group(function () {

        Route::get('study-plan', [VocabController::class, 'plan'])->name('plan');
        Route::post('study-plan', [VocabController::class, 'planSave'])->name('plan.save');

        Route::get('vocab-list', [VocabController::class, 'list'])->name('list');

        Route::get('test/{type?}', [VocabController::class, 'test'])->name('test')
            ->where('type', 'reading|listening|writing');
        Route::post('test/{type}/answer', [VocabController::class, 'answer'])->name('test.answer');
        Route::get('test/{type}/pending', [VocabController::class, 'pending'])->name('test.pending');
        Route::post('test/{type}/reset', [VocabController::class, 'reset'])->name('test.reset');

        Route::get('vocab-traps', [VocabController::class, 'traps'])->name('traps');
        Route::post('vocab-traps/{word}/paham', [VocabController::class, 'understood'])->name('traps.understood');
    });

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

    // PROFIL
    Route::get('/profil', [ProfilController::class, 'index'])
        ->name('profil.index');

    Route::put('/profil', [ProfilController::class, 'update'])
        ->name('profil.update');

    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])
        ->name('profil.password');

    Route::delete('/profil/foto', [ProfilController::class, 'destroyFoto'])
        ->name('profil.foto.destroy');
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

        // Pemberitahuan Surat
        Route::get('/surat', [LetterController::class, 'index'])
            ->name('surat');

        Route::post('/surat', [LetterController::class, 'store'])
            ->name('surat.store');

        Route::delete('/surat/{document}', [LetterController::class, 'destroy'])
            ->name('surat.destroy');

        // Monitoring Guru
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

        Route::get('/video/{video}/stream', [KurikulumVideoController::class, 'stream'])
            ->name('video.stream');
    });

/*
|--------------------------------------------------------------------------
| GURU
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Teacher', 'teacher.profile'])
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

        Route::get('/sop/template/{documentTemplate}', [GuruSopController::class, 'template'])
            ->name('sop.template');

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

        // Kelas
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
        Route::get('/cuti', [GuruCutiController::class, 'index'])->name('cuti');
        Route::post('/cuti', [GuruCutiController::class, 'store'])->name('cuti.store');

        // Dashboard
        Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');

        // Tugas perbaikan kelas dari Kurikulum (ditandai selesai dari Notifikasi)
        Route::post('/tugas/{classTask}/selesai', [GuruTugasKelasController::class, 'complete'])->name('tugas.complete');

        // Guru pengganti menerima / menolak permintaan menggantikan kelas
        Route::post('/permintaan-pengganti/{leave}/respond', [GuruAvailabilityController::class, 'respondReplacement'])->name('replacement.respond');

        // Ketersediaan guru (diisi & diedit guru di halaman Schedule)
        Route::post('/availability', [GuruAvailabilityController::class, 'store'])->name('availability.store');
        Route::put('/availability/{availability}', [GuruAvailabilityController::class, 'update'])->name('availability.update');
        Route::delete('/availability/{availability}', [GuruAvailabilityController::class, 'destroy'])->name('availability.destroy');

        // Terima / tolak kelas yang diberikan Kurikulum
        Route::post('/kelas-diberikan/{class}/respond', [GuruAvailabilityController::class, 'respondClass'])->name('class.respond');
    });

/*
|--------------------------------------------------------------------------
| OWNER
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::get('/dashboard', [OwnerDashboardController::class, 'index'])
            ->name('dashboard');

        // Akses area Guru (Owner memilih guru yang dibuka)
        Route::get('/guru', [OwnerAccessController::class, 'teachers'])
            ->name('guru');

        Route::post('/guru/ganti', [OwnerAccessController::class, 'switch'])
            ->name('guru.switch');

        Route::post('/guru/keluar', [OwnerAccessController::class, 'clear'])
            ->name('guru.clear');

        Route::post('/guru/{teacher}/lihat', [OwnerAccessController::class, 'view'])
            ->name('guru.view');
    });

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {

    // DASHBOARD (pakai alias AdminDashboardController)
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('dashboard');

    // CALON SISWA
    Route::prefix('calon-siswa')->name('calon-siswa.')->group(function () {
        // CRUD
        Route::get('/', [CalonSiswaController::class, 'index'])->name('index');
        Route::post('/', [CalonSiswaController::class, 'store'])->name('store');
        Route::get('/{candidateStudent}/edit', [CalonSiswaController::class, 'edit'])->name('edit');
        Route::put('/{candidateStudent}', [CalonSiswaController::class, 'update'])->name('update');
        Route::delete('/{candidateStudent}', [CalonSiswaController::class, 'destroy'])->name('destroy');

        // KONVERSI CALON SISWA (wizard 3 step)
        Route::get('/{candidateStudent}/convert', [ConvertController::class, 'create'])
            ->name('convert');                      // step 1 (tampilan)
        Route::post('/{candidateStudent}/convert/step-1', [ConvertController::class, 'storeStep1'])
            ->name('convert.step1.store');          // step 1 (submit)
        Route::get('/{candidateStudent}/convert/pembayaran', [ConvertController::class, 'step2'])
            ->name('convert.step2');                // step 2 (tampilan)
        Route::post('/{candidateStudent}/convert/pembayaran', [ConvertController::class, 'store'])
            ->name('convert.store');                // step 2 (submit)
        Route::get('/enrollment/{enrollment}/dokumen', [ConvertController::class, 'step3'])
            ->name('convert.step3');                // step 3
    });

    // SISWA
    Route::prefix('siswa')->group(function () {
        Route::get('/', [SiswaController::class, 'index'])
            ->name('siswa');
        Route::get('/{siswa}/edit', [SiswaController::class, 'edit'])
            ->name('siswa.edit');
        Route::put('/{siswa}', [SiswaController::class, 'update'])
            ->name('siswa.update');
        Route::get('/{siswa}', [SiswaController::class, 'show'])
            ->name('siswa.show');

        // SISWA: LANJUT PROGRAM
        Route::get('/{siswa}/continue', [SiswaController::class, 'continueProgramForm'])
            ->name('siswa.continue.form');
        // Jalur 1 (belum lunas): pelunasan
        Route::get('/{siswa}/pelunasan', [SiswaController::class, 'settlementForm'])
            ->name('siswa.settle');
        Route::post('/{siswa}/pelunasan', [SiswaController::class, 'settlementStore'])
            ->name('siswa.settle.store');
        // Jalur 2 (sudah lunas): pilih program
        Route::get('/{siswa}/continue/program', [SiswaController::class, 'continueProgramPage'])
            ->name('siswa.continue.program');
        Route::post('/{siswa}/continue', [SiswaController::class, 'continueProgram'])
            ->name('siswa.continue');
        Route::get('/{siswa}/continue/pembayaran', [SiswaController::class, 'continuePayment'])
            ->name('siswa.continue.payment');
        Route::post('/{siswa}/continue/pembayaran', [SiswaController::class, 'continueStore'])
            ->name('siswa.continue.store');

        // Halaman akhir (invoice & formulir), dipakai kedua jalur
        Route::get('/{siswa}/continue/selesai/{enrollment}', [SiswaController::class, 'continueDone'])
            ->name('siswa.continue.done');

        // Formulir pendaftaran
        Route::get('/{siswa}/formulir/{enrollment?}', [
            \App\Http\Controllers\Admin\CalonSiswa\RegistrationFormController::class,
            'download'
        ])->name('siswa.formulir');
    });

    // ENROLLMENTS
    Route::prefix('enrollments')->group(function () {
        Route::get('/{enrollment}/waiting-class-options', [SiswaController::class, 'waitingClassOptions'])
            ->name('enrollments.waiting-class-options');
        Route::patch('/{enrollment}/assign-class', [SiswaController::class, 'assignClass'])
            ->name('enrollments.assign-class');
    });

    // JADWAL
    Route::prefix('jadwal')->name('jadwal.')->group(function () {
        // Halaman utama
        Route::get('/', [JadwalController::class, 'index'])
            ->name('index');
        // Session
        Route::post('/sessions', [JadwalController::class, 'storeSession'])
            ->name('sessions.store');
        Route::put('/sessions/{classSession}/complete', [JadwalController::class, 'complete'])
            ->name('complete');
        Route::put('/sessions/{classSession}/reschedule', [JadwalController::class, 'reschedule'])
            ->name('reschedule');

        // Fee
        Route::put('/fees/{sessionFee}/pay', [JadwalController::class, 'payFee'])
            ->name('fees.pay');
        Route::get('/fees/{sessionFee}/invoice', [JadwalController::class, 'feeInvoice'])
            ->name('fees.invoice');
    });

    // PENGATURAN
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');

    // PENGATURAN — TEMPLATE PRINT DOKUMEN (FORM & INVOICE)
    Route::prefix('print-dokumen')->name('template-dokumen.')->group(function () {
        Route::get('/', [TemplatePrintController::class, 'index'])
            ->name('index');
        Route::get('/{template}/edit', [TemplatePrintController::class, 'edit'])
            ->name('edit');
        Route::put('/{template}', [TemplatePrintController::class, 'update'])
            ->name('update');
        Route::post('/{template}/reset', [TemplatePrintController::class, 'reset'])
            ->name('reset');
        Route::post('/{template}/preview', [TemplatePrintController::class, 'preview'])
            ->name('preview');
    });

    // KELAS
    Route::prefix('kelas')->group(function () {
        Route::get('/', [KelasController::class, 'index'])
            ->name('kelas');
        Route::post('/', [KelasController::class, 'store'])
            ->name('kelas.store');
        Route::put('/{id}', [KelasController::class, 'update'])
            ->name('kelas.update');
        Route::get('/{id}/siswa', [KelasController::class, 'students'])
            ->name('kelas.students');
        Route::get('/{id}/siswa/cari', [KelasController::class, 'searchStudents'])
            ->name('kelas.students.search');
        Route::post('/{id}/siswa', [KelasController::class, 'enrollStudent'])
            ->name('kelas.students.store');
    });

    // PROGRAM & LEVEL
    Route::prefix('program-level')->group(function () {
        Route::get('/', [ProgramLevelController::class, 'index'])
            ->name('program-level');
        Route::get('/struktur', [ProgramLevelController::class, 'struktur'])
            ->name('program-level.struktur');

        // PROGRAM
        Route::prefix('program')->group(function () {
            Route::post('/', [ProgramLevelController::class, 'storeProgram'])
                ->name('program-level.program.store');
            Route::get('/{program}/edit', [ProgramLevelController::class, 'editProgram'])
                ->name('program-level.program.edit');
            Route::put('/{program}', [ProgramLevelController::class, 'updateProgram'])
                ->name('program-level.program.update');
            Route::delete('/{program}', [ProgramLevelController::class, 'destroyProgram'])
                ->name('program-level.program.destroy');
        });

        // CATEGORY
        Route::prefix('category')->group(function () {
            Route::post('/', [ProgramLevelController::class, 'storeCategory'])
                ->name('program-level.category.store');
            Route::get('/{category}/edit', [ProgramLevelController::class, 'editCategory'])
                ->name('program-level.category.edit');
            Route::put('/{category}', [ProgramLevelController::class, 'updateCategory'])
                ->name('program-level.category.update');
            Route::delete('/{category}', [ProgramLevelController::class, 'destroyCategory'])
                ->name('program-level.category.destroy');
        });

        // LEVEL
        Route::prefix('level')->group(function () {
            Route::post('/', [ProgramLevelController::class, 'storeLevel'])
                ->name('program-level.level.store');
            Route::get('/{level}/edit', [ProgramLevelController::class, 'editLevel'])
                ->name('program-level.level.edit');
            Route::put('/{level}', [ProgramLevelController::class, 'updateLevel'])
                ->name('program-level.level.update');
            Route::delete('/{level}', [ProgramLevelController::class, 'destroyLevel'])
                ->name('program-level.level.destroy');
        });

        // PAKET REGULER
        Route::prefix('paket')->group(function () {
            Route::post('/', [ProgramLevelController::class, 'storePackage'])
                ->name('program-level.paket.store');
            Route::get('/{package}/edit', [ProgramLevelController::class, 'editPackage'])
                ->name('program-level.paket.edit');
            Route::put('/{package}', [ProgramLevelController::class, 'updatePackage'])
                ->name('program-level.paket.update');
            Route::delete('/{package}', [ProgramLevelController::class, 'destroyPackage'])
                ->name('program-level.paket.destroy');
        });

        // PAKET PRIVATE
        Route::prefix('private')->group(function () {
            Route::post('/', [ProgramLevelController::class, 'storePrivate'])
                ->name('program-level.private.store');
            Route::get('/{privatePackage}/edit', [ProgramLevelController::class, 'editPrivate'])
                ->name('program-level.private.edit');
            Route::put('/{privatePackage}', [ProgramLevelController::class, 'updatePrivate'])
                ->name('program-level.private.update');
            Route::delete('/{privatePackage}', [ProgramLevelController::class, 'destroyPrivate'])
                ->name('program-level.private.destroy');
        });
    });

    // GURU
    Route::prefix('guru')->name('guru.')->controller(GuruController::class)->group(function () {
        Route::get('/', 'index')
            ->name('index');
        Route::post('/', 'store')
            ->name('store');

        // Dokumen: taruh sebelum route {teacher} supaya "documents" tidak ditangkap sebagai ID guru
        Route::delete('/documents/{document}', 'destroyDocument')
            ->name('documents.destroy');
        Route::get('/{teacher}/detail', 'show')
            ->name('show');
        Route::post('/{teacher}/documents', 'storeDocument')
            ->name('documents.store');
        Route::get('/{teacher}/edit', 'edit')
            ->name('edit');
        Route::put('/{teacher}', 'update')
            ->name('update');
        Route::delete('/{teacher}', 'destroy')
            ->name('destroy');
    });

    // PEMBAYARAN
    Route::prefix('pembayaran')->group(function () {
        Route::get('/', [PembayaranController::class, 'index'])
            ->name('pembayaran');
        Route::get('/{enrollment}', [PembayaranController::class, 'show'])
            ->name('pembayaran.show');
        Route::post('/{enrollment}', [PembayaranController::class, 'store'])
            ->name('pembayaran.store');
    });

    // INVOICE
    Route::prefix('payments')->group(function () {
        Route::get('/{payment}/invoice', [PaymentInvoiceController::class, 'show'])
            ->name('pembayaran.invoice');
    });

    // JURNAL MENGAJAR
    Route::prefix('jurnal-mengajar')->group(function () {
        Route::get('/', [JurnalMengajarController::class, 'index'])
            ->name('jurnal-mengajar');

        Route::get('/{journal}', [JurnalMengajarController::class, 'show'])
            ->name('jurnal-mengajar.show');

        Route::post('/ingatkan', [JurnalMengajarController::class, 'remind'])
            ->name('jurnal-mengajar.remind');
    });

    // KURIKULUM & MATERI (read-only)
    Route::get('/kurikulum-materi', [KurikulumMateriController::class, 'index'])
        ->name('kurikulum-materi');

    Route::get('/kurikulum-materi/{material}/unduh', [KurikulumMateriController::class, 'unduh'])
        ->name('kurikulum-materi.unduh');

    // PROGRESS REPORT
    Route::get('/progress-report', [ProgressReportController::class, 'index'])
        ->name('progress-report');

    Route::get('/progress-report/{report}/lihat', [ProgressReportController::class, 'lihat'])
        ->name('progress-report.lihat');

    Route::get('/progress-report/{report}/unduh', [ProgressReportController::class, 'unduh'])
        ->name('progress-report.unduh');

    Route::patch('/progress-report/{report}/follow-up', [ProgressReportController::class, 'followUp'])
        ->name('progress-report.follow-up');

    // DOKUMEN
    Route::get('/dokumen', [DokumenController::class, 'index'])
        ->name('dokumen');

    Route::post('/dokumen', [DokumenController::class, 'store'])
        ->name('dokumen.store');

    Route::put('/dokumen/{document}', [DokumenController::class, 'update'])
        ->name('dokumen.update');

    Route::delete('/dokumen/{document}', [DokumenController::class, 'destroy'])
        ->name('dokumen.destroy');

    Route::get('/dokumen/{document}/lihat', [DokumenController::class, 'lihat'])
        ->name('dokumen.lihat');

    Route::get('/dokumen/{document}/unduh', [DokumenController::class, 'unduh'])
        ->name('dokumen.unduh');

    Route::post('/dokumen/folder', [DokumenController::class, 'storeFolder'])
        ->name('dokumen.folder.store');

    Route::delete('/dokumen/folder/{folder}', [DokumenController::class, 'destroyFolder'])
        ->name('dokumen.folder.destroy');
});