@extends('layouts.kurikulum')

@section('title', 'SOP')

@section('content')

@php
    // Untuk membuka kembali modal yang gagal disimpan & mencegah isian bocor ke form lain
    $editId   = (string) old('_edit_id');
    $editKind = (string) old('_edit_kind');
    $addErr   = fn (string $field) => $editId === '' ? $errors->first($field) : '';
@endphp

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">SOP</h2>
        <p class="text-muted mb-0">
            Kelola folder SOP — upload baru, upload ulang, atau hapus. SOP langsung bisa dibaca semua guru.
        </p>
    </div>

    {{-- PESAN --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan.</strong>
            <ul class="mt-2 mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- TAMBAH SOP --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h3 class="fw-bold mb-4">Tambah SOP Baru</h3>

            <form action="{{ route('kurikulum.sop.store') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="return lockSubmit(this, 'Mengunggah...')">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul SOP</label>
                    <input type="text" name="title" maxlength="255"
                           class="form-control {{ $addErr('title') ? 'is-invalid' : '' }}"
                           value="{{ $editId === '' ? old('title') : '' }}"
                           placeholder="Contoh: SOP Onboarding Guru Baru" required>
                    @if ($addErr('title'))
                        <div class="invalid-feedback">{{ $addErr('title') }}</div>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">File</label>
                    <input type="file" name="file"
                           class="form-control {{ $addErr('file') ? 'is-invalid' : '' }}"
                           accept=".pdf" required>
                    <small class="text-muted">Format PDF, maksimal 5 MB.</small>
                    @if ($addErr('file'))
                        <div class="invalid-feedback d-block">{{ $addErr('file') }}</div>
                    @endif
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-upload me-2"></i> Upload SOP Baru
                </button>
            </form>
        </div>
    </div>

    {{-- DAFTAR SOP --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Daftar SOP</h5>
            <span class="badge bg-primary">{{ $sops->count() }} SOP</span>
        </div>

        <div class="card-body p-0">
            @forelse ($sops as $sop)
                <div class="border-bottom px-4 py-3">
                    <div class="row align-items-center">

                        <div class="col-md-7">
                            <h6 class="fw-semibold mb-1">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i>{{ $sop->title }}
                                @unless ($sop->file_exists)
                                    <span class="badge bg-danger ms-2">File hilang</span>
                                @endunless
                            </h6>
                            <small class="text-muted">
                                PDF · {{ $sop->file_size_label }}
                                · diperbarui {{ ($sop->uploaded_at ?? $sop->updated_at)?->diffForHumans() }}
                                @if ($sop->uploader) · oleh {{ $sop->uploader->username }} @endif
                            </small>
                        </div>

                        <div class="col-md-5">
                            <div class="d-flex justify-content-md-end justify-content-start flex-wrap gap-2 mt-3 mt-md-0">
                                @if ($sop->file_exists)
                                    <a href="{{ route('kurikulum.sop.show', $sop) }}" target="_blank"
                                       class="btn btn-light border btn-sm">Buka</a>
                                    <a href="{{ route('kurikulum.sop.download', $sop) }}"
                                       class="btn btn-light border btn-sm">Download</a>
                                @endif
                                <button type="button" class="btn btn-light border btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#editModal{{ $sop->id }}">
                                    Upload Ulang
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal{{ $sop->id }}">
                                    Hapus
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fa-solid fa-folder-open fa-3x text-secondary mb-3"></i>
                    <h5 class="fw-bold">Belum Ada SOP</h5>
                    <p class="text-muted mb-0">Silakan upload SOP pertama Anda.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- UPLOAD TEMPLATE PROGRESS REPORT --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h4 class="fw-bold mb-2">Template Progress Report</h4>
            <p class="text-muted small mb-4">
                Template ini tampil di halaman <strong>SOP guru</strong> (bagian Template Progress Report) dan bisa diunduh guru.
                Mengunggah dengan Jenis Kelas yang sama akan <strong>menggantikan</strong> file lama.
            </p>

            <form action="{{ route('kurikulum.template.upload') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="return lockSubmit(this, 'Mengunggah...')">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis Kelas</label>
                    <select name="name" class="form-select {{ $addErr('name') ? 'is-invalid' : '' }}" required>
                        <option value="">-- Pilih Jenis Kelas --</option>
                        @foreach ($classTypes as $type)
                            <option value="{{ $type }}" @selected($editId === '' && old('name') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @if ($addErr('name'))
                        <div class="invalid-feedback">{{ $addErr('name') }}</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">File Template (.docx / .xlsx / .pdf)</label>
                    <input type="file" name="file" class="form-control" accept=".doc,.docx,.xls,.xlsx,.pdf" required>
                    <small class="text-muted">Maksimal 10 MB.</small>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-upload me-2"></i> Upload / Perbarui Template
                </button>
            </form>
        </div>
    </div>

    {{-- DAFTAR TEMPLATE --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            @forelse ($templates as $template)
                <div class="border-bottom px-4 py-3">
                    <div class="row align-items-center">

                        <div class="col-md-7">
                            <h6 class="fw-semibold mb-1">
                                {{ $template->name }}
                                @if ($template->status === 'Active')
                                    <span class="badge bg-success ms-1">Aktif</span>
                                @else
                                    <span class="badge bg-secondary ms-1">Nonaktif</span>
                                @endif
                                @unless ($template->file_exists)
                                    <span class="badge bg-danger ms-1">File hilang</span>
                                @endunless
                            </h6>
                            <small class="text-muted">
                                {{ strtoupper($template->file_extension) }} · {{ $template->file_size_label }}
                                · diperbarui {{ $template->updated_at->diffForHumans() }}
                                @if ($template->uploader) · oleh {{ $template->uploader->username }} @endif
                            </small>
                        </div>

                        <div class="col-md-5">
                            <div class="d-flex justify-content-md-end justify-content-start flex-wrap gap-2 mt-3 mt-md-0">
                                @if ($template->file_exists)
                                    <a href="{{ route('kurikulum.template.download', $template) }}"
                                       class="btn btn-light border btn-sm">Download</a>
                                @endif
                                <button type="button" class="btn btn-light border btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#templateModal{{ $template->id }}">
                                    Upload Ulang
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#templateDeleteModal{{ $template->id }}">
                                    Hapus
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fa-solid fa-file-word fa-3x text-secondary mb-3"></i>
                    <h5 class="fw-bold">Belum Ada Template Progress Report</h5>
                    <p class="text-muted mb-0">Silakan upload template terlebih dahulu.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL UPLOAD ULANG SOP --}}
    @foreach ($sops as $sop)
        @php $useOld = $editKind === 'sop' && $editId === (string) $sop->id; @endphp

        <div class="modal fade" id="editModal{{ $sop->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('kurikulum.sop.update', $sop) }}" method="POST" enctype="multipart/form-data"
                          onsubmit="return lockSubmit(this, 'Menyimpan...')">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_edit_id" value="{{ $sop->id }}">
                        <input type="hidden" name="_edit_kind" value="sop">

                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Upload Ulang SOP</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Judul SOP</label>
                                <input type="text" name="title" maxlength="255"
                                       class="form-control {{ $useOld && $errors->has('title') ? 'is-invalid' : '' }}"
                                       value="{{ $useOld ? old('title', $sop->title) : $sop->title }}" required>
                                @if ($useOld && $errors->has('title'))
                                    <div class="invalid-feedback">{{ $errors->first('title') }}</div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">File Saat Ini</label>
                                <div class="border rounded bg-light p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <i class="fa-solid fa-file-pdf text-danger me-2"></i>
                                        PDF · {{ $sop->file_size_label }}
                                        @unless ($sop->file_exists)
                                            <span class="badge bg-danger ms-1">File hilang di server</span>
                                        @endunless
                                    </div>
                                    @if ($sop->file_exists)
                                        <a href="{{ route('kurikulum.sop.show', $sop) }}" target="_blank"
                                           class="btn btn-sm btn-outline-primary">Lihat</a>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Upload File Baru</label>
                                <input type="file" name="file"
                                       class="form-control {{ $useOld && $errors->has('file') ? 'is-invalid' : '' }}"
                                       accept=".pdf">
                                <small class="text-muted">Kosongkan jika hanya ingin mengubah judul. PDF, maksimal 5 MB.</small>
                                @if ($useOld && $errors->has('file'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('file') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- MODAL UPLOAD ULANG TEMPLATE --}}
    @foreach ($templates as $template)
        @php $useOld = $editKind === 'template' && $editId === (string) $template->id; @endphp

        <div class="modal fade" id="templateModal{{ $template->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('kurikulum.template.update', $template) }}" method="POST" enctype="multipart/form-data"
                          onsubmit="return lockSubmit(this, 'Menyimpan...')">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_edit_id" value="{{ $template->id }}">
                        <input type="hidden" name="_edit_kind" value="template">

                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Upload Ulang Template</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label fw-semibold">Jenis Kelas</label>
                                    <select name="name" class="form-select {{ $useOld && $errors->has('name') ? 'is-invalid' : '' }}" required>
                                        @foreach ($classTypes as $type)
                                            <option value="{{ $type }}"
                                                @selected(($useOld ? old('name', $template->name) : $template->name) === $type)>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($useOld && $errors->has('name'))
                                        <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="status" class="form-select" required>
                                        @php $currentStatus = $useOld ? old('status', $template->status) : $template->status; @endphp
                                        <option value="Active" @selected($currentStatus === 'Active')>Aktif (tampil di guru)</option>
                                        <option value="Inactive" @selected($currentStatus === 'Inactive')>Nonaktif</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">File Saat Ini</label>
                                <div class="border rounded bg-light p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="badge bg-secondary">{{ strtoupper($template->file_extension) }}</span>
                                        {{ $template->file_size_label }}
                                        @unless ($template->file_exists)
                                            <span class="badge bg-danger ms-1">File hilang di server</span>
                                        @endunless
                                    </div>
                                    @if ($template->file_exists)
                                        <a href="{{ route('kurikulum.template.download', $template) }}"
                                           class="btn btn-sm btn-outline-primary">Download</a>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Upload File Baru</label>
                                <input type="file" name="file"
                                       class="form-control {{ $useOld && $errors->has('file') ? 'is-invalid' : '' }}"
                                       accept=".doc,.docx,.xls,.xlsx,.pdf">
                                <small class="text-muted">Kosongkan jika file tidak diganti. Maksimal 10 MB.</small>
                                @if ($useOld && $errors->has('file'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('file') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- MODAL HAPUS SOP --}}
    @foreach ($sops as $sop)
        <div class="modal fade" id="deleteModal{{ $sop->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('kurikulum.sop.destroy', $sop) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <div class="modal-header">
                            <h5 class="modal-title text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> Hapus SOP
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <p>Apakah Anda yakin ingin menghapus SOP berikut?</p>
                            <div class="border rounded p-3 bg-light"><strong>{{ $sop->title }}</strong></div>
                            <div class="alert alert-warning mt-3 mb-0">
                                SOP akan hilang dari halaman semua guru, dan file dihapus dari server.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-trash me-2"></i> Ya, Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- MODAL HAPUS TEMPLATE --}}
    @foreach ($templates as $template)
        <div class="modal fade" id="templateDeleteModal{{ $template->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('kurikulum.template.destroy', $template) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <div class="modal-header">
                            <h5 class="modal-title text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> Hapus Template
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <p>Hapus template berikut?</p>
                            <div class="border rounded p-3 bg-light"><strong>{{ $template->name }}</strong></div>
                            <div class="alert alert-warning mt-3 mb-0">
                                Template akan hilang dari halaman guru, dan file dihapus dari server.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-trash me-2"></i> Ya, Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

</div>

<script>
    function lockSubmit(form, text) {
        const btn = form.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.innerText = text; }
        return true;
    }

    @if ($editId !== '')
        // Jika simpan gagal validasi, buka kembali modal yang sama
        document.addEventListener('DOMContentLoaded', function () {
            const id = '{{ $editKind === 'template' ? 'templateModal' : 'editModal' }}{{ (int) $editId }}';
            const el = document.getElementById(id);
            if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
        });
    @endif
</script>

@push('styles')
<style>
/* baris terakhir di daftar tidak perlu garis bawah */
.card-body.p-0 > .border-bottom:last-child {
    border-bottom: none !important;
}
.btn-light { background: #fff; }
.btn-light:hover { background: #f7f7f7; }

/* HP: tombol aksi menumpuk penuh */
@media (max-width: 768px) {
    .d-flex.gap-2 { flex-direction: column; width: 100%; }
    .btn-sm { width: 100%; }
}
</style>
@endpush

@endsection