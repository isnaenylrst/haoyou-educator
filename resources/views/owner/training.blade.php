@extends('layouts.owner')

@section('title', 'Materi Training & Pengembangan Kompetensi')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-graduation-cap text-warning me-2"></i> Materi Training Pengajar & Staf</h2>
            <p class="text-muted mb-0">
                Kelola modul pelatihan, SOP, video tutorial, dan materi pengembangan kompetensi untuk peran Admin, Kurikulum, dan Guru.
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
            <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Terjadi kesalahan validasi pada formulir:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK SINGKAT DARI DATA RIIL --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-layer-group fa-2x"></i>
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
                        <div class="text-muted small">Dokumen / SOP</div>
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

    {{-- FILTER TABS PERAN & TIPE --}}
    <div class="card shadow-sm border-0 mb-3 rounded-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                {{-- Filter Peran --}}
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    <span class="text-muted small me-2 fw-semibold"><i class="fa-solid fa-users me-1"></i> Peran:</span>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('role', 'page'), ['role' => 'All'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ ($roleTab === 'All' || !$roleTab) ? 'btn-dark' : 'btn-outline-secondary' }}">
                        Semua Peran ({{ $stats['total'] }})
                    </a>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('role', 'page'), ['role' => 'Admin'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ $roleTab === 'Admin' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Admin ({{ $stats['admin'] }})
                    </a>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('role', 'page'), ['role' => 'Curriculum'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ $roleTab === 'Curriculum' ? 'btn-purple text-white' : 'btn-outline-purple' }}">
                        Kurikulum ({{ $stats['curriculum'] }})
                    </a>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('role', 'page'), ['role' => 'Teacher'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ $roleTab === 'Teacher' ? 'btn-success' : 'btn-outline-success' }}">
                        Guru ({{ $stats['teacher'] }})
                    </a>
                </div>

                {{-- Filter Tipe --}}
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    <span class="text-muted small me-2 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Tipe:</span>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('type', 'page'), ['type' => 'all'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ ($typeTab === 'all' || !$typeTab) ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        Semua
                    </a>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('type', 'page'), ['type' => 'video'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ $typeTab === 'video' ? 'btn-danger' : 'btn-outline-danger' }}">
                        <i class="fa-solid fa-video me-1"></i> Video
                    </a>
                    <a href="{{ route('owner.training.index', array_merge(request()->except('type', 'page'), ['type' => 'document'])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ $typeTab === 'document' ? 'btn-success' : 'btn-outline-success' }}">
                        <i class="fa-solid fa-file-pdf me-1"></i> Dokumen/SOP
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER PENCARIAN, LEVEL & KATEGORI --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('owner.training.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="role" value="{{ $roleTab }}">
                <input type="hidden" name="type" value="{{ $typeTab }}">

                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0" placeholder="Cari judul materi training atau deskripsi...">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="level" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Level</option>
                        @foreach (['Umum', 'Dasar', 'Menengah', 'Lanjutan'] as $lvl)
                            <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>Level {{ $lvl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 fw-semibold rounded-3">Cari</button>
                    @if(request('q') || request('category') || request('level') || request('role') !== 'All' || request('type') !== 'all')
                        <a href="{{ route('owner.training.index') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @php
        $videoMaterials = $materials->filter(fn($m) => $m->type === 'video');
        $docMaterials   = $materials->filter(fn($m) => $m->type === 'document');
    @endphp

    {{-- KONTEN MATERI --}}
    @if ($materials->isEmpty())
        <div class="card shadow-sm border-0 rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-graduation-cap fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Tidak Ada Materi Ditemukan</h5>
                <p class="text-muted">Tidak ada materi training yang cocok dengan filter atau pencarian saat ini.</p>
                <a href="{{ route('owner.training.index') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-rotate-left me-1"></i> Tampilkan Semua
                </a>
            </div>
        </div>
    @else

        {{-- BAGIAN 1: GALERI VIDEO --}}
        @if ($typeTab !== 'document' && $videoMaterials->isNotEmpty())
            <div class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-circle-play text-danger me-2"></i> Galeri Video Pembelajaran & Tutorial
                    </h4>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 fw-semibold">
                        {{ $videoMaterials->count() }} Video
                    </span>
                </div>

                <div class="row g-3">
                    @foreach ($videoMaterials as $m)
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-sm border-0 rounded-4 h-100 d-flex flex-column overflow-hidden">
                                {{-- Thumbnail Video --}}
                                <div class="position-relative bg-dark d-flex align-items-center justify-content-center" style="height: 180px;">
                                    @if ($m->thumbnail_path)
                                        <img src="{{ Storage::disk('public')->url($m->thumbnail_path) }}" alt="{{ $m->title }}" class="w-100 h-100 object-fit-cover opacity-75">
                                    @else
                                        <div class="text-white text-center opacity-75">
                                            <i class="fa-solid fa-video fa-3x mb-2 text-danger"></i>
                                            <div class="small fw-semibold">Video Tutorial</div>
                                        </div>
                                    @endif

                                    <button type="button" class="btn btn-danger btn-lg rounded-circle position-absolute top-50 start-50 translate-middle shadow"
                                            title="Putar Video"
                                            onclick="openVideoModal('{{ addslashes($m->title) }}', '{{ $m->youtube_embed_url }}', '{{ $m->video_path ? route('owner.training.stream', $m->id) : ($m->video_url ?: '') }}')">
                                        <i class="fa-solid fa-play"></i>
                                    </button>

                                    <span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 start-0 m-2 rounded-pill small">
                                        {{ $m->version }}
                                    </span>
                                </div>

                                <div class="card-body d-flex flex-column p-4">
                                    {{-- Badges Peran, Level, Kategori --}}
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        @foreach ($m->target_roles_list as $roleItem)
                                            <span class="badge {{ $roleItem === 'Admin' ? 'bg-primary' : ($roleItem === 'Curriculum' ? 'bg-purple' : 'bg-success') }} text-white rounded-pill" style="font-size: 11px;">
                                                {{ $roleItem }}
                                            </span>
                                        @endforeach

                                        <span class="badge {{ $m->level_badge }} rounded-pill" style="font-size: 11px;">
                                            {{ $m->level }}
                                        </span>

                                        <span class="badge bg-light text-secondary border rounded-pill" style="font-size: 11px;">
                                            {{ $m->category }}
                                        </span>
                                    </div>

                                    <h5 class="fw-bold text-dark mb-2">{{ $m->title }}</h5>

                                    <p class="text-muted small flex-grow-1 mb-3" style="min-height: 44px; line-height: 1.5;">
                                        {{ Str::limit($m->description ?: 'Tidak ada deskripsi tambahan.', 110) }}
                                    </p>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="btn btn-sm btn-danger rounded-pill fw-semibold px-3"
                                                onclick="openVideoModal('{{ addslashes($m->title) }}', '{{ $m->youtube_embed_url }}', '{{ $m->video_path ? route('owner.training.stream', $m->id) : ($m->video_url ?: '') }}')">
                                            <i class="fa-solid fa-circle-play me-1"></i> Putar Video
                                        </button>

                                        @if ($m->file_path)
                                            <a href="{{ route('owner.training.download', $m->id) }}" class="btn btn-sm btn-outline-success rounded-pill fw-semibold">
                                                <i class="fa-solid fa-download me-1"></i> Unduh Lampiran
                                            </a>
                                        @endif
                                    </div>

                                    <hr class="my-2 border-secondary border-opacity-10">

                                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                                        <small class="text-muted">
                                            <i class="fa-solid fa-calendar me-1"></i> {{ $m->created_at->format('d M Y') }}
                                        </small>
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
                    @endforeach
                </div>
            </div>
        @endif

        {{-- BAGIAN 2: DAFTAR DOKUMEN / SOP --}}
        @if ($typeTab !== 'video' && $docMaterials->isNotEmpty())
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-file-lines text-success me-2"></i> Dokumen, Modul & SOP
                    </h4>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-semibold">
                        {{ $docMaterials->count() }} Dokumen
                    </span>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 35%;">Materi & Deskripsi</th>
                                    <th>Target Peran</th>
                                    <th>Level</th>
                                    <th>Kategori</th>
                                    <th>Versi</th>
                                    <th>Aksi Berkas</th>
                                    <th class="text-end pe-4">Kelola</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($docMaterials as $m)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
                                                    <i class="fa-solid fa-file-lines fa-xl"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $m->title }}</div>
                                                    <small class="text-muted d-block">{{ Str::limit($m->description ?: 'Tanpa deskripsi.', 75) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($m->target_roles_list as $roleItem)
                                                    <span class="badge {{ $roleItem === 'Admin' ? 'bg-primary' : ($roleItem === 'Curriculum' ? 'bg-purple' : 'bg-success') }} text-white rounded-pill" style="font-size: 11px;">
                                                        {{ $roleItem }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $m->level_badge }} rounded-pill">{{ $m->level }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border rounded-pill">{{ $m->category }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark border rounded-pill">{{ $m->version }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @if ($m->file_path)
                                                    @if ($m->file_extension === 'pdf')
                                                        <a href="{{ route('owner.training.preview', $m->id) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
                                                            <i class="fa-solid fa-eye me-1"></i> Lihat
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('owner.training.download', $m->id) }}" class="btn btn-sm btn-success rounded-pill fw-semibold">
                                                        <i class="fa-solid fa-download me-1"></i> Unduh
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Tidak ada berkas</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-1">
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
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- PAGINATION --}}
        <div class="mt-4">
            {{ $materials->links() }}
        </div>
    @endif

    {{-- MODAL EDIT UNTUK SEMUA ITEM --}}
    @foreach ($materials as $m)
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
                            {{-- Judul --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Judul Materi <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $m->title) }}" required>
                            </div>

                            {{-- Target Roles (Checkbox pilihan Admin, Kurikulum, Guru) --}}
                            @php
                                $currentRoles = old('target_roles', $m->target_roles_list);
                            @endphp
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Dikirim ke (Peran Tujuan) <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-4 pt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="Admin" id="edit_role_admin_{{ $m->id }}"
                                               {{ in_array('Admin', $currentRoles, true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="edit_role_admin_{{ $m->id }}">
                                            <span class="badge bg-primary text-white me-1">Admin</span> Tim Administrasi
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="Curriculum" id="edit_role_curriculum_{{ $m->id }}"
                                               {{ in_array('Curriculum', $currentRoles, true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="edit_role_curriculum_{{ $m->id }}">
                                            <span class="badge bg-purple text-white me-1">Kurikulum</span> Tim Kurikulum
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="Teacher" id="edit_role_teacher_{{ $m->id }}"
                                               {{ in_array('Teacher', $currentRoles, true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="edit_role_teacher_{{ $m->id }}">
                                            <span class="badge bg-success text-white me-1">Guru</span> Guru / Pengajar
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">Boleh pilih satu atau lebih peran. Materi hanya akan terlihat oleh peran yang dicentang.</small>
                            </div>

                            {{-- Kategori & Level --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Kategori Materi <span class="text-danger">*</span></label>
                                    <input type="text" name="category" list="category_list_edit" class="form-control" value="{{ old('category', $m->category) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Level Materi <span class="text-danger">*</span></label>
                                    <select name="level" class="form-select" required>
                                        @foreach (['Umum', 'Dasar', 'Menengah', 'Lanjutan'] as $lvl)
                                            <option value="{{ $lvl }}" {{ old('level', $m->level) === $lvl ? 'selected' : '' }}>Level {{ $lvl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Tipe Materi (Radio Dokumen / Video) --}}
                            @php
                                $editType = old('type', $m->type);
                            @endphp
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Tipe Materi <span class="text-danger">*</span></label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="edit_type_doc_{{ $m->id }}" value="document"
                                               {{ $editType === 'document' ? 'checked' : '' }}
                                               onchange="toggleEditTypeFields({{ $m->id }}, 'document')">
                                        <label class="form-check-label fw-semibold" for="edit_type_doc_{{ $m->id }}">
                                            <i class="fa-solid fa-file-pdf text-success me-1"></i> Dokumen / SOP
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="edit_type_video_{{ $m->id }}" value="video"
                                               {{ $editType === 'video' ? 'checked' : '' }}
                                               onchange="toggleEditTypeFields({{ $m->id }}, 'video')">
                                        <label class="form-check-label fw-semibold" for="edit_type_video_{{ $m->id }}">
                                            <i class="fa-solid fa-video text-danger me-1"></i> Video
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Field Dokumen --}}
                            <div id="edit_doc_fields_{{ $m->id }}" class="{{ $editType === 'document' ? '' : 'd-none' }}">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold">Ganti File Dokumen (PDF, PPT, DOC, XLS, ZIP)</label>
                                        <input type="file" name="file" class="form-control">
                                        @if($m->file_path)
                                            <small class="text-muted d-block mt-1">File tersimpan: <strong>{{ $m->file_name }}</strong></small>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Versi Dokumen</label>
                                        <input type="text" name="version" class="form-control" value="{{ old('version', $m->version) }}" placeholder="v1.0">
                                    </div>
                                </div>
                            </div>

                            {{-- Field Video --}}
                            <div id="edit_video_fields_{{ $m->id }}" class="{{ $editType === 'video' ? '' : 'd-none' }}">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ganti File Video Lokal (MP4, WEBM, MOV)</label>
                                        <input type="file" name="video_file" class="form-control" accept="video/*">
                                        @if($m->video_path)
                                            <small class="text-muted d-block mt-1">Video tersimpan di server.</small>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Atau Link Video (YouTube / Google Drive)</label>
                                        <input type="url" name="video_url" class="form-control" value="{{ old('video_url', $m->video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Thumbnail Video (Opsional, JPG/PNG)</label>
                                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                                    @if($m->thumbnail_path)
                                        <small class="text-muted d-block mt-1">Thumbnail kustom terpasang.</small>
                                    @endif
                                </div>
                            </div>

                            {{-- Deskripsi --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi / Catatan Materi</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $m->description) }}</textarea>
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

{{-- MODAL TAMBAH MATERI (UPLOAD BARU) --}}
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
                    {{-- Judul --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Materi <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" placeholder="Contoh: SOP Pengajaran Kelas Reguler & Penanganan Siswa Pasif" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Target Roles (Checkbox pilihan Admin, Kurikulum, Guru) --}}
                    @php
                        $oldRoles = old('target_roles', []);
                    @endphp
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Dikirim ke (Peran Tujuan) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-4 pt-1">
                            <div class="form-check">
                                <input class="form-check-input @error('target_roles') is-invalid @enderror" type="checkbox" name="target_roles[]" value="Admin" id="upload_role_admin"
                                       {{ in_array('Admin', $oldRoles, true) ? 'checked' : '' }} onchange="updateInfoRecipientText()">
                                <label class="form-check-label fw-semibold" for="upload_role_admin">
                                    <span class="badge bg-primary text-white me-1">Admin</span> Tim Administrasi
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('target_roles') is-invalid @enderror" type="checkbox" name="target_roles[]" value="Curriculum" id="upload_role_curriculum"
                                       {{ in_array('Curriculum', $oldRoles, true) ? 'checked' : '' }} onchange="updateInfoRecipientText()">
                                <label class="form-check-label fw-semibold" for="upload_role_curriculum">
                                    <span class="badge bg-purple text-white me-1">Kurikulum</span> Tim Kurikulum
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('target_roles') is-invalid @enderror" type="checkbox" name="target_roles[]" value="Teacher" id="upload_role_teacher"
                                       {{ in_array('Teacher', $oldRoles, true) ? 'checked' : '' }} onchange="updateInfoRecipientText()">
                                <label class="form-check-label fw-semibold" for="upload_role_teacher">
                                    <span class="badge bg-success text-white me-1">Guru</span> Guru / Pengajar
                                </label>
                            </div>
                        </div>
                        @error('target_roles')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">Boleh pilih satu atau lebih peran. Centang ketiganya jika materi ditujukan untuk semua peran.</small>
                    </div>

                    {{-- Kategori & Level --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori Materi <span class="text-danger">*</span></label>
                            <input type="text" name="category" list="category_list_upload" class="form-control @error('category') is-invalid @enderror"
                                   value="{{ old('category') }}" placeholder="Pilih atau ketik kategori..." required>
                            <datalist id="category_list_upload">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Level Materi <span class="text-danger">*</span></label>
                            <select name="level" class="form-select @error('level') is-invalid @enderror" required>
                                @foreach (['Umum', 'Dasar', 'Menengah', 'Lanjutan'] as $lvl)
                                    <option value="{{ $lvl }}" {{ old('level', 'Umum') === $lvl ? 'selected' : '' }}>Level {{ $lvl }}</option>
                                @endforeach
                            </select>
                            @error('level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Tipe Materi (Radio Dokumen / Video) --}}
                    @php
                        $createType = old('type', 'document');
                    @endphp
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Tipe Materi <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="upload_type_doc" value="document"
                                       {{ $createType === 'document' ? 'checked' : '' }}
                                       onchange="toggleUploadTypeFields('document')">
                                <label class="form-check-label fw-semibold" for="upload_type_doc">
                                    <i class="fa-solid fa-file-pdf text-success me-1"></i> Dokumen / SOP
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="upload_type_video" value="video"
                                       {{ $createType === 'video' ? 'checked' : '' }}
                                       onchange="toggleUploadTypeFields('video')">
                                <label class="form-check-label fw-semibold" for="upload_type_video">
                                    <i class="fa-solid fa-video text-danger me-1"></i> Video
                                </label>
                            </div>
                        </div>
                        @error('type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Bagian File Dokumen --}}
                    <div id="upload_doc_fields" class="{{ $createType === 'document' ? '' : 'd-none' }}">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Upload File Dokumen <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror">
                                <small class="text-muted">Format: PDF, PPTX, DOCX, XLSX, ZIP. Maks. 50 MB.</small>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Versi Dokumen</label>
                                <input type="text" name="version" class="form-control" value="{{ old('version', 'v1.0') }}" placeholder="v1.0">
                            </div>
                        </div>
                    </div>

                    {{-- Bagian Video --}}
                    <div id="upload_video_fields" class="{{ $createType === 'video' ? '' : 'd-none' }}">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Upload File Video</label>
                                <input type="file" name="video_file" class="form-control @error('video_file') is-invalid @enderror" accept="video/*">
                                <small class="text-muted">Format: MP4, WEBM, MOV. Maks. 100 MB.</small>
                                @error('video_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Atau Link Video</label>
                                <input type="url" name="video_url" class="form-control @error('video_url') is-invalid @enderror"
                                       value="{{ old('video_url') }}" placeholder="https://www.youtube.com/watch?v=...">
                                <small class="text-muted">Link YouTube atau Google Drive.</small>
                                @error('video_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Thumbnail Video (Opsional)</label>
                            <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, WEBP. Maks. 5 MB.</small>
                            @error('thumbnail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi / Ringkasan Materi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tuliskan tujuan materi pelatihan, pokok bahasan, atau instruksi kerja...">{{ old('description') }}</textarea>
                    </div>

                    {{-- Info Notifikasi Sesuai Pilihan --}}
                    <div class="alert alert-info py-2 px-3 rounded-3 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <span id="uploadNotificationText">
                            Setelah diunggah, notifikasi otomatis hanya akan dikirimkan kepada peran yang Anda pilih di atas.
                        </span>
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

<style>
.bg-purple {
    background-color: #6f42c1 !important;
}
.btn-purple {
    background-color: #6f42c1;
    border-color: #6f42c1;
}
.btn-purple:hover {
    background-color: #59339d;
    border-color: #59339d;
}
.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}
.btn-outline-purple:hover {
    background-color: #6f42c1;
    color: #fff;
}
</style>

<script>
    function toggleUploadTypeFields(type) {
        const docFields = document.getElementById('upload_doc_fields');
        const videoFields = document.getElementById('upload_video_fields');
        if (type === 'video') {
            docFields.classList.add('d-none');
            videoFields.classList.remove('d-none');
        } else {
            docFields.classList.remove('d-none');
            videoFields.classList.add('d-none');
        }
    }

    function toggleEditTypeFields(id, type) {
        const docFields = document.getElementById('edit_doc_fields_' + id);
        const videoFields = document.getElementById('edit_video_fields_' + id);
        if (type === 'video') {
            docFields.classList.add('d-none');
            videoFields.classList.remove('d-none');
        } else {
            docFields.classList.remove('d-none');
            videoFields.classList.add('d-none');
        }
    }

    function updateInfoRecipientText() {
        const adminChecked = document.getElementById('upload_role_admin').checked;
        const currChecked = document.getElementById('upload_role_curriculum').checked;
        const teacherChecked = document.getElementById('upload_role_teacher').checked;

        const selected = [];
        if (adminChecked) selected.push('Admin');
        if (currChecked) selected.push('Kurikulum');
        if (teacherChecked) selected.push('Guru');

        const el = document.getElementById('uploadNotificationText');
        if (selected.length === 0) {
            el.innerText = 'Pilih minimal satu peran. Notifikasi hanya akan dikirimkan kepada peran yang Anda centang.';
        } else if (selected.length === 3) {
            el.innerText = 'Notifikasi otomatis akan dikirimkan ke seluruh pengguna peran Admin, Kurikulum, dan Guru.';
        } else {
            el.innerText = 'Notifikasi otomatis hanya akan dikirimkan kepada pengguna peran: ' + selected.join(', ') + '.';
        }
    }

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

    // Otomatis buka kembali modal upload jika ada validation error
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', function () {
            const tambahModal = new bootstrap.Modal(document.getElementById('modalTambahMateri'));
            tambahModal.show();
            updateInfoRecipientText();
        });
    @endif
</script>
@endsection
