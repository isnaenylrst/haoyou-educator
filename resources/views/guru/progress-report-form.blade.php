@extends('layouts.guru')
@section('title', 'Buat Progress Report')

@section('content')
<div class="container-fluid">

<h2 class="fw-bold">Progress Report: {{ $e->student_name }}</h2>
<p class="text-muted">
    Kelas {{ $e->class_name }} · Kehadiran otomatis: <strong>{{ $present }}/{{ $total }}</strong>
</p>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
    </div>
@endif

{{-- Dikembalikan Kurikulum --}}
@if($report && $report->returned_at)
    <div class="alert alert-warning">
        <strong>Dikembalikan Kurikulum.</strong>
        {{ $report->review_note ?: 'Silakan perbaiki lalu kirim ulang.' }}
    </div>
@endif

{{-- Batas H+3 --}}
@if($deadline)
    <div class="alert {{ $isLate ? 'alert-danger' : 'alert-info' }}">
        @if($isLate)
            Batas pengisian sudah lewat ({{ $deadline->translatedFormat('d M Y') }}).
            Hubungi Kurikulum untuk membuka kembali.
        @else
            Batas pengisian: <strong>{{ $deadline->translatedFormat('l, d M Y') }}</strong>
            (maksimal H+3 setelah pertemuan terakhir).
        @endif
    </div>
@endif

@if($competencies->isEmpty())
    <div class="alert alert-warning">
        Kurikulum belum menetapkan kompetensi untuk level kelas ini, jadi progress report belum bisa diisi.
    </div>
    <a href="{{ route('teacher.progress-report', ['class_id' => $e->class_id]) }}" class="btn btn-outline-secondary">Kembali</a>
@else

<form method="POST" action="{{ route('teacher.progress-report.store', $e->id) }}">
    @csrf

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Jenis Report</label>
            <input name="report_type" class="form-control"
                   value="{{ old('report_type', $report->report_type ?? 'Trimester Academic Progress Report') }}">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Periode</label>
            <input name="report_period" class="form-control" placeholder="mis. Siklus 3 Bulan · Jul–Sep 2026"
                   value="{{ old('report_period', $report->report_period ?? '') }}">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">NO</th>
                    <th>Kompetensi</th>
                    <th style="width:220px">Achievement Level</th>
                    <th style="width:140px">Nilai Latihan</th>
                </tr>
            </thead>
            <tbody>
            @foreach($competencies as $i => $comp)
                @php
                    $prev = $saved->get($comp->id);
                    $levelVal = old("rows.$i.level", $prev['level'] ?? '');
                    $scoreVal = old("rows.$i.score", $prev['score'] ?? '');
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $comp->name }}</strong>
                        <input type="hidden" name="rows[{{ $i }}][competency_id]" value="{{ $comp->id }}">
                    </td>
                    <td>
                        <select name="rows[{{ $i }}][level]" class="form-select" required>
                            <option value="">— pilih —</option>
                            @foreach($levels as $lv)
                                <option value="{{ $lv->level }}" title="{{ $lv->description }}"
                                        @selected($levelVal === $lv->level)>{{ $lv->level }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="rows[{{ $i }}][score]" class="form-control"
                               min="0" max="100" step="0.1" value="{{ $scoreVal }}" required>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-muted small">
        Deskripsi untuk tiap Achievement Level sudah baku dan otomatis tercantum di PDF.
    </p>

    <div class="mt-3 d-flex gap-2 flex-wrap">
        <button type="submit" name="action" value="draft" class="btn btn-outline-primary" @disabled($isLate)>
            Simpan Draft & Buat PDF
        </button>
        <button type="submit" name="action" value="submit" class="btn btn-primary" @disabled($isLate)>
            Kirim ke Kurikulum
        </button>
        <a href="{{ route('teacher.progress-report', ['class_id' => $e->class_id]) }}" class="btn btn-link">Batal</a>
    </div>
</form>

@endif

</div>
@endsection