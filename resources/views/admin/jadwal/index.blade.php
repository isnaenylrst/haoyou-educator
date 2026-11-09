```blade
@extends('admin.app')

@section('title', 'Jadwal | Pembelajaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/jadwal.css') }}">
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
            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
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
                    <span class="legend-dot dot-daily"></span>
                    Daily Activity
                </span>

                <span class="legend-item">
                    <span class="legend-dot dot-hsk"></span>
                    HSK
                </span>

                <span class="legend-item">
                    <span class="legend-dot dot-private"></span>
                    Private
                </span>

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
                                @endphp

                                <div class="sess
                                            {{ $kindClass[$session['kind']] ?? 'kind-daily' }}
                                            {{ $duration <= 30 ? 'is-short' : '' }}
                                            {{ $session['has_conflict'] ? 'has-conflict' : '' }}
                                            {{ $session['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                     style="top: calc({{ round($top, 3) }}% + 1px);
                                            height: calc({{ round($height, 3) }}% - 2px);
                                            left: calc({{ round($left, 3) }}% + 2px);
                                            width: calc({{ round($width, 3) }}% - 4px);"
                                     title="{{ $session['title'] }} · {{ $teacherLabel }} · {{ $session['start_time'] }}–{{ $session['end_time'] }}"
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
                    <span>Waktu</span>
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

                <div class="detail-row">
                    <span id="detail_students_label">Siswa Terdaftar</span>
                    <strong id="detail_students">-</strong>
                </div>
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

        document.getElementById('detail_students_label').textContent =
            data.student_name ? 'Siswa' : 'Siswa Terdaftar';

        document.getElementById('detail_students').textContent =
            data.student_name ?? data.students_count ?? '-';

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
```