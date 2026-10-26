@extends('layouts.guru')

@section('title', 'Dashboard Guru')

@push('styles')
<style>
    .dash-card { background: var(--bs-body-bg, #fff); border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .stat-label { font-size: 11px; letter-spacing: .5px; text-transform: uppercase; color: #8a8a8a; font-weight: 700; }
    .stat-value { font-size: 34px; font-weight: 800; line-height: 1.1; }
    .stat-sub { font-size: 12px; color: #1e9e5a; }
    .prog-row { padding: 14px 0; border-bottom: 1px solid rgba(128,128,128,.18); }
    .prog-row:last-child { border-bottom: 0; }
    .prog-track { height: 5px; background: rgba(128,128,128,.2); border-radius: 4px; overflow: hidden; }
    .prog-fill { height: 100%; border-radius: 4px; }
    .pill { font-size: 10px; font-weight: 700; padding: 2px 9px; border-radius: 99px; border: 1px solid; text-transform: uppercase; }
    .pill.Ongoing { color: #1e9e5a; border-color: #1e9e5a; }
    .pill.Completed { color: #777; border-color: #999; }
    .pill.Scheduled { color: #3B82F6; border-color: #3B82F6; }
    .today-item { border: 1px solid rgba(128,128,128,.22); border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
    .today-item.highlight { background: #FFF4D8; border-color: #F2D48A; color: #222; }
    .today-item.completed { opacity: .55; }
    .today-time { font-size: 11px; color: #9a9a9a; }
    .today-item.highlight .today-time { color: #D48A00; }
    .avatar { width: 34px; height: 34px; border-radius: 50%; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .act-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid rgba(128,128,128,.18); }
    .act-row:last-child { border-bottom: 0; }
    .tone-ok { color: #1e9e5a; } .tone-bad { color: #dc3545; } .tone-warn { color: #d99100; }
    .bg-ok { background: #1e9e5a; } .bg-bad { background: #dc3545; } .bg-warn { background: #d99100; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">{{ $greeting }}, {{ $teacher->name }}!</h2>
        <div class="text-muted small">Haoyou Educator · Les Mandarin Malang · ringkasan operasional hari ini</div>
    </div>

    {{-- STATISTIK --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6">
            <div class="dash-card">
                <div class="stat-label">Total Active Students</div>
                <div class="stat-value">{{ $stats['students'] }}</div>
                <div class="stat-sub">↑ +{{ $stats['new'] }} siswa baru bulan ini</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="dash-card">
                <div class="stat-label">Active Classes</div>
                <div class="stat-value">{{ $stats['classes'] }}</div>
                <div class="stat-sub">{{ $stats['online'] }} Online · {{ $stats['offline'] }} Offline</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="dash-card">
                <div class="stat-label">Sesi Bulan Ini</div>
                <div class="stat-value">{{ $stats['sessions'] }}</div>
                <div class="stat-sub">Sesi terlaksana</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="dash-card">
                <div class="stat-label">Cuti Menunggu</div>
                <div class="stat-value">{{ $stats['leaves'] }}</div>
                <div class="stat-sub" style="color:#888">Menunggu Kurikulum</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">

        {{-- ACTIVE CLASS PROGRESS --}}
        <div class="col-lg-8">
            <div class="dash-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h5 class="fw-bold mb-0">Active Class Progress</h5>
                        <div class="text-muted small">Status penyelesaian sesi untuk semua kelas aktif</div>
                    </div>
                    <a href="{{ url('/guru/kelas') }}" class="small text-decoration-none">View All →</a>
                </div>

                @forelse($classes as $c)
                    <div class="prog-row">
                        <div class="fw-bold">{{ $c->class_name }}</div>
                        <div class="text-muted small mb-2">{{ $c->students }} siswa · {{ $c->delivery_mode }}</div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="prog-track flex-grow-1">
                                <div class="prog-fill" style="width: {{ $c->pct }}%; background: {{ $c->color }}"></div>
                            </div>
                            <div class="small text-muted">{{ $c->done }}/{{ $c->total_meetings ?? '–' }}</div>
                            <span class="pill {{ $c->state }}">{{ $c->state }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-muted py-4 text-center">Belum ada kelas aktif yang Anda ampu.</div>
                @endforelse
            </div>
        </div>

        {{-- CLASSES TODAY --}}
        <div class="col-lg-4">
            <div class="dash-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Classes Today</h5>
                        <div class="text-muted small">{{ $todayClasses->count() }} kelas terjadwal</div>
                    </div>
                    <a href="{{ url('/guru/schedule') }}" class="small text-decoration-none">Calendar →</a>
                </div>

                @forelse($todayClasses as $s)
                    <div class="today-item {{ $s->highlight ? 'highlight' : '' }} {{ $s->state === 'completed' ? 'completed' : '' }}">
                        <div class="today-time">
                            {{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }} · {{ $s->label }}
                        </div>
                        <div class="fw-bold">{{ $s->class_name }}</div>
                        <div class="small text-muted">{{ $s->students }} siswa · {{ $s->delivery_mode }}{{ $s->room ? ' · ' . $s->room : '' }}</div>
                        @if(in_array($s->state, ['ongoing', 'missed']))
                            <a href="{{ url('/guru/attendance') }}" class="btn btn-warning btn-sm mt-2 fw-bold">Isi Attendance</a>
                        @endif
                    </div>
                @empty
                    <div class="text-muted py-4 text-center">Tidak ada jadwal mengajar hari ini.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- RECENT ACTIVITY --}}
    <div class="dash-card">
        <h5 class="fw-bold mb-0">Recent Activity</h5>
        <div class="text-muted small mb-2">Aktivitas terbaru Anda</div>

        @forelse($activities as $a)
            <div class="act-row">
                <div class="avatar bg-{{ $a['tone'] }}">{{ strtoupper(mb_substr($a['name'], 0, 2)) }}</div>
                <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ $a['title'] }}</div>
                    <div class="text-muted" style="font-size:11px">
                        {{ $a['time']->locale('id')->diffForHumans() }} · {{ $a['sub'] }}
                    </div>
                </div>
                <div class="small fw-bold tone-{{ $a['tone'] }}">● {{ $a['badge'] }}</div>
            </div>
        @empty
            <div class="text-muted py-4 text-center">Belum ada aktivitas.</div>
        @endforelse
    </div>

</div>
@endsection