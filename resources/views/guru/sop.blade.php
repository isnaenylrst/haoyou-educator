@extends('layouts.guru')

@section('title','SOP')

@section('content')

<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">SOP</h2>
        <p class="text-muted">
            Standar operasional prosedur — bisa dibaca sewaktu-waktu sebagai panduan mengajar
        </p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Card --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-0">

            @forelse ($sops as $sop)

                <div class="d-flex justify-content-between align-items-center p-4 {{ !$loop->last ? 'border-bottom' : '' }}">

                    <div>

                        <h5 class="fw-semibold mb-1">
                            {{ $sop->title }}
                        </h5>

                        <small class="text-muted">
                            Diperbarui
                            {{ optional($sop->uploaded_at ?? $sop->updated_at)->diffForHumans() ?? '-' }}
                            • PDF
                        </small>

                    </div>

                    <div class="d-flex gap-2">

                        <a href="{{ route('teacher.sop.show', $sop) }}"
                           target="_blank"
                           class="btn btn-outline-dark rounded-3">
                            <i class="fas fa-eye me-2"></i>
                            Buka
                        </a>

                        <a href="{{ route('teacher.sop.download', $sop) }}"
                           class="btn btn-outline-dark rounded-3">
                            <i class="fas fa-download me-2"></i>
                            Download
                        </a>

                    </div>

                </div>

            @empty

                <div class="text-center text-muted py-5">
                    Belum ada SOP yang diupload Kurikulum.
                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection