@extends('layouts.guru')

@section('title','Materi & Silabus')

@section('content')

<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Materi & Silabus</h2>
        <p class="text-muted mb-0">
            Perpustakaan referensi kurikulum — lihat silabus, kosakata resmi & materi ACC dari semua guru per kategori kelas
        </p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter Kategori --}}
    <div class="mb-3 d-flex flex-wrap gap-2" id="categoryFilterTabs">
        <button class="btn btn-dark rounded-pill btn-sm px-3" data-cat="all" onclick="filterByCategory('all', this)">
            Semua
        </button>
        @foreach ($categories as $cat)
            <button class="btn btn-outline-secondary rounded-pill btn-sm px-3" data-cat="{{ $cat }}" onclick="filterByCategory('{{ $cat }}', this)">
                {{ $cat }}
            </button>
        @endforeach
    </div>

    {{-- List Materi --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-0" id="materialListWrapper">

            @forelse ($materials as $material)

                <div class="d-flex justify-content-between align-items-center p-4 {{ !$loop->last ? 'border-bottom' : '' }}"
                     data-category="{{ $material->level->category->category_name ?? '' }}">

                    <div>

                        <h5 class="fw-semibold mb-1">
                            {{ $material->title }}
                        </h5>

                        <small class="text-muted">
                            V{{ $material->meeting_number }}
                            •
                            {{ $material->uploader->username ?? 'Kurikulum' }}
                        </small>

                    </div>

                    <div class="d-flex gap-2">

                        <a href="{{ route('teacher.materi.show', $material) }}"
                           target="_blank"
                           class="btn btn-outline-dark rounded-3">
                            Lihat
                        </a>

                        <a href="{{ route('teacher.materi.download', $material) }}"
                           class="btn btn-warning rounded-3 fw-semibold">
                            Download
                        </a>

                    </div>

                </div>

            @empty

                <div class="text-center text-muted py-5">
                    Belum ada materi yang diupload Kurikulum.
                </div>

            @endforelse

        </div>

    </div>

    <p class="text-muted small mb-4">
        Semua materi, silabus & kosakata pada halaman ini diupload oleh Kepala Kurikulum.
        Guru hanya dapat melihat dan mengunduh sebagai acuan mengajar.
    </p>

    {{-- Detail Silabus per Materi --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body">

            <div class="d-flex align-items-center gap-2 mb-4">
                <h5 class="fw-bold mb-0">Silabus & Kosakata</h5>
                <span class="badge bg-secondary">Guru hanya melihat</span>
            </div>

            @forelse ($materials as $material)

                <div class="mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">

                    <h6 class="fw-semibold">
                        {{ $material->title }}
                    </h6>

                    @if ($material->syllabus)
                        <div class="border rounded p-3 bg-light mb-3">
                            {!! nl2br(e($material->syllabus)) !!}
                        </div>
                    @endif

                    @foreach ([
                        'cco' => 'CCO',
                        'lesson_plan' => 'Lesson Plan',
                        'activity' => 'Aktivitas',
                    ] as $field => $label)
                        @if ($material->{$field})
                            <div class="mb-3">
                                <div class="fw-semibold small text-muted mb-1">{{ $label }}</div>
                                <div class="border rounded p-3 bg-light">
                                    {!! nl2br(e($material->{$field})) !!}
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if ($material->box_code || $material->equipment_list)
                        <div class="mb-3">
                            <div class="fw-semibold small text-muted mb-1">
                                Box Perlengkapan
                                @if ($material->box_code)
                                    <span class="badge bg-dark ms-1">{{ $material->box_code }}</span>
                                @endif
                            </div>
                            @if ($material->equipment_list)
                                <div class="border rounded p-3 bg-light">
                                    {!! nl2br(e($material->equipment_list)) !!}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($material->vocabularies->count())
                        <div class="row">
                            @foreach ($material->vocabularies as $vocab)
                                <div class="col-md-6 mb-1">
                                    <strong>{{ $vocab->hanzi }}</strong>
                                    <span class="text-muted">({{ $vocab->pinyin }})</span>
                                    - {{ $vocab->meaning }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>

            @empty
                <div class="text-center text-muted py-4">
                    Belum ada silabus.
                </div>
            @endforelse

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
function filterByCategory(cat, btn) {
    document.querySelectorAll('#categoryFilterTabs button').forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-dark');

    document.querySelectorAll('#materialListWrapper [data-category]').forEach(row => {
        row.style.display = (cat === 'all' || row.dataset.category === cat) ? '' : 'none';
    });
}
</script>
@endpush