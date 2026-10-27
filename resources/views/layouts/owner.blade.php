<!DOCTYPE html>
<html lang="id" data-theme="light" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Owner') | Haoyou</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bg: #f6f7fb; --surface: #ffffff; --text: #1d1d27; --muted: #8b8fa3;
            --line: #ececf2; --accent: #ffd600; --accent-soft: #fff7cc;
            --sidebar-w: 240px; --sidebar-w-collapsed: 84px;
            --shadow: 0 4px 15px rgba(0,0,0,.06);
        }
        [data-theme="dark"] {
            --bg: #14151c; --surface: #1c1e28; --text: #eceef6; --muted: #9a9eb5;
            --line: #2a2d3a; --accent: #ffd600; --accent-soft: #3a3410;
            --shadow: 0 4px 15px rgba(0,0,0,.35);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); margin: 0; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed; inset: 0 auto 0 0; width: var(--sidebar-w); background: var(--surface);
            border-right: 1px solid var(--line); display: flex; flex-direction: column; z-index: 1040;
            transition: width .2s ease, transform .2s ease;
        }
        .sidebar-logo { padding: 22px 0 10px; text-align: center; }
        .sidebar-logo img { width: 92px; height: auto; border-radius: 50%; transition: width .2s; }
        .sidebar-scroll { flex: 1; overflow-y: auto; padding: 8px 14px 14px; }
        .nav-label { font-size: 11px; font-weight: 700; letter-spacing: .8px; color: var(--muted);
            text-transform: uppercase; padding: 18px 12px 8px; }
        .nav-link-item {
            display: flex; align-items: center; gap: 14px; padding: 11px 14px; margin-bottom: 4px;
            border-radius: 12px; color: var(--text); text-decoration: none; font-weight: 600; font-size: 14.5px;
            white-space: nowrap; transition: background .15s;
        }
        .nav-link-item i { width: 20px; text-align: center; font-size: 16px; color: var(--muted); }
        .nav-link-item:hover { background: var(--accent-soft); color: var(--text); }
        .nav-link-item.active { background: var(--accent); color: #1d1d27; font-weight: 700; }
        .nav-link-item.active i { color: #1d1d27; }

        .sidebar-user { border-top: 1px solid var(--line); padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 38px; height: 38px; border-radius: 50%; background: var(--accent); color: #1d1d27;
            font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .user-meta { line-height: 1.25; min-width: 0; flex: 1; }
        .user-meta .n { font-weight: 700; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .user-meta .r { font-size: 12px; color: var(--muted); }
        .collapse-btn {
            width: 30px; height: 30px; border: 0; border-radius: 9px; background: #1d1d27; color: #fff;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;
        }
        [data-theme="dark"] .collapse-btn { background: #ffd600; color: #1d1d27; }

        body.collapsed .sidebar { width: var(--sidebar-w-collapsed); }
        body.collapsed .sidebar-logo img { width: 48px; }
        body.collapsed .nav-label, body.collapsed .nav-text, body.collapsed .user-meta { display: none; }
        body.collapsed .nav-link-item { justify-content: center; padding: 12px 0; }
        body.collapsed .sidebar-user { flex-direction: column; gap: 10px; padding: 12px 0; }
        body.collapsed .collapse-btn i { transform: rotate(180deg); }

        /* ===== MAIN & TOPBAR ===== */
        .main { margin-left: var(--sidebar-w); transition: margin-left .2s ease; min-height: 100vh; }
        body.collapsed .main { margin-left: var(--sidebar-w-collapsed); }

        .topbar {
            position: sticky; top: 0; z-index: 1030; background: var(--surface); border-bottom: 1px solid var(--line);
            height: 72px; padding: 0 28px; display: flex; align-items: center; gap: 16px;
        }
        .hamburger { display: none; border: 0; background: transparent; color: var(--text); font-size: 20px; }
        .searchbox { position: relative; flex: 0 1 360px; }
        .searchbox input {
            width: 100%; border: 1px solid var(--line); background: var(--bg); color: var(--text);
            border-radius: 12px; padding: 11px 70px 11px 40px; font-size: 14px; outline: none;
        }
        .searchbox input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(255,214,0,.25); }
        .searchbox .ic { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); }
        .searchbox .kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11px; color: var(--muted); }

        .top-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .icon-btn {
            position: relative; width: 42px; height: 42px; border: 0; border-radius: 12px; background: var(--bg);
            color: var(--text); display: flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer;
        }
        .icon-btn:hover { background: var(--accent-soft); color: var(--text); }
        .top-user { display: flex; align-items: center; gap: 10px; padding-left: 14px; margin-left: 6px; border-left: 1px solid var(--line); cursor: pointer; }
        .top-user .n { font-weight: 700; font-size: 14px; line-height: 1.2; }
        .top-user .r { font-size: 12px; color: var(--muted); }

        .dropdown-menu { border-radius: 14px; border: 1px solid var(--line); box-shadow: 0 10px 30px rgba(0,0,0,.08); }

        .content { padding: 28px; }
        .backdrop { display: none; }

        h1, h2, h3, h4, h5, h6 { font-weight: 700; color: var(--text); }
        p { color: var(--muted); }
        .card { background: var(--surface); border: none; border-radius: 16px; box-shadow: var(--shadow); margin-bottom: 25px; color: var(--text); }
        .btn-primary { background: #FFDD00; border-color: #FFDD00; color: #111; font-weight: 600; }
        .table { background: var(--surface); color: var(--text); }
        .table td { vertical-align: middle; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-thumb { background: #d9d9d9; border-radius: 20px; }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; }
            body.sidebar-open .sidebar { transform: translateX(0); }
            body.sidebar-open .backdrop { display: block; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1035; }
            .main, body.collapsed .main { margin-left: 0; }
            .hamburger { display: block; }
            .top-user .meta, .searchbox .kbd { display: none; }
            .topbar { padding: 0 16px; }
            .content { padding: 18px 16px; }
            .collapse-btn { display: none; }
            body.collapsed .nav-label, body.collapsed .nav-text, body.collapsed .user-meta { display: initial; }
        }
    </style>
    @stack('styles')

    {{-- gaya bersama Guru + Kurikulum + Owner (harus SETELAH @stack('styles')) --}}
    <link rel="stylesheet" href="{{ asset('css/haoyou-ui.css') }}?v={{ @filemtime(public_path('css/haoyou-ui.css')) }}">
</head>
<body>

@php
    // ====== DAFTAR MENU ======
    // Semua fitur Admin, Kurikulum, dan Guru dari satu sumber: app/Support/OwnerMenu.php
    $menu = \App\Support\OwnerMenu::asLayoutMenu();

    $authUser  = auth()->user();
    $userName  = $authUser->username ?? 'Owner';
    $initials  = strtoupper(mb_substr($userName, 0, 2));
    $roleLabel = 'Owner';
@endphp

{{-- ===== SIDEBAR ===== --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <a href="{{ url('/owner/dashboard') }}">
            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Haoyou">
        </a>
    </div>

    <nav class="sidebar-scroll" id="sidebarNav">
        @foreach($menu as $group)
            @if($group['label'])
                <div class="nav-label" @if(!empty($group['color'])) style="color: {{ $group['color'] }}" @endif>{{ $group['label'] }}</div>
            @endif
            @foreach($group['items'] as [$text, $icon, $url, $pattern])
                <a href="{{ url($url) }}"
                   class="nav-link-item {{ request()->is($pattern) ? 'active' : '' }}"
                   title="{{ $text }}">
                    <i class="fa-solid {{ $icon }}"></i>
                    <span class="nav-text">{{ $text }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sidebar-user">
        <div class="avatar">{{ $initials }}</div>
        <div class="user-meta">
            <div class="n">{{ $userName }}</div>
            <div class="r">{{ $roleLabel }}</div>
        </div>
        <button type="button" class="collapse-btn" id="collapseBtn" title="Ciutkan sidebar">
            <i class="fa-solid fa-chevron-left" style="font-size:12px"></i>
        </button>
    </div>
</aside>
<div class="backdrop" id="backdrop"></div>

{{-- ===== MAIN ===== --}}
<div class="main">

    <header class="topbar">
        <button class="hamburger" id="hamburger" type="button"><i class="fa-solid fa-bars"></i></button>

        <div class="searchbox">
            <i class="fa-solid fa-magnifying-glass ic"></i>
            <input type="text" id="menuSearch" placeholder="Cari menu…" autocomplete="off">
            <span class="kbd">Ctrl K</span>
        </div>

        <div class="top-actions">
            <button class="icon-btn" id="themeBtn" type="button" title="Mode gelap / terang">
                <i class="fa-regular fa-moon" id="themeIcon"></i>
            </button>

            {{-- Profil --}}
            <div class="dropdown">
                <div class="top-user" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar">{{ $initials }}</div>
                    <div class="meta">
                        <div class="n">{{ $userName }}</div>
                        <div class="r">{{ $roleLabel }}</div>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size:11px;color:var(--muted)"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:200px">
                    <li class="px-3 py-2 small text-muted">Masuk sebagai <strong>{{ $userName }}</strong></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-3 text-danger fw-semibold">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="content">
        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const body = document.body, root = document.documentElement;
    const safe = (fn) => { try { return fn(); } catch (e) { return null; } };

    // ----- sidebar collapse (desktop) & buka-tutup (mobile) -----
    if (safe(() => localStorage.getItem('owner_collapsed')) === '1') body.classList.add('collapsed');

    document.getElementById('collapseBtn').addEventListener('click', () => {
        body.classList.toggle('collapsed');
        safe(() => localStorage.setItem('owner_collapsed', body.classList.contains('collapsed') ? '1' : '0'));
    });
    document.getElementById('hamburger').addEventListener('click', () => body.classList.toggle('sidebar-open'));
    document.getElementById('backdrop').addEventListener('click', () => body.classList.remove('sidebar-open'));

    // ----- mode gelap / terang -----
    function applyTheme(t) {
        root.setAttribute('data-theme', t);
        root.setAttribute('data-bs-theme', t);
        document.getElementById('themeIcon').className = t === 'dark' ? 'fa-regular fa-sun' : 'fa-regular fa-moon';
    }
    applyTheme(safe(() => localStorage.getItem('owner_theme')) || 'light');
    document.getElementById('themeBtn').addEventListener('click', () => {
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        safe(() => localStorage.setItem('owner_theme', next));
        window.location.reload();   // grafik ikut berganti warna
    });

    // ----- pencarian menu (Ctrl+K) -----
    const search = document.getElementById('menuSearch');
    const links = [...document.querySelectorAll('#sidebarNav .nav-link-item')];
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); search.focus(); }
    });
    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        links.forEach(a => a.style.display = (!q || a.textContent.toLowerCase().includes(q)) ? '' : 'none');
    });
    search.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const first = links.find(a => a.style.display !== 'none');
            if (first) window.location = first.href;
        }
        if (e.key === 'Escape') { search.value = ''; links.forEach(a => a.style.display = ''); search.blur(); }
    });
})();
</script>
@stack('scripts')
</body>
</html>