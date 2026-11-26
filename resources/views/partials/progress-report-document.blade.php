{{--
    Dokumen Progress Report (format mengikuti contoh PDF Haoyou).
    Dipakai untuk: pratinjau HTML (mode=html) dan PDF dompdf (mode=pdf).
    Variabel: $doc (array dari ProgressReportDocument::data), $mode.
    Seluruh teks memakai Noto Sans SC supaya karakter Mandarin tampil.
--}}
@php
    $isPdf = ($mode ?? 'html') === 'pdf';
    $nl = fn ($t) => nl2br(e((string) $t));                                   // teks polos (assessment)
    $rt = fn ($t) => \App\Support\RichText::render((string) $t, $isPdf ? 0.8 : 1.0); // teks berformat (materi/description)
    $boldFont = is_file(storage_path('fonts/NotoSansSC-Bold.ttf')) ? 'NotoSansSC-Bold.ttf' : 'NotoSansSC-Regular.ttf';
    $logo = public_path('image/Logo-Haoyou.jpeg');
    $logoSrc = $isPdf ? $logo : asset('image/Logo-Haoyou.jpeg');
@endphp
@if($isPdf)
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Progress Report - {{ $doc['student'] }}</title>
@endif
<style>
    @if($isPdf)
    @font-face {
        font-family: 'NotoSansSC';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path('fonts/NotoSansSC-Regular.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'NotoSansSC';
        font-style: normal;
        font-weight: bold;
        src: url('{{ storage_path('fonts/' . $boldFont) }}') format('truetype');
    }
    @page { margin: 118px 52px 48px 52px; }
    body { margin: 0; }
    .hy-logo-fixed { position: fixed; top: -100px; left: 0; }
    @endif
    .hy-doc, .hy-doc * { font-family: 'NotoSansSC', 'Noto Sans SC', 'Microsoft YaHei', 'DejaVu Sans', sans-serif; }
    .hy-doc { font-size: 10.5px; color: #000; line-height: 1.35; }
    .hy-head td { font-size: 14px; font-weight: bold; padding: 1px 0; }
    .hy-title { text-align: center; font-size: 14px; font-weight: bold; margin: 16px 0 10px; }
    table.hy-grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.hy-grid th, table.hy-grid td { border: 1px solid #000; padding: 5px 7px; vertical-align: top; }
    table.hy-grid th { background: #ffe699; text-align: center; font-weight: bold; font-size: 11.5px; }
    table.hy-grid tr { page-break-inside: auto; }
    .hy-c { text-align: center; }
    .rt p { margin: 0 0 4px 0; }
    .rt ul, .rt ol { margin: 2px 0 4px 0; padding-left: 18px; }
    .rt li { margin: 0 0 2px 0; }
    .hy-lvl { font-weight: bold; text-align: center; vertical-align: middle !important; }
    .hy-legend h4 { margin: 0 0 8px; font-size: 12px; }
    .hy-legend .lv { font-weight: bold; margin-top: 12px; }
    .hy-legend .crit { margin-left: 18px; margin-top: 5px; }
    .hy-legend .crit b { display: block; }
    .hy-sign { width: 100%; margin: 6px auto 0; border-collapse: collapse; page-break-inside: avoid; }
    .hy-sign td { border: 1px solid #000; width: 33.33%; height: 130px; vertical-align: top; padding: 6px 8px; font-size: 10.5px; text-align: center; }
</style>
@if($isPdf)
</head>
<body>
@endif

<div class="hy-doc">
    @if($isPdf)
        <div class="hy-logo-fixed">@if(is_file($logo))<img src="{{ $logo }}" style="height: 78px;" alt="Haoyou">@endif</div>
    @else
        <div style="margin-bottom: 12px;"><img src="{{ $logoSrc }}" style="height: 78px;" alt="Haoyou"></div>
    @endif

    <table class="hy-head" style="width:100%;">
        <tr><td style="width: 80px;">Teacher</td><td>: {{ $doc['teacher'] }}</td></tr>
        <tr><td>Student</td><td>: {{ $doc['student'] }}</td></tr>
        <tr><td>Class</td><td>: {{ $doc['class'] }}</td></tr>
        <tr><td>Present</td><td>: {{ $doc['present'] }}</td></tr>
    </table>

    <div class="hy-title">{{ $doc['title'] }}</div>

    <table class="hy-grid">
        <thead>
            <tr>
                <th style="width: 6%;">NO</th>
                <th style="width: 14%;">Tanggal<br>Pertemuan</th>
                <th style="width: 32%;">Learning Materials</th>
                <th style="width: 14%;">Achievement<br>Level</th>
                <th style="width: 34%;">Description</th>
            </tr>
        </thead>
        <tbody>
        @forelse($doc['rows'] as $row)
            <tr>
                <td class="hy-c">{{ $row['no'] }}</td>
                <td class="hy-c" style="font-size: 11px; white-space: nowrap;">{{ $row['meeting_date'] ?? '-' }}</td>
                <td><div class="rt">{!! $rt($row['material']) !!}</div></td>
                <td class="hy-lvl">{{ $row['level'] }}</td>
                <td><div class="rt">{!! $rt($row['description']) !!}</div></td>
            </tr>
        @empty
            <tr><td colspan="5" class="hy-c">Belum ada materi pembelajaran.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if(count($doc['assessments']))
    <table class="hy-grid" style="page-break-inside: avoid;">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 24%;">Assessment</th>
                <th style="width: 22%;">Score</th>
                <th style="width: 10%;">Grade</th>
                <th style="width: 38%;">Note</th>
            </tr>
        </thead>
        <tbody>
        @foreach($doc['assessments'] as $i => $a)
            <tr>
                <td class="hy-c"><b>{{ $i + 1 }}</b></td>
                <td><b>{{ $a['assessment'] }}</b></td>
                <td>{!! $nl($a['score']) !!}</td>
                <td class="hy-c"><b>{{ $a['grade'] }}</b></td>
                <td><i>{!! $nl($a['note']) !!}</i></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    @if($doc['suggestion'] !== '')
        <div style="margin: 4px 0 12px;"><b>Suggestion:</b> {!! $nl($doc['suggestion']) !!}</div>
    @endif

        {{-- Halaman Achievement Level: diketik guru (dikosongkan = tidak dicetak) --}}
    @if(trim($doc['achievement_html']) !== '')
    <div class="hy-legend rt" @if($isPdf) style="page-break-before: always;" @else style="margin-top: 28px; border-top: 1px dashed #bbb; padding-top: 14px;" @endif>
        {!! $rt($doc['achievement_html']) !!}
    </div>
    @endif

    @php
        $sigSrc  = fn ($f) => $isPdf ? $f : 'data:image/png;base64,' . base64_encode(file_get_contents($f));
        $sigDate = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('j F Y') : null;
    @endphp

    <div style="text-align: right; font-size: 10px; margin-top: 22px;">
        {{ $doc['city'] }}, {{ \Illuminate\Support\Carbon::parse($doc['date'])->locale('id')->translatedFormat('j F Y') }}
    </div>

    <table class="hy-sign">
        <tr>
            {{-- 1. Laoshi: otomatis saat report dikirim ke Kurikulum --}}
            <td>
                <b>Laoshi</b>
                <div style="height: 70px; text-align: center; padding-top: 6px;">
                    @if($doc['teacher_signature'])
                        <img src="{{ $sigSrc($doc['teacher_signature']) }}" style="max-height: 60px; max-width: 130px;" alt="TTD Guru">
                    @endif
                </div>
                <div style="margin-top: 6px;"><b>{{ $doc['teacher_sign_name'] }}</b></div>
                @if($sigDate($doc['teacher_signed_at']))
                    <div style="font-size: 8.5px; color: #555;">Dikirim: {{ $sigDate($doc['teacher_signed_at']) }}</div>
                @endif
            </td>

            {{-- 2. Kurikulum: saat report di-ACC --}}
            <td>
                <b>{{ $doc['curriculum_title'] }}</b>
                <div style="height: 70px; text-align: center; padding-top: 6px;">
                    @if($doc['curriculum_signature'])
                        <img src="{{ $sigSrc($doc['curriculum_signature']) }}" style="max-height: 60px; max-width: 130px;" alt="TTD Kurikulum">
                    @endif
                </div>
                <div style="margin-top: 6px;"><b>( {{ $doc['curriculum_signature'] ? $doc['curriculum_name'] : '.....................' }} )</b></div>
                @if($sigDate($doc['curriculum_signed_at']))
                    <div style="font-size: 8.5px; color: #555;">Di-ACC: {{ $sigDate($doc['curriculum_signed_at']) }}</div>
                @endif
            </td>

            {{-- 3. Owner: saat TTD di antrean Owner --}}
            <td>
                <b>{{ $doc['director_title'] }}</b>
                <div style="height: 70px; text-align: center; padding-top: 6px;">
                    @if($doc['owner_signature'])
                        <img src="{{ $sigSrc($doc['owner_signature']) }}" style="max-height: 60px; max-width: 130px;" alt="TTD Owner">
                    @endif
                </div>
                <div style="margin-top: 6px;"><b>( {{ $doc['owner_name'] }} )</b></div>
                @if($sigDate($doc['owner_signed_at']))
                    <div style="font-size: 8.5px; color: #555;">Disahkan: {{ $sigDate($doc['owner_signed_at']) }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>
@if($isPdf)
</body>
</html>
@endif