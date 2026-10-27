@extends('layouts.kurikulum')

@section('title','Jadwal Konsultasi')

@section('content')

@php
    $editId = (string) old('_edit_id');
    $addErr = fn (string $field) => $editId === '' ? $errors->first($field) : '';

    // Tautan WhatsApp (wa.me) siap kirim, dari nomor HP guru
    $waLink = function ($c) {
        $phone = preg_replace('/\D+/', '', $c->teacher->phone ?? '');
        if ($phone === '') {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $when = $c->scheduled_at ? $c->scheduled_at->translatedFormat('l, d F Y \p\u\k\u\l H:i') : 'akan diinformasikan';
        $text = "Halo {$c->teacher->name}, jadwal {$c->type_label}: {$when}.";
        if ($c->notes) {
            $text .= " Catatan: {$c->notes}.";
        }
        $text .= ' Terima kasih.';

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    };

    $tabs = [
        'aktif'      => ['Aktif', ($counts['Belum Terjadwal'] ?? 0) + ($counts['Terjadwal'] ?? 0)],
        'selesai'    => ['Selesai', $counts['Selesai'] ?? 0],
        'dibatalkan' => ['Dibatalkan', $counts['Dibatalkan'] ?? 0],
        'semua'      => ['Semua', $counts->sum()],
    ];
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Jadwal Konsultasi</h3>
        <small class="text-muted">
            Jadwal konsultasi untuk guru — LP & PPT, Direktur, dan Trial Teaching.
            Jadwal yang dibuat di sini langsung muncul di Notifikasi dan Progres Kelas guru.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Form belum bisa disimpan:</strong>
            <ul class="mb-0">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (($counts['Belum Terjadwal'] ?? 0) > 0)
        <div class="mb-3">
            <span class="badge bg-danger-subtle text-danger">
                {{ $counts['Belum Terjadwal'] }} pengajuan Belum Terjadwal — perlu dijadwalkan
            </span>
        </div>
    @endif

    {{-- TAB --}}
    <ul class="nav nav-pills mb-3">
        @foreach ($tabs as $key => [$label, $total])
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('kurikulum.jadwal-konsultasi', ['tab' => $key]) }}">
                    {{ $label }} <span class="badge bg-light text-dark ms-1">{{ $total }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- DAFTAR --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="list-group list-group-flush">

            @forelse ($consultations as $c)
                @php $wa = ($c->status === 'Terjadwal' && $c->notify_whatsapp) ? $waLink($c) : null; @endphp

                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $c->type_label }} — {{ $c->teacher->name ?? '-' }}
                        </div>
                        <small class="text-muted">
                            @if ($c->scheduled_at)
                                {{ $c->scheduled_at->translatedFormat('l, d F Y \p\u\k\u\l H:i') }}
                            @else
                                Belum terjadwal
                            @endif
                            @if ($c->teacherMaterial)
                                · Pengajuan: {{ $c->teacherMaterial->ppt_title }}
                            @endif
                        </small>
                        @if ($c->notes)
                            <div class="small mt-1"><i class="fa-solid fa-location-dot me-1 text-muted"></i>{{ $c->notes }}</div>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($c->status === 'Belum Terjadwal')
                            <span class="badge bg-warning text-dark">Belum Terjadwal</span>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal{{ $c->id }}">
                                Jadwalkan
                            </button>
                        @elseif ($c->status === 'Terjadwal')
                            <span class="badge bg-success">Terjadwal</span>
                            @if ($wa)
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm">
                                    <i class="fa-brands fa-whatsapp"></i> Kirim WA
                                </a>
                            @endif
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal{{ $c->id }}">
                                Ganti Jadwal
                            </button>
                            <form action="{{ route('kurikulum.jadwal-konsultasi.complete', $c) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Tandai konsultasi ini sudah selesai?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-success btn-sm">Selesai</button>
                            </form>
                        @elseif ($c->status === 'Selesai')
                            <span class="badge bg-secondary">Selesai</span>
                        @else
                            <span class="badge bg-dark">Dibatalkan</span>
                        @endif

                        @if (in_array($c->status, ['Belum Terjadwal', 'Terjadwal'], true))
                            <form action="{{ route('kurikulum.jadwal-konsultasi.cancel', $c) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Batalkan konsultasi ini? Guru akan bisa mengajukan ulang.')">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-warning btn-sm">Batalkan</button>
                            </form>
                        @endif

                        <form action="{{ route('kurikulum.jadwal-konsultasi.destroy', $c) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Hapus jadwal ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">
                    Tidak ada jadwal konsultasi pada tab ini.
                </div>
            @endforelse

        </div>

        @if ($consultations->hasPages())
            <div class="card-footer bg-white">{{ $consultations->links() }}</div>
        @endif
    </div>

    {{-- MODAL JADWALKAN / GANTI JADWAL --}}
    @foreach ($consultations as $c)
        @if (in_array($c->status, ['Belum Terjadwal', 'Terjadwal'], true))
            @php $useOld = $editId === (string) $c->id; @endphp

            <div class="modal fade" id="modalJadwal{{ $c->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('kurikulum.jadwal-konsultasi.update', $c) }}" method="POST" class="modal-content"
                          onsubmit="return lockSubmit(this, 'Menyimpan...')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="_edit_id" value="{{ $c->id }}">

                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">Jadwalkan — {{ $c->teacher->name ?? '-' }}</h5>
                                <small class="text-muted">{{ $c->type_label }}</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal & Jam</label>
                                <input type="datetime-local" name="scheduled_at"
                                       class="form-control {{ $useOld && $errors->has('scheduled_at') ? 'is-invalid' : '' }}"
                                       min="{{ now()->format('Y-m-d\T00:00') }}"
                                       value="{{ $useOld ? old('scheduled_at') : $c->scheduled_at?->format('Y-m-d\TH:i') }}" required>
                                @if ($useOld && $errors->has('scheduled_at'))
                                    <div class="invalid-feedback">{{ $errors->first('scheduled_at') }}</div>
                                @endif
                            </div>

                            <div class="mb-1">
                                <label class="form-label fw-semibold">Lokasi / Link Meeting <small class="text-muted">(opsional)</small></label>
                                <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                                          placeholder="mis. Ruang Kurikulum lantai 2, atau https://meet.google.com/...">{{ $useOld ? old('notes') : $c->notes }}</textarea>
                                <small class="text-muted">Catatan ini terlihat oleh guru.</small>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    {{-- BUAT JADWAL BARU --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h3 class="fw-bold mb-4">Buat Jadwal Baru</h3>

            <form action="{{ route('kurikulum.jadwal-konsultasi.store') }}" method="POST"
                  onsubmit="return lockSubmit(this, 'Menyimpan...')">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Konsultasi</label>
                        <select name="type" class="form-select {{ $addErr('type') ? 'is-invalid' : '' }}" required>
                            @foreach (['LP_PPT' => 'Konsultasi LP & PPT (Kurikulum)', 'Direktur' => 'Konsultasi Direktur', 'Trial_Teaching' => 'Trial Teaching'] as $val => $label)
                                <option value="{{ $val }}" @selected($editId === '' && old('type') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($addErr('type'))<small class="text-danger">{{ $addErr('type') }}</small>@endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Guru</label>
                        <select name="teacher_id" class="form-select {{ $addErr('teacher_id') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Pilih Guru --</option>
                            @forelse ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($editId === '' && (string) old('teacher_id') === (string) $teacher->id)>
                                    {{ $teacher->name }}
                                </option>
                            @empty
                                <option disabled>-- Tidak ada guru aktif --</option>
                            @endforelse
                        </select>
                        @if ($addErr('teacher_id'))<small class="text-danger">{{ $addErr('teacher_id') }}</small>@endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal & Jam <small class="text-muted">(kosongkan jika belum pasti)</small></label>
                    <input type="datetime-local" name="scheduled_at"
                           class="form-control {{ $addErr('scheduled_at') ? 'is-invalid' : '' }}"
                           min="{{ now()->format('Y-m-d\T00:00') }}"
                           value="{{ $editId === '' ? old('scheduled_at') : '' }}">
                    @if ($addErr('scheduled_at'))<small class="text-danger">{{ $addErr('scheduled_at') }}</small>@endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Lokasi / Link Meeting <small class="text-muted">(opsional)</small></label>
                    <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                              placeholder="mis. Ruang Kurikulum lantai 2, atau https://meet.google.com/...">{{ $editId === '' ? old('notes') : '' }}</textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="notifyWa" checked>
                    <label class="form-check-label" for="notifyWa">
                        Tampilkan tombol “Kirim WA” untuk memberi kabar guru
                    </label>
                    <div><small class="text-muted">Tombol membuka WhatsApp dengan pesan siap kirim. Pengiriman otomatis belum aktif.</small></div>
                </div>

                <button type="submit" class="btn btn-success">Buat Jadwal</button>
            </form>
        </div>
    </div>

</div>

<script>
    function lockSubmit(form, text) {
        const btn = form.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.innerText = text; }
        return true;
    }

    @if ($editId !== '')
        // Jika simpan jadwal gagal validasi, buka kembali modal yang sama
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('modalJadwal{{ (int) $editId }}');
            if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
        });
    @endif
</script>

@endsection