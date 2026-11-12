@extends('admin.app')

@section('title', 'Rekap Pengajuan Guru & Bahan Ajar')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        /* Warna status tambahan khusus halaman Rekap Pengajuan */
        .badge-owner { background: rgba(168, 85, 247, .12); color: #9333ea; }
        .badge-paid  { background: rgba(20, 184, 166, .12); color: #0f766e; }
        .badge-done  { background: #e5e7eb; color: #111827; }
    </style>
@endpush

@section('content')
@php
    // Status => [class badge, ikon, keterangan tahap]
    $stageMap = [
        'Diajukan'         => ['badge-pending', 'Kurikulum'],
        'Review Kurikulum' => ['badge-cold', 'Kurikulum'],
        'Review Admin'     => ['badge-warning',' Admin'],
        'Review Owner'     => ['badge-owner', 'Owner'],
        'Disetujui'        => ['badge-completed', 'Owner'],
        'Dibayar'          => ['badge-paid', 'Owner'],
        'Selesai'          => ['badge-done', ''],
        'Ditolak'          => ['badge-hot', 'Ditolak'],
    ];

    $statusOptions = array_keys($stageMap);
    $typeOptions   = ['Reimburse Bahan Ajar', 'Pembelian Bahan Ajar', 'Pembelian Sertifikat'];
@endphp

{{-- HEADER --}}
<div class="dashboard-header">
    <div>
        <div class="eyebrow">{{ $isOwner ? 'Owner' : 'Admin' }} · Keuangan</div>
        <h1>Rekap Pengajuan Bahan Ajar &amp; Reimburse</h1>
        <p>Monitoring dan persetujuan pengajuan reimburse bahan ajar, pengadaan buku/kit, dan sertifikat.</p>
    </div>
</div>

{{-- ALERT --}}
@if (session('success'))
    <div class="success-banner">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert-error">
        <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert-error">
        <strong>Terjadi kesalahan:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- RINGKASAN (ikut berubah sesuai filter) --}}
<div class="x-note">
    <i class="fa-solid fa-calendar-days"></i>
    <span>Ringkasan periode: <strong>{{ $periodLabel }}</strong></span>
</div>

<div class="stat-row">
    <div class="stat-card c-red">
        <div class="stat-top">
            <span class="stat-label">Total Pengeluaran</span>
            <i class="fa-solid fa-arrow-trend-down stat-icon"></i>
        </div>
        <div class="stat-value money">Rp {{ number_format($totalPengeluaran ?? 0, 0, ',', '.') }}</div>
        <div class="stat-note">Reimburse &amp; pengadaan disetujui</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Pengajuan</span>
            <i class="fa-solid fa-folder-open stat-icon"></i>
        </div>
        <div class="stat-value">{{ $totalCount }}</div>
        <div class="stat-note">Semua status</div>
    </div>

    <div class="stat-card c-yellow">
        <div class="stat-top">
            <span class="stat-label">Perlu Verifikasi</span>
            <i class="fa-solid fa-clipboard-check stat-icon"></i>
        </div>
        <div class="stat-value">{{ $totalVerifikasi }}</div>
        <div class="stat-note {{ $totalVerifikasi > 0 ? 'warn' : '' }}">Menunggu verifikasi Admin</div>
    </div>

    <div class="stat-card c-green">
        <div class="stat-top">
            <span class="stat-label">Telah Disetujui</span>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
        <div class="stat-value">{{ $totalApproved }}</div>
        <div class="stat-note up">Disetujui, Dibayar &amp; Selesai</div>
    </div>
</div>

{{-- FILTER (otomatis terfilter saat pilihan diubah) --}}
<form method="GET" class="toolbar" id="filterForm">
    <select name="status" class="select-chip">
        <option value="">Semua Status</option>
        @foreach ($statusOptions as $opt)
            <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt }}</option>
        @endforeach
    </select>

    <select name="type" class="select-chip">
        <option value="">Semua Jenis Pengajuan</option>
        @foreach ($typeOptions as $opt)
            <option value="{{ $opt }}" @selected($type === $opt)>{{ $opt }}</option>
        @endforeach
    </select>

    <select name="teacher_id" class="select-chip">
        <option value="">Semua Nama Pengaju</option>
        @foreach ($teachers as $t)
            <option value="{{ $t->id }}" @selected($teacherId == $t->id)>{{ $t->name }}</option>
        @endforeach
    </select>

    <div class="date-range" title="Filter rentang tanggal pengajuan">
        <input type="date" name="date_from" id="filterFrom" class="select-chip" value="{{ $dateFrom }}">
        <span>-</span>
        <input type="date" name="date_to" id="filterTo" class="select-chip" value="{{ $dateTo }}">
    </div>
</form>

{{-- TABEL REKAP --}}
<div class="card">
    <div class="sd-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nama Pengaju</th>
                    <th  style="text-align: center; vertical-align: middle;">Jenis</th>
                    <th  style="text-align: center; vertical-align: middle;">Item / Barang</th>
                    <th  style="text-align: center; vertical-align: middle;">Nominal</th>
                    <th  style="text-align: center; vertical-align: middle;">Tanggal</th>
                    <th  style="text-align: center; vertical-align: middle;">Status</th>
                    <th  style="text-align: center; vertical-align: middle;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $sub)
                    @php
                        $teacherName = $sub->teacher->name ?? $sub->creator?->display_name ?? '-';

                        [$badgeClass, $stageText] = $stageMap[$sub->status] ?? ['badge-neutral', $sub->status];

                        // Pada status Ditolak: tampilkan di tahap mana ditolaknya
                        $rejectNote = null;
                        if ($sub->status === 'Ditolak') {
                            if ($sub->owner_id) {
                                $stageText = 'Ditolak di tahap Owner';
                                $rejectNote = $sub->owner_note;
                            } elseif ($sub->admin_verified_at) {
                                $stageText = 'Ditolak di tahap Admin';
                                $rejectNote = $sub->admin_note;
                            } else {
                                $stageText = 'Ditolak di tahap Kurikulum';
                                $rejectNote = $sub->curriculum_note;
                            }
                        }

                        $canAdminVerify  = !$isOwner && $sub->status === 'Review Admin';
                        $canOwnerApprove = $isOwner && in_array($sub->status, ['Review Owner', 'Review Admin']);
                        $canMarkPaid     = $isOwner && $sub->status === 'Disetujui';

                        $verifyHint = match (true) {
                            in_array($sub->status, ['Diajukan', 'Review Kurikulum']) => 'Belum bisa diverifikasi: masih di tahap Kurikulum',
                            $sub->status === 'Ditolak'                               => 'Pengajuan ditolak',
                            default                                                  => 'Sudah diverifikasi Admin',
                        };
                        $approveHint = match (true) {
                            in_array($sub->status, ['Diajukan', 'Review Kurikulum']) => 'Belum sampai tahap Owner',
                            $sub->status === 'Ditolak'                               => 'Pengajuan ditolak',
                            default                                                  => 'Sudah diputuskan Owner',
                        };
                        $paidHint = match (true) {
                            in_array($sub->status, ['Dibayar', 'Selesai']) => 'Sudah dibayar',
                            $sub->status === 'Ditolak'                     => 'Pengajuan ditolak',
                            default                                        => 'Belum disetujui Owner',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="name-cell">
                                {{-- <div class="avatar-sm">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacherName, 0, 2)) }}</div> --}}
                                <div class="cand-name">{{ $teacherName }}</div>
                            </div>
                        </td>

                        <td class="cell-nowrap" style="text-align: center; vertical-align: middle;">
                            <span class="pay-type-tag no-margin">{{ $sub->type }}</span>
                        </td>

                        <td>
                            <div class="cell-stack-main">{{ $sub->item_name }}</div>
                        </td>

                        <td  style="text-align: center; vertical-align: middle;">
                            <span class="money money-main">{{ $sub->formatted_amount }}</span>
                        </td>

                        <td class="cell-nowrap"  style="text-align: center; vertical-align: middle;">
                            {{ $sub->date?->translatedFormat('d M Y') ?? '-' }}
                        </td>

                        <td  style="text-align: center; vertical-align: middle;">
                            <span class="badge {{ $badgeClass }}">{{ $sub->status }}</span>
                            <div class="cell-stack-sub" @if($rejectNote) title="Catatan: {{ $rejectNote }}" @endif>
                                {{ $stageText }}
                            </div>
                        </td>

                        <td  style="text-align: center; vertical-align: middle;">
                            <div class="row-actions" style="align-items:center;">
                                <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub->id) }}"
                                   target="_blank" class="icon-btn" title="Cetak / Dokumen TTD">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>

                                @if (!$isOwner)
                                    {{-- ADMIN: kuning bila bisa verifikasi, abu bila belum/tidak bisa --}}
                                    @if ($canAdminVerify)
                                        <button type="button" class="sd-icon-btn" title="Verifikasi &amp; TTD Admin"
                                                data-open-modal="modalAdminVerify{{ $sub->id }}">
                                            <i class="fa-solid fa-clipboard-check"></i>
                                        </button>
                                    @else
                                        <span class="sd-icon-btn is-disabled" title="{{ $verifyHint }}">
                                            <i class="fa-solid fa-clipboard-check"></i>
                                        </span>
                                    @endif
                                @else
                                    {{-- OWNER: persetujuan & tandai dibayar --}}
                                    @if ($canOwnerApprove)
                                        <button type="button" class="sd-icon-btn" title="Approve &amp; TTD Owner"
                                                data-open-modal="modalOwnerApprove{{ $sub->id }}">
                                            <i class="fa-solid fa-signature"></i>
                                        </button>
                                    @else
                                        <span class="sd-icon-btn is-disabled" title="{{ $approveHint }}">
                                            <i class="fa-solid fa-signature"></i>
                                        </span>
                                    @endif

                                    @if ($canMarkPaid)
                                        <form action="{{ route('owner.pengajuan.mark-paid', $sub->id) }}" method="POST" class="x-inline-form"
                                              onsubmit="return confirm('Tandai pengajuan {{ e($sub->item_name) }} telah dibayar?')">
                                            @csrf
                                            <button type="submit" class="sd-icon-btn" title="Tandai Dibayar">
                                                <i class="fa-solid fa-money-bill-wave"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="sd-icon-btn is-disabled" title="{{ $paidHint }}">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="x-empty">
                                <i class="fa-solid fa-receipt"></i>
                                Tidak ada data pengajuan yang cocok dengan filter.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span class="table-footer-info">
            Menampilkan
            <strong>{{ $submissions->firstItem() ?? 0 }}</strong>
            -
            <strong>{{ $submissions->lastItem() ?? 0 }}</strong>
            dari
            <strong>{{ $submissions->total() }}</strong>
            pengajuan
        </span>

        <div class="table-footer-pagination">
            @if ($submissions->hasPages())
                @php
                    $current = $submissions->currentPage();
                    $last = $submissions->lastPage();
                    $onEachSide = 1;
                @endphp

                <nav class="pagination-nav" aria-label="Pagination">
                    <ul class="pagination-list">
                        @if ($submissions->onFirstPage())
                            <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                        @else
                            <li class="page-item"><a class="page-btn" href="{{ $submissions->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                        @endif

                        @for ($page = 1; $page <= $last; $page++)
                            @php
                                $isEdge = $page == 1 || $page == $last;
                                $isNearCurrent = abs($page - $current) <= $onEachSide;
                            @endphp

                            @if ($isEdge || $isNearCurrent)
                                @if ($page == $current)
                                    <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-btn" href="{{ $submissions->url($page) }}">{{ $page }}</a></li>
                                @endif
                            @elseif ($page == 2 && $current - $onEachSide > 2)
                                <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                            @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                            @endif
                        @endfor

                        @if ($submissions->hasMorePages())
                            <li class="page-item"><a class="page-btn" href="{{ $submissions->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                        @else
                            <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                        @endif
                    </ul>
                </nav>
            @endif
        </div>
    </div>
</div>

{{-- ========================= MODAL (di luar tabel) ========================= --}}
@foreach ($submissions as $sub)
    @php
        $teacherName = $sub->teacher->name ?? $sub->creator?->display_name ?? '-';
    @endphp

    {{-- MODAL VERIFIKASI ADMIN --}}
    @if (!$isOwner && $sub->status === 'Review Admin')
        <div class="modal-overlay" id="modalAdminVerify{{ $sub->id }}">
            <div class="modal">
                <form action="{{ route('admin.pengajuan.verify', $sub->id) }}" method="POST" class="js-sign-form">
                    @csrf

                    <div class="modal-head">
                        <div>
                            <div class="modal-title">Verifikasi Pengajuan</div>
                            <div class="modal-sub">Periksa dokumen, lalu tanda tangani untuk meneruskan ke Owner.</div>
                        </div>
                        <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-section">
                            <div class="form-section-title"><i class="fa-solid fa-receipt"></i> Detail Pengajuan</div>
                            <div class="x-detail-grid">
                                <div>
                                    <div class="x-detail-label">Nama Pengaju</div>
                                    <div class="x-detail-value">{{ $teacherName }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Jenis Pengajuan</div>
                                    <div class="x-detail-value">{{ $sub->type }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Item / Keperluan</div>
                                    <div class="x-detail-value">{{ $sub->item_name }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Nominal</div>
                                    <div class="x-detail-value money money-main">{{ $sub->formatted_amount }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Tanggal Pengajuan</div>
                                    <div class="x-detail-value">{{ $sub->date?->translatedFormat('d F Y') ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">ACC Kurikulum</div>
                                    <div class="x-detail-value">
                                        {{ $sub->curriculum_approved_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                        ({{ $sub->curriculum->display_name ?? 'OK' }})
                                    </div>
                                </div>
                                @if ($sub->description)
                                    <div class="full">
                                        <div class="x-detail-label">Keterangan</div>
                                        <div class="x-pre">{{ $sub->description }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($sub->receipt_path || $sub->photo_path)
                            <div class="form-section">
                                <div class="form-section-title"><i class="fa-solid fa-paperclip"></i> Lampiran</div>
                                <div class="sd-icon-row">
                                    @if ($sub->receipt_path)
                                        <a href="{{ asset('storage/' . $sub->receipt_path) }}" target="_blank" class="btn btn-secondary">
                                            <i class="fa-solid fa-receipt"></i> Cek Nota Pembelian
                                        </a>
                                    @endif
                                    @if ($sub->photo_path)
                                        <a href="{{ asset('storage/' . $sub->photo_path) }}" target="_blank" class="btn btn-secondary">
                                            <i class="fa-solid fa-image"></i> Cek Foto Fisik Barang
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="form-section">
                            <div class="form-section-title"><i class="fa-solid fa-clipboard-check"></i> Keputusan Verifikasi</div>
                            <div class="form-grid">
                                <div class="form-field full">
                                    <label>Keputusan Verifikasi Administrasi *</label>
                                    <select name="decision" class="js-decision" required>
                                        <option value="Approved">Verifikasi Sesuai &amp; Teruskan ke Owner</option>
                                        <option value="Rejected">Tolak / Dokumen Tidak Lengkap</option>
                                    </select>
                                </div>
                                <div class="form-field full">
                                    <label>Catatan Admin <span class="opt">(opsional)</span></label>
                                    <textarea name="note" rows="2" maxlength="1000"
                                              placeholder="Contoh: Nota dan foto barang fisik telah dicek lengkap dan sesuai."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section js-sig-section">
                            <div class="form-section-title"><i class="fa-solid fa-signature"></i> Tanda Tangan Admin</div>
                            <div class="form-grid">
                                <div class="form-field full">
                                    <label>Tanda Tangan * <span class="opt">(khusus pengajuan #{{ $sub->id }})</span></label>
                                    <canvas class="js-sig-canvas"
                                            style="width:100%;height:170px;border:1px dashed #d1d5db;border-radius:10px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
                                    <input type="hidden" name="signature" class="js-sig-input">
                                    <div>
                                        <button type="button" class="btn btn-secondary btn-sm js-sig-clear">
                                            <i class="fa-solid fa-eraser"></i> Hapus &amp; ulangi
                                        </button>
                                    </div>
                                    <span class="form-hint">Tanda tangan di kotak di atas dengan mouse atau jari. TTD ini hanya berlaku untuk pengajuan ini.</span>
                                </div>
                                <div class="form-field full">
                                    <label>Nama Penandatangan * <span class="opt">(tercetak di bawah TTD)</span></label>
                                    <input type="text" name="signer_name" maxlength="120"
                                           value="{{ auth()->user()->display_name ?? auth()->user()->username }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-foot">
                        <div class="modal-foot-left">
                            <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub->id) }}" target="_blank" class="btn btn-secondary">
                                <i class="fa-solid fa-file-invoice"></i> Lihat Dokumen
                            </a>
                        </div>
                        <div class="modal-foot-right">
                            <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Kirim Hasil Verifikasi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL PERSETUJUAN OWNER --}}
    @if ($isOwner && in_array($sub->status, ['Review Owner', 'Review Admin']))
        <div class="modal-overlay" id="modalOwnerApprove{{ $sub->id }}">
            <div class="modal">
                <form action="{{ route('owner.pengajuan.approve', $sub->id) }}" method="POST">
                    @csrf

                    <div class="modal-head">
                        <div>
                            <div class="modal-title">Persetujuan Owner</div>
                            <div class="modal-sub">Setujui atau tolak pengajuan ini. TTD Owner ditempel otomatis dari profil.</div>
                        </div>
                        <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-section">
                            <div class="form-section-title"><i class="fa-solid fa-receipt"></i> Detail Pengajuan</div>
                            <div class="x-detail-grid">
                                <div>
                                    <div class="x-detail-label">Guru Pengaju</div>
                                    <div class="x-detail-value">{{ $teacherName }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Jenis Pengajuan</div>
                                    <div class="x-detail-value">{{ $sub->type }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Item / Keperluan</div>
                                    <div class="x-detail-value">{{ $sub->item_name }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Nominal</div>
                                    <div class="x-detail-value money money-main">{{ $sub->formatted_amount }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Disetujui Kurikulum</div>
                                    <div class="x-detail-value">{{ $sub->curriculum_approved_at?->translatedFormat('d M Y') ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="x-detail-label">Diverifikasi Admin</div>
                                    <div class="x-detail-value">{{ $sub->admin_verified_at?->translatedFormat('d M Y') ?? '-' }}</div>
                                </div>
                                @if ($sub->admin_note)
                                    <div class="full">
                                        <div class="x-detail-label">Catatan Admin</div>
                                        <div class="x-pre">{{ $sub->admin_note }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fa-solid fa-signature"></i> Keputusan Owner</div>
                            <div class="x-note">
                                <i class="fa-solid fa-signature"></i>
                                <span>Tanda tangan Owner yang tersimpan di profil akan otomatis ditempelkan pada lembar dokumen pencairan.</span>
                            </div>
                            <div class="form-grid">
                                <div class="form-field full">
                                    <label>Keputusan *</label>
                                    <select name="decision" required>
                                        <option value="Approved">Setujui Pencairan (Approved)</option>
                                        <option value="Rejected">Tolak Pengajuan (Rejected)</option>
                                    </select>
                                </div>
                                <div class="form-field full">
                                    <label>Catatan Owner <span class="opt">(opsional)</span></label>
                                    <textarea name="note" rows="2" placeholder="Catatan persetujuan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-foot">
                        <div class="modal-foot-left">
                            <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub->id) }}" target="_blank" class="btn btn-secondary">
                                <i class="fa-solid fa-file-invoice"></i> Lihat Dokumen
                            </a>
                        </div>
                        <div class="modal-foot-right">
                            <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Simpan Keputusan &amp; TTD
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        function openModal(id) {
            var el = document.getElementById(id);
            if (el) {
                el.classList.add('open');
                initSignaturePad(el);
            }
        }
        function closeModal(el) {
            if (el) { el.classList.remove('open'); }
        }

        // ---------- Pad tanda tangan (per modal verifikasi Admin) ----------
        function initSignaturePad(overlay) {
            var canvas = overlay.querySelector('.js-sig-canvas');
            if (!canvas || canvas.dataset.ready) { return; }

            var input   = overlay.querySelector('.js-sig-input');
            var section = overlay.querySelector('.js-sig-section');
            var form    = overlay.querySelector('.js-sign-form');
            var select  = overlay.querySelector('.js-decision');
            var clear   = overlay.querySelector('.js-sig-clear');

            // Ukuran canvas baru bisa dihitung setelah modal tampil
            var ratio = window.devicePixelRatio || 1;
            canvas.width  = canvas.clientWidth * ratio;
            canvas.height = canvas.clientHeight * ratio;
            canvas.dataset.ready = '1';

            var ctx = canvas.getContext('2d');
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#111827';

            var drawing = false;

            function pos(e) {
                var r = canvas.getBoundingClientRect();
                return { x: e.clientX - r.left, y: e.clientY - r.top };
            }

            canvas.addEventListener('pointerdown', function (e) {
                drawing = true;
                canvas.setPointerCapture(e.pointerId);
                var p = pos(e);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p.x + 0.01, p.y + 0.01); // titik tunggal tetap terlihat
                ctx.stroke();
            });
            canvas.addEventListener('pointermove', function (e) {
                if (!drawing) { return; }
                var p = pos(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
            });
            function endStroke() {
                if (!drawing) { return; }
                drawing = false;
                input.value = canvas.toDataURL('image/png');
            }
            canvas.addEventListener('pointerup', endStroke);
            canvas.addEventListener('pointercancel', endStroke);

            clear.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                input.value = '';
            });

            // TTD hanya diperlukan saat keputusan = Approved
            select.addEventListener('change', function () {
                section.style.display = select.value === 'Approved' ? '' : 'none';
            });

            form.addEventListener('submit', function (e) {
                if (select.value === 'Approved' && !input.value) {
                    e.preventDefault();
                    alert('Tanda tangan Admin wajib diisi sebelum verifikasi dikirim.');
                }
            });
        }

        document.addEventListener('click', function (e) {
            var opener = e.target.closest('[data-open-modal]');
            if (opener) {
                openModal(opener.getAttribute('data-open-modal'));
                return;
            }
            if (e.target.closest('[data-close-modal]')) {
                closeModal(e.target.closest('.modal-overlay'));
                return;
            }
            // klik area gelap di luar modal
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                closeModal(e.target);
            }
        });

        // ---------- Filter: bulan dan rentang tanggal tidak dipakai bersamaan ----------
        var fBulan = document.getElementById('filterBulan');
        var fFrom  = document.getElementById('filterFrom');
        var fTo    = document.getElementById('filterTo');
        if (fBulan && fFrom && fTo) {
            fBulan.addEventListener('change', function () {
                if (fBulan.value) { fFrom.value = ''; fTo.value = ''; }
            });
            [fFrom, fTo].forEach(function (el) {
                el.addEventListener('change', function () {
                    if (el.value) { fBulan.value = ''; }
                });
            });
        }

        // ---------- Filter otomatis: langsung terfilter saat pilihan diubah ----------
        var filterForm = document.getElementById('filterForm');
        if (filterForm) {
            var timer = null;
            filterForm.addEventListener('change', function (e) {
                clearTimeout(timer);
                // input tanggal/bulan diberi jeda agar tidak terkirim saat tahun masih diketik
                var isDate = e.target.type === 'date' || e.target.type === 'month';
                timer = setTimeout(function () { filterForm.submit(); }, isDate ? 700 : 0);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.open').forEach(closeModal);
            }
        });
    })();
</script>
@endpush