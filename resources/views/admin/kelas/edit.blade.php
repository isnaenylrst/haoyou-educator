@extends('admin.app')

@section('title', 'Edit Kelas | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/kelas.css') }}">
    <style>
        .kelas-create-page { width: 100%; max-width: 1200px; margin: 0 auto; }
        .kelas-page-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:24px; }
        .kelas-page-heading h1 { margin:0; font-size:26px; font-weight:700; color:#111827; }
        .kelas-page-heading p { margin:8px 0 0; color:#6b7280; font-size:14px; }
        .kelas-form-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(15,23,42,.04); }
        .kelas-form-body { display:flex; flex-direction:column; gap:24px; }
        .kelas-form-actions { display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #e5e7eb; padding-top:20px; margin-top:24px; }
        .schedule-row { display:flex; gap:10px; align-items:flex-end; margin-bottom:12px; }
        .schedule-row .form-field { flex:1; min-width:0; }
        .schedule-row .schedule-remove { flex:none; }
        .room-picker { display:flex; gap:6px; }
        .room-picker select { flex:1; min-width:0; }
        .room-add { flex:none; width:40px; border:1px dashed #e0c200; background:#fff8cc; border-radius:10px; font-size:18px; font-weight:700; color:#8a6d00; cursor:pointer; }
        .room-add:hover { background:#ffef99; }
        .avail-title { display:block; font-size:13px; font-weight:600; margin-bottom:8px; }
        .avail-panel { background:#fafafa; border:1px solid #eee; border-radius:12px; padding:14px 16px; margin-bottom:16px; }
        .avail-empty { font-size:13px; color:#888; }
        .avail-row { display:flex; gap:14px; align-items:flex-start; padding:5px 0; }
        .avail-day { width:56px; flex:none; font-size:13px; font-weight:600; padding-top:3px; }
        .avail-chips { display:flex; flex-wrap:wrap; gap:6px; }
        .avail-chip { font-size:12px; font-weight:600; padding:3px 10px; border-radius:999px; border:1px solid; white-space:nowrap; }
        .avail-chip.ok { background:#f0fdf4; border-color:#bbf7d0; color:#16a34a; }
        .avail-chip.busy { background:#fef2f2; border-color:#fecaca; color:#dc2626; }
        .avail-legend { display:flex; gap:8px; margin-top:10px; padding-top:10px; border-top:1px dashed #e5e5e5; }
        .avail-warn { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:10px; padding:10px 14px; margin-top:12px; font-size:13px; line-height:1.6; }
        .form-hint { display:block; margin-top:6px; font-size:12px; color:#6b7280; }
        /* Paket Program dan Level dalam satu baris */
.form-row-program-level {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    width: 100%;
}

.form-row-program-level .form-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.form-row-program-level .form-group label {
    margin-bottom: 8px;
}

.form-row-program-level .form-group select {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.form-row-program-level .form-hint {
    display: block;
    margin-top: 8px;
}

/* Container Zoom memenuhi seluruh lebar form */
#edit_zoom_fields {
    display: none;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 20px;

    width: 100%;
    max-width: 100%;
    box-sizing: border-box;

    grid-column: 1 / -1;
    margin: 0;
}

/* Field Link Zoom dan Passcode */
#edit_zoom_fields .form-field {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    margin: 0;
    box-sizing: border-box;
}

/* Input memenuhi kolom */
#edit_zoom_fields .form-field input {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

/* Keterangan memenuhi satu baris */
#edit_zoom_fields .zoom-hint {
    grid-column: 1 / -1;
    width: 100%;
    margin: 0;
}

/* Tampilan HP */
@media (max-width: 600px) {
    #edit_zoom_fields {
        grid-template-columns: minmax(0, 1fr);
    }

    #edit_zoom_fields .zoom-hint {
        grid-column: 1;
    }
}

/* Tampilan HP */
@media (max-width: 600px) {
    .form-row-program-level {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
        @media (max-width: 768px) {
            .kelas-form-card { padding:16px; }
            .schedule-row { flex-wrap:wrap; }
            .schedule-row .form-field { flex:1 1 calc(50% - 10px); }
            .kelas-form-actions { flex-direction:column-reverse; }
            .kelas-form-actions .btn { width:100%; }
        }

        a.btn, a.icon-btn { text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
        .kelas-edit-card + .kelas-edit-card { margin-top:20px; }
        .lock-note { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:13px; line-height:1.6; }
        .lock-note a { color:#92400e; font-weight:600; }
        .edit-students .ms-list { max-height:360px; overflow-y:auto; }
        /* Jarak antar kartu: pakai ~ karena ada <form> tersembunyi di antara kartu */
        .kelas-edit-card ~ .kelas-edit-card { margin-top:24px; }

        /* Guru pengampu (baca-saja) */
        .teacher-readonly { display:flex; align-items:center; gap:10px; min-height:44px; padding:10px 14px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; font-size:14px; font-weight:400; color:#111827; }
        .teacher-readonly b { font-weight:600; }

        /* Ajukan ke Guru / Ganti Guru (ringkas). Jangan set font-family di semua elemen: ikon Font Awesome ikut rusak */
        .offer-group, .offer-row, .offer-chip, #offerCard .btn, #offerCard .auto-note > span { font-family:inherit; }
        #offerCard .auto-note { margin-bottom:4px; }
        #offerCard .auto-note > span { flex:1; min-width:0; white-space:normal; line-height:1.6; }
        .offer-group { margin-top:16px; }
        .offer-head { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px; }
        .offer-head-title { font-size:13px; font-weight:600; color:#111827; }
        .offer-count { margin-left:6px; font-weight:500; color:#6b7280; }
        .offer-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:8px; }
        .offer-row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 12px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; }
        .offer-main { min-width:0; }
        .offer-name { font-size:13px; font-weight:500; color:#111827; line-height:1.4; }
        .offer-why { margin-top:1px; font-size:11.5px; color:#6b7280; }
        .offer-note { margin-top:1px; font-size:11.5px; color:#b45309; }
        .offer-actions { display:flex; align-items:center; gap:6px; flex:none; }
        .offer-chip { display:inline-block; padding:2px 8px; border-radius:999px; background:#f3f4f6; color:#4b5563; font-size:11px; font-weight:600; line-height:1.5; white-space:nowrap; }
        .offer-chip.accepted { background:#f0fdf4; color:#16a34a; }
        .offer-chip.pending { background:#fffbeb; color:#b45309; }
        .offer-chip.rejected { background:#fef2f2; color:#dc2626; }
        #offerCard .btn-sm { padding:5px 12px; font-size:12px; font-weight:600; line-height:1.4; border-radius:8px; }
        .offer-empty { padding:10px 14px; border:1px dashed #e5e7eb; border-radius:10px; background:#fafafa; font-size:12.5px; color:#6b7280; line-height:1.6; }
        @media (max-width: 560px) { .offer-grid { grid-template-columns:1fr; } .offer-row { flex-wrap:wrap; } }
    </style>
@endpush

@section('content')
    @php
        $isPrivate = ! is_null($kelas->private_package_id);

        $editData = [
            'id'                 => $kelas->id,
            'class_name'         => $kelas->class_name,
            'type'               => $isPrivate ? 'Private' : 'Reguler',
            'delivery_mode'      => $kelas->delivery_mode,
            'zoom_link'          => $kelas->zoom_link,
            'zoom_passcode'      => $kelas->zoom_passcode,
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

<div class="kelas-create-page">
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Edit Kelas</h1>
            <p>{{ $kelas->class_name }} &mdash; perbarui data kelas dan kelola siswanya.</p>
        </div>
        <div class="dashboard-header-actions">
            <a href="{{ route('admin.kelas') }}" class="btn">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Kelas
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-error" style="margin-bottom:16px;">
            <strong>Data belum bisa disimpan:</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================= DATA KELAS ========================= --}}
    <div class="kelas-form-card kelas-edit-card">
        <form action="{{ route('admin.kelas.update', $kelas->id) }}" method="POST" id="editKelasForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_kelas_id" id="edit_kelas_id" value="{{ $kelas->id }}">

            <div class="kelas-form-body">

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-chalkboard"></i> Informasi Kelas</div>
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Nama Kelas *</label>
                            <input type="text" name="class_name" id="edit_class_name" required>
                        </div>
                        <div class="form-field full">
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

                        {{-- ZOOM (hanya tampil saat mode Online) --}}
                        <div class="form-row-two" id="edit_zoom_fields" style="display: none;">
                            <div class="form-field">
                                <label for="edit_zoom_link">Link Zoom <span class="opt">(opsional)</span></label>
                                <input type="url" name="zoom_link" id="edit_zoom_link" placeholder="https://zoom.us/j/...">
                            </div>
                            <div class="form-field">
                                <label for="edit_zoom_passcode">Passcode <span class="opt">(opsional)</span></label>
                                <input type="text" name="zoom_passcode" id="edit_zoom_passcode" maxlength="50">
                            </div>
                            <div class="form-hint zoom-hint">
                                Link ini yang akan muncul sebagai tombol "Buka Zoom" di dashboard siswa.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program &amp; Guru</div>
                    <div class="form-grid">

                        {{-- Reguler --}}
                        <div class="form-field full" id="edit_regular_fields">
                            <div class="form-row-program-level">
                                <div class="form-group">
                                    <label for="edit_program_package_id">Paket Program</label>
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
                                </div>

                                <div class="form-group">
                                    <label for="edit_level_id">Level *</label>
                                    <select name="level_id" id="edit_level_id" required>
                                        <option value="">Pilih Level</option>
                                        @foreach ($levels as $level)
                                            <option value="{{ $level->id }}" data-program-id="{{ $level->program_id }}">
                                                {{ $level->category_name ? $level->category_name . ' — ' : '' }}{{ $level->level_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
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
                            <div class="teacher-readonly">
                                @if ($kelas->teacher_id)
                                    <b>{{ $kelas->teacher?->name }}</b>
                                    @if ($kelas->teacher_response === 'Pending')
                                    @elseif ($kelas->teacher_response === 'Rejected')
                                        <span class="offer-chip">Ditolak guru</span>
                                    @endif
                                @else
                                    <span style="color:#6b7280;">Belum ada guru</span>
                                @endif
                            </div>
                            <span class="form-hint">
                                Terisi otomatis setelah guru menerima. Untuk menetapkan atau mengganti guru,
                                gunakan bagian "{{ $kelas->teacher_id ? 'Ganti Guru' : 'Ajukan ke Guru' }}" di bawah.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Mingguan</div>

                    @if ($hasSessions)
                        <div class="lock-note">
                            <i class="fa-solid fa-lock"></i>
                            Untuk perubah jadwal pertemuan klik
                            <a href="{{ route('admin.kelas.ubah-jadwal', $kelas->id) }}">Ubah Jadwal</a>.
                        </div>
                    @endif

                    <div id="edit_schedule_rows"></div>
                    <button type="button" class="add-schedule-btn" id="edit_add_schedule_btn" onclick="addScheduleRow('edit_schedule_rows')">
                        <i class="fa-solid fa-plus"></i> Tambah Jadwal
                    </button>

                    @unless ($hasSessions)
                        <div class="auto-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Jadwal yang sudah punya jurnal mengajar tidak bisa dihapus, hanya bisa diubah hari/jamnya.
                        </div>
                    @endunless
                </div>
            </div>

            <div class="kelas-form-actions">
                <a href="{{ route('admin.kelas') }}" class="btn">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>

    {{-- ========================= AJUKAN KE GURU / GANTI GURU ========================= --}}
    @php
        $offerStatusLabel = ['Pending' => 'Menunggu jawaban', 'Rejected' => 'Ditolak', 'Accepted' => 'Diterima', 'Closed' => 'Ditutup'];

        $offerGroups = [];
        if ($offerFit->isNotEmpty()) {
            $offerGroups[] = ['title' => 'Guru yang cocok', 'count' => $offerFit->count(), 'list' => $offerFit,
                              'mode' => 'matching', 'label' => 'Ajukan Semua yang Cocok', 'empty' => null];
        } else {
            $offerGroups[] = ['title' => 'Belum ada guru yang cocok', 'count' => null, 'list' => collect(),
                              'mode' => 'all', 'label' => 'Ajukan ke Semua Guru',
                              'empty' => 'Tidak ada guru yang jam tersedianya menutup jadwal kelas ini tanpa bentrok. Ajukan ke semua guru, atau pilih guru satu per satu di bawah.'];
        }
        if ($offerOthers->isNotEmpty()) {
            $offerGroups[] = ['title' => 'Guru lain (di luar jadwal tersedia atau bentrok)', 'count' => $offerOthers->count(), 'list' => $offerOthers,
                              'mode' => $offerFit->isNotEmpty() ? 'all' : null, 'label' => 'Ajukan ke Semua Guru', 'empty' => null];
        }
    @endphp

    <div class="kelas-form-card kelas-edit-card" id="offerCard">
        <div class="form-section">
            <div class="form-section-title">
                <i class="fa-solid fa-paper-plane"></i> {{ $kelas->teacher_id ? 'Ganti Guru' : 'Ajukan ke Guru' }}
            </div>

            <div class="auto-note">
                <i class="fa-solid fa-circle-info"></i>
                <span>
                    @if ($kelas->teacher_id)
                        Guru saat ini: <b>{{ $kelas->teacher?->name }}</b>.
                    @else
                        Guru kelas baru terisi setelah ada guru yang menerima.
                    @endif
                </span>
            </div>

            @foreach ($offerGroups as $g)
                <div class="offer-group">
                    <div class="offer-head">
                        <div class="offer-head-title">
                            {{ $g['title'] }}@if ($g['count'] !== null)<span class="offer-count">{{ $g['count'] }}</span>@endif
                        </div>
                        @if ($g['mode'])
                            <button type="button" class="btn btn-primary btn-sm" onclick="submitOffer('{{ $g['mode'] }}')">
                                <i class="fa-solid fa-paper-plane"></i> {{ $g['label'] }}
                            </button>
                        @endif
                    </div>

                    @if ($g['empty'])
                        <div class="offer-empty">{{ $g['empty'] }}</div>
                    @endif

                    @if ($g['list']->isNotEmpty())
                        <div class="offer-grid">
                            @foreach ($g['list'] as $t)
                                <div class="offer-row">
                                    <div class="offer-main">
                                        <div class="offer-name">{{ $t->name }}</div>
                                        @if ($t->reason)
                                            <div class="offer-why">{{ $t->reason }}</div>
                                        @endif
                                        @if ($t->status === 'Rejected' && $t->note)
                                            <div class="offer-note">Alasan menolak: {{ $t->note }}</div>
                                        @endif
                                    </div>
                                    <div class="offer-actions">
                                        @if ($t->status && $t->status !== 'Closed')
                                            <span class="offer-chip {{ strtolower($t->status) }}">{{ $offerStatusLabel[$t->status] ?? $t->status }}</span>
                                        @endif
                                        @if ($t->status !== 'Pending')
                                            <button type="button" class="btn btn-primary btn-sm" onclick="submitOffer('selected', [{{ $t->id }}])">
                                                {{ $t->status === 'Rejected' ? 'Ajukan Lagi' : 'Ajukan' }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Form tersembunyi untuk tombol Ajukan (terpisah dari form Edit Kelas) --}}
    <form id="offerForm" method="POST" action="{{ route('admin.kelas.offer.store', $kelas->id) }}" style="display:none;">
        @csrf
        <input type="hidden" name="mode" id="offer_mode">
        <div id="offer_ids"></div>
    </form>

    {{-- ========================= SISWA KELAS ========================= --}}
    <div class="kelas-form-card kelas-edit-card edit-students" id="studentsCard">
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-users-gear"></i> Siswa Kelas</div>

            <div class="auto-note" style="margin-bottom:10px;">
                <i class="fa-solid fa-circle-info"></i>
                Menambah siswa langsung tersimpan, tidak perlu menekan "Simpan Perubahan".
            </div>

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
    </div>
</div>
@endsection

@push('scripts')
<script>
    let scheduleIndex = 0;

// Daftar ruangan (dari server). Ruangan baru lewat tombol + ditambahkan ke daftar ini.
const ROOM_OPTIONS = @json($roomSuggestions);

function attrEscape(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function roomOptionsHtml(selected) {
    const list = [...ROOM_OPTIONS];

    if (selected && selected !== 'Online' && !list.includes(selected)) {
        list.push(selected);
    }

    return '<option value="">Pilih ruangan</option>' + list.map(r =>
        `<option value="${attrEscape(r)}" ${r === selected ? 'selected' : ''}>${escapeHtml(r)}</option>`
    ).join('');
}

function addRoomOption(btn) {
    const name = (prompt('Nama ruangan baru:') || '').trim();
    if (!name) return;

    const existing = ROOM_OPTIONS.find(
        r => r.toLowerCase() === name.toLowerCase()
    );

    const finalName = existing || name;
    if (!existing) ROOM_OPTIONS.push(name);

    document.querySelectorAll('.room-field select').forEach(sel => {
        const selected = sel.value;
        sel.innerHTML = roomOptionsHtml(selected);
    });

    btn.closest('.room-picker').querySelector('select').value = finalName;
}

// Ketersediaan guru
const TEACHER_AVAIL = @json($teacherAvailabilities);
const TEACHER_BUSY = @json($teacherBusy);
const DAY_ORDER = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
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

    // Ruangan hanya untuk Offline, field Zoom hanya untuk Online
    function syncRooms(prefix) {
        const online = document.getElementById(prefix + '_delivery_mode').value === 'Online';

        document.querySelectorAll('#' + prefix + '_schedule_rows .room-field').forEach(el => {
            el.style.display = online ? 'none' : '';
        });

        const zoom = document.getElementById(prefix + '_zoom_fields');
        if (zoom) {
            zoom.style.display = online ? 'grid' : 'none';
            // field tersembunyi dinonaktifkan agar tidak ikut tervalidasi/terkirim
            zoom.querySelectorAll('input').forEach(el => { el.disabled = ! online; });
        }
    }

    /* ============================================================
       KETERSEDIAAN GURU
       Slot yang menyambung digabung (08:00–10:00 + 10:00–12:00 = 08:00–12:00),
       logikanya sama dengan TeacherAvailability::teacherCovers() di server.
    ============================================================ */
    function slotCovered(slots, day, start, end) {
        const list = slots
            .filter(s => s.day === day)
            .sort((a, b) => a.start.localeCompare(b.start));

        let cursor = start;
        for (const s of list) {
            if (s.start > cursor) break;
            if (s.end > cursor) cursor = s.end;
            if (cursor >= end) return true;
        }
        return false;
    }

    function readScheduleRows(prefix) {
        return Array.from(document.querySelectorAll(`#${prefix}_schedule_rows .schedule-row`))
            .map(r => ({
                day:   r.querySelector('select[name$="[day]"]').value,
                start: r.querySelector('input[name$="[start_time]"]').value,
                end:   r.querySelector('input[name$="[end_time]"]').value,
            }))
            .filter(r => r.start && r.end && r.end > r.start);
    }

    function timesOverlap(aStart, aEnd, bStart, bEnd) {
        return aStart < bEnd && bStart < aEnd;
    }

    // Slot guru yang dipakai kelas lain (kelas yang sedang diedit tidak dihitung)
    function busySlotsFor(teacherId, prefix) {
        const selfId = prefix === 'edit'
            ? (Number(document.getElementById('edit_kelas_id').value) || null)
            : null;
        return (TEACHER_BUSY[teacherId] || []).filter(b => b.class_id !== selfId);
    }

    // Gambar panel "Jadwal Tersedia Guru" + peringatan jadwal yang tidak cocok
    function refreshTeacherAvailability(prefix) {
        const select = document.getElementById(prefix + '_teacher_id');
        const panel  = document.getElementById(prefix + '_avail_panel');
        const warn   = document.getElementById(prefix + '_avail_warn');
        if (! select || ! panel || ! warn) return;

        const teacherId = select.value;

        if (! teacherId) {
            panel.innerHTML = '<div class="avail-empty">Pilih guru pengampu untuk melihat jadwal tersedianya.</div>';
            warn.style.display = 'none';
            return;
        }

        const slots = TEACHER_AVAIL[teacherId] || [];
        const busy  = busySlotsFor(teacherId, prefix);

        if (! slots.length) {
            panel.innerHTML = '<div class="avail-empty">Guru ini belum memiliki jadwal tersedia. Atur ketersediaan guru terlebih dahulu.</div>';
        } else {
            const days = DAY_ORDER.filter(d => slots.some(s => s.day === d));
            panel.innerHTML = days.map(d => {
                const chips = slots.filter(s => s.day === d)
                    .map(s => `<span class="avail-chip ok">${s.start}–${s.end}</span>`)
                    .concat(busy.filter(b => b.day === d)
                        .map(b => `<span class="avail-chip busy">${b.start}–${b.end} · ${escapeHtml(b.class_name)}</span>`))
                    .join('');
                return `<div class="avail-row"><div class="avail-day">${d}</div><div class="avail-chips">${chips}</div></div>`;
            }).join('') + `<div class="avail-legend">
                <span class="avail-chip ok">tersedia</span>
                <span class="avail-chip busy">sudah terpakai kelas lain</span>
            </div>`;
        }

        // Peringatan per baris jadwal yang diisi
        const messages = [];
        readScheduleRows(prefix).forEach(r => {
            const label = `${r.day} ${r.start}–${r.end}`;

            if (! slotCovered(slots, r.day, r.start, r.end)) {
                messages.push(`${label}: di luar jadwal tersedia guru.`);
            }

            const clash = busy.find(b => b.day === r.day && timesOverlap(r.start, r.end, b.start, b.end));
            if (clash) {
                messages.push(`${label}: bentrok dengan kelas "${clash.class_name}" (${clash.start}–${clash.end}).`);
            }
        });

        warn.innerHTML = messages.map(m => `<div>⚠ ${escapeHtml(m)}</div>`).join('');
        warn.style.display = messages.length ? '' : 'none';
    }

    function addScheduleRow(containerId, values = {}) {
        const wrap = document.getElementById(containerId);
        const prefix = containerId.replace('_schedule_rows', '');
        const i = scheduleIndex++;
        const room = values.room && values.room !== 'Online' ? values.room : '';

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
                <div class="room-picker">
                    <select name="schedules[${i}][room]">${roomOptionsHtml(room)}</select>
                    <button type="button" class="room-add" title="Tambah ruangan baru" onclick="addRoomOption(this)">+</button>
                </div>
            </div>
            <button type="button" class="schedule-remove">
                <i class="fa-solid fa-trash"></i>
            </button>`;
            if (values.day) {
                row.querySelector('select[name$="[day]"]').value = values.day;
            }
            if (values.start_time) {
                row.querySelector('input[name$="[start_time]"]').value = values.start_time;
            }
            if (values.end_time) {
                row.querySelector('input[name$="[end_time]"]').value = values.end_time;
            }

            // Setiap perubahan jadwal -> hitung ulang guru yang tersedia
            row.addEventListener('input', () => refreshTeacherAvailability(prefix));
            row.addEventListener('change', () => refreshTeacherAvailability(prefix));

            row.querySelector('.schedule-remove').addEventListener('click', () => {
                row.remove();
                refreshTeacherAvailability(prefix);
            });

            wrap.appendChild(row);
            syncRooms(prefix);
            refreshTeacherAvailability(prefix);
       
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
        setValue(prefix + '_zoom_link', data.zoom_link);
        setValue(prefix + '_zoom_passcode', data.zoom_passcode);
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

        syncRooms(prefix);
        refreshTeacherAvailability(prefix);

        if (prefix === 'edit') {
            const hint = document.getElementById('edit_capacity_hint');
            hint.textContent = data.students_count !== undefined
                ? 'Siswa terdaftar saat ini: ' + data.students_count
                : '';
        }
    }


    /* ============================================================
       HALAMAN EDIT: isi form + kunci jadwal bila sudah ada pertemuan
    ============================================================ */
    const KELAS        = @json($editData);
    const HAS_SESSIONS = @json($hasSessions);
    const OLD_INPUT    = @json(old());
    const CSRF_TOKEN   = @json(csrf_token());
    const URL_STUDENTS = {
        list:   @json(route('admin.kelas.students', $kelas->id)),
        search: @json(route('admin.kelas.students.search', $kelas->id)),
        enroll: @json(route('admin.kelas.students.store', $kelas->id)),
    };

    function lockSchedule() {
        ['edit_delivery_mode', 'edit_start_date', 'edit_end_date'].forEach(id => {
            document.getElementById(id).disabled = true;
        });
        document.querySelectorAll('#edit_schedule_rows input, #edit_schedule_rows select, #edit_schedule_rows button')
            .forEach(el => { el.disabled = true; });
        document.querySelectorAll('#edit_schedule_rows .schedule-remove, #edit_schedule_rows .room-add')
            .forEach(el => { el.style.display = 'none'; });
        document.getElementById('edit_add_schedule_btn').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Tipe & paket tidak bisa diubah: selalu ikut data kelas, abaikan input lama
        fillKelasForm('edit', Object.assign({}, KELAS, OLD_INPUT, {
            type: KELAS.type,
            program_package_id: KELAS.program_package_id,
            private_package_id: KELAS.private_package_id,
        }));

        if (HAS_SESSIONS) lockSchedule();

        loadStudentsPanel();
    });

    /* ============================================================
       SISWA KELAS
    ============================================================ */
    let msIsFull = false;
    let msSearchTimer;
    let msEnrolledStudents = [];

    function loadStudentsPanel(keepTab = false) {
        msIsFull = false;
        msEnrolledStudents = [];

        document.getElementById('ms_capacity_banner').textContent = 'Memuat data kelas...';
        document.getElementById('ms_capacity_banner').className = 'ms-capacity';
        document.getElementById('ms_waiting_list').innerHTML = '<div class="ms-empty">Memuat...</div>';
        document.getElementById('ms_new_list').innerHTML = '<div class="ms-empty">Memuat...</div>';
        document.getElementById('ms_search_input').value = '';

        if (! keepTab) switchStudentTab('waiting');

        fetch(URL_STUDENTS.list, { headers: { 'Accept': 'application/json' } })
            .then(r => { if (! r.ok) throw new Error(); return r.json(); })
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

    // Tampilan awal tab "Siswa Baru": siswa yang sudah terdaftar (nonaktif), sebelum admin mengetik pencarian
    function renderNewListDefault() {
        const list = document.getElementById('ms_new_list');
        const hint = '<div class="ms-new-hint">Ketik nama untuk mencari siswa lain yang bisa ditambahkan.</div>';

        if (! msEnrolledStudents.length) {
            list.innerHTML = hint + '<div class="ms-empty">Belum ada siswa di kelas ini.</div>';
            return;
        }
        list.innerHTML = hint + msEnrolledStudents.map(s => renderStudentItem(s, 'new')).join('');
    }

    function renderCapacityBanner(kelas) {
        const el = document.getElementById('ms_capacity_banner');
        el.textContent = `Siswa terdaftar: ${kelas.students_count}/${kelas.capacity}`
            + (kelas.is_full ? ' — Kelas penuh, tidak bisa menambah siswa lagi.' : '');
        el.className = 'ms-capacity' + (kelas.is_full ? ' is-full' : '');

        document.getElementById('edit_capacity_hint').textContent = 'Siswa terdaftar saat ini: ' + kelas.students_count;
    }

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

        fetch(`${URL_STUDENTS.search}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } })
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

    function submitEnroll(source, id, button) {
        button.disabled = true;
        button.textContent = 'Menyimpan...';

        const body = source === 'waiting' ? { source, enrollment_id: id } : { source, student_id: id };

        fetch(URL_STUDENTS.enroll, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify(body),
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (! r.ok) {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menambahkan siswa.');
                    alert(msg);
                    button.disabled = false;
                    button.textContent = 'Tambahkan';
                    return;
                }
                // Isian form di atas tidak ikut hilang: cukup muat ulang panel siswa
                loadStudentsPanel(true);
            })
            .catch(() => {
                alert('Gagal menghubungi server.');
                button.disabled = false;
                button.textContent = 'Tambahkan';
            });
    }

    // Enter di kolom cari jangan menggeser halaman / men-submit apa pun
    document.getElementById('ms_search_input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });

    /* ============================================================
       AJUKAN KE GURU (panel di bawah form)
    ============================================================ */
    function submitOffer(mode, ids = []) {
        document.getElementById('offer_mode').value = mode;
        document.getElementById('offer_ids').innerHTML = ids
            .map(id => `<input type="hidden" name="teacher_ids[]" value="${id}">`)
            .join('');
        document.getElementById('offerForm').submit();
    }
</script>
@endpush