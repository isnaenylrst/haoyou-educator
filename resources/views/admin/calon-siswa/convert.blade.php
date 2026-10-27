{{-- resources/views/admin/calon-siswa/convert.blade.php
     Satu file untuk 3 step. Controller mengirim variabel $step (1, 2, atau 3).

     Dipakai oleh 3 alur:
     - Konversi calon siswa  (ConvertController)
     - Lanjut program, jalur 2: sudah lunas, 3 step (SiswaController::continue*)
     - Lanjut program, jalur 1: belum lunas, 2 step (SiswaController::settlement*)
--}}
@extends('admin.app')

@section('title', 'Konversi Calon Siswa | Haoyou Educator')

@push('styles')
<style>
    /* ============================================================
       WIZARD KONVERSI CALON SISWA (step 1, 2, 3 dalam satu file)
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
        padding: 24px;
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

    /* =====================================================
       STEPPER
    ===================================================== */

    .convert-steps {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .convert-step {
        flex: 1;
        min-width: 170px;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid #edeef1;
        border-radius: 10px;
        font-size: 12.5px;
        font-weight: 600;
        color: #9ca3af;
    }

    .convert-step .num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .convert-step.active {
        background: #FFF8D6;
        border-color: #ffd400;
        color: #111827;
    }

    .convert-step.active .num {
        background: #ffd400;
        color: #111;
    }

    .convert-step.done {
        color: #16a34a;
    }

    .convert-step.done .num {
        background: #dcfce7;
        color: #16a34a;
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

    .convert-layout.equal {
        grid-template-columns: 1fr 1fr;
    }

    .convert-side {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* =====================================================
       FORM
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
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
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
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

    .form-field select {
        text-overflow: ellipsis;
    }

    .form-field input[type="file"] {
        padding: 8px 10px;
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

    #class_id option[hidden],
    #level_id option[hidden] {
        display: none;
    }

    .link-fill {
        align-self: flex-start;
        background: none;
        border: 0;
        padding: 0;
        font-size: 11.5px;
        font-weight: 600;
        color: #2563eb;
        cursor: pointer;
    }

    .package-type-toggle {
        display: flex;
        gap: 12px;
        margin-top: 8px;
        margin-bottom: 20px;
    }

    .radio-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: 1px solid #d0d0d0;
        border-radius: 999px;
        cursor: pointer;
        font-size: 14px;
        transition: border-color 0.15s, background-color 0.15s;
    }

    .radio-pill:has(input:checked) {
        border-color: #c9a227;
        background-color: rgba(201, 162, 39, 0.08);
    }

    .radio-pill input[type="radio"] {
        margin: 0;
        accent-color: #c9a227;
    }

    .radio-pill span {
        white-space: nowrap;
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

    /* =====================================================
       KELAS PRIVATE (guru, mode, tanggal, jadwal mingguan)
    ===================================================== */

    .private-class-box {
        grid-column: 1 / -1;
        border-top: 1px dashed #e5e7eb;
        padding-top: 16px;
        margin-top: 4px;
    }

    .private-class-box .form-section-title {
        margin-bottom: 14px;
    }

    .schedule-rows {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .schedule-row {
        display: grid;
        grid-template-columns: 1.1fr 1fr 1fr 1.4fr auto;
        gap: 8px;
        align-items: center;
    }

    .schedule-row.no-room {
        grid-template-columns: 1.1fr 1fr 1fr auto;
    }

    .schedule-row select,
    .schedule-row input {
        width: 100%;
        box-sizing: border-box;
        padding: 9px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        outline: none;
        background: #fff;
        font: inherit;
        font-size: 13px;
        color: #111827;
    }

    .schedule-row select:focus,
    .schedule-row input:focus {
        border-color: #FFDD05;
        box-shadow: 0 0 0 3px rgba(255, 221, 5, 0.18);
    }

    .schedule-remove {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #edeef1;
        background: #fff;
        color: #9ca3af;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .schedule-remove:hover {
        color: #dc2626;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .schedule-add {
        align-self: flex-start;
        margin-top: 10px;
        background: none;
        border: 0;
        padding: 0;
        font-size: 12px;
        font-weight: 700;
        color: #2563eb;
        cursor: pointer;
    }

    /* =====================================================
       INFO (kolom samping / ringkasan)
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

    .info-item-value .badge-private-inline {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 8px;
        border-radius: 999px;
        background: rgba(201, 162, 39, 0.12);
        color: #b8860b;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        vertical-align: middle;
    }

    .schedule-list-item {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .bill-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px;
        color: #374151;
        padding: 6px 0;
    }

    .bill-row.total {
        border-top: 1px dashed #e5e7eb;
        margin-top: 4px;
        padding-top: 12px;
        font-weight: 700;
        color: #111827;
        font-size: 14px;
    }

    .badge-rule {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .badge-rule.lunas {
        background: #fef2f2;
        color: #dc2626;
    }

    .badge-rule.cicil {
        background: #f0fdf4;
        color: #16a34a;
    }

    /* =====================================================
       DOKUMEN (step 3)
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

    /* =====================================================
       SEARCH-SELECT (combobox: bisa diketik, bisa klik daftar)
    ===================================================== */

    .search-select {
        position: relative;
    }

    .search-select-native {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        pointer-events: none;
    }

    .search-select-input {
        position: relative;
        z-index: 1;
        cursor: text;
        background: #fff;
        width: 100%;
        box-sizing: border-box;
    }

    .search-select-list {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 30;
        max-height: 230px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, .1);
    }

    .search-select-list.open {
        display: block;
    }

    .search-select-item {
        padding: 8px 12px;
        font-size: 12.5px;
        color: #111827;
        cursor: pointer;
        line-height: 1.4;
    }

    .search-select-item:hover,
    .search-select-item.is-highlighted {
        background: #FFF8D6;
    }

    .search-select-item.is-selected {
        font-weight: 700;
    }

    .search-select-empty {
        padding: 8px 12px;
        font-size: 12px;
        color: #9ca3af;
    }

    /* =====================================================
       JADWAL TERSEDIA GURU (panel info di form kelas private)
    ===================================================== */

    .teacher-availability {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 10px 12px;
        border: 1px solid #edeef1;
        border-radius: 9px;
        background: #fafafa;
    }

    .ta-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .ta-day {
        width: 62px;
        flex-shrink: 0;
        padding-top: 3px;
        font-size: 12px;
        font-weight: 700;
        color: #374151;
    }

    .ta-slots {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .ta-chip {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
    }

    .ta-chip.free {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .ta-chip.busy {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .ta-legend {
        display: flex;
        gap: 8px;
        margin-top: 4px;
        padding-top: 6px;
        border-top: 1px dashed #e5e7eb;
    }

    .ta-empty {
        font-size: 12px;
        color: #9ca3af;
    }

    .ta-warn {
        font-size: 12px;
        font-weight: 600;
        color: #dc2626;
    }

    .schedule-check {
        margin-top: 8px;
        padding: 8px 12px;
        border-radius: 9px;
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #92400e;
        font-size: 12px;
        line-height: 1.6;
    }

    /* =====================================================
       RESPONSIVE
    ===================================================== */

    @media (max-width: 900px) {
        .convert-layout,
        .convert-layout.equal {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .card {
            padding: 18px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .schedule-row,
        .schedule-row.no-room {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endpush

@section('content')

    @php
        $step = $step ?? 1;

        // Alur "lanjut program" (siswa lama) ditandai variabel yang hanya dikirim SiswaController.
        // Konversi calon siswa tidak mengirim satu pun dari ketiganya.
        $isContinueFlow = isset($step1Action) || isset($formAction) || isset($doneTitle);

        $stepLabels = $stepLabels ?? [
            1 => $isContinueFlow ? 'Pilih Program' : 'Data Diri & Program',
            2 => 'Pembayaran & Bukti',
            3 => 'Invoice & Formulir',
        ];
        $stepDisplay = $stepDisplay ?? $step;
    @endphp

    {{-- ===================== HEADER (berbeda per step) ===================== --}}
    <div class="dashboard-header">
        <div>
            @if ($step === 3)
                <div class="eyebrow">{{ $eyebrow ?? 'CRM » Calon Siswa' }}</div>
                <h1>{{ $doneTitle ?? 'Konversi Berhasil' }}: {{ $student->name }}</h1>
                <p>{{ $subtitle ?? 'Siswa sudah aktif di sistem. Unduh dokumen yang diperlukan di bawah ini.' }}</p>
            @elseif ($step === 2)
                <div class="eyebrow">{{ $eyebrow ?? 'Jadikan Siswa:' }}</div>
                <h1>{{ $personName ?? $candidateStudent->name }}</h1>
                <p>{{ $subtitle ?? 'Catat pembayaran dan upload bukti transfer. Siswa baru dibuat setelah langkah ini disimpan.' }}</p>
            @else
                <div class="eyebrow">{{ $eyebrow ?? 'Jadikan Siswa:' }}</div>
                <h1>{{ $personName ?? $candidateStudent->name }}</h1>
                <p>{{ $subtitle ?? 'Pilih paket program untuk calon siswa ini, lalu lanjut ke pembayaran.' }}</p>
            @endif
        </div>
        <div class="dashboard-header-actions">
            @if ($step === 2)
                <a href="{{ $backUrl ?? route('admin.calon-siswa.convert', $candidateStudent->id) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> {{ isset($backUrl) ? 'Kembali' : 'Kembali ke Data Program' }}
                </a>
            @else
                <a href="{{ $step === 3 ? ($doneUrl ?? route('admin.calon-siswa.index')) : ($backUrl ?? route('admin.calon-siswa.index')) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> {{ $step === 3 ? ($doneLabel ?? 'Daftar Calon Siswa') : 'Kembali' }}
                </a>
            @endif
        </div>
    </div>

    {{-- ===================== STEPPER ===================== --}}
    <div class="convert-steps">
        @foreach ($stepLabels as $n => $label)
            <div class="convert-step {{ $stepDisplay == $n ? 'active' : ($stepDisplay > $n ? 'done' : '') }}">
                <span class="num">
                    @if ($stepDisplay > $n)
                        <i class="fa-solid fa-check"></i>
                    @else
                        {{ $n }}
                    @endif
                </span>
                {{ $label }}
            </div>
        @endforeach
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

    @if (session('error'))
        <div class="alert-error">
            <ul><li>{{ session('error') }}</li></ul>
        </div>
    @endif

    @if (session('success'))
        <div class="success-banner">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif


    {{-- =====================================================================
         STEP 1: DATA DIRI & PROGRAM
         (konversi calon siswa, dan "pilih program" untuk lanjut program)
    ===================================================================== --}}
    @if ($step === 1)

        @php
            $defaultPackageType = old('package_type', $draft['package_type'] ?? $defaultPackageType);

            // Isi awal baris jadwal private: old() > draft step 1 > jadwal preferensi calon siswa
            $prefillSchedules = old('schedules', $draft['schedules'] ?? null);
            if (empty($prefillSchedules)) {
                $prefillSchedules = $candidateStudent->availableSchedules
                    ->map(fn ($s) => [
                        'day'        => $s->day,
                        'start_time' => substr($s->start_time, 0, 5),
                        'end_time'   => substr($s->end_time, 0, 5),
                        'room'       => '',
                    ])
                    ->values()
                    ->all();
            }

            $oldTeacherId    = old('teacher_id', $draft['teacher_id'] ?? '');
            $oldDeliveryMode = old('delivery_mode', $draft['delivery_mode'] ?? 'Offline');
            $oldStartDate    = old('start_date', $draft['start_date'] ?? '');
            $oldEndDate      = old('end_date', '');
        @endphp

        <div class="convert-layout">

            {{-- ===================== FORM ===================== --}}
            <div class="card">
                <form action="{{ $step1Action ?? route('admin.calon-siswa.convert.step1.store', $candidateStudent->id) }}" method="POST">
                    @csrf

                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-box"></i> Paket Program</div>

                        <div class="form-field full">
                            <label>Tipe Paket *</label>
                            <div class="package-type-toggle">
                                <label class="radio-pill">
                                    <input type="radio" name="package_type" value="program"
                                           {{ $defaultPackageType === 'program' ? 'checked' : '' }} onchange="togglePackageType()">
                                    <span>Program Reguler</span>
                                </label>
                                <label class="radio-pill">
                                    <input type="radio" name="package_type" value="private"
                                           {{ $defaultPackageType === 'private' ? 'checked' : '' }} onchange="togglePackageType()">
                                    <span>Private</span>
                                </label>
                            </div>
                        </div>

                        {{-- Blok Program Reguler --}}
                        <div class="form-grid" id="program-package-block"
                             style="display: {{ $defaultPackageType === 'program' ? 'grid' : 'none' }};">

                            <div class="form-field full">
                                <label>Program Package *</label>
                                <select name="program_package_id" id="program_package_id" onchange="onProgramPackageChange()">
                                    <option value="">Pilih Package</option>
                                    @foreach ($packages as $package)
                                        @php
                                            $isRecommended = $recommendedCategory && $package->category_name === $recommendedCategory;
                                            $label = $package->package_name
                                                . ($isRecommended ? ' - ⭐ Direkomendasikan' : '')
                                                . ' - Rp ' . number_format($package->price, 0, ',', '.');
                                        @endphp
                                        <option value="{{ $package->id }}"
                                                data-price="{{ $package->price }}"
                                                data-category-id="{{ $package->category_id }}"
                                                data-level-id="{{ $package->level_id }}"
                                                {!! $isRecommended ? 'data-recommended="1"' : '' !!}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if ($packages->isEmpty())
                                    <small>Belum ada program package untuk program ini.</small>
                                @endif
                            </div>

                            <div class="form-field">
                                <label>Level *</label>
                                <select name="level_id" id="level_id" onchange="filterClasses()">
                                    <option value="">Pilih Level</option>
                                    @foreach ($levels as $level)
                                        <option value="{{ $level->id }}" data-category-id="{{ $level->category_id }}">
                                            {{ $level->category_name ? $level->category_name . ' - ' : '' }}{{ $level->level_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="form-section-note" style="margin-top:0;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Level otomatis terisi untuk paket HSK
                                </span>
                            </div>

                            <div class="form-field">
                                <label>Kelas (opsional, bisa ditentukan belakangan)</label>
                                <select name="class_id" id="class_id">
                                    <option value="">Belum ditentukan</option>
                                    @foreach ($classes as $class)
                                        @php
                                            $scheduleText = $class->schedules
                                                ->map(fn ($s) => substr($s->day, 0, 3) . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))
                                                ->implode(', ');
                                        @endphp
                                        <option value="{{ $class->id }}"
                                                data-package="{{ $class->program_package_id }}"
                                                data-level="{{ $class->level_id }}">
                                            {{ $class->class_name }} ({{ $class->delivery_mode }}, {{ $class->status }})@if ($scheduleText) - {{ $scheduleText }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-section-note" style="margin-top: 4px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Jika tidak dipilih, siswa berstatus "Menunggu Kelas"
                                </p>
                            </div>
                        </div>

                        {{-- Blok Private: paket + data kelas yang langsung dibuat --}}
                        <div class="form-grid" id="private-package-block"
                             style="display: {{ $defaultPackageType === 'private' ? 'grid' : 'none' }};">
                            <div class="form-field full">
                                <label>Private Package *</label>
                                <select name="private_package_id" id="private_package_id">
                                    <option value="">Pilih Package</option>
                                    @foreach ($privatePackages as $privatePackage)
                                        <option value="{{ $privatePackage->id }}" data-price="{{ $privatePackage->price }}"
                                            {{ $candidateStudent->private_package_id === $privatePackage->id ? 'selected' : '' }}>{{ $privatePackage->package_name }} &mdash; Rp {{ number_format($privatePackage->price, 0, ',', '.') }}</option>
                                    @endforeach
                                </select>
                                @if ($privatePackages->isEmpty())
                                    <small>Belum ada private package aktif.</small>
                                @endif
                            </div>

                            <div class="private-class-box" id="private-class-fields">
                                <div class="form-section-title"><i class="fa-solid fa-calendar-days"></i> Jadwal Kelas Private</div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Guru *</label>
                                        <select name="teacher_id" id="teacher_id" required onchange="onTeacherChange()">
                                            <option value="">Pilih Guru</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}"
                                                        {{ (string) $oldTeacherId === (string) $teacher->id ? 'selected' : '' }}>
                                                    {{ $teacher->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label>Mode Kelas *</label>
                                        <select name="delivery_mode" id="delivery_mode" required onchange="applyPrivateState()">
                                            <option value="Offline" {{ $oldDeliveryMode === 'Offline' ? 'selected' : '' }}>Offline</option>
                                            <option value="Online" {{ $oldDeliveryMode === 'Online' ? 'selected' : '' }}>Online</option>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label>Tanggal Mulai *</label>
                                        <input type="date" name="start_date" id="start_date" required value="{{ $oldStartDate }}">
                                    </div>

                                    <div class="form-field">
                                        <label>Tanggal Selesai (opsional)</label>
                                        <input type="date" name="end_date" id="end_date" value="{{ $oldEndDate }}">
                                        <span class="form-section-note" style="margin-top:0;">
                                            <i class="fa-solid fa-circle-info"></i>
                                            Kosongkan agar dihitung otomatis dari jumlah pertemuan paket
                                        </span>
                                    </div>

                                    <div class="form-field full">
                                        <label>Jadwal Tersedia Guru</label>
                                        <div id="teacher-availability" class="teacher-availability"></div>
                                    </div>

                                    <div class="form-field full">
                                        <label>Jadwal Mingguan *</label>
                                        <div class="schedule-rows" id="schedule-rows"></div>
                                        <button type="button" class="schedule-add" onclick="addScheduleRow()">
                                            <i class="fa-solid fa-plus"></i> Tambah jadwal
                                        </button>
                                        <div id="schedule-check" class="schedule-check" style="display:none;"></div>
                                        <span class="form-section-note" style="margin-top:6px;">
                                            <i class="fa-solid fa-circle-info"></i>
                                            Baris awal diisi dari jadwal yang diminta calon siswa. Sesuaikan dengan jadwal guru.
                                        </span>
                                    </div>
                                </div>

                                <datalist id="room-suggestions">
                                    @foreach ($roomSuggestions as $room)
                                        <option value="{{ $room }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <a href="{{ $cancelUrl ?? route('admin.calon-siswa.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            Lanjut ke Pembayaran <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- ===================== KOLOM SAMPING: DATA CALON SISWA ===================== --}}
            <div class="convert-side">
                <div class="card">
                    <div class="form-section-title">
                        <i class="fa-solid fa-user"></i> {{ isset($personName) ? 'Data Siswa' : 'Data Calon Siswa' }}
                    </div>
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
                            <div class="info-item-value">
                                @if ($candidateStudent->program)
                                    {{ $candidateStudent->program->program_name }}
                                @elseif ($candidateStudent->privatePackage)
                                    {{ $candidateStudent->privatePackage->package_name }}
                                    <span class="badge-private-inline">Private</span>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="info-item-label">Jadwal Tersedia</div>
                            <div class="info-item-value">
                                @forelse ($candidateStudent->availableSchedules as $schedule)
                                    <div class="schedule-list-item">
                                        {{ $schedule->day }},
                                        {{ \Illuminate\Support\Str::substr($schedule->start_time, 0, 5) }}–{{ \Illuminate\Support\Str::substr($schedule->end_time, 0, 5) }}
                                    </div>
                                @empty
                                    -
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


    {{-- =====================================================================
         STEP 2: PEMBAYARAN & UPLOAD BUKTI
         (konversi calon siswa, program baru siswa lama, dan pelunasan)
    ===================================================================== --}}
    @elseif ($step === 2)

        <div class="convert-layout">

            {{-- ===================== FORM PEMBAYARAN ===================== --}}
            <div class="card">
                <form action="{{ $formAction ?? route('admin.calon-siswa.convert.store', $candidateStudent->id) }}"
                      method="POST" enctype="multipart/form-data"
                      id="payment-form"
                      data-base="{{ (float) $totalBill }}"
                      data-base-termin="{{ isset($totalBillTermin) ? (float) $totalBillTermin : '' }}"
                      data-les-lunas="{{ isset($lesTotal) ? (float) $lesTotal : '' }}"
                      data-les-termin="{{ isset($lesTotalTermin) ? (float) $lesTotalTermin : '' }}"
                      data-installment="{{ $canInstallment ? 1 : 0 }}">
                    @csrf

                    <div class="form-section-title"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</div>

                    <div class="form-grid">

                        {{-- Skema pembayaran: hanya muncul kalau paket punya harga termin & boleh dicicil --}}
                        @if (isset($totalBillTermin) && $canInstallment && empty($settlement))
                            <div class="form-field full">
                                <label>Skema Pembayaran *</label>
                                <div class="package-type-toggle" style="margin:0;">
                                    <label class="radio-pill">
                                        <input type="radio" name="payment_scheme" value="Lunas"
                                               {{ old('payment_scheme', 'Lunas') === 'Lunas' ? 'checked' : '' }}>
                                        <span>Lunas di awal (Rp {{ number_format($lesTotal, 0, ',', '.') }})</span>
                                    </label>
                                    <label class="radio-pill">
                                        <input type="radio" name="payment_scheme" value="Termin"
                                               {{ old('payment_scheme') === 'Termin' ? 'checked' : '' }}>
                                        <span>Termin (Rp {{ number_format($lesTotalTermin, 0, ',', '.') }} total)</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div class="form-field">
                            <label>Nominal Dibayar *</label>
                            <input type="number" name="amount_paid" id="amount_paid" min="1" step="0.01" required
                                   value="{{ old('amount_paid', $canInstallment ? '' : (float) $totalBill) }}">
                            <button type="button" class="link-fill" id="fill-total">
                                Isi sesuai {{ empty($settlement) ? 'total tagihan' : 'sisa tagihan' }} (<span id="fill-total-label">Rp {{ number_format($totalBill, 0, ',', '.') }}</span>)
                            </button>
                        </div>

                        @if (empty($settlement))
                            <div class="form-field">
                                <label>Diskon (Rp)</label>
                                <input type="number" name="discount" id="discount" min="0" step="0.01"
                                       value="{{ old('discount', 0) }}">
                            </div>
                        @else
                            {{-- pelunasan tidak memakai diskon; input tetap ada karena dibaca JS --}}
                            <input type="hidden" name="discount" id="discount" value="0">
                        @endif

                        {{-- Catatan enrollment: konversi calon siswa dan lanjut program (tidak untuk pelunasan) --}}
                        @if (empty($settlement))
                            <div class="form-field full">
                                <label>Catatan Enrollment</label>
                                <textarea name="notes" id="notes" rows="3" maxlength="1000"
                                          placeholder="Contoh: Masuk di pertengahan kelas (mulai pertemuan ke-5), berhak potongan.">{{ old('notes', $isContinueFlow ? '' : ($candidateStudent->notes ?? '')) }}</textarea>
                                <span class="form-section-note" style="margin-top:0;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Catatan ini akan tercetak di invoice.
                                </span>
                            </div>
                        @endif

                        <div class="form-field">
                            <label>Metode Pembayaran *</label>
                            <select name="payment_method" required>
                                <option value="">Pilih Metode</option>
                                @foreach (['Transfer Bank', 'Cash', 'QRIS'] as $method)
                                    <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>
                                        {{ $method }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Tanggal Pembayaran *</label>
                            <input type="date" name="payment_date" required max="{{ now()->toDateString() }}"
                                   value="{{ old('payment_date', now()->toDateString()) }}">
                        </div>

                        <div class="form-field full">
                            <label>Bukti Pembayaran * (JPG, PNG, atau PDF, maks 4 MB)</label>
                            <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                    </div>

                    <p class="form-section-note">
                        <i class="fa-solid fa-circle-info"></i>
                        @if (! empty($settlement))
                            @if ($canInstallment)
                                Nominal boleh di bawah sisa tagihan (dicicil), tetapi program berikutnya baru bisa dipilih setelah lunas.
                            @else
                                Paket ini wajib dibayar lunas, jadi nominal harus sama dengan sisa tagihan.
                            @endif
                        @elseif ($canInstallment)
                            Nominal di bawah total tagihan dicatat sebagai DP. Sisanya bisa dibayar bertahap setelah siswa aktif.
                        @else
                            Paket ini wajib dibayar lunas sebelum program dimulai, jadi nominal harus sama dengan total tagihan.
                        @endif
                    </p>

                    <div class="form-footer">
                        <a href="{{ $cancelUrl ?? route('admin.calon-siswa.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i>
                            {{ isset($formAction) ? 'Simpan Pembayaran' : 'Simpan & Konversi Jadi Siswa' }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- ===================== RINGKASAN TAGIHAN ===================== --}}
            <div class="card">
                <div class="form-section-title"><i class="fa-solid fa-receipt"></i> Ringkasan Tagihan</div>

                <div class="info-list">
                    <div>
                        <div class="info-item-label">{{ $personLabel ?? 'Calon Siswa' }}</div>
                        <div class="info-item-value">{{ $personName ?? $candidateStudent->name }}</div>
                    </div>

                    <div>
                        <div class="info-item-label">Paket</div>
                        <div class="info-item-value">
                            {{ $package->package_name ?? '-' }}
                            <span style="font-size:11px;color:#9ca3af;font-weight:600;">
                                ({{ $isPrivate ? 'Private' : 'Reguler' }})
                            </span>
                        </div>
                    </div>

                    <div>
                        @if (! empty($settlement))
                            {{-- PELUNASAN (lanjut program, jalur 1) --}}
                            <div class="bill-row">
                                <span>Total tagihan program</span>
                                <span>Rp {{ number_format($settlement['total'], 0, ',', '.') }}</span>
                            </div>
                            <div class="bill-row">
                                <span>Sudah dibayar</span>
                                <span>Rp {{ number_format($settlement['paid'], 0, ',', '.') }}</span>
                            </div>
                            <span id="bill-discount" hidden></span>
                            <div class="bill-row total">
                                <span>Sisa tagihan</span>
                                <span id="bill-total">Rp {{ number_format($settlement['remaining'], 0, ',', '.') }}</span>
                            </div>
                        @else
                            {{-- KONVERSI CALON SISWA & PROGRAM BARU SISWA LAMA (lanjut program, jalur 2) --}}
                            <div class="bill-row">
                                <span>
                                    Harga paket
                                    <br><small style="color:#9ca3af;">{{ $package->total_meetings }} pertemuan · {{ $months }} bulan</small>
                                </span>
                                <span id="bill-package">Rp {{ number_format($lesTotal, 0, ',', '.') }}</span>
                            </div>

                            @if ($registrationFee > 0)
                                <div class="bill-row">
                                    <span>Biaya pendaftaran</span>
                                    <span>Rp {{ number_format($registrationFee, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            @if ($activityFee > 0)
                                <div class="bill-row">
                                    <span>Biaya aktivitas</span>
                                    <span>Rp {{ number_format($activityFee, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            <div class="bill-row">
                                <span>Diskon</span>
                                <span id="bill-discount">- Rp 0</span>
                            </div>
                            <div class="bill-row total">
                                <span>Total tagihan</span>
                                <span id="bill-total">Rp {{ number_format($totalBill, 0, ',', '.') }}</span>
                            </div>

                            @if (! empty($registrationNote))
                                <p class="form-section-note">
                                    <i class="fa-solid fa-circle-info"></i> {{ $registrationNote }}
                                </p>
                            @endif
                        @endif
                    </div>

                    <div>
                        <div class="info-item-label">Aturan Pembayaran</div>
                        @if ($canInstallment)
                            <span class="badge-rule cicil">Boleh DP / dicicil</span>
                        @else
                            <span class="badge-rule lunas">Wajib lunas</span>
                        @endif
                    </div>
                </div>
            </div>

        </div>


    {{-- =====================================================================
         STEP 3: DOWNLOAD INVOICE & FORMULIR
    ===================================================================== --}}
    @else

        <div class="convert-layout equal">

            <div class="card">
                <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Siswa</div>
                <div class="info-list">
                    <div>
                        <div class="info-item-label">Nama</div>
                        <div class="info-item-value">{{ $student->name }}</div>
                    </div>
                    <div>
                        <div class="info-item-label">Status Siswa</div>
                        <div class="info-item-value">{{ $student->status }}</div>
                    </div>
                    <div>
                        <div class="info-item-label">Program / Kelas</div>
                        <div class="info-item-value">
                            @if ($enrollment->private_package_id)
                                {{ $enrollment->privatePackage->package_name ?? 'Private' }}
                                @if ($enrollment->class)
                                    &mdash; {{ $enrollment->class->class_name }}
                                @endif
                            @else
                                {{ $enrollment->class?->class_name ?? ($enrollment->status === 'Waiting Class' ? 'Menunggu Kelas' : '-') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="form-section-title"><i class="fa-solid fa-file-lines"></i> Dokumen</div>

                {{-- Syarat & ketentuan sudah ada di halaman 1 formulir, jadi tidak perlu tombol terpisah --}}
                <div class="doc-download-item">
                    <div class="doc-info">
                        <div class="doc-icon"><i class="fa-solid fa-file-signature"></i></div>
                        <div>
                            <div class="doc-name">Formulir Perjanjian</div>
                            <div class="doc-note">Syarat &amp; ketentuan + data terisi otomatis, siap diprint &amp; ditandatangani</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.siswa.formulir', [$student->id, $enrollment->id]) }}"
                       target="_blank"
                       class="doc-download-btn"
                       title="Unduh Formulir Perjanjian">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>

                @php $latestPayment = $enrollment->payments()->latest('id')->first(); @endphp
                <div class="doc-download-item">
                    <div class="doc-info">
                        <div class="doc-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div>
                            <div class="doc-name">Invoice</div>
                            <div class="doc-note">Bukti pembayaran {{ $latestPayment?->payment_stage ?? '' }}</div>
                        </div>
                    </div>
                    @if ($latestPayment)
                        <a href="{{ route('admin.pembayaran.invoice', $latestPayment) }}" target="_blank"
                           class="doc-download-btn" title="Unduh Invoice">
                            <i class="fa-solid fa-download"></i>
                        </a>
                    @else
                        <span class="doc-download-btn disabled" title="Belum ada pembayaran">
                            <i class="fa-solid fa-download"></i>
                        </span>
                    @endif
                </div>
            </div>

        </div>

        <div class="form-footer">
            <a href="{{ route('admin.siswa') }}" class="btn btn-secondary">Lihat di Halaman Siswa</a>
            <a href="{{ $doneUrl ?? route('admin.calon-siswa.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-check"></i> Selesai
            </a>
        </div>

    @endif

@endsection


{{-- JavaScript hanya dimuat di step 1 (step 2 dan 3 tidak butuh) --}}
@if (($step ?? 1) === 1)
@push('scripts')
<script>
    /* ============================================================
       SEARCH-SELECT (generik): bungkus <select> jadi input+dropdown
       yang bisa diketik. Select asli TETAP ada di DOM (opacity 0)
       supaya name, required, .value, .dataset, onchange semuanya
       tetap berfungsi persis seperti select biasa.
    ============================================================ */
    function initSearchSelect(selectId, placeholder) {
        const select = document.getElementById(selectId);
        if (! select || select.dataset.searchInit) return;
        select.dataset.searchInit = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'search-select';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('search-select-native');

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'search-select-input';
        input.placeholder = placeholder || 'Ketik untuk mencari...';
        input.autocomplete = 'off';
        wrapper.appendChild(input);

        const list = document.createElement('div');
        list.className = 'search-select-list';
        wrapper.appendChild(list);

        let highlighted = -1;

        function visibleOptions() {
            return Array.from(select.options).filter(o => o.value && ! o.hidden && ! o.disabled);
        }

        function buildList(filterText) {
            const ft = (filterText || '').trim().toLowerCase();
            list.innerHTML = '';
            highlighted = -1;

            const matches = visibleOptions().filter(o => ! ft || o.textContent.toLowerCase().includes(ft));

            if (! matches.length) {
                const empty = document.createElement('div');
                empty.className = 'search-select-empty';
                empty.textContent = 'Tidak ada hasil';
                list.appendChild(empty);
                return;
            }

            matches.forEach((opt, idx) => {
                const item = document.createElement('div');
                item.className = 'search-select-item' + (opt.value === select.value ? ' is-selected' : '');
                item.textContent = opt.textContent.trim();
                item.dataset.value = opt.value;

                item.addEventListener('mousedown', function (e) {
                    e.preventDefault(); // supaya input tidak blur duluan sebelum klik terdaftar
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncInputFromSelect();
                    closeList();
                });

                item.addEventListener('mouseenter', function () {
                    highlighted = idx;
                    highlightItem();
                });

                list.appendChild(item);
            });
        }

        function highlightItem() {
            Array.from(list.children).forEach((el, idx) => {
                el.classList.toggle('is-highlighted', idx === highlighted);
            });
            const active = list.children[highlighted];
            if (active) active.scrollIntoView({ block: 'nearest' });
        }

        function syncInputFromSelect() {
            const opt = select.selectedOptions[0];
            input.value = (opt && opt.value) ? opt.textContent.trim() : '';
        }

        function currentSelectedText() {
            const opt = select.selectedOptions[0];
            return (opt && opt.value) ? opt.textContent.trim() : '';
        }

        function openList() {
            buildList(input.value === currentSelectedText() ? '' : input.value);
            list.classList.add('open');
        }

        function closeList() {
            list.classList.remove('open');
            syncInputFromSelect();
        }

        input.addEventListener('focus', function () {
            input.select();
            openList();
        });

        input.addEventListener('click', openList);

        input.addEventListener('input', function () {
            buildList(input.value);
            list.classList.add('open');
        });

        input.addEventListener('keydown', function (e) {
            const items = Array.from(list.children).filter(el => el.dataset.value !== undefined);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (! list.classList.contains('open')) { openList(); return; }
                highlighted = Math.min(highlighted + 1, items.length - 1);
                highlightItem();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlighted = Math.max(highlighted - 1, 0);
                highlightItem();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlighted >= 0 && items[highlighted]) {
                    items[highlighted].dispatchEvent(new Event('mousedown'));
                }
            } else if (e.key === 'Escape') {
                closeList();
                input.blur();
            }
        });

        document.addEventListener('click', function (e) {
            if (! wrapper.contains(e.target)) closeList();
        });

        select.__syncSearchInput = syncInputFromSelect;
        syncInputFromSelect();
    }

    function refreshSearchSelect(selectId) {
        const el = document.getElementById(selectId);
        if (el && el.__syncSearchInput) el.__syncSearchInput();
    }

    /* ============================================================
       JADWAL KELAS PRIVATE: baris jadwal mingguan dinamis
    ============================================================ */
    const DAYS = @json(\App\Services\ClassScheduleService::DAYS);
    let scheduleIndex = 0;

    function addScheduleRow(data) {
        data = data || {};
        const container = document.getElementById('schedule-rows');
        const i = scheduleIndex++;

        const row = document.createElement('div');
        row.className = 'schedule-row';
        row.innerHTML = `
            <select name="schedules[${i}][day]" required>
                <option value="">Hari</option>
                ${DAYS.map(d => `<option value="${d}">${d}</option>`).join('')}
            </select>
            <input type="time" name="schedules[${i}][start_time]" required>
            <input type="time" name="schedules[${i}][end_time]" required>
            <div class="room-wrap">
                <input type="text" class="js-room" name="schedules[${i}][room]"
                       list="room-suggestions" placeholder="Ruangan" maxlength="255" autocomplete="off">
            </div>
            <button type="button" class="schedule-remove" title="Hapus jadwal" onclick="removeScheduleRow(this)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        // Isi nilai lewat DOM (bukan string HTML) supaya aman dari karakter khusus
        row.querySelector(`[name="schedules[${i}][day]"]`).value = data.day || '';
        row.querySelector(`[name="schedules[${i}][start_time]"]`).value = data.start_time || '';
        row.querySelector(`[name="schedules[${i}][end_time]"]`).value = data.end_time || '';
        row.querySelector('.js-room').value = data.room || '';

        container.appendChild(row);
        applyPrivateState();
        checkScheduleRows();
    }

    function removeScheduleRow(btn) {
        const container = document.getElementById('schedule-rows');
        if (container.children.length <= 1) return; // minimal satu jadwal
        btn.closest('.schedule-row').remove();
        checkScheduleRows();
    }

    /* ============================================================
       JADWAL TERSEDIA GURU: ditampilkan ke admin + peringatan langsung
       kalau slot yang diisi di luar jam tersedia atau bentrok.
       (Validasi sebenarnya tetap dilakukan server saat submit.)
    ============================================================ */
    const TEACHER_SLOTS = @json($teacherSlots ?? ['availability' => [], 'booked' => []]);

    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function selectedTeacherSlots() {
        const select = document.getElementById('teacher_id');
        const id = select ? select.value : '';

        return {
            id,
            availability: (TEACHER_SLOTS.availability || {})[id] || {},
            booked: (TEACHER_SLOTS.booked || {})[id] || {},
        };
    }

    function renderTeacherAvailability() {
        const box = document.getElementById('teacher-availability');
        if (! box) return;

        const { id, availability, booked } = selectedTeacherSlots();

        if (! id) {
            box.innerHTML = '<span class="ta-empty">Pilih guru untuk melihat jadwal tersedianya.</span>';
            return;
        }

        const days = DAYS.filter(d => (availability[d] || []).length);

        if (! days.length) {
            box.innerHTML = '<span class="ta-warn">Guru ini belum mengisi jadwal tersedia, jadi belum bisa dijadwalkan.</span>';
            return;
        }

        box.innerHTML = days.map(day => {
            const free = availability[day]
                .map(a => `<span class="ta-chip free">${esc(a.start)}–${esc(a.end)}</span>`)
                .join('');

            const used = (booked[day] || [])
                .map(b => `<span class="ta-chip busy" title="${esc(b.class)}">${esc(b.start)}–${esc(b.end)} · ${esc(b.class)}</span>`)
                .join('');

            return `<div class="ta-row"><div class="ta-day">${day}</div><div class="ta-slots">${free}${used}</div></div>`;
        }).join('')
            + '<div class="ta-legend"><span class="ta-chip free">tersedia</span><span class="ta-chip busy">sudah terpakai kelas lain</span></div>';
    }

    function checkScheduleRows() {
        const out = document.getElementById('schedule-check');
        if (! out) return;

        const { id, availability, booked } = selectedTeacherSlots();
        const problems = [];

        if (id) {
            const hasAny = Object.values(availability).some(list => list.length);

            document.querySelectorAll('#schedule-rows .schedule-row').forEach(row => {
                const day   = row.querySelector('select[name$="[day]"]')?.value;
                const start = row.querySelector('input[name$="[start_time]"]')?.value;
                const end   = row.querySelector('input[name$="[end_time]"]')?.value;

                if (! day || ! start || ! end) return;

                const label = `${day} ${start}–${end}`;

                if (hasAny && ! (availability[day] || []).some(a => a.start <= start && a.end >= end)) {
                    problems.push(`${label}: di luar jadwal tersedia guru.`);
                }

                const clash = (booked[day] || []).find(b => start < b.end && b.start < end);
                if (clash) {
                    problems.push(`${label}: bentrok dengan kelas "${clash.class}".`);
                }
            });
        }

        out.style.display = problems.length ? 'block' : 'none';
        out.innerHTML = problems.map(p => `<div>⚠ ${esc(p)}</div>`).join('');
    }

    function onTeacherChange() {
        renderTeacherAvailability();
        checkScheduleRows();
    }

    /**
     * Sinkronkan kondisi field private:
     * - Disabled saat tipe = Reguler, supaya tidak ikut terkirim & tidak memicu validasi browser.
     * - Kolom ruangan disembunyikan dan dinonaktifkan saat mode Online (server mengisi "Online").
     */
    function applyPrivateState() {
        const isPrivate = document.querySelector('input[name="package_type"]:checked').value === 'private';
        const online = document.getElementById('delivery_mode').value === 'Online';
        const box = document.getElementById('private-class-fields');

        box.querySelectorAll('select, input').forEach(el => {
            if (el.classList.contains('js-room')) {
                el.disabled = ! isPrivate || online;
                el.required = isPrivate && ! online;
                el.closest('.room-wrap').style.display = online ? 'none' : '';
                el.closest('.schedule-row').classList.toggle('no-room', online);
            } else {
                el.disabled = ! isPrivate;
            }
        });
    }

    /* ============================================================
       LOGIKA STEP 1: toggle tipe paket, sinkron level, filter kelas
    ============================================================ */
    function togglePackageType() {
        const type = document.querySelector('input[name="package_type"]:checked').value;
        const programBlock = document.getElementById('program-package-block');
        const privateBlock = document.getElementById('private-package-block');
        const programSelect = document.getElementById('program_package_id');
        const privateSelect = document.getElementById('private_package_id');
        const levelSelect = document.getElementById('level_id');
        const classSelect = document.getElementById('class_id');

        if (type === 'private') {
            programBlock.style.display = 'none';
            privateBlock.style.display = 'grid';
            programSelect.removeAttribute('required');
            programSelect.value = '';
            levelSelect.removeAttribute('required');
            levelSelect.value = '';
            classSelect.value = '';
            privateSelect.setAttribute('required', 'required');
        } else {
            programBlock.style.display = 'grid';
            privateBlock.style.display = 'none';
            privateSelect.removeAttribute('required');
            privateSelect.value = '';
            programSelect.setAttribute('required', 'required');
            levelSelect.setAttribute('required', 'required');
        }

        applyPrivateState();

        refreshSearchSelect('program_package_id');
        refreshSearchSelect('level_id');
        refreshSearchSelect('private_package_id');
    }

    // Dipanggil saat Program Package berubah: sinkronkan pilihan Level, lalu filter Kelas
    function onProgramPackageChange() {
        syncLevelOptions();
        filterClasses();
    }

    // Batasi opsi Level ke kategori paket yang dipilih, dan kunci otomatis untuk paket ber-level tetap (HSK)
    function syncLevelOptions() {
        const pkgSelect = document.getElementById('program_package_id');
        const opt = pkgSelect.selectedOptions[0];
        const levelSelect = document.getElementById('level_id');

        const hasPackage = !!(opt && opt.value);
        const categoryId = hasPackage ? opt.dataset.categoryId : '';
        const fixedLevelId = hasPackage ? opt.dataset.levelId : '';

        Array.from(levelSelect.options).forEach(o => {
            if (! o.value) return;
            // Paket tanpa kategori tetap tidak membatasi pilihan level
            const match = ! categoryId || o.dataset.categoryId === categoryId;
            o.hidden = ! match;
            o.disabled = ! match;
        });

        if (fixedLevelId) {
            levelSelect.value = fixedLevelId;
        } else {
            const stillValid = Array.from(levelSelect.options)
                .some(o => o.value === levelSelect.value && ! o.hidden);
            if (! stillValid) levelSelect.value = '';
        }

        refreshSearchSelect('level_id');
    }

    // Filter Kelas berdasarkan Program Package DAN Level yang dipilih
    function filterClasses() {
        const packageId = document.getElementById('program_package_id').value;
        const levelId = document.getElementById('level_id').value;
        const classSelect = document.getElementById('class_id');

        [...classSelect.options].forEach(opt => {
            if (! opt.dataset.package) return;

            const packageMatch = ! packageId || opt.dataset.package === packageId;
            const levelMatch = ! levelId || ! opt.dataset.level || opt.dataset.level === levelId;

            opt.hidden = ! (packageMatch && levelMatch);
        });

        classSelect.value = '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSearchSelect('program_package_id', 'Ketik nama paket program...');
        initSearchSelect('level_id', 'Ketik nama level...');
        initSearchSelect('private_package_id', 'Ketik nama paket private...');

        // Baris jadwal private dibuat dulu, baru togglePackageType() mengatur disabled/required-nya
        const initialSchedules = @json($prefillSchedules);
        if (initialSchedules && initialSchedules.length) {
            initialSchedules.forEach(s => addScheduleRow(s));
        } else {
            addScheduleRow();
        }

        togglePackageType();

        // Panel jadwal guru + cek jadwal: tampil awal, lalu ikut berubah saat baris jadwal diedit
        const rowsBox = document.getElementById('schedule-rows');
        ['input', 'change'].forEach(evt => rowsBox.addEventListener(evt, checkScheduleRows));
        renderTeacherAvailability();
        checkScheduleRows();

        const recommendedOption = document.querySelector('#program_package_id option[data-recommended="1"]');
        if (recommendedOption) {
            recommendedOption.selected = true;
        }

        syncLevelOptions();
        filterClasses();

        refreshSearchSelect('program_package_id');
        refreshSearchSelect('level_id');
        refreshSearchSelect('private_package_id');

        // Kembalikan pilihan sebelumnya kalau admin kembali dari step 2
        const draft = @json($draft ?? []);

        if (draft.program_package_id) {
            document.getElementById('program_package_id').value = draft.program_package_id;
            onProgramPackageChange();

            if (draft.level_id) {
                document.getElementById('level_id').value = draft.level_id;
                refreshSearchSelect('level_id');
                filterClasses();
            }

            if (draft.class_id) {
                document.getElementById('class_id').value = draft.class_id;
            }

            refreshSearchSelect('program_package_id');
        }

        if (draft.private_package_id) {
            document.getElementById('private_package_id').value = draft.private_package_id;
            refreshSearchSelect('private_package_id');
        }
    });
</script>
@endpush
@endif


{{-- JavaScript step 2: hitung ulang total saat diskon diisi atau skema pembayaran diganti --}}
@if (($step ?? 1) === 2)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form          = document.getElementById('payment-form');
        const baseLunas     = parseFloat(form.dataset.base);
        const baseTermin    = parseFloat(form.dataset.baseTermin);
        const lesLunas      = parseFloat(form.dataset.lesLunas);
        const lesTermin     = parseFloat(form.dataset.lesTermin);
        const hasTermin     = ! isNaN(baseTermin);
        const installment   = form.dataset.installment === '1';
        const discountInput = document.getElementById('discount');
        const amountInput   = document.getElementById('amount_paid');
        const rupiah        = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        let total = baseLunas;

        // true kalau admin memilih skema Termin (radio hanya ada untuk paket yang punya harga termin)
        function isTermin() {
            const checked = document.querySelector('input[name="payment_scheme"]:checked');
            return hasTermin && checked && checked.value === 'Termin';
        }

        function recalc() {
            const base = isTermin() ? baseTermin : baseLunas;
            const les  = isTermin() ? lesTermin : lesLunas;

            let discount = parseFloat(discountInput.value) || 0;
            discount = Math.min(Math.max(discount, 0), base);
            total = base - discount;

            // Harga paket di ringkasan ikut berubah sesuai skema
            const pkg = document.getElementById('bill-package');
            if (pkg && ! isNaN(les)) pkg.textContent = rupiah(les);

            document.getElementById('bill-discount').textContent = '- ' + rupiah(discount);
            document.getElementById('bill-total').textContent = rupiah(total);
            document.getElementById('fill-total-label').textContent = rupiah(total);

            // Paket wajib lunas: nominal otomatis mengikuti total setelah diskon
            if (! installment) amountInput.value = total;
        }

        document.getElementById('fill-total').addEventListener('click', () => {
            amountInput.value = total;
        });

        document.querySelectorAll('input[name="payment_scheme"]').forEach(r => r.addEventListener('change', recalc));
        discountInput.addEventListener('input', recalc);
        recalc();
    });
</script>
@endpush
@endif