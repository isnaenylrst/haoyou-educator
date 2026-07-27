@extends('layouts.kurikulum')

@section('title','Monitoring Guru & Kelas')

@section('content')

<div class="container-fluid">

    <!-- =========================
            HEADER
    ========================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Monitoring Guru & Kelas
            </h2>

            <p class="text-muted mb-0">
                Kalender jadwal seluruh guru, plus ringkasan jam & sesi mengajar
            </p>

        </div>

        <div>

            <button class="btn btn-primary">

                <i class="fas fa-download me-2"></i>

                Export Laporan

            </button>

        </div>

    </div>



    <!-- =========================
            STATISTIK
    ========================== -->

    <div class="row mb-4">

        <!-- Total Guru -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">

                        TOTAL GURU

                    </small>

                    <h2 class="fw-bold mt-2">

                        18

                    </h2>

                </div>

            </div>

        </div>


        <!-- Total Jam -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">

                        TOTAL JAM MENGAJAR

                    </small>

                    <h2 class="fw-bold mt-2">

                        312 Jam

                    </h2>

                </div>

            </div>

        </div>


        <!-- Total Sesi -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">

                        TOTAL SESI BULAN INI

                    </small>

                    <h2 class="fw-bold mt-2">

                        164 Sesi

                    </h2>

                </div>

            </div>

        </div>


        <!-- Rata-rata -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">

                        RATA-RATA JAM / GURU

                    </small>

                    <h2 class="fw-bold mt-2 text-warning">

                        17,3 Jam

                    </h2>

                </div>

            </div>

        </div>

    </div>



    <!-- =========================
            JADWAL MINGGUAN
            (PART 2)
    ========================== -->
    <div class="card shadow-sm border-0 mb-5">

    <div class="card-header bg-white">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h5 class="mb-0 fw-bold">

                    Jadwal Mengajar Mingguan

                </h5>

                <small class="text-muted">

                    Monitoring jadwal seluruh guru selama 1 minggu

                </small>

            </div>

            <div>

                <button class="btn btn-outline-secondary btn-sm">

                    <i class="fas fa-chevron-left"></i>

                </button>

                <button class="btn btn-outline-secondary btn-sm">

                    Minggu Ini

                </button>

                <button class="btn btn-outline-secondary btn-sm">

                    <i class="fas fa-chevron-right"></i>

                </button>

            </div>

        </div>

    </div>


    <div class="table-responsive">

        <table class="table table-bordered align-middle text-center mb-0">

            <thead class="table-light">

            <tr>

                <th width="90">

                    Jam

                </th>

                <th>

                    Senin

                </th>

                <th>

                    Selasa

                </th>

                <th>

                    Rabu

                </th>

                <th>

                    Kamis

                </th>

                <th>

                    Jumat

                </th>

            </tr>

            </thead>

            <tbody>

            <!-- 13.00 -->

            <tr style="height:95px;">

                <td class="fw-bold">

                    13.00

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-warning-subtle border-start border-4 border-warning">

                        <strong>

                            Daily Activity

                        </strong>

                        <br>

                        Maochong

                        <br>

                        <small>

                            Rina Wulandari

                        </small>

                    </div>

                </td>

                <td></td>

                <td>

                    <div class="rounded p-2 text-start bg-primary-subtle border-start border-4 border-primary">

                        <strong>

                            HSK Prep

                        </strong>

                        <br>

                        Hudie

                        <br>

                        <small>

                            Ahmad Fauzi

                        </small>

                    </div>

                </td>

                <td></td>

                <td>

                    <div class="rounded p-2 text-start bg-info-subtle border-start border-4 border-info">

                        <strong>

                            Daily Activity

                        </strong>

                        <br>

                        Jianer

                        <br>

                        <small>

                            Budi Santoso

                        </small>

                    </div>

                </td>

            </tr>



            <!-- 15.00 -->

            <tr style="height:95px;">

                <td class="fw-bold">

                    15.00

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-danger-subtle border-start border-4 border-danger">

                        <strong>

                            Business Chinese

                        </strong>

                        <br>

                        Feixiang

                        <br>

                        <small>

                            Ahmad Fauzi

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-warning-subtle border-start border-4 border-warning">

                        <strong>

                            Daily Activity

                        </strong>

                        <br>

                        Maochong

                        <br>

                        <small>

                            Rina Wulandari

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start"

                        style="background:#ede9fe;border-left:5px solid #7c3aed;">

                        <strong>

                            Traveling Chinese

                        </strong>

                        <br>

                        Feixiang

                        <br>

                        <small>

                            Siti Nurhaliza

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-primary-subtle border-start border-4 border-primary">

                        <strong>

                            Private Class

                        </strong>

                        <br>

                        Jianer

                        <br>

                        <small>

                            Budi Santoso

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start"

                        style="background:#ede9fe;border-left:5px solid #7c3aed;">

                        <strong>

                            HSK Prep

                        </strong>

                        <br>

                        Hudie

                        <br>

                        <small>

                            Rina Wulandari

                        </small>

                    </div>

                </td>

            </tr>



            <!-- 16.30 -->

            <tr style="height:95px;">

                <td class="fw-bold">

                    16.30

                </td>

                <td></td>

                <td>

                    <div class="rounded p-2 text-start"

                        style="background:#ede9fe;border-left:5px solid #7c3aed;">

                        <strong>

                            HSK Prep

                        </strong>

                        <br>

                        Jianer B

                        <br>

                        <small>

                            Ahmad Fauzi

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-warning-subtle border-start border-4 border-warning">

                        <strong>

                            Daily Activity

                        </strong>

                        <br>

                        Jianer

                        <br>

                        <small>

                            Budi Santoso

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-primary-subtle border-start border-4 border-primary">

                        <strong>

                            Native Speaker

                        </strong>

                        <br>

                        Hudie

                        <br>

                        <small>

                            Siti Nurhaliza

                        </small>

                    </div>

                </td>

                <td>

                    <div class="rounded p-2 text-start bg-danger-subtle border-start border-4 border-danger">

                        <strong>

                            Business Chinese

                        </strong>

                        <br>

                        Feixiang

                        <br>

                        <small>

                            Ahmad Fauzi

                        </small>

                    </div>

                </td>

            </tr>

            </tbody>

        </table>

    </div>

</div>

<!-- =========================
        RINGKASAN GURU
        (PART 3)
========================== -->
<!-- =========================
        RINGKASAN GURU
========================== -->

<div class="row mb-5">

    <!-- Guru 1 -->

    <div class="col-lg-4 mb-3">

        <div class="card shadow-sm border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h5 class="fw-bold mb-1">

                            Rina Wulandari

                        </h5>

                        <small class="text-muted">

                            Program Jianer

                        </small>

                    </div>

                    <span class="badge bg-success">

                        Aktif

                    </span>

                </div>

                <hr>

                <div class="row text-center">

                    <div class="col-4">

                        <h4 class="fw-bold text-primary">

                            24

                        </h4>

                        <small>Jam</small>

                    </div>

                    <div class="col-4">

                        <h4 class="fw-bold text-success">

                            12

                        </h4>

                        <small>Sesi</small>

                    </div>

                    <div class="col-4">

                        <h4 class="fw-bold text-warning">

                            96%

                        </h4>

                        <small>Hadir</small>

                    </div>

                </div>

                <hr>

                <p class="mb-2">

                    LP & PPT

                </p>

                <div class="progress mb-3">

                    <div class="progress-bar bg-success"

                         style="width:100%">

                    </div>

                </div>

                <span class="badge bg-success">

                    Semua Lengkap

                </span>

            </div>

        </div>

    </div>



    <!-- Guru 2 -->

    <div class="col-lg-4 mb-3">

        <div class="card shadow-sm border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h5 class="fw-bold mb-1">

                            Budi Santoso

                        </h5>

                        <small class="text-muted">

                            Program Maochong

                        </small>

                    </div>

                    <span class="badge bg-primary">

                        Aktif

                    </span>

                </div>

                <hr>

                <div class="row text-center">

                    <div class="col-4">

                        <h4 class="fw-bold text-primary">

                            20

                        </h4>

                        <small>Jam</small>

                    </div>

                    <div class="col-4">

                        <h4 class="fw-bold text-success">

                            10

                        </h4>

                        <small>Sesi</small>

                    </div>

                    <div class="col-4">

                        <h4 class="fw-bold text-warning">

                            90%

                        </h4>

                        <small>Hadir</small>

                    </div>

                </div>

                <hr>

                <p class="mb-2">

                    LP & PPT

                </p>

                <div class="progress mb-3">

                    <div class="progress-bar bg-warning"

                         style="width:70%">

                    </div>

                </div>

                <span class="badge bg-warning text-dark">

                    Menunggu Upload

                </span>

            </div>

        </div>

    </div>



    <!-- Guru 3 -->

    <div class="col-lg-4 mb-3">

        <div class="card shadow-sm border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <h5 class="fw-bold mb-1">

                            Ahmad Fauzi

                        </h5>

                        <small class="text-muted">

                            Program Feixiang

                        </small>

                    </div>

                    <span class="badge bg-danger">

                        Perlu Perhatian

                    </span>

                </div>

                <hr>

                <div class="row text-center">

                    <div class="col-4">

                        <h4 class="fw-bold text-primary">

                            18

                        </h4>

                        <small>Jam</small>

                    </div>

                    <div class="col-4">

                        <h4 class="fw-bold text-success">

                            8

                        </h4>

                        <small>Sesi</small>

                    </div>

                    <div class="col-4">

                        <h4 class="fw-bold text-danger">

                            78%

                        </h4>

                        <small>Hadir</small>

                    </div>

                </div>

                <hr>

                <p class="mb-2">

                    LP & PPT

                </p>

                <div class="progress mb-3">

                    <div class="progress-bar bg-danger"

                         style="width:40%">

                    </div>

                </div>

                <span class="badge bg-danger">

                    Belum Upload

                </span>

            </div>

        </div>

    </div>

</div>



<!-- =========================
        FILTER DATA
        (PART 4)
========================== -->
<!-- =========================
        FILTER
========================== -->

<div class="card shadow-sm border-0 mb-4">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">

            Filter Monitoring Guru

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-3">

            <!-- Cari Guru -->

            <div class="col-lg-3">

                <label class="form-label">

                    Nama Guru

                </label>

                <input type="text"
                       class="form-control"
                       placeholder="Cari nama guru...">

            </div>


            <!-- Program -->

            <div class="col-lg-2">

                <label class="form-label">

                    Program

                </label>

                <select class="form-select">

                    <option>Semua Program</option>
                    <option>Maochong</option>
                    <option>Jianer</option>
                    <option>Hudie</option>
                    <option>Feixiang</option>

                </select>

            </div>


            <!-- Status -->

            <div class="col-lg-2">

                <label class="form-label">

                    Status Guru

                </label>

                <select class="form-select">

                    <option>Semua Status</option>
                    <option>Aktif</option>
                    <option>Cuti</option>
                    <option>Pengganti</option>

                </select>

            </div>


            <!-- Hari -->

            <div class="col-lg-2">

                <label class="form-label">

                    Hari

                </label>

                <select class="form-select">

                    <option>Semua Hari</option>
                    <option>Senin</option>
                    <option>Selasa</option>
                    <option>Rabu</option>
                    <option>Kamis</option>
                    <option>Jumat</option>

                </select>

            </div>


            <!-- Tanggal -->

            <div class="col-lg-2">

                <label class="form-label">

                    Tanggal

                </label>

                <input type="date"
                       class="form-control">

            </div>


            <!-- Tombol -->

            <div class="col-lg-1 d-flex align-items-end">

                <button class="btn btn-primary w-100">

                    Filter

                </button>

            </div>

        </div>

    </div>

</div>



<!-- =========================
        QUICK SUMMARY
========================== -->

<div class="row mb-4">

    <div class="col-lg-3 mb-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-muted">

                    Guru Hadir Hari Ini

                </small>

                <h2 class="fw-bold text-success mt-2">

                    16

                </h2>

            </div>

        </div>

    </div>


    <div class="col-lg-3 mb-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-muted">

                    Tidak Hadir

                </small>

                <h2 class="fw-bold text-danger mt-2">

                    2

                </h2>

            </div>

        </div>

    </div>


    <div class="col-lg-3 mb-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-muted">

                    LP Belum Upload

                </small>

                <h2 class="fw-bold text-warning mt-2">

                    5

                </h2>

            </div>

        </div>

    </div>


    <div class="col-lg-3 mb-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-muted">

                    Progress Report Pending

                </small>

                <h2 class="fw-bold text-primary mt-2">

                    3

                </h2>

            </div>

        </div>

    </div>

</div>



<!-- =========================
        TABEL MONITORING
        (PART 5)
========================== -->
<div class="card shadow-sm border-0">

    <div class="card-header bg-white">

        <div class="d-flex justify-content-between align-items-center">

            <h5 class="mb-0 fw-bold">

                Daftar Monitoring Guru

            </h5>

            <span class="badge bg-primary">

                18 Guru

            </span>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-light">

            <tr>

                <th>No</th>
                <th>Guru</th>
                <th>Program</th>
                <th>Kelas</th>
                <th>Jam</th>
                <th>Kehadiran</th>
                <th>LP & PPT</th>
                <th>Progress Report</th>
                <th>Status</th>
                <th width="220">Aksi</th>

            </tr>

            </thead>

            <tbody>

            <!-- ==================== -->

            <tr>

                <td>1</td>

                <td>

                    <strong>Rina Wulandari</strong>

                    <br>

                    <small class="text-muted">

                        Guru Tetap

                    </small>

                </td>

                <td>Jianer</td>

                <td>2B</td>

                <td>

                    <span class="badge bg-info">

                        24 Jam

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Hadir

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Lengkap

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Sudah

                    </span>

                </td>

                <td>

                    <span class="badge bg-primary">

                        Aktif

                    </span>

                </td>

                <td>

                    <button class="btn btn-info btn-sm">

                        Detail

                    </button>

                    <button class="btn btn-warning btn-sm">

                        Edit

                    </button>

                    <button class="btn btn-success btn-sm">

                        Jadwal

                    </button>

                </td>

            </tr>

            <!-- ==================== -->

            <tr>

                <td>2</td>

                <td>

                    <strong>Budi Santoso</strong>

                    <br>

                    <small class="text-muted">

                        Guru Tetap

                    </small>

                </td>

                <td>Maochong</td>

                <td>3A</td>

                <td>

                    <span class="badge bg-info">

                        20 Jam

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Hadir

                    </span>

                </td>

                <td>

                    <span class="badge bg-warning text-dark">

                        Pending

                    </span>

                </td>

                <td>

                    <span class="badge bg-warning text-dark">

                        Pending

                    </span>

                </td>

                <td>

                    <span class="badge bg-primary">

                        Aktif

                    </span>

                </td>

                <td>

                    <button class="btn btn-info btn-sm">

                        Detail

                    </button>

                    <button class="btn btn-warning btn-sm">

                        Edit

                    </button>

                    <button class="btn btn-success btn-sm">

                        Jadwal

                    </button>

                </td>

            </tr>

            <!-- ==================== -->

            <tr>

                <td>3</td>

                <td>

                    <strong>Siti Nurhaliza</strong>

                    <br>

                    <small class="text-muted">

                        Guru Pengganti

                    </small>

                </td>

                <td>Hudie</td>

                <td>1A</td>

                <td>

                    <span class="badge bg-info">

                        18 Jam

                    </span>

                </td>

                <td>

                    <span class="badge bg-danger">

                        Tidak Hadir

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Lengkap

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Sudah

                    </span>

                </td>

                <td>

                    <span class="badge bg-secondary">

                        Cuti

                    </span>

                </td>

                <td>

                    <button class="btn btn-info btn-sm">

                        Detail

                    </button>

                    <button class="btn btn-warning btn-sm">

                        Edit

                    </button>

                    <button class="btn btn-success btn-sm">

                        Jadwal

                    </button>

                </td>

            </tr>

            <!-- ==================== -->

            <tr>

                <td>4</td>

                <td>

                    <strong>Ahmad Fauzi</strong>

                    <br>

                    <small class="text-muted">

                        Guru Tetap

                    </small>

                </td>

                <td>Feixiang</td>

                <td>1C</td>

                <td>

                    <span class="badge bg-info">

                        18 Jam

                    </span>

                </td>

                <td>

                    <span class="badge bg-success">

                        Hadir

                    </span>

                </td>

                <td>

                    <span class="badge bg-danger">

                        Belum Upload

                    </span>

                </td>

                <td>

                    <span class="badge bg-danger">

                        Belum Upload

                    </span>

                </td>

                <td>

                    <span class="badge bg-primary">

                        Aktif

                    </span>

                </td>

                <td>

                    <button class="btn btn-info btn-sm">

                        Detail

                    </button>

                    <button class="btn btn-warning btn-sm">

                        Edit

                    </button>

                    <button class="btn btn-success btn-sm">

                        Jadwal

                    </button>

                </td>

            </tr>

            </tbody>

        </table>

    </div>

    <div class="card-footer bg-white">

        <div class="d-flex justify-content-between align-items-center">

            <small class="text-muted">

                Menampilkan 1 - 4 dari 18 data guru

            </small>

            <nav>

                <ul class="pagination pagination-sm mb-0">

                    <li class="page-item disabled">

                        <a class="page-link">

                            Previous

                        </a>

                    </li>

                    <li class="page-item active">

                        <a class="page-link">

                            1

                        </a>

                    </li>

                    <li class="page-item">

                        <a class="page-link">

                            2

                        </a>

                    </li>

                    <li class="page-item">

                        <a class="page-link">

                            3

                        </a>

                    </li>

                    <li class="page-item">

                        <a class="page-link">

                            Next

                        </a>

                    </li>

                </ul>

            </nav>

        </div>

    </div>

</div>

<!-- =========================
        CSS
        (PART 6)
========================== -->
@push('styles')

<style>

body{
    background:#f5f7fb;
}

.card{
    border:none;
    border-radius:15px;
    overflow:hidden;
}

.card-header{
    background:#fff;
    font-weight:600;
}

.table thead th{

    background:#f8fafc;

    font-size:14px;

    font-weight:600;

    color:#555;

    text-align:center;

    vertical-align:middle;

}

.table tbody td{

    vertical-align:middle;

}

.table-hover tbody tr:hover{

    background:#f7fbff;

}

.badge{

    padding:8px 12px;

    font-size:12px;

    border-radius:20px;

}

.progress{

    height:8px;

    border-radius:20px;

}

.btn{

    border-radius:8px;

}

.btn-sm{

    padding:5px 12px;

}

.table-responsive{

    overflow-x:auto;

}

.table td{

    white-space:nowrap;

}

.rounded{

    border-radius:12px !important;

}

.border-start{

    border-left-width:5px !important;

}

.card-body h2{

    font-weight:700;

}

.card-body h4{

    font-weight:700;

}

.pagination .page-link{

    border-radius:8px;

    margin:0 2px;

}

.table tbody tr{

    transition:.2s;

}

.table tbody tr:hover{

    transform:scale(1.002);

}

</style>

@endpush

</div>

@endsection