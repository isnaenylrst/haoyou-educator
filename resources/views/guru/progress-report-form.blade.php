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
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong class="d-block mb-1">Periksa kembali isian:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
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

    <form method="POST" action="{{ route('teacher.progress-report.store', $e->id) }}">
        @csrf

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Judul / Jenis Report</label>
                        <input name="report_type" class="form-control"
                               value="{{ old('report_type', $report->report_type ?? 'Trimester Academic Progress Report') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Periode Pembelajaran</label>
                        <input name="report_period" class="form-control" placeholder="Contoh: Siklus 3 Bulan (Juli - September 2026)"
                               value="{{ old('report_period', $report->report_period ?? '') }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 1: LEARNING MATERIALS --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">
                <i class="fa-solid fa-book text-warning me-2"></i> 1. Learning Materials & Achievement Level
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 30%;">Learning Materials</th>
                                <th style="width: 25%;">Achievement Level</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classMaterials as $i => $mat)
                                @php
                                    $matName = $mat->material_name;
                                    $prevRow = $savedMaterials->get($matName);
                                    $currLevel = old("materials.$i.level", $prevRow['level'] ?? 'Proficient');
                                    $currDesc = old("materials.$i.description", $prevRow['description'] ?? ($levelDescriptions[$currLevel] ?? 'Demonstrates a solid understanding of the material.'));
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $matName }}</div>
                                        <input type="hidden" name="materials[{{ $i }}][material]" value="{{ $matName }}">
                                    </td>
                                    <td>
                                        <select name="materials[{{ $i }}][level]" class="form-select ach-select" data-index="{{ $i }}" required>
                                            @foreach($achievementChoices as $choice)
                                                <option value="{{ $choice }}" @selected($currLevel === $choice)>{{ $choice }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="materials[{{ $i }}][description]" id="desc_{{ $i }}" rows="2" class="form-control form-control-sm" required>{{ $currDesc }}</textarea>
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
                                        <input type="text" name="assessments[{{ $aIdx }}][assessment]" class="form-control" value="{{ old("assessments.$aIdx.assessment", $aData['assessment']) }}" placeholder="Nama aspek penilaian" required>
                                    </td>
                                    <td>
                                        <input type="number" name="assessments[{{ $aIdx }}][score]" class="form-control text-center" min="0" max="100" value="{{ old("assessments.$aIdx.score", $aData['score']) }}" placeholder="0-100" required>
                                    </td>
                                    <td>
                                        <input type="text" name="assessments[{{ $aIdx }}][grade]" class="form-control text-center" value="{{ old("assessments.$aIdx.grade", $aData['grade']) }}" placeholder="A/B/C" required>
                                    </td>
                                    <td>
                                        <input type="text" name="assessments[{{ $aIdx }}][note]" class="form-control" value="{{ old("assessments.$aIdx.note", $aData['note']) }}" placeholder="Catatan evaluasi...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- OVERALL ACHIEVEMENT LEVEL (SESUAI SPESIFIKASI POIN 5) --}}
                <div class="row align-items-center mt-3 pt-3 border-top">
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-0">Overall Achievement Level (Capaian Keseluruhan):</label>
                        <small class="text-muted d-block">Penilaian level performa belajar siswa secara komprehensif.</small>
                    </div>
                    <div class="col-md-7">
                        <select name="overall_achievement_level" class="form-select fw-semibold" required>
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
                <button type="submit" name="action" value="draft" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Draft
                </button>
                <button type="submit" name="action" value="submit" class="btn btn-warning rounded-pill px-5 fw-bold text-dark"
                        onclick="return confirm('Kirimkan progress report ini ke Kurikulum untuk di-ACC?')">
                    <i class="fa-solid fa-paper-plane me-1"></i> Ajukan ke Kurikulum
                </button>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
const defaultDescriptions = @json($levelDescriptions);

document.querySelectorAll('.ach-select').forEach(sel => {
    sel.addEventListener('change', function () {
        const idx = this.dataset.index;
        const targetTextarea = document.getElementById('desc_' + idx);
        if (targetTextarea && defaultDescriptions[this.value]) {
            targetTextarea.value = defaultDescriptions[this.value];
        }
    });
});
</script>
@endpush
@endsection
