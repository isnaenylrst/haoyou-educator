<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Haoyou Educator')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>

<style>

/* ==========================================
        VARIABEL WARNA (LIGHT / DARK)
========================================== */

:root{
    --bg:#f7f8fa;
    --sidebar-bg:#ffffff;
    --sidebar-border:#ececec;
    --card-bg:#ffffff;
    --text-main:#2c2c2c;
    --text-muted:#6b7280;
    --menu-text:#555555;
    --menu-hover-bg:#FFF8CC;
    --topbar-bg:#ffffff;
    --topbar-border:#ececec;
    --gold:#FFDD00;
    --gold-text:#A9822C;
    --shadow:0 4px 15px rgba(0,0,0,.06);
}

html[data-theme="dark"]{
    --bg:#14161a;
    --sidebar-bg:#1b1e24;
    --sidebar-border:#272b33;
    --card-bg:#20232b;
    --text-main:#eaeaea;
    --text-muted:#9aa0ab;
    --menu-text:#c7cbd1;
    --menu-hover-bg:#2b2a1f;
    --topbar-bg:#1b1e24;
    --topbar-border:#272b33;
    --shadow:0 4px 15px rgba(0,0,0,.35);
}

*{margin:0;padding:0;box-sizing:border-box;}

html,body{
    width:100%;
    min-height:100%;
    background:var(--bg);
    font-family:'Poppins',sans-serif;
    color:var(--text-main);
    transition:background .2s ease, color .2s ease;
}

body{overflow-x:hidden;}

.wrapper{display:flex;min-height:100vh;background:var(--bg);}

/* ==========================================
        SIDEBAR
========================================== */

.sidebar{
    width:240px;
    min-height:100vh;
    background:var(--sidebar-bg);
    border-right:1px solid var(--sidebar-border);
    position:fixed;
    left:0;top:0;bottom:0;
    z-index:200;
    transition:width .2s ease, transform .2s ease;
    overflow-x:hidden;
}

body.sidebar-collapsed .sidebar{width:78px;}

.logo{
    text-align:center;
    padding:26px 14px;
    border-bottom:1px solid var(--sidebar-border);
    white-space:nowrap;
}

.logo img{width:56px;margin-bottom:8px;}

.logo h5{
    color:var(--gold-text);
    font-weight:700;
    letter-spacing:2px;
    margin:0;
    font-size:15px;
    transition:opacity .15s ease;
}

body.sidebar-collapsed .logo h5{display:none;}

.menu{margin-top:16px;}

.menu a{
    display:flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    color:var(--menu-text);
    padding:12px 18px;
    margin:6px 12px;
    border-radius:12px;
    transition:.2s;
    font-size:14px;
    font-weight:500;
    white-space:nowrap;
    overflow:hidden;
}

.menu a i{width:20px;text-align:center;flex-shrink:0;}

.menu a span.menu-label{transition:opacity .15s ease;}

body.sidebar-collapsed .menu a span.menu-label{display:none;}
body.sidebar-collapsed .menu a{justify-content:center;padding:12px;}

.menu a:hover{background:var(--menu-hover-bg);color:var(--gold-text);}

.menu a.active{background:var(--gold);color:var(--gold-text);font-weight:600;}

/* ==========================================
        TOPBAR
========================================== */

.topbar{
    position:sticky;
    top:0;
    z-index:150;
    height:64px;
    background:var(--topbar-bg);
    border-bottom:1px solid var(--topbar-border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 22px;
}

.hamburger-btn{
    background:none;
    border:none;
    font-size:19px;
    color:var(--text-main);
    width:40px;height:40px;
    border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;
    transition:.2s;
}

.hamburger-btn:hover{background:var(--menu-hover-bg);}

.topbar-right{display:flex;align-items:center;gap:10px;}

.icon-btn{
    position:relative;
    background:none;
    border:none;
    width:40px;height:40px;
    border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:16px;
    color:var(--text-main);
    cursor:pointer;
    transition:.2s;
    text-decoration:none;
}

.icon-btn:hover{background:var(--menu-hover-bg);}

.icon-btn .dot-badge{
    position:absolute;
    top:6px;right:7px;
    width:8px;height:8px;
    border-radius:50%;
    background:#E95A5A;
    border:2px solid var(--topbar-bg);
}

.profile-btn{
    display:flex;align-items:center;gap:8px;
    background:none;border:none;cursor:pointer;
    padding:4px 8px 4px 4px;
    border-radius:30px;
    transition:.2s;
}

.profile-btn:hover{background:var(--menu-hover-bg);}

.avatar-circle{
    width:34px;height:34px;
    border-radius:50%;
    background:var(--gold);
    color:var(--gold-text);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;
    font-size:13px;
    flex-shrink:0;
}

.profile-name{font-size:13px;font-weight:600;color:var(--text-main);line-height:1.1;}
.profile-role{font-size:11px;color:var(--text-muted);}

/* ==========================================
        CONTENT
========================================== */

.content-area{
    margin-left:240px;
    width:calc(100% - 240px);
    min-height:100vh;
    transition:margin-left .2s ease, width .2s ease;
}

body.sidebar-collapsed .content-area{margin-left:78px;width:calc(100% - 78px);}

.content{padding:30px;}

/* ==========================================
        MISC (card, table, dsb ikut variabel dark)
========================================== */

h1,h2,h3,h4,h5,h6{font-weight:600;color:var(--text-main);}
p{color:var(--text-muted);}

.card{
    background:var(--card-bg);
    border:none;
    border-radius:16px;
    box-shadow:var(--shadow);
    margin-bottom:25px;
    color:var(--text-main);
}

.small-box{background:var(--card-bg) !important;border:none;border-radius:16px;box-shadow:var(--shadow);}

.btn-primary{background:var(--gold);border-color:var(--gold);color:var(--gold-text);font-weight:600;}
.btn-primary:hover{background:#f5d300;border-color:#f5d300;color:var(--gold-text);}
.btn-outline-primary{border-color:var(--gold);color:var(--gold-text);}
.btn-outline-primary:hover{background:var(--gold);color:var(--gold-text);}

.badge-success{background:#6CC070;color:white;}
.badge-warning{background:var(--gold);color:var(--gold-text);}
.badge-danger{background:#E95A5A;color:white;}

.table{background:var(--card-bg);color:var(--text-main);}
.table td{vertical-align:middle;}
.table thead{background:rgba(0,0,0,.03);}

::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-thumb{background:#d9d9d9;border-radius:20px;}
::-webkit-scrollbar-thumb:hover{background:#bfbfbf;}

/* Mobile: sidebar jadi overlay, bukan dorong konten */
@media (max-width: 900px){
    .sidebar{transform:translateX(-100%);}
    body.sidebar-collapsed .sidebar{transform:translateX(0);width:240px;}
    body.sidebar-collapsed .logo h5,
    body.sidebar-collapsed .menu a span.menu-label{display:inline;}
    body.sidebar-collapsed .menu a{justify-content:flex-start;padding:12px 18px;}
    .content-area{margin-left:0 !important;width:100% !important;}
}

</style>

@stack('styles')

</head>

<body id="appBody">

<div class="wrapper">

    {{-- =========================
            SIDEBAR
    ========================== --}}

    <div class="sidebar">

        <div class="logo">
            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Logo">
            <h5>HAOYOU</h5>
        </div>

        <div class="menu">

            <a href="{{ url('/kurikulum/dashboard') }}" class="{{ request()->is('kurikulum/dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i><span class="menu-label">Dashboard</span>
            </a>

            <a href="{{ url('/kurikulum/sop') }}" class="{{ request()->is('kurikulum/sop') ? 'active' : '' }}">
                <i class="fas fa-book"></i><span class="menu-label">SOP</span>
            </a>

            <a href="{{ url('/kurikulum/materi') }}" class="{{ request()->is('kurikulum/materi') ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i><span class="menu-label">Materi & Silabus</span>
            </a>

            <a href="{{ url('/kurikulum/jadwal-konsultasi') }}" class="{{ request()->is('kurikulum/jadwal-konsultasi') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i><span class="menu-label">Jadwal Konsultasi</span>
            </a>

            <a href="{{ url('/kurikulum/review-pengajuan') }}" class="{{ request()->is('kurikulum/review-pengajuan') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i><span class="menu-label">Review Pengajuan</span>
            </a>

            <a href="{{ url('/kurikulum/monitoring') }}" class="{{ request()->is('kurikulum/monitoring') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i><span class="menu-label">Monitoring Guru & Kelas</span>
            </a>

            <a href="{{ url('/kurikulum/surat') }}" class="{{ request()->is('kurikulum/surat') ? 'active' : '' }}">
                <i class="fas fa-envelope"></i><span class="menu-label">Pemberitahuan Surat</span>
            </a>

        </div>

    </div>

    {{-- =========================
            CONTENT + TOPBAR
    ========================== --}}

    <div class="content-area">

        <div class="topbar">

            <button class="hamburger-btn" id="hamburgerBtn" title="Buka/tutup sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <div class="topbar-right">

                {{-- Notifikasi --}}
                <a href="{{ url('/kurikulum/surat') }}" class="icon-btn" title="Notifikasi / Surat">
                    <i class="fas fa-bell"></i>
                    <span class="dot-badge"></span>
                </a>

                {{-- Dark / Light mode --}}
                <button class="icon-btn" id="themeToggleBtn" title="Ganti tema">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>

                {{-- Profil --}}
                <div class="dropdown">
                    <button class="profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle">
                            {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="profile-name">{{ auth()->user()->username ?? 'User' }}</div>
                            <div class="profile-role">{{ auth()->user()->level->nama_level ?? '-' }}</div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size:11px;color:var(--text-muted);"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2">
                        <li class="px-3 py-2">
                            <div class="fw-semibold">{{ auth()->user()->username ?? 'User' }}</div>
                            <small class="text-muted">{{ auth()->user()->level->nama_level ?? '-' }}</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i> Profil Saya
                            </a>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

            </div>

        </div>

        <div class="content">
            @yield('content')
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ================= SIDEBAR TOGGLE =================
const body = document.getElementById('appBody');
const hamburgerBtn = document.getElementById('hamburgerBtn');

function applySidebarState() {
    const collapsed = localStorage.getItem('haoyou-sidebar-collapsed') === '1';
    body.classList.toggle('sidebar-collapsed', collapsed);
}
applySidebarState();

hamburgerBtn.addEventListener('click', function () {
    const collapsed = body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('haoyou-sidebar-collapsed', collapsed ? '1' : '0');
});

// ================= DARK / LIGHT MODE =================
const themeToggleBtn = document.getElementById('themeToggleBtn');
const themeIcon = document.getElementById('themeIcon');
const htmlEl = document.documentElement;

function applyTheme(theme) {
    htmlEl.setAttribute('data-theme', theme);
    themeIcon.classList.toggle('fa-moon', theme === 'light');
    themeIcon.classList.toggle('fa-sun', theme === 'dark');
}

const savedTheme = localStorage.getItem('haoyou-theme') || 'light';
applyTheme(savedTheme);

themeToggleBtn.addEventListener('click', function () {
    const current = htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('haoyou-theme', current);
    applyTheme(current);
});
</script>

@stack('scripts')

</body>
</html>