@extends('admin.app')

@section('title', 'Materi Training & SOP Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endpush

@section('content')
<div class="content-body">

    {{-- Header --}}
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">Training</div>
            <h1>Materi Training & SOP Admin</h1>
            <p>Modul panduan operasional, SOP, dan video tutorial yang disediakan oleh Owner.</p>
        </div>
        <div class="dashboard-header-actions">
            <span class="badge badge-warning"><i class="fa-solid fa-eye"></i> Mode Read-Only (Admin)</span>
        </div>
    </div>

    {{-- Filter tipe --}}
    <div class="filter-pills">
        <a href="{{ route('admin.training.index', array_merge(request()->query(), ['type' => 'all', 'page' => null])) }}"
           class="filter-pill {{ ($type ?? 'all') === 'all' ? 'active' : '' }}">Semua Tipe</a>
        <a href="{{ route('admin.training.index', array_merge(request()->query(), ['type' => 'document', 'page' => null])) }}"
           class="filter-pill {{ ($type ?? '') === 'document' ? 'active' : '' }}">Dokumen / SOP</a>
        <a href="{{ route('admin.training.index', array_merge(request()->query(), ['type' => 'video', 'page' => null])) }}"
           class="filter-pill {{ ($type ?? '') === 'video' ? 'active' : '' }}">Video Tutorial</a>
    </div>

    {{-- Toolbar: level, kategori, cari --}}
    <form method="GET" action="{{ route('admin.training.index') }}" class="toolbar">
        @if(!empty($type) && $type !== 'all')
            <input type="hidden" name="type" value="{{ $type }}">
        @endif

        <select name="level" class="select-chip" onchange="this.form.submit()">
            <option value="">Semua Level</option>
            @foreach(['Umum', 'Dasar', 'Menengah', 'Lanjutan'] as $lv)
                <option value="{{ $lv }}" {{ ($level ?? '') === $lv ? 'selected' : '' }}>{{ $lv }}</option>
            @endforeach
        </select>

        @if(!empty($categories))
            <select name="category" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ ($category ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        @endif

        <div class="toolbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari materi training…">
        </div>

        <button type="submit" class="btn btn-primary">Cari</button>

        @if(request()->hasAny(['type', 'level', 'category', 'q']))
            <a href="{{ route('admin.training.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    {{-- Daftar materi --}}
    @if($materials->count())
        <div class="x-training-grid">
            @foreach($materials as $material)
                @php $isVideo = $material->type === 'video'; @endphp

                <div class="card x-training-card">
                    @if($material->thumbnail_path && Storage::disk('public')->exists($material->thumbnail_path))
                        <div class="x-training-thumb">
                            <img src="{{ asset('storage/' . $material->thumbnail_path) }}" alt="{{ $material->title }}" loading="lazy">
                        </div>
                    @endif

                    <div class="x-training-body">
                        <div class="x-training-top">
                            <span class="badge badge-cold x-training-cat"><i class="fa-solid fa-tag"></i> {{ $material->category }}</span>
                            @if($material->created_at)
                                <span class="x-training-date">{{ $material->created_at->translatedFormat('d M Y') }}</span>
                            @endif
                        </div>

                        <div class="x-training-title">{{ $material->title }}</div>
                        <p class="x-training-desc">
                            {{ Str::limit($material->description, 120) ?: 'Tidak ada deskripsi tambahan.' }}
                        </p>

                        <div class="x-training-meta">
                            <span class="badge {{ $isVideo ? 'badge-hot' : 'badge-warning' }}">
                                <i class="fa-solid {{ $isVideo ? 'fa-circle-play' : 'fa-file-lines' }}"></i>
                                {{ $isVideo ? 'Video' : 'Dokumen' }}
                            </span>
                            <span class="badge badge-pending">Level: {{ $material->level ?: 'Umum' }}</span>
                            <span class="x-muted">{{ $material->version ?: 'v1.0' }}</span>
                        </div>

                        @if($isVideo || $material->file_path)
                            <div class="x-training-actions">
                                @if($isVideo)
                                    <a href="{{ route('admin.training.stream', $material->id) }}" target="_blank" class="x-training-btn is-play">
                                        <i class="fa-solid fa-circle-play"></i> Tonton Video
                                    </a>
                                @endif

                                @if($material->file_path)
                                    <a href="{{ route('admin.training.download', $material->id) }}" class="x-training-btn is-download">
                                        <i class="fa-solid fa-download"></i> Unduh File
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="x-training-foot">
                        <span><i class="fa-solid fa-user"></i>Dari: Owner</span>
                        <span class="x-training-ro">Read-Only</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-footer">
            <div class="table-footer-info">
                Menampilkan <strong>{{ $materials->firstItem() }}–{{ $materials->lastItem() }}</strong>
                dari <strong>{{ $materials->total() }}</strong> materi
            </div>
            <div class="table-footer-pagination">
                {{ $materials->links() }}
            </div>
        </div>
    @else
        <div class="card">
            <div class="x-empty">
                <i class="fa-solid fa-graduation-cap"></i>
                Belum ada materi training untuk Admin.
            </div>
        </div>
    @endif

</div>
@endsection