@extends('layouts.guru')

@section('title', 'Materi & Silabus')

@section('content')

<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Materi & Silabus</h2>
        <p class="text-muted mb-0">
            Perpustakaan materi resmi dari Kurikulum yang dikelompokkan per program belajar dan materi umum yang Anda ajar.
        </p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Program & Pencarian Judul --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('teacher.materi') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold mb-1">Cari Judul / Silabus</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" id="searchMateriInput" value="{{ request('q') }}"
                                   class="form-control border-start-0"
                                   placeholder="Ketik judul materi, topik, atau kata kunci..."
                                   oninput="filterRealtime()">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-1">Filter Program</label>
                        <select name="program" id="programSelectFilter" class="form-select" onchange="filterRealtime()">
                            <option value="">Semua Program ({{ $materials->count() }} Materi)</option>
                            @foreach ($availablePrograms as $pName)
                                <option value="{{ $pName }}" @selected(request('program') === $pName)>
                                    Program: {{ $pName }} ({{ $groupedMaterials[$pName]->count() ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark flex-fill rounded-3">
                            <i class="fa-solid fa-filter me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('teacher.materi') }}" class="btn btn-outline-secondary rounded-3">Reset</a>
                    </div>
                </div>
            </form>

            {{-- Quick Filter Pills --}}
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top" id="programPills">
                <button type="button" class="btn btn-sm rounded-pill {{ !request('program') ? 'btn-dark' : 'btn-outline-secondary' }}"
                        onclick="selectProgramPill('', this)">
                    Semua ({{ $materials->count() }})
                </button>
                @foreach ($availablePrograms as $pName)
                    <button type="button" class="btn btn-sm rounded-pill {{ request('program') === $pName ? 'btn-dark' : 'btn-outline-secondary' }}"
                            data-program="{{ $pName }}"
                            onclick="selectProgramPill('{{ $pName }}', this)">
                        {{ $pName }} <span class="badge bg-light text-dark ms-1">{{ $groupedMaterials[$pName]->count() ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Accordion Materi per Program --}}
    @if ($groupedMaterials->isNotEmpty())
        <div class="accordion" id="accordionMateriPrograms">
            @foreach ($groupedMaterials as $programName => $items)
                @php
                    $collapseId = 'collapseProg_' . \Illuminate\Support\Str::slug($programName ?: 'umum');
                    $isUmum = ($programName === 'Umum');
                @endphp

                <div class="accordion-item border-0 shadow-sm rounded-4 mb-3 overflow-hidden program-accordion-block"
                     data-program-name="{{ $programName }}">
                    <h2 class="accordion-header" id="heading_{{ $collapseId }}">
                        <button class="accordion-button bg-white text-dark fw-bold py-3 px-4 shadow-none"
                                type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                aria-expanded="true" aria-controls="{{ $collapseId }}">
                            <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $isUmum ? 'bg-secondary' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-layer-group me-1"></i> {{ $programName }}
                                    </span>
                                    <span class="fw-semibold text-secondary small">
                                        {{ $items->count() }} materi
                                    </span>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="{{ $collapseId }}" class="accordion-collapse collapse show"
                         aria-labelledby="heading_{{ $collapseId }}">
                        <div class="accordion-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach ($items as $material)
                                    <div class="list-group-item p-4 material-item-row"
                                         data-title="{{ strtolower($material->title) }}"
                                         data-syllabus="{{ strtolower($material->syllabus ?? '') }}"
                                         data-program="{{ $programName }}">
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                    <span class="badge bg-primary rounded-pill">Pertemuan {{ $material->meeting_number }}</span>
                                                    @if ($material->level)
                                                        <span class="badge bg-info text-dark rounded-pill">
                                                            Level {{ $material->level->level_name }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-dark border rounded-pill">
                                                            Semua Level
                                                        </span>
                                                    @endif
                                                    <small class="text-muted">
                                                        Diunggah oleh {{ $material->uploader->username ?? 'Kurikulum' }} · {{ $material->created_at?->format('d M Y') }}
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

                                            <div class="d-flex gap-2 flex-shrink-0">
                                                <a href="{{ route('teacher.materi.show', $material) }}"
                                                   target="_blank"
                                                   class="btn btn-outline-dark rounded-3 px-3">
                                                    <i class="fa-solid fa-eye me-1"></i> Lihat
                                                </a>

                                                <a href="{{ route('teacher.materi.download', $material) }}"
                                                   class="btn btn-warning rounded-3 fw-semibold px-3">
                                                    <i class="fa-solid fa-download me-1"></i> Unduh
                                                </a>

                                                @if ($material->syllabus || $material->cco || $material->lesson_plan || $material->vocabularies->count())
                                                    <button class="btn btn-light border rounded-3 px-3" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#detailSyllabus_{{ $material->id }}"
                                                            aria-expanded="false">
                                                        <i class="fa-solid fa-circle-info me-1"></i> Detail
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Collapsible Detail Silabus & Kosakata --}}
                                        @if ($material->syllabus || $material->cco || $material->lesson_plan || $material->vocabularies->count() || $material->box_code || $material->equipment_list)
                                            <div class="collapse mt-3 pt-3 border-top" id="detailSyllabus_{{ $material->id }}">
                                                @if ($material->syllabus)
                                                    <div class="mb-3">
                                                        <div class="fw-semibold text-muted small mb-1">Silabus Pembelajaran:</div>
                                                        <div class="p-3 bg-light rounded-3 text-secondary small">
                                                            {!! nl2br(e($material->syllabus)) !!}
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="row g-2 mb-2">
                                                    @if ($material->cco)
                                                        <div class="col-md-4">
                                                            <div class="p-2 bg-light rounded-2 small">
                                                                <strong class="text-muted d-block">CCO:</strong>
                                                                {!! nl2br(e($material->cco)) !!}
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if ($material->lesson_plan)
                                                        <div class="col-md-4">
                                                            <div class="p-2 bg-light rounded-2 small">
                                                                <strong class="text-muted d-block">Lesson Plan:</strong>
                                                                {!! nl2br(e($material->lesson_plan)) !!}
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if ($material->activity)
                                                        <div class="col-md-4">
                                                            <div class="p-2 bg-light rounded-2 small">
                                                                <strong class="text-muted d-block">Aktivitas:</strong>
                                                                {!! nl2br(e($material->activity)) !!}
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if ($material->box_code || $material->equipment_list)
                                                    <div class="p-2 bg-light rounded-2 small mb-2">
                                                        <strong class="text-muted">Perlengkapan / Box:</strong>
                                                        @if ($material->box_code)
                                                            <span class="badge bg-dark ms-1">{{ $material->box_code }}</span>
                                                        @endif
                                                        <span class="ms-1">{{ $material->equipment_list }}</span>
                                                    </div>
                                                @endif

                                                @if ($material->vocabularies->count())
                                                    <div class="mt-2">
                                                        <div class="fw-semibold text-muted small mb-1">Daftar Kosakata:</div>
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
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-book-open fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold text-secondary">Belum ada materi pembelajaran</h5>
                <p class="text-muted small mb-0">
                    Materi belum diunggah oleh Kurikulum untuk kelas yang Anda ajar.
                </p>
            </div>
        </div>
    @endif

    <div class="text-muted small mt-4">
        <i class="fa-solid fa-info-circle me-1"></i>
        Materi & Silabus dikelola oleh Kurikulum. Guru dapat melihat dan mengunduh materi sesuai program dan kelas yang diajar plus materi umum.
    </div>

</div>

@endsection

@push('scripts')
<script>
function filterRealtime() {
    const q = document.getElementById('searchMateriInput').value.toLowerCase().trim();
    const prog = document.getElementById('programSelectFilter').value;

    document.querySelectorAll('.program-accordion-block').forEach(function (block) {
        const blockProg = block.dataset.programName;
        let progMatches = (!prog || blockProg === prog);
        let visibleCount = 0;

        block.querySelectorAll('.material-item-row').forEach(function (row) {
            const title = row.dataset.title || '';
            const syllabus = row.dataset.syllabus || '';
            let qMatches = (!q || title.includes(q) || syllabus.includes(q));

            if (progMatches && qMatches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        block.style.display = (visibleCount > 0) ? '' : 'none';
    });
}

function selectProgramPill(progName, btn) {
    document.querySelectorAll('#programPills button').forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-dark');

    const select = document.getElementById('programSelectFilter');
    if (select) {
        select.value = progName;
    }
    filterRealtime();
}
</script>
@endpush
