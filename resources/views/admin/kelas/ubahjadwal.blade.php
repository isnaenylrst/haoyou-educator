@extends('admin.app')

@section('title', 'Ubah Jadwal | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/kelas.css') }}">
    <style>
        .kelas-create-page { width:100%; max-width:1200px; margin:0 auto; }
        a.btn, a.icon-btn { text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }

        .uj-grid { display:grid; grid-template-columns:minmax(0, 1.25fr) minmax(0, 1fr); gap:20px; align-items:start; }
        .uj-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(15,23,42,.04); }
        .uj-card h2 { margin:0 0 4px; font-size:16px; font-weight:700; color:#111827; }
        .uj-card .uj-sub { margin:0 0 16px; font-size:13px; color:#6b7280; }
        .uj-side { position:sticky; top:16px; }
        .uj-placeholder { text-align:center; padding:36px 12px; color:#9ca3af; font-size:13px; line-height:1.7; }
        .uj-placeholder i { display:block; font-size:28px; margin-bottom:10px; color:#d1d5db; }
        .uj-pattern { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:18px; }

        #ses_list .ms-item.is-selected { border-color:#facc15; background:#fffbeb; }
        #ses_list { max-height:none; }

        .ses-actions { display:flex; align-items:center; gap:8px; flex:none; }
        .ses-changed { font-size:12px; color:#92400e; margin-top:2px; }
        .ses-meta { font-size:12px; color:#6b7280; margin-top:2px; }
        #rs_confirm_wrap { display:none; margin-top:4px; }
        .uj-actions { display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #e5e7eb; padding-top:16px; margin-top:18px; }

        @media (max-width: 900px) {
            .uj-grid { grid-template-columns:minmax(0, 1fr); }
            .uj-side { position:static; }
            .uj-card { padding:16px; }
        }
    </style>
@endpush

@section('content')
<div class="kelas-create-page">
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Ubah Jadwal</h1>
            <p>
                {{ $kelas->class_name }}
                &middot; {{ $kelas->teacher?->name ?? 'Belum ada guru' }}
                @if ($kelas->level?->level_name)
                    &middot; Level {{ $kelas->level->level_name }}
                @endif
            </p>
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

    <div class="uj-grid">

        {{-- ========================= DAFTAR PERTEMUAN ========================= --}}
        <div class="uj-card">
            <h2>Pertemuan Kelas</h2>
            <p class="uj-sub">Pilih pertemuan yang ingin dipindah jadwalnya.</p>

            @if ($kelas->schedules->count())
                <div class="uj-pattern">
                    @foreach ($kelas->schedules as $sc)
                        <span class="schedule-chip">
                            {{ substr($sc->day, 0, 3) }} {{ substr($sc->start_time, 0, 5) }}–{{ substr($sc->end_time, 0, 5) }}
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="ms-list" id="ses_list"></div>
        </div>

        {{-- ========================= FORM UBAH JADWAL ========================= --}}
        <div class="uj-card uj-side" id="rescheduleCard">
            <div class="uj-placeholder" id="rs_placeholder">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Belum ada pertemuan yang dipilih.<br>
                Klik "Ubah Jadwal" pada salah satu pertemuan di daftar.
            </div>

            <div id="rs_form" style="display:none;">
                <h2>Ubah Jadwal Pertemuan</h2>
                <p class="uj-sub" id="rs_info">-</p>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Cakupan Perubahan *</label>
                        <div class="form-radio-group">
                            <label class="form-radio">
                                <input type="radio" name="rs_scope" value="one" checked onchange="onRescheduleScopeChange()"> Pertemuan ini saja
                            </label>
                            <label class="form-radio">
                                <input type="radio" name="rs_scope" value="following" id="rs_scope_following" onchange="onRescheduleScopeChange()"> Pertemuan ini dan seterusnya
                            </label>
                        </div>
                        <span class="form-hint" id="rs_scope_hint"></span>
                    </div>

                    {{-- "Pertemuan ini saja": pilih tanggal --}}
                    <div class="form-field" id="rs_date_field">
                        <label>Tanggal Baru *</label>
                        <input type="date" id="rs_date">
                    </div>

                    {{-- "Pertemuan ini dan seterusnya": pilih hari (pola mingguan baru) --}}
                    <div class="form-field" id="rs_day_field" style="display:none;">
                        <label>Hari Baru *</label>
                        <select id="rs_day" onchange="updateFollowingPreview()">
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                        <span class="form-hint" id="rs_day_preview"></span>
                    </div>

                    <div class="form-field">
                        <label>Jam Mulai *</label>
                        <input type="time" id="rs_start" required>
                        <span class="form-hint">Durasi pertemuan tetap sama.</span>
                    </div>

                    <div class="form-field full" id="rs_room_field">
                        <label>Ruangan</label>
                        <input type="text" id="rs_room" list="rs_room_list" placeholder="Pilih atau ketik nama ruangan">
                        <datalist id="rs_room_list"></datalist>
                    </div>

                    <div class="form-field full">
                        <label>Alasan <span class="opt">(opsional)</span></label>
                        <input type="text" id="rs_reason" maxlength="255" placeholder="Contoh: guru ada acara, ruang dipakai kelas lain">
                    </div>

                    <div class="form-field full" id="rs_confirm_wrap">
                        <label class="form-radio">
                            <input type="checkbox" id="rs_confirm"> Guru sudah setuju mengajar di luar jam tersedianya
                        </label>
                    </div>
                </div>

                <div class="alert-error" id="rs_error" style="display:none;margin-top:12px;"></div>

                <div class="uj-actions">
                    <button type="button" class="btn" onclick="cancelReschedule()">Batal</button>
                    <button type="button" class="btn btn-primary" id="rs_submit" onclick="submitReschedule()">
                        <i class="fa-solid fa-check"></i> Simpan Jadwal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const SESSIONS      = @json($sessionsData);
    const ROOM_OPTIONS  = @json($roomSuggestions);
    const CSRF_TOKEN    = @json(csrf_token());
    const URL_RESCHEDULE = @json(route('admin.kelas.sessions.reschedule', ['classSession' => '__ID__']));
    const DAY_ORDER     = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const SESSION_STATUS_LABEL = { Scheduled: 'Terjadwal', Completed: 'Selesai', Cancelled: 'Dibatalkan' };

    const sesData = {};
    SESSIONS.forEach(s => { sesData[s.id] = s; });
    let rsSession = null;

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

    function formatDateId(iso) {
        const [y, m, d] = String(iso).split('-');
        return `${d}-${m}-${y}`;
    }

    function todayIso() {
        const t = new Date();
        return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;
    }

    /* ============================================================
       DAFTAR PERTEMUAN
    ============================================================ */
    function renderSessionItem(s) {
        const status = SESSION_STATUS_LABEL[s.status] || s.status;
        const place  = s.location_type === 'Studio' ? (s.room || 'Ruangan belum diisi') : s.location_type;
        const changed = s.deviates && s.original_date
            ? `<div class="ses-changed">Semula ${formatDateId(s.original_date)}${s.change_reason ? ' — ' + escapeHtml(s.change_reason) : ''}</div>`
            : '';

        const action = s.can_reschedule
            ? `<button type="button" class="btn btn-primary btn-sm" onclick="selectSession(${s.id})">Ubah Jadwal</button>`
            : '';

        return `<div class="ms-item" id="ses_item_${s.id}">
            <div>
                <div class="ms-item-name">Pertemuan ke-${s.number ?? '-'} &middot; ${escapeHtml(s.date_label)}</div>
                <div class="ses-meta">${escapeHtml(s.start)}–${escapeHtml(s.end)} &middot; ${escapeHtml(place)} &middot; ${escapeHtml(s.teacher || 'Belum ada guru')}</div>
                ${changed}
            </div>
            <div class="ses-actions">
                <span class="ms-status-chip">${escapeHtml(status)}</span>
                ${action}
            </div>
        </div>`;
    }

    (function renderSessions() {
        const list = document.getElementById('ses_list');
        list.innerHTML = SESSIONS.length
            ? SESSIONS.map(renderSessionItem).join('')
            : '<div class="ms-empty">Kelas ini belum punya pertemuan bertanggal.</div>';
    })();

    /* ============================================================
       FORM UBAH JADWAL
    ============================================================ */
    function selectSession(sessionId) {
        const s = sesData[sessionId];
        if (! s) return;
        rsSession = s;

        document.querySelectorAll('#ses_list .ms-item').forEach(el => el.classList.remove('is-selected'));
        document.getElementById('ses_item_' + s.id)?.classList.add('is-selected');

        document.getElementById('rs_info').textContent =
            `Pertemuan ke-${s.number ?? '-'} — ${s.date_label}, ${s.start}–${s.end}`;

        // Cakupan: "seterusnya" hanya kalau pola jadwalnya masih berlaku
        document.querySelector('input[name="rs_scope"][value="one"]').checked = true;
        const following = document.getElementById('rs_scope_following');
        following.disabled = ! s.can_following;
        document.getElementById('rs_scope_hint').textContent = s.can_following
            ? '"Seterusnya" mengubah pola jadwal mingguan kelas: hari, jam, dan ruangan baru berlaku untuk pertemuan ini dan semua pertemuan sesudahnya.'
            : 'Pertemuan ini tidak punya pola mingguan yang berlaku, jadi hanya bisa diubah satu pertemuan.';

        const dateInput = document.getElementById('rs_date');
        dateInput.min = todayIso();
        dateInput.value = s.date;
        document.getElementById('rs_start').value = s.start;
        document.getElementById('rs_reason').value = '';

        // Ruangan hanya relevan untuk pertemuan offline (Studio)
        const isStudio = s.location_type === 'Studio';
        document.getElementById('rs_room_field').style.display = isStudio ? '' : 'none';
        document.getElementById('rs_room').value = isStudio ? (s.room || '') : '';
        document.getElementById('rs_room_list').innerHTML = ROOM_OPTIONS
            .map(r => `<option value="${attrEscape(r)}"></option>`).join('');

        // Hari awal = hari pertemuan saat ini
        document.getElementById('rs_day').value = DAY_ORDER[weekdayIndex(s.date)];

        document.getElementById('rs_confirm').checked = false;
        document.getElementById('rs_confirm_wrap').style.display = 'none';
        document.getElementById('rs_error').style.display = 'none';
        document.getElementById('rs_submit').disabled = false;

        onRescheduleScopeChange();

        document.getElementById('rs_placeholder').style.display = 'none';
        document.getElementById('rs_form').style.display = '';
        document.getElementById('rescheduleCard').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function cancelReschedule() {
        rsSession = null;
        document.querySelectorAll('#ses_list .ms-item').forEach(el => el.classList.remove('is-selected'));
        document.getElementById('rs_form').style.display = 'none';
        document.getElementById('rs_placeholder').style.display = '';
    }

    // 0 = Senin ... 6 = Minggu
    function weekdayIndex(iso) {
        return (new Date(iso + 'T00:00:00').getDay() + 6) % 7;
    }

    // Tanggal pertemuan ini kalau pindah ke hari lain di minggu yang sama (Senin–Minggu)
    function sameWeekDate(iso, dayIndex) {
        const d = new Date(iso + 'T00:00:00');
        d.setDate(d.getDate() - weekdayIndex(iso) + dayIndex);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    function currentRescheduleScope() {
        return document.querySelector('input[name="rs_scope"]:checked').value;
    }

    // "Satu pertemuan" -> pilih tanggal; "Seterusnya" -> pilih hari
    function onRescheduleScopeChange() {
        const following = currentRescheduleScope() === 'following';
        document.getElementById('rs_date_field').style.display = following ? 'none' : '';
        document.getElementById('rs_day_field').style.display = following ? '' : 'none';
        updateFollowingPreview();
    }

    function updateFollowingPreview() {
        const el = document.getElementById('rs_day_preview');
        if (! rsSession || currentRescheduleScope() !== 'following') { el.textContent = ''; return; }

        const idx = DAY_ORDER.indexOf(document.getElementById('rs_day').value);
        el.textContent = 'Pertemuan ini pindah ke ' + formatDateId(sameWeekDate(rsSession.date, idx))
            + ', pertemuan sesudahnya mengikuti hari yang sama tiap minggu.';
    }

    function showRescheduleError(messages) {
        const box = document.getElementById('rs_error');
        box.innerHTML = messages.map(m => `<div>${escapeHtml(m)}</div>`).join('');
        box.style.display = '';
    }

    function submitReschedule() {
        const s = rsSession;
        if (! s) return;

        const scope     = currentRescheduleScope();
        const following = scope === 'following';
        const date      = document.getElementById('rs_date').value;
        const start     = document.getElementById('rs_start').value;

        if (! start || (! following && ! date)) {
            showRescheduleError([following ? 'Hari dan jam mulai wajib diisi.' : 'Tanggal dan jam mulai wajib diisi.']);
            return;
        }

        const body = {
            scope: scope,
            start_time: start,
            reason: document.getElementById('rs_reason').value.trim() || null,
            confirm_outside_availability: document.getElementById('rs_confirm').checked,
        };
        if (following) {
            body.day = document.getElementById('rs_day').value;
        } else {
            body.session_date = date;
        }
        if (s.location_type === 'Studio') {
            body.room = document.getElementById('rs_room').value.trim();
        }

        const btn = document.getElementById('rs_submit');
        btn.disabled = true;
        document.getElementById('rs_error').style.display = 'none';

        fetch(URL_RESCHEDULE.replace('__ID__', s.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify(body),
        })
            .then(async (r) => {
                if (r.ok) {
                    window.location.reload(); // pesan sukses tampil lewat flash session
                    return;
                }

                const data = await r.json().catch(() => ({}));
                const errors = data.errors || {};
                const messages = Object.values(errors).flat();

                // Di luar jam tersedia guru: tampilkan centang konfirmasi, admin simpan ulang
                if (errors.availability) {
                    document.getElementById('rs_confirm_wrap').style.display = 'block';
                }

                showRescheduleError(messages.length
                    ? messages
                    : [r.status === 419 ? 'Sesi habis, muat ulang halaman lalu coba lagi.' : (data.message || 'Gagal menyimpan jadwal.')]);
                btn.disabled = false;
            })
            .catch(() => {
                showRescheduleError(['Gagal menghubungi server.']);
                btn.disabled = false;
            });
    }
</script>
@endpush