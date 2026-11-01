@extends('layouts.guru')
@section('title', 'Form Progress Report - ' . $e->student_name)

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-1">Pengisian Progress Report: {{ $e->student_name }}</h3>
            <p class="text-muted mb-0">
                Kelas <strong>{{ $e->class_name }}</strong> · Kehadiran: <strong>{{ $present }}/{{ $total }}</strong> · 
                <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock-rotate-left me-1"></i> Kelas Pengganti: {{ $replacementCount }} kali</span>
            </p>
        </div>
        <a href="{{ route('teacher.progress-report', ['class_id' => $e->class_id]) }}" class="btn btn-outline-secondary rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong class="d-block mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Terjadi Kesalahan Pengisian:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Dikembalikan Kurikulum --}}
    @if($report && $report->returned_at)
        <div class="alert alert-warning border-warning">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <strong>Dikembalikan Kurikulum untuk diperbaiki:</strong>
            {{ $report->review_note ?: 'Silakan perbaiki isian lalu kirim ulang.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.progress-report.store', $e->id) }}" id="progressReportForm">
        @csrf
        <input type="hidden" name="action" id="formActionInput" value="draft">

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Judul / Jenis Report</label>
                        <input name="report_type" class="form-control @error('report_type') is-invalid @enderror"
                               value="{{ old('report_type', $report->report_type ?? 'Trimester Academic Progress Report') }}" required>
                        @error('report_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Periode Pembelajaran</label>
                        <input name="report_period" class="form-control @error('report_period') is-invalid @enderror" placeholder="Contoh: Siklus 3 Bulan (Juli - September 2026)"
                               value="{{ old('report_period', $report->report_period ?? '') }}" required>
                        @error('report_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 1: LEARNING MATERIALS --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-book text-warning me-2"></i> 1. Learning Materials & Achievement Level</span>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="addMaterialRow()">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Materi
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="materialsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 32%;">Learning Materials</th>
                                <th style="width: 25%;">Achievement Level</th>
                                <th>Description</th>
                                <th style="width: 50px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="materialsTableBody">
                            @foreach($classMaterials as $i => $mat)
                                @php
                                    $matName = $mat->material_name;
                                    $prevRow = $savedMaterials->get($matName);
                                    $currLevel = old("materials.$i.level", $prevRow['level'] ?? 'Proficient');
                                    $currDesc = old("materials.$i.description", $prevRow['description'] ?? ($levelDescriptions[$currLevel] ?? 'Demonstrates a solid understanding of the material.'));
                                @endphp
                                <tr class="mat-row">
                                    <td class="text-center row-number">{{ $loop->iteration }}</td>
                                    <td>
                                        <input type="text" name="materials[{{ $i }}][material]" class="form-control form-control-sm fw-bold text-dark" value="{{ old("materials.$i.material", $matName) }}" placeholder="Nama materi pembelajaran" required>
                                    </td>
                                    <td>
                                        <select name="materials[{{ $i }}][level]" class="form-select form-select-sm ach-select" data-index="{{ $i }}" onchange="updateDesc(this)">
                                            @foreach($achievementChoices as $choice)
                                                <option value="{{ $choice }}" @selected($currLevel === $choice)>{{ $choice }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="materials[{{ $i }}][description]" rows="2" class="form-control form-control-sm desc-textarea">{{ $currDesc }}</textarea>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeMaterialRow(this)" title="Hapus baris">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: ASSESSMENT & OVERALL ACHIEVEMENT --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">
                <i class="fa-solid fa-list-check text-warning me-2"></i> 2. Assessment & Overall Achievement
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Assessment</th>
                                <th style="width: 15%;" class="text-center">Score (0-100)</th>
                                <th style="width: 15%;" class="text-center">Grade</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($savedAssessments as $aIdx => $aData)
                                <tr>
                                    <td>
                                        <input type="text" name="assessments[{{ $aIdx }}][assessment]" class="form-control" value="{{ old("assessments.$aIdx.assessment", $aData['assessment']) }}" placeholder="Nama aspek penilaian">
                                    </td>
                                    <td>
                                        <input type="number" name="assessments[{{ $aIdx }}][score]" class="form-control text-center score-input" min="0" max="100" value="{{ old("assessments.$aIdx.score", $aData['score']) }}" placeholder="0-100" data-idx="{{ $aIdx }}" oninput="autoCalcGrade(this)">
                                    </td>
                                    <td>
                                        <input type="text" name="assessments[{{ $aIdx }}][grade]" id="grade_{{ $aIdx }}" class="form-control text-center fw-bold" value="{{ old("assessments.$aIdx.grade", $aData['grade']) }}" placeholder="A/B/C">
                                    </td>
                                    <td>
                                        <input type="text" name="assessments[{{ $aIdx }}][note]" class="form-control" value="{{ old("assessments.$aIdx.note", $aData['note']) }}" placeholder="Catatan evaluasi...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- OVERALL ACHIEVEMENT LEVEL --}}
                <div class="row align-items-center mt-3 pt-3 border-top">
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-0">Overall Achievement Level (Capaian Keseluruhan):</label>
                        <small class="text-muted d-block">Penilaian level performa belajar siswa secara komprehensif.</small>
                    </div>
                    <div class="col-md-7">
                        <select name="overall_achievement_level" class="form-select fw-semibold">
                            @foreach(['Sufficient', 'Proficient', 'Excellent'] as $lvl)
                                <option value="{{ $lvl }}" @selected(old('overall_achievement_level', $overallLevel) === $lvl)>{{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 3: SUGGESTION BOX & KELAS PENGGANTI --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-comment-dots text-warning me-2"></i> 3. Suggestion Box & Catatan Sesi</span>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-2">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Kelas Pengganti: <strong>{{ $replacementCount }} kali</strong> (Otomatis)
                </span>
            </div>
            <div class="card-body p-4">
                <label class="form-label small text-muted">Isikan saran, masukan perkembangan, atau rekomendasi belajar untuk siswa ini:</label>
                <textarea name="suggestion_box" rows="3" class="form-control" placeholder="Tuliskan saran & masukan perkembangan siswa di sini...">{{ old('suggestion_box', $report->suggestion_box ?? '') }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="{{ route('teacher.progress-report', ['class_id' => $e->class_id]) }}" class="btn btn-outline-secondary rounded-pill px-4">
                Batal
            </a>
            <div class="d-flex gap-2">
                <button type="submit" onclick="document.getElementById('formActionInput').value='draft'" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Draft
                </button>
                <button type="submit" onclick="return confirmSubmit()" class="btn btn-warning rounded-pill px-5 fw-bold text-dark">
                    <i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Kurikulum
                </button>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
const defaultDescriptions = @json($levelDescriptions);

function updateDesc(sel) {
    const row = sel.closest('tr');
    const textarea = row.querySelector('.desc-textarea');
    if (textarea && defaultDescriptions[sel.value]) {
        textarea.value = defaultDescriptions[sel.value];
    }
}

function autoCalcGrade(input) {
    const idx = input.dataset.idx;
    const gradeInput = document.getElementById('grade_' + idx);
    if (!gradeInput) return;
    const val = parseFloat(input.value);
    if (isNaN(val)) return;
    if (val >= 85) { gradeInput.value = 'A'; }
    else if (val >= 75) { gradeInput.value = 'B'; }
    else if (val >= 60) { gradeInput.value = 'C'; }
    else { gradeInput.value = 'D'; }
}

function reindexRows() {
    const rows = document.querySelectorAll('#materialsTableBody tr.mat-row');
    rows.forEach((row, i) => {
        row.querySelector('.row-number').textContent = i + 1;
        row.querySelectorAll('input, select, textarea').forEach(el => {
            el.name = el.name.replace(/materials\[\d+\]/, `materials[${i}]`);
        });
    });
}

function removeMaterialRow(btn) {
    const rows = document.querySelectorAll('#materialsTableBody tr.mat-row');
    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris materi pembelajaran.');
        return;
    }
    btn.closest('tr').remove();
    reindexRows();
}

function addMaterialRow() {
    const tbody = document.getElementById('materialsTableBody');
    const count = tbody.querySelectorAll('tr.mat-row').length;
    const defaultText = defaultDescriptions['Proficient'] || 'Demonstrates a solid understanding of the material.';
    
    const tr = document.createElement('tr');
    tr.className = 'mat-row';
    tr.innerHTML = `
        <td class="text-center row-number">${count + 1}</td>
        <td>
            <input type="text" name="materials[${count}][material]" class="form-control form-control-sm fw-bold text-dark" placeholder="Nama materi pembelajaran" required>
        </td>
        <td>
            <select name="materials[${count}][level]" class="form-select form-select-sm ach-select" onchange="updateDesc(this)">
                <option value="Sufficient">Sufficient</option>
                <option value="Proficient" selected>Proficient</option>
                <option value="Excellent">Excellent</option>
            </select>
        </td>
        <td>
            <textarea name="materials[${count}][description]" rows="2" class="form-control form-control-sm desc-textarea">${defaultText}</textarea>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeMaterialRow(this)" title="Hapus baris">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function confirmSubmit() {
    if (confirm('Kirimkan progress report ini ke Kurikulum untuk di-ACC?')) {
        document.getElementById('formActionInput').value = 'submit';
        return true;
    }
    return false;
}
</script>
@endpush
@endsection
