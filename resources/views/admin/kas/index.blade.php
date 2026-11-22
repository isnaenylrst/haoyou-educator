@extends('admin.app')

@section('title', 'Kas Operasional')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        .badge-owner { background: rgba(168, 85, 247, .12); color: #9333ea; }

        .stat-row-6 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) { .stat-row-6 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 560px) { .stat-row-6 { grid-template-columns: 1fr; } }
        .stat-value.is-negative { color: #ef4444 !important; }

        .stat-card.c-blue   { border-left-color: #3b82f6; }
        .stat-card.c-green  { border-left-color: #22a05a; }
        .stat-card.c-red    { border-left-color: #ef4444; }
        .stat-card.c-yellow { border-left-color: #e8a317; }

        .period-header-line { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 13px; flex-wrap: wrap; }
        .period-badge-chip { background: #fff; border: 1px solid #e5e7eb; padding: 4px 12px; border-radius: 99px; font-weight: 600; color: #111827; }
        .period-note { color: #6b7280; font-size: 12px; }

        /* header: judul kiri, tombol kanan (sama seperti halaman Guru) */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }
        .dashboard-header .dashboard-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        /* jarak antar tabel */
        .recap-card { margin-top: 24px; margin-bottom: 24px; }
        .recap-head { padding: 16px 20px 12px; font-weight: 600; font-size: 14px; color: #111827; }

        /* tombol aksi rata tengah */
        .sd-scroll .row-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .sd-scroll .row-actions form { margin: 0; display: flex; }

        /* tombol aksi nonaktif: tetap tampil tapi mati */
        .icon-btn.is-disabled,
        .icon-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
            pointer-events: auto;
            background: #f3f4f6;
            box-shadow: none;
        }
        .icon-btn.is-disabled:hover,
        .icon-btn:disabled:hover {
            background: #f3f4f6;
            transform: none;
        }
        .recap-list { display: flex; flex-wrap: wrap; gap: 10px; padding: 12px 16px 16px; }
        .recap-chip { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 12px; min-width: 150px; }
        .recap-chip .rc-name { font-size: 12px; color: #6b7280; }
        .recap-chip .rc-total { font-weight: 700; color: #111827; font-size: 14px; }
        .recap-chip .rc-count { font-size: 11px; color: #9ca3af; }
        .recap-empty { padding: 12px 16px 16px; color: #9ca3af; font-size: 13px; }

        .link-badge { display: inline-flex; align-items: center; gap: 6px; }
    </style>
@endpush

@section('content')
@php
    $currentIndexUrl = route('admin.kas.index');
@endphp

{{-- HEADER --}}
<div class="dashboard-header">
    <div>
        <div class="eyebrow">Keuangan</div>
        <h1>Kas Operasional</h1>
        <p>Uang kas pengeluaran operasional Haoyou Educator(ATK, kebersihan, konsumsi, dll.).</p>
    </div>

    @if ($isAdmin)
        <div class="dashboard-header-actions">
            <button type="button" class="btn btn-secondary" data-open-modal="modalKasMasuk">
                <i class="fa-solid fa-plus"></i>
                Tambah Kas Masuk
            </button>

            <button type="button" class="btn btn-primary" data-open-modal="modalKasCreate">
                <i class="fa-solid fa-plus"></i>
                Tambah Pengeluaran
            </button>
        </div>
    @endif
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

{{-- RINGKASAN --}}
<div class="period-header-line">
    <span class="period-badge-chip">
        <i class="fa-regular fa-calendar me-1"></i> Periode: <strong>{{ $periodLabel }}</strong>
    </span>
    <span class="period-note">Angka ringkasan mengikuti filter yang aktif</span>
    @if ($semuaPeriode)
        <a href="{{ $currentIndexUrl }}" class="period-note" style="text-decoration:underline;">Kembali ke bulan ini</a>
    @else
        <a href="{{ $currentIndexUrl }}?periode=all" class="period-note" style="text-decoration:underline;">Lihat semua periode</a>
    @endif
</div>

<div class="stat-row-6">
    <div class="stat-card c-blue">
        <div class="stat-top">
            <span class="stat-label">Saldo Kas</span>
            <i class="fa-solid fa-wallet stat-icon"></i>
        </div>
        <div class="stat-value money {{ $saldoKas < 0 ? 'is-negative' : '' }}">{{ format_rupiah($saldoKas) }}</div>
        <div class="stat-note {{ $saldoKas < 0 ? 'warn' : '' }}">
            Per {{ $asOfLabel }} &middot; kas masuk dikurangi pengeluaran disetujui
        </div>
    </div>

    <div class="stat-card c-green">
        <div class="stat-top">
            <span class="stat-label">Kas Masuk</span>
            <i class="fa-solid fa-arrow-trend-up stat-icon"></i>
        </div>
        <div class="stat-value money">{{ format_rupiah($kasMasukPeriode) }}</div>
        <div class="stat-note up">{{ $kasMasukCount }} penerimaan pada periode ini</div>
    </div>

    <div class="stat-card c-red">
        <div class="stat-top">
            <span class="stat-label">Pengeluaran Disetujui</span>
            <i class="fa-solid fa-arrow-trend-down stat-icon"></i>
        </div>
        <div class="stat-value money">{{ format_rupiah($totalDisetujui) }}</div>
        <div class="stat-note">Sudah ditandatangani Owner, mengurangi saldo</div>
    </div>

    <div class="stat-card c-yellow">
        <div class="stat-top">
            <span class="stat-label">Menunggu TTD Owner</span>
            <i class="fa-solid fa-hourglass-half stat-icon"></i>
        </div>
        <div class="stat-value money">{{ format_rupiah($menungguTotal) }}</div>
        <div class="stat-note {{ $menungguCount > 0 ? 'warn' : '' }}">{{ $menungguCount }} pengeluaran</div>
    </div>

    <div class="stat-card c-blue">
        <div class="stat-top">
            <span class="stat-label">Saldo Setelah Menunggu</span>
            <i class="fa-solid fa-scale-balanced stat-icon"></i>
        </div>
        <div class="stat-value money {{ $saldoSetelahMenunggu < 0 ? 'is-negative' : '' }}">{{ format_rupiah($saldoSetelahMenunggu) }}</div>
        <div class="stat-note">Jika semua yang menunggu TTD disetujui</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Ditolak</span>
            <i class="fa-solid fa-circle-xmark stat-icon"></i>
        </div>
        <div class="stat-value">{{ $ditolakCount }}</div>
        <div class="stat-note {{ $ditolakCount > 0 ? 'warn' : '' }}">
            Perlu diperbaiki & dikirim ulang
        </div>
    </div>
</div>

{{-- FILTER --}}
<form method="GET" action="{{ $currentIndexUrl }}" class="toolbar" id="filterForm">
    @if ($semuaPeriode)
        <input type="hidden" name="periode" id="filterPeriode" value="all">
    @endif
    <div style="position: relative; flex: 1; min-width: 200px;">
        <input type="text" name="q" class="select-chip" style="width: 100%; padding-left: 28px;"
               placeholder="Cari barang atau keterangan..." value="{{ $search ?? '' }}">
        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 12px;"></i>
    </div>

    <select name="status" class="select-chip">
        <option value="">Semua Status</option>
        @foreach ($statuses as $opt)
            <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt }}</option>
        @endforeach
    </select>

    <select name="category" class="select-chip">
        <option value="">Semua Kategori</option>
        @foreach ($categories as $opt)
            <option value="{{ $opt }}" @selected($category === $opt)>{{ $opt }}</option>
        @endforeach
    </select>

    <input type="month" name="bulan" id="filterBulan" class="select-chip" value="{{ $bulan ?? '' }}" title="Pilih per Bulan">

    <div class="date-range" title="Filter rentang tanggal pembelian">
        <input type="date" name="date_from" id="filterFrom" class="select-chip" value="{{ $dateFrom }}">
        <span>-</span>
        <input type="date" name="date_to" id="filterTo" class="select-chip" value="{{ $dateTo }}">
    </div>
</form>

{{-- TABEL --}}
<div class="card">
    <div class="sd-scroll">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center; vertical-align: middle;">Tanggal</th>
                    <th>Barang</th>
                    <th style="text-align: center; vertical-align: middle;">Kategori</th>
                    <th style="text-align: center; vertical-align: middle;">Cara Beli</th>
                    <th style="text-align: center; vertical-align: middle;">Nominal</th>
                    <th style="text-align: center; vertical-align: middle;">Status</th>
                    <th style="text-align: center; vertical-align: middle;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $e)
                    @php
                        $stageText = match ($e->status) {
                            'Menunggu Owner' => 'Menunggu TTD Owner',
                            'Disetujui'      => 'TTD Owner ' . ($e->owner_signed_at?->translatedFormat('d M Y') ?? ''),
                            'Ditolak'        => 'Ditolak Owner',
                            default          => '',
                        };
                    @endphp
                    <tr>
                        <td class="cell-nowrap" style="text-align: center; vertical-align: middle;">
                            {{ $e->date?->translatedFormat('d M Y') ?? '-' }}
                        </td>

                        <td>
                            <div class="cell-stack-main">{{ $e->item_name }}</div>
                            <div class="cell-stack-sub">Dicatat: {{ $e->creator->display_name ?? $e->creator->username ?? '-' }}</div>
                        </td>

                        <td class="cell-nowrap" style="text-align: center; vertical-align: middle;">
                            <span class="pay-type-tag no-margin">{{ $e->category }}</span>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            @if ($e->isOnline())
                                <span class="link-badge">
                                    Online
                                    @if ($e->purchase_link)
                                        <a href="{{ $e->purchase_link }}" target="_blank" rel="noopener noreferrer"
                                           class="icon-btn" title="Buka link pembelian">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif
                                </span>
                            @else
                                Offline
                            @endif
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <span class="money money-main">{{ $e->formatted_amount }}</span>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge {{ $e->status_badge_class }}">{{ $e->status }}</span>
                            <div class="cell-stack-sub" @if ($e->status === 'Ditolak' && $e->owner_note) title="Catatan Owner: {{ $e->owner_note }}" @endif>
                                {{ $stageText }}
                            </div>
                        </td>

                        <td style="text-align: center; vertical-align: middle;">
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Lihat detail"
                                        data-open-modal="modalKasDetail{{ $e->id }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                @if ($e->receipt_path)
                                    <a href="{{ asset('storage/' . $e->receipt_path) }}" target="_blank" rel="noopener" class="icon-btn" title="Lihat nota">
                                        <i class="fa-solid fa-receipt"></i>
                                    </a>
                                @else
                                    <span class="icon-btn is-disabled" title="Nota tidak tersedia" aria-disabled="true">
                                        <i class="fa-solid fa-receipt"></i>
                                    </span>
                                @endif

                                {{-- ADMIN: edit / hapus selalu tampil; nonaktif setelah disetujui --}}
                                @if ($isAdmin)
                                    @if ($e->isEditable())
                                        <button type="button" class="icon-btn" title="Edit"
                                                data-open-modal="modalKasEdit{{ $e->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <form action="{{ route('admin.kas.destroy', $e->id) }}" method="POST"
                                              onsubmit="return confirm('Hapus pengeluaran &quot;{{ addslashes($e->item_name) }}&quot;?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn danger" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="icon-btn is-disabled" disabled
                                                title="Sudah disetujui Owner, tidak bisa diedit">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" class="icon-btn is-disabled" disabled
                                                title="Sudah disetujui Owner, tidak bisa dihapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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
                                Tidak ada data pengeluaran yang cocok dengan filter.
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
            <strong>{{ $expenses->firstItem() ?? 0 }}</strong>
            -
            <strong>{{ $expenses->lastItem() ?? 0 }}</strong>
            dari
            <strong>{{ $expenses->total() }}</strong>
            pengeluaran
        </span>

        <div class="table-footer-pagination">
            @if ($expenses->hasPages())
                @php
                    $current = $expenses->currentPage();
                    $last = $expenses->lastPage();
                    $onEachSide = 1;
                @endphp

                <nav class="pagination-nav" aria-label="Pagination">
                    <ul class="pagination-list">
                        @if ($expenses->onFirstPage())
                            <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                        @else
                            <li class="page-item"><a class="page-btn" href="{{ $expenses->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
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
                                    <li class="page-item"><a class="page-btn" href="{{ $expenses->url($page) }}">{{ $page }}</a></li>
                                @endif
                            @elseif ($page == 2 && $current - $onEachSide > 2)
                                <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                            @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                            @endif
                        @endfor

                        @if ($expenses->hasMorePages())
                            <li class="page-item"><a class="page-btn" href="{{ $expenses->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                        @else
                            <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                        @endif
                    </ul>
                </nav>
            @endif
        </div>
    </div>
</div>

{{-- RIWAYAT KAS MASUK --}}
<div class="card recap-card">
    <div class="recap-head">
        Riwayat Kas Masuk
        <span class="period-note">
            (periode aktif{{ $kasMasukCount > $kasMasukList->count() ? ', ' . $kasMasukList->count() . ' terbaru dari ' . $kasMasukCount : '' }})
        </span>
    </div>
    @if ($kasMasukList->isEmpty())
        <div class="recap-empty">Belum ada kas masuk pada periode ini.</div>
    @else
        <div class="sd-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">Tanggal</th>
                        <th>Keterangan</th>
                        <th style="text-align: center;">Nominal</th>
                        <th style="text-align: center;">Dicatat Oleh</th>
                        @if ($isAdmin)
                            <th style="text-align: center;">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($kasMasukList as $k)
                        @php $kUser = $kasMasukUsers->get($k->created_by); @endphp
                        <tr>
                            <td class="cell-nowrap" style="text-align: center;">
                                {{ \Carbon\Carbon::parse($k->date)->translatedFormat('d M Y') }}
                            </td>
                            <td>{{ $k->note ?: '-' }}</td>
                            <td style="text-align: center;">
                                <span class="money money-main">{{ format_rupiah($k->amount) }}</span>
                            </td>
                            <td style="text-align: center;">{{ $kUser->display_name ?? $kUser->username ?? '-' }}</td>
                            @if ($isAdmin)
                                <td style="text-align: center; vertical-align: middle;">
                                    <div class="row-actions">
                                        <form action="{{ route('admin.kas.cash.destroy', $k->id) }}" method="POST"
                                              onsubmit="return confirm('Hapus catatan kas masuk ini? Saldo kas akan berkurang.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn danger" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ========================= MODAL (di luar tabel) ========================= --}}

{{-- ===== FORM TAMBAH / EDIT (Admin): 1 modal tambah + 1 modal edit per baris ===== --}}
@if ($isAdmin)
@php $kmOld = old('_form') === 'modalKasMasuk'; @endphp
<div class="modal-overlay" id="modalKasMasuk">
    <div class="modal">
        <form action="{{ route('admin.kas.cash.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="modalKasMasuk">

            <div class="modal-head">
                <div>
                    <div class="modal-title">Tambah Kas Masuk</div>
                    <div class="modal-sub">Catat uang kas yang diterima dari Owner. Nominal ini menambah saldo kas.</div>
                </div>
                <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-section">
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Tanggal Diterima *</label>
                            <input type="date" name="date" required max="{{ now()->toDateString() }}"
                                   value="{{ $kmOld ? old('date') : now()->toDateString() }}">
                        </div>
                        <div class="form-field">
                            <label>Nominal (Rp) *</label>
                            <input type="number" name="amount" min="1" step="1" required
                                   value="{{ $kmOld ? old('amount') : '' }}">
                        </div>
                        <div class="form-field full">
                            <label>Keterangan <span class="opt">(opsional)</span></label>
                            <input type="text" name="note" maxlength="255"
                                   placeholder="Contoh: Kas operasional Desember dari Owner"
                                   value="{{ $kmOld ? old('note') : '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <div class="modal-foot-left"></div>
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Kas Masuk
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@foreach (collect([null])->concat($expenses->all()) as $expense)
@continue($expense && ! $expense->isEditable())
@php
    $modalId = $expense ? 'modalKasEdit' . $expense->id : 'modalKasCreate';
    $isEdit = isset($expense) && $expense;
    $useOld = old('_form') === $modalId;
    // Setelah gagal validasi, isi ulang dari old() hanya untuk modal yang tadi dikirim
    $val = fn ($key, $default = null) => $useOld ? old($key, $default) : $default;

    $method = $val('purchase_method', $isEdit ? $expense->purchase_method : 'Offline');
    $isOnline = $method === 'Online';
    $action = $isEdit ? route('admin.kas.update', $expense->id) : route('admin.kas.store');
@endphp

<div class="modal-overlay" id="{{ $modalId }}">
    <div class="modal">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="js-sign-form">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="_form" value="{{ $modalId }}">

            <div class="modal-head">
                <div>
                    <div class="modal-title">{{ $isEdit ? 'Edit Pengeluaran Kas' : 'Tambah Pengeluaran Kas' }}</div>
                    <div class="modal-sub">
                        @if ($isEdit)
                            Perubahan akan dikirim ulang ke Owner dan perlu tanda tangan ulang.
                        @else
                            Catat pembelian operasional, unggah nota, lalu tanda tangani untuk diteruskan ke Owner.
                        @endif
                    </div>
                </div>
                <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-receipt"></i> Detail Pembelian</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Tanggal Pembelian *</label>
                            <input type="date" name="date" required max="{{ now()->toDateString() }}"
                                   value="{{ $val('date', $isEdit ? $expense->date?->format('Y-m-d') : null) }}">
                        </div>

                        <div class="form-field">
                            <label>Kategori *</label>
                            @php $selCat = $val('category', $isEdit ? $expense->category : null); @endphp
                            <select name="category" required>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c }}" @selected($selCat === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field full">
                            <label>Nama Barang *</label>
                            <input type="text" name="item_name" maxlength="255" required
                                   placeholder="Contoh: Kertas HVS A4 2 rim"
                                   value="{{ $val('item_name', $isEdit ? $expense->item_name : null) }}">
                        </div>

                        <div class="form-field">
                            <label>Nominal (Rp) *</label>
                            <input type="number" name="amount" min="1" step="1" required
                                   value="{{ $val('amount', $isEdit ? (int) $expense->amount : null) }}">
                        </div>

                        <div class="form-field">
                            <label>Cara Pembelian *</label>
                            <select name="purchase_method" class="js-method" required>
                                <option value="Offline" @selected($method === 'Offline')>Offline (beli langsung)</option>
                                <option value="Online" @selected($method === 'Online')>Online</option>
                            </select>
                        </div>

                        <div class="form-field full js-link-field" style="{{ $isOnline ? '' : 'display:none;' }}">
                            <label>Link Pembelian * <span class="opt">(Shopee, Tokopedia, dll.)</span></label>
                            <input type="url" name="purchase_link" maxlength="2048" placeholder="https://..."
                                   value="{{ $val('purchase_link', $isEdit ? $expense->purchase_link : null) }}"
                                   @if ($isOnline) required @endif>
                        </div>

                        <div class="form-field full">
                            <label>
                                Nota / Bukti Pembelian {{ $isEdit ? '' : '*' }}
                                <span class="opt">(JPG, PNG, PDF maks 4 MB)</span>
                            </label>
                            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" @if (! $isEdit) required @endif>
                            @if ($isEdit && $expense->receipt_path)
                                <span class="form-hint">
                                    Nota saat ini:
                                    <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank" rel="noopener">lihat</a>.
                                    Kosongkan jika tidak ingin mengganti.
                                </span>
                            @endif
                        </div>

                        <div class="form-field full">
                            <label>Keterangan <span class="opt">(opsional)</span></label>
                            <textarea name="description" rows="2" maxlength="1000"
                                      placeholder="Contoh: Untuk stok bulan Desember">{{ $val('description', $isEdit ? $expense->description : null) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section js-sig-section">
                    <div class="form-section-title"><i class="fa-solid fa-signature"></i> Tanda Tangan Admin</div>
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Tanda Tangan * <span class="opt">(khusus pengeluaran ini)</span></label>
                            <canvas class="js-sig-canvas"
                                    style="width:100%;height:170px;border:1px dashed #d1d5db;border-radius:10px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
                            <input type="hidden" name="signature" class="js-sig-input">
                            <div>
                                <button type="button" class="btn btn-secondary btn-sm js-sig-clear">
                                    <i class="fa-solid fa-eraser"></i> Hapus &amp; ulangi
                                </button>
                            </div>
                            <span class="form-hint">Tanda tangan di kotak di atas dengan mouse atau jari.</span>
                        </div>
                        <div class="form-field full">
                            <label>Nama Penandatangan * <span class="opt">(tampil di bawah TTD)</span></label>
                            <input type="text" name="signer_name" maxlength="120" required
                                   value="{{ $val('signer_name', auth()->user()->display_name ?? auth()->user()->username) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <div class="modal-foot-left"></div>
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> {{ $isEdit ? 'Simpan & Kirim Ulang' : 'Simpan & Kirim ke Owner' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endif

@foreach ($expenses as $e)
    {{-- Detail pengeluaran (semua role) --}}
    <div class="modal-overlay" id="modalKasDetail{{ $e->id }}">
        <div class="modal">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Detail Pengeluaran Kas</div>
                    <div class="modal-sub">
                        <span class="badge {{ $e->status_badge_class }}">{{ $e->status }}</span>
                    </div>
                </div>
                <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-receipt"></i> Pembelian</div>
                    <div class="x-detail-grid">
                        <div>
                            <div class="x-detail-label">Barang</div>
                            <div class="x-detail-value">{{ $e->item_name }}</div>
                        </div>
                        <div>
                            <div class="x-detail-label">Kategori</div>
                            <div class="x-detail-value">{{ $e->category }}</div>
                        </div>
                        <div>
                            <div class="x-detail-label">Nominal</div>
                            <div class="x-detail-value money money-main">{{ $e->formatted_amount }}</div>
                        </div>
                        <div>
                            <div class="x-detail-label">Tanggal Pembelian</div>
                            <div class="x-detail-value">{{ $e->date?->translatedFormat('d F Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="x-detail-label">Cara Pembelian</div>
                            <div class="x-detail-value">{{ $e->purchase_method }}</div>
                        </div>
                        <div>
                            <div class="x-detail-label">Dicatat Oleh</div>
                            <div class="x-detail-value">{{ $e->creator->display_name ?? $e->creator->username ?? '-' }}</div>
                        </div>
                        @if ($e->description)
                            <div class="full">
                                <div class="x-detail-label">Keterangan</div>
                                <div class="x-pre">{{ $e->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-paperclip"></i> Lampiran</div>
                    <div class="sd-icon-row">
                        @if ($e->receipt_path)
                            <a href="{{ asset('storage/' . $e->receipt_path) }}" target="_blank" rel="noopener" class="btn btn-secondary">
                                <i class="fa-solid fa-receipt"></i> Lihat Nota
                            </a>
                        @endif
                        @if ($e->isOnline() && $e->purchase_link)
                            <a href="{{ $e->purchase_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Link Pembelian
                            </a>
                        @endif
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-signature"></i> Tanda Tangan</div>
                    <div class="x-detail-grid">
                        <div>
                            <div class="x-detail-label">Admin</div>
                            @if ($e->admin_signature_path)
                                <img src="{{ asset('storage/' . $e->admin_signature_path) }}" alt="TTD Admin"
                                     style="max-height:90px;max-width:100%;border:1px solid #e5e7eb;border-radius:8px;background:#fff;padding:6px;">
                                <div class="x-detail-value">{{ $e->admin_signer_name }}</div>
                                <div class="form-hint">{{ $e->admin_signed_at?->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <div class="x-detail-value">-</div>
                            @endif
                        </div>
                        <div>
                            <div class="x-detail-label">Owner</div>
                            @if ($e->owner_signature_path)
                                <img src="{{ asset('storage/' . $e->owner_signature_path) }}" alt="TTD Owner"
                                     style="max-height:90px;max-width:100%;border:1px solid #e5e7eb;border-radius:8px;background:#fff;padding:6px;">
                                <div class="x-detail-value">{{ $e->owner_signer_name }}</div>
                                <div class="form-hint">{{ $e->owner_signed_at?->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <div class="x-detail-value">
                                    {{ $e->status === 'Ditolak' ? 'Ditolak' : 'Belum ditandatangani' }}
                                </div>
                            @endif
                        </div>
                        @if ($e->status === 'Ditolak' && $e->owner_note)
                            <div class="full">
                                <div class="x-detail-label">Catatan Owner</div>
                                <div class="x-pre">{{ $e->owner_note }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <div class="modal-foot-left"></div>
                <div class="modal-foot-right">
                    <button type="button" class="btn btn-secondary" data-close-modal>Tutup</button>
                </div>
            </div>
        </div>
    </div>
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

        // ---------- Pad tanda tangan (per modal) ----------
        function initSignaturePad(overlay) {
            var canvas = overlay.querySelector('.js-sig-canvas');
            if (!canvas || canvas.dataset.ready) { return; }

            var input   = overlay.querySelector('.js-sig-input');
            var section = overlay.querySelector('.js-sig-section');
            var form    = overlay.querySelector('.js-sign-form');
            var select  = overlay.querySelector('.js-decision');   // hanya ada di modal Owner
            var note    = overlay.querySelector('.js-note');
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
                ctx.lineTo(p.x + 0.01, p.y + 0.01);
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

            // Modal Owner: TTD hanya diperlukan saat keputusan = Approved
            if (select) {
                select.addEventListener('change', function () {
                    section.style.display = select.value === 'Approved' ? '' : 'none';
                });
            }

            form.addEventListener('submit', function (e) {
                var needSignature = !select || select.value === 'Approved';
                if (needSignature && !input.value) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum dikirim.');
                    return;
                }
                if (select && select.value === 'Rejected' && note && !note.value.trim()) {
                    e.preventDefault();
                    alert('Alasan penolakan wajib diisi.');
                }
            });
        }

        // ---------- Toggle kolom link saat cara pembelian = Online ----------
        document.addEventListener('change', function (e) {
            if (!e.target.classList || !e.target.classList.contains('js-method')) { return; }
            var form  = e.target.closest('form');
            var field = form.querySelector('.js-link-field');
            var input = field ? field.querySelector('input') : null;
            var online = e.target.value === 'Online';
            if (field) { field.style.display = online ? '' : 'none'; }
            if (input) {
                input.required = online;
                if (!online) { input.value = ''; }
            }
        });

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
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                closeModal(e.target);
            }
        });

        // ---------- Filter: bulan dan rentang tanggal tidak dipakai bersamaan ----------
        var fBulan = document.getElementById('filterBulan');
        var fFrom  = document.getElementById('filterFrom');
        var fTo    = document.getElementById('filterTo');
        var fPeriode = document.getElementById('filterPeriode');
        if (fBulan && fFrom && fTo) {
            fBulan.addEventListener('change', function () {
                if (fBulan.value) { fFrom.value = ''; fTo.value = ''; if (fPeriode) fPeriode.disabled = true; }
            });
            [fFrom, fTo].forEach(function (el) {
                el.addEventListener('change', function () {
                    if (el.value) { fBulan.value = ''; if (fPeriode) fPeriode.disabled = true; }
                });
            });
        }

        // ---------- Filter otomatis ----------
        var filterForm = document.getElementById('filterForm');
        if (filterForm) {
            var timer = null;
            filterForm.addEventListener('change', function (e) {
                clearTimeout(timer);
                var isDate = e.target.type === 'date' || e.target.type === 'month';
                timer = setTimeout(function () { filterForm.submit(); }, isDate ? 700 : 0);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.open').forEach(closeModal);
            }
        });

        // ---------- Buka lagi modal yang gagal validasi ----------
        @if ($errors->any() && old('_form'))
            openModal(@json(old('_form')));
        @endif
    })();
</script>
@endpush