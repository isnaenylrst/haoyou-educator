@extends('admin.app')

@section('title', 'Ubah Jadwal | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        .ta-box{border:1px solid #e2e8f0;border-radius:10px;padding:12px;background:#f8fafc}
        .ta-status{font-size:13px;font-weight:600;margin-bottom:8px}
        .ta-status.ok{color:#15803d}.ta-status.warn{color:#b45309}.ta-status.bad{color:#b91c1c}
        .ta-title{font-size:12px;color:#64748b;margin-bottom:6px}
        .ta-chips{display:flex;flex-wrap:wrap;gap:6px}
        .ta-chip{border:1px solid #86efac;background:#f0fdf4;color:#166534;border-radius:999px;padding:3px 10px;font-size:12px;cursor:pointer}
        .ta-chip.short{border-color:#e2e8f0;background:#fff;color:#94a3b8;cursor:default}
        .ta-busy{font-size:12px;color:#64748b;margin-top:8px}
        .ta-week summary{cursor:pointer;font-size:12px;margin-top:10px;color:#475569}
        .ta-row{display:flex;gap:10px;align-items:flex-start;padding:6px 0;border-top:1px dashed #e2e8f0}
        .ta-row.is-current{background:#eef2ff}
        .ta-day{width:92px;flex-shrink:0;font-size:12px;font-weight:600;cursor:pointer}
        .ta-day small{display:block;font-weight:400;color:#94a3b8}
        .ta-none{font-size:12px;color:#94a3b8}
        .uj-left{display:flex;flex-direction:column;gap:16px;min-width:0}
        #ses_list{max-height:max(360px, calc(100vh - 340px));overflow-y:auto;padding-right:6px;scroll-behavior:auto}
        .uj-side{position:sticky;top:100px;align-self:start;max-height:max(520px, calc(100vh - 120px));overflow-y:auto}
        .ta-row:first-child{border-top:0}
        @media (max-width: 1100px){ .uj-side{position:static;max-height:none;overflow:visible} }
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
            <a href="{{ route('admin.kelas') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <div class="uj-grid">

        <div class="uj-left">
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

        {{-- ========================= KETERSEDIAAN GURU (ringkasan semua hari) ========================= --}}
        <div class="uj-card" id="rs_week_card" style="display:none;">
            <h2>Ketersediaan Guru</h2>
            <p class="uj-sub" id="rs_week_sub">-</p>
            <div id="rs_week_body"></div>
            <span class="form-hint">Klik nama hari untuk pindah ke hari itu, atau klik jam untuk mengisi hari dan Jam Mulai sekaligus. Jam abu-abu lebih pendek dari durasi pertemuan.</span>
        </div>
        </div>

        {{-- ========================= FORM UBAH JADWAL ========================= --}}
        <div class="uj-card uj-side" id="rescheduleCard">
            <div class="uj-placeholder" id="rs_placeholder">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Belum ada pertemuan yang dipilih.<br>
                Klik "Ubah Jadwal" pada pertemuan yang belum berlangsung,<br>atau "Detail Jurnal" pada pertemuan yang sudah berlangsung.
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
                        <input type="date" id="rs_date" onchange="refreshAvailability()">
                    </div>

                    {{-- "Pertemuan ini dan seterusnya": pilih hari (pola mingguan baru) --}}
                    <div class="form-field" id="rs_day_field" style="display:none;">
                        <label>Hari Baru *</label>
                        <select id="rs_day" onchange="updateFollowingPreview(); refreshAvailability()">
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
                        <input type="time" id="rs_start" required oninput="refreshAvailability()">
                        <span class="form-hint">Durasi pertemuan tetap sama.</span>
                    </div>

                    {{-- Ketersediaan guru: jam yang diisi guru dikurangi jadwal kelas lain --}}
                    <div class="form-field full" id="rs_avail" style="display:none;">
                        <label>Ketersediaan Guru</label>
                        <div class="ta-box" id="rs_avail_body"></div>
                        <span class="form-hint">Jam kosong = jam yang diisi guru, dikurangi jadwal kelas lain dan tugas sebagai guru pengganti. Klik jam untuk mengisi Jam Mulai. Ringkasan semua hari ada di kartu Ketersediaan Guru.</span>
                    </div>

                    <div class="form-field full" id="rs_confirm_wrap">
                        <label class="form-radio">
                            <input type="checkbox" id="rs_confirm"> Guru sudah setuju mengajar di luar jam tersedianya
                        </label>
                        <span class="form-hint">Centang kalau jadwal baru di luar jam tersedia guru dan guru sudah menyetujuinya.</span>
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

                    {{-- ===== Home Visit ===== --}}
                    <div class="form-field full" id="rs_hv_done" style="display:none;">
                        <span class="form-hint">Pertemuan ini sudah Home Visit dan sudah dibayar. Pindah jadwal tidak dikenai biaya lagi.</span>
                    </div>

                    {{-- Lokasi pertemuan yang home visit-nya sudah dibayar: boleh pindah ke kantor/online tanpa biaya baru, tanpa pengembalian --}}
                    <div class="form-field full" id="rs_loc_wrap" style="display:none;">
                        <label>Lokasi pertemuan</label>
                        <div class="form-radio-group">
                            <label class="form-radio">
                                <input type="radio" name="rs_loc" value="Home Visit" onchange="refreshHomeVisitUi()"> Home Visit
                            </label>
                            <label class="form-radio">
                                <input type="radio" name="rs_loc" value="Studio" onchange="refreshHomeVisitUi()"> Kantor (Studio)
                            </label>
                            <label class="form-radio">
                                <input type="radio" name="rs_loc" value="Online" onchange="refreshHomeVisitUi()"> Online
                            </label>
                        </div>
                        <span class="form-hint">Biaya home visit pertemuan ini sudah dibayar dan tidak dikembalikan. Pindah ke kantor atau online, atau kembali ke Home Visit, tidak dikenai biaya lagi.</span>
                    </div>

                    <div class="form-field full" id="rs_hv_wrap" style="display:none;">
                        <label>Home Visit (guru datang ke rumah)</label>
                        <div class="form-radio-group">
                            <label class="form-radio">
                                <input type="radio" name="rs_hv" value="0" checked onchange="refreshHomeVisitUi()"> Tidak
                            </label>
                            <label class="form-radio">
                                <input type="radio" name="rs_hv" value="1" onchange="refreshHomeVisitUi()"> Ya, jadikan Home Visit
                            </label>
                        </div>
                    </div>

                    <div class="form-field full" id="rs_hv_note_wrap" style="display:none;">
                        <span class="form-hint" id="rs_hv_note"></span>
                    </div>

                    <div class="form-field full rs-hv-field" style="display:none;">
                        <div class="alert-success" style="margin:0;">
                            <strong>Ketentuan Home Visit</strong>
                            <ul style="margin:6px 0 0 18px; padding:0;">
                                <li>Biaya <strong id="rs_hv_fee"></strong> per pertemuan, hanya untuk pertemuan ini. Pertemuan lain tidak kena biaya.</li>
                                <li>Wajib dibayar dulu: jadwal baru tersimpan setelah pembayaran tercatat.</li>
                                <li>Satu invoice untuk pertemuan ini, terbit otomatis setelah disimpan.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-field full rs-hv-field" style="display:none;">
                        <label>Dibayar oleh *</label>
                        <select id="rs_payer"></select>
                    </div>

                    <div class="form-field rs-hv-field" style="display:none;">
                        <label>Tanggal Bayar *</label>
                        <input type="date" id="rs_pay_date">
                    </div>

                    <div class="form-field rs-hv-field" style="display:none;">
                        <label>Metode Bayar *</label>
                        <select id="rs_pay_method">
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Cash">Cash</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>

                    <div class="form-field full rs-hv-field" style="display:none;">
                        <label>Bukti Pembayaran *</label>
                        <input type="file" id="rs_pay_proof" accept=".jpg,.jpeg,.png,.pdf">
                        <span class="form-hint">JPG, PNG, atau PDF, maksimal 4 MB.</span>
                    </div>
                </div>

                <div class="alert-error" id="rs_error" style="display:none;margin-top:12px;"></div>

                <div class="uj-actions">
                    <button type="button" class="btn btn-secondary" onclick="cancelReschedule()">Batal</button>
                    <button type="button" class="btn btn-primary" id="rs_submit" onclick="submitReschedule()">
                        <i class="fa-solid fa-check"></i> Simpan Jadwal
                    </button>
                </div>
            </div>

            {{-- ===== Detail Jurnal Mengajar (pertemuan yang sudah berlangsung, hanya lihat) ===== --}}
            <div id="jr_view" style="display:none;">
                <h2>Jurnal Mengajar</h2>
                <p class="uj-sub" id="jr_info">-</p>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Status Pertemuan</label>
                        <div id="jr_status">-</div>
                    </div>
                    <div class="form-field full">
                        <label>Guru</label>
                        <div id="jr_teacher">-</div>
                    </div>
                    <div class="form-field full" id="jr_sub_wrap" style="display:none;">
                        <label>Guru Pengganti</label>
                        <div id="jr_sub">-</div>
                    </div>
                    <div class="form-field full" id="jr_material_wrap" style="display:none;">
                        <label>Materi</label>
                        <div id="jr_material">-</div>
                    </div>
                    <div class="form-field full">
                        <label>Kegiatan Belajar</label>
                        <div id="jr_activities" style="white-space:pre-line;">-</div>
                    </div>
                    <div class="form-field full">
                        <label>Kendala</label>
                        <div id="jr_problems" style="white-space:pre-line;">-</div>
                    </div>
                    <div class="form-field full">
                        <label>Solusi</label>
                        <div id="jr_solutions" style="white-space:pre-line;">-</div>
                    </div>
                    <div class="form-field full">
                        <label>Hasil</label>
                        <div id="jr_results" style="white-space:pre-line;">-</div>
                    </div>
                </div>

                <div class="uj-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeJournal()">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

    const SESSIONS      = @json($sessionsData);
    const ROOM_OPTIONS  = @json($roomSuggestions);
    const CSRF_TOKEN    = @json(csrf_token());
    const URL_RESCHEDULE = @json(route('admin.kelas.sessions.reschedule', ['classSession' => '__ID__']));
    const PAYERS        = @json($payers);
    const HV_FEE        = @json($homeVisitFee);
    const DAY_ORDER     = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const SESSION_STATUS_LABEL = { Scheduled: 'Terjadwal', Completed: 'Selesai', Cancelled: 'Dibatalkan' };
    const SESSION_STATUS_BADGE = { Scheduled: 'badge-cold', Completed: 'badge-completed', Cancelled: 'badge-cancelled' };
    const JOURNAL_STATUS_LABEL = { Conducted: 'Terlaksana', Cancelled: 'Dibatalkan', Rescheduled: 'Dijadwal ulang' };

    // "Kelas Atas", "kelas atas" dan "Atas" dianggap satu ruangan
    function normRoom(r) {
        return String(r ?? '').toLowerCase().replace(/\s+/g, ' ').trim().replace(/^kelas\s+/, '');
    }

    function canonicalRoom(r) {
        const key = normRoom(r);
        return ROOM_OPTIONS.find(o => normRoom(o) === key) || r || '';
    }

    const sesData = {};
    SESSIONS.forEach(s => { sesData[s.id] = s; });
    let rsSession = null;

    const TEACHER_SLOTS = @json($teacherSlots);

    const toMin  = t => { const [h, m] = String(t).split(':'); return (+h) * 60 + (+m); };
    const fmtMin = m => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');

    function mergeIntervals(list) {
        const sorted = list.filter(i => i.end > i.start).sort((a, b) => a.start - b.start);
        const out = [];
        sorted.forEach(i => {
            const last = out[out.length - 1];
            if (last && i.start <= last.end) last.end = Math.max(last.end, i.end);
            else out.push({ start: i.start, end: i.end });
        });
        return out;
    }

    function subtractIntervals(base, busy) {
        const res = [];
        base.forEach(b => {
            let cur = b.start;
            busy.forEach(x => {
                if (x.end <= cur || x.start >= b.end) return;
                if (x.start > cur) res.push({ start: cur, end: x.start });
                cur = Math.max(cur, x.end);
            });
            if (cur < b.end) res.push({ start: cur, end: b.end });
        });
        return res;
    }

    // Jam kosong guru pada satu hari.
    // "one"       -> bentrok dari pertemuan bertanggal di tanggal itu (+ tugas guru pengganti)
    // "following" -> bentrok dari pola mingguan kelas aktif guru
    function teacherDayInfo(s, dayName, scope, iso) {
        const key   = 't' + s.teacher_id;
        const avail = mergeIntervals((TEACHER_SLOTS.availability[key] || [])
            .filter(a => a.day === dayName)
            .map(a => ({ start: toMin(a.start), end: toMin(a.end) })));

        const raw = scope === 'one'
            ? (TEACHER_SLOTS.dated[key] || []).filter(b => b.date === iso && b.session_id !== s.id)
            : (TEACHER_SLOTS.weekly[key] || []).filter(b => b.day === dayName && b.schedule_id !== s.schedule_id);

        const busy = raw.map(b => ({ start: toMin(b.start), end: toMin(b.end), name: b.class_name }))
            .sort((a, b) => a.start - b.start);

        const free = subtractIntervals(avail, mergeIntervals(busy.map(({ start, end }) => ({ start, end }))));
        return { avail, busy, free };
    }

    function availTarget() {
        if (currentRescheduleScope() === 'following') {
            return { scope: 'following', day: document.getElementById('rs_day').value, iso: null };
        }
        const iso = document.getElementById('rs_date').value;
        return { scope: 'one', day: iso ? DAY_ORDER[weekdayIndex(iso)] : null, iso };
    }

    function chipsHtml(windows, dur, onclickFor) {
        return windows.map(w => {
            const label = `${fmtMin(w.start)}–${fmtMin(w.end)}`;
            return (w.end - w.start) >= dur
                ? `<button type="button" class="ta-chip" onclick="${onclickFor(w)}">${label}</button>`
                : `<span class="ta-chip short" title="Lebih pendek dari durasi pertemuan">${label}</span>`;
        }).join('');
    }

    function refreshAvailability() {
        const wrap     = document.getElementById('rs_avail');
        const body     = document.getElementById('rs_avail_body');
        const weekCard = document.getElementById('rs_week_card');
        const weekBody = document.getElementById('rs_week_body');
        const s = rsSession;

        if (! s) { wrap.style.display = 'none'; weekCard.style.display = 'none'; return; }
        wrap.style.display = '';

        if (! s.teacher_id) {
            body.innerHTML = '<div class="ta-none">Kelas ini belum punya guru, jadi ketersediaan guru belum bisa ditampilkan.</div>';
            weekCard.style.display = 'none';
            return;
        }

        const t = availTarget();
        if (! t.day) {
            body.innerHTML = '<div class="ta-none">Pilih tanggal baru untuk melihat ketersediaan guru.</div>';
            weekCard.style.display = 'none';
            return;
        }

        const dur   = toMin(s.end) - toMin(s.start);
        const start = toMin(document.getElementById('rs_start').value || s.start);
        const end   = start + dur;
        const info  = teacherDayInfo(s, t.day, t.scope, t.iso);

        // Status untuk jam yang sedang diisi
        let status;
        if (info.free.some(w => start >= w.start && end <= w.end)) {
            status = ['ok', `Jam ${fmtMin(start)}–${fmtMin(end)} tersedia untuk guru.`];
        } else {
            const clash = info.busy.find(b => start < b.end && end > b.start);
            if (clash) {
                status = ['bad', `Bentrok: guru mengajar ${clash.name} pukul ${fmtMin(clash.start)}–${fmtMin(clash.end)}.`];
            } else if (t.scope === 'following') {
                status = ['bad', `Jam ${fmtMin(start)}–${fmtMin(end)} di luar jam tersedia guru. Untuk "ini dan seterusnya" tidak bisa disimpan, minta guru mengisi ketersediaan dulu.`];
            } else {
                status = ['warn', `Jam ${fmtMin(start)}–${fmtMin(end)} di luar jam tersedia guru (boleh, centang konfirmasi kalau guru sudah setuju).`];
            }
        }

        const title = t.scope === 'following' ? `${t.day}, setiap minggu` : `${t.day}, ${formatDateId(t.iso)}`;

        const freeHtml = info.free.length
            ? chipsHtml(info.free, dur, w => `pickStart('${fmtMin(w.start)}')`)
            : `<span class="ta-none">${info.avail.length
                ? 'Semua jam tersedia guru sudah terpakai.'
                : 'Guru tidak mengisi jam tersedia di hari ini.'}</span>`;

        const busyHtml = info.busy.length
            ? `<div class="ta-busy">Sudah mengajar: ${info.busy.map(b =>
                `${escapeHtml(b.name)} ${fmtMin(b.start)}–${fmtMin(b.end)}`).join('; ')}</div>`
            : '';

        // Form (kanan): hanya status + jam kosong hari yang dipilih
        body.innerHTML = `
            <div class="ta-status ${status[0]}">${escapeHtml(status[1])}</div>
            <div class="ta-title">Jam kosong ${escapeHtml(title)} &middot; durasi pertemuan ${dur} menit</div>
            <div class="ta-chips">${freeHtml}</div>
            ${busyHtml}`;

        // Kartu kiri: ringkasan semua hari
        document.getElementById('rs_week_sub').textContent = (s.teacher || 'Guru') + ' · ' + (t.scope === 'following'
            ? 'pola mingguan (setiap minggu)'
            : `minggu ${formatDateId(sameWeekDate(t.iso, 0))} s/d ${formatDateId(sameWeekDate(t.iso, 6))}`);

        weekBody.innerHTML = DAY_ORDER.map((day, idx) => {
            const iso  = t.scope === 'one' ? sameWeekDate(t.iso, idx) : null;
            const past = iso && iso < todayIso();
            const i    = teacherDayInfo(s, day, t.scope, iso);
            const chips = past
                ? '<span class="ta-none">Sudah lewat</span>'
                : (i.free.length
                    ? chipsHtml(i.free, dur, w => `pickSlot(${idx}, '${fmtMin(w.start)}')`)
                    : '<span class="ta-none">Tidak ada jam kosong</span>');

            return `<div class="ta-row ${day === t.day ? 'is-current' : ''}">
                <div class="ta-day" ${past ? '' : `onclick="pickDay(${idx})"`}>${day}${iso ? `<small>${formatDateId(iso)}</small>` : ''}</div>
                <div class="ta-chips">${chips}</div>
            </div>`;
        }).join('');
        weekCard.style.display = '';
    }

    function pickStart(hhmm) {
        document.getElementById('rs_start').value = hhmm;
        refreshAvailability();
    }

    function pickDay(idx) {
        if (currentRescheduleScope() === 'following') {
            document.getElementById('rs_day').value = DAY_ORDER[idx];
            updateFollowingPreview();
        } else {
            const el = document.getElementById('rs_date');
            if (el.value) el.value = sameWeekDate(el.value, idx);
        }
        refreshAvailability();
    }

    function pickSlot(idx, hhmm) {
        pickDay(idx);
        pickStart(hhmm);
    }

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

        // Sudah berlangsung -> lihat jurnal mengajar; belum -> boleh diubah jadwalnya
        let action = '';
        if (s.started) {
            action = s.journal
                ? `<button type="button" class="btn btn-secondary btn-sm" onclick="showJournal(${s.id})">
                       <i class="fa-solid fa-book-open"></i> Detail Jurnal
                   </button>`
                : '';
        } else if (s.can_reschedule) {
            action = `<button type="button" class="btn btn-primary btn-sm" onclick="selectSession(${s.id})">Ubah Jadwal</button>`;
        }

        const noJournal = (! s.journal && s.is_past && s.status === 'Scheduled')
            ? '<span class="badge badge-pending">Jurnal belum diisi</span>'
            : '';

        const invoices = (s.payments || []).map(p =>
            `<a href="${attrEscape(p.invoice_url)}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-file-invoice"></i> Invoice
            </a>`).join('');

        return `<div class="ms-item" id="ses_item_${s.id}">
            <div>
                <div class="ms-item-name">Pertemuan ke-${s.number ?? '-'} &middot; ${escapeHtml(s.date_label)}</div>
                <div class="ses-meta">${escapeHtml(s.start)}–${escapeHtml(s.end)} &middot; ${escapeHtml(place)} &middot; ${escapeHtml(s.teacher || 'Belum ada guru')}${s.replacement ? ' &middot; <strong>Pengganti: ' + escapeHtml(s.replacement.name) + '</strong>' + (s.replacement.awaiting_curriculum ? ' <span class="badge badge-pending">Menunggu ACC Kurikulum</span>' : '') : ''}</div>
                ${changed}
            </div>
            <div class="ses-actions">
                ${noJournal}
                <span class="badge ${SESSION_STATUS_BADGE[s.status] || 'badge-pending'}">${escapeHtml(status)}</span>
                ${invoices}
                ${action}
            </div>
        </div>`;
    }

    (function renderSessions() {
        const list = document.getElementById('ses_list');
        list.innerHTML = SESSIONS.length
            ? SESSIONS.map(renderSessionItem).join('')
            : '<div class="ms-empty">Kelas ini belum punya pertemuan bertanggal.</div>';

        // Buka di pertemuan pertama yang belum berlangsung; pertemuan sebelumnya tetap ada di atasnya (scroll ke atas)
        const focusUpcoming = () => {
            const target = SESSIONS.find(s => ! s.started && s.status === 'Scheduled')
                || SESSIONS[SESSIONS.length - 1];
            const el = target && document.getElementById('ses_item_' + target.id);
            if (! el) return;
            list.scrollTop += el.getBoundingClientRect().top - list.getBoundingClientRect().top;
        };
        focusUpcoming();
        window.addEventListener('load', focusUpcoming); // ulangi setelah font/CSS selesai dimuat
    })();

    /* ============================================================
       DETAIL JURNAL MENGAJAR (pertemuan yang sudah berlangsung, hanya lihat)
    ============================================================ */
    function showJournal(sessionId) {
        const s = sesData[sessionId];
        if (! s || ! s.journal) return;
        const j = s.journal;

        rsSession = null;
        document.querySelectorAll('#ses_list .ms-item').forEach(el => el.classList.remove('is-selected'));
        document.getElementById('ses_item_' + s.id)?.classList.add('is-selected');

        const setText = (id, v) => { document.getElementById(id).textContent = v || '-'; };

        setText('jr_info', `Pertemuan ke-${s.number ?? '-'} — ${s.date_label}, ${s.start}–${s.end}`);
        setText('jr_status', JOURNAL_STATUS_LABEL[j.status] || j.status);
        setText('jr_teacher', j.teacher || s.teacher);
        setText('jr_activities', j.activities);
        setText('jr_problems', j.problems);
        setText('jr_solutions', j.solutions);
        setText('jr_results', j.results);

        setText('jr_sub', j.substitute);
        document.getElementById('jr_sub_wrap').style.display = j.substitute ? '' : 'none';

        setText('jr_material', j.material);
        document.getElementById('jr_material_wrap').style.display = j.material ? '' : 'none';

        document.getElementById('rs_placeholder').style.display = 'none';
        document.getElementById('rs_form').style.display = 'none';
        document.getElementById('jr_view').style.display = '';
        refreshAvailability();
        document.getElementById('rescheduleCard').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closeJournal() {
        document.querySelectorAll('#ses_list .ms-item').forEach(el => el.classList.remove('is-selected'));
        document.getElementById('jr_view').style.display = 'none';
        document.getElementById('rs_placeholder').style.display = '';
    }

    /* ============================================================
       FORM UBAH JADWAL
    ============================================================ */
    function selectSession(sessionId) {
        const s = sesData[sessionId];
        if (! s) return;
        rsSession = s;
        document.getElementById('jr_view').style.display = 'none';

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
        document.getElementById('rs_room').value = isStudio ? canonicalRoom(s.room) : '';
        document.getElementById('rs_room_list').innerHTML = ROOM_OPTIONS
            .map(r => `<option value="${attrEscape(r)}"></option>`).join('');

        // Hari awal = hari pertemuan saat ini
        document.getElementById('rs_day').value = DAY_ORDER[weekdayIndex(s.date)];

        document.getElementById('rs_confirm').checked = false;
        document.getElementById('rs_error').style.display = 'none';
        document.getElementById('rs_submit').disabled = false;

        resetHomeVisitForm();
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
        refreshAvailability();
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
        refreshHomeVisitUi();

        // Konfirmasi "di luar jam tersedia" hanya berlaku untuk satu pertemuan; "seterusnya" selalu ketat di server
        document.getElementById('rs_confirm_wrap').style.display = following ? 'none' : '';
        refreshAvailability();
    }

    function updateFollowingPreview() {
        const el = document.getElementById('rs_day_preview');
        if (! rsSession || currentRescheduleScope() !== 'following') { el.textContent = ''; return; }

        const idx = DAY_ORDER.indexOf(document.getElementById('rs_day').value);
        el.textContent = 'Pertemuan ini pindah ke ' + formatDateId(sameWeekDate(rsSession.date, idx))
            + ', pertemuan sesudahnya mengikuti hari yang sama tiap minggu.';
    }

    /* ============================================================
       HOME VISIT
    ============================================================ */
    function selectedHv() {
        return document.querySelector('input[name="rs_hv"]:checked').value;
    }

    function resetHomeVisitForm() {
        document.querySelector('input[name="rs_hv"][value="0"]').checked = true;
        document.getElementById('rs_hv_fee').textContent = 'Rp' + Number(HV_FEE).toLocaleString('id-ID');
        document.getElementById('rs_payer').innerHTML = PAYERS
            .map(p => `<option value="${p.enrollment_id}">${escapeHtml(p.name)}</option>`).join('');

        const pd = document.getElementById('rs_pay_date');
        pd.max = todayIso();
        pd.value = todayIso();
        document.getElementById('rs_pay_method').value = 'Transfer Bank';
        document.getElementById('rs_pay_proof').value = '';

        // Lokasi awal = lokasi pertemuan sekarang
        const cur = rsSession && document.querySelector(`input[name="rs_loc"][value="${rsSession.location_type}"]`);
        if (cur) cur.checked = true;
    }

    function refreshHomeVisitUi() {
        const s = rsSession;
        if (! s) return;

        const following = currentRescheduleScope() === 'following';
        const alreadyHv = s.location_type === 'Home Visit';
        const noPayer   = PAYERS.length === 0;
        const offered   = ! alreadyHv && s.can_home_visit && ! following && ! noPayer;

        document.getElementById('rs_hv_done').style.display = alreadyHv ? '' : 'none';
        document.getElementById('rs_hv_wrap').style.display = offered ? '' : 'none';

        let note = '';
        if (! alreadyHv && s.can_home_visit && following) {
            note = 'Home visit dikenakan per pertemuan, jadi hanya tersedia untuk "Pertemuan ini saja".';
        } else if (! alreadyHv && s.can_home_visit && noPayer) {
            note = 'Home visit belum bisa dipilih: tidak ada siswa aktif di kelas ini sebagai pembayar.';
        }
        document.getElementById('rs_hv_note').textContent = note;
        document.getElementById('rs_hv_note_wrap').style.display = note ? '' : 'none';

        if (! offered) {
            document.querySelector('input[name="rs_hv"][value="0"]').checked = true;
        }

        const on = offered && selectedHv() === '1';
        document.querySelectorAll('.rs-hv-field').forEach(el => el.style.display = on ? '' : 'none');

        // Pilihan lokasi: hanya untuk pertemuan yang home visit-nya sudah dibayar, dan hanya satu pertemuan
        const hvPaid = !! s.hv_paid && ! following;
        document.getElementById('rs_loc_wrap').style.display = hvPaid ? '' : 'none';

        const locEl = document.querySelector('input[name="rs_loc"]:checked');
        const loc   = (hvPaid && locEl) ? locEl.value : s.location_type;

        // Ruangan hanya untuk pertemuan di studio (dan tidak relevan kalau baru dijadikan Home Visit)
        document.getElementById('rs_room_field').style.display = (loc === 'Studio' && ! on) ? '' : 'none';
    }

    function showRescheduleError(messages) {
        const box = document.getElementById('rs_error');
        box.innerHTML = messages.map(m => `<div>${escapeHtml(m)}</div>`).join('');
        box.style.display = '';
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function submitReschedule() {
        const s = rsSession;
        if (! s) return;

        const scope     = currentRescheduleScope();
        const following = scope === 'following';
        const date      = document.getElementById('rs_date').value;
        const start     = document.getElementById('rs_start').value;
        const hv        = ! following && selectedHv() === '1' && s.can_home_visit;

        if (! start || (! following && ! date)) {
            showRescheduleError([following ? 'Hari dan jam mulai wajib diisi.' : 'Tanggal dan jam mulai wajib diisi.']);
            return;
        }

        const proof = document.getElementById('rs_pay_proof').files[0];
        if (hv && ! proof) {
            showRescheduleError(['Bukti pembayaran home visit wajib diunggah.']);
            return;
        }

        // Pakai FormData (ada upload bukti). POST + _method=PUT supaya route PUT tidak perlu diubah.
        const form = new FormData();
        form.append('_method', 'PUT');
        form.append('scope', scope);
        form.append('start_time', start);
        form.append('confirm_outside_availability', document.getElementById('rs_confirm').checked ? '1' : '0');

        const reason = document.getElementById('rs_reason').value.trim();
        if (reason) form.append('reason', reason);

        if (following) {
            form.append('day', document.getElementById('rs_day').value);
        } else {
            form.append('session_date', date);
        }

        if (hv) {
            form.append('home_visit', '1');
            form.append('payer_enrollment_id', document.getElementById('rs_payer').value);
            form.append('payment_date', document.getElementById('rs_pay_date').value);
            form.append('payment_method', document.getElementById('rs_pay_method').value);
            form.append('proof', proof);
        } else {
            const hvPaid = !! s.hv_paid && ! following;
            const locEl  = document.querySelector('input[name="rs_loc"]:checked');
            const loc    = (hvPaid && locEl) ? locEl.value : s.location_type;
            const room   = canonicalRoom(document.getElementById('rs_room').value.trim());

            if (hvPaid && loc !== s.location_type) {
                form.append('location_type', loc);
            }

            if (loc === 'Studio') {
                if (! room) {
                    showRescheduleError(['Pilih ruangan untuk pertemuan di kantor.']);
                    return;
                }
                form.append('room', room);
            }
        }

        const btn = document.getElementById('rs_submit');
        btn.disabled = true;
        document.getElementById('rs_error').style.display = 'none';

        fetch(URL_RESCHEDULE.replace('__ID__', s.id), {
            method: 'POST',
            redirect: 'manual',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                // Content-Type jangan diisi: browser mengisinya beserta boundary multipart
            },
            body: form,
        })
            .then(async (r) => {
                // Server membalas redirect: pesan (sukses/error) ada di flash session,
                // jadi reload supaya tampil di bagian atas halaman.
                if (r.type === 'opaqueredirect') {
                    window.location.reload();
                    return;
                }

                const data = await r.json().catch(() => null);

                // Sukses hanya kalau server benar-benar membalas JSON berisi message
                if (r.ok && data && data.message) {
                    window.location.reload(); // pesan sukses tampil lewat flash session
                    return;
                }

                const errors   = (data && data.errors) || {};
                const messages = Object.values(errors).flat();

                // Di luar jam tersedia guru: sorot centang konfirmasi, admin simpan ulang
                if (errors.availability) {
                    const wrap = document.getElementById('rs_confirm_wrap');
                    wrap.style.display = 'block';
                    wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                showRescheduleError(messages.length
                    ? messages
                    : [r.status === 419
                        ? 'Sesi habis, muat ulang halaman lalu coba lagi.'
                        : ((data && data.message) || `Respons tak terduga dari server (status ${r.status}).`)]);
                btn.disabled = false;
            })
            .catch(() => {
                showRescheduleError(['Gagal menghubungi server.']);
                btn.disabled = false;
            });
    }
</script>
@endpush