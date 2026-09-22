@extends('admin.app')

@section('title', 'Dashboard | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/admin/dashboard.css') }}">
@endpush

@section('content')

    {{-- =====================================================
        HEADER
    ===================================================== --}}
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">RINGKASAN</div>
            <h1>Selamat datang kembali, Isnaeny👋</i></h1>
            <p>Berikut ringkasan operasional Haoyou Educator hari ini, Selasa 14 Juli 2026.</p>
        </div>

        <div class="dashboard-header-actions">
            <button class="btn btn-secondary">
                <i class="fa-solid fa-chart-line"></i> Lihat Laporan
            </button>
            <button class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Lead
            </button>
        </div>
    </div>

    {{-- =====================================================
        QUICK ACTIONS
    ===================================================== --}}
    <div class="quick-actions">
        <div class="quick-card">
            <div class="quick-icon yellow"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <strong>Tambah Lead</strong>
                <span>Input calon siswa baru</span>
            </div>
        </div>

        <div class="quick-card">
            <div class="quick-icon yellow"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div>
                <strong>Buat Invoice</strong>
                <span>Tagihan baru / termin</span>
            </div>
        </div>

        <div class="quick-card">
            <div class="quick-icon yellow"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <strong>Jadwalkan Trial</strong>
                <span>Atur tanggal, jam, guru</span>
            </div>
        </div>

        <div class="quick-card">
            <div class="quick-icon yellow"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <strong>Tambah Kelas</strong>
                <span>Buat kelas baru</span>
            </div>
        </div>
    </div>

    {{-- =====================================================
        PRIORITY
    ===================================================== --}}
    <div class="priority-card">
        <div class="priority-title">
            <span class="danger-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <strong>Prioritas Hari Ini</strong>
            <span>2 hal paling mendesak &middot; lihat semua di ikon <i class="fa-solid fa-bell"></i> Notifikasi</span>
        </div>

        <div class="priority-items">
            <div class="priority-item" style="border-right: 1px solid #F1F2F4;">
                <div class="priority-icon red"><i class="fa-solid fa-money-check-dollar"></i></div>
                <div class="priority-text">
                    <strong>3 Bukti pembayaran belum diverifikasi</strong>
                    <span>Uang sudah masuk, tinggal dikonfirmasi Admin.</span>
                </div>
                <button>Verifikasi</button>
            </div>

            <div class="priority-item">
                <div class="priority-icon yellow"><i class="fa-solid fa-calendar-xmark"></i></div>
                <div class="priority-text">
                    <strong>2 Jadwal bentrok</strong>
                    <span>Guru/ruangan dobel booking, harus diatur ulang.</span>
                </div>
                <button>Lihat Detail</button>
            </div>
        </div>
    </div>

    {{-- =====================================================
        STATISTICS
    ===================================================== --}}
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-header">
                <span>Chat Masuk Hari Ini</span>
                <span class="stat-icon"><i class="fa-solid fa-comments"></i></span>
            </div>
            <strong>14</strong>
            <small class="positive">
                <i class="fa-solid fa-arrow-up"></i> 4 dibanding kemarin
            </small>
        </div>

        <div class="stat-card red">
            <div class="stat-header">
                <span>Prioritas Follow Up</span>
                <span class="stat-icon"><i class="fa-solid fa-phone-volume"></i></span>
            </div>
            <strong>33</strong>
            <small class="negative">Client baru, trial, &amp; pembayaran</small>
        </div>

        <div class="stat-card green">
            <div class="stat-header">
                <span>Siswa Aktif</span>
                <span class="stat-icon"><i class="fa-solid fa-user-graduate"></i></span>
            </div>
            <strong>312</strong>
            <small class="positive">
                <i class="fa-solid fa-arrow-up"></i> 8 siswa baru bulan ini
            </small>
        </div>

        <div class="stat-card blue">
            <div class="stat-header">
                <span>Waiting Trial</span>
                <span class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></span>
            </div>
            <strong>9</strong>
            <small>3 dijadwalkan minggu ini</small>
        </div>

        <div class="stat-card purple">
            <div class="stat-header">
                <span>Menunggu Jadwal Kelas</span>
                <span class="stat-icon"><i class="fa-solid fa-calendar-days"></i></span>
            </div>
            <strong>4</strong>
            <small>Sudah bayar, belum ada kelas</small>
        </div>

        <div class="stat-card yellow">
            <div class="stat-header">
                <span>Kelas Berjalan Hari Ini</span>
                <span class="stat-icon"><i class="fa-solid fa-chalkboard"></i></span>
            </div>
            <strong>38</strong>
            <small>4 sedang berlangsung sekarang</small>
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
                    <h3>Lead Conversion Funnel</h3>
                    <p>Inquiry &rarr; Warm &rarr; Trial &rarr; Payment &rarr; Student</p>
                </div>
                <div class="period-tabs">
                    <button class="active">30 Hari</button>
                    <button>90 Hari</button>
                </div>
            </div>

            <div class="funnel-list">
                <div class="funnel-row">
                    <span class="funnel-name">Inquiry</span>
                    <div class="funnel-bar"><div style="width: 100%" class="funnel-fill gray"></div></div>
                    <strong>128</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Warm Lead</span>
                    <div class="funnel-bar"><div style="width: 68%" class="funnel-fill yellow"></div></div>
                    <strong>87</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Trial</span>
                    <div class="funnel-bar"><div style="width: 38%" class="funnel-fill blue"></div></div>
                    <strong>49</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Payment</span>
                    <div class="funnel-bar"><div style="width: 29%" class="funnel-fill gold"></div></div>
                    <strong>37</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Student</span>
                    <div class="funnel-bar"><div style="width: 24%" class="funnel-fill green"></div></div>
                    <strong>31</strong>
                </div>
            </div>
        </div>

        {{-- PAYMENT STATUS --}}
        <div class="dashboard-card payment-card">
            <div class="card-header">
                <div>
                    <h3>Status Pembayaran</h3>
                    <p>Bulan berjalan</p>
                </div>
            </div>

            <div class="payment-content">
                <div class="donut">
                    <div class="donut-center">
                        <div class="val">Rp205Jt</div>
                        <div class="lbl">Total Invoice</div>
                    </div>
                </div>

                <div class="legend">
                    <div class="legend-item">
                        <span class="legend-dot green-dot"></span>
                        Paid - 58%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot yellow-dot"></span>
                        Partial - 20%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot red-dot"></span>
                        Overdue - 12%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot gray-dot"></span>
                        Belum Ditagih - 10%
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
                    <h3>Program Kelas Aktif</h3>
                    <p>Kelas mendekati sesi terakhir ditandai khusus</p>
                </div>
            </div>

            <div class="cp-lis">
                <div class="cp-row">
                    <div class="cp-head">
                        <div>
                            <div class="cp-title">HSK 3 - Batch B</div>
                            <div class="cp-meta">Guru Mei Wong · 8 siswa · Offline </div>
                        </div>
                        <div class="cp-badges">
                            <span class="class-status class-ending">Akan Berakhir · 2 sesi lagi</span>
                        </div>
                    </div>
                    <div class="cp-track">
                        <div class="cp-fill" style="width: 90%" background:#D9A404;></div>
                    </div>
                    <div class="cp-foot">
                        <span class="cp-sessions">Sesi
                            <b>18/20</b>
                        </span>
                    </div>
                </div>

                <div class="cp-row">
                    <div class="cp-head">
                        <div>
                            <div class="cp-title">Private - Sinta Nuraini</div>
                            <div class="cp-meta">Guru Andi Susanto · 1 siswa · Online </div>
                        </div>
                        <div class="cp-badges">
                            <span class="class-status class-ending">Akan Berakhir · 3 sesi lagi</span>
                        </div>
                    </div>
                    <div class="cp-track">
                        <div class="cp-fill" style="width: 75%"></div>
                    </div>
                    <div class="cp-foot">
                        <span class="cp-sessions">Sesi
                            <b>9/12</b>
                        </span>
                    </div>
                </div>

                <div class="cp-row">
                    <div class="cp-head">
                        <div>
                            <div class="cp-title">Reguler Kids - Batch A</div>
                            <div class="cp-meta">Guru Chen Li · 10 siswa · Offline </div>
                        </div>
                        <div class="cp-badges">
                            <span class="class-status class-live">Berjalan</span>
                        </div>
                    </div>
                    <div class="cp-track">
                        <div class="cp-fill" style="width: 30%"></div>
                    </div>
                    <div class="cp-foot">
                        <span class="cp-sessions">Sesi
                            <b>6/20</b>
                        </span>
                    </div>
                </div>

                <div class="cp-row">
                    <div class="cp-head">
                        <div>
                            <div class="cp-title">HSK 1 — Batch A</div>
                            <div class="cp-meta">Guru Mei Wong · 12 siswa · Offline </div>
                        </div>
                        <div class="cp-badges">
                            <span class="class-status class-done">Selesai</span>
                        </div>
                    </div>
                    <div class="cp-track">
                        <div class="cp-fill" width:100%; background:#16A34A;></div>
                    </div>
                    <div class="cp-foot">
                        <span class="cp-sessions">Sesi
                            <b>20/20</b>
                        </span>
                    </div>
                </div>

                <div class="cp-row">
                    <div class="cp-head">
                        <div>
                            <div class="cp-title">Reguler Teen - Batch C</div>
                            <div class="cp-meta">Guru Dewi Wulandari · 9 siswa · Online </div>
                        </div>
                        <div class="cp-badges">
                            <span class="class-status class-scheduled">Belum Mulai</span>
                        </div>
                    </div>
                    <div class="cp-track">
                        <div class="cp-fill" style="width: 0%"></div>
                    </div>
                    <div class="cp-foot">
                        <span class="cp-sessions">Sesi
                            <b>0/16</b>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- JADWAL --}}
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3>Jadwal Kelas Hari Ini</h3>
                    <p>8 kelas terjadwal</p>
                </div>
                <a href="#">Kalender <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="today-list">
              <div class="today-item completed">
                <div class="today-time">07:00 – 08:00 · completed</div>
                <div class="today-title">Daily Activity — Maochong</div>
                <div class="today-meta">Ratna · 5/6 hadir · <b>Offline</b></div>
              </div>
              <div class="today-item soon">
                <div class="today-time" style="color: #E0A400; font-weight: 800;">09:00 – 10:00 · in 19 minutes</div>
                <div class="today-title">HSK Preparation — Hudie</div>
                <div class="today-meta">Dinda · 8 siswa · <b>Online</b></div>
              </div>
              <div class="today-item">
                <div class="today-time">13:00 – 14:00</div>
                <div class="today-title">Business Class — Feixiang</div>
                <div class="today-meta">Ratna · 5 siswa · <b>Offline</b></div>
              </div>
              <div class="today-item">
                <div class="today-time">15:00 – 16:00</div>
                <div class="today-title">Daily Activity — Jianer</div>
                <div class="today-meta">Dinda · 8 siswa · <b>Online</b></div>
              </div>
              <div class="today-item">
                <div class="today-time">16:30 – 17:30</div>
                <div class="today-title">Private Class — Jianer</div>
                <div class="today-meta">Ratna · 3 siswa · <b>Offline</b></div>
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
                        <h3>Pembayaran Jatuh Tempo</h3>
                        <p>Per siswa, diurutkan terdekat</p>
                    </div>
                </div>

                <div class="panel-list">
              <div class="panel-row">
                <div class="panel-avatar">SN</div>
                <div><div class="panel-title">Sinta Nuraini</div><div class="panel-meta">Termin 2/3 · Rp 2.500.000</div></div>
                <span class="due-badge due-overdue" style="margin-left:auto;">Terlambat 3 hr</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">FH</div>
                <div><div class="panel-title">Farhan Hidayat</div><div class="panel-meta">Termin 1/3 · Rp 1.800.000</div></div>
                <span class="due-badge due-overdue" style="margin-left:auto;">Terlambat 1 hr</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">AL</div>
                <div><div class="panel-title">Alya Lestari</div><div class="panel-meta">Termin 1/3 · Rp 1.800.000</div></div>
                <span class="due-badge due-soon" style="margin-left:auto;">Jatuh tempo 2 hr lagi</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">DP</div>
                <div><div class="panel-title">Dewi Putri</div><div class="panel-meta">Termin 3/3 · Rp 900.000</div></div>
                <span class="due-badge due-soon" style="margin-left:auto;">Jatuh tempo 4 hr lagi</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">RH</div>
                <div><div class="panel-title">Rendra Hakim</div><div class="panel-meta">Termin 2/3 · Rp 1.200.000</div></div>
                <span class="due-badge due-later" style="margin-left:auto;">14 hr lagi</span>
              </div>
            </div>
            <button class="btn btn-ghost" style="width:100%; justify-content:center; margin-top:12px; font-size:12px; padding:8px;">Lihat semua tagihan</button>
            </div>

            {{-- TEACHER --}}
            <div class="dashboard-card">
                <div class="card-header">
                    <div>
                        <h3>Guru Tersedia Hari Ini</h3>
                        <p>Bisa dialokasikan untuk trial / kelas pengganti</p>
                    </div>
                </div>

            <div class="panel-list">
              <div class="panel-row">
                <div class="panel-avatar" style="background:#FFF7E0; color:#92620A;">AS</div>
                <div><div class="panel-title">Andi Susanto</div><div class="panel-meta">Kosong 11:00–15:00</div></div>
                <span class="due-badge class-live" style="margin-left:auto;">Tidak ada jadwal</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar" style="background:#FFF7E0; color:#92620A;">DW</div>
                <div><div class="panel-title">Dewi Wulandari</div><div class="panel-meta">Kosong sepanjang hari</div></div>
                <span class="due-badge class-live" style="margin-left:auto;">Tidak ada jadwal</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar" style="background:#FFF7E0;">CL</div>
                <div><div class="panel-title">Chen Li</div><div class="panel-meta">Mengajar sampai 16:30</div></div>
                <span class="due-badge due-later" style="margin-left:auto;">Mengajar</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar" style="background:#FFF7E0;">MW</div>
                <div><div class="panel-title">Mei Wong</div><div class="panel-meta">Mengajar sampai 10:30</div></div>
                <span class="due-badge due-later" style="margin-left:auto;">Mengajar</span>
              </div>
            </div>
            </div>

        </div>
    </div>

@endsection