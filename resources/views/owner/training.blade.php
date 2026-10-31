@extends('layouts.owner')

@section('title', 'Materi Training Pengajar')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-graduation-cap text-warning me-2"></i> Materi Training Pengajar</h2>
            <p class="text-muted mb-0">
                Kelola modul pelatihan, SOP, video tutorial, dan materi pengembangan kompetensi untuk seluruh guru dan tim kurikulum.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-warning rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMateri">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Materi Baru
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

    {{-- STATISTIK SINGKAT --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-book-open fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Materi</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-circle-play fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Materi Video</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['with_video'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-file-pdf fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Dokumen / Modul</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['with_file'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-tags fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Kategori Materi</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['total_categories'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('owner.training.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0" placeholder="Cari judul materi training atau deskripsi...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 fw-semibold rounded-3">Cari</button>
                    @if(request('q') || request('category'))
                        <a href="{{ route('owner.training.index') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR MATERI (GRID CARDS) --}}
    @if ($materials->isEmpty())
        <div class="card shadow-sm border-0 rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-graduation-cap fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Belum Ada Materi Training</h5>
                <p class="text-muted">Klik tombol "Upload Materi Baru" untuk membagikan modul atau video pelatihan kepada pengajar.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($materials as $m)
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100 d-flex flex-column">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1 rounded-pill small">
                                    <i class="fa-solid fa-tag me-1"></i> {{ $m->category }}
                                </span>
                                <small class="text-muted">{{ $m->created_at->format('d M Y') }}</small>
                            </div>

                            <h5 class="fw-bold text-dark mb-2">{{ $m->title }}</h5>

                            <p class="text-muted small flex-grow-1 mb-3" style="min-height: 48px; line-height: 1.5;">
                                {{ Str::limit($m->description ?: 'Tidak ada deskripsi tambahan.', 110) }}
                            </p>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @if ($m->has_video)
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-semibold"
                                            onclick="openVideoModal('{{ addslashes($m->title) }}', '{{ $m->youtube_embed_url }}', '{{ $m->video_path ? route('owner.training.stream', $m->id) : ($m->video_url ?: '') }}')">
                                        <i class="fa-solid fa-circle-play me-1"></i> Putar Video
                                    </button>
                                @endif

                                @if ($m->file_path)
                                    <a href="{{ route('owner.training.download', $m->id) }}" class="btn btn-sm btn-outline-success rounded-pill fw-semibold">
                                        <i class="fa-solid fa-download me-1"></i> Unduh {{ strtoupper($m->file_extension ?: 'FILE') }}
                                    </a>
                                @endif
                            </div>

                            <hr class="my-2 border-secondary border-opacity-10">

                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                                <div class="text-muted small">
                                    <i class="fa-solid fa-user-circle me-1"></i> {{ $m->uploader?->name ?? $m->uploader?->username ?? 'Owner' }}
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light text-primary rounded-circle"
                                            title="Edit Materi"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditMateri{{ $m->id }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form method="POST" action="{{ route('owner.training.destroy', $m->id) }}"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi training ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle" title="Hapus Materi">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL EDIT MATERI --}}
                <div class="modal fade" id="modalEditMateri{{ $m->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="fw-bold"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Materi Training</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('owner.training.update', $m->id) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Judul Materi <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" value="{{ old('title', $m->title) }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                                        <input type="text" name="category" list="category_list_edit" class="form-control" value="{{ old('category', $m->category) }}" required>
                                        <datalist id="category_list_edit">
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat }}">
                                            @endforeach
                                        </datalist>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Deskripsi / Catatan Materi</label>
                                        <textarea name="description" class="form-control" rows="3">{{ old('description', $m->description) }}</textarea>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Ganti File Dokumen (PDF, PPT, DOC, XLS, ZIP)</label>
                                            <input type="file" name="file" class="form-control">
                                            @if($m->file_path)
                                                <small class="text-muted d-block mt-1">File saat ini: {{ $m->file_name }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Ganti File Video Lokal (MP4, WEBM)</label>
                                            <input type="file" name="video_file" class="form-control" accept="video/*">
                                            @if($m->video_path)
                                                <small class="text-muted d-block mt-1">Video lokal tersimpan</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Link Video (YouTube / Google Drive / URL Eksternal)</label>
                                        <input type="url" name="video_url" class="form-control" value="{{ old('video_url', $m->video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
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
        </div>

        <div class="mt-4">
            {{ $materials->links() }}
        </div>
    @endif

</div>

{{-- MODAL TAMBAH MATERI --}}
<div class="modal fade" id="modalTambahMateri" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i> Upload Materi Training Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('owner.training.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Materi <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: SOP Pengajaran Kelas Reguler Anak & Penanganan Siswa Pasif" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori Materi <span class="text-danger">*</span></label>
                        <input type="text" name="category" list="category_list" class="form-control" placeholder="Pilih atau ketik kategori baru..." required>
                        <datalist id="category_list">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi / Ringkasan Materi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tuliskan tujuan pelatihan, pokok bahasan, atau instruksi untuk guru/kurikulum..."></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Upload File Dokumen</label>
                            <input type="file" name="file" class="form-control">
                            <small class="text-muted">Format: PDF, PPTX, DOCX, XLSX, ZIP. Maks. 50 MB.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Upload Video Penjelasan (Opsional)</label>
                            <input type="file" name="video_file" class="form-control" accept="video/*">
                            <small class="text-muted">Format: MP4, WEBM. Maks. 100 MB.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Atau Link Video Eksternal (YouTube / Video URL)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                        <small class="text-muted">Masukkan link jika video di-hosting di YouTube atau server eksternal.</small>
                    </div>
                    <div class="alert alert-info py-2 px-3 rounded-3 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i> Setelah diunggah, seluruh Guru dan Tim Kurikulum akan menerima notifikasi otomatis.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold">Upload & Kirim Notifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PEMUTAR VIDEO --}}
<div class="modal fade" id="modalVideoPlayer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow bg-dark text-white">
            <div class="modal-header border-secondary pb-2">
                <h6 class="fw-bold mb-0 text-white" id="videoModalTitle"><i class="fa-solid fa-circle-play text-danger me-2"></i> Putar Video Training</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopVideo()"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9" id="videoContainer">
                    {{-- Iframe / Video dimasukkan lewat JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openVideoModal(title, embedUrl, directUrl) {
        document.getElementById('videoModalTitle').innerText = title;
        const container = document.getElementById('videoContainer');
        container.innerHTML = '';

        if (embedUrl) {
            container.innerHTML = `<iframe src="${embedUrl}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        } else if (directUrl) {
            container.innerHTML = `<video src="${directUrl}" controls autoplay style="width:100%;height:100%;background:#000;"></video>`;
        }

        const modal = new bootstrap.Modal(document.getElementById('modalVideoPlayer'));
        modal.show();
    }

    function stopVideo() {
        document.getElementById('videoContainer').innerHTML = '';
    }

    document.getElementById('modalVideoPlayer').addEventListener('hidden.bs.modal', function () {
        stopVideo();
    });
</script>
@endsection

