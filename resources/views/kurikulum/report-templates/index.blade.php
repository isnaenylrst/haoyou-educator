@extends('layouts.kurikulum')
@section('title', 'Template Progress Report')

@section('content')
<div class="container-fluid py-3">
    <div class="mb-3">
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-table-list text-warning me-2"></i> Template Progress Report per Program</h3>
        <div class="text-muted">Learning Materials tiap pertemuan ditetapkan di sini (Feixiang, Maochong, HSK, dst). Guru hanya mengisi Achievement Level, Description, dan Assessment; kolom Learning Materials terkunci.</div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach(collect($errors->all())->unique() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- STATUS TEMPLATE PER PROGRAM --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Status template per program</h6>
            <div class="small text-muted mb-3">Program tanpa template: guru <b>tidak bisa</b> membuat progress report untuk kelas program tersebut.</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Program</th><th>Status</th><th class="text-center">Pertemuan</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @foreach($categories as $c)
                        @php($t = $byCategory->get($c->id))
                        <tr>
                            <td class="fw-semibold">{{ $c->category_name }}</td>
                            <td>
                                @if($t && $t->meetings_count > 0)
                                    <span class="badge bg-success">Siap dipakai guru</span>
                                @elseif($t)
                                    <span class="badge bg-warning text-dark">Template kosong</span>
                                @else
                                    <span class="badge bg-danger">Belum ada template</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $t?->meetings_count ?? 0 }}</td>
                            <td class="text-end">
                                @if($t)
                                    <a href="{{ route('kurikulum.report-template.edit', $t) }}" class="btn btn-outline-primary btn-sm">Isi / Edit</a>
                                @else
                                    <form method="POST" action="{{ route('kurikulum.report-template.store') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $c->category_name }}">
                                        <input type="hidden" name="program_category_id" value="{{ $c->id }}">
                                        <button class="btn btn-warning btn-sm fw-bold">Buat template {{ $c->category_name }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Buat template baru</h6>
            <form method="POST" action="{{ route('kurikulum.report-template.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nama template <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="mis. Feixiang 读写 - Trimester 1" required maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Program (kategori)</label>
                    <select name="program_category_id" class="form-select">
                        <option value="">-- pilih program --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('program_category_id') == $c->id)>{{ $c->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Atau khusus satu level</label>
                    <select name="program_level_id" class="form-select">
                        <option value="">-- semua level program --</option>
                        @foreach($levels as $l)
                            <option value="{{ $l->id }}" @selected(old('program_level_id') == $l->id)>{{ $l->level_name }}{{ $l->category ? ' ('.$l->category->category_name.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-warning fw-bold w-100">Buat &amp; isi pertemuan</button></div>
            </form>
            <div class="small text-muted mt-2">Template level khusus diutamakan; bila tidak ada, dipakai template program.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Template</th><th>Program</th><th>Level</th><th class="text-center">Pertemuan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                @forelse($templates as $t)
                    <tr>
                        <td class="fw-semibold">{{ $t->name }}</td>
                        <td>{{ $t->category?->category_name ?? '-' }}</td>
                        <td>{{ $t->level?->level_name ?? 'Semua level' }}</td>
                        <td class="text-center"><span class="badge bg-dark">{{ $t->meetings_count }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('kurikulum.report-template.edit', $t) }}" class="btn btn-outline-primary btn-sm">Isi / Edit</a>
                            <form action="{{ route('kurikulum.report-template.destroy', $t) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus template ini? Progress report yang sudah dibuat tidak berubah.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada template. Buat satu untuk tiap program.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection