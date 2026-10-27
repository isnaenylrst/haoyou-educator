{{--
    resources/views/admin/jurnalmengajar.blade.php
    Halaman READ-ONLY untuk Admin (jurnal diisi oleh Guru).

    Variabel dari controller:
      $journals      LengthAwarePaginator<TeachingJournal>
                     with(['class.level', 'class.privatePackage', 'class.programPackage',
                           'teacher', 'substituteTeacher', 'material', 'attendances'])
      $classes       Collection<ClassModel>  (id, class_name)  -> opsi filter
      $teachers      Collection<Teacher>     (id, name)        -> opsi filter
      $totalJournals, $conductedCount, $notConductedCount, $substituteCount  (angka statistik)
--}}
@extends('admin.app')

@section('title', 'Jurnal Mengajar | Pembelajaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/jurnalmengajar.css') }}">
@endpush

@section('content')
@php
    $classStatusMap = [
        'Conducted'   => ['label' => 'Terlaksana',        'badge' => 'badge-completed'],
        'Cancelled'   => ['label' => 'Dibatalkan',        'badge' => 'badge-cancelled'],
        'Rescheduled' => ['label' => 'Dijadwalkan Ulang', 'badge' => 'badge-pending'],
    ];
    $attendanceMap = [
        'Present'    => ['label' => 'Hadir', 'badge' => 'badge-completed'],
        'Permission' => ['label' => 'Izin',  'badge' => 'badge-pending'],
        'Sick'       => ['label' => 'Sakit', 'badge' => 'badge-warm'],
        'Absent'     => ['label' => 'Alpha', 'badge' => 'badge-cancelled'],
    ];


    $hasFilter = request()->hasAny(['search', 'class_id', 'teacher_id', 'class_status', 'from', 'to']);
@endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">Pembelajaran</div>
            <h1>Jurnal Mengajar</h1>
            <p>
                Catatan setiap pertemuan kelas yang diisi guru: materi yang diajarkan,
                kegiatan belajar, guru pengganti, dan kehadiran siswa.
            </p>
        </div>

    </div>

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Total Jurnal</span>
                <i class="fa-solid fa-book-open stat-icon"></i>
            </div>
            <div class="stat-value">{{ $totalJournals }}</div>
            <div class="stat-note">Seluruh pertemuan tercatat</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Terlaksana</span>
                <i class="fa-solid fa-chalkboard-user stat-icon"></i>
            </div>
            <div class="stat-value">{{ $conductedCount }}</div>
            <div class="stat-note">Pertemuan berjalan</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Tidak Terlaksana</span>
                <i class="fa-solid fa-ban stat-icon"></i>
            </div>
            <div class="stat-value">{{ $notConductedCount }}</div>
            <div class="stat-note warn">Dibatalkan atau dijadwalkan ulang</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Guru Pengganti</span>
                <i class="fa-solid fa-people-arrows stat-icon"></i>
            </div>
            <div class="stat-value">{{ $substituteCount }}</div>
            <div class="stat-note">Pertemuan diajar pengganti</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.jurnal-mengajar') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    id="searchInput"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama kelas atau guru">
            </div>

            <select name="class_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->class_name }}
                    </option>
                @endforeach
            </select>

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            <select name="class_status" class="select-chip" onchange="this.form.submit()">
                <option value="">Status Pertemuan</option>
                @foreach ($classStatusMap as $value => $meta)
                    <option value="{{ $value }}" {{ request('class_status') === $value ? 'selected' : '' }}>
                        {{ $meta['label'] }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}" class="select-chip" aria-label="Dari tanggal" onchange="this.form.submit()">
            <input type="date" name="to" value="{{ request('to') }}" class="select-chip" aria-label="Sampai tanggal" onchange="this.form.submit()">

            @if ($hasFilter)
                <a href="{{ route('admin.jurnal-mengajar') }}" class="btn">
                    <i class="fa-solid fa-rotate-right"></i>
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- ========================= TABEL ========================= --}}
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kelas</th>
                    <th>Guru</th>
                    <th>Materi</th>
                    <th>Kehadiran</th>
                    <th>Status</th>
                    <th width="120" style="text-align: center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($journals as $journal)
                    @php
                        $class     = $journal->class;
                        $status    = $classStatusMap[$journal->class_status] ?? ['label' => $journal->class_status, 'badge' => ''];
                        $teacher   = $journal->substituteTeacher ?? $journal->teacher;
                        $total     = $journal->attendances->count();
                        $present   = $journal->attendances->where('status', 'Present')->count();
                        $classSub  = $class?->level?->level_name
                                     ?? $class?->privatePackage?->package_name
                                     ?? $class?->programPackage?->package_name;
                    @endphp
                    <tr>
                        <td>
                            <div class="cand-name">{{ $journal->session_date?->format('d M Y') }}</div>
                            <div class="cand-sub">{{ $journal->session_date?->translatedFormat('l') }}</div>
                        </td>

                        <td>
                            <div class="cand-name">{{ $class?->class_name ?? '-' }}</div>
                            @if ($classSub)
                                <div class="cand-sub">{{ $classSub }}</div>
                            @endif
                        </td>

                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($teacher?->name ?? '?', 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $teacher?->name ?? '-' }}</div>
                                    @if ($journal->substituteTeacher)
                                        <div class="cand-sub">Pengganti {{ $journal->teacher?->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            @if ($journal->material)
                                {{ $journal->material->title }}
                            @elseif ($class?->private_package_id)
                                <span class="muted">Dari dokumen kelas</span>
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            @if ($journal->class_status === 'Conducted' && $total > 0)
                                {{ $present }}/{{ $total }} hadir
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <span class="badge {{ $status['badge'] }}">
                                <span class="badge-dot"></span>
                                {{ $status['label'] }}
                            </span>
                        </td>

                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Lihat detail"
                                        onclick="openDetailModal({{ $journal->id }})">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:50px;color:#888;">
                            {{ $hasFilter ? 'Tidak ada jurnal yang cocok dengan filter.' : 'Belum ada jurnal mengajar.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $journals->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $journals->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $journals->total() }}</strong>
                jurnal
            </span>

            <div class="table-footer-pagination">
                @if ($journals->hasPages())
                    @php
                        $current = $journals->currentPage();
                        $last = $journals->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($journals->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $journals->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
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
                                        <li class="page-item"><a class="page-btn" href="{{ $journals->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($journals->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $journals->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — DETAIL JURNAL ========================= --}}
    <div class="modal-overlay" id="detailModalOverlay">
        <div class="modal">
            <div class="modal-head">
                <div>
                    <div class="modal-title" id="dt_title">Detail Jurnal</div>
                    <div class="modal-sub" id="dt_subtitle"></div>
                </div>
                <button type="button" class="modal-close" onclick="closeDetailModal()" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-circle-info"></i> Pertemuan</div>
                    <dl class="detail-list">
                        <dt>Status</dt><dd id="dt_status"></dd>
                        <dt>Guru</dt><dd id="dt_teacher"></dd>
                        <dt>Materi</dt><dd id="dt_material"></dd>
                    </dl>
                </div>

                <div id="dt_learning">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-book-open-reader"></i> Catatan Pembelajaran</div>
                        <div class="detail-block"><h4>Kegiatan pembelajaran</h4><p id="dt_activities"></p></div>
                        <div class="detail-block"><h4>Kendala</h4><p id="dt_problems"></p></div>
                        <div class="detail-block"><h4>Solusi</h4><p id="dt_solutions"></p></div>
                        <div class="detail-block"><h4>Hasil</h4><p id="dt_results"></p></div>
                        <div class="detail-block"><h4>Catatan tambahan</h4><p id="dt_notes"></p></div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-user-check"></i> Kehadiran Siswa</div>
                        <table class="mini-table">
                            <thead><tr><th>Siswa</th><th>Status</th><th>Catatan</th></tr></thead>
                            <tbody id="dt_attendance"></tbody>
                        </table>
                    </div>
                </div>

                <div class="notice" id="dt_notice" style="display:none;"></div>
            </div>

            <div class="modal-foot">
                <div class="modal-foot-left"></div>
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
<script>
    const BASE       = @json(url('admin/jurnal-mengajar'));
    const STATUS_MAP = @json($classStatusMap);
    const ATT_MAP    = @json($attendanceMap);

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    /* ============================================================
       SEARCH (debounce) — sama seperti halaman Calon Siswa
    ============================================================ */
    (function () {
        const searchInput = $('searchInput');
        const filterForm = $('filterForm');
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => filterForm.submit(), 600);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                filterForm.submit();
            }
        });
    })();

    function formatDate(iso) {
        if (!iso) return '-';
        return new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
    }

    function badge(meta) {
        return `<span class="badge ${meta.badge}"><span class="badge-dot"></span>${esc(meta.label)}</span>`;
    }

    /* ============================================================
       MODAL: DETAIL (read-only)
    ============================================================ */
    function openDetailModal(id) {
        $('detailModalOverlay').classList.add('open');

        fetch(`${BASE}/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error('Gagal memuat data'); return r.json(); })
            .then(d => {
                const status = STATUS_MAP[d.class_status] || { label: d.class_status, badge: '' };
                const conducted = d.class_status === 'Conducted';

                $('dt_title').textContent = d.class_name || 'Detail Jurnal';
                $('dt_subtitle').textContent = formatDate(d.session_date);
                $('dt_status').innerHTML = badge(status);
                $('dt_teacher').textContent = d.substitute_name
                    ? `${d.substitute_name} (pengganti ${d.teacher_name ?? '-'})`
                    : (d.teacher_name || '-');
                $('dt_material').textContent = d.material_title || (d.is_private ? 'Dari dokumen kelas private' : '-');

                $('dt_learning').style.display = conducted ? '' : 'none';
                $('dt_notice').style.display = conducted ? 'none' : '';
                $('dt_notice').textContent = `Pertemuan ini ${status.label.toLowerCase()}, jadi tidak ada catatan pembelajaran atau kehadiran.`;

                const text = (v) => v && v.trim() ? v : 'Tidak ada catatan.';
                $('dt_activities').textContent = text(d.learning_activities);
                $('dt_problems').textContent   = text(d.problems);
                $('dt_solutions').textContent  = text(d.solutions);
                $('dt_results').textContent    = text(d.results);
                $('dt_notes').textContent      = text(d.notes);

                $('dt_attendance').innerHTML = (d.attendances && d.attendances.length)
                    ? d.attendances.map(a => `
                        <tr>
                            <td>${esc(a.name)}</td>
                            <td>${badge(ATT_MAP[a.status] || { label: a.status, badge: '' })}</td>
                            <td class="muted">${esc(a.note) || '-'}</td>
                        </tr>`).join('')
                    : '<tr><td colspan="3" class="muted center">Kehadiran belum dicatat.</td></tr>';
            })
            .catch(() => {
                alert('Gagal memuat detail jurnal.');
                closeDetailModal();
            });
    }

    function closeDetailModal() { $('detailModalOverlay').classList.remove('open'); }
    $('detailModalOverlay').addEventListener('click', function (e) { if (e.target === this) closeDetailModal(); });
</script>
@endpush