@extends('layouts.guru')

@section('title','Sertifikat')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Sertifikat</h2>
        <p class="text-muted mb-0">
            Buat sertifikat les Mandarin siswa. Tanggal dan nilai Listening, Reading, Speaking, Writing terisi otomatis
            dari progress report, tetapi tetap bisa Anda ubah. Nilai akhir dihitung otomatis (rata-rata).
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
                <form method="POST" action="{{ route('teacher.sertifikat.store') }}" class="row g-3 align-items-end">
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
                               value="{{ old('course_name') }}" class="form-control" required>
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

                    @foreach (['listening' => 'Listening', 'reading' => 'Reading', 'speaking' => 'Speaking', 'writing' => 'Writing'] as $key => $label)
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
                            <input type="number" name="{{ $key }}_score" id="{{ $key }}_score" step="0.1" min="0" max="100"
                                   value="{{ old($key . '_score') }}" class="form-control js-skill" placeholder="0 - 100" required>
                        </div>
                    @endforeach

                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold mb-1">Nilai Akhir</label>
                        <input type="text" id="avg_score" class="form-control bg-light" readonly placeholder="otomatis">
                    </div>

                    <div class="col-md-2 d-grid">
                        <button class="btn btn-warning fw-bold">Buat &amp; Ajukan</button>
                    </div>

                    <div class="col-12">
                        <div class="small text-muted">
                            Setelah memilih siswa, kolom terisi otomatis dari progress report. Jika ada nilai yang masih kosong,
                            silakan isi manual sesuai report siswa. Keempat nilai wajib diisi.
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
                            <th>L / R / S / W</th>
                            <th>Akhir</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($certificates as $c)
                        @php $n = fn ($v) => is_null($v) ? '-' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.'); @endphp
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
                                {{ $n($c->listening_score) }} / {{ $n($c->reading_score) }} / {{ $n($c->speaking_score) }} / {{ $n($c->writing_score) }}
                            </td>
                            <td class="fw-semibold">{{ $n($c->score) }}</td>
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
                                <form method="POST" action="{{ route('teacher.sertifikat.update', $c) }}" class="row g-2 align-items-end">
                                    @csrf @method('PUT')

                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Nama Kursus / Level</label>
                                        <input type="text" name="course_name" maxlength="150" class="form-control form-control-sm"
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

                                    @foreach (['listening' => 'Listening', 'reading' => 'Reading', 'speaking' => 'Speaking', 'writing' => 'Writing'] as $key => $label)
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
                                            <input type="number" name="{{ $key }}_score" step="0.1" min="0" max="100"
                                                   class="form-control form-control-sm" value="{{ $c->{$key . '_score'} }}" required>
                                        </div>
                                    @endforeach

                                    <div class="col-md-4 d-flex gap-1">
                                        <button class="btn btn-warning btn-sm fw-bold">Simpan &amp; Ajukan Ulang</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="document.getElementById('edit{{ $c->id }}').classList.add('d-none')">Batal</button>
                                    </div>
                                    <div class="col-12 small text-muted">
                                        Setelah disimpan, PDF dibuat ulang (nilai akhir dihitung otomatis) dan sertifikat dikirim lagi
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
    const selEl = document.getElementById('enrollment_id');
    const skills = ['listening', 'reading', 'speaking', 'writing'];

    function updateAvg() {
        const out = document.getElementById('avg_score');
        if (!out) return;
        const vals = skills.map(s => parseFloat(document.getElementById(s + '_score').value));
        out.value = vals.some(isNaN) ? '' : (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1);
    }

    function fillFromEnrollment() {
        if (!selEl) return;
        const d = prefill[selEl.value];
        if (!d) return;
        document.getElementById('course_name').value = d.course ?? '';
        document.getElementById('start_date').value  = d.start ?? '';
        document.getElementById('end_date').value    = d.end ?? '';
        skills.forEach(s => document.getElementById(s + '_score').value = d[s] ?? '');
        updateAvg();
    }

    if (selEl) {
        selEl.addEventListener('change', fillFromEnrollment);
        document.querySelectorAll('.js-skill').forEach(el => el.addEventListener('input', updateAvg));
        @if (!old('enrollment_id'))
            fillFromEnrollment();
        @else
            updateAvg();
        @endif
    }
</script>

@endsection