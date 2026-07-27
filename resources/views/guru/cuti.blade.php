@extends('layouts.guru')

@section('content')

<div class="mb-4">

    <h2 class="fw-bold mb-1">
        Cuti / Ganti Kelas
    </h2>

    <p class="text-muted">
        Pengajuan cuti atau permintaan guru pengganti.
        Semua pengajuan akan dikirim ke Kepala Kurikulum.
    </p>

</div>


<div class="card shadow-sm border-0 mb-4">

<div class="card-body">

<form>

<div class="row">

<div class="col-md-4">

<label class="form-label fw-semibold">
Jenis Pengajuan
</label>

<select class="form-select">

<option>Cuti</option>

<option>Ganti Kelas</option>

</select>

</div>


<div class="col-md-4">

<label class="form-label fw-semibold">

Tanggal

</label>

<input
type="date"
class="form-control">

</div>


<div class="col-md-4">

<label class="form-label fw-semibold">

Kelas

</label>

<select class="form-select">

<option>Pilih Kelas</option>

<option>Maochong 3A</option>

<option>Jianer 2B</option>

<option>Hudie 1A</option>

</select>

</div>

</div>


<div class="mt-3">

<label class="form-label fw-semibold">

Guru Pengganti

</label>

<select class="form-select">

<option>Pilih Guru</option>

<option>Ratna</option>

<option>Dinda</option>

<option>Rina</option>

</select>

<small class="text-muted">

Daftar guru diambil dari jadwal yang tersedia.

</small>

</div>


<div class="mt-3">

<label class="form-label fw-semibold">

Alasan

</label>

<textarea

class="form-control"

rows="4"

placeholder="Tuliskan alasan cuti atau pergantian kelas..."></textarea>

</div>


<div class="mt-4">

<button class="btn btn-primary">

<i class="fas fa-paper-plane me-1"></i>

Kirim Pengajuan

</button>

</div>

</form>

</div>

</div>


<div class="card shadow-sm border-0">

<div class="card-body">

<h5 class="fw-bold mb-3">

Riwayat Pengajuan

</h5>

<table class="table align-middle">

<thead>

<tr>

<th>Tanggal</th>

<th>Jenis</th>

<th>Kelas</th>

<th>Guru Pengganti</th>

<th>Status</th>

<th>Keterangan</th>

</tr>

</thead>

<tbody>

<tr>

<td>18 Juli 2026</td>

<td>Cuti</td>

<td>Jianer 2B</td>

<td>-</td>

<td>

<span class="badge bg-success">

Disetujui

</span>

</td>

<td>

Libur keluarga

</td>

</tr>


<tr>

<td>11 Juli 2026</td>

<td>Ganti Kelas</td>

<td>Maochong 3A</td>

<td>Dinda</td>

<td>

<span class="badge bg-warning text-dark">

Menunggu

</span>

</td>

<td>

Bentrok jadwal

</td>

</tr>


<tr>

<td>3 Juli 2026</td>

<td>Cuti</td>

<td>Hudie 1A</td>

<td>-</td>

<td>

<span class="badge bg-danger">

Ditolak

</span>

</td>

<td>

Tidak ada guru pengganti

</td>

</tr>

</tbody>

</table>

</div>

</div>

@endsection