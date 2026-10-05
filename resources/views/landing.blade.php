@extends('layouts.app')

@section('title', 'Haoyou Educator — Les Mandarin Terbaik | Game Based Learning Chinese Course')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  /* Semua class diawali "hy-" dan di-scope di .hy supaya tidak bentrok dengan Tailwind / layout */
  html{ scroll-behavior:smooth; scroll-padding-top:90px; }
  .hy{
    --ink:#1E1B4B; --ink2:#2E2A6B; --sun:#FFD43B; --pink:#FF5C8A; --mint:#7BE0B5;
    --lav:#F1F0FF; --text:#E9E7FF; --muted:#B9B5E8; --dark:#14122F;
    background:var(--ink); color:var(--text); font-family:'Nunito',system-ui,sans-serif; line-height:1.65; overflow-x:hidden;
  }
  .hy *, .hy *::before, .hy *::after{ box-sizing:border-box; }
  .hy h1,.hy h2,.hy h3,.hy .hy-f{ font-family:'Bricolage Grotesque',sans-serif; }
  .hy a{ text-decoration:none; }
  .hy img{ max-width:100%; }
  .hy-wrap{ max-width:1180px; margin:0 auto; padding:0 24px; }
  .hy a:focus-visible,.hy button:focus-visible{ outline:3px solid var(--sun); outline-offset:3px; }

  /* ---------- Header ---------- */
  .hy-header{ position:sticky; top:0; z-index:30; background:var(--ink); }
  .hy-nav{ display:flex; align-items:center; gap:24px; padding:12px 0; position:relative; }
  .hy-logo{ display:flex; align-items:center; gap:12px; color:#fff; }
  .hy-logo img{ width:52px; height:52px; object-fit:contain; background:#fff; border-radius:50%; padding:4px; }
  .hy-logo b{ display:block; font:800 18px/1.1 'Bricolage Grotesque',sans-serif; }
  .hy-logo small{ font-size:11px; color:var(--muted); }
  .hy-links{ display:flex; gap:24px; list-style:none; margin:0 auto 0 auto; padding:0; }
  .hy-links a{ color:var(--text); font-weight:600; font-size:15px; }
  .hy-links a:hover{ color:var(--sun); }
  .hy-m-only{ display:none; }
  .hy-actions{ display:flex; gap:10px; }
  .hy-burger{ display:none; background:none; border:2px solid #6C68B8; color:#fff; border-radius:12px; width:44px; height:44px; font-size:22px; cursor:pointer; }

  .hy-btn{ display:inline-block; padding:13px 26px; border-radius:999px; font:800 15px 'Nunito',sans-serif; border:2px solid var(--sun); transition:transform .15s; cursor:pointer; }
  .hy-btn:hover{ transform:translateY(-2px); }
  .hy-btn.fill{ background:var(--sun); color:var(--ink); box-shadow:0 5px 0 #C9A000; }
  .hy-btn.line{ color:#fff; border-color:#6C68B8; background:transparent; }
  .hy-btn.line:hover{ border-color:#fff; }
  .hy-btn.sm{ padding:10px 20px; font-size:14px; }

  /* ---------- Hero ---------- */
  .hy-hero{ display:grid; grid-template-columns:1.1fr .9fr; gap:56px; align-items:center; padding:56px 0 96px; }
  .hy-tag{ display:inline-flex; gap:8px; background:var(--ink2); border-radius:999px; padding:7px 16px; font-weight:800; font-size:13px; color:var(--mint); margin-bottom:22px; }
  .hy-hero h1{ font:800 clamp(36px,5.4vw,66px)/1.06 'Bricolage Grotesque',sans-serif; color:#fff; letter-spacing:-.02em; margin:0; }
  .hy mark{ background:linear-gradient(transparent 55%,var(--pink) 55%); color:var(--sun); padding:0 4px; }
  .hy-lead{ max-width:520px; margin:26px 0 22px; font-size:18px; }
  .hy-quote{ display:inline-block; border-left:4px solid var(--sun); padding:4px 0 4px 16px; font:500 21px/1.35 'Bricolage Grotesque',sans-serif; color:var(--sun); margin:0 0 34px; }
  .hy-cta{ display:flex; gap:14px; flex-wrap:wrap; }
  .hy-pics{ position:relative; max-width:470px; width:100%; justify-self:center; }
  .hy-pics::before{ content:""; position:absolute; inset:26px -22px -22px 26px; background:var(--sun); border-radius:200px 200px 28px 28px; }
  .hy-pics > img{ position:relative; display:block; width:100%; aspect-ratio:4/4.6; object-fit:cover; border-radius:200px 200px 28px 28px; border:6px solid var(--ink); }
  .hy-chip{ position:absolute; z-index:2; background:#fff; color:var(--ink); border-radius:18px; padding:10px 16px; font-weight:800; font-size:14px; box-shadow:0 10px 24px rgba(0,0,0,.28); display:flex; gap:10px; align-items:center; }
  .hy-chip em{ font:normal 800 22px 'Bricolage Grotesque',serif; color:var(--pink); }
  .hy-c1{ left:-46px; top:22%; } .hy-c2{ right:-34px; bottom:14%; } .hy-c3{ left:-26px; bottom:-18px; }
  .hy-big{ position:absolute; right:-24px; top:-30px; z-index:2; width:96px; height:96px; border-radius:50%; background:#fff; border:4px solid var(--sun); box-shadow:0 10px 24px rgba(0,0,0,.28); display:grid; place-items:center; padding:6px; transform:rotate(10deg); }
  .hy-big img{ width:100%; height:100%; object-fit:contain; border-radius:50%; }

  /* strip (opsional) */
  .hy-strip{ background:#fff; color:var(--ink); padding:34px 0; }
  .hy-strip .hy-wrap{ display:grid; grid-template-columns:repeat(3,1fr); gap:28px; }
  .hy-it{ display:flex; gap:14px; align-items:flex-start; }
  .hy-ico{ flex:none; width:46px; height:46px; border-radius:14px; display:grid; place-items:center; font-size:22px; }
  .hy-it:nth-child(1) .hy-ico{ background:var(--sun); } .hy-it:nth-child(2) .hy-ico{ background:var(--mint); } .hy-it:nth-child(3) .hy-ico{ background:#FFC2D4; }
  .hy-it b{ display:block; font:800 17px 'Bricolage Grotesque',sans-serif; }
  .hy-it p{ margin:0; font-size:14px; color:#55527F; }

  /* ---------- Umum section ---------- */
  .hy-s{ padding:88px 0; }
  .hy-light{ background:#fff; color:var(--ink); }
  .hy-lavs{ background:var(--lav); color:var(--ink); }
  .hy-head{ text-align:center; max-width:620px; margin:0 auto 48px; }
  .hy-pill{ display:inline-block; font-weight:800; font-size:13px; padding:5px 14px; border-radius:999px; background:var(--sun); color:var(--ink); margin-bottom:14px; }
  .hy-light .hy-pill, .hy-lavs .hy-pill{ background:var(--ink); color:var(--sun); }
  .hy-head h2{ font-size:clamp(30px,4vw,48px); line-height:1.1; letter-spacing:-.02em; margin:0; }
  .hy-head p{ margin:14px 0 0; font-size:17px; opacity:.85; }
  .hy-white{ color:#fff; }

  /* Program */
  .hy-prog{ display:grid; grid-template-columns:repeat(6,1fr); gap:18px; }
  .hy-prog > div{ grid-column:span 2; background:var(--lav); border-radius:24px; padding:26px; font-weight:600; }
  .hy-prog > div:nth-child(4){ grid-column:2/4; } .hy-prog > div:nth-child(5){ grid-column:4/6; }
  .hy-prog b{ display:grid; place-items:center; width:42px; height:42px; border-radius:50%; background:var(--ink); color:var(--sun); font:800 17px 'Bricolage Grotesque',sans-serif; margin-bottom:14px; }
  .hy-prog p{ margin:0; }
  .hy-kelas{ margin-top:56px; background:var(--sun); border-radius:36px; padding:52px 28px; text-align:center; }
  .hy-kelas h3{ font-size:34px; margin:0 0 26px; }
  .hy-chips{ display:flex; flex-wrap:wrap; gap:12px; justify-content:center; max-width:760px; margin:0 auto; }
  .hy-chips span{ background:#fff; padding:12px 22px; border-radius:999px; font-weight:800; box-shadow:0 4px 0 var(--ink); }
  .hy-umur{ display:grid; grid-template-columns:repeat(4,1fr); gap:22px; }
  .hy-umur-card{ text-align:center; }
  .hy-umur-card .ph{ aspect-ratio:1; border-radius:28px; background:var(--lav); overflow:hidden; border:4px solid var(--ink); box-shadow:0 8px 0 var(--sun); transition:transform .2s; }
  .hy-umur-card:hover .ph{ transform:translateY(-6px) rotate(-1.5deg); }
  .hy-umur-card .ph img{ width:100%; height:100%; object-fit:cover; display:block; }
  .hy-umur-card h3{ font-size:19px; margin:18px 0 0; }
  .hy-umur-card p{ margin:0; font-size:14px; opacity:.75; }

  /* Service */
  .hy-svc{ max-width:760px; margin:0 auto; display:grid; gap:14px; }
  .hy-svc > div{ display:flex; align-items:center; gap:18px; background:var(--ink2); border-radius:22px; padding:16px 22px; font:800 19px 'Bricolage Grotesque',sans-serif; color:#fff; transition:transform .2s; }
  .hy-svc > div:hover{ transform:translateX(6px); }
  .hy-svc span{ flex:none; width:52px; height:52px; border-radius:50%; background:var(--sun); display:grid; place-items:center; font-size:24px; }

  /* Reward */
  .hy-flow{ display:flex; justify-content:center; align-items:center; gap:18px; margin-bottom:48px; flex-wrap:wrap; }
  .hy-flow > div{ text-align:center; font-weight:800; }
  .hy-flow span{ display:grid; place-items:center; width:68px; height:68px; border-radius:50%; background:var(--ink); font-size:28px; margin:0 auto 6px; }
  .hy-flow i{ font:800 26px 'Bricolage Grotesque'; font-style:normal; color:var(--pink); }
  .hy-rw{ display:grid; grid-template-columns:repeat(5,1fr); gap:16px; }
  .hy-rw figure{ margin:0; background:#fff; border-radius:24px; padding:14px 14px 18px; text-align:center; box-shadow:0 8px 0 var(--sun); border:2px solid var(--ink); transition:transform .2s; }
  .hy-rw figure:hover{ transform:translateY(-6px); }
  .hy-rw img{ width:100%; aspect-ratio:1; object-fit:contain; border-radius:14px; display:block; }
  .hy-rw figcaption{ margin-top:10px; font:800 14px/1.3 'Nunito',sans-serif; }

  /* Event */
  .hy-ev{ display:grid; grid-template-columns:.9fr 1.1fr; gap:48px; align-items:center; }
  .hy-ev p{ font-size:17px; margin:0 0 14px; }
  .hy-ev h3{ display:inline-flex; gap:10px; align-items:center; background:var(--ink); color:var(--sun); padding:8px 18px; border-radius:999px; font-size:17px; margin:0 0 14px; }
  .hy-hint{ font-weight:800; font-size:14px; color:var(--ink2); }
  .hy-bento{ display:grid; grid-template-columns:1.15fr 1fr 1fr; grid-template-rows:repeat(2,minmax(150px,1fr)); gap:14px; }
  .hy-bento > img{ grid-row:1/3; width:100%; height:100%; min-height:320px; object-fit:cover; border-radius:28px; border:5px solid #fff; box-shadow:8px 8px 0 var(--pink); }
  .hy-tile{ border:3px solid var(--ink); border-radius:24px; background:#fff; color:var(--ink); cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; padding:10px; font:800 14px 'Nunito',sans-serif; transition:transform .2s,box-shadow .2s,background .2s; overflow:hidden; }
  .hy-tile span{ font-size:clamp(34px,5vw,52px); line-height:1; transition:transform .25s; }
  .hy-tile img{ width:100%; height:80px; object-fit:cover; border-radius:12px; }
  .hy-tile:hover{ transform:translateY(-4px); box-shadow:0 6px 0 var(--ink); }
  .hy-tile:hover span{ transform:rotate(-8deg) scale(1.12); }
  .hy-tile.on{ background:var(--sun); box-shadow:0 6px 0 var(--ink); }
  .hy-cap{ grid-column:1/4; background:var(--ink); color:#fff; border-radius:20px; padding:14px 20px; display:flex; gap:14px; align-items:center; font-weight:600; }
  .hy-cap b{ font:800 20px 'Bricolage Grotesque'; color:var(--sun); }
  .hy-cap em{ font-style:normal; font-size:30px; }
  .hy-pop{ animation:hyPop .35s; } @keyframes hyPop{ 0%{ transform:scale(.9); opacity:.3; } 100%{ transform:none; opacity:1; } }

  /* Testimoni */
  .hy-stars{ color:var(--sun); font-size:26px; letter-spacing:4px; margin-top:8px; }
  .hy-tm{ display:grid; grid-template-columns:repeat(4,1fr); gap:18px; }
  .hy-tm img{ width:100%; aspect-ratio:4/5; object-fit:contain; object-position:center; background:#fff; padding:10px; display:block; border-radius:24px; box-shadow:0 8px 0 var(--sun); cursor:zoom-in; transition:transform .2s; }
  .hy-tm img:hover{ transform:translateY(-6px); }
  .hy-swipe{ display:none; text-align:center; color:var(--muted); font-weight:800; font-size:13px; margin:12px 0 0; }
  .hy-lb{ position:fixed; inset:0; background:rgba(20,18,47,.92); display:none; place-items:center; z-index:60; padding:20px; cursor:zoom-out; }
  .hy-lb.on{ display:grid; } .hy-lb img{ max-width:min(92vw,460px); max-height:88vh; border-radius:18px; }

  /* Tentang + harga */
  .hy-about{ text-align:center; }
  .hy-price{ margin:46px auto 0; max-width:520px; background:var(--sun); color:var(--ink); border-radius:40px; padding:44px 24px; box-shadow:0 10px 0 var(--pink); }
  .hy-price small{ font-weight:800; }
  .hy-price .n{ font:800 clamp(56px,10vw,96px)/1.05 'Bricolage Grotesque'; letter-spacing:-.03em; }
  .hy-price .hy-btn{ background:var(--ink); color:var(--sun); border-color:var(--ink); margin-top:20px; }

  /* Footer */
  .hy-footer{ background:var(--dark); padding:56px 0 28px; }
  .hy-footer h3{ text-align:center; font-size:22px; color:#fff; margin:0 0 28px; }
  .hy-ct{ display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
  .hy-ct > a, .hy-ct > div{ display:flex; gap:12px; align-items:center; background:var(--ink); border-radius:18px; padding:16px; color:#fff; transition:transform .2s; }
  .hy-ct > a:hover{ transform:translateY(-4px); }
  .hy-ct small{ display:block; font-size:12px; color:var(--muted); letter-spacing:.08em; }
  .hy-ct b{ font-size:15px; line-height:1.3; display:block; }
  .hy-copy{ margin:32px 0 0; border-top:1px solid #3A3680; padding-top:20px; text-align:center; font-size:13px; color:var(--muted); }

  /* ---------- Responsive ---------- */
  @media(max-width:1000px){
    .hy-ct{ grid-template-columns:repeat(2,1fr); }
    .hy-rw{ grid-template-columns:repeat(3,1fr); }
  }
  @media(max-width:900px){
    .hy-burger{ display:block; margin-left:auto; }
    .hy-actions .hy-btn.line{ display:none; }
    .hy-actions{ margin-left:0; }
    .hy-links{ display:none; position:absolute; top:100%; left:-24px; right:-24px; flex-direction:column; gap:0; background:var(--ink); padding:8px 24px 20px; border-top:1px solid #3A3680; box-shadow:0 20px 30px rgba(0,0,0,.3); }
    .hy-links.open{ display:flex; }
    .hy-links a{ display:block; padding:12px 0; font-size:17px; }
    .hy-m-only{ display:block; }
    .hy-hero,.hy-ev{ grid-template-columns:1fr; }
    .hy-hero{ padding-top:24px; }
    .hy-pics{ order:-1; max-width:340px; }
    .hy-c1{ left:-14px; } .hy-c2{ right:-10px; } .hy-c3{ left:-8px; bottom:-14px; } .hy-pics{ margin-bottom:24px; }
    .hy-strip .hy-wrap{ grid-template-columns:1fr; }
    .hy-prog{ grid-template-columns:1fr; }
    .hy-prog > div, .hy-prog > div:nth-child(4), .hy-prog > div:nth-child(5){ grid-column:auto; }
    .hy-umur{ grid-template-columns:repeat(2,1fr); }
    .hy-rw{ grid-template-columns:repeat(2,1fr); }
    .hy-tm{ grid-template-columns:repeat(2,1fr); }
    .hy-bento{ grid-template-columns:1fr 1fr; }
    .hy-bento > img{ grid-column:1/3; grid-row:auto; min-height:0; aspect-ratio:4/3; }
    .hy-cap{ grid-column:1/3; }
    .hy-s{ padding:64px 0; }
  }
  @media(max-width:600px){
    .hy-logo small{ display:none; }
    .hy-actions .hy-btn{ padding:10px 16px; font-size:14px; }
    .hy-cta .hy-btn{ flex:1; text-align:center; }
    .hy-pics::before{ inset:18px -10px -14px 18px; }
    .hy-tm{ display:flex; overflow-x:auto; scroll-snap-type:x mandatory; gap:14px; padding:6px 24px 12px; margin:0 -24px; -webkit-overflow-scrolling:touch; }
    .hy-tm img{ flex:0 0 76%; width:76%; scroll-snap-align:center; }
    .hy-swipe{ display:block; }
    .hy-ct{ grid-template-columns:1fr; }
    .hy-svc > div{ font-size:16px; padding:14px 16px; }
  }
  @media(prefers-reduced-motion:no-preference){
    .hy-chip{ animation:hyFl 4s ease-in-out infinite; } .hy-c2{ animation-delay:-2s; } .hy-c3{ animation-delay:-1s; animation-duration:5s; }
    @keyframes hyFl{ 50%{ transform:translateY(-8px); } }
  }
</style>
@endpush

@section('content')

@if (session('status'))
  <div style="background:#e2ecd7;border-bottom:1px solid #a9c98f;color:#4b6b2f;font-size:14px;padding:12px 6%;text-align:center;">
    {{ session('status') }}
  </div>
@endif

<div class="hy">

{{-- ===================== HEADER ===================== --}}
<header class="hy-header">
  <div class="hy-wrap">
    <nav class="hy-nav">
      <a class="hy-logo" href="#top">
        <img src="{{ asset('assets/img/logo.png') }}" alt="Haoyou Educator">
        <span><b>Haoyou Educator</b><small>Mandarin Learning Center</small></span>
      </a>
      <ul class="hy-links" id="hyMenu">
        <li><a href="#about">About</a></li>
        <li><a href="#program">Program Kursus</a></li>
        <li><a href="#ourservice">Our Service</a></li>
        <li><a href="#event">Event</a></li>
        <li><a href="#testimoni">Testimoni</a></li>
        <li><a href="#tentang">Tentang Kami</a></li>
        <li class="hy-m-only"><a href="{{ route('login') }}">Masuk Portal Siswa</a></li>
      </ul>
      <div class="hy-actions">
        <a href="{{ route('login') }}" class="hy-btn line sm">Masuk Portal Siswa</a>
        <a href="{{ route('pendaftaran.create') }}" class="hy-btn fill sm">Daftar Sekarang</a>
      </div>
      <button class="hy-burger" id="hyBurger" type="button" aria-label="Buka menu" aria-expanded="false">☰</button>
    </nav>
  </div>
</header>

{{-- ===================== HERO ABOUT ===================== --}}
<div class="hy-wrap" id="top">
  <section class="hy-hero" id="about">
    <div>
      <span class="hy-tag">🎮 Game Based Learning · Chinese Course</span>
      <h1>Sudah les Mandarin lama, tapi masih <mark>nggak berani</mark> ngomong Mandarin?</h1>
      <p class="hy-lead">Kami memperluas wawasan melalui pembelajaran bahasa Mandarin yang menyenangkan, diperkaya dengan permainan interaktif, pertukaran budaya, dan pengalaman edukatif yang membuat murid lebih aktif berbicara bahasa Mandarin.</p>
      <p class="hy-quote">Belajar Mandarin itu bukan soal hafalan, tapi kebiasaan.</p>
      <div class="hy-cta">
        <a href="{{ route('pendaftaran.create') }}" class="hy-btn fill">Mulai Belajar</a>
        <a href="{{ route('konsultasi.gratis') }}" class="hy-btn line">Konsultasi Gratis</a>
      </div>
    </div>
    <div class="hy-pics">
      <div class="hy-big"><img src="{{ asset('assets/img/logo.png') }}" alt="Logo Haoyou"></div>
      <img src="{{ asset('assets/img/kelas.jpeg') }}" alt="Foto siswa belajar Mandarin">
      <div class="hy-chip hy-c1"><em>你好!</em>Berani ngomong</div>
      <div class="hy-chip hy-c2">⭐ Belajar sambil main</div>
      <div class="hy-chip hy-c3">🎓 200++ siswa sudah bergabung</div>
    </div>
  </section>
</div>

{{-- Strip ringkasan (opsional, boleh dihapus) --}}
<section class="hy-strip"><div class="hy-wrap">
  <div class="hy-it"><span class="hy-ico">🎲</span><span><b>Permainan interaktif</b><p>Belajar lewat game, bukan sekadar menghafal.</p></span></div>
  <div class="hy-it"><span class="hy-ico">🏮</span><span><b>Pertukaran budaya</b><p>Mengenal budaya Tiongkok langsung dalam kelas.</p></span></div>
  <div class="hy-it"><span class="hy-ico">💬</span><span><b>Aktif berbicara</b><p>Murid terbiasa ngomong Mandarin setiap hari.</p></span></div>
</div></section>

{{-- ===================== PROGRAM KURSUS ===================== --}}
<section class="hy-s hy-light" id="program">
  <div class="hy-wrap">
    <div class="hy-head">
      <span class="hy-pill">Kurikulum</span>
      <h2>Program Kursus</h2>
      <p>Metode belajar yang dirancang supaya anak berani &amp; terbiasa berbicara Mandarin.</p>
    </div>

    @php
      $metodeBelajar = [
        'Kelas kecil (maks. 6 murid).',
        'Guru & murid pakai Bahasa Mandarin di dalam kelas (interaktif di dalam kelas).',
        'Fokus listening, speaking, writing, reading & kebiasaan sehari-hari.',
        'Reward system yang memotivasi anak aktif berbicara Mandarin.',
        'Program seru penerapan Bahasa Mandarin.',
      ];
      $pilihanKelas = [
        'Daily Activity Class', 'HSK Preparation', 'Business Class',
        'Traveling Class', 'Private Class', 'Native Speaker Program',
      ];
      $kelompokUmur = [
        ['nama' => 'Maochong', 'usia' => '3–6 Tahun',   'foto' => 'maochong.jpeg'],
        ['nama' => 'Jianer',   'usia' => '7–9 Tahun',   'foto' => 'jianer.jpeg'],
        ['nama' => 'Hudie',    'usia' => '10–15 Tahun', 'foto' => 'hudie.jpeg'],
        ['nama' => 'Feixiang', 'usia' => '15++ Tahun',  'foto' => 'feixiang.jpeg'],
      ];
    @endphp

    <div class="hy-prog">
      @foreach ($metodeBelajar as $i => $poin)
        <div><b>{{ $i + 1 }}</b><p>{{ $poin }}</p></div>
      @endforeach
    </div>

    <div class="hy-kelas">
      <span class="hy-pill">Pilih sesuai kebutuhan</span>
      <h3>Pilihan Kelas</h3>
      <div class="hy-chips">
        @foreach ($pilihanKelas as $kelas)
          <span>{{ $kelas }}</span>
        @endforeach
      </div>
    </div>

    <div class="hy-head" style="margin-top:72px;">
      <span class="hy-pill">Sesuai usia</span>
      <h2>Pilihan Kelompok Umur</h2>
    </div>
    <div class="hy-umur">
      @foreach ($kelompokUmur as $k)
        <div class="hy-umur-card">
          <div class="ph"><img src="{{ asset('assets/img/' . $k['foto']) }}" alt="{{ $k['nama'] }}" loading="lazy"></div>
          <h3>{{ $k['nama'] }}</h3>
          <p>({{ $k['usia'] }})</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== OUR SERVICE ===================== --}}
<section class="hy-s" id="ourservice">
  <div class="hy-wrap">
    <div class="hy-head">
      <span class="hy-pill">Layanan profesional</span>
      <h2 class="hy-white">Our Service</h2>
      <p>Haoyou juga menyediakan layanan profesional untuk kebutuhan personal maupun korporasi:</p>
    </div>
    <div class="hy-svc">
      <div><span>🗺️</span>Mandarin Travel Assistance</div>
      <div><span>🧑‍💼</span>Professional Tour Guide (Mandarin – Indonesia – English)</div>
      <div><span>📝</span>Certified Translator (Dokumen Resmi &amp; Bisnis)</div>
      <div><span>💬</span>Professional Interpreter (Meeting, Event, Negotiation)</div>
      <div><span>📄</span>Mandarin Book Typing &amp; Academic Formatting</div>
    </div>
  </div>
</section>

{{-- ===================== REWARD SYSTEM ===================== --}}
<section class="hy-s hy-light" id="reward">
  <div class="hy-wrap">
    <div class="hy-head">
      <span class="hy-pill">Motivasi belajar</span>
      <h2>Reward System</h2>
      <p>Semakin aktif belajar &amp; berbicara Mandarin, semakin banyak reward yang bisa ditukar.</p>
    </div>
    <div class="hy-flow">
      <div><span>📖</span>Belajar</div><i>→</i>
      <div><span>⭐</span>Poin</div><i>→</i>
      <div><span>🎁</span>Hadiah</div>
    </div>

    @php
      $katalogHadiah = [
        ['nama' => 'Samsung Smart TV 32 Inch', 'gambar' => 'samsung.jpg'],
        ['nama' => 'Nintendo Switch OLED v2',  'gambar' => 'nintendo.jpg'],
        ['nama' => 'PlayStation 5 Original',   'gambar' => 'ps5.jpg'],
        ['nama' => 'Sepeda Listrik',           'gambar' => 'sepedah.jpg'],
        ['nama' => 'Mobil Listrik Anak',       'gambar' => 'mobil.jpg'],
      ];
    @endphp
    <div class="hy-rw">
      @foreach ($katalogHadiah as $hadiah)
        <figure>
          <img src="{{ asset('assets/img/' . $hadiah['gambar']) }}" alt="{{ $hadiah['nama'] }}" loading="lazy">
          <figcaption>{{ $hadiah['nama'] }}</figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== EVENT ===================== --}}
<section class="hy-s hy-lavs" id="event">
  <div class="hy-wrap">
    <div class="hy-head">
      <span class="hy-pill">Belajar lewat pengalaman</span>
      <h2>Event</h2>
    </div>

    @php
      // Isi 'foto' dengan nama file di public/assets/img/ bila sudah punya foto kegiatannya (contoh: 'event-parfum.jpg').
      // Kalau null, kotak menampilkan emoji.
      $eventTiles = [
        ['emoji' => '🧴', 'label' => 'Parfum',          'judul' => 'Membuat parfum',        'zh' => '香水 · xiāngshuǐ',     'foto' => null],
        ['emoji' => '🍕', 'label' => 'Pizza',           'judul' => 'Membuat pizza',         'zh' => '披萨 · pīsà',          'foto' => null],
        ['emoji' => '🥪', 'label' => 'Steak sandwich',  'judul' => 'Membuat steak sandwich','zh' => '三明治 · sānmíngzhì',  'foto' => null],
        ['emoji' => '✨', 'label' => 'Dll',             'judul' => 'Dan masih banyak lagi', 'zh' => '好玩 · hǎowán (seru!)', 'foto' => null],
      ];
      $tileAktif = 1;
    @endphp

    <div class="hy-ev">
      <div>
        <p>Di Haoyou, bahasa Mandarin tidak hanya dipelajari dari buku, tetapi juga lewat pengalaman nyata.</p>
        <h3>✔ Event edukasi interaktif</h3>
        <p>Siswa berbicara Mandarin langsung sambil melakukan kegiatan seru. Mereka belajar kosakata baru, memahami instruksi, dan berlatih ngomong secara natural, jadi belajar lebih menyenangkan dan lebih membekas.</p>
        <p class="hy-hint">👆 Tap kegiatan untuk melihat kosakatanya</p>
      </div>

      <div class="hy-bento">
        <img src="{{ asset('assets/img/event.jpeg') }}" alt="Event belajar Mandarin" loading="lazy">
        @foreach ($eventTiles as $i => $t)
          <button type="button" class="hy-tile {{ $i === $tileAktif ? 'on' : '' }}"
                  data-e="{{ $t['emoji'] }}" data-t="{{ $t['judul'] }}" data-z="{{ $t['zh'] }}">
            @if ($t['foto'])
              <img src="{{ asset('assets/img/' . $t['foto']) }}" alt="{{ $t['judul'] }}">
            @else
              <span>{{ $t['emoji'] }}</span>
            @endif
            {{ $t['label'] }}
          </button>
        @endforeach
        <div class="hy-cap" id="hyCap" aria-live="polite">
          <em>{{ $eventTiles[$tileAktif]['emoji'] }}</em>
          <span><b>{{ $eventTiles[$tileAktif]['judul'] }}</b><br>{{ $eventTiles[$tileAktif]['zh'] }}</span>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ===================== TESTIMONI ===================== --}}
<section class="hy-s" id="testimoni">
  <div class="hy-wrap">
    <div class="hy-head">
      <span class="hy-pill">Bukti nyata</span>
      <h2 class="hy-white">Kata Mereka Setelah Belajar Disini</h2>
      <div class="hy-stars">★★★★★</div>
      <p>Kepuasan siswa dan orang tua menjadi motivasi kami untuk terus memberikan pengalaman belajar Mandarin yang menyenangkan dan berkualitas.</p>
    </div>

    @php
      $fotoTestimoni = ['testimoni1.jpg', 'testimoni2.jfif', 'testimoni3.jfif', 'testimoni4.jfif'];
    @endphp
    <div class="hy-tm">
      @foreach ($fotoTestimoni as $i => $file)
        <img src="{{ asset('assets/img/' . $file) }}" alt="Testimoni {{ $i + 1 }}" loading="lazy">
      @endforeach
    </div>
    <p class="hy-swipe">← geser untuk melihat lainnya →</p>
  </div>
</section>

{{-- ===================== TENTANG KAMI + HARGA ===================== --}}
<section class="hy-s hy-about" id="tentang" style="padding-top:40px;">
  <div class="hy-wrap">
    <div class="hy-head" style="margin-bottom:0;">
      <span class="hy-pill">Tentang kami</span>
      <h2 class="hy-white">Haoyou Educator</h2>
      <p>Mandarin Learning Center di Malang, mengajak murid belajar Mandarin lewat Game Based Learning, event nyata, dan sistem reward yang memotivasi.</p>
    </div>
    <div class="hy-price">
      <small>Harga mulai dari</small>
      <div class="n">Rp550rb</div>
      <small>/ bulan</small><br>
      <a href="{{ route('pendaftaran.create') }}" class="hy-btn">Daftar Sekarang</a>
    </div>
  </div>
</section>

{{-- ===================== FOOTER ===================== --}}
@php $waNomor = $whatsappAdmin ?? '62895352684913'; @endphp
<footer class="hy-footer" id="kontak">
  <div class="hy-wrap">
    <h3>Hubungi Kami</h3>
    <div class="hy-ct">
      <a href="https://wa.me/{{ $waNomor }}" target="_blank" rel="noopener">
        <iconify-icon icon="logos:whatsapp-icon" width="34"></iconify-icon>
        <span><small>WHATSAPP</small><b>(+62) 895-3526-84913</b></span>
      </a>
      <a href="https://instagram.com/haoyou.educator" target="_blank" rel="noopener">
        <iconify-icon icon="skill-icons:instagram" width="34"></iconify-icon>
        <span><small>INSTAGRAM</small><b>@haoyou.educator</b></span>
      </a>
      <a href="https://tiktok.com/@haoyoueducator" target="_blank" rel="noopener">
        <iconify-icon icon="logos:tiktok-icon" width="34"></iconify-icon>
        <span><small>TIKTOK</small><b>@haoyoueducator</b></span>
      </a>
      <div>
        <span style="font-size:30px;line-height:1;">📍</span>
        <span><small>ALAMAT</small><b>Jl. Terusan Dieng No. 9E,<br>Malang 65115</b></span>
      </div>
    </div>
    <p class="hy-copy">© 2026 Haoyou Educator. Semua hak dilindungi.</p>
  </div>
</footer>

{{-- Lightbox testimoni --}}
<div class="hy-lb" id="hyLb"><img alt=""></div>

</div>{{-- /.hy --}}

<script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
<script>
  // Menu mobile
  const burger = document.getElementById('hyBurger'), menu = document.getElementById('hyMenu');
  burger.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    burger.setAttribute('aria-expanded', open);
    burger.textContent = open ? '✕' : '☰';
  });
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    menu.classList.remove('open'); burger.textContent = '☰'; burger.setAttribute('aria-expanded', false);
  }));

  // Event: klik kegiatan -> ganti keterangan
  const cap = document.getElementById('hyCap'), tiles = document.querySelectorAll('.hy-tile');
  tiles.forEach(b => b.addEventListener('click', () => {
    tiles.forEach(x => x.classList.remove('on')); b.classList.add('on');
    cap.innerHTML = '';
    const em = document.createElement('em'); em.textContent = b.dataset.e;
    const sp = document.createElement('span'), bold = document.createElement('b');
    bold.textContent = b.dataset.t;
    sp.append(bold, document.createElement('br'), document.createTextNode(b.dataset.z));
    cap.append(em, sp);
    cap.classList.remove('hy-pop'); void cap.offsetWidth; cap.classList.add('hy-pop');
  }));

  // Testimoni: klik untuk memperbesar
  const lb = document.getElementById('hyLb');
  document.querySelectorAll('.hy-tm img').forEach(i => i.addEventListener('click', () => {
    lb.firstElementChild.src = i.src; lb.classList.add('on');
  }));
  lb.addEventListener('click', () => lb.classList.remove('on'));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') lb.classList.remove('on'); });
</script>

@endsection