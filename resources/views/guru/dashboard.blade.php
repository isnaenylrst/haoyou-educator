@extends('layouts.guru')

@section('title','Dashboard Guru')

@section('content')

<style>

.dashboard-title{
    font-size:40px;
    font-weight:700;
    color:#1F2937;
}

.dashboard-subtitle{
    color:#8C8C8C;
    font-size:15px;
}

.btn-add{

    background:#F28C38;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:10px 22px;
    font-weight:600;

}

.btn-add:hover{

    background:#e67f22;
    color:#fff;

}

.stat-card{

    border:1px solid #ECECEC;
    border-radius:18px;
    background:#fff;
    transition:.25s;
    height:100%;

}

.stat-card:hover{

    box-shadow:0 5px 18px rgba(0,0,0,.06);

}

.stat-title{

    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.7px;
    color:#9AA0A6;
    font-weight:700;

}

.stat-number{

    font-size:42px;
    font-weight:700;
    line-height:1;
    color:#1F2937;

}

.stat-info{

    font-size:14px;

}

.section-card{

    border:1px solid #ECECEC;
    border-radius:18px;
    background:#fff;

}

.section-title{

    font-size:28px;
    font-weight:700;

}

.section-subtitle{

    color:#909090;
    font-size:14px;

}

.view-link{

    text-decoration:none;
    font-weight:600;

}

.view-link:hover{

    text-decoration:none;

}

.progress{

    height:6px;
    border-radius:30px;
    background:#ECECEC;

}

.progress-bar{

    border-radius:30px;

}

.class-title{

    font-weight:700;
    font-size:20px;

}

.class-meta{

    color:#888;
    font-size:14px;

}

.badge-outline-success{

    border:1px solid #34C759;
    color:#34C759;
    background:#fff;

}

.badge-outline-secondary{

    border:1px solid #AFAFAF;
    color:#777;
    background:#fff;

}

.badge-outline-primary{

    border:1px solid #2962FF;
    color:#2962FF;
    background:#fff;

}

.badge{

    border-radius:50px;
    padding:7px 13px;
    font-size:11px;
    font-weight:700;

}

.schedule-item{

    border:1px solid #ECECEC;
    border-radius:15px;
    padding:18px;
    margin-bottom:14px;

}

.schedule-active{

    background:#FFF7E3;

}

.schedule-time{

    color:#8F8F8F;
    font-size:13px;

}

.schedule-title{

    font-size:20px;
    font-weight:700;

}

.schedule-meta{

    color:#888;
    font-size:14px;

}

.activity-item{

    padding:20px 0;
    border-bottom:1px solid #EFEFEF;

}

.activity-item:last-child{

    border-bottom:none;

}

.avatar{

    width:42px;
    height:42px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:700;

}

</style>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-start mb-4">

    <div>

        <h2 class="dashboard-title mb-1">
            Good afternoon, Ratna!
        </h2>

        <div class="dashboard-subtitle">
            Haoyou Educator · Les Mandarin Malang · ringkasan operasional hari ini
        </div>

    </div>

    <button class="btn btn-add">

        <i class="fas fa-plus me-2"></i>

        Add Student

    </button>

</div>

<div class="row g-3 mb-4">

    <div class="col-lg-3 col-md-6">

        <div class="card stat-card">

            <div class="card-body">

                <div class="stat-title">

                    TOTAL ACTIVE STUDENTS

                </div>

                <div class="stat-number mt-2">

                    87

                </div>

                <div class="stat-info text-success mt-2">

                    ↑ +5 new students this month

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card stat-card">

            <div class="card-body">

                <div class="stat-title">

                    ACTIVE CLASSES

                </div>

                <div class="stat-number mt-2">

                    14

                </div>

                <div class="stat-info text-secondary mt-2">

                    • Online & Offline

                </div>

            </div>

        </div>

    </div>

</div>

<div class="row g-4">

<div class="col-lg-8">

<div class="card section-card">

    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-4">

            <div>

                <h4 class="fw-bold mb-1">

                    Active Class Progress

                </h4>

                <div class="section-subtitle">

                    Status penyelesaian sesi untuk semua kelas aktif

                </div>

            </div>

            <a href="#" class="view-link">

                View All <i class="fas fa-arrow-right ms-1"></i>

            </a>

        </div>

        <!-- Class 1 -->

        <div class="mb-4">

            <div class="class-title">

                HSK Preparation — Hudie

            </div>

            <div class="class-meta mb-2">

                Ratna • 6 siswa • Offline

            </div>

            <div class="d-flex align-items-center">

                <div class="progress flex-grow-1 me-3">

                    <div class="progress-bar bg-primary"
                         style="width:75%;background:#7C4DFF !important;">

                    </div>

                </div>

                <span class="me-3">

                    15/20

                </span>

                <span class="badge badge-outline-success">

                    ONGOING

                </span>

            </div>

        </div>

        <hr>

        <!-- Class 2 -->

        <div class="mb-4">

            <div class="class-title">

                Daily Activity — Jianer

            </div>

            <div class="class-meta mb-2">

                Dinda • 8 siswa • Online

            </div>

            <div class="d-flex align-items-center">

                <div class="progress flex-grow-1 me-3">

                    <div class="progress-bar"
                         style="width:40%;background:#3F7BFF;">

                    </div>

                </div>

                <span class="me-3">

                    8/20

                </span>

                <span class="badge badge-outline-success">

                    ONGOING

                </span>

            </div>

        </div>

        <hr>

        <!-- Class 3 -->

        <div class="mb-4">

            <div class="class-title">

                Native Speaker — Hudie

            </div>

            <div class="class-meta mb-2">

                Dinda • 4 siswa • Online

            </div>

            <div class="d-flex align-items-center">

                <div class="progress flex-grow-1 me-3">

                    <div class="progress-bar bg-success"
                         style="width:100%;">

                    </div>

                </div>

                <span class="me-3">

                    20/20

                </span>

                <span class="badge badge-outline-secondary">

                    COMPLETED

                </span>

            </div>

        </div>

        <hr>

        <!-- Class 4 -->

        <div>

            <div class="class-title">

                Traveling Class — Feixiang

            </div>

            <div class="class-meta mb-2">

                Dinda • 6 siswa • Online

            </div>

            <div class="d-flex align-items-center">

                <div class="progress flex-grow-1 me-3">

                    <div class="progress-bar bg-light"
                         style="width:0%;">

                    </div>

                </div>

                <span class="me-3">

                    0/16

                </span>

                <span class="badge badge-outline-primary">

                    SCHEDULED

                </span>

            </div>

        </div>

    </div>

</div>

</div>

<div class="col-lg-4">

<div class="card section-card h-100">

    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-4">

            <div>

                <h4 class="fw-bold mb-1">

                    Classes Today

                </h4>

                <div class="section-subtitle">

                    8 classes scheduled

                </div>

            </div>

            <a href="#" class="view-link">

                Calendar <i class="fas fa-arrow-right ms-1"></i>

            </a>

        </div>

        <!-- Item 1 -->

        <div class="schedule-item border-0 bg-white opacity-50">

            <div class="schedule-time">

                07:00 – 08:00 • completed

            </div>

            <div class="schedule-title mt-1">

                Daily Activity — Maochong

            </div>

            <div class="schedule-meta">

                Ratna • 5/6 hadir • Offline

            </div>

        </div>

        <!-- Item 2 -->

        <div class="schedule-item schedule-active border-0">

            <div class="schedule-time text-warning">

                09:00 – 10:00 • in 19 minutes

            </div>

            <div class="schedule-title mt-1">

                HSK Preparation — Hudie

            </div>

            <div class="schedule-meta">

                Dinda • 8 siswa • Online

            </div>

        </div>

        <!-- Item 3 -->

        <div class="schedule-item">

            <div class="schedule-time">

                13:00 – 14:00

            </div>

            <div class="schedule-title mt-1">

                Business Class — Feixiang

            </div>

            <div class="schedule-meta">

                Ratna • 5 siswa • Offline

            </div>

        </div>

        <!-- Item 4 -->

        <div class="schedule-item mb-0">

            <div class="schedule-time">

                15:00 – 16:00

            </div>

            <div class="schedule-title mt-1">

                Daily Activity — Jianer

            </div>

            <div class="schedule-meta">

                Dinda • 8 siswa • Online

            </div>

        </div>

    </div>

</div>

</div>

</div>

<!-- Recent Activity -->

<div class="card section-card mt-4">

    <div class="card-body p-4">

        <h3 class="fw-bold mb-1">

            Recent Activity

        </h3>

        <div class="section-subtitle mb-4">

            Last 30 minutes

        </div>

        <!-- Activity 1 -->

        <div class="activity-item">

            <div class="d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <div class="avatar bg-success">

                        ZA

                    </div>

                    <div class="ms-3">

                        <div class="fw-bold">

                            Sari Dewi attended HSK Preparation — Session 12/20

                        </div>

                        <small class="text-muted">

                            2 minutes ago · Attendance recorded

                        </small>

                    </div>

                </div>

                <div class="text-success fw-semibold">

                    ● Present

                </div>

            </div>

        </div>

        <!-- Activity 2 -->

        <div class="activity-item">

            <div class="d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <div class="avatar bg-danger">

                        RP

                    </div>

                    <div class="ms-3">

                        <div class="fw-bold">

                            Budi Santoso absent from Daily Activity — 2nd absence

                        </div>

                        <small class="text-muted">

                            15 minutes ago · Parent notification sent

                        </small>

                    </div>

                </div>

                <div class="text-danger fw-semibold">

                    ● Absent

                </div>

            </div>

        </div>

        <!-- Activity 3 -->

        <div class="activity-item border-0">

            <div class="d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <div class="avatar"
                         style="background:#C68A00;">

                        JS

                    </div>

                    <div class="ms-3">

                        <div class="fw-bold">

                            Progress report Mid 1 Private Hudie disimpan

                        </div>

                        <small class="text-muted">

                            1 jam lalu · Menunggu review Kurikulum

                        </small>

                    </div>

                </div>

                <div style="color:#C68A00;font-weight:600;">

                    ● Diajukan

                </div>

            </div>

        </div>

    </div>

</div>

</div>

@endsection