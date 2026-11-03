@extends('admin.app')

@section('title', 'Materi Training & SOP Admin')

@section('content')
<div class="content-body" style="padding: 24px;">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Materi Training & SOP Admin</h3>
            <p class="text-muted mb-0">Modul panduan operasional, SOP, dan video tutorial yang disediakan oleh Owner.</p>
        </div>
        <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
            <i class="fa-solid fa-eye me-1"></i> Mode Read-Only (Admin)
        </div>
    </div>

    {{-- Filter Nav: All, Dokumen / SOP PDF, Video --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.training.index') }}" class="row g-2 align-items-center">
                <div class="col-auto">
                    <div class="btn-group rounded-pill" role="group">
                        <a href="{{ route('admin.training.index', array_merge(request()->query(), ['type' => 'all'])) }}" class="btn btn-sm {{ ($type ?? 'all') === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Semua Tipe</a>
                        <a href="{{ route('admin.training.index', array_merge(request()->query(), ['type' => 'document'])) }}" class="btn btn-sm {{ ($type ?? '') === 'document' ? 'btn-dark' : 'btn-outline-secondary' }}"><i class="fa-solid fa-file-pdf me-1 text-danger"></i> Dokumen / SOP</a>
                        <a href="{{ route('admin.training.index', array_merge(request()->query(), ['type' => 'video'])) }}" class="btn btn-sm {{ ($type ?? '') === 'video' ? 'btn-dark' : 'btn-outline-secondary' }}"><i class="fa-solid fa-circle-play me-1 text-primary"></i> Video Tutorial</a>
                    </div>
                </div>

                <div class="col-auto">
                    <select name="level" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                        <option value="">Semua Level</option>
                        <option value="Umum" {{ ($level ?? '') === 'Umum' ? 'selected' : '' }}>Umum</option>
                        <option value="Dasar" {{ ($level ?? '') === 'Dasar' ? 'selected' : '' }}>Dasar</option>
                        <option value="Menengah" {{ ($level ?? '') === 'Menengah' ? 'selected' : '' }}>Menengah</option>
                        <option value="Lanjutan" {{ ($level ?? '') === 'Lanjutan' ? 'selected' : '' }}>Lanjutan</option>
                    </select>
                </div>

                @if(!empty($categories))
                    <div class="col-auto">
                        <select name="category" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ ($category ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col">
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari materi training…" class="form-control form-control-sm rounded-pill">
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Cari</button>
                    @if(request()->hasAny(['type', 'level', 'category', 'q']))
                        <a href="{{ route('admin.training.index') }}" class="btn btn-sm btn-link text-muted text-decoration-none">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="row g-4">
        @forelse($materials as $material)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                    <div style="height: 160px; background-color: #f1f5f9; position: relative;" class="d-flex align-items-center justify-content-center">
                        @if($material->thumbnail_path && Storage::disk('public')->exists($material->thumbnail_path))
                            <img src="{{ asset('storage/' . $material->thumbnail_path) }}" alt="{{ $material->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <i class="fa-solid {{ $material->type === 'video' ? 'fa-circle-play text-primary' : 'fa-file-lines text-warning' }} fs-1"></i>
                        @endif
                        <span class="badge position-absolute top-0 end-0 m-3 {{ $material->type === 'video' ? 'bg-primary' : 'bg-warning text-dark' }} rounded-pill px-3">
                            {{ $material->type === 'video' ? 'Video' : 'Dokumen' }}
                        </span>
                        <span class="badge position-absolute bottom-0 start-0 m-3 bg-dark-subtle text-dark border border-secondary-subtle rounded-pill">
                            Level: {{ $material->level ?: 'Umum' }}
                        </span>
                    </div>

                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">{{ $material->category }}</span>
                            <span class="small text-muted">{{ $material->version ?: 'v1.0' }}</span>
                        </div>

                        <h5 class="fw-bold mb-2 text-dark">{{ $material->title }}</h5>
                        <p class="text-muted small flex-grow-1" style="line-height: 1.4;">
                            {{ Str::limit($material->description, 100) ?: 'Tidak ada deskripsi tambahan.' }}
                        </p>

                        <div class="pt-3 border-top d-flex gap-2">
                            @if($material->type === 'video')
                                <a href="{{ route('admin.training.stream', $material->id) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill flex-fill">
                                    <i class="fa-solid fa-play me-1"></i> Tonton Video
                                </a>
                            @endif

                            @if($material->file_path)
                                <a href="{{ route('admin.training.download', $material->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill flex-fill">
                                    <i class="fa-solid fa-download me-1"></i> Unduh File
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-graduation-cap fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted">Belum ada materi training untuk Admin.</h5>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $materials->links() }}
    </div>
</div>
@endsection

