@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Panduan</h1>
  <p class="text-sm text-[#6b6652] mt-1">Tonton video tutorial dan baca alur belajarnya sebelum mulai.</p>
@endsection

@push('styles')
@include('Siswa.vocab._styles')
<style>
  .fl-hero{background:linear-gradient(135deg,#FFF8D6,#FFEFA0);border:1px solid #F2DB80;border-radius:20px;padding:22px 24px;margin-bottom:22px;display:flex;gap:18px;align-items:center;flex-wrap:wrap}
  .fl-hero h2{font-size:20px;font-weight:800;color:#3a2e00}.fl-hero p{font-size:13px;color:#5c4500;margin-top:4px}
  .fl-meter{margin-left:auto;min-width:210px}
  .fl-meter .lbl{font-size:12px;font-weight:700;color:#5c4500;display:flex;justify-content:space-between;margin-bottom:6px}
  .fl-meter .bar{height:10px;background:#fff;border-radius:6px;overflow:hidden}.fl-meter .bar div{height:100%;background:#FFD60A;border-radius:6px;transition:.4s}

  .fl-list{position:relative;margin-bottom:26px}
  .fl-step{display:flex;gap:16px;position:relative;padding-bottom:16px}
  .fl-step:last-child{padding-bottom:0}
  .fl-step:not(:last-child)::before{content:"";position:absolute;left:21px;top:46px;bottom:-2px;width:3px;border-radius:3px;background:#EFEBDB}
  .fl-step.done:not(:last-child)::before{background:#6EE0B8}
  .fl-num{width:44px;height:44px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;background:#F5F4EE;color:#9CA294;border:3px solid #fff;box-shadow:0 0 0 2px #EFEBDB;position:relative;z-index:1}
  .fl-step.current .fl-num{background:#FFD60A;color:#3a2e00;box-shadow:0 0 0 3px #FFE34D,0 6px 14px rgba(255,214,10,.45)}
  .fl-step.done .fl-num{background:#6EE0B8;color:#14543C;box-shadow:0 0 0 2px #BCE0C4}
  .fl-card{flex:1;background:#fff;border:1px solid #E6E4DC;border-radius:18px;padding:16px 18px;transition:.15s}
  .fl-step.current .fl-card{border-color:#FFD60A;background:#FFFCF0;box-shadow:0 8px 22px rgba(224,166,47,.18)}
  .fl-step.done .fl-card{background:#F6FCF8;border-color:#D9EEDD}
  .fl-card:hover{transform:translateY(-1px)}
  .fl-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .fl-emoji{font-size:22px}.fl-title{font-weight:700;font-size:15.5px;color:#1d1a0f}
  .fl-badge{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px}
  .fl-badge.go{background:#FFD60A;color:#3a2e00}.fl-badge.ok{background:#DDF1E1;color:#2C6B3F}.fl-badge.wait{background:#F5F4EE;color:#9CA294}
  .fl-desc{font-size:13px;color:#6b6652;margin:6px 0 8px;line-height:1.6}
  .fl-tips{margin:0 0 10px;padding:0;list-style:none;font-size:12.5px;color:#4a4636}
  .fl-tips li{padding-left:18px;position:relative;margin-bottom:3px}.fl-tips li::before{content:"✦";position:absolute;left:0;color:#E0A62F;font-size:11px;top:1px}
  .fl-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .fl-meta{font-size:12px;color:#6b6652}
  .fl-chips{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
  .fl-chip{background:#FFF8D6;border:1px solid #F2DB80;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#5c4500;text-decoration:none}
  .fl-chip:hover{background:#FFD60A}
  .fl-go{text-decoration:none;display:inline-flex}
  .fl-sec{font-size:18px;font-weight:800;margin:6px 0 14px;display:flex;align-items:center;gap:10px}
  .fl-sec span{background:#FFD60A;border-radius:10px;padding:2px 12px;font-size:13px}

  .fl-video{background:#fff;border:1px solid #E6E4DC;border-radius:18px;padding:16px 18px;margin-bottom:26px}
  .fl-video .ratio{position:relative;padding-top:56.25%;border-radius:14px;overflow:hidden;background:#000}
  .fl-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
  .fl-empty{padding:34px 20px;text-align:center;color:#6b6652;background:#F6F4EA;border-radius:14px;font-size:13px}
</style>
@endpush

@section('dashboard-content')
@php
  $p = $progress;
  $done = [1 => $p['plan'], 2 => $p['tested'] > 0, 3 => $p['learned'] > 0, 4 => $p['paham'] > 0, 5 => $p['stars'] > 0];
  $doneCount = count(array_filter($done));
  $current = collect($done)->search(false);

  $steps = [
    1 => ['📝', 'Isi Study Plan', 'Tentukan tanggal mulai, target hafal per hari, tanggal selesai, dan waktu belajar harianmu. Ini jadi peta belajarmu.',
          ['Ketik sendiri target hafal per harinya, sesuai kemampuanmu', 'Estimasi durasi terhitung otomatis'],
          route('student.vocab.plan'), 'Buka Study Plan →', $p['plan'] ? 'Rencana sudah tersimpan' : 'Belum diisi'],
    2 => ['📖', 'Mulai belajar di Vocab List', 'Kenali kata-kata baru: hanzi, pinyin, arti, dan jenis kata. Pelajari sesuai target harianmu.',
          ['Centang kolom contoh kalimat untuk melihat beberapa contoh langsung di bawah kata', 'Pakai kotak pencarian & filter untuk menemukan kata'],
          route('student.vocab.list'), 'Buka Vocab List →', $p['tested'] > 0 ? 'Kamu sudah mulai berlatih' : 'Belum mulai'],
    3 => ['✍️', 'Latihan di Test', 'Uji hafalanmu lewat tiga jenis test. Status dinilai otomatis: hijau hafal, kuning ragu, merah salah.',
          ['Reading: lihat hanzi, ketik artinya', 'Listening: dengarkan audio, ketik artinya', 'Writing: lihat arti, tulis pinyin dan hanzi', 'Kata ragu/salah dikumpulkan di tabel bawah, bisa diulang sampai hafal'],
          route('student.vocab.test'), 'Mulai Test →', $p['learned'] . ' kata sudah hafal'],
    4 => ['⚠️', 'Pahami Vocab Traps', 'Pelajari kata yang mirip tapi artinya beda, supaya tidak tertukar saat ujian.',
          ['Klik ikon mata 👁 untuk melihat beberapa contoh kalimat', 'Centang “Paham” jika sudah mengerti'],
          route('student.vocab.traps'), 'Buka Vocab Traps →', $p['paham'] . ' kata sudah dipahami'],
    5 => ['⭐', 'Lihat hasil progresmu', 'Kembali ke Vocab List dan lihat kolom Progress. Semakin banyak kata yang kamu hafal di tiap test, semakin banyak bintangmu.',
          ['★ 1 bintang = hafal di Reading', '★★ 2 bintang = hafal di Reading + Listening', '★★★ 3 bintang = hafal di Reading + Listening + Writing'],
          route('student.vocab.list'), 'Lihat bintangku →', $p['stars'] . ' bintang terkumpul'],
  ];
@endphp

{{-- ================= VIDEO TUTORIAL ================= --}}
<div class="fl-sec"><span>Video</span> Tutorial belajar Vocab</div>
<div class="fl-video">
  @if($videoId)
    <div class="ratio">
      <iframe src="https://www.youtube-nocookie.com/embed/{{ $videoId }}" title="Tutorial Belajar Vocab"
              loading="lazy" allowfullscreen
              allow="accelerometer; encrypted-media; picture-in-picture"></iframe>
    </div>
  @else
    <div class="fl-empty">🎬 Video tutorial belum tersedia.</div>
  @endif
</div>

{{-- ================= ALUR BELAJAR ================= --}}
<div class="fl-hero">
  <div style="font-size:42px">🗺️</div>
  <div>
    <h2>Alur Belajar Vocab</h2>
    <p>5 langkah mudah dari merencanakan sampai mengumpulkan bintang. Ikuti urutannya ya!</p>
  </div>
  <div class="fl-meter">
    <div class="lbl"><span>Progres alurmu</span><span>{{ $doneCount }} / 5 langkah</span></div>
    <div class="bar"><div style="width:{{ $doneCount / 5 * 100 }}%"></div></div>
  </div>
</div>

<div class="fl-list">
  @foreach($steps as $n => [$emoji, $title, $desc, $tips, $url, $btn, $meta])
    @php $state = $done[$n] ? 'done' : ($current === $n ? 'current' : 'todo'); @endphp
    <div class="fl-step {{ $state }}">
      <div class="fl-num">{{ $done[$n] ? '✓' : $n }}</div>
      <div class="fl-card">
        <div class="fl-head">
          <span class="fl-emoji">{{ $emoji }}</span>
          <span class="fl-title">Langkah {{ $n }} — {{ $title }}</span>
          @if($state === 'done')<span class="fl-badge ok">✓ Selesai</span>
          @elseif($state === 'current')<span class="fl-badge go">👉 Giliranmu</span>
          @else<span class="fl-badge wait">Berikutnya</span>@endif
        </div>
        <div class="fl-desc">{{ $desc }}</div>
        <ul class="fl-tips">@foreach($tips as $t)<li>{{ $t }}</li>@endforeach</ul>

        @if($n === 3)
          <div class="fl-chips">
            <a class="fl-chip" href="{{ route('student.vocab.test', 'reading') }}">📖 Reading</a>
            <a class="fl-chip" href="{{ route('student.vocab.test', 'listening') }}">🎧 Listening</a>
            <a class="fl-chip" href="{{ route('student.vocab.test', 'writing') }}">✏️ Writing</a>
          </div>
        @endif

        <div class="fl-foot">
          <a href="{{ $url }}" class="vb-btn fl-go">{{ $btn }}</a>
          <span class="fl-meta">{{ $meta }}</span>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection