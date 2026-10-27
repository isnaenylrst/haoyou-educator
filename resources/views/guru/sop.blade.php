@extends('layouts.guru')

@section('title','SOP')

@push('styles')
<style>
    /* kartu yang dituju lewat link notifikasi (#sop-ID) disorot */
    .sop-item:target { background: #fffbeb; box-shadow: inset 4px 0 0 #ffd600; }
    .sop-item { scroll-margin-top: 90px; }
</style>
@endpush

@section('content')

<div class="container-fluid py-3">

    {{-- Header + pencarian --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">SOP</h2>
            <p class="text-muted mb-0">
                Standar operasional prosedur — bisa dibaca sewaktu-waktu sebagai panduan mengajar
            </p>
        </div>

        <form method="GET" action="{{ route('teacher.sop') }}" class="d-flex gap-2" style="min-width:280px">
            <input type="search" name="q" value="{{ $q }}" maxlength="100"
                   class="form-control" placeholder="Cari judul SOP…">
            <button class="btn btn-outline-dark" title="Cari"><i class="fas fa-search"></i></button>
            @if($q !== '')
                <a href="{{ route('teacher.sop') }}" class="btn btn-outline-secondary" title="Hapus pencarian"><i class="fas fa-xmark"></i></a>
            @endif
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="small text-muted mb-2">
        @if($q !== '')
            {{ $sops->total() }} hasil untuk "<strong>{{ $q }}</strong>" dari {{ $total }} SOP
        @else
            {{ $total }} SOP tersedia
        @endif
    </div>

    {{-- DAFTAR SOP --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            @forelse ($sops as $sop)
                @php
                    $uploaded = $sop->uploaded_at ?? $sop->updated_at;
                    $isNew    = $uploaded && $uploaded->gt(now()->subDays(14));
                @endphp

                <div id="sop-{{ $sop->id }}"
                     class="sop-item d-flex justify-content-between align-items-center flex-wrap gap-2 p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <h5 class="fw-semibold mb-1">
                            {{ $sop->title }}
                            @if($isNew)
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1 align-middle"
                                      style="font-size:11px" data-sop-baru>Baru</span>
                            @endif
                        </h5>
                        <small class="text-muted">
                            Diperbarui {{ $uploaded ? $uploaded->translatedFormat('d M Y') . ' · ' . $uploaded->diffForHumans() : '-' }}
                            • PDF • {{ $sop->file_size_label }}
                        </small>
                    </div>

                    @if ($sop->file_exists)
                        <div class="d-flex gap-2">
                            <a href="{{ route('teacher.sop.show', $sop) }}" target="_blank" rel="noopener"
                               class="btn btn-outline-dark rounded-3">
                                <i class="fas fa-eye me-2"></i> Buka
                            </a>
                            <a href="{{ route('teacher.sop.download', $sop) }}"
                               class="btn btn-outline-dark rounded-3">
                                <i class="fas fa-download me-2"></i> Download
                            </a>
                        </div>
                    @else
                        <span class="badge bg-secondary">File belum tersedia</span>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    @if($q !== '')
                        Tidak ada SOP yang cocok dengan "{{ $q }}".
                        <div class="mt-2"><a href="{{ route('teacher.sop') }}" class="small">Tampilkan semua SOP</a></div>
                    @else
                        Belum ada SOP yang diupload Kurikulum.
                    @endif
                </div>
            @endforelse

        </div>
    </div>

    @if($sops->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $sops->links('pagination::bootstrap-5') }}
        </div>
    @endif

    {{-- TEMPLATE PROGRESS REPORT --}}
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <h5 class="fw-bold mb-1">Template Progress Report</h5>
                <small class="text-muted">
                    Acuan format dari Kurikulum. Pengisian progress report dilakukan di menu Progress Report.
                </small>
            </div>

            @forelse ($templates as $template)
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <div class="fw-semibold">{{ $template->name }}</div>
                        <small class="text-muted">
                            {{ strtoupper($template->file_extension) }} • {{ $template->file_size_label }}
                            • diperbarui {{ $template->updated_at->diffForHumans() }}
                        </small>
                    </div>

                    @if ($template->file_exists)
                        <a href="{{ route('teacher.sop.template', $template) }}"
                           class="btn btn-outline-dark rounded-3">
                            <i class="fas fa-download me-2"></i> Download
                        </a>
                    @else
                        <span class="badge bg-secondary">File belum tersedia</span>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    Belum ada template dari Kurikulum.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection