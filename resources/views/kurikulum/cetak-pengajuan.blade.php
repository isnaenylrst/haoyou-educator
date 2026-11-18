<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FORM PENGAJUAN PEMBELIAN BAHAN AJAR - {{ $submission->item_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #111; background: #fff; }
        .sheet { max-width: 900px; margin: 20px auto; padding: 40px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .kop { border-bottom: 3px double #222; padding-bottom: 15px; margin-bottom: 25px; }
        .kop img { max-height: 70px; }
        .title { font-size: 18px; font-weight: bold; text-decoration: underline; text-align: center; text-transform: uppercase; margin-bottom: 5px; }
        .doc-no { text-align: center; font-size: 13px; margin-bottom: 20px; color: #555; }
        .intro-text { font-size: 14px; margin-bottom: 20px; line-height: 1.6; }
        .table-detail td { padding: 8px 12px; }
        .sig-row { page-break-inside: avoid; }
        .sig-head { font-size: 14px; font-weight: 600; white-space: nowrap; }
        .sig-role { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
        .sig-box { height: 90px; display: flex; align-items: center; justify-content: center; }
        .sig-name { min-height: 24px; font-weight: bold; text-decoration: underline; }
        .sig-box img { max-height: 80px; max-width: 130px; object-fit: contain; }
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

    {{-- A. JUDUL --}}
    <div class="title">FORM PENGAJUAN PEMBELIAN BAHAN AJAR</div>
    <div class="doc-no">Nomor: {{ str_pad($submission->id, 4, '0', STR_PAD_LEFT) }}/HY-SUB/{{ $submission->date ? $submission->date->format('m/Y') : date('m/Y') }}</div>

    {{-- B. KALIMAT OTOMATIS DENGAN NAMA GURU --}}
    @php
        $teacherName = $submission->teacher->name ?? ($submission->creator->name ?? ($submission->creator->username ?? 'Guru'));
        $classNameGlobal = $submission->class ? $submission->class->class_name . ($submission->class->schedules->isNotEmpty() ? ' (' . $submission->class->schedules->map(fn($s) => $s->day . ' ' . substr($s->start_time, 0, 5))->implode(', ') . ')' : '') : null;
    @endphp
    <div class="intro-text">
        Saya <strong>({{ $teacherName }})</strong> yang bertandatangan di bawah ini mengajukan pembelian bahan ajar dengan rincian sebagai berikut:
    </div>

    {{-- C. ISIAN MANUAL REKENING --}}
    <table class="table table-bordered table-detail mb-4">
        <tbody>
            <tr>
                <td style="width: 28%;" class="bg-light fw-bold">Nomor Rekening</td>
                <td class="fw-bold">{{ $submission->account_number ?: '-' }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Atas Nama Rekening (a/n rek)</td>
                <td class="fw-bold">{{ $submission->account_name ?: '-' }}</td>
            </tr>
            <tr>
                <td class="bg-light fw-bold">Nama Pengajuan / Keperluan</td>
                <td class="fw-bold text-dark">{{ $submission->item_name }}</td>
            </tr>
            @if($classNameGlobal)
            <tr>
                <td class="bg-light fw-bold">Kelas Terkait</td>
                <td>{{ $classNameGlobal }}</td>
            </tr>
            @endif
            <tr>
                <td class="bg-light fw-bold">Tanggal Pengajuan</td>
                <td>{{ $submission->date ? $submission->date->translatedFormat('d F Y') : '-' }}</td>
            </tr>
            @if($submission->description)
            <tr>
                <td class="bg-light fw-bold">Keterangan Tambahan</td>
                <td>{{ $submission->description }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- D. TABEL DENGAN KOLOM LENGKAP --}}
    @php
        $items = !empty($submission->items) && is_array($submission->items) ? $submission->items : [];
    @endphp

    <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-list-check me-1"></i> Rincian Pembelian Bahan Ajar:</h6>
    <table class="table table-bordered mb-2 align-middle" style="font-size: 12px;">
        <thead class="table-light text-center">
            <tr>
                <th style="width: 4%;">No</th>
                <th>Nama Item</th>
                <th style="width: 6%;">Qty</th>
                <th style="width: 11%;">Harga Satuan</th>
                <th style="width: 9%;">Ongkir</th>
                <th style="width: 12%;">Total</th>
                <th>Nama & Jadwal Kelas</th>
                <th style="width: 10%;">Tgl Beli</th>
                <th>Link Pembelian</th>
                <th style="width: 10%;">Tgl Cek</th>
                <th style="width: 10%;">Tgl Kembali</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $it)
                @php
                    $qty = isset($it['qty']) && $it['qty'] !== '' ? (float) $it['qty'] : 1;
                    $price = isset($it['price']) ? (float) $it['price'] : 0;
                    $shipping = isset($it['shipping']) ? (float) $it['shipping'] : 0;
                    $rowTotal = isset($it['amount']) ? (float) $it['amount'] : (($qty * $price) + $shipping);
                    $cls = $it['class'] ?? ($classNameGlobal ?: '-');
                    $buyDate = !empty($it['buy_date']) ? \Illuminate\Support\Carbon::parse($it['buy_date'])->translatedFormat('d/m/Y') : '-';
                    $checkDate = !empty($it['check_date']) ? \Illuminate\Support\Carbon::parse($it['check_date'])->translatedFormat('d/m/Y') : '-';
                    $returnDate = !empty($it['return_date']) ? \Illuminate\Support\Carbon::parse($it['return_date'])->translatedFormat('d/m/Y') : '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold">{{ $it['name'] ?? '-' }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-end">Rp {{ number_format($price, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($shipping, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($rowTotal, 0, ',', '.') }}</td>
                    <td>{{ $cls }}</td>
                    <td class="text-center">{{ $buyDate }}</td>
                    <td>
                        @if(!empty($it['link']))
                            <a href="{{ $it['link'] }}" target="_blank" class="text-decoration-underline text-primary" style="word-break: break-all;">Link</a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ $checkDate }}</td>
                    <td class="text-center">{{ $returnDate }}</td>
                </tr>
            @empty
                {{-- Fallback untuk pengajuan lama yang belum pakai multi-item --}}
                <tr>
                    <td class="text-center">1</td>
                    <td class="fw-bold">{{ $submission->item_name }}</td>
                    <td class="text-center">1</td>
                    <td class="text-end">Rp {{ number_format($submission->amount, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($submission->shipping_cost ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">{{ $submission->formatted_amount }}</td>
                    <td>{{ $classNameGlobal ?: '-' }}</td>
                    <td class="text-center">{{ $submission->date ? $submission->date->translatedFormat('d/m/Y') : '-' }}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- E. DI BAWAH TABEL: TOTAL KESELURUHAN DAN TERBILANG --}}
    @php
        $terbilangText = \App\Services\TerbilangService::make($submission->amount);
    @endphp
    <div class="p-3 bg-light rounded border mb-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold text-dark fs-6">TOTAL KESELURUHAN:</span>
            <span class="fs-5 fw-bold text-success">{{ $submission->formatted_amount }}</span>
        </div>
        <div style="font-size: 13px;">
            <strong class="text-secondary">TERBILANG:</strong>
            <span class="fst-italic text-dark fw-bold ms-1">"{{ $terbilangText }}"</span>
        </div>
    </div>

    {{-- BUKTI NOTA & FOTO BAHAN AJAR JIKA ADA --}}
    @if($submission->receipt_path || $submission->photo_path)
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
                    <div class="fw-bold small text-muted mb-2"><i class="fa-solid fa-camera me-1"></i> Foto Bahan Ajar (Setelah Digunakan):</div>
                    <div class="text-center">
                        <img src="{{ asset('storage/' . $submission->photo_path) }}" alt="Foto Bahan Ajar" style="max-height: 220px; max-width: 100%; object-fit: contain;" class="border p-1 bg-white rounded">
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- F. BLOK TANDA TANGAN:
         Dibuat oleh (nama guru), Disetujui (Kurikulum), Diketahui (Direktur), Dicek (Admin)
         Tanda tangan tampil setelah pihak terkait menyetujui, dan kosong sebelum itu. --}}
    <div class="row mt-4 text-center sig-row">
        {{-- 1. DIBUAT OLEH (GURU) --}}
        <div class="col-3">
            <div class="sig-head">Dibuat oleh</div>
            <div class="sig-role">Guru Pengajar</div>
            <div class="sig-box">
                @if($submission->teacher?->user?->signature_path)
                    <img src="{{ asset('storage/' . $submission->teacher->user->signature_path) }}" alt="TTD Guru">
                @endif
            </div>
            <div class="sig-name">{{ $teacherName }}</div>
            <div class="small text-muted">Tanggal: {{ $submission->date ? $submission->date->translatedFormat('d/m/Y') : '-' }}</div>
        </div>

        {{-- 2. DISETUJUI (KURIKULUM) --}}
        <div class="col-3">
            <div class="sig-head">Disetujui</div>
            <div class="sig-role">Kurikulum</div>
            <div class="sig-box">
                @if($submission->curriculum_approved_at && $submission->curriculum_signature_path)
                    <img src="{{ asset('storage/' . $submission->curriculum_signature_path) }}" alt="TTD Kurikulum">
                @elseif($submission->curriculum_approved_at)
                    <div class="text-success small fw-semibold">
                        <i class="fa-solid fa-circle-check fs-4 d-block mb-1"></i>
                        DISETUJUI DIGITAL
                    </div>
                @else
                    <div class="text-muted small fst-italic"></div>
                @endif
            </div>
            <div class="sig-name">{{ $submission->curriculum_approved_at ? ($submission->curriculum->name ?? ($submission->curriculum->username ?? 'Kurikulum')) : '' }}</div>
            <div class="small text-muted">
                Tanggal: {{ $submission->curriculum_approved_at ? $submission->curriculum_approved_at->translatedFormat('d/m/Y') : '-' }}
            </div>
        </div>

        {{-- 3. DIKETAHUI (DIREKTUR / OWNER) --}}
        <div class="col-3">
            <div class="sig-head">Diketahui</div>
            <div class="sig-role">Direktur</div>
            <div class="sig-box">
                @if(($submission->owner_approved_at || in_array($submission->status, ['Disetujui', 'Dibayar', 'Selesai'])) && $submission->owner_signature_path)
                    <img src="{{ asset('storage/' . $submission->owner_signature_path) }}" alt="TTD Direktur">
                @elseif($submission->owner_approved_at || in_array($submission->status, ['Disetujui', 'Dibayar', 'Selesai']))
                    <div class="text-success small fw-semibold">
                        <i class="fa-solid fa-circle-check fs-4 d-block mb-1"></i>
                        DISETUJUI DIGITAL
                    </div>
                @else
                    <div class="text-muted small fst-italic"></div>
                @endif
            </div>
            <div class="sig-name">{{ ($submission->owner_approved_at || in_array($submission->status, ['Disetujui', 'Dibayar', 'Selesai'])) ? ($submission->owner?->name ?? ($submission->owner?->username ?? 'Joy Limmunandar')) : '' }}</div>
            <div class="small text-muted">
                Tanggal: {{ $submission->owner_approved_at ? $submission->owner_approved_at->translatedFormat('d/m/Y') : '-' }}
            </div>
        </div>

        {{-- 4. DICEK (ADMIN) --}}
        <div class="col-3">
            <div class="sig-head">Dicek</div>
            <div class="sig-role">Admin</div>
            <div class="sig-box">
                @if($submission->admin_verified_at && $submission->admin_signature_path)
                    <img src="{{ asset('storage/' . $submission->admin_signature_path) }}" alt="TTD Admin">
                @elseif($submission->admin_verified_at)
                    <div class="text-success small fw-semibold">
                        <i class="fa-solid fa-circle-check fs-4 d-block mb-1"></i>
                        TERVERIFIKASI
                    </div>
                @else
                    <div class="text-muted small fst-italic"></div>
                @endif
            </div>
            <div class="sig-name">{{ $submission->admin_verified_at ? ($submission->admin_signer_name ?: ($submission->admin->name ?? ($submission->admin->username ?? 'Admin'))) : '' }}</div>
            <div class="small text-muted">
                Tanggal: {{ $submission->admin_verified_at ? $submission->admin_verified_at->translatedFormat('d/m/Y') : '-' }}
            </div>
        </div>
    </div>
</div>

</body>
</html>
