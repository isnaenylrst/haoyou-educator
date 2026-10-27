@extends('layouts.kurikulum')

@section('title', 'Tugas Kelas')

@section('content')

@php
    $editId   = (string) old('_edit_id');
    $editKind = (string) old('_edit_kind');
    $addErr   = fn (string $f) => $editId === '' ? $errors->first($f) : '';

    $tabs = [
        ''        => ['Semua', $counts['all']],
        'Open'    => ['Belum Selesai', $counts['Open']],
        'Overdue' => ['Terlambat', $counts['Overdue']],
        'Done'    => ['Selesai', $counts['Done']],
    ];
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Tugas Perbaikan Kelas</h3>
        <small class="text-muted">
            Buat tugas untuk kelas yang perlu diperbaiki. Tugas langsung masuk ke notifikasi guru pengampu.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Belum bisa disimpan:</strong>
            <ul class="mb-0">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM BUAT TUGAS --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Buat Tugas Baru</div>
        <div class="card-body">
            <form action="{{ route('kurikulum.tugas-kelas.store') }}" method="POST"
                  onsubmit="return lockSubmit(this, 'Mengirim...')">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Kelas</label>
                        <select name="class_id" class="form-select {{ $addErr('class_id') ? 'is-invalid' : '' }}" required>
                            <option value="">— pilih kelas —</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" @selected($editId === '' && old('class_id') == $c->id)>
                                    {{ $c->class_name }} — {{ $c->teacher->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Tugas dikirim ke guru pengampu kelas yang dipilih.</small>
                        @if ($addErr('class_id'))
                            <div class="invalid-feedback d-block">{{ $addErr('class_id') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Batas Waktu (opsional)</label>
                        <input type="date" name="due_date"
                               class="form-control {{ $addErr('due_date') ? 'is-invalid' : '' }}"
                               min="{{ today()->toDateString() }}"
                               value="{{ $editId === '' ? old('due_date') : '' }}">
                        @if ($addErr('due_date'))
                            <div class="invalid-feedback">{{ $addErr('due_date') }}</div>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Tugas</label>
                    <input type="text" name="title" maxlength="255"
                           class="form-control {{ $addErr('title') ? 'is-invalid' : '' }}"
                           placeholder="mis. Perbaiki PPT pertemuan 5"
                           value="{{ $editId === '' ? old('title') : '' }}" required>
                    @if ($addErr('title'))
                        <div class="invalid-feedback">{{ $addErr('title') }}</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Yang Perlu Diperbaiki</label>
                    <textarea name="description" rows="4" maxlength="2000" class="form-control"
                              placeholder="Jelaskan bagian kelas yang perlu diperbaiki">{{ $editId === '' ? old('description') : '' }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Kirim ke Guru</button>
            </form>
        </div>
    </div>

    {{-- DAFTAR TUGAS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="fw-bold">Daftar Tugas</span>
                    @if ($counts['Open'] > 0)
                        <span class="badge bg-warning text-dark ms-2">{{ $counts['Open'] }} belum selesai</span>
                    @endif
                    @if ($counts['Overdue'] > 0)
                        <span class="badge bg-danger ms-1">{{ $counts['Overdue'] }} terlambat</span>
                    @endif
                </div>

                <div class="btn-group btn-group-sm">
                    @foreach ($tabs as $key => [$label, $total])
                        <a href="{{ route('kurikulum.tugas-kelas', array_filter(['status' => $key, 'q' => $q])) }}"
                           class="btn btn-outline-secondary {{ (string) $status === (string) $key ? 'active' : '' }}">
                            {{ $label }} <span class="badge bg-light text-dark">{{ $total }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <form method="GET" action="{{ route('kurikulum.tugas-kelas') }}" class="row g-2 mt-2">
                @if ($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div class="col-md-8">
                    <input type="text" name="q" value="{{ $q }}" maxlength="100" class="form-control form-control-sm"
                           placeholder="Cari judul tugas, nama kelas, atau nama guru">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-fill">Cari</button>
                    <a href="{{ route('kurikulum.tugas-kelas') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
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
                                    · Batas
                                    <span class="{{ $t->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                        {{ $t->due_date->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                                · dibuat {{ $t->created_at->translatedFormat('d M Y') }}
                                @if ($t->creator) oleh {{ $t->creator->username }} @endif
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

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if ($t->status === 'Done')
                                <span class="badge bg-success">
                                    Selesai {{ $t->done_at?->translatedFormat('d M Y H:i') }}
                                </span>
                                <button type="button" class="btn btn-outline-warning btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#reopenTask{{ $t->id }}">
                                    Buka Kembali
                                </button>
                            @elseif ($t->is_overdue)
                                <span class="badge bg-danger">Terlambat</span>
                            @else
                                <span class="badge bg-warning text-dark">Menunggu guru</span>
                            @endif

                            {{-- Ubah tersedia untuk SEMUA tugas (belum selesai maupun selesai) --}}
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#editTask{{ $t->id }}">
                                Ubah
                            </button>

                            <form action="{{ route('kurikulum.tugas-kelas.destroy', $t) }}" method="POST"
                                  onsubmit="return confirm('Hapus tugas ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </div>

                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">
                    @if ($status || $q !== '')
                        Tidak ada tugas yang cocok. <a href="{{ route('kurikulum.tugas-kelas') }}">Reset filter</a>
                    @else
                        Belum ada tugas.
                    @endif
                </div>
            @endforelse
        </div>

        @if ($tasks->hasPages())
            <div class="card-footer bg-white">{{ $tasks->links() }}</div>
        @endif
    </div>

    {{-- MODAL UBAH TUGAS (semua status) --}}
    @foreach ($tasks as $t)
        @php
            $useOld         = $editKind === 'edit' && $editId === (string) $t->id;
            $isOpen         = $t->status === 'Open';
            $currentClassId = $useOld ? old('class_id', $t->class_id) : $t->class_id;
        @endphp

        <div class="modal fade" id="editTask{{ $t->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('kurikulum.tugas-kelas.update', $t) }}" method="POST" class="modal-content"
                      onsubmit="return lockSubmit(this, 'Menyimpan...')">
                    @csrf @method('PUT')
                    <input type="hidden" name="_edit_id" value="{{ $t->id }}">
                    <input type="hidden" name="_edit_kind" value="edit">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Ubah Tugas</h5>
                            <small class="text-muted">
                                {{ $isOpen ? 'Belum selesai' : 'Sudah selesai' }}
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        @if ($isOpen)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Kelas</label>
                                <select name="class_id" required
                                        class="form-select {{ $useOld && $errors->has('class_id') ? 'is-invalid' : '' }}">
                                    @foreach ($classes as $c)
                                        <option value="{{ $c->id }}" @selected((string) $currentClassId === (string) $c->id)>
                                            {{ $c->class_name }} — {{ $c->teacher->name ?? '-' }}
                                        </option>
                                    @endforeach

                                    {{-- kelas saat ini tetap tampil walau sudah tidak berstatus Open/Running --}}
                                    @unless ($classes->contains('id', $t->class_id))
                                        <option value="{{ $t->class_id }}" @selected((string) $currentClassId === (string) $t->class_id)>
                                            {{ $t->class->class_name ?? 'Kelas' }} — {{ $t->class->teacher->name ?? '-' }}
                                        </option>
                                    @endunless
                                </select>
                                <small class="text-muted">
                                    Mengganti kelas memindahkan tugas ke guru pengampu kelas yang baru.
                                </small>
                                @if ($useOld && $errors->has('class_id'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('class_id') }}</div>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-light border small">
                                <strong>Kelas:</strong> {{ $t->class->class_name ?? '-' }} — {{ $t->teacher->name ?? '-' }}<br>
                                Tugas ini sudah selesai, jadi kelas tidak bisa diganti dan status tetap Selesai.
                                Untuk meminta perbaikan ulang, gunakan <strong>Buka Kembali</strong>.
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul Tugas</label>
                            <input type="text" name="title" maxlength="255" required
                                   class="form-control {{ $useOld && $errors->has('title') ? 'is-invalid' : '' }}"
                                   value="{{ $useOld ? old('title') : $t->title }}">
                            @if ($useOld && $errors->has('title'))
                                <div class="invalid-feedback">{{ $errors->first('title') }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Batas Waktu (opsional)</label>
                            <input type="date" name="due_date"
                                   class="form-control {{ $useOld && $errors->has('due_date') ? 'is-invalid' : '' }}"
                                   value="{{ $useOld ? old('due_date') : $t->due_date?->toDateString() }}">
                            @if ($useOld && $errors->has('due_date'))
                                <div class="invalid-feedback">{{ $errors->first('due_date') }}</div>
                            @endif
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-semibold">Yang Perlu Diperbaiki</label>
                            <textarea name="description" rows="5" maxlength="2000"
                                      class="form-control">{{ $useOld ? old('description') : $t->description }}</textarea>
                            <small class="text-muted">
                                Perubahan langsung terlihat oleh guru di halaman Notifikasi.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- MODAL BUKA KEMBALI (tugas selesai) --}}
    @foreach ($tasks as $t)
        @if ($t->status === 'Done')
            @php $useOld = $editKind === 'reopen' && $editId === (string) $t->id; @endphp

            <div class="modal fade" id="reopenTask{{ $t->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('kurikulum.tugas-kelas.reopen', $t) }}" method="POST" class="modal-content"
                          onsubmit="return lockSubmit(this, 'Mengirim...')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="_edit_id" value="{{ $t->id }}">
                        <input type="hidden" name="_edit_kind" value="reopen">

                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">Buka Kembali Tugas</h5>
                                <small class="text-muted">{{ $t->title }}</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            @if ($t->teacher_note)
                                <div class="alert alert-light border small">
                                    <strong>Catatan guru:</strong> {{ $t->teacher_note }}
                                </div>
                            @endif

                            <label class="form-label fw-semibold">Alasan membuka kembali</label>
                            <textarea name="reason" rows="3" maxlength="500" required
                                      class="form-control {{ $useOld && $errors->has('reason') ? 'is-invalid' : '' }}"
                                      placeholder="mis. Bagian slide 5 masih salah, mohon dicek ulang">{{ $useOld ? old('reason') : '' }}</textarea>
                            @if ($useOld && $errors->has('reason'))
                                <div class="invalid-feedback">{{ $errors->first('reason') }}</div>
                            @endif
                            <small class="text-muted">Alasan ini ditambahkan ke tugas dan terbaca guru di Notifikasi.</small>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Buka Kembali & Kirim ke Guru</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

</div>

<script>
    function lockSubmit(form, text) {
        const btn = form.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.innerText = text; }
        return true;
    }

    @if ($editId !== '')
        // Jika simpan gagal validasi, buka kembali modal yang sama
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('{{ $editKind === 'reopen' ? 'reopenTask' : 'editTask' }}{{ (int) $editId }}');
            if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
        });
    @endif
</script>

@endsection