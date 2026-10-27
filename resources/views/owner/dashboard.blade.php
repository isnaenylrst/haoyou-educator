@extends('layouts.owner')

@section('title', 'Dashboard Owner')

@php
    $rp    = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $pc    = fn ($v, $d = 0) => $v === null ? '-' : number_format((float) $v, $d, ',', '.') . '%';
    $delta = function ($now, $prev) {
        if ($prev === null || (float) $prev <= 0) {
            return null;
        }

        return (int) round((($now - $prev) / $prev) * 100);
    };

    // warna per jenis kelas (sama dengan jadwal Guru/Kurikulum)
    $famColor = [
        'Maochong' => '#e8a317', 'Jianer' => '#3b82f6', 'Hudie' => '#8b5cf6', 'Feixiang' => '#ef4444',
        'HSK' => '#22a05a', 'Private' => '#0ea5b7', 'Lainnya' => '#8b8fa3',
    ];

    $dMonth = $delta($fin['monthNow'], $fin['monthPrev']);
    $dYtd   = $delta($fin['ytd'], $fin['ytdPrev']);

    $hasRevenue = array_sum($fin['series']) + array_sum($fin['prevSeries']) > 0;
    $hasYears   = array_sum(array_column($fin['years'], 'total')) > 0;

    $teacherChart = collect($tea['rows'])->filter(fn ($r) => $r['pct'] !== null)->values();
    $roleGroups   = collect(\App\Support\OwnerMenu::groups())->reject(fn ($g) => $g['key'] === 'owner');
@endphp

@push('styles')
<style>
    .sec { scroll-margin-top: 130px; margin-bottom: 8px; }
    .sec-title { font-size: 18px; font-weight: 700; margin: 22px 0 4px; display: flex; align-items: center; gap: 8px; }
    .sec-sub { font-size: 12.5px; color: var(--muted); margin-bottom: 14px; }

    .navpills {
        position: sticky; top: 68px; z-index: 1020; background: var(--bg); padding: 10px 0 12px; margin: 0 0 6px;
        display: flex; flex-wrap: wrap; gap: 6px;
    }
    .navpills a {
        text-decoration: none; font-size: 12.5px; font-weight: 600; color: var(--text);
        border: 1px solid var(--line); background: var(--surface); border-radius: 99px; padding: 6px 14px;
    }
    .navpills a:hover { background: var(--accent-soft); }

    .alert-card { display: flex; gap: 12px; align-items: flex-start; border-radius: 12px; padding: 12px 14px; height: 100%;
        text-decoration: none; border: 1px solid var(--line); background: var(--surface); color: var(--text); border-left-width: 4px; }
    .alert-card:hover { background: var(--accent-soft); color: var(--text); }
    .alert-card.danger  { border-left-color: #ef4444; }
    .alert-card.warning { border-left-color: #e8a317; }
    .alert-card.success { border-left-color: #22a05a; }
    .alert-card .ai { font-size: 18px; margin-top: 2px; }
    .alert-card.danger .ai  { color: #ef4444; }
    .alert-card.warning .ai { color: #e8a317; }
    .alert-card.success .ai { color: #22a05a; }
    .alert-card .at { font-size: 13.5px; font-weight: 700; line-height: 1.3; }
    .alert-card .ad { font-size: 12px; color: var(--muted); }

    .kpi { height: 100%; margin-bottom: 0 !important; }
    .kpi-label { font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
    .kpi-value { font-size: 24px; font-weight: 700; line-height: 1.25; margin: 6px 0 4px; color: var(--text); }
    .kpi-sub { font-size: 12.5px; color: var(--muted); }
    .kpi-ico { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        background: var(--accent-soft); color: #a9822c; font-size: 17px; flex-shrink: 0; }

    .chart-box { position: relative; height: 290px; }
    .chart-box.sm { height: 210px; }
    .chart-empty { height: 200px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 13px; text-align: center; }

    .tile { border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; text-align: center; height: 100%; }
    .tile .v { font-size: 21px; font-weight: 700; line-height: 1.2; }
    .tile .l { font-size: 11.5px; color: var(--muted); }

    .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px; }
    .up   { color: #1a9b50; }
    .down { color: #d93636; }
    .mini-note { font-size: 11.5px; color: var(--muted); }

    .toggle-group { display: inline-flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .toggle-group button { border: 0; background: transparent; color: var(--text); font-size: 12px; font-weight: 600; padding: 5px 12px; cursor: pointer; font-family: inherit; }
    .toggle-group button.active { background: var(--accent); color: #1d1d27; }

    .kv { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
    .kv:last-child { border-bottom: 0; }
    .kv span:first-child { color: var(--muted); }
    .kv span:last-child { font-weight: 600; text-align: right; }

    .funnel-row { margin-bottom: 12px; }
    .funnel-row .fl { display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px; }

    .role-card { margin-bottom: 0 !important; overflow: hidden; }
    .role-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px 4px; }
    .role-chip { color: #fff; font-size: 11.5px; font-weight: 700; letter-spacing: .06em; padding: 4px 12px; border-radius: 99px; }
    .role-count { font-size: 12px; color: var(--muted); }
    .role-desc { padding: 0 18px 10px; font-size: 12.5px; color: var(--muted); border-bottom: 1px solid var(--line); }
    .feat { display: flex; align-items: center; gap: 12px; padding: 10px 18px; text-decoration: none; color: var(--text); border-bottom: 1px solid var(--line); }
    .feat:last-child { border-bottom: 0; }
    .feat:hover { background: var(--accent-soft); color: var(--text); }
    .feat-ico { width: 22px; text-align: center; font-size: 15px; flex-shrink: 0; }
    .feat-txt { display: flex; flex-direction: column; min-width: 0; flex: 1; line-height: 1.3; }
    .feat-name { font-size: 13.5px; font-weight: 600; }
    .feat-desc { font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .feat-go { font-size: 10px; color: var(--muted); }

    @media print { .navpills, .btn { display: none !important; } }
</style>
@endpush

@section('content')

<div class="container-fluid">

    {{-- ===== HEADER ===== --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2" id="ringkasan">
        <div>
            <h2 class="fw-bold mb-1">Dashboard Owner</h2>
            <p class="text-muted mb-0">Perkembangan perusahaan dan bahan evaluasi · {{ $monthLabel }}</p>
        </div>

        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Cetak
        </button>
    </div>

    <div class="navpills">
        <a href="#ringkasan">Ringkasan</a>
        <a href="#keuangan">Keuangan</a>
        <a href="#siswa">Siswa & Retensi</a>
        <a href="#guru">Ketepatan Guru</a>
        <a href="#kelas">Kelas & Operasional</a>
        <a href="#marketing">Marketing</a>
        <a href="#akademik">Mutu Akademik</a>
        <a href="#fitur">Semua Fitur</a>
    </div>

    {{-- ===== PERINGATAN OTOMATIS ===== --}}
    <div class="sec-title" style="margin-top:6px"><i class="fa-solid fa-bell"></i> Perlu Perhatian Owner</div>
    <div class="sec-sub">Dibuat otomatis dari data; klik untuk menuju bagian atau fitur terkait.</div>

    <div class="row g-3 mb-4">
        @foreach($alerts as $al)
            <div class="col-xl-4 col-md-6">
                <a href="{{ $al['url'] }}" class="alert-card {{ $al['level'] }}">
                    <i class="fa-solid {{ $al['icon'] }} ai"></i>
                    <div>
                        <div class="at">{{ $al['title'] }}</div>
                        <div class="ad">{{ $al['text'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- ===== 8 KPI UTAMA ===== --}}
    <div class="row g-3 mb-3">

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Pendapatan Bulan Ini</div>
                    <div class="kpi-value">{{ $rp($fin['monthNow']) }}</div>
                    <div class="kpi-sub">
                        @if($dMonth === null) Belum ada pembanding bulan lalu
                        @elseif($dMonth >= 0) <span class="up"><i class="fa-solid fa-arrow-trend-up"></i> {{ $dMonth }}%</span> dari bulan lalu ({{ $rp($fin['monthPrev']) }})
                        @else <span class="down"><i class="fa-solid fa-arrow-trend-down"></i> {{ abs($dMonth) }}%</span> dari bulan lalu ({{ $rp($fin['monthPrev']) }})
                        @endif
                    </div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-wallet"></i></div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Pendapatan Tahun Ini</div>
                    <div class="kpi-value">{{ $rp($fin['ytd']) }}</div>
                    <div class="kpi-sub">
                        @if($dYtd === null) Belum ada pembanding tahun lalu
                        @elseif($dYtd >= 0) <span class="up"><i class="fa-solid fa-arrow-trend-up"></i> {{ $dYtd }}%</span> dari periode sama tahun lalu
                        @else <span class="down"><i class="fa-solid fa-arrow-trend-down"></i> {{ abs($dYtd) }}%</span> dari periode sama tahun lalu
                        @endif
                    </div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-chart-line"></i></div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Belum Lunas (Piutang)</div>
                    <div class="kpi-value {{ $fin['outstanding'] > 0 ? 'down' : '' }}">{{ $rp($fin['outstanding']) }}</div>
                    <div class="kpi-sub">{{ $fin['unpaidCount'] }} pendaftaran · tertagih {{ $pc($fin['collectionRate'], 1) }}</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Siswa Aktif</div>
                    <div class="kpi-value">{{ number_format($stu['active'], 0, ',', '.') }}</div>
                    <div class="kpi-sub"><span class="up">+{{ $stu['newNow'] }}</span> baru bulan ini · {{ $stu['newPrev'] }} bulan lalu</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-user-graduate"></i></div>
            </div></div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Tingkat Siswa Lanjut</div>
                    <div class="kpi-value">{{ $pc($stu['renewalRate'], 1) }}</div>
                    <div class="kpi-sub">{{ $stu['renewedStudents'] }} dari {{ $stu['completedStudents'] }} siswa yang selesai paket</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-rotate"></i></div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Ketepatan Guru</div>
                    <div class="kpi-value">{{ $pc($tea['overallPct'], 1) }}</div>
                    <div class="kpi-sub">jurnal tepat waktu · {{ $tea['good'] }} guru tepat waktu, {{ $tea['bad'] }} sering terlambat</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-clock"></i></div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Keterisian Kelas</div>
                    <div class="kpi-value">{{ $pc($cls['utilization'], 1) }}</div>
                    <div class="kpi-sub">{{ $cls['students'] }} dari {{ $cls['capacity'] }} kursi · {{ $cls['running'] }} kelas berjalan</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-chalkboard"></i></div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Conversion Lead</div>
                    <div class="kpi-value">{{ $pc($mkt['conversionRate'], 1) }}</div>
                    <div class="kpi-sub">{{ $mkt['converted'] }} dari {{ $mkt['total'] }} calon siswa menjadi siswa</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-bullseye"></i></div>
            </div></div>
        </div>

    </div>

    {{-- ==========================================================
        KEUANGAN
    =========================================================== --}}
    <div class="sec" id="keuangan">
        <div class="sec-title"><i class="fa-solid fa-wallet"></i> Keuangan</div>
        <div class="sec-sub">Pendapatan dihitung dari jumlah yang sudah dibayar (amount paid) pada tanggal pembayaran.</div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Pendapatan</span>
                        <div class="toggle-group" id="revToggle">
                            <button type="button" class="active" data-target="boxMonthly">12 Bulan</button>
                            <button type="button" data-target="boxYearly">3 Tahun</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="boxMonthly">
                            @if($hasRevenue)
                                <div class="chart-box"><canvas id="chRevenue"></canvas></div>
                                <div class="mini-note mt-2">Batang kuning = tahun ini, garis abu-abu = bulan yang sama tahun lalu.</div>
                            @else
                                <div class="chart-empty">Belum ada data pembayaran.</div>
                            @endif
                        </div>
                        <div id="boxYearly" class="d-none">
                            @if($hasYears)
                                <div class="chart-box"><canvas id="chYears"></canvas></div>
                            @else
                                <div class="chart-empty">Belum ada data pembayaran.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Ringkasan Pendapatan</div>
                    <div class="card-body">
                        @foreach($fin['years'] as $y)
                            <div class="kv">
                                <span>Tahun {{ $y['year'] }}</span>
                                <span>
                                    {{ $rp($y['total']) }}
                                    @if($y['growth'] !== null)
                                        <small class="{{ $y['growth'] >= 0 ? 'up' : 'down' }}">({{ $y['growth'] >= 0 ? '+' : '' }}{{ $y['growth'] }}%)</small>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                        <div class="kv"><span>Rata-rata 3 bulan penuh terakhir</span><span>{{ $rp($fin['avg3']) }}</span></div>
                        <div class="kv"><span>Proyeksi akhir tahun</span><span>{{ $rp($fin['projection']) }}</span></div>
                        <div class="kv"><span>Tagihan tertagih</span><span>{{ $pc($fin['collectionRate'], 1) }}</span></div>
                        <div class="mini-note mt-2">Proyeksi = pendapatan tahun ini + rata-rata 3 bulan × sisa bulan.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Pendapatan per Program (12 bulan)</div>
                    <div class="card-body">
                        @if($fin['byProgram']->sum('total') > 0)
                            <div class="chart-box sm"><canvas id="chProgram"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Metode Pembayaran (12 bulan)</div>
                    <div class="card-body">
                        @if($fin['byMethod']->sum('total') > 0)
                            <div class="chart-box sm"><canvas id="chMethod"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Umur Piutang</div>
                    <div class="card-body">
                        @foreach($fin['aging'] as $label => $ag)
                            <div class="kv">
                                <span>{{ $label }} <small>({{ $ag['count'] }})</small></span>
                                <span class="{{ in_array($label, ['61-90 hari', '> 90 hari'], true) && $ag['count'] > 0 ? 'down' : '' }}">{{ $rp($ag['total']) }}</span>
                            </div>
                        @endforeach
                        <div class="mini-note mt-2">Dihitung sejak tanggal pembayaran terakhir tiap pendaftaran.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Sisa Tagihan Terbesar</span>
                <span class="badge {{ $fin['unpaidCount'] > 0 ? 'bg-danger' : 'bg-success' }}">{{ $fin['unpaidCount'] }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th><th>Kelas</th><th>Tahap</th>
                            <th class="text-end">Tagihan</th><th class="text-end">Dibayar</th><th class="text-end">Sisa</th>
                            <th>Pembayaran Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fin['unpaidList'] as $u)
                            <tr>
                                <td class="fw-semibold">{{ $u->student }}</td>
                                <td>{{ $u->class_name ?? '-' }}</td>
                                <td><span class="badge bg-warning text-dark">{{ $u->payment_stage }}</span></td>
                                <td class="text-end">{{ $rp($u->total_bill) }}</td>
                                <td class="text-end">{{ $rp($u->amount_paid) }}</td>
                                <td class="text-end fw-bold down">{{ $rp($u->remaining_bill) }}</td>
                                <td>{{ \Carbon\Carbon::parse($u->payment_date)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Semua pembayaran sudah lunas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==========================================================
        SISWA & RETENSI
    =========================================================== --}}
    <div class="sec" id="siswa">
        <div class="sec-title"><i class="fa-solid fa-graduation-cap"></i> Siswa & Retensi</div>
        <div class="sec-sub">"Lanjut" = pendaftaran paket ke-2 dan seterusnya oleh siswa yang sama (tidak termasuk yang dibatalkan).</div>

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-white">Siswa Baru vs Siswa Lanjut (12 bulan)</div>
                    <div class="card-body">
                        @if(array_sum($stu['newSeries']) + array_sum($stu['renewSeries']) > 0)
                            <div class="chart-box"><canvas id="chNewRenew"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data siswa.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header bg-white">Status & Lama Belajar</div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-4"><div class="tile"><div class="v up">{{ $stu['active'] }}</div><div class="l">Aktif</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v">{{ $stu['inactive'] }}</div><div class="l">Nonaktif</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v">{{ $stu['graduated'] }}</div><div class="l">Lulus</div></div></div>
                        </div>

                        <div class="kv"><span>Rata-rata lama belajar (aktif)</span><span>{{ $stu['avgTenure'] !== null ? $stu['avgTenure'] . ' bulan' : '-' }}</span></div>
                        <div class="kv"><span>Rata-rata paket per siswa</span><span>{{ $stu['avgPackages'] ?? '-' }}</span></div>
                        <div class="kv"><span>Belum lanjut setelah selesai paket</span><span class="{{ $stu['notContinuedCount'] > 0 ? 'down' : '' }}">{{ $stu['notContinuedCount'] }} siswa</span></div>

                        @if(array_sum($stu['tenureBuckets']) > 0)
                            <div class="chart-box sm mt-3"><canvas id="chTenure"></canvas></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Siswa Paling Lama Belajar</div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Siswa</th><th>Bergabung</th><th class="text-center">Lama</th><th class="text-center">Paket</th></tr></thead>
                            <tbody>
                                @forelse($stu['longest'] as $s)
                                    <tr>
                                        <td class="fw-semibold">{{ $s->name }}</td>
                                        <td>{{ $s->join_date ? \Carbon\Carbon::parse($s->join_date)->format('d M Y') : '-' }}</td>
                                        <td class="text-center">{{ $s->months }} bln</td>
                                        <td class="text-center">
                                            <span class="badge {{ $s->packages >= 2 ? 'bg-success' : 'bg-secondary' }}">{{ $s->packages }}{{ $s->packages >= 2 ? ' · lanjut' : '' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data siswa aktif.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span>Selesai Paket, Belum Lanjut (perlu follow-up)</span>
                        <span class="badge {{ $stu['notContinuedCount'] > 0 ? 'bg-warning text-dark' : 'bg-success' }}">{{ $stu['notContinuedCount'] }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Siswa</th><th>Status</th><th class="text-center">Paket</th><th>Selesai</th></tr></thead>
                            <tbody>
                                @forelse($stu['notContinued'] as $n)
                                    <tr>
                                        <td class="fw-semibold">{{ $n['name'] }}</td>
                                        <td><span class="badge bg-secondary">{{ $n['status'] }}</span></td>
                                        <td class="text-center">{{ $n['packages'] }}</td>
                                        <td>{{ $n['days'] }} hari lalu</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Semua siswa yang selesai paket sudah lanjut.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white mini-note">Diurutkan dari yang paling baru selesai (paling mudah dibujuk lanjut).</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================================
        KETEPATAN GURU
    =========================================================== --}}
    <div class="sec" id="guru">
        <div class="sec-title"><i class="fa-solid fa-chalkboard-user"></i> Ketepatan & Beban Guru</div>
        <div class="sec-sub">
            Tepat waktu = jurnal mengajar dibuat paling lambat {{ $tea['grace'] }} hari setelah tanggal sesi (90 hari terakhir).
            Progress report terlambat = dikirim setelah batas H+3.
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Tren Jurnal Tepat Waktu per Bulan (%)</div>
                    <div class="card-body">
                        @if(count(array_filter($tea['trend'], fn ($v) => $v !== null)) > 0)
                            <div class="chart-box sm"><canvas id="chTeaTrend"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada jurnal mengajar.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Ketepatan per Guru (%)</div>
                    <div class="card-body">
                        @if($teacherChart->count() > 0)
                            <div class="chart-box sm"><canvas id="chTeaBar"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada jurnal mengajar dalam 90 hari terakhir.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">Peringkat Guru</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Guru</th>
                            <th class="text-center">Kelas</th>
                            <th class="text-center">Jam / Minggu</th>
                            <th class="text-center">Jurnal (90 hr)</th>
                            <th style="min-width:170px">Tepat Waktu</th>
                            <th class="text-center">Rata² Telat</th>
                            <th class="text-center">Report Telat</th>
                            <th class="text-center">Cuti</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tea['rows'] as $t)
                            @php $bar = $t['pct'] === null ? 'bg-secondary' : ($t['pct'] >= 90 ? 'bg-success' : ($t['pct'] >= 70 ? 'bg-warning' : 'bg-danger')); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $t['name'] }}</td>
                                <td class="text-center">{{ $t['classes'] }}</td>
                                <td class="text-center">{{ $t['hours'] }}</td>
                                <td class="text-center">{{ $t['journals'] }}</td>
                                <td>
                                    @if($t['pct'] !== null)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1"><div class="progress-bar {{ $bar }}" style="width: {{ $t['pct'] }}%"></div></div>
                                            <span class="small fw-semibold" style="min-width:38px">{{ $t['pct'] }}%</span>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $t['avgDelay'] !== null ? $t['avgDelay'] . ' hr' : '-' }}</td>
                                <td class="text-center">
                                    <span class="{{ $t['reportLate'] > 0 ? 'down fw-bold' : '' }}">{{ $t['reportLate'] }}</span>
                                    <small class="text-muted">/ {{ $t['reports'] }}</small>
                                </td>
                                <td class="text-center">{{ $t['leaves'] }}</td>
                                <td><span class="badge bg-{{ $t['badge'] }}">{{ $t['label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data guru.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white mini-note">
                Tepat Waktu: ≥ 90% jurnal tepat waktu dan tidak ada progress report terlambat · Perlu Perhatian: 70-89% · Sering Terlambat: di bawah 70%.
                Jam / Minggu dihitung dari jadwal kelas yang sudah diterima guru.
            </div>
        </div>
    </div>

    {{-- ==========================================================
        KELAS & OPERASIONAL
    =========================================================== --}}
    <div class="sec" id="kelas">
        <div class="sec-title"><i class="fa-solid fa-chalkboard"></i> Kelas & Operasional</div>

        <div class="row g-3 mb-3">
            <div class="col-md-2 col-6"><div class="tile"><div class="v up">{{ $cls['running'] }}</div><div class="l">Berjalan</div></div></div>
            <div class="col-md-2 col-6"><div class="tile"><div class="v">{{ $cls['open'] }}</div><div class="l">Open</div></div></div>
            <div class="col-md-2 col-6"><div class="tile"><div class="v">{{ $cls['completed'] }}</div><div class="l">Selesai</div></div></div>
            <div class="col-md-2 col-6"><div class="tile"><div class="v">{{ $pc($cls['completion'], 0) }}</div><div class="l">Tingkat Selesai</div></div></div>
            <div class="col-md-2 col-6"><div class="tile"><div class="v">{{ $pc($cls['attRate'], 1) }}</div><div class="l">Kehadiran Bulan Ini</div></div></div>
            <div class="col-md-2 col-6"><div class="tile"><div class="v {{ $cls['noTeacher'] > 0 ? 'down' : '' }}">{{ $cls['noTeacher'] }}</div><div class="l">Kelas Tanpa Guru</div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Isi Kelas per Jenis (Open & Running)</div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Jenis</th><th class="text-center">Kelas</th><th class="text-center">Siswa</th><th class="text-center">Kursi</th><th style="min-width:150px">Terisi</th></tr></thead>
                            <tbody>
                                @forelse($cls['families'] as $f)
                                    @php $barClass = $f['fill'] >= 80 ? 'bg-success' : ($f['fill'] >= 50 ? 'bg-warning' : 'bg-danger'); @endphp
                                    <tr>
                                        <td class="fw-semibold"><span class="dot" style="background: {{ $famColor[$f['name']] ?? '#8b8fa3' }}"></span>{{ $f['name'] }}</td>
                                        <td class="text-center">{{ $f['classes'] }}</td>
                                        <td class="text-center">{{ $f['students'] }}</td>
                                        <td class="text-center">{{ $f['capacity'] }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1"><div class="progress-bar {{ $barClass }}" style="width: {{ $f['fill'] }}%"></div></div>
                                                <span class="small fw-semibold" style="min-width:36px">{{ $f['fill'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada kelas Open / Running.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Kehadiran Siswa per Bulan (%)</div>
                    <div class="card-body">
                        @if(count(array_filter($cls['attSeries'], fn ($v) => $v !== null)) > 0)
                            <div class="chart-box sm"><canvas id="chAttendance"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data absensi.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span>Kelas Keterisian Rendah (&lt; 50%)</span>
                        <span class="badge {{ $cls['lowFillCount'] > 0 ? 'bg-warning text-dark' : 'bg-success' }}">{{ $cls['lowFillCount'] }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Kelas</th><th>Guru</th><th class="text-center">Terisi</th></tr></thead>
                            <tbody>
                                @forelse($cls['lowFill'] as $c)
                                    <tr>
                                        <td class="fw-semibold">{{ $c['name'] }}</td>
                                        <td>{{ $c['teacher'] ?? '-' }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $c['filled'] }}/{{ $c['capacity'] }} · {{ $c['fill'] }}%</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada kelas dengan keterisian rendah.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Siswa Berisiko Berhenti (kehadiran &lt; 75%)</div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Siswa</th><th class="text-center">Hadir</th><th class="text-center">Persen</th></tr></thead>
                            <tbody>
                                @forelse($cls['atRisk'] as $s)
                                    @php $p = (int) round(((int) $s->present / (int) $s->total) * 100); @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $s->name }}</td>
                                        <td class="text-center">{{ $s->present }}/{{ $s->total }}</td>
                                        <td class="text-center"><span class="badge {{ $p < 50 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $p }}%</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada siswa berisiko (90 hari terakhir, minimal 3 sesi).</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================================
        MARKETING
    =========================================================== --}}
    <div class="sec" id="marketing">
        <div class="sec-title"><i class="fa-solid fa-user-plus"></i> Marketing (Calon Siswa)</div>

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-white">Calon Siswa Masuk vs Menjadi Siswa (12 bulan)</div>
                    <div class="card-body">
                        @if(array_sum($mkt['leadSeries']) + array_sum($mkt['convSeries']) > 0)
                            <div class="chart-box"><canvas id="chLeads"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data calon siswa.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header bg-white">Corong Konversi</div>
                    <div class="card-body">
                        @php
                            $funnel = [
                                ['Calon siswa masuk', $mkt['total'], '#3b82f6'],
                                ['Trial dijadwalkan', $mkt['trialPlanned'], '#8b5cf6'],
                                ['Trial selesai', $mkt['trialDone'], '#e8a317'],
                                ['Menjadi siswa', $mkt['converted'], '#22a05a'],
                            ];
                        @endphp
                        @foreach($funnel as [$fl, $fv, $fc])
                            @php $fp = $mkt['total'] > 0 ? min(100, round(($fv / $mkt['total']) * 100)) : 0; @endphp
                            <div class="funnel-row">
                                <div class="fl"><span>{{ $fl }}</span><span class="fw-semibold">{{ $fv }} · {{ $fp }}%</span></div>
                                <div class="progress"><div class="progress-bar" style="width: {{ $fp }}%; background: {{ $fc }}"></div></div>
                            </div>
                        @endforeach

                        <div class="row g-2 mt-2">
                            <div class="col-4"><div class="tile"><div class="v" style="color:#3b82f6">{{ $mkt['leadStatus']['Cold'] ?? 0 }}</div><div class="l">Cold</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v" style="color:#e8a317">{{ $mkt['leadStatus']['Warm'] ?? 0 }}</div><div class="l">Warm</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v down">{{ $mkt['leadStatus']['Hot'] ?? 0 }}</div><div class="l">Hot</div></div></div>
                        </div>

                        <div class="kv mt-3"><span>Rata-rata hari hingga jadi siswa</span><span>{{ $mkt['avgDays'] !== null ? $mkt['avgDays'] . ' hari' : '-' }}</span></div>
                        <div class="kv"><span>Follow-up terlambat</span><span class="{{ $mkt['followOverdue'] > 0 ? 'down' : '' }}">{{ $mkt['followOverdue'] }}</span></div>
                        <div class="kv"><span>Biaya pendaftaran belum dibayar</span><span>{{ $mkt['feePending'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">Konversi per Sumber Calon Siswa</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Sumber</th><th class="text-center">Calon Siswa</th><th class="text-center">Jadi Siswa</th><th style="min-width:180px">Konversi</th></tr></thead>
                    <tbody>
                        @forelse($mkt['bySource'] as $s)
                            @php $cp = (int) $s->leads > 0 ? (int) round(((int) $s->converted / (int) $s->leads) * 100) : 0; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $s->source }}</td>
                                <td class="text-center">{{ $s->leads }}</td>
                                <td class="text-center">{{ $s->converted }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1"><div class="progress-bar {{ $cp >= 50 ? 'bg-success' : ($cp >= 25 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $cp }}%"></div></div>
                                        <span class="small fw-semibold" style="min-width:36px">{{ $cp }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data sumber calon siswa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white mini-note">Gunakan untuk menentukan sumber promosi yang paling efektif.</div>
        </div>
    </div>

    {{-- ==========================================================
        MUTU AKADEMIK
    =========================================================== --}}
    <div class="sec" id="akademik">
        <div class="sec-title"><i class="fa-solid fa-award"></i> Mutu Akademik & Antrian Review</div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-white">Progress Report & Sertifikat Disetujui (12 bulan)</div>
                    <div class="card-body">
                        @if(array_sum($aca['reportSeries']) + array_sum($aca['certSeries']) > 0)
                            <div class="chart-box"><canvas id="chAcademic"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada progress report atau sertifikat yang disetujui.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Antrian Review Kurikulum</div>
                    <div class="card-body">
                        <div class="kv"><span>Progress report menunggu ACC</span><span class="{{ $aca['reportsWaiting'] > 0 ? 'down' : '' }}">{{ $aca['reportsWaiting'] }}{{ $aca['reportsOldestAge'] !== null ? ' · tertua ' . $aca['reportsOldestAge'] . ' hr' : '' }}</span></div>
                        <div class="kv"><span>Jurnal menunggu review</span><span>{{ $aca['journalsPending'] }}{{ $aca['journalsOldestAge'] !== null ? ' · tertua ' . $aca['journalsOldestAge'] . ' hr' : '' }}</span></div>
                        <div class="kv"><span>LP / PPT menunggu review</span><span>{{ $aca['materialsPending'] }}</span></div>
                        <div class="kv"><span>Cuti / ganti kelas menunggu</span><span>{{ $aca['leavesPending'] }}</span></div>
                        <div class="kv"><span>Sertifikat terbit</span><span class="up">{{ $aca['certApproved'] }}</span></div>
                        <div class="kv"><span>Sertifikat menunggu ACC</span><span>{{ $aca['certPending'] }}</span></div>
                        <div class="kv"><span>Rata-rata nilai sertifikat</span><span>{{ $aca['certScore'] ?? '-' }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================================
        SEMUA FITUR PER ROLE
    =========================================================== --}}
    <div class="sec" id="fitur">
        <div class="sec-title"><i class="fa-solid fa-grip"></i> Semua Fitur per Role</div>
        <div class="sec-sub">Akses penuh ke fitur Admin, Kurikulum, dan Guru.</div>

        <div class="row g-3 mb-4">
            @foreach($roleGroups as $g)
                <div class="col-xl-4 col-lg-6">
                    <div class="card role-card h-100" style="border-top: 4px solid {{ $g['color'] }}">
                        <div class="role-head">
                            <span class="role-chip" style="background: {{ $g['color'] }}">{{ $g['label'] }}</span>
                            <span class="role-count">{{ count($g['items']) }} fitur</span>
                        </div>
                        <div class="role-desc">{{ $g['desc'] }}</div>

                        <div class="list-group list-group-flush">
                            @foreach($g['items'] as $it)
                                <a href="{{ url($it['url']) }}" class="feat">
                                    <span class="feat-ico" style="color: {{ $g['color'] }}"><i class="fa-solid {{ $it['icon'] }}"></i></span>
                                    <span class="feat-txt">
                                        <span class="feat-name">{{ $it['label'] }}</span>
                                        <span class="feat-desc">{{ $it['desc'] }}</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-right feat-go"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ----- pindah tampilan pendapatan: 12 bulan / 3 tahun -----
    document.querySelectorAll('#revToggle button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#revToggle button').forEach(x => x.classList.toggle('active', x === b));
            ['boxMonthly', 'boxYearly'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('d-none', id !== b.dataset.target);
            });
        });
    });

    if (typeof Chart === 'undefined') return;

    const css   = getComputedStyle(document.documentElement);
    const muted = (css.getPropertyValue('--muted') || '#8b8fa3').trim();
    const grid  = (css.getPropertyValue('--line') || '#ececf2').trim();

    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = muted;

    const C = { yellow: '#FFD400', blue: '#3b82f6', purple: '#8b5cf6', red: '#ef4444', green: '#22a05a',
                teal: '#0ea5b7', pink: '#ec4899', olive: '#7c8a1a', orange: '#e8a317', gray: '#9aa0b5' };
    const palette = [C.yellow, C.blue, C.purple, C.red, C.green, C.teal, C.pink, C.olive];

    const rupiah  = v => 'Rp ' + Number(v).toLocaleString('id-ID');
    const singkat = v => v >= 1e9 ? (v / 1e9) + ' M' : v >= 1e6 ? (v / 1e6) + ' jt' : v >= 1e3 ? (v / 1e3) + ' rb' : v;
    const mk = (id, cfg) => { const el = document.getElementById(id); return el ? new Chart(el, cfg) : null; };

    const months  = @json($monthLabels);
    const baseOpt = { responsive: true, maintainAspectRatio: false };
    const yGrid   = { grid: { color: grid } };

    // ----- Pendapatan 12 bulan + pembanding tahun lalu -----
    mk('chRevenue', {
        data: {
            labels: months,
            datasets: [
                { type: 'bar',  label: 'Tahun ini',  data: @json($fin['series']), backgroundColor: C.yellow, borderRadius: 6, order: 2 },
                { type: 'line', label: 'Tahun lalu', data: @json($fin['prevSeries']), borderColor: C.gray, borderDash: [5, 4], pointRadius: 3, tension: .3, order: 1 }
            ]
        },
        options: { ...baseOpt,
            plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + rupiah(c.raw) } } },
            scales: { y: { beginAtZero: true, ...yGrid, ticks: { callback: singkat } }, x: { grid: { display: false } } } }
    });

    // ----- Pendapatan 3 tahun -----
    mk('chYears', {
        type: 'bar',
        data: {
            labels: @json(array_map(fn ($y) => (string) $y['year'], $fin['years'])),
            datasets: [{ label: 'Pendapatan', data: @json(array_column($fin['years'], 'total')), backgroundColor: [C.gray, C.blue, C.yellow], borderRadius: 8 }]
        },
        options: { ...baseOpt,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rupiah(c.raw) } } },
            scales: { y: { beginAtZero: true, ...yGrid, ticks: { callback: singkat } }, x: { grid: { display: false } } } }
    });

    // ----- Donat program & metode -----
    const donut = (id, labels, data) => mk(id, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: data, backgroundColor: palette, borderWidth: 0 }] },
        options: { ...baseOpt, cutout: '62%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } },
                       tooltip: { callbacks: { label: c => c.label + ': ' + rupiah(c.raw) } } } }
    });
    donut('chProgram', @json($fin['byProgram']->pluck('label')->values()), @json($fin['byProgram']->pluck('total')->map(fn ($v) => (float) $v)->values()));
    donut('chMethod',  @json($fin['byMethod']->pluck('label')->values()),  @json($fin['byMethod']->pluck('total')->map(fn ($v) => (float) $v)->values()));

    // ----- Siswa baru vs lanjut -----
    mk('chNewRenew', {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                { label: 'Siswa baru',  data: @json($stu['newSeries']),   backgroundColor: C.blue,  borderRadius: 5 },
                { label: 'Siswa lanjut', data: @json($stu['renewSeries']), backgroundColor: C.green, borderRadius: 5 }
            ]
        },
        options: { ...baseOpt, plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, ...yGrid }, x: { grid: { display: false } } } }
    });

    // ----- Sebaran lama belajar -----
    mk('chTenure', {
        type: 'bar',
        data: { labels: @json(array_keys($stu['tenureBuckets'])), datasets: [{ label: 'Siswa aktif', data: @json(array_values($stu['tenureBuckets'])), backgroundColor: C.purple, borderRadius: 5 }] },
        options: { ...baseOpt, plugins: { legend: { display: false }, title: { display: true, text: 'Sebaran lama belajar siswa aktif' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, ...yGrid }, x: { grid: { display: false } } } }
    });

    // ----- Ketepatan guru -----
    mk('chTeaTrend', {
        type: 'line',
        data: { labels: months, datasets: [{ label: 'Tepat waktu (%)', data: @json($tea['trend']), borderColor: C.green, backgroundColor: 'rgba(34,160,90,.15)', fill: true, tension: .3, pointRadius: 4, spanGaps: true }] },
        options: { ...baseOpt, plugins: { legend: { display: false } },
            scales: { y: { min: 0, max: 100, ...yGrid, ticks: { callback: v => v + '%' } }, x: { grid: { display: false } } } }
    });

    const teaNames = @json($teacherChart->pluck('name')->values());
    const teaPct   = @json($teacherChart->pluck('pct')->values());
    mk('chTeaBar', {
        type: 'bar',
        data: { labels: teaNames, datasets: [{ label: 'Tepat waktu (%)', data: teaPct,
            backgroundColor: teaPct.map(v => v >= 90 ? C.green : (v >= 70 ? C.orange : C.red)), borderRadius: 5 }] },
        options: { ...baseOpt, indexAxis: 'y', plugins: { legend: { display: false } },
            scales: { x: { min: 0, max: 100, ...yGrid, ticks: { callback: v => v + '%' } }, y: { grid: { display: false } } } }
    });

    // ----- Kehadiran siswa -----
    mk('chAttendance', {
        type: 'line',
        data: { labels: months, datasets: [{ label: 'Kehadiran (%)', data: @json($cls['attSeries']), borderColor: C.blue, backgroundColor: 'rgba(59,130,246,.15)', fill: true, tension: .3, pointRadius: 4, spanGaps: true }] },
        options: { ...baseOpt, plugins: { legend: { display: false } },
            scales: { y: { min: 0, max: 100, ...yGrid, ticks: { callback: v => v + '%' } }, x: { grid: { display: false } } } }
    });

    // ----- Marketing -----
    mk('chLeads', {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                { label: 'Calon siswa masuk', data: @json($mkt['leadSeries']), backgroundColor: C.blue, borderRadius: 5 },
                { label: 'Menjadi siswa',     data: @json($mkt['convSeries']), backgroundColor: C.green, borderRadius: 5 }
            ]
        },
        options: { ...baseOpt, plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, ...yGrid }, x: { grid: { display: false } } } }
    });

    // ----- Mutu akademik -----
    mk('chAcademic', {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                { label: 'Progress report di-ACC', data: @json($aca['reportSeries']), backgroundColor: C.purple, borderRadius: 5 },
                { label: 'Sertifikat terbit',      data: @json($aca['certSeries']),   backgroundColor: C.yellow, borderRadius: 5 }
            ]
        },
        options: { ...baseOpt, plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, ...yGrid }, x: { grid: { display: false } } } }
    });
})();
</script>
@endpush