<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Persetujuan Pengajuan - {{ $submission->item_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #111; background: #fff; }
        .sheet { max-width: 800px; margin: 20px auto; padding: 40px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .kop { border-bottom: 3px double #222; padding-bottom: 15px; margin-bottom: 25px; }
        .kop img { max-height: 70px; }
        .title { font-size: 18px; font-weight: bold; text-decoration: underline; text-align: center; text-transform: uppercase; margin-bottom: 5px; }
        .doc-no { text-align: center; font-size: 13px; margin-bottom: 25px; color: #555; }
        .table-detail td { padding: 8px 12px; }
        .sig-box { min-height: 90px; display: flex; align-items: center; justify-content: center; }
        .sig-box img { max-height: 80px; max-width: 160px; object-fit: contain; }
        @media print {
            body { background: none; }
            .sheet { border: 0; box-shadow: none; margin: 0; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="sheet">
    <div class="no-print d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border">
        <div>
            <span class="badge {{ $submission->status_badge }} fs-6">{{ $submission->status }}</span>
            <span class="text-muted ms-2 small">ID: #SUB-{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-1">
                Tutup
            </button>
        </div>
    </div>

    {{-- KOP SURAT --}}
    <div class="kop d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Haoyou Educator" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
            <div>
                <h4 class="fw-bold mb-0" style="letter-spacing: 1px;">HAOYOU MANDARIN EDUCATOR</h4>
                <div class="small text-muted">Lembaga Pendidikan Bahasa Mandarin & Pengembangan Kurikulum</div>
                <div class="small text-muted" style="font-size: 11px;">Jl. Pendidikan Utama No. 88 | Email: info@haoyoueducator.com | Telp: (021) 8899-7711</div>
            </div>
        </div>
    </div>

    {{-- TITLE --}}
    <div class="title">LEMBAR PERSETUJUAN PENGAJUAN</div>
    <div class="doc-no">Nomor: {{ str_pad($submission->id, 4, '0', STR_PAD_LEFT) }}/HY-SUB/{{ $submission->date->format('m/Y') }}</div>

    {{-- DETAIL TABLE --}}
    <table class="table table-bordered table-detail mb-4">
        <tbody>
            <tr>
                <td style="width: 28%;" class="bg-light fw-bold">Jenis Pengajuan</td>
                <td class="fw-bold text-dark">{{ $submission->type }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Nama Guru / Pengaju</td>
                <td>{{ $submission->teacher->name ?? $submission->creator->display_name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Nama Barang / Keperluan</td>
                <td class="fw-bold">{{ $submission->item_name }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Nominal yang Diajukan</td>
                <td class="fs-5 fw-bold text-success">{{ $submission->formatted_amount }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Tanggal Pengajuan</td>
                <td>{{ $submission->date->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Keterangan / Rincian</td>
                <td>{{ $submission->description ?: 'Tidak ada keterangan tambahan.' }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Status Verifikasi</td>
                <td>
                    <span class="badge {{ $submission->status_badge }}">{{ $submission->status }}</span>
                    @if($submission->curriculum_approved_at)
                        <span class="small text-muted ms-2">(Disetujui Kurikulum pada {{ $submission->curriculum_approved_at->translatedFormat('d F Y H:i') }})</span>
                    @endif
                </td>
            </tr>
            @if($submission->curriculum_note)
            <tr>
                <td class="bg-light fw-bold">Catatan Kurikulum</td>
                <td><em>"{{ $submission->curriculum_note }}"</em></td>
            </tr>
            @endif
            @if($submission->owner_note)
            <tr>
                <td class="bg-light fw-bold">Catatan Owner</td>
                <td><em>"{{ $submission->owner_note }}"</em></td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- TABEL RINCIAN MULTI-ITEM JIKA ADA --}}
    @if(!empty($submission->items) && is_array($submission->items))
        <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-list-check me-1"></i> Rincian Barang / Komponen Pembelian:</h6>
        <table class="table table-bordered mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 8%;" class="text-center">No</th>
                    <th>Nama Item Barang</th>
                    <th style="width: 30%;" class="text-end">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submission->items as $idx => $it)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $it['name'] ?? '-' }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format($it['amount'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="table-light">
                    <td colspan="2" class="text-end fw-bold">TOTAL PENGAJUAN:</td>
                    <td class="text-end fw-bold text-success fs-6">{{ $submission->formatted_amount }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- BUKTI NOTA & FOTO BAHAN AJAR JIKA ADA --}}
    <div class="row g-3 mb-4">
        @if($submission->receipt_path)
            <div class="{{ $submission->photo_path ? 'col-6' : 'col-12' }}">
                <div class="p-3 border rounded h-100">
                    <div class="fw-bold small text-muted mb-2"><i class="fa-solid fa-receipt me-1"></i> Lampiran Nota / Bukti Pembelian:</div>
                    <div class="text-center">
                        <img src="{{ asset('storage/' . $submission->receipt_path) }}" alt="Nota Bukti" style="max-height: 220px; max-width: 100%; object-fit: contain;" class="border p-1 bg-white rounded">
                    </div>
                </div>
            </div>
        @endif

        @if($submission->photo_path)
            <div class="{{ $submission->receipt_path ? 'col-6' : 'col-12' }}">
                <div class="p-3 border rounded h-100">
                    <div class="fw-bold small text-muted mb-2"><i class="fa-solid fa-camera me-1"></i> Foto Bahan Ajar / Fisik Barang:</div>
                    <div class="text-center">
                        <img src="{{ asset('storage/' . $submission->photo_path) }}" alt="Foto Bahan Ajar" style="max-height: 220px; max-width: 100%; object-fit: contain;" class="border p-1 bg-white rounded">
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- TANDA TANGAN OTOMATIS --}}
    <div class="row mt-5 text-center">
        <div class="col-4">
            <div class="small fw-semibold">Diajukan Oleh:</div>
            <div class="sig-box">
                <div class="text-muted small fst-italic">Tenaga Pengajar</div>
            </div>
            <div class="fw-bold text-decoration-underline">{{ $submission->teacher->name ?? $submission->creator->display_name ?? 'Guru' }}</div>
            <div class="small text-muted">Tanggal: {{ $submission->date->translatedFormat('d/m/Y') }}</div>
        </div>

        <div class="col-4">
            <div class="small fw-semibold">Disetujui Oleh (Kurikulum):</div>
            <div class="sig-box">
                @if($submission->curriculum_signature_path)
                    <img src="{{ asset('storage/' . $submission->curriculum_signature_path) }}" alt="TTD Kurikulum">
                @elseif($submission->curriculum_approved_at)
                    <div class="text-success small fw-semibold">
                        <i class="fa-solid fa-circle-check fs-4 d-block mb-1"></i>
                        DISETUJUI DIGITAL
                    </div>
                @else
                    <div class="text-muted small fst-italic">Menunggu Persetujuan</div>
                @endif
            </div>
            <div class="fw-bold text-decoration-underline">{{ $submission->curriculum->display_name ?? 'Bagian Kurikulum' }}</div>
            <div class="small text-muted">
                Tanggal: {{ $submission->curriculum_approved_at ? $submission->curriculum_approved_at->translatedFormat('d/m/Y') : '-' }}
            </div>
        </div>

        <div class="col-4">
            <div class="small fw-semibold">Mengetahui (Owner):</div>
            <div class="sig-box">
                @if($submission->owner_signature_path)
                    <img src="{{ asset('storage/' . $submission->owner_signature_path) }}" alt="TTD Owner">
                @elseif($submission->owner_approved_at || in_array($submission->status, ['Disetujui', 'Selesai']))
                    <div class="text-success small fw-semibold">
                        <i class="fa-solid fa-circle-check fs-4 d-block mb-1"></i>
                        TERVERIFIKASI
                    </div>
                @else
                    <div class="text-muted small fst-italic">{{ $submission->status === 'Review Owner' ? 'Menunggu Review' : '-' }}</div>
                @endif
            </div>
            <div class="fw-bold text-decoration-underline">{{ $submission->owner?->name ?? ($submission->owner?->display_name ?? 'Joy Limmunandar') }}</div>
            <div class="small text-muted">
                Tanggal: {{ $submission->owner_approved_at ? $submission->owner_approved_at->translatedFormat('d/m/Y') : '-' }}
            </div>
        </div>
    </div>
</div>

</body>
</html>

