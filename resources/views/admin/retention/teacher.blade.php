@extends('admin.app')

@section('title', 'Detail Retention ' . $teacherName)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        .back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#6b7280; text-decoration:none; margin-bottom:8px; }
        .back-link:hover { color:#111827; }
        .class-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:18px 20px 6px; flex-wrap:wrap; }
        .class-name { font-size:16px; font-weight:700; }
        .class-meta { font-size:13px; color:#6b7280; margin-top:2px; }
        .class-chips { display:flex; gap:8px; flex-wrap:wrap; }
        .chip { font-size:12px; padding:4px 10px; border-radius:999px; background:#f1f5f9; color:#374151; white-space:nowrap; }
        .chip.green { background:#dcfce7; color:#166534; }
        .chip.amber { background:#fef3c7; color:#92400e; }
        .chip.gray  { background:#f3f4f6; color:#4b5563; }
        .chip.red   { background:#fee2e2; color:#991b1b; }
        .muted { color:#9ca3af; }
        .legend { font-size:13px; color:#6b7280; padding:0 20px 14px; line-height:1.6; }
    </style>
@endpush

@section('content')

    @php
        $s = $detail['summary'];
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    @endphp

    <div class="dashboard-header">
        <div>
            <a href="{{ route('admin.retention.index', ['dari' => $start->toDateString(), 'sampai' => $end->toDateString()]) }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Retention Rate
            </a>
            <div class="eyebrow">Retention Rate - {{ $periodLabel }}</div>
            <h1>{{ $teacherName }}</h1>
            <p>Kelas yang berakhir pada {{ $periodLabel }} dan nasib tiap siswanya setelah kelas selesai.</p>
        </div>
    </div>

    {{-- ========================= RINGKASAN ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Siswa Selesai</span>
                <i class="fa-solid fa-flag-checkered stat-icon"></i>
            </div>
            <div class="stat-value">{{ $s['selesai'] }}</div>
            <div class="stat-note">{{ $detail['classes']->count() }} kelas berakhir</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Lanjut &amp; Sudah Bayar</span>
                <i class="fa-solid fa-user-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $s['lanjut'] }}</div>
            <div class="stat-note">Dihitung di retention</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Minat Lanjut, Belum Bayar</span>
                <i class="fa-solid fa-hourglass-half stat-icon"></i>
            </div>
            <div class="stat-value">{{ $s['menunggu_bayar'] }}</div>
            <div class="stat-note">Sudah daftar lanjut, belum ada pembayaran</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Retention Rate</span>
                <i class="fa-solid fa-arrow-trend-up stat-icon"></i>
            </div>
            <div class="stat-value">
                {{ is_null($s['rate']) ? '-' : number_format($s['rate'], 1, ',', '.') . '%' }}
            </div>
            <div class="stat-note">
                {{ $s['tidak_lanjut'] }} belum lanjut · {{ $s['expired'] ?? 0 }} expired
            </div>
        </div>
    </div>

    {{-- ========================= PER KELAS ========================= --}}
    @forelse ($detail['classes'] as $class)
        <div class="card" style="margin-bottom:24px;">
            <div class="class-head">
                <div>
                    <div class="class-name">{{ $class['name'] }}</div>
                    <div class="class-meta">
                        Berakhir {{ \Carbon\Carbon::parse($class['end_date'])->translatedFormat('d F Y') }}
                    </div>
                </div>

                <div class="class-chips">
                    <span class="chip">{{ $class['selesai'] }} siswa selesai</span>
                    <span class="chip green">{{ $class['lanjut'] }} lanjut &amp; bayar</span>
                    @if ($class['menunggu_bayar'] > 0)
                        <span class="chip amber">{{ $class['menunggu_bayar'] }} belum bayar</span>
                    @endif
                    @if ($class['tidak_lanjut'] > 0)
                        <span class="chip gray">{{ $class['tidak_lanjut'] }} belum lanjut</span>
                    @endif
                    @if (($class['expired'] ?? 0) > 0)
                        <span class="chip red">{{ $class['expired'] }} expired</span>
                    @endif
                    <span class="chip">
                        RR {{ is_null($class['rate']) ? '-' : number_format($class['rate'], 1, ',', '.') . '%' }}
                    </span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th style="text-align: center; vertical-align: middle;">Status</th>
                        <th>Lanjut ke</th>
                        <th style="text-align: center; vertical-align: middle;">Sudah Dibayar</th>
                        <th style="text-align: center; vertical-align: middle;">Follow Up Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($class['students'] as $st)
                        @php
                            [$badge, $label] = match ($st['state']) {
                                'paid'    => ['badge-completed', 'Lanjut & sudah bayar'],
                                'unpaid'  => ['badge-pending',   'Minat lanjut, belum bayar'],
                                'expired' => ['badge-cancelled', 'Expired (> 1 bulan)'],
                                default   => ['badge-cancelled', 'Belum lanjut (< 1 bulan)'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="name-cell">
                                    <div class="avatar-sm">{{ strtoupper(substr($st['name'], 0, 1)) }}</div>
                                    <div class="cand-name">{{ $st['name'] }}</div>
                                </div>
                            </td>

                            <td style="text-align: center; vertical-align: middle;">
                                <span class="badge {{ $badge }}">
                                    <span class="badge-dot"></span>
                                    {{ $label }}
                                </span>
                            </td>

                            <td>
                                @if ($st['next_label'])
                                    {{ $st['next_label'] }}
                                    @if ($st['next_status'] === 'Waiting Class')
                                        <div class="cand-sub">Menunggu kelas</div>
                                    @endif
                                    @if ($st['state'] === 'expired')
                                        <div class="cand-sub">Daftar lewat 1 bulan, tidak dihitung</div>
                                    @endif
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>

                            <td style="text-align: center; vertical-align: middle;">
                                {{ $st['paid_total'] > 0 ? $rupiah($st['paid_total']) : '-' }}
                            </td>

                            <td style="text-align: center; vertical-align: middle;">
                                @if ($st['follow_up'])
                                    {{ $st['follow_up'] }}
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="card">
            <div style="text-align:center;padding:50px;color:#888;">
                Tidak ada kelas yang berakhir pada {{ $periodLabel }} untuk guru ini.
            </div>
        </div>
    @endforelse

    <div class="legend">
        <strong>Cara membaca:</strong>
        <em>Lanjut &amp; sudah bayar</em> = siswa punya enrollment baru dalam 1 bulan setelah kelas berakhir dan sudah ada pembayaran masuk (dihitung retention).
        <em>Minat lanjut, belum bayar</em> = sudah didaftarkan lanjut tapi belum ada pembayaran (tidak dihitung).
        <em>Belum lanjut</em> = belum ada enrollment baru, batas 1 bulan setelah kelas berakhir belum lewat.
        <em>Expired</em> = lewat 1 bulan setelah kelas berakhir tanpa lanjut &amp; bayar tepat waktu (termasuk yang daftar lagi setelah lewat batas); tidak dihitung lanjut dan harus daftar ulang.
        Kolom Follow Up Admin hanya info dari Progress Report, tidak mempengaruhi angka.
    </div>

@endsection