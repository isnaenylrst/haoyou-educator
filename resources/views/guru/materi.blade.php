@extends('layouts.guru')

@section('title','Materi & Silabus')

@section('content')

<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="mb-3">
        <h2 class="fw-bold mb-1">
            Materi & Silabus
        </h2>

        <p class="text-muted mb-3">
            Perpustakaan referensi kurikulum — lihat silabus, kosakata resmi & materi ACC dari semua guru per kategori kelas
        </p>

        {{-- Filter --}}
        <div class="d-flex flex-wrap gap-2">

            <button class="btn btn-dark btn-sm rounded-pill">Semua</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Maochong</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Jianer</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Hudie</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Feixiang</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">HSK</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Private</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Bisnis</button>

            <button class="btn btn-outline-secondary btn-sm rounded-pill">Tradisional / TOCFL</button>

        </div>

    </div>

    {{-- ===================== LIST MATERI ===================== --}}

    <div class="card shadow-sm border-0 rounded-4 mb-4">

        <div class="list-group list-group-flush">

            <div class="list-group-item py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="fw-semibold mb-1">
                            HSK 3 (9A) — Silabus & Kosakata Lengkap
                        </h6>

                        <small class="text-muted">
                            V4 • Kurikulum
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-dark btn-sm">
                            Lihat
                        </button>

                        <button class="btn btn-warning btn-sm">
                            Download
                        </button>

                    </div>

                </div>

            </div>

            <div class="list-group-item py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="fw-semibold mb-1">
                            Maochong 3A — Unit 5: Keluarga
                        </h6>

                        <small class="text-muted">
                            V2 • Kurikulum
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-dark btn-sm">
                            Lihat
                        </button>

                        <button class="btn btn-warning btn-sm">
                            Download
                        </button>

                    </div>

                </div>

            </div>

            <div class="list-group-item py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="fw-semibold mb-1">
                            Hudie 1A — Unit 1: Salam
                        </h6>

                        <small class="text-muted">
                            V3 • Kurikulum
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-dark btn-sm">
                            Lihat
                        </button>

                        <button class="btn btn-warning btn-sm">
                            Download
                        </button>

                    </div>

                </div>

            </div>

            <div class="list-group-item py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="fw-semibold mb-1">
                            Bisnis — Unit 1: Perkenalan di Kantor
                        </h6>

                        <small class="text-muted">
                            V1 • Kurikulum
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-dark btn-sm">
                            Lihat
                        </button>

                        <button class="btn btn-warning btn-sm">
                            Download
                        </button>

                    </div>

                </div>

            </div>

            <div class="list-group-item py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="fw-semibold mb-1">
                            Mandarin Tradisional / TOCFL — Unit 1
                        </h6>

                        <small class="text-muted">
                            V1 • Kurikulum
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-dark btn-sm">
                            Lihat
                        </button>

                        <button class="btn btn-warning btn-sm">
                            Download
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <small class="text-muted">

        Semua materi, silabus & kosakata pada halaman ini diupload oleh Kepala Kurikulum.

        Guru hanya dapat melihat dan mengunduh sebagai acuan mengajar.

    </small>

    {{-- ================= SILABUS ================== --}}

    <div class="card shadow-sm border-0 rounded-4 mt-4">

        <div class="card-body">

            <h4 class="fw-bold">
                Silabus — Kompetensi & Target Pencapaian
            </h4>

            <span class="badge bg-secondary">
                Guru hanya melihat
            </span>

            <p class="text-muted mt-3">

                Maochong 3A, Unit 5 (Keluarga)

            </p>

            <table class="table mt-3">

                <thead>

                <tr>

                    <th>Kompetensi</th>

                    <th>Tema Mid</th>

                    <th>Target</th>

                </tr>

                </thead>

                <tbody>

                <tr>

                    <td>

                        Menyebutkan anggota keluarga dalam Bahasa Mandarin

                    </td>

                    <td>

                        Keluarga & Kekerabatan

                    </td>

                    <td>

                        80% siswa mampu menghafal 12 kosakata

                    </td>

                </tr>

                </tbody>

            </table>

        </div>

    </div>

    {{-- =================== KOSAKATA =================== --}}

    <div class="card shadow-sm border-0 rounded-4 mt-4">

        <div class="card-body">

            <h4 class="fw-bold">

                Silabus — Kosakata yang Diajarkan

            </h4>

            <p class="text-muted">

                Maochong 3A Unit 5

            </p>

            <table class="table table-bordered align-middle">

                <thead>

                <tr>

                    <th>Pertemuan</th>

                    <th>Hanzi</th>

                    <th>Pinyin</th>

                    <th>Arti</th>

                </tr>

                </thead>

                <tbody>

                <tr>

                    <td>Pertemuan 1</td>

                    <td>爸爸</td>

                    <td>bàba</td>

                    <td>Ayah</td>

                </tr>

                <tr>

                    <td></td>

                    <td>妈妈</td>

                    <td>māma</td>

                    <td>Ibu</td>

                </tr>

                <tr>

                    <td>Pertemuan 2</td>

                    <td>哥哥</td>

                    <td>gēge</td>

                    <td>Kakak laki-laki</td>

                </tr>

                <tr>

                    <td></td>

                    <td>姐姐</td>

                    <td>jiějie</td>

                    <td>Kakak perempuan</td>

                </tr>

                <tr>

                    <td>Pertemuan 3</td>

                    <td colspan="3" class="text-muted">

                        Belum dijadwalkan Kurikulum

                    </td>

                </tr>

                </tbody>

            </table>

            <button class="btn btn-warning">

                <i class="fas fa-file-pdf me-2"></i>

                Lihat PDF

            </button>

        </div>

    </div>

</div>

@endsection