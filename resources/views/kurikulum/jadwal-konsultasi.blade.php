@extends('layouts.kurikulum')

@section('title','Jadwal Konsultasi')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Jadwal Konsultasi</h3>
        <small class="text-muted">
            Jadwal konsultasi untuk guru — LP & PPT, Direktur, dan Trial Teaching.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Form belum bisa disimpan:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3">
        <span class="badge bg-danger-subtle text-danger">
            {{ $totalBelumTerjadwal }} Belum Terjadwal
        </span>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="list-group list-group-flush">

            @forelse ($consultations as $c)

            <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>
                    <div class="fw-semibold">
                        {{ $c->type_label }} — {{ $c->teacher->name ?? '-' }}
                    </div>
                    <small class="text-muted">
                        @if ($c->scheduled_at)
                            {{ $c->scheduled_at->translatedFormat('l, d M Y H:i') }}
                        @else
                            Belum terjadwal
                        @endif
                    </small>
                </div>

                <div class="d-flex align-items-center gap-2">

                    @if ($c->status === 'Belum Terjadwal')
                        <span class="badge bg-warning text-dark">Belum Terjadwal</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal{{ $c->id }}">
                            Jadwalkan
                        </button>
                    @elseif ($c->status === 'Terjadwal')
                        <span class="badge bg-success">Terjadwal</span>
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal{{ $c->id }}">
                            Ganti Hari
                        </button>
                        <form action="{{ route('kurikulum.jadwal-konsultasi.complete', $c) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-success btn-sm">Selesai</button>
                        </form>
                    @elseif ($c->status === 'Selesai')
                        <span class="badge bg-secondary">Selesai</span>
                    @else
                        <span class="badge bg-dark">Dibatalkan</span>
                    @endif

                    <form action="{{ route('kurikulum.jadwal-konsultasi.destroy', $c) }}" method="POST"
                          onsubmit="return confirm('Hapus jadwal ini?')" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>

                </div>
            </div>

            <div class="modal fade" id="modalJadwal{{ $c->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('kurikulum.jadwal-konsultasi.update', $c) }}" method="POST" class="modal-content">
                        @csrf @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title">Jadwalkan — {{ $c->teacher->name ?? '-' }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Tanggal & Jam</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control" required
                                value="{{ $c->scheduled_at ? $c->scheduled_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            @empty
            <div class="list-group-item text-center text-muted py-4">
                Belum ada jadwal konsultasi.
            </div>
            @endforelse

        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h3 class="fw-bold mb-4">Buat Jadwal Baru</h3>

            <form action="{{ route('kurikulum.jadwal-konsultasi.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Jenis Konsultasi</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="LP_PPT"        {{ old('type') === 'LP_PPT' ? 'selected' : '' }}>Konsultasi LP & PPT (Kurikulum)</option>
                        <option value="Direktur"      {{ old('type') === 'Direktur' ? 'selected' : '' }}>Konsultasi Direktur</option>
                        <option value="Trial_Teaching" {{ old('type') === 'Trial_Teaching' ? 'selected' : '' }}>Trial Teaching</option>
                    </select>
                    @error('type')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Guru</label>
                    <select name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Guru --</option>
                        @forelse ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ (string) old('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @empty
                            <option disabled>-- Tidak ada guru aktif ditemukan --</option>
                        @endforelse
                    </select>
                    @error('teacher_id')<small class="text-danger">{{ $message }}</small>@enderror

                    @if ($teachers->isEmpty())
                        <small class="text-danger">
                            ⚠ Tabel guru kosong / tidak ada yang berstatus "Active". Cek data di tabel <code>teachers</code>.
                        </small>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal & Jam <small class="text-muted">(kosongkan jika belum pasti)</small></label>
                    <input type="datetime-local" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror" value="{{ old('scheduled_at') }}">
                    @error('scheduled_at')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" checked>
                    <label class="form-check-label">Kirim juga ke WhatsApp Guru & Pak Joy</label>
                </div>

                <button type="submit" class="btn btn-success">Buat Jadwal</button>

            </form>

        </div>
    </div>

</div>

@endsection