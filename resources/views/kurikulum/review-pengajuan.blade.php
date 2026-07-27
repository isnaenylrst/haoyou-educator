@extends('layouts.kurikulum')

@section('title','Review Pengajuan')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Review Pengajuan</h2>
        <small class="text-muted">
            Card sesi jurnal & absensi dari semua guru, dan tab dokumen LP/PPT/Jurnal/Progress/Cuti untuk direview satu per satu.
        </small>
    </div>

    {{-- Summary --}}
    <div class="row mb-4">

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Sesi Hari Ini (Semua Guru)
                    </small>

                    <h2 class="fw-bold mt-2 mb-0">
                        14
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Sudah Diisi
                    </small>

                    <h2 class="fw-bold text-success mt-2 mb-0">
                        9
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Belum Diisi
                    </small>

                    <h2 class="fw-bold text-danger mt-2 mb-0">
                        5
                    </h2>

                </div>

            </div>

        </div>

    </div>



    {{-- Judul --}}
    <h5 class="fw-bold mb-3">
        Card Sesi — Jurnal & Absensi
    </h5>


    {{-- Filter Guru --}}
    <div class="mb-3">

        <span class="badge rounded-pill bg-dark px-3 py-2">
            Semua Guru
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            Ratna
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            Ahmad Fauzi
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            Rina Wulandari
        </span>

    </div>



    {{-- ====================== CARD 1 ========================= --}}

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

            <div class="d-flex justify-content-between">

                <div>

                    <h5 class="fw-bold">
                        Maochong A — Daily Activity
                    </h5>

                    <small class="badge bg-light text-dark">
                        Guru : Ratna
                    </small>

                    <small class="badge bg-light text-dark">
                        08:00–09:30
                    </small>

                    <small class="badge bg-light text-dark">
                        Maochong
                    </small>

                    <small class="badge bg-light text-dark">
                        Offline
                    </small>

                </div>

                <div>

                    <span class="badge rounded-pill bg-warning text-dark">
                        Sudah Diisi • Belum ACC
                    </span>

                </div>

            </div>


            <div class="mt-3">

                <span class="badge bg-success">
                    ✓ Zahra Putri
                </span>

                <span class="badge bg-success">
                    ✓ Budi Santoso
                </span>

                <span class="badge bg-danger">
                    ✕ Anisa Rahmawati
                </span>

            </div>

            <div class="alert alert-light border mt-3 mb-3">

                Poin sesi:
                <b>90</b>

                • Materi:
                Kosakata warna dalam Mandarin

                • Aktivitas:
                Flashcard, bernyanyi bersama, tanya jawab

            </div>

            <button class="btn btn-success btn-sm">
                Tandai ACC
            </button>

        </div>

    </div>




    {{-- ====================== CARD 2 ========================= --}}

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

            <div class="d-flex justify-content-between">

                <div>

                    <h5 class="fw-bold">
                        HSK 3 (9A) — HSK Preparation
                    </h5>

                    <small class="badge bg-light text-dark">
                        Guru : Ahmad Fauzi
                    </small>

                    <small class="badge bg-light text-dark">
                        09:00–10:00
                    </small>

                    <small class="badge bg-light text-dark">
                        HSK
                    </small>

                    <small class="badge bg-light text-dark">
                        Online
                    </small>

                </div>

                <div>

                    <span class="badge rounded-pill bg-warning text-dark">
                        Sudah Diisi • Belum ACC
                    </span>

                </div>

            </div>

            <div class="mt-3">

                <span class="badge bg-success">
                    ✓ Dinda K.
                </span>

                <span class="badge bg-success">
                    ✓ Sari Dewi
                </span>

                <span class="badge bg-success">
                    ✓ Reza P.
                </span>

            </div>

            <div class="alert alert-light border mt-3 mb-3">

                Poin sesi:
                <b>85</b>

                • Materi:
                Latihan pola kalimat 是...的

                • Aktivitas:
                Latihan lisan, role play singkat

            </div>

            <button class="btn btn-success btn-sm">
                Tandai ACC
            </button>

        </div>

    </div>




    {{-- ====================== CARD 3 ========================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between">

                <div>

                    <h5 class="fw-bold">
                        Jianer 2B (7B) — Daily Activity
                    </h5>

                    <small class="badge bg-light text-dark">
                        Guru : Rina Wulandari
                    </small>

                    <small class="badge bg-light text-dark">
                        15:00–16:00
                    </small>

                    <small class="badge bg-light text-dark">
                        Jianer
                    </small>

                    <small class="badge bg-light text-dark">
                        Offline
                    </small>

                </div>

                <div>

                    <span class="badge rounded-pill bg-danger">
                        Belum Diisi
                    </span>

                </div>

            </div>


            <h5 class="text-danger mt-3">

                Sesi sudah lewat 2 jam,
                guru belum mengisi jurnal & absensi.

            </h5>


            <button class="btn btn-outline-secondary btn-sm">

                Kirim Reminder

            </button>

        </div>

    </div>

        {{-- =========================================
        REVIEW DOKUMEN PER KATEGORI
    ========================================== --}}

    <h5 class="fw-bold mb-3">
        Review Dokumen per Kategori
    </h5>

    {{-- Tab --}}
    <div class="mb-3">

        <span class="badge rounded-pill bg-dark px-3 py-2">
            LP
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            PPT
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            Jurnal Online
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            Progress Report
        </span>

        <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
            Cuti / Pengajuan Kelas
        </span>

    </div>




    <div class="card shadow-sm border-0">

        <div class="list-group list-group-flush">

            {{-- ================= ITEM 1 ================= --}}

            <div class="list-group-item">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="mb-1">
                            Rina Wulandari — LP kelas 7B (Jianer 2B)
                        </h6>

                        <small class="text-muted">
                            Diajukan hari ini
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-secondary btn-sm">
                            Lihat Dokumen
                        </button>

                        <button class="btn btn-outline-secondary btn-sm">
                            Download
                        </button>

                        <button class="btn btn-primary btn-sm">
                            ACC
                        </button>

                        <button class="btn btn-outline-danger btn-sm">
                            Kembalikan
                        </button>

                    </div>

                </div>

            </div>



            {{-- ================= ITEM 2 ================= --}}

            <div class="list-group-item">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="mb-1">
                            Ahmad Fauzi — LP kelas 9A (HSK 3)
                        </h6>

                        <small class="text-muted">
                            Diajukan kemarin
                        </small>

                    </div>

                    <div>

                        <button class="btn btn-outline-secondary btn-sm">
                            Lihat Dokumen
                        </button>

                        <button class="btn btn-outline-secondary btn-sm">
                            Download
                        </button>

                        <button class="btn btn-primary btn-sm">
                            ACC
                        </button>

                        <button class="btn btn-outline-danger btn-sm">
                            Kembalikan
                        </button>

                    </div>

                </div>

            </div>



            {{-- ================= ITEM 3 ================= --}}

            <div class="list-group-item">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="mb-1">
                            Budi Santoso — LP kelas 8C (Feixiang 1C)
                        </h6>

                        <small class="text-muted">
                            Sudah dikembalikan — guru belum upload ulang
                        </small>

                    </div>

                    <div>

                        <span class="badge rounded-pill bg-warning text-dark">
                            Menunggu Guru
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <small class="text-muted mt-3 d-block">

        Kalau ditekan
        <b>"Kembalikan"</b>,
        status berubah menjadi perlu revisi dan otomatis muncul tombol
        <b>"Upload Ulang"</b>
        di sisi Guru pada menu Kelas.

    </small>

    </div>

<style>

.card{
    border-radius:12px;
}

.list-group-item{
    padding:18px 20px;
}

.btn{
    border-radius:8px;
}

.badge{
    font-weight:500;
}

.alert-light{
    background:#faf8f1;
    border:none;
}

.btn-success{
    background:#2d7f76;
    border-color:#2d7f76;
}

.btn-success:hover{
    background:#256b63;
}

.btn-primary{
    background:#3b82f6;
    border-color:#3b82f6;
}

.btn-outline-danger{
    color:#dc3545;
}

.btn-outline-danger:hover{
    color:white;
}

h2,h5,h6{
    font-weight:700;
}

</style>

@endsection