@extends('layouts.kurikulum')

@section('title', 'Kompetensi Report')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Kompetensi Progress Report</h3>
        <small class="text-muted">
            Tetapkan kompetensi yang dinilai. Guru hanya mengisi Achievement Level dan nilai latihan.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- TAMBAH KOMPETENSI --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Tambah Kompetensi</div>
        <div class="card-body">
            <form action="{{ route('kurikulum.kompetensi.store') }}" method="POST" class="row g-2 align-items-end">
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
                    <input type="number" name="sort_order" min="0" max="999" class="form-control"
                           value="{{ old('sort_order') }}">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR KOMPETENSI --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold">Daftar Kompetensi</span>

            <form method="GET" class="d-flex gap-2">
                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua kompetensi</option>
                    <option value="all" @selected($levelFilter === 'all')>Berlaku semua level</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l->id }}" @selected((string) $levelFilter === (string) $l->id)>
                            {{ $l->level_name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($competencies as $c)
                <div class="list-group-item">
                    <form id="upd{{ $c->id }}" action="{{ route('kurikulum.kompetensi.update', $c) }}" method="POST">
                        @csrf @method('PUT')
                    </form>
                    <form id="del{{ $c->id }}" action="{{ route('kurikulum.kompetensi.destroy', $c) }}" method="POST"
                          onsubmit="return confirm('Hapus kompetensi ini?')">
                        @csrf @method('DELETE')
                    </form>

                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <select form="upd{{ $c->id }}" name="program_level_id" class="form-select form-select-sm">
                                <option value="">Semua level</option>
                                @foreach ($levels as $l)
                                    <option value="{{ $l->id }}" @selected($c->program_level_id == $l->id)>
                                        {{ $l->level_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-5">
                            <input form="upd{{ $c->id }}" type="text" name="name" maxlength="255"
                                   value="{{ $c->name }}" class="form-control form-control-sm" required>
                        </div>

                        <div class="col-md-1">
                            <input form="upd{{ $c->id }}" type="number" name="sort_order" min="0" max="999"
                                   value="{{ $c->sort_order }}" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-1">
                            <div class="form-check">
                                <input form="upd{{ $c->id }}" class="form-check-input" type="checkbox"
                                       name="is_active" value="1" id="act{{ $c->id }}" @checked($c->is_active)>
                                <label class="form-check-label small" for="act{{ $c->id }}">Aktif</label>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex gap-1 justify-content-end">
                            <button form="upd{{ $c->id }}" class="btn btn-outline-primary btn-sm">Simpan</button>
                            <button form="del{{ $c->id }}" class="btn btn-outline-danger btn-sm">Hapus</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">
                    Belum ada kompetensi. Tambahkan di atas supaya guru bisa mengisi progress report.
                </div>
            @endforelse
        </div>
    </div>

    {{-- DESKRIPSI ACHIEVEMENT LEVEL --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Deskripsi Achievement Level (baku)</div>
        <div class="card-body">
            <p class="text-muted small">
                Teks ini otomatis muncul di progress report sesuai level yang dipilih guru.
                Guru tidak bisa mengubahnya.
            </p>

            <form action="{{ route('kurikulum.kompetensi.descriptions') }}" method="POST">
                @csrf @method('PUT')

                @foreach ($achievementLevels as $a)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $a->level }}</label>
                        <textarea name="descriptions[{{ $a->id }}]" rows="2" maxlength="1000"
                                  class="form-control" required>{{ $a->description }}</textarea>
                    </div>
                @endforeach

                <button class="btn btn-primary">Simpan Deskripsi</button>
            </form>
        </div>
    </div>

</div>

@endsection