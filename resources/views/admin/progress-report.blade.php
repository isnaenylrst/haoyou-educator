@extends('admin.app')

@section('title', 'Progress Report | Laporan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endpush

@section('content')

    @php
        $keepQuery = request()->except('page', 'tab');
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">LAPORAN</div>
            <h1>Progress Report</h1>
            <p>
                Pantau progress report dari laoshi dan catat follow up kelanjutan les siswa.
                Laporan yang sudah jatuh tempo tapi belum dikirim bisa langsung diingatkan lewat notifikasi.
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
            <div class="stat-note">Sudah disetujui Kurikulum</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Belum Diisi</span>
                <i class="fa-solid fa-circle-exclamation stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['belum'] }}</div>
            <div class="stat-note">{{ $stats['teachers_late'] }} laoshi perlu diingatkan</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Belum Di-follow up</span>
                <i class="fa-solid fa-phone-volume stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['belum_fu'] }}</div>
            <div class="stat-note">Perlu ditanya lanjut les</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Lanjut</span>
                <i class="fa-solid fa-circle-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['lanjut'] }}</div>
            <div class="stat-note">{{ $stats['tidak'] }} siswa tidak melanjutkan</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.progress-report') }}" id="filterForm">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}"
                       placeholder="{{ $tab === 'belum' ? 'Cari nama siswa atau kelas' : 'Cari nama siswa atau periode' }}">
            </div>

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Laoshi</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            @if ($tab === 'laporan')
                <select name="proses" class="select-chip" onchange="this.form.submit()">
                    <option value="">Semua Proses</option>
                    @foreach (\App\Http\Controllers\Admin\ProgressReportController::PROSES as $p)
                        <option value="{{ $p }}" {{ request('proses') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>

                <select name="follow_up" class="select-chip" onchange="this.form.submit()">
                    <option value="">Semua Follow Up</option>
                    @foreach (\App\Models\ProgressReport::FOLLOW_UPS as $f)
                        <option value="{{ $f }}" {{ request('follow_up') === $f ? 'selected' : '' }}>{{ $f }}</option>
                    @endforeach
                </select>
            @endif

            <a href="{{ route('admin.progress-report', ['tab' => $tab]) }}" class="btn">
                <i class="fa-solid fa-rotate-right"></i>
                Reset
            </a>
        </div>
    </form>

    {{-- ========================= TAB ========================= --}}
    <div class="pkg-tabs">
        <a class="pkg-tab {{ $tab === 'laporan' ? 'active' : '' }}"
        href="{{ route('admin.progress-report', array_merge($keepQuery, ['tab' => 'laporan'])) }}">
            <i class="fa-solid fa-file-lines"></i> Laporan
            <span class="pkg-tab-count">{{ $stats['total'] }}</span>
        </a>
        <a class="pkg-tab {{ $tab === 'belum' ? 'active' : '' }}"
        href="{{ route('admin.progress-report', array_merge($keepQuery, ['tab' => 'belum'])) }}">
            <i class="fa-solid fa-hourglass-half"></i> Belum Diisi
            <span class="pkg-tab-count {{ $stats['belum'] ? 'pkg-tab-count-red' : '' }}">{{ $stats['belum'] }}</span>
        </a>
    </div>

    {{-- ========================= TAB: LAPORAN ========================= --}}
    @if ($tab === 'laporan')
        <div class="card">
            <table class="x-pr-table">
                <thead>
                    <tr>
                        <th class="c-siswa">Siswa</th>
                        <th class="c-laoshi">Laoshi</th>
                        <th class="c-tgl" style="text-align: center; vertical-align: middle;">Tanggal Terbit</th>
                        <th class="c-proses" style="text-align: center; vertical-align: middle;">Proses</th>
                        <th class="c-follow" style="text-align: center; vertical-align: middle;">Follow Up</th>
                        <th class="c-aksi" style="text-align: center; vertical-align: middle;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($reports as $r)
                        @php
                            $enrollment = $r->enrollment;
                            $isPrivate = (bool) $enrollment?->private_package_id;

                            $className = $isPrivate
                                ? ($enrollment->privatePackage?->package_name ?? 'Private')
                                : ($enrollment?->class?->class_name ?? '-');

                            // Proses: Draft / Dikembalikan (belum dikirim) · Menunggu Review · Disetujui
                            $proses = $r->status === 'Submitted'
                                ? ($r->reviewed_at ? 'Disetujui' : 'Menunggu Review')
                                : ($r->returned_at ? 'Dikembalikan' : 'Draft');
                            $approved = $proses === 'Disetujui';

                            $prosesBadge = match ($proses) {
                                'Disetujui' => 'badge-completed',
                                'Menunggu Review' => 'badge-warning',
                                'Dikembalikan' => 'badge-cancelled',
                                default => 'badge-pending',
                            };

                            // tanggal terbit = tanggal disetujui Kurikulum (hanya untuk laporan yang sudah disetujui)
                            $issued = $approved && $r->reviewed_at
                                ? \Carbon\Carbon::parse($r->reviewed_at)->locale('id')->translatedFormat('d M Y')
                                : '-';
                        @endphp
                        <tr>
                            <td class="c-siswa">
                                <div class="name-cell">
                                    <div class="avatar-sm">{{ strtoupper(substr($r->student?->name ?? '?', 0, 1)) }}</div>
                                    <div>
                                        <div class="cand-name">{{ $r->student?->name ?? '-' }}</div>
                                        <div class="cand-sub">{{ $className }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="c-laoshi">{{ $r->teacher?->name ?? '-' }}</td>
                            <td class="c-tgl" style="text-align: center; vertical-align: middle;">{{ $issued }}</td>
                            <td class="c-proses" style="text-align: center; vertical-align: middle;">
                                <span class="badge {{ $prosesBadge }}">{{ $proses }}</span>
                            </td>
                            <td class="c-follow" style="text-align: center; vertical-align: middle;">
                                {{-- Follow up bisa dicatat untuk semua laporan --}}
                                <form method="POST"
                                      action="{{ route('admin.progress-report.follow-up', $r) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="follow_up_status" class="select-chip" title="Ubah status follow up"
                                            onchange="this.form.submit()">
                                        @foreach (\App\Models\ProgressReport::FOLLOW_UPS as $f)
                                            <option value="{{ $f }}" {{ $r->follow_up_status === $f ? 'selected' : '' }}>{{ $f }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="c-aksi" style="text-align: center; vertical-align: middle;">
                                @if ($approved)
                                    <div class="row-actions">
                                        <a href="{{ route('admin.progress-report.lihat', $r) }}" target="_blank" rel="noopener"
                                           class="icon-btn" title="Lihat">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.progress-report.unduh', $r) }}" class="icon-btn" title="Unduh">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    </div>
                                @else
                                    <span class="x-muted" title="PDF tersedia setelah disetujui Kurikulum">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="x-empty">
                                <i class="fa-regular fa-file-lines"></i>
                                Belum ada progress report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @include('admin.partials.pagination', ['paginator' => $reports, 'noun' => 'laporan'])
        </div>
    @endif

    {{-- ========================= TAB: BELUM DIISI ========================= --}}
    @if ($tab === 'belum')

        @if ($byTeacher->isNotEmpty())
            <div class="card x-summary">
                <div class="x-summary-title"><i class="fa-solid fa-bell"></i> Laoshi dengan progress report tertunggak</div>
                <div class="x-summary-list">
                    @foreach ($byTeacher as $row)
                        <div class="x-summary-item">
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($row['teacher']->name, 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $row['teacher']->name }}</div>
                                    <div class="cand-sub">
                                        <span class="x-late">{{ $row['count'] }} progress report belum diisi</span>
                                        @if ($row['last_reminded'])
                                            · diingatkan {{ $row['last_reminded']->locale('id')->diffForHumans() }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.progress-report.remind') }}" class="x-inline-form">
                                @csrf
                                <input type="hidden" name="teacher_id" value="{{ $row['teacher']->id }}">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-regular fa-bell"></i> Ingatkan semua
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Laoshi</th>
                        <th style="text-align: center; vertical-align: middle;">Jatuh Tempo</th>
                        <th style="text-align: center; vertical-align: middle;">Status</th>
                        <th style="text-align: center; vertical-align: middle;">Keterlambatan</th>
                        <th style="text-align: center; vertical-align: middle;">Pengingat</th>
                        <th class="c-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($missingPage as $m)
                        @php
                            $stateBadge = match ($m->state) {
                                'Draft' => 'badge-pending',
                                'Dikembalikan' => 'badge-warning',
                                default => 'badge-cancelled',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="name-cell">
                                    <div class="avatar-sm">{{ strtoupper(substr($m->student->name ?? '?', 0, 1)) }}</div>
                                    <div>
                                        <div class="cand-name">{{ $m->student->name ?? '-' }}</div>
                                        <div class="cand-sub">{{ $m->kind_label }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cand-name">{{ $m->class->class_name }}</div>
                                <div class="cand-sub">
                                    {{ $m->class->private_package_id ? 'Private' : ($m->class->programPackage?->program?->program_name ?? '-') }}@if ($m->class->level) · {{ $m->class->level->level_name }}@endif
                                </div>
                            </td>
                            <td>{{ $m->teacher->name }}</td>
                            <td style="text-align: center; vertical-align: middle;">
                                {{ $m->due->format('d M Y') }}
                                <div class="cand-sub">{{ $m->due->copy()->locale('id')->translatedFormat('l') }}</div>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <span class="badge {{ $stateBadge }}">{{ $m->state }}</span>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <span class="{{ $m->overdue ? 'x-late' : '' }}">
                                    {{ $m->days_late === 0 ? 'Hari ini' : $m->days_late . ' hari' }}
                                </span>
                                @if ($m->overdue)
                                    <div class="cand-sub">Lewat batas H+{{ \App\Services\ProgressReportService::GRACE_DAYS }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                @if ($m->reminded_at)
                                    <span class="x-muted"><i class="fa-solid fa-check"></i> {{ $m->reminded_at->locale('id')->diffForHumans() }}</span>
                                @else
                                    <span class="x-muted">Belum diingatkan</span>
                                @endif
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <div class="row-actions">
                                    <form method="POST" action="{{ route('admin.progress-report.remind') }}" class="x-inline-form">
                                        @csrf
                                        <input type="hidden" name="teacher_id" value="{{ $m->teacher->id }}">
                                        <input type="hidden" name="keys[]" value="{{ $m->key }}">
                                        <button type="submit" class="icon-btn wa" title="Ingatkan laoshi untuk laporan ini">
                                            <i class="fa-regular fa-bell"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="x-empty is-ok">
                                <i class="fa-solid fa-circle-check"></i>
                                Semua progress report yang jatuh tempo sudah dikirim.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @include('admin.partials.pagination', ['paginator' => $missingPage, 'noun' => 'laporan'])
        </div>
    @endif

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