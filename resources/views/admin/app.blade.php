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

                <a href="{{ route('admin.calon-siswa.index') }}"class="nav-item {{ request()->routeIs('admin.calon-siswa.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-plus nav-icon" title="Calon Siswa"></i>
                    <span class="label">Calon Siswa</span>
                </a>

                <a href="{{ route('admin.request-belajar.index') }}" class="nav-item {{ request()->routeIs('admin.request-belajar.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open-reader nav-icon" title="Request Belajar Siswa"></i>
                    <span class="label">Request Belajar</span>
                </a>
            </div>

            {{-- AKADEMIK --}}
            <div class="nav-group">
                <div class="nav-group-label">Akademik</div>

                <a href="{{ route('admin.siswa') }}"class="nav-item {{ request()->routeIs('admin.siswa') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap nav-icon" title="Siswa"></i>
                    <span class="label">Siswa</span>
                </a>

                <a href="{{ route('admin.kelas') }}"class="nav-item {{ request()->routeIs('admin.kelas') ? 'active' : '' }}">
                    <i class="fa-solid fa-users nav-icon" title="Kelas"></i>
                    <span class="label">Kelas</span>
                </a>

                <a href="{{ route('admin.jadwal.index') }}"class="nav-item {{ request()->routeIs('admin.jadwal.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days nav-icon" title="Jadwal"></i>
                    <span class="label">Jadwal</span>
                </a>

                <a href="{{ route('admin.jurnal-mengajar') }}"class="nav-item {{ request()->routeIs('admin.jurnal-mengajar*') ? 'active' : '' }}">
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

                <a href="{{ route('admin.pembayaran') }}"
                class="nav-item {{ request()->routeIs('admin.pembayaran*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet nav-icon" title="Pembayaran"></i>
                    <span class="label">Pembayaran</span>
                </a>

                <a href="{{ route('admin.pengajuan.index') }}"
                class="nav-item {{ request()->routeIs('admin.pengajuan*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt nav-icon" title="Rekap Pengajuan"></i>
                    <span class="label">Rekap Pengajuan</span>
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

                <a href="{{ route('admin.program-level') }}"
                class="nav-item {{ request()->routeIs('admin.program-level*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group nav-icon" title="Program & Level"></i>
                    <span class="label">Program & Level</span>
                </a>

                {{-- <a href="{{ route('admin.template-dokumen.index') }}"class="nav-item {{ request()->routeIs('admin.template-dokumen*') ? 'active' : '' }}">
                    <i class="fa-solid fa-print nav-icon" title="Print Dokumen"></i>
                    <span class="label">Print Dokumen</span>
                </a>                 --}}

                <a href="{{ route('admin.pengaturan.index') }}"
                class="nav-item {{ request()->routeIs('admin.pengaturan.*', 'admin.template-dokumen.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear nav-icon" title="Pengaturan"></i>
                    <span class="label">Pengaturan</span>
                </a>
            </div>

        </nav>

        {{-- SIDEBAR FOOTER --}}
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->username ?? 'AD', 0, 2)) }}</div>

                <div class="user-meta">
                    <div class="name">{{ auth()->user()->display_name }}</div>
                    <div class="role">{{ auth()->user()->level->nama_level ?? 'Admin' }}</div>
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

                <div class="dropdown">
                    <div class="topbar-profile d-flex align-items-center gap-2 cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                        <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->username ?? 'AD', 0, 2)) }}</div>
                        <div class="profile-info">
                            <strong>{{ auth()->user()->display_name }}</strong>
                            <span>{{ auth()->user()->level->nama_level ?? 'Administrator' }}</span>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:200px;">
                        <li>
                            <a href="{{ route('profil.index') }}" class="dropdown-item rounded-2 fw-semibold">
                                <i class="fa-solid fa-signature me-2 text-warning"></i> Profil & Tanda Tangan
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-2 text-danger fw-semibold">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
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