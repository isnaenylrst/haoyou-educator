<!DOCTYPE html>
<html lang="id" data-theme="light" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Haoyou Educator')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    {{-- UBAH: font Poppins (sama dengan halaman Admin) --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bg: #f6f7fb; --surface: #ffffff; --text: #1d1d27; --muted: #8b8fa3;
            --line: #ececf2; --accent: #ffd600; --accent-soft: #fff7cc;
            --sidebar-w: 240px; --sidebar-w-collapsed: 84px;  /* UBAH: sidebar 240px seperti Admin */
            --shadow: 0 4px 15px rgba(0,0,0,.06);

            /* nama variabel lama (dipakai halaman-halaman guru yang sudah jadi) */
            --card-bg: var(--surface); --text-main: var(--text); --text-muted: var(--muted);
            --menu-hover-bg: var(--accent-soft); --gold: #FFDD00; --gold-text: #A9822C;
        }
        [data-theme="dark"] {
            --bg: #14151c; --surface: #1c1e28; --text: #eceef6; --muted: #9a9eb5;
            --line: #2a2d3a; --accent: #ffd600; --accent-soft: #3a3410;
            --shadow: 0 4px 15px rgba(0,0,0,.35);
        }
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); margin: 0; } /* UBAH: Poppins */

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
        .icon-btn .dot { position: absolute; top: 9px; right: 10px; width: 8px; height: 8px; border-radius: 50%; background: #ffb400; border: 2px solid var(--bg); }
        .top-user { display: flex; align-items: center; gap: 10px; padding-left: 14px; margin-left: 6px; border-left: 1px solid var(--line); cursor: pointer; }
        .top-user .n { font-weight: 700; font-size: 14px; line-height: 1.2; }
        .top-user .r { font-size: 12px; color: var(--muted); }

        .dropdown-menu { border-radius: 14px; border: 1px solid var(--line); box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .notif-item { display: flex; gap: 12px; padding: 10px 16px; text-decoration: none; color: var(--text); }
        .notif-item:hover { background: var(--accent-soft); color: var(--text); }
        .notif-item .badge-num { min-width: 30px; height: 30px; border-radius: 9px; background: var(--accent); color: #1d1d27;
            font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 13px; }

        .content { padding: 28px; }
        .backdrop { display: none; }

        /* ===== GAYA UMUM (dipakai halaman-halaman guru) ===== */
        h1, h2, h3, h4, h5, h6 { font-weight: 700; color: var(--text); }
        p { color: var(--muted); }
        .card { background: var(--surface); border: none; border-radius: 16px; box-shadow: var(--shadow); margin-bottom: 25px; color: var(--text); }
        .small-box { background: var(--surface) !important; border: none; border-radius: 16px; box-shadow: var(--shadow); }
        .btn-primary { background: var(--gold); border-color: var(--gold); color: var(--gold-text); font-weight: 600; }
        .btn-primary:hover { background: #f5d300; border-color: #f5d300; color: var(--gold-text); }
        .btn-outline-primary { border-color: var(--gold); color: var(--gold-text); }
        .btn-outline-primary:hover { background: var(--gold); color: var(--gold-text); }
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

    {{-- BARU: gaya bersama Guru + Kurikulum (harus SETELAH @stack('styles')) --}}
    <link rel="stylesheet" href="{{ asset('css/haoyou-ui.css') }}?v={{ @filemtime(public_path('css/haoyou-ui.css')) }}">
</head>
<body>

@php
    // ====== DAFTAR MENU ======
    $menu = [
        ['label' => null, 'items' => [
            ['Dashboard',   'fa-house', '/guru/dashboard',   'guru/dashboard*'],
            ['Notifikasi',  'fa-bell',  '/guru/notifikasi',  'guru/notifikasi*'],
        ]],
        ['label' => 'Akademik', 'items' => [
            ['SOP',              'fa-book',        '/guru/sop',     'guru/sop*'],
            ['Materi & Silabus', 'fa-folder-open', '/guru/materi',  'guru/materi*'],
            ['Kelas',            'fa-chalkboard',  '/guru/kelas',   'guru/kelas*'],
        ]],
        ['label' => 'Mengajar', 'items' => [
            ['Attendance & Journal', 'fa-user-check',    '/guru/attendance',      'guru/attendance*'],
            ['Progress Report',      'fa-chart-column',  '/guru/progress-report', 'guru/progress-report*'],
            ['Sertifikat',           'fa-certificate',   '/guru/sertifikat',      'guru/sertifikat*'],
            ['Schedule',             'fa-calendar-days', '/guru/schedule',        'guru/schedule*'],
            ['Teaching Log',         'fa-clipboard-list','/guru/teaching-log',    'guru/teaching-log*'],
        ]],
        ['label' => 'Pengajuan', 'items' => [
            ['Cuti / Ganti Kelas', 'fa-plane-departure', '/guru/cuti', 'guru/cuti*'],
        ]],
    ];

    $authUser  = auth()->user();
    $teacher   = $authUser->teacher ?? null;
    $userName  = $teacher->name ?? $authUser->username ?? 'User';
    $initials  = strtoupper(mb_substr($authUser->username ?? $userName, 0, 2));
    $roleLabel = 'Teacher';
    $profileUrl = \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : '#';

    // ====== LONCENG NOTIFIKASI (tiap hitungan aman jika tabel/kolom belum ada) ======
    $safeCount = function (callable $fn) {
        try { return (int) $fn(); } catch (\Throwable $e) { return 0; }
    };
    $teacherId = $teacher->id ?? null;
    $db = \Illuminate\Support\Facades\DB::class;

    $offersPending    = $teacherId ? $safeCount(fn () => $db::table('class_assignments')->where('teacher_id', $teacherId)->where('status', 'Pending')->count()) : 0;
    $journalsRevision = $teacherId ? $safeCount(fn () => $db::table('teaching_journals')->where('teacher_id', $teacherId)->where('status', 'Revision')->count()) : 0;
    $materialsRejected = $teacherId ? $safeCount(fn () => $db::table('teacher_materials')->where('teacher_id', $teacherId)->where('status', 'Rejected')->where('updated_at', '>=', now()->subDays(7))->count()) : 0;
    $notifTotal = $offersPending + $journalsRevision + $materialsRejected;

    
    // ===== OWNER: sidebar menampilkan SEMUA fitur Admin, Kurikulum, dan Guru =====
    if (($authUser->level->nama_level ?? null) === 'Owner') {
        $menu      = \App\Support\OwnerMenu::asLayoutMenu();
        $userName  = $authUser->username ?? 'Owner';
        $initials  = strtoupper(mb_substr($userName, 0, 2));
        $roleLabel = 'Owner';
    }
    
@endphp

{{-- ===== SIDEBAR ===== --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <a href="{{ url('/guru/dashboard') }}">
            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Haoyou">
        </a>
    </div>

    <nav class="sidebar-scroll" id="sidebarNav">
        @foreach($menu as $group)
            @if($group['label'])
                <div class="nav-label" @if(!empty($group['color'])) style="color: {{ $group['color'] }}" @endif>{{ $group['label'] }}</div>
            @endif
            @foreach($group['items'] as [$text, $icon, $url, $pattern])
                <a href="{{ url($url) }}" class="nav-link-item {{ request()->is($pattern) ? 'active' : '' }}" title="{{ $text }}">
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
            <a href="{{ url('/guru/schedule') }}" class="icon-btn" title="Schedule">
                <i class="fa-regular fa-calendar-check"></i>
            </a>

            {{-- Notifikasi --}}
            <div class="dropdown">
                <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
                    <i class="fa-regular fa-bell"></i>
                    @if($notifTotal > 0)<span class="dot"></span>@endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width:320px">
                    <div class="px-3 py-3 fw-bold border-bottom">
                        Notifikasi
                        @if($notifTotal > 0)<span class="badge text-bg-warning ms-1">{{ $notifTotal }}</span>@endif
                    </div>

                    @if($notifTotal === 0)
                        <div class="p-4 text-center text-muted small">Tidak ada yang perlu ditindaklanjuti 🎉</div>
                    @else
                        @if($offersPending > 0)
                            <a class="notif-item" href="{{ url('/guru/schedule') }}#tawaran-kelas">
                                <div class="badge-num">{{ $offersPending }}</div>
                                <div><div class="fw-semibold small">Tawaran kelas menunggu</div>
                                <div class="text-muted" style="font-size:12px">Terima atau tolak di Schedule</div></div>
                            </a>
                        @endif
                        @if($journalsRevision > 0)
                            <a class="notif-item" href="{{ url('/guru/teaching-log') }}">
                                <div class="badge-num">{{ $journalsRevision }}</div>
                                <div><div class="fw-semibold small">Jurnal perlu revisi</div>
                                <div class="text-muted" style="font-size:12px">Dikembalikan Kurikulum</div></div>
                            </a>
                        @endif
                        @if($materialsRejected > 0)
                            <a class="notif-item" href="{{ url('/guru/kelas') }}">
                                <div class="badge-num">{{ $materialsRejected }}</div>
                                <div><div class="fw-semibold small">LP / PPT ditolak</div>
                                <div class="text-muted" style="font-size:12px">7 hari terakhir, upload ulang di Kelas</div></div>
                            </a>
                        @endif
                    @endif

                    <a href="{{ url('/guru/notifikasi') }}" class="d-block text-center py-2 small fw-semibold border-top text-decoration-none">Lihat semua notifikasi →</a>
                </div>
            </div>

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
                <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px">
                    <li class="px-3 py-2 small text-muted">Masuk sebagai <strong>{{ $authUser->username ?? $userName }}</strong></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item rounded-3 fw-semibold" href="{{ $profileUrl }}">
                            <i class="fa-regular fa-user me-2"></i> Edit Profil
                        </a>
                    </li>
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
    if (safe(() => localStorage.getItem('haoyou-sidebar-collapsed')) === '1') body.classList.add('collapsed');

    document.getElementById('collapseBtn').addEventListener('click', () => {
        body.classList.toggle('collapsed');
        safe(() => localStorage.setItem('haoyou-sidebar-collapsed', body.classList.contains('collapsed') ? '1' : '0'));
    });
    document.getElementById('hamburger').addEventListener('click', () => body.classList.toggle('sidebar-open'));
    document.getElementById('backdrop').addEventListener('click', () => body.classList.remove('sidebar-open'));

    // ----- mode gelap / terang -----
    function applyTheme(t) {
        root.setAttribute('data-theme', t);
        root.setAttribute('data-bs-theme', t);
        document.getElementById('themeIcon').className = t === 'dark' ? 'fa-regular fa-sun' : 'fa-regular fa-moon';
    }
    applyTheme(safe(() => localStorage.getItem('haoyou-theme')) || 'light');
    document.getElementById('themeBtn').addEventListener('click', () => {
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        safe(() => localStorage.setItem('haoyou-theme', next));
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