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
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
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
            <div class="convert-main" style="min-width:0;">
                <form action="{{ $step1Action ?? route('admin.calon-siswa.convert.step1.store', $candidateStudent->id) }}" method="POST"
                      style="display:flex; flex-direction:column; gap:20px;">
                    @csrf

                    {{-- Kartu 1: Paket Program --}}
                    <div class="card">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fa-solid fa-box"></i> Paket Program</div>

                        <div class="form-field full">
                            <label>Tipe Paket *</label>
                            <div class="form-radio-group">
                                <label class="form-radio">
                                    <input type="radio" name="package_type" value="program"
                                           {{ $defaultPackageType === 'program' ? 'checked' : '' }} onchange="togglePackageType()">
                                    <span>Program Reguler</span>
                                </label>
                                <label class="form-radio">
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
                                <p class="form-section-note" style="margin-top: 4px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Level otomatis terisi untuk paket HSK
                                </p>
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

                                            $pg = $classProgress[$class->id] ?? [
                                                'done' => 0,
                                                'total' => 0,
                                                'last_date' => null
                                            ];
                                        @endphp

                                        <option value="{{ $class->id }}"
                                            data-package="{{ $class->program_package_id }}"
                                            data-level="{{ $class->level_id }}"
                                            data-done="{{ $pg['done'] }}"
                                            data-total="{{ $pg['total'] }}"
                                            data-last="{{ $pg['last_date'] }}"
                                            data-slots="{{ $class->slots_left }}">

                                            {{ $class->class_name }} ({{ $class->delivery_mode }}, {{ $class->status }})
                                            @if ($scheduleText) - {{ $scheduleText }} @endif
                                            @isset($class->slots_left)
                                                &middot; sisa {{ $class->slots_left }} slot
                                            @endisset
                                            @if ($pg['done'] > 0)
                                                &middot; sudah berjalan {{ $pg['done'] }}{{ $pg['total'] ? '/' . $pg['total'] : '' }} pertemuan
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Satu tampilan: teks bawaan saat kelas kosong, rincian progres saat kelas dipilih (diisi renderClassProgress) --}}
                                <p class="form-section-note" id="class-info" style="margin-top: 4px;"
                                   data-default="Jika tidak dipilih, siswa berstatus &quot;Menunggu Kelas&quot;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Jika tidak dipilih, siswa berstatus "Menunggu Kelas"</span>
                                </p>
                            </div>
                        </div>

                        {{-- Blok Private: paket + data kelas yang langsung dibuat --}}
                        <div class="form-grid" id="private-package-block"
                             style="display: {{ $defaultPackageType === 'private' ? 'grid' : 'none' }};">
                            <div class="form-field full">
                                <label>Private Package *</label>
                                <select name="private_package_id" id="private_package_id" onchange="onPrivatePackageChange()">
                                    <option value="">Pilih Package</option>
                                    @foreach ($privatePackages as $privatePackage)
                                        <option value="{{ $privatePackage->id }}" data-price="{{ $privatePackage->price }}" data-max="{{ $privatePackage->max_students ?? 1 }}"
                                            {{ $candidateStudent->private_package_id === $privatePackage->id ? 'selected' : '' }}>{{ $privatePackage->package_name }} &mdash; Rp {{ number_format($privatePackage->price, 0, ',', '.') }}</option>
                                    @endforeach
                                </select>
                                @if ($privatePackages->isEmpty())
                                    <small>Belum ada private package aktif.</small>
                                @endif
                            </div>

                            {{-- Materi yang diminta siswa private: per siswa, wajib diisi, tercetak di formulir --}}
                            <div class="form-field full" id="requested-material-field">
                                <label>Materi yang Diminta Siswa *</label>
                                <textarea name="requested_material" id="requested_material" rows="3" maxlength="2000"
                                          placeholder="Contoh: percakapan sehari-hari, persiapan HSK 3, belajar menulis hanzi">{{ old('requested_material', ($draft ?? [])['requested_material'] ?? '') }}</textarea>
                                <span class="form-section-note" style="margin-top:6px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Diisi per siswa, termasuk saat bergabung ke kelas private yang sudah ada. Materi ini tercetak di formulir pendaftaran.
                                </span>
                            </div>

                            {{-- Pilihan buat kelas baru / gabung kelas private yang sudah ada (konversi dan lanjut program) --}}
                            @if (true)
                                @php
                                    $oldJoinClassId = old('join_class_id', $draft['join_class_id'] ?? '');
                                    $oldPrivateMode = $oldJoinClassId ? 'join' : 'new';
                                @endphp

                                <div class="form-field full" id="private-mode-block" style="display:none;">
                                    <label>Kelas Private</label>
                                    <div class="form-radio-group">
                                        <label class="form-radio">
                                            <input type="radio" name="private_mode" value="new"
                                                   {{ $oldPrivateMode === 'new' ? 'checked' : '' }} onchange="applyPrivateState()">
                                            <span>Buat kelas baru</span>
                                        </label>
                                        <label class="form-radio" id="join-pill">
                                            <input type="radio" name="private_mode" value="join"
                                                   {{ $oldPrivateMode === 'join' ? 'checked' : '' }} onchange="applyPrivateState()">
                                            <span>Gabung kelas yang sudah ada</span>
                                        </label>
                                    </div>

                                    <div id="join-section" style="display:none; margin-top:10px;">
                                        <select name="join_class_id" id="join_class_id">
                                            <option value="">Pilih kelas</option>
                                            @foreach ($joinableClasses ?? [] as $jc)
                                                @php
                                                    $jcSchedule = $jc->schedules
                                                        ->map(fn ($sc) => $sc->day . ' ' . substr($sc->start_time, 0, 5) . '-' . substr($sc->end_time, 0, 5))
                                                        ->implode(', ');
                                                @endphp
                                                @php $jpg = $classProgress[$jc->id] ?? ['done' => 0, 'total' => 0, 'last_date' => null]; @endphp
                                                <option value="{{ $jc->id }}" data-package="{{ $jc->private_package_id }}"
                                                        data-done="{{ $jpg['done'] }}"
                                                        data-total="{{ $jpg['total'] }}"
                                                        data-last="{{ $jpg['last_date'] }}"
                                                        data-slots="{{ $jc->slots_left }}"
                                                        {{ (string) $oldJoinClassId === (string) $jc->id ? 'selected' : '' }}>
                                                    {{ $jc->class_name }}
                                                    &middot; {{ $jc->teacher?->name ?? 'Menunggu guru' }}
                                                    @if ($jcSchedule) &middot; {{ $jcSchedule }} @endif
                                                    &middot; sisa {{ $jc->slots_left }} slot
                                                    @if ($jpg['done'] > 0) &middot; sudah berjalan {{ $jpg['done'] }}{{ $jpg['total'] ? '/' . $jpg['total'] : '' }} pertemuan @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="form-section-note" id="join-empty-note" style="display:none; margin-top:6px;">
                                            <i class="fa-solid fa-circle-info"></i>
                                            Belum ada kelas dengan slot kosong untuk paket ini. Buat kelas baru.
                                        </span>
                                        <span class="form-section-note" id="join-progress-box" style="display:none; margin-top:6px;"></span>
                                        <span class="form-section-note" style="margin-top:6px;">
                                            <i class="fa-solid fa-circle-info"></i>
                                            Jadwal, guru, dan mode mengikuti kelas yang dipilih. Kalau kelas sudah berjalan,
                                            catatan otomatis terisi di langkah berikutnya dan masih bisa diedit.
                                        </span>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>

                    </div>{{-- /kartu Paket Program --}}

                    {{-- Kartu terpisah: hanya tampil untuk Private + buat kelas baru (disinkronkan applyPrivateState) --}}
                    <div class="card private-class-box" id="private-class-fields"
                         style="border-style:solid; margin:0;{{ $defaultPackageType === 'private' ? '' : ' display:none;' }}">
                        <div class="form-section-title"><i class="fa-solid fa-calendar-days"></i> Jadwal Kelas Private</div>

                        <div class="form-grid">
                            @php
                                $oldOfferMode = old('offer_mode', $draft['offer_mode'] ?? 'matching');
                                $oldOfferIds  = collect(old('offer_teacher_ids', $draft['offer_teacher_ids'] ?? []))
                                    ->map(fn ($i) => (int) $i)->all();
                                $offerModes = [
                                    'selected' => 'Pilih Laoshi',
                                    'matching' => 'Laoshi cocok',
                                    'all'      => 'Semua Laoshi',
                                    'none'     => 'Ajukan nanti',
                                ];
                            @endphp

                            <div class="form-field full">
                                <label>Ajukan ke Guru</label>

                                <div class="offer-modes">
                                    @foreach ($offerModes as $value => $label)
                                        <label class="offer-mode">
                                            <input type="radio" name="offer_mode" value="{{ $value }}"
                                                   {{ $oldOfferMode === $value ? 'checked' : '' }} onchange="applyOfferMode()">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="offer-teacher-list" id="offer-teacher-list">
                                    @forelse ($teachers ?? [] as $teacher)
                                        <label class="offer-teacher" data-teacher="{{ $teacher->id }}">
                                            <input type="checkbox" name="offer_teacher_ids[]" value="{{ $teacher->id }}"
                                                   {{ in_array($teacher->id, $oldOfferIds, true) ? 'checked' : '' }}>
                                            <span class="offer-teacher-name">{{ $teacher->name }}</span>
                                            <span class="offer-teacher-tag"></span>
                                        </label>
                                    @empty
                                        <span class="form-section-note" style="margin:0;">Belum ada guru aktif.</span>
                                    @endforelse
                                </div>

                                <p class="form-section-note" id="offer-hint" style="margin-top:8px;"></p>
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
                                <label>Jadwal Mingguan *</label>
                                <div class="schedule-rows" id="schedule-rows"></div>
                                <button type="button" class="schedule-add" onclick="addScheduleRow()">
                                    <i class="fa-solid fa-plus"></i> Tambah jadwal
                                </button>
                                <div id="schedule-check" class="schedule-check" style="display:none;"></div>
                                <span class="form-section-note" style="margin-top:6px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Baris awal diisi dari jadwal yang diminta calon siswa. Bentrok jadwal/ruangan dicek saat menekan "Lanjut ke Pembayaran".
                                </span>
                            </div>
                        </div>

                        <datalist id="room-suggestions">
                            @foreach ($roomSuggestions as $room)
                                <option value="{{ $room }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="form-footer" style="border-top:0; margin-top:0; padding-top:0;">
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
                            <div class="info-item-label">Umur</div>
                            <div class="info-item-value">
                                @php
                                    $umurCalon = $candidateStudent->birth_date
                                        ? \Carbon\Carbon::parse($candidateStudent->birth_date)->age
                                        : null;
                                @endphp
                                {{ $umurCalon !== null ? $umurCalon . ' Tahun' : '-' }}
                            </div>
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

        @php
            // Pelunasan/termin lanjutan: nominal dan tahap ditentukan sistem (PaymentInstallmentService::nextPayment).
            // Didefinisikan di awal step 2 supaya terpakai di form, catatan, dan ringkasan.
            $lockedPayment = null;

            if (! empty($settlement) && ! empty($nextPayment) && ! empty($nextPayment['exact'])) {
                $lockedPayment = $nextPayment;
            }
        @endphp

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
                      data-installment="{{ $canInstallment ? 1 : 0 }}"
                      data-reg-fee="{{ (float) ($registrationFee ?? 0) }}"
                      data-act-fee="{{ (float) ($activityFee ?? 0) }}"
                      data-settlement="{{ ! empty($settlement) ? 1 : 0 }}">
                    @csrf

                    <div class="form-section-title"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</div>

                    <div class="form-grid">

                        {{-- Skema pembayaran: hanya muncul kalau paket punya harga termin & boleh dicicil --}}
                        @if (isset($totalBillTermin) && $canInstallment && empty($settlement))
                            <div class="form-field full">
                                <label>Skema Pembayaran *</label>
                                <div class="form-radio-group">
                                    <label class="form-radio">
                                        <input type="radio" name="payment_scheme" value="Lunas"
                                               {{ old('payment_scheme', 'Lunas') === 'Lunas' ? 'checked' : '' }}>
                                        <span>Lunas di awal (Rp {{ number_format($lesTotal, 0, ',', '.') }})</span>
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="payment_scheme" value="Termin"
                                               {{ old('payment_scheme') === 'Termin' ? 'checked' : '' }}>
                                        <span>Termin (Rp {{ number_format($lesTotalTermin, 0, ',', '.') }} total)</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div class="form-field">
                            <label>
                                Nominal Dibayar *
                                @if ($lockedPayment)
                                    <span class="stage-chip">{{ $lockedPayment['stage'] }}</span>
                                @endif
                            </label>
                            <input type="number" name="amount_paid" id="amount_paid" step="0.01" required
                                   min="{{ $lockedPayment ? (float) $lockedPayment['amount'] : 1 }}"
                                   {{ $lockedPayment ? 'readonly' : '' }}
                                   value="{{ old('amount_paid', $lockedPayment ? (float) $lockedPayment['amount'] : '') }}">

                            @if ($lockedPayment)
                                <span class="form-section-note" style="margin-top:6px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Nominal {{ $lockedPayment['stage'] }} sudah ditentukan sistem sesuai skema pembayaran siswa.
                                </span>
                            @elseif (empty($settlement))
                                {{-- Rencana pembayaran: dihitung ulang di JS dari nominal yang diisi --}}
                                <span class="form-section-note" style="margin-top:6px;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span id="payment-plan">Isi nominal DP untuk melihat rencana pembayaran. Minimal DP <strong>Rp {{ number_format(max((float) ($registrationFee ?: ($activityFee ?? 0)), 1), 0, ',', '.') }}</strong>.</span>
                                </span>
                            @endif
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
                            @if (! empty($chosenClass) && ! empty($chosenProgress))
                                <div class="form-field full">
                                    <div class="class-progress-box {{ $chosenProgress['done'] > 0 ? '' : 'fresh' }}">
                                        <strong>{{ $chosenClass->class_name }}</strong><br>
                                        @if ($chosenProgress['done'] > 0)
                                            Sudah berjalan {{ $chosenProgress['done'] }}{{ $chosenProgress['total'] ? ' dari ' . $chosenProgress['total'] : '' }} pertemuan
                                            @if ($chosenProgress['last_date']) (terakhir {{ $chosenProgress['last_date'] }}) @endif.
                                            Siswa baru mulai dari pertemuan ke-{{ $chosenProgress['next'] }}.
                                        @else
                                            Kelas ini belum memulai pertemuan.
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="form-field full">
                                <label>Catatan Enrollment</label>
                                <textarea name="notes" id="notes" rows="3" maxlength="1000"
                                          placeholder="Contoh: Masuk di pertengahan kelas (mulai pertemuan ke-5), berhak potongan.">{{ old('notes', $isContinueFlow ? '' : trim(($midClassNote ?? '') . ' ' . ($candidateStudent->notes ?? ''))) }}</textarea>
                                <span class="form-section-note" style="margin-top:0;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Catatan ini akan tercetak di invoice.
                                    @if (! empty($midClassNote)) Terisi otomatis karena kelas sudah berjalan, silakan edit kalau perlu. @endif
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
                            @if ($lockedPayment)
                                Tahap dan nominal mengikuti jadwal pembayaran siswa. Program berikutnya baru bisa dipilih setelah lunas.
                            @else
                                Nominal tidak boleh melebihi sisa tagihan, dan program berikutnya baru bisa dipilih setelah lunas.
                            @endif
                        @else
                            Pembayaran pertama dicatat sebagai DP (minimal sebesar biaya pendaftaran). Sisanya dibayar setelah siswa aktif,
                            sesuai skema Lunas (sekali bayar) atau Termin (3 kali).
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
                        @if (! empty($settlement) && ! empty($nextPayment))
                            <span class="badge-rule cicil">{{ $nextPayment['stage'] }}</span>
                        @elseif ($canInstallment)
                            <span class="badge-rule cicil">DP minimal pendaftaran · Lunas atau Termin</span>
                        @else
                            <span class="badge-rule lunas">DP minimal pendaftaran · pelunasan sekali bayar</span>
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

                @php $latestPayment = $enrollment->packagePayments()->latest('id')->first(); @endphp
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

    /* ============================================================
       PEMILIH GURU (kartu)
       - Daftar guru selalu tampil, tiap guru diberi label "cocok" / "di luar jadwal"
         berdasarkan jadwal mingguan yang diisi (data TEACHER_SLOTS).
       - "Guru yang dicentang": checkbox bisa dipilih.
       - Mode lain: daftar jadi pratinjau (redup, tidak ikut terkirim):
         cocok -> yang cocok tercentang, semua guru -> semua tercentang, jangan ajukan -> kosong.
       Pengecekan akhir tetap dilakukan server saat kelas disimpan.
    ============================================================ */
    const offerChosen = new Set((@json($oldOfferIds ?? [])).map(Number));

    function currentScheduleRows() {
        const rows = [];

        document.querySelectorAll('#schedule-rows .schedule-row').forEach(row => {
            const day   = row.querySelector('select[name$="[day]"]')?.value;
            const start = row.querySelector('input[name$="[start_time]"]')?.value;
            const end   = row.querySelector('input[name$="[end_time]"]')?.value;

            if (day && start && end) rows.push({ day, start, end });
        });

        return rows;
    }

    // Guru cocok kalau SEMUA baris jadwal masuk jam tersedianya dan tidak bentrok dengan kelas lain
    function teacherMatches(id, rows) {
        const availability = (TEACHER_SLOTS.availability || {})[id] || {};
        const booked = (TEACHER_SLOTS.booked || {})[id] || {};

        return rows.every(r =>
            (availability[r.day] || []).some(a => a.start <= r.start && a.end >= r.end)
            && ! (booked[r.day] || []).some(b => r.start < b.end && b.start < r.end)
        );
    }

    function refreshOfferTeachers() {
        const list = document.getElementById('offer-teacher-list');
        if (! list) return;

        const rows  = currentScheduleRows();
        const cards = Array.from(list.querySelectorAll('.offer-teacher'));

        cards.forEach((card, i) => {
            if (card.dataset.order === undefined) card.dataset.order = i;

            const match = rows.length ? teacherMatches(card.dataset.teacher, rows) : null;
            card.dataset.match = match === null ? '' : (match ? '1' : '0');

            const tag = card.querySelector('.offer-teacher-tag');
            tag.textContent = match === null ? '' : (match ? 'cocok' : 'di luar jadwal');
            tag.className = 'offer-teacher-tag' + (match === null ? '' : (match ? ' is-match' : ' is-off'));
        });

        // yang cocok di depan, sisanya mengikuti urutan awal
        cards
            .sort((a, b) => (b.dataset.match === '1') - (a.dataset.match === '1') || a.dataset.order - b.dataset.order)
            .forEach(c => list.appendChild(c));

        applyOfferMode();
    }

    function applyOfferMode() {
        const checked = document.querySelector('input[name="offer_mode"]:checked');
        const mode = checked ? checked.value : 'matching';
        const radios = document.querySelectorAll('input[name="offer_mode"]');
        const active = radios.length > 0 && ! radios[0].disabled;

        const list = document.getElementById('offer-teacher-list');
        if (! list) return;

        list.classList.toggle('is-preview', mode !== 'selected');

        let matchCount = 0;
        let anyRows = false;

        list.querySelectorAll('.offer-teacher').forEach(card => {
            const cb = card.querySelector('input[type="checkbox"]');
            const id = Number(card.dataset.teacher);
            const match = card.dataset.match === '1';

            if (card.dataset.match !== undefined && card.dataset.match !== '') anyRows = true;
            if (match) matchCount++;

            cb.disabled = ! active || mode !== 'selected';

            if (mode === 'selected')      cb.checked = offerChosen.has(id);
            else if (mode === 'matching') cb.checked = match;
            else if (mode === 'all')      cb.checked = true;
            else                          cb.checked = false;
        });

        let hint = '';
        if (mode === 'selected') {
            hint = 'Centang minimal satu guru, atau pilih opsi lain.';
        } else if (mode === 'matching') {
            hint = ! anyRows
                ? 'Isi jadwal mingguan untuk melihat guru yang cocok.'
                : (matchCount > 0
                    ? matchCount + ' guru yang cocok akan diajukan saat kelas disimpan.'
                    : 'Belum ada guru yang cocok dengan jadwal ini. Pilih "Semua guru" atau centang guru satu per satu.');
        } else if (mode === 'all') {
            hint = 'Kelas diajukan ke semua guru aktif saat disimpan.';
        }

        const hintEl = document.getElementById('offer-hint');
        if (hintEl) {
            hintEl.textContent = hint;
            hintEl.style.display = hint ? '' : 'none';
        }
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

        // Mode gabung kelas (hanya ada di konversi calon siswa): form kelas baru disembunyikan & dinonaktifkan
        const modeBlock = document.getElementById('private-mode-block');
        const modeEl = document.querySelector('input[name="private_mode"]:checked');
        const joining = !! (isPrivate && modeBlock && modeBlock.style.display !== 'none' && modeEl && modeEl.value === 'join');

        const joinSection = document.getElementById('join-section');
        const joinSelect = document.getElementById('join_class_id');
        if (joinSection) joinSection.style.display = joining ? '' : 'none';
        if (joinSelect) {
            joinSelect.disabled = ! joining;
            joinSelect.required = joining;
        }

        // Kartu jadwal terpisah: tampil hanya untuk Private + buat kelas baru (bukan gabung kelas)
        box.style.display = (isPrivate && ! joining) ? '' : 'none';

        // Materi permintaan siswa: hanya untuk paket private
        const material = document.getElementById('requested_material');
        if (material) {
            material.disabled = ! isPrivate;
            material.required = isPrivate;
        }

        box.querySelectorAll('select, input').forEach(el => {
            if (el.classList.contains('js-room')) {
                el.disabled = ! isPrivate || joining || online;
                el.required = isPrivate && ! joining && ! online;
                el.closest('.room-wrap').style.display = online ? 'none' : '';
                el.closest('.schedule-row').classList.toggle('no-room', online);
            } else {
                el.disabled = ! isPrivate || joining;
            }
        });

        applyOfferMode();
    }

    /**
     * Pilihan "gabung kelas" hanya muncul untuk paket private yang boleh diikuti lebih dari 1 siswa
     * (max_students > 1), dan hanya menampilkan kelas dengan paket yang sama.
     */
    function onPrivatePackageChange() {
        const select = document.getElementById('private_package_id');
        const modeBlock = document.getElementById('private-mode-block');
        if (! modeBlock) { applyPrivateState(); return; }

        const opt = select.selectedOptions[0];
        const packageId = opt ? opt.value : '';
        const shared = !! (opt && opt.value && parseInt(opt.dataset.max || '1', 10) > 1);

        const joinSelect = document.getElementById('join_class_id');
        let available = 0;

        Array.from(joinSelect.options).forEach(o => {
            if (! o.value) return;
            const match = o.dataset.package === packageId;
            o.hidden = ! match;
            o.disabled = ! match;
            if (match) available++;
        });

        const stillValid = Array.from(joinSelect.options).some(o => o.value === joinSelect.value && ! o.disabled);
        if (! stillValid) joinSelect.value = '';

        document.getElementById('join-empty-note').style.display = available ? 'none' : '';

        const joinRadio = document.querySelector('input[name="private_mode"][value="join"]');
        const newRadio  = document.querySelector('input[name="private_mode"][value="new"]');
        joinRadio.disabled = ! available;
        document.getElementById('join-pill').style.opacity = available ? '' : '0.5';

        if (! shared || ! available) newRadio.checked = true;

        modeBlock.style.display = shared ? '' : 'none';

        applyPrivateState();
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
    /** Rincian progres kelas yang dipilih (reguler & private join): sudah berapa pertemuan berjalan. */
    function renderClassProgress(selectId, boxId) {
        const select = document.getElementById(selectId);
        const box = document.getElementById(boxId);
        if (! select || ! box) return;

        // Tampilan bergaya .form-section-note: ikon + teks. Kelas kosong -> teks bawaan (data-default)
        // kalau ada, kalau tidak disembunyikan. Kelas dipilih -> rincian progres kelas.
        const show = text => {
            box.innerHTML = '<i class="fa-solid fa-circle-info"></i><span>' + text + '</span>';
            box.style.display = '';
        };

        const opt = select.selectedOptions[0];
        if (! opt || ! opt.value) {
            if (box.dataset.default) { show(box.dataset.default); } else { box.style.display = 'none'; }
            return;
        }

        const done = parseInt(opt.dataset.done || '0', 10);
        const total = parseInt(opt.dataset.total || '0', 10);
        const last = opt.dataset.last || '';
        const slots = opt.dataset.slots;

        let html = '';
        if (done > 0) {
            html = 'Kelas ini <strong>sudah berjalan ' + done + (total ? ' dari ' + total : '') + ' pertemuan</strong>'
                 + '. Siswa baru mulai dari <strong>pertemuan ke-' + (done + 1) + '</strong>.';
        } else {
            html = 'Kelas ini belum memulai pertemuan.';
        }
        if (slots !== undefined && slots !== '') html += ' Sisa slot: ' + slots + '.';

        show(html);
    }

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
        renderClassProgress('class_id', 'class-info');
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
        ['input', 'change'].forEach(evt => rowsBox.addEventListener(evt, () => { checkScheduleRows(); refreshOfferTeachers(); }));
        // baris jadwal ditambah / dihapus
        new MutationObserver(refreshOfferTeachers).observe(rowsBox, { childList: true });
        renderTeacherAvailability();
        checkScheduleRows();

        // centang manual (hanya berlaku di mode "Guru yang dicentang")
        document.getElementById('offer-teacher-list')?.addEventListener('change', e => {
            const cb = e.target;
            if (! cb.matches('input[type="checkbox"]') || cb.disabled) return;
            const id = Number(cb.value);
            cb.checked ? offerChosen.add(id) : offerChosen.delete(id);
        });
        refreshOfferTeachers();

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

        // Pilihan gabung kelas (kembali dari step 2 / gagal validasi)
        onPrivatePackageChange();

        // Rincian progres kelas yang dipilih
        const regSel = document.getElementById('class_id');
        const joinSel = document.getElementById('join_class_id');
        if (regSel) { regSel.addEventListener('change', () => renderClassProgress('class_id', 'class-info')); renderClassProgress('class_id', 'class-info'); }
        if (joinSel) { joinSel.addEventListener('change', () => renderClassProgress('join_class_id', 'join-progress-box')); renderClassProgress('join_class_id', 'join-progress-box'); }
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
        const regFee        = parseFloat(form.dataset.regFee) || 0;
        const actFee        = parseFloat(form.dataset.actFee) || 0;
        const isSettlement  = form.dataset.settlement === '1';
        const discountInput = document.getElementById('discount');
        const amountInput   = document.getElementById('amount_paid');
        const planBox       = document.getElementById('payment-plan');
        const fillMin       = document.getElementById('fill-min');
        const fillTotal     = document.getElementById('fill-total');
        const rupiah        = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        let total = baseLunas;
        let minDp = 1;

        // true kalau admin memilih skema Termin (radio hanya ada untuk paket yang punya harga termin)
        function isTermin() {
            const checked = document.querySelector('input[name="payment_scheme"]:checked');
            return hasTermin && checked && checked.value === 'Termin';
        }

        // Sama dengan PaymentInstallmentService: minimal DP = biaya pendaftaran (kalau tidak ada: biaya aktivitas)
        function minimumDown(totalBill) {
            const min = regFee > 0 ? regFee : actFee;
            return Math.min(Math.max(min, 1), Math.max(totalBill, 1));
        }

        // Sebar uang masuk: pendaftaran, aktivitas, lalu biaya les (sama dengan PaymentInstallmentService::allocate)
        function allocate(amount, totalBill) {
            const lesNet = Math.max(totalBill - regFee - actFee, 0);
            const feeDue = totalBill - lesNet;
            const regDue = Math.min(regFee, feeDue);
            const actDue = feeDue - regDue;
            const toReg  = Math.min(amount, regDue);
            const toAct  = Math.min(amount - toReg, actDue);
            const toLes  = amount - toReg - toAct;
            return { toReg, toAct, toLes, actUnpaid: actDue - toAct, lesRemaining: Math.max(lesNet - toLes, 0) };
        }

        function renderPlan() {
            if (! planBox) return;

            const amount = parseFloat(amountInput.value) || 0;

            if (amount <= 0) {
                planBox.innerHTML = 'Isi nominal DP untuk melihat rencana pembayaran. Minimal DP <strong>' + rupiah(minDp) + '</strong>.';
                return;
            }

            if (amount + 0.005 < minDp) {
                planBox.innerHTML = '<strong>Nominal kurang.</strong> Minimal DP ' + rupiah(minDp) + ' (biaya pendaftaran).';
                return;
            }

            if (amount > total + 0.005) {
                planBox.innerHTML = '<strong>Nominal melebihi total tagihan</strong> (' + rupiah(total) + ').';
                return;
            }

            const a = allocate(amount, total);
            let html = '<strong>Rencana pembayaran</strong><br>'
                + 'DP ' + rupiah(amount) + ' &rarr; pendaftaran ' + rupiah(a.toReg)
                + (a.toAct > 0 ? ', aktivitas ' + rupiah(a.toAct) : '')
                + (a.toLes > 0 ? ', biaya les ' + rupiah(a.toLes) : '') + '.<br>';

            if (amount >= total - 0.005) {
                planBox.innerHTML = html + 'Tagihan <strong>lunas</strong>, tidak ada pembayaran berikutnya.';
                return;
            }

            if (isTermin()) {
                const base = Math.floor(a.lesRemaining / 3);
                const parts = [base + a.actUnpaid, base, a.lesRemaining - 2 * base];
                const rows = [];
                parts.forEach((v, i) => { if (v > 0) rows.push('Termin ' + (i + 1) + ': ' + rupiah(v)); });
                html += rows.join(' &middot; ')
                    + (a.actUnpaid > 0 ? '<br><small>Termin 1 sudah termasuk sisa biaya aktivitas ' + rupiah(a.actUnpaid) + '.</small>' : '');
            } else {
                html += 'Sisa <strong>' + rupiah(total - amount) + '</strong> dilunasi sekali bayar (Pelunasan).';
            }

            planBox.innerHTML = html;
        }

        function recalc() {
            const base = isTermin() ? baseTermin : baseLunas;
            const les  = isTermin() ? lesTermin : lesLunas;

            let discount = parseFloat(discountInput.value) || 0;
            discount = Math.min(Math.max(discount, 0), base);
            total = base - discount;
            minDp = isSettlement ? 1 : minimumDown(total);

            // Harga paket di ringkasan ikut berubah sesuai skema
            const pkg = document.getElementById('bill-package');
            if (pkg && ! isNaN(les)) pkg.textContent = rupiah(les);

            document.getElementById('bill-discount').textContent = '- ' + rupiah(discount);
            document.getElementById('bill-total').textContent = rupiah(total);

            const totalLabel = document.getElementById('fill-total-label');
            if (totalLabel) totalLabel.textContent = rupiah(total);

            const minLabel = document.getElementById('min-dp-label');
            if (minLabel) minLabel.textContent = rupiah(minDp);

            if (! amountInput.readOnly) amountInput.min = minDp;

            renderPlan();
        }

        if (fillMin)   fillMin.addEventListener('click',   () => { amountInput.value = minDp; renderPlan(); });
        if (fillTotal) fillTotal.addEventListener('click', () => { amountInput.value = total;  renderPlan(); });
        amountInput.addEventListener('input', renderPlan);

        document.querySelectorAll('input[name="payment_scheme"]').forEach(r => r.addEventListener('change', recalc));
        discountInput.addEventListener('input', recalc);
        recalc();
    });
</script>
@endpush
@endif