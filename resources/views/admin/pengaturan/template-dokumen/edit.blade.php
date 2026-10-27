@extends('admin.app')

@section('title', 'Edit ' . $template->name)

@push('styles')
<style>
    .dashboard-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 23px; flex-wrap: wrap; gap: 12px; }
    .eyebrow { color: #d4a900; font-size: 11.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; margin-bottom: 6px; }
    .dashboard-header h1 { margin-top: 4px; font-size: 24px; font-weight: 700; line-height: 1.2; color: #111827; }
    .dashboard-header p { margin-top: 4px; color: #6B7280; font-size: 13px; }

    .btn { display: inline-flex; align-items: center; gap: 7px; height: 35px; padding: 9px 18px; border-radius: 7px;
           font-size: 13px; font-weight: 700; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
    .btn-secondary { background: #fff; border-color: #E5E7EB; color: #3F3F3F; }
    .btn-primary { background: #ffd400; border-color: #ffd400; color: #111; }

    .card { background: #fff; border: 1px solid #edeef1; border-radius: 12px; padding: 20px; }

    .success-banner { display: flex; align-items: center; gap: 10px; background: #f0fdf4; border: 1px solid #bbf7d0;
                      color: #16a34a; border-radius: 10px; padding: 14px 18px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; }
    .alert-error ul { margin: 0; padding-left: 18px; }
    .alert-error li { font-size: 13px; line-height: 1.6; }

    .tpl-layout { display: grid; grid-template-columns: 1fr 280px; gap: 20px; align-items: start; }
    .tpl-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; flex-wrap: wrap; }

    .tpl-side { max-height: 780px; overflow-y: auto; }
    .tpl-group { margin-bottom: 14px; }
    .tpl-group h4 { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #b8860b; margin: 0 0 6px; }
    .tpl-chip { display: block; width: 100%; text-align: left; margin-bottom: 4px; padding: 6px 10px;
                border: 1px solid #e5e7eb; border-radius: 7px; background: #fff; font-size: 12px; color: #111827; cursor: pointer; }
    .tpl-chip:hover { background: #FFF8D6; }
    .tpl-chip code { color: #6b5410; font-size: 11px; }

    @media (max-width: 900px) { .tpl-layout { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">Pengaturan &raquo; Print Dokumen</div>
            <h1>{{ $template->name }}</h1>
            <p>Klik penanda di kanan untuk menyisipkannya di posisi kursor. Penanda diganti data siswa saat dicetak.</p>
        </div>
        <a href="{{ route('admin.template-dokumen.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    @if (session('success'))
        <div class="success-banner">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tpl-layout">

        {{-- ===================== EDITOR ===================== --}}
        <div class="card">
            <form action="{{ route('admin.template-dokumen.update', $template) }}" method="POST">
                @csrf
                @method('PUT')

                <textarea id="content" name="content">{{ old('content', $template->content) }}</textarea>

                <div class="tpl-actions">
                    <button type="submit" form="reset-form" class="btn btn-secondary"
                            onclick="return confirm('Kembalikan template ke versi awal? Perubahan yang sudah disimpan akan hilang.')">
                        Kembalikan ke Awal
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="previewTemplate()">
                        <i class="fa-solid fa-eye"></i> Pratinjau
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Simpan
                    </button>
                </div>
            </form>

            {{-- Form terpisah (tidak boleh bersarang di dalam form utama) --}}
            <form id="reset-form" action="{{ route('admin.template-dokumen.reset', $template) }}" method="POST">
                @csrf
            </form>

            <form id="preview-form" action="{{ route('admin.template-dokumen.preview', $template) }}"
                  method="POST" target="_blank">
                @csrf
                <input type="hidden" name="content" id="preview-content">
            </form>
        </div>

        {{-- ===================== DAFTAR PENANDA ===================== --}}
        <div class="card tpl-side">
            @foreach ($placeholders as $group => $items)
                <div class="tpl-group">
                    <h4>{{ $group }}</h4>
                    @foreach ($items as $key => $label)
                        <button type="button" class="tpl-chip" onclick="insertToken('{{ '{' . $key . '}' }}')">
                            {{ $label }}<br>
                            <code>{{ '{' . $key . '}' }}</code>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        height: 680,
        menubar: false,
        plugins: 'table lists code',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline | alignleft aligncenter alignright alignjustify | numlist bullist | table tableprops tablecellprops tablerowprops | halamanbaru | code',
        font_family_formats: 'Times New Roman=times new roman,times,serif; Arial=arial,helvetica,sans-serif',
        content_style: "body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; } table, td, th, p, li { font-family: 'Times New Roman', Times, serif; }",
        setup: function (editor) {
            editor.ui.registry.addButton('halamanbaru', {
                text: 'Halaman Baru',
                onAction: function () {
                    editor.insertContent('<div style="page-break-before: always;">&nbsp;</div>');
                }
            });
        }
    });

    function insertToken(token) {
        tinymce.get('content').insertContent(token);
    }

    function previewTemplate() {
        document.getElementById('preview-content').value = tinymce.get('content').getContent();
        document.getElementById('preview-form').submit();
    }
</script>
@endpush