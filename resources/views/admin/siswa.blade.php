@extends('admin.app')

@section('title', 'Calon Siswa | CRM')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Siswa</h1>
            <p>
                Kelola data siswa aktif, kelas, dan jadwal pembelajaran dengan mudah dan terorganisir.
            </p>
        </div>

        <div class="dashboard-header-actions">
            <button class="btn">
                <i class="fa-solid fa-file-export"></i>
                Export
            </button>

            <button type="button" class="btn btn-primary" onclick="openLeadModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Siswa
            </button>
        </div>
    </div>

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Total Siswa Aktif</span>
                <i class="fa-solid fa-users stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_active'] }}</div>
            <div class="stat-note">Siswa yang sedang aktif belajar</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Daily Activity</span>
                <i class="fa-solid fa-chalkboard-user stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_daily_activity'] }}</div>
            <div class="stat-note">Terdaftar pada kelas Daily Activity</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">HSK</span>
                <i class="fa-solid fa-book-open stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_hsk'] }}</div>
            <div class="stat-note">Terdaftar pada program HSK</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Private</span>
                <i class="fa-solid fa-user-tie stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['total_private'] }}</div>
            <div class="stat-note">Terdaftar pada kelas Private</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.siswa') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    id="searchInput"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama, telepon, sekolah...">
            </div>

            <select name="status" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Aktif</option>
                <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Cuti</option>
                <option value="Graduated" {{ request('status') == 'Graduated' ? 'selected' : '' }}>Lulus</option>
            </select>

            <select name="class_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->class_name }}
                    </option>
                @endforeach
            </select>

            <a href="{{ route('admin.siswa') }}" class="btn">
                <i class="fa-solid fa-rotate-right"></i>
                Reset
            </a>
        </div>
    </form>

    {{-- ========================= TABLE ========================= --}}
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Program</th>
                    <th>Kelas Aktif</th>
                    <th>Status</th>
                    <th>Poin</th>
                    <th>Tanggal Bergabung</th>
                    <th width="120" style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($students as $student)
                    @php
                        $activeEnrollment = $student->activeEnrollment;
                        $isPrivate = $activeEnrollment?->private_package_id;
                        $isWaitingClass = $activeEnrollment?->status === 'Waiting Class';

                        $kelasAktifLabel = $isPrivate
                            ? ($activeEnrollment->privatePackage->package_name ?? 'Private')
                            : ($activeEnrollment?->class->class_name ?? ($isWaitingClass ? ' ' : '-'));
                        
                        $programLabel = $isPrivate
                            ? 'Private'
                            : ($activeEnrollment?->class?->programPackage?->program?->program_name
                                ?? $activeEnrollment?->programPackage?->program?->program_name
                                ?? '-');

                            $programBadgeClass = match ($programLabel) {
                                'HSK' => 'badge-warning',
                                'Daily Activity' => 'badge-cold',
                                'Private' => 'badge-completed',
                                default => '',
                            };
                    @endphp
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $student->name }}</div>
                                    <div class="cand-sub">
                                        {{ $student->user->phone ?? $student->candidateStudent->phone ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            @if ($programLabel !== '-')
                                <span class="badge {{ $programBadgeClass }}">{{ $programLabel }}</span>
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            {{ $kelasAktifLabel }}
                            @if ($isWaitingClass)
                                <span class="badge badge-hot" style="margin-left: 6px;">Menunggu Kelas</span>
                            @endif
                        </td>

                        <td>
                            @php
                                $statusClass = match ($student->status) {
                                    'Active' => 'badge-completed',
                                    'Inactive' => 'badge-hot',
                                    'Graduated' => 'badge-cold',
                                    default => '',
                                };
                                $statusLabel = match ($student->status) {
                                    'Active' => 'Aktif',
                                    'Inactive' => 'Cuti',
                                    'Graduated' => 'Lulus',
                                    default => $student->status,
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                <span class="badge-dot"></span>
                                {{ $statusLabel }}
                            </span>
                        </td>

                        <td>{{ $student->points }}</td>

                        <td>
                            {{ $student->join_date ? \Carbon\Carbon::parse($student->join_date)->format('d M Y') : '-' }}
                        </td>

                        {{-- Action --}}
                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Edit" onclick="openEditModal({{ $student->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn" title="Detail" onclick="openDetailModal({{ $student->id }})">
                                    <i class="fa-regular fa-eye"></i>
                                </button>

                                @if ($isWaitingClass)
                                    <button type="button" class="icon-btn wa" title="Assign Kelas"
                                            onclick="openAssignClassModal({{ $activeEnrollment->id }})">
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                    </button>
                                @else
                                    <button type="button" class="icon-btn wa" title="Lanjut Program Berikutnya"
                                            onclick="openContinueModal({{ $student->id }})">
                                        <i class="fa-solid fa-forward"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data siswa.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $students->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $students->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $students->total() }}</strong>
                siswa
            </span>

            <div class="table-footer-pagination">
                @if ($students->hasPages())
                    @php
                        $current = $students->currentPage();
                        $last = $students->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($students->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $students->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
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
                                        <li class="page-item"><a class="page-btn" href="{{ $students->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($students->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $students->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL EDIT ========================= --}}
    <div class="modal-overlay" id="editModalOverlay">
        <div class="modal">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Siswa</div>
                        <div class="modal-sub">Perbarui data siswa ini.</div>
                    </div>
                    <button
                        type="button"
                        class="modal-close"
                        onclick="closeEditModal()"
                        aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body" id="editModalBody">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Siswa</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" id="edit_name" required>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Bergabung</label>
                                <input type="date" id="edit_join_date" disabled>
                                <small>Otomatis tercatat dari tanggal pembayaran pertama pada program yang sedang diikuti.</small>
                            </div>
                            <div class="form-field">
                                <label>Poin</label>
                                <input type="number" name="points" id="edit_points" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user-check"></i> Status</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Status Siswa *</label>
                                <select name="status" id="edit_status" required>
                                    <option value="Active">Aktif</option>
                                    <option value="Inactive">Cuti</option>
                                    <option value="Graduated">Lulus</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-foot">
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL DETAIL ========================= --}}
    <div class="modal-overlay" id="detailModalOverlay">
        <div class="modal">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Detail Siswa</div>
                    <div class="modal-sub" id="detail_name">-</div>
                </div>
                <button type="button" class="modal-close" onclick="closeDetailModal()" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body" id="detailModalBody">

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-user"></i> Info Umum</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>No. HP</label>
                            <div id="detail_phone">-</div>
                        </div>
                        <div class="form-field">
                            <label>Usia</label>
                            <div id="detail_age">-</div>
                        </div>
                        <div class="form-field">
                            <label>Status</label>
                            <div id="detail_status">-</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Siswa</div>
                    <table class="table-mini">
                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                            </tr>
                        </thead>
                        <tbody id="detail_schedules">
                            <tr><td colspan="3">-</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program yang Diikuti</div>
                    <table class="table-mini">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Kelas</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody id="detail_programs">
                            <tr><td colspan="5">-</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-chart-line"></i> Progress Report</div>
                    <table class="table-mini">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Tanggal Upload</th>
                            </tr>
                        </thead>
                        <tbody id="detail_progress_reports">
                            <tr><td colspan="4">-</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-money-bill-wave"></i> Status Pembayaran</div>
                    <table class="table-mini">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Program</th>
                                <th>Total Tagihan</th>
                                <th>Dibayar</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="detail_payments">
                            <tr><td colspan="7">-</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-foot">
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================= MODAL LANJUT PROGRAM BERIKUTNYA ========================= --}}
    <div class="modal-overlay" id="continueModalOverlay">
        <div class="modal">
            <form id="continueForm" method="POST">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Lanjut Program Berikutnya</div>
                        <div class="modal-sub" id="continue_current_info">-</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeContinueModal()" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                @if ($errors->any() && session('continue_student_id'))
                    <div class="alert-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="modal-body" id="continueModalBody">

                    <div class="form-section">
                        <div id="continue_recommendation_banner" class="recommendation-banner" style="display: none;"></div>
                        <div id="continue_outstanding_banner" class="alert-error" style="display: none; margin-top: 10px;"></div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-chalkboard"></i> Program / Kelas Berikutnya</div>

                        <div class="form-field full">
                            <label>Tipe Program *</label>
                            <div class="package-type-toggle">
                                <label class="radio-pill">
                                    <input type="radio" name="package_type" value="program" id="continue_type_program" checked onchange="onContinuePackageTypeChange()">
                                    <span>Program Reguler</span>
                                </label>
                                <label class="radio-pill">
                                    <input type="radio" name="package_type" value="private" id="continue_type_private" onchange="onContinuePackageTypeChange()">
                                    <span>Private</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-grid" id="continue-program-block">
                            <div class="form-field full">
                                <label>Pilih Kelas *</label>
                                <select name="class_id" id="continue_class_id" onchange="onContinueClassChange()">
                                    <option value="">- Pilih Kelas -</option>
                                </select>
                            </div>
                            <input type="hidden" name="program_package_id" id="continue_program_package_id">
                        </div>

                        <div class="form-grid" id="continue-private-block" style="display: none;">
                            <div class="form-field full">
                                <label>Private Package *</label>
                                <select name="private_package_id" id="continue_private_package_id" onchange="onContinuePrivateChange()">
                                    <option value="">- Pilih Package -</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-field full">
                            <label>Harga Paket</label>
                            <div id="continue_price_display">-</div>
                            <input type="hidden" id="continue_price_raw" value="0">
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-money-check-dollar"></i> Pembayaran</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Tahap Pembayaran *</label>
                                <input type="text" name="payment_stage" id="continue_payment_stage" placeholder="Contoh: DP, Pelunasan, Full Payment" required>
                            </div>
                            <div class="form-field">
                                <label>Jumlah Dibayar *</label>
                                <input type="number" name="amount_paid" id="continue_amount_paid" min="0" step="1000" required>
                            </div>
                            <div class="form-field">
                                <label>Metode Pembayaran *</label>
                                <select name="payment_method" id="continue_payment_method" required>
                                    <option value="">Pilih Metode</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Cash">Cash</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Pembayaran *</label>
                                <input type="date" name="payment_date" id="continue_payment_date" required>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-foot">
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeContinueModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-forward"></i>
                            Lanjutkan Program
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL ASSIGN KELAS ========================= --}}
    <div class="modal-overlay" id="assignClassModalOverlay">
        <div class="modal">
            <form id="assignClassForm" method="POST">
                @csrf
                @method('PATCH')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Assign Kelas</div>
                        <div class="modal-sub">Pilih kelas untuk siswa yang masih menunggu.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeAssignClassModal()" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-section" style="margin-bottom: 0;">
                        <div class="form-field full">
                            <label>Kelas *</label>
                            <select name="class_id" id="assign_class_id" required>
                                <option value="">- Pilih Kelas -</option>
                            </select>
                            <small id="assign_class_empty_note" style="display:none; color:#b45309;">
                                Belum ada kelas Open/Running untuk paket ini.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeAssignClassModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i>
                            Assign Kelas
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
        MODAL: EDIT SISWA
        ============================================================ */
        function setSelectValue(id, value) {
            const el = document.getElementById(id);
            el.value = value ?? '';

            if (value && el.value !== value) {
                console.warn(`[Edit Siswa] Nilai "${value}" untuk #${id} tidak ada di pilihan dropdown.`);
            }
        }

        function openEditModal(id) {
            const overlay = document.getElementById('editModalOverlay');
            const form = document.getElementById('editForm');

            overlay.classList.add('open');
            form.action = `/admin/siswa/${id}`;

            fetch(`/admin/siswa/${id}/edit`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(data => {
                document.getElementById('edit_name').value = data.name ?? '';
                document.getElementById('edit_join_date').value = data.join_date ?? '';
                document.getElementById('edit_points').value = data.points ?? 0;
                setSelectValue('edit_status', data.status ?? 'Active');

            })
            .catch(() => {
                alert('Gagal memuat data siswa untuk diedit.');
                closeEditModal();
            });
        }

        function closeEditModal() {
            document.getElementById('editModalOverlay').classList.remove('open');
        }

        document.getElementById('editModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeEditModal();
        });

        /* ============================================================
        MODAL: LANJUT PROGRAM BERIKUTNYA
        ============================================================ */
        let continueClassOptions = [];
        let continuePrivateOptions = [];
        let continueRecommendation = { type: null };

        function openContinueModal(id) {
            const overlay = document.getElementById('continueModalOverlay');
            const form = document.getElementById('continueForm');
            const outstandingBanner = document.getElementById('continue_outstanding_banner');
            const submitBtn = form.querySelector('button[type="submit"]');

            overlay.classList.add('open');
            form.action = `/admin/siswa/${id}/continue`;
            form.reset();

            // reset state dari modal sebelumnya
            outstandingBanner.style.display = 'none';
            outstandingBanner.innerHTML = '';
            submitBtn.disabled = false;

            fetch(`/admin/siswa/${id}/continue`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(data => {
                document.getElementById('continue_current_info').textContent =
                    `Saat ini: ${data.current_program} — ${data.current_class}`;

                continueClassOptions = data.class_options ?? [];
                continuePrivateOptions = data.private_options ?? [];
                continueRecommendation = data.recommendation ?? { type: null };

                // Populate dropdown Kelas Reguler
                const classSelect = document.getElementById('continue_class_id');
                classSelect.innerHTML = '<option value="">- Pilih Kelas -</option>';
                continueClassOptions.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.id;
                    option.textContent = `${opt.program_name} — ${opt.class_name} (${opt.package_name})`;
                    option.dataset.price = opt.price;
                    option.dataset.package = opt.program_package_id;
                    classSelect.appendChild(option);
                });

                // Populate dropdown Private
                const privateSelect = document.getElementById('continue_private_package_id');
                privateSelect.innerHTML = '<option value="">- Pilih Package -</option>';
                continuePrivateOptions.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.id;
                    option.textContent = `${opt.package_name} — ${formatRupiah(opt.price)}`;
                    option.dataset.price = opt.price;
                    privateSelect.appendChild(option);
                });

                const banner = document.getElementById('continue_recommendation_banner');

                if (continueRecommendation.type === 'program') {
                    banner.style.display = 'flex';
                    banner.innerHTML = `<i class="fa-solid fa-star"></i> ${continueRecommendation.label}`;

                    document.getElementById('continue_type_program').checked = true;
                    onContinuePackageTypeChange();

                    if (continueRecommendation.class_id) {
                        classSelect.value = continueRecommendation.class_id;
                    }
                    onContinueClassChange();

                } else if (continueRecommendation.type === 'private') {
                    banner.style.display = 'flex';
                    banner.innerHTML = `<i class="fa-solid fa-star"></i> ${continueRecommendation.label}`;

                    document.getElementById('continue_type_private').checked = true;
                    onContinuePackageTypeChange();

                    if (continueRecommendation.private_package_id) {
                        privateSelect.value = continueRecommendation.private_package_id;
                    }
                    onContinuePrivateChange();

                } else {
                    banner.style.display = 'none';
                    document.getElementById('continue_type_program').checked = true;
                    onContinuePackageTypeChange();
                    document.getElementById('continue_price_display').textContent = '-';
                    document.getElementById('continue_price_raw').value = 0;
                }

                // Cek tagihan belum lunas dari program/kelas saat ini
                if (data.has_outstanding_balance) {
                    outstandingBanner.style.display = 'block';
                    outstandingBanner.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${data.outstanding_message}`;
                    submitBtn.disabled = true;
                } else {
                    outstandingBanner.style.display = 'none';
                    outstandingBanner.innerHTML = '';
                    submitBtn.disabled = false;
                }
            })
            .catch(() => {
                alert('Gagal memuat daftar program/kelas.');
                closeContinueModal();
            });
        }

        function onContinuePackageTypeChange() {
            const type = document.querySelector('input[name="package_type"]:checked').value;
            const programBlock = document.getElementById('continue-program-block');
            const privateBlock = document.getElementById('continue-private-block');
            const classSelect = document.getElementById('continue_class_id');
            const privateSelect = document.getElementById('continue_private_package_id');

            if (type === 'private') {
                programBlock.style.display = 'none';
                privateBlock.style.display = 'grid';
                classSelect.removeAttribute('required');
                classSelect.value = '';
                privateSelect.setAttribute('required', 'required');
                onContinuePrivateChange();
            } else {
                programBlock.style.display = 'grid';
                privateBlock.style.display = 'none';
                privateSelect.removeAttribute('required');
                privateSelect.value = '';
                classSelect.setAttribute('required', 'required');
                onContinueClassChange();
            }
        }

        function onContinueClassChange() {
            const select = document.getElementById('continue_class_id');
            const selected = select.options[select.selectedIndex];
            const price = selected?.dataset.price ? Number(selected.dataset.price) : 0;
            const packageId = selected?.dataset.package ?? '';

            document.getElementById('continue_price_display').textContent = formatRupiah(price);
            document.getElementById('continue_price_raw').value = price;
            document.getElementById('continue_program_package_id').value = packageId;
        }

        function onContinuePrivateChange() {
            const select = document.getElementById('continue_private_package_id');
            const selected = select.options[select.selectedIndex];
            const price = selected?.dataset.price ? Number(selected.dataset.price) : 0;

            document.getElementById('continue_price_display').textContent = formatRupiah(price);
            document.getElementById('continue_price_raw').value = price;
        }

        function closeContinueModal() {
            document.getElementById('continueModalOverlay').classList.remove('open');
        }

        document.getElementById('continueModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeContinueModal();
        });

        /* ============================================================
        MODAL: DETAIL SISWA
        ============================================================ */
        function renderRows(tbodyId, items, emptyColspan, rowBuilder) {
            const tbody = document.getElementById(tbodyId);
            tbody.innerHTML = '';

            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${emptyColspan}">Belum ada data.</td></tr>`;
                return;
            }

            items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = rowBuilder(item);
                tbody.appendChild(tr);
            });
        }

        function formatRupiah(value) {
            const number = Number(value ?? 0);
            return 'Rp ' + number.toLocaleString('id-ID');
        }

        function statusLabel(status) {
            const labels = {
                'Active': 'Aktif',
                'Inactive': 'Cuti',
                'Graduated': 'Lulus',
            };
            return labels[status] ?? (status ?? '-');
        }

        function enrollmentStatusLabel(status) {
            const labels = {
                'Active': 'Berjalan',
                'Completed': 'Selesai',
                'Cancelled': 'Dibatalkan',
            };
            return labels[status] ?? (status ?? '-');
        }

        function openDetailModal(id) {
            const overlay = document.getElementById('detailModalOverlay');
            overlay.classList.add('open');

            fetch(`/admin/siswa/${id}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(data => {
                document.getElementById('detail_name').textContent = data.name ?? '-';
                document.getElementById('detail_phone').textContent = data.phone ?? '-';
                document.getElementById('detail_age').textContent = data.age ? `${data.age} Tahun` : '-';
                document.getElementById('detail_status').textContent = statusLabel(data.status);

                renderRows('detail_schedules', data.schedules, 3, s => `
                    <td>${s.day ?? '-'}</td>
                    <td>${s.start_time ?? '-'}</td>
                    <td>${s.end_time ?? '-'}</td>
                `);

                renderRows('detail_programs', data.programs, 5, p => `
                    <td>${p.program_name ?? '-'}</td>
                    <td>${p.class_name ?? '-'}</td>
                    <td>${p.course_type ?? '-'}</td>
                    <td>${enrollmentStatusLabel(p.enrollment_status)}</td>
                    <td>${p.enrollment_date ?? '-'}</td>
                `);

                renderRows('detail_progress_reports', data.progress_reports, 4, r => `
                    <td>${r.report_type ?? '-'}</td>
                    <td>${r.report_period ?? '-'}</td>
                    <td>${r.status ?? '-'}</td>
                    <td>${r.uploaded_at ?? '-'}</td>
                `);

                renderRows('detail_payments', data.payments, 7, p => `
                    <td>${p.invoice_number ?? '-'}</td>
                    <td>${p.program_name ?? '-'}</td>
                    <td>${formatRupiah(p.total_bill)}</td>
                    <td>${formatRupiah(p.amount_paid)}</td>
                    <td>${formatRupiah(p.remaining_bill)}</td>
                    <td>${p.status ?? '-'}</td>
                    <td>${p.payment_date ?? '-'}</td>
                `);
            })
            .catch(() => {
                alert('Gagal memuat detail siswa.');
                closeDetailModal();
            });
        }

        function closeDetailModal() {
            document.getElementById('detailModalOverlay').classList.remove('open');
        }

        document.getElementById('detailModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeDetailModal();
        });

        function openAssignClassModal(enrollmentId) {
            const overlay = document.getElementById('assignClassModalOverlay');
            const form = document.getElementById('assignClassForm');
            const select = document.getElementById('assign_class_id');
            const emptyNote = document.getElementById('assign_class_empty_note');

            form.action = `/admin/enrollments/${enrollmentId}/assign-class`;
            select.innerHTML = '<option value="">- Pilih Kelas -</option>';
            emptyNote.style.display = 'none';
            overlay.classList.add('open');

            fetch(`/admin/enrollments/${enrollmentId}/waiting-class-options`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(data => {
                const options = data.class_options ?? [];

                if (options.length === 0) {
                    emptyNote.style.display = 'block';
                    return;
                }

                options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.id;
                    option.textContent = `${opt.class_name} (${opt.delivery_mode}, ${opt.status})`;
                    select.appendChild(option);
                });
            })
            .catch(() => {
                alert('Gagal memuat daftar kelas.');
                closeAssignClassModal();
            });
        }

        function closeAssignClassModal() {
            document.getElementById('assignClassModalOverlay').classList.remove('open');
        }

        document.getElementById('assignClassModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeAssignClassModal();
        });
    </script>
@endpush

@if ($errors->any() && session('continue_student_id'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            openContinueModal({{ session('continue_student_id') }});
        });
    </script>
@endif