@extends('admin.app')

@section('title', 'Jadwal | Pembelajaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endpush

@section('content')

    @php
        $dayCount = count($days);
        $keepFilters = request()->only('teacher_id', 'program_id', 'room', 'class_id');

        $kindClass = [
            'Private'        => 'kind-private',
            'HSK'            => 'kind-hsk',
            'Daily Activity' => 'kind-daily',
            'Replacement'    => 'kind-private',
        ];

        // Warna kartu per kategori program (Maochong, Jianer, Hudie, Feixiang,
        // HSK Class, HSK Preparation). Private punya warna sendiri lewat CSS.
        $categoryColor = function (?string $category) {
            $hue = \App\Http\Controllers\Admin\JadwalController::CATEGORY_HUES[$category] ?? null;

            if ($hue === null) {
                return '--c-bg: hsl(215 14% 92%); --c-bd: hsl(215 12% 55%); --c-tx: hsl(215 20% 20%);';
            }

            return "--c-bg: hsl({$hue} 85% 90%); --c-bd: hsl({$hue} 60% 48%); --c-tx: hsl({$hue} 55% 17%);";
        };

        // Private diperlakukan seperti kategori lain: satu warna lewat variabel --c-*
        $privateColor = '--c-bg: hsl(142 85% 90%); --c-bd: hsl(142 60% 40%); --c-tx: hsl(142 55% 17%);';
        $isPrivateKind = fn ($kind) => in_array($kind, ['Private', 'Replacement'], true);

        // Warna kustom per kelas (hex dari admin, mis. #f97316). Dipakai untuk kelas
        // yang tidak masuk kategori supaya tidak abu-abu.
        $customColor = function (?string $hex) {
            if (! $hex || ! preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
                return null;
            }

            return "--c-bg: color-mix(in srgb, {$hex} 16%, #fff); --c-bd: {$hex}; --c-tx: color-mix(in srgb, {$hex} 30%, #000);";
        };

        // Kategori yang punya warna tetap (Maochong, Jianer, Hudie, dst.)
        $hasHue = fn (?string $category) => $category
            && isset(\App\Http\Controllers\Admin\JadwalController::CATEGORY_HUES[$category]);

        // Warna otomatis untuk kelas di luar kategori: hue diturunkan dari nama kelas,
        // jadi tiap kelas beda warna dan tetap sama setiap kali halaman dibuka.
        $autoColor = function (array $session) {
            $hue = crc32((string) ($session['class_name'] ?? $session['title'] ?? '')) % 360;

            return "--c-bg: hsl({$hue} 85% 90%); --c-bd: hsl({$hue} 60% 48%); --c-tx: hsl({$hue} 55% 17%);";
        };

        // Kelas yang warnanya tidak berasal dari kategori maupun Private
        $isOwnColor = fn (array $session) => $customColor($session['color'] ?? null)
            || (! $isPrivateKind($session['kind'] ?? null) && ! $hasHue($session['category'] ?? null));

        // Urutan prioritas: warna kustom (hex) > Private > kategori program > otomatis dari nama kelas
        $sessionColor = function (array $session) use ($customColor, $categoryColor, $privateColor, $isPrivateKind, $hasHue, $autoColor) {
            if ($custom = $customColor($session['color'] ?? null)) {
                return $custom;
            }

            if ($isPrivateKind($session['kind'] ?? null)) {
                return $privateColor;
            }

            if ($hasHue($session['category'] ?? null)) {
                return $categoryColor($session['category']);
            }

            return $autoColor($session);
        };

        $hasTrials = collect($trials)->flatten(1)->isNotEmpty();

        $isCurrentWeek = $weekStart->isSameDay(
            now()->startOfWeek(\Carbon\Carbon::MONDAY)
        );
    @endphp

    {{-- HEADER --}}
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">PEMBELAJARAN</div>
            <h1>Jadwal</h1>
            <p>
                Lihat jadwal kelas Daily Activity, HSK, Private, dan trial
                calon siswa berdasarkan hari dan ruangan.
            </p>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="class-filter-banner">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="conflict-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- FILTER DAN NAVIGASI MINGGU --}}
    <form method="GET" action="{{ route('admin.jadwal.index') }}" id="filterForm">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

        @if (request('class_id'))
            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
        @endif

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-calendar-week"></i>
                <span class="week-label">
                    {{ $weekStart->format('d M') }}
                    –
                    {{ $weekStart->copy()->addDays($dayCount - 1)->format('d M Y') }}
                </span>
            </div>

            <a href="{{ route('admin.jadwal.index', array_merge($keepFilters, ['week' => $prevWeek])) }}"
               class="jadwal-nav-btn"
               title="Minggu Sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
            </a>

            <a href="{{ route('admin.jadwal.index', array_merge($keepFilters, ['week' => now()->toDateString()])) }}"
               class="jadwal-nav-btn {{ $isCurrentWeek ? 'is-active' : '' }}"
               title="Kembali ke minggu ini">
                {{ $isCurrentWeek ? 'Minggu Ini' : 'Kembali ke Minggu Ini' }}
            </a>

            <a href="{{ route('admin.jadwal.index', array_merge($keepFilters, ['week' => $nextWeek])) }}"
               class="jadwal-nav-btn"
               title="Minggu Berikutnya">
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            {{-- FILTER GURU --}}
            <select name="teacher_id" class="select-chip" onchange="this.form.submit()"
                    title="Filter jadwal kelas dan jadwal available berdasarkan guru">
                <option value="">Semua Guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}"
                        {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            {{-- FILTER PROGRAM --}}
            <select name="program_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Program</option>

                @foreach ($programs as $program)
                    <option value="{{ $program->id }}"
                        {{ request('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->program_name }}
                    </option>
                @endforeach

                <option value="private"
                    {{ request('program_id') === 'private' ? 'selected' : '' }}>
                    Private
                </option>
            </select>

            {{-- FILTER RUANG --}}
            <select name="room" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Ruang</option>

                @foreach ($roomOptions as $room)
                    <option value="{{ $room }}"
                        {{ request('room') === $room ? 'selected' : '' }}>
                        {{ $room }}
                    </option>
                @endforeach
            </select>

            {{-- LEGENDA --}}
            <div class="legend">
                <span class="legend-item">
                    <span class="legend-dot dot-trial"></span>
                    Trial
                </span>

            </div>
        </div>
    </form>

    {{-- FILTER KELAS --}}
    @if (!empty($filteredClass))
        <div class="class-filter-banner">
            <i class="fa-solid fa-filter"></i>
            Menampilkan jadwal kelas
            <strong>{{ $filteredClass->class_name }}</strong> saja.

            <a href="{{ route('admin.jadwal.index', request()->except('class_id')) }}">
                Tampilkan semua kelas
            </a>
        </div>
    @endif

    {{-- INFORMASI BENTROK --}}
    @if ($conflictCount > 0)
        <div class="conflict-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $conflictCount }} bentrok jadwal guru terdeteksi minggu ini.

            <a href="#" onclick="highlightConflicts(event)">Lihat</a>
        </div>
    @endif

    {{-- KALENDER --}}
    <div class="card calendar-card">

        @php
            $slotCount = count($slots);
            $dayStartMin = $startHour * 60;
            $totalMin = $slotCount * 30;
            $bodyRow = $hasTrials ? 4 : 3;

            $colWidths = [];
            $colStart = [];
            $nextCol = 2;

            foreach ($days as $dayName) {
                $colStart[$dayName] = $nextCol;

                foreach ($dayRooms[$dayName] as $room) {
                    $colWidths[] = ($grid[$dayName][$room]['lanes'] ?? 1) * 150;
                    $nextCol++;
                }
            }

            $template = '70px ' . implode(' ', array_map(
                fn ($width) => 'minmax(' . $width . 'px, ' . ($width / 150) . 'fr)',
                $colWidths
            ));
        @endphp

        <div class="zoom-bar">
            <div class="zoom-bar-title">
                <i class="fa-solid fa-calendar-days"></i>
                Jadwal Kelas
            </div>

            <div class="zoom-controls">
                <span class="zoom-hint">Ctrl + scroll untuk zoom</span>
                <button type="button" class="zoom-btn" id="zoomOut" title="Zoom out" onclick="changeZoom(-0.1)">
                    <i class="fa-solid fa-magnifying-glass-minus"></i>
                </button>
                <span class="zoom-value" id="zoomValue">95%</span>
                <button type="button" class="zoom-btn" id="zoomIn" title="Zoom in" onclick="changeZoom(0.1)">
                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                </button>
                <button type="button" class="zoom-btn" title="Kembali ke ukuran awal" onclick="resetZoom()">
                    Reset
                </button>
            </div>
        </div>

        @php
            $categoryOrder = array_merge(
                array_keys(\App\Http\Controllers\Admin\JadwalController::CATEGORY_HUES),
                ['Lainnya']
            );

            $allSessions = collect($sessions)->flatten(1);

            $legendCategories = $allSessions
                ->reject(fn ($item) => $isOwnColor($item) || $isPrivateKind($item['kind'] ?? null))
                ->pluck('category')
                ->filter()
                ->unique()
                ->sortBy(fn ($category) => array_search($category, $categoryOrder, true))
                ->values();

            $hasPrivate = $allSessions->contains(
                fn ($item) => $isPrivateKind($item['kind'] ?? null) && ! $customColor($item['color'] ?? null)
            );

            // Kelas di luar kategori: satu chip per kelas dengan warnanya masing-masing
            $customLegend = $allSessions
                ->filter(fn ($item) => $isOwnColor($item))
                ->unique(fn ($item) => $item['class_name'] ?? $item['title'])
                ->map(fn ($item) => [
                    'label' => $item['class_name'] ?? $item['title'],
                    'style' => $customColor($item['color'] ?? null) ?? $autoColor($item),
                ])
                ->values();
        @endphp

        @if ($legendCategories->isNotEmpty() || $hasPrivate || $customLegend->isNotEmpty())
            <div class="class-legend">
                <span class="class-legend-label">Warna kategori:</span>

                @foreach ($legendCategories as $legendCategory)
                    <span class="class-legend-chip" style="{{ $categoryColor($legendCategory) }}">
                        {{ $legendCategory }}
                    </span>
                @endforeach

                @if ($hasPrivate)
                    <span class="class-legend-chip" style="{{ $privateColor }}">
                        Private
                    </span>
                @endif

                @foreach ($customLegend as $custom)
                    <span class="class-legend-chip" style="{{ $custom['style'] }}">
                        {{ $custom['label'] }}
                    </span>
                @endforeach

                <span class="class-legend-chip"
                      style="--c-bg: #e5e7eb; --c-bd: #6b7280; --c-tx: #1f2937;">
                    Pertemuan terakhir
                </span>
            </div>
        @endif

        <div class="week-scroll" id="weekGrid">
            <div class="week-grid"
                 style="grid-template-columns: {{ $template }}; --slot-count: {{ $slotCount }};">

                {{-- HEADER JAM --}}
                <div class="wg-corner" style="grid-column: 1; grid-row: 1 / span 2;">
                    Jam
                </div>

                {{-- HEADER HARI --}}
                @foreach ($days as $i => $dayName)
                    @php
                        $dayDate = $weekStart->copy()->addDays($i);
                        $isToday = $dayDate->isToday();
                    @endphp

                    <div class="wg-day {{ $isToday ? 'is-today' : '' }}"
                         style="grid-column: {{ $colStart[$dayName] }} / span {{ count($dayRooms[$dayName]) }}; grid-row: 1;">
                        {{ $dayName }} {{ $dayDate->format('d') }}

                        <span class="wg-day-sub">
                            {{ $dayDate->format('d M') }}
                            ·
                            {{ count($sessions[$dayName] ?? []) }} sesi
                        </span>
                    </div>
                @endforeach

                {{-- HEADER RUANGAN --}}
                @foreach ($days as $i => $dayName)
                    @foreach ($dayRooms[$dayName] as $ri => $room)
                        <div class="wg-room {{ $ri === 0 ? 'day-start' : '' }}"
                             style="grid-column: {{ $colStart[$dayName] + $ri }}; grid-row: 2;">
                            {{ $room }}
                        </div>
                    @endforeach
                @endforeach

                {{-- TRIAL CALON SISWA --}}
                @if ($hasTrials)
                    <div class="wg-time wg-trial-label"
                         style="grid-column: 1; grid-row: 3;">
                        Trial
                    </div>

                    @foreach ($days as $i => $dayName)
                        <div class="wg-trial-cell day-start"
                             style="grid-column: {{ $colStart[$dayName] }} / span {{ count($dayRooms[$dayName]) }}; grid-row: 3;">

                            @foreach ($trials[$dayName] ?? [] as $trial)
                                <div class="trial-chip {{ $trial['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                     title="{{ $trial['title'] }} — {{ $trial['status_label'] }}"
                                     data-session="{{ json_encode($trial) }}"
                                     onclick="openSessionDetail(this)">

                                    <i class="fa-solid fa-user-plus"></i>
                                    {{ $trial['candidate_name'] }}
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                {{-- KOLOM WAKTU --}}
                <div class="wg-time wg-time-body"
                     style="grid-column: 1; grid-row: {{ $bodyRow }};">

                    @foreach ($slots as $si => $slot)
                        <span class="wg-tick {{ $slot['is_hour'] ? 'is-hour' : '' }}"
                              style="top: {{ round($si / $slotCount * 100, 3) }}%;">
                            {{ $slot['label'] }}
                        </span>
                    @endforeach
                </div>

                {{-- SESI KELAS --}}
                @foreach ($days as $i => $dayName)
                    @php
                        $isToday = $weekStart->copy()->addDays($i)->isToday();
                    @endphp

                    @foreach ($dayRooms[$dayName] as $ri => $room)
                        <div class="wg-col {{ $ri === 0 ? 'day-start' : '' }} {{ $isToday ? 'is-today' : '' }}"
                             style="grid-column: {{ $colStart[$dayName] + $ri }}; grid-row: {{ $bodyRow }};">

                            @foreach ($grid[$dayName][$room]['sessions'] ?? [] as $session)
                                @php
                                    $teacherLabel = $session['teacher_name']
                                        ? $session['teacher_name'] . (
                                            $session['teacher_note']
                                                ? ' (' . $session['teacher_note'] . ')'
                                                : ''
                                        )
                                        : 'Belum ada guru';

                                    $duration = $session['end_min'] - $session['start_min'];
                                    $top = ($session['start_min'] - $dayStartMin) / $totalMin * 100;
                                    $height = $duration / $totalMin * 100;
                                    $left = $session['lane'] / $session['lanes'] * 100;
                                    $width = 100 / $session['lanes'];

                                    $isLast = ! empty($session['is_last_session']);
                                    $cardColor = $isLast ? null : $sessionColor($session);
                                    $useClassColor = $cardColor !== null;
                                @endphp

                                <div class="sess
                                            {{ $kindClass[$session['kind']] ?? 'kind-daily' }}
                                            {{ $useClassColor ? 'is-class-color' : '' }}
                                            {{ $isLast ? 'is-last-session' : '' }}
                                            {{ $duration <= 30 ? 'is-short' : '' }}
                                            {{ $session['has_conflict'] ? 'has-conflict' : '' }}
                                            {{ $session['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                     style="top: calc({{ round($top, 3) }}% + 1px);
                                            height: calc({{ round($height, 3) }}% - 2px);
                                            left: calc({{ round($left, 3) }}% + 1px);
                                            width: calc({{ round($width, 3) }}% - 2px);
                                            {{ $useClassColor ? $cardColor : '' }}"
                                     title="{{ $session['title'] }} · {{ $teacherLabel }} · {{ $session['start_time'] }}–{{ $session['end_time'] }}{{ $isLast ? ' · Pertemuan terakhir' : '' }}"
                                     data-session="{{ json_encode($session) }}"
                                     onclick="openSessionDetail(this)">

                                    <div class="sess-title">
                                        @if ($session['status_key'] === 'Conducted')
                                            <i class="fa-solid fa-check session-done"></i>
                                        @endif

                                        {{ $session['title'] }}
                                    </div>

                                    <div class="sess-meta">
                                        {{ $teacherLabel }}
                                        ·
                                        {{ $session['student_name'] ?? (($session['students_count'] ?? 0) . ' siswa') }}
                                    </div>

                                    <div class="sess-meta sess-time">
                                        {{ $session['start_time'] }}
                                        –
                                        {{ $session['end_time'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach

            </div>
        </div>
    </div>

    {{-- JADWAL AVAILABLE GURU (card terpisah, di bawah jadwal kelas) --}}
    <div class="card avail-card">
        <div class="avail-head">
            <div>
                <div class="avail-title">
                    <i class="fa-solid fa-user-clock"></i>
                    Jadwal Available Guru
                </div>
                <div class="avail-sub">
                    @if ($selectedTeacher)
                        Jam tersedia {{ $selectedTeacher->name }} setiap minggu.
                    @else
                        Jam tersedia semua guru setiap minggu. Pilih guru di filter untuk melihat satu guru saja.
                    @endif
                </div>
            </div>
        </div>

        @if (empty($availabilityRows))
            <div class="avail-none">
                @if ($selectedTeacher)
                    {{ $selectedTeacher->name }} belum punya jam tersedia.
                @else
                    Belum ada guru yang mengisi jam tersedia.
                @endif
            </div>
        @else
            <div class="avail-scroll">
                <table class="avail-table">
                    <thead>
                        <tr>
                            <th>Guru</th>
                            @foreach ($availDays as $dayName)
                                @php
                                    $di = array_search($dayName, ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'], true);
                                    $dDate = $weekStart->copy()->addDays($di);
                                @endphp
                                <th class="{{ $dDate->isToday() ? 'is-today' : '' }}">
                                    {{ $dayName }}
                                    <small>{{ $dDate->format('d M') }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($availabilityRows as $row)
                            <tr>
                                <td class="avail-teacher">{{ $row['name'] }}</td>
                                @foreach ($availDays as $dayName)
                                    <td>
                                        @forelse ($row['days'][$dayName] ?? [] as $slot)
                                            <span class="avail-chip">{{ $slot['label'] }}</span>
                                        @empty
                                            <span class="avail-empty">–</span>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- MODAL DETAIL SESI --}}
    <div class="modal-overlay" id="sessionDetailOverlay">
        <div class="modal modal-sm">

            <div class="modal-head">
                <div>
                    <div class="modal-title" id="detail_title">Detail Sesi</div>
                    <div class="modal-sub" id="detail_sub">-</div>
                </div>

                <button type="button"
                        class="modal-close"
                        onclick="closeSessionDetail()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="detail-row">
                    <span>Kelas</span>
                    <strong id="detail_class">-</strong>
                </div>

                <div class="detail-row">
                    <span>Tipe</span>
                    <strong id="detail_type">-</strong>
                </div>

                <div class="detail-row">
                    <span>Guru</span>
                    <strong id="detail_teacher">-</strong>
                </div>

                <div class="detail-row">
                    <span>Tanggal</span>
                    <strong id="detail_date">-</strong>
                </div>

                <div class="detail-row">
                    <span>Jam</span>
                    <strong id="detail_time">-</strong>
                </div>

                <div class="detail-row">
                    <span>Ruangan</span>
                    <strong id="detail_room">-</strong>
                </div>

                <div class="detail-row">
                    <span>Status Sesi</span>
                    <strong id="detail_status">-</strong>
                </div>

                <div class="detail-row">
                    <span id="detail_students_label">Jumlah Siswa</span>
                    <strong id="detail_students">-</strong>
                </div>

                <div class="detail-row" id="detail_meeting_row">
                    <span>Pertemuan</span>
                    <strong id="detail_meeting">-</strong>
                </div>

                <div class="detail-row" id="detail_moved_row" style="display: none;">
                    <span>Dipindah dari</span>
                    <strong id="detail_moved">-</strong>
                </div>

                <div class="detail-row" id="detail_reason_row" style="display: none;">
                    <span>Alasan</span>
                    <strong id="detail_reason">-</strong>
                </div>

                <div class="detail-row" id="detail_kindnote_row" style="display: none;">
                    <span>Jenis</span>
                    <strong id="detail_kindnote">-</strong>
                </div>

                {{-- KETERANGAN PEMBAYARAN DARI PAYMENTS --}}
                <div id="detail_fees" style="margin-top: 8px;"></div>

            </div>

            <div class="modal-foot">
                <button type="button"
                        class="btn"
                        onclick="closeSessionDetail()">
                    Tutup
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    /**
     * Scroll ke hari ini ketika kalender dibuka.
     */
    document.addEventListener('DOMContentLoaded', function () {
        const wrap = document.getElementById('weekGrid');
        const today = wrap?.querySelector('.wg-day.is-today');

        if (today) {
            wrap.scrollLeft = Math.max(0, today.offsetLeft - 90);
        }
    });

    /**
     * Zoom kalender kelas. Nilai disimpan di browser supaya tetap sama
     * saat pindah minggu atau mengganti filter.
     */
    const ZOOM_KEY = 'jadwalZoom95';
    const ZOOM_MIN = 0.6;
    const ZOOM_MAX = 2;
    const ZOOM_DEFAULT = 0.95;
    let currentZoom = ZOOM_DEFAULT;

    function applyZoom(value) {
        currentZoom = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(value * 100) / 100));

        const grid = document.querySelector('#weekGrid .week-grid');
        if (grid) {
            grid.style.zoom = currentZoom;
        }

        document.getElementById('zoomValue').textContent = Math.round(currentZoom * 100) + '%';
        document.getElementById('zoomOut').disabled = currentZoom <= ZOOM_MIN;
        document.getElementById('zoomIn').disabled = currentZoom >= ZOOM_MAX;

        try { localStorage.setItem(ZOOM_KEY, currentZoom); } catch (e) {}
    }

    function changeZoom(delta) {
        applyZoom(currentZoom + delta);
    }

    function resetZoom() {
        applyZoom(ZOOM_DEFAULT);
    }

    document.addEventListener('DOMContentLoaded', function () {
        let saved = ZOOM_DEFAULT;

        try {
            const stored = parseFloat(localStorage.getItem(ZOOM_KEY));
            if (!isNaN(stored)) saved = stored;
        } catch (e) {}

        applyZoom(saved);

        // Ctrl + scroll (atau pinch di trackpad) untuk zoom kalender.
        document.getElementById('weekGrid').addEventListener('wheel', function (event) {
            if (!event.ctrlKey) return;
            event.preventDefault();
            changeZoom(event.deltaY < 0 ? 0.1 : -0.1);
        }, { passive: false });
    });

    /**
     * Menyorot sesi yang memiliki bentrok jadwal.
     */
    function highlightConflicts(event) {
        event.preventDefault();

        const all = document.querySelectorAll('#weekGrid .has-conflict');

        if (!all.length) {
            return;
        }

        all.forEach(element => {
            element.classList.add('pulse');

            setTimeout(() => {
                element.classList.remove('pulse');
            }, 2500);
        });

        all[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'center'
        });
    }

    /**
     * Menampilkan keterangan pembayaran dari data payments.
     * Bagian ini hanya menampilkan informasi, bukan mengubah pembayaran.
     *
     * Controller mempertahankan nama properti "fees" untuk kompatibilitas
     * dengan data kalender yang sudah digunakan oleh Blade.
     */
    function renderFees(payments) {
        const box = document.getElementById('detail_fees');
        box.innerHTML = '';

        if (!Array.isArray(payments) || payments.length === 0) {
            const row = document.createElement('div');
            row.className = 'detail-row';

            const label = document.createElement('span');
            label.textContent = 'Biaya';

            const value = document.createElement('strong');
            value.textContent = 'Belum ada data pembayaran';

            row.append(label, value);
            box.appendChild(row);

            return;
        }

        payments.forEach(payment => {
            const row = document.createElement('div');
            row.className = 'detail-row';

            const label = document.createElement('span');
            const paymentLabel =
                payment.label ??
                payment.payment_type ??
                'Biaya tambahan';

            label.textContent = `${paymentLabel} (${payment.amount ?? '-'})`;

            const value = document.createElement('strong');
            value.textContent = payment.paid
                ? `Lunas${payment.paid_at ? ' · ' + payment.paid_at : ''}`
                : 'Belum dibayar';

            row.append(label, value);
            box.appendChild(row);

            if (payment.invoice_url) {
                const invoiceRow = document.createElement('div');
                invoiceRow.className = 'detail-row';

                const invoiceLabel = document.createElement('span');
                invoiceLabel.textContent = 'Invoice';

                const invoiceLink = document.createElement('a');
                invoiceLink.href = payment.invoice_url;
                invoiceLink.target = '_blank';
                invoiceLink.rel = 'noopener noreferrer';
                invoiceLink.textContent = payment.invoice_number
                    ? `Lihat ${payment.invoice_number}`
                    : 'Lihat invoice';

                invoiceRow.append(invoiceLabel, invoiceLink);
                box.appendChild(invoiceRow);
            }
        });
    }

    /**
     * Membuka detail sesi dari kartu kalender.
     */
    function openSessionDetail(block) {
        let data;

        try {
            data = JSON.parse(block.dataset.session);
        } catch (error) {
            console.error('Data detail sesi tidak valid:', error);
            return;
        }

        document.getElementById('sessionDetailOverlay').classList.add('open');

        const teacher = data.teacher_name
            ? data.teacher_name + (
                data.teacher_note ? ' (' + data.teacher_note + ')' : ''
            )
            : (data.kind === 'Trial' ? '-' : 'Belum ada guru');

        document.getElementById('detail_title').textContent =
            data.title ?? 'Detail Sesi';

        document.getElementById('detail_sub').textContent =
            data.kind ?? '-';

        document.getElementById('detail_class').textContent =
            data.class_name ?? '-';

        document.getElementById('detail_type').textContent =
            data.kind ?? '-';

        document.getElementById('detail_teacher').textContent = teacher;

        document.getElementById('detail_date').textContent =
            data.date_label ?? '-';

        document.getElementById('detail_time').textContent =
            data.time_label ?? '-';

        document.getElementById('detail_room').textContent =
            data.room ?? '-';

        document.getElementById('detail_status').textContent =
            data.status_label ?? '-';

        const hasCount = data.students_count !== null && data.students_count !== undefined;

        document.getElementById('detail_students_label').textContent =
            hasCount || !data.student_name ? 'Jumlah Siswa' : 'Siswa';

        document.getElementById('detail_students').textContent = hasCount
            ? data.students_count + ' siswa' + (data.student_name ? ' (' + data.student_name + ')' : '')
            : (data.student_name ?? '-');

        const meetingRow = document.getElementById('detail_meeting_row');
        const meeting = document.getElementById('detail_meeting');

        meetingRow.style.display = data.kind === 'Trial' ? 'none' : '';
        meeting.textContent = data.meeting_label ?? '-';
        meeting.classList.toggle('is-last', !!data.is_last_session);

        document.getElementById('detail_moved_row').style.display =
            data.moved_from ? '' : 'none';

        document.getElementById('detail_moved').textContent =
            data.moved_from ?? '-';

        document.getElementById('detail_reason_row').style.display =
            data.change_reason ? '' : 'none';

        document.getElementById('detail_reason').textContent =
            data.change_reason ?? '-';

        document.getElementById('detail_kindnote_row').style.display =
            data.kind_note ? '' : 'none';

        document.getElementById('detail_kindnote').textContent =
            data.kind_note ?? '-';

        renderFees(data.fees ?? []);
    }

    /**
     * Menutup modal detail sesi.
     */
    function closeSessionDetail() {
        document.getElementById('sessionDetailOverlay').classList.remove('open');
    }

    document.getElementById('sessionDetailOverlay')
        .addEventListener('click', function (event) {
            if (event.target === this) {
                closeSessionDetail();
            }
        });
</script>
@endpush