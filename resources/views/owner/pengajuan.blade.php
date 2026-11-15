@extends('layouts.owner')

@section('title', 'Rekap Pengajuan Bahan Ajar & Reimburse')

@push('styles')
<style>
    /* Styling khusus Rekap Pengajuan Owner (Tampilan Persetujuan) */
    .stat-row-owner-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    @media (max-width: 992px) {
        .stat-row-owner-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 576px) {
        .stat-row-owner-4 {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        background: var(--surface, #fff);
        border: 1px solid var(--line, #ececf2);
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .03);
        border-left: 4px solid var(--muted, #8b8fa3);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .stat-card.c-green  { border-left-color: #22a05a; }
    .stat-card.c-red    { border-left-color: #ef4444; }
    .stat-card.c-blue   { border-left-color: #3b82f6; }
    .stat-card.c-yellow { border-left-color: #e8a317; }

    .stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--muted, #8b8fa3);
    }
    .stat-icon {
        font-size: 16px;
        color: var(--muted, #8b8fa3);
    }
    .c-green .stat-icon  { color: #22a05a; }
    .c-red .stat-icon    { color: #ef4444; }
    .c-blue .stat-icon   { color: #3b82f6; }
    .c-yellow .stat-icon { color: #e8a317; }

    .stat-value {
        font-size: 20px;
        font-weight: 800;
        color: var(--text, #1d1d27);
        line-height: 1.25;
        margin-bottom: 4px;
        word-break: break-word;
    }
    .stat-note {
        font-size: 11.5px;
        color: var(--muted, #8b8fa3);
    }
    .stat-note.up   { color: #22a05a; }
    .stat-note.down { color: #ef4444; }
    .stat-note.warn { color: #e8a317; }

    /* Badges */
    .badge-pending   { background: rgba(234, 179, 8, .12); color: #ca8a04; }
    .badge-cold      { background: rgba(59, 130, 246, .12); color: #2563eb; }
    .badge-warning   { background: rgba(249, 115, 22, .12); color: #ea580c; }
    .badge-owner     { background: rgba(168, 85, 247, .12); color: #9333ea; }
    .badge-completed { background: rgba(34, 197, 94, .12); color: #16a34a; }
    .badge-paid      { background: rgba(20, 184, 166, .12); color: #0f766e; }
    .badge-done      { background: #e5e7eb; color: #111827; }
    .badge-hot       { background: rgba(239, 68, 68, .12); color: #dc2626; }
    .badge-neutral   { background: #f3f4f6; color: #4b5563; }

    /* Modals & Overlay */
    .x-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(17, 24, 39, .55);
        backdrop-filter: blur(2px);
        z-index: 1060;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .x-modal-overlay.open {
        display: flex;
    }
    .x-modal-box {
        background: var(--surface, #fff);
        color: var(--text, #1d1d27);
        border-radius: 16px;
        width: 100%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
        display: flex;
        flex-direction: column;
    }
    .x-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 18px 24px;
        border-bottom: 1px solid var(--line, #ececf2);
    }
    .x-modal-head h5 {
        margin: 0;
        font-weight: 700;
        font-size: 16px;
    }
    .x-modal-head p {
        margin: 2px 0 0;
        font-size: 12px;
        color: var(--muted, #8b8fa3);
    }
    .x-modal-body {
        padding: 20px 24px;
    }
    .x-modal-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 24px;
        border-top: 1px solid var(--line, #ececf2);
        background: var(--bg, #f6f7fb);
        border-radius: 0 0 16px 16px;
    }
    .x-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        background: var(--bg, #f6f7fb);
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 16px;
    }
    @media (max-width: 576px) {
        .x-detail-grid { grid-template-columns: 1fr; }
    }
    .x-detail-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--muted, #8b8fa3);
        text-transform: uppercase;
    }
    .x-detail-value {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text, #1d1d27);
    }
    .x-detail-full {
        grid-column: 1 / -1;
    }
    .x-signature-info {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 12.5px;
        margin-bottom: 14px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    [data-theme="dark"] .x-signature-info {
        background: #064e3b;
        border-color: #047857;
        color: #ecfdf5;
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="text-uppercase fw-bold text-muted small" style="letter-spacing: .08em;">KEUANGAN &amp; MANAJEMEN</div>
            <h2 class="fw-bold mb-1">Rekap Pengajuan Bahan Ajar &amp; Reimburse</h2>
            <p class="text-muted mb-0">Persetujuan pencairan dana, monitoring pengajuan yang di-ACC Kurikulum, dan verifikasi tanda tangan digital Owner.</p>
        </div>
        <div>
            <a href="{{ route('profil.index') }}" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
                <i class="fa-solid fa-signature me-1"></i> Pengaturan TTD Profil
            </a>
        </div>
    </div>

    {{-- ALERTS --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- 4 KARTU RINGKASAN OWNER (TAMPILAN PERSETUJUAN: HANYA 4 KARTU) --}}
    <div class="stat-row-owner-4">
        {{-- 1. Total Pengeluaran --}}
        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Total Pengeluaran</span>
                <i class="fa-solid fa-arrow-trend-down stat-icon"></i>
            </div>
            <div class="stat-value">
                {{ format_rupiah($totalPengeluaran ?? 0) }}
            </div>
            <div class="stat-note down">Reimburse &amp; pengadaan disetujui</div>
        </div>

        {{-- 2. Total Pengajuan --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Pengajuan</span>
                <i class="fa-solid fa-folder-open stat-icon"></i>
            </div>
            <div class="stat-value">{{ $totalCount }}</div>
            <div class="stat-note">Pengajuan sudah ACC Kurikulum</div>
        </div>

        {{-- 3. Perlu Review Owner --}}
        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Perlu Review Owner</span>
                <i class="fa-solid fa-hourglass-half stat-icon"></i>
            </div>
            <div class="stat-value">{{ $perluReviewOwner }}</div>
            <div class="stat-note {{ $perluReviewOwner > 0 ? 'warn' : '' }}">Status Review Owner</div>
        </div>

        {{-- 4. Telah Disetujui --}}
        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Telah Disetujui</span>
                <i class="fa-solid fa-circle-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $totalApproved }}</div>
            <div class="stat-note up">Disetujui, Dibayar &amp; Selesai</div>
        </div>
    </div>

    {{-- FILTER FORM (GAYA ADMIN YANG LAMA: STATUS, JENIS, NAMA PENGAJU, RENTANG TANGGAL, PENCARIAN) --}}
    <div class="card border rounded-4 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('owner.pengajuan.index') }}" class="row g-2 align-items-center" id="filterForm">
                <div class="col-md-3 col-sm-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Cari item, nama, catatan..." value="{{ $search ?? '' }}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach (['Review Owner', 'Disetujui', 'Review Admin', 'Dibayar', 'Selesai', 'Ditolak'] as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua Jenis Pengajuan</option>
                        @foreach (['Reimburse Bahan Ajar', 'Pembelian Bahan Ajar', 'Pembelian Sertifikat'] as $tp)
                            <option value="{{ $tp }}" @selected($type === $tp)>{{ $tp }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <select name="teacher_id" class="form-select form-select-sm">
                        <option value="">Semua Nama Pengaju</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected($teacherId == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_from" id="filterFrom" class="form-control" value="{{ $dateFrom }}" placeholder="Dari">
                        <span class="input-group-text">-</span>
                        <input type="date" name="date_to" id="filterTo" class="form-control" value="{{ $dateTo }}" placeholder="Sampai">
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL REKAP PENGAJUAN OWNER --}}
    <div class="card border rounded-4 shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama Pengaju</th>
                        <th class="text-center">Jenis</th>
                        <th>Item / Barang</th>
                        <th class="text-end">Nominal</th>
                        <th class="text-center">Tanggal</th>
                        <th class="text-center">Status &amp; TTD</th>
                        <th class="text-center" style="width: 190px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $stageMap = [
                            'Diajukan'         => 'badge-pending',
                            'Review Kurikulum' => 'badge-cold',
                            'Review Admin'     => 'badge-warning',
                            'Review Owner'     => 'badge-owner',
                            'Disetujui'        => 'badge-completed',
                            'Dibayar'          => 'badge-paid',
                            'Selesai'          => 'badge-done',
                            'Ditolak'          => 'badge-hot',
                        ];
                    @endphp

                    @forelse ($submissions as $sub)
                        @php
                            $teacherName = $sub->teacher->name ?? $sub->creator?->display_name ?? '-';
                            $badgeClass  = $stageMap[$sub->status] ?? 'badge-neutral';

                            $canOwnerApprove = in_array($sub->status, ['Review Owner', 'Review Admin']);
                            $canMarkPaid     = $sub->status === 'Disetujui';

                            $rejectStage = null;
                            if ($sub->status === 'Ditolak') {
                                if ($sub->owner_id) {
                                    $rejectStage = 'Ditolak di tahap Owner';
                                } elseif ($sub->admin_verified_at) {
                                    $rejectStage = 'Ditolak di tahap Admin';
                                } else {
                                    $rejectStage = 'Ditolak di tahap Kurikulum';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark">{{ $teacherName }}</div>
                                <small class="text-muted">ID: #SUB-{{ str_pad($sub->id, 4, '0', STR_PAD_LEFT) }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1">{{ $sub->type }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sub->item_name }}</div>
                                @if(!empty($sub->items) && is_array($sub->items))
                                    <small class="badge bg-light text-muted border me-1">
                                        {{ count($sub->items) }} Item
                                    </small>
                                @endif
                                @if($sub->description)
                                    <div class="small text-muted">{{ Str::limit($sub->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
                            </td>
                            <td class="text-center text-nowrap">
                                <div>{{ $sub->date?->translatedFormat('d M Y') ?? '-' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $badgeClass }} px-2 py-1">{{ $sub->status }}</span>

                                {{-- Tampilan info TTD Kurikulum & TTD Admin yang sudah ada --}}
                                <div class="d-flex flex-column gap-1 mt-1" style="font-size: 11px;">
                                    @if ($sub->curriculum_approved_at)
                                        <span class="text-success" title="ACC Kurikulum: {{ $sub->curriculum_approved_at->format('d/m/Y H:i') }}">
                                            <i class="fa-solid fa-circle-check"></i> TTD Kurikulum ({{ $sub->curriculum?->display_name ?? 'OK' }})
                                        </span>
                                    @endif
                                    @if ($sub->admin_verified_at)
                                        <span class="text-info" title="Verifikasi Admin: {{ $sub->admin_verified_at->format('d/m/Y H:i') }}">
                                            <i class="fa-solid fa-clipboard-check"></i> TTD Admin ({{ $sub->admin_signer_name ?? $sub->admin?->display_name ?? 'OK' }})
                                        </span>
                                    @endif
                                    @if ($sub->owner_approved_at)
                                        <span class="text-primary" title="Disetujui Owner: {{ $sub->owner_approved_at->format('d/m/Y H:i') }}">
                                            <i class="fa-solid fa-signature"></i> TTD Owner
                                        </span>
                                    @endif
                                    @if ($rejectStage)
                                        <span class="text-danger fw-semibold">{{ $rejectStage }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    {{-- Tombol Lihat/Cetak Dokumen --}}
                                    <a href="{{ route('owner.pengajuan.print', $sub->id) }}" target="_blank"
                                       class="btn btn-outline-secondary" title="Lihat Formulir Dokumen &amp; TTD">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>

                                    {{-- Baris Review Owner: Setujui + Tolak --}}
                                    @if ($canOwnerApprove)
                                        <button type="button" class="btn btn-warning text-dark"
                                                onclick="openOwnerModal('modalApprove{{ $sub->id }}')"
                                                title="Setujui / Tolak Pengajuan">
                                            <i class="fa-solid fa-signature"></i>
                                        </button>
                                    @endif

                                    {{-- Baris Disetujui: Tandai Dibayar --}}
                                    @if ($canMarkPaid)
                                        <form action="{{ route('owner.pengajuan.mark-paid', $sub->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Tandai pengajuan {{ e($sub->item_name) }} sebesar {{ $sub->formatted_amount }} telah dibayar/dicairkan?')">
                                            @csrf
                                            <button type="submit" class="btn btn-success" title="Tandai Sudah Dibayar">
                                                <i class="fa-solid fa-money-bill-wave"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fa-solid fa-receipt fs-2 text-secondary mb-2 d-block"></i>
                                Tidak ada pengajuan yang sesuai dengan filter atau kriteria Owner.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($submissions->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 px-3">
                <small class="text-muted">
                    Menampilkan {{ $submissions->firstItem() ?? 0 }} - {{ $submissions->lastItem() ?? 0 }} dari {{ $submissions->total() }} data
                </small>
                <div>
                    {{ $submissions->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

</div>

{{-- ==================== MODAL PERSETUJUAN OWNER ==================== --}}
@foreach ($submissions as $sub)
    @if (in_array($sub->status, ['Review Owner', 'Review Admin']))
        <div class="x-modal-overlay" id="modalApprove{{ $sub->id }}">
            <div class="x-modal-box">
                <form action="{{ route('owner.pengajuan.approve', $sub->id) }}" method="POST">
                    @csrf

                    <div class="x-modal-head">
                        <div>
                            <h5>Persetujuan &amp; Pencairan Pengajuan</h5>
                            <p>Tanda tangan Owner akan ditempelkan secara otomatis dari profil.</p>
                        </div>
                        <button type="button" class="btn-close" onclick="closeOwnerModal('modalApprove{{ $sub->id }}')" aria-label="Tutup"></button>
                    </div>

                    <div class="x-modal-body">
                        {{-- Ringkasan Pengajuan --}}
                        <div class="x-detail-grid">
                            <div>
                                <div class="x-detail-label">Pengaju</div>
                                <div class="x-detail-value">{{ $sub->teacher->name ?? $sub->creator?->display_name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="x-detail-label">Jenis</div>
                                <div class="x-detail-value">{{ $sub->type }}</div>
                            </div>
                            <div class="x-detail-full">
                                <div class="x-detail-label">Item / Keperluan</div>
                                <div class="x-detail-value">{{ $sub->item_name }}</div>
                            </div>
                            <div>
                                <div class="x-detail-label">Nominal</div>
                                <div class="x-detail-value text-success fs-6">{{ $sub->formatted_amount }}</div>
                            </div>
                            <div>
                                <div class="x-detail-label">Tanggal</div>
                                <div class="x-detail-value">{{ $sub->date?->translatedFormat('d M Y') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="x-detail-label">ACC Kurikulum</div>
                                <div class="x-detail-value">
                                    {{ $sub->curriculum_approved_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                    <span class="text-muted small">({{ $sub->curriculum->display_name ?? 'Kurikulum' }})</span>
                                </div>
                            </div>
                            <div>
                                <div class="x-detail-label">Verifikasi Admin</div>
                                <div class="x-detail-value">
                                    {{ $sub->admin_verified_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                    <span class="text-muted small">({{ $sub->admin->display_name ?? 'Admin' }})</span>
                                </div>
                            </div>
                            @if ($sub->admin_note)
                                <div class="x-detail-full">
                                    <div class="x-detail-label">Catatan Admin</div>
                                    <div class="small text-muted fst-italic">"{{ $sub->admin_note }}"</div>
                                </div>
                            @endif
                        </div>

                        {{-- Tautan Bukti Nota & Foto --}}
                        @if ($sub->receipt_path || $sub->photo_path)
                            <div class="d-flex gap-2 mb-3">
                                @if ($sub->receipt_path)
                                    <a href="{{ asset('storage/' . $sub->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-receipt me-1"></i> Cek Nota Pembelian
                                    </a>
                                @endif
                                @if ($sub->photo_path)
                                    <a href="{{ asset('storage/' . $sub->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fa-solid fa-image me-1"></i> Cek Foto Barang
                                    </a>
                                @endif
                            </div>
                        @endif

                        {{-- Info TTD Owner Profil --}}
                        <div class="x-signature-info">
                            <i class="fa-solid fa-signature fs-5 text-success"></i>
                            <div>
                                <strong>Tanda Tangan Digital Profil:</strong>
                                <div>Tanda tangan digital Owner yang tersimpan pada menu Profil akan otomatis dibubuhkan pada lembar pencairan.</div>
                            </div>
                        </div>

                        {{-- Form Keputusan --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Keputusan Owner <span class="text-danger">*</span></label>
                            <select name="decision" class="form-select form-select-sm" required id="decisionSelect{{ $sub->id }}" onchange="handleDecisionChange({{ $sub->id }})">
                                <option value="Approved">Setujui Pencairan (Approved)</option>
                                <option value="Rejected">Tolak Pengajuan (Rejected)</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small" id="noteLabel{{ $sub->id }}">Catatan Owner <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea name="note" id="noteInput{{ $sub->id }}" rows="2" class="form-control form-control-sm" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                            <small class="text-danger d-none" id="noteRequiredHelp{{ $sub->id }}">Catatan wajib diisi jika menolak pengajuan.</small>
                        </div>
                    </div>

                    <div class="x-modal-foot">
                        <a href="{{ route('owner.pengajuan.print', $sub->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-file-invoice me-1"></i> Lihat Lembar PDF
                        </a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="closeOwnerModal('modalApprove{{ $sub->id }}')">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary px-3" id="btnSubmit{{ $sub->id }}">
                                <i class="fa-solid fa-check me-1"></i> Simpan &amp; TTD
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

@push('scripts')
<script>
    function openOwnerModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('open');
        }
    }

    function closeOwnerModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('open');
        }
    }

    function handleDecisionChange(id) {
        const sel = document.getElementById('decisionSelect' + id);
        const note = document.getElementById('noteInput' + id);
        const label = document.getElementById('noteLabel' + id);
        const help = document.getElementById('noteRequiredHelp' + id);
        const btn = document.getElementById('btnSubmit' + id);

        if (sel.value === 'Rejected') {
            note.setAttribute('required', 'required');
            label.innerHTML = 'Alasan Penolakan <span class="text-danger">*</span>';
            help.classList.remove('d-none');
            btn.className = 'btn btn-sm btn-danger px-3';
            btn.innerHTML = '<i class="fa-solid fa-ban me-1"></i> Tolak Pengajuan';
        } else {
            note.removeAttribute('required');
            label.innerHTML = 'Catatan Owner <span class="text-muted fw-normal">(opsional)</span>';
            help.classList.add('d-none');
            btn.className = 'btn btn-sm btn-primary px-3';
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Simpan &amp; TTD';
        }
    }

    // Auto submit filter on change
    document.addEventListener('DOMContentLoaded', function () {
        const fFrom  = document.getElementById('filterFrom');
        const fTo    = document.getElementById('filterTo');
        const form   = document.getElementById('filterForm');

        if (form) {
            [fFrom, fTo].forEach(function (el) {
                if (el) {
                    el.addEventListener('change', function () {
                        if (fFrom.value && fTo.value) { form.submit(); }
                    });
                }
            });
            form.querySelectorAll('select').forEach(function (sel) {
                sel.addEventListener('change', function () { form.submit(); });
            });
        }

        // Close modal on click outside box
        document.querySelectorAll('.x-modal-overlay').forEach(function (ov) {
            ov.addEventListener('click', function (e) {
                if (e.target === ov) { ov.classList.remove('open'); }
            });
        });

        // Close on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.x-modal-overlay.open').forEach(function (ov) {
                    ov.classList.remove('open');
                });
            }
        });
    });
</script>
@endpush
@endsection
