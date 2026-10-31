@extends('admin.app')

@section('title', 'Tambah Kelas | Akademik')

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
#add_zoom_fields {
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
#add_zoom_fields .form-field {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    margin: 0;
    box-sizing: border-box;
}

/* Input memenuhi kolom */
#add_zoom_fields .form-field input {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

/* Keterangan memenuhi satu baris */
#add_zoom_fields .zoom-hint {
    grid-column: 1 / -1;
    width: 100%;
    margin: 0;
}

/* Tampilan HP */
@media (max-width: 600px) {
    #add_zoom_fields {
        grid-template-columns: minmax(0, 1fr);
    }

    #add_zoom_fields .zoom-hint {
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
        .offer-list { display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:6px; max-height:200px; overflow:auto; margin-top:10px; padding:10px; border:1px solid #eee; border-radius:12px; background:#fafafa; }
        .offer-list.is-disabled { opacity:.45; }
        .offer-item { display:flex; align-items:center; gap:8px; font-size:13px; }
        .offer-badge { margin-left:auto; font-size:11px; color:#6b7280; }
        .offer-item.fits .offer-badge { color:#15803d; font-weight:600; }
    </style>
@endpush

@section('content')
<div class="kelas-create-page">
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Tambah Kelas</h1>
            <p>Buat kelas Reguler atau Private beserta jadwal mingguannya.</p>
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

    <div class="kelas-form-card">
            <form action="{{ route('admin.kelas.store') }}" method="POST" id="addKelasForm">
                @csrf
                <input type="hidden" name="_form" value="add">

                <div class="kelas-form-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-chalkboard"></i> Informasi Kelas</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Kelas *</label>
                                <input type="text" name="class_name" id="add_class_name" required placeholder="mis. Maochong 1A">
                            </div>
                            <div class="form-field full">
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

                            {{-- ZOOM (hanya tampil saat mode Online) --}}
                            <div class="form-row-two" id="add_zoom_fields" style="display: none;">

                                <div class="form-field">
                                    <label for="add_zoom_link">
                                        Link Zoom <span class="opt">(opsional)</span>
                                    </label>

                                    <input
                                        type="url"
                                        name="zoom_link"
                                        id="add_zoom_link"
                                        placeholder="https://zoom.us/j/..."
                                    >
                                </div>

                                <div class="form-field">
                                    <label for="add_zoom_passcode">
                                        Passcode <span class="opt">(opsional)</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="zoom_passcode"
                                        id="add_zoom_passcode"
                                        maxlength="50"
                                    >
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
                            <div class="form-field full" id="add_regular_fields">
                                <div class="form-row-program-level">

                                    {{-- Paket Program --}}
                                    <div class="form-group">
                                        <label for="add_program_package_id">
                                            Paket Program *
                                        </label>

                                        <select
                                            name="program_package_id"
                                            id="add_program_package_id"
                                            required
                                            onchange="onPackageChange('add')"
                                        >
                                            <option value="">Pilih Paket Program</option>

                                            @foreach ($programPackages as $package)
                                                @continue(! $package->is_active)

                                                <option
                                                    value="{{ $package->id }}"
                                                    data-program-id="{{ $package->program_id }}"
                                                    data-level-id="{{ $package->level_id }}"
                                                    data-max="{{ $package->max_students }}"
                                                >
                                                    {{ $package->program?->program_name }}
                                                    — {{ $package->package_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Level --}}
                                    <div class="form-group">
                                        <label for="add_level_id">
                                            Level *
                                        </label>

                                        <select
                                            name="level_id"
                                            id="add_level_id"
                                            required
                                        >
                                            <option value="">Pilih Level</option>

                                            @foreach ($levels as $level)
                                                <option
                                                    value="{{ $level->id }}"
                                                    data-program-id="{{ $level->program_id }}"
                                                >
                                                    {{ $level->category_name ? $level->category_name . ' — ' : '' }}{{ $level->level_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <span class="form-hint">
                                    Level otomatis terisi untuk paket HSK. Untuk Daily Activity, pilih sesuai kelasnya.
                                </span>
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
                                <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Mingguan</div>
                                <div id="add_schedule_rows"></div>
                                <button type="button" class="add-schedule-btn" onclick="addScheduleRow('add_schedule_rows')">
                                    <i class="fa-solid fa-plus"></i> Tambah Jadwal
                                </button>
                                <div class="avail-warn" id="add_avail_warn" style="display:none;"></div>
                                <div class="auto-note">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Jadwal ini menjadi pola sesi mingguan kelas dan dipakai untuk mencatat jurnal mengajar.
                                </div>
                            </div>

                            <div class="form-field full">
                                <label>Ajukan ke Guru</label>

                                <div class="form-radio-group" id="add_offer_modes" onchange="offerModeTouched = true; refreshOfferList()">
                                    <label class="form-radio">
                                        <input type="radio" name="offer_mode" value="selected" @checked(old('offer_mode', 'selected') === 'selected')> Guru yang dicentang
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="offer_mode" value="matching" @checked(old('offer_mode') === 'matching')> Semua guru yang cocok
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="offer_mode" value="all" @checked(old('offer_mode') === 'all')> Semua guru
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="offer_mode" value="none" @checked(old('offer_mode') === 'none')> Jangan ajukan dulu
                                    </label>
                                </div>

                                <div class="offer-list" id="add_offer_list" onchange="offerTouched = true; refreshOfferList()">
                                    @foreach ($teachers->where('status', 'Active') as $teacher)
                                        <label class="form-radio offer-item" data-teacher="{{ $teacher->id }}">
                                            <input type="checkbox" name="offer_teacher_ids[]" value="{{ $teacher->id }}"
                                                @checked(collect(old('offer_teacher_ids', []))->contains($teacher->id))>
                                            {{ $teacher->name }}
                                            <span class="offer-badge"></span>
                                        </label>
                                    @endforeach
                                </div>
                                <span class="form-hint" id="add_offer_summary"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kelas-form-actions">
                    <a href="{{ route('admin.kelas') }}" class="btn">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Kelas</button>
                </div>
            </form>
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

    /* ============================================================
       AJUKAN KE GURU: guru yang cocok dengan jadwal ada di paling atas
    ============================================================ */
    let offerTouched     = {{ old('offer_teacher_ids') !== null ? 'true' : 'false' }};
    let offerModeTouched = {{ old('offer_mode') !== null ? 'true' : 'false' }};

    function refreshOfferList() {
        const rows  = readScheduleRows('add');
        const list  = document.getElementById('add_offer_list');
        const items = Array.from(list.querySelectorAll('.offer-item'));
        let fitCount = 0;

        items.forEach(el => {
            const id    = el.dataset.teacher;
            const cb    = el.querySelector('input');
            const badge = el.querySelector('.offer-badge');

            if (! rows.length) {
                badge.textContent = '';
                el.dataset.fits = '';
                el.classList.remove('fits');
                return;
            }

            const slots  = TEACHER_AVAIL[id] || [];
            const busy   = busySlotsFor(id, 'add');
            const covers = rows.every(r => slotCovered(slots, r.day, r.start, r.end));
            const clash  = rows.some(r => busy.some(b => b.day === r.day && timesOverlap(r.start, r.end, b.start, b.end)));
            const fits   = covers && ! clash;

            if (fits) fitCount++;
            badge.textContent = fits ? 'cocok' : (clash ? 'bentrok' : 'di luar jadwal');
            el.dataset.fits = fits ? '1' : '';
            el.classList.toggle('fits', fits);

            // Selama admin belum mengubah sendiri, yang cocok otomatis tercentang
            if (! offerTouched) cb.checked = fits;
        });

        items.sort((a, b) => (b.dataset.fits ? 1 : 0) - (a.dataset.fits ? 1 : 0)
            || a.textContent.trim().localeCompare(b.textContent.trim()))
            .forEach(el => list.appendChild(el));

        // Pilihan bawaan: ada yang cocok -> guru yang dicentang; tidak ada yang cocok -> semua guru
        if (rows.length && ! offerModeTouched) {
            document.querySelector(`input[name="offer_mode"][value="${fitCount ? 'selected' : 'all'}"]`).checked = true;
        }

        const mode    = document.querySelector('input[name="offer_mode"]:checked').value;
        const checked = list.querySelectorAll('input:checked').length;
        list.classList.toggle('is-disabled', mode !== 'selected');

        let text = '';
        if (mode === 'selected') {
            text = checked
                ? `${checked} guru dicentang. Kelas diajukan ke mereka saat disimpan; guru kelas terisi setelah ada yang menerima.`
                : 'Centang minimal satu guru, atau pilih opsi lain.';
        } else if (mode === 'matching') {
            text = ! rows.length ? 'Isi jadwal mingguan dulu.'
                : (fitCount ? `${fitCount} guru yang cocok akan diajukan saat kelas disimpan.`
                            : 'Belum ada guru yang cocok. Pilih "Semua guru".');
        } else if (mode === 'all') {
            text = 'Kelas diajukan ke semua guru aktif saat disimpan.';
        } else {
            text = 'Kelas disimpan tanpa diajukan. Ajukan nanti lewat halaman Edit kelas.';
        }
        document.getElementById('add_offer_summary').textContent = text;
    }

    // Gambar panel "Jadwal Tersedia Guru" + peringatan jadwal yang tidak cocok
    function refreshTeacherAvailability(prefix) {
        // Halaman Tambah tidak punya pilihan guru tunggal: yang diperbarui daftar pengajuan
        if (prefix === 'add') { refreshOfferList(); return; }

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


    // Inisialisasi form tambah kelas saat halaman dibuka.
    document.addEventListener('DOMContentLoaded', function () {
        fillKelasForm('add', Object.assign(
            { type: 'Reguler', delivery_mode: 'Offline', schedules: [] },
            @json(session()->getOldInput())
        ));
    });
</script>
@endpush