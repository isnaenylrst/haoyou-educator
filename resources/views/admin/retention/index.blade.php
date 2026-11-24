@extends('admin.app')

@section('title', 'Retention Rate')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        .rate-bar { display:flex; align-items:center; gap:10px; }
        .rate-bar-track { flex:1; height:8px; background:#f1f5f9; border-radius:999px; overflow:hidden; }
        .rate-bar-fill { height:100%; background:#16a34a; border-radius:999px; }
        .rate-bar-text { min-width:52px; text-align:right; font-weight:600; }
        .rank-no { font-weight:700; color:#6b7280; }
        .card-title { font-size:16px; font-weight:700; padding:18px 20px 4px; }
        .card-desc { font-size:13px; color:#6b7280; padding:0 20px 12px; }
        .pay-wait { font-size:12px; color:#b45309; }
    </style>
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">Lainnya</div>
            <h1>Retention Rate</h1>
            <p>
                Pantau persentase kelanjutan dan pembayaran siswa setiap bulan serta performa guru berdasarkan retensi siswa dan beban mengajar.
            </p>
        </div>
    </div>

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Siswa Selesai</span>
                <i class="fa-solid fa-flag-checkered stat-icon"></i>
            </div>
            <div class="stat-value">{{ $summary['selesai'] }}</div>
            <div class="stat-note">Kelas berakhir {{ $month->translatedFormat('F Y') }}</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Siswa Lanjut</span>
                <i class="fa-solid fa-user-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $summary['lanjut'] }}</div>
            <div class="stat-note">
                Sudah bayar
                @if (($summary['menunggu_bayar'] ?? 0) > 0)
                    · {{ $summary['menunggu_bayar'] }} menunggu pembayaran
                @endif
            </div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Retention Rate</span>
                <i class="fa-solid fa-arrow-trend-up stat-icon"></i>
            </div>
            <div class="stat-value">
                {{ is_null($summary['rate']) ? '-' : number_format($summary['rate'], 1, ',', '.') . '%' }}
            </div>
            <div class="stat-note">Siswa lanjut (sudah bayar) / siswa selesai</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Ranking 1 Bulan Ini</span>
                <i class="fa-solid fa-trophy stat-icon"></i>
            </div>
            @php $top = $teachers->first(); @endphp
            <div class="stat-value" style="font-size:22px;">
                {{ $top ? ($names[$top['teacher_id']] ?? 'Belum ada guru') : '-' }}
            </div>
            <div class="stat-note">
                {{ $top ? 'Score ' . number_format($top['score'], 1, ',', '.') : 'Belum ada data' }}
            </div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.retention.index') }}" id="filterForm">
        <div class="toolbar">
            <input
                type="month"
                name="bulan"
                class="select-chip"
                value="{{ $month->format('Y-m') }}"
                max="{{ now()->format('Y-m') }}"
                onchange="this.form.submit()">
        </div>
    </form>

    {{-- ========================= TREN 6 BULAN ========================= --}}
    <div class="card">
        <div class="card-title">Tren 6 Bulan Terakhir</div>
        <div class="card-desc">
            Retention rate bulanan dari kelas yang berakhir di tiap bulan.
            Hanya siswa yang sudah membayar program lanjutannya yang dihitung lanjut.
        </div>

        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th style="text-align: center; vertical-align: middle;">Siswa Selesai</th>
                    <th style="text-align: center; vertical-align: middle;">Siswa Lanjut</th>
                    <th style="text-align: center; vertical-align: middle;">Menunggu Bayar</th>
                    <th style="width:36%;">Retention Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trend as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td style="text-align: center; vertical-align: middle;">{{ $row['selesai'] }}</td>
                        <td style="text-align: center; vertical-align: middle;">{{ $row['lanjut'] }}</td>
                        <td style="text-align: center; vertical-align: middle;">
                            @if (($row['menunggu_bayar'] ?? 0) > 0)
                                <span class="pay-wait">{{ $row['menunggu_bayar'] }}</span>
                            @else
                                <span style="color:#bbb;">-</span>
                            @endif
                        </td>
                        <td>
                            @if (is_null($row['rate']))
                                <span style="color:#888;">Tidak ada kelas berakhir</span>
                            @else
                                <div class="rate-bar">
                                    <div class="rate-bar-track">
                                        <div class="rate-bar-fill" style="width: {{ min($row['rate'], 100) }}%"></div>
                                    </div>
                                    <span class="rate-bar-text">{{ number_format($row['rate'], 1, ',', '.') }}%</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ========================= RANKING GURU ========================= --}}
    <div class="card" style="margin-top:24px;">
        <div class="card-title">Ranking Guru - {{ $month->translatedFormat('F Y') }}</div>
        <div class="card-desc">
            Score = (Retention Rate × 70%) + (Teaching Load × 30%).
            Teaching Load dibandingkan dengan guru yang paling banyak mengajar bulan ini.
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:60px; text-align:center;">#</th>
                    <th>Guru</th>
                    <th style="text-align: center; vertical-align: middle;">Siswa Selesai</th>
                    <th style="text-align: center; vertical-align: middle;">Siswa Lanjut</th>
                    <th style="text-align: center; vertical-align: middle;">Retention Rate</th>
                    <th style="text-align: center; vertical-align: middle;">Pertemuan</th>
                    <th style="text-align: center; vertical-align: middle;">Teaching Load</th>
                    <th style="text-align: center; vertical-align: middle;">Score</th>
                    <th style="text-align: center; vertical-align: middle;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($teachers as $i => $t)
                    @php
                        $teacherName = $names[$t['teacher_id']] ?? 'Belum ada guru';
                        $rrClass = match (true) {
                            $t['rr'] >= 70 => 'badge-completed',
                            $t['rr'] >= 40 => 'badge-pending',
                            default        => 'badge-cancelled',
                        };
                    @endphp
                    <tr>
                        <td style="text-align:center;"><span class="rank-no">{{ $i + 1 }}</span></td>

                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($teacherName, 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $teacherName }}</div>
                                    @if ($t['selesai'] < 3)
                                        <div class="cand-sub">Sampel kecil (&lt; 3 siswa)</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">{{ $t['selesai'] }}</td>

                        <td style="text-align: center; vertical-align: middle;">
                            {{ $t['lanjut'] }}
                            @if (($t['menunggu_bayar'] ?? 0) > 0)
                                <div class="pay-wait">+{{ $t['menunggu_bayar'] }} menunggu bayar</div>
                            @endif
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge {{ $rrClass }}">
                                <span class="badge-dot"></span>
                                {{ number_format($t['rr'], 1, ',', '.') }}%
                            </span>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">{{ $t['pertemuan'] }}</td>
                        <td style="text-align: center; vertical-align: middle;">
                            {{ number_format($t['tl'], 1, ',', '.') }}%
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <strong>{{ number_format($t['score'], 1, ',', '.') }}</strong>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <div class="row-actions" style="justify-content:center;">
                                <a href="{{ route('admin.retention.teacher', ['teacherId' => $t['teacher_id'], 'bulan' => $month->format('Y-m')]) }}"
                                class="icon-btn"
                                title="Lihat detail siswa">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>                        
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:50px;color:#888;">
                            Belum ada kelas yang berakhir di bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan <strong>{{ $teachers->count() }}</strong> guru
                dengan kelas berakhir di <strong>{{ $month->translatedFormat('F Y') }}</strong>
            </span>
        </div>
    </div>

@endsection