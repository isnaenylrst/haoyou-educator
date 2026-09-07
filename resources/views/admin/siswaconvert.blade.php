@extends('admin.app')

@section('title', 'Konversi Calon Siswa | Haoyou Educator')

@push('styles')
<style>
    /* ============================================================
       CONVERT SISWA — CSS inline, diambil & disesuaikan dari calonsiswa.css
    ============================================================ */

    .dashboard-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 23px;
        flex-wrap: wrap;
    }

    .eyebrow {
        color: #d4a900;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .dashboard-header h1 {
        margin-top: 4px;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.01em;
        color: #111827;
    }

    .dashboard-header p {
        margin-top: 4px;
        color: #6B7280;
        font-size: 13px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        height: 35px;
        padding: 9px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-secondary {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        color: #3F3F3F;
    }

    .btn-primary {
        background: #ffd400;
        border: 1px solid #ffd400;
        box-shadow: 0 1px 2px rgba(28,25,23,0.06);
        color: #111;
    }

    .card {
        background: #fff;
        border: 1px solid #edeef1;
        border-radius: 12px;
        overflow: hidden;
    }

    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
    }

    .alert-error ul {
        margin: 0;
        padding-left: 18px;
    }

    .alert-error li {
        font-size: 13px;
        line-height: 1.6;
    }

    /* =====================================================
       LAYOUT 2 KOLOM
    ===================================================== */

    .convert-layout {
        display: grid;
        grid-template-columns: 1.7fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .convert-main,
    .convert-side > .card {
        padding: 24px;
    }

    .convert-side {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* =====================================================
       FORM SECTIONS
    ===================================================== */

    .form-section {
        margin-bottom: 22px;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .form-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #b8860b;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .form-section-title i {
        font-size: 11px;
    }

    .form-section-note {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        margin-top: 10px;
        font-size: 12px;
        line-height: 1.5;
        color: #6b7280;
    }

    .form-section-note i {
        margin-top: 1px;
        color: #b8860b;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-field.full {
        grid-column: 1 / -1;
    }

    .form-field label {
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        padding: 9px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        outline: none;
        background: #fff;
        font: inherit;
        font-size: 13px;
        color: #111827;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: #FFDD05;
        box-shadow: 0 0 0 3px rgba(255, 221, 5, 0.18);
    }

    .form-field small {
        font-size: 11px;
        color: #dc2626;
    }

    #class_id option[hidden] {
        display: none;
    }

    /* =====================================================
       INFO CALON SISWA (read-only, bukan input disabled lagi)
    ===================================================== */

    .info-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .info-item-label {
        font-size: 11.5px;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 3px;
    }

    .info-item-value {
        font-size: 14px;
        color: #111827;
        font-weight: 600;
    }

    /* =====================================================
       DOKUMEN
    ===================================================== */

    .doc-download-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid #edeef1;
        border-radius: 10px;
        margin-bottom: 10px;
    }

    .doc-download-item:last-child {
        margin-bottom: 0;
    }

    .doc-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .doc-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #FFF8D6;
        color: #A46A00;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }

    .doc-name {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .doc-note {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 1px;
    }

    .doc-download-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 1px solid #edeef1;
        background: #fff;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .2s;
        flex-shrink: 0;
    }

    .doc-download-btn:hover {
        background: #f8f9fb;
        border-color: #2563eb;
        color: #2563eb;
    }

    .doc-download-btn.disabled {
        opacity: .45;
        pointer-events: none;
        cursor: not-allowed;
    }

    /* =====================================================
       FOOTER FORM
    ===================================================== */

    .form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid #edeef1;
        margin-top: 24px;
    }

    /* =====================================================
       RESPONSIVE
    ===================================================== */

    @media (max-width: 900px) {
        .convert-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .convert-main,
        .convert-side > .card {
            padding: 18px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">CRM &raquo; Calon Siswa</div>
            <h1>Jadikan Siswa: {{ $candidateStudent->name }}</h1>
            <p>Lengkapi data pembayaran untuk mengonversi calon siswa ini menjadi siswa aktif.</p>
        </div>
        <div class="dashboard-header-actions">
            <a href="{{ route('admin.calon-siswa') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="convert-layout">

        {{-- ===================== KOLOM UTAMA: FORM ===================== --}}
        <div class="card convert-main">
            <form action="{{ route('admin.calon-siswa.convert.store', $candidateStudent->id) }}" method="POST">
                @csrf

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-box"></i> Paket Program</div>
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Program Package *</label>
                            <select name="program_package_id" id="program_package_id" required onchange="filterClasses()">
                                <option value="">Pilih Package</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" data-price="{{ $package->price }}">
                                        {{ $package->package_name }} ({{ $package->course_type }}) &mdash;
                                        Rp {{ number_format($package->price, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($packages->isEmpty())
                                <small>Belum ada program package untuk program ini.</small>
                            @endif
                        </div>

                        <div class="form-field full">
                            <label>Kelas (opsional, bisa ditentukan belakangan)</label>
                            <select name="class_id" id="class_id">
                                <option value="">Belum ditentukan</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" data-package="{{ $class->program_package_id }}">
                                        {{ $class->class_name }} ({{ $class->delivery_mode }}, {{ $class->status }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran (DP)</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Nominal Dibayar *</label>
                            <input type="number" name="amount_paid" min="1" step="0.01" required>
                        </div>
                        <div class="form-field">
                            <label>Metode Pembayaran *</label>
                            <select name="payment_method" required>
                                <option value="">Pilih Metode</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="Cash">Cash</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Tanggal Pembayaran *</label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>
                    <p class="form-section-note">
                        <i class="fa-solid fa-circle-info"></i>
                        Upload bukti pembayaran akan ditambahkan pada pengembangan berikutnya.
                    </p>
                </div>

                <div class="form-footer">
                    <a href="{{ route('admin.calon-siswa') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Konversi Jadi Siswa
                    </button>
                </div>
            </form>
        </div>

        {{-- ===================== KOLOM SAMPING: INFO & DOKUMEN ===================== --}}
        <div class="convert-side">

            <div class="card">
                <div class="form-section" style="margin-bottom: 0;">
                    <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Calon Siswa</div>
                    <div class="info-list">
                        <div>
                            <div class="info-item-label">Nama</div>
                            <div class="info-item-value">{{ $candidateStudent->name }}</div>
                        </div>
                        <div>
                            <div class="info-item-label">No. Telepon</div>
                            <div class="info-item-value">{{ $candidateStudent->phone }}</div>
                        </div>
                        <div>
                            <div class="info-item-label">Program Diminati</div>
                            <div class="info-item-value">{{ $candidateStudent->program->program_name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="form-section" style="margin-bottom: 0;">
                    <div class="form-section-title"><i class="fa-solid fa-file-lines"></i> Dokumen</div>

                    <div class="doc-download-item">
                        <div class="doc-info">
                            <div class="doc-icon"><i class="fa-solid fa-file-signature"></i></div>
                            <div>
                                <div class="doc-name">Syarat & Ketentuan</div>
                                <div class="doc-note">Untuk ditandatangani calon siswa</div>
                            </div>
                        </div>
                        <a href="{{ asset('documents/syarat-ketentuan-pendaftaran.pdf') }}"
                           target="_blank"
                           class="doc-download-btn"
                           title="Unduh Syarat & Ketentuan">
                            <i class="fa-solid fa-download"></i>
                        </a>
                    </div>

                    <div class="doc-download-item">
                        <div class="doc-info">
                            <div class="doc-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            <div>
                                <div class="doc-name">Invoice</div>
                                <div class="doc-note">Tersedia setelah konversi berhasil</div>
                            </div>
                        </div>
                        <span class="doc-download-btn disabled" title="Belum tersedia">
                            <i class="fa-solid fa-download"></i>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
    function filterClasses() {
        const packageId = document.getElementById('program_package_id').value;
        const classSelect = document.getElementById('class_id');

        [...classSelect.options].forEach(opt => {
            if (!opt.dataset.package) return;
            opt.hidden = packageId && opt.dataset.package !== packageId;
        });

        classSelect.value = '';
    }
</script>
@endpush