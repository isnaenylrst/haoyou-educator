@extends('layouts.guru')

@section('title', 'Request Belajar Siswa')

@section('content')
<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-graduation-cap text-warning me-2"></i> Request Belajar Siswa
            </h4>
            <p class="text-muted small mb-0">
                Kebutuhan materi tambahan, permintaan khusus, dan catatan konsultasi belajar dari siswa/wali pada kelas yang Anda ampu.
            </p>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('teacher.request-belajar') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" value="{{ $q }}" class="form-control border-start-0" placeholder="Cari topik, catatan, atau nama siswa...">
                    </div>
                </div>

                <div class="col-md-4">
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kelas Saya</option>
                        @foreach ($myClasses as $c)
                            <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>
                                {{ $c->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-warning rounded-3 fw-bold flex-fill text-dark">
                        Filter
                    </button>
                    @if($q || $selectedClassId)
                        <a href="{{ route('teacher.request-belajar') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR REQUEST BELAJAR --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white py-3 fw-bold border-bottom d-flex justify-content-between align-items-center">
            <span>Daftar Permintaan Belajar ({{ $requests->total() }})</span>
            <span class="badge bg-light text-dark border">Total: {{ $totalRequests }} Permintaan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 20%;">Nama Siswa</th>
                        <th style="width: 20%;">Kelas</th>
                        <th style="width: 25%;">Topik Kebutuhan Belajar</th>
                        <th>Catatan Konsultasi / Request</th>
                        <th style="width: 12%;">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $idx => $req)
                        <tr>
                            <td class="text-center text-muted">{{ $requests->firstItem() + $idx }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $req->student->name ?? 'Siswa' }}</div>
                                <small class="text-muted">{{ $req->student->student_number ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                    <i class="fa-solid fa-chalkboard me-1"></i> {{ $req->class->class_name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $req->topic ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="small text-secondary">{{ $req->consultation_notes ?: '-' }}</div>
                                @if($req->creator)
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        Dicatat oleh: {{ $req->creator->name ?? 'Admin/Kurikulum' }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $req->created_at->translatedFormat('d M Y') }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-graduation-cap fs-2 mb-2 text-secondary d-block"></i>
                                Belum ada request kebutuhan belajar untuk siswa pada kelas yang Anda ampu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

