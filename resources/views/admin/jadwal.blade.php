@extends('admin.app')

@section('title', 'Jadwal | Pembelajaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/jadwal.css') }}">
@endpush

@section('content')

    {{-- ============================================================
         DATA STATIS SEMENTARA — ganti dengan data dari controller
         ($weekStart, $sessions, $teachers, $programs, $conflicts, dst.)
         ketika backend sudah siap.
    ============================================================ --}}
    @php
        $dayStartMinutes = 8 * 60;
        $dayEndMinutes   = 20 * 60;
        $totalMinutes    = $dayEndMinutes - $dayStartMinutes;
        $hourMarks       = range(8, 20);
        $days            = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $weekStart = \Carbon\Carbon::now()->startOfWeek();

        $teachers = [
            (object) ['id' => 1, 'name' => 'Bu Lina'],
            (object) ['id' => 2, 'name' => 'Pak Wijaya'],
        ];

        $programs = [
            (object) ['id' => 1, 'program_name' => 'HSK 1'],
            (object) ['id' => 2, 'program_name' => 'HSK 2'],
            (object) ['id' => 3, 'program_name' => 'HSK 3'],
        ];

        $classOptions = [
            (object) ['id' => 1, 'name' => 'Mandarin Dasar A1 - Sore', 'type' => 'Reguler'],
            (object) ['id' => 2, 'name' => 'Private Conversation - Andi', 'type' => 'Private'],
        ];

        $rawSessions = [
            (object) [
                'id' => 1, 'title' => 'Mandarin Dasar A1', 'kind' => 'Reguler',
                'class_name' => 'Mandarin Dasar A1 - Sore', 'teacher_name' => 'Bu Lina',
                'start_time' => '15:00', 'end_time' => '16:30', 'students_count' => 10,
                'has_conflict' => false,
            ],
            (object) [
                'id' => 2, 'title' => 'Mandarin Dasar A1', 'kind' => 'Reguler',
                'class_name' => 'Mandarin Dasar A1 - Sore', 'teacher_name' => 'Bu Lina',
                'start_time' => '15:00', 'end_time' => '16:30', 'students_count' => 10,
                'has_conflict' => false,
            ],
            (object) [
                'id' => 3, 'title' => 'Private - Andi', 'kind' => 'Private',
                'class_name' => 'Private Conversation - Andi', 'teacher_name' => 'Pak Wijaya',
                'start_time' => '11:00', 'end_time' => '12:00', 'students_count' => 1,
                'has_conflict' => false,
            ],
            (object) [
                'id' => 4, 'title' => 'Trial - Siti', 'kind' => 'Trial',
                'class_name' => 'Trial Calon Siswa', 'teacher_name' => 'Bu Lina',
                'start_time' => '14:00', 'end_time' => '15:00', 'students_count' => 1,
                'has_conflict' => true,
            ],
            (object) [
                'id' => 5, 'title' => 'HSK 2 Weekend', 'kind' => 'Reguler',
                'class_name' => 'Mandarin HSK 2 - Weekend', 'teacher_name' => null,
                'start_time' => '09:00', 'end_time' => '10:30', 'students_count' => 12,
                'has_conflict' => false,
            ],
        ];

        $sessions = collect([
            'Senin'  => collect([$rawSessions[0]]),
            'Selasa' => collect([$rawSessions[2]]),
            'Rabu'   => collect([$rawSessions[1]]),
            'Kamis'  => collect([$rawSessions[3]]),
            'Jumat'  => collect([]),
            'Sabtu'  => collect([$rawSessions[4]]),
        ]);

        $conflicts = collect([$rawSessions[3]]);
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">PEMBELAJARAN</div>
            <h1>Jadwal</h1>
            <p>
                Lihat seluruh sesi pertemuan — kelas Reguler, Private, dan trial calon siswa —
                dalam satu kalender untuk cek ketersediaan guru.
            </p>
        </div>

        <div class="dashboard-header-actions">
            <div class="view-toggle">
                <button type="button" class="view-toggle-btn active" data-view="week">Minggu</button>
                <button type="button" class="view-toggle-btn" data-view="day">Hari</button>
            </div>

            <button type="button" class="btn btn-primary" onclick="openSessionModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Sesi
            </button>
        </div>
    </div>

    {{-- ========================= FILTER (statis, belum submit ke backend) ========================= --}}
    <div class="toolbar">
        <div class="toolbar-search">
            <i class="fa-solid fa-calendar-week"></i>
            <span class="week-label">{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->addDays(5)->format('d M Y') }}</span>
        </div>

        <button type="button" class="btn" title="Minggu Sebelumnya">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button type="button" class="btn" title="Minggu Berikutnya">
            <i class="fa-solid fa-chevron-right"></i>
        </button>

        <select class="select-chip">
            <option value="">Semua Guru</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
            @endforeach
        </select>

        <select class="select-chip">
            <option value="">Semua Program</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}">{{ $program->program_name }}</option>
            @endforeach
        </select>

        <div class="legend">
            <span class="legend-item"><span class="legend-dot dot-reguler"></span> Reguler</span>
            <span class="legend-item"><span class="legend-dot dot-private"></span> Private</span>
            <span class="legend-item"><span class="legend-dot dot-trial"></span> Trial</span>
        </div>
    </div>

    @if ($conflicts->count())
        <div class="conflict-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $conflicts->count() }} bentrok jadwal guru terdeteksi minggu ini.
            <a href="#" onclick="highlightConflicts(event)">Lihat</a>
        </div>
    @endif

    {{-- ========================= KALENDER ========================= --}}
    <div class="card calendar-card">
        <div class="calendar-grid" style="grid-template-columns: 64px repeat({{ count($days) }}, 1fr);">

            <div class="cal-corner"></div>
            @foreach ($days as $i => $dayName)
                @php $dayDate = $weekStart->copy()->addDays($i); @endphp
                <div class="cal-day-header {{ $dayDate->isToday() ? 'is-today' : '' }}">
                    <div class="cal-day-name">{{ $dayName }}</div>
                    <div class="cal-day-date">{{ $dayDate->format('d M') }}</div>
                </div>
            @endforeach

            <div class="cal-hours">
                @foreach ($hourMarks as $hour)
                    <div class="cal-hour-mark">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                @endforeach
            </div>

            @foreach ($days as $i => $dayName)
                @php $dayDate = $weekStart->copy()->addDays($i); @endphp
                <div class="cal-day-col {{ $dayDate->isToday() ? 'is-today' : '' }}">
                    @foreach ($hourMarks as $hour)
                        <div class="cal-hour-line"></div>
                    @endforeach

                    @foreach ($sessions->get($dayName, collect()) as $session)
                        @php
                            [$sh, $sm] = explode(':', $session->start_time);
                            [$eh, $em] = explode(':', $session->end_time);
                            $startMin = ($sh * 60 + $sm) - $dayStartMinutes;
                            $durMin   = ($eh * 60 + $em) - ($sh * 60 + $sm);
                            $top      = max(0, ($startMin / $totalMinutes) * 100);
                            $height   = max(4, ($durMin / $totalMinutes) * 100);

                            $blockClass = match ($session->kind) {
                                'Private' => 'block-private',
                                'Trial'   => 'block-trial',
                                default   => 'block-reguler',
                            };
                        @endphp
                        <div
                            class="cal-block {{ $blockClass }} {{ $session->has_conflict ? 'has-conflict' : '' }}"
                            style="top: {{ $top }}%; height: {{ $height }}%;"
                            title="{{ $session->title }}"
                            data-session='@json($session)'
                            onclick="openSessionDetail(this)">
                            <div class="cal-block-title">{{ $session->title }}</div>
                            <div class="cal-block-sub">{{ $session->start_time }}–{{ $session->end_time }} · {{ $session->teacher_name ?? 'Belum ada guru' }}</div>
                        </div>
                    @endforeach
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
                <div class="detail-row"><span>Waktu</span><strong id="detail_time">-</strong></div>
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
                                    <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->type }})</option>
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
       (statis, tanpa fetch ke backend)
    ============================================================ */
    function openSessionDetail(block) {
        const data = JSON.parse(block.dataset.session);
        document.getElementById('sessionDetailOverlay').classList.add('open');

        document.getElementById('detail_title').textContent = data.title ?? 'Detail Sesi';
        document.getElementById('detail_sub').textContent = data.kind ?? '-';
        document.getElementById('detail_class').textContent = data.class_name ?? '-';
        document.getElementById('detail_type').textContent = data.kind ?? '-';
        document.getElementById('detail_teacher').textContent = data.teacher_name ?? 'Belum ada guru';
        document.getElementById('detail_time').textContent = `${data.start_time ?? ''} – ${data.end_time ?? ''}`;
        document.getElementById('detail_students').textContent = data.students_count ?? 0;

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

    /* ============================================================
       TOGGLE VIEW: Minggu / Hari (placeholder — perluas sesuai kebutuhan)
    ============================================================ */
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelector('.calendar-card').classList.toggle('view-day', this.dataset.view === 'day');
        });
    });

    function highlightConflicts(e) {
        e.preventDefault();
        document.querySelectorAll('.has-conflict').forEach(el => {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

</script>
@endpush