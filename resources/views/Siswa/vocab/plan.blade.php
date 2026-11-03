@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Study plan</h1>
  <p class="text-sm text-[#6b6652] mt-1">Isi rencana belajarmu. Belum paham alurnya? Buka tab <a href="{{ route('student.vocab.guide') }}" style="font-weight:700;text-decoration:underline">Panduan</a>.</p>
@endsection

@push('styles')
@include('Siswa.vocab._styles')
<style>
  .fl-next{display:flex;align-items:center;gap:14px;flex-wrap:wrap;background:#E6F5E9;border:1px solid #BCE0C4;border-radius:16px;padding:14px 18px;margin-bottom:16px;color:#2C6B3F;font-size:13.5px}
  .fl-next b{font-size:14.5px}.fl-next .vb-btn{margin-left:auto}
  .fl-go{text-decoration:none;display:inline-flex}

  /* ===== Tombol "Simpan rencana": hijau mint + teks dark green ===== */
  .vb-grid2 form .vb-btn{
    background:#D5DFCC !important;
    color:#2F4B3B !important;
    border:1.5px solid #B5C2A0 !important;
    box-shadow:none !important;
    font-weight:800;
  }
  .vb-grid2 form .vb-btn:hover{
    background:#C6D4BA !important;
    border-color:#2F4B3B !important;
  }

  /* ===== Blok "Ringkasan rencana": semua teks dark green, garis kuning dihapus ===== */
  .vb-grid2 .vb-card[style*="D5DFCC"],
  .vb-grid2 .vb-card[style*="D5DFCC"] .vb-title,
  .vb-grid2 .vb-card[style*="D5DFCC"] .vb-kv,
  .vb-grid2 .vb-card[style*="D5DFCC"] .vb-kv span,
  .vb-grid2 .vb-card[style*="D5DFCC"] b{
    color:#2F4B3B !important;
  }
  .vb-grid2 .vb-card[style*="D5DFCC"] .vb-kv{
    border:0 !important;
    border-bottom:1px solid rgba(47,75,59,.18) !important;
    background:transparent !important;
  }
  .vb-grid2 .vb-card[style*="D5DFCC"] .vb-kv:last-of-type{
    border-bottom:0 !important;
  }
  .vb-grid2 .vb-card[style*="D5DFCC"] .vb-kv span:last-child{
    font-weight:800;
  }
</style>
@endpush

@section('dashboard-content')

@if(session('ok'))
  <div class="fl-next">
    <div style="font-size:26px">🎉</div>
    <div><b>{{ session('ok') }}!</b><br>Sekarang lanjut belajar kosakata di Vocab List.</div>
    <a href="{{ route('student.vocab.list') }}" class="vb-btn fl-go">Lanjut ke Vocab List →</a>
  </div>
@endif
@if($errors->any())<div class="vb-flash" style="background:#FDEBE8;border-color:#F3BDB5;color:#B3382A">{{ $errors->first() }}</div>@endif

<div class="vb-grid2">
  <form method="POST" action="{{ route('student.vocab.plan.save') }}" class="vb-card" style="margin:0">
    @csrf
    <div class="vb-title">Rencana belajarmu</div>
    <div class="vb-sub" style="margin-bottom:16px">Isi sendiri, lalu selesaikan Huahua sesuai targetmu.</div>

    <div class="vb-field"><label>Tanggal mulai</label>
      <input type="date" name="start_date" id="pStart" value="{{ old('start_date', optional($plan)->start_date?->toDateString() ?? today()->toDateString()) }}"></div>
    <div class="vb-field"><label>Target hafal vocab per hari</label>
      <input type="number" name="words_per_day" id="pPer" min="1" max="500" placeholder="Ketik targetmu, mis. 20" value="{{ old('words_per_day', optional($plan)->words_per_day ?? 10) }}"></div>
    <div class="vb-field"><label>Tanggal selesai (target kamu)</label>
      <input type="date" name="end_date" id="pEnd" value="{{ old('end_date', optional($plan)->end_date?->toDateString() ?? today()->addDays((int) ceil($total / 10))->toDateString()) }}"></div>
    <div class="vb-field"><label>Waktu belajar harian</label>
      <select name="daily_minutes" id="pTime">
        @foreach($minutes as $v => $l)<option value="{{ $v }}" @selected(old('daily_minutes', optional($plan)->daily_minutes ?? 30) == $v)>{{ $l }}</option>@endforeach
      </select></div>
    <div class="vb-field"><label>Estimasi durasi <span style="font-weight:400;color:#888">(otomatis)</span></label>
      <input type="text" id="pDur" readonly style="background:#F6F4EA;font-weight:600"></div>
    <div id="pWarn" style="font-size:12.5px;margin:2px 0 14px"></div>
    <button class="vb-btn" type="submit">Simpan rencana</button>
  </form>

  <div class="vb-card" style="margin:0;background:#D5DFCC;border-color:#D5DFCC">
    <div class="vb-title" style="margin-bottom:12px">Ringkasan rencana</div>
    <div class="vb-kv"><span>Tanggal mulai</span><span id="sStart">-</span></div>
    <div class="vb-kv"><span>Target hafal per hari</span><span id="sPer">-</span></div>
    <div class="vb-kv"><span>Waktu belajar harian</span><span id="sTime">-</span></div>
    <div class="vb-kv"><span>Estimasi durasi</span><span id="sDur">-</span></div>
    <div class="vb-kv"><span>Tanggal selesai</span><span id="sEnd">-</span></div>
    <div class="vb-kv"><span>Status</span><span id="sStat">-</span></div>
    <div style="font-size:12px;color:#2F4B3B;margin-top:12px;line-height:1.5">Kalau lewat dari tanggal selesai dan belum tuntas, durasi otomatis ditambah <b>+7 hari</b> dari target.</div>
  </div>
</div>

<script>
(function(){
  const TOTAL=@json($total), DAY=864e5, $=id=>document.getElementById(id);
  const parse=v=>new Date(v+'T00:00:00Z'), iso=d=>d.toISOString().slice(0,10);
  const fmt=d=>d.toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric',timeZone:'UTC'});
  const today=parse(iso(new Date()));
  function calc(){
    $('sTime').textContent=$('pTime').selectedOptions[0].text;
    const per=+$('pPer').value;
    if(!(per>0)){ $('pDur').value='Isi target hafal per hari dulu'; return; }
    const st=parse($('pStart').value), en=parse($('pEnd').value);
    if(isNaN(st)||isNaN(en)) return;
    let dur=Math.round((en-st)/DAY), endFinal=en, note='', stat='Sesuai target';
    if(dur<1){ $('pDur').value='Tanggal selesai harus setelah tanggal mulai'; return; }
    const late=Math.ceil((today-en)/DAY);
    if(late>0){ const ext=Math.ceil(late/7)*7; dur+=ext; endFinal=new Date(en.getTime()+ext*DAY); note=' (termasuk +'+ext+' hari molor)'; stat='Molor +'+ext+' hari'; }
    const min=Math.ceil(TOTAL/per);
    $('pWarn').innerHTML=Math.round((en-st)/DAY)<min
      ? '<span style="color:#C0392B">Dengan '+per+' vocab/hari, butuh minimal '+min+' hari untuk '+TOTAL+' vocab.</span>'
      : '<span style="color:#666">Minimal '+min+' hari untuk '+TOTAL+' vocab pada '+per+' vocab/hari.</span>';
    $('pDur').value=dur+' hari'+note;
    $('sStart').textContent=fmt(st); $('sPer').textContent=per+' vocab'; $('sDur').textContent=dur+' hari';
    $('sEnd').textContent=fmt(endFinal); $('sStat').textContent=stat;
  }
  ['pStart','pEnd','pPer','pTime'].forEach(i=>$(i).addEventListener('input',calc)); calc();
})();
</script>
@endsection