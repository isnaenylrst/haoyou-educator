@extends('layouts.app')

@section('title', 'Pendaftaran Berhasil — Haoyou Educator')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-paper px-5 py-10">
  <div class="w-full max-w-[420px] bg-white border border-line rounded-[20px] p-9 text-center">
    <div class="ring-deco mb-5" style="width:70px;height:70px;">
      <span class="text-xl">✓</span>
    </div>
    <h2 class="text-[22px] font-semibold mb-3">Pendaftaran Berhasil!</h2>
    <p class="text-[13.5px] text-[#6b6555] leading-relaxed mb-7">
      Silakan tunggu Admin menghubungi Anda via WA/Email maksimal 1x24 Jam
      untuk proses pembayaran dan aktivasi akun.
    </p>
    <a href="{{ route('landing') }}" class="inline-block px-6 py-3 rounded-full text-sm font-semibold bg-ink text-white hover:bg-oliveDark transition">
      Kembali ke Halaman Utama
    </a>
  </div>
</div>
@endsection