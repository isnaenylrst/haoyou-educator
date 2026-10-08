@extends('layouts.dashboard')

@section('page-heading')
  @php
    $jam = now()->hour;
    $sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
  @endphp
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">
  {{ $sapaan }}, {{ auth()->user()->display_name }}
  </h1>
  <p class="text-sm text-[#6b6652] mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>
@endsection

@section('dashboard-content')

{{-- =========================================================
    STATISTIK
========================================================== --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-7">

  {{-- TOTAL POIN --}}
  <div class="bg-white border border-[#F3EFDD] rounded-3xl p-6 shadow-[0_2px_12px_rgba(120,95,0,.08)]">
    <div class="w-11 h-11 rounded-2xl bg-[#FFF3B8] flex items-center justify-center text-xl">⭐</div>
    <div class="text-[40px] font-bold leading-none mt-4">
      {{ number_format($stats['total_poin'], 0, ',', '.') }}
    </div>
    <div class="text-sm text-[#6b6652] mt-1.5">Total Poin</div>
  </div>

  {{-- RANKING --}}
  <div class="bg-white border border-[#F3EFDD] rounded-3xl p-6 shadow-[0_2px_12px_rgba(120,95,0,.08)]">
    <div class="w-11 h-11 rounded-2xl bg-[#FFF3B8] flex items-center justify-center text-xl">🏆</div>
    <div class="text-[40px] font-bold leading-none mt-4">
      #{{ $stats['peringkat'] }}
    </div>
    <div class="text-sm text-[#6b6652] mt-1.5">Peringkat Leaderboard</div>
  </div>

  {{-- KELAS MINGGU INI --}}
  <div class="bg-white border border-[#F3EFDD] rounded-3xl p-6 shadow-[0_2px_12px_rgba(120,95,0,.08)]">
    <div class="w-11 h-11 rounded-2xl bg-[#FFF3B8] flex items-center justify-center text-xl">📅</div>
    <div class="text-[40px] font-bold leading-none mt-4">
      {{ $stats['kelas_minggu_ini'] }}
    </div>
    <div class="text-sm text-[#6b6652] mt-1.5">Kelas Terjadwal Minggu Ini</div>
  </div>

</div>

{{-- =========================================================
    JADWAL TERDEKAT
========================================================== --}}
<div class="bg-white border border-[#F3EFDD] rounded-3xl p-6 md:p-7 mb-7 shadow-[0_2px_12px_rgba(120,95,0,.08)]">

  <div class="flex items-center justify-between mb-3">
    <h3 class="text-xl font-bold">Jadwal Terdekat</h3>
    <a href="{{ route('kelassaya.index') }}"
       class="text-sm font-semibold text-[#7a5c00] min-h-[44px] flex items-center hover:underline">
      Lihat semua →
    </a>
  </div>

  @forelse ($jadwalTerdekat as $jadwal)
    <div class="flex items-center justify-between py-3.5 border-b border-[#F3EFDD] last:border-0">
      <div class="flex items-center gap-3.5">
        {{-- ICON --}}
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl bg-[#FFF3B8]">
          {{ $jadwal['platform'] === 'online' ? '🀄' : '📖' }}
        </div>
        {{-- INFORMASI KELAS --}}
        <div>
          <div class="text-[15px] font-semibold">{{ $jadwal['judul'] }}</div>
          <div class="text-[13px] text-[#6b6652]">{{ $jadwal['waktu'] }}</div>
        </div>
      </div>

      {{-- ONLINE / OFFLINE --}}
      <span class="text-xs font-semibold px-3.5 py-1.5 rounded-full text-[#1d1a0f]
        {{ $jadwal['platform'] === 'online' ? 'bg-[#e2ecd7]' : 'bg-[#fff4b8]' }}">
        {{ ucfirst($jadwal['platform']) }}
      </span>
    </div>
  @empty
    <div class="py-9 flex flex-col items-center gap-3.5">
      <div class="w-[72px] h-[72px] rounded-3xl bg-[#FFF3B8] flex items-center justify-center text-4xl">📅</div>
      <p class="text-[15px] text-[#6b6652]">Belum ada jadwal kelas.</p>
    </div>
  @endforelse

</div>

{{-- =========================================================
    LEADERBOARD
========================================================== --}}
<div class="bg-white border border-[#F3EFDD] rounded-3xl p-6 md:p-7 shadow-[0_2px_12px_rgba(120,95,0,.08)]">

  <h3 class="text-xl font-bold mb-4">Leaderboard Poin</h3>

  <div class="flex flex-col gap-1.5">
    @forelse ($leaderboard as $item)
      @php $saya = $item['nama'] === $student->name; @endphp

      <div class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-[15px]
                  {{ $saya ? 'bg-[#FFF8D6] border border-[#F2D84A]' : '' }}">

        {{-- RANK --}}
        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-[#1d1a0f]
                    {{ $item['rank'] === 1 ? 'bg-[#FFD60A]' : 'bg-[#f1eedf]' }}">
          {{ $item['rank'] }}
        </div>

        {{-- NAMA --}}
        <span class="flex-1 {{ $saya ? 'font-semibold' : 'font-medium' }}">
          {{ $item['nama'] }}
          @if ($saya)
            <span class="font-medium text-[#6b6652]">(kamu)</span>
          @endif
        </span>

        {{-- POIN --}}
        <span class="font-bold text-base">{{ number_format($item['poin'], 0, ',', '.') }}</span>
      </div>
    @empty
      <div class="py-6 text-center text-sm text-[#6b6652]">Belum ada data leaderboard.</div>
    @endforelse
  </div>

</div>

@endsection