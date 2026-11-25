@php
    $rp     = request()->routeIs('kurikulum.*') ? 'kurikulum' : 'owner';
    $layout = $rp === 'kurikulum' ? 'layouts.kurikulum' : 'layouts.owner';
@endphp
@extends($layout)

@section('title', 'Video Self Learning')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-circle-play text-warning me-2"></i> Video Self Learning</h2>
            <p class="text-muted mb-0">
                Kelola video tutorial yang tampil di Belajar Mandiri → Panduan → Video Tutorial untuk siswa.
            </p>
        </div>
        <button type="button" class="btn btn-warning rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahVideo">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Tambah Video
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm">
            <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Gagal menyimpan:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-layer-group fa-2x"></i></div>
                    <div><div class="text-muted small">Total Video</div><h4 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success"><i class="fa-solid fa-language fa-2x"></i></div>
                    <div>
                        <div class="text-muted small">Panduan Vocab</div>
                        <h6 class="fw-bold mb-0 {{ $stats['vocab'] ? 'text-success' : 'text-secondary' }}">{{ $stats['vocab'] ? 'Ada video aktif' : 'Belum ada' }}</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info"><i class="fa-solid fa-spell-check fa-2x"></i></div>
                    <div>
                        <div class="text-muted small">Panduan Grammar</div>
                        <h6 class="fw-bold mb-0 {{ $stats['grammar'] ? 'text-success' : 'text-secondary' }}">{{ $stats['grammar'] ? 'Ada video aktif' : 'Belum ada' }}</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger"><i class="fa-solid fa-file-video fa-2x"></i></div>
                    <div><div class="text-muted small">File Upload</div><h4 class="fw-bold mb-0 text-dark">{{ $stats['upload'] }}</h4></div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER MODUL --}}
    <div class="card shadow-sm border-0 mb-3 rounded-4">
        <div class="card-body p-3 d-flex flex-wrap gap-1 align-items-center">
            <span class="text-muted small me-2 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Modul:</span>
            <a href="{{ route($rp . '.self-learning.index') }}" class="btn btn-sm rounded-pill px-3 {{ !$module ? 'btn-dark' : 'btn-outline-secondary' }}">Semua</a>
            @foreach ($modules as $key => $label)
                <a href="{{ route($rp . '.self-learning.index', ['module' => $key]) }}"
                   class="btn btn-sm rounded-pill px-3 {{ $module === $key ? 'btn-warning' : 'btn-outline-warning text-dark' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="alert alert-info py-2 px-3 rounded-3 small">
        <i class="fa-solid fa-circle-info me-1"></i>
        Setiap modul hanya menampilkan <strong>satu video aktif</strong>. Mengaktifkan video baru otomatis menonaktifkan video aktif sebelumnya di modul yang sama.
    </div>

    {{-- DAFTAR VIDEO --}}
    @if ($videos->isEmpty())
        <div class="card shadow-sm border-0 rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-circle-play fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Belum ada video</h5>
                <p class="text-muted mb-0">Klik "Tambah Video" untuk mengunggah video tutorial pertama.</p>
            </div>
        </div>
    @else
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Video</th>
                            <th>Modul</th>
                            <th>Sumber</th>
                            <th>Status</th>
                            <th>Diunggah</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($videos as $v)
                            <tr>
                                <td class="ps-4" style="min-width:260px">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 overflow-hidden bg-dark flex-shrink-0 d-flex align-items-center justify-content-center" style="width:96px;height:56px;">
                                            @if ($v->thumbnail_url)
                                                <img src="{{ $v->thumbnail_url }}" alt="" class="w-100 h-100 object-fit-cover">
                                            @else
                                                <i class="fa-solid fa-file-video text-white-50 fa-lg"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $v->title }}</div>
                                            <small class="text-muted d-block">{{ Str::limit($v->description ?: 'Tanpa deskripsi.', 70) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge {{ $v->module === 'grammar' ? 'bg-info' : 'bg-success' }} rounded-pill">{{ $v->module_label }}</span></td>
                                <td>
                                    @if ($v->source_type === 'youtube')
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill"><i class="fa-brands fa-youtube me-1"></i> YouTube</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill"><i class="fa-solid fa-file-video me-1"></i> Upload
                                            @if ($v->file_size) · {{ number_format($v->file_size / 1048576, 1) }} MB @endif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($v->is_active)
                                        <span class="badge bg-success rounded-pill">Aktif (tampil ke siswa)</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $v->created_at->format('d M Y') }}<br>{{ $v->uploader?->username }}</small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="btn btn-sm btn-light text-danger rounded-circle" title="Putar"
                                                data-title="{{ $v->title }}"
                                                data-embed="{{ $v->embed_url }}"
                                                data-src="{{ $v->is_upload ? route($rp . '.self-learning.stream', $v) : '' }}"
                                                onclick="playVideo(this)">
                                            <i class="fa-solid fa-play"></i>
                                        </button>

                                        <form method="POST" action="{{ route($rp . '.self-learning.toggle', $v) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $v->is_active ? 'btn-outline-secondary' : 'btn-success' }} rounded-pill fw-semibold">
                                                {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" title="Edit"
                                                data-bs-toggle="modal" data-bs-target="#modalEditVideo{{ $v->id }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <form method="POST" action="{{ route($rp . '.self-learning.destroy', $v) }}"
                                              onsubmit="return confirm('Hapus video ini? File yang diunggah juga ikut terhapus.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODAL EDIT --}}
    @foreach ($videos as $v)
        <div class="modal fade" id="modalEditVideo{{ $v->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Video</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="{{route($rp . '.self-learning.update', $v) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ $v->title }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Modul <span class="text-danger">*</span></label>
                                    <select name="module" class="form-select" required>
                                        @foreach ($modules as $key => $label)
                                            <option value="{{ $key }}" {{ $v->module === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2">{{ $v->description }}</textarea>
                            </div>

                            <div class="border rounded-3 p-3 bg-light">
                                <div class="small fw-semibold mb-2">
                                    Sumber saat ini:
                                    @if ($v->source_type === 'youtube') <span class="text-danger">YouTube</span> ({{ $v->youtube_url }})
                                    @else <span class="text-secondary">File upload</span>
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Ganti dengan link YouTube</label>
                                        <input type="text" name="youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Atau ganti dengan file video</label>
                                        <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">Kosongkan keduanya jika sumber video tidak diganti.</small>
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

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahVideo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i> Tambah Video Self Learning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route($rp . '.self-learning.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @php $srcType = old('source_type', 'youtube'); @endphp

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Contoh: Tutorial belajar Vocab" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tampil di modul <span class="text-danger">*</span></label>
                            <select name="module" class="form-select" required>
                                @foreach ($modules as $key => $label)
                                    <option value="{{ $key }}" {{ old('module', $module) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi (opsional)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Ringkasan isi video">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Sumber Video <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source_type" id="src_yt" value="youtube" {{ $srcType === 'youtube' ? 'checked' : '' }} onchange="toggleSource('youtube')">
                                <label class="form-check-label fw-semibold" for="src_yt"><i class="fa-brands fa-youtube text-danger me-1"></i> Link YouTube</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source_type" id="src_up" value="upload" {{ $srcType === 'upload' ? 'checked' : '' }} onchange="toggleSource('upload')">
                                <label class="form-check-label fw-semibold" for="src_up"><i class="fa-solid fa-file-video text-secondary me-1"></i> Upload File</label>
                            </div>
                        </div>
                    </div>

                    <div id="fieldYoutube" class="{{ $srcType === 'youtube' ? '' : 'd-none' }} mb-3">
                        <label class="form-label fw-semibold">Link YouTube</label>
                        <input type="text" name="youtube_url" class="form-control" value="{{ old('youtube_url') }}" placeholder="https://www.youtube.com/watch?v=... atau https://youtu.be/...">
                        <small class="text-muted">Mendukung link watch, youtu.be, shorts, dan embed.</small>
                    </div>

                    <div id="fieldUpload" class="{{ $srcType === 'upload' ? '' : 'd-none' }} mb-3">
                        <label class="form-label fw-semibold">File Video</label>
                        <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
                        <small class="text-muted">Format MP4, WEBM, MOV. Maks. 200 MB. Video besar sebaiknya lewat YouTube.</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="isActiveNew" checked>
                        <label class="form-check-label fw-semibold" for="isActiveNew">Langsung aktifkan (tampil ke siswa)</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold">Simpan Video</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PEMUTAR --}}
<div class="modal fade" id="modalPlayer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow bg-dark text-white">
            <div class="modal-header border-secondary pb-2">
                <h6 class="fw-bold mb-0 text-white" id="playerTitle">Pratinjau Video</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9" id="playerBox"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSource(type) {
        document.getElementById('fieldYoutube').classList.toggle('d-none', type !== 'youtube');
        document.getElementById('fieldUpload').classList.toggle('d-none', type !== 'upload');
    }

    function playVideo(btn) {
        const box = document.getElementById('playerBox');
        document.getElementById('playerTitle').innerText = btn.dataset.title;
        box.innerHTML = '';

        if (btn.dataset.embed) {
            box.innerHTML = `<iframe src="${btn.dataset.embed}?autoplay=1" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>`;
        } else if (btn.dataset.src) {
            box.innerHTML = `<video src="${btn.dataset.src}" controls autoplay style="width:100%;height:100%;background:#000;"></video>`;
        }
        new bootstrap.Modal(document.getElementById('modalPlayer')).show();
    }

    document.getElementById('modalPlayer').addEventListener('hidden.bs.modal', () => {
        document.getElementById('playerBox').innerHTML = '';
    });

    @if ($errors->any() && old('title') !== null)
        document.addEventListener('DOMContentLoaded', () => {
            new bootstrap.Modal(document.getElementById('modalTambahVideo')).show();
        });
    @endif
</script>
@endsection