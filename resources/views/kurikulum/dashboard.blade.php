@extends('layouts.kurikulum')

@section('title', 'Dashboard Kurikulum')

@section('content')

<div class="mb-4">

    <h2 class="fw-bold">Dashboard Kepala Kurikulum</h2>

    <p class="text-muted mb-0">
        Semua Pengajuan, PPT dan Jurnal Guru dapat dilihat di sini. Silakan tindaklanjuti sesuai kebutuhan.
    </p>

    @if($curriculum)

        <div class="alert alert-info mt-3">

            <strong>{{ $curriculum->name }}</strong>

            <br>

            Specialist :
            {{ $curriculum->specialist }}

        </div>

    @endif

</div>

<div class="row">

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold">{{ $guruAktif }}</h3>
                    <p class="mb-0">Guru Aktif</p>
                </div>

                <i class="fas fa-users fa-2x text-primary"></i>

            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-warning">{{$materiPending}}</h3>
                    <p class="mb-0">Materi/PPT/LP Menunggu</p>
                </div>

                <i class="fas fa-book fa-2x text-warning"></i>

            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-warning">{{ $jurnalPending }}</h3>
                    <p class="mb-0">Jurnal Belum Direview</p>
                </div>

                <i class="fas fa-file-alt fa-2x text-warning"></i>

            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-danger">{{ $cutiPending }}</h3>
                    <p class="mb-0">Cuti/Ganti Kelas Pending</p>
                </div>

                <i class="fas fa-calendar-times fa-2x text-danger"></i>

            </div>
        </div>
    </div>

</div>

<div class="card shadow-sm border-0 mt-4">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">

        <h5 class="fw-bold mb-0">
            Perlu Ditindaklanjuti
        </h5>

        <button class="btn btn-primary">
            Jadwalkan
        </button>

    </div>

    <div class="table-responsive">

        <table class="table table-hover mb-0">

          <tbody>

@if($pendingMaterials->count())

    @foreach($pendingMaterials as $item)

    <tr>

        <td>

            <b>{{ $item->teacher->name }}</b>

            <br>

            Upload PPT :
            {{ $item->ppt_title }}

            <br>

            <small class="text-muted">

                {{ $item->created_at->diffForHumans() }}

            </small>

        </td>

        <td width="120">

            <a href="#"
               class="btn btn-outline-primary btn-sm">

                Review

            </a>

        </td>

    </tr>

    @endforeach

@else

<tr>

    <td colspan="2" class="text-center text-muted">

        Tidak ada PPT yang perlu ditindaklanjuti.

    </td>

</tr>

@endif

</tbody>  

        </table>

    </div>

</div>

<div class="card shadow-sm border-0 mt-4">

    <div class="card-header bg-white">
        <h5 class="fw-bold mb-0">SOP & Materi</h5>
    </div>

    <div class="table-responsive">

        <table class="table table-hover mb-0">

           <tbody>

@if($latestMaterials->count())

@foreach($latestMaterials as $item)

<tr>

    <td>

        <strong>

            {{ $item->material->title }}

        </strong>

        <br>

        <small class="text-muted">

            Meeting {{ $item->material->meeting_number }}

        </small>

    </td>

    <td>

        {{ $item->teacher->name }}

    </td>

    <td>

        @if($item->status=='Pending')

            <span class="badge bg-warning">

                Pending

            </span>

        @elseif($item->status=='Approved')

            <span class="badge bg-success">

                Approved

            </span>

        @else

            <span class="badge bg-danger">

                Rejected

            </span>

        @endif

    </td>

</tr>

@endforeach

@else

<tr>

<td colspan="3" class="text-center text-muted">

Belum ada data materi.

</td>

</tr>

@endif

</tbody>

        </table>

    </div>

</div>

@endsection