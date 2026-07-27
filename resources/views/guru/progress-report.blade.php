@extends('layouts.guru')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">Progress Report</h2>
            <p class="text-muted">
                Download template dari Kurikulum kemudian upload laporan perkembangan setiap siswa.
            </p>
        </div>
    </div>

    {{-- PILIH KELAS --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">

            <label class="form-label fw-semibold">
                Pilih Kelas
            </label>

            <select class="form-select">
                <option>HSK 3 (9A) - Regular</option>
                <option>Hudie 2A</option>
                <option>Maochong 3B</option>
                <option>Private Jason</option>
            </select>

            <small class="text-muted">
                Template dan daftar siswa akan mengikuti kelas yang dipilih.
            </small>

        </div>
    </div>

    {{-- TEMPLATE --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between">

                <div>

                    <h5 class="fw-bold">
                        Template Progress Report
                    </h5>

                    <div class="text-muted">
                        Siklus 3 Bulan
                    </div>

                </div>

                <div>

                    <a href="#"
                       class="btn btn-warning">

                        <i class="fa fa-download"></i>

                        Download Template

                    </a>

                </div>

            </div>

            <hr>

            <h6 class="fw-bold mb-3">
                Upload Progress Report Per Siswa
            </h6>

            {{-- SISWA 1 --}}

            <div class="row align-items-center py-3 border-bottom">

                <div class="col-md-4">

                    <strong>Sari Dewi</strong>

                    <div class="text-muted small">

                        Siklus 1 & 2 sudah upload

                    </div>

                </div>

                <div class="col-md-3">

                    <span class="badge bg-success">
                        Siklus 1-2
                    </span>

                </div>

                <div class="col-md-3">

                    <input
                        type="file"
                        class="form-control">

                </div>

                <div class="col-md-2 text-end">

                    <button class="btn btn-primary">

                        Upload

                    </button>

                </div>

            </div>

            {{-- SISWA 2 --}}

            <div class="row align-items-center py-3 border-bottom">

                <div class="col-md-4">

                    <strong>Reza P</strong>

                    <div class="text-muted small">

                        Siklus 1 & 2 sudah upload

                    </div>

                </div>

                <div class="col-md-3">

                    <span class="badge bg-success">
                        Siklus 1-2
                    </span>

                </div>

                <div class="col-md-3">

                    <input
                        type="file"
                        class="form-control">

                </div>

                <div class="col-md-2 text-end">

                    <button class="btn btn-primary">

                        Upload

                    </button>

                </div>

            </div>

            {{-- SISWA 3 --}}

            <div class="row align-items-center py-3">

                <div class="col-md-4">

                    <strong>Dinda K</strong>

                    <div class="text-muted small">

                        Belum upload

                    </div>

                </div>

                <div class="col-md-3">

                    <span class="badge bg-warning text-dark">
                        Belum Upload
                    </span>

                </div>

                <div class="col-md-3">

                    <input
                        type="file"
                        class="form-control">

                </div>

                <div class="col-md-2 text-end">

                    <button class="btn btn-primary">

                        Upload

                    </button>

                </div>

            </div>

            <hr>

            <div class="d-flex gap-3">

                <button class="btn btn-success">

                    Kirim Semua ke Kurikulum

                </button>

                <button class="btn btn-outline-success">

                    Kirim via WhatsApp

                </button>

            </div>

        </div>

    </div>

    {{-- RIWAYAT --}}

    <div class="card shadow-sm border-0 rounded-4">

        <div class="card-header bg-white fw-bold">

            Riwayat Progress Report

        </div>

        <div class="list-group list-group-flush">

            <div class="list-group-item">

                <div class="d-flex justify-content-between">

                    <div>

                        <strong>

                            HSK 3 (9A)

                        </strong>

                        <br>

                        <small class="text-muted">

                            Siklus 1 & 2

                        </small>

                    </div>

                    <span class="badge bg-success">

                        Direview

                    </span>

                </div>

            </div>

            <div class="list-group-item">

                <div class="d-flex justify-content-between">

                    <div>

                        <strong>

                            Private Jason

                        </strong>

                        <br>

                        <small class="text-muted">

                            Siklus 1

                        </small>

                    </div>

                    <span class="badge bg-success">

                        Direview

                    </span>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection