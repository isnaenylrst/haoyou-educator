@extends('layouts.app')

@section('title', ($title ?? 'Dashboard') . ' — Haoyou')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

    .font-poppins {
        font-family: 'Poppins', sans-serif;
    }

    /* ---------- LATAR: 3 LINGKARAN (statis, senada halaman login) ---------- */
    .bg-rings{ position:absolute; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
    .bg-rings span{ position:absolute; border-radius:50%; display:block; }
    .bg-rings .ring-a{ right:-140px; top:90px;  width:400px; height:400px; border:34px solid #ffe135; }
    .bg-rings .ring-b{ right:-105px; top:127px; width:330px; height:330px; border:4px solid #EE9F8F; }
    .bg-rings .ring-c{ right:-70px;  top:165px; width:260px; height:260px; border:3px solid #2F4B3B; }
    @media (max-width:767px){ .bg-rings{ display:none; } }

    /* ---------- SIDEBAR ---------- */
    .side-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 12px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 500;
        color: #4a4636;
        margin-bottom: 6px;
        transition: .15s;
        text-decoration: none;
        min-height: 46px;
    }

    .side-link .ico {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: #d5dfcc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
        transition: .15s;
    }

    .side-link:hover {
        background: #ffe135;
        color: #1d1a0f;
    }

    .side-link:hover .ico {
        background: #F5E5A3;
    }

    .side-link.is-active {
        background: #ffe135;
        color: #000000;
        font-weight: 600;
    }

    .side-link.is-active .ico {
        background: #F5E5A3;
    }

    .side-link.alumni-restricted {
        position: relative;
    }

    .alumni-mode .side-link.alumni-restricted {
        opacity: .35;
        pointer-events: none;
    }

    .alumni-mode .side-link.alumni-restricted::after {
        content: "🔒";
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
    }

    .alumni-mode .menu-restricted {
        opacity: .35;
        pointer-events: none;
    }

    /* ---------- SUBMENU BELAJAR VOCAB ---------- */
    .side-subwrap {
        display: none;
        padding-left: 18px;
        margin: -2px 0 6px;
    }

    .side-group.open .side-subwrap {
        display: block;
    }

    .side-chev {
        margin-left: auto;
        transition: .15s;
    }

    .side-group.open .side-chev {
        transform: rotate(90deg);
    }

    .side-sub {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        margin-bottom: 3px;
        font-size: 13.5px;
        font-weight: 500;
        color: #000000;
        border-left: 2px solid #ffe135;
        border-radius: 0 10px 10px 0;
        text-decoration: none;
    }

    .side-sub:hover {
        background: #ffe135;
    }

    .side-sub.is-active {
        background: #ffe135;
        border-left-color: #E5C85B;
        color: #1A1A1A;
        font-weight: 600;
    }

    .alumni-mode .side-sub.alumni-restricted {
        opacity: .35;
        pointer-events: none;
    }

    /* ---------- TOPBAR ---------- */
    .top-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #E5EBDD;
        color: #3D4938;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: .15s;
    }

    .top-btn:hover {
        background: #F0F3EB;
    }

    .bell-dot {
        position: absolute;
        top: 9px;
        right: 10px;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #D93025;
        border: 2px solid #D5DFCC;
    }

    /* ---------- DROPDOWN PROFIL ---------- */
    .profile-menu {
        position: absolute;
        top: 58px;
        right: 0;
        width: 272px;
        background: #fff;
        border: 1px solid #F3EFDD;
        border-radius: 18px;
        box-shadow: 0 12px 32px rgba(90,70,0,.18);
        overflow: hidden;
        z-index: 50;
        display: none;
    }

    .profile-menu.open {
        display: block;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 0 18px;
        min-height: 48px;
        font-size: 14px;
        font-weight: 500;
        color: #000000;
        background: none;
        border: 0;
        text-align: left;
        cursor: pointer;
        text-decoration: none;
    }

    .menu-item:hover {
        background: #ffe135;
    }

    .menu-item.danger {
        color: #C0392B;
    }

    .menu-item.danger:hover {
        background: #FDECEA;
    }

    .avatar {
        border-radius: 9999px;
        background: #F8F5E9;
        color: #5c4500;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
@php
    $authUser = auth()->user();
    $username = $authUser->display_name;
    $namaUser = $username;
    $inisial = collect(explode(' ', trim($namaUser)))
        ->filter()
        ->take(2)
        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');

    $isAlumni = method_exists($authUser, 'isAlumni') && $authUser->isAlumni();
    $fotoUrl = !empty($authUser->foto) ? asset('storage/' . $authUser->foto) : null;
@endphp

{{-- PERUBAHAN: wrapper utama sekarang punya isolate agar z-index lingkaran terkontrol --}}
<div class="relative isolate overflow-hidden flex min-h-screen bg-white font-poppins {{ $isAlumni ? 'alumni-mode' : '' }}">

    {{-- ===================== LATAR LINGKARAN ===================== --}}
    <div class="bg-rings" aria-hidden="true">
        <span class="ring-a"></span>
        <span class="ring-b"></span>
        <span class="ring-c"></span>
    </div>

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="relative overflow-hidden z-10 w-[264px] bg-white text-[#1d1a0f] flex-shrink-0 hidden md:flex flex-col border-r border-[#EFEBDB]">

        <div class="h-24 flex items-center justify-center border-b border-[#EFEBDB]">
            <img src="{{ asset('assets/img/logo.png') }}"
                 alt="Haoyou"
                 class="w-[68px] h-[68px] object-contain">
        </div>

        <nav class="relative z-10 flex-1 p-3.5 pt-5">

            <a href="{{ route('dashboard') }}"
               class="side-link alumni-restricted {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <span class="ico">🏠</span> Dashboard
            </a>

            <a href="{{ route('notifikasi.index') }}"
               class="side-link alumni-restricted {{ request()->routeIs('notifikasi.*') ? 'is-active' : '' }}">
                <span class="ico">🔔</span> Notifikasi
            </a>

            <a href="{{ route('program.index') }}"
               class="side-link alumni-restricted {{ request()->routeIs('program.*') ? 'is-active' : '' }}">
                <span class="ico">📘</span> Program Belajar
            </a>

            <a href="{{ route('kelassaya.index') }}"
               class="side-link alumni-restricted {{ request()->routeIs('kelassaya.*') ? 'is-active' : '' }}">
                <span class="ico">🎓</span> Kelas Saya
            </a>

            {{--
            <a href="{{ route('booking.index') }}"
               class="side-link alumni-restricted {{ request()->routeIs('booking.*') ? 'is-active' : '' }}">
                <span class="ico">🗓️</span> Booking Kelas
            </a>
            --}}

          
            <a href="{{ route('progresreport.index') }}"
               class="side-link {{ request()->routeIs('progresreport.*') ? 'is-active' : '' }}">
                <span class="ico">📊</span> Progress Report
            </a>

            {{-- <a href="{{ route('sertifikat.index') }}"
               class="side-link {{ request()->routeIs('sertifikat.*') ? 'is-active' : '' }}">
                <span class="ico">🏅</span> Sertifikat
            </a> --}}

            {{-- ============ SERTIFIKAT ============ --}}
            @php
                $inSertifikat = request()->routeIs('sertifikat.*');
                $tabMandiri   = request('tab') === 'mandiri';
            @endphp

            <div id="grpSertifikat" class="side-group {{ $inSertifikat ? 'open' : '' }}">
                <button type="button"
                        data-side-toggle="grpSertifikat"
                        aria-expanded="{{ $inSertifikat ? 'true' : 'false' }}"
                        class="side-link w-full {{ $inSertifikat ? 'is-active' : '' }}"
                        style="border:0;cursor:pointer;text-align:left">
                    <span class="ico">🏅</span>
                    Sertifikat
                    <svg class="side-chev" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 5 7 7-7 7"/>
                    </svg>
                </button>

                <div class="side-subwrap">
                    <a href="{{ route('sertifikat.index') }}"
                      class="side-sub {{ $inSertifikat && !$tabMandiri ? 'is-active' : '' }}">
                        <span>🎓</span> Sertifikat Kelas
                    </a>
                    <a href="{{ route('sertifikat.index', ['tab' => 'mandiri']) }}"
                      class="side-sub {{ $inSertifikat && $tabMandiri ? 'is-active' : '' }}">
                        <span>👩‍🎓</span> Belajar Mandiri
                    </a>
                </div>
            </div>

            {{-- ============ BELAJAR VOCAB ============ --}}
            @php
                $inVocab = request()->routeIs('student.vocab.*');
            @endphp

            <div id="grpVocab" class="side-group {{ $inVocab ? 'open' : '' }}">

                <button type="button"
                        data-side-toggle="grpVocab"
                        aria-expanded="{{ $inVocab ? 'true' : 'false' }}"
                        class="side-link alumni-restricted w-full"
                        style="border:0;background:none;cursor:pointer;text-align:left">

                    <span class="ico">📖</span>
                    Belajar Vocab

                    <svg class="side-chev"
                         width="14"
                         height="14"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2.4"
                         stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="m9 5 7 7-7 7"/>
                    </svg>
                </button>

                <div class="side-subwrap">

                    <a href="{{ route('student.vocab.guide') }}"
                       class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.guide') ? 'is-active' : '' }}">
                        <span>📘</span> Panduan
                    </a>

                    <a href="{{ route('student.vocab.plan') }}"
                       class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.plan') ? 'is-active' : '' }}">
                        <span>📅</span> Study Plan
                    </a>

                    <a href="{{ route('student.vocab.list') }}"
                       class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.list') ? 'is-active' : '' }}">
                        <span>📖</span> Vocab List
                    </a>

                    <a href="{{ route('student.vocab.test') }}"
                       class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.test*') ? 'is-active' : '' }}">
                        <span>✍️</span> Test
                    </a>

                    <a href="{{ route('student.vocab.traps') }}"
                       class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.traps*') ? 'is-active' : '' }}">
                        <span>⚠️</span> Vocab Traps
                    </a>

                </div>
            </div>
            {{-- ============ BELAJAR GRAMMAR ============ --}}
            @php $inGrammar = request()->routeIs('student.grammar.*'); @endphp

            <div id="grpGrammar" class="side-group {{ $inGrammar ? 'open' : '' }}">

                <button type="button"
                        data-side-toggle="grpGrammar"
                        aria-expanded="{{ $inGrammar ? 'true' : 'false' }}"
                        class="side-link alumni-restricted w-full"
                        style="border:0;background:none;cursor:pointer;text-align:left">

                    <span class="ico">📝</span>
                    Belajar Grammar

                    <svg class="side-chev" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 5 7 7-7 7"/>
                    </svg>
                </button>

                <div class="side-subwrap">
                <a href="{{ route('student.grammar.guide') }}"
                class="side-sub alumni-restricted {{ request()->routeIs('student.grammar.guide') ? 'is-active' : '' }}">
                    <span>📘</span> Panduan
                </a>

                <a href="{{ route('student.grammar.list') }}"
                class="side-sub alumni-restricted {{ request()->routeIs('student.grammar.list') ? 'is-active' : '' }}">
                    <span>📖</span> Grammar List
                </a>

                <a href="{{ route('student.grammar.traps') }}"
                class="side-sub alumni-restricted {{ request()->routeIs('student.grammar.traps') ? 'is-active' : '' }}">
                    <span>⚠️</span> Grammar Traps
                </a>

                <a href="{{ route('student.grammar.test') }}"
                class="side-sub alumni-restricted {{ request()->routeIs('student.grammar.test*') ? 'is-active' : '' }}">
                    <span>✍️</span> Test
                </a>
            </div>
        </div>
        </nav>

        {{-- Dekorasi garis berwarna di dasar sidebar --}}
        <svg class="absolute bottom-0 left-0 pointer-events-none"
             width="264"
             height="230"
             viewBox="0 0 264 230"
             fill="none"
             aria-hidden="true">

            <path d="M-10 120 C 45 84 100 150 160 118 S 240 92 280 112"
                  stroke="#ffe135"
                  stroke-width="14"
                  stroke-linecap="round"/>

            <path d="M-10 150 C 45 114 100 180 160 148 S 240 122 280 142"
                  stroke="#2F4B3B"
                  stroke-width="3"
                  stroke-linecap="round"/>

            <path d="M-10 176 C 45 140 100 206 160 174 S 240 148 280 168"
                  stroke="#EE9F8F"
                  stroke-width="3"
                  stroke-linecap="round"/>

            <path d="M-10 202 C 45 166 100 232 160 200 S 240 174 280 194"
                  stroke="#C5D6D0"
                  stroke-width="5"
                  stroke-linecap="round"/>
        </svg>

    </aside>

    {{-- ===================== KOLOM KANAN ===================== --}}
    {{-- PERUBAHAN: z-10 dihapus dari sini supaya tidak menutupi lingkaran sebagai satu lapisan solid --}}
    <div class="relative flex-1 min-w-0 flex flex-col">

        {{-- TOPBAR MINT --}}
        <header class="relative z-20 h-[72px] bg-[#D5DFCC] flex items-center justify-between px-5 md:px-8 flex-shrink-0">

            <div class="flex items-center gap-2 bg-[#E5EBDD] text-[#3a4635] text-sm font-semibold px-4 min-h-[44px] rounded-2xl">
                Haoyou Student
            </div>

            <div class="flex items-center gap-3">

                <a href="{{ route('notifikasi.index') }}"
                   aria-label="Notifikasi"
                   class="top-btn alumni-restricted">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10 21a2 2 0 0 0 4 0"/>
                    </svg>

                    <span class="bell-dot"></span>
                </a>

                {{-- AVATAR + DROPDOWN PROFIL --}}
                <div class="relative">

                    <button id="profileBtn"
                            type="button"
                            aria-label="Menu profil"
                            aria-haspopup="true"
                            aria-expanded="false"
                            class="avatar w-11 h-11 text-[15px] border-2 border-white cursor-pointer">

                        @if($fotoUrl)
                            <img src="{{ $fotoUrl }}" alt="{{ $namaUser }}">
                        @else
                            {{ $inisial }}
                        @endif

                    </button>

                    <div id="profileMenu" class="profile-menu" role="menu">

                        <div class="flex items-center gap-3 px-[18px] py-4 border-b border-[#F3EFDD]">

                            <div class="avatar w-12 h-12 text-base">

                                @if($fotoUrl)
                                    <img src="{{ $fotoUrl }}" alt="{{ $namaUser }}">
                                @else
                                    {{ $inisial }}
                                @endif

                            </div>

                            <div class="min-w-0">

                                <div class="font-bold text-[15px] leading-tight truncate">
                                    {{ $username }}
                                </div>

                                <div class="text-[13px] text-[#6b6652]">
                                    {{ $levelAktif ?? 'Belum ada kelas aktif' }}
                                </div>

                            </div>
                        </div>

                        <a href="{{ route('profil.index') }}"
                           class="menu-item menu-restricted"
                           role="menuitem">

                            <svg width="18"
                                 height="18"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>
                            </svg>

                            Profil Saya
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                    class="menu-item danger"
                                    role="menuitem">

                                <svg width="18"
                                     height="18"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">

                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                                </svg>

                                Keluar
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </header>

        {{-- ISI HALAMAN --}}
        {{-- PERUBAHAN: bg-white dihapus dari <main> dan diganti bg-transparent --}}
        <main class="flex-1 bg-transparent px-5 py-6 md:px-10 md:py-8">

            <div class="mb-7">

                <div class="text-sm text-[#6b6652]">
                    Home &nbsp;›&nbsp;
                    <span class="font-semibold text-[#1d1a0f]">
                        {{ $title ?? 'Dashboard' }}
                    </span>
                </div>

                @hasSection('page-heading')
                    @yield('page-heading')
                @else

                    <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">
                        {{ $title ?? 'Dashboard' }}
                    </h1>

                    @if(!empty($subtitle))
                        <p class="text-sm text-[#6b6652] mt-1">
                            {{ $subtitle }}
                        </p>
                    @endif

                @endif
            </div>

            @yield('dashboard-content')

        </main>
    </div>
</div>

<script>
    // ---------- Dropdown profil ----------
    (function(){
        var btn = document.getElementById('profileBtn');
        var menu = document.getElementById('profileMenu');

        function close(){
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', function(e){
            e.stopPropagation();

            var open = menu.classList.toggle('open');

            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function(e){
            if(!menu.contains(e.target)) close();
        });

        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape') close();
        });
    })();

    // ---------- Buka/tutup submenu Belajar Vocab ----------
    document.querySelectorAll('[data-side-toggle]').forEach(function(b){
        b.addEventListener('click', function(){
            var g = document.getElementById(b.dataset.sideToggle);

            b.setAttribute(
                'aria-expanded',
                g.classList.toggle('open') ? 'true' : 'false'
            );
        });
    });

    // ---------- Pembatasan akun Alumni ----------
    var alumniMode = @json($isAlumni);

    document.querySelectorAll('.alumni-restricted, .menu-restricted').forEach(function(link){
        link.addEventListener('click', function(e){
            if(alumniMode){
                e.preventDefault();

                alert('Halaman ini tidak bisa diakses untuk akun Alumni. Alumni hanya bisa melihat Sertifikat & Progress Report saja yang lain tidak bisa di akses.');
            }
        });
    });
</script>
@endsection