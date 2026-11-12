@extends('layouts.kurikulum')

@section('title', 'Video Pembelajaran')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-circle-play text-warning me-2"></i> Video Pembelajaran</h3>
            <small class="text-muted">
                Kelola video pembelajaran berdasarkan kategori program (HSK, Jianer, Maochong, dll).
                Guru dan Siswa dapat mengakses video sesuai dengan program atau kelas mereka.
            </small>
        </div>
        <div>
            <button type="button" class="btn btn-outline-dark rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                <i class="fa-solid fa-folder-plus me-1 text-warning"></i> + Tambah Kategori Program
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER KATEGORI --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="fw-semibold text-muted small me-2"><i class="fa-solid fa-filter me-1"></i> Filter Kategori:</span>
                <a href="{{ route('kurikulum.video') }}" class="btn btn-sm rounded-pill {{ !$selectedCategory ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}">
                    Semua Kategori
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('kurikulum.video', ['category_id' => $cat->id]) }}" class="btn btn-sm rounded-pill {{ $selectedCategory == $cat->id ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}">
                        {{ $cat->category_name }}
                        @if($cat->program) <span class="opacity-75" style="font-size:10px;">({{ $cat->program->program_name }})</span> @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- FORM TAMBAH VIDEO --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i> Tambah Video Pembelajaran Baru
        </div>
        <div class="card-body p-4">
            <form action="{{ route('kurikulum.video.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                @php $source = old('source_type', 'file'); @endphp

                <div class="mb-3">
                    <label class="form-label fw-semibold d-block">Sumber Video</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="source_type" id="srcFile" value="file"
                               {{ $source === 'file' ? 'checked' : '' }} onchange="setSource('file')">
                        <label class="btn btn-outline-primary" for="srcFile">
                            <i class="fa-solid fa-upload me-1"></i> Upload File Video
                        </label>

                        <input type="radio" class="btn-check" name="source_type" id="srcYoutube" value="youtube"
                               {{ $source === 'youtube' ? 'checked' : '' }} onchange="setSource('youtube')">
                        <label class="btn btn-outline-danger" for="srcYoutube">
                            <i class="fa-brands fa-youtube me-1"></i> Link YouTube
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Judul Video <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" maxlength="255" value="{{ old('title') }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Kategori Program</label>
                        <select name="category_id" class="form-select">
                            <option value="">Pilih Kategori (HSK, Jianer, Maochong, dll)</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $selectedCategory) == $cat->id)>
                                    {{ $cat->category_name }}{{ $cat->program ? ' — ' . $cat->program->program_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Level / Kelas (Opsional)</label>
                        <select name="program_level_id" class="form-select">
                            <option value="">Semua Level di Kategori Ini</option>
                            @foreach ($levels as $l)
                                <option value="{{ $l->id }}" @selected(old('program_level_id') == $l->id)>
                                    {{ $l->level_name }}{{ $l->program ? ' — ' . $l->program->program_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi Video (opsional)</label>
                    <textarea name="description" rows="2" class="form-control" maxlength="2000">{{ old('description') }}</textarea>
                </div>

                <div class="row align-items-end">
                    <div class="col-md-5 mb-3">
                        <div id="boxFile">
                            <label class="form-label fw-semibold">File Video</label>
                            <input type="file" id="inputFile" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime">
                            <small class="text-muted">MP4, WebM, atau MOV. Maksimal 500 MB.</small>
                        </div>

                        <div id="boxYoutube" class="d-none">
                            <label class="form-label fw-semibold">Link YouTube</label>
                            <input type="url" id="inputYoutube" name="youtube_url" class="form-control"
                                   placeholder="https://www.youtube.com/watch?v=..."
                                   value="{{ old('youtube_url') }}" maxlength="255">
                            <small class="text-muted">Gunakan opsi unlisted/publik di YouTube.</small>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Thumbnail Gambar (Opsional)</label>
                        <input type="file" name="thumbnail" class="form-control" accept="image/png,image/jpeg,image/webp">
                        <small class="text-muted">JPG/PNG/WEBP, maks 2 MB.</small>
                    </div>

                    <div class="col-md-3 mb-3 text-md-end">
                        <div class="form-check form-switch mb-2 d-inline-block text-start">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="actNew" checked>
                            <label class="form-check-label small fw-semibold" for="actNew">Status Aktif</label>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold rounded-pill text-dark">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Video
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR VIDEO --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span>Daftar Video Pembelajaran ({{ $videos->total() }})</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px;">Thumbnail</th>
                        <th>Judul & Deskripsi</th>
                        <th>Kategori & Level</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($videos as $v)
                        <tr>
                            <td>
                                <img src="{{ $v->thumbnail_url }}" alt="" class="rounded shadow-sm" style="width:110px; height:65px; object-fit:cover;" onerror="this.src='{{ asset('assets/img/video-placeholder.png') }}'">
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $v->title }}</div>
                                <div class="text-muted small">{{ Str::limit($v->description, 90) ?: '-' }}</div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-warning text-dark">{{ $v->category?->category_name ?? 'Umum' }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    {{ $v->level?->level_name ? 'Level ' . $v->level->level_name : 'Semua Level' }}
                                </div>
                            </td>
                            <td>
                                @if ($v->is_youtube)
                                    <span class="badge bg-danger"><i class="fa-brands fa-youtube me-1"></i> YouTube</span>
                                @else
                                    <span class="badge bg-info text-dark"><i class="fa-solid fa-file-video me-1"></i> File</span>
                                @endif
                            </td>
                            <td>
                                @if($v->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-check me-1"></i> Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="fa-solid fa-pause me-1"></i> Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if ($v->is_youtube)
                                        <a href="{{ $v->watch_url }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Tonton di YouTube">
                                            <i class="fa-brands fa-youtube"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('kurikulum.video.stream', $v->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Putar Video">
                                            <i class="fa-solid fa-play"></i>
                                        </a>
                                    @endif

                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditVideo{{ $v->id }}" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <form action="{{ route('kurikulum.video.destroy', $v->id) }}" method="POST" onsubmit="return confirm('Hapus video ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- MODAL EDIT VIDEO --}}
                                <div class="modal fade" id="modalEditVideo{{ $v->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content rounded-4 border-0">
                                            <form action="{{ route('kurikulum.video.update', $v->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-bottom">
                                                    <h5 class="modal-title fw-bold">Edit Video Pembelajaran</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Judul Video</label>
                                                        <input type="text" name="title" class="form-control" value="{{ $v->title }}" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Kategori Program</label>
                                                            <select name="category_id" class="form-select">
                                                                <option value="">Pilih Kategori (Umum)</option>
                                                                @foreach ($categories as $cat)
                                                                    <option value="{{ $cat->id }}" @selected($v->category_id == $cat->id)>
                                                                        {{ $cat->category_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Level</label>
                                                            <select name="program_level_id" class="form-select">
                                                                <option value="">Semua Level</option>
                                                                @foreach ($levels as $l)
                                                                    <option value="{{ $l->id }}" @selected($v->program_level_id == $l->id)>
                                                                        {{ $l->level_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Deskripsi</label>
                                                        <textarea name="description" rows="2" class="form-control">{{ $v->description }}</textarea>
                                                    </div>
                                                    @if($v->source_type === 'youtube')
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Link YouTube</label>
                                                            <input type="url" name="youtube_url" class="form-control" value="{{ $v->youtube_url }}" required>
                                                        </div>
                                                    @endif
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Ganti Thumbnail Gambar (Opsional)</label>
                                                        <input type="file" name="thumbnail" class="form-control" accept="image/*">
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="actEdit{{ $v->id }}" @checked($v->is_active)>
                                                        <label class="form-check-label fw-semibold" for="actEdit{{ $v->id }}">Status Aktif</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-warning rounded-pill fw-semibold text-dark">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-play fs-2 mb-2 text-secondary d-block"></i>
                                Belum ada video pembelajaran untuk kategori ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white py-3">
            {{ $videos->links() }}
        </div>
    </div>
</div>

{{-- MODAL TAMBAH KATEGORI PROGRAM --}}
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('kurikulum.video.category.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Tambah Kategori Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Induk</label>
                        <select name="program_id" class="form-select" required>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->program_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori (contoh: HSK, Jianer, Maochong)</label>
                        <input type="text" name="category_name" class="form-control" placeholder="Nama Kategori" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small">Usia Min (opsional)</label>
                            <input type="number" name="min_age" class="form-control" placeholder="Misal: 3">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small">Usia Max (opsional)</label>
                            <input type="number" name="max_age" class="form-control" placeholder="Misal: 6">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-semibold text-dark">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setSource(src) {
    if (src === 'youtube') {
        document.getElementById('boxFile').classList.add('d-none');
        document.getElementById('boxYoutube').classList.remove('d-none');
    } else {
        document.getElementById('boxFile').classList.remove('d-none');
        document.getElementById('boxYoutube').classList.add('d-none');
    }
}
</script>
@endpush

@endsection
