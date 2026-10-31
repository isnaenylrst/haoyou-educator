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
                        <th>Nama Barang / Item</th>
                        <th>Nominal</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Bukti / Nota</th>
                        <th>Status</th>
                        <th>Catatan Kurikulum/Owner</th>
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
                                <small class="text-muted">{{ Str::limit($sub->description, 60) ?: '-' }}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
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
                                <span class="badge {{ $sub->status_badge }}">{{ $sub->status }}</span>
                                @if($sub->status === 'Review Admin')
                                    <div class="small text-info mt-1"><i class="fa-solid fa-user-check me-1"></i> ACC Kurikulum</div>
                                @elseif($sub->status === 'Review Owner')
                                    <div class="small text-primary mt-1"><i class="fa-solid fa-building-circle-check me-1"></i> Verifikasi Admin</div>
                                @endif
                            </td>
                            <td>
                                @if($sub->curriculum_note)
                                    <div class="small text-dark"><strong>Kurikulum:</strong> {{ $sub->curriculum_note }}</div>
                                @endif
                                @if($sub->admin_note)
                                    <div class="small text-dark"><strong>Admin:</strong> {{ $sub->admin_note }}</div>
                                @endif
                                @if($sub->owner_note)
                                    <div class="small text-dark"><strong>Owner:</strong> {{ $sub->owner_note }}</div>
                                @endif
                                @if(!$sub->curriculum_note && !$sub->admin_note && !$sub->owner_note)
                                    <span class="small text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Lihat Lembar Dokumen">
                                    <i class="fa-solid fa-file-invoice me-1"></i> Lembar TTD
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-receipt fs-2 mb-2 text-secondary d-block"></i>
                                Anda belum memiliki pengajuan. Klik tombol "Buat Pengajuan Baru" di atas.
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

{{-- MODAL TAMBAH PENGAJUAN --}}
<div class="modal fade" id="modalTambahPengajuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('teacher.pengajuan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Buat Pengajuan Reimburse / Pembelian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Jenis Pengajuan <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="Reimburse Bahan Ajar">Reimburse Bahan Ajar (Penggantian Nota)</option>
                                <option value="Pembelian Bahan Ajar">Pembelian Bahan Ajar (Pengadaan Baru)</option>
                                <option value="Pembelian Sertifikat">Pembelian Sertifikat (Blanko/Cetak)</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Nota / Pengajuan <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Nama Pengajuan / Keperluan Utama <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="Contoh: Pembelian Perlengkapan Modul Kelas Mandarin A" required>
                        </div>
                    </div>

                    {{-- BAGIAN MULTI-ITEM BARANG --}}
                    <div class="card border rounded-3 p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-list-check text-warning me-1"></i> Rincian Barang (Multi-Item)
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" id="btnAddItem">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Item
                            </button>
                        </div>
                        <p class="small text-muted mb-2">Masukkan setiap nama barang dan nominal harganya sesuai bukti nota.</p>
                        
                        <div id="itemsContainer">
                            <div class="row g-2 align-items-center mb-2 item-row">
                                <div class="col-7">
                                    <input type="text" name="items[0][name]" class="form-control form-control-sm item-name-input" placeholder="Nama item (contoh: Kertas Origami)" required>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="items[0][amount]" class="form-control form-control-sm item-amount-input" placeholder="Harga (Rp)" min="1" required oninput="calculateTotal()">
                                </div>
                                <div class="col-1 text-center">
                                    <button type="button" class="btn btn-sm text-danger btn-remove-item" onclick="removeItemRow(this)" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                            <span class="fw-bold text-dark">Total Nominal:</span>
                            <span class="fs-5 fw-bold text-success" id="displayTotalAmount">Rp 0</span>
                        </div>
                        <input type="hidden" name="amount" id="inputTotalAmount" value="0">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Upload Nota / Kuitansi Pembayaran <span class="text-danger">*</span></label>
                            <input type="file" name="receipt_file" class="form-control" accept="image/*,application/pdf" required>
                            <small class="text-muted">Format PDF, JPG, PNG, WEBP (maksimal 10 MB)</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Upload Foto Bahan Ajar (Bukti Fisik Barang)</label>
                            <input type="file" name="photo_file" class="form-control" accept="image/*">
                            <small class="text-muted">Foto barang fisik yang telah dibeli (maksimal 10 MB)</small>
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

document.getElementById('btnAddItem')?.addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'row g-2 align-items-center mb-2 item-row';
    newRow.innerHTML = `
        <div class="col-7">
            <input type="text" name="items[${itemIndex}][name]" class="form-control form-control-sm item-name-input" placeholder="Nama item (contoh: Spidol Warna)" required>
        </div>
        <div class="col-4">
            <input type="number" name="items[${itemIndex}][amount]" class="form-control form-control-sm item-amount-input" placeholder="Harga (Rp)" min="1" required oninput="calculateTotal()">
        </div>
        <div class="col-1 text-center">
            <button type="button" class="btn btn-sm text-danger btn-remove-item" onclick="removeItemRow(this)" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    itemIndex++;
    calculateTotal();
});

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
    document.querySelectorAll('.item-amount-input').forEach(input => {
        const val = parseFloat(input.value) || 0;
        total += val;
    });
    document.getElementById('displayTotalAmount').innerText = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('inputTotalAmount').value = total;
}
</script>
@endpush
@endsection

