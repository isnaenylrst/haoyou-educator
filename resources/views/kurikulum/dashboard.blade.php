@extends('layouts.kurikulum')

@section('title', 'Dashboard Kurikulum')

@section('content')

@php
    $reviewUrl = fn (string $tab = 'material') => route('kurikulum.review-pengajuan', ['tab' => $tab]);
    $cards = [
        ['Guru Aktif',               $stats['guruAktif'],  'fa-users',          'text-primary', route('kurikulum.ketersediaan')],
        ['LP & PPT Menunggu',        $stats['materi'],     'fa-book',           'text-warning', $reviewUrl('material')],
        ['Jurnal Belum Direview',    $stats['jurnal'],     'fa-file-lines',     'text-warning', $reviewUrl('jurnal')],
        ['Progress Report Menunggu', $stats['report'],     'fa-clipboard-check','text-warning', $reviewUrl('report')],
        ['Cuti/Ganti Kelas Pending', $stats['cuti'],       'fa-calendar-xmark', 'text-danger',  $reviewUrl('cuti')],
        ['Sertifikat Menunggu ACC',  $stats['sertifikat'], 'fa-award',          'text-danger',  $reviewUrl('sertifikat')],
    ];
@endphp

<div class="mb-4">
    <h2 class="fw-bold">Dashboard Kepala Kurikulum</h2>
    <p class="text-muted mb-0">
        Semua pengajuan, PPT dan jurnal guru dapat dilihat di sini. Silakan tindaklanjuti sesuai kebutuhan.
    </p>

</div>

{{-- KARTU STATISTIK --}}
<div class="row">
    @foreach($cards as [$label, $value, $icon, $color, $url])
        <div class="col-lg-4 col-md-6 mb-3">
            <a href="{{ $url }}" class="text-decoration-none text-reset">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fw-bold {{ $value > 0 ? $color : '' }}">{{ $value }}</h3>
                            <p class="mb-0">{{ $label }}</p>
                        </div>
                        <i class="fa-solid {{ $icon }} fa-2x {{ $color }}"></i>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- PERLU DITINDAKLANJUTI --}}
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0">
            Perlu Ditindaklanjuti
            @if($stats['totalPending'] > 0)
                <span class="badge text-bg-warning ms-1">{{ $stats['totalPending'] }}</span>
            @endif
        </h5>
        <a href="{{ route('kurikulum.jadwal-konsultasi') }}" class="btn btn-primary btn-sm">
            Jadwalkan Konsultasi
            @if($konsultasiBelumTerjadwal > 0)
                <span class="badge text-bg-light ms-1">{{ $konsultasiBelumTerjadwal }}</span>
            @endif
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <tbody>
                @forelse($pending as $item)
                    <tr>
                        <td>
                            <b>{{ $item['who'] }}</b><br>
                            {{ $item['what'] }}<br>
                            <small class="text-muted">{{ $item['time']?->diffForHumans() }}</small>
                        </td>
                        <td width="120" class="text-end">
                            <a href="{{ $reviewUrl($item['tab']) }}" class="btn btn-outline-primary btn-sm">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted py-4">
                            Tidak ada pengajuan yang perlu ditindaklanjuti.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    {{-- SOP TERBARU --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">SOP Terbaru</h5>
                <a href="{{ route('kurikulum.sop') }}" class="small">Kelola</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <tbody>
                        @forelse($latestSops as $sop)
                            <tr>
                                <td>
                                    <a href="{{ route('kurikulum.sop.show', $sop) }}" target="_blank" class="fw-semibold text-decoration-none">
                                        {{ $sop->title }}
                                    </a><br>
                                    <small class="text-muted">{{ $sop->created_at?->diffForHumans() }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-4">Belum ada SOP.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MATERI TERBARU --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Materi Terbaru</h5>
                <a href="{{ route('kurikulum.materi') }}" class="small">Kelola</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <tbody>
                        @forelse($latestMaterials as $m)
                            <tr>
                                <td>
                                    <strong>{{ $m->title }}</strong><br>
                                    <small class="text-muted">
                                        {{ $m->level?->program?->program_name ?? '-' }} · {{ $m->level?->level_name ?? '-' }}
                                        · Meeting {{ $m->meeting_number }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <small class="text-muted">{{ $m->uploader?->username ?? '-' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data materi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection