@extends('layouts.dashboard')

@section('dashboard-content')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .font-poppins {
        font-family: 'Poppins', sans-serif;
    }
</style>
@endpush

{{-- Dashboard Content --}}
<div class="bg-[#f7f8fa] min-h-screen font-poppins">

  {{-- Statistik --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <div class="bg-white border border-line rounded-2xl p-5">
      <div class="text-3xl font-bold text-black">
        {{ number_format($stats['total_poin'], 0, ',', '.') }}
      </div>
      <div class="text-xs text-gray-500 mt-1">Total Poin</div>
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">
      <div class="text-3xl font-bold text-black">
        #{{ $stats['peringkat'] }}
      </div>
      <div class="text-xs text-gray-500 mt-1">Peringkat Leaderboard</div>
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">
      <div class="text-3xl font-bold text-black">
        {{ $stats['kelas_minggu_ini'] }}
      </div>
      <div class="text-xs text-gray-500 mt-1">Kelas Terjadwal Minggu Ini</div>
    </div>

  </div>


  {{-- Jadwal Terdekat --}}
  <div class="bg-white border border-line rounded-2xl p-5 mb-5">

    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-black">
        Jadwal Terdekat
      </h3>

      <a href="{{ route('kelassaya.index') }}"
         class="text-xs text-black font-semibold hover:underline">
        Lihat semua →
      </a>
    </div>

    @foreach ($jadwalTerdekat as $jadwal)

      <div class="flex items-center justify-between py-3 border-b border-line last:border-0">

        <div class="flex items-center gap-3">

          <div class="w-11 h-11 rounded-xl bg-[#fff4b8] flex items-center justify-center text-lg">
            {{ $jadwal['platform'] === 'online' ? '🀄' : '📖' }}
          </div>

          <div>
            <div class="text-sm font-medium text-black">
              {{ $jadwal['judul'] }}
            </div>

            <div class="text-xs text-gray-500">
              {{ $jadwal['waktu'] }}
            </div>
          </div>

        </div>

        <span class="text-[11px] font-semibold px-3 py-1 rounded-full
          {{ $jadwal['platform'] === 'online'
              ? 'bg-[#e2ecd7] text-black'
              : 'bg-[#fff4b8] text-black' }}">

          {{ ucfirst($jadwal['platform']) }}

        </span>

      </div>

    @endforeach

  </div>


  {{-- Leaderboard --}}
  <div class="bg-white border border-line rounded-2xl p-5">

    <h3 class="font-semibold text-black mb-4">
      Leaderboard Poin
    </h3>

    @foreach ($leaderboard as $item)

      <div class="flex items-center gap-3 py-2.5 border-b border-line last:border-0 text-sm">

        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
          {{ $item['rank'] === 1
              ? 'bg-[#FFDD05] text-black'
              : 'bg-[#f1f1ed] text-black' }}">

          {{ $item['rank'] }}

        </div>

        <span class="text-black">
          {{ $item['nama'] }}
        </span>

        <span class="ml-auto font-bold text-black">
          {{ number_format($item['poin'], 0, ',', '.') }}
        </span>

      </div>

    @endforeach

  </div>

</div>

@endsection