<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haoyou Educator — Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <title>
        @yield('title', 'Dashboard') | Haoyou Educator
    </title>

    <link rel="stylesheet"
          href="{{ asset('css/admin/layout.css') }}">

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

            <div class="brand-mark">
                好
            </div>

            <div class="brand-text">

                <div class="brand-name">
                    Haoyou Educator
                </div>

                <div class="brand-sub">
                    Admin Panel
                </div>

            </div>

        </div>


        {{-- NAVIGATION --}}
        <nav class="sidebar-nav">

            {{-- DASHBOARD --}}
            <a href="{{ route('admin.dashboard') }}"
               class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">

                <span class="nav-icon">
                    ▦
                </span>

                <span class="label">
                    Dashboard
                </span>

            </a>


            {{-- CRM --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    CRM
                </div>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ♧
                    </span>

                    <span class="label">
                        Calon Siswa
                    </span>

                    <span class="nav-badge">
                        24
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ♧
                    </span>

                    <span class="label">
                        Follow Up
                    </span>

                </a>

            </div>


            {{-- AKADEMIK --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Akademik
                </div>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ♧
                    </span>

                    <span class="label">
                        Siswa
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▣
                    </span>

                    <span class="label">
                        Kelas
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▣
                    </span>

                    <span class="label">
                        Jadwal
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▤
                    </span>

                    <span class="label">
                        Jurnal Mengajar
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▥
                    </span>

                    <span class="label">
                        Kurikulum & Materi
                    </span>

                </a>

            </div>


            {{-- PENGAJAR --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Pengajar
                </div>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ♧
                    </span>

                    <span class="label">
                        Guru
                    </span>

                </a>

            </div>


            {{-- KEUANGAN --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Keuangan
                </div>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▱
                    </span>

                    <span class="label">
                        Pembayaran
                    </span>

                    <span class="nav-badge">
                        7
                    </span>

                </a>

            </div>


            {{-- LAPORAN --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Laporan
                </div>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ⌁
                    </span>

                    <span class="label">
                        Progress Report
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▤
                    </span>

                    <span class="label">
                        Dokumen
                    </span>

                </a>

            </div>


            {{-- PENGATURAN --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Pengaturan
                </div>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ▤
                    </span>

                    <span class="label">
                        Program & Level
                    </span>

                </a>


                <a href="#"
                   class="nav-item">

                    <span class="nav-icon">
                        ⚙
                    </span>

                    <span class="label">
                        Pengaturan
                    </span>

                </a>

            </div>

        </nav>


        {{-- SIDEBAR FOOTER --}}
        <div class="sidebar-footer">

            <div class="user-chip">

                <div class="avatar">
                    IE
                </div>

                <div class="user-meta">

                    <div class="name">
                        Isnaeny
                    </div>

                    <div class="role">
                        Admin
                    </div>

                </div>

                <button type="button"
                        class="collapse-btn"
                        id="sidebarCollapse">

                    ‹

                </button>

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

                <span>
                    ⌕
                </span>

                <input
                    type="text"
                    placeholder="Cari siswa, lead, invoice..."
                >

                <small>
                    ⌘K
                </small>

            </div>


            <div class="topbar-actions">

                <button class="topbar-button">
                    ▣
                </button>

                <button class="topbar-button notification">
                    ♧
                </button>

                <button class="topbar-button notification">
                    ♧
                </button>


                <div class="topbar-profile">

                    <div class="profile-avatar">
                        IE
                    </div>

                    <div class="profile-info">

                        <strong>
                            Isnaeny
                        </strong>

                        <span>
                            Administrator
                        </span>

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


<script>

document.addEventListener('DOMContentLoaded', function () {

    const app = document.getElementById('adminApp');

    const collapseButton =
        document.getElementById('sidebarCollapse');


    if (collapseButton) {

        collapseButton.addEventListener('click', function () {

            app.classList.toggle('collapsed');

        });

    }

});

</script>


@stack('scripts')

</body>

</html>