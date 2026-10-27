@extends('admin.app')

@section('title', 'Kurikulum & Materi | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin-extra.css') }}">
@endpush

@section('content')

    @php
        $programOf = fn ($class) => $class?->private_package_id
            ? 'Private'
            : ($class?->programPackage?->program?->program_name ?? $class?->level?->program?->program_name ?? '-');
        $keepQuery = request()->except('page', 'tab');
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Kurikulum &amp; Materi</h1>
            <p>
                Lihat pertemuan ke berapa dan materi apa yang dijadwalkan per kelas / siswa.
                Materi diunggah tim kurikulum — halaman ini hanya untuk dilihat.
            </p>
        </div>
    </div>

    @include('admin.partials.flash')

    {{-- ========================= TAB ========================= --}}
    <div class="x-tabs">
        <a class="x-tab {{ $tab === 'harian' ? 'active' : '' }}"
           href="{{ route('admin.kurikulum-materi', ['tab' => 'harian']) }}">
            <i class="fa-solid fa-calendar-day"></i> Materi per Hari
        </a>
        <a class="x-tab {{ $tab === 'katalog' ? 'active' : '' }}"
           href="{{ route('admin.kurikulum-materi', ['tab' => 'katalog']) }}">
            <i class="fa-solid fa-book"></i> Katalog Materi per Level
        </a>
    </div>

    {{-- ===================================================================
         TAB 1 — MATERI PER HARI
    =================================================================== --}}
    @if ($tab === 'harian')

        <div class="stat-row">
            <div class="stat-card c-blue">
                <div class="stat-top">
                    <span class="stat-label">Sesi Hari Ini</span>
                    <i class="fa-solid fa-calendar-day stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['sesi'] }}</div>
                <div class="stat-note">Pada tanggal yang dipilih</div>
            </div>

            <div class="stat-card c-green">
                <div class="stat-top">
                    <span class="stat-label">Materi Siap</span>
                    <i class="fa-solid fa-circle-check stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['siap'] }}</div>
                <div class="stat-note">Materi sudah tersedia</div>
            </div>

            <div class="stat-card c-red">
                <div class="stat-top">
                    <span class="stat-label">Materi Belum Ada</span>
                    <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['belum'] }}</div>
                <div class="stat-note">Belum diunggah tim kurikulum</div>
            </div>

            <div class="stat-card c-yellow">
                <div class="stat-top">
                    <span class="stat-label">Siswa Terjadwal</span>
                    <i class="fa-solid fa-users stat-icon"></i>
                </div>
                <div class="stat-value">{{ $stats['siswa'] }}</div>
                <div class="stat-note">Siswa aktif di sesi ini</div>
            </div>
        </div>

        {{-- Navigasi tanggal --}}
        @php
            $prev = $date->copy()->subDay()->toDateString();
            $next = $date->copy()->addDay()->toDateString();
        @endphp
        <div class="x-date-nav">
            <a class="btn" href="{{ route('admin.kurikulum-materi', array_merge($keepQuery, ['tab' => 'harian', 'date' => $prev])) }}">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div class="x-date-title">{{ $date->copy()->locale('id')->translatedFormat('l, d F Y') }}</div>
            <a class="btn" href="{{ route('admin.kurikulum-materi', array_merge($keepQuery, ['tab' => 'harian', 'date' => $next])) }}">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
            <a class="btn" href="{{ route('admin.kurikulum-materi', ['tab' => 'harian']) }}">Hari Ini</a>
        </div>

        <form method="GET" action="{{ route('admin.kurikulum-materi') }}" id="filterForm">
            <input type="hidden" name="tab" value="harian">

            <div class="toolbar">
                <div class="toolbar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa atau kelas">
                </div>

                <input type="date" name="date" value="{{ $date->toDateString() }}" class="select-chip" onchange="this.form.submit()">

                <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                    <option value="">Semua Laoshi</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('admin.kurikulum-materi', ['tab' => 'harian']) }}" class="btn">
                    <i class="fa-solid fa-rotate-right"></i> Reset
                </a>
            </div>
        </form>

        @forelse ($sessions as $s)
            @php
                $class = $s->class;
                $schedule = $s->schedule;
                $m = $s->material;
                $boxClass = match ($s->state) {
                    'rencana' => 'is-rencana',
                    'sudah' => 'is-sudah',
                    'belum_diupload', 'tanpa_level', 'tidak_berjalan' => 'is-warn',
                    default => '',
                };
            @endphp

            <div class="x-session">
                {{-- Waktu --}}
                <div>
                    <div class="x-session-time">
                        {{ substr($schedule->start_time, 0, 5) }}
                        <small>s/d {{ substr($schedule->end_time, 0, 5) }}</small>
                    </div>
                    @if ($schedule->room)
                        <div class="x-muted" style="margin-top:6px;"><i class="fa-solid fa-door-open"></i> {{ $schedule->room }}</div>
                    @endif
                </div>

                {{-- Kelas & siswa --}}
                <div>
                    <div class="x-session-title">{{ $class->class_name }}</div>
                    <div class="cand-sub">
                        {{ $programOf($class) }}@if ($class->level) · {{ $class->level->category?->category_name ? $class->level->category->category_name . ' - ' : '' }}{{ $class->level->level_name }}@endif
                        · {{ $class->delivery_mode }}
                    </div>
                    <div class="cand-sub">Laoshi: {{ $class->teacher?->name ?? 'Belum ada guru' }}</div>

                    <div class="x-students">
                        @forelse ($s->students as $name)
                            <span class="x-chip">{{ $name }}</span>
                        @empty
                            <span class="x-muted">Belum ada siswa aktif.</span>
                        @endforelse
                    </div>
                </div>

                {{-- Materi --}}
                <div class="x-material {{ $boxClass }}">
                    @if (in_array($s->state, ['rencana', 'sudah']) && $m)
                        <div class="x-material-meeting">
                            Pertemuan ke-{{ $s->number }}@if ($s->total) dari {{ $s->total }}@endif
                            @if ($s->state === 'sudah') · sesuai jurnal @else · berikutnya @endif
                        </div>
                        <div class="x-material-title">{{ $m->title }}</div>
                        <div class="x-material-syllabus">{{ \Illuminate\Support\Str::limit($m->syllabus, 160) }}</div>
                        <div class="x-material-foot">
                            @if ($m->vocabs_count)
                                <span class="x-chip">{{ $m->vocabs_count }} kosakata</span>
                            @endif
                            <a href="{{ route('admin.kurikulum-materi.unduh', $m) }}" class="btn btn-sm">
                                <i class="fa-solid fa-download"></i> Unduh Materi
                            </a>
                            <a href="{{ route('admin.kurikulum-materi', ['tab' => 'katalog', 'level_id' => $class->level_id]) }}" class="btn btn-sm">
                                Lihat di Katalog
                            </a>
                        </div>

                    @elseif ($s->state === 'tidak_berjalan')
                        <div class="x-material-meeting">Sesi tidak berjalan</div>
                        <div class="x-material-title">{{ $s->journal->class_status === 'Cancelled' ? 'Dibatalkan' : 'Dijadwal ulang' }}</div>
                        <div class="x-muted">Menurut jurnal laoshi. Pertemuan belum bertambah.</div>

                    @elseif ($s->state === 'tanpa_level')
                        <div class="x-material-meeting">Tanpa level</div>
                        <div class="x-material-title">Belum terhubung ke materi</div>
                        <div class="x-muted">Kelas ini tidak punya level, jadi tidak ada materi terjadwal.</div>

                    @else
                        <div class="x-material-meeting">Pertemuan ke-{{ $s->number }}</div>
                        <div class="x-material-title">Materi belum diunggah</div>
                        <div class="x-muted">
                            Tim kurikulum belum mengunggah materi pertemuan ke-{{ $s->number }} untuk level ini.
                        </div>
                    @endif

                    @if ($s->last_journal && $s->last_journal->material)
                        <div class="x-muted" style="margin-top:8px;">
                            Terakhir diajarkan: Pertemuan {{ $s->last_journal->material->meeting_number }}
                            ({{ $s->last_journal->session_date->format('d M Y') }})
                        </div>
                    @endif

                    @if ($s->journal_missing)
                        <div class="x-flag"><i class="fa-solid fa-circle-exclamation"></i> Jurnal sesi ini belum diisi laoshi</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card">
                <div class="x-empty">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    Tidak ada sesi kelas pada tanggal ini.
                </div>
            </div>
        @endforelse

        <div class="x-note" style="margin-top:12px;">
            <i class="fa-solid fa-circle-info"></i>
            Nomor pertemuan dihitung dari jurnal laoshi: pertemuan tertinggi yang sudah diajarkan + 1.
            Kalau laoshi belum mengisi jurnal sebelumnya, nomornya bisa tertinggal.
        </div>
    @endif

    {{-- ===================================================================
         TAB 2 — KATALOG MATERI PER LEVEL
    =================================================================== --}}
    @if ($tab === 'katalog')

        <form method="GET" action="{{ route('admin.kurikulum-materi') }}">
            <input type="hidden" name="tab" value="katalog">
            <div class="toolbar">
                <select name="level_id" class="select-chip" style="min-width:320px;" onchange="this.form.submit()">
                    @foreach ($levels->groupBy('program_id') as $programLevels)
                        <optgroup label="{{ $programLevels->first()->program?->program_name ?? 'Program' }}">
                            @foreach ($programLevels as $level)
                                <option value="{{ $level->id }}" {{ $selectedLevel?->id === $level->id ? 'selected' : '' }}>
                                    {{ $level->category?->category_name ? $level->category->category_name . ' — ' : '' }}{{ $level->level_name }}
                                    ({{ $level->materials_count }} materi)
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </form>

        @forelse ($materials as $material)
            <details class="x-material-card">
                <summary>
                    <div class="x-meeting-no">{{ $material->meeting_number }}</div>
                    <div style="flex:1;min-width:0;">
                        <div class="cand-name">{{ $material->title }}</div>
                        <div class="cand-sub">
                            Pertemuan ke-{{ $material->meeting_number }}
                            · {{ $material->vocabs->count() }} kosakata
                            · diunggah {{ $material->uploader?->curriculum?->name ?? $material->uploader?->username ?? '-' }}
                        </div>
                    </div>
                    <a href="{{ route('admin.kurikulum-materi.unduh', $material) }}" class="btn btn-sm"
                       onclick="event.stopPropagation()">
                        <i class="fa-solid fa-download"></i> Unduh
                    </a>
                </summary>

                <div class="x-body">
                    <div class="x-detail-label">Silabus</div>
                    <div class="x-pre" style="margin-bottom:12px;">{{ $material->syllabus }}</div>

                    @if ($material->vocabs->isNotEmpty())
                        <div class="x-detail-label">Kosakata</div>
                        <table class="table-mini">
                            <thead>
                                <tr><th>#</th><th>Hanzi</th><th>Pinyin</th><th>Arti</th><th>Contoh Kalimat</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($material->vocabs as $i => $v)
                                    <tr>
                                        <td>{{ $v->order_number ?? $i + 1 }}</td>
                                        <td>{{ $v->hanzi }}</td>
                                        <td>{{ $v->pinyin }}</td>
                                        <td>{{ $v->meaning }}</td>
                                        <td>{{ $v->example_sentence ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="x-muted">Belum ada daftar kosakata untuk pertemuan ini.</div>
                    @endif
                </div>
            </details>
        @empty
            <div class="card">
                <div class="x-empty">
                    <i class="fa-solid fa-book-open"></i>
                    Tim kurikulum belum mengunggah materi untuk level ini.
                </div>
            </div>
        @endforelse
    @endif

@endsection

@push('scripts')
    @if ($tab === 'harian')
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
    @endif
@endpush
