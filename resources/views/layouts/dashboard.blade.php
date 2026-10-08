@extends('layouts.app')

@section('title', ($title ?? 'Dashboard') . ' — Haoyou')

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
  .font-poppins{ font-family:'Poppins',sans-serif; }

  /* ---------- SIDEBAR ---------- */
  .side-link{ display:flex; align-items:center; gap:12px; padding:9px 12px; border-radius:14px; font-size:14px; font-weight:500; color:#4a4636; margin-bottom:6px; transition:.15s; text-decoration:none; min-height:46px; }
  .side-link .ico{ width:32px; height:32px; border-radius:10px; background:#FFF8D6; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; transition:.15s; }
  .side-link:hover{ background:#FFF8D6; color:#1d1a0f; }
  .side-link:hover .ico{ background:#FFEFA0; }
  .side-link.is-active{ background:#FFEFA0; color:#5c4500; font-weight:600; }
  .side-link.is-active .ico{ background:#FFD60A; }
  .side-link.alumni-restricted{ position:relative; }
  body.alumni-mode .side-link.alumni-restricted{ opacity:.35; pointer-events:none; }
  body.alumni-mode .side-link.alumni-restricted::after{ content:"🔒"; position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:12px; }
  body.alumni-mode .menu-restricted{ opacity:.35; pointer-events:none; }
  .alumni-toggle-btn{ border:1px solid #F2D84A; background:#FFF8D6; color:#5c4500; }
  .alumni-toggle-btn:hover{ background:#FFD60A; color:#1d1a0f; }

  /* ---------- TOPBAR ---------- */
  .top-btn{ width:44px; height:44px; border-radius:14px; background:#FFE34D; color:#3a2e00; display:flex; align-items:center; justify-content:center; position:relative; transition:.15s; }
  .top-btn:hover{ background:#FFF0A0; }
  .bell-dot{ position:absolute; top:9px; right:10px; width:9px; height:9px; border-radius:50%; background:#D93025; border:2px solid #FFE34D; }

  /* ---------- DROPDOWN PROFIL ---------- */
  .profile-menu{ position:absolute; top:58px; right:0; width:272px; background:#fff; border:1px solid #F3EFDD; border-radius:18px; box-shadow:0 12px 32px rgba(90,70,0,.18); overflow:hidden; z-index:50; display:none; }
  .profile-menu.open{ display:block; }
  .menu-item{ display:flex; align-items:center; gap:12px; width:100%; padding:0 18px; min-height:48px; font-size:14px; font-weight:500; color:#1d1a0f; background:none; border:0; text-align:left; cursor:pointer; text-decoration:none; }
  .menu-item:hover{ background:#FFF8D6; }
  .menu-item.danger{ color:#C0392B; }
  .menu-item.danger:hover{ background:#FDECEA; }
  .avatar{ border-radius:9999px; background:#FFF3B8; color:#5c4500; font-weight:700; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; }
  .avatar img{ width:100%; height:100%; object-fit:cover; }
</style>
@endpush

@section('content')
@php
  $authUser = auth()->user();
  $username = $authUser->display_name;
  $namaUser = $username;
  $inisial  = collect(explode(' ', trim($namaUser)))->filter()->take(2)->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $fotoUrl  = !empty($authUser->foto) ? asset('storage/' . $authUser->foto) : null;
@endphp

<div class="flex min-h-screen bg-[#FAF8F1] font-poppins">

  {{-- ===================== SIDEBAR ===================== --}}
  <aside class="w-[264px] bg-white text-[#1d1a0f] flex-shrink-0 hidden md:flex flex-col border-r border-[#EFEBDB]">

    <div class="h-24 flex items-center gap-3 px-6 border-b border-[#EFEBDB]">
      <img src="{{ asset('assets/img/logo.png') }}" alt="Haoyou" class="w-12 h-12 object-contain">
      <h2 class="font-bold text-xl tracking-wider text-[#1d1a0f]">HAOYOU</h2>
    </div>

    <nav class="flex-1 p-3.5 pt-5">
      <a href="{{ route('student.dashboard') }}" class="side-link alumni-restricted {{ request()->routeIs('student.dashboard') ? 'is-active' : '' }}">
        <span class="ico">🏠</span> Dashboard
      </a>
      <a href="{{ route('notifikasi.index') }}" class="side-link alumni-restricted {{ request()->routeIs('notifikasi.*') ? 'is-active' : '' }}">
        <span class="ico">🔔</span> Notifikasi
      </a>
      <a href="{{ route('program.index') }}" class="side-link alumni-restricted {{ request()->routeIs('program.*') ? 'is-active' : '' }}">
        <span class="ico">📘</span> Program Belajar
      </a>
      <a href="{{ route('kelassaya.index') }}" class="side-link alumni-restricted {{ request()->routeIs('kelassaya.*') ? 'is-active' : '' }}">
        <span class="ico">🎓</span> Kelas Saya
      </a>
      <a href="{{ route('booking.index') }}" class="side-link alumni-restricted {{ request()->routeIs('booking.*') ? 'is-active' : '' }}">
        <span class="ico">🗓️</span> Booking Kelas
      </a>
      <a href="{{ route('progresreport.index') }}" class="side-link {{ request()->routeIs('progresreport.*') ? 'is-active' : '' }}">
        <span class="ico">📊</span> Progress Report
      </a>
      <a href="{{ route('sertifikat.index') }}" class="side-link {{ request()->routeIs('sertifikat.*') ? 'is-active' : '' }}">
        <span class="ico">🏅</span> Sertifikat
      </a>
    </nav>

    <div class="px-5 pt-4 pb-6 border-t border-[#EFEBDB]">
      <div class="text-[13px] text-[#6b6652] leading-relaxed">
       {{ $username }}<br>
      HSK 2 · Hybrid
      </div>

      {{-- Demo toggle: karena database/otentikasi status alumni belum final,
           tombol ini HANYA untuk demo tampilan — nanti diganti logika
           `auth()->user()->isAlumni()` yang sesungguhnya. --}}
      <button id="alumniToggle" onclick="toggleAlumniMode()"
              class="alumni-toggle-btn mt-3.5 w-full text-[13px] font-medium rounded-2xl min-h-[44px] transition">
        🎓 Mode: Siswa Aktif (demo)
      </button>
    </div>
  </aside>

  {{-- ===================== KOLOM KANAN ===================== --}}
  <div class="flex-1 min-w-0 flex flex-col">

    {{-- TOPBAR KUNING --}}
    <header class="relative z-20 h-[72px] bg-[#FFD60A] flex items-center justify-between px-5 md:px-8 flex-shrink-0">
      <div class="flex items-center gap-2 bg-[#FFE34D] text-[#3a2e00] text-sm font-semibold px-4 min-h-[44px] rounded-2xl">
        Haoyou Student
      </div>

      <div class="flex items-center gap-3">
        <a href="{{ route('notifikasi.index') }}" aria-label="Notifikasi" class="top-btn alumni-restricted">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
          <span class="bell-dot"></span>
        </a>

        {{-- AVATAR + DROPDOWN PROFIL --}}
        <div class="relative">
          <button id="profileBtn" type="button" aria-label="Menu profil" aria-haspopup="true" aria-expanded="false"
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
                  <div class="font-bold text-[15px] leading-tight truncate">{{ $username }}</div>
                  <div class="text-[13px] text-[#6b6652]">HSK 2 · Hybrid</div>
                  </div>
            </div>

            <a href="{{ route('profil.index') }}" class="menu-item menu-restricted" role="menuitem">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>
              Profil Saya
            </a>

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="menu-item danger" role="menuitem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Keluar
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    {{-- ISI HALAMAN --}}
    <main class="flex-1 px-5 py-6 md:px-10 md:py-8">
      <div class="mb-7">
        <div class="text-sm text-[#6b6652]">
          Home &nbsp;›&nbsp; <span class="font-semibold text-[#1d1a0f]">{{ $title ?? 'Dashboard' }}</span>
        </div>

        @hasSection('page-heading')
          @yield('page-heading')
        @else
          <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">{{ $title ?? 'Dashboard' }}</h1>
          @if(!empty($subtitle))
            <p class="text-sm text-[#6b6652] mt-1">{{ $subtitle }}</p>
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
    var btn  = document.getElementById('profileBtn');
    var menu = document.getElementById('profileMenu');
    function close(){ menu.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      var open = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function(e){ if(!menu.contains(e.target)) close(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') close(); });
  })();

  // ---------- Demo mode alumni ----------
  var alumniMode = false;
  function toggleAlumniMode(){
    alumniMode = !alumniMode;
    document.body.classList.toggle('alumni-mode', alumniMode);
    document.getElementById('alumniToggle').textContent = alumniMode
      ? '🎓 Mode: Alumni (demo)'
      : '🎓 Mode: Siswa Aktif (demo)';
  }
  // Cegah klik ke menu yang di-restrict saat mode alumni aktif (khusus demo)
  document.querySelectorAll('.alumni-restricted, .menu-restricted').forEach(function(link){
    link.addEventListener('click', function(e){
      if(alumniMode){
        e.preventDefault();
        alert('Halaman ini tidak bisa diakses untuk akun Alumni. Alumni hanya bisa melihat Sertifikat & Progress Report saja yang lain tidak bisa di akses .');
      }
    });
  });
</script>
@endsection