@extends('layouts.dashboard')

@push('styles')
<style>
  .bm-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px}
  .bm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px}
  @media(max-width:900px){.bm-grid,.bm-sum{grid-template-columns:1fr}}

  .bm-stat{background:#fff;border:1px solid #E6E4DC;border-radius:18px;padding:16px 20px}
  .bm-stat--total{background:#FFF8DC;border-color:#F3E2A0}
  .bm-stat-label{font-size:13px;color:#6b6652;margin-bottom:6px}
  .bm-stat--total .bm-stat-label,.bm-stat--total .bm-stat-num,.bm-stat--total .bm-pct{color:#7a5a00}
  .bm-stat-num{font-size:26px;font-weight:700;color:#1d1a0f;line-height:1.2}
  .bm-stat-num small{font-size:13px;font-weight:500;color:#6b6652}

  .bm-card{background:#fff;border:1px solid #E6E4DC;border-radius:18px;padding:20px;display:flex;flex-direction:column}
  .bm-ico{width:40px;height:40px;border-radius:12px;background:#FFE135;display:flex;align-items:center;justify-content:center;color:#1d1a0f;margin-bottom:14px}
  .bm-title{font-weight:700;font-size:16px;color:#1d1a0f}
  .bm-desc{font-size:13px;color:#6b6652;margin-top:4px;line-height:1.5;min-height:40px}
  .bm-bar{height:6px;border-radius:99px;background:#F1EBDA;margin:12px 0 6px;overflow:hidden}
  .bm-bar>div{height:100%;background:#FFE135;border-radius:99px}
  .bm-stat .bm-bar>div{background:#EF9F27}
  .bm-stat--total .bm-bar{background:#fff}
  .bm-stat--total .bm-bar>div{background:#BA7517}
  .bm-pct{font-size:12.5px;color:#6b6652;margin-bottom:14px}
  .bm-stat .bm-pct{margin-bottom:0}
  .bm-btn{margin-top:auto;align-self:flex-start;background:#FFE135;color:#1d1a0f;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;transition:.15s}
  .bm-btn:hover{background:#FFD60A}
  .bm-btn.is-off{background:#EFEDE3;color:#9a967f;cursor:not-allowed}
  .bm-btn.is-off:hover{background:#EFEDE3}
  .bm-note{background:#F4F4EE;border:1px solid #ECEADF;border-radius:14px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:13.5px;color:#4a4636}
</style>
@endpush

@section('dashboard-content')
@php
  $vStars = $vocabStars ?? 0;
  $vMax   = $vocabMax ?? 0;
  $gStars = $grammarStars ?? 0;
  $gMax   = $grammarMax ?? 0;
  $sc     = $score ?? [
      'vocab_pct' => 0, 'grammar_pct' => 0, 'total' => 0,
      'predikat' => 'Belum lulus', 'lulus' => false, 'passing' => 60,
      'weight_vocab' => 70, 'weight_grammar' => 30,
  ];
  $bisaLihatSertifikat = $sc['lulus'] || $sertifikatMandiri > 0;
@endphp

{{-- RINGKASAN BINTANG --}}
<div class="bm-sum">
  <div class="bm-stat">
    <div class="bm-stat-label">⭐ Bintang vocab</div>
    <div class="bm-stat-num">{{ number_format($vStars, 0, ',', '.') }} <small>/ {{ number_format($vMax, 0, ',', '.') }}</small></div>
    <div class="bm-bar"><div style="width:{{ $sc['vocab_pct'] }}%"></div></div>
    <div class="bm-pct">{{ $sc['vocab_pct'] }}% · bobot {{ $sc['weight_vocab'] }}%</div>
  </div>

  <div class="bm-stat">
    <div class="bm-stat-label">⭐ Bintang grammar</div>
    <div class="bm-stat-num">{{ number_format($gStars, 0, ',', '.') }} <small>/ {{ number_format($gMax, 0, ',', '.') }}</small></div>
    <div class="bm-bar"><div style="width:{{ $sc['grammar_pct'] }}%"></div></div>
    <div class="bm-pct">{{ $sc['grammar_pct'] }}% · bobot {{ $sc['weight_grammar'] }}%</div>
  </div>

  <div class="bm-stat bm-stat--total">
    <div class="bm-stat-label">🏆 Total capaian</div>
    <div class="bm-stat-num">{{ $sc['total'] }}% <small style="color:#7a5a00">/ 100</small></div>
    <div class="bm-bar"><div style="width:{{ $sc['total'] }}%"></div></div>
    <div class="bm-pct">
      {{ $sc['lulus'] ? 'Predikat: '.$sc['predikat'] : 'Belum lulus · minimal '.$sc['passing'] }}
    </div>
  </div>
</div>

<div class="bm-grid">

  {{-- BELAJAR VOCAB --}}
  <div class="bm-card">
    <div class="bm-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </div>
    <div class="bm-title">Belajar vocab</div>
    <div class="bm-desc">Daftar kosakata, study plan, dan tes.</div>
    <div class="bm-bar"><div style="width:{{ $vocabPct }}%"></div></div>
    <div class="bm-pct">{{ $vocabPct }}% selesai</div>
    <a href="{{ route('student.vocab.list') }}" class="bm-btn">Lanjut belajar</a>
  </div>

  {{-- BELAJAR GRAMMAR --}}
  <div class="bm-card">
    <div class="bm-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
    </div>
    <div class="bm-title">Belajar grammar</div>
    <div class="bm-desc">Pola kalimat, jebakan umum, dan tes.</div>
    <div class="bm-bar"><div style="width:{{ $grammarPct }}%"></div></div>
    <div class="bm-pct">{{ $grammarPct }}% selesai</div>
    <a href="{{ route('student.grammar.list') }}" class="bm-btn">Lanjut belajar</a>
  </div>

  {{-- SERTIFIKAT MANDIRI --}}
  <div class="bm-card">
    <div class="bm-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.5 13.5 17 22l-5-3-5 3 1.5-8.5"/></svg>
    </div>
    <div class="bm-title">Sertifikat mandiri</div>
    <div class="bm-desc">Lihat dan unduh sertifikat hasil belajarmu.</div>
    <div class="bm-bar"><div style="width:{{ $sertifikatMandiri > 0 ? 100 : 0 }}%"></div></div>
    <div class="bm-pct">
      @if($sertifikatMandiri > 0)
        {{ $sertifikatMandiri }} sertifikat
      @elseif($sc['lulus'])
        Siap dibuat · nilai {{ $sc['total'] }}
      @else
        Aktif mulai nilai {{ $sc['passing'] }}
      @endif
    </div>
    @if($bisaLihatSertifikat)
      
       <a href="{{ route('student.certificates.index', ['tab' => 'mandiri']) }}" class="bm-btn">Lihat sertifikat</a>
    @else
      <span class="bm-btn is-off" title="Capai nilai {{ $sc['passing'] }} untuk membuka sertifikat">Lihat sertifikat</span>
    @endif
  </div>

</div>

{{-- <div class="bm-note">
  <span>Fitur ini masih uji coba. Ada masukan?</span>
  <a href="{{ config('app.feedback_url', '#') }}" class="bm-btn" style="margin-top:0">Beri masukan</a>
</div> --}}
@endsection