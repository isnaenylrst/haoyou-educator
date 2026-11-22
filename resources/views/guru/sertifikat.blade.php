@extends('layouts.guru')

@section('title','Sertifikat')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Sertifikat</h2>
        <p class="text-muted mb-0">
            Buat sertifikat Mock Test HSK siswa. Tanggal dan nilai Listening, Reading, Writing terisi otomatis
            dari progress report, tetapi tetap bisa Anda ubah. Total nilai dihitung otomatis (dijumlahkan).
            Sertifikat tampil ke siswa setelah di-ACC Kurikulum.
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

                    @foreach (['listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing'] as $key => $label)
                        <div class="col-6 col-md-2 js-skill-wrap" data-skill="{{ $key }}">
                            <label class="form-label small fw-semibold mb-1">{{ $label }} <span class="text-muted">(maks 100)</span></label>
                            <input type="number" name="{{ $key }}_score" id="{{ $key }}_score" data-skill="{{ $key }}"
                                   step="0.1" min="0" max="100" value="{{ old($key . '_score') }}"
                                   class="form-control js-skill" placeholder="0 - 100" required>
                        </div>
                    @endforeach

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Total Nilai</label>
                        <input type="text" class="form-control bg-light js-total" readonly placeholder="otomatis">
                    </div>

                    <div class="col-md-2 d-grid">
                        <button class="btn btn-warning fw-bold">Buat &amp; Ajukan</button>
                    </div>

                    <div class="col-12">
                        <div class="small js-hint"></div>
                        <div class="small text-muted">
                            Setelah memilih siswa, kolom terisi otomatis dari progress report. Jika ada nilai yang masih kosong,
                            silakan isi manual. HSK 1-2: Listening + Reading (maks 200, lulus min. 120).
                            HSK 3-6: Listening + Reading + Writing (maks 300, lulus min. 180).
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
                            <th>L / R / W</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($certificates as $c)
                        @php
                            $n = fn ($v) => is_null($v) ? '-' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
                            preg_match('/HSK\s*([1-6])/i', $c->course_name ?: ($c->enrollment->class->class_name ?? ''), $mm);
                            $lvl   = (int) ($mm[1] ?? 0);
                            $isLow = $lvl >= 1 && $lvl <= 2;
                            $max   = $isLow ? 200 : 300;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->student->name ?? '-' }}</div>
                                <div class="small text-muted">No. {{ $c->certificate_number }}</div>
                            </td>
                            <td>{{ $c->course_name ?: ($c->enrollment->class->class_name ?? '-') }}</td>
                            <td class="small">
                                {{ $c->start_date->translatedFormat('d M Y') }}<br>
                                s/d {{ $c->end_date->translatedFormat('d M Y') }}
                            </td>
                            <td class="small text-nowrap">
                                {{ $n($c->listening_score) }} / {{ $n($c->reading_score) }}@unless ($isLow) / {{ $n($c->writing_score) }}@endunless
                            </td>
                            <td class="fw-semibold text-nowrap">{{ $n($c->score) }} <span class="small text-muted fw-normal">/ {{ $max }}</span></td>
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
                                               value="{{ $c->course_name ?: ($c->enrollment->class->class_name ?? '') }}" required>
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

                                    @foreach (['listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing'] as $key => $label)
                                        <div class="col-6 col-md-2 js-skill-wrap" data-skill="{{ $key }}">
                                            <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
                                            <input type="number" name="{{ $key }}_score" data-skill="{{ $key }}" step="0.1" min="0" max="100"
                                                   class="form-control form-control-sm js-skill" value="{{ $c->{$key . '_score'} }}" required>
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
    const ALL = ['listening', 'reading', 'writing'];

    function hskLevel(text) {
        const m = /HSK\s*([1-6])/i.exec(text || '');
        return m ? parseInt(m[1], 10) : 0;
    }

    function rule(level) {
        return (level >= 1 && level <= 2)
            ? { skills: ['listening', 'reading'], max: 200, pass: 120 }
            : { skills: ['listening', 'reading', 'writing'], max: 300, pass: 180 };
    }

    function refreshForm(form) {
        const course = form.querySelector('.js-course');
        const level  = hskLevel(course ? course.value : '');
        const r      = rule(level);

        ALL.forEach(s => {
            const wrap  = form.querySelector('.js-skill-wrap[data-skill="' + s + '"]');
            if (!wrap) return;
            const input = wrap.querySelector('.js-skill');
            const on    = r.skills.includes(s);
            wrap.classList.toggle('d-none', !on);
            input.disabled = !on;   // tidak ikut terkirim & tidak wajib kalau disembunyikan
            input.required = on;
        });

        const vals  = r.skills.map(s => parseFloat(form.querySelector('.js-skill[data-skill="' + s + '"]').value));
        const total = form.querySelector('.js-total');
        const hint  = form.querySelector('.js-hint');
        let sum = null;

        if (!vals.some(isNaN)) sum = vals.reduce((a, b) => a + b, 0);
        total.value = sum === null ? '' : sum.toFixed(1) + ' / ' + r.max;

        let text = 'Standar kelulusan: minimal ' + r.pass + ' dari ' + r.max + '.';
        let cls  = 'text-muted';
        if (sum !== null) {
            if (sum >= r.pass) { text += ' Memenuhi standar kelulusan.'; cls = 'text-success'; }
            else               { text += ' Belum memenuhi standar kelulusan.'; cls = 'text-danger'; }
        }
        if (level === 0) text += ' (Level HSK tidak terdeteksi di nama kursus, dihitung seperti HSK 3-6.)';
        hint.className = 'small js-hint ' + cls;
        hint.textContent = text;
    }

    document.querySelectorAll('.js-cert-form').forEach(form => {
        form.addEventListener('input', () => refreshForm(form));
        refreshForm(form);
    });

    const selEl = document.getElementById('enrollment_id');

    function fillFromEnrollment() {
        const d = prefill[selEl.value];
        if (!d) return;
        const form = selEl.closest('form');
        document.getElementById('course_name').value = d.course ?? '';
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