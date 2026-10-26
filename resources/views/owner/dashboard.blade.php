@extends('layouts.owner')

@section('title', 'Dashboard Owner')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    // warna per jenis kelas (sama dengan jadwal Guru/Kurikulum)
    $famColor = [
        'Maochong' => '#e8a317', 'Jianer' => '#3b82f6', 'Hudie' => '#8b5cf6', 'Feixiang' => '#ef4444',
        'HSK' => '#22a05a', 'Private' => '#0ea5b7', 'Lainnya' => '#8b8fa3',
    ];
@endphp

@push('styles')
<style>
    .sec { scroll-margin-top: 90px; }
    .sec-title { font-size: 17px; font-weight: 700; margin: 8px 0 14px; }

    .kpi { height: 100%; margin-bottom: 0 !important; }
    .kpi-label { font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
    .kpi-value { font-size: 24px; font-weight: 700; line-height: 1.25; margin: 6px 0 4px; color: var(--text); }
    .kpi-sub { font-size: 12.5px; color: var(--muted); }
    .kpi-ico {
        width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        background: var(--accent-soft); color: #a9822c; font-size: 17px; flex-shrink: 0;
    }

    .chart-box { position: relative; height: 280px; }
    .chart-empty { height: 280px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 13px; }

    .tile { border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; text-align: center; height: 100%; }
    .tile .v { font-size: 22px; font-weight: 700; line-height: 1.2; }
    .tile .l { font-size: 12px; color: var(--muted); }

    /* ===== kartu fitur per role ===== */
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

    .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px; }
    .up   { color: #1a9b50; }
    .down { color: #d93636; }
</style>
@endpush

@section('content')

<div class="container-fluid">

    {{-- ===== HEADER ===== --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Dashboard Owner</h2>
            <p class="text-muted mb-0">
                Ringkasan keuangan, siswa, kelas, dan kinerja Haoyou Educator · {{ $monthLabel }}
            </p>
        </div>

        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Cetak Ringkasan
        </button>
    </div>

    {{-- ===== SEMUA FITUR PER ROLE ===== --}}
    @php $roleGroups = collect(\App\Support\OwnerMenu::groups())->reject(fn ($g) => $g['key'] === 'owner'); @endphp

    <div class="sec-title"><i class="fa-solid fa-grip me-2"></i>Semua Fitur per Role</div>

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

    {{-- ===== KPI UTAMA ===== --}}
    <div class="row g-3 mb-3">

        {{-- Pendapatan bulan ini --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Pendapatan Bulan Ini</div>
                    <div class="kpi-value">{{ $rp($revenueNow) }}</div>
                    <div class="kpi-sub">
                        @if($revenueDelta === null)
                            Belum ada pembanding bulan lalu
                        @elseif($revenueDelta >= 0)
                            <span class="up"><i class="fa-solid fa-arrow-trend-up"></i> {{ $revenueDelta }}%</span> dari bulan lalu ({{ $rp($revenuePrev) }})
                        @else
                            <span class="down"><i class="fa-solid fa-arrow-trend-down"></i> {{ abs($revenueDelta) }}%</span> dari bulan lalu ({{ $rp($revenuePrev) }})
                        @endif
                    </div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-wallet"></i></div>
            </div></div>
        </div>

        {{-- Piutang --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Belum Lunas (Piutang)</div>
                    <div class="kpi-value {{ $outstanding > 0 ? 'down' : '' }}">{{ $rp($outstanding) }}</div>
                    <div class="kpi-sub">{{ $unpaidCount }} pendaftaran belum lunas</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            </div></div>
        </div>

        {{-- Siswa aktif --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Siswa Aktif</div>
                    <div class="kpi-value">{{ number_format($activeStudents, 0, ',', '.') }}</div>
                    <div class="kpi-sub"><span class="up">+{{ $newStudentsNow }}</span> siswa baru bulan ini</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-user-graduate"></i></div>
            </div></div>
        </div>

        {{-- Conversion rate --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Conversion Rate</div>
                    <div class="kpi-value">{{ $conversionRate !== null ? $conversionRate . '%' : '-' }}</div>
                    <div class="kpi-sub">{{ $converted }} dari {{ $leadTotal }} calon siswa menjadi siswa</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-bullseye"></i></div>
            </div></div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        {{-- Kelas berjalan --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Kelas Berjalan</div>
                    <div class="kpi-value">{{ $runningClasses }}</div>
                    <div class="kpi-sub">{{ $openClasses }} kelas Open · {{ $completedClasses }} selesai</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-chalkboard"></i></div>
            </div></div>
        </div>

        {{-- Guru aktif --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Guru Aktif</div>
                    <div class="kpi-value">{{ $activeTeachers }}</div>
                    <div class="kpi-sub">{{ $journalsPending }} jurnal menunggu review</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div></div>
        </div>

        {{-- Kehadiran --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Kehadiran Siswa</div>
                    <div class="kpi-value">{{ $attendanceRate !== null ? $attendanceRate . '%' : '-' }}</div>
                    <div class="kpi-sub">Rata-rata kehadiran {{ $monthLabel }}</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-user-check"></i></div>
            </div></div>
        </div>

        {{-- Progress report --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi"><div class="card-body d-flex justify-content-between gap-2">
                <div>
                    <div class="kpi-label">Progress Report</div>
                    <div class="kpi-value">{{ $reportsApprovedMonth }}</div>
                    <div class="kpi-sub">di-ACC bulan ini · {{ $reportsWaiting }} menunggu ACC</div>
                </div>
                <div class="kpi-ico"><i class="fa-solid fa-chart-column"></i></div>
            </div></div>
        </div>

    </div>

    {{-- ==========================================================
        KEUANGAN
    =========================================================== --}}
    <div class="sec" id="keuangan">
        <div class="sec-title"><i class="fa-solid fa-wallet me-2"></i>Keuangan</div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-white">Pendapatan 6 Bulan Terakhir</div>
                    <div class="card-body">
                        @if(array_sum($revenueSeries) > 0)
                            <div class="chart-box"><canvas id="chRevenue"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data pembayaran.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Pendapatan per Program</div>
                    <div class="card-body">
                        @if(array_sum($programSeries) > 0)
                            <div class="chart-box"><canvas id="chProgram"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Pembayaran Belum Lunas</span>
                <span class="badge {{ $unpaidCount > 0 ? 'bg-danger' : 'bg-success' }}">{{ $unpaidCount }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Tahap</th>
                            <th class="text-end">Tagihan</th>
                            <th class="text-end">Dibayar</th>
                            <th class="text-end">Sisa</th>
                            <th>Pembayaran Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unpaidList as $u)
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
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Semua pembayaran sudah lunas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($unpaidCount > $unpaidList->count())
                <div class="card-footer bg-white small text-muted">
                    Menampilkan {{ $unpaidList->count() }} sisa tagihan terbesar dari {{ $unpaidCount }} pendaftaran.
                </div>
            @endif
        </div>
    </div>

    {{-- ==========================================================
        SISWA & KELAS
    =========================================================== --}}
    <div class="sec" id="siswa">
        <div class="sec-title"><i class="fa-solid fa-graduation-cap me-2"></i>Siswa & Kelas</div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-white">Siswa Baru 6 Bulan Terakhir</div>
                    <div class="card-body">
                        @if(array_sum($studentSeries) > 0)
                            <div class="chart-box"><canvas id="chStudents"></canvas></div>
                        @else
                            <div class="chart-empty">Belum ada data siswa baru.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">Status Siswa</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="tile"><div class="v up">{{ $activeStudents }}</div><div class="l">Aktif</div></div>
                            </div>
                            <div class="col-6">
                                <div class="tile"><div class="v">{{ $inactiveStudents }}</div><div class="l">Nonaktif</div></div>
                            </div>
                            <div class="col-6">
                                <div class="tile"><div class="v">{{ $graduatedStudents }}</div><div class="l">Lulus</div></div>
                            </div>
                        </div>
                        <div class="small text-muted mt-3">
                            Siswa baru: <strong>{{ $newStudentsNow }}</strong> bulan ini · <strong>{{ $newStudentsPrev }}</strong> bulan lalu
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">Isi Kelas per Jenis (kelas Open & Running)</div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Jenis Kelas</th>
                            <th class="text-center">Kelas</th>
                            <th class="text-center">Siswa Aktif</th>
                            <th class="text-center">Kapasitas</th>
                            <th style="min-width:200px">Keterisian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($families as $f)
                            @php
                                $barClass = $f['fill'] >= 80 ? 'bg-success' : ($f['fill'] >= 50 ? 'bg-warning' : 'bg-danger');
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <span class="dot" style="background: {{ $famColor[$f['name']] ?? '#8b8fa3' }}"></span>{{ $f['name'] }}
                                </td>
                                <td class="text-center">{{ $f['classes'] }}</td>
                                <td class="text-center">{{ $f['students'] }}</td>
                                <td class="text-center">{{ $f['capacity'] }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar {{ $barClass }}" style="width: {{ $f['fill'] }}%"></div>
                                        </div>
                                        <span class="small fw-semibold" style="min-width:38px">{{ $f['fill'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada kelas Open / Running.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==========================================================
        GURU
    =========================================================== --}}
    <div class="sec" id="guru">
        <div class="sec-title"><i class="fa-solid fa-chalkboard-user me-2"></i>Kinerja Guru ({{ $monthLabel }})</div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Guru</th>
                            <th class="text-center">Sesi Mengajar</th>
                            <th class="text-center">Jurnal Direview</th>
                            <th style="min-width:200px">Kelengkapan Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teacherStats as $t)
                            @php
                                $sessions = (int) $t->sessions;
                                $reviewed = (int) $t->reviewed;
                                $pct = $sessions > 0 ? (int) round(($reviewed / $sessions) * 100) : 0;
                                $barClass = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $t->name }}</td>
                                <td class="text-center">{{ $sessions }}</td>
                                <td class="text-center">{{ $reviewed }}</td>
                                <td>
                                    @if($sessions > 0)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar {{ $barClass }}" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="small fw-semibold" style="min-width:38px">{{ $pct }}%</span>
                                        </div>
                                    @else
                                        <span class="text-muted small">Belum ada sesi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada data guru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white small text-muted">
                Menampilkan 8 guru dengan sesi mengajar terbanyak bulan ini.
            </div>
        </div>
    </div>

    {{-- ==========================================================
        PROSPEK (CRM)
    =========================================================== --}}
    <div class="sec" id="crm">
        <div class="sec-title"><i class="fa-solid fa-user-plus me-2"></i>Prospek Calon Siswa</div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Status Lead</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-4"><div class="tile"><div class="v" style="color:#3b82f6">{{ $leadStatus['Cold'] ?? 0 }}</div><div class="l">Cold</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v" style="color:#e8a317">{{ $leadStatus['Warm'] ?? 0 }}</div><div class="l">Warm</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v down">{{ $leadStatus['Hot'] ?? 0 }}</div><div class="l">Hot</div></div></div>
                        </div>
                        <div class="small text-muted mt-3">
                            Total {{ $leadTotal }} calon siswa · <strong>{{ $leadsThisMonth }}</strong> baru bulan ini
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">Status Trial</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-4"><div class="tile"><div class="v">{{ $trialStatus['Pending'] ?? 0 }}</div><div class="l">Terjadwal</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v up">{{ $trialStatus['Completed'] ?? 0 }}</div><div class="l">Selesai</div></div></div>
                            <div class="col-4"><div class="tile"><div class="v down">{{ $trialStatus['Cancelled'] ?? 0 }}</div><div class="l">Batal</div></div></div>
                        </div>
                        <div class="small text-muted mt-3">
                            Conversion rate lead menjadi siswa:
                            <strong>{{ $conversionRate !== null ? $conversionRate . '%' : '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================================
        AKADEMIK
    =========================================================== --}}
    <div class="sec" id="akademik">
        <div class="sec-title"><i class="fa-solid fa-award me-2"></i>Mutu Akademik</div>

        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="tile"><div class="v up">{{ $reportsApprovedMonth }}</div><div class="l">Progress report di-ACC bulan ini</div></div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="tile"><div class="v" style="color:#e8a317">{{ $reportsWaiting }}</div><div class="l">Progress report menunggu ACC</div></div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="tile"><div class="v up">{{ $certificatesApproved }}</div><div class="l">Sertifikat terbit</div></div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="tile"><div class="v" style="color:#e8a317">{{ $certificatesPending }}</div><div class="l">Sertifikat menunggu ACC</div></div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    const css  = getComputedStyle(document.documentElement);
    const muted = (css.getPropertyValue('--muted') || '#8b8fa3').trim();
    const grid  = (css.getPropertyValue('--line') || '#ececf2').trim();

    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = muted;

    const rupiah = v => 'Rp ' + Number(v).toLocaleString('id-ID');
    const singkat = v => v >= 1e9 ? (v / 1e9) + ' M' : v >= 1e6 ? (v / 1e6) + ' jt' : v >= 1e3 ? (v / 1e3) + ' rb' : v;

    // ----- Pendapatan 6 bulan -----
    const elRev = document.getElementById('chRevenue');
    if (elRev) {
        new Chart(elRev, {
            type: 'bar',
            data: {
                labels: @json($revenueLabels),
                datasets: [{ label: 'Pendapatan', data: @json($revenueSeries), backgroundColor: '#FFD400', borderRadius: 6 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rupiah(c.raw) } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: singkat } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ----- Pendapatan per program -----
    const elProg = document.getElementById('chProgram');
    if (elProg) {
        new Chart(elProg, {
            type: 'doughnut',
            data: {
                labels: @json($programLabels),
                datasets: [{
                    data: @json($programSeries),
                    backgroundColor: ['#FFD400', '#3b82f6', '#8b5cf6', '#ef4444', '#22a05a', '#0ea5b7', '#ec4899', '#7c8a1a'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } },
                    tooltip: { callbacks: { label: c => c.label + ': ' + rupiah(c.raw) } }
                }
            }
        });
    }

    // ----- Siswa baru -----
    const elStu = document.getElementById('chStudents');
    if (elStu) {
        new Chart(elStu, {
            type: 'line',
            data: {
                labels: @json($revenueLabels),
                datasets: [{
                    label: 'Siswa baru', data: @json($studentSeries),
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.15)',
                    fill: true, tension: .3, pointRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
})();
</script>
@endpush