@extends('layouts.guru')

@section('content')
<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Teaching Log</h2>
        <p class="text-muted">Riwayat seluruh aktivitas mengajar guru.</p>
    </div>

    {{-- STATISTIK --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4"><div class="card-body">
                <div class="text-muted small">Total Jam</div>
                <div class="fs-3 fw-bold">{{ rtrim(rtrim(number_format($stats['hours'], 1), '0'), '.') }} Jam</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4"><div class="card-body">
                <div class="text-muted small">Total Sesi</div>
                <div class="fs-3 fw-bold">{{ $stats['sessions'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4"><div class="card-body">
                <div class="text-muted small">Private</div>
                <div class="fs-3 fw-bold text-warning">{{ $stats['private'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4"><div class="card-body">
                <div class="text-muted small">Ganti Guru</div>
                <div class="fs-3 fw-bold text-primary">{{ $stats['substitute'] }}</div>
            </div></div>
        </div>
    </div>

    {{-- RIWAYAT --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="fw-bold mb-0">Riwayat Mengajar</h5>

                <form method="GET" class="d-flex gap-2">
                    <input type="month" name="month" value="{{ $month }}"
                           class="form-control form-control-sm" onchange="this.form.submit()">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all"        @selected($type === 'all')>Semua Tipe</option>
                        <option value="Regular"    @selected($type === 'Regular')>Regular</option>
                        <option value="Private"    @selected($type === 'Private')>Private</option>
                        <option value="substitute" @selected($type === 'substitute')>Ganti Guru</option>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Kelas</th>
                            <th>Topik</th>
                            <th>Durasi</th>
                            <th>Tipe</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $loop->index }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($log->log_date)->translatedFormat('d M Y') }}</td>
                            <td>{{ $log->class_name }}</td>
                            <td>{{ $log->material_title ?? \Illuminate\Support\Str::limit($log->learning_activities, 50) }}</td>
                            <td>{{ (int) $log->minutes }} mnt</td>
                            <td>
                                @if($log->is_substitute)
                                    <span class="badge bg-primary">Ganti Guru</span>
                                @elseif($log->course_type === 'Private')
                                    <span class="badge bg-warning text-dark">Private</span>
                                @else
                                    <span class="badge bg-secondary">{{ $log->course_type ?? 'Regular' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($log->class_status === 'Conducted')
                                    <span class="badge bg-success">Terlaksana</span>
                                @elseif($log->class_status === 'Cancelled')
                                    <span class="badge bg-danger">Dibatalkan</span>
                                @else
                                    <span class="badge bg-info text-dark">Dijadwal Ulang</span>
                                @endif

                                @if($log->review_status === 'Reviewed')
                                    <div class="small text-success">✓ Direview</div>
                                @elseif($log->review_status === 'Revision')
                                    <div class="small text-danger">Perlu revisi</div>
                                @else
                                    <div class="small text-muted">Menunggu review</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat mengajar.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

</div>
@endsection