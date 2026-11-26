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
  <div class="bg-white border border-[#d5dfcc] rounded-3xl p-6 shadow-[0_4px_18px_rgba(31,27,22,.08)]">
    <div class="w-11 h-11 rounded-2xl bg-[#ffe135] flex items-center justify-center text-xl">⭐</div>
    <div class="text-[40px] font-bold leading-none mt-4">
      {{ number_format($stats['total_poin'], 0, ',', '.') }}
    </div>
    <div class="text-sm text-[#6b6652] mt-1.5">Total Poin</div>
  </div>

  {{-- RANKING --}}
  <div class="bg-white border border-[#d5dfcc] rounded-3xl p-6 shadow-[0_4px_18px_rgba(31,27,22,.08)]">
    <div class="w-11 h-11 rounded-2xl bg-[#ffe135] flex items-center justify-center text-xl">🏆</div>
    <div class="text-[40px] font-bold leading-none mt-4">
      {{ $stats['peringkat'] ? '#' . $stats['peringkat'] : '–' }}
    </div>
    <div class="text-sm text-[#6b6652] mt-1.5">Peringkat Leaderboard</div>
  </div>

  {{-- KELAS MINGGU INI --}}
  <div class="bg-white border border-[#d5dfcc] rounded-3xl p-6 shadow-[0_4px_18px_rgba(31,27,22,.08)]">
    <div class="w-11 h-11 rounded-2xl bg-[#ffe135] flex items-center justify-center text-xl">📅</div>
    <div class="text-[40px] font-bold leading-none mt-4">
      {{ $stats['kelas_minggu_ini'] }}
    </div>
    <div class="text-sm text-[#6b6652] mt-1.5">Kelas Terjadwal Minggu Ini</div>
  </div>

</div>

{{-- =========================================================
    JADWAL TERDEKAT
========================================================== --}}
<div class="bg-white border border-[#d5dfcc] rounded-3xl p-6 md:p-7 mb-7 shadow-[0_4px_18px_rgba(31,27,22,.08)]">

  <div class="flex items-center justify-between mb-3">
    <h3 class="text-xl font-bold">Jadwal Terdekat</h3>
    <a href="{{ route('kelassaya.index') }}"
       class="text-sm font-semibold text-[#2d291a] min-h-[44px] flex items-center hover:underline">
      Lihat semua →
    </a>
  </div>

  @forelse ($jadwalTerdekat as $jadwal)
    <div class="flex items-center justify-between py-3.5 border-b border-[#d5dfcc] last:border-0">
      <div class="flex items-center gap-3.5">
        {{-- ICON --}}
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl bg-[#ffe135]">
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
      <div class="w-[72px] h-[72px] rounded-3xl bg-[#ffe135] flex items-center justify-center text-4xl">📅</div>
      <p class="text-[15px] text-[#6b6652]">Belum ada jadwal kelas.</p>
    </div>
  @endforelse

</div>

{{-- =========================================================
    LEADERBOARD (dengan halaman berikutnya)
========================================================== --}}
<div id="leaderboard" class="bg-white border border-[#d5dfcc] rounded-3xl p-6 md:p-7 mb-7 shadow-[0_4px_18px_rgba(31,27,22,.08)]">

  <div class="flex items-center justify-between mb-4">
    <h3 class="text-xl font-bold">Leaderboard Poin</h3>
    <span class="text-xs text-[#6b6652]">{{ $leaderboard->total() }} siswa berpoin</span>
  </div>

  <div class="flex flex-col gap-1.5">
    @forelse ($leaderboard as $item)
      @php $saya = $item['id'] === $student->id; @endphp

      <div class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-[15px]
                  {{ $saya ? 'bg-[#FFF8D6] border border-[#F2D84A]' : '' }}">

        {{-- RANK --}}
        <div class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center text-sm font-bold text-[#1d1a0f]
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

  {{-- NAVIGASI HALAMAN (bernomor) --}}
  @if ($leaderboard->hasPages())
    @php
      $now  = $leaderboard->currentPage();
      $last = $leaderboard->lastPage();
      $nomor = collect([1, $last])
          ->merge(range(max(1, $now - 2), min($last, $now + 2)))
          ->unique()->sort()->values();
      $btn  = 'min-w-[40px] h-10 px-3 inline-flex items-center justify-center rounded-full text-sm font-semibold';
    @endphp

    <nav class="mt-5 flex flex-wrap items-center justify-center gap-1.5" aria-label="Halaman leaderboard">
      @if ($leaderboard->onFirstPage())
        <span class="{{ $btn }} bg-[#f1eedf] text-[#a39e87]">←</span>
      @else
        <a href="{{ $leaderboard->previousPageUrl() }}#leaderboard" class="{{ $btn }} bg-[#f1eedf] text-[#1d1a0f] hover:bg-[#ffe135]" aria-label="Sebelumnya">←</a>
      @endif

      @foreach ($nomor as $i => $p)
        @if ($i > 0 && $p - $nomor[$i - 1] > 1)
          <span class="px-1 text-[#a39e87]">…</span>
        @endif

        @if ($p === $now)
          <span class="{{ $btn }} bg-[#ffe135] text-[#1d1a0f] border border-[#F2D84A]" aria-current="page">{{ $p }}</span>
        @else
          <a href="{{ $leaderboard->url($p) }}#leaderboard" class="{{ $btn }} bg-[#f1eedf] text-[#1d1a0f] hover:bg-[#ffe135]">{{ $p }}</a>
        @endif
      @endforeach

      @if ($leaderboard->hasMorePages())
        <a href="{{ $leaderboard->nextPageUrl() }}#leaderboard" class="{{ $btn }} bg-[#f1eedf] text-[#1d1a0f] hover:bg-[#ffe135]" aria-label="Berikutnya">→</a>
      @else
        <span class="{{ $btn }} bg-[#f1eedf] text-[#a39e87]">→</span>
      @endif
    </nav>
  @endif

</div>

{{-- =========================================================
    KATALOG HADIAH PER KATEGORI
========================================================== --}}
@php
  $poinSaya = (int) $stats['total_poin'];
  $tierSaya = collect($tiers)->first(
      fn ($t) => $poinSaya >= $t['min'] && ($t['max'] === null || $poinSaya <= $t['max'])
  )['nama'] ?? null;
@endphp

<div class="bg-white border border-[#d5dfcc] rounded-3xl p-6 md:p-7 shadow-[0_4px_18px_rgba(31,27,22,.08)]">

  <h3 class="text-xl font-bold">Hadiah Poin</h3>
  <p class="text-sm text-[#6b6652] mt-1 mb-5">Kumpulkan poin untuk naik kategori dan tukarkan dengan hadiah.</p>

  <div class="flex flex-col gap-4">
    @foreach ($tiers as $tier)
      @php
        $rentang = $tier['max'] === null
            ? '>' . number_format($tier['min'] - 1, 0, ',', '.')
            : number_format($tier['min'], 0, ',', '.') . ' - ' . number_format($tier['max'], 0, ',', '.');
        $tierKamu = $tierSaya === $tier['nama'];
      @endphp

      <details class="group rounded-3xl overflow-hidden border border-[#e8dc7a]" @if($tierKamu) open @endif>
        <summary class="cursor-pointer list-none bg-[#ffe135] px-5 py-4 flex items-center justify-between gap-3">
          <div>
            <div class="text-2xl md:text-3xl font-extrabold italic tracking-tight text-[#1d1a0f]">
              {{ $tier['nama'] }}
              @if ($tierKamu)
                <span class="not-italic align-middle ml-2 text-xs font-semibold px-3 py-1 rounded-full bg-[#1d1a0f] text-[#D5DFCC]">Kategorimu</span>
              @endif
            </div>
            <div class="text-sm text-[#2d291a]">{{ $rentang }} poin · {{ count($tier['items']) }} hadiah</div>
          </div>
          <span class="text-xl text-[#1d1a0f] transition-transform group-open:rotate-180">⌄</span>
        </summary>

        <div class="bg-[#eceee4] p-4 md:p-5 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
          @foreach ($tier['items'] as $hadiah)
            @php $cukup = $poinSaya >= $hadiah['poin']; @endphp
            <div class="bg-[#D5DFCC] border border-[#1d1a0f]/70 rounded-[28px] px-3 pt-4 pb-3 flex flex-col items-center text-center">

              {{-- FOTO / ICON --}}
              <div class="h-24 w-full flex items-center justify-center">
                @if (!empty($hadiah['image']))
                 <img src="{{ asset(str_contains($hadiah['image'], '/') ? $hadiah['image'] : 'images/hadiah/' . $hadiah['image']) }}"
                       alt="{{ $hadiah['nama'] }}" loading="lazy"
                       class="max-h-24 max-w-full object-contain">
                @else
                  <span class="text-5xl" aria-hidden="true">{{ $hadiah['icon'] }}</span>
                @endif
              </div>

              {{-- POIN --}}
              <div class="mt-2 text-[15px] font-extrabold italic text-[#1d1a0f]">
                {{ number_format($hadiah['poin'], 0, ',', '.') }} Points
              </div>

              {{-- NAMA --}}
              <div class="mt-1.5 w-full flex-1 flex items-center justify-center rounded-lg bg-[#1d1a0f] text-[#D5DFCC] text-[11px] md:text-xs font-semibold leading-snug px-2 py-1.5 min-h-[38px]">
                {{ $hadiah['nama'] }}
              </div>

              {{-- USIA --}}
              <div class="mt-1.5 text-[10px] font-bold italic text-[#1d1a0f]">{{ $hadiah['usia'] }}</div>

              @if ($cukup)
                <div class="mt-1.5 text-[10px] font-semibold px-2.5 py-0.5 rounded-full bg-[#e2ecd7] text-[#1d1a0f]">Poinmu cukup</div>
              @endif
            </div>
          @endforeach
        </div>
      </details>
    @endforeach
  </div>

</div>

@endsection