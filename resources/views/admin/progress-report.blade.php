@extends('admin.app')

@section('title', 'Progress Report | Laporan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin-extra.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">LAPORAN</div>
            <h1>Progress Report</h1>
            <p>
                Laporan perkembangan siswa yang sudah diterbitkan laoshi. Admin dapat melihat
                dan mengunduh arsipnya.
            </p>
        </div>
    </div>

    @include('admin.partials.flash')

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Laporan Terbit</span>
                <i class="fa-solid fa-file-circle-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['terbit'] }}</div>
            <div class="stat-note">Sudah diterbitkan laoshi</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Bulanan</span>
                <i class="fa-solid fa-calendar-days stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['bulanan'] }}</div>
            <div class="stat-note">Laporan per bulan</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Akhir Paket</span>
                <i class="fa-solid fa-flag-checkered stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['akhir'] }}</div>
            <div class="stat-note">Laporan akhir paket</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Masih Draft</span>
                <i class="fa-solid fa-pen-ruler stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['draft'] }}</div>
            <div class="stat-note">Belum diterbitkan laoshi</div>
        </div>
    </div>

    @if ($stats['draft'] > 0)
        <div class="x-note">
            <i class="fa-solid fa-circle-info"></i>
            {{ $stats['draft'] }} laporan masih berstatus Draft di sisi laoshi, jadi belum ditampilkan di halaman ini.
        </div>
    @endif

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.progress-report') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa atau periode">
            </div>

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Laoshi</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            <select name="report_type" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" {{ request('report_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>

            <a href="{{ route('admin.progress-report') }}" class="btn">
                <i class="fa-solid fa-rotate-right"></i>
                Reset
            </a>
        </div>
    </form>

    {{-- ========================= TABLE ========================= --}}
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Program</th>
                    <th>Tipe</th>
                    <th>Periode</th>
                    <th>Laoshi</th>
                    <th>Tanggal Terbit</th>
                    <th width="110" style="text-align:center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($reports as $r)
                    @php
                        $enrollment = $r->enrollment;
                        $isPrivate = (bool) $enrollment?->private_package_id;

                        $programLabel = $isPrivate
                            ? 'Private'
                            : ($enrollment?->class?->programPackage?->program?->program_name
                                ?? $enrollment?->programPackage?->program?->program_name
                                ?? '-');

                        $programBadge = match ($programLabel) {
                            'HSK' => 'badge-warning',
                            'Daily Activity' => 'badge-cold',
                            'Private' => 'badge-completed',
                            default => '',
                        };

                        $className = $isPrivate
                            ? ($enrollment->privatePackage?->package_name ?? 'Private')
                            : ($enrollment?->class?->class_name ?? '-');

                        // created_at bisa null (model ProgressReport memakai $timestamps = false)
                        $issued = $r->created_at
                            ? \Carbon\Carbon::parse($r->created_at)->locale('id')->translatedFormat('d M Y')
                            : '-';
                    @endphp
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($r->student?->name ?? '?', 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $r->student?->name ?? '-' }}</div>
                                    <div class="cand-sub">{{ $className }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($programLabel !== '-')
                                <span class="badge {{ $programBadge }}">{{ $programLabel }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $r->report_type }}</td>
                        <td>{{ $r->report_period }}</td>
                        <td>{{ $r->teacher?->name ?? '-' }}</td>
                        <td>{{ $issued }}</td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('admin.progress-report.lihat', $r) }}" target="_blank" rel="noopener"
                                   class="icon-btn" title="Lihat">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.progress-report.unduh', $r) }}" class="icon-btn" title="Unduh">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="x-empty">
                            <i class="fa-regular fa-file-lines"></i>
                            Belum ada progress report yang diterbitkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @include('admin.partials.pagination', ['paginator' => $reports, 'noun' => 'laporan'])
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            let timer;

            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => filterForm.submit(), 600);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); filterForm.submit(); }
            });
        })();
    </script>
@endpush
