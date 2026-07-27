@extends('layouts.kurikulum')

@section('title','Jadwal Konsultasi')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold mb-1">
            Jadwal Konsultasi
        </h3>

        <small class="text-muted">
            Jadwal untuk guru — bisa ditandai terjadwal atau diganti hari.
            Konsultasi Direktur wajib H-14 sebelum kelas berjalan.
        </small>
    </div>

    {{-- =========================
        LIST JADWAL
    ========================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="list-group list-group-flush">

            {{-- Item --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">

                        ⚠ Konsultasi Direktur (H-14) —
                        Rina Wulandari, Jianer 2B

                    </div>

                    <small class="text-muted">
                        Belum terjadwal • batas waktu 12 Juli 2026 (6 hari lagi)
                    </small>

                </div>

                <div>

                    <span class="badge bg-danger-subtle text-danger me-2">
                        Mendekati Batas H-14
                    </span>

                    <button class="btn btn-primary btn-sm">
                        Jadwalkan Sekarang
                    </button>

                </div>

            </div>

            {{-- Item --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Konsultasi LP & PPT (Kurikulum) — Ahmad Fauzi
                    </div>

                    <small class="text-muted">
                        Kamis, 09.00
                    </small>

                </div>

                <div>

                    <span class="badge bg-success">
                        Terjadwal
                    </span>

                    <button class="btn btn-outline-secondary btn-sm ms-2">
                        Ganti Hari
                    </button>

                </div>

            </div>

            {{-- Item --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Konsultasi Revisi PPT (Kurikulum) — Rina Wulandari
                    </div>

                    <small class="text-muted">
                        Jumat, 10.30
                    </small>

                </div>

                <div>

                    <span class="badge bg-success">
                        Terjadwal
                    </span>

                    <button class="btn btn-outline-secondary btn-sm ms-2">
                        Ganti Hari
                    </button>

                </div>

            </div>

            {{-- Item --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Konsultasi Trial Teaching — Guru Reza
                    </div>

                    <small class="text-muted">
                        Menunggu konfirmasi
                    </small>

                </div>

                <div>

                    <span class="badge bg-warning text-dark">
                        Belum Dikonfirmasi
                    </span>

                    <button class="btn btn-outline-secondary btn-sm ms-2">
                        Ganti Hari
                    </button>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================
        FORM
    ========================== --}}

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h3 class="fw-bold mb-4">
                Buat Jadwal Baru
            </h3>

            <form>

                <div class="mb-3">

                    <label class="form-label">
                        Jenis Konsultasi
                    </label>

                    <select class="form-select">

                        <option>Konsultasi LP & PPT (Kurikulum)</option>
                        <option>Konsultasi Direktur</option>
                        <option>Trial Teaching</option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Guru
                    </label>

                    <select class="form-select">

                        <option>Ahmad Fauzi</option>
                        <option>Rina Wulandari</option>
                        <option>Reza</option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Tanggal & Jam
                    </label>

                    <input type="datetime-local"
                           class="form-control">

                </div>

                <div class="form-check mb-3">

                    <input class="form-check-input"
                           type="checkbox"
                           checked>

                    <label class="form-check-label">

                        Kirim juga ke WhatsApp Guru & Pak Joy

                    </label>

                </div>

                <button class="btn btn-success">
                    Buat Jadwal
                </button>

            </form>

        </div>

    </div>

</div>

@endsection