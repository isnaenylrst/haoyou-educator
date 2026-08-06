<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | Haoyou Educator</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer" />

    {{-- CSS Layout Admin --}}
    <link rel="stylesheet" href="{{ asset('css/admin/layout.css') }}">

    {{-- CSS Tambahan dari halaman --}}
    @stack('styles')
</head>

<body>

<div class="app" id="adminApp">

    {{-- =====================================================
        SIDEBAR
    ====================================================== --}}
    <aside class="sidebar">

        {{-- BRAND --}}
        <div class="brand">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Haoyou" class="logo">
            {{-- <div class="brand-mark">Haoyou</div> --}}
        </div>

        {{-- NAVIGATION --}}
        <nav class="sidebar-nav">

            {{-- DASHBOARD --}}
            <a href="{{ route('admin.dashboard') }}"class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house nav-icon" title="Dashboard"></i>
                <span class="label">Dashboard</span>
            </a>

            {{-- CRM --}}
            <div class="nav-group">
                <div class="nav-group-label">CRM</div>

                <a href="{{ route('admin.calon-siswa') }}"class="nav-item {{ request()->routeIs('admin.calon-siswa') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-plus nav-icon" title="Calon Siswa"></i>
                    <span class="label">Calon Siswa</span>

                    <span class="nav-badge">24</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-comments nav-icon" title="Follow Up"></i>
                    <span class="label">Follow Up</span>
                </a>
            </div>

            {{-- AKADEMIK --}}
            <div class="nav-group">
                <div class="nav-group-label">Akademik</div>

                <a href="{{ route('admin.siswa') }}"class="nav-item {{ request()->routeIs('admin.siswa') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap nav-icon" title="Siswa"></i>
                    <span class="label">Siswa</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-users nav-icon" title="Kelas"></i>
                    <span class="label">Kelas</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-calendar-days nav-icon" title="Jadwal"></i>
                    <span class="label">Jadwal</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-book-open nav-icon" title="Jurnal Mengajar"></i>
                    <span class="label">Jurnal Mengajar</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-book nav-icon" title="Kurikulu & Materi"></i>
                    <span class="label">Kurikulum & Materi</span>
                </a>
            </div>

            {{-- PENGAJAR --}}
            <div class="nav-group">
                <div class="nav-group-label">Pengajar</div>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-chalkboard-user nav-icon" title="Guru"></i>
                    <span class="label">Guru</span>
                </a>
            </div>

            {{-- KEUANGAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Keuangan</div>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-wallet nav-icon" title="Pembayaran"></i>
                    <span class="label">Pembayaran</span>
                    <span class="nav-badge">7</span>
                </a>
            </div>

            {{-- LAPORAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Laporan</div>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-chart-line nav-icon" title="Progres Report"></i>
                    <span class="label">Progress Report</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-file-lines nav-icon" title="Dokumen"></i>
                    <span class="label">Dokumen</span>
                </a>
            </div>

            {{-- PENGATURAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Pengaturan</div>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-layer-group nav-icon" title="Program & Level"></i>
                    <span class="label">Program & Level</span>
                </a>

                <a href="#" class="nav-item">
                    <i class="fa-solid fa-gear nav-icon" title="Pengaturan"></i>
                    <span class="label">Pengaturan</span>
                </a>
            </div>

        </nav>

        {{-- SIDEBAR FOOTER --}}
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">IE</div>

                <div class="user-meta">
                    <div class="name">Isnaeny</div>
                    <div class="role">Admin</div>
                </div>

                <button type="button" class="collapse-btn" id="sidebarCollapse">‹</button>
            </div>
        </div>

    </aside>

    {{-- =====================================================
        MAIN
    ====================================================== --}}
    <main class="main">

        {{-- TOPBAR --}}
        <header class="topbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Cari siswa, lead, invoice...">
                <small>⌘K</small>
            </div>

            <div class="topbar-actions">
                {{-- Kalender --}}
                <button class="topbar-button" title="Kalender">
                    <i class="fa-solid fa-calendar-check topbar-icon"></i>
                </button>

                {{-- Reminder / Tugas --}}
                <button class="topbar-button notification" title="Reminder">
                    <i class="fa-solid fa-list-check topbar-icon"></i>
                </button>

                {{-- Notifikasi --}}
                <button class="topbar-button notification" title="Notifikasi">
                    <i class="fa-regular fa-bell topbar-icon"></i>
                </button>

                <div class="topbar-divider"></div>

                <div class="topbar-profile">
                    <div class="profile-avatar">IE</div>
                    <div class="profile-info">
                        <strong>Isnaeny</strong>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <section class="page-content">
            @yield('content')
        </section>

    </main>

</div>

{{-- Sidebar Collapse --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const app = document.getElementById('adminApp');
        const collapseButton = document.getElementById('sidebarCollapse');

        if (collapseButton) {
            collapseButton.addEventListener('click', function () {
                app.classList.toggle('collapsed');
            });
        }
    });
</script>

{{-- Global JavaScript (Opsional) --}}
@if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}"></script>
@endif

{{-- JavaScript tambahan dari halaman --}}
@stack('scripts')

</body>

</html>