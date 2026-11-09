{{-- resources/views/admin/siswa/kelaspengganti.blade.php --}}
@extends('admin.app')

@section('title', 'Kelas Pengganti | Siswa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK &raquo; SISWA</div>
            <h1>Kelas Pengganti</h1>
            <p>Tambah satu pertemuan pengganti untuk <strong>{{ $siswa->name }}</strong></p>
        </div>

        <div class="dashboard-header-actions">
            <a href="{{ route('admin.siswa') }}" class="btn">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Siswa
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rp-alert" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rp-alert">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="rp-alert">
            Data belum bisa disimpan:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.siswa.replacement.store', $siswa) }}" enctype="multipart/form-data" id="replacementForm"
          data-fee-replacement="{{ (float) $fees['replacement'] }}"
          data-fee-home="{{ (float) $fees['home_visit'] }}"
          data-teachers-url="{{ route('admin.siswa.replacement.teachers') }}"
          data-old-teachers="{{ implode(',', (array) old('offer_teacher_ids', [])) }}"
          data-old-end="{{ old('end_time') ? '1' : '0' }}">
        @csrf

        <div class="rp-layout">

            <div>
                {{-- ===== 1. Pendaftaran & jadwal ===== --}}
                <div class="rp-card">
                    <div class="form-section" style="margin-bottom:0;">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-plus"></i> Pertemuan Pengganti</div>

                        <div class="form-grid">
                            <div class="form-field full">
                                <label for="class_enrollment_id">Untuk kelas *</label>
                                <select name="class_enrollment_id" id="class_enrollment_id" required>
                                    @foreach ($enrollments as $e)
                                        <option value="{{ $e['id'] }}"
                                                data-duration="{{ $e['duration'] }}"
                                                @selected((int) old('class_enrollment_id', $enrollments->first()['id']) === $e['id'])>
                                            {{ $e['class_name'] }} &mdash; {{ $e['program_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="session_date">Tanggal *</label>
                                <input type="date" name="session_date" id="session_date"
                                       min="{{ today()->toDateString() }}" value="{{ old('session_date') }}" required>
                            </div>

                            <div class="form-field">
                                <label for="start_time">Jam mulai *</label>
                                <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" required>
                            </div>

                            <div class="form-field">
                                <label for="end_time">Jam selesai *</label>
                                <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" required>
                                <div class="rp-hint" id="end_time_hint">Terisi otomatis dari durasi paket, bisa diubah.</div>
                            </div>

                            <div class="form-field">
                                <label for="location_type">Lokasi *</label>
                                <select name="location_type" id="location_type" required>
                                    @foreach (['Studio' => 'Studio', 'Online' => 'Online', 'Home Visit' => 'Guru ke rumah (Home Visit)'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('location_type', 'Studio') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field full" id="room_wrap">
                                <label for="room" id="room_label">Ruangan</label>
                                <input type="text" name="room" id="room" maxlength="255" value="{{ old('room') }}"
                                       placeholder="Mis. Beijing, Basement, Atas, Kaca">
                            </div>

                            <div class="form-field full">
                                <label>Ajukan ke guru *</label>

                                <div class="rp-modes">
                                    <label class="rp-mode">
                                        <input type="radio" name="offer_mode" value="matching" @checked(old('offer_mode', 'matching') === 'matching')>
                                        <span>Guru yang cocok jadwal <span class="rp-hint" id="count_matching"></span></span>
                                    </label>
                                    <label class="rp-mode">
                                        <input type="radio" name="offer_mode" value="selected" @checked(old('offer_mode') === 'selected')>
                                        <span>Pilih guru tertentu</span>
                                    </label>
                                    <label class="rp-mode">
                                        <input type="radio" name="offer_mode" value="all" @checked(old('offer_mode') === 'all')>
                                        <span>Semua guru aktif</span>
                                    </label>
                                </div>

                                <div id="teacher_list" class="rp-teacher-list" style="display:none;"></div>

                                <div class="rp-hint" id="teacher_hint">Isi tanggal dan jam dulu untuk melihat guru yang cocok.</div>
                                <div class="rp-note">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Pertemuan ini akan diajukan ke guru.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== 2. Pembayaran ===== --}}
                <div class="rp-card">
                    <div class="form-section" style="margin-bottom:0;">
                        <div class="form-section-title"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</div>

                        <div class="form-grid">
                            <div class="form-field">
                                <label for="payment_date">Tanggal bayar *</label>
                                <input type="date" name="payment_date" id="payment_date"
                                       max="{{ today()->toDateString() }}" value="{{ old('payment_date', today()->toDateString()) }}" required>
                            </div>

                            <div class="form-field">
                                <label for="payment_method">Metode *</label>
                                <select name="payment_method" id="payment_method" required>
                                    @foreach ($paymentMethods as $m)
                                        <option value="{{ $m }}" @selected(old('payment_method') === $m)>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field full">
                                <label for="proof">Bukti pembayaran *</label>
                                <input type="file" name="proof" id="proof" accept=".jpg,.jpeg,.png,.pdf" required>
                                <div class="rp-hint">JPG, PNG, atau PDF, maksimal 4 MB.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rp-actions">
                    <a href="{{ route('admin.siswa') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fa-solid fa-check"></i>
                        Simpan &amp; Ajukan ke Guru
                    </button>
                </div>
            </div>

            {{-- ===== Ringkasan biaya ===== --}}
            <aside class="rp-side">
                <div class="rp-card">
                    <div class="form-section-title"><i class="fa-solid fa-receipt"></i> Ringkasan Biaya</div>

                    <div class="rp-line">
                        <span>Kelas pengganti</span>
                        <span id="fee_replacement">Rp {{ number_format($fees['replacement'], 0, ',', '.') }}</span>
                    </div>
                    <div class="rp-line" id="fee_home_row" style="display:none;">
                        <span>Guru ke rumah</span>
                        <span>Rp {{ number_format($fees['home_visit'], 0, ',', '.') }}</span>
                    </div>
                    <div class="rp-line total">
                        <span>Total dibayar</span>
                        <span id="fee_total">Rp {{ number_format($fees['replacement'], 0, ',', '.') }}</span>
                    </div>

                    <div class="rp-note" id="invoice_note">Satu pertemuan, satu invoice.</div>
                    <div class="rp-note">Harga otomatis terisi</div>
                </div>
            </aside>
        </div>
    </form>

    {{-- ===== Riwayat ===== --}}
    <div class="rp-card" style="margin-top:20px;">
        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Kelas Pengganti</div>

            <table class="table-mini">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Guru</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Invoice</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusLabels = [
                            'Scheduled' => 'Terjadwal',
                            'Conducted' => 'Selesai',
                            'Cancelled' => 'Dibatalkan',
                            'Rescheduled' => 'Dijadwal ulang',
                        ];

                        $offerLabels = [
                            'Pending'  => 'menunggu jawaban',
                            'Accepted' => 'menerima',
                            'Rejected' => 'menolak',
                            'Closed'   => 'ditutup',
                        ];
                    @endphp

                    @forelse ($history as $h)
                        <tr>
                            <td>{{ $h['date'] }}</td>
                            <td>{{ $h['time'] }}</td>
                            <td>{{ $h['teacher'] ?? '-' }}</td>
                            <td>
                                {{ $h['location_type'] }}
                                @if (! empty($h['room']))
                                    <div class="rp-hint">{{ $h['room'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($h['awaiting'])
                                    <strong>Menunggu guru</strong>
                                    @foreach ($h['offers'] as $o)
                                        <div class="rp-hint">
                                            {{ $o['teacher'] }}:
                                            {{ $offerLabels[$o['status']] ?? $o['status'] }}@if ($o['status'] === 'Rejected' && $o['note']) ({{ $o['note'] }})@endif
                                        </div>
                                    @endforeach
                                @else
                                    {{ $statusLabels[$h['status']] ?? $h['status'] }}
                                @endif
                            </td>
                            <td>
                                @forelse ($h['payments'] as $p)
                                    <div>
                                        <a href="{{ $p['invoice_url'] }}" target="_blank" rel="noopener">{{ $p['invoice_number'] ?? 'Invoice' }}</a>
                                        <span class="rp-hint">Rp {{ number_format($p['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td>
                                @if ($h['awaiting'])
                                    <form method="POST" action="{{ $h['reoffer_url'] }}" class="rp-inline-form">
                                        @csrf
                                        <select name="mode">
                                            <option value="matching">Guru yang cocok</option>
                                            <option value="all">Semua guru</option>
                                        </select>
                                        <button type="submit" class="rp-btn-sm">Ajukan ulang</button>
                                    </form>
                                    <form method="POST" action="{{ $h['cancel_url'] }}" class="rp-inline-form"
                                          onsubmit="return confirm('Batalkan kelas pengganti ini? Pembayaran tetap tercatat dan pengembalian dana diurus manual.')">
                                        @csrf
                                        <button type="submit" class="rp-btn-sm danger">Batalkan</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Belum ada kelas pengganti.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const el = id => document.getElementById(id);

            // Data dari server dibaca lewat atribut data-* di <form>, jadi tidak ada sintaks Blade di dalam JS
            const form            = el('replacementForm');
            const FEE_REPLACEMENT = Number(form.dataset.feeReplacement);
            const FEE_HOME_VISIT  = Number(form.dataset.feeHome);
            const TEACHERS_URL    = form.dataset.teachersUrl;
            const OLD_TEACHERS    = (form.dataset.oldTeachers || '').split(',').filter(Boolean);

            const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');

            const enrollmentSel = el('class_enrollment_id');
            const dateInput     = el('session_date');
            const startInput    = el('start_time');
            const endInput      = el('end_time');
            const locationSel   = el('location_type');
            const teacherBox    = el('teacher_list');

            let endTouched = form.dataset.oldEnd === '1';
            let teacherReq = 0;

            /* ---------- jam selesai otomatis dari durasi paket ---------- */
            function addMinutes(hhmm, minutes) {
                const [h, m] = hhmm.split(':').map(Number);
                const total = h * 60 + m + minutes;
                if (total >= 24 * 60) return null;
                return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
            }

            function autoEnd() {
                if (endTouched || !startInput.value) return;
                const duration = Number(enrollmentSel.selectedOptions[0]?.dataset.duration || 60);
                const end = addMinutes(startInput.value, duration);
                if (end) endInput.value = end;
            }

            /* ---------- lokasi: label ruangan & biaya home visit ---------- */
            function syncLocation() {
                const type = locationSel.value;
                const wrap = el('room_wrap');

                wrap.style.display = type === 'Online' ? 'none' : '';
                el('room_label').textContent = type === 'Home Visit' ? 'Alamat / catatan lokasi' : 'Ruangan';
                el('room').placeholder = type === 'Home Visit'
                    ? 'Alamat siswa'
                    : 'Mis. Beijing, Basement, Atas, Kaca';
                if (type === 'Online') el('room').value = '';

                const home = type === 'Home Visit';
                el('fee_home_row').style.display = home ? '' : 'none';
                el('fee_total').textContent = rupiah(FEE_REPLACEMENT + (home ? FEE_HOME_VISIT : 0));
                el('invoice_note').textContent = home
                    ? 'Home visit membuat dua pembayaran dan dua invoice: kelas pengganti dan guru ke rumah.'
                    : 'Satu pertemuan, satu invoice.';
            }

            /* ---------- ajukan ke guru ---------- */
            let teacherList = [];
            let teacherLoaded = false;
            const chosen = new Set(OLD_TEACHERS);      // id guru yang dicentang (mode "pilih guru tertentu")
            const modeInputs = document.querySelectorAll('input[name="offer_mode"]');

            const currentMode = () => document.querySelector('input[name="offer_mode"]:checked')?.value || 'matching';

            function groupTitle(text) {
                const d = document.createElement('div');
                d.className = 'rp-teacher-group';
                d.textContent = text;
                return d;
            }

            function teacherRow(t, preview = false) {
                const row = document.createElement('label');
                row.className = 'rp-teacher-item' + (t.free ? '' : ' is-off') + (preview ? ' is-preview' : '');

                if (!preview) {
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.name = 'offer_teacher_ids[]';
                    cb.value = t.id;
                    cb.disabled = !t.free;                 // bentrok tidak bisa diajukan
                    cb.checked = t.free && chosen.has(String(t.id));
                    cb.addEventListener('change', () => {
                        cb.checked ? chosen.add(String(t.id)) : chosen.delete(String(t.id));
                        updateHint();
                    });
                    row.appendChild(cb);
                }

                const name = document.createElement('span');
                // alasan hanya untuk guru bentrok; grup sudah menjelaskan "di luar jam"
                name.textContent = t.name + (!t.free && t.reason ? ' (' + t.reason + ')' : '');
                row.appendChild(name);
                return row;
            }

            function renderTeachers() {
                const ok   = teacherList.filter(t => t.free && t.available);
                const off  = teacherList.filter(t => t.free && !t.available);
                const busy = teacherList.filter(t => !t.free);
                const mode = currentMode();
                const preview = mode !== 'selected';   // matching/all: daftar hanya info, tidak ikut dikirim

                el('count_matching').textContent = teacherLoaded ? '(' + ok.length + ' guru)' : '';

                teacherBox.innerHTML = '';
                const addGroup = (title, items) => {
                    if (!items.length) return;
                    teacherBox.appendChild(groupTitle(title));
                    items.forEach(t => teacherBox.appendChild(teacherRow(t, preview)));
                };

                if (mode === 'matching') {
                    addGroup('Akan diajukan ke (cocok jadwal)', ok);
                } else if (mode === 'all') {
                    addGroup('Cocok jadwal', ok);
                    addGroup('Di luar jam tersedia', off);
                    addGroup('Bentrok (akan gagal saat menerima)', busy);
                } else {
                    addGroup('Cocok jadwal', ok);
                    addGroup('Di luar jam tersedia', off);
                    addGroup('Bentrok (tidak bisa dipilih)', busy);
                }

                if (!teacherBox.children.length && teacherLoaded) {
                    const empty = document.createElement('div');
                    empty.className = 'rp-teacher-group';
                    empty.textContent = 'Belum ada guru yang cocok di jam ini.';
                    teacherBox.appendChild(empty);
                }

                teacherBox.style.display = teacherLoaded ? '' : 'none';
                updateHint();
            }

            function updateHint() {
                const hint = el('teacher_hint');
                hint.classList.remove('warn');

                if (!teacherLoaded) {
                    hint.textContent = 'Isi tanggal dan jam dulu untuk melihat guru yang cocok.';
                    return;
                }

                const ok  = teacherList.filter(t => t.free && t.available).length;
                const mode = currentMode();

                if (mode === 'matching') {
                    hint.textContent = ok
                        ? 'Diajukan ke ' + ok + ' guru yang jam tersedianya cocok dan tidak bentrok.'
                        : 'Belum ada guru yang cocok di jam ini. Pilih "Semua guru aktif" atau pilih guru tertentu.';
                    if (!ok) hint.classList.add('warn');
                } else if (mode === 'all') {
                    hint.textContent = 'Diajukan ke ' + teacherList.length + ' guru aktif. Guru yang bentrok akan gagal saat menerima.';
                } else {
                    const n = [...teacherBox.querySelectorAll('input:checked')].length;
                    hint.textContent = n ? n + ' guru dipilih.' : 'Centang minimal satu guru.';
                    if (!n) hint.classList.add('warn');
                }
            }

            async function loadTeachers() {
                const date = dateInput.value, start = startInput.value, end = endInput.value;
                const hint = el('teacher_hint');

                if (!date || !start || !end || end <= start) {
                    teacherLoaded = false;
                    teacherList = [];
                    teacherBox.style.display = 'none';
                    el('count_matching').textContent = '';
                    updateHint();
                    return;
                }

                const req = ++teacherReq;
                hint.classList.remove('warn');
                hint.textContent = 'Memuat guru...';

                try {
                    const qs = new URLSearchParams({ session_date: date, start_time: start, end_time: end });
                    const res = await fetch(TEACHERS_URL + '?' + qs, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const list = await res.json();
                    if (req !== teacherReq) return; // ada permintaan yang lebih baru

                    teacherList = list;
                    teacherLoaded = true;
                    renderTeachers();
                } catch (e) {
                    if (req !== teacherReq) return;
                    teacherLoaded = false;
                    hint.textContent = 'Daftar guru gagal dimuat. Muat ulang halaman lalu coba lagi.';
                    hint.classList.add('warn');
                }
            }

            endInput.addEventListener('input', () => { endTouched = true; });
            enrollmentSel.addEventListener('change', () => { endTouched = false; autoEnd(); loadTeachers(); });
            startInput.addEventListener('change', () => { autoEnd(); loadTeachers(); });
            endInput.addEventListener('change', loadTeachers);
            dateInput.addEventListener('change', loadTeachers);
            locationSel.addEventListener('change', syncLocation);
            modeInputs.forEach(r => r.addEventListener('change', renderTeachers));

            form.addEventListener('submit', function () {
                const btn = el('submitBtn');
                btn.disabled = true;           // cegah double submit (bisa membuat dua pembayaran)
                btn.style.opacity = '.7';
            });

            syncLocation();
            autoEnd();
            loadTeachers();
        })();
    </script>
@endpush