@extends('admin.app')

@section('title', 'Pembayaran | Keuangan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

        // satu paginator untuk footer, sesuai tampilan yang aktif
        $paginator = $view === 'tagihan' ? $enrollments : $transactions;
        $unitLabel = $view === 'tagihan' ? 'enrollment' : 'transaksi';
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">KEUANGAN</div>
            <h1>Pembayaran</h1>
            <p>
                Pantau tagihan tiap siswa dan catat pembayaran lanjutan (Termin 1–3 atau Pelunasan).
                Pembayaran pertama (DP/Lunas) tercatat otomatis saat calon siswa dikonversi.
                Pilih tampilan <strong>Transaksi</strong> untuk melihat semua pembayaran, termasuk Kelas Pengganti dan Home Visit.
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
            <div class="stat-note warn">Belum terbayar &middot; termasuk tagihan periode sebelumnya</div>
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
                <span class="stat-label">Terkumpul {{ $period['label'] }}</span>
                <i class="fa-solid fa-wallet stat-icon"></i>
            </div>
            <div class="stat-value">{{ $rupiah($collected) }}</div>
            <div class="stat-note">Semua jenis pembayaran &middot; {{ $period['note'] }}</div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-top">
                <span class="stat-label">Sudah Lunas</span>
                <i class="fa-solid fa-circle-check stat-icon"></i>
            </div>
            <div class="stat-value">{{ $paidCount }}</div>
            <div class="stat-note">Enrollment lunas &middot; {{ $period['note'] }}</div>
        </div>
    </div>

    {{-- ========================= FILTER ========================= --}}
    <form method="GET" action="{{ route('admin.pembayaran') }}" id="filterForm">
        <div class="toolbar">
            <div class="toolbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa">
            </div>

            {{-- Jenis: reguler (per kategori), private, dan biaya tambahan --}}
            <select name="type" class="select-chip" onchange="this.form.submit()">
                <option value="">Semua Jenis</option>

                <optgroup label="Reguler">
                    <option value="reguler" {{ request('type') === 'reguler' ? 'selected' : '' }}>Semua Reguler</option>
                    @foreach ($categories as $c)
                        <option value="kategori:{{ $c->id }}" {{ request('type') === 'kategori:' . $c->id ? 'selected' : '' }}>
                            {{ $c->program?->program_name }} – {{ $c->category_name }}
                        </option>
                    @endforeach
                </optgroup>

                <option value="private" {{ request('type') === 'private' ? 'selected' : '' }}>Private</option>

                <optgroup label="Biaya Tambahan">
                    <option value="kelas_pengganti" {{ request('type') === 'kelas_pengganti' ? 'selected' : '' }}>Kelas Pengganti</option>
                    <option value="home_visit" {{ request('type') === 'home_visit' ? 'selected' : '' }}>Home Visit</option>
                </optgroup>
            </select>

            {{-- Tampilan (Tagihan/Transaksi) dipilih lewat tab di bawah; dibawa lewat hidden input --}}
            <input type="hidden" name="view" value="{{ $view }}">

            {{-- Status bayar hanya relevan untuk tampilan Tagihan --}}
            @if ($view === 'tagihan')
                <select name="payment_status" class="select-chip" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="belum" {{ request('payment_status') === 'belum' ? 'selected' : '' }}>Belum bayar</option>
                    <option value="sebagian" {{ request('payment_status') === 'sebagian' ? 'selected' : '' }}>Bayar sebagian</option>
                    <option value="lunas" {{ request('payment_status') === 'lunas' ? 'selected' : '' }}>Lunas</option>
                </select>
            @endif

            {{-- Rentang tanggal: Tagihan = tanggal daftar / tanggal bayar / masih ada sisa. Transaksi & Terkumpul = tanggal bayar. Default bulan ini. --}}
            <div class="date-range">
                <i class="fa-regular fa-calendar date-icon"></i>
                <input type="text" id="dateRange" class="date-range-input" placeholder="Pilih rentang tanggal" readonly>
                <input type="hidden" name="from" id="filter_from" value="{{ $from }}">
                <input type="hidden" name="to" id="filter_to" value="{{ $to }}">

                {{-- tombol × hanya muncul kalau rentang bukan default (bulan ini) --}}
                @if (! ($period['key'] === 'bulan' && $period['current']))
                    <button type="button" class="btn" id="dateRangeClear" title="Kembali ke bulan ini">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                @endif
            </div>
        </div>
    </form>

    {{-- ========================= TAB: TAGIHAN / TRANSAKSI ========================= --}}
    @php
        $tabQuery = request()->except(['view', 'page']);
    @endphp
    <div class="pkg-tabs" role="tablist">
        <a class="pkg-tab {{ $view === 'tagihan' ? 'active' : '' }}"
           href="{{ route('admin.pembayaran', array_merge($tabQuery, ['view' => 'tagihan'])) }}">
            <i class="fa-solid fa-file-invoice-dollar"></i> Tagihan
        </a>
        <a class="pkg-tab {{ $view === 'transaksi' ? 'active' : '' }}"
           href="{{ route('admin.pembayaran', array_merge(\Illuminate\Support\Arr::except($tabQuery, ['payment_status']), ['view' => 'transaksi'])) }}">
            <i class="fa-solid fa-receipt"></i> Transaksi
        </a>
    </div>

    {{-- ========================= TABEL ========================= --}}
    <div class="card">

        @if ($view === 'tagihan')
            {{-- ---------- TAMPILAN TAGIHAN (per enrollment) ---------- --}}
            <table>
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Paket</th>
                        <th style="text-align: center; vertical-align: middle;">Tagihan</th>
                        <th style="text-align: center; vertical-align: middle;">Terbayar</th>
                        <th style="text-align: center; vertical-align: middle;">Sisa</th>
                        <th style="text-align: center; vertical-align: middle;">Status</th>
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
                                @if ($enrollment->category_name)
                                    <div class="cand-sub">{{ $enrollment->category_name }}</div>
                                @endif
                            </td>

                            <td class="money" style="text-align: center; vertical-align: middle;">{{ $rupiah($bill) }}</td>

                            <td class="money" style="text-align: center; vertical-align: middle;">
                                {{ $rupiah($paid) }}
                                <div class="pay-progress {{ $remaining > 0 ? 'is-partial' : '' }}">
                                    <span style="width: {{ $percent }}%;"></span>
                                </div>
                            </td>

                            <td class="money money-remaining {{ $remaining > 0 ? 'is-due' : 'is-zero' }}" style="text-align: center; vertical-align: middle;">
                                {{ $rupiah($remaining) }}
                            </td>

                            <td class="pay-status-cell" style="text-align: center; vertical-align: middle;">
                                <span class="badge {{ $statusClass }}">
                                    <span class="badge-dot"></span> {{ $statusLabel }}
                                </span>
                            </td>

                            <td style="text-align: center; vertical-align: middle;">
                                <div class="row-actions" style="display: flex; justify-content: center; align-items: center; gap: 8px;">
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
                                                data-next-amount="{{ $enrollment->next_amount }}"
                                                data-next-min="{{ $enrollment->next_min }}"
                                                data-next-exact="{{ $enrollment->next_exact ? 1 : 0 }}"
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
        @else
            {{-- ---------- TAMPILAN TRANSAKSI (satu baris per pembayaran, semua jenis) ---------- --}}
            <table>
                <thead>
                    <tr>
                        <th>Tanggal / Invoice</th>
                        <th>Siswa</th>
                        <th>Pembayaran</th>
                        <th>Nominal / Metode</th>
                        <th width="120" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($transactions as $p)
                        @php
                            [$jenis, $jenisClass] = match ($p->payment_type) {
                                'Replacement' => ['Kelas Pengganti', 'is-replacement'],
                                'Home Visit'  => ['Home Visit', 'is-homevisit'],
                                default       => [$p->enrollment?->private_package_id ? 'Private' : 'Reguler', ''],
                            };
                            $paket = $p->enrollment?->programPackage?->package_name
                                ?? $p->enrollment?->privatePackage?->package_name
                                ?? '-';
                        @endphp
                        <tr>
                            <td>
                                <div class="cand-name">{{ $p->payment_date?->format('d M Y') ?? '-' }}</div>
                                <div class="cand-sub">{{ $p->invoice_number ?? '-' }}</div>
                            </td>

                            <td>
                                <div class="cand-name">{{ $p->enrollment?->student?->name ?? '-' }}</div>
                                <div class="cand-sub">Enrollment #{{ $p->enrollment_id }}</div>
                            </td>

                            <td>
                                {{ $p->payment_stage ?? '-' }}
                                <span class="pay-type-tag {{ $jenisClass }}">{{ $jenis }}</span>
                                <div class="cand-sub">{{ $paket }}</div>
                            </td>

                            <td>
                                <div class="money-main">{{ $rupiah($p->amount_paid) }}</div>
                                <div class="cand-sub">{{ $p->payment_method ?? '-' }}</div>
                            </td>

                            <td style="text-align: center; vertical-align: middle;">
                                <div class="row-actions" style="display: flex; justify-content: center; align-items: center;">
                                    @if ($p->payment_proof_path)
                                        @php
                                            $isPdf = str_ends_with(strtolower($p->payment_proof_path), '.pdf');
                                        @endphp
                                        <a href="{{ asset('storage/' . $p->payment_proof_path) }}"
                                        target="_blank" rel="noopener"
                                        class="icon-btn" title="Lihat bukti pembayaran">
                                            <i class="fa-solid {{ $isPdf ? 'fa-file-pdf' : 'fa-image' }}"></i>
                                        </a>
                                    @else
                                        <span class="icon-btn is-disabled" title="Tidak ada bukti pembayaran">
                                            <i class="fa-solid fa-image"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:50px;color:#888;">
                                Belum ada transaksi pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        {{-- ---------- FOOTER + PAGINASI (dipakai kedua tampilan) ---------- --}}
        <div class="table-footer">
            <span class="table-footer-info">
                Menampilkan
                <strong>{{ $paginator->firstItem() ?? 0 }}</strong> -
                <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $paginator->total() }}</strong> {{ $unitLabel }}
            </span>

            <div class="table-footer-pagination">
                @if ($paginator->hasPages())
                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($paginator->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
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
                                        <li class="page-item"><a class="page-btn" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($paginator->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
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
                        {{-- Tahap ditentukan sistem (DP / Termin 1-3 / Pelunasan), hanya ditampilkan --}}
                        <div class="form-field">
                            <label>Tahap Pembayaran</label>
                            <input type="text" id="pay_stage" readonly>
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js"></script>
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
       FILTER TANGGAL (kalender rentang)
    ============================================================ */
    (function () {
        const form = document.getElementById('filterForm');
        const fromInput = document.getElementById('filter_from');
        const toInput = document.getElementById('filter_to');
        const clearBtn = document.getElementById('dateRangeClear');

        const initial = [fromInput.value, toInput.value].filter(Boolean);

        flatpickr('#dateRange', {
            mode: 'range',
            locale: 'id',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M Y',
            altInputClass: 'date-range-input',
            showMonths: 2,
            defaultDate: initial.length ? initial : null,
            onClose(dates, _str, instance) {
                if (dates.length === 0) return;

                // satu tanggal saja = rentang satu hari
                const from = instance.formatDate(dates[0], 'Y-m-d');
                const to = instance.formatDate(dates[1] ?? dates[0], 'Y-m-d');

                if (from === fromInput.value && to === toInput.value) return;

                fromInput.value = from;
                toInput.value = to;
                form.submit();
            },
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                fromInput.value = '';
                toInput.value = '';
                form.submit();
            });
        }
    })();

    /* ============================================================
       MODAL: CATAT PEMBAYARAN
    ============================================================ */
    function openPayModal(btn) {
        const remaining = Number(btn.dataset.remaining);

        document.getElementById('payForm').action = `/admin/pembayaran/${btn.dataset.id}`;
        document.getElementById('pay_sub').textContent = `${btn.dataset.name} · ${btn.dataset.package}`;
        document.getElementById('pay_stage').value = btn.dataset.nextStage || '';

        const amount = document.getElementById('pay_amount');
        const hint = document.getElementById('pay_remaining_hint');
        const exact = btn.dataset.nextExact === '1';
        const nextAmount = Number(btn.dataset.nextAmount);
        const nextMin = Number(btn.dataset.nextMin);

        amount.max = remaining;

        if (exact && nextAmount > 0) {
            // Termin 1-3 / Pelunasan: nominal ditentukan sistem
            amount.value = nextAmount;
            amount.min = nextAmount;
            amount.readOnly = true;
            hint.textContent = `Nominal ${btn.dataset.nextStage} sesuai jadwal. Sisa tagihan ${rupiah(remaining)}`;
        } else {
            // DP: nominal bebas, minimal sebesar biaya pendaftaran
            amount.value = '';
            amount.min = nextMin > 0 ? nextMin : 1;
            amount.readOnly = false;
            hint.textContent = `Minimal DP ${rupiah(nextMin)}. Sisa tagihan ${rupiah(remaining)}`;
        }

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
    // payment_type di database: Package | Replacement | Home Visit
    const PAYMENT_TYPE_LABEL = {
        'Package': 'Paket',
        'Replacement': 'Kelas Pengganti',
        'Home Visit': 'Home Visit',
    };

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
                        <td>
                            <strong>${p.stage ?? '-'}</strong>
                            <div style="margin-top:4px;">
                                <span class="badge ${p.payment_type === 'Package' ? 'bg-primary' : 'bg-info'}">
                                    ${PAYMENT_TYPE_LABEL[p.payment_type] ?? p.payment_type ?? '-'}
                                </span>
                            </div>
                        </td>
                        <td>${p.date ?? '-'}</td>
                        <td>${rupiah(p.amount_paid)}</td>
                        <td>${p.method ?? '-'}</td>
                        <td>${p.invoice_number ?? '-'}</td>
                        <td>${p.proof_url
                            ? `<a href="${p.proof_url}" target="_blank" rel="noopener">Lihat</a>`
                            : '-'}</td>
                    </tr>
                `).join('');
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