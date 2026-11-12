@extends('admin.app')

@section('title', 'Request Belajar Siswa')

@section('content')
<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-book-open-reader text-warning me-2"></i> Request Belajar Siswa & Hasil Konsultasi
            </h4>
            <p class="text-muted small mb-0">Input permintaan topik belajar khusus dari siswa/wali dan catatan konsultasi untuk diteruskan otomatis ke Guru & Kurikulum.</p>
        </div>
        <button type="button" class="btn btn-warning rounded-pill fw-semibold text-dark shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalTambahRequest">
            <i class="fa-solid fa-plus me-1"></i> Input Request Siswa
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER BAR --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="{{ route('admin.request-belajar.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Filter Siswa</label>
                    <select name="student_id" class="form-select form-select-sm">
                        <option value="">Semua Siswa</option>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected($studentId == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filter Kelas</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cari Topik / Catatan</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Ketik topik...">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill rounded-pill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.request-belajar.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- LIST REQUEST --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-warning me-2"></i> Daftar Permintaan Belajar Siswa
            </h6>
            <span class="badge bg-light text-dark border">{{ $requests->total() }} Permintaan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Waktu Input</th>
                        <th>Siswa & Kelas</th>
                        <th>Topik yang Diinginkan</th>
                        <th>Catatan Hasil Konsultasi</th>
                        <th>Diinput Oleh</th>
                        <th class="text-center" style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $req->created_at->translatedFormat('d M Y') }}</div>
                                <small class="text-muted">{{ $req->created_at->format('H:i') }} WIB</small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $req->student->name ?? '-' }}</div>
                                @if ($req->class)
                                    <div class="small text-muted">
                                        <i class="fa-solid fa-chalkboard text-warning me-1"></i> {{ $req->class->class_name }}
                                        @if($req->class->teacher)
                                            <span class="text-secondary">({{ $req->class->teacher->name }})</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary small">Umum / Tanpa Kelas</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 fs-6">
                                    {{ $req->topic }}
                                </span>
                            </td>
                            <td>
                                <div class="small text-secondary" style="max-width: 320px; white-space: pre-line;">
                                    {{ $req->consultation_notes ?: '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $req->creator->display_name ?? 'Admin' }}</div>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.request-belajar.destroy', $req->id) }}" method="POST" onsubmit="return confirm('Hapus data request belajar ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-book-open-reader fs-1 text-secondary mb-3 d-block"></i>
                                Belum ada data request belajar dari siswa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white py-3">
            {{ $requests->links() }}
        </div>
    </div>
</div>

{{-- MODAL TAMBAH REQUEST --}}
<div class="modal fade" id="modalTambahRequest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg text-start">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('admin.request-belajar.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Input Request Belajar Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                @foreach ($students as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas Siswa (opsional)</label>
                            <select name="class_id" class="form-select">
                                <option value="">-- Tanpa Kelas Spesifik / Belum Masuk Kelas --</option>
                                @foreach ($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }} ({{ $c->teacher?->name ?? 'Belum ada guru' }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Jika diisi, guru pengampu kelas akan otomatis menerima notifikasi.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Topik yang Ingin Dipelajari <span class="text-danger">*</span></label>
                        <input type="text" name="topic" class="form-control" placeholder="Contoh: Fokus Percakapan Sehari-hari, Persiapan HSK 3, Latihan Nada Pinyin..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Hasil Konsultasi Admin</label>
                        <textarea name="consultation_notes" rows="4" class="form-control" placeholder="Tuliskan hasil diskusi atau konsultasi dengan orang tua / siswa terkait target belajar, kesulitan yang dialami, request materi khusus, dll..."></textarea>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="fa-solid fa-bell me-1"></i> Setelah disimpan, notifikasi otomatis dikirimkan ke <strong>Kurikulum</strong> dan <strong>Guru</strong> yang mengajar kelas tersebut.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-semibold text-dark px-4">
                        <i class="fa-solid fa-paper-plane me-1"></i> Simpan & Kirim Notifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
