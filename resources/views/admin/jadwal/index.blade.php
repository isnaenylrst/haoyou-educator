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
            'Replacement'    => 'kind-private',
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

    <div style="margin-bottom: 12px;">
        <button type="button" class="btn btn-primary" onclick="openSessionModal()">
            <i class="fa-solid fa-plus"></i> Tambah Kelas Pengganti
        </button>
    </div>

    @if (session('success'))
        <div class="class-filter-banner"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="conflict-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.jadwal.index') }}" id="filterForm">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
        @if (request('class_id'))
            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
        @endif

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-calendar-week"></i>
                <span class="week-label">{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->addDays($dayCount - 1)->format('d M Y') }}</span>
            </div>

            <a href="{{ route('admin.jadwal.index', array_merge($keepFilters, ['week' => $prevWeek])) }}" class="jadwal-nav-btn" title="Minggu Sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <a href="{{ route('admin.jadwal.index', array_merge($keepFilters, ['week' => now()->toDateString()])) }}"
               class="jadwal-nav-btn {{ $isCurrentWeek ? 'is-active' : '' }}"
               title="Kembali ke minggu ini">
                {{ $isCurrentWeek ? 'Minggu Ini' : 'Kembali ke Minggu Ini' }}
            </a>
            <a href="{{ route('admin.jadwal.index', array_merge($keepFilters, ['week' => $nextWeek])) }}" class="jadwal-nav-btn" title="Minggu Berikutnya">
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
            <a href="{{ route('admin.jadwal.index', request()->except('class_id')) }}">Tampilkan semua kelas</a>
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
                                    <div class="sess-meta">{{ $teacherLabel }} · {{ $session['student_name'] ?? (($session['students_count'] ?? 0) . ' siswa') }}</div>
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
                <div class="detail-row" id="detail_moved_row" style="display:none"><span>Dipindah dari</span><strong id="detail_moved">-</strong></div>
                <div class="detail-row" id="detail_reason_row" style="display:none"><span>Alasan</span><strong id="detail_reason">-</strong></div>
                <div class="detail-row" id="detail_kindnote_row" style="display:none"><span>Jenis</span><strong id="detail_kindnote">-</strong></div>
                <div id="detail_fees" style="margin-top:8px"></div>
                <div class="detail-row"><span id="detail_students_label">Siswa Terdaftar</span><strong id="detail_students">-</strong></div>
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

    {{-- ========================= MODAL — RESCHEDULE ========================= --}}
    <div class="modal-overlay" id="rescheduleOverlay">
        <div class="modal modal-sm">
            <form method="POST" id="rescheduleForm" action="#">
                @csrf
                @method('PUT')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Ubah Jadwal</div>
                        <div class="modal-sub" id="rs_sub">-</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeReschedule()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Berlaku untuk *</label>
                            <label><input type="radio" name="scope" value="one" checked> Hanya pertemuan ini</label>
                            <label id="rs_following_label"><input type="radio" name="scope" value="following" id="rs_following"> Pertemuan ini dan seterusnya</label>
                        </div>
                        <div class="form-field">
                            <label>Tanggal baru *</label>
                            <input type="date" name="session_date" id="rs_date" required>
                        </div>
                        <div class="form-field">
                            <label>Jam mulai *</label>
                            <input type="time" name="start_time" id="rs_start" required>
                        </div>
                        <div class="form-field full" id="rs_room_wrap">
                            <label>Ruangan *</label>
                            <select name="room" id="rs_room">
                                @foreach ($roomOptions as $room)
                                    @if ($room !== 'Online')
                                        <option value="{{ $room }}">{{ $room }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field full">
                            <label>Alasan</label>
                            <input type="text" name="reason" maxlength="255" placeholder="Mis. guru ada keperluan, siswa minta ganti jam">
                        </div>
                        <div class="form-field full">
                            <label><input type="checkbox" name="confirm_outside_availability" value="1"> Izinkan di luar jam tersedia guru (guru sudah setuju)</label>
                        </div>
                    </div>
                    <small id="rs_note">Durasi pertemuan tetap. "Seterusnya" memindahkan semua pertemuan berikutnya ke hari baru dan memperbarui pola mingguan kelas.</small>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn" onclick="closeReschedule()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH KELAS PENGGANTI ========================= --}}
    <div class="modal-overlay" id="sessionModalOverlay">
        <div class="modal modal-sm">
            <form method="POST" action="{{ route('admin.jadwal.sessions.store') }}">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Kelas Pengganti</div>
                        <div class="modal-sub">Pertemuan pengganti untuk siswa kelas reguler yang berhalangan hadir.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeSessionModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Siswa (kelas asal) *</label>
                            <select name="class_enrollment_id" id="add_enrollment" required>
                                <option value="">Pilih siswa</option>
                                @foreach ($enrollmentOptions as $option)
                                    <option value="{{ $option['id'] }}" {{ old('class_enrollment_id') == $option['id'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field full">
                            <small>Pertemuan tambahan di luar paket, satu pertemuan saja. Biaya Rp175.000 tercatat otomatis sebagai belum dibayar dan bisa ditandai lunas dari detail pertemuan. Guru boleh berbeda dari guru kelas asal.</small>
                        </div>
                        <div class="form-field full">
                            <label>Guru *</label>
                            <select name="teacher_id" required>
                                <option value="">Pilih guru</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Tanggal *</label>
                            <input type="date" name="session_date" value="{{ old('session_date') }}" required>
                        </div>
                        <div class="form-field">
                            <label>Lokasi *</label>
                            <select name="location_type" id="add_location" onchange="toggleAddRoom()" required>
                                @foreach (['Studio', 'Online', 'Home Visit'] as $loc)
                                    <option value="{{ $loc }}" {{ old('location_type', 'Studio') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Jam Mulai *</label>
                            <input type="time" name="start_time" value="{{ old('start_time') }}" required>
                        </div>
                        <div class="form-field">
                            <label>Jam Selesai *</label>
                            <input type="time" name="end_time" value="{{ old('end_time') }}" required>
                        </div>
                        <div class="form-field full" id="add_room_wrap">
                            <label>Ruangan *</label>
                            <select name="room" id="add_room">
                                @foreach ($roomOptions as $room)
                                    @if ($room !== 'Online')
                                        <option value="{{ $room }}">{{ $room }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn" onclick="closeSessionModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Form tersembunyi untuk "Tandai Selesai" --}}
    <form method="POST" action="#" id="payFeeForm" style="display:none">
        @csrf
        @method('PUT')
        <input type="hidden" name="payment_method">
        <input type="hidden" name="paid_at">
    </form>

    <form method="POST" id="completeForm" action="#" style="display:none">
        @csrf
        @method('PUT')
    </form>

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
    function renderFees(fees) {
        const box = document.getElementById('detail_fees');
        box.innerHTML = '';
        fees.forEach(f => {
            const row = document.createElement('div');
            row.className = 'detail-row';
            const left = document.createElement('span');
            left.textContent = f.label + ' (' + f.amount + ')';
            const right = document.createElement('strong');
            if (f.paid) {
                right.textContent = 'Lunas' + (f.paid_at ? ' · ' + f.paid_at : '') + ' ';
                if (f.invoice_url) {
                    const a = document.createElement('a');
                    a.href = f.invoice_url;
                    a.target = '_blank';
                    a.textContent = 'Invoice ' + f.invoice_number;
                    right.appendChild(a);
                }
            } else {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn';
                btn.textContent = 'Tandai Lunas';
                btn.onclick = () => {
                    const method = prompt('Metode pembayaran (mis. Cash / Transfer):');
                    if (!method) return;
                    const form = document.getElementById('payFeeForm');
                    form.action = payFeeUrl.replace('__ID__', f.id);
                    form.querySelector('[name=payment_method]').value = method;
                    form.querySelector('[name=paid_at]').value = new Date().toISOString().slice(0, 10);
                    form.submit();
                };
                right.appendChild(btn);
            }
            row.append(left, right);
            box.appendChild(row);
        });
    }

    function toggleAddRoom() {
        const isStudio = document.getElementById('add_location').value === 'Studio';
        document.getElementById('add_room_wrap').style.display = isStudio ? '' : 'none';
        document.getElementById('add_room').disabled = !isStudio;
    }
    document.addEventListener('DOMContentLoaded', toggleAddRoom);

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
        document.getElementById('detail_students_label').textContent = data.student_name ? 'Siswa' : 'Siswa Terdaftar';
        document.getElementById('detail_students').textContent = data.student_name ?? data.students_count ?? '-';

        document.getElementById('detail_moved_row').style.display = data.moved_from ? '' : 'none';
        document.getElementById('detail_moved').textContent = data.moved_from ?? '-';
        document.getElementById('detail_reason_row').style.display = data.change_reason ? '' : 'none';
        document.getElementById('detail_reason').textContent = data.change_reason ?? '-';
        document.getElementById('detail_kindnote_row').style.display = data.kind_note ? '' : 'none';
        document.getElementById('detail_kindnote').textContent = data.kind_note ?? '-';
        renderFees(data.fees || []);

        const btnRs = document.getElementById('btnReschedule');
        btnRs.style.display = data.can_reschedule ? '' : 'none';
        btnRs.onclick = () => { closeSessionDetail(); openReschedule(data); };
        const btnDone = document.getElementById('btnMarkDone');
        btnDone.style.display = data.can_complete ? '' : 'none';
        btnDone.onclick = () => {
            if (!confirm('Tandai pertemuan "' + data.title + '" sebagai selesai? Setelah ini jadwalnya terkunci.')) return;
            const form = document.getElementById('completeForm');
            form.action = completeUrl.replace('__ID__', data.session_id);
            form.submit();
        };
    }

    /* ============================================================
       MODAL: RESCHEDULE
    ============================================================ */
    const payFeeUrl     = @json(route('admin.jadwal.fees.pay', ['sessionFee' => '__ID__']));
    const completeUrl   = @json(route('admin.jadwal.complete', ['classSession' => '__ID__']));
    const rescheduleUrl = @json(route('admin.jadwal.reschedule', ['classSession' => '__ID__']));

    function openReschedule(data) {
        const form = document.getElementById('rescheduleForm');
        form.action = rescheduleUrl.replace('__ID__', data.session_id);
        form.reset();

        document.getElementById('rs_sub').textContent =
            data.class_name + (data.session_number ? ' · Pertemuan ke-' + data.session_number : '');
        document.getElementById('rs_date').value  = data.date_value;
        document.getElementById('rs_start').value = data.start_time;

        const isStudio = data.location_type === 'Studio';
        document.getElementById('rs_room_wrap').style.display = isStudio ? '' : 'none';
        document.getElementById('rs_room').disabled = !isStudio;
        if (isStudio && data.room_value) document.getElementById('rs_room').value = data.room_value;

        const canFollow = !!data.can_reschedule_following;
        document.getElementById('rs_following').disabled = !canFollow;
        document.getElementById('rs_following_label').style.opacity = canFollow ? '' : '0.5';
        document.getElementById('rs_following_label').title = canFollow ? '' : 'Tidak tersedia untuk kelas pengganti / pola yang sudah ditutup';

        document.getElementById('rescheduleOverlay').classList.add('open');
    }
    function closeReschedule() {
        document.getElementById('rescheduleOverlay').classList.remove('open');
    }
    document.getElementById('rescheduleOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeReschedule();
    });

    function closeSessionDetail() {
        document.getElementById('sessionDetailOverlay').classList.remove('open');
    }
    document.getElementById('sessionDetailOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeSessionDetail();
    });

</script>
@endpush