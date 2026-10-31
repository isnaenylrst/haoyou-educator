@extends('layouts.guru')

@section('title', 'Materi Training Guru')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-graduation-cap text-warning me-2"></i> Materi & Pelatihan Guru</h3>
            <p class="text-muted mb-0">
                Akses modul pengajaran, video tutorial, dan standar operasional pelatihan dari Owner & Manajemen.
            </p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('teacher.training') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0" placeholder="Cari materi training atau modul...">
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
                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-3">Cari</button>
                    @if(request('q') || request('category'))
                        <a href="{{ route('teacher.training') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
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
                <p class="text-muted">Materi pelatihan dan modul dari Owner akan muncul di halaman ini.</p>
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
                                            onclick="openVideoModal('{{ addslashes($m->title) }}', '{{ $m->youtube_embed_url }}', '{{ $m->video_path ? route('teacher.training.stream', $m->id) : ($m->video_url ?: '') }}')">
                                        <i class="fa-solid fa-circle-play me-1"></i> Putar Video
                                    </button>
                                @endif

                                @if ($m->file_path)
                                    <a href="{{ route('teacher.training.download', $m->id) }}" class="btn btn-sm btn-outline-success rounded-pill fw-semibold">
                                        <i class="fa-solid fa-download me-1"></i> Unduh {{ strtoupper($m->file_extension ?: 'FILE') }}
                                    </a>
                                @endif
                            </div>

                            <hr class="my-2 border-secondary border-opacity-10">

                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 text-muted small">
                                <span><i class="fa-solid fa-user-tie me-1"></i> Dari: {{ $m->uploader?->name ?? 'Owner' }}</span>
                                <span class="badge bg-light text-secondary border">Materi Pelatihan</span>
                            </div>
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

