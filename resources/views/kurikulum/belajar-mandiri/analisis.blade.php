@extends('layouts.kurikulum')

@section('title', 'Analisis Soal Belajar Mandiri')

@section('content')
@php
    $bar = fn ($p) => '<div class="progress" style="height:8px;width:140px"><div class="progress-bar ' . ($p >= 50 ? 'bg-danger' : 'bg-warning') . '" style="width:' . (int) $p . '%"></div></div>';
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <a href="{{ url('/kurikulum/belajar-mandiri') }}" class="small text-decoration-none">&larr; Kembali ke rekap</a>
            <h2 class="fw-bold mt-1 mb-1">Analisis Soal Belajar Mandiri</h2>
            <p class="text-muted mb-0">Soal dan kata yang paling sering salah. Soal bawaan lama (nomor 1 sampai 5) tidak ikut terbaca.</p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">Minimal dijawab</label>
            <input type="number" name="min" min="1" value="{{ $min }}" class="form-control" style="width:90px">
            <button class="btn btn-primary">Terapkan</button>
        </form>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Grammar: soal paling sering salah</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Soal</th><th class="text-center">Dijawab</th><th class="text-center">Salah</th><th class="text-center">% salah</th><th></th></tr></thead>
                <tbody>
                @forelse($grammar as $g)
                    <tr>
                        <td><strong>No. {{ $g->id }}</strong><div class="small text-muted">{{ \Illuminate\Support\Str::limit($g->soal, 90) }}</div></td>
                        <td class="text-center">{{ $g->dijawab }}</td>
                        <td class="text-center">{{ $g->salah }}</td>
                        <td class="text-center fw-bold">{{ $g->persen }}%</td>
                        <td>{!! $bar($g->persen) !!}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data yang memenuhi batas minimal.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Vocab: kata paling sering salah <span class="small text-muted fw-normal">(dihitung sejak log Vocab dipasang)</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Kata</th><th>Arti</th><th>Tes</th><th class="text-center">Dijawab</th><th class="text-center">Salah</th><th class="text-center">% salah</th><th></th></tr></thead>
                <tbody>
                @forelse($vocab as $v)
                    <tr>
                        <td><span class="fs-5">{{ $v->hanzi }}</span><div class="small text-muted">{{ $v->pinyin }}</div></td>
                        <td>{{ $v->meaning_id }}</td>
                        <td>{{ ucfirst($v->test_type) }}</td>
                        <td class="text-center">{{ $v->dijawab }}</td>
                        <td class="text-center">{{ $v->salah }}</td>
                        <td class="text-center fw-bold">{{ $v->persen }}%</td>
                        <td>{!! $bar($v->persen) !!}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data yang memenuhi batas minimal.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection