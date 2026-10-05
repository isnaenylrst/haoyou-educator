@extends('admin.app')

@section('title', 'Struktur Program | Program & Level')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/programlevel.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">
                <a href="{{ route('admin.program-level') }}" style="color:inherit;text-decoration:none;">
                    <i class="fa-solid fa-arrow-left"></i> Program &amp; Level
                </a>
            </div>
            <h1>Struktur Program &amp; Kategori</h1>
            <p>Kelola program, kategori, dan level pembelajaran di Haoyou Educator.</p>
        </div>
        <div class="dashboard-header-actions">
            <button type="button" class="btn btn-primary" onclick="openAddProgramModal()">
                <i class="fa-solid fa-plus"></i> Tambah Program
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <div class="program-tree-grid">
        @foreach ($programs as $program)
            <div class="card program-tree-card">
                <div class="program-tree-head">
                    <div class="program-tree-name">{{ $program->program_name }}</div>
                    <div class="row-actions">
                        <span class="badge badge-neutral">{{ $program->categories->count() }} Kategori</span>
                        <button type="button" class="icon-btn" title="Edit Program"
                                onclick="openEditProgramModal({{ $program->id }})">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <form action="{{ route('admin.program-level.program.destroy', $program) }}"
                              method="POST" style="display:inline;"
                              onsubmit="return confirm('Hapus program &quot;{{ $program->program_name }}&quot;? Semua kategori dan level di dalamnya akan ikut terhapus.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-btn danger" title="Hapus Program">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <p class="program-tree-desc">{{ $program->description }}</p>

                <button type="button" class="add-schedule-btn" style="margin-bottom:14px;"
                        onclick="openAddCategoryModal({{ $program->id }})">
                    <i class="fa-solid fa-plus"></i> Tambah Kategori
                </button>

                @if ($program->categories->isEmpty())
                    <div class="program-tree-empty" style="margin-bottom:10px;">Belum ada kategori.</div>

                    <button type="button" class="add-schedule-btn"
                            onclick="openAddLevelModal({{ $program->id }}, '')">
                        <i class="fa-solid fa-plus"></i> Tambah Level Langsung
                    </button>

                    @if ($program->levels->isNotEmpty())
                        <div class="program-tree-categories" style="margin-top:12px;">
                            @foreach ($program->levels as $level)
                                <div class="program-tree-category">
                                    <span class="cat-name">{{ $level->level_name }}</span>
                                    <div class="row-actions" style="margin-left:auto;">
                                        <button type="button" class="icon-btn" title="Edit Level"
                                                onclick="openEditLevelModal({{ $level->id }})">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <form action="{{ route('admin.program-level.level.destroy', $level) }}"
                                              method="POST" style="display:inline;"
                                              onsubmit="return confirm('Hapus level &quot;{{ $level->level_name }}&quot;?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn danger" title="Hapus Level">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="program-tree-categories">
                        @foreach ($program->categories as $category)
                            <div class="category-block">
                                <div class="program-tree-category">
                                    <span class="cat-name">{{ $category->category_name }}</span>
                                    @if ($category->min_age)
                                        <span class="cat-age">{{ $category->min_age }}{{ $category->max_age ? '–' . $category->max_age : '+' }} th</span>
                                    @endif
                                    <span class="cat-level-count">{{ $category->levels->count() }} level</span>
                                    <div class="row-actions">
                                        <button type="button" class="icon-btn" title="Tambah Level"
                                                onclick="openAddLevelModal({{ $program->id }}, {{ $category->id }})">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                        <button type="button" class="icon-btn" title="Edit Kategori"
                                                onclick="openEditCategoryModal({{ $category->id }})">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <form action="{{ route('admin.program-level.category.destroy', $category) }}"
                                              method="POST" style="display:inline;"
                                              onsubmit="return confirm('Hapus kategori &quot;{{ $category->category_name }}&quot;? Semua level di dalamnya akan ikut terhapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn danger" title="Hapus Kategori">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                @if ($category->levels->isNotEmpty())
                                    <div class="level-list">
                                        @foreach ($category->levels as $level)
                                            <div class="level-chip">
                                                <span>{{ $level->level_name }}</span>
                                                <div class="row-actions">
                                                    <button type="button" class="icon-btn" title="Edit Level"
                                                            onclick="openEditLevelModal({{ $level->id }})">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                    <form action="{{ route('admin.program-level.level.destroy', $level) }}"
                                                          method="POST" style="display:inline;"
                                                          onsubmit="return confirm('Hapus level &quot;{{ $level->level_name }}&quot;?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-btn danger" title="Hapus Level">
                                                            <i class="fa-regular fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ========================= MODAL — TAMBAH PROGRAM ========================= --}}
    <div class="modal-overlay" id="addProgramModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.program-level.program.store') }}" method="POST">
                @csrf
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Program</div>
                        <div class="modal-sub">Program baru akan muncul di struktur di atas.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('addProgramModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Program *</label>
                                <input type="text" name="program_name" required placeholder="mis. HSK">
                            </div>
                            <div class="form-field full">
                                <label>Deskripsi</label>
                                <textarea name="description" placeholder="Deskripsi singkat program ini"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addProgramModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Program</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT PROGRAM ========================= --}}
    <div class="modal-overlay" id="editProgramModalOverlay">
        <div class="modal">
            <form id="editProgramForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Program</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('editProgramModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Program *</label>
                                <input type="text" name="program_name" id="edit_program_name" required>
                            </div>
                            <div class="form-field full">
                                <label>Deskripsi</label>
                                <textarea name="description" id="edit_program_description"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProgramModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH KATEGORI ========================= --}}
    <div class="modal-overlay" id="addCategoryModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.program-level.category.store') }}" method="POST">
                @csrf
                <input type="hidden" name="program_id" id="add_category_program_id">
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Kategori</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('addCategoryModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Kategori *</label>
                                <input type="text" name="category_name" required placeholder="mis. Maochong">
                            </div>
                            <div class="form-field">
                                <label>Usia Min <span class="opt">(opsional)</span></label>
                                <input type="number" name="min_age" min="0">
                            </div>
                            <div class="form-field">
                                <label>Usia Maks <span class="opt">(opsional)</span></label>
                                <input type="number" name="max_age" min="0">
                            </div>
                            <div class="form-field">
                                <label>Urutan <span class="opt">(opsional)</span></label>
                                <input type="number" name="sort_order" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT KATEGORI ========================= --}}
    <div class="modal-overlay" id="editCategoryModalOverlay">
        <div class="modal">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Kategori</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('editCategoryModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Kategori *</label>
                                <input type="text" name="category_name" id="edit_category_name" required>
                            </div>
                            <div class="form-field">
                                <label>Usia Min <span class="opt">(opsional)</span></label>
                                <input type="number" name="min_age" id="edit_category_min_age" min="0">
                            </div>
                            <div class="form-field">
                                <label>Usia Maks <span class="opt">(opsional)</span></label>
                                <input type="number" name="max_age" id="edit_category_max_age" min="0">
                            </div>
                            <div class="form-field">
                                <label>Urutan <span class="opt">(opsional)</span></label>
                                <input type="number" name="sort_order" id="edit_category_sort_order" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH LEVEL ========================= --}}
    <div class="modal-overlay" id="addLevelModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.program-level.level.store') }}" method="POST">
                @csrf
                <input type="hidden" name="program_id" id="add_level_program_id">
                <input type="hidden" name="category_id" id="add_level_category_id">
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Level</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('addLevelModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Level *</label>
                                <input type="text" name="level_name" required placeholder="mis. HSK 1">
                            </div>
                            <div class="form-field full">
                                <label>Urutan <span class="opt">(opsional)</span></label>
                                <input type="number" name="sort_order" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addLevelModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Level</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT LEVEL ========================= --}}
    <div class="modal-overlay" id="editLevelModalOverlay">
        <div class="modal">
            <form id="editLevelForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Level</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('editLevelModalOverlay')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Level *</label>
                                <input type="text" name="level_name" id="edit_level_name" required>
                            </div>
                            <div class="form-field full">
                                <label>Urutan <span class="opt">(opsional)</span></label>
                                <input type="number" name="sort_order" id="edit_level_sort_order" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editLevelModalOverlay')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    ['addProgramModalOverlay', 'editProgramModalOverlay', 'addCategoryModalOverlay', 'editCategoryModalOverlay',
     'addLevelModalOverlay', 'editLevelModalOverlay']
        .forEach(id => {
            document.getElementById(id).addEventListener('click', function (e) {
                if (e.target === this) closeModal(id);
            });
        });

    /* ============================================================
       PROGRAM
    ============================================================ */
    function openAddProgramModal() {
        document.getElementById('addProgramModalOverlay').classList.add('open');
    }

    function openEditProgramModal(id) {
        const overlay = document.getElementById('editProgramModalOverlay');
        const form = document.getElementById('editProgramForm');
        overlay.classList.add('open');
        form.action = `/admin/program-level/program/${id}`;

        fetch(`/admin/program-level/program/${id}/edit`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_program_name').value = data.program_name ?? '';
                document.getElementById('edit_program_description').value = data.description ?? '';
            })
            .catch(() => {
                alert('Gagal memuat data program.');
                closeModal('editProgramModalOverlay');
            });
    }

    /* ============================================================
       KATEGORI
    ============================================================ */
    function openAddCategoryModal(programId) {
        document.getElementById('add_category_program_id').value = programId;
        document.getElementById('addCategoryModalOverlay').classList.add('open');
    }

    function openEditCategoryModal(id) {
        const overlay = document.getElementById('editCategoryModalOverlay');
        const form = document.getElementById('editCategoryForm');
        overlay.classList.add('open');
        form.action = `/admin/program-level/category/${id}`;

        fetch(`/admin/program-level/category/${id}/edit`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_category_name').value = data.category_name ?? '';
                document.getElementById('edit_category_min_age').value = data.min_age ?? '';
                document.getElementById('edit_category_max_age').value = data.max_age ?? '';
                document.getElementById('edit_category_sort_order').value = data.sort_order ?? 0;
            })
            .catch(() => {
                alert('Gagal memuat data kategori.');
                closeModal('editCategoryModalOverlay');
            });
    }

    /* ============================================================
       LEVEL
    ============================================================ */
    function openAddLevelModal(programId, categoryId) {
        document.getElementById('add_level_program_id').value = programId;
        document.getElementById('add_level_category_id').value = categoryId;
        document.getElementById('addLevelModalOverlay').classList.add('open');
    }

    function openEditLevelModal(id) {
        const overlay = document.getElementById('editLevelModalOverlay');
        const form = document.getElementById('editLevelForm');
        overlay.classList.add('open');
        form.action = `/admin/program-level/level/${id}`;

        fetch(`/admin/program-level/level/${id}/edit`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_level_name').value = data.level_name ?? '';
                document.getElementById('edit_level_sort_order').value = data.sort_order ?? 0;
            })
            .catch(() => {
                alert('Gagal memuat data level.');
                closeModal('editLevelModalOverlay');
            });
    }
</script>
@endpush