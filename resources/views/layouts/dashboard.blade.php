@extends('layouts.app')

@section('title', ($title ?? 'Dashboard') . ' — Haoyou Educator')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

  body {
    font-family: 'Poppins', sans-serif;
  }
  .side-link{ display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:10px; font-size:13.5px; color:#1c1a14; margin-bottom:4px; transition:.15s; text-decoration:none; }
  .side-link:hover{ background:#fff; color:#1c1a14; }
  .side-link.is-active{ background:#FFDD05; color:#1c1a14; font-weight:600; }
  .side-link.alumni-restricted{ position:relative; }
  body.alumni-mode .side-link.alumni-restricted{ opacity:.35; pointer-events:none; }
  body.alumni-mode .side-link.alumni-restricted::after{ content:"🔒"; position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:12px; }
  .alumni-banner{ display:none; }
  body.alumni-mode .alumni-banner{ display:block; }
  .bell-btn::after{ content:""; width:8px; height:8px; background:#a6862a; border-radius:50%; position:absolute; top:9px; right:10px; border:2px solid #efece3; }
  .alumni-toggle-btn{ border:1px solid #FFDD05; color:#8a6d00; }
  .alumni-toggle-btn:hover{ background:#FFDD05; color:#1c1a14; }
</style>
@endpush

@section('content')
<div class="flex min-h-screen bg-[#f7f8fa]">

  {{-- ===================== SIDEBAR ===================== --}}
  <aside class="w-[230px] bg-white text-ink p-5 flex-shrink-0 hidden md:block border-r border-line">
    <div class="flex flex-col items-center justify-center mb-8">
    <img
        src="{{ asset('assets/img/logo.png') }}"
        alt="Haoyou"
        class="w-16 h-16 object-contain"
    >
    <h2 class="mt-2 text-[#FFDD05] font-bold text-lg tracking-wide">
        HAOYOU
    </h2>
    </div>
    <div class="border-b border-line mb-6"></div>

    <a href="{{ route('student.dashboard') }}" class="side-link alumni-restricted {{ request()->routeIs('student.dashboard') ? 'is-active' : '' }}">
    <span>🏠</span> Dashboard
    </a>

    <a href="{{ route('notifikasi.index') }}" class="side-link alumni-restricted {{ request()->routeIs('notifikasi.*') ? 'is-active' : '' }}">
        <span>🔔</span> Notifikasi
    </a>

    <a href="{{ route('program.index') }}" class="side-link alumni-restricted {{ request()->routeIs('program.*') ? 'is-active' : '' }}">
        <span>📘</span> Program Belajar
    </a>

    <a href="{{ route('kelassaya.index') }}" class="side-link alumni-restricted {{ request()->routeIs('kelassaya.*') ? 'is-active' : '' }}">
        <span>🎓</span> Kelas Saya
    </a>

    <a href="{{ route('booking.index') }}" class="side-link alumni-restricted {{ request()->routeIs('booking.*') ? 'is-active' : '' }}">
        <span>🗓️</span> Booking Kelas
    </a>

    <a href="{{ route('progresreport.index') }}" class="side-link {{ request()->routeIs('progresreport.*') ? 'is-active' : '' }}">
        <span>📊</span> Progress Report
    </a>

    <a href="{{ route('sertifikat.index') }}" class="side-link {{ request()->routeIs('sertifikat.*') ? 'is-active' : '' }}">
        <span>🏅</span> Sertifikat
    </a>

    <a href="{{ route('profil.index') }}" class="side-link alumni-restricted {{ request()->routeIs('profil.*') ? 'is-active' : '' }}">
        <span>👤</span> Profil
    </a>

    <div class="mt-8 pt-4 border-t border-line text-[11px] text-gray-500">
      Siswa: {{ auth()->user()->nama_lengkap ?? 'Dina Anggraini' }}<br>
      HSK 2 · Hybrid
    </div>

    {{-- Demo toggle: karena database/otentikasi status alumni belum final,
         tombol ini HANYA untuk demo tampilan — nanti diganti logika
         `auth()->user()->isAlumni()` yang sesungguhnya. --}}

    <button id="alumniToggle" onclick="toggleAlumniMode()"
            class="alumni-toggle-btn mt-4 w-full text-[11px] rounded-full py-2 transition">
      🎓 Mode: Siswa Aktif (demo)
    </button>
  </aside>

  {{-- ===================== MAIN ===================== --}}
  <main class="flex-1 p-6 md:p-10">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-black">
            Selamat Datang Kembali 👋🏻
        </h1>
        
        @if(!empty($subtitle))
         <p class="italic text-black text-sm mt-0.5">
         Selamat belajar hari ini
         </p>
        @endif
      </div>
      <a href="{{ route('notifikasi.index') }}" class="bell-btn relative w-11 h-11 rounded-full bg-white border border-line flex items-center justify-center">🔔</a>
    </div>

    @yield('dashboard-content')
  </main>
</div>

<script>
  var alumniMode = false;
  function toggleAlumniMode(){
    alumniMode = !alumniMode;
    document.body.classList.toggle('alumni-mode', alumniMode);
    document.getElementById('alumniToggle').textContent = alumniMode
      ? '🎓 Mode: Alumni (demo)'
      : '🎓 Mode: Siswa Aktif (demo)';
  }
  // Cegah klik ke menu yang di-restrict saat mode alumni aktif (khusus demo)
  document.querySelectorAll('.side-link.alumni-restricted').forEach(function(link){
    link.addEventListener('click', function(e){
      if(alumniMode){
        e.preventDefault();
        alert('Halaman ini tidak bisa diakses untuk akun Alumni. Alumni hanya bisa melihat Sertifikat & Progress Report saja yang lain tidak bisa di akses .');
      }
    });
  });
</script>
@endsection