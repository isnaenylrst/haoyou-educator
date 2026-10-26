@extends('layouts.guru')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">Progress Report</h2>
            <p class="text-muted">
                Isi nilai kompetensi setiap siswa (Achievement Level dan nilai latihan). PDF dibuat otomatis,
                lalu kirim ke Kurikulum. Batas pengisian maksimal H+3 setelah pertemuan terakhir.
                Siswa baru bisa melihat report setelah disetujui Kurikulum.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- PILIH KELAS --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('teacher.progress-report') }}">
                <label class="form-label fw-semibold">Pilih Kelas</label>

                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    @forelse($classes as $c)
                        <option value="{{ $c->id }}" @selected($c->id == $classId)>
                            {{ $c->class_name }}
                        </option>
                    @empty
                        <option>Belum ada kelas yang diampu</option>
                    @endforelse
                </select>

                <small class="text-muted">
                    Daftar siswa akan mengikuti kelas yang dipilih.
                </small>
            </form>
        </div>
    </div>

    {{-- DAFTAR SISWA --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">

            <div class="d-flex justify-content-between">
                <div>
                    <h5 class="fw-bold">Progress Report Per Siswa</h5>
                    <div class="text-muted">Siklus 3 Bulan</div>
                </div>
            </div>

            <hr>

            @forelse($students as $s)
                <div class="row align-items-center py-3 {{ !$loop->last ? 'border-bottom' : '' }}">

                    <div class="col-md-3">
                        <strong>{{ $s->name }}</strong>
                        <div class="text-muted small">
                            Kehadiran {{ $s->present }}/{{ $s->total }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        @if(!$s->report)
                            <span class="badge bg-warning text-dark">Belum Dibuat</span>
                        @elseif($s->report->is_approved)
                            <span class="badge bg-success">Disetujui Kurikulum</span>
                            <div class="text-muted small">{{ $s->report->report_period }}</div>
                        @elseif($s->report->is_pending_review)
                            <span class="badge bg-info text-dark">Menunggu Review</span>
                            <div class="text-muted small">{{ $s->report->report_period }}</div>
                        @elseif($s->report->is_returned)
                            <span class="badge bg-danger">Dikembalikan</span>
                            <div class="text-danger small">{{ $s->report->review_note ?: 'Perbaiki lalu kirim ulang.' }}</div>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                            <div class="text-muted small">{{ $s->report->report_period }}</div>
                        @endif

                        @if($s->deadline)
                            <div class="small {{ $s->is_late ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $s->is_late ? 'Lewat batas' : 'Batas' }} {{ $s->deadline->translatedFormat('d M Y') }}
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6 text-md-end mt-2 mt-md-0">

                        @if($s->is_late)
                            <span class="btn btn-secondary btn-sm disabled">Terkunci (lewat H+3)</span>
                        @else
                            <a href="{{ route('teacher.progress-report.form', $s->enrollment_id) }}"
                               class="btn btn-primary btn-sm">
                                {{ !$s->report ? 'Buat Report' : ($s->report->status === 'Draft' ? 'Lanjut Edit' : 'Report Baru') }}
                            </a>
                        @endif

                        @if($s->report && $s->report->file_path)
                            <a href="{{ route('teacher.progress-report.download', $s->report->id) }}"
                               class="btn btn-outline-secondary btn-sm">
                                Unduh PDF
                            </a>
                        @endif

                        @include('guru.partials.sertifikat-aksi', ['s' => $s])

                    </div>
                </div>
            @empty
                <div class="text-muted py-3">Belum ada siswa di kelas ini.</div>
            @endforelse

            <hr>

            <form method="POST" action="{{ route('teacher.progress-report.submit-all') }}">
                @csrf
                <button class="btn btn-success">Kirim Semua Draft ke Kurikulum</button>
            </form>

        </div>
    </div>

    {{-- RIWAYAT --}}
    <div class="card shadow-sm border-0 rounded-4">

        <div class="card-header bg-white fw-bold">Riwayat Progress Report</div>

        <div class="list-group list-group-flush">
            @forelse($history as $h)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ optional($h->student)->name }}</strong>
                            <br>
                            <small class="text-muted">
                                {{ $h->report_period }} · {{ $h->created_at?->format('d M Y') }}
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if($h->is_approved)
                                <span class="badge bg-success">Disetujui</span>
                            @elseif($h->is_pending_review)
                                <span class="badge bg-info text-dark">Menunggu Review</span>
                            @elseif($h->is_returned)
                                <span class="badge bg-danger">Dikembalikan</span>
                            @else
                                <span class="badge bg-secondary">Draft</span>
                            @endif

                            <a href="{{ route('teacher.progress-report.download', $h->id) }}"
                               class="btn btn-outline-secondary btn-sm">Unduh</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted">Belum ada riwayat.</div>
            @endforelse
        </div>

    </div>

</div>

@endsection