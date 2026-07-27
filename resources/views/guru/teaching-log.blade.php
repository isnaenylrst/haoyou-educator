@extends('layouts.guru')

@section('content')

<div class="mb-4">
    <h2 class="fw-bold mb-1">Teaching Log</h2>

    <p class="text-muted">
        Riwayat seluruh aktivitas mengajar guru.
    </p>
</div>

{{-- Ringkasan --}}
<div class="row mb-4">

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Total Jam</small>

                <h2 class="fw-bold mt-2">
                    {{ $totalHours ?? 13 }} Jam
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Total Sesi</small>

                <h2 class="fw-bold mt-2">
                    {{ $totalSession ?? 9 }}
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Private</small>

                <h2 class="fw-bold text-warning mt-2">
                    {{ $private ?? 2 }}
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Ganti Guru</small>

                <h2 class="fw-bold text-primary mt-2">
                    {{ $replace ?? 1 }}
                </h2>
            </div>
        </div>
    </div>

</div>


<div class="card shadow-sm border-0">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="fw-bold mb-0">
                Riwayat Mengajar
            </h5>

            <div>

                <select class="form-select d-inline-block w-auto">

                    <option>Semua Tipe</option>
                    <option>Regular</option>
                    <option>Private</option>
                    <option>Pengganti</option>

                </select>

            </div>

        </div>

        <table class="table align-middle">

            <thead>

            <tr>

                <th>#</th>
                <th>Tanggal</th>
                <th>Kelas</th>
                <th>Topik</th>
                <th>Durasi</th>
                <th>Tipe</th>
                <th>Status</th>

            </tr>

            </thead>

            <tbody>

            @forelse($logs ?? [] as $log)

                <tr>

                    <td>{{ $loop->iteration }}</td>

                    <td>{{ $log->date }}</td>

                    <td>{{ $log->class }}</td>

                    <td>{{ $log->topic }}</td>

                    <td>{{ $log->duration }}</td>

                    <td>

                        @if($log->type=="Regular")
                            <span class="badge bg-primary">
                                Regular
                            </span>

                        @elseif($log->type=="Private")
                            <span class="badge bg-warning text-dark">
                                Private
                            </span>

                        @else
                            <span class="badge bg-info">
                                Pengganti
                            </span>

                        @endif

                    </td>

                    <td>

                        @if($log->status=="Selesai")
                            <span class="badge bg-success">
                                Selesai
                            </span>
                        @else
                            <span class="badge bg-danger">
                                Cancel
                            </span>
                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="7" class="text-center text-muted">

                        Belum ada riwayat mengajar.

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection