@extends('layouts.app')

@section('title', 'Masuk Portal Siswa — Haoyou Educator')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-paper px-5 py-10">
  <div class="w-full max-w-[400px] bg-white border border-line rounded-[20px] p-9">


  <div class="flex justify-center mb-5">
    <img src="{{ asset('assets/img/logo.png') }}"
         alt="Haoyou Educator"
         class="w-24 object-contain">
  </div>

    <h2 class="text-center text-[22px] font-semibold mb-1.5">Masuk Portal Siswa</h2>
    <p class="text-center text-[12.5px] text-[#8a8571] mb-7">Akses diberikan setelah admin memverifikasi pendaftaran kamu.</p>

    @if ($errors->any())
      <div class="bg-[#f6e3df] border border-[#e4b6ab] text-[#8a3b2c] text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    @if (session('status'))
      <div class="bg-[#e2ecd7] border border-[#a9c98f] text-[#4b6b2f] text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <div class="mb-4">
        <label class="text-xs font-semibold block mb-1.5" style="color:#000000;"> 
          Nama Lengkap
        </label>
        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required
               placeholder="Sesuai data pendaftaran"
               class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
      </div>

      <div class="mb-4">
        <label class="text-xs font-semibold block mb-1.5" style="color:#000000;">Password</label>
        <input type="password" name="password" required
               placeholder="••••••••"
               class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
      </div>

      <label class="flex items-center gap-2 text-[12px] text-[#8a8571] mb-4">
        <input type="checkbox" name="remember"> Ingat saya
      </label>

      <button
    type="submit"
    class="w-full py-3.5 rounded-full text-[13.5px] font-semibold text-white transition hover:opacity-90"
    style="background:#ffDD05;color:#000;">
    Masuk Dashboard
    </button>

      {{-- <button type="submit" class="w-full py-3.5 rounded-full text-[13.5px] font-semibold bg-ink text-white hover:bg-oliveDark transition">
        Masuk Dashboard
      </button> --}}
    </form>

    <p class="text-center text-xs text-[#8a8571] mt-4.5 mt-5">
      Belum punya akun?
      <a href="{{ route('pendaftaran.create') }}"
      class="font-semibold no-underline"
      style="color:#ffbd08;">
        Daftar sebagai calon siswa
    </a>
      {{-- <a href="{{ route('pendaftaran.create') }}" class="text-gold font-semibold no-underline">Daftar sebagai calon siswa</a> --}}
    </p>
  </div>
</div>
@endsection