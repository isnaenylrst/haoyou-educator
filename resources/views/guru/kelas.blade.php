@extends('layouts.guru')

@section('title', 'Kelas')

@section('content')

@php
    use Carbon\Carbon;

    // Warna badge status pengajuan LP & PPT
    $statusBadge = [
        'Approved' => ['class' => 'bg-success',            'text' => 'ACC'],
        'Pending'  => ['class' => 'bg-info text-dark',     'text' => 'Menunggu Review'],
        'Rejected' => ['class' => 'bg-warning text-dark',  'text' => 'Belum ACC'],
    ];
@endphp

<div class="container-fluid py-3">

    {{-- ================= HEADER ================= --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Kelas</h2>
        <p class="text-muted">
            Kelas baru menggunakan materi yang telah disiapkan Kurikulum.
            Guru membuat Lesson Plan & PPT kemudian mengirimnya untuk direview.
        </p>
    </div>

    {{-- ================= FLASH MESSAGE ================= --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= TABS UTAMA ================= --}}
    @php
        $curTab = request()->query('tab', $activeTab ?? 'buat-lp');
    @endphp

    <ul class="nav nav-pills mb-4 gap-2" id="kelasMainTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-semibold {{ $curTab === 'buat-lp' ? 'active' : '' }}"
               id="tab-buat-lp"
               data-bs-toggle="pill"
               href="#pane-buat-lp"
               role="tab"
               onclick="history.replaceState(null, '', '?tab=buat-lp')">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Buat LP & PPT Kelas Baru
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-semibold {{ $curTab === 'tugas-materi' ? 'active' : '' }}"
               id="tab-tugas-materi"
               data-bs-toggle="pill"
               href="#pane-tugas-materi"
               role="tab"
               onclick="history.replaceState(null, '', '?tab=tugas-materi')">
                <i class="fa-solid fa-file-pen me-1"></i> Tugas Perbaikan Materi
                @if (($pendingMaterialTasksCount ?? 0) > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $pendingMaterialTasksCount }}</span>
                @else
                    <span class="badge bg-light text-muted rounded-pill ms-1">0</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-semibold {{ $curTab === 'tugas-kelas' ? 'active' : '' }}"
               id="tab-tugas-kelas"
               data-bs-toggle="pill"
               href="#pane-tugas-kelas"
               role="tab"
               onclick="history.replaceState(null, '', '?tab=tugas-kelas')">
                <i class="fa-solid fa-chalkboard-user me-1"></i> Tugas Perbaikan Kelas
                @if (($pendingClassTasksCount ?? 0) > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $pendingClassTasksCount }}</span>
                @else
                    <span class="badge bg-light text-muted rounded-pill ms-1">0</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="tab-content" id="kelasMainTabContent">

        {{-- ==================== TAB 1: BUAT LP & PPT KELAS BARU ==================== --}}
        <div class="tab-pane fade {{ $curTab === 'buat-lp' ? 'show active' : '' }}" id="pane-buat-lp" role="tabpanel">

    {{-- ==================== FORM UPLOAD ==================== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">

            <h4 class="fw-bold mb-1">Buat LP & PPT Kelas Baru</h4>
            <p class="text-muted mb-4">
                Materi, silabus dan kosakata berasal dari Kurikulum.
                Guru hanya membuat Lesson Plan serta PPT.
            </p>

            <form action="{{ route('teacher.kelas.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="classSelect">Kelas Anda <span class="text-danger">*</span></label>
                        <select id="classSelect"
                                name="class_id"
                                class="form-select @error('class_id') is-invalid @enderror"
                                required>
                            <option value="">-- Pilih Kelas Yang Diampu --</option>
                            @foreach ($myClasses as $c)
                                <option value="{{ $c->id }}" @selected(old('class_id', $selectedClassId) == $c->id)>
                                    {{ $c->class_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="categorySelect">Kategori Materi</label>
                        <select id="categorySelect" class="form-select">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="materialSelect">Pilih Materi Kurikulum <span class="text-danger">*</span></label>
                        <select id="materialSelect"
                                name="material_id"
                                class="form-select @error('material_id') is-invalid @enderror"
                                required>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}"
                                        data-category="{{ $material->level->category->id ?? '' }}"
                                        @selected(old('material_id') == $material->id)>
                                    {{ $material->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('material_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="lpFile">Upload Lesson Plan (PDF) <span class="text-danger">*</span></label>
                        <input type="file"
                               id="lpFile"
                               name="lp_file"
                               accept="application/pdf"
                               class="form-control @error('lp_file') is-invalid @enderror"
                               required>
                        @error('lp_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Format PDF, maksimal 10 MB.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="pptFile">Upload PPT <span class="text-danger">*</span></label>
                        <input type="file"
                               id="pptFile"
                               name="ppt_file"
                               accept=".pdf,.ppt,.pptx"
                               class="form-control @error('ppt_file') is-invalid @enderror"
                               required>
                        @error('ppt_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Format PDF / PPT / PPTX, maksimal 20 MB.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="notes">Catatan Tambahan (Opsional)</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Tuliskan catatan atau rencana silabus khusus untuk kelas ini...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success px-4">
                    <i class="fas fa-upload me-2"></i>
                    Upload & Kirim Review
                </button>
            </form>

        </div>
    </div>

    {{-- =================== PROGRESS =================== --}}
    @if ($activeSubmission && $progressSteps)

        @php
            $activeBadge = $statusBadge[$activeSubmission->status] ?? ['class' => 'bg-secondary', 'text' => $activeSubmission->status];
            $activeCategory = $activeSubmission->material->level->category->category_name ?? null;
        @endphp

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="fw-bold">
                            Progress LP — {{ $activeSubmission->ppt_title }}
                            @if ($activeCategory)
                                <span class="text-muted fw-normal">({{ $activeCategory }})</span>
                            @endif
                        </h5>
                        <small class="text-muted">
                            Konsultasi wajib selesai sebelum kelas berjalan.
                        </small>
                    </div>

                    <div>
                        <span class="badge {{ $activeBadge['class'] }}">
                            {{ $activeBadge['text'] }}
                        </span>
                    </div>
                </div>

                @if ($activeSubmission->status === 'Rejected' && $activeSubmission->review_note)
                    <div class="alert alert-warning mt-3 mb-0 py-2">
                        <strong>Catatan Kurikulum:</strong>
                        {{ $activeSubmission->review_note }}
                    </div>
                @endif

                <hr>

                @foreach ($progressSteps as $step)
                    @php
                        $isConsultationStep = array_key_exists('type', $step);
                        $consultation = $step['consultation'] ?? null;
                        $locked = $step['locked'] ?? false;

                        // Tanggal konsultasi (menyesuaikan nama kolom yang tersedia)
                        $rawDate = $consultation->scheduled_at
                            ?? $consultation->consultation_date
                            ?? $consultation->schedule_date
                            ?? $consultation->date
                            ?? null;

                        // PERBAIKAN 1: tampilkan hari, tanggal, dan JAM konsultasi
                        $dateText = $rawDate
                            ? Carbon::parse($rawDate)->translatedFormat('l, j F Y \p\u\k\u\l H:i')
                            : null;
                    @endphp

                    <div class="d-flex justify-content-between align-items-center {{ $loop->last ? '' : 'mb-3' }}">
                        <div>
                            <strong>{{ $step['label'] }}</strong>

                            @if ($isConsultationStep)
                                <br>

                                {{-- PERBAIKAN 2: teks status + lokasi/link dari Kurikulum --}}
                                <small class="text-muted">
                                    @if ($consultation && $consultation->status === 'Dibatalkan')
                                        Dibatalkan oleh Kurikulum — ajukan ulang jika masih diperlukan
                                    @elseif ($consultation && $dateText)
                                        {{ $dateText }}
                                    @elseif ($consultation)
                                        {{ $consultation->status }}
                                    @elseif ($locked)
                                        Selesaikan tahap sebelumnya terlebih dahulu
                                    @else
                                        Belum dijadwalkan
                                    @endif
                                </small>

                                @if ($consultation && $consultation->notes && $consultation->status !== 'Dibatalkan')
                                    <br>
                                    <small>
                                        <i class="fas fa-location-dot me-1"></i>{{ $consultation->notes }}
                                    </small>
                                @endif
                            @endif
                        </div>

                        <div>
                            @if ($isConsultationStep)

                                {{-- PERBAIKAN 3: badge status + tombol "Ajukan Ulang" jika dibatalkan --}}
                                @if ($consultation)
                                    @if ($consultation->status === 'Selesai')
                                        <span class="badge bg-success">Selesai</span>
                                    @elseif ($consultation->status === 'Belum Terjadwal')
                                        <span class="badge bg-secondary">Menunggu Jadwal</span>
                                    @elseif ($consultation->status === 'Dibatalkan')
                                        <span class="badge bg-danger me-1">Dibatalkan</span>
                                        <form action="{{ route('teacher.kelas.request-consultation', $activeSubmission->id) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            <input type="hidden" name="type" value="{{ $step['type'] }}">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                Ajukan Ulang
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-info text-dark">{{ $consultation->status }}</span>
                                    @endif

                                @elseif ($locked)
                                    <span class="badge bg-secondary">Terkunci</span>

                                @else
                                    <form action="{{ route('teacher.kelas.request-consultation', $activeSubmission->id) }}"
                                          method="POST"
                                          class="d-inline">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $step['type'] }}">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            Ajukan Jadwal
                                        </button>
                                    </form>
                                @endif

                            @else
                                @php
                                    $text = $step['status_text'] ?? 'Menunggu';
                                    $stepClass = match ($text) {
                                        'Selesai'      => 'bg-success',
                                        'Perlu Revisi' => 'bg-danger',
                                        default        => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $stepClass }}">{{ $text }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    @endif

    {{-- ==================== DAFTAR KELAS ==================== --}}
    <div class="mb-3" id="classFilter">
        <button type="button" class="btn btn-dark btn-sm rounded-pill" data-filter="all">Semua</button>
        @foreach ($categories as $category)
            <button type="button"
                    class="btn btn-outline-secondary btn-sm rounded-pill"
                    data-filter="{{ $category->id }}">
                {{ $category->category_name }}
            </button>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="list-group list-group-flush" id="classList">

            @forelse ($submissions as $submission)
                @php
                    $badge = $statusBadge[$submission->status] ?? ['class' => 'bg-secondary', 'text' => $submission->status];
                    $isApproved = $submission->status === 'Approved';
                    $categoryId = $submission->material->level->category->id ?? '';
                    $categoryName = $submission->material->level->category->category_name ?? '-';
                @endphp

                <div class="list-group-item py-3 class-item" data-category="{{ $categoryId }}">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="fw-semibold mb-0">{{ $submission->ppt_title }}</h6>
                                <span class="badge bg-dark-subtle text-dark border">v{{ $submission->current_version ?: 1 }}</span>
                                @if($submission->class)
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                        <i class="fa-solid fa-chalkboard me-1"></i> {{ $submission->class->class_name }}
                                    </span>
                                @endif
                            </div>
                            <small class="text-muted">
                                {{ $categoryName }} · LP & PPT · Dikirim {{ $submission->created_at->translatedFormat('j M Y') }}
                            </small>

                            @if (in_array($submission->status, ['Rejected', 'Perlu Revisi']) && $submission->review_note)
                                <div class="small text-danger mt-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Catatan Kurikulum: {{ $submission->review_note }}
                                </div>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $badge['class'] }}">{{ $badge['text'] }}</span>

                            {{-- Tombol Riwayat Versi --}}
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#versionHistoryModal{{ $submission->id }}">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat Versi ({{ $submission->versions->count() }})
                            </button>

                            @if ($isApproved)
                                {{-- Crosscheck: lihat file LP & PPT --}}
                                <div class="dropdown">
                                    <button class="btn btn-outline-dark btn-sm dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                        Dokumen
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if ($submission->lp_file_path)
                                            <li>
                                                <a class="dropdown-item" target="_blank"
                                                   href="{{ Storage::url($submission->lp_file_path) }}">
                                                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> Lihat Lesson Plan
                                                </a>
                                            </li>
                                        @endif
                                        @if ($submission->ppt_file_path)
                                            <li>
                                                <a class="dropdown-item" target="_blank"
                                                   href="{{ Storage::url($submission->ppt_file_path) }}">
                                                    <i class="fa-solid fa-file-powerpoint text-warning me-1"></i> Lihat PPT
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @else
                                <button type="button"
                                        class="btn btn-warning btn-sm fw-semibold"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reuploadModal{{ $submission->id }}">
                                    <i class="fa-solid fa-upload me-1"></i> Upload Revisi
                                </button>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- ========== MODAL RIWAYAT VERSI DOKUMEN ========== --}}
                <div class="modal fade" id="versionHistoryModal{{ $submission->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header bg-light">
                                <div>
                                    <h5 class="modal-title fw-bold mb-0">
                                        <i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> Riwayat Revisi & Versi Dokumen
                                    </h5>
                                    <small class="text-muted">{{ $submission->ppt_title }} {{ $submission->class ? '· ' . $submission->class->class_name : '' }}</small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body p-4">
                                @if($submission->versions->isEmpty())
                                    <div class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-file-circle-check fs-2 text-secondary mb-2"></i>
                                        <p class="mb-0">Belum ada riwayat revisi tambahan. Dokumen ini masih berada pada versi awal (v1).</p>
                                    </div>
                                @else
                                    <div class="timeline">
                                        @foreach($submission->versions->sortByDesc('version_number') as $ver)
                                            <div class="card border mb-3 rounded-3 shadow-none">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <span class="badge bg-dark fw-bold me-2">Versi {{ $ver->version_number }}</span>
                                                            <span class="badge {{ $ver->status === 'Disetujui' ? 'bg-success' : ($ver->status === 'Perlu Revisi' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                                {{ $ver->status }}
                                                            </span>
                                                        </div>
                                                        <small class="text-muted">
                                                            {{ $ver->created_at->translatedFormat('d M Y, H:i') }}
                                                        </small>
                                                    </div>

                                                    @if($ver->notes)
                                                        <div class="small mb-2">
                                                            <strong>Catatan Guru:</strong>
                                                            <span class="text-secondary">{{ $ver->notes }}</span>
                                                        </div>
                                                    @endif

                                                    @if($ver->review_note)
                                                        <div class="alert alert-warning py-2 px-3 small mb-2">
                                                            <strong>Evaluasi Kurikulum:</strong> {{ $ver->review_note }}
                                                            @if($ver->reviewer)
                                                                <div class="text-muted mt-1" style="font-size:11px;">
                                                                    Direview oleh: {{ $ver->reviewer->name }} ({{ $ver->reviewed_at?->translatedFormat('d M Y H:i') }})
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    <div class="d-flex gap-2 mt-2 pt-2 border-top">
                                                        @if($ver->lp_file_path)
                                                            <a href="{{ Storage::url($ver->lp_file_path) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                                                                <i class="fa-solid fa-file-pdf me-1"></i> Unduh LP v{{ $ver->version_number }}
                                                            </a>
                                                        @endif
                                                        @if($ver->ppt_file_path)
                                                            <a href="{{ Storage::url($ver->ppt_file_path) }}" target="_blank" class="btn btn-sm btn-outline-warning text-dark">
                                                                <i class="fa-solid fa-file-powerpoint me-1"></i> Unduh PPT v{{ $ver->version_number }}
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== MODAL UPLOAD ULANG / REVISI BARU ========== --}}
                @unless ($isApproved)
                    <div class="modal fade" id="reuploadModal{{ $submission->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form class="modal-content rounded-4 border-0 shadow"
                                  action="{{ route('teacher.kelas.reupload', $submission->id) }}"
                                  method="POST"
                                  enctype="multipart/form-data">
                                @csrf

                                <div class="modal-header bg-light">
                                    <h5 class="modal-title fw-bold">Upload Revisi Versi {{ ($submission->current_version ?: 1) + 1 }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body p-4">
                                    <p class="text-muted small mb-3">
                                        Unggah berkas LP dan/atau PPT yang telah diperbaiki. Versi baru akan tercatat di sistem dan Kurikulum akan menerima notifikasi.
                                    </p>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Lesson Plan Baru (PDF)</label>
                                        <input type="file" name="lp_file" accept="application/pdf" class="form-control">
                                        <small class="text-muted">Biarkan kosong jika tidak mengubah Lesson Plan.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">PPT Baru (PDF / PPT / PPTX)</label>
                                        <input type="file" name="ppt_file" accept=".pdf,.ppt,.pptx" class="form-control">
                                        <small class="text-muted">Biarkan kosong jika tidak mengubah PPT.</small>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-semibold">Catatan Poin Revisi <span class="text-danger">*</span></label>
                                        <textarea name="notes" rows="3" class="form-control" placeholder="Jelaskan perubahan yang telah Anda lakukan pada dokumen ini..." required></textarea>
                                        <small class="text-muted">Wajib diisi agar mempermudah tim Kurikulum mereview kembali.</small>
                                    </div>
                                </div>

                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim Revisi v{{ ($submission->current_version ?: 1) + 1 }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endunless

            @empty
                <div class="list-group-item py-4 text-center text-muted">
                    Belum ada LP & PPT yang dikirim.
                </div>
            @endforelse

            <div class="list-group-item py-4 text-center text-muted d-none" id="classEmptyFilter">
                Tidak ada kelas pada kategori ini.
            </div>

        </div>
    </div>

        </div> {{-- End pane-buat-lp --}}

        {{-- ==================== TAB 2: TUGAS PERBAIKAN MATERI ==================== --}}
        <div class="tab-pane fade {{ $curTab === 'tugas-materi' ? 'show active' : '' }}" id="pane-tugas-materi" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="fw-bold mb-1">Tugas Perbaikan Materi Guru</h4>
                            <p class="text-muted mb-0">
                                Daftar tugas perbaikan materi / modul mengajar yang ditugaskan Kurikulum langsung kepada Anda.
                            </p>
                        </div>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($materialTasks as $t)
                            @php
                                $effStatus = $t->effective_status;
                                $isOverdue = $t->is_overdue;
                                $isDone = $t->status === 'Done';
                                $sub = $t->latestSubmission;
                            @endphp
                            <div class="list-group-item py-3 px-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <h5 class="fw-bold mb-0">{{ $t->title }}</h5>
                                            <span class="badge bg-purple text-white" style="background-color: #6f42c1;">
                                                <i class="fa-solid fa-file-pen me-1"></i> Perbaikan Materi
                                            </span>

                                            @if ($t->priority === 'High')
                                                <span class="badge bg-danger">Prioritas Tinggi</span>
                                            @elseif ($t->priority === 'Medium')
                                                <span class="badge bg-warning text-dark">Prioritas Sedang</span>
                                            @elseif ($t->priority === 'Low')
                                                <span class="badge bg-light text-muted border">Prioritas Rendah</span>
                                            @endif

                                            @if ($effStatus === 'Selesai')
                                                <span class="badge bg-success">
                                                    <i class="fa-solid fa-check-circle me-1"></i> Selesai
                                                </span>
                                            @elseif ($effStatus === 'Perlu Revisi')
                                                <span class="badge bg-danger">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> Perlu Revisi
                                                </span>
                                            @elseif ($effStatus === 'Diajukan')
                                                <span class="badge bg-info text-dark">
                                                    <i class="fa-solid fa-clock me-1"></i> Menunggu Review
                                                </span>
                                            @elseif ($isOverdue)
                                                <span class="badge bg-danger">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Lewat Tenggat
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa-solid fa-circle-dot me-1"></i> Ditugaskan
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-muted small mb-2">
                                            <strong>Kelas:</strong> Tidak terkait kelas
                                            · <strong>Materi Terkait:</strong> {{ $t->material ? $t->material->title . ' (Pertemuan ' . $t->material->meeting_number . ')' : 'Tidak terkait materi spesifik' }}
                                            @if ($t->due_date)
                                                · <strong>Tenggat:</strong>
                                                <span class="{{ $isOverdue && !$isDone ? 'badge bg-danger text-white' : ($t->due_date->diffInDays(now()) <= 2 && !$isDone ? 'badge bg-warning text-dark' : 'fw-semibold') }}">
                                                    {{ $t->due_date->translatedFormat('d M Y') }}
                                                    @if ($isOverdue && !$isDone) (Terlewat) @endif
                                                </span>
                                            @endif
                                            · Dibuat: {{ $t->created_at->translatedFormat('d M Y') }}
                                            @if ($t->creator) oleh {{ $t->creator->name ?? $t->creator->username }} @endif
                                        </div>

                                        @if ($t->description)
                                            <div class="p-3 rounded-3 bg-light border mb-2 small">
                                                <strong>Instruksi & Poin Perbaikan:</strong>
                                                <div class="mt-1">{!! nl2br(e($t->description)) !!}</div>
                                            </div>
                                        @endif

                                        @if ($t->attachment_path)
                                            <div class="small mb-2">
                                                <i class="fa-solid fa-paperclip text-primary me-1"></i>
                                                <a href="{{ Storage::url($t->attachment_path) }}" target="_blank" class="fw-semibold text-decoration-none">
                                                    {{ $t->attachment_name ?? 'Unduh Lampiran Kurikulum' }}
                                                </a>
                                            </div>
                                        @endif

                                        @if ($sub && in_array($sub->status, ['Rejected', 'Perlu Revisi']) && $sub->review_note)
                                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                <strong>Catatan Revisi dari Kurikulum:</strong> {{ $sub->review_note }}
                                            </div>
                                        @endif

                                        @if ($sub)
                                            <div class="small text-muted mt-1">
                                                <i class="fa-solid fa-file-circle-check text-success me-1"></i>
                                                Pengajuan perbaikan terakhir dikirim {{ $sub->created_at->translatedFormat('d M Y H:i') }} (v{{ $sub->current_version ?: 1 }} - Status: <strong>{{ $sub->status }}</strong>)
                                            </div>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        @if ($effStatus === 'Selesai')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                                <i class="fa-solid fa-check me-1"></i> Tugas Tuntas
                                            </span>
                                        @elseif ($effStatus === 'Perlu Revisi' && $sub)
                                            <button type="button" class="btn btn-danger btn-sm rounded-pill fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#reuploadModal{{ $sub->id }}">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Upload Ulang Perbaikan
                                            </button>
                                        @elseif ($effStatus === 'Diajukan' && $sub)
                                            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#reuploadModal{{ $sub->id }}">
                                                <i class="fa-solid fa-upload me-1"></i> Update Berkas
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-sm rounded-pill fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#uploadTaskModal{{ $t->id }}">
                                                <i class="fa-solid fa-upload me-1"></i> Kerjakan / Upload Perbaikan
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL UPLOAD PERBAIKAN UNTUK TUGAS INI --}}
                            <div class="modal fade" id="uploadTaskModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <form class="modal-content rounded-4 border-0 shadow"
                                          action="{{ route('teacher.kelas.store') }}"
                                          method="POST"
                                          enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="task_id" value="{{ $t->id }}">
                                        @if ($t->material_id)
                                            <input type="hidden" name="material_id" value="{{ $t->material_id }}">
                                        @endif

                                        <div class="modal-header bg-light">
                                            <div>
                                                <h5 class="modal-title fw-bold mb-0">Upload Perbaikan Materi</h5>
                                                <small class="text-muted">{{ $t->title }}</small>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <div class="alert alert-info py-2 px-3 small mb-3">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                Silakan unggah Lesson Plan dan PPT perbaikan untuk tugas ini. Setelah dikirim, pengajuan akan otomatis terhubung ke tugas dan direview oleh Kurikulum.
                                            </div>

                                            @if (!$t->material_id)
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Pilih Materi Kurikulum (Opsional)</label>
                                                    <select name="material_id" class="form-select">
                                                        <option value="">-- Tidak terkait materi spesifik --</option>
                                                        @foreach ($materials as $m)
                                                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Upload Lesson Plan (PDF) <span class="text-danger">*</span></label>
                                                    <input type="file" name="lp_file" accept="application/pdf" class="form-control" required>
                                                    <small class="text-muted">Format PDF, maksimal 10 MB.</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Upload PPT <span class="text-danger">*</span></label>
                                                    <input type="file" name="ppt_file" accept=".pdf,.ppt,.pptx" class="form-control" required>
                                                    <small class="text-muted">Format PDF / PPT / PPTX, maksimal 20 MB.</small>
                                                </div>
                                            </div>

                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">Catatan Perbaikan (Opsional)</label>
                                                <textarea name="notes" rows="3" class="form-control" placeholder="Tuliskan catatan penjelasan perubahan yang dilakukan..."></textarea>
                                            </div>
                                        </div>

                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                                                <i class="fa-solid fa-paper-plane me-1"></i> Kirim Perbaikan ke Kurikulum
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-clipboard-check fs-1 text-secondary mb-3 d-block"></i>
                                <h5>Belum ada tugas perbaikan materi</h5>
                                <p class="mb-0">Semua materi Anda saat ini tidak memerlukan perbaikan atau revisi dari Kurikulum.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div> {{-- End pane-tugas-materi --}}

        {{-- ==================== TAB 3: TUGAS PERBAIKAN KELAS ==================== --}}
        <div class="tab-pane fade {{ $curTab === 'tugas-kelas' ? 'show active' : '' }}" id="pane-tugas-kelas" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="fw-bold mb-1">Tugas Perbaikan Kelas</h4>
                            <p class="text-muted mb-0">
                                Daftar tugas perbaikan terkait kelas yang Anda ampu dari Kurikulum.
                            </p>
                        </div>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($classTasks as $t)
                            @php
                                $effStatus = $t->effective_status;
                                $isOverdue = $t->is_overdue;
                                $isDone = $t->status === 'Done';
                                $sub = $t->latestSubmission;
                            @endphp
                            <div class="list-group-item py-3 px-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <h5 class="fw-bold mb-0">{{ $t->title }}</h5>
                                            <span class="badge bg-secondary">
                                                <i class="fa-solid fa-chalkboard-user me-1"></i> Tugas Kelas
                                            </span>

                                            @if ($t->priority === 'High')
                                                <span class="badge bg-danger">Prioritas Tinggi</span>
                                            @elseif ($t->priority === 'Medium')
                                                <span class="badge bg-warning text-dark">Prioritas Sedang</span>
                                            @elseif ($t->priority === 'Low')
                                                <span class="badge bg-light text-muted border">Prioritas Rendah</span>
                                            @endif

                                            @if ($effStatus === 'Selesai')
                                                <span class="badge bg-success">
                                                    <i class="fa-solid fa-check-circle me-1"></i> Selesai
                                                </span>
                                            @elseif ($effStatus === 'Perlu Revisi')
                                                <span class="badge bg-danger">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> Perlu Revisi
                                                </span>
                                            @elseif ($effStatus === 'Diajukan')
                                                <span class="badge bg-info text-dark">
                                                    <i class="fa-solid fa-clock me-1"></i> Menunggu Review
                                                </span>
                                            @elseif ($isOverdue)
                                                <span class="badge bg-danger">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Lewat Tenggat
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa-solid fa-circle-dot me-1"></i> Ditugaskan
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-muted small mb-2">
                                            <strong>Kelas Terkait:</strong> <span class="fw-semibold text-dark">{{ $t->class->class_name ?? 'Tidak terkait kelas' }}</span>
                                            @if ($t->material)
                                                · <strong>Materi:</strong> {{ $t->material->title }} (P-{{ $t->material->meeting_number }})
                                            @endif
                                            @if ($t->due_date)
                                                · <strong>Tenggat:</strong>
                                                <span class="{{ $isOverdue && !$isDone ? 'badge bg-danger text-white' : ($t->due_date->diffInDays(now()) <= 2 && !$isDone ? 'badge bg-warning text-dark' : 'fw-semibold') }}">
                                                    {{ $t->due_date->translatedFormat('d M Y') }}
                                                    @if ($isOverdue && !$isDone) (Terlewat) @endif
                                                </span>
                                            @endif
                                            · Dibuat: {{ $t->created_at->translatedFormat('d M Y') }}
                                            @if ($t->creator) oleh {{ $t->creator->name ?? $t->creator->username }} @endif
                                        </div>

                                        @if ($t->description)
                                            <div class="p-3 rounded-3 bg-light border mb-2 small">
                                                <strong>Instruksi & Poin Perbaikan:</strong>
                                                <div class="mt-1">{!! nl2br(e($t->description)) !!}</div>
                                            </div>
                                        @endif

                                        @if ($t->attachment_path)
                                            <div class="small mb-2">
                                                <i class="fa-solid fa-paperclip text-primary me-1"></i>
                                                <a href="{{ Storage::url($t->attachment_path) }}" target="_blank" class="fw-semibold text-decoration-none">
                                                    {{ $t->attachment_name ?? 'Unduh Lampiran Kurikulum' }}
                                                </a>
                                            </div>
                                        @endif

                                        @if ($sub && in_array($sub->status, ['Rejected', 'Perlu Revisi']) && $sub->review_note)
                                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                <strong>Catatan Revisi dari Kurikulum:</strong> {{ $sub->review_note }}
                                            </div>
                                        @endif

                                        @if ($sub)
                                            <div class="small text-muted mt-1">
                                                <i class="fa-solid fa-file-circle-check text-success me-1"></i>
                                                Pengajuan perbaikan terakhir dikirim {{ $sub->created_at->translatedFormat('d M Y H:i') }} (v{{ $sub->current_version ?: 1 }} - Status: <strong>{{ $sub->status }}</strong>)
                                            </div>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        @if ($effStatus === 'Selesai')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                                <i class="fa-solid fa-check me-1"></i> Tugas Tuntas
                                            </span>
                                        @elseif ($effStatus === 'Perlu Revisi' && $sub)
                                            <button type="button" class="btn btn-danger btn-sm rounded-pill fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#reuploadModal{{ $sub->id }}">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Upload Ulang Perbaikan
                                            </button>
                                        @elseif ($effStatus === 'Diajukan' && $sub)
                                            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#reuploadModal{{ $sub->id }}">
                                                <i class="fa-solid fa-upload me-1"></i> Update Berkas
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-sm rounded-pill fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#uploadTaskModal{{ $t->id }}">
                                                <i class="fa-solid fa-upload me-1"></i> Kerjakan / Upload Perbaikan
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL UPLOAD PERBAIKAN UNTUK TUGAS KELAS INI --}}
                            <div class="modal fade" id="uploadTaskModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <form class="modal-content rounded-4 border-0 shadow"
                                          action="{{ route('teacher.kelas.store') }}"
                                          method="POST"
                                          enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="task_id" value="{{ $t->id }}">
                                        @if ($t->class_id)
                                            <input type="hidden" name="class_id" value="{{ $t->class_id }}">
                                        @endif
                                        @if ($t->material_id)
                                            <input type="hidden" name="material_id" value="{{ $t->material_id }}">
                                        @endif

                                        <div class="modal-header bg-light">
                                            <div>
                                                <h5 class="modal-title fw-bold mb-0">Upload Perbaikan Kelas</h5>
                                                <small class="text-muted">{{ $t->title }} · {{ $t->class->class_name ?? 'Tidak terkait kelas' }}</small>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <div class="alert alert-info py-2 px-3 small mb-3">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                Silakan unggah Lesson Plan dan PPT perbaikan untuk kelas ini. Setelah dikirim, pengajuan akan otomatis terhubung ke tugas dan direview oleh Kurikulum.
                                            </div>

                                            @if (!$t->class_id)
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Pilih Kelas yang Diampu (Opsional)</label>
                                                    <select name="class_id" class="form-select">
                                                        <option value="">-- Pilih Kelas --</option>
                                                        @foreach ($myClasses as $c)
                                                            <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if (!$t->material_id)
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Pilih Materi Kurikulum (Opsional)</label>
                                                    <select name="material_id" class="form-select">
                                                        <option value="">-- Tidak terkait materi spesifik --</option>
                                                        @foreach ($materials as $m)
                                                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Upload Lesson Plan (PDF) <span class="text-danger">*</span></label>
                                                    <input type="file" name="lp_file" accept="application/pdf" class="form-control" required>
                                                    <small class="text-muted">Format PDF, maksimal 10 MB.</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Upload PPT <span class="text-danger">*</span></label>
                                                    <input type="file" name="ppt_file" accept=".pdf,.ppt,.pptx" class="form-control" required>
                                                    <small class="text-muted">Format PDF / PPT / PPTX, maksimal 20 MB.</small>
                                                </div>
                                            </div>

                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">Catatan Perbaikan (Opsional)</label>
                                                <textarea name="notes" rows="3" class="form-control" placeholder="Tuliskan catatan perbaikan atau penjelasan perubahan yang dilakukan..."></textarea>
                                            </div>
                                        </div>

                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                                                <i class="fa-solid fa-paper-plane me-1"></i> Kirim Perbaikan ke Kurikulum
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-clipboard-check fs-1 text-secondary mb-3 d-block"></i>
                                <h5>Belum ada tugas perbaikan kelas</h5>
                                <p class="mb-0">Semua kelas yang Anda ampu saat ini tidak memiliki tugas perbaikan dari Kurikulum.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div> {{-- End pane-tugas-kelas --}}

    </div> {{-- End tab-content kelasMainTabContent --}}

</div>

{{-- ==================== SCRIPT ==================== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // 1) Filter pilihan materi berdasarkan kategori
    const categorySelect = document.getElementById('categorySelect');
    const materialSelect = document.getElementById('materialSelect');

    function filterMaterials() {
        if (!categorySelect || !materialSelect) return;

        const categoryId = categorySelect.value;
        let firstVisible = null;

        Array.from(materialSelect.options).forEach(function (opt) {
            const match = opt.dataset.category === categoryId;
            opt.hidden = !match;
            opt.disabled = !match;
            if (match && !firstVisible) firstVisible = opt;
        });

        const current = materialSelect.options[materialSelect.selectedIndex];
        if (!current || current.disabled) {
            materialSelect.value = firstVisible ? firstVisible.value : '';
        }
    }

    // Jika ada old('material_id'), samakan kategori dengan materi tsb
    if (materialSelect && categorySelect) {
        const selected = materialSelect.options[materialSelect.selectedIndex];
        if (selected && selected.dataset.category) {
            categorySelect.value = selected.dataset.category;
        }
        categorySelect.addEventListener('change', filterMaterials);
        filterMaterials();
    }

    // 2) Filter daftar kelas berdasarkan kategori
    const filterWrap = document.getElementById('classFilter');
    const items = document.querySelectorAll('#classList .class-item');
    const emptyFilter = document.getElementById('classEmptyFilter');

    if (filterWrap) {
        filterWrap.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-filter]');
            if (!btn) return;

            filterWrap.querySelectorAll('button').forEach(function (b) {
                b.classList.remove('btn-dark');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-dark');

            const filter = btn.dataset.filter;
            let visible = 0;

            items.forEach(function (item) {
                const show = filter === 'all' || item.dataset.category === filter;
                item.classList.toggle('d-none', !show);
                if (show) visible++;
            });

            if (emptyFilter && items.length > 0) {
                emptyFilter.classList.toggle('d-none', visible > 0);
            }
        });
    }
});
</script>

@endsection