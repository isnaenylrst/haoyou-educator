@extends('layouts.kurikulum')

@section('title', 'Monitoring Guru & Kelas')

@section('content')

<div class="container-fluid">

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Monitoring Guru & Kelas
            </h2>

            <p class="text-muted mb-0">
                Monitoring jadwal, sesi mengajar, kehadiran, dan jurnal guru
            </p>
        </div>

        <div>
            <button type="button"
                    class="btn btn-primary"
                    onclick="window.print()">

                <i class="fas fa-download me-2"></i>
                Export Laporan

            </button>
        </div>

    </div>


    {{-- =========================================================
        STATISTIK UTAMA
    ========================================================== --}}

    <div class="row mb-4">

        {{-- TOTAL GURU --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">
                        Total Guru
                    </small>

                    <h2 class="fw-bold mt-2 mb-0">
                        {{ $totalTeacher }}
                    </h2>

                    <small class="text-muted">
                        Guru terdaftar
                    </small>

                </div>

            </div>

        </div>


        {{-- TOTAL JAM --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">
                        Total Jam Mengajar
                    </small>

                    <h2 class="fw-bold mt-2 mb-0">
                        {{ number_format($totalHour, 1, ',', '.') }} Jam
                    </h2>

                    <small class="text-muted">
                        Berdasarkan jadwal kelas
                    </small>

                </div>

            </div>

        </div>


        {{-- TOTAL SESI --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">
                        Total Sesi Mengajar
                    </small>

                    <h2 class="fw-bold mt-2 mb-0">
                        {{ $totalSession }}
                    </h2>

                    <small class="text-muted">
                        Berdasarkan jurnal mengajar
                    </small>

                </div>

            </div>

        </div>


        {{-- RATA-RATA --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-uppercase text-muted fw-bold">
                        Rata-rata Jam / Guru
                    </small>

                    <h2 class="fw-bold mt-2 mb-0 text-warning">
                        {{ number_format($averageHour, 1, ',', '.') }} Jam
                    </h2>

                    <small class="text-muted">
                        Rata-rata dari seluruh guru
                    </small>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        JADWAL MENGAJAR MINGGUAN
    ========================================================== --}}

    <div class="card shadow-sm border-0 mb-5">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-0 fw-bold">
                        Jadwal Mengajar Mingguan
                    </h5>

                    <small class="text-muted">
                        Jadwal berdasarkan data class schedules
                    </small>

                </div>

                <div>

                    <span class="badge bg-primary">
                        {{ $weeklySchedules->count() }} Jadwal
                    </span>

                </div>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-bordered align-middle mb-0 weekly-table">

                <thead class="table-light">

                <tr>

                    <th width="110">
                        Senin
                    </th>

                    <th width="110">
                        Selasa
                    </th>

                    <th width="110">
                        Rabu
                    </th>

                    <th width="110">
                        Kamis
                    </th>

                    <th width="110">
                        Jumat
                    </th>

                </tr>

                </thead>

                <tbody>

                <tr>

                    @php
                        $days = [
                            'Senin' => ['Senin', 'Monday', 'monday'],
                            'Selasa' => ['Selasa', 'Tuesday', 'tuesday'],
                            'Rabu' => ['Rabu', 'Wednesday', 'wednesday'],
                            'Kamis' => ['Kamis', 'Thursday', 'thursday'],
                            'Jumat' => ['Jumat', 'Friday', 'friday'],
                        ];
                    @endphp


                    @foreach($days as $dayName => $dayValues)

                        <td class="schedule-column">

                            @php
                                $daySchedules = $weeklySchedules->filter(function ($schedule) use ($dayValues) {

                                    return in_array(
                                        $schedule->day,
                                        $dayValues
                                    );

                                });
                            @endphp


                            @forelse($daySchedules as $schedule)

                                @php

                                    $teacherName = optional(
                                        optional($schedule->class)->teacher
                                    )->name ?? '-';

                                    $programName = optional(
                                        optional($schedule->class)->programPackage
                                    )->name ?? 'Program';

                                    $className =
                                        optional($schedule->class)->name
                                        ?? optional($schedule->class)->class_name
                                        ?? 'Kelas';

                                @endphp


                                <div class="schedule-item mb-2">

                                    <div class="schedule-time">

                                        {{ $schedule->start_time
                                            ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i')
                                            : '-' }}

                                        -

                                        {{ $schedule->end_time
                                            ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i')
                                            : '-' }}

                                    </div>


                                    <strong>
                                        {{ $programName }}
                                    </strong>


                                    <div class="small">
                                        {{ $className }}
                                    </div>


                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-user me-1"></i>
                                        {{ $teacherName }}
                                    </div>


                                    @if($schedule->room)

                                        <div class="small text-muted">
                                            <i class="fas fa-door-open me-1"></i>
                                            {{ $schedule->room }}
                                        </div>

                                    @endif

                                </div>

                            @empty

                                <div class="text-muted small text-center py-3">
                                    Tidak ada jadwal
                                </div>

                            @endforelse

                        </td>

                    @endforeach

                </tr>

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
        RINGKASAN GURU
    ========================================================== --}}

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h5 class="fw-bold mb-1">
                Ringkasan Guru
            </h5>

            <small class="text-muted">
                Informasi berdasarkan data guru dan jurnal mengajar
            </small>

        </div>

    </div>


    <div class="row mb-5">

        @forelse($teachers->take(6) as $teacher)

            @php

                /*
                |--------------------------------------------------------------------------
                | JUMLAH JURNAL GURU
                |--------------------------------------------------------------------------
                */

                $teacherJournals = $teacher->teachingJournals ?? collect();

                $sessionCount = $teacherJournals->count();


                /*
                |--------------------------------------------------------------------------
                | TOTAL JAM GURU
                |--------------------------------------------------------------------------
                */

                $teacherHour = 0;

                $teacherSchedules = collect();

                foreach ($teacher->classes ?? collect() as $class) {

                    foreach ($class->schedules ?? collect() as $schedule) {

                        $teacherSchedules->push($schedule);

                        if (
                            $schedule->start_time &&
                            $schedule->end_time
                        ) {

                            $start = strtotime($schedule->start_time);
                            $end = strtotime($schedule->end_time);

                            if ($end > $start) {

                                $teacherHour +=
                                    ($end - $start) / 3600;

                            }

                        }

                    }

                }

                $teacherHour = round($teacherHour, 1);


                /*
                |--------------------------------------------------------------------------
                | KEHADIRAN
                |--------------------------------------------------------------------------
                */

                $attendances = collect();

                foreach ($teacherJournals as $journal) {

                    foreach ($journal->attendances ?? collect() as $attendance) {

                        $attendances->push($attendance);

                    }

                }


                $attendanceTotal = $attendances->count();

                $attendancePresent = $attendances
                    ->where('status', 'Present')
                    ->count();

                $attendancePercentage =
                    $attendanceTotal > 0
                        ? round(
                            ($attendancePresent / $attendanceTotal) * 100
                        )
                        : 0;


                /*
                |--------------------------------------------------------------------------
                | STATUS GURU
                |--------------------------------------------------------------------------
                */

                $teacherStatus = strtolower(
                    $teacher->status ?? 'active'
                );


                if (
                    in_array(
                        $teacherStatus,
                        ['active', 'aktif']
                    )
                ) {

                    $statusLabel = 'Aktif';
                    $statusClass = 'success';

                } elseif (
                    in_array(
                        $teacherStatus,
                        ['leave', 'cuti']
                    )
                ) {

                    $statusLabel = 'Cuti';
                    $statusClass = 'secondary';

                } else {

                    $statusLabel = $teacher->status ?? 'Tidak diketahui';
                    $statusClass = 'warning';

                }


                /*
                |--------------------------------------------------------------------------
                | PROGRAM GURU
                |--------------------------------------------------------------------------
                */

                $programNames = collect();

                foreach ($teacher->classes ?? collect() as $class) {

                    $program = optional(
                        $class->programPackage
                    )->name;

                    if ($program) {
                        $programNames->push($program);
                    }

                }

                $programNames = $programNames
                    ->unique()
                    ->values();

            @endphp


            <div class="col-lg-4 col-md-6 mb-3">

                <div class="card shadow-sm border-0 h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <h5 class="fw-bold mb-1">

                                    {{ $teacher->name }}

                                </h5>


                                <small class="text-muted">

                                    @if($programNames->count())

                                        {{ $programNames->join(', ') }}

                                    @else

                                        Belum ada program

                                    @endif

                                </small>

                            </div>


                            <span class="badge bg-{{ $statusClass }}">

                                {{ $statusLabel }}

                            </span>

                        </div>


                        <hr>


                        <div class="row text-center">

                            <div class="col-4">

                                <h4 class="fw-bold text-primary">

                                    {{ number_format($teacherHour, 1, ',', '.') }}

                                </h4>

                                <small>
                                    Jam
                                </small>

                            </div>


                            <div class="col-4">

                                <h4 class="fw-bold text-success">

                                    {{ $sessionCount }}

                                </h4>

                                <small>
                                    Sesi
                                </small>

                            </div>


                            <div class="col-4">

                                <h4 class="fw-bold
                                    @if($attendancePercentage >= 90)
                                        text-success
                                    @elseif($attendancePercentage >= 75)
                                        text-warning
                                    @else
                                        text-danger
                                    @endif
                                ">

                                    {{ $attendancePercentage }}%

                                </h4>

                                <small>
                                    Hadir
                                </small>

                            </div>

                        </div>


                        <hr>


                        <p class="mb-2">
                            Jurnal Mengajar
                        </p>


                        @php

                            $journalCompleted =
                                $teacherJournals
                                    ->whereIn(
                                        'status',
                                        ['Reviewed', 'reviewed']
                                    )
                                    ->count();

                            $journalPercentage =
                                $sessionCount > 0
                                    ? round(
                                        ($journalCompleted / $sessionCount) * 100
                                    )
                                    : 0;

                        @endphp


                        <div class="progress mb-2">

                            <div
                                class="progress-bar
                                @if($journalPercentage >= 80)
                                    bg-success
                                @elseif($journalPercentage >= 50)
                                    bg-warning
                                @else
                                    bg-danger
                                @endif"
                                style="width: {{ $journalPercentage }}%"
                            >
                            </div>

                        </div>


                        @if($journalPercentage >= 80)

                            <span class="badge bg-success">
                                Jurnal Lengkap
                            </span>

                        @elseif($journalPercentage >= 50)

                            <span class="badge bg-warning text-dark">
                                Sebagian Direview
                            </span>

                        @else

                            <span class="badge bg-danger">
                                Perlu Review
                            </span>

                        @endif

                    </div>

                </div>

            </div>


        @empty

            <div class="col-12">

                <div class="alert alert-info">

                    Belum terdapat data guru.

                </div>

            </div>

        @endforelse

    </div>


    {{-- =========================================================
        FILTER
    ========================================================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0 fw-bold">
                Filter Monitoring Guru
            </h5>

        </div>


        <div class="card-body">

            <form method="GET"
                  action="{{ route('kurikulum.monitoring') }}">

                <div class="row g-3">


                    {{-- NAMA GURU --}}

                    <div class="col-lg-3">

                        <label class="form-label">
                            Nama Guru
                        </label>

                        <select
                            name="teacher_id"
                            class="form-select"
                        >

                            <option value="">
                                Semua Guru
                            </option>


                            @foreach(
                                \App\Models\Teacher::orderBy('name')->get()
                                as $teacherOption
                            )

                                <option
                                    value="{{ $teacherOption->id }}"
                                    {{ request('teacher_id') == $teacherOption->id ? 'selected' : '' }}
                                >

                                    {{ $teacherOption->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- STATUS --}}

                    <div class="col-lg-2">

                        <label class="form-label">
                            Status Guru
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">
                                Semua Status
                            </option>


                            @php

                                $statuses =
                                    \App\Models\Teacher::query()
                                        ->whereNotNull('status')
                                        ->distinct()
                                        ->pluck('status');

                            @endphp


                            @foreach($statuses as $teacherStatusOption)

                                <option
                                    value="{{ $teacherStatusOption }}"
                                    {{ request('status') == $teacherStatusOption ? 'selected' : '' }}
                                >

                                    {{ $teacherStatusOption }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- TANGGAL --}}

                    <div class="col-lg-2">

                        <label class="form-label">
                            Tanggal Monitoring
                        </label>

                        <input
                            type="date"
                            name="date"
                            class="form-control"
                            value="{{ $date }}"
                        >

                    </div>


                    {{-- BUTTON --}}

                    <div class="col-lg-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >

                            <i class="fas fa-filter me-1"></i>

                            Filter

                        </button>

                    </div>


                    {{-- RESET --}}

                    <div class="col-lg-2 d-flex align-items-end">

                        <a
                            href="{{ route('kurikulum.monitoring') }}"
                            class="btn btn-outline-secondary w-100"
                        >

                            <i class="fas fa-sync-alt me-1"></i>

                            Reset

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- =========================================================
        QUICK SUMMARY
    ========================================================== --}}

    <div class="row mb-4">

        {{-- GURU HADIR --}}

        <div class="col-lg-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Guru Hadir
                    </small>

                    <h2 class="fw-bold text-success mt-2 mb-0">

                        {{ $guruHadirHariIni }}

                    </h2>

                    <small class="text-muted">
                        {{ $date }}
                    </small>

                </div>

            </div>

        </div>


        {{-- TIDAK HADIR --}}

        <div class="col-lg-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Tidak Hadir
                    </small>

                    <h2 class="fw-bold text-danger mt-2 mb-0">

                        {{ $totalTidakHadirHariIni }}

                    </h2>

                    <small class="text-muted">
                        Absent, Sick, Permission
                    </small>

                </div>

            </div>

        </div>


        {{-- TOTAL ABSENSI --}}

        <div class="col-lg-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Total Absensi
                    </small>

                    <h2 class="fw-bold text-primary mt-2 mb-0">

                        {{ $totalAttendanceToday }}

                    </h2>

                    <small class="text-muted">
                        Data absensi hari ini
                    </small>

                </div>

            </div>

        </div>


        {{-- PROGRESS REPORT --}}

        <div class="col-lg-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Progress Report Pending
                    </small>

                    <h2 class="fw-bold text-warning mt-2 mb-0">

                        {{ $progressReportPending }}

                    </h2>

                    <small class="text-muted">
                        Perlu ditindaklanjuti
                    </small>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        TABEL MONITORING
    ========================================================== --}}

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-0 fw-bold">
                        Daftar Monitoring Guru
                    </h5>

                    <small class="text-muted">
                        Data guru dari database
                    </small>

                </div>


                <span class="badge bg-primary">

                    {{ $teachers->count() }} Guru

                </span>

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

                    <th width="170">
                        Aksi
                    </th>

                </tr>

                </thead>


                <tbody>


                @forelse($teachers as $index => $teacher)

                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | JAM GURU
                        |--------------------------------------------------------------------------
                        */

                        $teacherHour = 0;

                        foreach ($teacher->classes ?? collect() as $class) {

                            foreach ($class->schedules ?? collect() as $schedule) {

                                if (
                                    $schedule->start_time &&
                                    $schedule->end_time
                                ) {

                                    $start = strtotime($schedule->start_time);
                                    $end = strtotime($schedule->end_time);

                                    if ($end > $start) {

                                        $teacherHour +=
                                            ($end - $start) / 3600;

                                    }

                                }

                            }

                        }

                        $teacherHour = round($teacherHour, 1);


                        /*
                        |--------------------------------------------------------------------------
                        | JOURNAL
                        |--------------------------------------------------------------------------
                        */

                        $journals =
                            $teacher->teachingJournals
                            ?? collect();

                        $journalCount =
                            $journals->count();


                        /*
                        |--------------------------------------------------------------------------
                        | ATTENDANCE
                        |--------------------------------------------------------------------------
                        */

                        $attendanceRecords = collect();

                        foreach ($journals as $journal) {

                            foreach (
                                $journal->attendances ?? collect()
                                as $attendance
                            ) {

                                $attendanceRecords->push(
                                    $attendance
                                );

                            }

                        }


                        $attendanceTotal =
                            $attendanceRecords->count();

                        $attendancePresent =
                            $attendanceRecords
                                ->where(
                                    'status',
                                    'Present'
                                )
                                ->count();


                        $attendancePercentage =
                            $attendanceTotal > 0
                                ? round(
                                    (
                                        $attendancePresent /
                                        $attendanceTotal
                                    ) * 100
                                )
                                : 0;


                        /*
                        |--------------------------------------------------------------------------
                        | PROGRESS REPORT
                        |--------------------------------------------------------------------------
                        */

                        $reports =
                            $teacher->progressReports
                            ?? collect();

                        $pendingReports =
                            $reports->whereIn(
                                'status',
                                [
                                    'pending',
                                    'Pending',
                                    'draft',
                                    'Draft'
                                ]
                            )->count();


                        $approvedReports =
                            $reports->whereIn(
                                'status',
                                [
                                    'approved',
                                    'Approved',
                                    'completed',
                                    'Completed'
                                ]
                            )->count();


                        /*
                        |--------------------------------------------------------------------------
                        | PROGRAM
                        |--------------------------------------------------------------------------
                        */

                        $programs = collect();

                        foreach ($teacher->classes ?? collect() as $class) {

                            $program =
                                optional(
                                    $class->programPackage
                                )->name;

                            if ($program) {
                                $programs->push($program);
                            }

                        }

                        $programs =
                            $programs
                                ->unique()
                                ->values();


                        /*
                        |--------------------------------------------------------------------------
                        | KELAS
                        |--------------------------------------------------------------------------
                        */

                        $classes = collect();

                        foreach ($teacher->classes ?? collect() as $class) {

                            $className =
                                $class->name
                                ?? $class->class_name
                                ?? null;

                            if ($className) {
                                $classes->push($className);
                            }

                        }

                        $classes =
                            $classes
                                ->unique()
                                ->values();


                        /*
                        |--------------------------------------------------------------------------
                        | STATUS
                        |--------------------------------------------------------------------------
                        */

                        $teacherStatus =
                            $teacher->status
                            ?? 'Aktif';


                        $statusLower =
                            strtolower(
                                $teacherStatus
                            );


                        if (
                            in_array(
                                $statusLower,
                                [
                                    'active',
                                    'aktif'
                                ]
                            )
                        ) {

                            $statusClass = 'primary';

                        } elseif (
                            in_array(
                                $statusLower,
                                [
                                    'leave',
                                    'cuti'
                                ]
                            )
                        ) {

                            $statusClass = 'secondary';

                        } else {

                            $statusClass = 'warning';

                        }

                    @endphp


                    <tr>

                        {{-- NO --}}

                        <td>
                            {{ $index + 1 }}
                        </td>


                        {{-- GURU --}}

                        <td>

                            <strong>
                                {{ $teacher->name }}
                            </strong>

                            <br>

                            <small class="text-muted">
                                ID Guru:
                                {{ $teacher->id }}
                            </small>

                        </td>


                        {{-- PROGRAM --}}

                        <td>

                            @if($programs->count())

                                {{ $programs->join(', ') }}

                            @else

                                <span class="text-muted">
                                    -
                                </span>

                            @endif

                        </td>


                        {{-- KELAS --}}

                        <td>

                            @if($classes->count())

                                {{ $classes->join(', ') }}

                            @else

                                <span class="text-muted">
                                    -
                                </span>

                            @endif

                        </td>


                        {{-- JAM --}}

                        <td>

                            <span class="badge bg-info">

                                {{ number_format(
                                    $teacherHour,
                                    1,
                                    ',',
                                    '.'
                                ) }}
                                Jam

                            </span>

                        </td>


                        {{-- KEHADIRAN --}}

                        <td>

                            @if($attendancePercentage >= 90)

                                <span class="badge bg-success">
                                    {{ $attendancePercentage }}%
                                </span>

                            @elseif($attendancePercentage >= 75)

                                <span class="badge bg-warning text-dark">
                                    {{ $attendancePercentage }}%
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    {{ $attendancePercentage }}%
                                </span>

                            @endif

                        </td>


                        {{-- JURNAL --}}

                        <td>

                            @if($journalCount > 0)

                                <span class="badge bg-success">
                                    {{ $journalCount }} Sesi
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Belum Ada
                                </span>

                            @endif

                        </td>


                        {{-- PROGRESS REPORT --}}

                        <td>

                            @if($pendingReports > 0)

                                <span class="badge bg-warning text-dark">
                                    {{ $pendingReports }} Pending
                                </span>

                            @elseif($approvedReports > 0)

                                <span class="badge bg-success">
                                    {{ $approvedReports }} Selesai
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Belum Ada
                                </span>

                            @endif

                        </td>


                        {{-- STATUS --}}

                        <td>

                            <span class="badge bg-{{ $statusClass }}">

                                {{ $teacherStatus }}

                            </span>

                        </td>


                        {{-- AKSI --}}

                        <td>

                            <a
                                href="#"
                                class="btn btn-info btn-sm"
                                title="Detail guru"
                            >

                                <i class="fas fa-eye"></i>

                            </a>


                            <a
                                href="#"
                                class="btn btn-success btn-sm"
                                title="Jadwal guru"
                            >

                                <i class="fas fa-calendar"></i>

                            </a>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="10"
                            class="text-center py-5"
                        >

                            <i class="fas fa-user-slash fa-2x text-muted mb-3"></i>

                            <br>

                            <strong>
                                Belum ada data guru
                            </strong>

                            <br>

                            <small class="text-muted">
                                Data guru belum ditemukan berdasarkan filter.
                            </small>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- FOOTER --}}

        <div class="card-footer bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <small class="text-muted">

                    Menampilkan
                    {{ $teachers->count() }}
                    data guru

                </small>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    CSS
========================================================== --}}

@push('styles')

<style>

body {
    background: #f5f7fb;
}


/* CARD */

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}


.card-header {
    background: #fff;
    font-weight: 600;
}


/* TABLE */

.table thead th {

    background: #f8fafc;

    font-size: 13px;

    font-weight: 600;

    color: #555;

    text-align: center;

    vertical-align: middle;

}


.table tbody td {

    vertical-align: middle;

}


.table-hover tbody tr:hover {

    background: #f7fbff;

}


/* BADGE */

.badge {

    padding: 7px 11px;

    font-size: 11px;

    border-radius: 20px;

}


/* BUTTON */

.btn {

    border-radius: 8px;

}


.btn-sm {

    padding: 5px 10px;

}


/* PROGRESS */

.progress {

    height: 8px;

    border-radius: 20px;

}


/* SCHEDULE */

.weekly-table {

    min-width: 900px;

}


.schedule-column {

    vertical-align: top !important;

    min-width: 180px;

    height: 150px;

    background: #fff;

}


.schedule-item {

    padding: 10px;

    border-radius: 10px;

    background: #eef5ff;

    border-left: 4px solid #0d6efd;

    text-align: left;

    transition: .2s;

}


.schedule-item:hover {

    transform: translateY(-2px);

    box-shadow: 0 4px 12px rgba(0,0,0,.08);

}


.schedule-time {

    font-size: 11px;

    font-weight: 700;

    color: #0d6efd;

    margin-bottom: 4px;

}


/* TEXT */

.card-body h2 {

    font-weight: 700;

}


.card-body h4 {

    font-weight: 700;

}


/* TABLE RESPONSIVE */

.table-responsive {

    overflow-x: auto;

}


.table td {

    white-space: nowrap;

}


/* FILTER */

.form-control,
.form-select {

    border-radius: 8px;

}


.form-control:focus,
.form-select:focus {

    box-shadow: 0 0 0 .15rem rgba(13,110,253,.15);

}


/* PRINT */

@media print {

    body {

        background: white !important;

    }


    .btn,
    form,
    .sidebar,
    nav {

        display: none !important;

    }


    .card {

        box-shadow: none !important;

        border: 1px solid #ddd !important;

    }

}

</style>

@endpush

@endsection