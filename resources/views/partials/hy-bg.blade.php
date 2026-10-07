{{-- resources/views/partials/hy-bg.blade.php --}}
{{-- Pemakaian: @include('partials.hy-bg')  atau  @include('partials.hy-bg', ['tall' => true]) untuk halaman panjang --}}
<style>
  .hy-page{position:relative;overflow:hidden;background:#fff}
  .hy-bg{position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:0}
  .hy-bg svg{position:absolute;overflow:visible}
  .hy-side{display:none}
  @media (min-width:1024px){.hy-side{display:block}}
  .hy-hover{pointer-events:auto}

  /* animasi */
  .hy-chip{position:absolute;background:#fff;border:1.5px solid #1F1B4D;border-radius:999px;padding:6px 13px;font-size:12px;font-weight:500;color:#1F1B4D;box-shadow:0 3px 0 #1F1B4D;animation:hyBob 4s ease-in-out infinite;transition:transform .2s;cursor:default;white-space:nowrap}
  .hy-chip:hover{transform:scale(1.08) rotate(-3deg)}
  @keyframes hyBob{0%,100%{margin-top:0}50%{margin-top:-7px}}
  .hy-spin{transform-box:fill-box;transform-origin:center;animation:hySpin 50s linear infinite}
  @keyframes hySpin{to{transform:rotate(360deg)}}
  .hy-arch{transition:transform .3s}
  .hy-arch:hover{transform:translateY(-8px)}
  @media (prefers-reduced-motion:reduce){.hy-chip,.hy-spin{animation:none}}

  /* komponen bersama */
  .hy-card{position:relative;z-index:10;background:#fff;border:1.5px solid #1F1B4D;border-radius:24px;padding:36px;box-shadow:0 6px 0 #FFD93B}
  .hy-in{width:100%;padding:12px 14px;border:1.5px solid #E2DCC8;border-radius:12px;background:#F3F0E6;font-size:13.5px}
  .hy-in:focus{outline:none;background:#fff;border-color:#FFD93B}
  .hy-lbl{display:block;font-size:12px;font-weight:600;color:#1F1B4D;margin-bottom:6px}
  .hy-sec{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:.08em;color:#1F1B4D;margin:24px 0 12px}
  .hy-sec:before{content:"";width:16px;height:4px;border-radius:2px;background:#FF5C8A}
  .hy-btn{display:block;width:100%;padding:14px 0;border-radius:999px;background:#FFD93B;color:#1A1A1A;border-bottom:3px solid #E0B800;font-size:13.5px;font-weight:600;text-align:center;transition:filter .15s}
  .hy-btn:hover{filter:brightness(1.05)}
  .hy-btn:active{transform:translateY(1px)}
  .hy-link{font-weight:600;color:#1F1B4D;text-decoration:underline;text-decoration-color:#FFD93B;text-decoration-thickness:2px;text-underline-offset:3px}
</style>

<div class="hy-bg" aria-hidden="true">

  {{-- karakter samar --}}
  <div class="hy-side" style="position:absolute;left:12%;top:42%;font-size:150px;line-height:1;color:#F1EFF9;">好</div>
  <div class="hy-side" style="position:absolute;right:22%;top:14%;font-size:110px;line-height:1;color:#F1EFF9;">友</div>

  {{-- lengkung arch kiri bawah --}}
  <svg class="hy-side hy-hover hy-arch" viewBox="0 0 180 300" width="180" height="300" style="left:24px;bottom:60px">
    <path d="M10 300V110a80 80 0 0 1 160 0V300Z" fill="#FFD93B"/>
    <path d="M45 300V125a45 45 0 0 1 90 0V300Z" fill="#1F1B4D"/>
    <path d="M77 300V140a13 13 0 0 1 26 0V300Z" fill="#FF5C8A"/>
  </svg>

  {{-- busur kanan atas --}}
  <svg class="hy-side" viewBox="0 0 300 300" width="300" height="300" style="right:0;top:0">
    <g class="hy-spin"><circle cx="310" cy="110" r="140" fill="none" stroke="#FFD93B" stroke-width="14" stroke-dasharray="520 360"/></g>
    <circle cx="300" cy="110" r="100" fill="none" stroke="#1F1B4D" stroke-width="4"/>
    <circle cx="300" cy="110" r="72" fill="none" stroke="#FF5C8A" stroke-width="2"/>
  </svg>

  {{-- zigzag, cincin mint, bintang --}}
  <svg class="hy-side" viewBox="0 0 90 20" width="90" height="20" style="right:60px;top:58%">
    <path d="M3 14q14-18 28 0t28 0t28 0" stroke="#1F1B4D" stroke-width="4" fill="none" stroke-linecap="round"/>
  </svg>
  <svg class="hy-side" viewBox="0 0 90 20" width="90" height="20" style="left:24%;top:70px">
    <path d="M3 14q14-18 28 0t28 0" stroke="#FF5C8A" stroke-width="3.5" fill="none" stroke-linecap="round"/>
  </svg>
  <svg class="hy-side" viewBox="0 0 30 30" width="30" height="30" style="left:130px;top:140px">
    <circle cx="15" cy="15" r="12" fill="none" stroke="#6EE7B7" stroke-width="4"/>
  </svg>
  <svg class="hy-side" viewBox="-12 -12 24 24" width="24" height="24" style="left:24%;top:150px">
    <path d="M0-12L3.5-3.5L12 0L3.5 3.5L0 12L-3.5 3.5L-12 0L-3.5-3.5Z" fill="#FFD93B"/>
  </svg>
  <svg class="hy-side" viewBox="-12 -12 24 24" width="18" height="18" style="right:24%;top:48px">
    <path d="M0-12L3.5-3.5L12 0L3.5 3.5L0 12L-3.5 3.5L-12 0L-3.5-3.5Z" fill="#FFD93B"/>
  </svg>

  {{-- khusus halaman panjang (formulir pendaftaran) --}}
  @if (!empty($tall))
    <svg class="hy-side" viewBox="0 0 140 540" width="140" height="540" style="right:0;bottom:60px">
      <path d="M5 0C60 30 50 170 85 310C100 370 110 450 140 540H20C20 430-20 230 5 0Z" fill="#FFD93B"/>
      <path d="M70 70C110 110 100 230 122 330C132 380 136 450 140 490V70Z" fill="#1F1B4D"/>
    </svg>
  @endif

  {{-- chip melayang --}}
  <div class="hy-side hy-hover hy-chip" style="left:20px;top:52px"><span style="color:#FF5C8A;font-size:14px">你好!</span> Halo</div>
  <div class="hy-side hy-hover hy-chip" style="right:24px;top:52%;animation-delay:1.2s"><span style="color:#E5A800;font-size:14px">加油!</span> Semangat</div>

  {{-- gelombang bawah: navy, kuning, krem --}}
  <svg viewBox="0 0 680 140" preserveAspectRatio="none" style="left:0;bottom:0;width:100%;height:140px">
    <path d="M0 60C130 10 260 90 420 45C540 10 610 20 680 0V140H0Z" fill="#1F1B4D"/>
    <path d="M0 82C130 32 260 112 420 69C540 34 610 44 680 20V140H0Z" fill="#FFD93B"/>
    <path d="M0 108C130 60 260 135 420 97C540 63 610 72 680 50V140H0Z" fill="#FAF7EE"/>
  </svg>
</div>