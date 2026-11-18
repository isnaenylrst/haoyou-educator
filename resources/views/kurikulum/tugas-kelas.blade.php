@extends('layouts.kurikulum')

@section('title', 'Tugas Perbaikan Materi')

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
        <h3 class="fw-bold mb-1">Tugas Perbaikan Materi</h3>
        <small class="text-muted">
            Tugaskan perbaikan materi / modul mengajar kepada satu atau beberapa guru pengampu, serta kelola tugas perbaikan kelas.
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

    {{-- NAV TABS TIPE FORM --}}
    <ul class="nav nav-pills mb-3 gap-2" id="taskFormTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-pill fw-semibold" id="tab-material-task" data-bs-toggle="pill" data-bs-target="#pane-material-task" type="button">
                <i class="fa-solid fa-file-pen me-1"></i> Tugas Perbaikan Materi Guru
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill fw-semibold" id="tab-class-task" data-bs-toggle="pill" data-bs-target="#pane-class-task" type="button">
                <i class="fa-solid fa-chalkboard-user me-1"></i> Tugas Perbaikan Kelas
            </button>
        </li>
    </ul>

    <div class="tab-content mb-4">
        {{-- FORM 1: TUGAS PERBAIKAN MATERI (BARU) --}}
        <div class="tab-pane fade show active" id="pane-material-task">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">
                    <i class="fa-solid fa-plus-circle text-primary me-1"></i> Buat Tugas Perbaikan Materi Baru
                </div>
                <div class="card-body">
                    <form action="{{ route('kurikulum.tugas-kelas.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return lockSubmit(this, 'Mengirim Tugas...')">
                        @csrf
                        <input type="hidden" name="task_type" value="material_revision">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold mb-0">Guru Tujuan <span class="text-danger">*</span></label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="btnSelectAllTeachers" style="font-size: 11px;">
                                            <i class="fa-solid fa-check-double me-1"></i> Pilih Semua
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btnClearTeachers" style="font-size: 11px;">
                                            <i class="fa-solid fa-xmark me-1"></i> Hapus Pilihan
                                        </button>
                                    </div>
                                </div>

                                {{-- Input Pencarian Guru --}}
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                    <input type="text" id="teacherSearchInput" class="form-control" placeholder="Cari nama guru...">
                                </div>

                                {{-- Multi Select Guru --}}
                                <select name="teacher_ids[]" id="teacherMultiSelect" class="form-select {{ $addErr('teacher_ids') ? 'is-invalid' : '' }}" multiple size="5" required>
                                    @foreach ($teachers as $t)
                                        <option value="{{ $t->id }}" @selected(is_array(old('teacher_ids')) && in_array($t->id, old('teacher_ids')))>
                                            {{ $t->name }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Chip Guru Terpilih --}}
                                <div class="mt-2">
                                    <div class="small fw-semibold text-muted mb-1">Guru Terpilih (<span id="selectedTeacherCount">0</span>):</div>
                                    <div id="selectedTeacherChips" class="d-flex flex-wrap gap-1 p-2 border rounded bg-light" style="min-height: 42px;">
                                        <span class="text-muted small align-self-center fst-italic" id="noTeacherSelectedHint">Belum ada guru yang dipilih.</span>
                                    </div>
                                </div>

                                @if ($addErr('teacher_ids'))
                                    <div class="invalid-feedback d-block">{{ $addErr('teacher_ids') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Materi / Modul Terkait (opsional)</label>
                                    <select name="material_id" class="form-select">
                                        <option value="">— Tidak terikat materi spesifik —</option>
                                        @foreach ($materials as $m)
                                            <option value="{{ $m->id }}" @selected(old('material_id') == $m->id)>
                                                Pertemuan {{ $m->meeting_number }}: {{ $m->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Prioritas</label>
                                        <select name="priority" class="form-select" required>
                                            <option value="Low" @selected(old('priority') === 'Low')>Rendah (Low)</option>
                                            <option value="Medium" @selected(old('priority', 'Medium') === 'Medium')>Sedang (Medium)</option>
                                            <option value="High" @selected(old('priority') === 'High')>Tinggi (High / Urgent)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Tenggat Waktu</label>
                                        <input type="date" name="due_date" class="form-control" min="{{ today()->toDateString() }}" value="{{ old('due_date') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul Tugas <span class="text-danger">*</span></label>
                            <input type="text" name="title" maxlength="255" class="form-control" placeholder="mis. Revisi Slide PPT & Aktivitas Belajar Modul 3" value="{{ old('title') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Instruksi & Poin Perbaikan <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4" maxlength="2000" class="form-control" placeholder="Tuliskan detail poin yang perlu diperbaiki oleh guru..." required>{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lampiran Dokumen / Contoh (opsional)</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">Format: PDF, Word, JPG, PNG — maks. 10 MB.</small>
                        </div>

                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Tugaskan ke Guru
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- FORM 2: TUGAS KELAS (LAMA) --}}
        <div class="tab-pane fade" id="pane-class-task">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Buat Tugas Perbaikan Kelas</div>
                <div class="card-body">
                    <form action="{{ route('kurikulum.tugas-kelas.store') }}" method="POST"
                          enctype="multipart/form-data"
                          onsubmit="return lockSubmit(this, 'Mengirim...')">
                        @csrf
                        <input type="hidden" name="task_type" value="class_task">

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

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lampiran (opsional)</label>
                            <input type="file" name="attachment"
                                   class="form-control {{ $addErr('attachment') ? 'is-invalid' : '' }}"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            @if ($addErr('attachment'))
                                <div class="invalid-feedback">{{ $addErr('attachment') }}</div>
                            @endif
                            <small class="text-muted">Format: PDF, Word, JPG, PNG — maks. 10 MB.</small>
                        </div>

                        <button type="submit" class="btn btn-secondary">Kirim Tugas Kelas</button>
                    </form>
                </div>
            </div>
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
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-semibold">{{ $t->title }}</span>
                                @if ($t->task_type === 'material_revision')
                                    <span class="badge bg-purple text-white" style="background-color: #6f42c1;">
                                        <i class="fa-solid fa-file-pen me-1"></i> Perbaikan Materi
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fa-solid fa-chalkboard-user me-1"></i> Tugas Kelas
                                    </span>
                                @endif

                                @if ($t->priority === 'High')
                                    <span class="badge bg-danger">Prioritas Tinggi</span>
                                @elseif ($t->priority === 'Medium')
                                    <span class="badge bg-warning text-dark">Prioritas Sedang</span>
                                @elseif ($t->priority === 'Low')
                                    <span class="badge bg-light text-muted border">Prioritas Rendah</span>
                                @endif
                            </div>

                            <small class="text-muted d-block mt-1">
                                <strong>Guru:</strong> {{ $t->teacher->name ?? '-' }}
                                @if ($t->class)
                                    · <strong>Kelas:</strong> {{ $t->class->class_name }}
                                @endif
                                @if ($t->material)
                                    · <strong>Materi:</strong> P-{{ $t->material->meeting_number }} ({{ $t->material->title }})
                                @endif
                                @if ($t->due_date)
                                    · <strong>Batas:</strong>
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

                            @if ($t->attachment_path)
                                <div class="small mt-2">
                                    <i class="bi bi-paperclip me-1"></i>
                                    <a href="{{ Storage::url($t->attachment_path) }}" target="_blank" class="text-decoration-none">
                                        {{ $t->attachment_name ?? 'Lihat Lampiran' }}
                                    </a>
                                </div>
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
                      enctype="multipart/form-data"
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

                        {{-- Lampiran --}}
                        <div class="mb-1 mt-3">
                            <label class="form-label fw-semibold">Ganti Lampiran (opsional)</label>

                            @if ($t->attachment_path)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <i class="bi bi-paperclip text-muted"></i>
                                    <a href="{{ Storage::url($t->attachment_path) }}" target="_blank"
                                       class="small text-decoration-none">
                                        {{ $t->attachment_name ?? 'Lampiran saat ini' }}
                                    </a>
                                    <span class="text-muted small">(akan diganti jika upload file baru)</span>
                                </div>
                            @endif

                            <input type="file" name="attachment"
                                   class="form-control"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">Format: PDF, Word, JPG, PNG — maks. 10 MB. Kosongkan jika tidak ingin mengganti.</small>
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

    // Script Multi-select Guru dengan Pencarian, Chips, Pilih Semua, Hapus Pilihan
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('teacherMultiSelect');
        const searchInput = document.getElementById('teacherSearchInput');
        const chipsContainer = document.getElementById('selectedTeacherChips');
        const countSpan = document.getElementById('selectedTeacherCount');
        const emptyHint = document.getElementById('noTeacherSelectedHint');
        const btnSelectAll = document.getElementById('btnSelectAllTeachers');
        const btnClear = document.getElementById('btnClearTeachers');

        if (!select || !chipsContainer) return;

        function updateChips() {
            chipsContainer.innerHTML = '';
            let selectedCount = 0;

            Array.from(select.options).forEach(opt => {
                if (opt.selected) {
                    selectedCount++;
                    const chip = document.createElement('span');
                    chip.className = 'badge bg-primary text-white d-inline-flex align-items-center gap-1 p-2 rounded-pill';
                    chip.style.fontSize = '12px';
                    chip.innerHTML = `<span>${opt.text}</span>
                        <i class="fa-solid fa-xmark cursor-pointer ms-1" style="cursor: pointer;" title="Hapus guru ini"></i>`;

                    chip.querySelector('i').addEventListener('click', function (e) {
                        e.stopPropagation();
                        opt.selected = false;
                        updateChips();
                    });

                    chipsContainer.appendChild(chip);
                }
            });

            if (countSpan) countSpan.innerText = selectedCount;
            if (emptyHint) {
                if (selectedCount === 0) {
                    chipsContainer.appendChild(emptyHint);
                }
            }
        }

        // Event change pada select
        select.addEventListener('change', updateChips);

        // Pencarian guru dalam select list
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const keyword = this.value.toLowerCase().trim();
                Array.from(select.options).forEach(opt => {
                    const match = opt.text.toLowerCase().includes(keyword);
                    opt.style.display = match ? '' : 'none';
                });
            });
        }

        // Pilih Semua (yang sedang tampak)
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', function () {
                Array.from(select.options).forEach(opt => {
                    if (opt.style.display !== 'none') {
                        opt.selected = true;
                    }
                });
                updateChips();
            });
        }

        // Hapus Pilihan
        if (btnClear) {
            btnClear.addEventListener('click', function () {
                Array.from(select.options).forEach(opt => {
                    opt.selected = false;
                });
                updateChips();
            });
        }

        // Render chips inisial (mis. old value)
        updateChips();
    });
</script>

@endsection