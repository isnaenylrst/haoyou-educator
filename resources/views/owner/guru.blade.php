@extends('layouts.owner')

@section('title', 'Akses Area Guru')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Akses Area Guru</h2>
        <p class="text-muted mb-0">
            Pilih guru untuk membuka seluruh menu Guru (Dashboard, Kelas, Attendance, Progress Report,
            Sertifikat, Schedule, dan lainnya) sebagai guru tersebut.
        </p>
    </div>

    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span>Guru Aktif</span>
            <span class="badge bg-primary">{{ $teachers->count() }} Guru</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama Guru</th>
                        <th class="text-center">Jumlah Kelas</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($teachers as $t)
                        <tr>
                            <td class="fw-semibold">
                                {{ $t->name }}
                                @if ((int) $current === (int) $t->id)
                                    <span class="badge bg-success ms-1">Sedang dibuka</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $t->classes_count }}</td>
                            <td><span class="badge bg-success">{{ $t->status }}</span></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('owner.guru.view', $t) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-right-to-bracket me-1"></i> Buka sebagai guru
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada guru aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($current)
        <form method="POST" action="{{ route('owner.guru.clear') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Keluar dari mode guru
            </button>
        </form>
    @endif

</div>

@endsection