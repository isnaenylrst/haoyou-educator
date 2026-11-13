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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer" />

    {{-- Bootstrap (dipakai oleh halaman admin: grid, card, dropdown notifikasi, dll) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- CSS Layout Admin (HARUS setelah Bootstrap agar tidak tertimpa) --}}
    <link rel="stylesheet" href="{{ asset('css/admin/layout.css') }}">

    {{-- CSS Tambahan dari halaman --}}
    @stack('styles')

</head>

<body>

@php
    // ====== DATA USER YANG LOGIN ======
    $authUser   = auth()->user();
    $userName   = $authUser->teacher->name ?? $authUser->username ?? 'Admin';
    $initials   = strtoupper(mb_substr($authUser->username ?? $userName, 0, 2));
    $profileUrl = \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : '#';

    // Nama tampilan (dari Profil & Tanda Tangan) dan nama role; cadangan bila accessor belum ada
    $displayName = (method_exists($authUser, 'getDisplayNameAttribute') || method_exists($authUser, 'displayName'))
        ? ($authUser->display_name ?: $userName)
        : $userName;
    $levelName   = $authUser->level->nama_level ?? 'Administrator';
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

                {{-- <a href="{{ route('admin.request-belajar.index') }}" class="nav-item {{ request()->routeIs('admin.request-belajar.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open-reader nav-icon" title="Request Belajar Siswa"></i>
                    <span class="label">Request Belajar</span>
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

                <a href="{{ route('admin.progress-report') }}" class="nav-item {{ request()->routeIs('admin.progress-report*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line nav-icon" title="Progress Report"></i>
                    <span class="label">Progress Report</span>
                </a>

                <a href="{{ route('admin.kurikulum-materi') }}" class="nav-item {{ request()->routeIs('admin.kurikulum-materi*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book nav-icon" title="Kurikulum & Materi"></i>
                    <span class="label">Materi</span>
                </a>
            </div>

            {{-- PENGAJAR --}}
            <div class="nav-group">
                <div class="nav-group-label">Pengajar</div>

                <a href="{{ route('admin.guru.index') }}" class="nav-item {{ request()->routeIs('admin.guru.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-chalkboard-user nav-icon" title="Guru"></i>
                    <span class="label">Kurikulum & Guru</span>
                </a>
            </div>

            {{-- KEUANGAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Keuangan</div>

                <a href="{{ route('admin.pembayaran') }}" class="nav-item {{ request()->routeIs('admin.pembayaran*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet nav-icon" title="Pembayaran"></i>
                    <span class="label">Pembayaran</span>
                </a>

                <a href="{{ route('admin.pengajuan.index') }}"
                class="nav-item {{ request()->routeIs('admin.pengajuan*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt nav-icon" title="Rekap Pengajuan"></i>
                    <span class="label">Rekap Pengajuan</span>
                </a>
            </div>

            {{-- PENGATURAN --}}
            <div class="nav-group">
                <div class="nav-group-label">Lainnya</div>

                <a href="{{ route('admin.program-level') }}" class="nav-item {{ request()->routeIs('admin.program-level*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group nav-icon" title="Program & Level"></i>
                    <span class="label">Program & Level</span>
                </a>

                <a href="{{ route('admin.dokumen') }}" class="nav-item {{ request()->routeIs('admin.dokumen*', 'admin.template-dokumen.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines nav-icon" title="Dokumen"></i>
                    <span class="label">Dokumen</span>
                </a>

                <a href="{{ route('admin.training.index') }}" class="nav-item {{ request()->routeIs('admin.training*') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap nav-icon" title="Materi Training"></i>
                    <span class="label">SOP Admin</span>
                </a>

                {{-- <a href="{{ route('admin.pengaturan.index') }}" class="nav-item {{ request()->routeIs('admin.pengaturan.*', 'admin.template-dokumen.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear nav-icon" title="Pengaturan"></i>
                    <span class="label">Pengaturan</span>
                </a> --}}
            </div>

        </nav>

        {{-- Simpan & pulihkan posisi scroll sidebar supaya tidak lompat ke atas setiap pindah halaman --}}
        <script>
            (function () {
                var nav = document.querySelector('.sidebar-nav');
                if (!nav) return;
                var KEY = 'adminSidebarScroll';

                function restore() {
                    try {
                        var y = sessionStorage.getItem(KEY);
                        if (y !== null) nav.scrollTop = parseInt(y, 10) || 0;
                    } catch (e) {}

                    // pastikan menu aktif tetap terlihat (mis. saat pertama kali dibuka)
                    var active = nav.querySelector('.nav-item.active');
                    if (active) {
                        var n = nav.getBoundingClientRect(), a = active.getBoundingClientRect();
                        if (a.top < n.top || a.bottom > n.bottom) {
                            active.scrollIntoView({ block: 'nearest' });
                        }
                    }
                }

                restore();                                   // sebelum halaman digambar (tanpa kedip)
                window.addEventListener('load', restore);    // ulangi setelah font/ikon selesai dimuat

                nav.addEventListener('scroll', function () {
                    try { sessionStorage.setItem(KEY, nav.scrollTop); } catch (e) {}
                }, { passive: true });
            })();
        </script>

        {{-- SIDEBAR FOOTER --}}
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">{{ $initials }}</div>

                <div class="user-meta">
                    <div class="name">{{ $displayName }}</div>
                    <div class="role">{{ $levelName }}</div>
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

            <div class="topbar-actions d-flex align-items-center gap-2">
                {{-- Notifikasi (Laravel Database Notification) --}}
                <div class="dropdown">
                    <button class="topbar-button notification position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
                        <i class="fa-regular fa-bell topbar-icon"></i>
                        @if(($unreadNotificationsCount ?? 0) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;">
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow rounded-4" style="width:340px; max-height:450px; overflow-y:auto;">
                        <div class="px-3 py-2 fw-bold border-bottom d-flex justify-content-between align-items-center bg-light">
                            <span>Notifikasi @if(($unreadNotificationsCount ?? 0) > 0)<span class="badge bg-warning text-dark ms-1">{{ $unreadNotificationsCount }}</span>@endif</span>
                            @if(($unreadNotificationsCount ?? 0) > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" style="font-size:11px;">Tandai semua dibaca</button>
                                </form>
                            @endif
                        </div>

                        @if(!empty($unreadNotifications) && $unreadNotifications->count() > 0)
                            @foreach($unreadNotifications as $notif)
                                <a class="notif-item border-bottom d-flex align-items-start gap-2 text-decoration-none text-dark p-2" href="{{ route('notifications.read', $notif->id) }}">
                                    <div class="badge-num mt-1"><i class="fa-solid {{ $notif->data['icon'] ?? 'fa-bell' }}"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small">{{ $notif->data['title'] ?? 'Pemberitahuan' }}</div>
                                        <div class="text-muted" style="font-size:12px; line-height:1.2;">{{ Str::limit($notif->data['message'] ?? '', 75) }}</div>
                                        <div class="text-secondary" style="font-size:10px;">{{ $notif->created_at->diffForHumans() }}</div>
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div class="p-4 text-center text-muted small">
                                <i class="fa-regular fa-bell-slash fs-4 d-block mb-2 text-secondary"></i>
                                Tidak ada notifikasi baru 🎉
                            </div>
                        @endif
                    </div>
                </div>

                <div class="topbar-divider"></div>

                {{-- Profil + Logout (dropdown manual, tidak bergantung Bootstrap JS) --}}
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="topbar-profile" id="profileToggle" role="button" tabindex="0" aria-expanded="false">
                        <div class="profile-avatar">{{ $initials }}</div>
                        <div class="profile-info">
                            <strong>{{ $displayName }}</strong>
                            <span>{{ $levelName }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-down profile-caret"></i>
                    </div>

                    <div class="profile-menu" id="profileMenu">
                        <div class="profile-menu-head">
                            Masuk sebagai <strong>{{ $authUser->username ?? $userName }}</strong>
                        </div>

                        <div class="profile-menu-section">
                            <a href="{{ route('profil.index') }}" class="profile-menu-item">
                                <i class="fa-solid fa-user" style="color:#f5b700;"></i> Profil
                            </a>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="profile-menu-section">
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
            @include('partials.owner-bar')
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
                const collapsed = app.classList.toggle('collapsed');
                collapseButton.textContent = collapsed ? '›' : '‹';
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

{{-- Bootstrap JS (untuk data-bs-toggle="dropdown", alert dismiss, dll) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Global JavaScript (Opsional) --}}
@if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}"></script>
@endif

{{-- JavaScript tambahan dari halaman --}}
@stack('scripts')

</body>

</html>