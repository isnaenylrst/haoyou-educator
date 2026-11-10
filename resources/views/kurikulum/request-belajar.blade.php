@extends('layouts.kurikulum')

@section('title', 'Request Belajar Siswa')

@section('content')
<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-book-open-reader text-warning me-2"></i> Request Belajar Siswa
            </h4>
            <p class="text-muted small mb-0">Daftar permintaan materi/topik khusus siswa dan catatan konsultasi admin untuk penyesuaian kurikulum mengajar.</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="{{ route('kurikulum.request-belajar') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Filter Siswa</label>
                    <select name="student_id" class="form-select form-select-sm">
                        <option value="">Semua Siswa</option>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected($studentId == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filter Kelas</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cari Topik / Catatan</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Ketik topik...">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill rounded-pill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('kurikulum.request-belajar') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- CARDS / LIST --}}
    <div class="row g-4">
        @forelse ($requests as $req)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-1 rounded-pill small">
                            {{ $req->topic }}
                        </span>
                        <small class="text-muted">{{ $req->created_at->diffForHumans() }}</small>
                    </div>

                    <h5 class="fw-bold text-dark mt-2 mb-1">
                        <i class="fa-solid fa-user-graduate text-secondary me-2"></i> {{ $req->student->name ?? '-' }}
                    </h5>

                    <div class="small text-muted mb-3">
                        @if ($req->class)
                            <i class="fa-solid fa-chalkboard text-warning me-1"></i> Kelas: {{ $req->class->class_name }}
                            @if ($req->class->teacher)
                                <div><i class="fa-solid fa-chalkboard-user me-1 text-primary"></i> Guru: {{ $req->class->teacher->name }}</div>
                            @endif
                        @else
                            <i class="fa-solid fa-circle-info text-secondary me-1"></i> Tanpa Kelas Khusus
                        @endif
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3 flex-grow-1">
                        <small class="fw-bold text-dark d-block mb-1">
                            <i class="fa-solid fa-comment-dots text-primary me-1"></i> Catatan Konsultasi Admin:
                        </small>
                        <div class="small text-secondary" style="white-space: pre-line;">
                            {{ $req->consultation_notes ?: 'Tidak ada catatan tambahan.' }}
                        </div>
                    </div>

                    <div class="pt-2 border-top d-flex justify-content-between align-items-center text-muted small">
                        <span>Diinput: {{ $req->creator->display_name ?? 'Admin' }}</span>
                        <span>{{ $req->created_at->translatedFormat('d M Y') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
                    <i class="fa-solid fa-book-open-reader fs-1 text-secondary mb-3 d-block"></i>
                    Belum ada data request belajar dari siswa.
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>

</div>
@endsection
