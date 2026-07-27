@extends('layouts.kurikulum')

@section('title', 'Dashboard Kurikulum')

@section('content')

<div class="mb-4">
    <h2 class="fw-bold">Dashboard Kepala Kurikulum</h2>
    <p class="text-muted mb-0">
        Semua yang perlu ditindaklanjuti dari upload & pengajuan guru
    </p>
</div>

<div class="row">

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold">18</h3>
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
                    <h3 class="fw-bold text-warning">4</h3>
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
                    <h3 class="fw-bold text-warning">6</h3>
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
                    <h3 class="fw-bold text-danger">2</h3>
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

            <tr>
                <td>
                    <b>Rina Wulandari</b><br>
                    Konsultasi Direktur (H-14) belum terjadwal, Jianer 2B
                    <br>
                    <small class="text-danger">
                        Batas waktu 12 Juli 2026 · 6 hari lagi
                    </small>
                </td>

                <td width="120">
                    <button class="btn btn-outline-primary btn-sm">
                        Jadwalkan
                    </button>
                </td>
            </tr>

            <tr>
                <td>
                    <b>Rina Wulandari</b><br>
                    Upload LP & PPT kelas 7B
                    <br>
                    <small class="text-muted">Menunggu 2 hari</small>
                </td>

                <td>
                    <button class="btn btn-outline-secondary btn-sm">
                        Lihat
                    </button>
                </td>
            </tr>

            <tr>
                <td>
                    <b>Budi Santoso</b><br>
                    Upload LP & PPT Feixiang 1C
                    <br>
                    <small class="text-muted">Menunggu 1 hari</small>
                </td>

                <td>
                    <button class="btn btn-outline-secondary btn-sm">
                        Lihat
                    </button>
                </td>
            </tr>

            <tr>
                <td>
                    <b>Ahmad Fauzi</b><br>
                    Jurnal & Absensi kelas 9A
                    <br>
                    <small class="text-muted">Menunggu direview</small>
                </td>

                <td>
                    <button class="btn btn-outline-secondary btn-sm">
                        Lihat
                    </button>
                </td>
            </tr>

            <tr>
                <td>
                    <b>Rina Wulandari</b><br>
                    Progress Report Mid 2 kelas 7B
                    <br>
                    <small class="text-muted">Menunggu direview</small>
                </td>

                <td>
                    <button class="btn btn-outline-secondary btn-sm">
                        Lihat
                    </button>
                </td>
            </tr>

            <tr>
                <td>
                    <b>Siti Nurhaliza</b><br>
                    Ganti kelas 6C (Pengganti : Rina)
                    <br>
                    <small class="text-muted">Menunggu persetujuan</small>
                </td>

                <td>
                    <button class="btn btn-outline-secondary btn-sm">
                        Lihat
                    </button>
                </td>
            </tr>

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

            <tr>

                <td>
                    SOP Pengajaran Umum diperbarui
                    <br>
                    <small class="text-muted">3 hari lalu</small>
                </td>

                <td width="180">
                    <span class="badge bg-success">
                        Aktif
                    </span>
                </td>

            </tr>

            <tr>

                <td>
                    4 materi masih berstatus draft
                    <br>
                    <small class="text-muted">
                        Jianer • Feixiang • Private
                    </small>
                </td>

                <td>
                    <span class="badge bg-warning text-dark">
                        Perlu Diselesaikan
                    </span>
                </td>

            </tr>

            </tbody>

        </table>

    </div>

</div>

@endsection