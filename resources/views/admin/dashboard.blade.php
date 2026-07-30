@extends('admin.app')


@section('title', 'Dashboard | Haoyou Educator')


@push('styles')

<link
    rel="stylesheet"
    href="{{ asset('/css/admin/dashboard.css') }}"
>

@endpush


@section('content')


{{-- =====================================================
    HEADER
===================================================== --}}

<div class="dashboard-header">

    <div>

        <div class="eyebrow">
            RINGKASAN
        </div>

        <h1>
            Selamat datang kembali, Isnaeny 👋
        </h1>

        <p>
            Berikut ringkasan operasional Haoyou Educator hari ini,
            Selasa 14 Juli 2026.
        </p>

    </div>


    <div class="dashboard-header-actions">

        <button class="btn btn-secondary">
            ↗ &nbsp; Lihat Laporan
        </button>

        <button class="btn btn-primary">
            + &nbsp; Tambah Lead
        </button>

    </div>

</div>



{{-- =====================================================
    QUICK ACTIONS
===================================================== --}}

<div class="quick-actions">


    <div class="quick-card">

        <div class="quick-icon yellow">
            +
        </div>

        <div>

            <strong>
                Tambah Lead
            </strong>

            <span>
                Input calon siswa baru
            </span>

        </div>

    </div>


    <div class="quick-card">

        <div class="quick-icon yellow">
            ▤
        </div>

        <div>

            <strong>
                Buat Invoice
            </strong>

            <span>
                Tagihan baru / termin
            </span>

        </div>

    </div>


    <div class="quick-card">

        <div class="quick-icon yellow">
            ◷
        </div>

        <div>

            <strong>
                Jadwalkan Trial
            </strong>

            <span>
                Atur tanggal, jam, guru
            </span>

        </div>

    </div>


    <div class="quick-card">

        <div class="quick-icon yellow">
            ▤
        </div>

        <div>

            <strong>
                Tambah Kelas
            </strong>

            <span>
                Buat kelas baru
            </span>

        </div>

    </div>


</div>



{{-- =====================================================
    PRIORITY
===================================================== --}}

<div class="priority-card">

    <div class="priority-title">

        <span class="danger-icon">
            ⚠
        </span>

        <strong>
            Prioritas Hari Ini
        </strong>

        <span>
            2 hal paling mendesak · lihat semua di ikon 🔔 Notifikasi
        </span>

    </div>


    <div class="priority-items">


        <div class="priority-item">

            <div class="priority-icon red">
                ▤
            </div>

            <div class="priority-text">

                <strong>
                    3 Bukti pembayaran belum diverifikasi
                </strong>

                <span>
                    Uang sudah masuk, tinggal dikonfirmasi Admin.
                </span>

            </div>

            <button>
                Verifikasi
            </button>

        </div>


        <div class="priority-item">

            <div class="priority-icon yellow">
                ▤
            </div>

            <div class="priority-text">

                <strong>
                    2 Jadwal bentrok
                </strong>

                <span>
                    Guru/ruangan dobel booking, harus diatur ulang.
                </span>

            </div>

            <button>
                Lihat Detail
            </button>

        </div>


    </div>

</div>



{{-- =====================================================
    STATISTICS
===================================================== --}}

<div class="stats-grid">


    <div class="stat-card blue">

        <div class="stat-header">

            <span>
                Chat Masuk Hari Ini
            </span>

            <span class="stat-icon">
                ⌕
            </span>

        </div>

        <strong>
            14
        </strong>

        <small class="positive">
            ↑ 4 dibanding kemarin
        </small>

    </div>


    <div class="stat-card red">

        <div class="stat-header">

            <span>
                Prioritas Follow Up
            </span>

            <span class="stat-icon">
                ♧
            </span>

        </div>

        <strong>
            33
        </strong>

        <small class="negative">
            Client baru, trial, & pembayaran
        </small>

    </div>


    <div class="stat-card green">

        <div class="stat-header">

            <span>
                Siswa Aktif
            </span>

            <span class="stat-icon">
                ♧
            </span>

        </div>

        <strong>
            312
        </strong>

        <small class="positive">
            ↑ 8 siswa baru bulan ini
        </small>

    </div>


    <div class="stat-card blue">

        <div class="stat-header">

            <span>
                Waiting Trial
            </span>

            <span class="stat-icon">
                ◷
            </span>

        </div>

        <strong>
            9
        </strong>

        <small>
            3 dijadwalkan minggu ini
        </small>

    </div>


    <div class="stat-card purple">

        <div class="stat-header">

            <span>
                Menunggu Jadwal Kelas
            </span>

            <span class="stat-icon">
                ▤
            </span>

        </div>

        <strong>
            4
        </strong>

        <small>
            Sudah bayar, belum ada kelas
        </small>

    </div>


    <div class="stat-card yellow">

        <div class="stat-header">

            <span>
                Kelas Berjalan Hari Ini
            </span>

            <span class="stat-icon">
                ▤
            </span>

        </div>

        <strong>
            38
        </strong>

        <small>
            4 sedang berlangsung sekarang
        </small>

    </div>


</div>



{{-- =====================================================
    ANALYTICS
===================================================== --}}

<div class="dashboard-two-column">


    {{-- FUNNEL --}}

    <div class="dashboard-card funnel-card">

        <div class="card-header">

            <div>

                <h3>
                    Lead Conversion Funnel
                </h3>

                <p>
                    Inquiry → Warm → Trial → Payment → Student
                </p>

            </div>


            <div class="period-tabs">

                <button class="active">
                    30 Hari
                </button>

                <button>
                    90 Hari
                </button>

            </div>

        </div>


        <div class="funnel-list">


            <div class="funnel-row">

                <span>
                    Inquiry
                </span>

                <div class="funnel-bar">

                    <div
                        style="width: 100%"
                        class="funnel-fill gray"
                    ></div>

                </div>

                <strong>
                    128
                </strong>

            </div>


            <div class="funnel-row">

                <span>
                    Warm Lead
                </span>

                <div class="funnel-bar">

                    <div
                        style="width: 68%"
                        class="funnel-fill yellow"
                    ></div>

                </div>

                <strong>
                    87
                </strong>

            </div>


            <div class="funnel-row">

                <span>
                    Trial
                </span>

                <div class="funnel-bar">

                    <div
                        style="width: 38%"
                        class="funnel-fill blue"
                    ></div>

                </div>

                <strong>
                    49
                </strong>

            </div>


            <div class="funnel-row">

                <span>
                    Payment
                </span>

                <div class="funnel-bar">

                    <div
                        style="width: 29%"
                        class="funnel-fill gold"
                    ></div>

                </div>

                <strong>
                    37
                </strong>

            </div>


            <div class="funnel-row">

                <span>
                    Student
                </span>

                <div class="funnel-bar">

                    <div
                        style="width: 24%"
                        class="funnel-fill green"
                    ></div>

                </div>

                <strong>
                    31
                </strong>

            </div>


        </div>

    </div>



    {{-- PAYMENT STATUS --}}

    <div class="dashboard-card payment-card">

        <div class="card-header">

            <div>

                <h3>
                    Status Pembayaran
                </h3>

                <p>
                    Bulan berjalan
                </p>

            </div>

        </div>


        <div class="payment-content">

            <div class="donut-chart">

                <div class="donut-hole">

                    <strong>
                        100%
                    </strong>

                </div>

            </div>


            <div class="payment-legend">

                <div>
                    <span class="legend-dot green-dot"></span>
                    Paid — 58%
                </div>

                <div>
                    <span class="legend-dot yellow-dot"></span>
                    Partial — 20%
                </div>

                <div>
                    <span class="legend-dot red-dot"></span>
                    Overdue — 12%
                </div>

                <div>
                    <span class="legend-dot gray-dot"></span>
                    Belum ditagih — 10%
                </div>

            </div>

        </div>

    </div>


</div>



{{-- =====================================================
    PROGRAM + SCHEDULE
===================================================== --}}

<div class="dashboard-two-column">


    <div class="dashboard-card">

        <div class="card-header">

            <div>

                <h3>
                    Program Kelas Aktif
                </h3>

                <p>
                    Kelas mendekati sesi terakhir ditandai khusus
                </p>

            </div>

        </div>


        <div class="program-list">


            <div class="program-item">

                <div class="program-top">

                    <strong>
                        HSK 3 — Batch B
                    </strong>

                    <span class="program-status warning">
                        Akan Berakhir · 2 sesi lagi
                    </span>

                </div>

                <span>
                    Guru Mei Wong · 8 siswa · Offline
                </span>

                <div class="progress">

                    <div
                        style="width: 90%"
                    ></div>

                </div>

                <small>
                    Sesi 18/20
                </small>

            </div>


            <div class="program-item">

                <div class="program-top">

                    <strong>
                        Private — Sinta Nuraini
                    </strong>

                    <span class="program-status warning">
                        Akan Berakhir · 3 sesi lagi
                    </span>

                </div>

                <span>
                    Guru Andi Susanto · 1 siswa · Online
                </span>

                <div class="progress">

                    <div
                        style="width: 75%"
                    ></div>

                </div>

                <small>
                    Sesi 9/12
                </small>

            </div>


            <div class="program-item">

                <div class="program-top">

                    <strong>
                        Reguler Kids — Batch A
                    </strong>

                    <span class="program-status success">
                        ● Berjalan
                    </span>

                </div>

                <span>
                    Guru Chen Li · 10 siswa · Offline
                </span>

                <div class="progress">

                    <div
                        style="width: 40%"
                    ></div>

                </div>

                <small>
                    Sesi 6/20
                </small>

            </div>


            <div class="program-item">

                <div class="program-top">

                    <strong>
                        HSK 1 — Batch A
                    </strong>

                    <span class="program-status gray">
                        Selesai
                    </span>

                </div>

                <span>
                    Guru Mei Wong · 12 siswa · Offline
                </span>

                <div class="progress">

                    <div
                        style="width: 100%"
                    ></div>

                </div>

                <small>
                    Sesi 20/20
                </small>

            </div>


            <div class="program-item">

                <div class="program-top">

                    <strong>
                        Reguler Teen — Batch C
                    </strong>

                    <span class="program-status blue-status">
                        Belum Mulai
                    </span>

                </div>

                <span>
                    Guru Dewi Wulandari · 9 siswa · Online
                </span>

                <div class="progress">

                    <div
                        style="width: 0%"
                    ></div>

                </div>

                <small>
                    Sesi 0/16
                </small>

            </div>


        </div>

    </div>



    {{-- JADWAL --}}

    <div class="dashboard-card">

        <div class="card-header">

            <div>

                <h3>
                    Jadwal Kelas Hari Ini
                </h3>

                <p>
                    8 kelas terjadwal
                </p>

            </div>

            <a href="#">
                Kalender →
            </a>

        </div>


        <div class="schedule-list">


            <div class="schedule-item completed">

                <small>
                    07:00 — 08:00 · completed
                </small>

                <strong>
                    Daily Activity — Maocong
                </strong>

                <span>
                    Ratna · 5/6 hadir · Offline
                </span>

            </div>


            <div class="schedule-item current">

                <small>
                    09:00 — 10:00 · in 19 minutes
                </small>

                <strong>
                    HSK Preparation — Hudie
                </strong>

                <span>
                    Dinda · 8 siswa · Online
                </span>

            </div>


            <div class="schedule-item">

                <small>
                    13:00 — 14:00
                </small>

                <strong>
                    Business Class — Feixiang
                </strong>

                <span>
                    Ratna · 5 siswa · Offline
                </span>

            </div>


            <div class="schedule-item">

                <small>
                    15:00 — 16:00
                </small>

                <strong>
                    Daily Activity — Jianer
                </strong>

                <span>
                    Dinda · 8 siswa · Online
                </span>

            </div>


            <div class="schedule-item">

                <small>
                    16:30 — 17:30
                </small>

                <strong>
                    Private Class — Jianer
                </strong>

                <span>
                    Ratna · 3 siswa · Offline
                </span>

            </div>


        </div>

    </div>


</div>



{{-- =====================================================
    PAYMENT + TEACHER
===================================================== --}}

<div class="dashboard-two-column">


    {{-- PAYMENT DUE --}}

    <div class="dashboard-card">

        <div class="card-header">

            <div>

                <h3>
                    Pembayaran Jatuh Tempo
                </h3>

                <p>
                    Per siswa, diurutkan terdekat
                </p>

            </div>

        </div>


        <div class="payment-due-list">


            <div class="payment-due-item">

                <div class="avatar gray-avatar">
                    SN
                </div>

                <div class="person-info">

                    <strong>
                        Sinta Nuraini
                    </strong>

                    <span>
                        Termin 2/3 · Rp 2.500.000
                    </span>

                </div>

                <span class="due danger">
                    Terlambat 3 hr
                </span>

            </div>


            <div class="payment-due-item">

                <div class="avatar gray-avatar">
                    FH
                </div>

                <div class="person-info">

                    <strong>
                        Farhan Hidayat
                    </strong>

                    <span>
                        Termin 1/3 · Rp 1.800.000
                    </span>

                </div>

                <span class="due danger">
                    Terlambat 1 hr
                </span>

            </div>


            <div class="payment-due-item">

                <div class="avatar gray-avatar">
                    AL
                </div>

                <div class="person-info">

                    <strong>
                        Alya Lestari
                    </strong>

                    <span>
                        Termin 1/3 · Rp 1.800.000
                    </span>

                </div>

                <span class="due warning">
                    Jatuh tempo 2 hr lagi
                </span>

            </div>


            <div class="payment-due-item">

                <div class="avatar gray-avatar">
                    DP
                </div>

                <div class="person-info">

                    <strong>
                        Dewi Putri
                    </strong>

                    <span>
                        Termin 3/3 · Rp 900.000
                    </span>

                </div>

                <span class="due warning">
                    Jatuh tempo 4 hr lagi
                </span>

            </div>


            <div class="payment-due-item">

                <div class="avatar gray-avatar">
                    RH
                </div>

                <div class="person-info">

                    <strong>
                        Rendra Hakim
                    </strong>

                    <span>
                        Termin 2/3 · Rp 1.200.000
                    </span>

                </div>

                <span class="due normal">
                    14 hr lagi
                </span>

            </div>


        </div>


        <button class="full-button">
            Lihat semua tagihan
        </button>

    </div>



    {{-- TEACHER --}}

    <div class="dashboard-card">

        <div class="card-header">

            <div>

                <h3>
                    Guru Tersedia Hari Ini
                </h3>

                <p>
                    Bisa dialokasikan untuk trial / kelas pengganti
                </p>

            </div>

        </div>


        <div class="teacher-list">


            <div class="teacher-item">

                <div class="avatar yellow-avatar">
                    AS
                </div>

                <div class="person-info">

                    <strong>
                        Andi Susanto
                    </strong>

                    <span>
                        Kosong 11:00–15:00
                    </span>

                </div>

                <span class="teacher-status idle">
                    Idle
                </span>

            </div>


            <div class="teacher-item">

                <div class="avatar yellow-avatar">
                    DW
                </div>

                <div class="person-info">

                    <strong>
                        Dewi Wulandari
                    </strong>

                    <span>
                        Kosong sepanjang hari
                    </span>

                </div>

                <span class="teacher-status idle">
                    Idle
                </span>

            </div>


            <div class="teacher-item">

                <div class="avatar gray-avatar">
                    CL
                </div>

                <div class="person-info">

                    <strong>
                        Chen Li
                    </strong>

                    <span>
                        Mengajar sampai 16:30
                    </span>

                </div>

                <span class="teacher-status teaching">
                    Mengajar
                </span>

            </div>


            <div class="teacher-item">

                <div class="avatar gray-avatar">
                    MW
                </div>

                <div class="person-info">

                    <strong>
                        Mei Wong
                    </strong>

                    <span>
                        Mengajar sampai 10:30
                    </span>

                </div>

                <span class="teacher-status teaching">
                    Mengajar
                </span>

            </div>


        </div>

    </div>


</div>


@endsection