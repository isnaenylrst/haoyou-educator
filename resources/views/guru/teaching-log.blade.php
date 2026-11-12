@extends('layouts.guru')

@section('title', 'Teaching Log')

@section('content')
<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-clipboard-list text-warning me-2"></i> Teaching Log</h2>
            <p class="text-muted mb-0">Ringkasan status pengisian Jurnal Mengajar & Progress Report per kelas dan periode.</p>
        </div>

        {{-- FILTER FORM --}}
        <form method="GET" action="{{ route('teacher.teaching-log') }}" class="d-flex flex-wrap gap-2">
            <select name="class_id" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="">Semua Kelas Saya</option>
                @foreach($myClasses as $c)
                    <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->class_name }}</option>
                @endforeach
            </select>

            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width: 160px;" onchange="this.form.submit()">

            <button type="submit" class="btn btn-dark btn-sm rounded-pill px-3">Filter</button>
        </form>
    </div>

    {{-- RINGKASAN STATUS KARTU --}}
    <div class="row g-3 mb-4">
        {{-- BLOK JURNAL --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-book-open text-warning me-2"></i> Ringkasan Jurnal Mengajar
                    </h6>
                    <span class="badge bg-light text-dark border">Total: {{ $stats['total_sessions'] }} Sesi</span>
                </div>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-3 rounded-3 bg-success-subtle">
                            <div class="fs-4 fw-bold text-success">{{ $stats['journal_filled'] }}</div>
                            <small class="text-muted fw-semibold">Sudah Diisi</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-3 bg-warning-subtle">
                            <div class="fs-4 fw-bold text-warning">{{ $stats['journal_unfilled'] }}</div>
                            <small class="text-muted fw-semibold">Belum Diisi</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-3 bg-danger-subtle">
                            <div class="fs-4 fw-bold text-danger">{{ $stats['journal_late'] }}</div>
                            <small class="text-muted fw-semibold">Terlambat (>24j)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BLOK PROGRESS REPORT --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-chart-column text-warning me-2"></i> Ringkasan Progress Report Siswa
                    </h6>
                    <span class="badge bg-light text-dark border">Total: {{ $stats['total_students'] }} Siswa Aktif</span>
                </div>
                <div class="row g-2 text-center">
                    <div class="col-3">
                        <div class="p-3 rounded-3 bg-success-subtle">
                            <div class="fs-4 fw-bold text-success">{{ $stats['pr_approved'] }}</div>
                            <small class="text-muted fw-semibold" style="font-size: 11px;">Disetujui (ACC)</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 rounded-3 bg-info-subtle">
                            <div class="fs-4 fw-bold text-primary">{{ $stats['pr_submitted'] }}</div>
                            <small class="text-muted fw-semibold" style="font-size: 11px;">Diajukan</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 rounded-3 bg-secondary-subtle">
                            <div class="fs-4 fw-bold text-secondary">{{ $stats['pr_draft'] }}</div>
                            <small class="text-muted fw-semibold" style="font-size: 11px;">Draft Belum Diajukan</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 rounded-3 bg-danger-subtle">
                            <div class="fs-4 fw-bold text-danger">{{ $stats['pr_unfilled'] }}</div>
                            <small class="text-muted fw-semibold" style="font-size: 11px;">Belum Diisi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DAFTAR PERTEMUAN DAN STATUS JELAS --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Pertemuan & Status Pelaksanaan</h5>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">Tanggal & Hari</th>
                            <th style="width: 20%;">Kelas</th>
                            <th style="width: 12%;">Jam Sesi</th>
                            <th style="width: 15%;">Status Jurnal</th>
                            <th style="width: 15%;">Status Progress Report</th>
                            <th style="width: 18%;" class="text-end">Tautan Pengisian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedSessions as $s)
                            <tr>
                                <td>{{ $loop->iteration + ($paginatedSessions->currentPage() - 1) * $paginatedSessions->perPage() }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $s->date->translatedFormat('d M Y') }}</div>
                                    <small class="text-muted">{{ $s->day }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $s->class_name }}</div>
                                    @if($s->meeting_number !== '-')
                                        <small class="text-muted">Pertemuan ke-{{ $s->meeting_number }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $s->start_time }} – {{ $s->end_time }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $s->journal_badge }} px-3 py-2 rounded-pill">
                                        @if($s->journal_status === 'Terisi')
                                            <i class="fa-solid fa-check me-1"></i> Terisi
                                        @elseif($s->journal_status === 'Terlambat')
                                            <i class="fa-solid fa-lock me-1"></i> Terlambat
                                        @else
                                            <i class="fa-regular fa-clock me-1"></i> Belum Diisi
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $s->pr_badge }} px-3 py-2 rounded-pill">
                                        {{ $s->pr_status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        @if($s->journal_status === 'Terisi')
                                            <a href="{{ route('teacher.attendance', ['date' => $s->date->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Lihat Jurnal">
                                                <i class="fa-solid fa-eye me-1"></i> Jurnal
                                            </a>
                                        @else
                                            <a href="{{ route('teacher.attendance', ['date' => $s->date->format('Y-m-d')]) }}" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold" title="Isi Jurnal">
                                                <i class="fa-solid fa-pencil me-1"></i> Isi Jurnal
                                            </a>
                                        @endif

                                        <a href="{{ route('teacher.progress-report', ['class_id' => $s->class_id]) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3" title="Buka Progress Report Kelas">
                                            <i class="fa-solid fa-chart-column me-1"></i> Report
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-calendar-xmark fs-2 text-secondary d-block mb-2"></i>
                                    Tidak ada jadwal pertemuan mengajar pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $paginatedSessions->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
