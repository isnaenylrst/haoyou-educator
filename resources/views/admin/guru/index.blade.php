@extends('admin.app')

@section('title', 'Guru | CRM')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">MANAJEMEN SDM</div>
            <h1>Guru</h1>
            <p>Kelola data guru dan kurikulum, status pelatihan guru, serta akun login mereka.</p>
        </div>

        <div class="dashboard-header-actions">
            <button type="button" class="btn btn-secondary" onclick="openKurikulumModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Kurikulum
            </button>

            <button type="button" class="btn btn-primary" onclick="openGuruModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Guru
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert-error">
            {{ session('error') }}
        </div>
    @endif

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Total Guru</span>
                <i class="fa-solid fa-chalkboard-user stat-icon"></i>
            </div>
            <div class="stat-value">{{ $totalGuru }}</div>
            <div class="stat-note">Seluruh guru terdaftar</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Guru Aktif</span>
                <i class="fa-solid fa-user-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $guruAktif }}</div>
            <div class="stat-note">Status Active</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Sedang Training</span>
                <i class="fa-solid fa-graduation-cap stat-icon"></i>
            </div>
            <div class="stat-value">{{ $sedangTraining }}</div>
            <div class="stat-note">Masih dalam masa training</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Lulus Training</span>
                <i class="fa-solid fa-award stat-icon"></i>
            </div>
            <div class="stat-value">{{ $lulusTraining }}</div>
            <div class="stat-note">Status Passed</div>
        </div>
    </div>

    <div class="section-title" style="margin-top:8px;">
        <i class="fa-solid fa-chalkboard-user"></i> Data Guru
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.guru.index') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    id="searchInput"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama, no. HP, atau username guru">
            </div>

            <select name="status" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <select name="training_status" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Status Training</option>
                <option value="Training" {{ request('training_status') == 'Training' ? 'selected' : '' }}>Training</option>
                <option value="Passed" {{ request('training_status') == 'Passed' ? 'selected' : '' }}>Passed</option>
                <option value="Failed" {{ request('training_status') == 'Failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
    </form>

    <div class="card">
        <table class="table-aligned">
            <thead>
                <tr>
                    <th>Guru</th>
                    <th style="text-align: center; vertical-align: middle;">No. HP</th>
                    <th style="text-align: center; vertical-align: middle;">Tanggal Bergabung</th>
                    <th style="text-align: center; vertical-align: middle;">Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($teachers as $teacher)
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($teacher->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $teacher->name }}</div>
                                    <div class="cand-sub">{{ $teacher->user?->username ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            {{ $teacher->phone }}
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            {{ $teacher->join_date ? \Carbon\Carbon::parse($teacher->join_date)->format('d M Y') : '-' }}
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            @php
                                $statusClass = $teacher->status === 'Active' ? 'badge-completed' : 'badge-cancelled';
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                <span class="badge-dot"></span>
                                {{ $teacher->status }}
                            </span>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <div class="row-actions">
                                <a href="{{ route('admin.guru.show', $teacher->id) }}" class="icon-btn" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <button
                                    type="button"
                                    class="icon-btn"
                                    title="Edit"
                                    onclick="openEditGuruModal({{ $teacher->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <form action="{{ route('admin.guru.destroy', $teacher->id) }}" method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus guru {{ $teacher->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn danger" title="Hapus">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data guru.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $teachers->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $teachers->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $teachers->total() }}</strong>
                guru
            </span>

            <div class="table-footer-pagination">
                @if ($teachers->hasPages())
                    @php
                        $current = $teachers->currentPage();
                        $last = $teachers->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($teachers->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $teachers->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                            @endif

                            @for ($page = 1; $page <= $last; $page++)
                                @php
                                    $isEdge = $page == 1 || $page == $last;
                                    $isNearCurrent = abs($page - $current) <= $onEachSide;
                                @endphp

                                @if ($isEdge || $isNearCurrent)
                                    @if ($page == $current)
                                        <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-btn" href="{{ $teachers->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($teachers->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $teachers->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= TABEL KURIKULUM ========================= --}}
    <div class="section-title">
        <i class="fa-solid fa-book-open"></i> Data Kurikulum
        <span class="section-note">{{ $totalKurikulum }} akun</span>
    </div>

    <div class="card">
        <table class="table-aligned">
            <thead>
                <tr>
                    <th>Kurikulum</th>
                    <th style="text-align: center; vertical-align: middle;">No. HP</th>
                    <th style="text-align: center; vertical-align: middle;">Tanggal Bergabung</th>
                    <th style="text-align: center; vertical-align: middle;">Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($curriculums as $curriculum)
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($curriculum->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $curriculum->name }}</div>
                                    <div class="cand-sub">{{ $curriculum->user?->username ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            {{ $curriculum->phone }}
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            {{ $curriculum->join_date ? \Carbon\Carbon::parse($curriculum->join_date)->format('d M Y') : '-' }}
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge {{ $curriculum->status === 'Active' ? 'badge-completed' : 'badge-cancelled' }}">
                                <span class="badge-dot"></span>
                                {{ $curriculum->status }}
                            </span>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <div class="row-actions">
                                <button
                                    type="button"
                                    class="icon-btn"
                                    title="Edit"
                                    onclick="openEditKurikulumModal({{ $curriculum->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <form action="{{ route('admin.kurikulum-staf.destroy', $curriculum->id) }}" method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus kurikulum {{ $curriculum->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn danger" title="Hapus">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data kurikulum.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $curriculums->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $curriculums->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $curriculums->total() }}</strong>
                kurikulum
            </span>

            <div class="table-footer-pagination">
                @if ($curriculums->hasPages())
                    @php
                        $kCurrent = $curriculums->currentPage();
                        $kLast = $curriculums->lastPage();
                        $kSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination Kurikulum">
                        <ul class="pagination-list">
                            @if ($curriculums->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $curriculums->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                            @endif

                            @for ($kPage = 1; $kPage <= $kLast; $kPage++)
                                @php
                                    $kEdge = $kPage == 1 || $kPage == $kLast;
                                    $kNear = abs($kPage - $kCurrent) <= $kSide;
                                @endphp

                                @if ($kEdge || $kNear)
                                    @if ($kPage == $kCurrent)
                                        <li class="page-item active"><span class="page-btn current">{{ $kPage }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-btn" href="{{ $curriculums->url($kPage) }}">{{ $kPage }}</a></li>
                                    @endif
                                @elseif ($kPage == 2 && $kCurrent - $kSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($kPage == $kLast - 1 && $kCurrent + $kSide < $kLast - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($curriculums->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $curriculums->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH GURU ========================= --}}
    <div class="modal-overlay" id="guruModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.guru.store') }}" method="POST">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Guru Baru</div>
                        <div class="modal-sub">Isi data guru dan akun login-nya.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeGuruModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Guru</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-field">
                                <label>No. Telepon *</label>
                                <input type="text" name="phone" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Bergabung</label>
                                <input type="date" name="join_date">
                            </div>
                            <div class="form-field full">
                                <label>Alamat</label>
                                <textarea name="address" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Status Training & Aktivasi</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Status Training <span class="opt">(opsional)</span></label>
                                <select name="training_status">
                                    <option value="">- Belum Ditentukan -</option>
                                    <option value="Training">Training</option>
                                    <option value="Passed">Passed</option>
                                    <option value="Failed">Failed</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Status Akun *</label>
                                <select name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-key"></i> Akun Login Guru</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Username *</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-field">
                                <label>Password *</label>
                                <div style="position:relative;">
                                    <input type="password" name="password" id="add_guru_password"
                                           autocomplete="new-password" required minlength="6"
                                           style="width:100%;padding-right:44px;">
                                    <button type="button" onclick="togglePassword('add_guru_password', this)" title="Lihat password"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;color:#6b7280;">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div></div>
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeGuruModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Guru</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT GURU ========================= --}}
    <div class="modal-overlay" id="editGuruModalOverlay">
        <div class="modal">
            <form id="editGuruForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Data Guru</div>
                        <div class="modal-sub">Perbarui data guru ini.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeEditGuruModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Guru</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" id="edit_guru_name" required>
                            </div>
                            <div class="form-field">
                                <label>No. Telepon *</label>
                                <input type="text" name="phone" id="edit_guru_phone" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Bergabung</label>
                                <input type="date" name="join_date" id="edit_guru_join_date">
                            </div>
                            <div class="form-field full">
                                <label>Alamat</label>
                                <textarea name="address" id="edit_guru_address" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Status Training & Aktivasi</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Status Training</label>
                                <select name="training_status" id="edit_guru_training_status">
                                    <option value="">- Belum Ditentukan -</option>
                                    <option value="Training">Training</option>
                                    <option value="Passed">Passed</option>
                                    <option value="Failed">Failed</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Status Akun *</label>
                                <select name="status" id="edit_guru_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-key"></i> Akun Login Guru</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Username *</label>
                                <input type="text" name="username" id="edit_guru_username" required>
                            </div>
                            <div class="form-field">
                                <label>Password <span class="opt">(kosongkan jika tidak ingin diubah)</span></label>
                                <div style="position:relative;">
                                    <input type="password" name="password" id="edit_guru_password"
                                           autocomplete="new-password" minlength="6"
                                           style="width:100%;padding-right:44px;">
                                    <button type="button" onclick="togglePassword('edit_guru_password', this)" title="Lihat password"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;color:#6b7280;">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div></div>
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeEditGuruModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH KURIKULUM ========================= --}}
    <div class="modal-overlay" id="kurikulumModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.kurikulum-staf.store') }}" method="POST">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Kurikulum Baru</div>
                        <div class="modal-sub">Isi data kurikulum dan akun login-nya.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeKurikulumModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Kurikulum</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" id="add_kurikulum_name" required>
                            </div>
                            <div class="form-field">
                                <label>No. Telepon *</label>
                                <input type="text" name="phone" id="add_kurikulum_phone" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Bergabung</label>
                                <input type="date" name="join_date" id="add_kurikulum_join_date">
                            </div>
                            <div class="form-field full">
                                <label>Alamat</label>
                                <textarea name="address" id="add_kurikulum_address" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-toggle-on"></i> Aktivasi</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Status Akun *</label>
                                <select name="status" id="add_kurikulum_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-key"></i> Akun Login Kurikulum</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Username *</label>
                                <input type="text" name="username" id="add_kurikulum_username" required>
                            </div>
                            <div class="form-field">
                                <label>Password *</label>
                                <div style="position:relative;">
                                    <input type="password" name="password" id="add_kurikulum_password"
                                           autocomplete="new-password" required minlength="6"
                                           style="width:100%;padding-right:44px;">
                                    <button type="button" onclick="togglePassword('add_kurikulum_password', this)" title="Lihat password"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;color:#6b7280;">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div></div>
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeKurikulumModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Kurikulum</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT KURIKULUM ========================= --}}
    <div class="modal-overlay" id="editKurikulumModalOverlay">
        <div class="modal">
            <form id="editKurikulumForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Data Kurikulum</div>
                        <div class="modal-sub">Perbarui data kurikulum ini.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeEditKurikulumModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Kurikulum</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" id="edit_kurikulum_name" required>
                            </div>
                            <div class="form-field">
                                <label>No. Telepon *</label>
                                <input type="text" name="phone" id="edit_kurikulum_phone" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Bergabung</label>
                                <input type="date" name="join_date" id="edit_kurikulum_join_date">
                            </div>
                            <div class="form-field full">
                                <label>Alamat</label>
                                <textarea name="address" id="edit_kurikulum_address" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-toggle-on"></i> Aktivasi</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Status Akun *</label>
                                <select name="status" id="edit_kurikulum_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-key"></i> Akun Login Kurikulum</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Username *</label>
                                <input type="text" name="username" id="edit_kurikulum_username" required>
                            </div>
                            <div class="form-field">
                                <label>Password <span class="opt">(kosongkan jika tidak ingin diubah)</span></label>
                                <div style="position:relative;">
                                    <input type="password" name="password" id="edit_kurikulum_password"
                                           autocomplete="new-password" minlength="6"
                                           style="width:100%;padding-right:44px;">
                                    <button type="button" onclick="togglePassword('edit_kurikulum_password', this)" title="Lihat password"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;color:#6b7280;">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div></div>
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeEditKurikulumModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
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
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                filterForm.submit();
            }, 600);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                filterForm.submit();
            }
        });
    })();

    /* ============================================================
       HELPER: PASSWORD SHOW / HIDE
    ============================================================ */
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    }

    function resetPasswordField(id) {
        const input = document.getElementById(id);
        if (!input) return;

        input.type = 'password';
        input.value = '';

        const icon = input.parentElement.querySelector('i');
        if (icon) {
            icon.classList.add('fa-eye');
            icon.classList.remove('fa-eye-slash');
        }
    }

    /* ============================================================
       MODAL: TAMBAH GURU
    ============================================================ */
    function openGuruModal() {
        resetPasswordField('add_guru_password');
        document.getElementById('guruModalOverlay').classList.add('open');
    }

    function closeGuruModal() {
        resetPasswordField('add_guru_password');
        document.getElementById('guruModalOverlay').classList.remove('open');
    }

    document.getElementById('guruModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeGuruModal();
    });

    /* ============================================================
       MODAL: EDIT GURU
    ============================================================ */
    function openEditGuruModal(id) {
        const overlay = document.getElementById('editGuruModalOverlay');
        const form = document.getElementById('editGuruForm');

        overlay.classList.add('open');
        form.action = `/admin/guru/${id}`;

        fetch(`/admin/guru/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Gagal memuat data');
            return response.json();
        })
        .then(data => {
            document.getElementById('edit_guru_name').value = data.name ?? '';
            document.getElementById('edit_guru_phone').value = data.phone ?? '';
            document.getElementById('edit_guru_join_date').value = data.join_date ?? '';
            document.getElementById('edit_guru_address').value = data.address ?? '';
            document.getElementById('edit_guru_training_status').value = data.training_status ?? '';
            document.getElementById('edit_guru_status').value = data.status ?? 'Active';
            document.getElementById('edit_guru_username').value = data.username ?? '';
            resetPasswordField('edit_guru_password');
        })
        .catch(() => {
            alert('Gagal memuat data guru untuk diedit.');
            closeEditGuruModal();
        });
    }

    function closeEditGuruModal() {
        resetPasswordField('edit_guru_password');
        document.getElementById('editGuruModalOverlay').classList.remove('open');
    }

    document.getElementById('editGuruModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeEditGuruModal();
    });

    /* ============================================================
       MODAL: TAMBAH KURIKULUM
    ============================================================ */
    function openKurikulumModal() {
        resetPasswordField('add_kurikulum_password');
        document.getElementById('kurikulumModalOverlay').classList.add('open');
    }

    function closeKurikulumModal() {
        resetPasswordField('add_kurikulum_password');
        document.getElementById('kurikulumModalOverlay').classList.remove('open');
    }

    document.getElementById('kurikulumModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeKurikulumModal();
    });

    /* ============================================================
       MODAL: EDIT KURIKULUM
    ============================================================ */
    function openEditKurikulumModal(id) {
        const overlay = document.getElementById('editKurikulumModalOverlay');
        const form = document.getElementById('editKurikulumForm');

        overlay.classList.add('open');
        form.action = `/admin/kurikulum-staf/${id}`;

        fetch(`/admin/kurikulum-staf/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Gagal memuat data');
            return response.json();
        })
        .then(data => {
            document.getElementById('edit_kurikulum_name').value = data.name ?? '';
            document.getElementById('edit_kurikulum_phone').value = data.phone ?? '';
            document.getElementById('edit_kurikulum_join_date').value = data.join_date ?? '';
            document.getElementById('edit_kurikulum_address').value = data.address ?? '';
            document.getElementById('edit_kurikulum_status').value = data.status ?? 'Active';
            document.getElementById('edit_kurikulum_username').value = data.username ?? '';
            resetPasswordField('edit_kurikulum_password');
        })
        .catch(() => {
            alert('Gagal memuat data kurikulum untuk diedit.');
            closeEditKurikulumModal();
        });
    }

    function closeEditKurikulumModal() {
        resetPasswordField('edit_kurikulum_password');
        document.getElementById('editKurikulumModalOverlay').classList.remove('open');
    }

    document.getElementById('editKurikulumModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeEditKurikulumModal();
    });
</script>
@endpush