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

    {{-- Card --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-0">

            {{-- SOP 1 --}}
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">

                <div>

                    <h5 class="fw-semibold mb-1">
                        SOP Pengajaran Umum
                    </h5>

                    <small class="text-muted">
                        Diperbarui 2 minggu lalu • PDF
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-dark rounded-3">
                        <i class="fas fa-download me-2"></i>
                        Buka / Download
                    </button>

                </div>

            </div>

            {{-- SOP 2 --}}
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">

                <div>

                    <h5 class="fw-semibold mb-1">
                        SOP Penilaian & Progress Report
                    </h5>

                    <small class="text-muted">
                        Diperbarui 1 bulan lalu • PDF
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-dark rounded-3">
                        <i class="fas fa-download me-2"></i>
                        Buka / Download
                    </button>

                </div>

            </div>

            {{-- SOP 3 --}}
            <div class="d-flex justify-content-between align-items-center p-4">

                <div>

                    <h5 class="fw-semibold mb-1">
                        Fundamental Mengajar & Reward Point
                    </h5>

                    <small class="text-muted">
                        Materi pembekalan awal • PDF
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-dark rounded-3">
                        <i class="fas fa-download me-2"></i>
                        Buka / Download
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection