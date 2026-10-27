@extends('layouts.guru')

@section('title','Surat')

@section('content')

@php
    $typeBadge = [
        'SURAT_LIBUR' => ['bg-warning text-dark', 'Surat Libur'],
        'SURAT_DINAS' => ['bg-primary', 'Surat Dinas'],
        'LOA'         => ['bg-success', 'LoA'],
    ];
@endphp

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                Surat
                @if ($unreadCount > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $unreadCount }} belum dibaca</span>
                @endif
            </h2>
            <p class="text-muted mb-0">Surat pemberitahuan dari Kepala Kurikulum — libur, dinas, dan LoA.</p>
        </div>

        @if ($unreadCount > 0)
            <form action="{{ route('teacher.surat.read-all') }}" method="POST">
                @csrf @method('PATCH')
                <button class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-check-double me-1"></i> Tandai semua dibaca
                </button>
            </form>
        @endif
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

    {{-- FILTER --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('teacher.surat', array_filter(['type' => $type])) }}"
           class="btn btn-sm rounded-pill {{ !$status ? 'btn-dark' : 'btn-outline-secondary' }}">Semua</a>
        <a href="{{ route('teacher.surat', array_filter(['status' => 'unread', 'type' => $type])) }}"
           class="btn btn-sm rounded-pill {{ $status === 'unread' ? 'btn-dark' : 'btn-outline-secondary' }}">
            Belum dibaca ({{ $unreadCount }})
        </a>
        @foreach (['SURAT_LIBUR' => 'Libur', 'SURAT_DINAS' => 'Dinas', 'LOA' => 'LoA'] as $key => $label)
            <a href="{{ route('teacher.surat', array_filter(['status' => $status, 'type' => $type === $key ? null : $key])) }}"
               class="btn btn-sm rounded-pill {{ $type === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- DAFTAR SURAT --}}
    <div class="card border-0 shadow-sm rounded-4">
        @forelse ($letters as $letter)
            @php
                $unread = $letter->my_read_at === null;
                $badge  = $typeBadge[$letter->document_type] ?? ['bg-secondary', 'Surat'];
            @endphp

            <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }} {{ $unread ? 'bg-warning-subtle' : '' }}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1">
                        <h5 class="fw-semibold mb-1">
                            {{ $letter->title }}
                            @if ($unread)
                                <span class="badge rounded-pill bg-warning text-dark ms-1">Baru</span>
                            @endif
                        </h5>
                        <div class="mb-2">
                            <span class="badge rounded-pill {{ $badge[0] }}">{{ $badge[1] }}</span>
                            <small class="text-muted ms-1">
                                {{ $letter->uploaded_at?->translatedFormat('l, d F Y H:i') }}
                                · dari {{ $letter->uploader->username ?? 'Kurikulum' }}
                            </small>
                        </div>

                        @if ($letter->description)
                            <div class="small border-start border-3 ps-3 my-2">{!! nl2br(e($letter->description)) !!}</div>
                        @endif

                        @unless ($unread)
                            <small class="text-muted">
                                Dibuka {{ \Illuminate\Support\Carbon::parse($letter->my_read_at)->translatedFormat('d M Y H:i') }}
                            </small>
                        @endunless
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @if ($letter->file_exists)
                            <a href="{{ route('teacher.surat.show', $letter) }}" target="_blank" class="btn btn-dark btn-sm">
                                <i class="fas fa-eye me-1"></i> Buka Surat
                            </a>
                            <a href="{{ route('teacher.surat.download', $letter) }}" class="btn btn-outline-dark btn-sm">
                                <i class="fas fa-download me-1"></i> Unduh
                            </a>
                        @else
                            <span class="badge bg-secondary align-self-center">File belum tersedia</span>
                        @endif

                        @if ($unread)
                            <form action="{{ route('teacher.surat.read', $letter) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-secondary btn-sm">Tandai dibaca</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fa-regular fa-envelope fa-2x mb-2"></i><br>
                @if ($status || $type)
                    Tidak ada surat yang cocok. <a href="{{ route('teacher.surat') }}">Lihat semua</a>
                @else
                    Belum ada surat dari Kurikulum.
                @endif
            </div>
        @endforelse

        @if ($letters->hasPages())
            <div class="p-3 border-top">{{ $letters->links() }}</div>
        @endif
    </div>

</div>

@endsection