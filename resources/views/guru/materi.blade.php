@extends('layouts.guru')

@section('title', 'Materi & Silabus')

@section('content')

<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Materi & Silabus</h2>
        <p class="text-muted mb-0">
            Perpustakaan silabus dan materi resmi dari Kurikulum. Anda dapat melihat dan mengunduh seluruh materi persiapan mengajar per program, level, dan pertemuan.
        </p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Program, Level, & Pencarian --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('teacher.materi') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    {{-- Pencarian --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold small text-muted mb-1">Cari Judul / Silabus / Pertemuan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="q" id="searchMateriInput" value="{{ request('q') }}"
                                   class="form-control border-start-0"
                                   placeholder="Ketik judul, silabus, atau no. pertemuan...">
                        </div>
                    </div>

                    {{-- Filter Program --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold small text-muted mb-1">Program</label>
                        <select name="program" id="programSelectFilter" class="form-select" onchange="onProgramChange()">
                            <option value="">Semua Program ({{ $totalGuruMaterials }})</option>
                            @foreach ($programTabs as $tab)
                                <option value="{{ $tab['id'] }}" @selected($selectedProgram === $tab['id'])>
                                    {{ $tab['name'] }} ({{ $tab['count'] }}) @if($tab['is_taught']) â˜… Kelas Anda @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Level (Dependent pada Program) --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold small text-muted mb-1">Level</label>
                        <select name="level_id" id="levelSelectFilter" class="form-select">
                            <option value="">Semua Level</option>
                            {{-- Diisi secara dinamis lewat JS berdasarkan pilihan program --}}
                        </select>
                    </div>

                    {{-- Tombol Terapkan & Reset --}}
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark flex-fill rounded-3 fw-semibold">
                            <i class="fa-solid fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('teacher.materi') }}" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>

            {{-- Tab / Chips Program dengan Jumlah Materi --}}
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top" id="programPills">
                <a href="{{ route('teacher.materi', array_filter(['q' => request('q')])) }}"
                   class="btn btn-sm rounded-pill px-3 {{ empty($selectedProgram) ? 'btn-dark' : 'btn-outline-secondary' }}">
                    Semua <span class="badge bg-light text-dark ms-1">{{ $totalGuruMaterials }}</span>
                </a>
                @foreach ($programTabs as $tab)
                    @php
                        $isActive = ($selectedProgram === $tab['id']);
                    @endphp
                    <a href="{{ route('teacher.materi', array_filter(['program' => $tab['id'], 'q' => request('q')])) }}"
                       class="btn btn-sm rounded-pill px-3 {{ $isActive ? 'btn-dark' : 'btn-outline-secondary' }}"
                       data-program-id="{{ $tab['id'] }}">
                        {{ $tab['name'] }}
                        <span class="badge {{ $isActive ? 'bg-light text-dark' : 'bg-secondary-subtle text-dark' }} ms-1">{{ $tab['count'] }}</span>
                        @if ($tab['is_taught'])
                            <span class="badge bg-success-subtle text-success ms-1 border border-success-subtle" title="Program ini diajar oleh Anda di kelas aktif">
                                <i class="fa-solid fa-chalkboard-user me-1"></i>Kelas Anda
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Daftar Materi Terkelompok per Program & Level --}}
    @if ($groupedMaterials->isNotEmpty())
        <div class="accordion" id="accordionMateriPrograms">
            @foreach ($groupedMaterials as $progKey => $progItems)
                @php
                    $firstItem   = $progItems->first();
                    $isUmum      = ($progKey === 'umum' || is_null($firstItem->program_id));
                    $programName = $isUmum ? 'Umum' : ($firstItem->program?->category_name ?? 'Program Lain');
                    $collapseId  = 'collapseProg_' . \Illuminate\Support\Str::slug($programName ?: 'umum') . '_' . $progKey;
                    $isTaught    = in_array((int) $progKey, $taughtProgramIds, true);

                    // Kelompokkan materi dalam program ini berdasarkan Level
                    $itemsByLevel = $progItems->groupBy(fn ($m) => $m->level?->level_name ?? ($isUmum ? 'Materi Umum' : 'Semua Level'));
                @endphp

                <div class="accordion-item border-0 shadow-sm rounded-4 mb-4 overflow-hidden program-accordion-block">
                    <h2 class="accordion-header" id="heading_{{ $collapseId }}">
                        <button class="accordion-button bg-white text-dark fw-bold py-3 px-4 shadow-none"
                                type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                aria-expanded="true" aria-controls="{{ $collapseId }}">
                            <div class="d-flex align-items-center justify-content-between w-100 me-3 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $isUmum ? 'bg-secondary' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill fs-6">
                                        <i class="fa-solid fa-layer-group me-1"></i> {{ $programName }}
                                    </span>
                                    @if ($isTaught)
                                        <span class="badge bg-success text-white px-2 py-1 rounded-pill small">
                                            <i class="fa-solid fa-chalkboard-user me-1"></i>Kelas Anda
                                        </span>
                                    @endif
                                    <span class="fw-semibold text-muted small">
                                        {{ $progItems->count() }} materi
                                    </span>
                                </div>
                                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 small">
                                    {{ $itemsByLevel->count() }} Level / Kategori
                                </span>
                            </div>
                        </button>
                    </h2>

                    <div id="{{ $collapseId }}" class="accordion-collapse collapse show"
                         aria-labelledby="heading_{{ $collapseId }}">
                        <div class="accordion-body p-0">
                            @foreach ($itemsByLevel as $levelLabel => $levelMaterials)
                                @php
                                    $firstLvlMat = $levelMaterials->first();
                                    $isLevelTaught = $firstLvlMat->level_id && in_array($firstLvlMat->level_id, $taughtLevelIds, true);
                                @endphp

                                {{-- Header Sub-Kelompok Level --}}
                                <div class="bg-light px-4 py-2 border-top border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-bookmark text-warning"></i>
                                        <strong class="text-dark small">{{ $levelLabel }}</strong>
                                        @if ($isLevelTaught)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 11px;">
                                                Level Kelas Anda
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-muted small">{{ $levelMaterials->count() }} pertemuan</span>
                                </div>

                                {{-- Daftar Materi per Pertemuan di Level Ini --}}
                                <div class="list-group list-group-flush">
                                    @foreach ($levelMaterials as $material)
                                        <div class="list-group-item p-3 p-md-4 material-item-row">
                                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                        <span class="badge bg-primary rounded-pill px-2 py-1">
                                                            Pertemuan {{ $material->meeting_number }}
                                                        </span>
                                                        @if ($material->level)
                                                            <span class="badge bg-info-subtle text-dark border rounded-pill px-2 py-1">
                                                                Level {{ $material->level->level_name }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                                                Umum
                                                            </span>
                                                        @endif
                                                        <small class="text-muted">
                                                            Oleh: {{ $material->uploader->username ?? 'Kurikulum' }} Â· {{ $material->created_at?->format('d M Y') }}
                                                        </small>
                                                    </div>

                                                    <h5 class="fw-bold mb-1 text-dark">
                                                        {{ $material->title }}
                                                    </h5>

                                                    @if ($material->description)
                                                        <p class="text-muted small mb-0 mt-1">
                                                            {{ $material->description }}
                                                        </p>
                                                    @endif
                                                </div>

                                                {{-- Tombol Lihat, Unduh, dan Detail --}}
                                                <div class="d-flex gap-2 flex-shrink-0 align-items-center flex-wrap">
                                                    <a href="{{ route('teacher.materi.show', $material) }}"
                                                       target="_blank"
                                                       class="btn btn-outline-dark btn-sm rounded-pill px-3">
                                                        <i class="fa-solid fa-eye me-1"></i> Lihat
                                                    </a>

                                                    <a href="{{ route('teacher.materi.download', $material) }}"
                                                       class="btn btn-warning btn-sm rounded-pill fw-semibold px-3 text-dark">
                                                        <i class="fa-solid fa-download me-1"></i> Unduh
                                                    </a>

                                                    @if ($material->syllabus || $material->cco || $material->lesson_plan || $material->activity || $material->vocabularies->count() || $material->box_code || $material->equipment_list)
                                                        <button class="btn btn-light btn-sm border rounded-pill px-3" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#detailSyllabus_{{ $material->id }}"
                                                                aria-expanded="false">
                                                            <i class="fa-solid fa-circle-info me-1"></i> Silabus & CCO
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Collapsible Detail Silabus & Kosakata --}}
                                            @if ($material->syllabus || $material->cco || $material->lesson_plan || $material->activity || $material->vocabularies->count() || $material->box_code || $material->equipment_list)
                                                <div class="collapse mt-3 pt-3 border-top" id="detailSyllabus_{{ $material->id }}">
                                                    @if ($material->syllabus)
                                                        <div class="mb-3">
                                                            <div class="fw-semibold text-muted small mb-1">
                                                                <i class="fa-solid fa-list-check me-1"></i> Silabus Pembelajaran:
                                                            </div>
                                                            <div class="p-3 bg-light rounded-3 text-secondary small">
                                                                {!! nl2br(e($material->syllabus)) !!}
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="row g-2 mb-2">
                                                        @if ($material->cco)
                                                            <div class="col-md-4">
                                                                <div class="p-2 bg-light rounded-3 small h-100">
                                                                    <strong class="text-muted d-block mb-1">Course Content Outline (CCO):</strong>
                                                                    <div class="text-secondary">{!! nl2br(e($material->cco)) !!}</div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if ($material->lesson_plan)
                                                            <div class="col-md-4">
                                                                <div class="p-2 bg-light rounded-3 small h-100">
                                                                    <strong class="text-muted d-block mb-1">Lesson Plan:</strong>
                                                                    <div class="text-secondary">{!! nl2br(e($material->lesson_plan)) !!}</div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if ($material->activity)
                                                            <div class="col-md-4">
                                                                <div class="p-2 bg-light rounded-3 small h-100">
                                                                    <strong class="text-muted d-block mb-1">Aktivitas Belajar:</strong>
                                                                    <div class="text-secondary">{!! nl2br(e($material->activity)) !!}</div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    @if ($material->box_code || $material->equipment_list)
                                                        <div class="p-2 bg-light rounded-3 small mb-2 d-flex align-items-center gap-2 flex-wrap">
                                                            <strong class="text-muted">Perlengkapan / Box:</strong>
                                                            @if ($material->box_code)
                                                                <span class="badge bg-dark">{{ $material->box_code }}</span>
                                                            @endif
                                                            <span class="text-secondary">{{ $material->equipment_list }}</span>
                                                        </div>
                                                    @endif

                                                    @if ($material->vocabularies->count())
                                                        <div class="mt-2">
                                                            <div class="fw-semibold text-muted small mb-1">
                                                                <i class="fa-solid fa-language me-1"></i> Kosakata Pertemuan Ini:
                                                            </div>
                                                            <div class="row g-1">
                                                                @foreach ($material->vocabularies as $vocab)
                                                                    <div class="col-sm-6 col-md-4">
                                                                        <div class="border rounded p-2 bg-white small">
                                                                            <strong>{{ $vocab->hanzi }}</strong>
                                                                            <span class="text-muted">({{ $vocab->pinyin }})</span>
                                                                            <span class="text-secondary">- {{ $vocab->meaning }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="text-muted small">
                Menampilkan {{ $materials->firstItem() ?? 0 }} - {{ $materials->lastItem() ?? 0 }} dari {{ $materials->total() }} materi
            </div>
            <div>
                {{ $materials->links() }}
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-secondary">Tidak ada materi ditemukan</h5>
                <p class="text-muted small mb-3">
                    @if (request('q') || request('program') || request('level_id'))
                        Tidak ada materi yang sesuai dengan filter pencarian Anda. Silakan coba atur ulang filter.
                    @else
                        Materi untuk pengajar belum diunggah oleh pihak Kurikulum.
                    @endif
                </p>
                <a href="{{ route('teacher.materi') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-rotate-left me-1"></i> Tampilkan Semua Materi
                </a>
            </div>
        </div>
    @endif

    <div class="text-muted small mt-4">
        <i class="fa-solid fa-info-circle me-1"></i>
        Materi & Silabus dikelola resmi oleh Kurikulum. Sebagai pengajar, Anda dapat mengakses seluruh materi dan silabus semua program untuk referensi mengajar. Hak akses read-only.
    </div>

</div>

@endsection

@push('scripts')
<script>
const programTabsData = @json($programTabs);
const currentSelectedLevel = "{{ request('level_id', '') }}";

function populateLevels(programId) {
    const levelSelect = document.getElementById('levelSelectFilter');
    if (!levelSelect) return;

    levelSelect.innerHTML = '<option value="">Semua Level</option>';

    if (!programId || programId === 'umum') {
        return;
    }

    const prog = programTabsData.find(p => p.id === programId);
    if (prog && Array.isArray(prog.levels)) {
        prog.levels.forEach(lvl => {
            const opt = document.createElement('option');
            opt.value = lvl.id;
            opt.textContent = 'Level ' + lvl.name;
            if (String(lvl.id) === String(currentSelectedLevel)) {
                opt.selected = true;
            }
            levelSelect.appendChild(opt);
        });
    }
}

function onProgramChange() {
    const progSelect = document.getElementById('programSelectFilter');
    populateLevels(progSelect ? progSelect.value : '');
}

document.addEventListener('DOMContentLoaded', function () {
    const progSelect = document.getElementById('programSelectFilter');
    if (progSelect) {
        populateLevels(progSelect.value);
    }
});
</script>
@endpush
