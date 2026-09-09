@extends('admin.app')

@section('title', 'Program & Level | Pengaturan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/programlevel.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">Pengaturan</div>
            <h1>Program &amp; Level</h1>
            <p>
                Kelola program pembelajaran beserta kategori dan level yang tersedia di Haoyou Educator.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Total Program</span>
                <i class="fa-solid fa-layer-group stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_program'] }}</div>
            <div class="stat-note">Daily Activity &amp; HSK</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Total Kategori</span>
                <i class="fa-solid fa-sitemap stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_category'] }}</div>
            <div class="stat-note">Maochong, Jianer, HSK Class, dst.</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Total Paket</span>
                <i class="fa-solid fa-box-archive stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_package'] }}</div>
            <div class="stat-note">Reguler + Private</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Paket Aktif</span>
                <i class="fa-solid fa-circle-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['active_package'] }}</div>
            <div class="stat-note">Bisa dipilih calon siswa</div>
        </div>
    </div>

        {{-- ========================= STRUKTUR PROGRAM (RINGKASAN) ========================= --}}
    <div class="card struktur-summary-card">
        <div class="struktur-summary-text">
            <div class="section-title" style="margin:0;">
                <i class="fa-solid fa-diagram-project"></i> Struktur Program &amp; Kategori
            </div>
            <p>Kelola daftar Program, Kategori, dan Level pembelajaran di halaman terpisah.</p>
        </div>
        <a href="{{ route('admin.program-level.struktur') }}" class="btn btn-primary">
            <i class="fa-solid fa-sitemap"></i> Kelola Struktur
        </a>
    </div>

    {{-- ========================= PAKET REGULER ========================= --}}
    @php
        $dailyPackages = $packages->filter(fn ($p) => $p->program->program_name === 'Daily Activity');
        $hskPackages   = $packages->filter(fn ($p) => $p->program->program_name === 'HSK');
    @endphp

    {{-- ----- Daily Activity ----- --}}
    <div class="section-title">
        <i class="fa-solid fa-book"></i> Paket Reguler — Daily Activity
    </div>

    <div class="card">
        <div class="toolbar">
            <div></div>
            <button type="button" class="btn btn-primary" onclick="openAddPackageModal({{ $programs->firstWhere('program_name', 'Daily Activity')?->id }})">
                <i class="fa-solid fa-plus"></i> Tambah Paket
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nama Paket</th>
                    <th>Durasi</th>
                    <th>Pertemuan</th>
                    <th>Kapasitas</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th width="110" style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dailyPackages as $package)
                    <tr>
                        <td class="cell-name">{{ $package->package_name }}</td>
                        <td>{{ $package->duration_minutes }} menit</td>
                        <td>{{ $package->total_meetings }}x</td>
                        <td>{{ $package->min_students }}–{{ $package->max_students }} siswa</td>
                        <td>Rp{{ number_format($package->price, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $package->is_active ? 'badge-active' : 'badge-inactive' }}">
                                <span class="badge-dot"></span>
                                {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Edit"
                                        onclick="openEditPackageModal({{ $package->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.program-level.paket.destroy', $package) }}"
                                      method="POST" style="display:inline;"
                                      onsubmit="return confirm('Hapus paket &quot;{{ $package->package_name }}&quot;? Paket tidak bisa dihapus jika masih dipakai di kelas.');">
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
                        <td colspan="7" style="text-align:center;padding:50px;color:#888;">
                            Belum ada paket Daily Activity.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ----- HSK ----- --}}
    <div class="section-title">
        <i class="fa-solid fa-book"></i> Paket Reguler — HSK
    </div>

    <div class="card">
        <div class="toolbar">
            <div></div>
            <button type="button" class="btn btn-primary" onclick="openAddPackageModal({{ $programs->firstWhere('program_name', 'HSK')?->id }})">
                <i class="fa-solid fa-plus"></i> Tambah Paket
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nama Paket</th>
                    <th>Kategori</th>
                    <th>Level</th>
                    <th>Durasi</th>
                    <th>Pertemuan</th>
                    <th>Kapasitas</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th width="110" style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hskPackages as $package)
                    <tr>
                        <td class="cell-name">{{ $package->package_name }}</td>
                        <td>{{ $package->category->category_name ?? '—' }}</td>
                        <td>{{ $package->level->level_name ?? '—' }}</td>
                        <td>{{ $package->duration_minutes }} menit</td>
                        <td>{{ $package->total_meetings }}x</td>
                        <td>{{ $package->min_students }}–{{ $package->max_students }} siswa</td>
                        <td>Rp{{ number_format($package->price, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $package->is_active ? 'badge-active' : 'badge-inactive' }}">
                                <span class="badge-dot"></span>
                                {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Edit"
                                        onclick="openEditPackageModal({{ $package->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.program-level.paket.destroy', $package) }}"
                                      method="POST" style="display:inline;"
                                      onsubmit="return confirm('Hapus paket &quot;{{ $package->package_name }}&quot;? Paket tidak bisa dihapus jika masih dipakai di kelas.');">
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
                        <td colspan="9" style="text-align:center;padding:50px;color:#888;">
                            Belum ada paket HSK.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ========================= PAKET PRIVATE ========================= --}}
    <div class="section-title">
        <i class="fa-solid fa-user-graduate"></i> Paket Private
    </div>

    <div class="card">
        <div class="toolbar">
            <div></div>
            <button type="button" class="btn btn-primary" onclick="openAddPrivateModal()">
                <i class="fa-solid fa-plus"></i> Tambah Paket Private
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nama Paket</th>
                    <th>Durasi</th>
                    <th>Pertemuan</th>
                    <th>Kapasitas</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th width="110" style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($privatePackages as $pkg)
                    <tr>
                        <td class="cell-name">{{ $pkg->package_name }}</td>
                        <td>{{ $pkg->duration_minutes }} menit</td>
                        <td>{{ $pkg->total_meetings }}x</td>
                        <td>{{ $pkg->min_students }}–{{ $pkg->max_students }} siswa</td>
                        <td>Rp{{ number_format($pkg->price, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $pkg->is_active ? 'badge-active' : 'badge-inactive' }}">
                                <span class="badge-dot"></span>
                                {{ $pkg->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Edit"
                                        onclick="openEditPrivateModal({{ $pkg->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.program-level.private.destroy', $pkg) }}"
                                      method="POST" style="display:inline;"
                                      onsubmit="return confirm('Hapus paket &quot;{{ $pkg->package_name }}&quot;? Paket tidak bisa dihapus jika masih dipakai di data pendaftaran siswa.');">
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
                        <td colspan="7" style="text-align:center;padding:50px;color:#888;">
                            Belum ada paket private.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ========================= MODAL — TAMBAH PAKET REGULER ========================= --}}
    <div class="modal-overlay" id="addPackageModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.program-level.paket.store') }}" method="POST">
                @csrf
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Paket Reguler</div>
                        <div class="modal-sub">Paket ini akan muncul sebagai pilihan saat mendaftarkan calon siswa.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('addPackageModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-layer-group"></i> Program &amp; Level</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Program *</label>
                                <select name="program_id" id="program_id" required onchange="onProgramChange(this.value, '')">
                                    <option value="">Pilih Program</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Kategori</label>
                                <select name="category_id" id="category_id" onchange="onCategoryChange(this.value, '')">
                                    <option value="">— Tidak ada —</option>
                                </select>
                            </div>
                            <div class="form-field full">
                                <label>Level</label>
                                <select name="level_id" id="level_id">
                                    <option value="">— Tidak ada —</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-clipboard-list"></i> Detail Paket</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Paket *</label>
                                <input type="text" name="package_name" required
                                       placeholder="mis. Regular Class Anak - 1x/Minggu (1 Bulan)">
                            </div>
                            <div class="form-field">
                                <label>Durasi (menit) *</label>
                                <input type="number" name="duration_minutes" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Total Pertemuan *</label>
                                <input type="number" name="total_meetings" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Min. Siswa *</label>
                                <input type="number" name="min_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Maks. Siswa *</label>
                                <input type="number" name="max_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Harga (Rp) *</label>
                                <input type="number" name="price" min="0" step="1000" required>
                            </div>
                            <div class="form-field">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    Paket aktif (bisa dipilih calon siswa)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addPackageModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Paket</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT PAKET REGULER ========================= --}}
    <div class="modal-overlay" id="editPackageModalOverlay">
        <div class="modal">
            <form id="editPackageForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Paket Reguler</div>
                        <div class="modal-sub">Perbarui detail paket ini.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('editPackageModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-layer-group"></i> Program &amp; Level</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Program *</label>
                                <select name="program_id" id="edit_program_id" required
                                        onchange="onProgramChange(this.value, '', true)">
                                    <option value="">Pilih Program</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Kategori</label>
                                <select name="category_id" id="edit_category_id" onchange="onCategoryChange(this.value, '', true)">
                                    <option value="">— Tidak ada —</option>
                                </select>
                            </div>
                            <div class="form-field full">
                                <label>Level</label>
                                <select name="level_id" id="edit_level_id">
                                    <option value="">— Tidak ada —</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-clipboard-list"></i> Detail Paket</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Paket *</label>
                                <input type="text" name="package_name" id="edit_package_name" required>
                            </div>
                            <div class="form-field">
                                <label>Durasi (menit) *</label>
                                <input type="number" name="duration_minutes" id="edit_duration_minutes" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Total Pertemuan *</label>
                                <input type="number" name="total_meetings" id="edit_total_meetings" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Min. Siswa *</label>
                                <input type="number" name="min_students" id="edit_min_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Maks. Siswa *</label>
                                <input type="number" name="max_students" id="edit_max_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Harga (Rp) *</label>
                                <input type="number" name="price" id="edit_price" min="0" step="1000" required>
                            </div>
                            <div class="form-field">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                                    Paket aktif (bisa dipilih calon siswa)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPackageModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH PAKET PRIVATE ========================= --}}
    <div class="modal-overlay" id="addPrivateModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.program-level.private.store') }}" method="POST">
                @csrf
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Paket Private</div>
                        <div class="modal-sub">Paket private berdiri sendiri, tidak terikat Program/Kategori/Level.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('addPrivateModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-clipboard-list"></i> Detail Paket</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Paket *</label>
                                <input type="text" name="package_name" required
                                       placeholder="mis. Private VIP Lokal - 4 Pertemuan">
                            </div>
                            <div class="form-field">
                                <label>Durasi (menit) *</label>
                                <input type="number" name="duration_minutes" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Total Pertemuan *</label>
                                <input type="number" name="total_meetings" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Min. Siswa *</label>
                                <input type="number" name="min_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Maks. Siswa *</label>
                                <input type="number" name="max_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Harga (Rp) *</label>
                                <input type="number" name="price" min="0" step="1000" required>
                            </div>
                            <div class="form-field">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    Paket aktif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addPrivateModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Paket</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT PAKET PRIVATE ========================= --}}
    <div class="modal-overlay" id="editPrivateModalOverlay">
        <div class="modal">
            <form id="editPrivateForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Paket Private</div>
                        <div class="modal-sub">Perbarui detail paket ini.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('editPrivateModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-clipboard-list"></i> Detail Paket</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Paket *</label>
                                <input type="text" name="package_name" id="edit_priv_package_name" required>
                            </div>
                            <div class="form-field">
                                <label>Durasi (menit) *</label>
                                <input type="number" name="duration_minutes" id="edit_priv_duration_minutes" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Total Pertemuan *</label>
                                <input type="number" name="total_meetings" id="edit_priv_total_meetings" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Min. Siswa *</label>
                                <input type="number" name="min_students" id="edit_priv_min_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Maks. Siswa *</label>
                                <input type="number" name="max_students" id="edit_priv_max_students" min="1" required>
                            </div>
                            <div class="form-field">
                                <label>Harga (Rp) *</label>
                                <input type="number" name="price" id="edit_priv_price" min="0" step="1000" required>
                            </div>
                            <div class="form-field">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="is_active" id="edit_priv_is_active" value="1">
                                    Paket aktif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPrivateModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
@php
    $programTreeData = $programs->map(function ($p) {
        return [
            'id' => $p->id,
            'categories' => $p->categories->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->category_name,
                    'levels' => $c->levels->map(function ($l) {
                        return [
                            'id' => $l->id,
                            'name' => $l->level_name,
                        ];
                    }),
                ];
            }),
        ];
    });
@endphp

@push('scripts')
@php
    $programTreeData = $programs->map(function ($p) {
        return [
            'id' => $p->id,
            'categories' => $p->categories->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->category_name,
                    'levels' => $c->levels->map(function ($l) {
                        return [
                            'id' => $l->id,
                            'name' => $l->level_name,
                        ];
                    }),
                ];
            }),
        ];
    });
@endphp

<script>
    const programTree = @json($programTreeData);

    function findProgram(programId) {
        return programTree.find(p => String(p.id) === String(programId));
    }

    function fillSelect(selectEl, items, placeholder, selectedId) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            if (selectedId && String(selectedId) === String(item.id)) opt.selected = true;
            selectEl.appendChild(opt);
        });
    }

    function onProgramChange(programId, selectedCategoryId, isEdit = false, selectedLevelId = null) {
        const prefix = isEdit ? 'edit_' : '';
        const categorySelect = document.getElementById(prefix + 'category_id');
        const levelSelect = document.getElementById(prefix + 'level_id');

        const program = findProgram(programId);
        const categories = program ? program.categories : [];

        if (categories.length === 0) {
            // Program tanpa kategori (mis. Daily Activity) — kunci ke null.
            fillSelect(categorySelect, [], '— Tidak ada —', '');
            fillSelect(levelSelect, [], '— Tidak ada —', '');
            categorySelect.disabled = true;
            levelSelect.disabled = true;
        } else {
            categorySelect.disabled = false;
            fillSelect(categorySelect, categories, 'Pilih Kategori', selectedCategoryId);
            onCategoryChange(categorySelect.value, selectedLevelId, isEdit);
        }
    }

    function onCategoryChange(categoryId, selectedLevelId, isEdit = false) {
        const prefix = isEdit ? 'edit_' : '';
        const levelSelect = document.getElementById(prefix + 'level_id');

        if (!categoryId) {
            fillSelect(levelSelect, [], '— Tidak ada —', '');
            levelSelect.disabled = true;
            return;
        }

        let levels = [];
        programTree.forEach(p => {
            const cat = p.categories.find(c => String(c.id) === String(categoryId));
            if (cat) levels = cat.levels;
        });

        levelSelect.disabled = false;
        fillSelect(levelSelect, levels, 'Pilih Level', selectedLevelId);
    }

    /* ============================================================
       MODAL HELPERS
    ============================================================ */
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    ['addPackageModalOverlay', 'editPackageModalOverlay', 'addPrivateModalOverlay', 'editPrivateModalOverlay']
        .forEach(id => {
            document.getElementById(id).addEventListener('click', function (e) {
                if (e.target === this) closeModal(id);
            });
        });

    /* ============================================================
       PAKET REGULER
    ============================================================ */
    function openAddPackageModal(programId) {
        document.getElementById('addPackageModalOverlay').classList.add('open');
        if (programId) {
            document.getElementById('program_id').value = programId;
            onProgramChange(programId, '');
        }
    }

    function openEditPackageModal(id) {
        const overlay = document.getElementById('editPackageModalOverlay');
        const form = document.getElementById('editPackageForm');
        overlay.classList.add('open');
        form.action = `/admin/program-level/paket/${id}`;

        fetch(`/admin/program-level/paket/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(res => {
                if (!res.ok) throw new Error('Gagal memuat data');
                return res.json();
            })
            .then(data => {
                document.getElementById('edit_package_name').value = data.package_name ?? '';
                document.getElementById('edit_duration_minutes').value = data.duration_minutes ?? '';
                document.getElementById('edit_total_meetings').value = data.total_meetings ?? '';
                document.getElementById('edit_min_students').value = data.min_students ?? '';
                document.getElementById('edit_max_students').value = data.max_students ?? '';
                document.getElementById('edit_price').value = data.price ?? '';
                document.getElementById('edit_is_active').checked = !!data.is_active;
                document.getElementById('edit_program_id').value = data.program_id ?? '';

                onProgramChange(data.program_id, data.category_id, true, data.level_id);
            })
            .catch(() => {
                alert('Gagal memuat data paket untuk diedit.');
                closeModal('editPackageModalOverlay');
            });
    }

    /* ============================================================
       PAKET PRIVATE
    ============================================================ */
    function openAddPrivateModal() {
        document.getElementById('addPrivateModalOverlay').classList.add('open');
    }

    function openEditPrivateModal(id) {
        const overlay = document.getElementById('editPrivateModalOverlay');
        const form = document.getElementById('editPrivateForm');
        overlay.classList.add('open');
        form.action = `/admin/program-level/private/${id}`;

        fetch(`/admin/program-level/private/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(res => {
                if (!res.ok) throw new Error('Gagal memuat data');
                return res.json();
            })
            .then(data => {
                document.getElementById('edit_priv_package_name').value = data.package_name ?? '';
                document.getElementById('edit_priv_duration_minutes').value = data.duration_minutes ?? '';
                document.getElementById('edit_priv_total_meetings').value = data.total_meetings ?? '';
                document.getElementById('edit_priv_min_students').value = data.min_students ?? '';
                document.getElementById('edit_priv_max_students').value = data.max_students ?? '';
                document.getElementById('edit_priv_price').value = data.price ?? '';
                document.getElementById('edit_priv_is_active').checked = !!data.is_active;
            })
            .catch(() => {
                alert('Gagal memuat data paket private untuk diedit.');
                closeModal('editPrivateModalOverlay');
            });
    }
</script>
@endpush