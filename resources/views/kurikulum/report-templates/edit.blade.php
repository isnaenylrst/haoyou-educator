@extends('layouts.kurikulum')
@section('title', 'Template: ' . $template->name)

@section('content')
@use('App\Support\RichText')
@php
    $rows = old('meetings', $template->meetings->map(fn ($m) => ['number' => $m->meeting_number, 'materials' => $m->materials])->all());
    if (!$rows) { $rows = [['number' => 1, 'materials' => '']]; }
@endphp
<div class="container-fluid py-3 rt-wrap" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $template->name }}</h4>
            <div class="text-muted small">Program: {{ $template->category?->category_name ?? '-' }} · Level: {{ $template->level?->level_name ?? 'semua level' }}</div>
        </div>
        <a href="{{ route('kurikulum.report-template.index') }}" class="btn btn-outline-secondary rounded-pill"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach(collect($errors->all())->unique() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('kurikulum.report-template.update', $template) }}">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label fw-semibold">Nama template</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required maxlength="255">
        </div>

        <div class="alert alert-info small">Satu kartu = satu pertemuan. Ketik langsung seperti di Word (bold, ukuran huruf, warna, daftar). Teks Mandarin boleh. Guru akan melihat teks ini <b>terkunci</b> dan hanya mengisi Achievement Level dan Description.</div>

        <div id="meetings">
            @foreach($rows as $i => $r)
                <div class="card border-0 shadow-sm mb-3 mt-row">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Pertemuan ke-</label>
                                <input type="number" min="1" max="200" name="meetings[{{ $i }}][number]" class="form-control text-center fw-bold" value="{{ $r['number'] }}" required>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label small fw-semibold">Learning Materials</label>
                                <textarea name="meetings[{{ $i }}][materials]" class="rt-field" placeholder="Student learned ...">{{ RichText::editorValue($r['materials'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm mt-4 mt-del" title="Hapus pertemuan">✕</button></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="addMeeting" class="btn btn-outline-dark btn-sm mb-4"><i class="fa-solid fa-plus me-1"></i> Tambah pertemuan</button>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('kurikulum.report-template.index') }}" class="btn btn-link">Batal</a>
            <button class="btn btn-warning fw-bold px-4 rounded-pill"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan template</button>
        </div>
    </form>
</div>

@include('partials.rich-editor')

<script>
(function () {
    const wrap = document.getElementById('meetings');
    function reindex() {
        wrap.querySelectorAll('.mt-row').forEach((row, i) => row.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/meetings\[\d+\]/, 'meetings[' + i + ']');
        }));
    }
    document.getElementById('addMeeting').addEventListener('click', function () {
        const rows = wrap.querySelectorAll('.mt-row');
        const i = rows.length;
        const last = rows.length ? parseInt(rows[rows.length - 1].querySelector('input[type=number]').value || rows.length, 10) : 0;
        const div = document.createElement('div');
        div.className = 'card border-0 shadow-sm mb-3 mt-row';
        div.innerHTML = '<div class="card-body"><div class="row g-3">'
            + '<div class="col-md-2"><label class="form-label small fw-semibold">Pertemuan ke-</label><input type="number" min="1" max="200" name="meetings[' + i + '][number]" class="form-control text-center fw-bold" value="' + (last + 1) + '" required></div>'
            + '<div class="col-md-9"><label class="form-label small fw-semibold">Learning Materials</label><textarea name="meetings[' + i + '][materials]" class="rt-field" placeholder="Student learned ..."></textarea></div>'
            + '<div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm mt-4 mt-del" title="Hapus pertemuan">✕</button></div>'
            + '</div></div>';
        wrap.appendChild(div);
        div.querySelectorAll('textarea.rt-field').forEach(t => RT.add(t));
    });
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('mt-del')) return;
        if (wrap.querySelectorAll('.mt-row').length <= 1) return;
        const row = e.target.closest('.mt-row');
        RT.remove(row);
        row.remove(); reindex();
    });
})();
</script>
@endsection