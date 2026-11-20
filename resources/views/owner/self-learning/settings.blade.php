@extends('layouts.owner')

@section('title', 'Pengaturan Nilai Belajar Mandiri')

@section('content')
<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1"><i class="fa-solid fa-sliders text-warning me-2"></i> Pengaturan Nilai Belajar Mandiri</h2>
        <p class="text-muted mb-0">Atur batas lulus dan bobot nilai untuk Sertifikat Belajar Mandiri.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger rounded-3 shadow-sm">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4" style="max-width:640px">
        <form method="POST"action= "{{ route('owner.self-study.settings.update') }}" class="card-body p-4">
            @csrf @method('PUT')

            <div class="mb-4">
                <label class="form-label fw-semibold">Batas lulus (dari 100)</label>
                <input type="number" name="passing_score" min="1" max="100" required
                       class="form-control" value="{{ old('passing_score', $setting['passing_score']) }}">
                <small class="text-muted">Siswa bisa membuat sertifikat kalau total nilainya sama dengan atau di atas angka ini.</small>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-6">
                    <label class="form-label fw-semibold">Bobot Vocab (%)</label>
                    <input type="number" name="weight_vocab" id="wVocab" min="0" max="100" required
                           class="form-control" value="{{ old('weight_vocab', $setting['weight_vocab']) }}">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Bobot Grammar (%)</label>
                    <input type="number" id="wGrammar" class="form-control bg-light" readonly
                           value="{{ 100 - old('weight_vocab', $setting['weight_vocab']) }}">
                </div>
            </div>
            <small class="text-muted d-block mb-4">Bobot Grammar otomatis = 100 dikurangi bobot Vocab, jadi totalnya selalu 100%.</small>

            <div class="alert alert-warning small rounded-3">
                <strong>Perlu diketahui:</strong>
                <ul class="mb-0 ps-3">
                    <li>Perubahan hanya berlaku untuk <strong>sertifikat baru</strong>. Sertifikat yang sudah terbit tidak berubah.</li>
                    <li>Siswa yang nilainya jatuh di bawah batas baru tidak bisa membuat sertifikat sampai nilainya naik.</li>
                    <li>Predikat: @foreach ($predikat as $min => $label) {{ $label }} (≥ {{ $min }}){{ ! $loop->last ? ', ' : '' }} @endforeach.
                        Nilai lulus di bawah batas predikat terendah tercetak "Cukup".</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Simpan Pengaturan</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('wVocab').addEventListener('input', function () {
        const v = Math.min(100, Math.max(0, parseInt(this.value || 0, 10)));
        document.getElementById('wGrammar').value = 100 - v;
    });
</script>
@endsection