@extends('admin.app')

@section('title', 'Kelas | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/kelas.css') }}">
    <style>
        a.icon-btn, a.btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Progres pertemuan */
        .meet-label { font-size: 13px; color: #6b7280; }
        .meet-label strong { color: #111827; font-size: 14px; }
        .meet-bar { height: 5px; width: 90px; background: #eee; border-radius: 999px; overflow: hidden; margin: 5px 0 4px; }
        .meet-bar span { display: block; height: 100%; background: #facc15; border-radius: 999px; }
    </style>
@endpush

@section('content')
    @php
        $statusBadge = [
            'Open'      => 'badge-pending',
            'Running'   => 'badge-completed',
            'Completed' => 'badge-cold',
            'Closed'    => 'badge-cancelled',
        ];

        $hasJadwalRoute = \Illuminate\Support\Facades\Route::has('admin.jadwal');
        $hasExportRoute = \Illuminate\Support\Facades\Route::has('admin.kelas.export');
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Kelas</h1>
            <p>
                Kelola kelas Reguler &amp; Private — guru pengampu, kapasitas,
                dan pola jadwal mingguan.
            </p>
        </div>

        <div class="dashboard-header-actions">
            <a href="{{ $hasExportRoute ? route('admin.kelas.export', request()->query()) : '#' }}" class="btn">
                <i class="fa-solid fa-file-export"></i>
                Export
            </a>

            <a href="{{ route('admin.kelas.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Tambah Kelas
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            <strong>Data belum bisa disimpan:</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Daily Activity</span>
                <i class="fa-solid fa-child stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasDailyActivity }}</div>
            <div class="stat-note">Kelas aktif</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">HSK</span>
                <i class="fa-solid fa-language stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasHsk }}</div>
            <div class="stat-note">Kelas aktif</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Private</span>
                <i class="fa-solid fa-user-tie stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasPrivate }}</div>
            <div class="stat-note">Kelas aktif</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Kelas Aktif</span>
                <i class="fa-solid fa-chalkboard stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasAktif }}</div>
            <div class="stat-note">Status Open &amp; Running</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.kelas') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas">
            </div>

            <select name="program_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Program</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->program_name }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="Reguler" {{ request('type') == 'Reguler' ? 'selected' : '' }}>Reguler</option>
                <option value="Private" {{ request('type') == 'Private' ? 'selected' : '' }}>Private</option>
            </select>

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach (['Open', 'Running', 'Completed', 'Closed'] as $st)
                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    {{-- <th>Program &amp; Paket</th> --}}
                    {{-- <th>Tipe</th> --}}
                    <th>Guru</th>
                    <th>Jadwal Mingguan</th>
                    <th>Pertemuan</th>
                    <th>Kapasitas</th>
                    <th>Status</th>
                    <th width="140" style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($classes as $kelas)
                    @php
                        $isPrivate = ! is_null($kelas->private_package_id);
                        $jadwalUrl = $hasJadwalRoute ? route('admin.jadwal', ['class_id' => $kelas->id]) : '#';
                    @endphp
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($kelas->class_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $kelas->class_name }}</div>
                                    <div class="cand-sub">
                                        @if ($kelas->level?->level_name)
                                            Level {{ $kelas->level->level_name }} &middot;
                                        @endif
                                        {{ $kelas->delivery_mode }}
                                    </div>
                                    @if ($kelas->delivery_mode === 'Online')
                                        <div class="cand-sub" style="color: {{ $kelas->zoom_link ? '#16a34a' : '#dc2626' }};">
                                            {{ $kelas->zoom_link ? 'Zoom ✓' : 'Belum ada link Zoom' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- <td>
                            @if ($isPrivate)
                                Private
                                <div class="cand-sub">{{ $kelas->privatePackage?->package_name ?? '-' }}</div>
                            @else
                                {{ $kelas->programPackage?->program?->program_name ?? '-' }}
                                <div class="cand-sub">{{ $kelas->programPackage?->package_name ?? '' }}</div>
                            @endif
                        </td> --}}

                        {{-- <td>
                            <span class="badge {{ $isPrivate ? 'badge-private' : 'badge-reguler' }}">
                                <span class="badge-dot"></span>
                                {{ $isPrivate ? 'Private' : 'Reguler' }}
                            </span>
                        </td> --}}

                        <td>
                            @if ($kelas->teacher)
                                {{ $kelas->teacher->name }}
                            @else
                                <span style="color:#dc2626;font-weight:600;">Belum ada guru</span>
                            @endif
                        </td>

                        <td>
                            @if ($kelas->schedules->count())
                                <div class="schedule-chips">
                                    @foreach ($kelas->schedules as $s)
                                        <span class="schedule-chip">
                                            {{ substr($s->day, 0, 3) }} {{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="cand-sub">Belum ada jadwal</span>
                            @endif
                        </td>

                        <td>
                            @php
                                $mp    = $meetingProgress[$kelas->id] ?? null;
                                $done  = (int) ($mp->done ?? 0);
                                $total = (int) ($kelas->programPackage?->total_meetings ?? $kelas->privatePackage?->total_meetings ?? 0);
                                $pct   = $total > 0 ? min(100, (int) round($done / $total * 100)) : 0;
                            @endphp

                            <div class="meet-label">
                                <strong>{{ $done }}</strong>@if ($total > 0)<span>/{{ $total }}</span>@endif
                                <span>pertemuan</span>
                            </div>

                            @if ($total > 0)
                                <div class="meet-bar"><span style="width: {{ $pct }}%"></span></div>
                            @endif

                            <div class="cand-sub">
                                @if ($total > 0 && $done >= $total)
                                    Semua pertemuan selesai
                                @elseif ($mp)
                                    Terakhir {{ \Illuminate\Support\Carbon::parse($mp->last_date)->format('d M Y') }}
                                @else
                                    Belum ada jurnal
                                @endif
                            </div>
                        </td>                        

                        <td>
                            @php $full = $kelas->students_count >= $kelas->capacity; @endphp
                            <span class="{{ $full ? 'capacity-full' : 'capacity-ok' }}">
                                {{ $kelas->students_count }}/{{ $kelas->capacity }}
                            </span>
                        </td>

                        <td>
                            <span class="badge {{ $statusBadge[$kelas->status] ?? '' }}">
                                <span class="badge-dot"></span>
                                {{ $kelas->status }}
                            </span>
                        </td>

                        <td>
                            <div class="row-actions">
                                {{-- 1. Edit kelas (sudah termasuk tambah murid) --}}
                                <a href="{{ route('admin.kelas.edit', $kelas->id) }}" class="icon-btn" title="Edit Kelas &amp; Siswa">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>

                                {{-- 2. Ubah jadwal pertemuan --}}
                                <a href="{{ route('admin.kelas.ubah-jadwal', $kelas->id) }}" class="icon-btn" title="Ubah Jadwal">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </a>

                                {{-- 3. Lihat di halaman Jadwal --}}
                                <a href="{{ $jadwalUrl }}" class="icon-btn" title="Lihat Jadwal">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data kelas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $classes->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $classes->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $classes->total() }}</strong>
                kelas
            </span>

            <div class="table-footer-pagination">
                @if ($classes->hasPages())
                    @php
                        $current = $classes->currentPage();
                        $last = $classes->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($classes->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $classes->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                            @endif

                            @for ($page = 1; $page <= $last; $page++)
                                @php
                                    $isEdge = $page == 1 || $page == $last;
                                    $isNearCurrent = abs($page - $current) <= $onEachSide;
                                @endphp

                                @if ($isEdge || $isNearCurrent)
                                    @if ($page == $current)
                                        <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-btn" href="{{ $classes->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($classes->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $classes->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    /* ============================================================
       FILTER: pencarian otomatis
    ============================================================ */
    (function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { filterForm.submit(); }, 600);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                filterForm.submit();
            }
        });
    })();
</script>
@endpush