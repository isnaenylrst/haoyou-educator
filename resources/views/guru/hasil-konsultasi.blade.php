@extends('layouts.guru')

@section('title', 'Hasil Konsultasi Kurikulum')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-comments text-warning me-2"></i> Hasil Konsultasi Kurikulum</h3>
            <p class="text-muted mb-0">
                Catatan evaluasi, poin perbaikan, dan rencana pengembangan hasil sesi konsultasi bersama Tim Kurikulum.
            </p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('teacher.hasil-konsultasi') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" title="Dari Tanggal" placeholder="Dari Tanggal">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" title="Sampai Tanggal" placeholder="Sampai Tanggal">
                </div>
                <div class="col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari topik atau catatan...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-3">Filter</button>
                    @if(request('date_from') || request('date_to') || request('q'))
                        <a href="{{ route('teacher.hasil-konsultasi') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR HASIL KONSULTASI (TIMELINE / CARDS) --}}
    @if ($consultations->isEmpty())
        <div class="card shadow-sm border-0 rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fa-solid fa-comments fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Belum Ada Hasil Konsultasi</h5>
                <p class="text-muted">Hasil evaluasi atau konsultasi dari Tim Kurikulum akan tercatat di sini.</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($consultations as $c)
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="badge bg-warning bg-opacity-10 text-dark fw-bold px-3 py-2 rounded-pill me-2">
                                    <i class="fa-regular fa-calendar me-1"></i> {{ $c->consultation_date?->format('d F Y') }}
                                </span>
                                <span class="text-muted small">
                                    <i class="fa-solid fa-user-tie me-1"></i> Konsultasi bersama: <strong>{{ $c->curriculumUser?->name ?? 'Tim Kurikulum' }}</strong>
                                </span>
                            </div>
                            <div>
                                @if($c->observation)
                                    <span class="badge {{ $c->observation->sop_badge }} px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tindak Lanjut Observasi: {{ $c->observation->sop_compliance }} ({{ $c->observation->class?->class_name ?? 'Kelas' }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <h4 class="fw-bold text-dark mb-3">{{ $c->topic }}</h4>

                            <div class="row g-3">
                                {{-- POIN PERBAIKAN --}}
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light h-100 border">
                                        <h6 class="fw-bold text-danger mb-3">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Poin-Poin yang Perlu Diperbaiki:
                                        </h6>
                                        @if(is_array($c->improvement_points) && count($c->improvement_points) > 0)
                                            <ul class="list-unstyled mb-0">
                                                @foreach($c->improvement_points as $point)
                                                    <li class="mb-2 d-flex align-items-start gap-2">
                                                        <i class="fa-solid fa-circle-check text-warning mt-1"></i>
                                                        <span class="text-dark">{{ $point }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted mb-0 small">Tidak ada poin perbaikan spesifik.</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- TINDAK LANJUT --}}
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light h-100 border">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fa-solid fa-list-check me-1"></i> Rencana Tindak Lanjut & Solusi:
                                        </h6>
                                        <p class="text-dark mb-0" style="white-space: pre-line;">
                                            {{ $c->follow_up ?: 'Belum ada catatan tindak lanjut khusus.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($c->file_path)
                                <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="text-muted small">
                                        <i class="fa-solid fa-paperclip me-1"></i> Berkas Lampiran / Notulensi: <strong>{{ $c->file_name }}</strong>
                                    </div>
                                    <a href="{{ route('teacher.hasil-konsultasi.download', $c->id) }}" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
                                        <i class="fa-solid fa-download me-1"></i> Unduh Lampiran ({{ strtoupper($c->file_extension ?: 'FILE') }})
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $consultations->links() }}
        </div>
    @endif

</div>
@endsection

