@extends('layouts.kurikulum')

@section('title','Materi & Silabus')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Materi & Silabus</h3>

        <small class="text-muted">
            Materi, silabus, kosakata seluruhnya dibuat & diupload oleh Kurikulum —
            guru hanya melihat, download, dan membuat PPT mengikuti acuan ini.
        </small>
    </div>

    {{-- =======================
        Upload Materi
    ======================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <h4 class="fw-bold mb-2">
                Upload Materi / Silabus Baru
            </h4>

            <small class="text-muted">
                Materi yang diupload di sini langsung menjadi acuan materi bagi semua guru.
            </small>

            <form class="mt-3">

                <div class="mb-3">

                    <label class="form-label">
                        Kategori Kelas
                    </label>

                    <select class="form-select">
                        <option>Maochong</option>
                        <option>Jianer</option>
                        <option>Hudie</option>
                        <option>Feixiang</option>
                        <option>HSK</option>
                        <option>Private</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Nama Materi / Unit
                    </label>

                    <input type="text"
                           class="form-control"
                           placeholder="Contoh : Unit 6 - Aktivitas Sehari-hari">

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        File Silabus
                    </label>

                    <input type="file"
                           class="form-control">

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Daftar Kosakata (Mandarin, Pinyin, Arti)
                    </label>

                    <textarea
                        class="form-control"
                        rows="4"
                        placeholder="爸爸 - bàba - Ayah"></textarea>

                </div>

                <button class="btn btn-success">
                    Publish Materi & Silabus
                </button>

            </form>

        </div>

    </div>

    {{-- =======================
        Filter
    ======================= --}}

    <div class="mb-3">

        <span class="badge bg-dark px-3 py-2">Semua</span>

        <span class="badge bg-light text-dark border px-3 py-2">Maochong</span>

        <span class="badge bg-light text-dark border px-3 py-2">Jianer</span>

        <span class="badge bg-light text-dark border px-3 py-2">Hudie</span>

        <span class="badge bg-light text-dark border px-3 py-2">Feixiang</span>

        <span class="badge bg-light text-dark border px-3 py-2">HSK</span>

        <span class="badge bg-light text-dark border px-3 py-2">Private</span>

        <span class="badge bg-light text-dark border px-3 py-2">Bisnis</span>

        <span class="badge bg-light text-dark border px-3 py-2">Traditional / TOCFL</span>

    </div>

    {{-- =======================
        LIST MATERI
    ======================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="list-group list-group-flush">

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        HSK 3 (A) — Silabus & Kosakata Lengkap
                    </div>

                    <small class="text-muted">
                        v4 • Dipublikasikan
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-dark btn-sm">
                        Lihat / Edit
                    </button>

                    <span class="badge bg-success">
                        Terpublikasi
                    </span>

                </div>

            </div>

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Maochong 3A — Unit 5 Keluarga
                    </div>

                    <small class="text-muted">
                        v2 • Dipublikasikan
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-dark btn-sm">
                        Lihat / Edit
                    </button>

                    <span class="badge bg-success">
                        Terpublikasi
                    </span>

                </div>

            </div>

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Hudie 1A — Unit 1 Salam
                    </div>

                    <small class="text-muted">
                        v1 • Dipublikasikan
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-dark btn-sm">
                        Lihat / Edit
                    </button>

                    <span class="badge bg-success">
                        Terpublikasi
                    </span>

                </div>

            </div>

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Jianer 2B — Unit 3 Warna & Bentuk
                    </div>

                    <small class="text-muted">
                        v1 • Draft
                    </small>

                </div>

                <div>

                    <button class="btn btn-warning btn-sm">
                        Lanjutkan Edit
                    </button>

                    <span class="badge bg-warning text-dark">
                        Draft
                    </span>

                </div>

            </div>

        </div>

    </div>

    <small class="text-muted">
        Status "Draft" berarti belum selesai disusun Kurikulum dan belum terlihat oleh guru.
    </small>

    {{-- =======================
        SILABUS KOSAKATA
    ======================= --}}

    <div class="card shadow-sm border-0 mt-4">

        <div class="card-body">

            <h4 class="fw-bold">
                Silabus — Kosakata yang Diajarkan (Berkesinambungan per Pertemuan)
            </h4>

            <small class="text-muted">
                Maochong 3A, Unit 5 (Keluarga)
            </small>

            <div class="table-responsive mt-3">

                <table class="table">

                    <thead>

                    <tr>

                        <th>Pertemuan</th>
                        <th>Kata Mandarin</th>
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

                    </tbody>

                </table>

            </div>

            <button class="btn btn-outline-secondary btn-sm">
                Lihat PDF
            </button>

        </div>

    </div>

</div>

@endsection