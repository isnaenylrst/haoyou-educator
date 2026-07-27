@extends('layouts.guru')

@section('title','Kelas')

@section('content')

<div class="container-fluid py-3">

    {{-- ================= HEADER ================= --}}
    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            Kelas
        </h2>

        <p class="text-muted">
            Kelas baru menggunakan materi yang telah disiapkan Kurikulum.
            Guru membuat Lesson Plan & PPT kemudian mengirimnya untuk direview.
        </p>

    </div>

    {{-- ==================== FORM UPLOAD ==================== --}}

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <h4 class="fw-bold mb-1">

                Buat LP & PPT Kelas Baru

            </h4>

            <p class="text-muted mb-4">

                Materi, silabus dan kosakata berasal dari Kurikulum.
                Guru hanya membuat Lesson Plan serta PPT.

            </p>

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">

                        Kategori

                    </label>

                    <select class="form-select">

                        <option>Maochong</option>
                        <option>Jianer</option>
                        <option>Hudie</option>
                        <option>Feixiang</option>
                        <option>HSK</option>

                    </select>

                </div>

                <div class="col-md-8 mb-3">

                    <label class="form-label">

                        Pilih Materi Kurikulum

                    </label>

                    <select class="form-select">

                        <option>Unit 5 - Keluarga</option>

                        <option>Unit 6 - Aktivitas Sehari-hari</option>

                        <option>Unit 7 - Hobi</option>

                    </select>

                </div>

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Upload Lesson Plan (PDF)

                </label>

                <input
                    type="file"
                    class="form-control">

            </div>

            <div class="mb-4">

                <label class="form-label">

                    Upload PPT

                </label>

                <input
                    type="file"
                    class="form-control">

            </div>

            <button class="btn btn-success px-4">

                <i class="fas fa-upload me-2"></i>

                Upload & Kirim Review

            </button>

        </div>

    </div>

    {{-- =================== PROGRESS =================== --}}

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between">

                <div>

                    <h5 class="fw-bold">

                        Progress LP — Jianer 2B

                    </h5>

                    <small class="text-muted">

                        Konsultasi wajib selesai sebelum kelas berjalan.

                    </small>

                </div>

                <span class="badge bg-danger">

                    H-14

                </span>

            </div>

            <hr>

            <div class="d-flex justify-content-between mb-3">

                <div>

                    <strong>

                        1. Konsultasi Kurikulum

                    </strong>

                    <br>

                    <small class="text-muted">

                        5 Juli 2026

                    </small>

                </div>

                <span class="badge bg-success">

                    Selesai

                </span>

            </div>

            <div class="d-flex justify-content-between mb-3">

                <div>

                    <strong>

                        2. Konsultasi Direktur

                    </strong>

                    <br>

                    <small class="text-muted">

                        Belum dijadwalkan

                    </small>

                </div>

                <button class="btn btn-warning btn-sm">

                    Ajukan Jadwal

                </button>

            </div>

            <div class="d-flex justify-content-between mb-3">

                <div>

                    <strong>

                        3. Revisi LP

                    </strong>

                </div>

                <span class="badge bg-secondary">

                    Menunggu

                </span>

            </div>

            <div class="d-flex justify-content-between">

                <div>

                    <strong>

                        4. Upload PPT Final

                    </strong>

                </div>

                <span class="badge bg-secondary">

                    Belum Mulai

                </span>

            </div>

        </div>

    </div>

    {{-- ==================== DAFTAR KELAS ==================== --}}

    <div class="mb-3">

        <button class="btn btn-dark btn-sm rounded-pill">Semua</button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">Maochong</button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">Jianer</button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">Hudie</button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">Feixiang</button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">HSK</button>

    </div>

    <div class="card border-0 shadow-sm rounded-4">

        <div class="list-group list-group-flush">

            @foreach([

                ['HSK 3','ACC',true],

                ['Maochong 3A','ACC',true],

                ['Hudie 1A','ACC',true],

                ['Jianer 2B','Belum ACC',false],

                ['Feixiang 1C','Belum ACC',false]

            ] as $kelas)

            <div class="list-group-item py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="fw-semibold">

                            {{$kelas[0]}}

                        </h6>

                        <small class="text-muted">

                            LP & PPT

                        </small>

                    </div>

                    <div>

                        @if($kelas[2])

                            <span class="badge bg-success">

                                ACC

                            </span>

                            <button class="btn btn-outline-dark btn-sm">

                                Crosscheck

                            </button>

                        @else

                            <span class="badge bg-warning text-dark">

                                Belum ACC

                            </span>

                            <button class="btn btn-warning btn-sm">

                                Upload Ulang

                            </button>

                        @endif

                    </div>

                </div>

            </div>

            @endforeach

        </div>

    </div>

</div>

@endsection