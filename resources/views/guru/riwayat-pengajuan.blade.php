@extends('layouts.guru')

@section('title', 'Riwayat Pengajuan')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h2 class="fw-bold mb-1">Riwayat Pengajuan Saya</h2>
        <p class="text-muted mb-0">
            Semua yang pernah Anda ajukan ke Kurikulum beserta status dan progres tanda tangannya
            (Guru, Kurikulum, Owner). Riwayat tidak hilang walaupun sudah selesai.
        </p>
    </div>

    @include('partials.riwayat-pengajuan')
</div>
@endsection
