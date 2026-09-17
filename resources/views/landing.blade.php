@extends('layouts.app')

@section('title', 'Haoyou Educator — Les Mandarin Terbaik | Game Based Learning Chinese Course')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;1,9..144,500;1,9..144,600&display=swap" rel="stylesheet">
<style>
  :root{
    --mock-ink:#0f0f0c;
    --mock-paper:#ffffff;
    --mock-red:#d10000d6;
    --mock-olive-soft:#FFDD00;
    --mock-gold:#a9822c;
    --mock-line:#e6e6e0;
    --mock-muted:#6b6b62;
    --mock-yellow:#FFDD00;
  }
  .display2{ font-family:'Fraunces', serif; font-weight:600; }
  .display2-italic{ font-family:'Fraunces', serif; font-style:italic; font-weight:500; }
  .mock-label{ font-family:'Poppins'; font-size:11px; font-weight:600; letter-spacing:.22em; text-transform:uppercase; }
  .mock-wrap{ max-width:1180px; margin:0 auto; padding-left:6%; padding-right:6%; }

.dark-section{
    position:relative; overflow:hidden; color:#ffff07;
    background: linear-gradient(100deg, #ffffff 0%, #fffffd 62%, #fffef7 85%, #ffff00 100%);
  }
  
  .dark-section .blob{ position:absolute; border-radius:50%; filter:blur(70px); pointer-events:none; z-index:0; }
  .dark-section .blob-gold{ background: radial-gradient(circle, rgb(251, 255, 20) 0%, rgba(169,130,44,0) 70%); }
  .dark-section .blob-yellow{ background: radial-gradient(circle, rgb(255, 243, 12) 0%, rgba(124,145,66,0) 70%); }
  .dark-section > *{ position:relative; z-index:1; }
  .dark-section h1, .dark-section h2{ color:#1a1a12; }
  .dark-section .mock-label{ color:#5c4a00; }
  .dark-section .mock-label::before{ background:#5c4a00; }
  .dark-section p{ color:#fff423; }

  .stamp{
    width:88px; height:88px; border-radius:50%;
    border:2.5px solid var(--mock-olive); color:var(--mock-olive);
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces'; font-weight:600; font-size:22px;
    transform:rotate(-9deg); position:relative; flex-shrink:0;
    background: radial-gradient(circle at 40% 35%, rgba(78,90,39,.06), transparent 70%);
  }
  .stamp::before{ content:""; position:absolute; inset:6px; border-radius:50%; border:1px solid var(--mock-olive); opacity:.55; }
  .stamp-label{
    position:absolute; bottom:-9px; left:50%; transform:translateX(-50%);
    background:var(--mock-paper); padding:0 6px; font-family:'Poppins'; font-size:8px; font-weight:700;
    letter-spacing:.15em; color:var(--mock-olive);
  }

  .icon-badge{ width:46px; height:46px; border-radius:50%; border:1.5px solid currentColor; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .icon-badge svg{ width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:1.6; }

  .mock-btn{ padding:11px 22px; border-radius:999px; font-size:12.5px; font-weight:600; border:1.4px solid var(--mock-ink); cursor:pointer; transition:.15s; display:inline-block; }
  .mock-btn-ghost-dark{ background:transparent; color:#000000; border-color:rgba(0,0,0,.4); }
  .mock-btn-ghost-dark:hover{ background:#1a1a12; color:#fff8f8; border-color:#1a1a12; }
 
  .mock-btn-gold{ background:#ffdd05; border-color:#ffffff; color:#000000; font-weight: 700; }
  .mock-btn-gold:hover{ filter:brightness(1.4); transform: translateY(-2px); }

  .mock-hero{ padding:100px 0 120px; display:grid; grid-template-columns:1.15fr .85fr; gap:60px; align-items:center; }
  .mock-hero .mock-label{ color:hsla(28, 97%, 48%, 0.993); margin-bottom:22px; display:flex; align-items:center; gap:10px; } */
  .mock-hero .mock-label::before{ content:""; width:26px; height:1px; background:var(--mock-yellow); display:inline-block; }

  .mock-hero h1{ font-size:clamp(30px,4vw,46px); line-height:1.15; margin-bottom:22px; color:#1a1a12; }
  .mock-hero h1 em{ font-style:italic; color: #ff5d05; }
  .mock-hero .lede{ font-size:14.5px; color:#48483e; max-width:480px; margin-bottom:20px; }
  .mock-hero .quote{ font-size:19px; margin-bottom:34px; color:#ff5d05; } 

   .hero-visual{
    position:relative; aspect-ratio:4/5; border-radius:28px; overflow:hidden;
    background: linear-gradient(160deg, #ffffff 0%, #fff 65%);
    border:1px solid rgba(255,255,255,.12);
    display:flex; align-items:center; justify-content:center;
    box-shadow: 0 30px 60px -20px rgba(0,0,0,.5);
  }
  .hero-visual .stamp{ position:absolute; top:26px; right:26px; }
  .hero-visual .caption{ position:absolute; bottom:24px; left:24px; right:24px; font-family:'Fraunces'; font-style:italic; font-size:15px; color:var(--mock-olive); }
  .hero-visual .ph-text{ color:var(--mock-muted); font-size:12px; text-align:center; padding:0 20px; }

  .mock-section-head{ margin-bottom:40px; }
  .mock-section-head .mock-label{ color:var(--mock-olive); margin-bottom:10px; }
  .mock-section-head h2{ font-size:26px; }
  .mock-section-head p{ color:var(--mock-muted); font-size:13.5px; max-width:520px; margin-top:10px; }

  .metode-list{ display:grid; gap:1px; background:var(--mock-line); border:1px solid var(--mock-line); border-radius:16px; overflow:hidden; margin-bottom:60px; }
  .metode-item{ background:var(--mock-paper); display:flex; gap:16px; align-items:flex-start; padding:20px 26px; }
  .metode-item .num{ font-family:'Fraunces'; font-style:italic; color:#FF5D05; font-size:16px; width:22px; flex-shrink:0; }
  .metode-item p{ font-size:13.5px; color:var(--mock-ink); }

  .kelas-panel{
    position:relative; border-radius:24px; padding:48px 6%; margin-bottom:60px;
    background:#FFDD00;
  }
  .kelas-panel .mock-section-head{ text-align:center; margin-bottom:28px; }
  .kelas-panel .mock-section-head h2{ color:#ff5d05; }
  .kelas-panel .mock-label{ color:#ff5d05; justify-content:center; }
  .kelas-panel .mock-label::before{ display:none; }
  
  .kelas-panel .blob{ position:absolute; border-radius:50%; filter:blur(60px); pointer-events:none; }
  .kelas-panel > *{ position:relative; z-index:1; }
  .kelas-panel .mock-section-head{ text-align:center; margin-bottom:28px; }
  .kelas-panel .mock-section-head h2{ color:#000000; }
  .kelas-panel .mock-label{ color:#000000; justify-content:center; }
  .kelas-panel .mock-label::before{ display:none; }
  .kelas-grid{ display:flex; flex-wrap:wrap; justify-content:center; gap:12px; }
  .kelas-chip{ padding:11px 20px; border-radius:999px; border:1px solid rgba(0, 0, 0, 0.35); color:#000000; font-size:12.5px; font-weight:500; background:rgba(255,255,255,.06); }

  .umur-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:60px; }
  .umur-card{ text-align:center; }
  .umur-photo{
    aspect-ratio:1; border-radius:20px; background:#fff; border:1px solid var(--mock-line);
    display:flex; align-items:center; justify-content:center; color:var(--mock-muted); font-size:11px;
    margin-bottom:12px; position:relative;
  }
  .umur-photo::after{
    content:""; position:absolute; inset:8px; border:1px dashed var(--mock-line); border-radius:14px;
  }
  .umur-name{ font-family:'Fraunces'; font-weight:600; font-size:16px; }
  .umur-age{ font-size:11.5px; color:var(--mock-muted); }

  .hsk-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
  .hsk-card{ background:var(--mock-paper); border:1px solid var(--mock-line); border-radius:16px; padding:24px; }
  .hsk-tag{ display:inline-block; background:#030200; color:var(--mock-olive); font-size:11px; font-weight:700; padding:4px 10px; border-radius:999px; margin-bottom:12px; }
  .hsk-card h3{ font-size:16px; margin-bottom:6px; color:var(--mock-ink); }
  .hsk-card p{ font-size:13px; color:#000; line-height:1.6; }

 .service-list{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:18px;
    margin:0 auto 70px;
}

.service-item{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    width:100%;
    text-align:center;
    font-size:16px;
    color:#ff5d05;
    font-weight:500;
}
  .service-item .icon-badge{ color:#1b1b09; }
  
  .reward-flow{ display:flex; align-items:center; justify-content:center; gap:26px; margin:40px 0 60px; flex-wrap:wrap; }
  .reward-step{ text-align:center; }
  .reward-step .icon-badge{ width:60px; height:60px; margin:0 auto 12px; color:#ff5d05;
    border:2px solid #ff5d05;
    background:#fff;}
  .reward-step .icon-badge svg{ width:24px; height:24px; }
  .reward-step .step-label{ font-size:12.5px; font-weight:600; color:#000000; }
  .reward-arrow{ color:#ff5d05; font-size:20px; }
  .hadiah-grid{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:24px;
}

  .hadiah-card{
      text-align:center;
  }

  .hadiah-photo{
      width:180px;          /* Perbesar ukuran foto */
      height:180px;
      margin:0 auto 15px;
      overflow:hidden;
      border-radius:18px;
      background:#fff;
  }

  .hadiah-photo img{
      width:100%;
      height:100%;
      object-fit:cover;     /* Gambar memenuhi kotak */
      display:block;
  }

  .hadiah-name{
      font-size:16px;
      font-weight:600;
      color:#000;
      line-height:1.5;
  }
  
    .hadiah-name{
    font-size:11px;
    font-weight:600;
    line-height:1.4;
    color:#000;
}
  .event-grid2{ display:grid; grid-template-columns:1.3fr 1fr; gap:50px; align-items:center; }
  .event-grid2 p{ font-size:14.5px; margin-bottom:19px; }
  .event-grid2 .highlight{ color:var(--mock-olive-soft); font-weight:600; display:flex; gap:8px; align-items:center; }
  .event-photo{
    aspect-ratio:3/4;
    overflow:hidden;
    border-radius:24px;
    background:transparent;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 25px 50px rgba(0,0,0,.15);
}
  .event-img{
    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:24px;
    display:block;
}
  .event-title{
    font-size:48px;
    color:#000;
    margin-bottom:8px;
}

  .stars2{ letter-spacing:3px; color:#fff700; -webkit-text-stroke:.5px var(--mock-gold); font-size:18px; margin-top:8px; }
  .testi-grid2{ display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-top:36px; }
  .testi-card2{ background:#faf9f5; border:1px solid var(--mock-line); border-radius:16px; padding:24px; }
  .testi-card2 .quote2{ font-family:'Fraunces'; font-style:italic; font-size:15.5px; margin-bottom:14px; color:var(--mock-ink); }
  .testi-card2 .who2{ font-size:12px; font-weight:700; color:var(--mock-olive); }

  .testimoni-photo-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:25px;
  
}

.testimoni-photo{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 15px 35px rgba(0,0,0,.12);
    transition:.3s;
}

.testimoni-photo:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 45px rgba(0,0,0,.18);
}

.testimoni-photo img{
    width:100%;
    object-fit:contain;
    display:block;
}
  
  .harga-section{ padding:90px 0; text-align:center; background: #fff;
    position: relative;
    overflow: hidden;
}
  .harga-section .mock-label{ color: #000000; justify-content:center; }
  .harga-section .mock-label::before{ display:none; }
  .harga-section h2{ font-size:26px; margin-bottom:14px; color:#000; }
  .harga-section p.desc{ font-size:13.5px; color:#555; max-width:520px; margin:0 auto 46px; }
  .price-card{ display:inline-flex; flex-direction:column; align-items:center; gap:6px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.18); border-radius:28px; padding:40px 56px; position:relative; }
  .price-card .stamp{ position:absolute; top:-30px; right:-24px; background:var(--mock-ink); border-color:var(--mock-yellow); color:var(--mock-yellow); }
  .price-card .price-label{ font-size:14px; letter-spacing:.25em; color:#dc0707e7; font-weight:600; }
  .price-card .price{ font-family:'Fraunces'; font-size:72px; margin:6px 0; color:#000; }
  .price-card .price-sub{ font-size:14.5px; color:#dc0707e7; margin-bottom:24px; }

  @media(max-width:900px){
    .mock-hero{ grid-template-columns:1fr; }
    .metode-list, .service-list{ grid-template-columns:1fr; }
    .umur-grid, .hadiah-grid{ grid-template-columns:repeat(2,1fr); }
    .hsk-grid, .testi-grid2{ grid-template-columns:1fr; }
    .event-grid2{ grid-template-columns:1fr; }
  }
</style>
@endpush

@section('content')

@if (session('status'))
  <div class="bg-[#e2ecd7] border-b border-[#a9c98f] text-[#4b6b2f] text-sm px-[6%] py-3 text-center">
    {{ session('status') }}
  </div>
@endif

{{-- ===================== HEADER ===================== --}}
<header class="flex items-center justify-between px-[6%] py-3 bg-paper border-b border-line">
  <div class="flex items-center gap-3">
    <img
        src="{{ asset('assets/img/logo.png') }}"
        alt="Haoyou Educator"
        class="w-20 h-20 object-contain"
    >
    <div>
        <h1 class="text-lg font-bold text-black leading-none">
            Haoyou Educator
        </h1>
        <p class="text-[11px] tracking-[0.2em] text-gray-500 uppercase">
            Mandarin Learning Center
        </p>
    </div>
   </div>

  <nav class="hidden md:flex gap-7 text-sm">
    <a href="#about" class="opacity-80 hover:opacity-100">About</a>
    <a href="#program" class="opacity-80 hover:opacity-100">Program Kursus</a>
    <a href="#ourservice" class="opacity-80 hover:opacity-100">Our Service</a>
    <a href="#event" class="opacity-80 hover:opacity-100">Event</a>
    <a href="#testimoni" class="opacity-80 hover:opacity-100">Testimoni</a>
    <a href="#tentang" class="opacity-80 hover:opacity-100">Tentang Kami</a>
  </nav>
  <div class="flex gap-2.5">
    <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-full text-sm font-semibold border-[1.5px] border-ink hover:bg-ink hover:text-white transition">Masuk Portal Siswa</a>
    <a href="{{ route('pendaftaran.create') }}" class="px-5 py-2.5 rounded-full text-sm font-semibold bg-[#ffDD00] text-black font-semibold hover:bg-[#f7cf00]">Daftar Sekarang</a>
  </div>
</header>

{{-- ===================== HERO ABOUT ===================== --}}
<section id="about" class="dark-section">
  <div class="blob blob-gold" style="width:520px;height:520px;right:-140px;top:-160px;"></div>
  <div class="blob blob-olive" style="width:420px;height:420px;left:-120px;bottom:-180px;"></div>
  <div class="mock-wrap mock-hero">
    <div>
      <div class="mock-label">Game Based Learning · Chinese Course</div>
      <h1 class="display2">Sudah les Mandarin lama, tapi masih <em>nggak berani</em> ngomong Mandarin?</h1>
      <p class="lede">Kami memperluas wawasan melalui pembelajaran bahasa Mandarin yang menyenangkan, diperkaya dengan permainan interaktif, pertukaran budaya, dan pengalaman edukatif yang membuat murid lebih aktif berbicara bahasa Mandarin.</p>
      <p class="display2-italic quote">Belajar Mandarin itu bukan soal hafalan, tapi kebiasaan.</p>
      <div class="flex gap-3 flex-wrap">
        <a href="{{ route('pendaftaran.create') }}" class="mock-btn mock-btn-gold">Mulai Belajar</a>
        <a href="{{ route('konsultasi.gratis') }}" class="mock-btn mock-btn-ghost-dark">Konsultasi Gratis</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="stamp">好友<span class="stamp-label">HAOYOU</span></div>
      <img src="{{ asset('assets/img/kelas.jpeg') }}" alt="Foto siswa belajar" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; border-radius:28px;">
    </div>
  </div>
</section>

{{-- ===================== PROGRAM KURSUS ===================== --}}
<section id="program" class="mock-wrap py-20 border-t border-gray-100">

  <div class="mock-section-head" style="text-align:center;">
    <div class="mock-label" style="justify-content:center;">Kurikulum</div>
    <h2 class="display2">Program Kursus</h2>
    <p style="margin-left:auto;margin-right:auto;">Metode belajar yang dirancang supaya anak berani &amp; terbiasa berbicara Mandarin.</p>
  </div>

  <div class="metode-list">
    @php
      $metodeBelajar = [
        'Kelas kecil (maks. 6 murid).',
        'Guru & murid pakai Bahasa Mandarin di dalam kelas (interaktif di dalam kelas).',
        'Fokus listening, speaking, writing, reading & kebiasaan sehari-hari.',
        'Reward system yang memotivasi anak aktif berbicara Mandarin.',
        'Program seru penerapan Bahasa Mandarin.',
      ];
    @endphp
    @foreach ($metodeBelajar as $i => $poin)
      <div class="metode-item">
        <span class="num">{{ sprintf('%02d', $i + 1) }}</span>
        <p>{{ $poin }}</p>
      </div>
    @endforeach
  </div>

  <div class="kelas-panel">
    <div class="mock-section-head">
      <div class="mock-label">Pilih Sesuai Kebutuhan</div>
      <h2 class="display2">Pilihan Kelas</h2>
    </div>
    <div class="kelas-grid">
      @php
        $pilihanKelas = [
          'Daily Activity Class', 'HSK Preparation', 'Business Class',
          'Traveling Class', 'Private Class', 'Native Speaker Program',
        ];
      @endphp
      @foreach ($pilihanKelas as $kelas)
        <span class="kelas-chip">{{ $kelas }}</span>
      @endforeach
    </div>
  </div>

  <div class="mock-section-head" style="text-align:center;">
    <div class="mock-label" style="justify-content:center;">Sesuai Usia</div>
    <h2 class="display2">Pilihan Kelompok Umur</h2>
  </div>
  <div class="umur-grid">
    @php
      $kelompokUmur = [
    ['nama' => 'Maochong', 'usia' => '3–6 Tahun', 'foto' => 'maochong.jpeg'],
    ['nama' => 'Jianer', 'usia' => '7–9 Tahun', 'foto' => 'jianer.jpeg'],
    ['nama' => 'Hudie', 'usia' => '10–15 Tahun', 'foto' => 'hudie.jpeg'],
    ['nama' => 'Feixiang', 'usia' => '15++ Tahun', 'foto' => 'feixiang.jpeg'],
  ];
    @endphp
    @foreach ($kelompokUmur as $kelompok)
      <div class="umur-card">
        <div class="umur-photo">
        <img src="{{ asset('assets/img/' . $kelompok['foto']) }}"
          alt="{{ $kelompok['nama'] }}"
          style="width:100%; height:100%; object-fit:cover; border-radius:20px; position:relative; z-index:1;">
          </div> 
        <div class="umur-name">{{ $kelompok['nama'] }}</div>
        <div class="umur-age">({{ $kelompok['usia'] }})</div>
      </div>
    @endforeach
  </div>
</section>

{{-- ===================== OUR SERVICE + REWARD SYSTEM ===================== --}}

<section id="ourservice" style="padding:90px 0; background-color: #fff;">
 
  <div class="mock-wrap">
    <!-- Our Service -->
    <div class="mock-section-head" style="text-align:center;">
      <div class="mock-label" style="justify-content:center;">Layanan Profesional</div>
      
      <h2 class="display2">Our Service</h2>
      <p style="margin-left:auto;margin-right:auto;">Haoyou juga menyediakan layanan profesional untuk kebutuhan personal maupun korporasi:</p>
    </div>
       <div class="service-list" style="margin-bottom:70px;">
      <div class="service-item">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><path d="M3 6l9-3 9 3v11l-9 3-9-3V6z"/><path d="M12 3v17"/></svg></span>
        Mandarin Travel Assistance
      </div>
      <div class="service-item">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 21c0-4 3-6 7-6s7 2 7 6"/></svg></span>
        Professional Tour Guide (Mandarin – Indonesia – English)
      </div>
      <div class="service-item">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h5"/></svg></span>
        Certified Translator (Dokumen Resmi &amp; Bisnis)
      </div>
      <div class="service-item">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><path d="M4 5h16v10H7l-3 3V5z"/></svg></span>
        Professional Interpreter (Meeting, Event, Negotiation)
      </div>
      <div class="service-item">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><path d="M4 4h11l5 5v11H4V4z"/><path d="M15 4v5h5"/></svg></span>
        Mandarin Book Typing &amp; Academic Formatting
      </div>
    </div>

    <div class="mock-section-head" style="text-align:center;">
      <div class="mock-label" style="justify-content:center;">Motivasi Belajar</div>
      <h2 class="display2">Reward System</h2>
      <p style="margin-left:auto;margin-right:auto;">Semakin aktif belajar &amp; berbicara Mandarin, semakin banyak reward yang bisa ditukar.</p>
    </div>
    <div class="reward-flow">
      <div class="reward-step">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><path d="M4 5h8v15H4z"/><path d="M12 5h8v15h-8z"/></svg></span>
        <div class="step-label" style="color:#000000;">Belajar</div>
      </div>
      <div class="reward-arrow">→</div>
      <div class="reward-step">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.9L21 9.6l-4.6 4 1.3 6.4L12 16.9 6.3 20l1.3-6.4L3 9.6l6.4-.7L12 3z"/></svg></span>
        <div class="step-label" style="color:#000000;">Poin</div>
      </div>
      <div class="reward-arrow">→</div>
      <div class="reward-step">
        <span class="icon-badge"><svg viewBox="0 0 24 24"><rect x="4" y="9" width="16" height="11" rx="1"/><path d="M4 9h16M12 9v11M8 9c-2-1-2-4 0-5 2-1 4 2 4 5 0-3 2-6 4-5 2 1 2 4 0 5"/></svg></span>
        <div class="step-label" style="color:#000000;">Hadiah</div>
      </div>
    </div>
    <div class="hadiah-grid">
    
  @php
    $katalogHadiah = [
        [
            'nama' => 'Samsung Smart TV 32 Inch',
            'gambar' => 'samsung.jpg'
        ],
        [
            'nama' => 'Nintendo Switch OLED v2',
            'gambar' => 'nintendo.jpg'
        ],
        [
            'nama' => 'PlayStation 5 Original',
            'gambar' => 'ps5.jpg'
        ],
        [
            'nama' => 'Sepeda Listrik',
            'gambar' => 'sepedah.jpg'
        ],
        [
            'nama' => 'Mobil Listrik Anak',
            'gambar' => 'mobil.jpg'
        ],
    ];
@endphp

<div class="hadiah-grid">
@foreach ($katalogHadiah as $hadiah)
    <div class="hadiah-card">
        <div class="hadiah-photo">
            <img src="{{ asset('assets/img/' . $hadiah['gambar']) }}"
                 alt="{{ $hadiah['nama'] }}"
                 class="w-full h-full object-cover">
        </div>

        <div class="hadiah-name">
            {{ $hadiah['nama'] }}
        </div>
    </div>
@endforeach
</div>
</section>



{{-- ===================== EVENT ===================== --}}
<section id="event" class="mock-wrap" style="padding:90px 0;">
  <div class="mock-section-head" style="text-align:center;">
    <div class="mock-label" style="justify-content:center;">Belajar Lewat Pengalaman</div>
    <h2 class="display2 event-title">Event</h2>
  </div>
  <div class="event-grid2">
    <div>
      <p style="color:var(--mock-ink);">Di Haoyou, kami percaya bahwa bahasa Mandarin tidak hanya dipelajari dari buku, tetapi juga melalui pengalaman nyata. Karena itu, kami secara rutin mengadakan berbagai event edukasi interaktif yang membantu siswa belajar Mandarin melalui aktivitas kehidupan sehari-hari.</p>
      <p class="highlight" style="color:var(--mock-olive);">
        <span class="icon-badge" style="width:28px;height:28px;"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span>
        Event edukasi interaktif
      </p>
      <p style="color:var(--mock-ink);">Dalam event ini, siswa akan menggunakan bahasa Mandarin secara langsung sambil melakukan kegiatan seru, seperti membuat parfum, membuat pizza, membuat steak sandwich, dll. Melalui aktivitas ini, siswa belajar kosakata baru, memahami instruksi dalam bahasa Mandarin, serta berlatih berbicara secara natural dalam situasi nyata.</p>
      <p style="color:var(--mock-ink);">Melalui program ini, Haoyou berkomitmen menghadirkan pengalaman belajar bahasa Mandarin yang tidak hanya efektif secara akademis, tetapi juga relevan dengan kehidupan sehari-hari.</p>
      <p style="color:var(--mock-ink);">Dengan metode ini, proses belajar menjadi lebih menyenangkan, lebih mudah dipahami, dan lebih membekas, karena siswa tidak hanya menghafal, tetapi juga mengalami langsung penggunaan bahasa Mandarin dalam kehidupan sehari-hari.</p>
    </div>
    <div class="event-photo" style="border-color:var(--mock-line); background:#faf9f5; color:var(--mock-muted);">
      <img src="{{ asset('assets/img/event.jpeg') }}" alt="Event" class="event-img"> 
  </div>
</section>

{{-- ===================== TESTIMONI ===================== --}}
<section id="testimoni" class="mock-wrap" style="padding:90px 0;">
    <div class="mock-section-head" style="text-align:center;">
        <div class="mock-label" style="justify-content:center;color:#000000;">
            Bukti Nyata
        </div>

        <h2 class="display2" style="color:#000;">
            Kata Mereka Setelah Belajar Disini
        </h2>

        <div class="stars2">★★★★★</div>

        <p style="max-width:650px;margin:18px auto 50px;color:#555;">
            Kepuasan siswa dan orang tua menjadi motivasi kami untuk terus memberikan
            pengalaman belajar Mandarin yang menyenangkan dan berkualitas.
        </p>
    </div>

    <div class="testimoni-photo-grid">

        <div class="testimoni-photo">
            <img src="{{ asset('assets/img/testimoni1.jpg') }}" alt="Testimoni 1">
        </div>

        <div class="testimoni-photo">
            <img src="{{ asset('assets/img/testimoni2.jfif') }}" alt="Testimoni 2">
        </div>

        <div class="testimoni-photo">
            <img src="{{ asset('assets/img/testimoni3.jfif') }}" alt="Testimoni 3">
        </div>

        <div class="testimoni-photo">
            <img src="{{ asset('assets/img/testimoni4.jfif') }}" alt="Testimoni 4">
        </div>

    </div>
</section>


{{-- ===================== TENTANG KAMI + HARGA (sebelum footer) ===================== --}}
<section id="harga" class="dark-section harga-section">
  {{-- <div class="blob blob-gold" style="width:440px;height:440px;left:50%;top:-180px;transform:translateX(-50%);"></div> --}}
  <div class="mock-wrap">
    <div class="mock-label">Tentang Kami</div>
    <h2 class="display2">Haoyou Educator</h2>
    <p class="desc">Mandarin Learning Center di Malang, mengajak murid belajar Mandarin lewat Game Based Learning, event nyata, dan sistem reward yang memotivasi.</p>
    <div class="price-card">
      <div class="price-label">Harga Mulai Dari</div>
      <div class="price display2">Rp550rb</div>
      <div class="price-sub">/ bulan</div>
      <a href="{{ route('pendaftaran.create') }}" class="mock-btn mock-btn-gold">Daftar Sekarang</a>
    </div>
  </div>
</section>

{{-- ===================== FOOTER ===================== --}}
<footer id="tentang" class="bg-[#111111] text-white py-12">
  <div class="mock-wrap max-w-6xl mx-auto px-6">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
      
      <!-- HUBUNGI KAMI -->
      <div class="w-full">
        <h3 class="text-white uppercase tracking-[2px] text-sm font-semibold mb-6 text-center">
          HUBUNGI KAMI
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-6 text-sm">
          
          <!-- WhatsApp -->
          <a href="https://wa.me/62895352684913" target="_blank" class="flex items-center gap-3 hover:text-yellow-400 transition group">
            <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
            <div class="w-10 h-10 rounded-full justify-center">
              <iconify-icon
              icon="logos:whatsapp-icon"
              width="34">
            </iconify-icon>
            </div>
            <div>
              <div class="text-[10px] text-gray-500 tracking-widest">WHATSAPP</div>
              <div class="font-medium">(+62) 895-3526-84913</div>
            </div>
          </a>

          <!-- Instagram -->
          <a href="https://instagram.com/haoyou.educator" target="_blank" class="flex items-center gap-3 hover:text-yellow-400 transition group">
            <div class="w-10 h-10 flex items-center justify-center">
              <iconify-icon
              icon="skill-icons:instagram"
              width="34">
            </iconify-icon>
            </div>
            <div>
              <div class="text-[10px] text-gray-500 tracking-widest">INSTAGRAM</div>
              <div class="font-medium">@haoyou.educator</div>
            </div>
          </a>

          <!-- TikTok -->
          <a href="https://tiktok.com/@haoyoueducator" target="_blank" class="flex items-center gap-3 hover:text-yellow-400 transition group">
            <div class="w-10 h-10 rounded-full justify-center">
              <iconify-icon
              icon="logos:tiktok-icon"
              width="34">
            </iconify-icon>
            </div>

            <div>
              <div class="text-[10px] text-gray-500 tracking-widest">TIKTOK</div>
              <div class="font-medium">@haoyoueducator</div>
            </div>
          </a>

          <!-- Alamat -->
          <div class="flex items-center gap-3">
            <div class="flex items-center justify-center text-3xl">
              📍
            </div>
            <div>
              <div class="text-[10px] text-gray-500 tracking-widest">ALAMAT</div>
              <div class="font-medium leading-tight">
                Jl. Terusan Dieng No. 9E,<br>
                Malang 65115
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
    
  <!-- Copyright -->
  <div class="border-t border-gray-00 mt-16 pt-6 text-center text-xs text-gray-500">
    © 2026 Haoyou Educator. Semua hak dilindungi.
  </div>
</footer>

@endsection

