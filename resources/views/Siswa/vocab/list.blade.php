@extends('layouts.dashboard') {{-- ganti dengan nama layout dashboard kamu --}}

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Vocabulary list</h1>
  <p class="text-sm text-[#6b6652] mt-1">Tempat semua vocab &amp; progres bintang kamu terkumpul.</p>
@endsection

@push('styles') @include('Siswa.vocab._styles') @endpush

@section('dashboard-content')
@php
  $stageNames = ['Seed','Watering','Growing','Blooming'];
  $starHtml = fn ($n) => '<span class="vb-stars">'.str_repeat('★', $n).'<i>'.str_repeat('★', 3 - $n).'</i></span>';
  $q = request('q');
@endphp

<div class="vb-card" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
  @include('Siswa.vocab._star', ['size' => 52])
  <div style="flex:1;min-width:240px">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <span class="vb-pill s-hafal">⭐ {{ $learned }} / {{ $total }} kata hafal</span>
      @if($state === 'on_track')
        <span class="vb-pill s-hafal">On track</span>
      @elseif($state === 'behind')
        <span class="vb-pill s-ragu">Behind</span>
      @else
        <a href="{{ route('student.vocab.plan') }}" class="vb-pill s-blank" style="text-decoration:none">Belum ada rencana → isi Study Plan</a>
      @endif
    </div>
    <div class="vb-bar" style="margin:10px 0 6px"><div style="width:{{ max(1, $pct) }}%"></div></div>
    <div style="font-size:13px;color:#6b6652">Terus pelajari {{ $total - $learned }} kosa kata! Banyak hafal, banyak bintang!</div>
  </div>
</div>

<div class="vb-grid3">
  <div class="vb-chip"><b>Tanda *</b> pada nomor menandakan vocab yang tidak ada di Reading/Listening/Writing test, namun ada penjelasannya di Vocab Traps.</div>
  <div class="vb-chip">动 = kata kerja · 名 = kata benda · 形 = kata sifat · 副 = kata keterangan · 量 = kata satuan · 代 = kata ganti · 介 = preposisi · 连 = kata penghubung · 叹 = kata seru</div>
  <div class="vb-chip">Centang kotak di kolom <b>contoh kalimat</b> untuk melihat semua contohnya langsung di bawah kata.</div>
</div>

<form method="GET" class="vb-card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:14px 16px">
  <input type="hidden" name="filter" value="{{ $filter }}">
  <input class="vb-input" style="flex:1;min-width:200px" type="text" name="q" value="{{ $q }}" placeholder="Cari hanzi, pinyin, atau arti">
  @foreach(['all' => 'Semua', 'belum' => 'Belum dipelajari', 'sudah' => 'Sudah dipelajari'] as $k => $l)
    <a class="vb-fchip {{ $filter === $k ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['filter' => $k, 'page' => null]) }}">{{ $l }}</a>
  @endforeach
</form>

<div class="vb-card flush">
  <div class="vb-wrap">
    <table class="vb-table">
      <thead><tr><th>No</th><th>Hanzi</th><th>Pinyin</th><th>Arti (Indo)</th><th>Meaning (English)</th><th>Jenis kata</th><th style="text-align:center">Contoh kalimat</th><th>Progress</th></tr></thead>
      <tbody>
        @forelse($words as $w)
          <tr>
            <td>{{ $w->order_number }}{{ $w->trap_only ? '*' : '' }}</td>
            <td class="vb-hanzi">{{ $w->hanzi }}</td>
            <td>{{ $w->pinyin }}</td>
            <td>{{ $w->meaning_id }}</td>
            <td>{{ $w->meaning_en }}</td>
            <td>{{ $w->word_type }}</td>
            <td style="text-align:center">
              @if($w->examples->count())
                <input type="checkbox" class="vb-extoggle" data-target="ex{{ $w->id }}" aria-label="Lihat contoh kalimat {{ $w->hanzi }}">
              @endif
            </td>
            <td>{!! $starHtml(min(3, (int) ($stars[$w->id] ?? 0))) !!}</td>
          </tr>
          @if($w->examples->count())
            <tr class="vb-exrow" id="ex{{ $w->id }}" style="display:none"><td colspan="8">
              @foreach($w->examples as $e)
                <div class="vb-ex-item"><b>{{ $e->sentence_hanzi }}</b><span>{{ $e->sentence_pinyin }}</span>→ {{ $e->sentence_meaning }}</div>
              @endforeach
            </td></tr>
          @endif
        @empty
          <tr><td colspan="8" style="text-align:center;color:#9CA294;padding:24px">Tidak ada kata yang cocok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="vb-legend">
    <b>Arti bintang (Progress):</b>
    {!! $starHtml(1) !!} 1 bintang = hafal di <b>Reading</b> ·
    {!! $starHtml(2) !!} 2 bintang = hafal di <b>Reading + Listening</b> ·
    {!! $starHtml(3) !!} 3 bintang = hafal di <b>Reading + Listening + Writing</b>.
    Bintang abu-abu {!! $starHtml(0) !!} = belum hafal di test mana pun. Semakin banyak kata yang kamu hafal di tiap Test, bintangnya otomatis bertambah.
  </div>
</div>

<div style="margin-top:12px">{{ $words->links() }}</div>

<script>
  document.querySelectorAll('.vb-extoggle').forEach(cb => cb.addEventListener('change', () => {
    document.getElementById(cb.dataset.target).style.display = cb.checked ? 'table-row' : 'none';
  }));
</script>
@endsection
