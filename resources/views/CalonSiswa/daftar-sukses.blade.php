@extends('layouts.app')

@section('title', 'Pendaftaran Berhasil — Haoyou Educator')

@section('content')
<div class="hy-page min-h-[80vh] flex items-center justify-center px-5 py-10">
  @include('partials.hy-bg')

  <div class="hy-card w-full max-w-[420px] text-center">
    <div class="ring-deco mb-5" style="width:70px;height:70px;">
      <span class="text-xl">✓</span>
    </div>
    <h2 class="text-[22px] font-semibold mb-3 text-[#1F1B4D]">Pendaftaran Berhasil!</h2>
    <p class="text-[13.5px] text-[#6b6555] leading-relaxed mb-7">
      Silakan tunggu Admin menghubungi Anda via WA/Email maksimal 1x24 Jam
      untuk proses pembayaran dan aktivasi akun.
    </p>
    <a href="{{ route('landing') }}" class="hy-btn" style="display:inline-block;width:auto;padding:14px 28px;">
      Kembali ke Halaman Utama
    </a>
  </div>
</div>
@endsection