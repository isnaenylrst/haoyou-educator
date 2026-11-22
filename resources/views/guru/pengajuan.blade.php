@extends('layouts.guru')

@section('title', 'Pengajuan Reimburse & Pembelian')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-warning me-2"></i> Pengajuan Reimburse & Pembelian Bahan</h4>
            <p class="text-muted small mb-0">Ajukan reimburse pembelian perlengkapan kelas, pembelian bahan ajar, dan sertifikat kelulusan.</p>
        </div>
        <div>
            <button type="button" class="btn btn-warning rounded-pill px-4 fw-semibold text-dark" data-bs-toggle="modal" data-bs-target="#modalTambahPengajuan">
                <i class="fa-solid fa-plus me-1"></i> Buat Pengajuan Baru
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <strong class="d-block mb-1">Pengajuan belum dapat disimpan:</strong>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- RIWAYAT PENGAJUAN --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 fw-bold border-bottom">
            Riwayat Pengajuan Saya ({{ $submissions->total() }})
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jenis Pengajuan</th>
                        <th>Nama Pengajuan</th>
                        <th>Nominal Total</th>
                        <th>Rekening</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Bukti / Nota</th>
                        <th>Status</th>
                        <th>Catatan</th>
                        <th class="text-center">Dokumen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $sub)
                        <tr>
                            <td>
                                <span class="badge bg-warning-subtle text-dark border border-warning">
                                    {{ $sub->type }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $sub->item_name }}</div>
                                @if(!empty($sub->items) && is_array($sub->items))
                                    <span class="badge bg-light text-dark border me-1">
                                        <i class="fa-solid fa-boxes-stacked me-1"></i> {{ count($sub->items) }} Item
                                    </span>
                                @endif
                                <small class="text-muted d-block">{{ Str::limit($sub->description, 50) ?: '-' }}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
                            </td>
                            <td>
                                @if($sub->account_number)
                                    <div class="small fw-semibold">{{ $sub->account_number }}</div>
                                    <div class="small text-muted">a/n {{ $sub->account_name ?: '-' }}</div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>{{ $sub->date->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @if($sub->receipt_path)
                                        <a href="{{ asset('storage/' . $sub->receipt_path) }}" target="_blank" class="small text-primary text-decoration-none">
                                            <i class="fa-solid fa-receipt me-1"></i> Nota
                                        </a>
                                    @endif
                                    @if($sub->photo_path)
                                        <a href="{{ asset('storage/' . $sub->photo_path) }}" target="_blank" class="small text-success text-decoration-none">
                                            <i class="fa-solid fa-image me-1"></i> Foto Bahan
                                        </a>
                                    @endif
                                    @if(!$sub->receipt_path && !$sub->photo_path)
                                        <span class="small text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $sub->status_badge }} mb-1">{{ $sub->status }}</span>
                                @if($sub->status === 'Diajukan')
                                    <div class="small text-secondary" style="font-size:11px;"><i class="fa-regular fa-clock me-1"></i> Menunggu Kurikulum</div>
                                @elseif($sub->status === 'Review Admin' || $sub->status === 'Verifikasi Admin')
                                    <div class="small text-secondary" style="font-size:11px;"><i class="fa-regular fa-clock me-1"></i> Verifikasi Admin</div>
                                @elseif($sub->status === 'Review Owner')
                                    <div class="small text-primary" style="font-size:11px;"><i class="fa-solid fa-user-tie me-1"></i> Menunggu Owner</div>
                                @elseif(in_array($sub->status, ['Disetujui', 'Dibayar', 'Selesai']))
                                    <div class="small text-success" style="font-size:11px;"><i class="fa-solid fa-check-double me-1"></i> Disetujui Penuh</div>
                                @elseif($sub->status === 'Ditolak')
                                    <div class="small text-danger" style="font-size:11px;"><i class="fa-solid fa-ban me-1"></i> Ditolak</div>
                                @endif
                            </td>
                            <td>
                                @if($sub->curriculum_note || $sub->admin_note || $sub->owner_note)
                                    <div class="small text-muted" style="max-width: 200px;">
                                        @if($sub->curriculum_note)
                                            <div><strong class="text-dark">Kurikulum:</strong> {{ Str::limit($sub->curriculum_note, 40) }}</div>
                                        @endif
                                        @if($sub->admin_note)
                                            <div><strong class="text-dark">Admin:</strong> {{ Str::limit($sub->admin_note, 40) }}</div>
                                        @endif
                                        @if($sub->owner_note)
                                            <div><strong class="text-dark">Owner:</strong> {{ Str::limit($sub->owner_note, 40) }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3" title="Lihat & Cetak Dokumen Pengajuan">
                                    <i class="fa-solid fa-file-pdf me-1"></i> Lihat Dokumen
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                                Belum ada pengajuan reimburse / pembelian yang dibuat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($submissions->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL TAMBAH PENGAJUAN --}}
<div class="modal fade" id="modalTambahPengajuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('teacher.pengajuan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">FORM PENGAJUAN PEMBELIAN BAHAN AJAR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4">
                        <i class="fa-solid fa-user-pen text-primary me-2"></i>
                        Saya <strong>({{ Auth::user()->teacher->name ?? Auth::user()->name }})</strong> yang bertandatangan di bawah ini mengajukan pembelian bahan ajar dengan rincian sebagai berikut:
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Jenis Pengajuan <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="Pembelian Bahan Ajar" selected>Pembelian Bahan Ajar (Pengadaan Baru)</option>
                                <option value="Reimburse Bahan Ajar">Reimburse Bahan Ajar (Penggantian Nota)</option>
                                <option value="Pembelian Sertifikat">Pembelian Sertifikat (Blanko/Cetak)</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tanggal Pengajuan <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Nama & Jadwal Kelas (Opsional)</label>
                            <select name="class_id" class="form-select" id="selectClassGlobal">
                                <option value="">-- Pilih Kelas Terkait (Bila ada) --</option>
                                @foreach($classes as $c)
                                    @php
                                        $schedStr = $c->schedules->map(fn($s) => $s->day . ' ' . substr($s->start_time, 0, 5))->implode(', ');
                                    @endphp
                                    <option value="{{ $c->id }}" data-label="{{ $c->class_name }}{{ $schedStr ? ' (' . $schedStr . ')' : '' }}">
                                        {{ $c->class_name }}{{ $schedStr ? ' (' . $schedStr . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Judul Pengajuan / Keperluan Utama <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="Contoh: Pengajuan Pembelian Modul & Flashcard Kelas Hanzi A" required>
                        </div>
                    </div>

                    {{-- ISIAN REKENING MANUAL GURU --}}
                    <div class="row bg-light rounded-3 p-2 mb-3 border mx-0">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold mb-1">Nomor Rekening <span class="text-danger">*</span></label>
                            <input type="text" name="account_number" class="form-control" placeholder="Contoh: 1234567890 (Hanya angka)" pattern="[0-9]+" required>
                            <small class="text-muted">Masukkan hanya nomor rekening tujuan pencairan (angka).</small>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold mb-1">Atas Nama Rekening (a/n rek) <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" class="form-control" placeholder="Contoh: Budi Santoso" required>
                            <small class="text-muted">Nama pemilik rekening sesuai buku tabungan.</small>
                        </div>
                    </div>

                    {{-- BAGIAN TABEL RINCIAN ITEM --}}
                    <div class="card border rounded-3 p-3 bg-white mb-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-table-list text-warning me-1"></i> Rincian Item Pembelian Bahan Ajar
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" id="btnAddItem">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Baris Item
                            </button>
                        </div>
                        <p class="small text-muted mb-2">Total baris otomatis terhitung: (Qty × Harga Satuan) + Ongkir. Banyak baris item dapat ditambah/dihapus.</p>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="itemsTable">
                                <thead class="table-light text-nowrap small text-center">
                                    <tr>
                                        <th style="min-width: 140px;">Nama Item <span class="text-danger">*</span></th>
                                        <th style="width: 70px;">Qty</th>
                                        <th style="min-width: 100px;">Harga Satuan (Rp)</th>
                                        <th style="min-width: 90px;">Ongkir (Rp)</th>
                                        <th style="min-width: 100px;">Total</th>
                                        <th style="min-width: 150px;">Nama & Jadwal Kelas</th>
                                        <th style="min-width: 110px;">Tgl Beli</th>
                                        <th style="min-width: 140px;">Link Pembelian</th>
                                        <th style="min-width: 110px;">Tgl Cek</th>
                                        <th style="min-width: 110px;">Tgl Kembali</th>
                                        <th style="width: 40px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsContainer">
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" name="items[0][name]" class="form-control form-control-sm item-name-input" placeholder="Nama item" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][qty]" class="form-control form-control-sm text-center item-qty-input" value="1" min="0" required oninput="calculateRow(this)">
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][price]" class="form-control form-control-sm text-end item-price-input" placeholder="0" min="0" required oninput="calculateRow(this)">
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][shipping]" class="form-control form-control-sm text-end item-shipping-input" placeholder="0" min="0" value="0" oninput="calculateRow(this)">
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][amount]" class="form-control form-control-sm text-end item-amount-input bg-light fw-bold" placeholder="0" readonly>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][class]" class="form-control form-control-sm item-class-input" placeholder="Nama & Jadwal Kelas">
                                        </td>
                                        <td>
                                            <input type="date" name="items[0][buy_date]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                                        </td>
                                        <td>
                                            <input type="url" name="items[0][link]" class="form-control form-control-sm" placeholder="https://tokopedia.com/...">
                                        </td>
                                        <td>
                                            <input type="date" name="items[0][check_date]" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="date" name="items[0][return_date]" class="form-control form-control-sm">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm text-danger p-0 btn-remove-item" onclick="removeItemRow(this)" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- TOTAL & TERBILANG --}}
                        <div class="p-3 bg-light rounded border mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark fs-6">TOTAL KESELURUHAN:</span>
                                <span class="fs-4 fw-bold text-success" id="displayTotalAmount">Rp 0</span>
                            </div>
                            <div class="small">
                                <strong class="text-secondary">TERBILANG:</strong>
                                <span class="fst-italic text-dark fw-semibold ms-1" id="displayTerbilang">Nol rupiah</span>
                            </div>
                        </div>
                        <input type="hidden" name="amount" id="inputTotalAmount" value="0">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Upload Nota / Kuitansi Pembayaran (Bila sudah dibeli)</label>
                            <input type="file" name="receipt_file" class="form-control" accept="image/*,application/pdf">
                            <small class="text-muted">Format PDF, JPG, PNG, WEBP (maksimal 10 MB)</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Upload Foto Bahan Ajar (Setelah Digunakan / Fisik Barang)</label>
                            <input type="file" name="photo_file" class="form-control" accept="image/*">
                            <small class="text-muted">Foto barang fisik yang telah dibeli / digunakan (maksimal 10 MB)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan Tambahan / Alasan Kebutuhan</label>
                        <textarea name="description" rows="2" class="form-control" placeholder="Jelaskan kebutuhan bahan ajar untuk kelas yang bersangkutan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-semibold px-4 text-dark">
                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let itemIndex = 1;

function formatRupiahLive(num) {
    return 'Rp ' + Math.round(num || 0).toLocaleString('id-ID');
}

// Client-side Terbilang Bahasa Indonesia
function terbilangIndo(angka) {
    const bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    angka = Math.floor(Math.abs(Number(angka) || 0));
    if (angka === 0) return 'Nol rupiah';

    function konversi(n) {
        if (n < 12) return bilangan[n];
        if (n < 20) return konversi(n - 10) + ' belas';
        if (n < 100) return konversi(Math.floor(n / 10)) + ' puluh ' + (n % 10 ? konversi(n % 10) : '');
        if (n < 200) return 'seratus ' + (n - 100 ? konversi(n - 100) : '');
        if (n < 1000) return konversi(Math.floor(n / 100)) + ' ratus ' + (n % 100 ? konversi(n % 100) : '');
        if (n < 2000) return 'seribu ' + (n - 1000 ? konversi(n - 1000) : '');
        if (n < 1000000) return konversi(Math.floor(n / 1000)) + ' ribu ' + (n % 1000 ? konversi(n % 1000) : '');
        if (n < 1000000000) return konversi(Math.floor(n / 1000000)) + ' juta ' + (n % 1000000 ? konversi(n % 1000000) : '');
        if (n < 1000000000000) return konversi(Math.floor(n / 1000000000)) + ' miliar ' + (n % 1000000000 ? konversi(n % 1000000000) : '');
        return konversi(Math.floor(n / 1000000000000)) + ' triliun ' + (n % 1000000000000 ? konversi(n % 1000000000000) : '');
    }

    let hasil = konversi(angka).replace(/\s+/g, ' ').trim();
    return hasil.charAt(0).toUpperCase() + hasil.slice(1) + ' rupiah';
}

document.getElementById('btnAddItem')?.addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const selectedClass = document.getElementById('selectClassGlobal');
    const defaultClass = selectedClass ? selectedClass.options[selectedClass.selectedIndex]?.dataset.label || '' : '';

    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <td>
            <input type="text" name="items[${itemIndex}][name]" class="form-control form-control-sm item-name-input" placeholder="Nama item" required>
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][qty]" class="form-control form-control-sm text-center item-qty-input" value="1" min="0" required oninput="calculateRow(this)">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][price]" class="form-control form-control-sm text-end item-price-input" placeholder="0" min="0" required oninput="calculateRow(this)">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][shipping]" class="form-control form-control-sm text-end item-shipping-input" placeholder="0" min="0" value="0" oninput="calculateRow(this)">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][amount]" class="form-control form-control-sm text-end item-amount-input bg-light fw-bold" placeholder="0" readonly>
        </td>
        <td>
            <input type="text" name="items[${itemIndex}][class]" class="form-control form-control-sm item-class-input" placeholder="Nama & Jadwal Kelas" value="${defaultClass}">
        </td>
        <td>
            <input type="date" name="items[${itemIndex}][buy_date]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
        </td>
        <td>
            <input type="url" name="items[${itemIndex}][link]" class="form-control form-control-sm" placeholder="https://...">
        </td>
        <td>
            <input type="date" name="items[${itemIndex}][check_date]" class="form-control form-control-sm">
        </td>
        <td>
            <input type="date" name="items[${itemIndex}][return_date]" class="form-control form-control-sm">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm text-danger p-0 btn-remove-item" onclick="removeItemRow(this)" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
        </td>
    `;
    container.appendChild(newRow);
    itemIndex++;
    calculateTotal();
});

document.getElementById('selectClassGlobal')?.addEventListener('change', function() {
    const label = this.options[this.selectedIndex]?.dataset.label || '';
    if (label) {
        document.querySelectorAll('.item-class-input').forEach(input => {
            if (!input.value) input.value = label;
        });
    }
});

function calculateRow(el) {
    const row = el.closest('.item-row');
    const qty = parseFloat(row.querySelector('.item-qty-input')?.value) || 0;
    const price = parseFloat(row.querySelector('.item-price-input')?.value) || 0;
    const shipping = parseFloat(row.querySelector('.item-shipping-input')?.value) || 0;
    const subtotal = Math.round((qty * price) + shipping);

    const amountInput = row.querySelector('.item-amount-input');
    if (amountInput) {
        amountInput.value = subtotal;
    }

    calculateTotal();
}

function removeItemRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        btn.closest('.item-row').remove();
        calculateTotal();
    } else {
        alert('Minimal harus ada 1 item barang yang diisi.');
    }
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price-input')?.value) || 0;
        const shipping = parseFloat(row.querySelector('.item-shipping-input')?.value) || 0;
        const subtotal = (qty * price) + shipping;
        total += subtotal;
    });
    document.getElementById('displayTotalAmount').innerText = formatRupiahLive(total);
    document.getElementById('inputTotalAmount').value = Math.round(total);
    document.getElementById('displayTerbilang').innerText = terbilangIndo(total);
}
</script>
@endpush
@endsection
