<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CalonSiswa\PendaftaranController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CalonSiswa\CalonSiswaController;
use App\Http\Controllers\Admin\CalonSiswa\ConvertController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JurnalMengajarController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\ProgramLevelController;
use App\Http\Controllers\Admin\Pengaturan\PengaturanController;
use App\Http\Controllers\Admin\Pengaturan\TemplatePrintController;
use App\Http\Controllers\Admin\PaymentInvoiceController;

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


// ADMIN
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
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
        Route::get('/{enrollment}/waiting-class-options', [SiswaController::class,'waitingClassOptions'])
            ->name('enrollments.waiting-class-options');
        Route::patch('/{enrollment}/assign-class', [SiswaController::class,'assignClass'])
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
        Route::post('/', [JurnalMengajarController::class, 'store'])
            ->name('jurnal-mengajar.store');
        Route::get('/{journal}/edit', [JurnalMengajarController::class, 'edit'])
            ->name('jurnal-mengajar.edit');
        Route::put('/{journal}', [JurnalMengajarController::class, 'update'])
            ->name('jurnal-mengajar.update');
    });
});