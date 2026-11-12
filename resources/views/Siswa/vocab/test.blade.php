@extends('layouts.dashboard') 

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Vocab test</h1>
  <p class="text-sm text-[#6b6652] mt-1">Tes hafalan lewat Reading, Listening, dan Writing.</p>
@endsection

@push('styles') @include('Siswa.vocab._styles') @endpush

@section('dashboard-content')
@php
  $meta = [
    'reading'   => ['Reading test',   'Lihat hanzi, lalu tulis pinyin, nada, dan artinya (Indonesia atau Inggris).', 'Isi kolom “Pinyin”, “Nada”, dan “Arti” ya!'],
    'listening' => ['Listening test', 'Klik tombol putar, dengarkan, lalu tulis pinyin, nada, dan artinya.',        'Klik tombol putar, lalu isi “Pinyin”, “Nada”, dan “Arti” ya!'],
    'writing'   => ['Writing test',   'Dari arti yang diberikan, tulis sendiri hanzi, pinyin, dan nadanya.',         'Isi kolom “Hanzi”, “Pinyin”, dan “Nada” ya!'],
  ][$type];

  // kolom input per tes (urutan = urutan kolom di tabel)
  $fields = \App\Services\VocabTestEvaluator::FIELDS[$type];
  $label  = ['pinyin' => 'Pinyin', 'tone' => 'Nada ke-berapa', 'hanzi' => 'Hanzi', 'meaning' => 'Arti'];
  $ph     = ['pinyin' => 'tulis pinyin...', 'tone' => 'cth: 3 2', 'hanzi' => 'tulis hanzi...', 'meaning' => 'tulis arti...'];

  $firstNo = $words->firstItem();
@endphp

<div class="vb-tabs">
  @foreach(['reading' => 'Reading', 'listening' => 'Listening', 'writing' => 'Writing'] as $k => $l)
    <a class="vb-tab {{ $type === $k ? 'on' : '' }}" href="{{ route('student.vocab.test', $k) }}{{ $bab ? '?bab='.$bab : '' }}">{{ $l }}</a>
  @endforeach
</div>

<div class="vb-card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div><div class="vb-title" style="font-size:20px">{{ $meta[0] }}</div><div class="vb-sub">{{ $meta[1] }}</div></div>
    <div style="display:flex;align-items:flex-end;gap:6px"><div class="vb-bubble">{{ $meta[2] }}</div>@include('Siswa.vocab._star', ['size' => 52])</div>
  </div>

  <div class="vb-sub" style="margin-top:8px">
    Nada: tulis angka 1–4 untuk tiap suku kata, nada netral = 5. Contoh 你好 (nǐ hǎo) → <b>3 2</b>.
    Jawaban disimpan otomatis saat kamu pindah ke baris lain (atau tekan Enter).
  </div>

  {{-- garis progres penuh + bintang --}}
  <div class="vb-bar" style="margin-top:16px"><div id="bar"></div></div>
  <div class="vb-counter">
    <div class="n">⭐ <span id="nHafal">0</span> <span style="font-weight:400;color:#9CA294">/ <span id="nTotal">{{ $init['total'] }}</span> kosakata</span></div>
    <div class="note" id="note"></div>
  </div>

  {{-- pilih bab, tepat di atas tabel --}}
  <div style="margin-top:14px">@include('Siswa.vocab._chapters')</div>

  <div class="vb-wrap" style="margin-top:10px">
    <table class="vb-table">
      <thead><tr>
        <th style="width:40px">No</th>
        @if($type === 'reading')
          <th>Hanzi</th>
        @elseif($type === 'listening')
          <th style="width:70px">Audio</th>
        @else
          <th>Arti Indonesia</th><th>Meaning English</th>
        @endif
        @foreach($fields as $f)<th>{{ $label[$f] }}</th>@endforeach
        <th style="width:60px;text-align:center">Ragu</th><th style="width:100px">Status</th>
      </tr></thead>
      <tbody>
      @foreach($words as $i => $w)
        @php $r = $results[$w->id] ?? null; $st = $r->status ?? 'blank'; @endphp
        <tr data-word="{{ $w->id }}">
          <td>{{ $firstNo + $i }}</td>
          @if($type === 'reading')
            <td class="vb-hanzi">{{ $w->hanzi }}</td>
          @elseif($type === 'listening')
            <td><button type="button" class="vb-audio" data-hanzi="{{ $w->hanzi }}" data-src="{{ $w->audio_path ? asset('storage/'.$w->audio_path) : '' }}" aria-label="Putar audio">▶</button></td>
          @else
            <td>{{ $w->meaning_id }}</td><td>{{ $w->meaning_en }}</td>
          @endif

          @foreach($fields as $f)
            <td>
              <input class="vb-input vb-in" data-f="{{ $f }}"
                     value="{{ $r->{'answer_'.$f} ?? '' }}"
                     placeholder="{{ $ph[$f] }}" autocomplete="off"
                     @if($f === 'hanzi') lang="zh" @endif
                     style="{{ $f === 'tone' ? 'min-width:90px;max-width:130px' : '' }}">
            </td>
          @endforeach

          <td style="text-align:center"><input type="checkbox" class="vb-ragu" @checked($r && $r->is_ragu) aria-label="Ragu"></td>
          <td class="vb-st"><span class="vb-pill s-{{ $st }}">{{ ['hafal'=>'HAFAL','ragu'=>'RAGU','salah'=>'SALAH'][$st] ?? '-' }}</span></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div style="margin-top:12px">{{ $words->links('Siswa.vocab._pagination') }}</div>
</div>

<div class="vb-sum">
  <div class="c-ragu"><b>Kata masih ragu</b><small>review setiap 3–4 hari</small><div class="num" id="cRagu">0</div></div>
  <div class="c-salah"><b>Kata masih salah</b><small>review setiap hari</small><div class="num" id="cSalah">0</div></div>
  <div class="c-benar"><b>Kata hafal</b><small>sudah hafal</small><div class="num" id="cHafal">0</div></div>
</div>

<div class="vb-card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
    <div><div class="vb-title">Kata yang masih ragu / salah</div>
      <div class="vb-sub">Refresh untuk memasukkan kata baru &amp; mengacak urutan. Reset menghapus jawaban ragu/salah agar bisa mengulang sampai hafal.</div></div>
    <div style="display:flex;gap:8px"><button class="vb-btn" id="btnRefresh" type="button">🔄 Refresh &amp; acak</button><button class="vb-btn-out" id="btnReset" type="button">Reset</button></div>
  </div>
  <div style="display:flex;gap:6px;margin-bottom:10px" id="flt">
    <span class="vb-fchip on" data-f="all">Semua</span><span class="vb-fchip" data-f="ragu">Ragu</span><span class="vb-fchip" data-f="salah">Salah</span>
  </div>
  <div class="vb-wrap"><table class="vb-table">
    <thead><tr><th style="width:40px">No</th><th>{{ ['reading'=>'Hanzi','listening'=>'Audio','writing'=>'Arti'][$type] }}</th><th>Jawabanmu</th><th style="width:100px">Status</th></tr></thead>
    <tbody id="pendBody"></tbody>
  </table></div>
  <div id="pendEmpty" style="display:none;text-align:center;color:#9CA294;padding:18px;font-size:13px">belum ada kata di kategori ini</div>
</div>
<div class="vb-toast" id="toast"></div>

<script>
(function(){
  const CSRF=@json(csrf_token()), TYPE=@json($type), INIT=@json($init);
  const U={answer:@json(route('student.vocab.test.answer',$type)),pending:@json(route('student.vocab.test.pending',$type)),reset:@json(route('student.vocab.test.reset',$type))};
  const $=id=>document.getElementById(id); let pend=INIT.pending, flt='all';
  const toast=m=>{const t=$('toast');t.textContent=m;t.style.display='block';clearTimeout(t._h);t._h=setTimeout(()=>t.style.display='none',2400)};
  const pill=(s,l)=>{const p=document.createElement('span');p.className='vb-pill s-'+s;p.textContent=l;return p};
  async function req(url,method,body){
    const r=await fetch(url,{method,headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:body?JSON.stringify(body):undefined});
    if(!r.ok) throw new Error(r.status); return r.json();
  }
  function paint(j){
    const c=j.counts; pend=j.pending;
    $('nHafal').textContent=c.hafal; $('nTotal').textContent=j.total;
    $('cHafal').textContent=c.hafal; $('cRagu').textContent=c.ragu; $('cSalah').textContent=c.salah;
    $('bar').style.width=Math.max(1,c.hafal/j.total*100)+'%';
    $('note').textContent=(j.total-c.hafal)+' kosakata lagi! Banyak hafal, banyak bintang!';
    renderPend();
  }
  function renderPend(){
    const b=$('pendBody'); b.innerHTML='';
    const list=pend.filter(x=>flt==='all'||x.status===flt);
    $('pendEmpty').style.display=list.length?'none':'block';
    list.forEach((x,i)=>{
      const tr=document.createElement('tr'), td=n=>tr.appendChild(document.createElement('td'));
      td().textContent=i+1; const p=td(); p.textContent=x.prompt; if(TYPE==='reading') p.className='vb-hanzi';
      td().textContent=x.answer; td().appendChild(pill(x.status,x.label)); b.appendChild(tr);
    });
  }

  // simpan per baris: saat fokus keluar dari baris (atau Enter), atau saat "Ragu" diubah
  document.querySelectorAll('tr[data-word]').forEach(tr=>{
    let dirty=false;
    const send=async()=>{
      dirty=false;
      const body={word_id:tr.dataset.word,ragu:tr.querySelector('.vb-ragu').checked?1:0};
      tr.querySelectorAll('.vb-in').forEach(i=>{body[i.dataset.f]=i.value});
      try{
        const j=await req(U.answer,'POST',body);
        const st=tr.querySelector('.vb-st'); st.innerHTML=''; st.appendChild(pill(j.status,j.label)); paint(j);
      }catch(e){dirty=true;toast('Gagal menyimpan, coba lagi')}
    };
    tr.querySelectorAll('.vb-in').forEach(i=>{
      i.addEventListener('input',()=>{dirty=true});
      i.addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();i.blur();} });
    });
    tr.addEventListener('focusout',e=>{ if(dirty && !tr.contains(e.relatedTarget)) send(); });
    tr.querySelector('.vb-ragu').addEventListener('change',send);
  });

  $('flt').querySelectorAll('.vb-fchip').forEach(c=>c.addEventListener('click',()=>{
    $('flt').querySelectorAll('.vb-fchip').forEach(x=>x.classList.remove('on')); c.classList.add('on'); flt=c.dataset.f; renderPend();
  }));
  $('btnRefresh').onclick=async()=>{try{paint(await req(U.pending+'?shuffle=1','GET'));toast('Daftar diperbarui & diacak')}catch(e){toast('Gagal memuat')}};
  $('btnReset').onclick=async()=>{
    if(!confirm('Hapus semua jawaban kata ragu & salah di tab ini agar bisa diulang?')) return;
    try{await req(U.reset,'POST',{}); location.reload()}catch(e){toast('Gagal reset')}
  };
  document.querySelectorAll('.vb-audio').forEach(b=>b.addEventListener('click',()=>{
    if(b.dataset.src){ new Audio(b.dataset.src).play(); return; }
    if(!('speechSynthesis' in window)){ toast('Browser belum mendukung suara'); return; }
    const u=new SpeechSynthesisUtterance(b.dataset.hanzi); u.lang='zh-CN'; u.rate=.85;
    speechSynthesis.cancel(); speechSynthesis.speak(u);
  }));
  paint(INIT);
})();
</script>
@endsection