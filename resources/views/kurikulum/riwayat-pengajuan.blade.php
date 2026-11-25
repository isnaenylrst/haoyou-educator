@extends('layouts.kurikulum')

@section('title', 'Riwayat Pengajuan')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h2 class="fw-bold mb-1">Riwayat Pengajuan Guru</h2>
            <p class="text-muted mb-0">
                Semua yang pernah diajukan guru (LP &amp; PPT, jurnal, progress report, cuti / ganti kelas, sertifikat,
                reimburse &amp; bahan) tetap tersimpan di sini, termasuk yang sudah di-ACC dan TTD-nya lengkap.
            </p>
        </div>
        <a href="{{ url('/kurikulum/review-pengajuan') }}" class="btn btn-outline-dark btn-sm">
            <i class="fa-solid fa-circle-check me-1"></i> Kembali ke Review Pengajuan
        </a>
    </div>

    @include('partials.riwayat-pengajuan')
</div>
@endsection
