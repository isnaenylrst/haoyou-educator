@extends('admin.app')

@section('title', 'Pengaturan | Haoyou Educator')

@push('styles')
<style>
    .dashboard-header {
        margin-bottom: 23px;
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

    .settings-group {
        margin-bottom: 28px;
    }

    .settings-group-title {
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

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .settings-card {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 20px;
        background: #fff;
        border: 1px solid #edeef1;
        border-radius: 12px;
        text-decoration: none;
        color: inherit;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    a.settings-card:hover {
        border-color: #FFDD05;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        transform: translateY(-1px);
    }

    .settings-card.is-disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    .settings-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #FFF8D6;
        color: #A46A00;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }

    .settings-body {
        flex: 1;
        min-width: 0;
    }

    .settings-name {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .settings-desc {
        margin-top: 4px;
        font-size: 12.5px;
        line-height: 1.5;
        color: #6b7280;
    }

    .settings-badge {
        padding: 1px 8px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .settings-arrow {
        color: #9ca3af;
        font-size: 12px;
        margin-top: 4px;
    }

    @media (max-width: 1000px) {
        .settings-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .settings-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

    @php
        // Tambah menu pengaturan baru cukup dengan menambah item di array ini.
        // 'route' => null berarti belum tersedia (tampil abu-abu, "Segera hadir").
        $groups = [
            [
                'title' => 'Dokumen',
                'icon'  => 'fa-file-lines',
                'items' => [
                    [
                        'name'  => 'Template Dokumen',
                        'desc'  => 'Atur isi template Formulir Pendaftaran dan Invoice yang dicetak.',
                        'icon'  => 'fa-file-signature',
                        'route' => 'admin.template-dokumen.index',
                    ],
                ],
            ],
            [
                'title' => 'Lembaga',
                'icon'  => 'fa-building',
                'items' => [
                    [
                        'name'  => 'Profil Lembaga',
                        'desc'  => 'Nama, alamat, kontak, dan logo Haoyou Educator.',
                        'icon'  => 'fa-id-card',
                        'route' => null,
                    ],
                    [
                        'name'  => 'Metode Pembayaran',
                        'desc'  => 'Daftar rekening, QRIS, dan metode pembayaran yang tersedia.',
                        'icon'  => 'fa-money-bill-wave',
                        'route' => null,
                    ],
                ],
            ],
            [
                'title' => 'Sistem',
                'icon'  => 'fa-gear',
                'items' => [
                    [
                        'name'  => 'Pengguna & Hak Akses',
                        'desc'  => 'Kelola akun admin dan level akses.',
                        'icon'  => 'fa-user-shield',
                        'route' => null,
                    ],
                ],
            ],
        ];
    @endphp

    <div class="dashboard-header">
        <div class="eyebrow">Sistem &raquo; Pengaturan</div>
        <h1>Pengaturan</h1>
        <p>Kelola dokumen, data lembaga, dan konfigurasi sistem di satu tempat.</p>
    </div>

    @foreach ($groups as $group)
        <div class="settings-group">
            <div class="settings-group-title">
                <i class="fa-solid {{ $group['icon'] }}"></i> {{ $group['title'] }}
            </div>

            <div class="settings-grid">
                @foreach ($group['items'] as $item)
                    @if ($item['route'])
                        <a href="{{ route($item['route']) }}" class="settings-card">
                            <div class="settings-icon"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                            <div class="settings-body">
                                <div class="settings-name">{{ $item['name'] }}</div>
                                <div class="settings-desc">{{ $item['desc'] }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-right settings-arrow"></i>
                        </a>
                    @else
                        <div class="settings-card is-disabled" title="Belum tersedia">
                            <div class="settings-icon"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                            <div class="settings-body">
                                <div class="settings-name">
                                    {{ $item['name'] }}
                                    <span class="settings-badge">Segera hadir</span>
                                </div>
                                <div class="settings-desc">{{ $item['desc'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

@endsection