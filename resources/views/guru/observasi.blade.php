@extends('layouts.guru')

@section('title', 'Hasil Observasi Mengajar')

@section('content')
<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-magnifying-glass-chart text-warning me-2"></i> Hasil Observasi Mengajar
            </h4>
            <p class="text-muted small mb-0">Catatan evaluasi berkala, kesesuaian SOP, dan saran perbaikan mengajar dari Tim Kurikulum.</p>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Observasi</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ $totalCount }}</div>
                    </div>
                    <div class="rounded-3 bg-light p-3 text-secondary">
                        <i class="fa-solid fa-clipboard-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Sesuai SOP</div>
                        <div class="fs-4 fw-bold text-success mt-1">{{ $totalSesuai }}</div>
                    </div>
                    <div class="rounded-3 bg-success-subtle p-3 text-success">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Perlu Perbaikan</div>
                        <div class="fs-4 fw-bold text-warning mt-1">{{ $totalPerbaikan }}</div>
                    </div>
                    <div class="rounded-3 bg-warning-subtle p-3 text-warning">
                        <i class="fa-solid fa-circle-exclamation fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Tidak Sesuai SOP</div>
                        <div class="fs-4 fw-bold text-danger mt-1">{{ $totalTidak }}</div>
                    </div>
                    <div class="rounded-3 bg-danger-subtle p-3 text-danger">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER PILLS --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('teacher.observasi') }}" class="btn btn-sm rounded-pill {{ !$sopCompliance ? 'btn-dark' : 'btn-outline-secondary' }}">
            Semua ({{ $totalCount }})
        </a>
        <a href="{{ route('teacher.observasi', ['sop_compliance' => 'Sesuai SOP']) }}" class="btn btn-sm rounded-pill {{ $sopCompliance === 'Sesuai SOP' ? 'btn-success text-white' : 'btn-outline-secondary' }}">
            Sesuai SOP ({{ $totalSesuai }})
        </a>
        <a href="{{ route('teacher.observasi', ['sop_compliance' => 'Perlu Perbaikan']) }}" class="btn btn-sm rounded-pill {{ $sopCompliance === 'Perlu Perbaikan' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}">
            Perlu Perbaikan ({{ $totalPerbaikan }})
        </a>
        <a href="{{ route('teacher.observasi', ['sop_compliance' => 'Tidak Sesuai SOP']) }}" class="btn btn-sm rounded-pill {{ $sopCompliance === 'Tidak Sesuai SOP' ? 'btn-danger text-white' : 'btn-outline-secondary' }}">
            Tidak Sesuai SOP ({{ $totalTidak }})
        </a>
    </div>

    {{-- DAFTAR OBSERVASI --}}
    <div class="row g-4">
        @forelse ($observations as $obs)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge {{ $obs->sop_badge }} px-3 py-2 rounded-pill fw-semibold">
                                {{ $obs->sop_compliance }}
                            </span>
                            <h5 class="fw-bold text-dark mt-2 mb-1">
                                <i class="fa-solid fa-chalkboard text-warning me-2"></i> {{ $obs->class->class_name }}
                            </h5>
                            <small class="text-muted">
                                <i class="fa-solid fa-calendar me-1"></i> {{ $obs->observation_date->translatedFormat('l, d F Y') }}
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Diobservasi oleh:</small>
                            <span class="fw-semibold text-dark small">{{ $obs->curriculumUser->display_name ?? 'Kurikulum' }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="small fw-bold text-dark mb-1">
                            <i class="fa-solid fa-magnifying-glass text-warning me-1"></i> Hasil Observasi Kelas:
                        </div>
                        <div class="small text-secondary" style="white-space: pre-line;">{{ $obs->observation_result }}</div>
                    </div>

                    <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 mt-auto">
                        <div class="small fw-bold text-success-emphasis mb-1">
                            <i class="fa-solid fa-lightbulb text-warning me-1"></i> Feedback & Arahan Kurikulum:
                        </div>
                        <div class="small text-dark" style="white-space: pre-line;">{{ $obs->feedback_solution }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
                    <i class="fa-solid fa-magnifying-glass-chart fs-1 text-secondary mb-3 d-block"></i>
                    Belum ada hasil observasi mengajar dari tim Kurikulum untuk kelas Anda.
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $observations->links() }}
    </div>

</div>
@endsection

