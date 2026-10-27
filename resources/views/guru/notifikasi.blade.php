@extends('layouts.guru')

@section('title','Notifikasi')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Notifikasi</h2>
        <p class="text-muted mb-0">
            Semua pemberitahuan untuk Anda —
            hasil review LP/PPT/Jurnal/Progress/Cuti, tugas perbaikan kelas, penugasan kelas & surat dari Kepala Kurikulum
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Card --}}
    <div class="card border-0 shadow-sm rounded-4">

        @forelse ($notifications as $notif)

            @php
                $isNew = $notif['unread'] ?? ($notif['time'] && $notif['time']->diffInDays(now()) <= 3);
            @endphp

            <div class="d-flex justify-content-between align-items-center gap-3 p-3 {{ !$loop->last ? 'border-bottom' : '' }}">

                <div class="flex-grow-1">

                    <h6 class="fw-semibold mb-1">
                        {{ $notif['title'] }}
                    </h6>

                    <small class="text-muted">
                        {{ $notif['category'] }}
                        •
                        {{ $notif['time'] ? $notif['time']->diffForHumans() : '-' }}
                    </small>

                    @if (!empty($notif['note']))
                        <div class="small mt-2 border-start border-3 ps-2">
                            {!! nl2br(e($notif['note'])) !!}
                        </div>
                    @endif

                    @if (($notif['action']['kind'] ?? null) === 'task')
                        <form method="POST" action="{{ $notif['action']['url'] }}" class="d-flex gap-2 mt-2">
                            @csrf
                            <input type="text" name="teacher_note" maxlength="1000"
                                   class="form-control form-control-sm" placeholder="Catatan perbaikan (opsional)">
                            <button class="btn btn-success btn-sm text-nowrap">Tandai Selesai</button>
                        </form>
                    @elseif (($notif['action']['kind'] ?? null) === 'link')
                        <a href="{{ $notif['action']['url'] }}" class="btn btn-outline-primary btn-sm mt-2">
                            {{ $notif['action']['label'] }}
                        </a>
                    @endif

                </div>

                @if (($notif['action']['kind'] ?? null) === 'done')
                    <span class="badge rounded-pill bg-success px-3 py-2">Selesai</span>
                @elseif ($isNew)
                    <span class="badge rounded-pill text-warning border border-warning px-3 py-2 bg-white">
                        Baru
                    </span>
                @else
                    <span class="badge rounded-pill bg-light text-secondary border px-3 py-2">
                        Sudah Dibaca
                    </span>
                @endif

            </div>

        @empty

            <div class="text-center text-muted py-5">
                Belum ada notifikasi.
            </div>

        @endforelse

    </div>

</div>

@endsection