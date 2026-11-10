@extends('layouts.kurikulum')

@section('title', 'Materi & Silabus')

@section('content')

@php
    $audStyle = [
        'Guru'   => ['bg-primary', '👩‍🏫'],
        'Siswa'  => ['bg-success', '🎓'],
        'Huahua' => ['bg-danger',  '🌸'],
    ];

    $tabs = [
        ''       => ['Semua', $counts->sum()],
        'Guru'   => ['Guru', $counts['Guru'] ?? 0],
        'Siswa'  => ['Siswa', $counts['Siswa'] ?? 0],
        'Huahua' => ['Huahua', $counts['Huahua'] ?? 0],
    ];
@endphp

<style>
    /* Perbaikan: <form> membungkus modal-body & modal-footer sehingga scroll Bootstrap tidak jalan */
    .modal-dialog-scrollable .modal-content > form {
        display: flex;
        flex-direction: column;
        min-height: 0;
        max-height: 100%;
        overflow: hidden;
    }
    .modal-dialog-scrollable .modal-content > form > .modal-body {
        overflow-y: auto;
    }
    .aud-card.selected { border-color: #ffd600 !important; background: #fff9d6; }
</style>

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Materi & Silabus</h3>
        <small class="text-muted">
            Upload materi untuk <strong>Guru</strong>, <strong>Siswa</strong>, atau <strong>Huahua</strong> (Belajar Vocab).
            Setiap tujuan punya file sendiri dan hanya tampil ke pihak yang dituju.
        </small>
    </div>

    {{-- PESAN --}}
    @foreach (['success' => 'success', 'info' => 'info', 'warning' => 'warning', 'error' => 'danger'] as $key => $type)
        @if (session($key))
            <div class="alert alert-{{ $type }} alert-dismissible fade show">
                {{ session($key) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Terjadi Kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FORM UPLOAD BARU --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h4 class="fw-bold">Upload Materi Baru</h4>
            <small class="text-muted">Pilih dulu untuk siapa materi ini diupload.</small>

            <form action="{{ route('kurikulum.materi.store') }}" method="POST" enctype="multipart/form-data"
                  class="mt-4" data-audience-form onsubmit="return lockSubmit(this, 'Mengunggah... jangan tutup halaman')">
                @csrf

                @include('kurikulum.partials.materi-fields', ['m' => null, 'levels' => $levels, 'programs' => $programs])

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-upload me-2"></i> Publish Materi
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TAB TUJUAN + FILTER --}}
    <ul class="nav nav-pills mb-3">
        @foreach ($tabs as $key => [$label, $total])
            <li class="nav-item">
                <a class="nav-link {{ (string) $audience === (string) $key ? 'active' : '' }}"
                   href="{{ route('kurikulum.materi', array_filter(['audience' => $key, 'level_id' => request('level_id'), 'q' => request('q')])) }}">
                    {{ $label }} <span class="badge bg-light text-dark ms-1">{{ $total }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('kurikulum.materi') }}" class="row g-2 align-items-end">
                @if ($audience)
                    <input type="hidden" name="audience" value="{{ $audience }}">
                @endif

                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-1">Program</label>
                    <select name="program_id" class="form-select">
                        <option value="">Semua Program</option>
                        <option value="umum" @selected(request('program_id') === 'umum')>Umum</option>
                        @foreach ($programs as $prog)
                            <option value="{{ $prog->id }}" @selected((string) request('program_id') === (string) $prog->id)>
                                {{ $prog->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-1">Level</label>
                    <select name="level_id" class="form-select">
                        <option value="">Semua level</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected((string) request('level_id') === (string) $level->id)>
                                {{ $level->category?->category_name ?? $level->program?->program_name ?? '-' }} - Level {{ $level->level_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-1">Cari</label>
                    <input type="text" name="q" value="{{ request('q') }}" maxlength="100" class="form-control"
                           placeholder="Judul, silabus, deskripsi, kode box, atau nomor pertemuan">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Terapkan
                    </button>
                    <a href="{{ route('kurikulum.materi', array_filter(['audience' => $audience])) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR MATERI --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Daftar Materi</h5>
                <span class="badge bg-primary">{{ $materials->total() }} Materi</span>
            </div>
        </div>

        <div class="card-body p-0">
            @forelse ($materials as $material)
                @php
                    $inUse = $material->teacher_materials_count + $material->teaching_journals_count;
                    [$audClass, $audIcon] = $audStyle[$material->audience] ?? ['bg-secondary', ''];
                @endphp

                <div class="list-group list-group-flush">
                    <div class="list-group-item py-4">
                        <div class="row">

                            {{-- INFORMASI --}}
                            <div class="col-lg-9">
                                <h5 class="fw-bold mb-2">{{ $material->title }}</h5>

                                <div class="mb-2">
                                    <span class="badge {{ $audClass }}">{{ $audIcon }} {{ $material->audience }}</span>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-layer-group me-1"></i> {{ $material->program_name }}</span>
                                    <span class="badge bg-primary">Pertemuan {{ $material->meeting_number }}</span>

                                    @if ($inUse > 0)
                                        <span class="badge bg-info text-dark ms-1">
                                            Dipakai: {{ $material->teacher_materials_count }} pengajuan · {{ $material->teaching_journals_count }} jurnal
                                        </span>
                                    @endif
                                    @unless ($material->file_exists)
                                        <span class="badge bg-danger ms-1">File hilang</span>
                                    @endunless
                                </div>

                                <div class="text-muted mb-1"><strong>Program :</strong> {{ $material->program_name }} {{ $material->level?->program ? '(' . $material->level->program->program_name . ')' : '' }}</div>
                                <div class="text-muted mb-1"><strong>Level / Kelas :</strong> {{ $material->level?->level_name ?? 'Umum (Tanpa Level)' }}</div>
                                <div class="text-muted mb-1"><strong>Uploader :</strong> {{ $material->uploader?->username ?? '-' }}</div>
                                <div class="text-muted mb-3"><strong>Tanggal Upload :</strong> {{ $material->created_at?->format('d M Y H:i') }}</div>

                                @if ($material->audience === 'Guru')
                                    @foreach (['syllabus' => 'Silabus', 'cco' => 'CCO', 'lesson_plan' => 'Lesson Plan', 'activity' => 'Aktivitas'] as $field => $label)
                                        @if ($material->{$field})
                                            <div class="mb-3">
                                                <strong>{{ $label }}</strong>
                                                <div class="border rounded p-3 bg-light mt-2">{!! nl2br(e($material->{$field})) !!}</div>
                                            </div>
                                        @endif
                                    @endforeach

                                    @if ($material->box_code || $material->equipment_list)
                                        <div class="mb-3">
                                            <strong>
                                                Box Perlengkapan
                                                @if ($material->box_code)
                                                    <span class="badge bg-dark ms-1">{{ $material->box_code }}</span>
                                                @endif
                                            </strong>
                                            @if ($material->equipment_list)
                                                <div class="border rounded p-3 bg-light mt-2">{!! nl2br(e($material->equipment_list)) !!}</div>
                                            @endif
                                        </div>
                                    @endif
                                @elseif ($material->description)
                                    <div class="mb-3">
                                        <strong>Deskripsi</strong>
                                        <div class="border rounded p-3 bg-light mt-2">{!! nl2br(e($material->description)) !!}</div>
                                    </div>
                                @endif

                                {{-- Kosakata --}}
                                @if ($material->audience === 'Huahua')
                                    @if ($material->huahua_words_count > 0)
                                        <div class="mb-3">
                                            <strong>Bank Huahua ({{ $material->huahua_words_count }} kata)</strong>
                                            <div class="border rounded p-3 bg-light mt-2">
                                                <div class="row">
                                                    @foreach ($material->huahuaWords->take(12) as $w)
                                                        <div class="col-md-6 mb-2">
                                                            <strong>{{ $w->hanzi }}</strong>
                                                            <span class="text-muted">({{ $w->pinyin }})</span>
                                                            - {{ $w->meaning_id }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($material->huahua_words_count > 12)
                                                    <small class="text-muted">dan {{ $material->huahua_words_count - 12 }} kata lainnya (buka Ubah untuk melihat semua)</small>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @elseif ($material->vocabularies->count())
                                    <div class="mb-3">
                                        <strong>Kosakata ({{ $material->vocabularies->count() }})</strong>
                                        <div class="border rounded p-3 bg-light mt-2">
                                            <div class="row">
                                                @foreach ($material->vocabularies as $vocab)
                                                    <div class="col-md-6 mb-2">
                                                        <strong>{{ $vocab->hanzi }}</strong>
                                                        <span class="text-muted">({{ $vocab->pinyin }})</span>
                                                        - {{ $vocab->meaning }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- TOMBOL AKSI --}}
                            <div class="col-lg-3">
                                <div class="d-grid gap-2">
                                    @if ($material->file_exists)
                                        @if ($material->is_pdf)
                                            <a href="{{ route('kurikulum.materi.preview', $material) }}" target="_blank"
                                               class="btn btn-outline-primary">
                                                <i class="fa-solid fa-eye me-2"></i> Lihat
                                            </a>
                                        @endif
                                        <a href="{{ route('kurikulum.materi.download', $material) }}" class="btn btn-primary">
                                            <i class="fa-solid fa-download me-2"></i> Download Materi
                                        </a>
                                    @endif

                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editMaterialModal{{ $material->id }}">
                                        <i class="fa-solid fa-pen me-2"></i> Edit
                                    </button>

                                    @if ($inUse > 0)
                                        <button type="button" class="btn btn-danger w-100" disabled
                                                title="Materi sudah dipakai guru, tidak bisa dihapus">
                                            <i class="fa-solid fa-trash me-2"></i> Hapus
                                        </button>
                                    @else
                                        <form action="{{ route('kurikulum.materi.destroy', $material) }}" method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus materi ini? File juga akan dihapus.{{ $material->audience === 'Huahua' ? ' Kata di bank Huahua tetap ada.' : '' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fa-solid fa-trash me-2"></i> Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <h5 class="fw-bold">Belum Ada Materi</h5>
                    <p class="text-muted mb-0">
                        @if (request()->hasAny(['audience', 'level_id', 'q']))
                            Tidak ada materi yang cocok dengan filter. <a href="{{ route('kurikulum.materi') }}">Reset filter</a>
                        @else
                            Silakan upload materi pertama.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if ($materials->hasPages())
            <div class="card-footer bg-white">
                {{ $materials->links() }}
            </div>
        @endif
    </div>

</div>

{{-- MODAL EDIT (satu per materi pada halaman ini) --}}
@foreach ($materials as $material)
    <div class="modal fade" id="editMaterialModal{{ $material->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('kurikulum.materi.update', $material) }}" method="POST"
                      enctype="multipart/form-data" data-audience-form data-audience="{{ $material->audience }}"
                      onsubmit="return lockSubmit(this, 'Menyimpan...')">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold">Edit Materi</h5>
                            <small class="text-muted">
                                {{ $material->audience }} · Pertemuan {{ $material->meeting_number }} - {{ $material->level?->level_name ?? '-' }}
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        @include('kurikulum.partials.materi-fields', ['m' => $material, 'levels' => $levels, 'programs' => $programs])
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    function filterLevelsByProgram(selectEl, id) {
        const progId = selectEl.value;
        const levelSelect = document.getElementById('levelSelect' + id);
        if (!levelSelect) return;
        const options = levelSelect.querySelectorAll('option');
        options.forEach(opt => {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            const catId = opt.dataset.categoryId;
            if (!progId || catId === progId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function lockSubmit(form, text) {
        const btn = form.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.innerText = text; }
        return true;
    }

    // ---- Form menyesuaikan tujuan materi (Guru / Siswa / Huahua)
    const AUD = {
        Guru:   { title: 'Daftar Kosakata', hint: 'Format: Hanzi | Pinyin | Arti — satu kosakata per baris (maksimal 255 baris).',
                  ph: '爸爸|bàba|Ayah\n妈妈|māma|Ibu' },
        Siswa:  { title: 'Daftar Kosakata', hint: 'Format: Hanzi | Pinyin | Arti — satu kosakata per baris. Tampil bersama materi di Program Belajar siswa.',
                  ph: '爸爸|bàba|Ayah\n妈妈|māma|Ibu' },
        Huahua: { title: 'Kosakata untuk Bank Huahua', hint: 'Format: Hanzi | Pinyin | Arti Indonesia | English (opsional) | Jenis kata (opsional). Kata yang sama (level + hanzi) diperbarui, tidak digandakan.',
                  ph: '爸爸|bàba|Ayah|Father|名\n学习|xuéxí|Belajar|To study|动' }
    };

    function applyAudience(form) {
        const checked = form.querySelector('input[name=audience]:checked');
        const aud = checked ? checked.value : (form.dataset.audience || 'Guru');

        form.querySelectorAll('.aud-field').forEach(function (el) {
            el.style.display = (el.dataset.aud || '').split(' ').includes(aud) ? '' : 'none';
        });

        form.querySelectorAll('.aud-card').forEach(function (c) {
            const r = c.querySelector('input');
            c.classList.toggle('selected', !!(r && r.checked));
        });

        const cfg = AUD[aud] || AUD.Guru;
        form.querySelectorAll('[data-vocab-title]').forEach(e => e.textContent = cfg.title);
        form.querySelectorAll('[data-vocab-hint]').forEach(e => e.textContent = cfg.hint);
        form.querySelectorAll('[data-vocab-area]').forEach(e => e.placeholder = cfg.ph);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-audience-form]').forEach(function (form) {
            applyAudience(form);
            form.addEventListener('change', () => applyAudience(form));
        });
    });

    @if (old('_edit_id'))
        // Jika simpan edit gagal validasi, buka kembali modal yang sama
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('editMaterialModal{{ (int) old('_edit_id') }}');
            if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
        });
    @endif
</script>

@endsection