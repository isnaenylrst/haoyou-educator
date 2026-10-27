@extends('layouts.dashboard') {{-- ganti dengan nama layout dashboard kamu --}}

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Vocabulary traps</h1>
  <p class="text-sm text-[#6b6652] mt-1">Beberapa vocab yang kamu harus tau perbedaannya!</p>
@endsection

@push('styles') @include('Siswa.vocab._styles') @endpush

@section('dashboard-content')
@php
  $eye = '<svg class="on-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>'
       . '<svg class="off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 5.1A9.8 9.8 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.4-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
  $firstNo = 0;
@endphp

<div class="vb-card">
  <form method="GET" class="vb-counter" style="margin:0">
    <input class="vb-input" style="flex:1;min-width:180px;background:#fff" name="q" value="{{ request('q') }}" placeholder="Cari pasangan kata...">
    <div class="n">⭐ <span id="doneN">{{ $done }}</span> <span style="font-weight:400;color:#9CA294">/ {{ $totalTrap }}</span></div>
    @include('Siswa.vocab._star', ['size' => 40])
  </form>
  <div style="font-size:12px;color:#6b6652;margin-top:8px"><span id="leftN">{{ $totalTrap - $done }}</span> kosa kata lagi! Banyak hafal, banyak bintang!</div>
</div>

<div class="vb-grid2">
  <div class="vb-chip">动 = kata kerja · 名 = kata benda · 形 = kata sifat · 副 = kata keterangan · 量 = kata satuan · 代 = kata ganti · 介 = preposisi · 连 = kata penghubung · 叹 = kata seru</div>
  <div class="vb-chip">Klik ikon <b>mata</b> untuk melihat beberapa contoh kalimat, lalu centang <b>Paham</b> jika sudah mengerti.</div>
</div>

@foreach([['Vocab dengan arti sama', $sameMeaning, true, '#FBF0DA', '#8A6212'], ['Vocab dengan hanzi sama', $sameHanzi, false, '#FFE680', '#6B5200']] as [$label, $rows, $withExp, $bg, $fg])
<div class="vb-card flush" style="margin-bottom:18px">
  <div style="background:{{ $bg }};color:{{ $fg }};font-weight:700;text-align:center;padding:12px">{{ $label }}</div>
  <div class="vb-wrap">
    <table class="vb-table">
      <thead><tr><th style="width:36px">No</th><th>Hanzi</th><th>Pinyin</th><th>Jenis kata</th><th>Arti</th>@if($withExp)<th>Penjelasan</th>@endif<th style="text-align:center">Contoh kalimat</th><th style="width:60px;text-align:center">Paham</th></tr></thead>
      <tbody>
      @forelse($rows as $i => $t)
        @php $w = $t->word; $cols = $withExp ? 8 : 7; @endphp
        <tr>
          <td>{{ $i + 1 }}</td><td class="vb-hanzi">{{ $w->hanzi }}</td><td>{{ $w->pinyin }}</td><td>{{ $w->word_type }}</td><td>{{ $w->meaning_id }}</td>
          @if($withExp)<td><ul class="vb-ul">@foreach($t->explanations ?? [] as $p)<li>{{ $p }}</li>@endforeach</ul></td>@endif
          <td style="text-align:center">
            <button type="button" class="vb-eye" data-target="tx{{ $label === 'Vocab dengan arti sama' ? 'a' : 'b' }}{{ $t->id }}" aria-label="Lihat contoh kalimat {{ $w->hanzi }}" title="Lihat contoh kalimat">{!! $eye !!}</button>
          </td>
          <td style="text-align:center"><input type="checkbox" class="vb-paham" data-url="{{ route('student.vocab.traps.understood', $w->id) }}" @checked(in_array($w->id, $understood)) aria-label="Paham {{ $w->hanzi }}"></td>
        </tr>
        <tr class="vb-exrow" id="tx{{ $label === 'Vocab dengan arti sama' ? 'a' : 'b' }}{{ $t->id }}" style="display:none"><td colspan="{{ $cols }}">
          @foreach($w->examples as $e)
            <div class="vb-ex-item"><b>{{ $e->sentence_hanzi }}</b><span>{{ $e->sentence_pinyin }}</span>→ {{ $e->sentence_meaning }}</div>
          @endforeach
        </td></tr>
      @empty
        <tr><td colspan="{{ $withExp ? 8 : 7 }}" style="text-align:center;color:#9CA294;padding:20px">Belum ada data.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endforeach

<script>
(function(){
  const CSRF=@json(csrf_token()), TOTAL=@json($totalTrap);
  document.querySelectorAll('.vb-eye').forEach(b=>b.addEventListener('click',()=>{
    const r=document.getElementById(b.dataset.target), open=r.style.display==='none';
    r.style.display=open?'table-row':'none'; b.classList.toggle('on',open);
  }));
  document.querySelectorAll('.vb-paham').forEach(cb=>cb.addEventListener('change',async()=>{
    try{
      const r=await fetch(cb.dataset.url,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
      if(!r.ok) throw 0; const j=await r.json();
      document.getElementById('doneN').textContent=j.done; document.getElementById('leftN').textContent=TOTAL-j.done;
      // kata yang sama bisa muncul di dua tabel: samakan centangnya
      document.querySelectorAll('.vb-paham[data-url="'+cb.dataset.url+'"]').forEach(x=>x.checked=j.understood);
    }catch(e){cb.checked=!cb.checked;alert('Gagal menyimpan, coba lagi')}
  }));
})();
</script>
@endsection
