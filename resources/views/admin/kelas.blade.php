@extends('admin.app')

@section('title', 'Kelas | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/kelas.css') }}">
@endpush

@section('content')

    {{-- ============================================================
         DATA STATIS SEMENTARA — ganti dengan data dari controller
         ($classes, $programs, $programPackages, $teachers, dst.)
         ketika backend sudah siap.
    ============================================================ --}}
    @php
        $programs = [
            (object) ['id' => 1, 'program_name' => 'HSK 1'],
            (object) ['id' => 2, 'program_name' => 'HSK 2'],
            (object) ['id' => 3, 'program_name' => 'HSK 3'],
        ];

        $teachers = [
            (object) ['id' => 1, 'name' => 'Bu Lina'],
            (object) ['id' => 2, 'name' => 'Pak Wijaya'],
        ];

        $programPackages = [
            (object) ['id' => 1, 'package_name' => 'Reguler 12x Pertemuan', 'program' => (object) ['program_name' => 'HSK 1']],
            (object) ['id' => 2, 'package_name' => 'Private 8x Pertemuan', 'program' => (object) ['program_name' => 'HSK 3']],
            (object) ['id' => 3, 'package_name' => 'Reguler 12x Pertemuan', 'program' => (object) ['program_name' => 'HSK 2']],
        ];

        $classes = collect([
            (object) [
                'id' => 1,
                'name' => 'Mandarin Dasar A1 - Sore',
                'code' => 'KLS-001',
                'type' => 'Reguler',
                'status' => 'Aktif',
                'capacity' => 12,
                'students_count' => 10,
                'teacher' => (object) ['id' => 1, 'name' => 'Bu Lina'],
                'programPackage' => (object) [
                    'package_name' => 'Reguler 12x Pertemuan',
                    'program' => (object) ['program_name' => 'HSK 1'],
                ],
                'schedules' => collect([
                    (object) ['day_of_week' => 'Senin', 'start_time' => '15:00', 'end_time' => '16:30'],
                    (object) ['day_of_week' => 'Rabu', 'start_time' => '15:00', 'end_time' => '16:30'],
                ]),
            ],
            (object) [
                'id' => 2,
                'name' => 'Private Conversation - Andi',
                'code' => 'KLS-002',
                'type' => 'Private',
                'status' => 'Aktif',
                'capacity' => 1,
                'students_count' => 1,
                'teacher' => (object) ['id' => 2, 'name' => 'Pak Wijaya'],
                'programPackage' => (object) [
                    'package_name' => 'Private 8x Pertemuan',
                    'program' => (object) ['program_name' => 'HSK 3'],
                ],
                'schedules' => collect([]),
            ],
            (object) [
                'id' => 3,
                'name' => 'Mandarin HSK 2 - Weekend',
                'code' => 'KLS-003',
                'type' => 'Reguler',
                'status' => 'Draft',
                'capacity' => 12,
                'students_count' => 12,
                'teacher' => null,
                'programPackage' => (object) [
                    'package_name' => 'Reguler 12x Pertemuan',
                    'program' => (object) ['program_name' => 'HSK 2'],
                ],
                'schedules' => collect([
                    (object) ['day_of_week' => 'Sabtu', 'start_time' => '09:00', 'end_time' => '10:30'],
                ]),
            ],
        ]);

        $totalKelasAktif = $classes->where('status', 'Aktif')->count();
        $kelasReguler    = $classes->where('type', 'Reguler')->count();
        $kelasPerluGuru  = $classes->whereNull('teacher')->count();
        $kelasPenuh      = $classes->filter(fn ($k) => $k->students_count >= $k->capacity)->count();
        $avgOkupansi     = $classes->count()
            ? round($classes->avg(fn ($k) => $k->capacity ? $k->students_count / $k->capacity * 100 : 0))
            : 0;
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
            <button class="btn">
                <i class="fa-solid fa-file-export"></i>
                Export
            </button>

            <button type="button" class="btn btn-primary" onclick="openKelasModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Kelas
            </button>
        </div>
    </div>

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Total Kelas Aktif</span>
                <i class="fa-solid fa-chalkboard stat-icon"></i>
            </div>
            <div class="stat-value">{{ $totalKelasAktif }}</div>
            <div class="stat-note">Reguler &amp; Private</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Kelas Reguler</span>
                <i class="fa-solid fa-people-group stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasReguler }}</div>
            <div class="stat-note">Jadwal mingguan tetap</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Rata-rata Okupansi</span>
                <i class="fa-solid fa-chart-simple stat-icon"></i>
            </div>
            <div class="stat-value">{{ $avgOkupansi }}%</div>
            <div class="stat-note">Siswa terisi / kapasitas</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Perlu Tindakan</span>
                <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasPerluGuru + $kelasPenuh }}</div>
            <div class="stat-note warn">Butuh guru / kelas penuh</div>
        </div>
    </div>

    {{-- ========================= FILTER (statis, belum submit ke backend) ========================= --}}
    <div class="toolbar">
        <div class="toolbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Cari nama / kode kelas">
        </div>

        <select class="select-chip">
            <option value="">Semua Program</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}">{{ $program->program_name }}</option>
            @endforeach
        </select>

        <select class="select-chip">
            <option value="">Semua Tipe</option>
            <option value="Reguler">Reguler</option>
            <option value="Private">Private</option>
        </select>

        <select class="select-chip">
            <option value="">Semua Guru</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
            @endforeach
        </select>

        <select class="select-chip">
            <option value="">Semua Status</option>
            <option value="Draft">Draft</option>
            <option value="Aktif">Aktif</option>
            <option value="Selesai">Selesai</option>
        </select>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Program &amp; Paket</th>
                    <th>Tipe</th>
                    <th>Guru</th>
                    <th>Jadwal Mingguan</th>
                    <th>Kapasitas</th>
                    <th>Status</th>
                    <th width="140" style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($classes as $kelas)
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($kelas->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $kelas->name }}</div>
                                    <div class="cand-sub">{{ $kelas->code ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            {{ $kelas->programPackage->program->program_name ?? '-' }}
                            <div class="cand-sub">{{ $kelas->programPackage->package_name ?? '' }}</div>
                        </td>

                        <td>
                            @php $typeClass = $kelas->type === 'Private' ? 'badge-private' : 'badge-reguler'; @endphp
                            <span class="badge {{ $typeClass }}">
                                <span class="badge-dot"></span>
                                {{ $kelas->type }}
                            </span>
                        </td>

                        <td>
                            @if ($kelas->teacher)
                                {{ $kelas->teacher->name }}
                            @else
                                <span style="color:#dc2626;font-weight:600;">Belum ada guru</span>
                            @endif
                        </td>

                        <td>
                            @if ($kelas->type === 'Reguler' && $kelas->schedules->count())
                                <div class="schedule-chips">
                                    @foreach ($kelas->schedules as $s)
                                        <span class="schedule-chip">
                                            {{ substr($s->day_of_week, 0, 3) }} {{ $s->start_time }}–{{ $s->end_time }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="cand-sub">Fleksibel per sesi</span>
                            @endif
                        </td>

                        <td>
                            @php $full = $kelas->students_count >= $kelas->capacity; @endphp
                            <span class="{{ $full ? 'capacity-full' : 'capacity-ok' }}">
                                {{ $kelas->students_count }}/{{ $kelas->capacity }}
                            </span>
                        </td>

                        <td>
                            @php
                                $statusClass = match ($kelas->status) {
                                    'Draft' => 'badge-pending',
                                    'Aktif' => 'badge-completed',
                                    'Selesai' => 'badge-cold',
                                    default => '',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                <span class="badge-dot"></span>
                                {{ $kelas->status }}
                            </span>
                        </td>

                        <td>
                            <div class="row-actions">
                                <button
                                    type="button"
                                    class="icon-btn"
                                    title="Edit"
                                    data-kelas='@json($kelas)'
                                    onclick="openEditKelasModal(this)">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <button type="button" class="icon-btn" title="Kelola Siswa">
                                    <i class="fa-solid fa-users-gear"></i>
                                </button>

                                <button type="button" class="icon-btn" title="Lihat di Jadwal">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data kelas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan <strong>{{ $classes->count() }}</strong> dari <strong>{{ $classes->count() }}</strong> kelas
            </span>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH KELAS ========================= --}}
    <div class="modal-overlay" id="kelasModalOverlay">
        <div class="modal">
            <form onsubmit="return false;">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Kelas Baru</div>
                        <div class="modal-sub">Buat kelas Reguler atau Private beserta jadwalnya.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeKelasModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-chalkboard"></i> Informasi Kelas</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Kelas *</label>
                                <input type="text" name="name" required placeholder="mis. Mandarin Dasar A1 - Sore">
                            </div>
                            <div class="form-field">
                                <label>Tipe Kelas *</label>
                                <div class="form-radio-group">
                                    <label class="form-radio">
                                        <input type="radio" name="type" value="Reguler" checked onchange="toggleScheduleSection('add')"> Reguler
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="type" value="Private" onchange="toggleScheduleSection('add')"> Private
                                    </label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Kapasitas Maksimal *</label>
                                <input type="number" name="capacity" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Mulai *</label>
                                <input type="date" name="start_date" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Selesai <span class="opt">(opsional)</span></label>
                                <input type="date" name="end_date">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program &amp; Guru</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Paket Program *</label>
                                <select name="program_package_id" required>
                                    <option value="">Pilih Paket Program</option>
                                    @foreach ($programPackages as $package)
                                        <option value="{{ $package->id }}">
                                            {{ $package->program->program_name }} — {{ $package->package_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field full">
                                <label>Guru Pengampu</label>
                                <select name="teacher_id">
                                    <option value="">Belum Ditentukan</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="scheduleSection_add">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Mingguan</div>
                        <div id="scheduleRowsAdd">
                            <div class="schedule-row">
                                <div class="form-field">
                                    <label>Hari</label>
                                    <select name="day[]">
                                        <option>Senin</option><option>Selasa</option><option>Rabu</option>
                                        <option>Kamis</option><option>Jumat</option><option>Sabtu</option>
                                    </select>
                                </div>
                                <div class="form-field"><label>Jam Mulai</label><input type="time" name="start_time[]"></div>
                                <div class="form-field"><label>Jam Selesai</label><input type="time" name="end_time[]"></div>
                                <button type="button" class="schedule-remove" onclick="this.closest('.schedule-row').remove()"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" class="add-schedule-btn" onclick="addScheduleRow('scheduleRowsAdd')">
                            <i class="fa-solid fa-plus"></i> Tambah Jadwal
                        </button>
                        <div class="auto-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Jadwal di atas akan otomatis digenerate menjadi sesi pertemuan mingguan pada halaman Jadwal.
                        </div>
                    </div>

                    <div class="form-section" id="privateNote_add" style="display:none;">
                        <div class="auto-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Kelas Private tidak memakai jadwal mingguan tetap — sesi pertemuan diatur satu per satu di halaman Jadwal.
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn" onclick="closeKelasModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT KELAS ========================= --}}
    <div class="modal-overlay" id="editKelasModalOverlay">
        <div class="modal">
            <form onsubmit="return false;">
                @csrf
                @method('PUT')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Kelas</div>
                        <div class="modal-sub">Perbarui data kelas ini.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeEditKelasModal()" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body" id="editKelasModalBody">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-chalkboard"></i> Informasi Kelas</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Kelas *</label>
                                <input type="text" name="name" id="edit_kelas_name" required>
                            </div>
                            <div class="form-field">
                                <label>Tipe Kelas *</label>
                                <div class="form-radio-group">
                                    <label class="form-radio">
                                        <input type="radio" name="type" value="Reguler" id="edit_type_reguler" onchange="toggleScheduleSection('edit')"> Reguler
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="type" value="Private" id="edit_type_private" onchange="toggleScheduleSection('edit')"> Private
                                    </label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Kapasitas Maksimal *</label>
                                <input type="number" name="capacity" id="edit_capacity" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Status *</label>
                                <select name="status" id="edit_status" required>
                                    <option value="Draft">Draft</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program &amp; Guru</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Paket Program *</label>
                                <select name="program_package_id" id="edit_program_package_id" required>
                                    <option value="">Pilih Paket Program</option>
                                    @foreach ($programPackages as $package)
                                        <option value="{{ $package->id }}">
                                            {{ $package->program->program_name }} — {{ $package->package_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field full">
                                <label>Guru Pengampu</label>
                                <select name="teacher_id" id="edit_teacher_id">
                                    <option value="">Belum Ditentukan</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="scheduleSection_edit">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Mingguan</div>
                        <div id="scheduleRowsEdit"></div>
                        <button type="button" class="add-schedule-btn" onclick="addScheduleRow('scheduleRowsEdit')">
                            <i class="fa-solid fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>

                    <div class="form-section" id="privateNote_edit" style="display:none;">
                        <div class="auto-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Kelas Private tidak memakai jadwal mingguan tetap — sesi pertemuan diatur satu per satu di halaman Jadwal.
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div class="modal-foot-left">
                        <button type="button" class="btn btn-convert">
                            <i class="fa-solid fa-calendar-days"></i>
                            Lihat di Jadwal
                        </button>
                    </div>
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeEditKelasModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>

    /* ============================================================
       MODAL: TAMBAH KELAS
    ============================================================ */
    function openKelasModal() {
        document.getElementById('kelasModalOverlay').classList.add('open');
    }
    function closeKelasModal() {
        document.getElementById('kelasModalOverlay').classList.remove('open');
    }
    document.getElementById('kelasModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeKelasModal();
    });

    /* ============================================================
       TOGGLE JADWAL MINGGUAN — disembunyikan untuk tipe Private
    ============================================================ */
    function toggleScheduleSection(context) {
        const form = context === 'add'
            ? document.getElementById('kelasModalOverlay')
            : document.getElementById('editKelasModalOverlay');

        const checked = form.querySelector('input[name="type"]:checked');
        const isPrivate = checked && checked.value === 'Private';

        document.getElementById('scheduleSection_' + context).style.display = isPrivate ? 'none' : '';
        document.getElementById('privateNote_' + context).style.display = isPrivate ? '' : 'none';
    }

    /* ============================================================
       JADWAL MINGGUAN — baris dinamis, dipakai bareng Tambah & Edit
    ============================================================ */
    function addScheduleRow(containerId, values = {}) {
        const wrap = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'schedule-row';
        row.innerHTML = `
            <div class="form-field">
                <label>Hari</label>
                <select name="day[]">
                    <option>Senin</option>
                    <option>Selasa</option>
                    <option>Rabu</option>
                    <option>Kamis</option>
                    <option>Jumat</option>
                    <option>Sabtu</option>
                </select>
            </div>
            <div class="form-field">
                <label>Jam Mulai</label>
                <input type="time" name="start_time[]">
            </div>
            <div class="form-field">
                <label>Jam Selesai</label>
                <input type="time" name="end_time[]">
            </div>
            <button type="button" class="schedule-remove" onclick="this.closest('.schedule-row').remove()">
                <i class="fa-solid fa-trash"></i>
            </button>`;

        if (values.day) row.querySelector('select[name="day[]"]').value = values.day;
        if (values.start_time) row.querySelector('input[name="start_time[]"]').value = values.start_time;
        if (values.end_time) row.querySelector('input[name="end_time[]"]').value = values.end_time;

        wrap.appendChild(row);
    }

    /* ============================================================
       MODAL: EDIT KELAS — data diambil dari atribut data-kelas
       (statis, tanpa fetch ke backend)
    ============================================================ */
    function openEditKelasModal(button) {
        const data = JSON.parse(button.dataset.kelas);
        const overlay = document.getElementById('editKelasModalOverlay');
        overlay.classList.add('open');

        document.getElementById('scheduleRowsEdit').innerHTML = '';

        document.getElementById('edit_kelas_name').value = data.name ?? '';
        document.getElementById(data.type === 'Private' ? 'edit_type_private' : 'edit_type_reguler').checked = true;
        document.getElementById('edit_capacity').value = data.capacity ?? '';
        document.getElementById('edit_status').value = data.status ?? 'Draft';
        document.getElementById('edit_teacher_id').value = data.teacher?.id ?? '';

        if (data.type === 'Reguler' && data.schedules && data.schedules.length > 0) {
            data.schedules.forEach(s => addScheduleRow('scheduleRowsEdit', {
                day: s.day_of_week, start_time: s.start_time, end_time: s.end_time
            }));
        } else if (data.type === 'Reguler') {
            addScheduleRow('scheduleRowsEdit');
        }

        toggleScheduleSection('edit');
    }

    function closeEditKelasModal() {
        document.getElementById('editKelasModalOverlay').classList.remove('open');
    }
    document.getElementById('editKelasModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeEditKelasModal();
    });

</script>
@endpush