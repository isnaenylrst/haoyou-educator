@extends('layouts.app')

@section('title', 'Masuk Portal Siswa — Haoyou Educator')

@section('content')
<div class="hy-page min-h-[80vh] flex items-center justify-center px-5 py-10">
  @include('partials.hy-bg')

  <div class="hy-card w-full max-w-[400px]">

    <div class="flex justify-center mb-5">
      <img src="{{ asset('assets/img/logo.png') }}"
           alt="Haoyou Educator"
           class="w-24 object-contain">
    </div>

    <h2 class="text-center text-[22px] font-semibold mb-1.5 text-[#1F1B4D]">Masuk Portal Siswa</h2>
    <p class="text-center text-[12.5px] text-[#6B6A85] mb-7">Akses diberikan setelah admin memverifikasi pendaftaran kamu.</p>

    @if ($errors->any())
      <div class="bg-[#f6e3df] border border-[#e4b6ab] text-[#8a3b2c] text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    @if (session('error'))
      <div class="bg-[#f6e3df] border border-[#e4b6ab] text-[#8a3b2c] text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4">
        {{ session('error') }}
      </div>
    @endif

    @if (session('status'))
      <div class="bg-[#e2ecd7] border border-[#a9c98f] text-[#4b6b2f] text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ route('login.process') }}">
      @csrf

      <div class="mb-4">
        <label class="hy-lbl">username</label>
        <input type="text" name="username" value="{{ old('username') }}" required autofocus
               class="hy-in">
      </div>

      <div class="mb-4">
        <label class="hy-lbl">Password</label>
        <div class="relative">
          <input type="password" name="password" id="password" required
                 class="hy-in" style="padding-right:44px;">
          <button type="button" id="togglePassword" aria-label="Tampilkan password"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6B6A85] hover:text-[#1F1B4D] transition">
            <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
              <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
            </svg>
            <svg id="eyeClosed" class="hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/>
              <path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87"/>
              <path d="M3 3l18 18"/>
            </svg>
          </button>
        </div>
      </div>

      <label class="flex items-center gap-2 text-[12px] text-[#6B6A85] mb-4">
        <input type="checkbox" name="remember"> Ingat saya
      </label>

      <button type="submit" class="hy-btn">Masuk Dashboard</button>
    </form>

    <p class="text-center text-xs text-[#6B6A85] mt-5">
      Belum punya akun?
      <a href="{{ route('pendaftaran.create') }}" class="hy-link">
        Daftar sebagai calon siswa
      </a>
    </p>
  </div>
</div>

<script>
  document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    document.getElementById('eyeOpen').classList.toggle('hidden', show);
    document.getElementById('eyeClosed').classList.toggle('hidden', !show);
    this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  });
</script>
@endsection