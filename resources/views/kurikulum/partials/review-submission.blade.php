@if ($submissions->isEmpty())
    <div class="p-5 text-center text-muted">
        <i class="fa-solid fa-receipt fs-1 text-secondary mb-3 d-block"></i>
        Tidak ada pengajuan reimburse atau pembelian yang menunggu review 🎉
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Guru Pengaju</th>
                    <th>Jenis Pengajuan</th>
                    <th>Nama Barang / Item</th>
                    <th>Nominal</th>
                    <th>Tanggal & Bukti</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 160px;">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submissions as $sub)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $sub->teacher->name ?? $sub->creator->display_name ?? 'Guru' }}</div>
                            <small class="text-muted">Diajukan: {{ $sub->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            <span class="badge bg-warning-subtle text-dark border border-warning">
                                {{ $sub->type }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $sub->item_name }}</div>
                            <small class="text-muted">{{ Str::limit($sub->description, 60) ?: '-' }}</small>
                        </td>
                        <td>
                            <span class="fw-bold text-success">{{ $sub->formatted_amount }}</span>
                        </td>
                        <td>
                            <div>{{ $sub->date->translatedFormat('d M Y') }}</div>
                            @if ($sub->receipt_path)
                                <a href="{{ asset('storage/' . $sub->receipt_path) }}" target="_blank" class="small text-primary text-decoration-none">
                                    <i class="fa-solid fa-paperclip me-1"></i> Lihat Nota / Bukti
                                </a>
                            @else
                                <span class="small text-muted">Tanpa nota</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $sub->status_badge }}">{{ $sub->status }}</span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('kurikulum.review-pengajuan.print-submission', $sub->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview / Cetak Dokumen">
                                    <i class="fa-solid fa-print"></i>
                                </a>

                                <button type="button" class="btn btn-sm btn-success text-white fw-semibold" data-bs-toggle="modal" data-bs-target="#modalApproveSub{{ $sub->id }}" title="Approve">
                                    <i class="fa-solid fa-check me-1"></i> ACC
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRejectSub{{ $sub->id }}" title="Tolak">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            {{-- MODAL APPROVE SUBMISSION --}}
                            <div class="modal fade" id="modalApproveSub{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <div class="modal-content rounded-4 border-0">
                                        <form action="{{ route('kurikulum.review-pengajuan.submission', $sub->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="decision" value="Approved">

                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold">Setujui Pengajuan {{ $sub->type }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="p-3 bg-light rounded-3 mb-3 border">
                                                    <div><strong>Item:</strong> {{ $sub->item_name }}</div>
                                                    <div><strong>Nominal:</strong> <span class="text-success fw-bold">{{ $sub->formatted_amount }}</span></div>
                                                    <div><strong>Guru:</strong> {{ $sub->teacher->name ?? '-' }}</div>
                                                </div>

                                                <div class="alert alert-info py-2 px-3 small mb-3">
                                                    <i class="fa-solid fa-signature me-1"></i> Tanda tangan Anda yang tersimpan di profil akan <strong>otomatis ditempelkan</strong> pada lembar dokumen persetujuan ini beserta tanggal hari ini.
                                                </div>

                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" name="forward_to_owner" value="1" id="fwdOwner{{ $sub->id }}">
                                                    <label class="form-check-label fw-semibold" for="fwdOwner{{ $sub->id }}">
                                                        Lanjutkan ke Review Owner untuk persetujuan pencairan dana
                                                    </label>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Catatan Kurikulum (opsional)</label>
                                                    <textarea name="note" rows="2" class="form-control" placeholder="Catatan persetujuan..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success rounded-pill fw-semibold text-white px-4">
                                                    <i class="fa-solid fa-check me-1"></i> Konfirmasi Setujui & TTD
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL REJECT SUBMISSION --}}
                            <div class="modal fade" id="modalRejectSub{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <div class="modal-content rounded-4 border-0">
                                        <form action="{{ route('kurikulum.review-pengajuan.submission', $sub->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="decision" value="Rejected">

                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold text-danger">Tolak Pengajuan {{ $sub->type }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                    <textarea name="note" rows="3" class="form-control" placeholder="Tuliskan alasan penolakan agar guru mengetahui kekurangannya..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger rounded-pill fw-semibold px-4">
                                                    <i class="fa-solid fa-xmark me-1"></i> Tolak Pengajuan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-3 border-top bg-light">
        {{ $submissions->links() }}
    </div>
@endif

