@extends('layouts.guru')

@section('title','Sertifikat')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Sertifikat</h2>
        <p class="text-muted mb-0">
            Buat sertifikat les Mandarin siswa. Tanggal mulai-selesai dan nilai terisi otomatis dari data les,
            tetapi tetap bisa Anda ubah. Sertifikat tampil ke siswa setelah di-ACC Kurikulum.
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

                    <div class="col-md-4">
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

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Nama Kursus / Level di Sertifikat</label>
                        <input type="text" name="course_name" id="course_name" maxlength="150"
                               value="{{ old('course_name') }}" class="form-control" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Nilai</label>
                        <input type="number" name="score" id="score" step="0.1" min="0" max="100"
                               value="{{ old('score') }}" class="form-control" placeholder="0 - 100">
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

                    <div class="col-md-3 d-grid">
                        <button class="btn btn-warning fw-bold">Buat &amp; Ajukan ke Kurikulum</button>
                    </div>

                    <div class="col-12">
                        <div class="small text-muted">
                            Setelah memilih siswa, kolom kursus, tanggal, dan nilai terisi otomatis. Nilai kosong berarti
                            belum ada progress report yang di-ACC, silakan isi manual jika perlu.
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
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($certificates as $c)
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
                            <td>{{ is_null($c->score) ? '-' : $c->score }}</td>
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

                        {{-- Form edit (tersembunyi sampai tombol Edit ditekan) --}}
                        <tr id="edit{{ $c->id }}" class="d-none">
                            <td colspan="6" class="bg-light">
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
                                    <div class="col-md-1">
                                        <label class="form-label small fw-semibold mb-1">Nilai</label>
                                        <input type="number" name="score" step="0.1" min="0" max="100"
                                               class="form-control form-control-sm" value="{{ $c->score }}">
                                    </div>
                                    <div class="col-md-3 d-flex gap-1">
                                        <button class="btn btn-warning btn-sm fw-bold">Simpan &amp; Ajukan Ulang</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="document.getElementById('edit{{ $c->id }}').classList.add('d-none')">Batal</button>
                                    </div>
                                    <div class="col-12 small text-muted">
                                        Setelah disimpan, PDF dibuat ulang dan sertifikat dikirim lagi ke Kurikulum untuk di-ACC
                                        (status kembali menjadi menunggu).
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada sertifikat.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    // Isian otomatis: kursus, tanggal mulai-selesai, dan nilai dari data les siswa
    const prefill = @json($prefill);
    const selEl = document.getElementById('enrollment_id');

    function fillFromEnrollment() {
        if (!selEl) return;
        const d = prefill[selEl.value];
        if (!d) return;
        document.getElementById('course_name').value = d.course ?? '';
        document.getElementById('start_date').value  = d.start ?? '';
        document.getElementById('end_date').value    = d.end ?? '';
        document.getElementById('score').value       = d.score ?? '';
    }

    if (selEl) {
        selEl.addEventListener('change', fillFromEnrollment);
        @if (!old('enrollment_id'))
            fillFromEnrollment();
        @endif
    }
</script>

@endsection