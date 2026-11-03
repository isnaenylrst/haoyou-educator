
{{-- resources/views/partials/hy-bg.blade.php --}}
{{-- Pemakaian: @include('partials.hy-bg') --}}
{{-- Halaman panjang: @include('partials.hy-bg', ['tall' => true]) --}}

<style>
    /* =========================================
       HAoyou EARTH TONE COLOR PALETTE
    ========================================= */

    :root {
        --hy-green: #2F4B3B;
        --hy-sage: #B5C2A0;
        --hy-white: #FFFFFF;
        --hy-yellow: #FBF0BE;
        --hy-yellow-light: #FFF7DB;
        --hy-yellow-accent: #F3DE91;
        --hy-salmon: #EE9F8F;
        --hy-mint: #D5DFCC;
        --hy-sky: #DCE8E8;
        --hy-tan: #C6A982;
        --hy-light-sage: #E8EDE2;
        --hy-kuning: #ffe135;
    }

    /* =========================================
       BACKGROUND
    ========================================= */

    .hy-page {
        position: relative;
        overflow: hidden;
        background: #FFFFFF;
    }

    .hy-bg {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
        z-index: 0;
        background: #FFFFFF;
    }

    .hy-bg svg {
        position: absolute;
        overflow: visible;
    }

    .hy-side {
        display: none;
    }

    @media (min-width: 1024px) {
        .hy-side {
            display: block;
        }
    }

    .hy-hover {
        pointer-events: auto;
    }

    /* =========================================
       FLOATING CHIP
    ========================================= */

    .hy-chip {
        position: absolute;
        background: #FFFFFF;
        border: 1.5px solid var(--hy-green);
        border-radius: 999px;
        padding: 6px 13px;
        font-size: 12px;
        font-weight: 500;
        color: var(--hy-green);
        box-shadow: 0 3px 0 var(--hy-yellow);
        animation: hyBob 4s ease-in-out infinite;
        transition: transform .2s;
        cursor: default;
        white-space: nowrap;
    }

    .hy-chip:hover {
        transform: scale(1.08) rotate(-3deg);
    }

    @keyframes hyBob {
        0%, 100% {
            margin-top: 0;
        }

        50% {
            margin-top: -7px;
        }
    }

    /* =========================================
       ANIMATION
    ========================================= */

    .hy-spin {
        transform-box: fill-box;
        transform-origin: center;
        animation: hySpin 50s linear infinite;
    }

    @keyframes hySpin {
        to {
            transform: rotate(360deg);
        }
    }

    .hy-arch {
        transition: transform .3s;
    }

    .hy-arch:hover {
        transform: translateY(-8px);
    }

    @media (prefers-reduced-motion: reduce) {
        .hy-chip,
        .hy-spin {
            animation: none;
        }
    }

    /* =========================================
       LOGIN CARD
    ========================================= */

    .hy-card {
        position: relative;
        z-index: 10;
        background: #FFFFFF;
        border: 1.5px solid var(--hy-green);
        border-radius: 24px;
        padding: 36px;
        box-shadow: 0 6px 0 var(--hy-yellow);
    }

    /* =========================================
       INPUT
    ========================================= */

    .hy-in {
        width: 100%;
        padding: 12px 14px;
        border: 1.5px solid #DED9C9;
        border-radius: 12px;
        background: #F4F6F1;
        color: var(--hy-green);
        font-size: 13.5px;
        transition: .2s ease;
    }

    .hy-in:focus {
        outline: none;
        background: #FFFFFF;
        border-color: var(--hy-sage);
        box-shadow: 0 0 0 3px rgba(181, 194, 160, .18);
    }

    .hy-in::placeholder {
        color: #9A9C8D;
    }

    /* =========================================
       LABEL
    ========================================= */

    .hy-lbl {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--hy-green);
        margin-bottom: 6px;
    }

    /* =========================================
       SECTION
    ========================================= */

    .hy-sec {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        color: var(--hy-green);
        margin: 24px 0 12px;
    }

    .hy-sec:before {
        content: "";
        width: 16px;
        height: 4px;
        border-radius: 2px;
        background: var(--hy-salmon);
    }

    /* =========================================
       BUTTON LOGIN - SAGE GREEN
    ========================================= */

    .hy-btn {
        display: block;
        width: 100%;
        padding: 14px 0;
        border-radius: 999px;
        background: var(--hy-sage);
        color: var(--hy-green);
        border-bottom: 3px solid #8FA17B;
        font-size: 13.5px;
        font-weight: 600;
        text-align: center;
        transition: filter .15s, transform .15s;
    }

    .hy-btn:hover {
        background: #C3CFB0;
        filter: brightness(1.02);
    }

    .hy-btn:active {
        transform: translateY(1px);
    }

    /* =========================================
       LINK
    ========================================= */

    .hy-link {
        font-weight: 600;
        color: var(--hy-green);
        text-decoration: underline;
        text-decoration-color: var(--kuning, #ffe135);
        text-decoration-thickness: 2px;
        text-underline-offset: 3px;
    }

    .hy-link:hover {
        color: #58745D;
    }

</style>


<div class="hy-bg" aria-hidden="true">

    {{-- =====================================
         KARAKTER MANDARIN SAMAR
    ====================================== --}}

    <div class="hy-side"
         style="position:absolute;left:12%;top:42%;
                font-size:150px;line-height:1;
                color:#F0F2EB;">
        好
    </div>

    <div class="hy-side"
         style="position:absolute;right:22%;top:14%;
                font-size:110px;line-height:1;
                color:#F0F2EB;">
        友
    </div>


    {{-- =====================================
         ARCH KIRI BAWAH
    ====================================== --}}

    <svg class="hy-side hy-hover hy-arch"
         viewBox="0 0 180 300"
         width="180"
         height="300"
         style="left:24px;bottom:60px">

        {{-- Outer arch: kuning --}}
        <path d="M10 300V110a80 80 0 0 1 160 0V300Z"
              fill="#ffe135"/>

        {{-- Middle arch: dark green --}}
        <path d="M45 300V125a45 45 0 0 1 90 0V300Z"
              fill="#2F4B3B"/>

        {{-- Inner arch: mint --}}
        <path d="M77 300V140a13 13 0 0 1 26 0V300Z"
              fill="#D5DFCC"/>

    </svg>


    {{-- =====================================
         LINGKARAN KANAN ATAS
    ====================================== --}}

    <svg class="hy-side"
         viewBox="0 0 300 300"
         width="300"
         height="300"
         style="right:0;top:0">

        {{-- Outer rotating ring: soft yellow --}}
        <g class="hy-spin">
            <circle cx="310" cy="110" r="140"
                    fill="none"
                    stroke="#ffe135"
                    stroke-width="14"
                    stroke-dasharray="520 360"/>
        </g>

        {{-- Middle ring: biru --}}
        <circle cx="300" cy="110" r="100"
                fill="none"
                stroke="#D5E3E8"
                stroke-width="4"/>

        {{-- Inner ring: salmon --}}
        <circle cx="300" cy="110" r="72"
                fill="none"
                stroke="#EE9F8F"
                stroke-width="2"/>

    </svg>


    {{-- =====================================
         ZIGZAG KANAN
    ====================================== --}}

    <svg class="hy-side"
         viewBox="0 0 90 20"
         width="90"
         height="20"
         style="right:60px;top:58%">

        <path d="M3 14q14-18 28 0t28 0t28 0"
              stroke="#2F4B3B"
              stroke-width="4"
              fill="none"
              stroke-linecap="round"/>

    </svg>


    {{-- =====================================
         ZIGZAG KIRI
    ====================================== --}}

    <svg class="hy-side"
         viewBox="0 0 90 20"
         width="90"
         height="20"
         style="left:24%;top:70px">

        <path d="M3 14q14-18 28 0t28 0"
              stroke="#EE9F8F"
              stroke-width="3.5"
              fill="none"
              stroke-linecap="round"/>

    </svg>


    {{-- =====================================
         CINCIN MINT / SAGE
    ====================================== --}}

    <svg class="hy-side"
         viewBox="0 0 30 30"
         width="30"
         height="30"
         style="left:130px;top:140px">

        <circle cx="15" cy="15" r="12"
                fill="none"
                stroke="#B5C2A0"
                stroke-width="4"/>

    </svg>


    {{-- =====================================
         BINTANG KIRI
    ====================================== --}}

    <svg class="hy-side"
         viewBox="-12 -12 24 24"
         width="24"
         height="24"
         style="left:24%;top:150px">

        <path d="M0-12L3.5-3.5L12 0L3.5 3.5L0 12L-3.5 3.5L-12 0L-3.5-3.5Z"
              fill="#ffe135"/>

    </svg>


    {{-- =====================================
         BINTANG KANAN
    ====================================== --}}

    <svg class="hy-side"
         viewBox="-12 -12 24 24"
         width="18"
         height="18"
         style="right:24%;top:48px">

        <path d="M0-12L3.5-3.5L12 0L3.5 3.5L0 12L-3.5 3.5L-12 0L-3.5-3.5Z"
              fill="#ffe135"/>

    </svg>


    {{-- =====================================
         DEKORASI HALAMAN PANJANG
    ====================================== --}}

    @if (!empty($tall))

        <svg class="hy-side"
             viewBox="0 0 140 540"
             width="140"
             height="540"
             style="right:0;bottom:60px">

            <path d="M5 0C60 30 50 170 85 310C100 370 110 450 140 540H20C20 430-20 230 5 0Z"
                  fill="#FBF0BE"/>

            <path d="M70 70C110 110 100 230 122 330C132 380 136 450 140 490V70Z"
                  fill="#B5C2A0"/>

        </svg>

    @endif


    {{-- =====================================
         CHIP KIRI: HALO
    ====================================== --}}

    <div class="hy-side hy-hover hy-chip"
         style="left:20px;top:52px">

        <span style="color:#EE9F8F;font-size:14px">
            你好!
        </span>
        Halo

    </div>


    {{-- =====================================
         CHIP KANAN: SEMANGAT
    ====================================== --}}

    <div class="hy-side hy-hover hy-chip"
         style="right:24px;top:52%;animation-delay:1.2s">

        <span style="color:#93d9e5;font-size:14px">
            加油!
        </span>
        Semangat

    </div>


    {{-- =====================================
         GELOMBANG BAWAH
    ====================================== --}}

    <svg viewBox="0 0 680 140"
         preserveAspectRatio="none"
         style="left:0;bottom:0;width:100%;height:140px">

        {{-- Layer 1: kuning --}}
        <path d="M0 60C130 10 260 90 420 45C540 10 610 20 680 0V140H0Z"
              fill="#ffe135"/>

        {{-- Layer 2: soft green --}}
        <path d="M0 72C130 22 260 102 420 59C540 24 610 34 680 10V140H0Z"
              fill="#EE9F8F"/>

        {{-- Layer 3: pink --}}
        <path d="M0 82C130 32 260 112 420 69C540 34 610 44 680 20V140H0Z"
              fill="#D5E3E8"/>

        {{-- Layer 4: white --}}
        <path d="M0 108C130 60 260 135 420 97C540 63 610 72 680 50V140H0Z"
              fill="#FFFFFF"/>

    </svg>

</div>
