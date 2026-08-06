@extends('layouts.kurikulum')

@section('title','Pemberitahuan Surat')

@section('content')

<div class="container-fluid py-3">

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            Pemberitahuan Surat
        </h2>

        <p class="text-muted mb-0">
            Upload file surat & tentukan penerima
            (semua guru atau guru tertentu)
        </p>

    </div>


    {{-- =========================================================
        SUCCESS
    ========================================================== --}}

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


    {{-- =========================================================
        ERROR
    ========================================================== --}}

    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- =========================================================
        VALIDATION ERROR
    ========================================================== --}}

    @if($errors->any())

        <div class="alert alert-danger alert-dismissible fade show">

            <strong>
                Terjadi Kesalahan:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif



    {{-- =========================================================
        STATISTIK
    ========================================================== --}}

    <div class="row g-3 mb-4">

        {{-- TOTAL --}}

        <div class="col-lg-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small class="text-uppercase text-secondary fw-bold">
                        Total Terkirim
                    </small>

                    <h1 class="fw-bold mb-0 mt-2">
                        {{ $totalLetters }}
                    </h1>

                </div>

            </div>

        </div>


        {{-- BULAN INI --}}

        <div class="col-lg-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small class="text-uppercase text-secondary fw-bold">
                        Bulan Ini
                    </small>

                    <h1 class="fw-bold text-success mb-0 mt-2">
                        {{ $lettersThisMonth }}
                    </h1>

                </div>

            </div>

        </div>


        {{-- BELUM DIBACA --}}

        <div class="col-lg-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small class="text-uppercase text-secondary fw-bold">
                        Belum Dibaca
                    </small>

                    <h1 class="fw-bold text-warning mb-0 mt-2">
                        {{ $unreadLetters }}
                    </h1>

                    <small class="text-muted">
                        Fitur pembacaan belum diaktifkan
                    </small>

                </div>

            </div>

        </div>


        {{-- JENIS SURAT --}}

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



    {{-- =========================================================
        FORM SURAT
    ========================================================== --}}

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body p-4">

            <h3 class="fw-bold mb-4">
                Buat Surat Baru
            </h3>


            <form
                action="{{ route('kurikulum.surat.store') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf


                {{-- =================================================
                    JENIS SURAT
                ================================================== --}}

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Jenis Surat
                    </label>

                    <select
                        class="form-select"
                        name="jenis_surat"
                        required>

                        <option value="">
                            -- Pilih Jenis Surat --
                        </option>

                        <option
                            value="SURAT_LIBUR"
                            {{ old('jenis_surat') == 'SURAT_LIBUR' ? 'selected' : '' }}>

                            Surat Libur

                        </option>

                        <option
                            value="SURAT_DINAS"
                            {{ old('jenis_surat') == 'SURAT_DINAS' ? 'selected' : '' }}>

                            Surat Dinas

                        </option>

                        <option
                            value="LOA"
                            {{ old('jenis_surat') == 'LOA' ? 'selected' : '' }}>

                            LoA

                        </option>

                    </select>

                </div>



                {{-- =================================================
                    PENERIMA
                ================================================== --}}

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Penerima
                    </label>

                    <select
                        class="form-select"
                        name="penerima"
                        id="penerima"
                        required>

                        <option
                            value="SEMUA_GURU"
                            {{ old('penerima', 'SEMUA_GURU') == 'SEMUA_GURU' ? 'selected' : '' }}>

                            Semua Guru

                        </option>

                        <option
                            value="GURU_TERTENTU"
                            {{ old('penerima') == 'GURU_TERTENTU' ? 'selected' : '' }}>

                            Guru Tertentu

                        </option>

                    </select>

                </div>



                {{-- =================================================
                    PILIH GURU
                ================================================== --}}

                <div
                    class="mb-3"
                    id="teacherSelectContainer"
                    style="display: none;">

                    <label class="form-label fw-semibold">
                        Pilih Guru
                    </label>

                    <select
                        class="form-select"
                        name="teacher_id"
                        id="teacher_id">

                        <option value="">
                            -- Pilih Guru --
                        </option>

                        @foreach($teachers as $teacher)

                            <option
                                value="{{ $teacher->id }}"
                                {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>

                                {{ $teacher->name }}

                            </option>

                        @endforeach

                    </select>

                </div>



                {{-- =================================================
                    JUDUL
                ================================================== --}}

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Judul Surat
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="judul"
                        value="{{ old('judul') }}"
                        placeholder="Contoh: Libur Nasional 17 Agustus 2026"
                        required>

                </div>



                {{-- =================================================
                    ISI
                ================================================== --}}

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Isi Surat
                    </label>

                    <textarea
                        class="form-control"
                        rows="4"
                        name="isi"
                        placeholder="Tuliskan isi pemberitahuan di sini...">{{ old('isi') }}</textarea>

                </div>



                {{-- =================================================
                    FILE
                ================================================== --}}

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Upload File Surat (PDF/Gambar)
                    </label>

                    <input
                        type="file"
                        class="form-control"
                        name="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required>

                    <small class="text-muted">
                        Format: PDF, JPG, JPEG, PNG. Maksimal 10 MB.
                    </small>

                </div>



                {{-- =================================================
                    BUTTON
                ================================================== --}}

                <button
                    type="submit"
                    class="btn btn-success px-4">

                    <i class="fa-solid fa-paper-plane me-2"></i>

                    Kirim Surat

                </button>

            </form>

        </div>

    </div>



    {{-- =========================================================
        RIWAYAT SURAT
    ========================================================== --}}

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h3 class="fw-bold mb-0">
                    Riwayat Surat Terkirim
                </h3>

                <span class="badge bg-primary rounded-pill px-3 py-2">
                    {{ $letters->count() }} Surat
                </span>

            </div>



            @forelse($letters as $letter)

                <div class="list-group-item py-4 border-bottom">

                    <div class="row align-items-center">

                        {{-- =================================================
                            INFORMASI
                        ================================================== --}}

                        <div class="col-lg-8">

                            <h6 class="fw-bold mb-2">

                                {{ $letter->title }}

                            </h6>


                            <div class="mb-2">

                                @if($letter->document_type === 'SURAT_LIBUR')

                                    <span class="badge rounded-pill bg-warning text-dark px-3 py-2">

                                        Surat Libur

                                    </span>

                                @elseif($letter->document_type === 'SURAT_DINAS')

                                    <span class="badge rounded-pill bg-primary px-3 py-2">

                                        Surat Dinas

                                    </span>

                                @elseif($letter->document_type === 'LOA')

                                    <span class="badge rounded-pill bg-success px-3 py-2">

                                        LoA

                                    </span>

                                @else

                                    <span class="badge rounded-pill bg-secondary px-3 py-2">

                                        Surat

                                    </span>

                                @endif

                            </div>


                            <small class="text-muted d-block">

                                <strong>Penerima:</strong>

                                {{ $letter->recipient_name }}

                            </small>


                            <small class="text-muted d-block">

                                <strong>Dikirim:</strong>

                                {{ $letter->uploaded_at?->format('d M Y H:i') }}

                            </small>


                            @if($letter->uploader)

                                <small class="text-muted d-block">

                                    <strong>Pengirim:</strong>

                                    {{ $letter->uploader->name }}

                                </small>

                            @endif


                            @if($letter->description)

                                <div class="mt-3">

                                    <small class="text-muted">

                                        {{ $letter->description }}

                                    </small>

                                </div>

                            @endif

                        </div>



                        {{-- =================================================
                            ACTION
                        ================================================== --}}

                        <div class="col-lg-4 mt-3 mt-lg-0">

                            <div class="d-flex justify-content-lg-end gap-2">

                                @if($letter->file_path)

                                    <a
                                        href="{{ $letter->file_url }}"
                                        target="_blank"
                                        class="btn btn-primary">

                                        <i class="fa-solid fa-eye me-1"></i>

                                        Lihat

                                    </a>

                                @endif


                                <form
                                    action="{{ route('kurikulum.surat.destroy', $letter->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus surat ini?')">

                                    @csrf

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger">

                                        <i class="fa-solid fa-trash me-1"></i>

                                        Hapus

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center py-5">

                    <i class="fa-regular fa-envelope fa-3x text-muted mb-3"></i>

                    <h5 class="fw-bold">
                        Belum Ada Surat
                    </h5>

                    <p class="text-muted mb-0">
                        Belum ada surat yang dikirim.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>



{{-- =========================================================
    JAVASCRIPT
========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const penerima = document.getElementById('penerima');

    const teacherContainer =
        document.getElementById('teacherSelectContainer');

    const teacherSelect =
        document.getElementById('teacher_id');


    function toggleTeacherSelect() {

        if (penerima.value === 'GURU_TERTENTU') {

            teacherContainer.style.display = 'block';

            teacherSelect.required = true;

        } else {

            teacherContainer.style.display = 'none';

            teacherSelect.required = false;

            teacherSelect.value = '';

        }

    }


    penerima.addEventListener(
        'change',
        toggleTeacherSelect
    );


    toggleTeacherSelect();

});

</script>

@endsection