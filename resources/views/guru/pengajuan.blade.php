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
                                <small class="text-muted">{{ Str::limit($sub->description, 60) ?: '-' }}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
                            </td>
                            <td>{{ $sub->date->translatedFormat('d M Y') }}</td>
                            <td>
                                @if($sub->receipt_path)
                                    <a href="{{ asset('storage/' . $sub->receipt_path) }}" target="_blank" class="small text-primary text-decoration-none">
                                        <i class="fa-solid fa-paperclip me-1"></i> Lihat Nota
                                    </a>
                                @else
                                    <span class="small text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $sub->status_badge }}">{{ $sub->status }}</span>
                            </td>
                            <td>
                                @if($sub->curriculum_note)
                                    <div class="small text-dark"><strong>Kurikulum:</strong> {{ $sub->curriculum_note }}</div>
                                @endif
                                @if($sub->owner_note)
                                    <div class="small text-dark"><strong>Owner:</strong> {{ $sub->owner_note }}</div>
                                @endif
                                @if(!$sub->curriculum_note && !$sub->owner_note)
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
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-semibold">Nama Barang / Keperluan <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="Contoh: Kertas flashcard, spidol whiteboard, blanko sertifikat" required>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-semibold">Nominal Total (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" placeholder="Contoh: 150000" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Nota / Bukti Pembayaran (Wajib untuk Reimburse)</label>
                        <input type="file" name="receipt_file" class="form-control" accept="image/*,application/pdf">
                        <small class="text-muted">Format PDF, JPG, PNG, WEBP (maksimal 5 MB)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan Tambahan / Alasan Kebutuhan</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Jelaskan kebutuhan bahan ajar untuk kelas yang bersangkutan..."></textarea>
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
@endsection

