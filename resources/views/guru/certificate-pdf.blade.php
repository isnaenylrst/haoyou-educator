@php
    $passed = $cert['score'] !== null && (float) $cert['score'] >= $cert['passing'];
    $fmt = fn ($v) => is_null($v) ? '-' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    // Logo: taruh di public/images/haoyou-logo.png (atau salah satu lokasi di bawah)
    $logoPath = collect([
            'images', 'img', 'assets/images', 'assets/img', 'assets', 'image', '',
        ])
        ->flatMap(fn ($d) => glob(public_path(($d ? $d . '/' : '') . '*logo*.{png,PNG,jpg,jpeg,JPG,JPEG,gif}'), GLOB_BRACE) ?: [])
        ->first();

    // Tanda tangan: coba beberapa lokasi penyimpanan
    $sig = function ($path) {
        if (empty($path)) return null;
        $path = ltrim(preg_replace('#^(public/|storage/)#', '', $path), '/');
        foreach ([
            storage_path('app/public/' . $path),
            storage_path('app/' . $path),
            public_path('storage/' . $path),
            public_path($path),
        ] as $full) {
            if (file_exists($full)) return $full;
        }
        return null;
    };
    $kurSig = $sig($kurikulum['signature'] ?? null);
    $ownSig = $sig($owner['signature'] ?? null);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #222; }

    .outer  { position: absolute; top: 14px; left: 14px; width: 1093px; height: 764px; border: 1px solid #333; }
    .inner  { position: absolute; top: 28px; left: 28px; width: 1061px; height: 732px; border: 3px solid #c8900a; }
    .topbar { position: absolute; top: 28px; left: 28px; width: 1067px; height: 14px; background: #ffd60a; }

    .content { position: absolute; top: 52px; left: 28px; width: 1067px; text-align: center; }
    .serif   { font-family: 'DejaVu Serif', serif; }

    .logo   { height: 105px; }
    .title  { font-size: 42px; margin: 16px 0 4px; letter-spacing: 1px; }
    .sub    { font-size: 11px; letter-spacing: 3px; color: #9a6b00; }
    .intro  { font-size: 13px; color: #555; margin-top: 22px; }
    .name   { font-size: 40px; font-style: italic; margin: 12px 0 0; }
    .line   { width: 360px; border-top: 2px solid #d19a1a; margin: 6px auto 8px; }
    .desc   { font-size: 14px; line-height: 1.6; }
    .period { font-size: 12px; color: #555; margin-top: 2px; }

    table.scores { margin: 16px auto 0; border-collapse: collapse; border: 1px solid #d8d8d8; }
    table.scores td { padding: 8px 26px 8px; text-align: center; border-right: 1px solid #d8d8d8; }
    table.scores td.total { background: #faebd7; }
    .lbl { font-size: 10px; letter-spacing: 2px; color: #555; text-transform: uppercase; }
    .val { font-size: 24px; font-weight: bold; margin-top: 3px; }
    .total .lbl { color: #8a4b00; }
    .total .val { color: #6b3200; }
    .val small { font-size: 12px; font-weight: normal; color: #555; }

    .std  { font-size: 11px; color: #555; margin-top: 8px; }
    .pill { display: inline-block; margin-top: 4px; padding: 6px 24px; font-size: 14px; }
    .pill.ok  { background: #e7f3de; color: #1f4d00; }
    .pill.bad { background: #fde2e2; color: #b12727; }

    .footer { position: absolute; top: 560px; left: 28px; width: 1067px; }
    .issued-box { text-align: center; margin-top: 14px; }
    .issued-lbl { font-size: 12px; color: #555; }
    .issued     { font-size: 14px; }
    table.foot  { margin: 0 auto; border-collapse: collapse; }
    table.foot td { vertical-align: bottom; }
    .sigimg     { height: 58px; }
    .sigline    { border-top: 1px solid #333; margin: 4px 14px 0; padding-top: 4px; font-size: 13px; }
    .sigrole    { font-size: 10px; color: #555; }
    .no         { font-size: 9px; color: #888; margin-top: 4px; }
</style>
</head>
<body>

<div class="outer"></div>
<div class="inner"></div>
<div class="topbar"></div>

<div class="content">
    @if ($logoPath)
        <img class="logo" src="{{ $logoPath }}" alt="HaoYou">
    @else
        <div style="height:105px; line-height:105px; font-size:26px; font-weight:bold;">HaoYou</div>
    @endif

    <div class="title serif">Sertifikat Kelulusan</div>
    <div class="sub">LES MANDARIN &nbsp;·&nbsp; NO. {{ $number }}</div>

    <div class="intro">Dengan bangga diberikan kepada</div>
    <div class="name serif">{{ $e->student_name }}</div>
    <div class="line"></div>

    <div class="desc">
        atas keberhasilan menyelesaikan program les Mandarin
        <strong>{{ $cert['course'] }}</strong>
    </div>
    <div class="period">
        Periode {{ $cert['start']->translatedFormat('d F Y') }} s/d {{ $cert['end']->translatedFormat('d F Y') }}
    </div>

    <table class="scores">
        <tr>
            <td><div class="lbl">Listening</div><div class="val">{{ $fmt($cert['listening']) }}</div></td>
            <td><div class="lbl">Reading</div><div class="val">{{ $fmt($cert['reading']) }}</div></td>
            <td><div class="lbl">Speaking</div><div class="val">{{ $fmt($cert['speaking']) }}</div></td>
            <td><div class="lbl">Writing</div><div class="val">{{ $fmt($cert['writing']) }}</div></td>
            <td class="total" style="border-right:0;">
                <div class="lbl">Total Score</div>
                <div class="val">{{ $fmt($cert['score']) }} <small>/ 100</small></div>
            </td>
        </tr>
    </table>

    <div class="std">Standar kelulusan: minimal {{ $cert['passing'] }} dari 100</div>
    @if ($passed)
        <div class="pill ok">Memenuhi standar kelulusan</div>
    @else
        <div class="pill bad">Belum memenuhi standar kelulusan</div>
    @endif
</div>

<div class="footer">
    {{-- Dua tanda tangan, di tengah --}}
    <table class="foot">
        <tr>
            <td style="width:300px; text-align:center;">
                @if ($kurSig)
                    <img class="sigimg" src="{{ $kurSig }}" alt="TTD Kurikulum"><br>
                @else
                    <div style="height:58px;"></div>
                @endif
                <div class="sigline">{{ $kurikulum['name'] ?: '.....................' }}</div>
                <div class="sigrole">Kepala Kurikulum HaoYou Educator</div>
            </td>
            <td style="width:120px;"></td>
            <td style="width:300px; text-align:center;">
                @if ($ownSig)
                    <img class="sigimg" src="{{ $ownSig }}" alt="TTD Owner"><br>
                @else
                    <div style="height:58px;"></div>
                @endif
                <div class="sigline">{{ $owner['name'] }}</div>
                <div class="sigrole">Owner HaoYou Educator</div>
            </td>
        </tr>
    </table>

    {{-- Tempat & tanggal: di bawah tanda tangan --}}
    <div class="issued-box">
        <span class="issued-lbl">Diterbitkan di Malang,</span>
        <span class="issued">{{ now()->translatedFormat('d F Y') }}</span>
    </div>
</div>

</body>
</html>