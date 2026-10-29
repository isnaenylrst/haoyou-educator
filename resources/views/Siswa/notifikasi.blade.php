@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-5">
  Sistem mengirim notifikasi otomatis setiap kali ada jadwal kelas (online/offline) yang akan segera dimulai.
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold">Belum Dibaca</h3>

    @if ($belumDibaca->isNotEmpty())
      <form method="POST" action="{{ route('notifikasi.read-all') }}">
        @csrf
        <button type="submit" class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">
          Tandai semua dibaca
        </button>
      </form>
    @endif
  </div>

  @forelse ($belumDibaca as $n)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🔔</div>
        <div>
          <div class="text-sm font-medium">
            @if ($n['url'])
              <a href="{{ $n['url'] }}" class="hover:underline">{{ $n['judul'] }}</a>
            @else
              {{ $n['judul'] }}
            @endif
          </div>
          <div class="text-xs text-gray-500">{{ $n['ket'] }}</div>
          <div class="text-[11px] text-gray-400 mt-0.5">{{ $n['waktu'] }}</div>
        </div>
      </div>
      <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#e2ecd7] text-[#4b6b2f]">Baru</span>
    </div>
  @empty
    <p class="text-xs text-gray-500 py-2">Tidak ada notifikasi baru.</p>
  @endforelse
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Sudah Dibaca</h3>

  @forelse ($sudahDibaca as $n)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">✅</div>
        <div>
          <div class="text-sm font-medium">{{ $n['judul'] }}</div>
          <div class="text-xs text-gray-500">{{ $n['ket'] }}</div>
          <div class="text-[11px] text-gray-400 mt-0.5">{{ $n['waktu'] }}</div>
        </div>
      </div>
      <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-pale text-oliveDark">Selesai</span>
    </div>
  @empty
    <p class="text-xs text-gray-500 py-2">Belum ada riwayat notifikasi.</p>
  @endforelse
</div>

@endsection
