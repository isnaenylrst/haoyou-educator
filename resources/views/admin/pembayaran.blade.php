@extends('admin.app')

@section('title', 'Pembayaran | Keuangan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
    <style>
        .alert-success, .alert-error { border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 13px; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-error ul { margin: 0; padding-left: 18px; }

        .pay-progress { width: 100%; height: 6px; border-radius: 999px; background: #f1f2f4; overflow: hidden; margin-top: 6px; }
        .pay-progress > span { display: block; height: 100%; background: #22c55e; }
        .pay-progress.is-partial > span { background: #f59e0b; }

        .money { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .money-remaining { font-weight: 700; }
        .money-remaining.is-zero { color: #15803d; }
        .money-remaining.is-due { color: #dc2626; }

        .pay-type-tag { display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 999px; background: rgba(201,162,39,.12); color: #b8860b; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }

        .pay-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
        .pay-summary > div { background: #fafafa; border: 1px solid #edeef1; border-radius: 10px; padding: 10px 14px; }
        .pay-summary .lbl { font-size: 11px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .pay-summary .val { font-size: 15px; font-weight: 700; color: #111827; margin-top: 2px; }

        .history-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .history-table th { text-align: left; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; padding: 6px 8px; border-bottom: 1px solid #edeef1; }
        .history-table td { padding: 8px; border-bottom: 1px solid #f4f5f7; vertical-align: top; }
        .history-empty { text-align: center; color: #9ca3af; padding: 24px 0; font-size: 13px; }
    </style>
@endpush

@section('content')

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">KEUANGAN</div>
            <h1>Pembayaran</h1>
            <p>
                Pantau tagihan tiap siswa dan catat pembayaran lanjutan (Termin 1–3 atau Pelunasan).
                Pembayaran pertama (DP/Lunas) tercatat otomatis saat calon siswa dikonversi.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================= STATISTIK ========================= --}}
    <div class="stat-row">
        <div class="stat-card c-red">
            <div class="stat-top">
                <span class="stat-label">Total Sisa Tagihan</span>
                <i class="fa-solid fa-file-invoice-dollar stat-icon"></i>
            </div>
            <div class="stat-value">{{ $rupiah($outstanding) }}</div>
            <div class="stat-note warn">Belum terbayar</div>
        </div>

        <div class="stat-card c-yellow">
            <div class="stat-top">
                <span class="stat-label">Siswa Belum Lunas</span>
                <i class="fa-solid fa-hourglass-half stat-icon"></i>
            </div>
            <div class="stat-value">{{ $unpaidCount }}</div>
            <div class="stat-note">Masih punya sisa tagihan</div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-top">
                <span class="stat-label">Terkumpul Bulan Ini</span>
                <i class="fa-solid fa-wallet stat-icon"></i>
            </div>
            <div class="stat-value">{{ $rupiah($collectedThisMonth) }}</div>
            <div class="stat-note">{{ now()->translatedFormat('F Y') }}</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Sudah Lunas</span>
                <i class="fa-solid fa-circle-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $paidCount }}</div>
            <div class="stat-note">Enrollment lunas</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.pembayaran') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa">
            </div>

            <select name="type" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="Reguler" {{ request('type') === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                <option value="Private" {{ request('type') === 'Private' ? 'selected' : '' }}>Private</option>
            </select>

            <select name="payment_status" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="belum" {{ request('payment_status') === 'belum' ? 'selected' : '' }}>Belum bayar</option>
                <option value="sebagian" {{ request('payment_status') === 'sebagian' ? 'selected' : '' }}>Bayar sebagian</option>
                <option value="lunas" {{ request('payment_status') === 'lunas' ? 'selected' : '' }}>Lunas</option>
            </select>
        </div>
    </form>

    {{-- ========================= TABEL ========================= --}}
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Paket</th>
                    <th>Tagihan</th>
                    <th>Terbayar</th>
                    <th>Sisa</th>
                    <th>Status</th>
                    <th width="120" style="text-align:center;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($enrollments as $enrollment)
                    @php
                        $bill      = (float) $enrollment->total_bill;
                        $paid      = (float) $enrollment->total_paid;
                        $remaining = max($bill - $paid, 0);
                        $percent   = $bill > 0 ? min(100, round($paid / $bill * 100)) : 0;
                        $isPrivate = ! is_null($enrollment->private_package_id);
                        $packageName = $enrollment->programPackage?->package_name
                            ?? $enrollment->privatePackage?->package_name
                            ?? '-';

                        if ($bill > 0 && $remaining <= 0) {
                            [$statusLabel, $statusClass] = ['Lunas', 'badge-completed'];
                        } elseif ($paid > 0) {
                            [$statusLabel, $statusClass] = ['Sebagian', 'badge-warm'];
                        } else {
                            [$statusLabel, $statusClass] = ['Belum bayar', 'badge-cancelled'];
                        }
                    @endphp
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="avatar-sm">{{ strtoupper(substr($enrollment->student?->name ?? '?', 0, 1)) }}</div>
                                <div>
                                    <div class="cand-name">{{ $enrollment->student?->name ?? '-' }}</div>
                                    <div class="cand-sub">Enrollment #{{ $enrollment->id }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            {{ $packageName }}
                            @if ($isPrivate)
                                <span class="pay-type-tag">Private</span>
                            @endif
                        </td>

                        <td class="money">{{ $rupiah($bill) }}</td>

                        <td class="money">
                            {{ $rupiah($paid) }}
                            <div class="pay-progress {{ $remaining > 0 ? 'is-partial' : '' }}">
                                <span style="width: {{ $percent }}%;"></span>
                            </div>
                        </td>

                        <td class="money money-remaining {{ $remaining > 0 ? 'is-due' : 'is-zero' }}">
                            {{ $rupiah($remaining) }}
                        </td>

                        <td>
                            <span class="badge {{ $statusClass }}">
                                <span class="badge-dot"></span> {{ $statusLabel }}
                            </span>
                        </td>

                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-btn" title="Riwayat pembayaran"
                                        onclick="openHistoryModal({{ $enrollment->id }})">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </button>

                                @if ($remaining > 0)
                                    <button type="button" class="icon-btn convert" title="Catat pembayaran"
                                            data-id="{{ $enrollment->id }}"
                                            data-name="{{ $enrollment->student?->name }}"
                                            data-package="{{ $packageName }}"
                                            data-remaining="{{ $remaining }}"
                                            data-next-stage="{{ $enrollment->next_stage }}"
                                            onclick="openPayModal(this)">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:50px;color:#888;">
                            Belum ada data pembayaran.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $enrollments->firstItem() ?? 0 }}</strong> -
                <strong>{{ $enrollments->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $enrollments->total() }}</strong> enrollment
            </span>

            <div class="table-footer-pagination">
                @if ($enrollments->hasPages())
                    @php
                        $current = $enrollments->currentPage();
                        $last = $enrollments->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($enrollments->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $enrollments->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
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
                                        <li class="page-item"><a class="page-btn" href="{{ $enrollments->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($enrollments->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $enrollments->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — CATAT PEMBAYARAN ========================= --}}
    <div class="modal-overlay" id="payModalOverlay">
        <div class="modal modal-sm">
            <form id="payForm" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-head">
                    <div>
                        <div class="modal-title">Catat Pembayaran</div>
                        <div class="modal-sub" id="pay_sub">-</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closePayModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Tahap Pembayaran *</label>
                            <select name="payment_stage" id="pay_stage" required>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage }}">{{ $stage }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Nominal Dibayar *</label>
                            <input type="number" name="amount_paid" id="pay_amount" min="1" step="0.01" required>
                            <small id="pay_remaining_hint" style="font-size:11px;color:#6b7280;"></small>
                        </div>

                        <div class="form-field">
                            <label>Metode Pembayaran *</label>
                            <select name="payment_method" required>
                                <option value="">Pilih Metode</option>
                                @foreach ($methods as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Tanggal Pembayaran *</label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                        </div>

                        <div class="form-field full">
                            <label>Bukti Pembayaran (opsional)</label>
                            <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf">
                            <small style="font-size:11px;color:#6b7280;">JPG, PNG, atau PDF, maksimal 2 MB.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn" onclick="closePayModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= MODAL — RIWAYAT ========================= --}}
    <div class="modal-overlay" id="historyModalOverlay">
        <div class="modal">
            <div class="modal-head">
                <div>
                    <div class="modal-title" id="history_title">Riwayat Pembayaran</div>
                    <div class="modal-sub" id="history_sub">-</div>
                </div>
                <button type="button" class="modal-close" onclick="closeHistoryModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div class="pay-summary">
                    <div><div class="lbl">Tagihan</div><div class="val" id="history_bill">-</div></div>
                    <div><div class="lbl">Terbayar</div><div class="val" id="history_paid">-</div></div>
                    <div><div class="lbl">Sisa</div><div class="val" id="history_remaining">-</div></div>
                </div>

                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Tahap</th>
                            <th>Tanggal</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Invoice</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody id="history_rows"></tbody>
                </table>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn" onclick="closeHistoryModal()">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const rupiah = n => 'Rp ' + Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

    (function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => filterForm.submit(), 600);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                filterForm.submit();
            }
        });
    })();

    /* ============================================================
       MODAL: CATAT PEMBAYARAN
    ============================================================ */
    function openPayModal(btn) {
        const remaining = Number(btn.dataset.remaining);

        document.getElementById('payForm').action = `/admin/pembayaran/${btn.dataset.id}`;
        document.getElementById('pay_sub').textContent = `${btn.dataset.name} · ${btn.dataset.package}`;
        document.getElementById('pay_stage').value = btn.dataset.nextStage;

        const amount = document.getElementById('pay_amount');
        amount.value = '';
        amount.max = remaining;
        document.getElementById('pay_remaining_hint').textContent = `Sisa tagihan ${rupiah(remaining)}`;

        document.getElementById('payModalOverlay').classList.add('open');
    }

    function closePayModal() {
        document.getElementById('payModalOverlay').classList.remove('open');
    }

    document.getElementById('payModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closePayModal();
    });

    /* ============================================================
       MODAL: RIWAYAT PEMBAYARAN
    ============================================================ */
    function openHistoryModal(id) {
        const overlay = document.getElementById('historyModalOverlay');
        const rows = document.getElementById('history_rows');

        rows.innerHTML = '<tr><td colspan="6" class="history-empty">Memuat...</td></tr>';
        overlay.classList.add('open');

        fetch(`/admin/pembayaran/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(data => {
                document.getElementById('history_title').textContent = data.student_name ?? 'Riwayat Pembayaran';
                document.getElementById('history_sub').textContent = data.package_name ?? '-';
                document.getElementById('history_bill').textContent = rupiah(data.total_bill);
                document.getElementById('history_paid').textContent = rupiah(data.total_paid);
                document.getElementById('history_remaining').textContent = rupiah(data.remaining);

                if (!data.payments.length) {
                    rows.innerHTML = '<tr><td colspan="6" class="history-empty">Belum ada pembayaran.</td></tr>';
                    return;
                }

                rows.innerHTML = data.payments.map(p => `
                    <tr>
                        <td><strong>${p.stage ?? '-'}</strong></td>
                        <td>${p.date ?? '-'}</td>
                        <td>${rupiah(p.amount_paid)}</td>
                        <td>${p.method ?? '-'}</td>
                        <td>${p.invoice_number ?? '-'}</td>
                        <td>${p.proof_url ? `<a href="${p.proof_url}" target="_blank" rel="noopener">Lihat</a>` : '-'}</td>
                    </tr>`).join('');
            })
            .catch(() => {
                rows.innerHTML = '<tr><td colspan="6" class="history-empty">Gagal memuat riwayat.</td></tr>';
            });
    }

    function closeHistoryModal() {
        document.getElementById('historyModalOverlay').classList.remove('open');
    }

    document.getElementById('historyModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeHistoryModal();
    });
</script>
@endpush