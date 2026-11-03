@extends('layouts.owner')

@section('title', 'Antrean Tanda Tangan Digital')

@section('content')
<div class="container-fluid p-0">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Antrean Tanda Tangan Digital</h3>
            <p class="text-muted mb-0">Pengesahan tanda tangan Owner untuk Progress Report dan Sertifikat Siswa.</p>
        </div>
        <div>
            @if($ownerSignature)
                <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="fa-solid fa-signature me-1"></i> TTD Digital Siap
                </div>
            @else
                <a href="{{ route('profil.index') }}" class="btn btn-warning rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Upload TTD Digital di Profil
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-4 shadow-sm border" id="ttdTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold {{ $tab === 'reports' ? 'active' : '' }}" href="{{ route('owner.ttd.index', ['tab' => 'reports']) }}">
                <i class="fa-solid fa-chart-line me-1"></i> Progress Report
                @if($pendingReports->total() > 0)
                    <span class="badge bg-danger ms-1">{{ $pendingReports->total() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold {{ $tab === 'certs' ? 'active' : '' }}" href="{{ route('owner.ttd.index', ['tab' => 'certs']) }}">
                <i class="fa-solid fa-certificate me-1"></i> Sertifikat
                @if($pendingCertificates->total() > 0)
                    <span class="badge bg-danger ms-1">{{ $pendingCertificates->total() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold {{ $tab === 'submissions' ? 'active' : '' }}" href="{{ route('owner.ttd.index', ['tab' => 'submissions']) }}">
                <i class="fa-solid fa-receipt me-1"></i> Pengajuan Reimburse & Bahan
                @if(($pendingSubmissions->total() ?? 0) > 0)
                    <span class="badge bg-danger ms-1">{{ $pendingSubmissions->total() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('owner.ttd.index', ['tab' => 'history']) }}">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat TTD
            </a>
        </li>
    </ul>

    {{-- Tab 1: Progress Report --}}
    @if($tab === 'reports')
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form id="bulkReportForm" method="POST" action="{{ route('owner.ttd.report.bulk') }}">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold text-dark fs-5">
                            Menunggu TTD Owner ({{ $pendingReports->total() }})
                        </div>
                        @if($pendingReports->count() > 0)
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="return confirm('Tandatangani semua progress report yang dipilih?')">
                                <i class="fa-solid fa-signature me-1"></i> TTD Terpilih (Massal)
                            </button>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllReports">
                                    </th>
                                    <th>Siswa</th>
                                    <th>Kelas & Periode</th>
                                    <th>Guru</th>
                                    <th>Review Kurikulum</th>
                                    <th>Dokumen PDF</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingReports as $report)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="report_ids[]" value="{{ $report->id }}" class="form-check-input report-checkbox">
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $report->student?->name ?? 'Siswa #' . $report->student_id }}</div>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $report->report_type ?: 'Academic Report' }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $report->enrollment?->class?->class_name ?? '-' }}</div>
                                            <small class="text-muted">{{ $report->report_period ?: '-' }}</small>
                                        </td>
                                        <td>{{ $report->teacher?->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="fa-solid fa-circle-check me-1"></i> Di-ACC Kurikulum
                                            </span>
                                            <div class="small text-muted">{{ $report->reviewed_at?->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td>
                                            @if($report->file_path && Storage::disk('public')->exists($report->file_path))
                                                <a href="{{ asset('storage/' . $report->file_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> Lihat PDF
                                                </a>
                                            @else
                                                <span class="text-muted small">Belum di-generate</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="document.getElementById('sign-report-{{ $report->id }}').submit();">
                                                <i class="fa-solid fa-pen-nib me-1"></i> TTD
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-circle-check fs-1 text-success d-block mb-3"></i>
                                            Semua progress report yang di-ACC Kurikulum sudah ditandatangani!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                @foreach($pendingReports as $report)
                    <form id="sign-report-{{ $report->id }}" action="{{ route('owner.ttd.report', $report->id) }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                @endforeach

                <div class="mt-4">
                    {{ $pendingReports->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Tab 2: Sertifikat --}}
    @if($tab === 'certs')
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form id="bulkCertForm" method="POST" action="{{ route('owner.ttd.certificate.bulk') }}">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold text-dark fs-5">
                            Menunggu TTD Owner ({{ $pendingCertificates->total() }})
                        </div>
                        @if($pendingCertificates->count() > 0)
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="return confirm('Tandatangani semua sertifikat yang dipilih?')">
                                <i class="fa-solid fa-signature me-1"></i> TTD Terpilih (Massal)
                            </button>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllCerts">
                                    </th>
                                    <th>Nomor Sertifikat</th>
                                    <th>Siswa</th>
                                    <th>Kelas / Course</th>
                                    <th>Nilai</th>
                                    <th>Tanggal Review</th>
                                    <th>Dokumen PDF</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingCertificates as $cert)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="certificate_ids[]" value="{{ $cert->id }}" class="form-check-input cert-checkbox">
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $cert->certificate_number ?: 'CERT-' . $cert->id }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $cert->student_name ?: ($cert->student?->name ?? '-') }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $cert->course_name ?: ($cert->level_name ?: '-') }}</div>
                                            <small class="text-muted">{{ $cert->enrollment?->class?->class_name ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                {{ $cert->score ? number_format($cert->score, 1) : ($cert->total_score ? number_format($cert->total_score, 1) : '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $cert->reviewed_at?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td>
                                            @if($cert->file_path && Storage::disk('public')->exists($cert->file_path))
                                                <a href="{{ asset('storage/' . $cert->file_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> Lihat PDF
                                                </a>
                                            @else
                                                <span class="text-muted small">Belum di-generate</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="document.getElementById('sign-cert-{{ $cert->id }}').submit();">
                                                <i class="fa-solid fa-pen-nib me-1"></i> TTD
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-circle-check fs-1 text-success d-block mb-3"></i>
                                            Semua sertifikat yang di-ACC sudah ditandatangani!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                @foreach($pendingCertificates as $cert)
                    <form id="sign-cert-{{ $cert->id }}" action="{{ route('owner.ttd.certificate', $cert->id) }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                @endforeach

                <div class="mt-4">
                    {{ $pendingCertificates->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Pengajuan Reimburse & Bahan Ajar --}}
    @if($tab === 'submissions')
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="fw-bold text-dark fs-5 mb-3">
                    Pengajuan Menunggu Persetujuan & TTD Owner ({{ $pendingSubmissions->total() }})
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Pengaju (Guru)</th>
                                <th>Jenis & Barang</th>
                                <th>Nominal</th>
                                <th>Status Review</th>
                                <th>Bukti / Dokumen</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingSubmissions as $sub)
                                <tr>
                                    <td>{{ $sub->date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $sub->teacher?->name ?? 'Guru' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary mb-1">{{ $sub->type }}</span>
                                        <div class="fw-semibold text-dark">{{ $sub->item_name }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
                                    </td>
                                    <td>
                                        @if($sub->curriculum_approved_at)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="fa-solid fa-check me-1"></i> Di-ACC Kurikulum
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                Menunggu Owner
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sub->receipt_path && Storage::disk('public')->exists($sub->receipt_path))
                                            <a href="{{ asset('storage/' . $sub->receipt_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                <i class="fa-solid fa-file-invoice text-primary me-1"></i> Lihat Dokumen / Bukti
                                            </a>
                                        @elseif($sub->photo_path && Storage::disk('public')->exists($sub->photo_path))
                                            <a href="{{ asset('storage/' . $sub->photo_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                <i class="fa-solid fa-image text-primary me-1"></i> Lihat Foto
                                            </a>
                                        @else
                                            <span class="text-muted small">Tidak ada lampiran</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('owner.ttd.submission', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui dan tandatangani pengajuan ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="fa-solid fa-pen-nib me-1"></i> Setujui & TTD
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-circle-check fs-1 text-success d-block mb-3"></i>
                                        Tidak ada pengajuan yang menunggu tanda tangan Owner!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $pendingSubmissions->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Tab 3: Riwayat TTD --}}
    @if($tab === 'history')
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="fw-bold text-dark fs-5 mb-3">
                    Riwayat Penandatanganan Dokumen
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu TTD</th>
                                <th>Jenis Dokumen</th>
                                <th>Penandatangan</th>
                                <th>Peran</th>
                                <th>Catatan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($signatureHistories as $item)
                                <tr>
                                    <td>{{ $item->signed_at?->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        @if($item->document_type === 'progress_report')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="fa-solid fa-chart-line me-1"></i> Progress Report #{{ $item->document_id }}
                                            </span>
                                        @elseif($item->document_type === 'certificate')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                <i class="fa-solid fa-certificate me-1"></i> Sertifikat #{{ $item->document_id }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $item->document_type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $item->signer?->display_name ?? $item->signer?->username ?? 'User #' . $item->signer_id }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">{{ $item->signer_role }}</span>
                                    </td>
                                    <td>{{ $item->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        Belum ada riwayat penandatanganan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $signatureHistories->links() }}
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAllReports = document.getElementById('checkAllReports');
    if (checkAllReports) {
        checkAllReports.addEventListener('change', function () {
            document.querySelectorAll('.report-checkbox').forEach(cb => cb.checked = checkAllReports.checked);
        });
    }

    const checkAllCerts = document.getElementById('checkAllCerts');
    if (checkAllCerts) {
        checkAllCerts.addEventListener('change', function () {
            document.querySelectorAll('.cert-checkbox').forEach(cb => cb.checked = checkAllCerts.checked);
        });
    }
});
</script>
@endsection

