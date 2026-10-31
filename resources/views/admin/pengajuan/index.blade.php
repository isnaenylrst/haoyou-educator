@extends($layout)

@section('title', 'Rekap Pengajuan Guru & Bahan Ajar')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-warning me-2"></i> Rekap Pengajuan Bahan Ajar & Reimburse</h4>
            <p class="text-muted small mb-0">Monitoring dan persetujuan pengajuan reimburse bahan ajar, pengadaan buku/kit, dan sertifikat.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Pengajuan</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ $totalCount }}</div>
                    </div>
                    <div class="rounded-3 bg-light p-3 text-secondary">
                        <i class="fa-solid fa-folder-open fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Menunggu Review</div>
                        <div class="fs-4 fw-bold text-warning mt-1">{{ $totalPending }}</div>
                    </div>
                    <div class="rounded-3 bg-warning-subtle p-3 text-warning">
                        <i class="fa-solid fa-hourglass-half fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Telah Disetujui</div>
                        <div class="fs-4 fw-bold text-success mt-1">{{ $totalApproved }}</div>
                    </div>
                    <div class="rounded-3 bg-success-subtle p-3 text-success">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Nilai Disetujui</div>
                        <div class="fs-5 fw-bold text-primary mt-1">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                    </div>
                    <div class="rounded-3 bg-primary-subtle p-3 text-primary">
                        <i class="fa-solid fa-money-bill-wave fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER FORM --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Diajukan" @selected($status === 'Diajukan')>Diajukan</option>
                        <option value="Review Kurikulum" @selected($status === 'Review Kurikulum')>Review Kurikulum</option>
                        <option value="Review Owner" @selected($status === 'Review Owner')>Review Owner</option>
                        <option value="Disetujui" @selected($status === 'Disetujui')>Disetujui</option>
                        <option value="Ditolak" @selected($status === 'Ditolak')>Ditolak</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua Jenis Pengajuan</option>
                        <option value="Reimburse Bahan Ajar" @selected($type === 'Reimburse Bahan Ajar')>Reimburse Bahan Ajar</option>
                        <option value="Pembelian Bahan Ajar" @selected($type === 'Pembelian Bahan Ajar')>Pembelian Bahan Ajar</option>
                        <option value="Pembelian Sertifikat" @selected($type === 'Pembelian Sertifikat')>Pembelian Sertifikat</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="teacher_id" class="form-select form-select-sm">
                        <option value="">Semua Guru Pengaju</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" @selected($teacherId == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3 fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL REKAP --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Guru Pengaju</th>
                        <th>Jenis Pengajuan</th>
                        <th>Item / Barang</th>
                        <th>Nominal</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Verifikator</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $sub)
                        <tr>
                            <td><span class="text-muted small">#{{ $sub->id }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $sub->teacher->name ?? $sub->creator->display_name ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $sub->type }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $sub->item_name }}</div>
                                <small class="text-muted">{{ Str::limit($sub->description, 50) ?: '-' }}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
                            </td>
                            <td>{{ $sub->date->translatedFormat('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $sub->status_badge }}">{{ $sub->status }}</span>
                            </td>
                            <td>
                                <div class="small">
                                    @if($sub->curriculum_approved_at)
                                        <div><i class="fa-solid fa-check text-success me-1"></i> Kurikulum: {{ $sub->curriculum->display_name ?? 'OK' }}</div>
                                    @endif
                                    @if($sub->owner_approved_at)
                                        <div><i class="fa-solid fa-check-double text-primary me-1"></i> Owner: {{ $sub->owner->display_name ?? 'OK' }}</div>
                                    @endif
                                    @if(!$sub->curriculum_approved_at && !$sub->owner_approved_at)
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Cetak / Dokumen TTD">
                                        <i class="fa-solid fa-file-invoice"></i> Dokumen
                                    </a>

                                    @if($isOwner && $sub->status === 'Review Owner')
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-2 text-white fw-semibold" data-bs-toggle="modal" data-bs-target="#modalOwnerApprove{{ $sub->id }}">
                                            <i class="fa-solid fa-signature me-1"></i> Approve Owner
                                        </button>
                                    @endif
                                </div>

                                {{-- MODAL OWNER APPROVAL --}}
                                @if($isOwner && $sub->status === 'Review Owner')
                                    <div class="modal fade" id="modalOwnerApprove{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog text-start">
                                            <div class="modal-content rounded-4 border-0">
                                                <form action="{{ route('owner.pengajuan.approve', $sub->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold">Persetujuan Owner - {{ $sub->item_name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="p-3 bg-light rounded-3 mb-3 border">
                                                            <div><strong>Nominal:</strong> <span class="text-success fw-bold">{{ $sub->formatted_amount }}</span></div>
                                                            <div><strong>Guru:</strong> {{ $sub->teacher->name ?? '-' }}</div>
                                                            <div><strong>Disetujui Kurikulum:</strong> {{ $sub->curriculum_approved_at?->translatedFormat('d M Y') }}</div>
                                                        </div>

                                                        <div class="alert alert-info py-2 px-3 small mb-3">
                                                            <i class="fa-solid fa-signature me-1"></i> Tanda tangan Anda (Owner) yang tersimpan di profil akan otomatis ditempelkan pada lembar dokumen pencairan.
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Keputusan</label>
                                                            <select name="decision" class="form-select" required>
                                                                <option value="Approved">Setujui Pencairan (Approved)</option>
                                                                <option value="Rejected">Tolak Pengajuan (Rejected)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Catatan Owner (opsional)</label>
                                                            <textarea name="note" rows="2" class="form-control" placeholder="Catatan persetujuan..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary rounded-pill fw-semibold px-4">
                                                            Simpan Keputusan & TTD
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-receipt fs-2 mb-2 text-secondary d-block"></i>
                                Tidak ada data pengajuan yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top bg-light">
            {{ $submissions->links() }}
        </div>
    </div>
</div>
@endsection

