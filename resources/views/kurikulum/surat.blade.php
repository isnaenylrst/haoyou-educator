@extends('layouts.kurikulum')

@section('title','Pemberitahuan Surat')

@section('content')

<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Pemberitahuan Surat</h2>
        <p class="text-muted mb-0">
            Upload file surat & tentukan penerima (semua guru atau guru tertentu)
        </p>
    </div>

    {{-- Statistik --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-uppercase text-secondary fw-bold">
                        Total Terkirim
                    </small>

                    <h1 class="fw-bold mb-0 mt-2">
                        24
                    </h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-uppercase text-secondary fw-bold">
                        Bulan Ini
                    </small>

                    <h1 class="fw-bold text-success mb-0 mt-2">
                        6
                    </h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-uppercase text-secondary fw-bold">
                        Belum Dibaca
                    </small>

                    <h1 class="fw-bold text-warning mb-0 mt-2">
                        3
                    </h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-uppercase text-secondary fw-bold">
                        Jenis Surat
                    </small>

                    <h5 class="fw-bold mt-2 mb-0">
                        Libur / Dinas / LoA
                    </h5>
                </div>
            </div>
        </div>

    </div>

    {{-- Form Surat --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body p-4">

            <h3 class="fw-bold mb-4">
                Buat Surat Baru
            </h3>

            <form action="#" method="POST" enctype="multipart/form-data">

                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Jenis Surat
                    </label>

                    <select class="form-select" name="jenis_surat">
                        <option>Surat Libur</option>
                        <option>Surat Dinas</option>
                        <option>LoA</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Penerima
                    </label>

                    <select class="form-select" name="penerima">
                        <option>Semua Guru</option>
                        <option>Guru Tertentu</option>
                    </select>
                </div>

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Judul Surat
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="judul"
                        placeholder="Contoh: Libur Nasional 17 Agustus 2026">

                </div>

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Isi Surat
                    </label>

                    <textarea
                        class="form-control"
                        rows="4"
                        name="isi"
                        placeholder="Tuliskan isi pemberitahuan di sini..."></textarea>

                </div>

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Upload File Surat (PDF/Gambar)
                    </label>

                    <input
                        type="file"
                        class="form-control"
                        name="file">

                </div>

                <button class="btn btn-success px-4">
                    Kirim Surat
                </button>

            </form>

        </div>

    </div>

    {{-- Riwayat Surat --}}
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <h3 class="fw-bold mb-4">
                Riwayat Surat Terkirim
            </h3>

            <div class="list-group list-group-flush">

                {{-- Surat 1 --}}
                <div class="list-group-item py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="fw-bold mb-1">
                                Libur Nasional 17 Agustus 2026
                            </h6>

                            <small class="text-muted">
                                Semua Guru • 2 hari lalu • file terlampir
                            </small>

                        </div>

                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                            Surat Libur
                        </span>

                    </div>

                </div>

                {{-- Surat 2 --}}
                <div class="list-group-item py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="fw-bold mb-1">
                                LoA Guru Tetap 2026/2027
                            </h6>

                            <small class="text-muted">
                                Rina Wulandari • 1 minggu lalu • file terlampir
                            </small>

                        </div>

                        <span class="badge rounded-pill bg-success px-3 py-2">
                            LoA
                        </span>

                    </div>

                </div>

                {{-- Surat 3 --}}
                <div class="list-group-item py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="fw-bold mb-1">
                                Surat Dinas Workshop Kurikulum
                            </h6>

                            <small class="text-muted">
                                Semua Guru • 2 minggu lalu • file terlampir
                            </small>

                        </div>

                        <span class="badge rounded-pill bg-primary px-3 py-2">
                            Dinas
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection