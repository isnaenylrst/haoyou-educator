@extends('layouts.guru')

@section('title','Notifikasi')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Notifikasi</h2>
        <p class="text-muted mb-0">
            Semua pemberitahuan untuk Anda (Ratna) —
            hasil review LP/PPT/Jurnal/Progress/Cuti & surat dari Kepala Kurikulum
        </p>
    </div>

    {{-- Card --}}
    <div class="card border-0 shadow-sm rounded-4">

        {{-- Notifikasi 1 --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">

            <div>
                <h6 class="fw-semibold mb-1">
                    LP kelas 8C perlu direvisi
                </h6>

                <small class="text-muted">
                    Hasil Review LP & PPT • hari ini
                </small>
            </div>

            <span class="badge rounded-pill text-warning border border-warning px-3 py-2 bg-white">
                Baru
            </span>

        </div>

        {{-- Notifikasi 2 --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">

            <div>
                <h6 class="fw-semibold mb-1">
                    Progress Report Mid 1 kelas 9A disetujui
                </h6>

                <small class="text-muted">
                    Hasil Review Progress Report • hari ini
                </small>
            </div>

            <span class="badge rounded-pill text-warning border border-warning px-3 py-2 bg-white">
                Baru
            </span>

        </div>

        {{-- Notifikasi 3 --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">

            <div>
                <h6 class="fw-semibold mb-1">
                    Jurnal sesi Maochong A sudah direview
                </h6>

                <small class="text-muted">
                    Hasil Review Jurnal & Absensi • kemarin
                </small>
            </div>

            <span class="badge rounded-pill text-warning border border-warning px-3 py-2 bg-white">
                Baru
            </span>

        </div>

        {{-- Notifikasi 4 --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">

            <div>
                <h6 class="fw-semibold mb-1">
                    Surat Libur Nasional 17 Agustus 2026
                </h6>

                <small class="text-muted">
                    Surat Pemberitahuan • 2 hari lalu
                </small>
            </div>

            <span class="badge rounded-pill text-warning border border-warning px-3 py-2 bg-white">
                Baru
            </span>

        </div>

        {{-- Notifikasi 5 --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">

            <div>
                <h6 class="fw-semibold mb-1">
                    LoA Guru Tetap 2026/2027
                </h6>

                <small class="text-muted">
                    Surat Pemberitahuan • 1 minggu lalu
                </small>
            </div>

            <span class="badge rounded-pill text-warning border border-warning px-3 py-2 bg-white">
                Baru
            </span>

        </div>

        {{-- Notifikasi 6 --}}
        <div class="d-flex justify-content-between align-items-center p-3">

            <div>
                <h6 class="fw-semibold mb-1">
                    Ganti kelas disetujui (pengganti: Rina)
                </h6>

                <small class="text-muted">
                    Hasil Review Cuti/Ganti Kelas • 2 minggu lalu
                </small>
            </div>

            <span class="badge rounded-pill bg-light text-secondary border px-3 py-2">
                Sudah Dibaca
            </span>

        </div>

    </div>

</div>

@endsection