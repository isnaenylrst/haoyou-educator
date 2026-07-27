@extends('layouts.guru')

@section('title','Attendance & Journal')

@section('content')

<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Attendance & Session Journal
            </h2>

            <p class="text-muted">
                Isi absensi dan jurnal mengajar setiap selesai kelas.
            </p>

        </div>

        <input type="date"
               class="form-control"
               style="width:180px;">

    </div>

    {{-- CARD SUMMARY --}}

    <div class="row mb-4">

        <div class="col-md-3">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        SESI HARI INI
                    </small>

                    <h2 class="fw-bold mt-2">
                        4
                    </h2>

                    <small class="text-muted">
                        Terjadwal
                    </small>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        SUDAH DIISI
                    </small>

                    <h2 class="fw-bold text-success mt-2">
                        1
                    </h2>

                    <small class="text-success">
                        Jurnal selesai
                    </small>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        BELUM DIISI
                    </small>

                    <h2 class="fw-bold text-warning mt-2">
                        3
                    </h2>

                    <small class="text-warning">
                        Perlu diisi
                    </small>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        TOTAL HADIR
                    </small>

                    <h2 class="fw-bold mt-2">
                        9
                    </h2>

                    <small class="text-muted">
                        Siswa hari ini
                    </small>

                </div>

            </div>

        </div>

    </div>

    {{-- FILTER --}}

    <div class="mb-3">

        <button class="btn btn-dark btn-sm rounded-pill">
            Semua
        </button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">
            Belum Diisi
        </button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">
            Sudah Diisi
        </button>

        <button class="btn btn-outline-secondary btn-sm rounded-pill">
            Dikunci
        </button>

    </div>

    {{-- CARD ABSENSI --}}

    <div class="card shadow-sm border-0 rounded-4">

        <div class="card-body">

            <div class="d-flex justify-content-between">

                <div>

                    <h5 class="fw-bold">

                        Private — Jason (Feixiang)

                    </h5>

                    <small class="text-muted">

                        10.00 - 11.00

                    </small>

                </div>

                <span class="badge bg-warning text-dark">

                    Perlu Diisi

                </span>

            </div>

            <hr>

            <h6 class="fw-semibold">
                Kehadiran
            </h6>

            <div class="mb-3">

                <button class="btn btn-outline-success btn-sm">

                    ✓ Jason Kurniawan

                </button>

            </div>

            <h6 class="fw-semibold">
                Poin Sesi
            </h6>

            <div class="mb-3">

                <div class="form-check form-check-inline">

                    <input class="form-check-input"
                           type="radio"
                           name="point">

                    <label class="form-check-label">

                        🟡 Kuning (10)

                    </label>

                </div>

                <div class="form-check form-check-inline">

                    <input class="form-check-input"
                           type="radio"
                           name="point">

                    <label class="form-check-label">

                        🔴 Merah (5)

                    </label>

                </div>

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Materi yang diajarkan

                </label>

                <input type="text"
                       class="form-control"
                       placeholder="Contoh : Latihan angka 1-100">

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Aktivitas Pembelajaran

                </label>

                <textarea
                    class="form-control"
                    rows="3"
                    placeholder="Contoh : Flashcard, bernyanyi, tanya jawab"></textarea>

            </div>

            <button class="btn btn-primary">

                <i class="fas fa-lock me-2"></i>

                Simpan & Kunci Jurnal

            </button>

        </div>

    </div>

</div>

@endsection