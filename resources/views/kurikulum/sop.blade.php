@extends('layouts.kurikulum')

@section('title','SOP')

@section('content')

<div class="container-fluid">

    {{-- ==========================================
        PAGE HEADER
    =========================================== --}}
    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            SOP
        </h2>

        <p class="text-muted mb-0">
            Kelola folder SOP — upload baru, upload ulang, atau hapus
        </p>

    </div>

    {{-- ==========================================
        SUCCESS MESSAGE
    =========================================== --}}
    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fa-solid fa-circle-check me-2"></i>

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- ==========================================
        ERROR MESSAGE
    =========================================== --}}
    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="fa-solid fa-circle-xmark me-2"></i>

            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- ==========================================
        VALIDATION ERROR
    =========================================== --}}
    @if($errors->any())

        <div class="alert alert-danger">

            <strong>

                Terjadi kesalahan.

            </strong>

            <ul class="mt-2 mb-0">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ==========================================
        CARD TAMBAH SOP
    =========================================== --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <h3 class="fw-bold mb-4">

                Tambah SOP Baru

            </h3>

            <form
                action="{{ route('kurikulum.sop.store') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf

                {{-- ==========================
                    JUDUL SOP
                =========================== --}}
                <div class="mb-3">

                    <label class="form-label fw-semibold">

                        Judul SOP

                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title') }}"
                        placeholder="Contoh: SOP Onboarding Guru Baru"
                        required>

                    @error('title')

                        <div class="invalid-feedback">

                            {{ $message }}

                        </div>

                    @enderror

                </div>


                {{-- ==========================
                    FILE SOP
                =========================== --}}
                <div class="mb-4">

                    <label class="form-label fw-semibold">

                        File

                    </label>

                    <input
                        type="file"
                        name="file"
                        class="form-control @error('file') is-invalid @enderror"
                        accept=".pdf"
                        required>

                    @error('file')

                        <div class="invalid-feedback">

                            {{ $message }}

                        </div>

                    @enderror

                </div>


                {{-- ==========================
                    BUTTON
                =========================== --}}
                <button
                    type="submit"
                    class="btn btn-success">

                    <i class="fa-solid fa-upload me-2"></i>

                    Upload SOP Baru

                </button>

            </form>

        </div>

    </div>

        {{-- ==========================================================
        DAFTAR SOP
    =========================================================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-0">

            @forelse($sops as $sop)

                <div class="border-bottom px-4 py-3">

                    <div class="row align-items-center">

                        {{-- =====================================
                            INFORMASI SOP
                        ====================================== --}}
                        <div class="col-md-8">

                            <h6 class="fw-semibold mb-1">

                                {{ $sop->title }}

                            </h6>

                            <small class="text-muted">

                                Diperbarui

                                {{ $sop->updated_at->diffForHumans() }}

                                •

                                PDF

                            </small>

                        </div>


                        {{-- =====================================
                            BUTTON
                        ====================================== --}}
                        <div class="col-md-4">

                            <div
                                class="d-flex justify-content-md-end justify-content-start gap-2 mt-3 mt-md-0">

                                {{-- ========================
                                    BUKA
                                ========================= --}}
                                <a
                                    href="{{ Storage::url($sop->file_path) }}"
                                    target="_blank"
                                    class="btn btn-light border btn-sm">

                                    Buka

                                </a>


                                {{-- ========================
                                    UPLOAD ULANG
                                ========================= --}}
                                <button
                                    type="button"
                                    class="btn btn-light border btn-sm"

                                    data-bs-toggle="modal"

                                    data-bs-target="#editModal{{ $sop->id }}">

                                    Upload Ulang

                                </button>


                                {{-- ========================
                                    HAPUS
                                ========================= --}}
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"

                                    data-bs-toggle="modal"

                                    data-bs-target="#deleteModal{{ $sop->id }}">

                                    Hapus

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center py-5">

                    <i
                        class="fa-solid fa-folder-open
                        fa-3x
                        text-secondary
                        mb-3">
                    </i>

                    <h5 class="fw-bold">

                        Belum Ada SOP

                    </h5>

                    <p class="text-muted mb-0">

                        Silakan upload SOP pertama Anda.

                    </p>

                </div>

            @endforelse

        </div>

    </div>

        {{-- ==========================================================
        TEMPLATE PROGRESS REPORT
    =========================================================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <h4 class="fw-bold mb-2">

                Template Progress Report

            </h4>

            <p class="text-muted small mb-4">

                Template ini otomatis muncul pada tombol
                <strong>Download Template</strong>
                di menu Progress Report guru sesuai jenis kelasnya.

            </p>

            {{-- ==========================================
                FORM UPLOAD TEMPLATE
            =========================================== --}}

            <form
                action="{{ route('kurikulum.template.upload') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf

                {{-- ==========================
                    JENIS KELAS
                =========================== --}}
                <div class="mb-3">

                    <label class="form-label fw-semibold">

                        Jenis Kelas

                    </label>

                    <select
                        name="name"
                        class="form-select"
                        required>

                        <option value="">
                            -- Pilih Jenis Kelas --
                        </option>

                        <option value="Regular (Non-HSK)">
                            Regular (Non-HSK)
                        </option>

                        <option value="HSK 1">
                            HSK 1
                        </option>

                        <option value="HSK 2">
                            HSK 2
                        </option>

                        <option value="HSK 3">
                            HSK 3
                        </option>

                        <option value="HSK 4">
                            HSK 4
                        </option>

                        <option value="HSK 5">
                            HSK 5
                        </option>

                        <option value="HSK 6">
                            HSK 6
                        </option>

                    </select>

                </div>

                {{-- ==========================
                    FILE TEMPLATE
                =========================== --}}
                <div class="mb-3">

                    <label class="form-label fw-semibold">

                        File Template (.docx / .xlsx)

                    </label>

                    <input
                        type="file"
                        name="file"
                        class="form-control"
                        accept=".doc,.docx,.xls,.xlsx"
                        required>

                </div>

                {{-- ==========================
                    BUTTON
                =========================== --}}
                <button
                    type="submit"
                    class="btn btn-success">

                    <i class="fa-solid fa-upload me-2"></i>

                    Upload / Perbarui Template

                </button>

            </form>

        </div>

    </div>


    {{-- ==========================================================
        LIST TEMPLATE
    =========================================================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-0">

            @forelse($templates as $template)

                <div class="border-bottom px-4 py-3">

                    <div class="row align-items-center">

                        {{-- ==========================
                            INFORMASI TEMPLATE
                        =========================== --}}
                        <div class="col-md-8">

                            <h6 class="fw-semibold mb-1">

                                {{ $template->name }}

                            </h6>

                            <small class="text-muted">

                                {{ basename($template->file_path) }}

                                •

                                diperbarui

                                {{ $template->updated_at->diffForHumans() }}

                            </small>

                        </div>

                        {{-- ==========================
                            BUTTON
                        =========================== --}}
                        <div class="col-md-4">

                            <div
                                class="d-flex justify-content-md-end justify-content-start gap-2 mt-3 mt-md-0">

                                {{-- BUKA --}}
                                <a
                                    href="{{ Storage::url($template->file_path) }}"
                                    target="_blank"
                                    class="btn btn-light border btn-sm">

                                    Buka

                                </a>

                                {{-- UPLOAD ULANG --}}
                                <button
                                    type="button"
                                    class="btn btn-light border btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#templateModal{{ $template->id }}">

                                    Upload Ulang

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center py-5">

                    <i class="fa-solid fa-file-word fa-3x text-secondary mb-3"></i>

                    <h5 class="fw-bold">

                        Belum Ada Template Progress Report

                    </h5>

                    <p class="text-muted mb-0">

                        Silakan upload template terlebih dahulu.

                    </p>

                </div>

            @endforelse

        </div>

    </div>

    {{-- ==========================================================
    MODAL UPLOAD ULANG SOP
========================================================== --}}

@foreach($sops as $sop)

<div
    class="modal fade"
    id="editModal{{ $sop->id }}"
    tabindex="-1"
    aria-labelledby="editModalLabel{{ $sop->id }}"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                action="{{ route('kurikulum.sop.update',$sop) }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf
                @method('PUT')

                {{-- ==========================
                    HEADER
                =========================== --}}
                <div class="modal-header">

                    <h5
                        class="modal-title fw-bold"
                        id="editModalLabel{{ $sop->id }}">

                        Upload Ulang SOP

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                {{-- ==========================
                    BODY
                =========================== --}}
                <div class="modal-body">

                    {{-- Judul SOP --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Judul SOP

                        </label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            value="{{ old('title',$sop->title) }}"
                            required>

                    </div>

                    {{-- File Saat Ini --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            File Saat Ini

                        </label>

                        <div class="border rounded bg-light p-3">

                            <i class="fa-solid fa-file-pdf text-danger me-2"></i>

                            {{ basename($sop->file_path) }}

                        </div>

                    </div>

                    {{-- Upload Baru --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Upload File Baru

                        </label>

                        <input
                            type="file"
                            name="file"
                            class="form-control"
                            accept=".pdf">

                        <small class="text-muted">

                            Kosongkan apabila hanya ingin mengubah judul SOP.

                        </small>

                    </div>

                </div>


                {{-- ==========================
                    FOOTER
                =========================== --}}
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa-solid fa-floppy-disk me-2"></i>

                        Simpan Perubahan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endforeach
{{-- ==========================================================
    MODAL UPLOAD ULANG TEMPLATE
========================================================== --}}

@isset($templates)

@foreach($templates as $template)

<div
    class="modal fade"
    id="templateModal{{ $template->id }}"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                action="{{ route('kurikulum.template.update',$template) }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <div class="modal-header">

                    <h5 class="modal-title fw-bold">

                        Upload Ulang Template

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    {{-- Nama Template --}}
                    <div class="mb-3">

                        <label class="form-label">

                            Jenis Kelas

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="name"
                            value="{{ $template->name }}"
                            required>

                    </div>

                    {{-- File Lama --}}
                    <div class="mb-3">

                        <label class="form-label">

                            File Saat Ini

                        </label>

                        <div class="border rounded bg-light p-3">

                            {{ basename($template->file_path) }}

                        </div>

                    </div>

                    {{-- File Baru --}}
                    <div class="mb-3">

                        <label class="form-label">

                            Upload File Baru

                        </label>

                        <input
                            type="file"
                            name="file"
                            class="form-control"
                            accept=".doc,.docx,.xls,.xlsx">

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button
                        class="btn btn-primary">

                        Simpan Perubahan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endforeach

@endisset
{{-- ==========================================================
    MODAL HAPUS SOP
========================================================== --}}

@foreach($sops as $sop)

<div
    class="modal fade"
    id="deleteModal{{ $sop->id }}"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                action="{{ route('kurikulum.sop.destroy',$sop) }}"
                method="POST">

                @csrf
                @method('DELETE')

                <div class="modal-header">

                    <h5 class="modal-title text-danger">

                        <i class="fa-solid fa-triangle-exclamation me-2"></i>

                        Hapus SOP

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <p>

                        Apakah Anda yakin ingin menghapus SOP berikut?

                    </p>

                    <div class="border rounded p-3 bg-light">

                        <strong>

                            {{ $sop->title }}

                        </strong>

                        <br>

                        <small class="text-muted">

                            {{ basename($sop->file_path) }}

                        </small>

                    </div>

                    <div class="alert alert-warning mt-3 mb-0">

                        File akan dihapus dari storage dan database.

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger">

                        <i class="fa-solid fa-trash me-2"></i>

                        Ya, Hapus

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endforeach

@push('styles')

<style>

/* =====================================================
   PAGE
===================================================== */

.container-fluid{

    max-width:1100px;

}

/* =====================================================
   CARD
===================================================== */

.card{

    border:none;

    border-radius:12px;

    overflow:hidden;

    box-shadow:0 2px 10px rgba(0,0,0,.08);

}

/* =====================================================
   HEADER
===================================================== */

.card-body h3,
.card-body h4{

    font-weight:700;

}

/* =====================================================
   LIST
===================================================== */

.border-bottom:last-child{

    border-bottom:none!important;

}

/* =====================================================
   BUTTON
===================================================== */

.btn{

    border-radius:8px;

}

.btn-light{

    background:#fff;

}

.btn-light:hover{

    background:#f7f7f7;

}

/* =====================================================
   FORM
===================================================== */

.form-control,
.form-select{

    border-radius:8px;

}

/* =====================================================
   MODAL
===================================================== */

.modal-content{

    border-radius:14px;

    border:none;

}

/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:768px){

    .d-flex.gap-2{

        flex-direction:column;

        width:100%;

    }

    .btn-sm{

        width:100%;

    }

}

</style>

@endpush

@endsection