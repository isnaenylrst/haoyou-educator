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

    {{-- CSS Dropdown Profil + Logout --}}
    <style>
        .profile-dropdown { position: relative; }
        .topbar-profile { cursor: pointer; user-select: none; }
        .profile-caret { font-size: 11px; color: #8b8fa3; margin-left: 4px; transition: transform .15s; }
        .profile-dropdown.open .profile-caret { transform: rotate(180deg); }

        .profile-menu {
            display: none; position: absolute; right: 0; top: calc(100% + 10px); min-width: 220px;
            background: #fff; border: 1px solid #ececf2; border-radius: 14px; padding: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08); z-index: 1050;
        }
        .profile-dropdown.open .profile-menu { display: block; }

        .profile-menu-head { padding: 8px 12px; font-size: 12px; color: #8b8fa3; }
        .profile-menu-head strong { color: #1d1d27; }

        .profile-menu-item {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px;
            border: 0; background: transparent; border-radius: 10px; text-align: left;
            font: inherit; font-size: 14px; font-weight: 600; color: #1d1d27;
            text-decoration: none; cursor: pointer;
        }
        .profile-menu-item:hover { background: #fff7cc; color: #1d1d27; }
        .profile-menu-item.danger { color: #dc3545; }
        .profile-menu-item.danger:hover { background: #fdecee; color: #dc3545; }
        .profile-menu form { margin: 0; }
    </style>
</head>

<body>

@php
    // ====== DATA USER YANG LOGIN ======
    $authUser   = auth()->user();
    $userName   = $authUser->teacher->name ?? $authUser->username ?? 'Admin';
    $initials   = strtoupper(mb_substr($authUser->username ?? $userName, 0, 2));
    $profileUrl = \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : '#';
@endphp

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
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house nav-icon" title="Dashboard"></i>
                <span class="label">Dashboard</span>
            </a>

            {{-- CRM --}}
            <div class="nav-group">
                <div class="nav-group-label">CRM</div>

                <a href="{{ route('admin.calon-siswa.index') }}" class="nav-item {{ request()->routeIs('admin.calon-siswa.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-plus nav-icon" title="Calon Siswa"></i>
                    <span class="label">Calon Siswa</span>
                </a>

                {{-- <a href="#" class="nav-item">
                    <i class="fa-solid fa-comments nav-icon" title="Follow Up"></i>
                    <span class="label">Follow Up</span>
                </a> --}}
            </div>

            {{-- AKADEMIK --}}
            <div class="nav-group">
                <div class="nav-group-label">Akademik</div>

                <a href="{{ route('admin.siswa') }}" class="nav-item {{ request()->routeIs('admin.siswa') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap nav-icon" title="Siswa"></i>
                    <span class="label">Siswa</span>
                </a>

                <a href="{{ route('admin.kelas') }}" class="nav-item {{ request()->routeIs('admin.kelas') ? 'active' : '' }}">
                    <i class="fa-solid fa-users nav-icon" title="Kelas"></i>
                    <span class="label">Kelas</span>
                </a>

                <a href="{{ route('admin.jadwal.index') }}" class="nav-item {{ request()->routeIs('admin.jadwal.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days nav-icon" title="Jadwal"></i>
                    <span class="label">Jadwal</span>
                </a>

                <a href="{{ route('admin.jurnal-mengajar') }}" class="nav-item {{ request()->routeIs('admin.jurnal-mengajar*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open nav-icon" title="Jurnal Mengajar"></i>
                    <span class="label">Jurnal Mengajar</span>
                </a>

                <a href="{{ route('admin.kurikulum-materi') }}" class="nav-item {{ request()->routeIs('admin.kurikulum-materi*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book nav-icon" title="Kurikulum & Materi"></i>
                    <span class="label">Kurikulum & Materi</span>
                </a>
            </div>

            {{-- PENGAJAR --}}
            <div class="nav-group">
                <div class="nav-group-label">Pengajar</div>

                <a href="{{ route('admin.guru.index') }}" class="nav-item {{ request()->routeIs('admin.guru.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-chalkboard-user nav-icon" title="Guru"></i>
                    <span class="label">Guru</span>
                </a>
            </div>

            {{-- KEUANGAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Keuangan</div>

                <a href="{{ route('admin.pembayaran') }}" class="nav-item {{ request()->routeIs('admin.pembayaran*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet nav-icon" title="Pembayaran"></i>
                    <span class="label">Pembayaran</span>
                </a>
            </div>

            {{-- LAPORAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Laporan</div>

                <a href="{{ route('admin.progress-report') }}" class="nav-item {{ request()->routeIs('admin.progress-report*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line nav-icon" title="Progress Report"></i>
                    <span class="label">Progress Report</span>
                </a>

                <a href="{{ route('admin.dokumen') }}" class="nav-item {{ request()->routeIs('admin.dokumen*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines nav-icon" title="Dokumen"></i>
                    <span class="label">Dokumen</span>
                </a>
            </div>

            {{-- PENGATURAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Pengaturan</div>

                <a href="{{ route('admin.program-level') }}" class="nav-item {{ request()->routeIs('admin.program-level*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group nav-icon" title="Program & Level"></i>
                    <span class="label">Program & Level</span>
                </a>

                {{-- <a href="{{ route('admin.tamplate-dokumen') }}" class="nav-item {{ request()->routeIs('admin.tamplate-dokumen*') ? 'active' : '' }}">
                    <i class="fa-solid fa-print nav-icon" title="Print Dokumen"></i>
                    <span class="label">Print Dokumen</span>
                </a> --}}

                <a href="{{ route('admin.pengaturan.index') }}" class="nav-item {{ request()->routeIs('admin.pengaturan.*', 'admin.template-dokumen.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear nav-icon" title="Pengaturan"></i>
                    <span class="label">Pengaturan</span>
                </a>
            </div>

        </nav>

        {{-- SIDEBAR FOOTER --}}
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">{{ $initials }}</div>

                <div class="user-meta">
                    <div class="name">{{ $userName }}</div>
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

                {{-- Profil + Logout --}}
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="topbar-profile" id="profileToggle" role="button" tabindex="0" aria-expanded="false">
                        <div class="profile-avatar">{{ $initials }}</div>
                        <div class="profile-info">
                            <strong>{{ $userName }}</strong>
                            <span>Administrator</span>
                        </div>
                        <i class="fa-solid fa-chevron-down profile-caret"></i>
                    </div>

                    <div class="profile-menu" id="profileMenu">
                        <div class="profile-menu-head">
                            Masuk sebagai <strong>{{ $authUser->username ?? $userName }}</strong>
                        </div>

                        {{-- <a href="{{ $profileUrl }}" class="profile-menu-item">
                            <i class="fa-regular fa-user"></i> Edit Profil
                        </a> --}}

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-menu-item danger">
                                <i class="fa-solid fa-right-from-bracket"></i> Keluar
                            </button>
                        </form>
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

{{-- Dropdown Profil --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dd = document.getElementById('profileDropdown');
        const toggle = document.getElementById('profileToggle');
        if (!dd || !toggle) return;

        const setOpen = (open) => {
            dd.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            setOpen(!dd.classList.contains('open'));
        });
        toggle.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle.click(); }
        });
        document.addEventListener('click', (e) => { if (!dd.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
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