@extends('layouts.kurikulum')

@section('title', 'Video Pembelajaran')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Video Pembelajaran</h3>
        <small class="text-muted">
            Upload file video atau tempel link YouTube. Video yang dipublikasikan hanya bisa ditonton
            <strong>siswa yang sedang les (status aktif) di level kelas yang dipilih</strong>.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Video belum bisa disimpan:</strong>
            <ul class="mb-0">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM TAMBAH --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Tambah Video Baru</div>
        <div class="card-body">

            <form action="{{ route('kurikulum.video.store') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="return submitVideo(this)">
                @csrf

                @php $source = old('source_type', 'file'); @endphp

                <div class="mb-3">
                    <label class="form-label fw-semibold d-block">Sumber Video</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="source_type" id="srcFile" value="file"
                               {{ $source === 'file' ? 'checked' : '' }} onchange="setSource('file')">
                        <label class="btn btn-outline-primary" for="srcFile">
                            <i class="fa-solid fa-upload me-1"></i> Upload File
                        </label>

                        <input type="radio" class="btn-check" name="source_type" id="srcYoutube" value="youtube"
                               {{ $source === 'youtube' ? 'checked' : '' }} onchange="setSource('youtube')">
                        <label class="btn btn-outline-danger" for="srcYoutube">
                            <i class="fa-brands fa-youtube me-1"></i> Link YouTube
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Judul Video</label>
                        <input type="text" name="title" class="form-control" maxlength="255"
                               value="{{ old('title') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Untuk Level</label>
                        <select name="program_level_id" class="form-select">
                            <option value="">Semua level (semua siswa yang sedang les)</option>
                            @foreach ($levels as $l)
                                <option value="{{ $l->id }}" @selected(old('program_level_id') == $l->id)>
                                    {{ $l->level_name }}{{ $l->program ? ' — ' . $l->program->program_name : '' }}
                                    ({{ $levelCounts[$l->id] ?? 0 }} siswa aktif)
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            Hanya siswa yang punya kelas aktif di level ini yang bisa membuka video.
                        </small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi (opsional)</label>
                    <textarea name="description" rows="2" class="form-control" maxlength="2000">{{ old('description') }}</textarea>
                </div>

                <div class="row align-items-end">
                    <div class="col-md-6 mb-3">

                        <div id="boxFile">
                            <label class="form-label fw-semibold">File Video</label>
                            <input type="file" id="inputFile" name="video" class="form-control"
                                   accept="video/mp4,video/webm,video/quicktime">
                            <small class="text-muted">MP4, WebM, atau MOV. Maksimal 500 MB.</small>
                        </div>

                        <div id="boxYoutube" class="d-none">
                            <label class="form-label fw-semibold">Link YouTube</label>
                            <input type="url" id="inputYoutube" name="youtube_url" class="form-control"
                                   placeholder="https://www.youtube.com/watch?v=..."
                                   value="{{ old('youtube_url') }}" maxlength="255">
                            <small class="text-muted">
                                Di YouTube atur video ke <strong>Tidak publik (unlisted)</strong> agar link tidak
                                bisa ditemukan lewat pencarian. Jangan pilih Pribadi, karena siswa tidak akan bisa memutarnya.
                            </small>
                        </div>

                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pubNew" checked>
                            <label class="form-check-label" for="pubNew">Langsung dipublikasikan</label>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3 text-md-end">
                        <button type="submit" class="btn btn-primary">Simpan Video</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- DAFTAR VIDEO --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Daftar Video ({{ $videos->count() }})</div>

        <div class="list-group list-group-flush">
            @forelse ($videos as $v)
                @php
                    $accessCount = $v->program_level_id ? ($levelCounts[$v->program_level_id] ?? 0) : $allStudents;
                @endphp

                <div class="list-group-item">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                        <div class="d-flex gap-3">
                            @if ($v->is_youtube)
                                <img src="{{ $v->thumbnail_url }}" alt="" loading="lazy"
                                     style="width:120px;height:68px;object-fit:cover;border-radius:8px;">
                            @endif

                            <div>
                                <div class="fw-semibold">
                                    {{ $v->title }}

                                    @if ($v->is_youtube)
                                        <span class="badge bg-danger ms-1"><i class="fa-brands fa-youtube"></i> YouTube</span>
                                    @else
                                        <span class="badge bg-info text-dark ms-1">File</span>
                                    @endif

                                    @if ($v->is_published)
                                        <span class="badge bg-success ms-1">Dipublikasikan</span>
                                    @else
                                        <span class="badge bg-secondary ms-1">Draft</span>
                                    @endif
                                </div>

                                <small class="text-muted">
                                    {{ $v->level->level_name ?? 'Semua level' }}
                                    · {{ $v->size_label }}
                                    · ditambahkan {{ $v->created_at->translatedFormat('d M Y') }}
                                    @if ($v->uploader) oleh {{ $v->uploader->username }} @endif
                                </small>

                                <div class="small mt-1">
                                    <i class="fa-solid fa-user-check text-success me-1"></i>
                                    @if ($v->is_published)
                                        Bisa ditonton <strong>{{ $accessCount }}</strong> siswa aktif
                                    @else
                                        Belum bisa ditonton siswa (draft)
                                    @endif
                                </div>

                                @if ($v->description)
                                    <div class="small mt-1">{{ $v->description }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            @if ($v->is_youtube)
                                <a href="{{ $v->watch_url }}" target="_blank" rel="noopener"
                                   class="btn btn-outline-secondary btn-sm">Buka di YouTube</a>
                            @else
                                <a href="{{ route('kurikulum.video.stream', $v) }}" target="_blank"
                                   class="btn btn-outline-secondary btn-sm">Putar</a>
                            @endif

                            <form action="{{ route('kurikulum.video.destroy', $v) }}" method="POST"
                                  onsubmit="return confirm('Hapus video ini?{{ $v->is_youtube ? '' : ' File juga akan dihapus.' }}')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </div>

                    {{-- EDIT --}}
                    <details class="mt-2">
                        <summary class="small text-primary" style="cursor:pointer">Ubah judul / level / status</summary>

                        <form action="{{ route('kurikulum.video.update', $v) }}" method="POST" class="row g-2 mt-1">
                            @csrf
                            @method('PUT')

                            <div class="col-md-4">
                                <input type="text" name="title" class="form-control form-control-sm"
                                       value="{{ $v->title }}" maxlength="255" required>
                            </div>

                            <div class="col-md-4">
                                <select name="program_level_id" class="form-select form-select-sm">
                                    <option value="">Semua level</option>
                                    @foreach ($levels as $l)
                                        <option value="{{ $l->id }}" @selected($v->program_level_id == $l->id)>
                                            {{ $l->level_name }}{{ $l->program ? ' — ' . $l->program->program_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <input type="text" name="description" class="form-control form-control-sm"
                                       value="{{ $v->description }}" maxlength="2000" placeholder="Deskripsi">
                            </div>

                            @if ($v->is_youtube)
                                <div class="col-md-8">
                                    <input type="url" name="youtube_url" class="form-control form-control-sm"
                                           value="{{ $v->youtube_url }}" maxlength="255" required
                                           placeholder="Link YouTube">
                                </div>
                            @endif

                            <div class="col-md-4 d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_published" value="1"
                                           id="pub{{ $v->id }}" @checked($v->is_published)>
                                    <label class="form-check-label small" for="pub{{ $v->id }}">Publik</label>
                                </div>
                                <button class="btn btn-primary btn-sm">Simpan</button>
                            </div>
                        </form>
                    </details>

                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">Belum ada video.</div>
            @endforelse
        </div>
    </div>

</div>

<script>
    function setSource(type) {
        const isFile = type === 'file';

        document.getElementById('boxFile').classList.toggle('d-none', !isFile);
        document.getElementById('boxYoutube').classList.toggle('d-none', isFile);

        const f = document.getElementById('inputFile');
        const y = document.getElementById('inputYoutube');

        f.required = isFile;  f.disabled = !isFile;
        y.required = !isFile; y.disabled = isFile;
    }

    function submitVideo(form) {
        const type = form.querySelector('input[name=source_type]:checked').value;
        const btn  = form.querySelector('button[type=submit]');

        btn.disabled  = true;
        btn.innerText = type === 'file' ? 'Mengunggah... jangan tutup halaman' : 'Menyimpan...';

        return true;
    }

    setSource(document.querySelector('input[name=source_type]:checked').value);
</script>

@endsection