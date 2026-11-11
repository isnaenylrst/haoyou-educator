@extends($layout)
@section('title', 'Dokumen Progress Report - ' . ($header['student'] ?? ''))

@section('content')
@use('App\Support\RichText')
@php
    $levels = ['Sufficient', 'Proficient', 'Excellent'];
    $lock = $lockMaterials ?? false;
    $rowsOld = old('materials', $rows);
    $assOld  = old('assessments', $assessments);
    $h = fn ($k) => old("header.$k", $header[$k] ?? '');
@endphp
<style>
    .pr-paper { background:#fff; color:#000; max-width: 1020px; margin: 0 auto; padding: 28px 34px; border:1px solid #e3e3e8; box-shadow: 0 4px 18px rgba(0,0,0,.07); border-radius: 6px; font-family: 'Noto Sans SC','Microsoft YaHei',Arial,sans-serif; }
    .pr-paper input.pr-in, .pr-paper select.pr-in, .pr-paper textarea.pr-in { width:100%; border:1px dashed #c9c9d3; background:#fffef5; padding:5px 7px; font-size: 13px; font-family: inherit; border-radius:3px; }
    .pr-paper input.pr-in:focus, .pr-paper select.pr-in:focus { outline:2px solid #ffd600; background:#fff; }
    table.pr-t { width:100%; border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; }
    table.pr-t th, table.pr-t td { border:1px solid #000; padding:5px; vertical-align: top; }
    table.pr-t th { background:#ffe699; text-align:center; font-size: 13px; }
    .pr-head td { padding: 2px 0; font-weight: 700; }
    .pr-head input { font-weight:700; }
    .pr-del { border:0; background:transparent; color:#c0392b; cursor:pointer; font-size: 12px; }
    .pr-locked { background:#f1f1f4; padding:8px 10px; border-radius:4px; font-size:13px; min-height:60px; }
    @media print { .no-print { display:none !important; } }
</style>

<div class="container-fluid py-3 rt-wrap">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
        <div>
            <h4 class="fw-bold mb-1">Dokumen Progress Report</h4>
            <div class="text-muted small">Isi langsung seperti dokumen Word: bold, ukuran huruf, warna, rata teks, daftar. Hasilnya otomatis menjadi PDF.</div>
        </div>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary rounded-pill"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger no-print">
            <strong>Periksa kembali isian:</strong>
            <ul class="mb-0">@foreach(collect($errors->all())->unique() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif
    @if(!empty($notice))
        <div class="alert alert-warning no-print">{{ $notice }}</div>
    @endif
    @if($lock)
        <div class="alert alert-info no-print"><i class="fa-solid fa-lock me-1"></i> Learning Materials sudah disiapkan Kurikulum{{ !empty($templateName) ? ' ('.$templateName.')' : '' }} dan tidak bisa diubah. Isi <b>Achievement Level</b> dan <b>Description</b> untuk pertemuan yang sudah berjalan; pertemuan yang dikosongkan tidak ikut tampil di dokumen.</div>
    @endif

    <form method="POST" action="{{ $formAction }}" id="prForm">
        @csrf
        @if($method === 'PUT') @method('PUT') @endif

        <div class="pr-paper">
            <table class="pr-head" style="width:100%; margin-bottom: 6px;">
                <tr><td style="width:90px;">Teacher</td><td>: <input class="pr-in" style="width:80%" name="header[teacher]" value="{{ $h('teacher') }}" maxlength="255"></td></tr>
                <tr><td>Student</td><td>: <input class="pr-in" style="width:80%" name="header[student]" value="{{ $h('student') }}" maxlength="255"></td></tr>
                <tr><td>Class</td><td>: <input class="pr-in" style="width:80%" name="header[class]" value="{{ $h('class') }}" maxlength="255"></td></tr>
                <tr><td>Present</td><td>: <input class="pr-in" style="width:30%" name="header[present]" value="{{ $h('present') }}" maxlength="50"></td></tr>
            </table>

            <div class="text-center my-3">
                <input class="pr-in text-center fw-bold" style="max-width:520px; display:inline-block" name="header[title]" value="{{ $h('title') ?: config('progress_report.default_title') }}" maxlength="150">
                <div class="mt-1 small text-muted no-print">Periode (opsional): <input class="pr-in d-inline-block" style="width:220px" name="report_period" value="{{ old('report_period', $period) }}" maxlength="100"></div>
            </div>

            @if($lock)<div class="small text-muted mb-2 no-print"><i class="fa-solid fa-list-check me-1"></i> Pertemuan terisi: <b id="prCounter">0</b> dari {{ count($rowsOld) }}. Kosongkan Level &amp; Description pada pertemuan yang belum berjalan.</div>@endif
            <table class="pr-t" id="rowsTable">
                <thead>
                    <tr><th style="width:7%">NO<br><span class="small fw-normal">(pertemuan)</span></th><th style="width:37%">Learning Materials</th><th style="width:14%">Achievement<br>Level</th><th style="width:38%">Description</th><th style="width:4%" class="no-print"></th></tr>
                </thead>
                <tbody>
                @foreach($rowsOld as $i => $r)
                    <tr class="pr-row">
                        <td><input class="pr-in text-center" name="materials[{{ $i }}][meeting]" value="{{ $r['meeting'] ?? ($i + 1) }}" maxlength="10" @if($lock) readonly style="background:#eee" @endif></td>
                        <td>
                            @if($lock)
                                <div class="pr-locked rt-view">{!! RichText::render($r['material'] ?? '') !!}</div>
                            @else
                                <textarea class="rt-field" name="materials[{{ $i }}][material]" placeholder="Student learned ...">{{ RichText::editorValue($r['material'] ?? '') }}</textarea>
                            @endif
                        </td>
                        <td>
                            <select class="pr-in" name="materials[{{ $i }}][level]">
                                <option value="">-- pilih --</option>
                                @foreach($levels as $lv)<option value="{{ $lv }}" @selected(($r['level'] ?? '') === $lv)>{{ $lv }}</option>@endforeach
                            </select>
                        </td>
                        <td><textarea class="rt-field" name="materials[{{ $i }}][description]" placeholder="The student ...">{{ RichText::editorValue($r['description'] ?? '') }}</textarea></td>
                        <td class="no-print text-center">@unless($lock)<button type="button" class="pr-del" title="Hapus pertemuan">✕</button>@endunless</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-dark mb-3 no-print" id="addRow" @if($lock) style="display:none" @endif><i class="fa-solid fa-plus me-1"></i> Tambah pertemuan</button>

            <table class="pr-t" id="assTable">
                <thead>
                    <tr><th style="width:6%">No.</th><th style="width:24%">Assessment</th><th style="width:26%">Score</th><th style="width:10%">Grade</th><th style="width:30%">Note</th><th style="width:4%" class="no-print"></th></tr>
                </thead>
                <tbody>
                @foreach($assOld as $i => $a)
                    <tr class="pr-arow">
                        <td class="text-center fw-bold pr-no">{{ $i + 1 }}</td>
                        <td><input class="pr-in" name="assessments[{{ $i }}][assessment]" value="{{ $a['assessment'] ?? '' }}" maxlength="255"></td>
                        <td><textarea class="pr-in" style="min-height:70px" name="assessments[{{ $i }}][score]" maxlength="300" placeholder="Reading : 46&#10;Writing : 49&#10;Total : 95">{{ $a['score'] ?? '' }}</textarea></td>
                        <td><input class="pr-in text-center" name="assessments[{{ $i }}][grade]" value="{{ $a['grade'] ?? '' }}" maxlength="10"></td>
                        <td><textarea class="pr-in" style="min-height:70px" name="assessments[{{ $i }}][note]" maxlength="500">{{ $a['note'] ?? '' }}</textarea></td>
                        <td class="no-print text-center"><button type="button" class="pr-del" title="Hapus">✕</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-dark mb-3 no-print" id="addAss"><i class="fa-solid fa-plus me-1"></i> Tambah assessment</button>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="small fw-bold">Overall Achievement Level</label>
                    <select class="pr-in" name="overall_achievement_level">
                        @foreach($levels as $lv)<option value="{{ $lv }}" @selected(old('overall_achievement_level', $overall) === $lv)>{{ $lv }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="small fw-bold">Suggestion (opsional, tampil di dokumen)</label>
                    <textarea class="pr-in" style="min-height:60px" name="suggestion_box" maxlength="2000">{{ old('suggestion_box', $suggestion) }}</textarea>
                </div>
            </div>

                       <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1 no-print">
                    <label class="small fw-bold mb-0">Halaman Achievement Level (diketik guru, tampil di halaman terakhir PDF)</label>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="resetAchievement">Pakai teks standar</button>
                </div>
                <textarea id="achievement_page_field" class="rt-field" name="achievement_page">{{ RichText::editorValue(old('achievement_page', $achievementHtml ?? '')) }}</textarea>
                <div class="small text-muted no-print">Ketik sendiri sesuai kebutuhan siswa. Kosongkan seluruhnya bila halaman ini tidak ingin dicetak.</div>
            </div>

            <table style="width:78%; margin: 18px auto 0; border-collapse:collapse;">
                <tr>
                    <td style="border:1px solid #000; width:50%; height:110px; vertical-align:top; padding:8px;">
                        <b>Laoshi</b>
                        <div style="height:50px"></div>
                        <input class="pr-in fw-bold" name="header[teacher_sign]" value="{{ $h('teacher_sign') }}" maxlength="255" placeholder="Nama guru di kotak TTD">
                    </td>
                    <td style="border:1px solid #000; vertical-align:top; padding:8px;">
                        <div class="text-end small">{{ config('progress_report.city') }}, tanggal terbit otomatis</div>
                        <b>{{ config('progress_report.director_title') }}</b>
                        <div style="height:34px"></div>
                        <b>( {{ \App\Services\ProgressReportDocument::ownerName() }} )</b>
                    </td>
                </tr>
            </table>
        </div>

        <div class="d-flex gap-2 justify-content-center flex-wrap my-4 no-print">
            @if($canDraft)
                <button type="submit" name="action" value="draft" class="btn btn-outline-dark rounded-pill px-4"><i class="fa-solid fa-floppy-disk me-1"></i> {{ $draftLabel ?? 'Simpan Draft' }}</button>
            @endif
            @if(!empty($report?->id))
                <a href="{{ route('progress-report.document', $report) }}" target="_blank" class="btn btn-outline-secondary rounded-pill px-4"><i class="fa-regular fa-eye me-1"></i> Pratinjau</a>
                <a href="{{ route('progress-report.document.pdf', $report) }}" target="_blank" class="btn btn-outline-danger rounded-pill px-4"><i class="fa-solid fa-file-pdf me-1"></i> PDF</a>
            @endif
            @if($canSubmit)
                <button type="submit" name="action" value="submit" class="btn btn-warning fw-bold rounded-pill px-4" onclick="return confirm('Ajukan ke Kurikulum? Pastikan semua teks sudah benar.')"><i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Kurikulum</button>
            @endif
        </div>
    </form>

    @if(!empty($deleteUrl))
        <form method="POST" action="{{ $deleteUrl }}" class="text-center no-print" onsubmit="return confirm('Hapus draft report ini? Isian yang sudah ditulis akan hilang.')">
            @csrf @method('DELETE')
            <button class="btn btn-link text-danger"><i class="fa-regular fa-trash-can me-1"></i> Hapus draft ini</button>
        </form>
    @endif
</div>

@include('partials.rich-editor')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('resetAchievement');
    if (!btn) { return; }
    btn.addEventListener('click', function () {
        if (!confirm('Ganti isi halaman ini dengan teks standar?')) { return; }
        RT.setContent(document.getElementById('achievement_page_field'), @json($defaultAchievementHtml ?? ''));
    });
});
</script>

<script>
(function () {
    const levels = @json($levels);
    const tbody = document.querySelector('#rowsTable tbody');
    const abody = document.querySelector('#assTable tbody');
    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
    function reindex() {
        tbody.querySelectorAll('tr.pr-row').forEach((tr, i) => tr.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/materials\[\d+\]/, 'materials[' + i + ']');
        }));
        abody.querySelectorAll('tr.pr-arow').forEach((tr, i) => {
            tr.querySelector('.pr-no').textContent = i + 1;
            tr.querySelectorAll('[name]').forEach(el => el.name = el.name.replace(/assessments\[\d+\]/, 'assessments[' + i + ']'));
        });
    }
    function updateCounter() {
        const el = document.getElementById('prCounter'); if (!el) return;
        let n = 0;
        tbody.querySelectorAll('tr.pr-row').forEach(tr => {
            const lv = tr.querySelector('select').value;
            const ds = tr.querySelector('textarea[name$="[description]"]').value;
            if (lv || !RT.isEmpty(ds)) n++;
        });
        el.textContent = n;
    }
    tbody.addEventListener('change', updateCounter);
    document.addEventListener('rt-change', updateCounter);
    document.addEventListener('DOMContentLoaded', updateCounter);

    document.getElementById('addRow').addEventListener('click', function () {
        const i = tbody.querySelectorAll('tr.pr-row').length;
        const tr = document.createElement('tr'); tr.className = 'pr-row';
        tr.innerHTML = '<td><input class="pr-in text-center" name="materials[' + i + '][meeting]" value="' + (i + 1) + '" maxlength="10"></td>'
            + '<td><textarea class="rt-field" name="materials[' + i + '][material]" placeholder="Student learned ..."></textarea></td>'
            + '<td><select class="pr-in" name="materials[' + i + '][level]"><option value="">-- pilih --</option>' + levels.map(l => '<option value="' + esc(l) + '">' + esc(l) + '</option>').join('') + '</select></td>'
            + '<td><textarea class="rt-field" name="materials[' + i + '][description]" placeholder="The student ..."></textarea></td>'
            + '<td class="no-print text-center"><button type="button" class="pr-del" title="Hapus pertemuan">✕</button></td>';
        tbody.appendChild(tr);
        tr.querySelectorAll('textarea.rt-field').forEach(t => RT.add(t));
    });
    document.getElementById('addAss').addEventListener('click', function () {
        const i = abody.querySelectorAll('tr.pr-arow').length;
        const tr = document.createElement('tr'); tr.className = 'pr-arow';
        tr.innerHTML = '<td class="text-center fw-bold pr-no">' + (i + 1) + '</td>'
            + '<td><input class="pr-in" name="assessments[' + i + '][assessment]" maxlength="255"></td>'
            + '<td><textarea class="pr-in" style="min-height:70px" name="assessments[' + i + '][score]" maxlength="300"></textarea></td>'
            + '<td><input class="pr-in text-center" name="assessments[' + i + '][grade]" maxlength="10"></td>'
            + '<td><textarea class="pr-in" style="min-height:70px" name="assessments[' + i + '][note]" maxlength="500"></textarea></td>'
            + '<td class="no-print text-center"><button type="button" class="pr-del" title="Hapus">✕</button></td>';
        abody.appendChild(tr);
    });
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('pr-del')) return;
        const tr = e.target.closest('tr');
        if (tr.parentElement === tbody && tbody.querySelectorAll('tr.pr-row').length <= 1) { return; }
        RT.remove(tr);
        tr.remove(); reindex(); updateCounter();
    });
})();
</script>
@endsection