@extends('layouts.kurikulum')

@section('title', 'Hasil Konsultasi Kurikulum')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-comments text-warning me-2"></i> Hasil Konsultasi Kurikulum</h3>
            <p class="text-muted mb-0">
                Dokumentasikan evaluasi, poin perbaikan, dan rencana tindak lanjut dari sesi konsultasi bersama guru.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-warning rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKonsultasi">
                <i class="fa-solid fa-plus me-1"></i> + Buat Hasil Konsultasi
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm">
            <strong>Terjadi kesalahan validasi:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- NAV TABS --}}
    <ul class="nav nav-pills mb-4 gap-2" id="kurikulumConsultationTabs">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'hasil' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('kurikulum.hasil-konsultasi.index', ['tab' => 'hasil']) }}">
                <i class="fa-solid fa-clipboard-check me-2"></i> Hasil Konsultasi Kurikulum ({{ $consultations->total() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'revisi' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('kurikulum.hasil-konsultasi.index', ['tab' => 'revisi']) }}">
                <i class="fa-solid fa-arrows-rotate me-2"></i> Permintaan Konsultasi Revisi Guru ({{ $revisionConsultations->total() }})
            </a>
        </li>
    </ul>

    @if ($activeTab === 'hasil')
    {{-- FILTER & SEARCH --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('kurikulum.hasil-konsultasi.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="teacher_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Guru</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" title="Dari Tanggal" placeholder="Dari Tanggal">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" title="Sampai Tanggal" placeholder="Sampai Tanggal">
                </div>
                <div class="col-md-3">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari topik atau catatan...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-3">Filter</button>
                    @if(request('teacher_id') || request('date_from') || request('date_to') || request('q'))
                        <a href="{{ route('kurikulum.hasil-konsultasi.index') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR HASIL KONSULTASI --}}
    @if ($consultations->isEmpty())
        <div class="card shadow-sm border-0 rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-comments fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Belum Ada Riwayat Hasil Konsultasi</h5>
                <p class="text-muted">Klik tombol "+ Buat Hasil Konsultasi" untuk mencatat sesi konsultasi baru.</p>
            </div>
        </div>
    @else
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 140px;">Tanggal & Guru</th>
                            <th style="min-width: 180px;">Topik Konsultasi</th>
                            <th style="min-width: 220px;">Poin Perbaikan</th>
                            <th style="min-width: 180px;">Tindak Lanjut</th>
                            <th style="min-width: 130px;">Kaitan Observasi</th>
                            <th style="min-width: 100px;">Lampiran</th>
                            <th style="min-width: 90px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consultations as $c)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $c->teacher?->name ?? 'Guru tidak ditemukan' }}</div>
                                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> {{ $c->consultation_date?->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $c->topic }}</div>
                                    <small class="text-muted">Oleh: {{ $c->curriculumUser?->name ?? $c->curriculumUser?->username ?? 'Kurikulum' }}</small>
                                </td>
                                <td>
                                    @if(is_array($c->improvement_points) && count($c->improvement_points) > 0)
                                        <ul class="mb-0 ps-3 small text-secondary">
                                            @foreach($c->improvement_points as $point)
                                                <li>{{ $point }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-dark">{{ $c->follow_up ? Str::limit($c->follow_up, 100) : '-' }}</small>
                                </td>
                                <td>
                                    @if($c->observation)
                                        <div class="small">
                                            <span class="badge {{ $c->observation->sop_badge }} mb-1">
                                                {{ $c->observation->sop_compliance }}
                                            </span>
                                            <div class="text-muted" style="font-size: 11px;">
                                                Obs: {{ $c->observation->observation_date?->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted border">Tanpa Observasi</span>
                                    @endif
                                </td>
                                <td>
                                    @if($c->file_path)
                                        <a href="{{ route('kurikulum.hasil-konsultasi.download', $c->id) }}" class="btn btn-sm btn-outline-primary rounded-pill" title="Unduh berkas">
                                            <i class="fa-solid fa-download me-1"></i> {{ strtoupper($c->file_extension ?: 'FILE') }}
                                        </a>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light text-primary rounded-circle"
                                                title="Edit Data"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditKonsultasi{{ $c->id }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <form method="POST" action="{{ route('kurikulum.hasil-konsultasi.destroy', $c->id) }}"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus hasil konsultasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle" title="Hapus Data">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- MODAL EDIT KONSULTASI --}}
                            <div class="modal fade" id="modalEditKonsultasi{{ $c->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="fw-bold"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Hasil Konsultasi</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('kurikulum.hasil-konsultasi.update', $c->id) }}" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Guru <span class="text-danger">*</span></label>
                                                        <select name="teacher_id" class="form-select" required>
                                                            @foreach ($teachers as $t)
                                                                <option value="{{ $t->id }}" {{ $c->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Tanggal Konsultasi <span class="text-danger">*</span></label>
                                                        <input type="date" name="consultation_date" class="form-control" value="{{ $c->consultation_date?->format('Y-m-d') }}" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Topik Konsultasi <span class="text-danger">*</span></label>
                                                    <input type="text" name="topic" class="form-control" value="{{ $c->topic }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Kaitkan dengan Observasi Mengajar (Opsional)</label>
                                                    <select name="observation_id" class="form-select">
                                                        <option value="">-- Tidak dikaitkan dengan observasi --</option>
                                                        @foreach ($observations->where('teacher_id', $c->teacher_id) as $obs)
                                                            <option value="{{ $obs->id }}" {{ $c->observation_id == $obs->id ? 'selected' : '' }}>
                                                                {{ $obs->observation_date?->format('d/m/Y') }} - {{ $obs->class?->class_name ?? 'Kelas' }} ({{ $obs->sop_compliance }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                                        <span>Poin-Poin yang Perlu Diperbaiki <span class="text-danger">*</span></span>
                                                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" onclick="addImprovementRow('edit_points_{{ $c->id }}')">
                                                            <i class="fa-solid fa-plus me-1"></i> Tambah Poin
                                                        </button>
                                                    </label>
                                                    <div id="edit_points_{{ $c->id }}" class="d-flex flex-column gap-2">
                                                        @if(is_array($c->improvement_points) && count($c->improvement_points) > 0)
                                                            @foreach($c->improvement_points as $pt)
                                                                <div class="input-group">
                                                                    <span class="input-group-text bg-light"><i class="fa-solid fa-circle-dot text-warning"></i></span>
                                                                    <input type="text" name="improvement_points[]" class="form-control" value="{{ $pt }}" required>
                                                                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fa-solid fa-trash"></i></button>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-light"><i class="fa-solid fa-circle-dot text-warning"></i></span>
                                                                <input type="text" name="improvement_points[]" class="form-control" placeholder="Poin evaluasi atau perbaikan..." required>
                                                                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fa-solid fa-trash"></i></button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Rencana Tindak Lanjut / Solusi</label>
                                                    <textarea name="follow_up" class="form-control" rows="3">{{ $c->follow_up }}</textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Ganti File Dokumen / Lampiran</label>
                                                    <input type="file" name="file" class="form-control">
                                                    @if($c->file_path)
                                                        <small class="text-muted d-block mt-1">File tersimpan: {{ $c->file_name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning rounded-pill fw-bold">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top">
                {{ $consultations->links() }}
            </div>
        </div>
    @endif
    @else
        {{-- ==================== TAB 2: PERMINTAAN KONSULTASI REVISI DARI GURU ==================== --}}
        @if ($revisionConsultations->isEmpty())
            <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-arrows-rotate fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Permintaan Konsultasi Revisi</h5>
                    <p class="text-muted">Guru belum mengajukan konsultasi revisi untuk dokumen mengajar.</p>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 18%;">Guru & Kelas</th>
                                <th style="width: 15%;">Dokumen</th>
                                <th style="width: 25%;">Kendala Guru</th>
                                <th style="width: 12%;">Status</th>
                                <th style="width: 25%;" class="text-end">Aksi Kurikulum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($revisionConsultations as $rc)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $rc->teacher->name ?? '-' }}</div>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-chalkboard me-1"></i> {{ $rc->class->class_name ?? 'Umum' }}
                                        </small>
                                        <small class="text-muted d-block" style="font-size: 11px;">
                                            {{ $rc->created_at->translatedFormat('d M Y, H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary text-white px-3 py-2 rounded-pill">
                                            {{ $rc->document_type }}
                                        </span>
                                        @if($rc->attachment_path)
                                            <div class="mt-1">
                                                <a href="{{ route('kurikulum.hasil-konsultasi.revisi.download', $rc->id) }}" class="small text-decoration-none">
                                                    <i class="fa-solid fa-paperclip me-1"></i> Unduh Berkas
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small text-dark" style="white-space: pre-line;">{{ $rc->obstacle }}</div>
                                        @if($rc->curriculum_reply)
                                            <div class="mt-2 p-2 rounded-3 bg-success-subtle small border border-success-subtle">
                                                <strong><i class="fa-solid fa-reply me-1"></i> Balasan:</strong> {{ $rc->curriculum_reply }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $rc->status_badge }} px-3 py-2 rounded-pill fw-semibold">
                                            {{ $rc->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                                            @if($rc->status === 'Menunggu')
                                                <form action="{{ route('kurikulum.hasil-konsultasi.revisi.accept', $rc->id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-info text-dark rounded-pill px-3 fw-semibold">
                                                        <i class="fa-solid fa-check me-1"></i> Terima
                                                    </button>
                                                </form>
                                            @endif

                                            @if($rc->status !== 'Selesai')
                                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold"
                                                        data-bs-toggle="modal" data-bs-target="#modalBalasRevisi{{ $rc->id }}">
                                                    <i class="fa-solid fa-reply me-1"></i> Balas & Selesai
                                                </button>
                                            @else
                                                <span class="badge bg-light text-muted border py-2 px-3">
                                                    <i class="fa-solid fa-circle-check text-success me-1"></i> Selesai
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- MODAL BALAS REVISI --}}
                                <div class="modal fade" id="modalBalasRevisi{{ $rc->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form class="modal-content border-0 shadow rounded-4" action="{{ route('kurikulum.hasil-konsultasi.revisi.complete', $rc->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold">
                                                    <i class="fa-solid fa-reply text-warning me-2"></i> Balas Konsultasi — {{ $rc->teacher->name }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <div class="p-3 bg-light rounded-3 border mb-3">
                                                    <div class="small fw-bold text-muted mb-1">Kendala / Pertanyaan Guru:</div>
                                                    <div class="small text-dark">{{ $rc->obstacle }}</div>
                                                </div>

                                                <div class="mb-0">
                                                    <label class="form-label fw-semibold">Arahan / Solusi Kurikulum <span class="text-danger">*</span></label>
                                                    <textarea name="curriculum_reply" rows="4" class="form-control" placeholder="Tuliskan arahan perbaikan, solusi materi, atau rekomendasi kurikulum..." required>{{ $rc->curriculum_reply }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                                                    <i class="fa-solid fa-paper-plane me-1"></i> Kirim & Tandai Selesai
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top">
                    {{ $revisionConsultations->links() }}
                </div>
            </div>
        @endif
    @endif

</div>

{{-- MODAL TAMBAH HASIL KONSULTASI --}}
<div class="modal fade" id="modalTambahKonsultasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-comments text-warning me-2"></i> Buat Hasil Konsultasi Kurikulum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('kurikulum.hasil-konsultasi.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Guru Pengampu <span class="text-danger">*</span></label>
                            <select name="teacher_id" id="add_teacher_id" class="form-select" onchange="loadObservationsForTeacher(this.value)" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Konsultasi <span class="text-danger">*</span></label>
                            <input type="date" name="consultation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Topik Konsultasi <span class="text-danger">*</span></label>
                        <input type="text" name="topic" class="form-control" placeholder="Contoh: Evaluasi Manajemen Kelas & Strategi Interaksi Siswa Pemula" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kaitkan dengan Observasi Guru (Opsional)</label>
                        <select name="observation_id" id="add_observation_id" class="form-select">
                            <option value="">-- Tidak dikaitkan dengan observasi --</option>
                        </select>
                        <small class="text-muted">Pilih observasi yang relevan bila konsultasi merupakan tindak lanjut observasi kelas.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Poin-Poin yang Perlu Diperbaiki <span class="text-danger">*</span></span>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" onclick="addImprovementRow('add_points_container')">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Poin
                            </button>
                        </label>
                        <div id="add_points_container" class="d-flex flex-column gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-circle-dot text-warning"></i></span>
                                <input type="text" name="improvement_points[]" class="form-control" placeholder="Poin 1: contoh, Penggunaan media visual PPT kurang interaktif" required>
                                <button type="button" class="btn btn-outline-danger" onclick="removeImprovementRow(this)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <small class="text-muted">Dapat menambahkan beberapa poin perbaikan yang disepakati bersama pengajar.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rencana Tindak Lanjut / Solusi</label>
                        <textarea name="follow_up" class="form-control" rows="3" placeholder="Tuliskan solusi praktis, tenggat waktu perbaikan, atau jadwal sesi supervisi lanjutan..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload File Hasil / Notulensi / Lampiran (Opsional)</label>
                        <input type="file" name="file" class="form-control">
                        <small class="text-muted">Format: PDF, Gambar (JPG/PNG), DOCX, XLSX. Maks. 20 MB.</small>
                    </div>

                    <div class="alert alert-info py-2 px-3 rounded-3 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i> Guru yang bersangkutan akan menerima notifikasi otomatis dan dapat melihat rincian hasil konsultasi ini di akun mereka.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold">Simpan & Kirim Notifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function addImprovementRow(containerId) {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = 'input-group';
        div.innerHTML = `
            <span class="input-group-text bg-light"><i class="fa-solid fa-circle-dot text-warning"></i></span>
            <input type="text" name="improvement_points[]" class="form-control" placeholder="Poin perbaikan selanjutnya..." required>
            <button type="button" class="btn btn-outline-danger" onclick="removeImprovementRow(this)"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(div);
    }

    function removeImprovementRow(btn) {
        const group = btn.closest('.input-group');
        const container = group.parentElement;
        if (container.querySelectorAll('.input-group').length > 1) {
            group.remove();
        } else {
            alert('Minimal harus ada 1 poin yang perlu diperbaiki.');
        }
    }

    function loadObservationsForTeacher(teacherId) {
        const select = document.getElementById('add_observation_id');
        select.innerHTML = '<option value="">-- Tidak dikaitkan dengan observasi --</option>';
        if (!teacherId) return;

        fetch(`{{ url('/kurikulum/hasil-konsultasi/teacher') }}/${teacherId}/observations`)
            .then(res => res.json())
            .then(data => {
                data.forEach(obs => {
                    const opt = document.createElement('option');
                    opt.value = obs.id;
                    opt.textContent = `${obs.date} - ${obs.class_name} (${obs.sop_compliance})`;
                    select.appendChild(opt);
                });
            })
            .catch(err => console.error('Error fetching observations:', err));
    }
</script>
@endsection

