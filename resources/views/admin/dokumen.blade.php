@extends('admin.app')

@section('title', 'Dokumen | Laporan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin-extra.css') }}">
    <style>
        .x-flist { display: flex; flex-direction: column; gap: 12px; }
        .x-frow {
            display: flex; align-items: center; gap: 12px;
            background: #fff; border: 1px solid #eceef3; border-radius: 14px;
            padding: 6px 14px 6px 6px; transition: .15s;
        }
        .x-frow:hover { border-color: #f5c400; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .x-frow-link {
            flex: 1; display: flex; align-items: center; gap: 16px;
            padding: 10px; text-decoration: none; color: inherit;
        }
        .x-frow-icon {
            width: 48px; height: 48px; border-radius: 12px; background: #fff4c2;
            color: #9a6a00; display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .x-frow-name { font-weight: 600; font-size: 16px; }
        .x-frow-count { font-size: 13px; color: #8a8f9c; }
        .x-frow-arrow { margin-left: auto; color: #b3b7c2; }
        .x-frow form { margin: 0; }
    </style>
@endpush

@section('content')

    @php
        $visibilityBadge = [
            'Private' => 'badge-hot',
            'Teacher' => 'badge-cold',
            'Student' => 'badge-warning',
            'Public'  => 'badge-completed',
        ];
        $defaultFolder = $folder ?? $folders->keys()->first();
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">LAPORAN</div>
            <h1>Dokumen</h1>
            <p>
                @if ($current)
                    Folder aktif: <strong>{{ $current->label }}</strong>
                @else
                    Arsip dokumen, dikelompokkan dalam folder. Buka folder untuk melihat isinya.
                @endif
            </p>
        </div>

        <div class="dashboard-header-actions">
            @if ($current)
                <a href="{{ route('admin.dokumen') }}" class="btn">
                    <i class="fa-solid fa-arrow-left"></i> Semua Folder
                </a>
            @else
                <button type="button" class="btn" onclick="openFolderModal()">
                    <i class="fa-solid fa-folder-plus"></i> Folder Baru
                </button>
            @endif
            <button type="button" class="btn btn-primary" onclick="openDocModal()">
                <i class="fa-solid fa-upload"></i> Unggah Dokumen
            </button>
        </div>
    </div>

    @include('admin.partials.flash')

    @if (! $current)
        {{-- ========================= DAFTAR FOLDER (KE BAWAH) ========================= --}}
        <div class="x-flist">
            @forelse ($folders as $slug => $meta)
                @php $count = (int) $counts->get($meta->document_type, 0); @endphp
                <div class="x-frow">
                    <a href="{{ route('admin.dokumen', ['folder' => $slug]) }}" class="x-frow-link">
                        <div class="x-frow-icon"><i class="fa-solid {{ $meta->icon }}"></i></div>
                        <div>
                            <div class="x-frow-name">{{ $meta->label }}</div>
                            <div class="x-frow-count">{{ $count }} dokumen</div>
                        </div>
                        <i class="fa-solid fa-chevron-right x-frow-arrow"></i>
                    </a>

                    @if ($count === 0)
                        <form method="POST" action="{{ route('admin.dokumen.folder.destroy', $meta) }}"
                              onsubmit="return confirm('Hapus folder &quot;{{ addslashes($meta->label) }}&quot;?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-btn x-btn-danger" title="Hapus folder">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="card x-empty">
                    <i class="fa-regular fa-folder-open"></i> Belum ada folder.
                </div>
            @endforelse
        </div>
    @else
        {{-- ========================= FILTER ========================= --}}
        <form method="GET" action="{{ route('admin.dokumen') }}" id="filterForm">
            <input type="hidden" name="folder" value="{{ $folder }}">

            <div class="toolbar">
                <div class="toolbar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari judul atau deskripsi">
                </div>

                <select name="owner_id" class="select-chip" onchange="this.form.submit()">
                    <option value="">Semua Pemilik</option>
                    @foreach ($owners as $group => $people)
                        @if ($people->isNotEmpty())
                            <optgroup label="{{ $group }}">
                                @foreach ($people as $person)
                                    <option value="{{ $person->user_id }}" {{ request('owner_id') == $person->user_id ? 'selected' : '' }}>
                                        {{ $person->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>

                <a href="{{ route('admin.dokumen', ['folder' => $folder]) }}" class="btn">
                    <i class="fa-solid fa-rotate-right"></i> Reset
                </a>
            </div>
        </form>

        {{-- ========================= TABEL ISI FOLDER ========================= --}}
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>Pemilik</th>
                        <th>Akses</th>
                        <th>Diunggah</th>
                        <th width="170" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($documents as $doc)
                        @php
                            $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                            [$fileIcon, $fileClass] = match ($ext) {
                                'pdf' => ['fa-file-pdf', 'pdf'],
                                'doc', 'docx' => ['fa-file-word', 'word'],
                                'xls', 'xlsx' => ['fa-file-excel', 'excel'],
                                'ppt', 'pptx' => ['fa-file-powerpoint', 'ppt'],
                                'jpg', 'jpeg', 'png' => ['fa-file-image', 'image'],
                                default => ['fa-file', ''],
                            };

                            $u = $doc->user;
                            $ownerName = $u ? ($u->teacher?->name ?? $u->curriculum?->name ?? $u->student?->name ?? $u->username) : null;
                            $ownerRole = $u ? ($u->teacher ? 'Guru' : ($u->curriculum ? 'Kurikulum' : ($u->student ? 'Siswa' : null))) : null;

                            $uploadedAt = $doc->uploaded_at ?? $doc->created_at;

                            $editData = [
                                'update_url'  => route('admin.dokumen.update', $doc),
                                'title'       => $doc->title,
                                'folder'      => $folder,
                                'user_id'     => $doc->user_id,
                                'visibility'  => $doc->visibility,
                                'description' => $doc->description,
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="x-file">
                                    <div class="x-file-icon {{ $fileClass }}"><i class="fa-regular {{ $fileIcon }}"></i></div>
                                    <div>
                                        <div class="cand-name">{{ $doc->title }}</div>
                                        <div class="cand-sub">
                                            {{ strtoupper($ext) }}@if ($doc->description) · {{ \Illuminate\Support\Str::limit($doc->description, 50) }}@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($ownerName)
                                    {{ $ownerName }}
                                    @if ($ownerRole)<div class="cand-sub">{{ $ownerRole }}</div>@endif
                                @else
                                    <span class="x-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $visibilityBadge[$doc->visibility] ?? '' }}">
                                    {{ $visibilities[$doc->visibility] ?? $doc->visibility }}
                                </span>
                            </td>
                            <td>
                                {{ $uploadedAt ? \Carbon\Carbon::parse($uploadedAt)->format('d M Y') : '-' }}
                                <div class="cand-sub">oleh {{ $doc->uploader?->username ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('admin.dokumen.lihat', $doc) }}" target="_blank" rel="noopener" class="icon-btn" title="Lihat">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.dokumen.unduh', $doc) }}" class="icon-btn" title="Unduh">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                    <button type="button" class="icon-btn" title="Edit"
                                            data-doc="{{ json_encode($editData) }}" onclick="openDocEditModal(this)">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.dokumen.destroy', $doc) }}" class="x-inline-form"
                                          onsubmit="return confirm('Hapus dokumen &quot;{{ addslashes($doc->title) }}&quot;? File juga akan dihapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn x-btn-danger" title="Hapus">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="x-empty">
                                <i class="fa-regular fa-folder-open"></i>
                                Folder ini masih kosong.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @include('admin.partials.pagination', ['paginator' => $documents, 'noun' => 'dokumen'])
        </div>
    @endif

    {{-- ========================= MODAL FOLDER BARU ========================= --}}
    <div class="modal-overlay" id="folderModalOverlay">
        <div class="modal">
            <form method="POST" action="{{ route('admin.dokumen.folder.store') }}">
                @csrf
                <input type="hidden" name="_form" value="folder">

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Folder Baru</div>
                        <div class="modal-sub">Folder akan muncul di daftar dan bisa langsung diisi dokumen.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeFolderModal()" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Folder *</label>
                                <input type="text" name="label" value="{{ old('label') }}" required maxlength="60"
                                       placeholder="mis. Surat Perjanjian">
                            </div>
                            <div class="form-field full">
                                <label>Ikon <span class="opt">(opsional)</span></label>
                                <select name="icon">
                                    @foreach ($icons as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeFolderModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Buat Folder</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL UNGGAH / EDIT ========================= --}}
    <div class="modal-overlay" id="docModalOverlay">
        <div class="modal">
            <form id="docForm" method="POST" action="{{ route('admin.dokumen.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="doc_method" disabled>
                <input type="hidden" name="_form" value="doc">

                <div class="modal-head">
                    <div>
                        <div class="modal-title" id="doc_modal_title">Unggah Dokumen</div>
                        <div class="modal-sub">Maksimal 10 MB. Format: PDF, Word, Excel, PowerPoint, JPG, PNG.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeDocModal()" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Judul Dokumen *</label>
                                <input type="text" name="title" id="doc_title" value="{{ old('title') }}" required maxlength="255"
                                       placeholder="mis. CV Vanessa Tan">
                            </div>

                            <div class="form-field">
                                <label>Folder *</label>
                                <select name="folder" id="doc_folder" required>
                                    @foreach ($folders as $slug => $meta)
                                        <option value="{{ $slug }}" {{ old('folder', $defaultFolder) === $slug ? 'selected' : '' }}>
                                            {{ $meta->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Pemilik <span class="opt">(opsional)</span></label>
                                <select name="user_id" id="doc_user_id">
                                    <option value="">Tanpa pemilik</option>
                                    @foreach ($owners as $group => $people)
                                        @if ($people->isNotEmpty())
                                            <optgroup label="{{ $group }}">
                                                @foreach ($people as $person)
                                                    <option value="{{ $person->user_id }}" {{ old('user_id') == $person->user_id ? 'selected' : '' }}>
                                                        {{ $person->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field full">
                                <label>Siapa yang boleh melihat *</label>
                                <select name="visibility" id="doc_visibility" required>
                                    @foreach ($visibilities as $value => $label)
                                        <option value="{{ $value }}" {{ old('visibility', 'Private') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field full">
                                <label>Deskripsi <span class="opt">(opsional)</span></label>
                                <textarea name="description" id="doc_description" rows="3" maxlength="1000">{{ old('description') }}</textarea>
                            </div>

                            <div class="form-field full" id="doc_file_wrap">
                                <label>File *</label>
                                <input type="file" name="file" id="doc_file" required
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeDocModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> <span id="doc_submit_label">Simpan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            if (!searchInput || !filterForm) return;
            let timer;

            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => filterForm.submit(), 600);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); filterForm.submit(); }
            });
        })();

        const docStoreUrl = @json(route('admin.dokumen.store'));
        const docDefaultFolder = @json($defaultFolder);

        function openDocModal() {
            const form = document.getElementById('docForm');
            form.reset();
            form.action = docStoreUrl;

            document.getElementById('doc_method').disabled = true;
            document.getElementById('doc_modal_title').textContent = 'Unggah Dokumen';
            document.getElementById('doc_submit_label').textContent = 'Unggah';
            document.getElementById('doc_file_wrap').style.display = '';
            document.getElementById('doc_file').disabled = false;
            document.getElementById('doc_file').required = true;
            document.getElementById('doc_folder').value = docDefaultFolder;

            document.getElementById('docModalOverlay').classList.add('open');
        }

        function openDocEditModal(button) {
            const d = JSON.parse(button.dataset.doc);
            const form = document.getElementById('docForm');

            form.action = d.update_url;
            document.getElementById('doc_method').disabled = false;
            document.getElementById('doc_modal_title').textContent = 'Edit Dokumen';
            document.getElementById('doc_submit_label').textContent = 'Simpan Perubahan';

            document.getElementById('doc_file_wrap').style.display = 'none';
            document.getElementById('doc_file').disabled = true;
            document.getElementById('doc_file').required = false;

            document.getElementById('doc_title').value = d.title ?? '';
            document.getElementById('doc_folder').value = d.folder ?? docDefaultFolder;
            document.getElementById('doc_user_id').value = d.user_id ?? '';
            document.getElementById('doc_visibility').value = d.visibility ?? 'Private';
            document.getElementById('doc_description').value = d.description ?? '';

            document.getElementById('docModalOverlay').classList.add('open');
        }

        function closeDocModal() {
            document.getElementById('docModalOverlay').classList.remove('open');
        }

        function openFolderModal() {
            document.getElementById('folderModalOverlay').classList.add('open');
        }

        function closeFolderModal() {
            document.getElementById('folderModalOverlay').classList.remove('open');
        }

        ['docModalOverlay', 'folderModalOverlay'].forEach(function (id) {
            document.getElementById(id).addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('open');
            });
        });

        @if ($errors->any() && old('_form') === 'doc')
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('docModalOverlay').classList.add('open');
            });
        @endif
        @if ($errors->any() && old('_form') === 'folder')
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('folderModalOverlay').classList.add('open');
            });
        @endif
    </script>
@endpush