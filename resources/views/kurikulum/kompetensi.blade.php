@extends('layouts.kurikulum')

@section('title', 'Kompetensi & Learning Materials Report')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-list-check text-warning me-2"></i> Kompetensi & Learning Materials Progress Report</h3>
        <small class="text-muted">
            Kurikulum menyiapkan template Learning Materials per kelas (contoh Kelas Feixiang 1A). Guru tinggal mengisi Achievement Level (Sufficient, Proficient, Excellent), deskripsi, dan bagian Assessment pada saat pengisian report.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ==================== 1. TEMPLATE PER KELAS: LEARNING MATERIALS ==================== --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-header bg-white py-3 fw-bold border-bottom d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-chalkboard-user text-warning me-2"></i> 1. Template Learning Materials per Kelas (Contoh: Feixiang 1A)</span>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('kurikulum.kompetensi.class-material.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Pilih Kelas</label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Pilih Kelas Aktif --</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected(old('class_id', $classFilter) == $c->id)>
                                {{ $c->class_name }} ({{ $c->level?->level_name ?? 'Umum' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nama Learning Material</label>
                    <input type="text" name="material_name" class="form-control" placeholder="Contoh: Unit 1: Perkenalan Diri & Sapaan Mandarin" required>
                </div>

                <div class="col-md-1">
                    <label class="form-label fw-semibold">Urutan</label>
                    <input type="number" name="sort_order" class="form-control" placeholder="1" min="1">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-warning rounded-pill w-100 fw-semibold text-dark">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Materi
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Nama Kelas</th>
                            <th>Daftar Learning Materials (Kurikulum)</th>
                            <th style="width: 100px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $cls)
                            @if($cls->learningMaterials->isNotEmpty())
                                <tr>
                                    <td class="align-top">
                                        <div class="fw-bold text-dark">{{ $cls->class_name }}</div>
                                        <small class="text-muted">Level {{ $cls->level?->level_name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <ol class="mb-0 ps-3">
                                            @foreach($cls->learningMaterials as $lm)
                                                <li class="mb-1">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>{{ $lm->material_name }}</span>
                                                        <form action="{{ route('kurikulum.kompetensi.class-material.destroy', $lm->id) }}" method="POST" onsubmit="return confirm('Hapus materi ini?')" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0 ms-2" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                                                        </form>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </td>
                                    <td class="text-center align-top">
                                        <span class="badge bg-success-subtle text-success border border-success">{{ $cls->learningMaterials->count() }} materi</span>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada template Learning Materials per kelas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== 2. KOMPETENSI PER LEVEL (GLOBAL FALLBACK) ==================== --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-header bg-white py-3 fw-bold border-bottom">
            <i class="fa-solid fa-layer-group text-warning me-2"></i> 2. Standar Kompetensi per Level (Fallback Umum)
        </div>
        <div class="card-body p-4">
            <form action="{{ route('kurikulum.kompetensi.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Level</label>
                    <select name="program_level_id" class="form-select">
                        <option value="">Semua level</option>
                        @foreach ($levels as $l)
                            <option value="{{ $l->id }}" @selected(old('program_level_id') == $l->id)>
                                {{ $l->level_name }}{{ $l->program ? ' — ' . $l->program->program_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nama Kompetensi</label>
                    <input type="text" name="name" maxlength="255" class="form-control"
                           placeholder="mis. Vocabulary & Pronunciation" value="{{ old('name') }}" required>
                </div>

                <div class="col-md-1">
                    <label class="form-label fw-semibold">Urutan</label>
                    <input type="number" name="sort_order" min="0" max="999" class="form-control" value="{{ old('sort_order') }}">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-dark rounded-pill w-100 fw-semibold">Tambah</button>
                </div>
            </form>

            <div class="list-group list-group-flush border rounded-3">
                @forelse ($competencies as $c)
                    <div class="list-group-item">
                        <form id="upd{{ $c->id }}" action="{{ route('kurikulum.kompetensi.update', $c) }}" method="POST">@csrf @method('PUT')</form>
                        <form id="del{{ $c->id }}" action="{{ route('kurikulum.kompetensi.destroy', $c) }}" method="POST" onsubmit="return confirm('Hapus kompetensi ini?')">@csrf @method('DELETE')</form>

                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <select form="upd{{ $c->id }}" name="program_level_id" class="form-select form-select-sm">
                                    <option value="">Semua level</option>
                                    @foreach ($levels as $l)
                                        <option value="{{ $l->id }}" @selected($c->program_level_id == $l->id)>{{ $l->level_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input form="upd{{ $c->id }}" type="text" name="name" maxlength="255" value="{{ $c->name }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-2">
                                <input form="upd{{ $c->id }}" type="number" name="sort_order" min="0" max="999" value="{{ $c->sort_order }}" class="form-control form-control-sm" placeholder="Urutan">
                            </div>
                            <div class="col-md-2 d-flex gap-1 justify-content-end">
                                <button form="upd{{ $c->id }}" class="btn btn-outline-primary btn-sm">Simpan</button>
                                <button form="del{{ $c->id }}" class="btn btn-outline-danger btn-sm">Hapus</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-3">Belum ada kompetensi global.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ==================== 3. DESKRIPSI ACHIEVEMENT LEVEL ==================== --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white py-3 fw-bold border-bottom">
            <i class="fa-solid fa-award text-warning me-2"></i> 3. Pilihan Achievement Level Baku (Sufficient, Proficient, Excellent)
        </div>
        <div class="card-body p-4">
            <form action="{{ route('kurikulum.kompetensi.descriptions') }}" method="POST">
                @csrf @method('PUT')

                @foreach ($achievementLevels as $a)
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">{{ $a->level }}</label>
                        <textarea name="descriptions[{{ $a->id }}]" rows="2" maxlength="1000" class="form-control" required>{{ $a->description }}</textarea>
                    </div>
                @endforeach

                <button class="btn btn-warning rounded-pill px-4 fw-semibold text-dark">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Deskripsi Level
                </button>
            </form>
        </div>
    </div>

</div>

@endsection
