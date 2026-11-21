@extends('layouts.kurikulum')

@section('title', 'Monitoring Belajar Mandiri')

@section('content')
@php
    $badge   = ['belum' => ['Belum mulai', 'secondary'], 'belajar' => ['Sedang belajar', 'primary'], 'lulus' => ['Lulus', 'success']];
    $sortUrl = fn ($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
    $arrow   = fn ($col) => $sort === $col ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
@endphp

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Monitoring Belajar Mandiri</h2>
            <p class="text-muted mb-0">Progres Vocab dan Grammar semua siswa. Data diperbarui otomatis tiap 10 menit.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url('/kurikulum/belajar-mandiri/analisis') }}" class="btn btn-outline-secondary">
                <i class="fas fa-chart-bar me-1"></i> Analisis Soal
            </a>
            <a href="{{ request()->fullUrlWithQuery(['refresh' => 1]) }}" class="btn btn-outline-secondary">
                <i class="fas fa-sync-alt me-1"></i> Perbarui Data
            </a>
            <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-primary">
                <i class="fas fa-download me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="row mb-3">
        @foreach([['Total siswa', $stats['total'], ''], ['Sedang belajar', $stats['belajar'], 'text-primary'], ['Lulus (nilai ' . $passScore . '+)', $stats['lulus'], 'text-success'], ['Tidak aktif 7 hari', $stats['inactive'], 'text-danger']] as [$label, $val, $cls])
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100 mb-0">
                    <div class="card-body">
                        <small class="text-uppercase text-muted fw-bold">{{ $label }}</small>
                        <h2 class="fw-bold mt-2 mb-0 {{ $cls }}">{{ $val }}</h2>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-lg-4"><input type="text" name="q" class="form-control" placeholder="Cari nama atau username" value="{{ request('q') }}"></div>
                <div class="col-lg-3">
                    <select name="level" class="form-select">
                        <option value="">Semua level</option>
                        @foreach($levels as $lv)
                            <option value="{{ $lv }}" {{ request('level') === $lv ? 'selected' : '' }}>{{ $lv }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach($badge as $k => [$l])
                            <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Filter</button>
                    <a href="{{ url('/kurikulum/belajar-mandiri') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><a href="{{ $sortUrl('name') }}" class="text-dark text-decoration-none">Siswa{!! $arrow('name') !!}</a></th>
                        <th>Level</th>
                        <th><a href="{{ $sortUrl('vocab') }}" class="text-dark text-decoration-none">Vocab{!! $arrow('vocab') !!}</a></th>
                        <th><a href="{{ $sortUrl('grammar') }}" class="text-dark text-decoration-none">Grammar{!! $arrow('grammar') !!}</a></th>
                        <th><a href="{{ $sortUrl('score') }}" class="text-dark text-decoration-none">Nilai total{!! $arrow('score') !!}</a></th>
                        <th>Status</th>
                        <th>Sertifikat</th>
                        <th><a href="{{ $sortUrl('last') }}" class="text-dark text-decoration-none">Terakhir belajar{!! $arrow('last') !!}</a></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $x)
                    @php [$stLabel, $stClass] = $badge[$x['status']]; @endphp
                    <tr>
                        <td>
                            <a href="{{ url('/kurikulum/belajar-mandiri/siswa/' . $x['student_id']) }}" class="fw-bold text-dark text-decoration-none">{{ $x['name'] }}</a>
                            <div class="small text-muted">{{ $x['username'] }}</div>
                        </td>
                        <td>{{ $x['level'] ?? '-' }}</td>
                        <td>{{ $x['vocab_stars'] }} / {{ $x['vocab_max'] }}</td>
                        <td>{{ $x['grammar_stars'] }} / {{ $x['grammar_max'] }}</td>
                        <td>
                            @if($x['error'])
                                <span class="text-muted small">gagal dihitung</span>
                            @else
                                <strong>{{ $x['score'] !== null ? rtrim(rtrim(number_format($x['score'], 1, ',', '.'), '0'), ',') . '%' : '-' }}</strong>
                                <div class="small text-muted">{{ $x['predikat'] ?: '-' }}</div>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $stClass }}">{{ $stLabel }}</span></td>
                        <td>
                            @if($x['cert_count'])
                                {{ $x['cert_count'] }}
                                @if($x['cert_last'])<div class="small text-muted">{{ \Carbon\Carbon::parse($x['cert_last'])->format('d M Y') }}</div>@endif
                            @else - @endif
                        </td>
                        <td>
                            @if($x['last_at'])
                                {{ \Carbon\Carbon::parse($x['last_at'])->format('d M Y') }}
                                <div class="small text-muted">{{ \Carbon\Carbon::parse($x['last_at'])->diffForHumans() }}</div>
                            @else - @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Tidak ada siswa yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $rows->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection