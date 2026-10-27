@extends('layouts.kurikulum')

@section('title', 'Tugas Kelas')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Tugas Perbaikan Kelas</h3>
        <small class="text-muted">
            Buat tugas untuk kelas yang perlu diperbaiki. Tugas langsung masuk ke notifikasi guru pengampu.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Tugas belum bisa dikirim:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Buat Tugas Baru</div>
        <div class="card-body">
            <form action="{{ route('kurikulum.tugas-kelas.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Kelas</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">— pilih kelas —</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>
                                    {{ $c->class_name }} — {{ $c->teacher->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Tugas dikirim ke guru pengampu kelas yang dipilih.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Batas Waktu (opsional)</label>
                        <input type="date" name="due_date" class="form-control"
                               min="{{ now()->toDateString() }}" value="{{ old('due_date') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Tugas</label>
                    <input type="text" name="title" maxlength="255" class="form-control"
                           placeholder="mis. Perbaiki PPT pertemuan 5" value="{{ old('title') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Yang Perlu Diperbaiki</label>
                    <textarea name="description" rows="4" class="form-control"
                              placeholder="Jelaskan bagian kelas yang perlu diperbaiki">{{ old('description') }}</textarea>
                </div>

                <button class="btn btn-primary">Kirim ke Guru</button>
            </form>
        </div>
    </div>

    {{-- DAFTAR --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold">Daftar Tugas</span>
                <span class="badge bg-warning text-dark ms-2">{{ $totalOpen }} belum selesai</span>
            </div>

            <div class="btn-group btn-group-sm">
                <a href="{{ route('kurikulum.tugas-kelas') }}"
                   class="btn btn-outline-secondary {{ !$status ? 'active' : '' }}">Semua</a>
                <a href="{{ route('kurikulum.tugas-kelas', ['status' => 'Open']) }}"
                   class="btn btn-outline-secondary {{ $status === 'Open' ? 'active' : '' }}">Belum Selesai</a>
                <a href="{{ route('kurikulum.tugas-kelas', ['status' => 'Done']) }}"
                   class="btn btn-outline-secondary {{ $status === 'Done' ? 'active' : '' }}">Selesai</a>
            </div>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($tasks as $t)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $t->title }}</div>
                            <small class="text-muted">
                                {{ $t->class->class_name ?? '-' }} · {{ $t->teacher->name ?? '-' }}
                                @if ($t->due_date)
                                    · Batas {{ $t->due_date->translatedFormat('d M Y') }}
                                @endif
                            </small>

                            @if ($t->description)
                                <div class="small mt-2">{!! nl2br(e($t->description)) !!}</div>
                            @endif

                            @if ($t->status === 'Done' && $t->teacher_note)
                                <div class="small mt-2 text-success">
                                    Catatan guru: {{ $t->teacher_note }}
                                </div>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if ($t->status === 'Done')
                                <span class="badge bg-success">
                                    Selesai {{ $t->done_at?->translatedFormat('d M') }}
                                </span>
                            @elseif ($t->is_overdue)
                                <span class="badge bg-danger">Terlambat</span>
                            @else
                                <span class="badge bg-warning text-dark">Menunggu guru</span>
                            @endif

                            <form action="{{ route('kurikulum.tugas-kelas.destroy', $t) }}" method="POST"
                                  onsubmit="return confirm('Hapus tugas ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">Belum ada tugas.</div>
            @endforelse
        </div>
    </div>

</div>

@endsection