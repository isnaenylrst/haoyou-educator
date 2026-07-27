@extends('layouts.guru')

@section('content')

<style>

.schedule-table{
    table-layout: fixed;
    min-width:1200px;
}

.schedule-table th,
.schedule-table td{
    border:1px solid #e9ecef;
    vertical-align:top;
}

.schedule-table thead th{
    text-align:center;
    font-size:13px;
    font-weight:700;
    color:#666;
    background:#fff;
}

.time-col{
    width:90px;
    font-weight:600;
    color:#555;
    background:#fff;
}

.schedule-cell{
    height:78px;
    padding:6px;
}

.schedule-item{

    border-radius:8px;
    padding:8px 10px;
    font-size:13px;
    line-height:1.3;
    border-left:4px solid transparent;

}

.schedule-item b{
    display:block;
    font-size:14px;
}

.bg-yellow{

    background:#FFF4D8;
    border-left-color:#F2A12E;

}

.bg-blue{

    background:#E7F1FD;
    border-left-color:#3B82F6;

}

.bg-purple{

    background:#EFE6FB;
    border-left-color:#7C4DFF;

}

.bg-red{

    background:#FCE9E9;
    border-left-color:#D9534F;

}

.legend-box{

    width:10px;
    height:10px;
    display:inline-block;
    border-radius:2px;
    margin-right:5px;

}

.student-tag{

    display:inline-block;
    padding:6px 12px;
    border-radius:8px;
    margin:4px 4px 0 0;
    font-size:14px;

}

.present{

    border:1px solid #28a745;
    color:#28a745;
    background:#F4FFF6;

}

.absent{

    border:1px solid #dc3545;
    color:#dc3545;
    background:#FFF7F7;

}

.late{

    border:1px solid #F0AD4E;
    color:#F0AD4E;
    background:#FFF8EE;

}

.normal{

    border:1px solid #ddd;

}

</style>

<div class="container-fluid">

<div class="mb-4">

<h2 class="fw-bold">
Schedule
</h2>

<p class="text-muted">
Kalender jadwal mengajar — menampilkan jadwal semua guru untuk mencari pengganti
</p>

</div>

<div class="card shadow-sm border-0">

<div class="table-responsive">

<table class="table schedule-table mb-0">

<thead>

<tr>

<th class="time-col">
TIME
</th>

<th>
MON 9
</th>

<th>
TUE 19
</th>

<th>
WED 20
</th>

<th>
THU 21
</th>

<th>
FRI 22
</th>

</tr>

</thead>

<tbody>

<tr>

<td class="time-col">
13:00
</td>

<td class="schedule-cell">

<div class="schedule-item bg-yellow">

<b>Daily Activity — Maochong</b>

Ratna • 7 siswa • Offline

</div>

</td>

<td class="schedule-cell">

</td>

<td class="schedule-cell">

<div class="schedule-item bg-blue">

<b>HSK Prep — Hudie</b>

Dinda • 6 siswa • Online

</div>

</td>

<td class="schedule-cell">

</td>

<td class="schedule-cell">

<div class="schedule-item bg-blue">

<b>Daily Activity — Jianer</b>

Dinda • 8 siswa • Offline

</div>

</td>

</tr>
<tr>

    <td class="time-col">
        15:00
    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-red">

            <b>Business — Feixiang</b>

            Ratna • 5 siswa • Offline

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-yellow">

            <b>Daily Activity — Maochong</b>

            Ratna • 6 siswa • Offline

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-purple">

            <b>Traveling — Feixiang</b>

            Dinda • 5 siswa • Online

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-blue">

            <b>Private — Jianer</b>

            Ratna • 2 siswa • Offline

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-purple">

            <b>HSK Prep — Hudie</b>

            Ratna • 6 siswa • Online

        </div>

    </td>

</tr>


<tr>

    <td class="time-col">
        16:30
    </td>

    <td class="schedule-cell">

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-purple">

            <b>HSK Prep — Jianer B</b>

            Dinda • 8 siswa • Online

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-yellow">

            <b>Daily Activity — Jianer B</b>

            Dinda • 8 siswa • Offline

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-blue">

            <b>Native Speaker — Hudie</b>

            Ratna • 5 siswa • Online

        </div>

    </td>

    <td class="schedule-cell">

        <div class="schedule-item bg-red">

            <b>Business — Feixiang B</b>

            Ratna • 4 siswa • Offline

        </div>

    </td>

</tr>

</tbody>

</table>

</div>

</div>
{{-- ========================= --}}
{{-- Legend --}}
{{-- ========================= --}}

<div class="d-flex flex-wrap align-items-center gap-4 mt-3 mb-4">

    <div>
        <span class="legend-box" style="background:#F2A12E"></span>
        Maochong (3–6)
    </div>

    <div>
        <span class="legend-box" style="background:#3B82F6"></span>
        Jianer (7–9)
    </div>

    <div>
        <span class="legend-box" style="background:#7C4DFF"></span>
        Hudie (10–15)
    </div>

    <div>
        <span class="legend-box" style="background:#D9534F"></span>
        Feixiang (15+)
    </div>

    <div>
        <span class="legend-box" style="background:#28A745"></span>
        Private / NSP
    </div>

</div>


{{-- ========================= --}}
{{-- UP NEXT --}}
{{-- ========================= --}}

<div class="card shadow-sm border-0">

    <div class="card-body">

        <div
            class="text-uppercase fw-bold mb-3"
            style="font-size:13px;color:#D48A00;"
        >
            UP NEXT
        </div>

        <h3 class="fw-bold mb-3">

            Daily Activity — Maochong A

        </h3>

        <div class="text-muted mb-3">

            Monday, June 18 ·
            15:00 – 16:30 ·
            Ratna

        </div>


        {{-- Badge siswa --}}

        <div class="mb-4">

            <span class="student-tag present">
                ✓ Zahra A.
            </span>

            <span class="student-tag present">
                ✓ Bima R.
            </span>

            <span class="student-tag present">
                ✓ Nadia K.
            </span>

            <span class="student-tag absent">
                ✕ Rafi H.
            </span>

            <span class="student-tag present">
                ✓ Kirana P.
            </span>

            <span class="student-tag late">
                L Dimas S.
            </span>

            <span class="student-tag present">
                ✓ Laila M.
            </span>

            <span class="student-tag normal">
                Arya F.
            </span>

        </div>

        <button class="btn btn-warning px-4 fw-bold">

            Start Attendance

        </button>

    </div>

</div>

</div>

@endsection