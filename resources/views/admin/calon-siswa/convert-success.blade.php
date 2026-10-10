@extends('admin.app')

@section('title', 'Konversi Berhasil | Haoyou Educator')

@push('styles')
<style>
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
        padding: 24px;
    }

    .success-banner {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #16a34a;
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .convert-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
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
        text-decoration: none;
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

    .form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid #edeef1;
        margin-top: 24px;
    }

    @media (max-width: 900px) {
        .convert-layout {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">CRM &raquo; Calon Siswa</div>
            <h1>Konversi Berhasil: {{ $student->name }}</h1>
            <p>Siswa sudah aktif di sistem. Unduh dokumen yang diperlukan di bawah ini.</p>
        </div>
        <div class="dashboard-header-actions">
            <a href="{{ route('admin.calon-siswa.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Daftar Calon Siswa
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="success-banner">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="convert-layout">

        <div class="card">
            <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Siswa</div>
            <div class="info-list">
                <div>
                    <div class="info-item-label">Nama</div>
                    <div class="info-item-value">{{ $student->name }}</div>
                </div>
                <div>
                    <div class="info-item-label">Status</div>
                    <div class="info-item-value">{{ $student->status }}</div>
                </div>
                <div>
                    <div class="info-item-label">Program / Kelas</div>
                    <div class="info-item-value">
                        @php $enrollment = $student->activeEnrollment; @endphp
                        @if ($enrollment?->private_package_id)
                            {{ $enrollment->privatePackage->package_name ?? 'Private' }}
                        @else
                            {{ $enrollment?->class?->class_name ?? ($enrollment?->status === 'Waiting Class' ? 'Menunggu Kelas' : '-') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="form-section-title"><i class="fa-solid fa-file-lines"></i> Dokumen</div>

            <div class="doc-download-item">
                <div class="doc-info">
                    <div class="doc-icon"><i class="fa-solid fa-file-signature"></i></div>
                    <div>
                        <div class="doc-name">Syarat & Ketentuan</div>
                        <div class="doc-note">Untuk ditandatangani orang tua/siswa</div>
                    </div>
                </div>
                <a href="{{ route('admin.dokumen.syarat-ketentuan') }}"
                   target="_blank"
                   class="doc-download-btn"
                   title="Unduh Syarat & Ketentuan">
                    <i class="fa-solid fa-download"></i>
                </a>
            </div>

            <div class="doc-download-item">
                <div class="doc-info">
                    <div class="doc-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div>
                        <div class="doc-name">Formulir Pendaftaran</div>
                        <div class="doc-note">Terisi otomatis, siap diprint & ditandatangani</div>
                    </div>
                </div>
                <a href="{{ route('admin.siswa.formulir', $student->id) }}"
                   target="_blank"
                   class="doc-download-btn"
                   title="Unduh Formulir Pendaftaran">
                    <i class="fa-solid fa-download"></i>
                </a>
            </div>

            <div class="doc-download-item">
                <div class="doc-info">
                    <div class="doc-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <div>
                        <div class="doc-name">Invoice</div>
                        <div class="doc-note">Belum tersedia</div>
                    </div>
                </div>
                <span class="doc-download-btn disabled" title="Belum tersedia">
                    <i class="fa-solid fa-download"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.siswa') }}" class="btn btn-secondary">Lihat di Halaman Siswa</a>
        <a href="{{ route('admin.calon-siswa') }}" class="btn btn-primary">
            <i class="fa-solid fa-check"></i> Selesai
        </a>
    </div>

@endsection