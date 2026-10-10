@extends('admin.app')

@section('title', 'Print Dokumen')

@push('styles')
<style>
    .dashboard-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 23px; flex-wrap: wrap; }
    .eyebrow { color: #d4a900; font-size: 11.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; margin-bottom: 6px; }
    .dashboard-header h1 { margin-top: 4px; font-size: 24px; font-weight: 700; line-height: 1.2; color: #111827; }
    .dashboard-header p { margin-top: 4px; color: #6B7280; font-size: 13px; }

    .btn { display: inline-flex; align-items: center; gap: 7px; height: 35px; padding: 9px 18px; border-radius: 7px;
           font-size: 13px; font-weight: 700; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
    .btn-primary { background: #ffd400; border-color: #ffd400; color: #111; }

    .card { background: #fff; border: 1px solid #edeef1; border-radius: 12px; overflow: hidden; }

    .success-banner { display: flex; align-items: center; gap: 10px; background: #f0fdf4; border: 1px solid #bbf7d0;
                      color: #16a34a; border-radius: 10px; padding: 14px 18px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }

    .tpl-table { width: 100%; border-collapse: collapse; }
    .tpl-table td { padding: 14px 18px; border-bottom: 1px solid #edeef1; font-size: 13px; }
    .tpl-table tr:last-child td { border-bottom: 0; }
    .tpl-name { font-weight: 600; color: #111827; }
    .tpl-meta { color: #9ca3af; font-size: 12px; }
</style>
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">Pengaturan &raquo; Print Dokumen</div>
            <h1>Print Dokumen</h1>
            <p>Ubah isi dan format dokumen yang dicetak tanpa programmer.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="success-banner">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <table class="tpl-table">
            @forelse ($templates as $template)
                <tr>
                    <td class="tpl-name">{{ $template->name }}</td>
                    <td class="tpl-meta">Terakhir diubah {{ $template->updated_at->format('d/m/Y H:i') }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('admin.template-dokumen.edit', $template) }}" class="btn btn-primary">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="tpl-meta">Belum ada template. Jalankan <code>php artisan db:seed --class=PrintTemplateSeeder</code>.</td>
                </tr>
            @endforelse
        </table>
    </div>

@endsection