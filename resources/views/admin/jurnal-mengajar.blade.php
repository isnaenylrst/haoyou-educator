@extends('admin.app')

@section('title', 'Jurnal Mengajar | Akademik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin-extra.css') }}">
@endpush

@section('content')

    @php
        $statusMap = [
            'Conducted'   => ['Berjalan', 'badge-completed'],
            'Cancelled'   => ['Dibatalkan', 'badge-cancelled'],
            'Rescheduled' => ['Dijadwal Ulang', 'badge-pending'],
        ];
        $attendanceMap = ['Present' => 'Hadir', 'Absent' => 'Alpa', 'Sick' => 'Sakit', 'Permission' => 'Izin'];
        $programOf = fn ($class) => $class?->private_package_id
            ? 'Private'
            : ($class?->programPackage?->program?->program_name ?? $class?->level?->program?->program_name ?? '-');
        $keepQuery = request()->except('page', 'tab');
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK</div>
            <h1>Jurnal Mengajar</h1>
            <p>
                Pantau jurnal yang diisi laoshi. Sesi yang sudah lewat tapi jurnalnya kosong
                bisa langsung diingatkan lewat notifikasi.
            </p>
        </div>
    </div>

    @include('admin.partials.flash')

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Jurnal Masuk</span>
                <i class="fa-solid fa-book-open stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['masuk'] }}</div>
            <div class="stat-note">Pada periode yang dipilih</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Kelas Terlaksana</span>
                <i class="fa-solid fa-chalkboard-user stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['conducted'] }}</div>
            <div class="stat-note">{{ $stats['not_conducted'] }} dibatalkan / dijadwal ulang</div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Belum Diisi</span>
                <i class="fa-solid fa-circle-exclamation stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['belum'] }}</div>
            <div class="stat-note">Sesi sudah lewat, jurnal kosong</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Laoshi Tertunggak</span>
                <i class="fa-solid fa-user-clock stat-icon"></i>
            </div>
            <div class="stat-value">{{ $stats['teachers_late'] }}</div>
            <div class="stat-note">Perlu diingatkan</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.jurnal-mengajar') }}" id="filterForm">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas">
            </div>

            <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="select-chip" title="Dari tanggal" onchange="this.form.submit()">
            <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="select-chip" title="Sampai tanggal" onchange="this.form.submit()">

            <select name="teacher_id" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Laoshi</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>

            @if ($tab === 'masuk')
                <select name="class_status" class="select-chip" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach ($statusMap as $value => $meta)
                        <option value="{{ $value }}" {{ request('class_status') == $value ? 'selected' : '' }}>{{ $meta[0] }}</option>
                    @endforeach
                </select>
            @endif

            <a href="{{ route('admin.jurnal-mengajar', ['tab' => $tab]) }}" class="btn">
                <i class="fa-solid fa-rotate-right"></i>
                Reset
            </a>
        </div>
    </form>

    {{-- ========================= TAB ========================= --}}
    <div class="x-tabs">
        <a class="x-tab {{ $tab === 'masuk' ? 'active' : '' }}"
           href="{{ route('admin.jurnal-mengajar', array_merge($keepQuery, ['tab' => 'masuk'])) }}">
            Jurnal Masuk <span class="x-count">{{ $stats['masuk'] }}</span>
        </a>
        <a class="x-tab {{ $tab === 'belum' ? 'active' : '' }}"
           href="{{ route('admin.jurnal-mengajar', array_merge($keepQuery, ['tab' => 'belum'])) }}">
            Belum Diisi <span class="x-count {{ $stats['belum'] ? 'x-count-red' : '' }}">{{ $stats['belum'] }}</span>
        </a>
    </div>

    {{-- ========================= TAB: JURNAL MASUK ========================= --}}
    @if ($tab === 'masuk')
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kelas</th>
                        <th>Laoshi</th>
                        <th>Jadwal</th>
                        <th>Materi</th>
                        <th>Status</th>
                        <th>Kehadiran</th>
                        <th width="80" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journals as $j)
                        @php
                            [$statusLabel, $statusClass] = $statusMap[$j->class_status] ?? [$j->class_status, ''];
                            $schedule = $j->classSchedule;
                            $scheduleText = $schedule
                                ? $schedule->day . ' ' . substr($schedule->start_time, 0, 5) . '–' . substr($schedule->end_time, 0, 5)
                                : '-';
                            $presentCount = $j->attendances->where('status', 'Present')->count();

                            $detail = [
                                'date'       => $j->session_date->copy()->locale('id')->translatedFormat('l, d F Y'),
                                'class'      => $j->class?->class_name ?? '-',
                                'teacher'    => $j->teacher?->name ?? '-',
                                'substitute' => $j->is_substitute ? ($j->substituteTeacher?->name ?? 'Ya') : null,
                                'schedule'   => $scheduleText . ($schedule?->room ? ' · ' . $schedule->room : ''),
                                'material'   => $j->material ? 'Pertemuan ' . $j->material->meeting_number . ' — ' . $j->material->title : '-',
                                'status'     => $statusLabel,
                                'activities' => $j->learning_activities,
                                'problems'   => $j->problems,
                                'solutions'  => $j->solutions,
                                'results'    => $j->results,
                                'notes'      => $j->notes,
                                'attendance' => $j->attendances->map(fn ($a) => [
                                    'name'   => $a->student?->name ?? '-',
                                    'status' => $attendanceMap[$a->status] ?? $a->status,
                                    'note'   => $a->note,
                                ])->values(),
                            ];
                        @endphp
                        <tr>
                            <td>
                                {{ $j->session_date->format('d M Y') }}
                                <div class="cand-sub">{{ $j->session_date->copy()->locale('id')->translatedFormat('l') }}</div>
                            </td>
                            <td>
                                <div class="cand-name">{{ $j->class?->class_name ?? '-' }}</div>
                                <div class="cand-sub">{{ $programOf($j->class) }}@if ($j->class?->level) · {{ $j->class->level->level_name }}@endif</div>
                            </td>
                            <td>
                                {{ $j->teacher?->name ?? '-' }}
                                @if ($j->is_substitute)
                                    <span class="badge badge-warning" style="margin-left:4px;">Pengganti</span>
                                    @if ($j->substituteTeacher)
                                        <div class="cand-sub">{{ $j->substituteTeacher->name }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $scheduleText }}</td>
                            <td>
                                @if ($j->material)
                                    Pertemuan {{ $j->material->meeting_number }}
                                    <div class="cand-sub">{{ \Illuminate\Support\Str::limit($j->material->title, 36) }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}"><span class="badge-dot"></span>{{ $statusLabel }}</span>
                            </td>
                            <td>
                                @if ($j->attendances->count())
                                    {{ $presentCount }}/{{ $j->attendances->count() }} hadir
                                @else
                                    <span class="x-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="icon-btn" title="Lihat detail"
                                            data-journal="{{ json_encode($detail) }}"
                                            onclick="openJournalModal(this)">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="x-empty">
                                <i class="fa-regular fa-folder-open"></i>
                                Belum ada jurnal pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @include('admin.partials.pagination', ['paginator' => $journals, 'noun' => 'jurnal'])
        </div>
    @endif

    {{-- ========================= TAB: BELUM DIISI ========================= --}}
    @if ($tab === 'belum')

        @if ($byTeacher->isNotEmpty())
            <div class="card x-summary">
                <div class="x-summary-title"><i class="fa-solid fa-bell"></i> Laoshi dengan jurnal tertunggak</div>
                <div class="x-summary-list">
                    @foreach ($byTeacher as $row)
                        <div class="x-summary-item">
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($row['teacher']->name, 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $row['teacher']->name }}</div>
                                    <div class="cand-sub">
                                        <span class="x-late">{{ $row['count'] }} jurnal belum diisi</span>
                                        @if ($row['last_reminded'])
                                            · diingatkan {{ $row['last_reminded']->locale('id')->diffForHumans() }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.jurnal-mengajar.remind') }}" class="x-inline-form">
                                @csrf
                                <input type="hidden" name="teacher_id" value="{{ $row['teacher']->id }}">
                                <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                                <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-regular fa-bell"></i> Ingatkan semua
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Sesi</th>
                        <th>Kelas</th>
                        <th>Laoshi</th>
                        <th>Jadwal</th>
                        <th>Keterlambatan</th>
                        <th>Pengingat</th>
                        <th width="110" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($missingPage as $m)
                        <tr>
                            <td>
                                {{ $m->date->format('d M Y') }}
                                <div class="cand-sub">{{ $m->date->copy()->locale('id')->translatedFormat('l') }}</div>
                            </td>
                            <td>
                                <div class="cand-name">{{ $m->class->class_name }}</div>
                                <div class="cand-sub">{{ $programOf($m->class) }}@if ($m->class->level) · {{ $m->class->level->level_name }}@endif</div>
                            </td>
                            <td>
                                {{ $m->teacher->name }}
                                @if ($m->is_substitute)
                                    <span class="badge badge-warning" style="margin-left:4px;">Pengganti</span>
                                @endif
                            </td>
                            <td>
                                {{ $m->schedule->day }}
                                {{ substr($m->schedule->start_time, 0, 5) }}–{{ substr($m->schedule->end_time, 0, 5) }}
                                @if ($m->schedule->room)
                                    <div class="cand-sub">{{ $m->schedule->room }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $m->days_late >= 3 ? 'x-late' : '' }}">
                                    {{ $m->days_late === 0 ? 'Hari ini' : $m->days_late . ' hari' }}
                                </span>
                            </td>
                            <td>
                                @if ($m->reminded_at)
                                    <span class="x-muted"><i class="fa-solid fa-check"></i> {{ $m->reminded_at->locale('id')->diffForHumans() }}</span>
                                @else
                                    <span class="x-muted">Belum diingatkan</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <form method="POST" action="{{ route('admin.jurnal-mengajar.remind') }}" class="x-inline-form">
                                        @csrf
                                        <input type="hidden" name="teacher_id" value="{{ $m->teacher->id }}">
                                        <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                                        <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                                        <input type="hidden" name="keys[]" value="{{ $m->key }}">
                                        <button type="submit" class="icon-btn wa" title="Ingatkan laoshi untuk sesi ini">
                                            <i class="fa-regular fa-bell"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="x-empty is-ok">
                                <i class="fa-solid fa-circle-check"></i>
                                Semua jurnal pada periode ini sudah terisi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @include('admin.partials.pagination', ['paginator' => $missingPage, 'noun' => 'sesi'])
        </div>
    @endif

    {{-- ========================= MODAL DETAIL JURNAL ========================= --}}
    <div class="modal-overlay" id="journalModalOverlay">
        <div class="modal modal-lg">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Detail Jurnal Mengajar</div>
                    <div class="modal-sub" id="jr_class">-</div>
                </div>
                <button type="button" class="modal-close" onclick="closeJournalModal()" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="x-detail-grid">
                    <div><div class="x-detail-label">Tanggal</div><div class="x-detail-value" id="jr_date">-</div></div>
                    <div><div class="x-detail-label">Jadwal</div><div class="x-detail-value" id="jr_schedule">-</div></div>
                    <div><div class="x-detail-label">Laoshi</div><div class="x-detail-value" id="jr_teacher">-</div></div>
                    <div><div class="x-detail-label">Status</div><div class="x-detail-value" id="jr_status">-</div></div>
                    <div class="full" id="jr_substitute_wrap" style="display:none;">
                        <div class="x-detail-label">Guru Pengganti</div><div class="x-detail-value" id="jr_substitute">-</div>
                    </div>
                    <div class="full"><div class="x-detail-label">Materi</div><div class="x-detail-value" id="jr_material">-</div></div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-person-chalkboard"></i> Aktivitas Pembelajaran</div>
                    <div class="x-pre" id="jr_activities">-</div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-clipboard-list"></i> Kendala, Solusi &amp; Hasil</div>
                    <div class="x-detail-grid">
                        <div class="full"><div class="x-detail-label">Kendala</div><div class="x-pre" id="jr_problems">-</div></div>
                        <div class="full"><div class="x-detail-label">Solusi</div><div class="x-pre" id="jr_solutions">-</div></div>
                        <div class="full"><div class="x-detail-label">Hasil</div><div class="x-pre" id="jr_results">-</div></div>
                        <div class="full"><div class="x-detail-label">Catatan</div><div class="x-pre" id="jr_notes">-</div></div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-user-check"></i> Kehadiran Siswa</div>
                    <table class="table-mini">
                        <thead><tr><th>Siswa</th><th>Status</th><th>Catatan</th></tr></thead>
                        <tbody id="jr_attendance"><tr><td colspan="3">-</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-foot">
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" onclick="closeJournalModal()">Tutup</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            let timer;

            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => filterForm.submit(), 600);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); filterForm.submit(); }
            });
        })();

        function esc(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function setText(id, value) {
            document.getElementById(id).textContent = (value && String(value).trim()) ? value : '-';
        }

        function openJournalModal(button) {
            const d = JSON.parse(button.dataset.journal);

            setText('jr_class', d.class);
            setText('jr_date', d.date);
            setText('jr_schedule', d.schedule);
            setText('jr_teacher', d.teacher);
            setText('jr_status', d.status);
            setText('jr_material', d.material);
            setText('jr_activities', d.activities);
            setText('jr_problems', d.problems);
            setText('jr_solutions', d.solutions);
            setText('jr_results', d.results);
            setText('jr_notes', d.notes);

            document.getElementById('jr_substitute_wrap').style.display = d.substitute ? '' : 'none';
            setText('jr_substitute', d.substitute);

            const body = document.getElementById('jr_attendance');
            body.innerHTML = (d.attendance && d.attendance.length)
                ? d.attendance.map(a => `<tr><td>${esc(a.name)}</td><td>${esc(a.status)}</td><td>${esc(a.note) || '-'}</td></tr>`).join('')
                : '<tr><td colspan="3">Belum ada data kehadiran.</td></tr>';

            document.getElementById('journalModalOverlay').classList.add('open');
        }

        function closeJournalModal() {
            document.getElementById('journalModalOverlay').classList.remove('open');
        }

        document.getElementById('journalModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeJournalModal();
        });
    </script>
@endpush
