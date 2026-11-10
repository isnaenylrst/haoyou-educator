@extends('layouts.owner')

@section('title', 'Konsultasi Pengajar & Kurikulum')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-comments text-warning me-2"></i> Konsultasi Pengajar</h3>
            <p class="text-muted mb-0">
                Monitoring jadwal, hasil evaluasi, dan tindak lanjut konsultasi bersama guru serta tim kurikulum.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-warning rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKonsultasi">
                <i class="fa-solid fa-plus me-1"></i> + Buat Hasil Konsultasi
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
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
    <ul class="nav nav-pills mb-4 gap-2" id="ownerConsultationTabs">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'hasil' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('owner.konsultasi.index', ['tab' => 'hasil']) }}">
                <i class="fa-solid fa-clipboard-check me-2"></i> Hasil Konsultasi ({{ $consultations->total() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'jadwal' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('owner.konsultasi.index', ['tab' => 'jadwal']) }}">
                <i class="fa-solid fa-calendar-days me-2"></i> Jadwal Konsultasi ({{ $scheduledConsultations->total() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'revisi' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('owner.konsultasi.index', ['tab' => 'revisi']) }}">
                <i class="fa-solid fa-arrows-rotate me-2"></i> Permintaan Revisi Guru ({{ $revisionConsultations->total() }})
            </a>
        </li>
    </ul>

    {{-- FILTER FORM --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('owner.konsultasi.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                <div class="col-md-3">
                    <select name="teacher_id" class="form-select form-select-sm">
                        <option value="">Semua Guru</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" {{ $teacherId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($activeTab === 'jadwal' || $activeTab === 'revisi')
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            @if ($activeTab === 'jadwal')
                                <option value="Belum Terjadwal" {{ $status === 'Belum Terjadwal' ? 'selected' : '' }}>Belum Terjadwal</option>
                                <option value="Terjadwal" {{ $status === 'Terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                                <option value="Selesai" {{ $status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="Dibatalkan" {{ $status === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                            @else
                                <option value="Pending" {{ $status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Accepted" {{ $status === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="Completed" {{ $status === 'Completed' ? 'selected' : '' }}>Completed</option>
                            @endif
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" placeholder="Dari Tanggal" title="Dari Tanggal">
                </div>

                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" placeholder="Sampai Tanggal" title="Sampai Tanggal">
                </div>

                @if ($activeTab === 'hasil')
                    <div class="col-md-3">
                        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Cari topik / peserta / poin...">
                    </div>
                @endif

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold rounded-pill">Filter</button>
                    @if($teacherId || $dateFrom || $dateTo || $status || $q)
                        <a href="{{ route('owner.konsultasi.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-secondary rounded-pill" title="Reset filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- TAB 1: HASIL KONSULTASI --}}
    @if ($activeTab === 'hasil')
        @if ($consultations->isEmpty())
            <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-clipboard-check fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Riwayat Hasil Konsultasi</h5>
                    <p class="text-muted">Klik tombol "+ Buat Hasil Konsultasi" untuk mencatat sesi evaluasi pengajar.</p>
                </div>
            </div>
        @else
            <div class="row g-3">
                @foreach ($consultations as $c)
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 rounded-4 h-100 d-flex flex-column">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1">{{ $c->topic }}</h5>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1 rounded-pill small">
                                            <i class="fa-solid fa-user-tie me-1"></i> {{ $c->teacher?->name ?? 'Semua Guru' }}
                                        </span>
                                        @if($c->participant_names)
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold px-2 py-1 rounded-pill small ms-1">
                                                <i class="fa-solid fa-users me-1"></i> {{ $c->participant_names }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="text-muted small d-block">{{ $c->consultation_date->format('d M Y') }}</span>
                                        <small class="text-secondary" style="font-size: 11px;">Oleh: {{ $c->curriculumUser?->display_name ?? 'Owner' }}</small>
                                    </div>
                                </div>

                                <hr class="my-2 border-secondary border-opacity-10">

                                <div class="mb-3">
                                    <strong class="small text-muted d-block mb-1"><i class="fa-solid fa-list-check me-1 text-warning"></i> Poin-Poin Perbaikan:</strong>
                                    <ul class="mb-0 ps-3 small">
                                        @foreach ((array) $c->improvement_points as $pt)
                                            <li>{{ $pt }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                @if ($c->follow_up)
                                    <div class="mb-3 p-2 bg-light rounded-3 small">
                                        <strong class="text-dark d-block mb-1"><i class="fa-solid fa-bullseye me-1 text-primary"></i> Rencana Tindak Lanjut:</strong>
                                        <p class="mb-0 text-muted">{{ $c->follow_up }}</p>
                                    </div>
                                @endif

                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                    <div>
                                        @if ($c->file_path)
                                            <a href="{{ route('owner.konsultasi.download', $c->id) }}" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
                                                <i class="fa-solid fa-paperclip me-1"></i> Lampiran ({{ strtoupper($c->file_extension ?: 'FILE') }})
                                            </a>
                                        @else
                                            <span class="text-muted small fst-italic">Tanpa lampiran</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light text-primary rounded-circle"
                                                title="Edit Hasil Konsultasi"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditKonsultasi{{ $c->id }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('owner.konsultasi.destroy', $c->id) }}"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus hasil konsultasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL EDIT HASIL KONSULTASI --}}
                    <div class="modal fade" id="modalEditKonsultasi{{ $c->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0">
                                <form method="POST" action="{{ route('owner.konsultasi.update', $c->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Edit Hasil Konsultasi</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Guru Pengajar <span class="text-danger">*</span></label>
                                                <select name="teacher_id" class="form-select" required>
                                                    @foreach ($teachers as $t)
                                                        <option value="{{ $t->id }}" {{ $c->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Peserta Lainnya (Opsional)</label>
                                                <input type="text" name="participant_names" class="form-control" value="{{ $c->participant_names }}" placeholder="Nama peserta/staf pendamping">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Tanggal Konsultasi <span class="text-danger">*</span></label>
                                                <input type="date" name="consultation_date" class="form-control" value="{{ $c->consultation_date->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Topik Evaluasi <span class="text-danger">*</span></label>
                                                <input type="text" name="topic" class="form-control" value="{{ $c->topic }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Poin-Poin Perbaikan <span class="text-danger">*</span></label>
                                                <div id="editPointsContainer{{ $c->id }}">
                                                    @foreach ((array) $c->improvement_points as $pIdx => $val)
                                                        <div class="input-group mb-2">
                                                            <span class="input-group-text bg-light border-end-0">{{ $pIdx + 1 }}</span>
                                                            <input type="text" name="improvement_points[]" class="form-control" value="{{ $val }}" required>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Rencana Tindak Lanjut</label>
                                                <textarea name="follow_up" class="form-control" rows="2">{{ $c->follow_up }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Ganti Lampiran File (Opsional)</label>
                                                <input type="file" name="file" class="form-control">
                                                <small class="text-muted">Maksimal 20 MB (PDF, JPG, PNG, DOC, XLS). Biarkan kosong jika tidak ingin mengganti.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $consultations->links() }}
            </div>
        @endif

    {{-- TAB 2: JADWAL KONSULTASI --}}
    @elseif ($activeTab === 'jadwal')
        @if ($scheduledConsultations->isEmpty())
            <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-calendar-xmark fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Jadwal Konsultasi</h5>
                    <p class="text-muted">Tidak ada jadwal konsultasi yang cocok dengan filter yang dipilih.</p>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jenis</th>
                                <th>Guru</th>
                                <th>Kelas / Materi</th>
                                <th>Jadwal / Deadline</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scheduledConsultations as $sc)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $sc->type_label ?? $sc->type }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $sc->teacher?->name ?? $sc->teacher_name }}</div>
                                        <small class="text-muted">{{ $sc->teacher_phone ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $sc->class?->class_name ?? '-' }}</div>
                                        <small class="text-muted">{{ $sc->teacherMaterial?->title ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div><i class="fa-regular fa-clock me-1 text-muted"></i> {{ $sc->scheduled_at ? $sc->scheduled_at->format('d M Y H:i') : '-' }}</div>
                                        @if($sc->deadline_at)
                                            <small class="text-danger">Deadline: {{ $sc->deadline_at->format('d M Y') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $bClass = match($sc->status) {
                                                'Terjadwal'       => 'bg-primary',
                                                'Selesai'         => 'bg-success',
                                                'Dibatalkan'      => 'bg-danger',
                                                'Belum Terjadwal' => 'bg-warning text-dark',
                                                default           => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $bClass }}">{{ $sc->status }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($sc->notes ?: '-', 40) }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-light">
                    {{ $scheduledConsultations->links() }}
                </div>
            </div>
        @endif

    {{-- TAB 3: KONSULTASI REVISI GURU --}}
    @elseif ($activeTab === 'revisi')
        @if ($revisionConsultations->isEmpty())
            <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-arrows-rotate fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Permintaan Konsultasi Revisi</h5>
                    <p class="text-muted">Guru belum mengajukan revisi materi/LP/PPT.</p>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Guru</th>
                                <th>Kelas</th>
                                <th>Jenis Dokumen</th>
                                <th>Kendala Guru</th>
                                <th>Status</th>
                                <th>Tanggapan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($revisionConsultations as $rc)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $rc->teacher?->name }}</div>
                                        <small class="text-muted">{{ $rc->created_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td>{{ $rc->class?->class_name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $rc->document_type }}</span></td>
                                    <td>
                                        <div>{{ Str::limit($rc->obstacle, 60) }}</div>
                                        @if($rc->attachment_path)
                                            <a href="{{ route('owner.konsultasi.revisi.download', $rc->id) }}" class="btn btn-sm btn-link p-0 text-primary" style="font-size:12px;">
                                                <i class="fa-solid fa-download me-1"></i> {{ $rc->attachment_name ?: 'Lampiran' }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $rcBadge = match($rc->status) {
                                                'Pending'   => 'bg-warning text-dark',
                                                'Accepted'  => 'bg-info text-dark',
                                                'Completed' => 'bg-success',
                                                default     => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $rcBadge }}">{{ $rc->status }}</span>
                                    </td>
                                    <td>
                                        @if($rc->curriculum_reply)
                                            <small class="text-muted">{{ Str::limit($rc->curriculum_reply, 50) }}</small>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($rc->status === 'Pending')
                                            <form method="POST" action="{{ route('owner.konsultasi.revisi.accept', $rc->id) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="fa-solid fa-check me-1"></i> Terima
                                                </button>
                                            </form>
                                        @elseif ($rc->status === 'Accepted')
                                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalSelesaiRevisi{{ $rc->id }}">
                                                <i class="fa-solid fa-check-double me-1"></i> Selesaikan
                                            </button>
                                        @else
                                            <span class="text-muted small">Selesai</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- MODAL SELESAI REVISI --}}
                                @if($rc->status === 'Accepted')
                                    <div class="modal fade" id="modalSelesaiRevisi{{ $rc->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0">
                                                <form method="POST" action="{{ route('owner.konsultasi.revisi.complete', $rc->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold">Selesaikan Konsultasi Revisi</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Tanggapan / Catatan Penyelesaian <span class="text-danger">*</span></label>
                                                            <textarea name="curriculum_reply" class="form-control" rows="3" required placeholder="Tuliskan arahan perbaikan revisi..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0">
                                                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Kirim Penyelesaian</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-light">
                    {{ $revisionConsultations->links() }}
                </div>
            </div>
        @endif
    @endif

</div>

{{-- MODAL TAMBAH HASIL KONSULTASI BARU --}}
<div class="modal fade" id="modalTambahKonsultasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="POST" action="{{ route('owner.konsultasi.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-comments text-warning me-2"></i> Buat Hasil Konsultasi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Guru Pengajar <span class="text-danger">*</span></label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->id }}" {{ old('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Peserta Lainnya (Opsional)</label>
                            <input type="text" name="participant_names" class="form-control" value="{{ old('participant_names') }}" placeholder="Contoh: Kurikulum / Guru Pendamping">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Konsultasi <span class="text-danger">*</span></label>
                            <input type="date" name="consultation_date" class="form-control" value="{{ old('consultation_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Topik Evaluasi <span class="text-danger">*</span></label>
                            <input type="text" name="topic" class="form-control" value="{{ old('topic') }}" placeholder="Contoh: Evaluasi Penguasaan Nada & Kecepatan Mengajar" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span>Poin-Poin Perbaikan <span class="text-danger">*</span></span>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" id="btnAddPoint">
                                    <i class="fa-solid fa-plus-circle me-1"></i> Tambah Poin
                                </button>
                            </label>
                            <div id="pointsContainer">
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-light border-end-0">1</span>
                                    <input type="text" name="improvement_points[]" class="form-control" placeholder="Poin perbaikan 1" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Rencana Tindak Lanjut</label>
                            <textarea name="follow_up" class="form-control" rows="2" placeholder="Langkah konkrit yang disepakati untuk perbaikan berikutnya...">{{ old('follow_up') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Upload Dokumen / Foto Evaluasi (Opsional)</label>
                            <input type="file" name="file" class="form-control">
                            <small class="text-muted">Maksimal 20 MB (PDF, JPG, PNG, DOC, XLS).</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Simpan & Kirim Notifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnAdd = document.getElementById('btnAddPoint');
        const container = document.getElementById('pointsContainer');
        if (btnAdd && container) {
            btnAdd.addEventListener('click', function () {
                const count = container.querySelectorAll('.input-group').length + 1;
                const div = document.createElement('div');
                div.className = 'input-group mb-2';
                div.innerHTML = `
                    <span class="input-group-text bg-light border-end-0">${count}</span>
                    <input type="text" name="improvement_points[]" class="form-control" placeholder="Poin perbaikan ${count}" required>
                    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
                `;
                container.appendChild(div);
            });
        }
    });
</script>

@endsection

