<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Progress Report - {{ $doc['student'] }}</title>
<style>
    body { background:#eef0f4; margin:0; font-family: Arial, sans-serif; }
    .bar { position: sticky; top:0; background:#1d1d27; color:#fff; padding:10px 16px; display:flex; gap:10px; align-items:center; flex-wrap:wrap; z-index:5; }
    .bar a, .bar button { background:#ffd600; color:#1d1d27; border:0; padding:7px 14px; border-radius:20px; text-decoration:none; font-weight:700; font-size:13px; cursor:pointer; }
    .bar .sp { flex:1; }
    .flash { background:#d1fae5; color:#065f46; padding:8px 16px; }
    .paper { background:#fff; max-width: 860px; margin: 18px auto; padding: 30px 40px; box-shadow: 0 4px 18px rgba(0,0,0,.12); }
    @media print { .bar, .flash { display:none; } body { background:#fff; } .paper { box-shadow:none; margin:0; max-width:none; } }
</style>
</head>
<body>
<div class="bar">
    <strong>Pratinjau Progress Report</strong>
    <span style="font-size:12px;opacity:.8">Status: {{ $doc['status'] }}{{ $doc['is_signed'] ? ' · sudah ditandatangani Owner' : ' · belum ditandatangani Owner' }}</span>
    <span class="sp"></span>
    @if($canEdit)<a href="{{ route('progress-report.document.edit', $report) }}">Edit teks</a>@endif
    <a href="{{ route('progress-report.document.pdf', $report) }}" target="_blank">Buka PDF</a>
    <button onclick="window.print()">Cetak</button>
    <a href="javascript:history.back()" style="background:#fff">Kembali</a>
</div>
@if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
<div class="paper">
    @include('partials.progress-report-document', ['doc' => $doc, 'mode' => 'html'])
</div>
</body>
</html>