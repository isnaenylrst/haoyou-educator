@extends('layouts.kurikulum')

@section('title', 'Video Pembelajaran')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Video Pembelajaran</h3>
        <small class="text-muted">
            Unggah video untuk siswa. Video yang dipublikasikan langsung bisa ditonton siswa pada level kelas yang dipilih.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Video belum bisa diunggah:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM UPLOAD --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Upload Video Baru</div>
        <div class="card-body">
            <form action="{{ route('kurikulum.video.store') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Mengunggah... jangan tutup halaman';">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Judul Video</label>
                        <input type="text" name="title" class="form-control" maxlength="255"
                               value="{{ old('title') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Untuk Level</label>
                        <select name="program_level_id" class="form-select">
                            <option value="">Semua level</option>
                            @foreach ($levels as $l)
                                <option value="{{ $l->id }}" @selected(old('program_level_id') == $l->id)>
                                    {{ $l->level_name }}{{ $l->program ? ' — ' . $l->program->program_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi (opsional)</label>
                    <textarea name="description" rows="2" class="form-control" maxlength="2000">{{ old('description') }}</textarea>
                </div>

                <div class="row align-items-end">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">File Video</label>
                        <input type="file" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime" required>
                        <small class="text-muted">MP4, WebM, atau MOV. Maksimal 500 MB.</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pubNew" checked>
                            <label class="form-check-label" for="pubNew">Langsung dipublikasikan</label>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3 text-md-end">
                        <button type="submit" class="btn btn-primary">Upload Video</button>
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
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">
                                {{ $v->title }}
                                @if ($v->is_published)
                                    <span class="badge bg-success ms-1">Dipublikasikan</span>
                                @else
                                    <span class="badge bg-secondary ms-1">Draft</span>
                                @endif
                            </div>
                            <small class="text-muted">
                                {{ $v->level->level_name ?? 'Semua level' }}
                                · {{ $v->size_label }}
                                · diunggah {{ $v->created_at->translatedFormat('d M Y') }}
                                @if ($v->uploader) oleh {{ $v->uploader->username }} @endif
                            </small>
                            @if ($v->description)
                                <div class="small mt-1">{{ $v->description }}</div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ asset('storage/' . $v->file_path) }}" target="_blank"
                               class="btn btn-outline-secondary btn-sm">Putar</a>

                            <form action="{{ route('kurikulum.video.destroy', $v) }}" method="POST"
                                  onsubmit="return confirm('Hapus video ini? File juga akan dihapus.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </div>

                    <details class="mt-2">
                        <summary class="small text-primary" style="cursor:pointer">Ubah judul / level / status</summary>
                        <form action="{{ route('kurikulum.video.update', $v) }}" method="POST" class="row g-2 mt-1">
                            @csrf @method('PUT')

                            <div class="col-md-4">
                                <input type="text" name="title" class="form-control form-control-sm"
                                       value="{{ $v->title }}" maxlength="255" required>
                            </div>

                            <div class="col-md-3">
                                <select name="program_level_id" class="form-select form-select-sm">
                                    <option value="">Semua level</option>
                                    @foreach ($levels as $l)
                                        <option value="{{ $l->id }}" @selected($v->program_level_id == $l->id)>
                                            {{ $l->level_name }}{{ $l->program ? ' — ' . $l->program->program_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <input type="text" name="description" class="form-control form-control-sm"
                                       value="{{ $v->description }}" maxlength="2000" placeholder="Deskripsi">
                            </div>

                            <div class="col-md-2 d-flex align-items-center gap-2">
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
                <div class="list-group-item text-center text-muted py-4">Belum ada video yang diunggah.</div>
            @endforelse

        </div>
    </div>

</div>

@endsection