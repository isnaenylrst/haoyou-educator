@extends('layouts.guru')

@section('title','Sertifikat')

@section('content')

@php
    use App\Http\Controllers\Guru\CertificateController as CertCtl;

    $skillNames = ['listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing', 'speaking' => 'Speaking'];
    $n = fn ($v) => is_null($v) ? '-' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
            <h2 class="fw-bold mb-0">Sertifikat</h2>
            <a href="{{ route('teacher.sertifikat-kategori.index') }}" class="btn btn-outline-secondary btn-sm">Kelola Kategori Penilaian</a>
        </div>
        <p class="text-muted mb-0">
            Buat sertifikat Mock Test siswa. Pilih <strong>kategori penilaian</strong> (HSK, Reguler, atau kategori buatan guru),
            lalu isi nilainya. Tanggal dan nilai terisi otomatis dari progress report bila tersedia, tetapi tetap bisa Anda ubah.
            Total nilai dihitung otomatis (dijumlahkan). Sertifikat tampil ke siswa setelah di-ACC Kurikulum.
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== TAMBAH SERTIFIKAT ===== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Tambah Sertifikat</h5>

            @if ($enrollments->isEmpty())
                <div class="text-muted small">
                    Tidak ada siswa yang bisa dibuatkan sertifikat. Semua siswa di kelas Anda sudah punya sertifikat,
                    atau belum ada siswa di kelas yang sudah Anda terima.
                </div>
            @else
                <form method="POST" action="{{ route('teacher.sertifikat.store') }}" class="row g-3 align-items-end js-cert-form">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Siswa &amp; Kelas</label>
                        <select name="enrollment_id" id="enrollment_id" class="form-select" required>
                            <option value="">Pilih siswa</option>
                            @foreach ($enrollments as $e)
                                <option value="{{ $e->id }}" @selected((string) old('enrollment_id') === (string) $e->id)>
                                    {{ $e->student_name }} — {{ $e->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Nama Kursus / Level di Sertifikat</label>
                        <input type="text" name="course_name" id="course_name" maxlength="150"
                               value="{{ old('course_name') }}" class="form-control js-course" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Kategori Penilaian</label>
                        <select name="category" id="category" class="form-select js-category" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $key => $cat)
                                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $cat['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Tanggal Mulai Les</label>
                        <input type="date" name="start_date" id="start_date"
                               value="{{ old('start_date') }}" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Tanggal Selesai Les</label>
                        <input type="date" name="end_date" id="end_date"
                               value="{{ old('end_date') }}" class="form-control" required>
                    </div>

                    <div class="col-12"></div>

                    @foreach ($skillNames as $key => $label)
                        <div class="col-6 col-md-2 js-skill-wrap" data-skill="{{ $key }}">
                            <label class="form-label small fw-semibold mb-1">
                                <span class="js-skill-label">{{ $label }}</span> <span class="text-muted">(maks 100)</span>
                            </label>
                            <input type="number" name="{{ $key }}_score" id="{{ $key }}_score" data-skill="{{ $key }}"
                                   step="0.1" min="0" max="100" value="{{ old($key . '_score') }}"
                                   class="form-control js-skill" placeholder="0 - 100">
                        </div>
                    @endforeach

                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold mb-1">Total Nilai</label>
                        <input type="text" class="form-control bg-light js-total" readonly placeholder="otomatis">
                    </div>

                    <div class="col-md-2 d-grid">
                        <button class="btn btn-warning fw-bold">Buat &amp; Ajukan</button>
                    </div>

                    <div class="col-12">
                        <div class="small js-hint"></div>
                        <div class="small text-muted">
                            Kelas HSK: kategori terpilih otomatis dari nama kelas. Kelas Reguler dan Privat: pilih kategori sendiri
                            (untuk Privat, sesuaikan dengan ujian yang diminta siswa). Jika ada nilai yang masih kosong, isi manual.<br>
                            @foreach ($categories as $cat){{ $cat['label'] }}: maks {{ $cat['max'] }}, lulus min. {{ $cat['passing'] }}@if (!$loop->last) · @endif @endforeach
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== DAFTAR SERTIFIKAT ===== --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Sertifikat yang Saya Buat</h5>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Kursus</th>
                            <th>Periode Les</th>
                            <th>Nilai</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($certificates as $c)
                        @php
                            $courseText = $c->course_name ?: ($c->enrollment->class->class_name ?? '');
                            $catKey     = CertCtl::categoryOf($c->category, $courseText);
                            $cat        = $categories[$catKey];
                            $storedKey  = isset($categories[$c->category ?? '']) ? $c->category : '';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->student->name ?? '-' }}</div>
                                <div class="small text-muted">No. {{ $c->certificate_number }}</div>
                            </td>
                            <td>
                                {{ $courseText ?: '-' }}
                                <div><span class="badge bg-light text-dark border">{{ $cat['label'] }}</span></div>
                                @unless ($storedKey)
                                    <div><span class="badge bg-danger">Kategori belum tersimpan. Klik Edit &amp; pilih ulang</span></div>
                                @endunless
                            </td>
                            <td class="small">
                                {{ $c->start_date->translatedFormat('d M Y') }}<br>
                                s/d {{ $c->end_date->translatedFormat('d M Y') }}
                            </td>
                            <td class="small text-nowrap">
                                @foreach ($cat['skills'] as $sk => $lbl)
                                    {{ $lbl }}: {{ $n($c->{$sk . '_score'}) }}@if (!$loop->last)<br>@endif
                                @endforeach
                            </td>
                            <td class="fw-semibold text-nowrap">{{ $n($c->score) }} <span class="small text-muted fw-normal">/ {{ $cat['max'] }}</span></td>
                            <td>
                                @if ($c->status === 'Approved')
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif ($c->status === 'Rejected')
                                    <span class="badge bg-danger">Ditolak</span>
                                    @if ($c->review_note)
                                        <div class="small text-danger mt-1">Catatan Kurikulum: {{ $c->review_note }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-warning text-dark">Menunggu ACC</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($c->file_path)
                                    <a href="{{ route('teacher.sertifikat.download', $c) }}"
                                       class="btn btn-outline-secondary btn-sm">Unduh</a>
                                @endif

                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="document.getElementById('edit{{ $c->id }}').classList.toggle('d-none')">
                                    Edit
                                </button>

                                <form method="POST" action="{{ route('teacher.sertifikat.destroy', $c) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $c->status === 'Approved' ? 'Sertifikat ini sudah disetujui. Jika dihapus, siswa tidak bisa melihatnya lagi. Lanjutkan?' : 'Hapus sertifikat ini?' }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        {{-- Form edit --}}
                        <tr id="edit{{ $c->id }}" class="d-none">
                            <td colspan="7" class="bg-light">
                                <form method="POST" action="{{ route('teacher.sertifikat.update', $c) }}" class="row g-2 align-items-end js-cert-form">
                                    @csrf @method('PUT')

                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Nama Kursus / Level</label>
                                        <input type="text" name="course_name" maxlength="150" class="form-control form-control-sm js-course"
                                               value="{{ $courseText }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1">Kategori Penilaian</label>
                                        <select name="category" class="form-select form-select-sm js-category" required>
                                            <option value="">Pilih kategori</option>
                                            @foreach ($categories as $key => $opt)
                                                <option value="{{ $key }}" @selected($storedKey === $key)>{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold mb-1">Mulai</label>
                                        <input type="date" name="start_date" class="form-control form-control-sm"
                                               value="{{ $c->start_date->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold mb-1">Selesai</label>
                                        <input type="date" name="end_date" class="form-control form-control-sm"
                                               value="{{ $c->end_date->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-12"></div>

                                    @foreach ($skillNames as $key => $label)
                                        <div class="col-6 col-md-2 js-skill-wrap" data-skill="{{ $key }}">
                                            <label class="form-label small fw-semibold mb-1 js-skill-label">{{ $label }}</label>
                                            <input type="number" name="{{ $key }}_score" data-skill="{{ $key }}" step="0.1" min="0" max="100"
                                                   class="form-control form-control-sm js-skill" value="{{ $c->{$key . '_score'} }}">
                                        </div>
                                    @endforeach

                                    <div class="col-6 col-md-2">
                                        <label class="form-label small fw-semibold mb-1">Total</label>
                                        <input type="text" class="form-control form-control-sm bg-light js-total" readonly>
                                    </div>

                                    <div class="col-md-4 d-flex gap-1">
                                        <button class="btn btn-warning btn-sm fw-bold">Simpan &amp; Ajukan Ulang</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="document.getElementById('edit{{ $c->id }}').classList.add('d-none')">Batal</button>
                                    </div>
                                    <div class="col-12 small js-hint"></div>
                                    <div class="col-12 small text-muted">
                                        Setelah disimpan, PDF dibuat ulang (total dihitung otomatis) dan sertifikat dikirim lagi
                                        ke Kurikulum untuk di-ACC (status kembali menjadi menunggu).
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada sertifikat.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    const prefill = @json($prefill);
    const CATS    = @json($categories);
    const ALL     = ['listening', 'reading', 'writing', 'speaking'];

    // Tebak kategori dari nama kursus (hanya HSK). '' jika tidak terdeteksi.
    function detectCategory(text) {
        const m = /HSK\s*([1-6])/i.exec(text || '');
        if (!m) return '';
        return parseInt(m[1], 10) <= 2 ? 'hsk12' : 'hsk36';
    }

    function refreshForm(form) {
        const sel   = form.querySelector('.js-category');
        const cat   = sel ? (CATS[sel.value] || null) : null;
        const total = form.querySelector('.js-total');
        const hint  = form.querySelector('.js-hint');

        ALL.forEach(s => {
            const wrap = form.querySelector('.js-skill-wrap[data-skill="' + s + '"]');
            if (!wrap) return;
            const input = wrap.querySelector('.js-skill');
            const on    = !!(cat && cat.skills[s]);

            wrap.classList.toggle('d-none', !on);
            input.disabled = !on;   // tidak terkirim & tidak wajib bila disembunyikan
            input.required = on;

            if (on) {
                const lbl = wrap.querySelector('.js-skill-label');
                if (lbl) lbl.textContent = cat.skills[s];
            }
        });

        if (!cat) {
            total.value = '';
            hint.className = 'small js-hint text-muted';
            hint.textContent = 'Pilih kategori penilaian terlebih dahulu.';
            return;
        }

        const keys = Object.keys(cat.skills);
        const vals = keys.map(s => parseFloat(form.querySelector('.js-skill[data-skill="' + s + '"]').value));
        let sum = null;
        if (!vals.some(isNaN)) sum = vals.reduce((a, b) => a + b, 0);

        total.value = sum === null ? '' : sum.toFixed(1) + ' / ' + cat.max;

        let text = 'Standar kelulusan: minimal ' + cat.passing + ' dari ' + cat.max + '.';
        let cls  = 'text-muted';
        if (sum !== null) {
            if (sum >= cat.passing) { text += ' Memenuhi standar kelulusan.'; cls = 'text-success'; }
            else                    { text += ' Belum memenuhi standar kelulusan.'; cls = 'text-danger'; }
        }
        hint.className = 'small js-hint ' + cls;
        hint.textContent = text;
    }

    document.querySelectorAll('.js-cert-form').forEach(form => {
        form.addEventListener('input', () => refreshForm(form));
        form.addEventListener('change', () => refreshForm(form));

        // Jika nama kursus mengandung HSK, kategori ikut terpilih otomatis.
        const course = form.querySelector('.js-course');
        const sel    = form.querySelector('.js-category');
        if (course && sel) {
            course.addEventListener('input', () => {
                const d = detectCategory(course.value);
                if (d) { sel.value = d; refreshForm(form); }
            });
        }

        refreshForm(form);
    });

    const selEl = document.getElementById('enrollment_id');

    function fillFromEnrollment() {
        const d = prefill[selEl.value];
        if (!d) return;
        const form = selEl.closest('form');
        document.getElementById('course_name').value = d.course ?? '';
        document.getElementById('category').value    = d.category ?? '';
        document.getElementById('start_date').value  = d.start ?? '';
        document.getElementById('end_date').value    = d.end ?? '';
        ALL.forEach(s => document.getElementById(s + '_score').value = d[s] ?? '');
        refreshForm(form);
    }

    if (selEl) {
        selEl.addEventListener('change', fillFromEnrollment);
        @if (!old('enrollment_id'))
            fillFromEnrollment();
        @endif
    }
</script>

@endsection