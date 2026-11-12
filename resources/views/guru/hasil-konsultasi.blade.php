@extends('layouts.guru')

@section('title', 'Hasil Konsultasi & Konsultasi Revisi')

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-comments text-warning me-2"></i> Hasil Konsultasi & Konsultasi Revisi</h3>
            <p class="text-muted mb-0">
                Pusat evaluasi Kurikulum dan pengajuan konsultasi revisi dokumen mengajar (Materi, Progress Report, LP, PPT).
            </p>
        </div>

        <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAjukanRevisi">
            <i class="fa-solid fa-plus me-1"></i> Ajukan Konsultasi Revisi
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- NAV TABS --}}
    <ul class="nav nav-pills mb-4 gap-2" id="consultationTabs">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'hasil' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('teacher.hasil-konsultasi', ['tab' => 'hasil']) }}">
                <i class="fa-solid fa-clipboard-check me-2"></i> Hasil Konsultasi Kurikulum ({{ $consultations->total() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-semibold {{ $activeTab === 'revisi' ? 'active bg-dark text-white' : 'bg-white text-dark shadow-sm' }}"
               href="{{ route('teacher.hasil-konsultasi', ['tab' => 'revisi']) }}">
                <i class="fa-solid fa-arrows-rotate me-2"></i> Konsultasi Revisi Dokumen ({{ $revisionConsultations->total() }})
            </a>
        </li>
    </ul>

    @if ($activeTab === 'hasil')
        {{-- ==================== TAB 1: HASIL KONSULTASI DARI KURIKULUM (READ-ONLY) ==================== --}}
        {{-- FILTER & SEARCH --}}
        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('teacher.hasil-konsultasi') }}" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="hasil">
                    <div class="col-md-3">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" title="Dari Tanggal">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" title="Sampai Tanggal">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari topik atau catatan...">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-warning w-100 fw-bold rounded-3">Filter</button>
                        @if(request('date_from') || request('date_to') || request('q'))
                            <a href="{{ route('teacher.hasil-konsultasi', ['tab' => 'hasil']) }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if ($consultations->isEmpty())
            <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-comments fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Hasil Konsultasi</h5>
                    <p class="text-muted">Hasil evaluasi atau konsultasi dari Tim Kurikulum akan tercatat di sini.</p>
                </div>
            </div>
        @else
            <div class="row g-4">
                @foreach ($consultations as $c)
                    <div class="col-12">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-warning bg-opacity-10 text-dark fw-bold px-3 py-2 rounded-pill me-2">
                                        <i class="fa-regular fa-calendar me-1"></i> {{ $c->consultation_date?->translatedFormat('d F Y') }}
                                    </span>
                                    <span class="text-muted small">
                                        <i class="fa-solid fa-user-tie me-1"></i> Tim Kurikulum: <strong>{{ $c->curriculumUser?->display_name ?? 'Kurikulum' }}</strong>
                                    </span>
                                </div>
                                <div>
                                    @if($c->observation)
                                        <span class="badge {{ $c->observation->sop_badge }} px-3 py-2 rounded-pill">
                                            <i class="fa-solid fa-magnifying-glass me-1"></i> Observasi: {{ $c->observation->sop_compliance }} ({{ $c->observation->class?->class_name ?? 'Kelas' }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <h4 class="fw-bold text-dark mb-3">{{ $c->topic }}</h4>

                                <div class="row g-3">
                                    {{-- POIN PERBAIKAN --}}
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light h-100 border">
                                            <h6 class="fw-bold text-danger mb-3">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Poin-Poin yang Perlu Diperbaiki:
                                            </h6>
                                            @if(is_array($c->improvement_points) && count($c->improvement_points) > 0)
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($c->improvement_points as $point)
                                                        <li class="mb-2 d-flex align-items-start gap-2">
                                                            <i class="fa-solid fa-circle-check text-warning mt-1"></i>
                                                            <span class="text-dark">{{ $point }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted mb-0 small">Tidak ada poin perbaikan spesifik.</p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- TINDAK LANJUT --}}
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light h-100 border">
                                            <h6 class="fw-bold text-primary mb-3">
                                                <i class="fa-solid fa-list-check me-1"></i> Rencana Tindak Lanjut & Solusi:
                                            </h6>
                                            <p class="text-dark mb-0" style="white-space: pre-line;">
                                                {{ $c->follow_up ?: 'Belum ada catatan tindak lanjut khusus.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                @if($c->file_path)
                                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="text-muted small">
                                            <i class="fa-solid fa-paperclip me-1"></i> Lampiran Berkas: <strong>{{ $c->file_name }}</strong>
                                        </div>
                                        <a href="{{ route('teacher.hasil-konsultasi.download', $c->id) }}" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
                                            <i class="fa-solid fa-download me-1"></i> Unduh Berkas ({{ strtoupper($c->file_extension ?: 'FILE') }})
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $consultations->links() }}
            </div>
        @endif

    @else
        {{-- ==================== TAB 2: KONSULTASI REVISI GURU (POIN 2) ==================== --}}
        @if ($revisionConsultations->isEmpty())
            <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-arrows-rotate fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Pengajuan Konsultasi Revisi</h5>
                    <p class="text-muted">Klik tombol "Ajukan Konsultasi Revisi" di atas untuk berkonsultasi mengenai materi, progress report, LP, atau PPT.</p>
                </div>
            </div>
        @else
            <div class="row g-4">
                @foreach ($revisionConsultations as $rc)
                    <div class="col-12">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill me-2">
                                        Dokumen: {{ $rc->document_type }}
                                    </span>
                                    @if($rc->class)
                                        <span class="fw-bold text-dark me-2">
                                            <i class="fa-solid fa-chalkboard text-warning me-1"></i> {{ $rc->class->class_name }}
                                        </span>
                                    @endif
                                    <small class="text-muted">Diajukan: {{ $rc->created_at->translatedFormat('d M Y, H:i') }}</small>
                                </div>
                                <div>
                                    <span class="badge {{ $rc->status_badge }} px-3 py-2 rounded-pill fw-semibold">
                                        {{ $rc->status }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="row g-3">
                                    {{-- KENDALA GURU --}}
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light h-100 border">
                                            <h6 class="fw-bold text-danger mb-2">
                                                <i class="fa-solid fa-circle-question me-1"></i> Kendala / Pertanyaan Guru:
                                            </h6>
                                            <p class="text-dark mb-0 small" style="white-space: pre-line;">{{ $rc->obstacle }}</p>

                                            @if($rc->attachment_path)
                                                <div class="mt-3 pt-2 border-top">
                                                    <a href="{{ route('teacher.hasil-konsultasi.revisi.download', $rc->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                                                        <i class="fa-solid fa-paperclip me-1"></i> Unduh Lampiran Guru
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- ARAHAN / BALASAN KURIKULUM --}}
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 {{ $rc->status === 'Selesai' ? 'bg-success-subtle border border-success-subtle' : 'bg-light border' }} h-100">
                                            <h6 class="fw-bold {{ $rc->status === 'Selesai' ? 'text-success-emphasis' : 'text-primary' }} mb-2">
                                                <i class="fa-solid fa-reply me-1"></i> Balasan & Arahan Kurikulum:
                                            </h6>
                                            @if($rc->curriculum_reply)
                                                <p class="text-dark mb-0 small" style="white-space: pre-line;">{{ $rc->curriculum_reply }}</p>
                                                <small class="text-muted d-block mt-2">Dijawab oleh: <strong>{{ $rc->curriculum?->display_name ?? 'Kurikulum' }}</strong> ({{ $rc->completed_at?->translatedFormat('d M Y H:i') }})</small>
                                            @elseif($rc->status === 'Diterima')
                                                <p class="text-muted mb-0 small fst-italic">
                                                    Permintaan konsultasi telah diterima oleh Kurikulum ({{ $rc->curriculum?->display_name ?? 'Kurikulum' }}). Menunggu penyusunan balasan & solusi.
                                                </p>
                                            @else
                                                <p class="text-muted mb-0 small fst-italic">
                                                    Menunggu respon dari Tim Kurikulum.
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $revisionConsultations->links() }}
            </div>
        @endif
    @endif

</div>

{{-- MODAL AJUKAN KONSULTASI REVISI --}}
<div class="modal fade" id="modalAjukanRevisi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('teacher.hasil-konsultasi.revisi.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-comments text-warning me-2"></i> Ajukan Konsultasi Revisi Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis Dokumen yang Dikonsultasikan <span class="text-danger">*</span></label>
                    <select name="document_type" class="form-select" required>
                        <option value="">Pilih Jenis Dokumen...</option>
                        <option value="LP">Lesson Plan (LP)</option>
                        <option value="PPT">Slide Presentasi (PPT)</option>
                        <option value="Materi">Materi Kurikulum / Silabus</option>
                        <option value="Progress Report">Progress Report Siswa</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas Terkait (Opsional)</label>
                    <select name="class_id" class="form-select">
                        <option value="">Pilih Kelas (Jika spesifik)...</option>
                        @foreach($myClasses as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->class_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kendala / Pertanyaan Revisi <span class="text-danger">*</span></label>
                    <textarea name="obstacle" rows="4" class="form-control" placeholder="Jelaskan bagian yang membingungkan atau kendala yang dihadapi saat merevisi..." required minlength="10" maxlength="3000"></textarea>
                    <small class="text-muted">Minimal 10 karakter.</small>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold">Lampiran Berkas (Opsional)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                    <small class="text-muted">Format: PDF, Word, PPT, atau Gambar. Maksimal 20 MB.</small>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                    <i class="fa-solid fa-paper-plane me-1"></i> Kirim ke Kurikulum
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
