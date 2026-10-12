@extends('admin.app')

@section('title', 'Kelas | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/kelas.css') }}">
    <style>
        /* Baris jadwal punya 4 field (hari, mulai, selesai, ruangan) + tombol hapus */
        .schedule-row { display: flex; gap: 10px; align-items: flex-end; }
        .schedule-row .form-field { flex: 1; min-width: 0; }
        .schedule-row .schedule-remove { flex: none; }
    </style>
@endpush

@section('content')

    {{-- Saran nama ruangan (diambil dari yang pernah dipakai; nama ruangan boleh diketik bebas) --}}
    <datalist id="roomSuggestions">
        @foreach ($roomSuggestions as $room)
            <option value="{{ $room }}"></option>
        @endforeach
    </datalist>

    @php
        $statusBadge = [
            'Open'      => 'badge-pending',
            'Running'   => 'badge-completed',
            'Completed' => 'badge-cold',
            'Closed'    => 'badge-cancelled',
        ];
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

    @if (session('success'))
        <div class="alert-success" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            <strong>Data belum bisa disimpan:</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Daily Activity</span>
                <i class="fa-solid fa-child stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasDailyActivity }}</div>
            <div class="stat-note">Kelas aktif</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">HSK</span>
                <i class="fa-solid fa-language stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasHsk }}</div>
            <div class="stat-note">Kelas aktif</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Private</span>
                <i class="fa-solid fa-user-tie stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasPrivate }}</div>
            <div class="stat-note">Kelas aktif</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Kelas Aktif</span>
                <i class="fa-solid fa-chalkboard stat-icon"></i>
            </div>
            <div class="stat-value">{{ $kelasAktif }}</div>
            <div class="stat-note">Status Open &amp; Running</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.kelas') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas">
            </div>

            <select name="program_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Program</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->program_name }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="Reguler" {{ request('type') == 'Reguler' ? 'selected' : '' }}>Reguler</option>
                <option value="Private" {{ request('type') == 'Private' ? 'selected' : '' }}>Private</option>
            </select>

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach (['Open', 'Running', 'Completed', 'Closed'] as $st)
                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>
    </form>

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
                    @php
                        $isPrivate = ! is_null($kelas->private_package_id);

                        $editData = [
                            'id'                 => $kelas->id,
                            'update_url'         => route('admin.kelas.update', $kelas->id),
                            'class_name'         => $kelas->class_name,
                            'type'               => $isPrivate ? 'Private' : 'Reguler',
                            'delivery_mode'      => $kelas->delivery_mode,
                            'capacity'           => $kelas->capacity,
                            'status'             => $kelas->status,
                            'start_date'         => $kelas->start_date ? \Illuminate\Support\Carbon::parse($kelas->start_date)->format('Y-m-d') : '',
                            'end_date'           => $kelas->end_date ? \Illuminate\Support\Carbon::parse($kelas->end_date)->format('Y-m-d') : '',
                            'program_package_id' => $kelas->program_package_id,
                            'private_package_id' => $kelas->private_package_id,
                            'level_id'           => $kelas->level_id,
                            'teacher_id'         => $kelas->teacher_id,
                            'students_count'     => $kelas->students_count,
                            'schedules'          => $kelas->schedules->map(fn ($s) => [
                                'id'         => $s->id,
                                'day'        => $s->day,
                                'start_time' => substr($s->start_time, 0, 5),
                                'end_time'   => substr($s->end_time, 0, 5),
                                'room'       => $s->room,
                            ])->values(),
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($kelas->class_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $kelas->class_name }}</div>
                                    <div class="cand-sub">
                                        @if ($kelas->level?->level_name)
                                            Level {{ $kelas->level->level_name }} &middot;
                                        @endif
                                        {{ $kelas->delivery_mode }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            @if ($isPrivate)
                                Private
                                <div class="cand-sub">{{ $kelas->privatePackage?->package_name ?? '-' }}</div>
                            @else
                                {{ $kelas->programPackage?->program?->program_name ?? '-' }}
                                <div class="cand-sub">{{ $kelas->programPackage?->package_name ?? '' }}</div>
                            @endif
                        </td>

                        <td>
                            <span class="badge {{ $isPrivate ? 'badge-private' : 'badge-reguler' }}">
                                <span class="badge-dot"></span>
                                {{ $isPrivate ? 'Private' : 'Reguler' }}
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
                            @if ($kelas->schedules->count())
                                <div class="schedule-chips">
                                    @foreach ($kelas->schedules as $s)
                                        <span class="schedule-chip">
                                            {{ substr($s->day, 0, 3) }} {{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="cand-sub">Belum ada jadwal</span>
                            @endif
                        </td>

                        <td>
                            @php $full = $kelas->students_count >= $kelas->capacity; @endphp
                            <span class="{{ $full ? 'capacity-full' : 'capacity-ok' }}">
                                {{ $kelas->students_count }}/{{ $kelas->capacity }}
                            </span>
                        </td>

                        <td>
                            <span class="badge {{ $statusBadge[$kelas->status] ?? '' }}">
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
                                    data-kelas-id="{{ $kelas->id }}"
                                    data-kelas="{{ json_encode($editData) }}"
                                    onclick="openEditKelasModal(this)">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <button
                                    type="button"
                                    class="icon-btn"
                                    title="Kelola Siswa"
                                    onclick="openManageStudentsModal({{ $kelas->id }}, @js($kelas->class_name))">
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
                Menampilkan
                <strong>{{ $classes->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $classes->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $classes->total() }}</strong>
                kelas
            </span>

            <div class="table-footer-pagination">
                @if ($classes->hasPages())
                    @php
                        $current = $classes->currentPage();
                        $last = $classes->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($classes->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $classes->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                            @endif

                            @for ($page = 1; $page <= $last; $page++)
                                @php
                                    $isEdge = $page == 1 || $page == $last;
                                    $isNearCurrent = abs($page - $current) <= $onEachSide;
                                @endphp

                                @if ($isEdge || $isNearCurrent)
                                    @if ($page == $current)
                                        <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-btn" href="{{ $classes->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($classes->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $classes->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH KELAS ========================= --}}
    <div class="modal-overlay" id="kelasModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.kelas.store') }}" method="POST" id="addKelasForm">
                @csrf
                <input type="hidden" name="_form" value="add">

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
                                <input type="text" name="class_name" id="add_class_name" required placeholder="mis. Maochong 1A">
                            </div>
                            <div class="form-field">
                                <label>Tipe Kelas *</label>
                                <div class="form-radio-group">
                                    <label class="form-radio">
                                        <input type="radio" name="type" value="Reguler" id="add_type_reguler" checked onchange="toggleKelasType('add')"> Reguler
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="type" value="Private" id="add_type_private" onchange="toggleKelasType('add')"> Private
                                    </label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Mode Kelas *</label>
                                <select name="delivery_mode" id="add_delivery_mode" required onchange="syncRooms('add')">
                                    <option value="Offline">Offline</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Kapasitas Maksimal *</label>
                                <input type="number" name="capacity" id="add_capacity" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Mulai *</label>
                                <input type="date" name="start_date" id="add_start_date" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Selesai <span class="opt">(opsional)</span></label>
                                <input type="date" name="end_date" id="add_end_date">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program &amp; Guru</div>
                        <div class="form-grid">

                            {{-- Reguler --}}
                            <div class="form-field full" id="add_regular_fields">
                                <label>Paket Program *</label>
                                <select name="program_package_id" id="add_program_package_id" required onchange="onPackageChange('add')">
                                    <option value="">Pilih Paket Program</option>
                                    @foreach ($programPackages as $package)
                                        @continue(! $package->is_active)
                                        <option value="{{ $package->id }}"
                                                data-program-id="{{ $package->program_id }}"
                                                data-level-id="{{ $package->level_id }}"
                                                data-max="{{ $package->max_students }}">
                                            {{ $package->program?->program_name }} — {{ $package->package_name }}
                                        </option>
                                    @endforeach
                                </select>

                                <label style="margin-top:12px;">Level *</label>
                                <select name="level_id" id="add_level_id" required>
                                    <option value="">Pilih Level</option>
                                    @foreach ($levels as $level)
                                        <option value="{{ $level->id }}" data-program-id="{{ $level->program_id }}">
                                            {{ $level->category_name ? $level->category_name . ' — ' : '' }}{{ $level->level_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="form-hint">Level otomatis terisi untuk paket HSK. Untuk Daily Activity, pilih sesuai kelasnya.</span>
                            </div>

                            {{-- Private --}}
                            <div class="form-field full" id="add_private_fields" style="display:none;">
                                <label>Paket Private *</label>
                                <select name="private_package_id" id="add_private_package_id" onchange="onPackageChange('add')">
                                    <option value="">Pilih Paket Private</option>
                                    @foreach ($privatePackages as $package)
                                        @continue(! $package->is_active)
                                        <option value="{{ $package->id }}" data-max="{{ $package->max_students }}">
                                            {{ $package->package_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field full">
                                <label>Guru Pengampu</label>
                                <select name="teacher_id" id="add_teacher_id">
                                    <option value="">Belum Ditentukan</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Mingguan</div>
                        <div id="add_schedule_rows"></div>
                        <button type="button" class="add-schedule-btn" onclick="addScheduleRow('add_schedule_rows')">
                            <i class="fa-solid fa-plus"></i> Tambah Jadwal
                        </button>
                        <div class="auto-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Jadwal ini menjadi pola sesi mingguan kelas dan dipakai untuk mencatat jurnal mengajar.
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
            <form method="POST" id="editKelasForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_kelas_id" id="edit_kelas_id">

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
                                <input type="text" name="class_name" id="edit_class_name" required>
                            </div>
                            <div class="form-field">
                                <label>Tipe Kelas</label>
                                <div class="form-radio-group">
                                    <label class="form-radio">
                                        <input type="radio" name="edit_type_display" id="edit_type_reguler" disabled> Reguler
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="edit_type_display" id="edit_type_private" disabled> Private
                                    </label>
                                </div>
                                <span class="form-hint">Tipe dan paket tidak dapat diubah setelah kelas dibuat.</span>
                            </div>
                            <div class="form-field">
                                <label>Mode Kelas *</label>
                                <select name="delivery_mode" id="edit_delivery_mode" required onchange="syncRooms('edit')">
                                    <option value="Offline">Offline</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Kapasitas Maksimal *</label>
                                <input type="number" name="capacity" id="edit_capacity" min="1" required>
                                <span class="form-hint" id="edit_capacity_hint"></span>
                            </div>
                            <div class="form-field">
                                <label>Status *</label>
                                <select name="status" id="edit_status" required>
                                    <option value="Open">Open</option>
                                    <option value="Running">Running</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Mulai *</label>
                                <input type="date" name="start_date" id="edit_start_date" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Selesai <span class="opt">(opsional)</span></label>
                                <input type="date" name="end_date" id="edit_end_date">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program &amp; Guru</div>
                        <div class="form-grid">

                            {{-- Reguler --}}
                            <div class="form-field full" id="edit_regular_fields">
                                <label>Paket Program</label>
                                <select id="edit_program_package_id" disabled onchange="onPackageChange('edit')">
                                    <option value="">-</option>
                                    @foreach ($programPackages as $package)
                                        <option value="{{ $package->id }}"
                                                data-program-id="{{ $package->program_id }}"
                                                data-level-id="{{ $package->level_id }}"
                                                data-max="{{ $package->max_students }}">
                                            {{ $package->program?->program_name }} — {{ $package->package_name }}
                                        </option>
                                    @endforeach
                                </select>

                                <label style="margin-top:12px;">Level *</label>
                                <select name="level_id" id="edit_level_id" required>
                                    <option value="">Pilih Level</option>
                                    @foreach ($levels as $level)
                                        <option value="{{ $level->id }}" data-program-id="{{ $level->program_id }}">
                                            {{ $level->category_name ? $level->category_name . ' — ' : '' }}{{ $level->level_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Private --}}
                            <div class="form-field full" id="edit_private_fields" style="display:none;">
                                <label>Paket Private</label>
                                <select id="edit_private_package_id" disabled onchange="onPackageChange('edit')">
                                    <option value="">-</option>
                                    @foreach ($privatePackages as $package)
                                        <option value="{{ $package->id }}" data-max="{{ $package->max_students }}">
                                            {{ $package->package_name }}
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

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Mingguan</div>
                        <div id="edit_schedule_rows"></div>
                        <button type="button" class="add-schedule-btn" onclick="addScheduleRow('edit_schedule_rows')">
                            <i class="fa-solid fa-plus"></i> Tambah Jadwal
                        </button>
                        <div class="auto-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Jadwal yang sudah punya jurnal mengajar tidak bisa dihapus, hanya bisa diubah hari/jamnya.
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

    {{-- ========================= MODAL — KELOLA SISWA ========================= --}}
    <div class="modal-overlay" id="manageStudentsOverlay">
        <div class="modal modal-lg">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Kelola Siswa</div>
                    <div class="modal-sub" id="ms_kelas_name">-</div>
                </div>
                <button type="button" class="modal-close" onclick="closeManageStudentsModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div class="ms-capacity" id="ms_capacity_banner">Memuat data kelas...</div>

                <div class="ms-tabs">
                    <button type="button" class="ms-tab active" data-tab="waiting" onclick="switchStudentTab('waiting')">
                        Dari Daftar Tunggu
                    </button>
                    <button type="button" class="ms-tab" data-tab="new" onclick="switchStudentTab('new')">
                        Siswa Baru
                    </button>
                </div>

                <div class="ms-pane" data-pane="waiting">
                    <div class="ms-list" id="ms_waiting_list">
                        <div class="ms-empty">Memuat...</div>
                    </div>
                </div>

                <div class="ms-pane" data-pane="new" style="display:none;">
                    <div class="toolbar-search" style="margin-bottom:12px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="ms_search_input" placeholder="Ketik nama siswa...">
                    </div>
                    <div class="ms-list" id="ms_new_list">
                        <div class="ms-empty">Memuat...</div>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn" onclick="closeManageStudentsModal()">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    /* ============================================================
       FILTER: pencarian otomatis
    ============================================================ */
    (function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { filterForm.submit(); }, 600);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                filterForm.submit();
            }
        });
    })();

    /* ============================================================
       HELPER FORM (dipakai bareng modal Tambah "add" & Edit "edit")
    ============================================================ */
    let scheduleIndex = 0;

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value ?? '';
    }

    function setControlsDisabled(container, disabled) {
        container.querySelectorAll('select, input').forEach(el => { el.disabled = disabled; });
    }

    // Tampilkan field sesuai tipe (Reguler / Private)
    function toggleKelasType(prefix) {
        const isPrivate = document.getElementById(prefix + '_type_private').checked;
        const regular = document.getElementById(prefix + '_regular_fields');
        const priv = document.getElementById(prefix + '_private_fields');

        regular.style.display = isPrivate ? 'none' : '';
        priv.style.display = isPrivate ? '' : 'none';

        // Field yang disembunyikan dinonaktifkan supaya tidak memblokir submit (required) & tidak terkirim
        setControlsDisabled(regular, isPrivate);
        setControlsDisabled(priv, ! isPrivate);

        // Saat edit, paket selalu terkunci
        if (prefix === 'edit') {
            document.getElementById('edit_program_package_id').disabled = true;
            document.getElementById('edit_private_package_id').disabled = true;
        }

        onPackageChange(prefix, true);
    }

    // Saat paket berubah: batasi kapasitas, filter level sesuai program, isi otomatis level HSK
    function onPackageChange(prefix, keepLevel = false) {
        const isPrivate = document.getElementById(prefix + '_type_private').checked;
        const pkgSelect = document.getElementById(prefix + (isPrivate ? '_private_package_id' : '_program_package_id'));
        const opt = pkgSelect.selectedOptions[0];
        const capacity = document.getElementById(prefix + '_capacity');

        if (opt && opt.value && opt.dataset.max) {
            capacity.max = opt.dataset.max;
            capacity.placeholder = 'Maks. ' + opt.dataset.max;
        } else {
            capacity.removeAttribute('max');
            capacity.placeholder = '';
        }

        if (isPrivate) return;

        const programId = opt && opt.value ? opt.dataset.programId : '';
        const levelSelect = document.getElementById(prefix + '_level_id');

        Array.from(levelSelect.options).forEach(o => {
            if (! o.value) return;
            const match = programId && o.dataset.programId === programId;
            o.hidden = ! match;
            o.disabled = ! match;
        });

        if (! keepLevel) {
            const fixedLevel = opt && opt.value ? opt.dataset.levelId : '';
            levelSelect.value = fixedLevel || '';
        }
    }

    // Ruangan disembunyikan untuk kelas Online (server otomatis mengisi "Online")
    function syncRooms(prefix) {
        const online = document.getElementById(prefix + '_delivery_mode').value === 'Online';
        document.querySelectorAll('#' + prefix + '_schedule_rows .room-field').forEach(el => {
            el.style.display = online ? 'none' : '';
        });
    }

    function addScheduleRow(containerId, values = {}) {
        const wrap = document.getElementById(containerId);
        const prefix = containerId.replace('_schedule_rows', '');
        const i = scheduleIndex++;

        const row = document.createElement('div');
        row.className = 'schedule-row';
        row.innerHTML = `
            <input type="hidden" name="schedules[${i}][id]" value="${values.id ?? ''}">
            <div class="form-field">
                <label>Hari</label>
                <select name="schedules[${i}][day]" required>
                    <option>Senin</option><option>Selasa</option><option>Rabu</option>
                    <option>Kamis</option><option>Jumat</option><option>Sabtu</option><option>Minggu</option>
                </select>
            </div>
            <div class="form-field">
                <label>Jam Mulai</label>
                <input type="time" name="schedules[${i}][start_time]" required>
            </div>
            <div class="form-field">
                <label>Jam Selesai</label>
                <input type="time" name="schedules[${i}][end_time]" required>
            </div>
            <div class="form-field room-field">
                <label>Ruangan</label>
                <input type="text" name="schedules[${i}][room]" list="roomSuggestions" placeholder="mis. Ruang A" maxlength="255">
            </div>
            <button type="button" class="schedule-remove" onclick="this.closest('.schedule-row').remove()">
                <i class="fa-solid fa-trash"></i>
            </button>`;

        if (values.day) row.querySelector('select[name$="[day]"]').value = values.day;
        if (values.start_time) row.querySelector('input[name$="[start_time]"]').value = values.start_time;
        if (values.end_time) row.querySelector('input[name$="[end_time]"]').value = values.end_time;

        const roomInput = row.querySelector('input[name$="[room]"]');
        if (values.room && values.room !== 'Online') {
            roomInput.value = values.room;
        }

        wrap.appendChild(row);
        syncRooms(prefix);
    }

    // Isi seluruh field form dari satu objek data
    function fillKelasForm(prefix, data) {
        setValue(prefix + '_class_name', data.class_name);
        document.getElementById(prefix + (data.type === 'Private' ? '_type_private' : '_type_reguler')).checked = true;
        setValue(prefix + '_delivery_mode', data.delivery_mode || 'Offline');
        setValue(prefix + '_capacity', data.capacity);
        setValue(prefix + '_start_date', data.start_date);
        setValue(prefix + '_end_date', data.end_date);
        setValue(prefix + '_status', data.status || 'Open');
        setValue(prefix + '_teacher_id', data.teacher_id);
        setValue(prefix + '_program_package_id', data.program_package_id);
        setValue(prefix + '_private_package_id', data.private_package_id);

        toggleKelasType(prefix);
        onPackageChange(prefix, true);
        setValue(prefix + '_level_id', data.level_id);

        const rows = document.getElementById(prefix + '_schedule_rows');
        rows.innerHTML = '';
        const schedules = Object.values(data.schedules || {});
        if (schedules.length) {
            schedules.forEach(s => addScheduleRow(prefix + '_schedule_rows', s));
        } else {
            addScheduleRow(prefix + '_schedule_rows');
        }

        if (prefix === 'edit') {
            const hint = document.getElementById('edit_capacity_hint');
            hint.textContent = data.students_count !== undefined
                ? 'Siswa terdaftar saat ini: ' + data.students_count
                : '';
        }
    }

    /* ============================================================
       MODAL: TAMBAH KELAS
    ============================================================ */
    function openKelasModal() {
        fillKelasForm('add', { type: 'Reguler', delivery_mode: 'Offline', schedules: [] });
        document.getElementById('kelasModalOverlay').classList.add('open');
    }
    function closeKelasModal() {
        document.getElementById('kelasModalOverlay').classList.remove('open');
    }
    document.getElementById('kelasModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeKelasModal();
    });

    /* ============================================================
       MODAL: EDIT KELAS
    ============================================================ */
    function openEditKelasModal(button) {
        const data = JSON.parse(button.dataset.kelas);

        document.getElementById('editKelasForm').action = data.update_url;
        document.getElementById('edit_kelas_id').value = data.id;
        fillKelasForm('edit', data);

        document.getElementById('editKelasModalOverlay').classList.add('open');
    }
    function closeEditKelasModal() {
        document.getElementById('editKelasModalOverlay').classList.remove('open');
    }
    document.getElementById('editKelasModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeEditKelasModal();
    });

    /* ============================================================
       MODAL: KELOLA SISWA
    ============================================================ */
    let msKelasId = null;
    let msIsFull = false;
    let msSearchTimer;
    let msEnrolledStudents = [];

    function openManageStudentsModal(kelasId, kelasName) {
        msKelasId = kelasId;
        msIsFull = false;
        msEnrolledStudents = [];

        document.getElementById('ms_kelas_name').textContent = kelasName;
        document.getElementById('ms_capacity_banner').textContent = 'Memuat data kelas...';
        document.getElementById('ms_capacity_banner').className = 'ms-capacity';
        document.getElementById('ms_waiting_list').innerHTML = '<div class="ms-empty">Memuat...</div>';
        document.getElementById('ms_new_list').innerHTML = '<div class="ms-empty">Memuat...</div>';
        document.getElementById('ms_search_input').value = '';

        switchStudentTab('waiting');
        document.getElementById('manageStudentsOverlay').classList.add('open');

        fetch(`/admin/kelas/${kelasId}/siswa`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                msIsFull = data.kelas.is_full;
                renderCapacityBanner(data.kelas);

                // Siswa yang sudah terdaftar ditampilkan di tab "Siswa Baru" (nonaktif, tanpa jadwal)
                msEnrolledStudents = data.enrolled.map(s => ({
                    student_id: s.student_id,
                    name: s.name,
                    already_in_class: true,
                    schedules: [],
                }));
                renderNewListDefault();

                const list = document.getElementById('ms_waiting_list');
                if (! data.waiting.length) {
                    list.innerHTML = '<div class="ms-empty">Tidak ada siswa di daftar tunggu untuk program/level ini.</div>';
                    return;
                }
                list.innerHTML = data.waiting.map(s => renderStudentItem(s, 'waiting')).join('');
            })
            .catch(() => {
                document.getElementById('ms_waiting_list').innerHTML = '<div class="ms-empty">Gagal memuat data.</div>';
                document.getElementById('ms_new_list').innerHTML = '<div class="ms-empty">Gagal memuat data.</div>';
            });
    }

    function formatScheduleList(schedules) {
        if (! schedules || ! schedules.length) return 'Belum ada jadwal tersimpan';
        return schedules.map(s => `${s.day} ${s.start_time}–${s.end_time}`).join(', ');
    }

    // Tampilan awal tab "Siswa Baru": daftar siswa yang sudah terdaftar (nonaktif),
    // sebelum admin mengetik pencarian siswa lain.
    function renderNewListDefault() {
        const list = document.getElementById('ms_new_list');
        const hint = '<div class="ms-new-hint">Ketik nama untuk mencari siswa lain yang bisa ditambahkan.</div>';

        if (! msEnrolledStudents.length) {
            list.innerHTML = hint + '<div class="ms-empty">Belum ada siswa di kelas ini.</div>';
            return;
        }

        list.innerHTML = hint + msEnrolledStudents.map(s => renderStudentItem(s, 'new')).join('');
    }

    function formatScheduleList(schedules) {
        if (! schedules || ! schedules.length) return 'Belum ada jadwal tersimpan';
        return schedules.map(s => `${s.day} ${s.start_time}–${s.end_time}`).join(', ');
    }

    function renderEnrolledItem(s) {
        const statusLabel = s.status === 'Completed' ? 'Selesai' : 'Aktif';
        return `<div class="ms-item">
            <div>
                <div class="ms-item-name">${escapeHtml(s.name)}</div>
                <div class="ms-item-schedule">${escapeHtml(formatScheduleList(s.schedules))}</div>
            </div>
            <span class="ms-status-chip">${statusLabel}</span>
        </div>`;
    }

    function renderCapacityBanner(kelas) {
        const el = document.getElementById('ms_capacity_banner');
        el.textContent = `Siswa terdaftar: ${kelas.students_count}/${kelas.capacity}`
            + (kelas.is_full ? ' — Kelas penuh, tidak bisa menambah siswa lagi.' : '');
        el.className = 'ms-capacity' + (kelas.is_full ? ' is-full' : '');
    }

    function closeManageStudentsModal() {
        document.getElementById('manageStudentsOverlay').classList.remove('open');
    }
    document.getElementById('manageStudentsOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeManageStudentsModal();
    });

    function switchStudentTab(tab) {
        document.querySelectorAll('.ms-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
        document.querySelectorAll('.ms-pane').forEach(p => p.style.display = p.dataset.pane === tab ? '' : 'none');
    }

    document.getElementById('ms_search_input').addEventListener('input', function () {
        clearTimeout(msSearchTimer);
        const q = this.value.trim();
        msSearchTimer = setTimeout(() => searchNewStudents(q), 400);
    });

    function searchNewStudents(q) {
        const list = document.getElementById('ms_new_list');

        if (! q) {
            renderNewListDefault();
            return;
        }
        list.innerHTML = '<div class="ms-empty">Mencari...</div>';

        fetch(`/admin/kelas/${msKelasId}/siswa/cari?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (! data.students.length) {
                    list.innerHTML = '<div class="ms-empty">Tidak ada siswa yang cocok.</div>';
                    return;
                }
                list.innerHTML = data.students.map(s => renderStudentItem(s, 'new')).join('');
            })
            .catch(() => {
                list.innerHTML = '<div class="ms-empty">Gagal mencari data.</div>';
            });
    }

function renderStudentItem(s, source) {
    const disabled = s.already_in_class || msIsFull;
    const label = s.already_in_class ? 'Sudah di kelas ini' : (msIsFull ? 'Kelas penuh' : 'Tambahkan');
    const idValue = source === 'waiting' ? s.enrollment_id : s.student_id;

    // Jadwal hanya relevan untuk siswa yang BELUM masuk kelas ini
    const scheduleHtml = s.already_in_class
        ? ''
        : `<div class="ms-item-schedule">${escapeHtml(formatScheduleList(s.schedules))}</div>`;

    return `<div class="ms-item">
        <div>
            <div class="ms-item-name">${escapeHtml(s.name)}</div>
            ${s.join_date ? `<div class="ms-item-sub">Bergabung ${s.join_date}</div>` : ''}
            ${scheduleHtml}
        </div>
        <button type="button" class="btn btn-primary btn-sm" ${disabled ? 'disabled' : ''}
            onclick="submitEnroll('${source}', ${idValue}, this)">
            ${label}
        </button>
    </div>`;
}

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function submitEnroll(source, id, button) {
        button.disabled = true;
        button.textContent = 'Menyimpan...';

        const token = document.querySelector('#addKelasForm input[name="_token"]').value;
        const body = source === 'waiting' ? { source, enrollment_id: id } : { source, student_id: id };

        fetch(`/admin/kelas/${msKelasId}/siswa`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify(body),
        })
            .then(async (r) => {
                const data = await r.json();
                if (! r.ok) {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menambahkan siswa.');
                    alert(msg);
                    button.disabled = false;
                    button.textContent = 'Tambahkan';
                    return;
                }
                window.location.reload();
            })
            .catch(() => {
                alert('Gagal menghubungi server.');
                button.disabled = false;
                button.textContent = 'Tambahkan';
            });
    }

    /* ============================================================
       BUKA KEMBALI MODAL + ISI ULANG DATA JIKA VALIDASI GAGAL
    ============================================================ */
    @if ($errors->any())
    (function () {
        const old = @json(session()->getOldInput());
        if (! old || ! old._form) return;

        if (old._form === 'edit') {
            const btn = document.querySelector('[data-kelas-id="' + old._kelas_id + '"]');
            if (! btn) return;

            const base = JSON.parse(btn.dataset.kelas);
            document.getElementById('editKelasForm').action = base.update_url;
            document.getElementById('edit_kelas_id').value = base.id;
            fillKelasForm('edit', Object.assign({}, base, old, { type: base.type, program_package_id: base.program_package_id, private_package_id: base.private_package_id }));
            document.getElementById('editKelasModalOverlay').classList.add('open');
        } else {
            fillKelasForm('add', Object.assign({ type: 'Reguler', delivery_mode: 'Offline' }, old));
            document.getElementById('kelasModalOverlay').classList.add('open');
        }
    })();
    @endif
</script>
@endpush