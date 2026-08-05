@extends('layouts.kurikulum')

@section('title', 'Materi & Silabus')

@section('content')

<div class="container-fluid">

    {{-- =========================
        HEADER
    ========================== --}}
    <div class="mb-4">

        <h3 class="fw-bold mb-1">
            Materi & Silabus
        </h3>

        <small class="text-muted">
            Materi, Silabus, dan Kosakata dibuat oleh Kurikulum.
            Guru hanya dapat melihat, mengunduh, dan menggunakan materi
            sebagai acuan mengajar.
        </small>

    </div>


    {{-- =========================
        SUCCESS MESSAGE
    ========================== --}}

    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- =========================
        ERROR MESSAGE
    ========================== --}}

    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- =========================
        VALIDATION ERROR
    ========================== --}}

    @if($errors->any())

        <div class="alert alert-danger alert-dismissible fade show">

            <strong>Terjadi Kesalahan :</strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

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
        FORM UPLOAD MATERI BARU
    ========================================================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <h4 class="fw-bold">
                Upload Materi Baru
            </h4>

            <small class="text-muted">
                Materi yang diupload akan digunakan seluruh guru.
            </small>


            <form
                action="{{ route('kurikulum.materi.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-4">

                @csrf


                {{-- PILIH KELAS --}}

                <div class="mb-3">

                    <label class="form-label">
                        Pilih Kelas
                    </label>

                    <select
                        name="class_id"
                        class="form-select"
                        required>

                        <option value="">
                            -- Pilih Kelas --
                        </option>

                        @foreach($classes as $class)

                            <option
                                value="{{ $class->id }}"
                                {{ old('class_id') == $class->id ? 'selected' : '' }}>

                                {{ $class->programPackage?->program?->program_name ?? '-' }}
                                -
                                {{ $class->class_name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- PERTEMUAN --}}

                <div class="mb-3">

                    <label class="form-label">
                        Pertemuan
                    </label>

                    <input
                        type="number"
                        name="meeting_number"
                        class="form-control"
                        value="{{ old('meeting_number') }}"
                        min="1"
                        required>

                </div>


                {{-- JUDUL --}}

                <div class="mb-3">

                    <label class="form-label">
                        Judul Materi
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="{{ old('title') }}"
                        required>

                </div>


                {{-- SILABUS --}}

                <div class="mb-3">

                    <label class="form-label">
                        Silabus
                    </label>

                    <textarea
                        name="syllabus"
                        rows="5"
                        class="form-control">{{ old('syllabus') }}</textarea>

                </div>


                {{-- FILE --}}

                <div class="mb-3">

                    <label class="form-label">
                        Upload File Materi
                    </label>

                    <input
                        type="file"
                        name="material_file"
                        class="form-control"
                        accept=".pdf,.doc,.docx,.ppt,.pptx"
                        required>

                    <small class="text-muted">
                        Maksimal 10 MB
                    </small>

                </div>


                {{-- VOCABULARY --}}

                <div class="mb-3">

                    <label class="form-label">
                        Daftar Kosakata
                    </label>

                    <textarea
                        name="vocabularies"
                        rows="6"
                        class="form-control"
                        placeholder="爸爸|bàba|Ayah
妈妈|māma|Ibu
哥哥|gēge|Kakak Laki-laki">{{ old('vocabularies') }}</textarea>

                    <small class="text-muted">

                        Format penulisan :

                        <br>

                        <strong>Hanzi | Pinyin | Arti</strong>

                        <br><br>

                        Contoh :

                        <br>

                        爸爸|bàba|Ayah

                        <br>

                        妈妈|māma|Ibu

                    </small>

                </div>


                {{-- BUTTON --}}

                <div class="text-end">

                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="fa-solid fa-upload me-2"></i>

                        Publish Materi

                    </button>

                </div>

            </form>

        </div>

    </div>



    {{-- =========================================================
        DAFTAR MATERI
    ========================================================== --}}

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="fw-bold mb-0">
                    Daftar Materi
                </h5>

                <span class="badge bg-primary">
                    {{ $materials->count() }} Materi
                </span>

            </div>

        </div>


        <div class="card-body p-0">

            @forelse($materials as $material)

                <div class="list-group list-group-flush">

                    <div class="list-group-item py-4">

                        <div class="row">

                            {{-- =================================================
                                INFORMASI MATERI
                            ================================================== --}}

                            <div class="col-lg-9">

                                <h5 class="fw-bold mb-2">
                                    {{ $material->title }}
                                </h5>


                                <div class="mb-2">

                                    <span class="badge bg-primary">

                                        Pertemuan
                                        {{ $material->meeting_number }}

                                    </span>

                                </div>


                                <div class="text-muted mb-1">

                                    <strong>Kelas :</strong>

                                    {{ $material->classroom?->class_name ?? '-' }}

                                </div>


                                <div class="text-muted mb-1">

                                    <strong>Program :</strong>

                                    {{ $material->classroom?->programPackage?->program?->program_name ?? '-' }}

                                </div>


                                <div class="text-muted mb-1">

                                    <strong>Uploader :</strong>

                                    {{ $material->uploader?->name ?? '-' }}

                                </div>


                                <div class="text-muted mb-3">

                                    <strong>Tanggal Upload :</strong>

                                    {{ $material->created_at?->format('d M Y H:i') }}

                                </div>


                                {{-- SILABUS --}}

                                @if($material->syllabus)

                                    <div class="mb-3">

                                        <strong>
                                            Silabus
                                        </strong>

                                        <div class="border rounded p-3 bg-light mt-2">

                                            {!! nl2br(e($material->syllabus)) !!}

                                        </div>

                                    </div>

                                @endif


                                {{-- VOCABULARY PREVIEW --}}

                                @if($material->vocabularies->count())

                                    <div class="mb-3">

                                        <strong>
                                            Kosakata
                                        </strong>

                                        <div class="border rounded p-3 bg-light mt-2">

                                            <div class="row">

                                                @foreach($material->vocabularies as $vocab)

                                                    <div class="col-md-6 mb-2">

                                                        <strong>
                                                            {{ $vocab->hanzi }}
                                                        </strong>

                                                        <span class="text-muted">
                                                            ({{ $vocab->pinyin }})
                                                        </span>

                                                        -
                                                        {{ $vocab->meaning }}

                                                    </div>

                                                @endforeach

                                            </div>

                                        </div>

                                    </div>

                                @endif

                            </div>


                            {{-- =================================================
                                ACTION BUTTON
                            ================================================== --}}

                            <div class="col-lg-3">

                                <div class="d-grid gap-2">


                                    {{-- DOWNLOAD / LIHAT FILE --}}

                                    <a
                                        href="{{ $material->material_url }}"
                                        target="_blank"
                                        class="btn btn-primary">

                                        <i class="fa-solid fa-download me-2"></i>

                                        Download Materi

                                    </a>


                                    {{-- =================================================
                                        EDIT
                                    ================================================== --}}

                                    <button
                                        type="button"
                                        class="btn btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editMaterialModal{{ $material->id }}">

                                        <i class="fa-solid fa-pen me-2"></i>

                                        Edit

                                    </button>


                                    {{-- =================================================
                                        DELETE
                                    ================================================== --}}

                                    <form
                                        action="{{ route('kurikulum.materi.destroy', $material->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus materi ini?')">

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger w-100">

                                            <i class="fa-solid fa-trash me-2"></i>

                                            Hapus

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =========================================================
                    MODAL EDIT MATERI
                ========================================================== --}}

                <div
                    class="modal fade"
                    id="editMaterialModal{{ $material->id }}"
                    tabindex="-1"
                    aria-labelledby="editMaterialModalLabel{{ $material->id }}"
                    aria-hidden="true">

                    <div class="modal-dialog modal-lg modal-dialog-scrollable">

                        <div class="modal-content">


                            {{-- MODAL HEADER --}}

                            <div class="modal-header">

                                <div>

                                    <h5
                                        class="modal-title fw-bold"
                                        id="editMaterialModalLabel{{ $material->id }}">

                                        Edit Materi

                                    </h5>

                                    <small class="text-muted">

                                        Pertemuan
                                        {{ $material->meeting_number }}

                                        -
                                        {{ $material->classroom?->class_name ?? '-' }}

                                    </small>

                                </div>

                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal">
                                </button>

                            </div>


                            {{-- MODAL BODY --}}

                            <form
                                action="{{ route('kurikulum.materi.update', $material->id) }}"
                                method="POST"
                                enctype="multipart/form-data">

                                @csrf

                                @method('PUT')


                                <div class="modal-body">


                                    {{-- PILIH KELAS --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            Pilih Kelas
                                        </label>

                                        <select
                                            name="class_id"
                                            class="form-select"
                                            required>

                                            @foreach($classes as $class)

                                                <option
                                                    value="{{ $class->id }}"
                                                    {{ $material->class_id == $class->id ? 'selected' : '' }}>

                                                    {{ $class->programPackage?->program?->program_name ?? '-' }}
                                                    -
                                                    {{ $class->class_name }}

                                                </option>

                                            @endforeach

                                        </select>

                                    </div>


                                    {{-- PERTEMUAN --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            Pertemuan
                                        </label>

                                        <input
                                            type="number"
                                            name="meeting_number"
                                            class="form-control"
                                            min="1"
                                            value="{{ $material->meeting_number }}"
                                            required>

                                    </div>


                                    {{-- JUDUL --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            Judul Materi
                                        </label>

                                        <input
                                            type="text"
                                            name="title"
                                            class="form-control"
                                            value="{{ $material->title }}"
                                            required>

                                    </div>


                                    {{-- SILABUS --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            Silabus
                                        </label>

                                        <textarea
                                            name="syllabus"
                                            rows="6"
                                            class="form-control">{{ $material->syllabus }}</textarea>

                                    </div>


                                    {{-- FILE SAAT INI --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            File Materi Saat Ini
                                        </label>

                                        <div class="border rounded p-3 bg-light">

                                            <div class="d-flex justify-content-between align-items-center">

                                                <div>

                                                    <i class="fa-solid fa-file-lines me-2"></i>

                                                    {{ basename($material->material_file_path) }}

                                                </div>


                                                <a
                                                    href="{{ $material->material_url }}"
                                                    target="_blank"
                                                    class="btn btn-sm btn-primary">

                                                    <i class="fa-solid fa-eye me-1"></i>

                                                    Lihat

                                                </a>

                                            </div>

                                        </div>

                                    </div>


                                    {{-- GANTI FILE --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            Ganti File Materi
                                        </label>

                                        <input
                                            type="file"
                                            name="material_file"
                                            class="form-control"
                                            accept=".pdf,.doc,.docx,.ppt,.pptx">

                                        <small class="text-muted">

                                            Kosongkan jika tidak ingin mengganti file.

                                            <br>

                                            Maksimal 10 MB.

                                        </small>

                                    </div>


                                    {{-- VOCABULARY --}}

                                    <div class="mb-3">

                                        <label class="form-label fw-semibold">
                                            Daftar Kosakata
                                        </label>

                                        <textarea
                                            name="vocabularies"
                                            rows="10"
                                            class="form-control"
                                            placeholder="爸爸|bàba|Ayah
妈妈|māma|Ibu">{{ $material->vocabularies
    ->map(function ($vocab) {
        return $vocab->hanzi . '|' . $vocab->pinyin . '|' . $vocab->meaning;
    })
    ->implode("\n") }}</textarea>

                                        <small class="text-muted">

                                            Format:

                                            <strong>
                                                Hanzi | Pinyin | Arti
                                            </strong>

                                            <br>

                                            Satu kosakata per baris.

                                        </small>

                                    </div>


                                    {{-- INFO --}}

                                    <div class="alert alert-warning mb-0">

                                        <i class="fa-solid fa-circle-info me-2"></i>

                                        Jika kosakata diubah, data kosakata lama
                                        akan diganti dengan data yang baru.

                                    </div>

                                </div>


                                {{-- MODAL FOOTER --}}

                                <div class="modal-footer">

                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        data-bs-dismiss="modal">

                                        Batal

                                    </button>


                                    <button
                                        type="submit"
                                        class="btn btn-success">

                                        <i class="fa-solid fa-save me-2"></i>

                                        Simpan Perubahan

                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>


            @empty


                {{-- =========================================================
                    EMPTY DATA
                ========================================================== --}}

                <div class="text-center py-5">

                    <img
                        src="https://cdn-icons-png.flaticon.com/512/7486/7486740.png"
                        width="120"
                        class="mb-3">

                    <h5 class="fw-bold">
                        Belum Ada Materi
                    </h5>

                    <p class="text-muted mb-0">
                        Silakan upload materi pertama.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection