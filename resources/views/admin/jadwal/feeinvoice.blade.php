@php
    $session   = $fee->session;
    $student   = $fee->enrollment?->student?->name ?? '-';
    $className = $session->class?->class_name;
    $isHome    = $fee->fee_type === 'Home Visit';

    $itemLabel = $isHome ? 'Biaya guru mengajar ke rumah murid' : 'Pertemuan tambahan (kelas pengganti)';
    $when      = $session->session_date->locale('id')->translatedFormat('l, j F Y')
                 . ', ' . substr($session->start_time, 0, 5) . '–' . substr($session->end_time, 0, 5);
    $amount    = 'Rp' . number_format((float) $fee->amount, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $fee->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 0; padding: 32px; }
        .invoice { max-width: 720px; margin: 0 auto; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 12px; }
        .brand { font-size: 22px; font-weight: 700; }
        .meta { text-align: right; font-size: 13px; line-height: 1.6; }
        .meta strong { font-size: 15px; }
        .block { margin-top: 20px; font-size: 14px; line-height: 1.7; }
        .block span { display: inline-block; min-width: 130px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f3f3f3; }
        td.num, th.num { text-align: right; }
        .total td { font-weight: 700; background: #fafafa; }
        .stamp { display: inline-block; margin-top: 18px; padding: 4px 14px; border: 2px solid #1a7f37; color: #1a7f37; font-weight: 700; letter-spacing: 1px; }
        .foot { margin-top: 28px; font-size: 12px; color: #666; }
        .actions { margin-bottom: 18px; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="invoice">
    <div class="actions"><button onclick="window.print()">Cetak</button></div>

    <div class="head">
        <div class="brand">Haoyou Educator</div>
        <div class="meta">
            <strong>INVOICE</strong><br>
            No: {{ $fee->invoice_number }}<br>
            Tanggal: {{ $fee->paid_at->locale('id')->translatedFormat('j F Y') }}
        </div>
    </div>

    <div class="block">
        <div><span>Nama siswa</span>{{ $student }}</div>
        @if ($className)
            <div><span>Kelas</span>{{ $className }}</div>
        @endif
        <div><span>Jadwal pertemuan</span>{{ $when }}</div>
        <div><span>Guru</span>{{ $session->teacher?->name ?? '-' }}</div>
        <div><span>Lokasi</span>{{ $session->location_type }}{{ $session->room && $session->location_type === 'Studio' ? ' · ' . $session->room : '' }}</div>
    </div>

    <table>
        <thead>
            <tr><th>Keterangan</th><th class="num">Jumlah</th></tr>
        </thead>
        <tbody>
            <tr><td>{{ $itemLabel }}</td><td class="num">{{ $amount }}</td></tr>
            <tr class="total"><td>Total dibayar</td><td class="num">{{ $amount }}</td></tr>
        </tbody>
    </table>

    <div class="block">
        <div><span>Metode pembayaran</span>{{ $fee->payment_method }}</div>
        <div><span>Tanggal bayar</span>{{ $fee->paid_at->locale('id')->translatedFormat('j F Y') }}</div>
    </div>

    <div class="stamp">LUNAS</div>

    <div class="foot">Invoice ini adalah bukti pembayaran biaya tambahan dan terpisah dari invoice pembayaran paket.</div>
</div>
</body>
</html>