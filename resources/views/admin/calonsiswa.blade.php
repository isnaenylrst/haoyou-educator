@extends('admin.app')

@section('title', 'Calon Siswa | CRM')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">CRM</div>
            <h1>Calon Siswa</h1>
            <p>
                Kelola informasi calon siswa mulai dari pendaftaran,
                jadwal trial, hingga proses konversi menjadi siswa aktif.
            </p>
        </div>

        <div class="dashboard-header-actions">
            <button class="btn">
                <i class="fa-solid fa-file-export"></i>
                Export
            </button>

            <button type="button" class="btn btn-primary" onclick="openLeadModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Lead
            </button>
        </div>
    </div>

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Total Lead Aktif</span>
                <i class="fa-solid fa-users stat-icon"></i>
            </div>
            <div class="stat-value">{{ $totalLead }}</div>
            <div class="stat-note">Total calon siswa</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Trial Terjadwal</span>
                <i class="fa-solid fa-flask stat-icon"></i>
            </div>
            <div class="stat-value">{{ $trialScheduled }}</div>
            <div class="stat-note">Status Pending</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Follow-up Overdue</span>
                <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
            </div>
            <div class="stat-value">{{ $followUpOverdue }}</div>
            <div class="stat-note warn">Perlu ditindaklanjuti</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Conversion Rate</span>
                <i class="fa-solid fa-arrow-trend-up stat-icon"></i>
            </div>
            <div class="stat-value">{{ $conversionRate }}%</div>
            <div class="stat-note">Lead menjadi siswa</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.calon-siswa') }}" id="filterForm">
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

            <select name="lead_status" class="select-chip" onchange="this.form.submit()">
                <option value="">Status Lead</option>
                <option value="Cold" {{ request('lead_status') == 'Cold' ? 'selected' : '' }}>Cold</option>
                <option value="Warm" {{ request('lead_status') == 'Warm' ? 'selected' : '' }}>Warm</option>
                <option value="Hot" {{ request('lead_status') == 'Hot' ? 'selected' : '' }}>Hot</option>
            </select>

            <select name="trial_status" class="select-chip" onchange="this.form.submit()">
                <option value="">Status Trial</option>
                <option value="Pending" {{ request('trial_status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Completed" {{ request('trial_status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                <option value="Cancelled" {{ request('trial_status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <select name="source" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Sumber</option>
                <option value="Instagram" {{ request('source') == 'Instagram' ? 'selected' : '' }}>Instagram</option>
                <option value="TikTok" {{ request('source') == 'TikTok' ? 'selected' : '' }}>TikTok</option>
                <option value="Website" {{ request('source') == 'Website' ? 'selected' : '' }}>Website</option>
                <option value="Referral" {{ request('source') == 'Referral' ? 'selected' : '' }}>Referral</option>
                <option value="Walk-in" {{ request('source') == 'Walk-in' ? 'selected' : '' }}>Walk-in</option>
            </select>

            <select name="followup" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Follow-up</option>
                <option value="today" {{ request('followup') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                <option value="overdue" {{ request('followup') == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>

            <a href="{{ route('admin.calon-siswa') }}" class="btn">
                <i class="fa-solid fa-rotate-right"></i>
                Reset
            </a>
        </div>
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Calon Siswa</th>
                    <th>Program</th>
                    <th>Sumber</th>
                    <th>Status Lead</th>
                    <th>Status Trial</th>
                    <th>Tanggal Trial</th>
                    <th>Follow-up Berikutnya</th>
                    <th width="120" style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($candidateStudents as $candidate)
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">
                                    {{ strtoupper(substr($candidate->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cand-name">{{ $candidate->name }}</div>
                                    <div class="cand-sub">
                                        {{ $candidate->age ? $candidate->age . ' Tahun' : '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>{{ $candidate->program_package ?: '-' }}</td>
                        <td>{{ $candidate->source ?: '-' }}</td>

                        <td>
                            @php
                                $leadClass = match ($candidate->lead_status) {
                                    'Cold' => 'badge-cold',
                                    'Warm' => 'badge-warm',
                                    'Hot' => 'badge-hot',
                                    default => '',
                                };
                            @endphp
                            <span class="badge {{ $leadClass }}">
                                <span class="badge-dot"></span>
                                {{ $candidate->lead_status }}
                            </span>
                        </td>

                        <td>
                            @php
                                $trialClass = match ($candidate->trial_status) {
                                    'Pending' => 'badge-pending',
                                    'Completed' => 'badge-completed',
                                    'Cancelled' => 'badge-cancelled',
                                    default => '',
                                };
                            @endphp
                            <span class="badge {{ $trialClass }}">
                                <span class="badge-dot"></span>
                                {{ $candidate->trial_status }}
                            </span>
                        </td>

                        <td>
                            {{ $candidate->trial_date ? \Carbon\Carbon::parse($candidate->trial_date)->format('d M Y') : '-' }}
                        </td>

                        <td>
                            @if($candidate->latestFollowUp)
                                @php
                                    $next = \Carbon\Carbon::parse($candidate->latestFollowUp->next_followup);
                                @endphp
                                @if($next->isPast())
                                    <span class="followup-flag overdue"><i class="fa-solid fa-circle"></i> Overdue</span>
                                @elseif($next->isToday())
                                    <span class="followup-flag today"><i class="fa-solid fa-circle"></i> Hari Ini</span>
                                @else
                                    <span class="followup-flag upcoming"><i class="fa-solid fa-circle"></i> {{ $next->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="followup-flag upcoming">-</span>
                            @endif
                        </td>

                        {{-- Action --}}
                        <td>
                            <div class="row-actions">
                                <button
                                    type="button"
                                    class="icon-btn"
                                    title="Edit"
                                    onclick="openEditModal({{ $candidate->id }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <button class="icon-btn wa" title="Follow Up">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </button>

                                <form
                                    action="{{ route('admin.calon-siswa.destroy', $candidate->id) }}"
                                    method="POST"
                                    class="delete-form"
                                    onsubmit="return confirm('Yakin ingin menghapus data {{ $candidate->name }}? Tindakan ini tidak bisa dibatalkan.');">
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
                        <td colspan="8" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data calon siswa.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $candidateStudents->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $candidateStudents->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $candidateStudents->total() }}</strong>
                calon siswa
            </span>

            <div class="table-footer-pagination">
                @if ($candidateStudents->hasPages())
                    @php
                        $current = $candidateStudents->currentPage();
                        $last = $candidateStudents->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($candidateStudents->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $candidateStudents->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
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
                                        <li class="page-item"><a class="page-btn" href="{{ $candidateStudents->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($candidateStudents->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $candidateStudents->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — TAMBAH LEAD ========================= --}}
<div class="modal-overlay" id="leadModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.calon-siswa.store') }}" method="POST">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Tambah Lead Baru</div>
                        <div class="modal-sub">Isi data calon siswa yang masuk.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeLeadModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Calon Siswa</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-field">
                                <label>Jenis Kelamin *</label>
                                <div class="form-radio-group">
                                    <label class="form-radio"><input type="radio" name="gender" value="Male" required> Laki-laki</label>
                                    <label class="form-radio"><input type="radio" name="gender" value="Female" checked> Perempuan</label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Lahir *</label>
                                <input type="date" name="birth_date">
                            </div>
                            <div class="form-field">
                                <label>No. Telepon *</label>
                                <input type="text" name="phone" required>
                            </div>
                            <div class="form-field">
                                <label>Asal Sekolah</label>
                                <input type="text" name="school">
                            </div>
                            <div class="form-field full">
                                <label>Alamat</label>
                                <textarea name="address" rows="3"></textarea>
                            </div>
                            <div class="form-field full">
                                <label>Riwayat Alergi</label>
                                <textarea name="allergy" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-people-roof"></i> Data Orang Tua / Wali</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Nama Orang Tua</label>
                                <input type="text" name="parent_name">
                            </div>
                            <div class="form-field">
                                <label>No. Telepon Orang Tua</label>
                                <input type="text" name="parent_phone">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program Diminati</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Program Diminati *</label>
                                <input
                                    type="text"
                                    name="program_package"
                                    id="program_package"
                                    list="program_package_list"
                                    autocomplete="off"
                                    placeholder="Ketik nama program..."
                                    required>
                                <datalist id="program_package_list">
                                    @foreach ($programPackages as $programName => $packages)
                                        @foreach ($packages as $package)
                                            <option value="{{ $package->package_name }}">
                                                {{ $programName }} — {{ $package->course_type }} (Rp {{ number_format($package->price, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-bullhorn"></i> Sumber Lead</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Sumber Lead *</label>
                                <select name="source" required>
                                    <option value="">Pilih Sumber</option>
                                    <option>Instagram</option>
                                    <option>TikTok</option>
                                    <option>Website</option>
                                    <option>Referral</option>
                                    <option>Walk-in</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-flask"></i> Trial Class</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Tanggal Trial</label>
                                <input type="date" name="trial_date">
                            </div>
                            <div class="form-field">
                                <label>Status Trial</label>
                                <input type="text" value="Pending" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Tersedia</div>
                        <div id="scheduleRowsAdd">
                            <div class="schedule-row">
                                <div class="form-field">
                                    <label>Hari</label>
                                    <select name="day[]">
                                        <option>Senin</option><option>Selasa</option><option>Rabu</option>
                                        <option>Kamis</option><option>Jumat</option><option>Sabtu</option>
                                    </select>
                                </div>
                                <div class="form-field"><label>Jam Mulai</label><input type="time" name="start_time[]"></div>
                                <div class="form-field"><label>Jam Selesai</label><input type="time" name="end_time[]"></div>
                                <button type="button" class="schedule-remove" onclick="this.closest('.schedule-row').remove()"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" class="add-schedule-btn" onclick="addScheduleRow('scheduleRowsAdd')">
                            <i class="fa-solid fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn" onclick="closeLeadModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Lead</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — EDIT CALON SISWA ========================= --}}
    <div class="modal-overlay" id="editModalOverlay">
        <div class="modal">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Edit Calon Siswa</div>
                        <div class="modal-sub">Perbarui data calon siswa ini.</div>
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
                        <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Calon Siswa</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="name" id="edit_name" required>
                            </div>
                            <div class="form-field">
                                <label>Jenis Kelamin *</label>
                                <div class="form-radio-group">
                                    <label class="form-radio"><input type="radio" name="gender" value="Male" id="edit_gender_male" required> Laki-laki</label>
                                    <label class="form-radio"><input type="radio" name="gender" value="Female" id="edit_gender_female"> Perempuan</label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Tanggal Lahir *</label>
                                <input type="date" name="birth_date" id="edit_birth_date">
                            </div>
                            <div class="form-field">
                                <label>No. Telepon *</label>
                                <input type="text" name="phone" id="edit_phone" required>
                            </div>
                            <div class="form-field">
                                <label>Asal Sekolah</label>
                                <input type="text" name="school" id="edit_school">
                            </div>
                            <div class="form-field full">
                                <label>Alamat</label>
                                <textarea name="address" id="edit_address" rows="3"></textarea>
                            </div>
                            <div class="form-field full">
                                <label>Riwayat Alergi</label>
                                <textarea name="allergy" id="edit_allergy" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-people-roof"></i> Data Orang Tua / Wali</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Nama Orang Tua</label>
                                <input type="text" name="parent_name" id="edit_parent_name">
                            </div>
                            <div class="form-field">
                                <label>No. Telepon Orang Tua</label>
                                <input type="text" name="parent_phone" id="edit_parent_phone">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program Diminati</div>
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Program Diminati *</label>
                                <input
                                    type="text"
                                    name="program_package"
                                    id="edit_program_package"
                                    list="edit_program_package_list"
                                    autocomplete="off"
                                    placeholder="Ketik nama program..."
                                    required>
                                <datalist id="edit_program_package_list">
                                    @foreach ($programPackages as $programName => $packages)
                                        @foreach ($packages as $package)
                                            <option value="{{ $package->package_name }}">
                                                {{ $programName }} — {{ $package->course_type }} (Rp {{ number_format($package->price, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-bullhorn"></i> Sumber Lead</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Sumber Lead *</label>
                                <select name="source" id="edit_source" required>
                                    <option value="">Pilih Sumber</option>
                                    <option>Instagram</option>
                                    <option>TikTok</option>
                                    <option>Website</option>
                                    <option>Referral</option>
                                    <option>Walk-in</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Status Lead *</label>
                                <select name="lead_status" id="edit_lead_status" required>
                                    <option value="Cold">Cold</option>
                                    <option value="Warm">Warm</option>
                                    <option value="Hot">Hot</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-flask"></i> Trial Class</div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Tanggal Trial</label>
                                <input type="date" name="trial_date" id="edit_trial_date">
                            </div>
                            <div class="form-field">
                                <label>Status Trial *</label>
                                <select name="trial_status" id="edit_trial_status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Tersedia</div>
                        <div id="scheduleRowsEdit">
                            {{-- diisi ulang lewat JS setiap modal dibuka --}}
                        </div>
                        <button type="button" class="add-schedule-btn" onclick="addScheduleRow('scheduleRowsEdit')">
                            <i class="fa-solid fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>

                </div>

                <div class="modal-foot">
                    <div class="modal-foot-left">
                        <button type="button" class="btn btn-convert">
                            <i class="fa-solid fa-user-plus"></i>
                            Jadikan Siswa
                        </button>
                    </div>

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
       MODAL: TAMBAH LEAD
    ============================================================ */
    function openLeadModal() {
        document.getElementById('leadModalOverlay').classList.add('open');
    }

    function closeLeadModal() {
        document.getElementById('leadModalOverlay').classList.remove('open');
    }

    document.getElementById('leadModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeLeadModal();
    });

    /* ============================================================
       JADWAL TERSEDIA — dipakai bareng oleh modal Tambah & Edit
    ============================================================ */
    function addScheduleRow(containerId, values = {}) {
        const wrap = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'schedule-row';
        row.innerHTML = `
            <div class="form-field">
                <label>Hari</label>
                <select name="day[]">
                    <option>Senin</option>
                    <option>Selasa</option>
                    <option>Rabu</option>
                    <option>Kamis</option>
                    <option>Jumat</option>
                    <option>Sabtu</option>
                </select>
            </div>
            <div class="form-field">
                <label>Jam Mulai</label>
                <input type="time" name="start_time[]">
            </div>
            <div class="form-field">
                <label>Jam Selesai</label>
                <input type="time" name="end_time[]">
            </div>
            <button type="button" class="schedule-remove" onclick="this.closest('.schedule-row').remove()">
                <i class="fa-solid fa-trash"></i>
            </button>`;

        if (values.day) row.querySelector('select[name="day[]"]').value = values.day;
        if (values.start_time) row.querySelector('input[name="start_time[]"]').value = values.start_time;
        if (values.end_time) row.querySelector('input[name="end_time[]"]').value = values.end_time;

        wrap.appendChild(row);
    }

    /* ============================================================
       MODAL: EDIT CALON SISWA
       (menggantikan modal Detail — field sekarang bisa disunting)
    ============================================================ */
    function setSelectValue(id, value) {
        const el = document.getElementById(id);
        el.value = value ?? '';

        if (value && el.value !== value) {
            console.warn(`[Edit Calon Siswa] Nilai "${value}" untuk #${id} tidak ada di pilihan dropdown.`);
        }
    }

    function openEditModal(id) {
        const overlay = document.getElementById('editModalOverlay');
        const form = document.getElementById('editForm');

        overlay.classList.add('open');
        form.action = `/admin/calon-siswa/${id}`;

        document.getElementById('scheduleRowsEdit').innerHTML = '';

        fetch(`/admin/calon-siswa/${id}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Gagal memuat data');
            return response.json();
        })
        .then(data => {
            document.getElementById('edit_name').value = data.name ?? '';
            document.getElementById(data.gender === 'Male' ? 'edit_gender_male' : 'edit_gender_female').checked = true;
            document.getElementById('edit_birth_date').value = data.birth_date ?? '';
            document.getElementById('edit_phone').value = data.phone ?? '';
            document.getElementById('edit_school').value = data.school ?? '';
            document.getElementById('edit_address').value = data.address ?? '';
            document.getElementById('edit_allergy').value = data.allergy ?? '';
            document.getElementById('edit_parent_name').value = data.parent_name ?? '';
            document.getElementById('edit_parent_phone').value = data.parent_phone ?? '';
            document.getElementById('edit_program_package').value = data.program_package ?? '';
            setSelectValue('edit_source', data.source);
            document.getElementById('edit_lead_status').value = data.lead_status ?? 'Cold';
            document.getElementById('edit_trial_date').value = data.trial_date ?? '';
            document.getElementById('edit_trial_status').value = data.trial_status ?? 'Pending';

            if (data.schedules && data.schedules.length > 0) {
                data.schedules.forEach(s => addScheduleRow('scheduleRowsEdit', s));
            } else {
                addScheduleRow('scheduleRowsEdit');
            }
        })
        .catch(() => {
            alert('Gagal memuat data calon siswa untuk diedit.');
            closeEditModal();
        });
    }

    function closeEditModal() {
        document.getElementById('editModalOverlay').classList.remove('open');
    }

    document.getElementById('editModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeEditModal();
    });

</script>
@endpush