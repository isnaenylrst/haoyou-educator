@extends('admin.app')

@section('title', 'Jadwal | Pembelajaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/jadwal.css') }}">
@endpush

@section('content')

    @php
        $dayCount    = count($days);
        $roomCount   = count($roomColumns);
        $keepFilters = request()->only('teacher_id', 'program_id', 'room', 'class_id');

        $kindClass = [
            'Private'        => 'kind-private',
            'HSK'            => 'kind-hsk',
            'Daily Activity' => 'kind-daily',
        ];

        $hasTrials = collect($trials)->flatten(1)->isNotEmpty();

        // Apakah minggu yang ditampilkan = minggu berjalan?
        $isCurrentWeek = $weekStart->isSameDay(now()->startOfWeek(\Carbon\Carbon::MONDAY));
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">PEMBELAJARAN</div>
            <h1>Jadwal</h1>
            <p>
                Lihat seluruh sesi pertemuan — kelas Daily Activity, HSK, Private, dan trial calon siswa —
                per ruang untuk cek ketersediaan ruangan.
            </p>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.jadwal') }}" id="filterForm">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
        @if (request('class_id'))
            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
        @endif

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-calendar-week"></i>
                <span class="week-label">{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->addDays($dayCount - 1)->format('d M Y') }}</span>
            </div>

            <a href="{{ route('admin.jadwal', array_merge($keepFilters, ['week' => $prevWeek])) }}" class="jadwal-nav-btn" title="Minggu Sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <a href="{{ route('admin.jadwal', array_merge($keepFilters, ['week' => now()->toDateString()])) }}"
               class="jadwal-nav-btn {{ $isCurrentWeek ? 'is-active' : '' }}"
               title="Kembali ke minggu ini">
                {{ $isCurrentWeek ? 'Minggu Ini' : 'Kembali ke Minggu Ini' }}
            </a>
            <a href="{{ route('admin.jadwal', array_merge($keepFilters, ['week' => $nextWeek])) }}" class="jadwal-nav-btn" title="Minggu Berikutnya">
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            <select name="program_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Program</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->program_name }}
                    </option>
                @endforeach
                <option value="private" {{ request('program_id') === 'private' ? 'selected' : '' }}>Private</option>
            </select>

            <select name="room" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Ruang</option>
                @foreach ($roomOptions as $room)
                    <option value="{{ $room }}" {{ request('room') === $room ? 'selected' : '' }}>{{ $room }}</option>
                @endforeach
            </select>

            <div class="legend">
                <span class="legend-item"><span class="legend-dot dot-daily"></span> Daily Activity</span>
                <span class="legend-item"><span class="legend-dot dot-hsk"></span> HSK</span>
                <span class="legend-item"><span class="legend-dot dot-private"></span> Private</span>
                <span class="legend-item"><span class="legend-dot dot-trial"></span> Trial</span>
            </div>
        </div>
    </form>

    @if (! empty($filteredClass))
        <div class="class-filter-banner">
            <i class="fa-solid fa-filter"></i>
            Menampilkan jadwal kelas <strong>{{ $filteredClass->class_name }}</strong> saja.
            <a href="{{ route('admin.jadwal', request()->except('class_id')) }}">Tampilkan semua kelas</a>
        </div>
    @endif

    @if ($conflictCount > 0)
        <div class="conflict-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $conflictCount }} bentrok jadwal guru terdeteksi minggu ini.
            <a href="#" onclick="highlightConflicts(event)">Lihat</a>
        </div>
    @endif

    <div class="card calendar-card">

        @php
            $slotCount   = count($slots);
            $dayStartMin = $startHour * 60;
            $totalMin    = $slotCount * 30;
            $bodyRow     = $hasTrials ? 4 : 3;

            $colWidths = [];
            $colStart  = [];   // kolom grid tempat tiap hari dimulai
            $nextCol   = 2;    // kolom 1 = jam
            foreach ($days as $dayName) {
                $colStart[$dayName] = $nextCol;
                foreach ($dayRooms[$dayName] as $room) {
                    $colWidths[] = ($grid[$dayName][$room]['lanes'] ?? 1) * 150;
                    $nextCol++;
                }
            }

            $template = '70px ' . implode(' ', array_map(
                fn ($w) => 'minmax(' . $w . 'px, ' . ($w / 150) . 'fr)',
                $colWidths
            ));
        @endphp

        {{-- Kolom = hari (tiap hari dibagi per ruang). Jam ke bawah; tinggi kartu mengikuti durasi sesi. --}}
        <div class="week-scroll" id="weekGrid">
            <div class="week-grid" style="grid-template-columns: {{ $template }}; --slot-count: {{ $slotCount }};">

                <div class="wg-corner" style="grid-column: 1; grid-row: 1 / span 2;">Jam</div>

                {{-- Header hari --}}
                @foreach ($days as $i => $dayName)
                    @php
                        $dayDate = $weekStart->copy()->addDays($i);
                        $isToday = $dayDate->isToday();
                    @endphp
                    <div class="wg-day {{ $isToday ? 'is-today' : '' }}"
                        style="grid-column: {{ $colStart[$dayName] }} / span {{ count($dayRooms[$dayName]) }}; grid-row: 1;">
                        {{ $dayName }} {{ $dayDate->format('d') }}
                        <span class="wg-day-sub">{{ $dayDate->format('d M') }} · {{ count($sessions[$dayName] ?? []) }} sesi</span>
                    </div>
                @endforeach

                {{-- Header ruang --}}
                @foreach ($days as $i => $dayName)
                    @foreach ($dayRooms[$dayName] as $ri => $room)
                        <div class="wg-room {{ $ri === 0 ? 'day-start' : '' }}"
                            style="grid-column: {{ $colStart[$dayName] + $ri }}; grid-row: 2;">{{ $room }}</div>
                    @endforeach
                @endforeach

                {{-- Trial calon siswa: hanya punya tanggal, jadi ditaruh di baris sendiri --}}
                @if ($hasTrials)
                    <div class="wg-time wg-trial-label" style="grid-column: 1; grid-row: 3;">Trial</div>
                    @foreach ($days as $i => $dayName)
                        <div class="wg-trial-cell day-start"
                            style="grid-column: {{ $colStart[$dayName] }} / span {{ count($dayRooms[$dayName]) }}; grid-row: 3;">
                            @foreach ($trials[$dayName] ?? [] as $trial)
                                <div class="trial-chip {{ $trial['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                     title="{{ $trial['title'] }} — {{ $trial['status_label'] }}"
                                     data-session="{{ json_encode($trial) }}"
                                     onclick="openSessionDetail(this)">
                                    <i class="fa-solid fa-user-plus"></i> {{ $trial['candidate_name'] }}
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                {{-- Kolom jam --}}
                <div class="wg-time wg-time-body" style="grid-column: 1; grid-row: {{ $bodyRow }};">
                    @foreach ($slots as $si => $slot)
                        <span class="wg-tick {{ $slot['is_hour'] ? 'is-hour' : '' }}"
                              style="top: {{ round($si / $slotCount * 100, 3) }}%;">{{ $slot['label'] }}</span>
                    @endforeach
                </div>

                {{-- Kolom per hari x ruang --}}
                @foreach ($days as $i => $dayName)
                    @php $isToday = $weekStart->copy()->addDays($i)->isToday(); @endphp
                    @foreach ($dayRooms[$dayName] as $ri => $room)
                        <div class="wg-col {{ $ri === 0 ? 'day-start' : '' }} {{ $isToday ? 'is-today' : '' }}"
                            style="grid-column: {{ $colStart[$dayName] + $ri }}; grid-row: {{ $bodyRow }};">

                            @foreach ($grid[$dayName][$room]['sessions'] ?? [] as $session)
                                @php
                                    $teacherLabel = $session['teacher_name']
                                        ? $session['teacher_name'] . ($session['teacher_note'] ? ' (' . $session['teacher_note'] . ')' : '')
                                        : 'Belum ada guru';

                                    $duration = $session['end_min'] - $session['start_min'];
                                    $top      = ($session['start_min'] - $dayStartMin) / $totalMin * 100;
                                    $height   = $duration / $totalMin * 100;
                                    $left     = $session['lane'] / $session['lanes'] * 100;
                                    $width    = 100 / $session['lanes'];
                                @endphp
                                <div class="sess {{ $kindClass[$session['kind']] ?? 'kind-daily' }} {{ $duration <= 30 ? 'is-short' : '' }} {{ $session['has_conflict'] ? 'has-conflict' : '' }} {{ $session['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                     style="top: calc({{ round($top, 3) }}% + 1px); height: calc({{ round($height, 3) }}% - 2px); left: calc({{ round($left, 3) }}% + 2px); width: calc({{ round($width, 3) }}% - 4px);"
                                     title="{{ $session['title'] }} · {{ $teacherLabel }} · {{ $session['start_time'] }}–{{ $session['end_time'] }}"
                                     data-session="{{ json_encode($session) }}"
                                     onclick="openSessionDetail(this)">
                                    <div class="sess-title">
                                        @if ($session['status_key'] === 'Conducted')
                                            <i class="fa-solid fa-check session-done"></i>
                                        @endif
                                        {{ $session['title'] }}
                                    </div>
                                    <div class="sess-meta">{{ $teacherLabel }} · {{ $session['students_count'] }} siswa</div>
                                    <div class="sess-meta sess-time">{{ $session['start_time'] }} – {{ $session['end_time'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — DETAIL SESI ========================= --}}
    <div class="modal-overlay" id="sessionDetailOverlay">
        <div class="modal modal-sm">
            <div class="modal-head">
                <div>
                    <div class="modal-title" id="detail_title">Detail Sesi</div>
                    <div class="modal-sub" id="detail_sub">-</div>
                </div>
                <button type="button" class="modal-close" onclick="closeSessionDetail()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div class="detail-row"><span>Kelas</span><strong id="detail_class">-</strong></div>
                <div class="detail-row"><span>Tipe</span><strong id="detail_type">-</strong></div>
                <div class="detail-row"><span>Guru</span><strong id="detail_teacher">-</strong></div>
                <div class="detail-row"><span>Tanggal</span><strong id="detail_date">-</strong></div>
                <div class="detail-row"><span>Waktu</span><strong id="detail_time">-</strong></div>
                <div class="detail-row"><span>Ruangan</span><strong id="detail_room">-</strong></div>
                <div class="detail-row"><span>Status Sesi</span><strong id="detail_status">-</strong></div>
                <div class="detail-row"><span>Siswa Terdaftar</span><strong id="detail_students">-</strong></div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn" onclick="closeSessionDetail()">Tutup</button>
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" id="btnReschedule">
                        <i class="fa-solid fa-clock-rotate-left"></i> Reschedule
                    </button>
                    <button type="button" class="btn btn-primary" id="btnMarkDone">
                        <i class="fa-solid fa-check"></i> Tandai Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH SESI (Private) ========================= --}}
    <div class="modal-overlay" id="sessionModalOverlay">
        <div class="modal modal-sm">
            <form onsubmit="return false;">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Sesi</div>
                        <div class="modal-sub">Untuk kelas Private atau sesi tambahan/pengganti.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeSessionModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Kelas *</label>
                            <select name="class_id" required>
                                <option value="">Pilih Kelas</option>
                                @foreach ($classOptions as $option)
                                    <option value="{{ $option->id }}">
                                        {{ $option->class_name }} ({{ $option->private_package_id ? 'Private' : 'Reguler' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Tanggal *</label>
                            <input type="date" name="date" required>
                        </div>
                        <div class="form-field"></div>
                        <div class="form-field">
                            <label>Jam Mulai *</label>
                            <input type="time" name="start_time" required>
                        </div>
                        <div class="form-field">
                            <label>Jam Selesai *</label>
                            <input type="time" name="end_time" required>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn" onclick="closeSessionModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Sesi</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>

    /* Saat dibuka: scroll horizontal ke kolom hari ini (kalau ada di minggu ini) */
    document.addEventListener('DOMContentLoaded', function () {
        const wrap  = document.getElementById('weekGrid');
        const today = wrap?.querySelector('.wg-day.is-today');
        if (today) {
            wrap.scrollLeft = Math.max(0, today.offsetLeft - 90);
        }
    });

    function highlightConflicts(e) {
        e.preventDefault();

        const all = document.querySelectorAll('#weekGrid .has-conflict');
        if (!all.length) return;

        all.forEach(el => {
            el.classList.add('pulse');
            setTimeout(() => el.classList.remove('pulse'), 2500);
        });

        all[0].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
    }

    /* ============================================================
       MODAL: TAMBAH SESI
    ============================================================ */
    function openSessionModal() {
        document.getElementById('sessionModalOverlay').classList.add('open');
    }
    function closeSessionModal() {
        document.getElementById('sessionModalOverlay').classList.remove('open');
    }
    document.getElementById('sessionModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeSessionModal();
    });

    /* ============================================================
       MODAL: DETAIL SESI — data diambil dari atribut data-session
    ============================================================ */
    function openSessionDetail(block) {
        const data = JSON.parse(block.dataset.session);
        document.getElementById('sessionDetailOverlay').classList.add('open');

        const teacher = data.teacher_name
            ? data.teacher_name + (data.teacher_note ? ' (' + data.teacher_note + ')' : '')
            : (data.kind === 'Trial' ? '-' : 'Belum ada guru');

        document.getElementById('detail_title').textContent = data.title ?? 'Detail Sesi';
        document.getElementById('detail_sub').textContent = data.kind ?? '-';
        document.getElementById('detail_class').textContent = data.class_name ?? '-';
        document.getElementById('detail_type').textContent = data.kind ?? '-';
        document.getElementById('detail_teacher').textContent = teacher;
        document.getElementById('detail_date').textContent = data.date_label ?? '-';
        document.getElementById('detail_time').textContent = data.time_label ?? '-';
        document.getElementById('detail_room').textContent = data.room ?? '-';
        document.getElementById('detail_status').textContent = data.status_label ?? '-';
        document.getElementById('detail_students').textContent = data.students_count ?? '-';

        document.getElementById('btnReschedule').style.display = data.kind === 'Private' ? '' : 'none';
        document.getElementById('btnMarkDone').onclick = () => {
            alert('Sesi "' + data.title + '" ditandai selesai (contoh statis, belum terhubung ke backend).');
            closeSessionDetail();
        };
    }

    function closeSessionDetail() {
        document.getElementById('sessionDetailOverlay').classList.remove('open');
    }
    document.getElementById('sessionDetailOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeSessionDetail();
    });

</script>
@endpush