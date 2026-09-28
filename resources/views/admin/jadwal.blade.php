@extends('admin.app')

@section('title', 'Jadwal | Pembelajaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/jadwal.css') }}">
@endpush

@section('content')

    @php
        $dayCount    = count($days);
        $keepFilters = request()->only('teacher_id', 'program_id');

        $hourCount   = $endHour - $startHour;
        $hourMarks   = range($startHour, $endHour - 1);
        $dayStartMin = $startHour * 60;
        $totalMin    = $hourCount * 60;
        $laneHeight  = 52; // px per lane (baris sesi) di timeline

        $kindClass = [
            'Private'        => 'kind-private',
            'HSK'            => 'kind-hsk',
            'Daily Activity' => 'kind-daily',
        ];

        // Hari yang dibuka pertama kali: hari ini (jika ada di minggu ini), kalau tidak hari pertama
        $defaultDay = collect($days)->first(
            fn ($d, $i) => $weekStart->copy()->addDays($i)->isToday()
        ) ?? $days[0];
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">PEMBELAJARAN</div>
            <h1>Jadwal</h1>
            <p>
                Lihat seluruh sesi pertemuan — kelas Daily Activity, HSK, Private, dan trial calon siswa —
                per hari untuk cek ketersediaan ruang.
            </p>
        </div>

        <div class="dashboard-header-actions">
            <button type="button" class="btn btn-primary" onclick="openSessionModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Sesi
            </button>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.jadwal') }}" id="filterForm">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-calendar-week"></i>
                <span class="week-label">{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->addDays($dayCount - 1)->format('d M Y') }}</span>
            </div>

            <a href="{{ route('admin.jadwal', array_merge($keepFilters, ['week' => $prevWeek])) }}" class="jadwal-nav-btn" title="Minggu Sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <a href="{{ route('admin.jadwal', $keepFilters) }}" class="jadwal-nav-btn" title="Kembali ke minggu ini">Hari Ini</a>
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

            <div class="legend">
                <span class="legend-item"><span class="legend-dot dot-daily"></span> Daily Activity</span>
                <span class="legend-item"><span class="legend-dot dot-hsk"></span> HSK</span>
                <span class="legend-item"><span class="legend-dot dot-private"></span> Private</span>
                <span class="legend-item"><span class="legend-dot dot-trial"></span> Trial</span>
            </div>
        </div>
    </form>

    @if ($conflictCount > 0)
        <div class="conflict-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $conflictCount }} bentrok jadwal guru terdeteksi minggu ini.
            <a href="#" onclick="highlightConflicts(event)">Lihat</a>
        </div>
    @endif

    <div class="card calendar-card">

        {{-- ==================================================================
             TAMPILAN HARI — tab per hari + grid per ruang
             (Beijing/Basement/Atas/Kaca/Online). Guru ditulis langsung di
             dalam kartu sesi (bukan kolom sendiri).
        ================================================================== --}}
        <div id="dayView">

            <div class="day-tabs">
                @foreach ($days as $i => $dayName)
                    @php $dayDate = $weekStart->copy()->addDays($i); @endphp
                    <button type="button"
                            class="day-tab {{ $dayName === $defaultDay ? 'active' : '' }}"
                            data-day="{{ $dayName }}"
                            onclick="showDayView('{{ $dayName }}')">
                        <strong>{{ $dayName }}</strong>
                        <span>{{ $dayDate->format('d M') }} · {{ count($sessions[$dayName] ?? []) }} sesi</span>
                    </button>
                @endforeach
            </div>

            @foreach ($days as $i => $dayName)
                <div class="day-pane" data-day="{{ $dayName }}" style="{{ $dayName === $defaultDay ? '' : 'display:none;' }}">

                    @if (! empty($trials[$dayName]))
                        <div class="day-trials">
                            <span class="day-trials-label">Trial calon siswa</span>
                            @foreach ($trials[$dayName] as $trial)
                                <div class="trial-chip {{ $trial['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                     title="{{ $trial['title'] }} — {{ $trial['status_label'] }}"
                                     data-session="{{ json_encode($trial) }}"
                                     onclick="openSessionDetail(this)">
                                    <i class="fa-solid fa-user-plus"></i> {{ $trial['candidate_name'] }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="timeline-scroll">
                        <div class="timeline" style="--hours: {{ $hourCount }};">

                            <div class="tl-row tl-head">
                                <div class="tl-label">Ruang</div>
                                <div class="tl-track-head">
                                    @foreach ($hourMarks as $hour)
                                        <div class="tl-hour">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                                    @endforeach
                                </div>
                            </div>

                            @forelse ($timeline[$dayName] ?? [] as $row)
                                <div class="tl-row">
                                    <div class="tl-label">
                                        <div class="tl-teacher">{{ $row['room_name'] }}</div>
                                        <div class="tl-teacher-sub {{ $row['free'] ? 'is-free' : '' }}">
                                            {{ $row['free'] ? 'Tidak ada sesi' : count($row['sessions']) . ' sesi' }}
                                        </div>
                                    </div>

                                    <div class="tl-track" style="height: {{ $row['lanes'] * $laneHeight + 8 }}px;">
                                        @foreach ($row['sessions'] as $session)
                                            @php
                                                $left  = round(($session['start_min'] - $dayStartMin) / $totalMin * 100, 3);
                                                $width = round(($session['end_min'] - $session['start_min']) / $totalMin * 100, 3);
                                                $top   = 4 + $session['lane'] * $laneHeight;
                                                $teacherLabel = $session['teacher_name']
                                                    ? $session['teacher_name'] . ($session['teacher_note'] ? ' (' . $session['teacher_note'] . ')' : '')
                                                    : 'Belum ada guru';
                                            @endphp
                                            <div class="tl-block {{ $kindClass[$session['kind']] ?? 'kind-daily' }} {{ $session['has_conflict'] ? 'has-conflict' : '' }} {{ $session['status_key'] === 'Cancelled' ? 'is-cancelled' : '' }}"
                                                 style="left: {{ $left }}%; width: {{ $width }}%; top: {{ $top }}px; height: {{ $laneHeight - 4 }}px;"
                                                 title="{{ $session['title'] }} · {{ $teacherLabel }} · {{ $session['start_time'] }}–{{ $session['end_time'] }}"
                                                 data-session="{{ json_encode($session) }}"
                                                 onclick="openSessionDetail(this)">
                                                <div class="tl-block-title">
                                                    @if ($session['status_key'] === 'Conducted')
                                                        <i class="fa-solid fa-check session-done"></i>
                                                    @endif
                                                    {{ $session['title'] }}
                                                </div>
                                                <div class="tl-block-sub">{{ $session['start_time'] }}–{{ $session['end_time'] }} · {{ $teacherLabel }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="week-empty" style="padding:24px;">Tidak ada ruang untuk ditampilkan.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
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

    /* ============================================================
       Ganti tab hari (hanya satu tampilan: grid per ruang per hari)
    ============================================================ */
    function showDayView(dayName) {
        document.querySelectorAll('.day-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.day === dayName);
        });
        document.querySelectorAll('.day-pane').forEach(p => {
            p.style.display = p.dataset.day === dayName ? '' : 'none';
        });
    }

    function highlightConflicts(e) {
        e.preventDefault();

        // Bentrok bisa ada di hari lain, jadi cari di semua pane (walau sedang disembunyikan)
        const firstConflict = document.querySelector('#dayView .has-conflict');
        if (! firstConflict) return;

        const pane = firstConflict.closest('.day-pane');
        if (pane) showDayView(pane.dataset.day);

        document.querySelectorAll('#dayView .has-conflict').forEach(el => {
            el.classList.add('pulse');
            setTimeout(() => el.classList.remove('pulse'), 2500);
        });

        setTimeout(() => firstConflict.scrollIntoView({ behavior: 'smooth', block: 'center' }), 50);
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