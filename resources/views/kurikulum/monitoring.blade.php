@extends('layouts.kurikulum')

@section('title', 'Monitoring Guru & Kelas')

@section('content')

<div class="container-fluid">

    {{-- ===== HEADER ===== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Monitoring Guru & Kelas</h2>
            <p class="text-muted mb-0">Monitoring jadwal, sesi mengajar, kehadiran, dan jurnal guru</p>
        </div>

        <div>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-download me-2"></i>
                Export Laporan
            </button>
        </div>
    </div>

    {{-- ===== STATISTIK UTAMA ===== --}}
    <div class="row mb-4">

        {{-- TOTAL GURU --}}
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-uppercase text-muted fw-bold">Total Guru</small>
                    <h2 class="fw-bold mt-2 mb-0">{{ $totalTeacher }}</h2>
                    <small class="text-muted">Guru terdaftar</small>
                </div>
            </div>
        </div>

        {{-- TOTAL JAM --}}
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-uppercase text-muted fw-bold">Total Jam Mengajar</small>
                    <h2 class="fw-bold mt-2 mb-0">{{ number_format($totalHour, 1, ',', '.') }} Jam</h2>
                    <small class="text-muted">Berdasarkan jadwal kelas</small>
                </div>
            </div>
        </div>

        {{-- TOTAL SESI --}}
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-uppercase text-muted fw-bold">Total Sesi Mengajar</small>
                    <h2 class="fw-bold mt-2 mb-0">{{ $totalSession }}</h2>
                    <small class="text-muted">Berdasarkan jurnal mengajar</small>
                </div>
            </div>
        </div>

        {{-- RATA-RATA --}}
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-uppercase text-muted fw-bold">Rata-rata Jam / Guru</small>
                    <h2 class="fw-bold mt-2 mb-0 text-warning">{{ number_format($averageHour, 1, ',', '.') }} Jam</h2>
                    <small class="text-muted">Rata-rata dari seluruh guru</small>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== JADWAL MENGAJAR MINGGUAN ===== --}}
    @php
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        $dayAlias = [
            'senin' => 'Senin',   'monday'    => 'Senin',
            'selasa' => 'Selasa', 'tuesday'   => 'Selasa',
            'rabu' => 'Rabu',     'wednesday' => 'Rabu',
            'kamis' => 'Kamis',   'thursday'  => 'Kamis',
            'jumat' => 'Jumat',   "jum'at"    => 'Jumat', 'friday' => 'Jumat',
            'sabtu' => 'Sabtu',   'saturday'  => 'Sabtu',
            'minggu' => 'Minggu', 'sunday'    => 'Minggu',
        ];

        // warna per jenis kelas (sama dengan menu Schedule guru)
        $colorFor = function ($name) {
            $n = strtolower((string) $name);

            if (str_contains($n, 'maochong')) return 'orange';
            if (str_contains($n, 'jianer') || str_contains($n, 'jian er')) return 'blue';
            if (str_contains($n, 'hudie')) return 'purple';
            if (str_contains($n, 'feixiang')) return 'red';

            return 'green'; // HSK / Private / lainnya
        };

        $schGrid     = [];
        $schSlots    = [];
        $schTeachers = [];

        foreach ($weeklySchedules as $schedule) {
            $day = $dayAlias[strtolower(trim((string) $schedule->day))] ?? null;

            if (!$day || !$schedule->start_time) {
                continue;
            }

            $slot = \Carbon\Carbon::parse($schedule->start_time)->format('H:i');

            $schSlots[$slot]         = true;
            $schGrid[$slot][$day][]  = $schedule;

            $schTeacher = optional($schedule->class)->teacher;
            if ($schTeacher) {
                $schTeachers[$schTeacher->id] = $schTeacher->name;
            }
        }

        ksort($schSlots);
        $schSlots = array_keys($schSlots);
        asort($schTeachers);

        // Senin - Sabtu selalu tampil; Minggu hanya jika ada jadwal
        $hasSunday = collect($schGrid)->contains(fn ($byDay) => isset($byDay['Minggu']));
        $schDays   = array_values(array_filter($dayNames, fn ($d) => $d !== 'Minggu' || $hasSunday));
        $todayName = $dayNames[now()->dayOfWeekIso - 1];
    @endphp

    <div class="card shadow-sm border-0 mb-5">

        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Jadwal Mengajar Mingguan</h5>
                    <small class="text-muted">Jadwal berdasarkan data class schedules</small>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <select id="schTeacher" class="form-select form-select-sm" style="min-width:190px">
                        <option value="">Semua guru</option>
                        @foreach($schTeachers as $tid => $tname)
                            <option value="{{ $tid }}">{{ $tname }}</option>
                        @endforeach
                    </select>

                    <span class="badge bg-primary">{{ $weeklySchedules->count() }} Jadwal</span>
                </div>
            </div>
        </div>

        <div class="card-body pb-2 pt-3">
            <div class="sch-legend">
                <span><i class="orange"></i>Maochong</span>
                <span><i class="blue"></i>Jianer</span>
                <span><i class="purple"></i>Hudie</span>
                <span><i class="red"></i>Feixiang</span>
                <span><i class="green"></i>HSK / Private / Lainnya</span>
            </div>
        </div>

        <div class="sch-wrap">
            <table class="sch-table">

                <thead>
                    <tr>
                        <th class="sch-time">Time</th>
                        @foreach($schDays as $d)
                            <th class="{{ $d === $todayName ? 'today' : '' }}">{{ $d }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse($schSlots as $slot)
                        <tr>
                            <td class="sch-time">{{ $slot }}</td>

                            @foreach($schDays as $d)
                                <td>
                                    @foreach($schGrid[$slot][$d] ?? [] as $schedule)
                                        @php
                                            $schClass   = $schedule->class;
                                            $className  = optional($schClass)->name ?? optional($schClass)->class_name ?? 'Kelas';
                                            $schTeacher = optional($schClass)->teacher;
                                            $schMode    = optional($schClass)->delivery_mode;
                                            $schEnd     = $schedule->end_time
                                                ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i')
                                                : '-';
                                        @endphp

                                        <div class="sch-card {{ $colorFor($className) }}"
                                             data-teacher="{{ optional($schTeacher)->id }}">
                                            <strong>{{ $className }}</strong>
                                            <small>
                                                {{ optional($schTeacher)->name ?? 'Belum ada guru' }}{{ $schMode ? ' · ' . $schMode : '' }}
                                            </small>
                                            <small class="d-block">
                                                {{ $slot }} – {{ $schEnd }}{{ $schedule->room ? ' · ' . $schedule->room : '' }}
                                            </small>
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($schDays) + 1 }}" class="text-center text-muted py-4">
                                Belum ada jadwal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    {{-- ===== RINGKASAN GURU ===== --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-1">Ringkasan Guru</h5>
            <small class="text-muted">Informasi berdasarkan data guru dan jurnal mengajar</small>
        </div>
    </div>

    <div class="row mb-5">

        @forelse($teachers->take(6) as $teacher)

            @php
                // jumlah jurnal guru
                $teacherJournals = $teacher->teachingJournals ?? collect();
                $sessionCount = $teacherJournals->count();

                // total jam guru
                $teacherHour = 0;
                $teacherSchedules = collect();

                foreach ($teacher->classes ?? collect() as $class) {
                    foreach ($class->schedules ?? collect() as $schedule) {
                        $teacherSchedules->push($schedule);

                        if ($schedule->start_time && $schedule->end_time) {
                            $start = strtotime($schedule->start_time);
                            $end = strtotime($schedule->end_time);

                            if ($end > $start) {
                                $teacherHour += ($end - $start) / 3600;
                            }
                        }
                    }
                }

                $teacherHour = round($teacherHour, 1);

                // kehadiran
                $attendances = collect();

                foreach ($teacherJournals as $journal) {
                    foreach ($journal->attendances ?? collect() as $attendance) {
                        $attendances->push($attendance);
                    }
                }

                $attendanceTotal = $attendances->count();
                $attendancePresent = $attendances->where('status', 'Present')->count();
                $attendancePercentage = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100) : 0;

                // status guru
                $teacherStatus = strtolower($teacher->status ?? 'active');

                if (in_array($teacherStatus, ['active', 'aktif'])) {
                    $statusLabel = 'Aktif';
                    $statusClass = 'success';
                } elseif (in_array($teacherStatus, ['leave', 'cuti'])) {
                    $statusLabel = 'Cuti';
                    $statusClass = 'secondary';
                } else {
                    $statusLabel = $teacher->status ?? 'Tidak diketahui';
                    $statusClass = 'warning';
                }

                // program guru
                $programNames = collect();

                foreach ($teacher->classes ?? collect() as $class) {
                    $program = optional($class->programPackage)->name;

                    if ($program) {
                        $programNames->push($program);
                    }
                }

                $programNames = $programNames->unique()->values();
            @endphp

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">

                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="fw-bold mb-1">{{ $teacher->name }}</h5>

                                <small class="text-muted">
                                    @if($programNames->count())
                                        {{ $programNames->join(', ') }}
                                    @else
                                        Belum ada program
                                    @endif
                                </small>
                            </div>

                            <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <hr>

                        <div class="row text-center">
                            <div class="col-4">
                                <h4 class="fw-bold text-primary">{{ number_format($teacherHour, 1, ',', '.') }}</h4>
                                <small>Jam</small>
                            </div>

                            <div class="col-4">
                                <h4 class="fw-bold text-success">{{ $sessionCount }}</h4>
                                <small>Sesi</small>
                            </div>

                            <div class="col-4">
                                <h4 class="fw-bold @if($attendancePercentage >= 90) text-success @elseif($attendancePercentage >= 75) text-warning @else text-danger @endif">
                                    {{ $attendancePercentage }}%
                                </h4>
                                <small>Hadir</small>
                            </div>
                        </div>

                        <hr>

                        <p class="mb-2">Jurnal Mengajar</p>

                        @php
                            $journalCompleted = $teacherJournals->whereIn('status', ['Reviewed', 'reviewed'])->count();
                            $journalPercentage = $sessionCount > 0 ? round(($journalCompleted / $sessionCount) * 100) : 0;
                        @endphp

                        <div class="progress mb-2">
                            <div class="progress-bar @if($journalPercentage >= 80) bg-success @elseif($journalPercentage >= 50) bg-warning @else bg-danger @endif"
                                 style="width: {{ $journalPercentage }}%">
                            </div>
                        </div>

                        @if($journalPercentage >= 80)
                            <span class="badge bg-success">Jurnal Lengkap</span>
                        @elseif($journalPercentage >= 50)
                            <span class="badge bg-warning text-dark">Sebagian Direview</span>
                        @else
                            <span class="badge bg-danger">Perlu Review</span>
                        @endif

                    </div>
                </div>
            </div>

        @empty

            <div class="col-12">
                <div class="alert alert-info">Belum terdapat data guru.</div>
            </div>

        @endforelse

    </div>

    {{-- ===== FILTER ===== --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">Filter Monitoring Guru</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('kurikulum.monitoring') }}">
                <div class="row g-3">

                    {{-- NAMA GURU --}}
                    <div class="col-lg-3">
                        <label class="form-label">Nama Guru</label>

                        <select name="teacher_id" class="form-select">
                            <option value="">Semua Guru</option>

                            @foreach(\App\Models\Teacher::orderBy('name')->get() as $teacherOption)
                                <option value="{{ $teacherOption->id }}"
                                    {{ request('teacher_id') == $teacherOption->id ? 'selected' : '' }}>
                                    {{ $teacherOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div class="col-lg-2">
                        <label class="form-label">Status Guru</label>

                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>

                            @php
                                $statuses = \App\Models\Teacher::query()
                                    ->whereNotNull('status')
                                    ->distinct()
                                    ->pluck('status');
                            @endphp

                            @foreach($statuses as $teacherStatusOption)
                                <option value="{{ $teacherStatusOption }}"
                                    {{ request('status') == $teacherStatusOption ? 'selected' : '' }}>
                                    {{ $teacherStatusOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- TANGGAL --}}
                    <div class="col-lg-2">
                        <label class="form-label">Tanggal Monitoring</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-lg-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>
                            Filter
                        </button>
                    </div>

                    {{-- RESET --}}
                    <div class="col-lg-2 d-flex align-items-end">
                        <a href="{{ route('kurikulum.monitoring') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-sync-alt me-1"></i>
                            Reset
                        </a>
                    </div>

                </div>
            </form>
        </div>

    </div>

    {{-- ===== QUICK SUMMARY ===== --}}
    <div class="row mb-4">

        {{-- GURU HADIR --}}
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Guru Hadir</small>
                    <h2 class="fw-bold text-success mt-2 mb-0">{{ $guruHadirHariIni }}</h2>
                    <small class="text-muted">{{ $date }}</small>
                </div>
            </div>
        </div>

        {{-- TIDAK HADIR --}}
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Tidak Hadir</small>
                    <h2 class="fw-bold text-danger mt-2 mb-0">{{ $totalTidakHadirHariIni }}</h2>
                    <small class="text-muted">Absent, Sick, Permission</small>
                </div>
            </div>
        </div>

        {{-- TOTAL ABSENSI --}}
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Total Absensi</small>
                    <h2 class="fw-bold text-primary mt-2 mb-0">{{ $totalAttendanceToday }}</h2>
                    <small class="text-muted">Data absensi hari ini</small>
                </div>
            </div>
        </div>

        {{-- PROGRESS REPORT --}}
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Progress Report Pending</small>
                    <h2 class="fw-bold text-warning mt-2 mb-0">{{ $progressReportPending }}</h2>
                    <small class="text-muted">Perlu ditindaklanjuti</small>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== TABEL MONITORING ===== --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Daftar Monitoring Guru</h5>
                    <small class="text-muted">Data guru dari database</small>
                </div>

                <span class="badge bg-primary">{{ $teachers->count() }} Guru</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Guru</th>
                        <th>Program</th>
                        <th>Kelas</th>
                        <th>Jam</th>
                        <th>Kehadiran</th>
                        <th>Jurnal</th>
                        <th>Progress Report</th>
                        <th>Status</th>
                        <th width="170">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($teachers as $index => $teacher)

                        @php
                            // jam guru
                            $teacherHour = 0;

                            foreach ($teacher->classes ?? collect() as $class) {
                                foreach ($class->schedules ?? collect() as $schedule) {
                                    if ($schedule->start_time && $schedule->end_time) {
                                        $start = strtotime($schedule->start_time);
                                        $end = strtotime($schedule->end_time);

                                        if ($end > $start) {
                                            $teacherHour += ($end - $start) / 3600;
                                        }
                                    }
                                }
                            }

                            $teacherHour = round($teacherHour, 1);

                            // journal
                            $journals = $teacher->teachingJournals ?? collect();
                            $journalCount = $journals->count();

                            // attendance
                            $attendanceRecords = collect();

                            foreach ($journals as $journal) {
                                foreach ($journal->attendances ?? collect() as $attendance) {
                                    $attendanceRecords->push($attendance);
                                }
                            }

                            $attendanceTotal = $attendanceRecords->count();
                            $attendancePresent = $attendanceRecords->where('status', 'Present')->count();
                            $attendancePercentage = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100) : 0;

                            // progress report
                            $reports = $teacher->progressReports ?? collect();

                            $pendingReports = $reports->whereIn('status', ['pending', 'Pending', 'draft', 'Draft'])->count();
                            $approvedReports = $reports->whereIn('status', ['approved', 'Approved', 'completed', 'Completed'])->count();

                            // program
                            $programs = collect();

                            foreach ($teacher->classes ?? collect() as $class) {
                                $program = optional($class->programPackage)->name;

                                if ($program) {
                                    $programs->push($program);
                                }
                            }

                            $programs = $programs->unique()->values();

                            // kelas
                            $classes = collect();

                            foreach ($teacher->classes ?? collect() as $class) {
                                $className = $class->name ?? $class->class_name ?? null;

                                if ($className) {
                                    $classes->push($className);
                                }
                            }

                            $classes = $classes->unique()->values();

                            // status
                            $teacherStatus = $teacher->status ?? 'Aktif';
                            $statusLower = strtolower($teacherStatus);

                            if (in_array($statusLower, ['active', 'aktif'])) {
                                $statusClass = 'primary';
                            } elseif (in_array($statusLower, ['leave', 'cuti'])) {
                                $statusClass = 'secondary';
                            } else {
                                $statusClass = 'warning';
                            }
                        @endphp

                        <tr>
                            {{-- NO --}}
                            <td>{{ $index + 1 }}</td>

                            {{-- GURU --}}
                            <td>
                                <strong>{{ $teacher->name }}</strong>
                                <br>
                                <small class="text-muted">ID Guru: {{ $teacher->id }}</small>
                            </td>

                            {{-- PROGRAM --}}
                            <td>
                                @if($programs->count())
                                    {{ $programs->join(', ') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- KELAS --}}
                            <td>
                                @if($classes->count())
                                    {{ $classes->join(', ') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- JAM --}}
                            <td>
                                <span class="badge bg-info">{{ number_format($teacherHour, 1, ',', '.') }} Jam</span>
                            </td>

                            {{-- KEHADIRAN --}}
                            <td>
                                @if($attendancePercentage >= 90)
                                    <span class="badge bg-success">{{ $attendancePercentage }}%</span>
                                @elseif($attendancePercentage >= 75)
                                    <span class="badge bg-warning text-dark">{{ $attendancePercentage }}%</span>
                                @else
                                    <span class="badge bg-danger">{{ $attendancePercentage }}%</span>
                                @endif
                            </td>

                            {{-- JURNAL --}}
                            <td>
                                @if($journalCount > 0)
                                    <span class="badge bg-success">{{ $journalCount }} Sesi</span>
                                @else
                                    <span class="badge bg-danger">Belum Ada</span>
                                @endif
                            </td>

                            {{-- PROGRESS REPORT --}}
                            <td>
                                @if($pendingReports > 0)
                                    <span class="badge bg-warning text-dark">{{ $pendingReports }} Pending</span>
                                @elseif($approvedReports > 0)
                                    <span class="badge bg-success">{{ $approvedReports }} Selesai</span>
                                @else
                                    <span class="badge bg-secondary">Belum Ada</span>
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td>
                                <span class="badge bg-{{ $statusClass }}">{{ $teacherStatus }}</span>
                            </td>

                            {{-- AKSI --}}
                            <td>
                                <a href="#" class="btn btn-info btn-sm" title="Detail guru">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <a href="#" class="btn btn-success btn-sm" title="Jadwal guru">
                                    <i class="fas fa-calendar"></i>
                                </a>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fas fa-user-slash fa-2x text-muted mb-3"></i>
                                <br>
                                <strong>Belum ada data guru</strong>
                                <br>
                                <small class="text-muted">Data guru belum ditemukan berdasarkan filter.</small>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        {{-- FOOTER --}}
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Menampilkan {{ $teachers->count() }} data guru</small>
            </div>
        </div>

    </div>

</div>

@push('styles')

<style>

/* tabel daftar monitoring guru */
.table tbody td { vertical-align: middle; }
.table td { white-space: nowrap; }
.table-hover tbody tr:hover { background: #f7fbff; }

/* =====================================================
   JADWAL MINGGUAN (sama dengan menu Schedule guru)
===================================================== */
.sch-wrap { overflow-x: visible; }
.sch-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
.sch-table th, .sch-table td {
    border: 1px solid rgba(128, 128, 128, .2);
    padding: 6px;
    vertical-align: top;
    white-space: normal;          /* teks kartu boleh turun baris, tabel tidak melebar */
    word-break: break-word;
}
.sch-table th { font-size: 11px; text-transform: uppercase; text-align: center; }
.sch-table th.today { background: #fff3c4; color: #000; }
.sch-table th.sch-time, .sch-table td.sch-time { width: 64px; }
.sch-time { font-weight: 700; font-size: 13px; text-align: center; }

.sch-card {
    border-radius: 8px;
    padding: 6px 9px;
    margin-bottom: 6px;
    font-size: 12px;
    border: 2px solid transparent;
    color: #222;
}
.sch-card strong { display: block; font-size: 12.5px; }
.sch-card small  { opacity: .8; font-size: 11.5px; }
.sch-card.orange { background: #fdf0cf; }
.sch-card.blue   { background: #dfeafb; }
.sch-card.purple { background: #e8e0fb; }
.sch-card.red    { background: #fbe3e3; }
.sch-card.green  { background: #dcf3e2; }

.sch-legend { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 12px; }
.sch-legend span { display: inline-flex; align-items: center; }
.sch-legend i { display: inline-block; width: 11px; height: 11px; border-radius: 3px; margin-right: 6px; border: 1px solid rgba(0,0,0,.12); }
.sch-legend i.orange { background: #fdf0cf; }
.sch-legend i.blue   { background: #dfeafb; }
.sch-legend i.purple { background: #e8e0fb; }
.sch-legend i.red    { background: #fbe3e3; }
.sch-legend i.green  { background: #dcf3e2; }

/* layar kecil: baru boleh digeser */
@media (max-width: 991.98px) {
    .sch-wrap { overflow-x: auto; }
    .sch-table { min-width: 860px; }
}

/* PRINT */
@media print {
    .btn, form, .sidebar, nav, #schTeacher { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}

</style>

@endpush

@push('scripts')
<script>
// Filter kartu jadwal per guru
document.getElementById('schTeacher')?.addEventListener('change', function (e) {
    const id = e.target.value;
    document.querySelectorAll('.sch-card').forEach(function (c) {
        c.style.display = (!id || c.dataset.teacher === id) ? '' : 'none';
    });
});
</script>
@endpush

@endsection