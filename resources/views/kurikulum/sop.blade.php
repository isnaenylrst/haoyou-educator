@extends('layouts.kurikulum')

@section('title','SOP')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">SOP</h3>
        <small class="text-muted">
            Kelola folder SOP — upload baru, upload ulang, atau hapus
        </small>
    </div>

    {{-- ===========================
        FORM TAMBAH SOP
    ============================ --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <h4 class="fw-bold mb-4">
                Tambah SOP Baru
            </h4>

            <form>

                <div class="mb-3">
                    <label class="form-label">Judul SOP</label>

                    <input type="text"
                           class="form-control"
                           placeholder="Contoh: SOP Onboarding Guru Baru">
                </div>

                <div class="mb-3">
                    <label class="form-label">File</label>

                    <input type="file"
                           class="form-control">
                </div>

                <button class="btn btn-success">
                    Upload SOP Baru
                </button>

            </form>

        </div>
    </div>

    {{-- ===========================
        LIST SOP
    ============================ --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="list-group list-group-flush">

            {{-- Item 1 --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        SOP Pengajaran Umum
                    </div>

                    <small class="text-muted">
                        Diperbarui 2 minggu lalu • PDF
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-secondary btn-sm">
                        Buka
                    </button>

                    <button class="btn btn-outline-primary btn-sm">
                        Upload Ulang
                    </button>

                    <button class="btn btn-outline-danger btn-sm">
                        Hapus
                    </button>

                </div>

            </div>

            {{-- Item 2 --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        SOP Penilaian & Progress Report
                    </div>

                    <small class="text-muted">
                        Diperbarui 1 bulan lalu • PDF
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-secondary btn-sm">
                        Buka
                    </button>

                    <button class="btn btn-outline-primary btn-sm">
                        Upload Ulang
                    </button>

                    <button class="btn btn-outline-danger btn-sm">
                        Hapus
                    </button>

                </div>

            </div>

            {{-- Item 3 --}}
            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        SOP Trial Teaching Guru Baru
                    </div>

                    <small class="text-muted">
                        Diperbarui 2 bulan lalu • PDF
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-secondary btn-sm">
                        Buka
                    </button>

                    <button class="btn btn-outline-primary btn-sm">
                        Upload Ulang
                    </button>

                    <button class="btn btn-outline-danger btn-sm">
                        Hapus
                    </button>

                </div>

            </div>

        </div>

    </div>

    {{-- ===========================
        TEMPLATE PROGRESS REPORT
    ============================ --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <h4 class="fw-bold mb-3">
                Template Progress Report
            </h4>

            <p class="text-muted small">
                Template inilah yang otomatis muncul di tombol
                <b>"Download Template"</b> pada menu Progress Report
                milik semua guru.
            </p>

            <form>

                <div class="mb-3">

                    <label class="form-label">
                        Jenis Kelas
                    </label>

                    <select class="form-select">

                        <option>Reguler (non-HSK)</option>
                        <option>HSK</option>
                        <option>Private</option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        File Template (.docx / .xlsx)
                    </label>

                    <input type="file"
                           class="form-control">

                </div>

                <button class="btn btn-success">
                    Upload / Perbarui Template
                </button>

            </form>

        </div>

    </div>

    {{-- ===========================
        LIST TEMPLATE
    ============================ --}}

    <div class="card shadow-sm border-0">

        <div class="list-group list-group-flush">

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Template Progress Report — Reguler
                    </div>

                    <small class="text-muted">
                        template_progress_report_reguler.docx • diperbarui 3 minggu lalu
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-secondary btn-sm">
                        Buka
                    </button>

                    <button class="btn btn-outline-primary btn-sm">
                        Upload Ulang
                    </button>

                </div>

            </div>

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Template Progress Report — HSK
                    </div>

                    <small class="text-muted">
                        template_progress_report_hsk.docx • termasuk kolom sertifikat
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-secondary btn-sm">
                        Buka
                    </button>

                    <button class="btn btn-outline-primary btn-sm">
                        Upload Ulang
                    </button>

                </div>

            </div>

            <div class="list-group-item d-flex justify-content-between align-items-center">

                <div>

                    <div class="fw-semibold">
                        Template Progress Report — Private
                    </div>

                    <small class="text-muted">
                        template_progress_report_private.docx
                    </small>

                </div>

                <div>

                    <button class="btn btn-outline-secondary btn-sm">
                        Buka
                    </button>

                    <button class="btn btn-outline-primary btn-sm">
                        Upload Ulang
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection