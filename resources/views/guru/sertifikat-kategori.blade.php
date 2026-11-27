@extends('layouts.guru')

@section('title','Kategori Penilaian Sertifikat')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
        <h2 class="fw-bold mb-0">Kategori Penilaian Sertifikat</h2>
        <a href="{{ route('teacher.sertifikat') }}" class="btn btn-outline-secondary btn-sm">&larr; Kembali ke Sertifikat</a>
    </div>
    <p class="text-muted mb-4">
        Atur kategori yang muncul di dropdown pembuatan sertifikat. Tiap kategori menentukan komponen nilai
        (maks 100 per komponen), label yang tampil di sertifikat, dan nilai lulus minimal.
        Total maksimal otomatis = jumlah komponen &times; 100. Kategori berlaku untuk semua guru.
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== TAMBAH KATEGORI ===== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Tambah Kategori</h5>

            <form method="POST" action="{{ route('teacher.sertifikat-kategori.store') }}" class="row g-3 align-items-end js-cat-form">
                @csrf

                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Nama Kategori (tampil di dropdown)</label>
                    <input type="text" name="name" maxlength="100" value="{{ old('name') }}" class="form-control"
                           placeholder="mis. HSK 3-6 (Listening + Reading + Writing + Speaking)" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">Subjudul di Sertifikat <span class="text-muted">(opsional)</span></label>
                    <input type="text" name="sub" maxlength="150" value="{{ old('sub') }}" class="form-control"
                           placeholder="mis. MOCK TEST HSK 3-6">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Nilai Lulus Minimal</label>
                    <input type="number" name="passing" min="1" value="{{ old('passing') }}" class="form-control js-passing" required>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold mb-2">Komponen nilai &amp; label di sertifikat</label>
                    <div class="row g-2">
                        @foreach ($skillNames as $key => $label)
                            <div class="col-6 col-md-3">
                                <div class="form-check mb-1">
                                    <input class="form-check-input js-skill-check" type="checkbox" name="skills[]"
                                           value="{{ $key }}" id="new_{{ $key }}"
                                           @checked(in_array($key, (array) old('skills', [])))>
                                    <label class="form-check-label small" for="new_{{ $key }}">{{ $label }}</label>
                                </div>
                                <input type="text" name="labels[{{ $key }}]" maxlength="30" value="{{ old('labels.' . $key) }}"
                                       class="form-control form-control-sm" placeholder="Label (mis. {{ $label }})">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-9 small js-summary text-muted"></div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-warning fw-bold">Tambah Kategori</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== DAFTAR KATEGORI ===== --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Daftar Kategori</h5>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Komponen</th>
                            <th>Maks</th>
                            <th>Lulus Min.</th>
                            <th>Dipakai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($categories as $c)
                        @php $used = (int) ($usage[$c->code] ?? 0); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->name }}</div>
                                <div class="small text-muted">{{ $c->sub ?: 'MOCK TEST ' . mb_strtoupper($c->name) }}</div>
                            </td>
                            <td class="small">{{ implode(', ', $c->skills ?? []) }}</td>
                            <td>{{ $c->max }}</td>
                            <td>{{ $c->passing }}</td>
                            <td class="small text-muted">{{ $used }} sertifikat</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="document.getElementById('editcat{{ $c->id }}').classList.toggle('d-none')">Edit</button>

                                <form method="POST" action="{{ route('teacher.sertifikat-kategori.destroy', $c) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" @disabled($used > 0)
                                            title="{{ $used > 0 ? 'Sedang dipakai sertifikat' : '' }}">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        <tr id="editcat{{ $c->id }}" class="d-none">
                            <td colspan="6" class="bg-light">
                                <form method="POST" action="{{ route('teacher.sertifikat-kategori.update', $c) }}" class="row g-2 align-items-end js-cat-form">
                                    @csrf @method('PUT')

                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Nama Kategori</label>
                                        <input type="text" name="name" maxlength="100" value="{{ $c->name }}" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold mb-1">Subjudul di Sertifikat</label>
                                        <input type="text" name="sub" maxlength="150" value="{{ $c->sub }}" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1">Nilai Lulus Minimal</label>
                                        <input type="number" name="passing" min="1" value="{{ $c->passing }}" class="form-control form-control-sm js-passing" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small fw-semibold mb-1">Komponen nilai &amp; label</label>
                                        <div class="row g-2">
                                            @foreach ($skillNames as $key => $label)
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input js-skill-check" type="checkbox" name="skills[]"
                                                               value="{{ $key }}" id="c{{ $c->id }}_{{ $key }}"
                                                               @checked(isset($c->skills[$key]))>
                                                        <label class="form-check-label small" for="c{{ $c->id }}_{{ $key }}">{{ $label }}</label>
                                                    </div>
                                                    <input type="text" name="labels[{{ $key }}]" maxlength="30"
                                                           value="{{ $c->skills[$key] ?? '' }}"
                                                           class="form-control form-control-sm" placeholder="Label (mis. {{ $label }})">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if ($used > 0)
                                        <div class="col-12 small text-muted">
                                            Kategori ini dipakai {{ $used }} sertifikat, jadi komponen nilainya tidak bisa diubah.
                                            Nama, label, subjudul, dan nilai lulus tetap bisa diubah.
                                        </div>
                                    @endif

                                    <div class="col-md-9 small js-summary text-muted"></div>
                                    <div class="col-md-3 d-flex gap-1 justify-content-end">
                                        <button class="btn btn-warning btn-sm fw-bold">Simpan</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="document.getElementById('editcat{{ $c->id }}').classList.add('d-none')">Batal</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    // Ringkasan: nilai maksimal otomatis = jumlah komponen x 100
    function refreshCat(form) {
        const count   = form.querySelectorAll('.js-skill-check:checked').length;
        const max     = count * 100;
        const passing = parseInt(form.querySelector('.js-passing').value, 10);
        const box     = form.querySelector('.js-summary');

        let text = count === 0
            ? 'Pilih minimal satu komponen nilai.'
            : 'Nilai maksimal: ' + max + (isNaN(passing) ? '.' : ', lulus min. ' + passing + ' dari ' + max + '.');
        let bad = count > 0 && !isNaN(passing) && passing > max;
        if (bad) text = 'Nilai lulus (' + passing + ') tidak boleh melebihi nilai maksimal (' + max + ').';

        box.className = 'small js-summary ' + (bad || count === 0 ? 'text-danger' : 'text-muted');
        box.textContent = text;
    }

    document.querySelectorAll('.js-cat-form').forEach(form => {
        form.addEventListener('input', () => refreshCat(form));
        form.addEventListener('change', () => refreshCat(form));
        refreshCat(form);
    });
</script>

@endsection