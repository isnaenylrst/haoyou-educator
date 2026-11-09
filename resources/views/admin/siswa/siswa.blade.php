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
                    <th style="text-align: center;">Program</th>
                    <th style="text-align: center;">Hadir</th>
                    <th style="text-align: center;">Tidak Hadir</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Poin</th>
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

                        // Kategori program yang diikuti (mis. Maochong); Private tetap "Private"
                        $categoryLabel = $isPrivate
                            ? 'Private'
                            : ($student->currentLevel?->category?->category_name ?? $programLabel);

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
                                        @if ($isWaitingClass)
                                            <span style="color:#dc2626;font-weight:600;">Menunggu Kelas</span>
                                        @else
                                            {{ $kelasAktifLabel }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            @if ($categoryLabel && $categoryLabel !== '-')
                                <span class="badge {{ $programBadgeClass }}">{{ $categoryLabel }}</span>
                            @else
                                -
                            @endif
                        </td>

                        @php
                            $classId = $activeEnrollment?->class_id;
                            $attended = $classId ? (int) ($attendedMap[$student->id][$classId] ?? 0) : 0;
                            // $absentMap = [student_id => [class_id => jumlah tidak hadir]] dari controller
                            $absent = ($classId && isset($absentMap)) ? (int) ($absentMap[$student->id][$classId] ?? 0) : null;
                            $held = $classId ? (int) ($heldMap[$classId] ?? 0) : 0;
                            $totalMeetings = (int) (
                                $activeEnrollment?->class?->programPackage?->total_meetings
                                ?? $activeEnrollment?->programPackage?->total_meetings
                                ?? $activeEnrollment?->privatePackage?->total_meetings
                                ?? 0
                            );
                            $pct = $totalMeetings > 0 ? min(100, (int) round($attended / $totalMeetings * 100)) : 0;
                            $absentPct = ($totalMeetings > 0 && $absent !== null) ? min(100, (int) round($absent / $totalMeetings * 100)) : 0;
                        @endphp

                        {{-- Hadir --}}
                        <td style="text-align: center; vertical-align: middle;">
                            @if ($classId)
                                <div class="meet-label">
                                    <strong>{{ $attended }}</strong>@if ($totalMeetings > 0)<span>/{{ $totalMeetings }}</span>@endif
                                    <span>(hadir)</span>
                                </div>
                            @else
                                <span class="cand-sub">-</span>
                            @endif
                        </td>

                        {{-- Tidak Hadir --}}
                        <td style="text-align: center; vertical-align: middle;">
                            @if ($classId && $absent !== null)
                                <div class="meet-label absent">
                                    <strong>{{ $absent }}</strong>@if ($totalMeetings > 0)<span>/{{ $totalMeetings }}</span>@endif
                                    <span>(tidak hadir)</span>
                                </div>
                            @else
                                <span class="cand-sub">-</span>
                            @endif
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
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

                        <td style="text-align: center; vertical-align: middle;">{{ $student->points }}</td>

                        {{-- Action --}}
                        <td style="text-align: center; vertical-align: middle;">
                            @php
                                $hasOutstanding = (bool) ($student->has_outstanding ?? false);

                                // Kelas pengganti hanya untuk siswa aktif yang sedang punya kelas berjalan
                                $classStatus = $activeEnrollment?->class?->status;
                                $canReplace  = $student->status === 'Active'
                                    && $activeEnrollment
                                    && ! $isWaitingClass
                                    && $activeEnrollment->class_id
                                    && in_array($classStatus, ['Open', 'Running'], true);

                                $replaceHint = $canReplace
                                    ? 'Tambah Kelas Pengganti'
                                    : match (true) {
                                        ! $activeEnrollment           => 'Kelas pengganti tidak tersedia: siswa tidak sedang mengikuti kelas',
                                        $isWaitingClass               => 'Kelas pengganti tidak tersedia: siswa masih menunggu kelas',
                                        $student->status !== 'Active' => 'Kelas pengganti tidak tersedia: siswa tidak aktif',
                                        default                       => 'Kelas pengganti tidak tersedia: kelas sudah selesai',
                                    };
                            @endphp

                            <div class="row-actions">
                                {{-- 1. Detail & Edit --}}
                                <a class="icon-btn" title="Detail & Edit"
                                   href="{{ route('admin.siswa.detail', $student) }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>

                                {{-- 2. Assign Kelas / Lanjutkan Pembayaran / Lanjut Program --}}
                                @if ($isWaitingClass)
                                    <button type="button" class="icon-btn wa" title="Assign Kelas"
                                            onclick="openAssignClassModal({{ $activeEnrollment->id }})">
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                    </button>
                                @elseif ($hasOutstanding)
                                    <a class="icon-btn wa" title="Lanjutkan Pembayaran"
                                       href="{{ route('admin.siswa.continue.program', $student) }}">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </a>
                                @else
                                    <a class="icon-btn wa" title="Lanjut Program Berikutnya"
                                       href="{{ route('admin.siswa.continue.program', $student) }}">
                                        <i class="fa-solid fa-forward"></i>
                                    </a>
                                @endif

                                {{-- 3. Kelas Pengganti --}}
                                @if ($canReplace)
                                    <a class="icon-btn wa" title="{{ $replaceHint }}"
                                       href="{{ route('admin.siswa.replacement', $student) }}">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </a>
                                @else
                                    <button type="button" class="icon-btn is-disabled" disabled title="{{ $replaceHint }}">
                                        <i class="fa-solid fa-calendar-plus"></i>
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
                    <div class="form-section">
                        <div class="form-field full">
                            <label>Jadwal Siswa</label>
                            <div id="assign_student_schedule" style="font-size:13px;color:#374151;">-</div>
                        </div>
                    </div>

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
        /* ============================================================
        PENCARIAN (auto-submit)
        ============================================================ */
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
        MODAL: ASSIGN KELAS
        ============================================================ */
        function openAssignClassModal(enrollmentId) {
            const overlay = document.getElementById('assignClassModalOverlay');
            const form = document.getElementById('assignClassForm');
            const select = document.getElementById('assign_class_id');
            const emptyNote = document.getElementById('assign_class_empty_note');
            const scheduleBox = document.getElementById('assign_student_schedule');

            form.action = `/admin/enrollments/${enrollmentId}/assign-class`;
            select.innerHTML = '<option value="">- Pilih Kelas -</option>';
            emptyNote.style.display = 'none';
            scheduleBox.textContent = '-';
            overlay.classList.add('open');

            fetch(`/admin/enrollments/${enrollmentId}/waiting-class-options`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(data => {
                const schedules = data.student_schedules ?? [];
                scheduleBox.textContent = schedules.length
                    ? schedules.map(s => `${s.day} ${s.start_time}–${s.end_time}`).join(', ')
                    : 'Belum ada jadwal tersimpan';

                const options = data.class_options ?? [];

                if (options.length === 0) {
                    emptyNote.style.display = 'block';
                    return;
                }

                options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.id;

                    const scheduleText = opt.schedule_text ? ` — ${opt.schedule_text}` : '';
                    const kuotaText = opt.kuota_text ? ` [${opt.kuota_text}]` : '';

                    option.textContent = `${opt.class_name} (${opt.delivery_mode}, ${opt.status})${kuotaText}${scheduleText}${opt.is_full ? ' — PENUH' : ''}`;
                    option.disabled = opt.is_full;

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

        /* Tombol Esc menutup pop-up yang sedang terbuka */
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;

            closeAssignClassModal();
        });
    </script>
@endpush