@extends('admin.app')

@section('title', 'Siswa Retention')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        .back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#6b7280; text-decoration:none; margin-bottom:8px; }
        .back-link:hover { color:#111827; }
        .tabs { display:flex; gap:8px; flex-wrap:wrap; padding:18px 20px 12px; }
        .tab { font-size:13px; padding:6px 14px; border-radius:999px; background:#f1f5f9; color:#374151; text-decoration:none; white-space:nowrap; }
        .tab:hover { background:#e5e7eb; }
        .tab.active { background:#111827; color:#fff; }
        .tab .count { opacity:.7; margin-left:4px; }
        .muted { color:#9ca3af; }
        .sub { font-size:12px; color:#6b7280; margin-top:2px; }
        .date-sub { font-size:12px; color:#6b7280; margin-top:2px; }
        tfoot td { padding: 14px 20px; }
    </style>
@endpush

@section('content')

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $query  = ['dari' => $start->toDateString(), 'sampai' => $end->toDateString()];

        $tabs = [
            null      => ['Semua',                'all'],
            'paid'    => ['Lanjut & sudah bayar', 'paid'],
            'unpaid'  => ['Belum bayar',          'unpaid'],
            'none'    => ['Belum lanjut',         'none'],
            'expired' => ['Expired',              'expired'],
        ];
    @endphp

    <div class="dashboard-header">
        <div>
            <a href="{{ route('admin.retention.index', $query) }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Retention Rate
            </a>
            <div class="eyebrow">Retention Rate - {{ $periodLabel }}</div>
            <h1>Daftar Siswa</h1>
            <p>Siswa yang kelasnya berakhir pada {{ $periodLabel }}, beserta program sebelum dan sesudah yang diikuti.</p>
        </div>
    </div>

    <div class="card">
        <div class="tabs">
            @foreach ($tabs as $key => [$label, $countKey])
                <a href="{{ route('admin.retention.students', $query + ($key ? ['status' => $key] : [])) }}"
                   class="tab {{ ($state ?? null) === $key ? 'active' : '' }}">
                    {{ $label }}<span class="count">{{ $counts[$countKey] }}</span>
                </a>
            @endforeach
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:48px; text-align:center;">#</th>
                    <th>Siswa</th>
                    <th>Program Sebelumnya</th>
                    <th>Program Sesudahnya</th>
                    <th style="text-align: center; vertical-align: middle;">Status</th>
                    <th style="text-align: center; vertical-align: middle;">Pembayaran Lanjutan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $i => $st)
                    @php
                        [$badge, $label] = match ($st['state']) {
                            'paid'    => ['badge-completed', 'Lanjut & sudah bayar'],
                            'unpaid'  => ['badge-pending',   'Minat lanjut, belum bayar'],
                            'expired' => ['badge-cancelled', 'Expired (> 1 bulan)'],
                            default   => ['badge-cancelled', 'Belum lanjut (< 1 bulan)'],
                        };
                    @endphp
                    <tr>
                        <td style="text-align:center;"><span class="muted">{{ $i + 1 }}</span></td>

                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($st['name'], 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $st['name'] }}</div>
                                    @if ($st['teacher'])
                                        <div class="cand-sub">Guru: {{ $st['teacher'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- PROGRAM SEBELUMNYA --}}
                        <td>
                            <div>{{ $st['prev_program'] }}</div>
                            @if ($st['prev_class'] && $st['prev_class'] !== $st['prev_program'])
                                <div class="sub">{{ $st['prev_class'] }}</div>
                            @endif
                            <div class="date-sub">
                                @if ($st['prev_start'])
                                    {{ $st['prev_start']->translatedFormat('d M Y') }} –
                                @endif
                                {{ $st['prev_end']?->translatedFormat('d M Y') ?? '-' }}
                            </div>
                        </td>

                        {{-- PROGRAM SESUDAHNYA --}}
                        <td>
                            @if ($st['next_program'])
                                <div>{{ $st['next_program'] }}</div>
                                @if ($st['next_class'] && $st['next_class'] !== $st['next_program'])
                                    <div class="sub">{{ $st['next_class'] }}</div>
                                @endif
                                <div class="date-sub">
                                    Daftar {{ $st['next_date']?->translatedFormat('d M Y') ?? '-' }}
                                    @if ($st['next_status'] === 'Waiting Class')
                                        · Menunggu kelas
                                    @endif
                                </div>
                                @if ($st['state'] === 'expired')
                                    <div class="date-sub">Lewat 1 bulan, tidak dihitung</div>
                                @endif
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge {{ $badge }}">
                                <span class="badge-dot"></span>
                                {{ $label }}
                            </span>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            @if ($st['paid_total'] > 0)
                                <div>{{ $rupiah($st['paid_total']) }}</div>
                                @if ($st['paid_date'])
                                    <div class="date-sub">Bayar pertama {{ $st['paid_date']->translatedFormat('d M Y') }}</div>
                                @endif
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:50px;color:#888;">
                            Tidak ada siswa untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan <strong>{{ $students->count() }}</strong> siswa
                dengan kelas berakhir pada <strong>{{ $periodLabel }}</strong>
            </span>
        </div>
    </div>

@endsection