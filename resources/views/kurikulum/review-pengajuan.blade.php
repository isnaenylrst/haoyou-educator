@extends('layouts.kurikulum')

@section('title','Review Pengajuan')

@push('styles')
<style>
.status-pill{border:none;border-radius:20px;padding:6px 14px;cursor:pointer;font-size:13px;font-weight:600;background:#f1f1f1;color:#555;}
.status-pill.active{background:#111;color:#fff;}
.doc-pill{border:none;border-radius:20px;padding:6px 16px;cursor:pointer;font-size:13px;font-weight:600;background:#f1f1f1;color:#555;}
.doc-pill.active{background:#111;color:#fff;}
.session-card{border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px;}
.badge-soft-warning{background:#FFF3CD;color:#8A6D00;}
.badge-soft-danger{background:#FDE2E2;color:#B12727;}
.chip-present{background:#E5F6EA;color:#1E7B3C;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;}
.chip-absent{background:#FDE2E2;color:#B12727;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;}
.note-box{background:#FBF7EE;border-radius:10px;padding:10px 14px;font-size:13.5px;color:#555;margin:10px 0;}
.review-actions form{margin:0;}
</style>
@endpush

@section('content')

@php
    $attLabels = config('journal.attendance_status', []);

    // Tautan WhatsApp (wa.me) untuk mengingatkan guru yang belum mengisi jurnal
    $waReminder = function ($schedule) {
        $teacher = $schedule->class->teacher ?? null;
        $phone   = preg_replace('/\D+/', '', $teacher->phone ?? '');
        if (!$teacher || $phone === '') {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        $time = \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i');
        $text = "Halo {$teacher->name}, mohon segera mengisi jurnal & absensi kelas "
              . ($schedule->class->class_name ?? '-') . " (jam {$time}) hari ini. Terima kasih.";

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    };

    $docTabs = [
        'material'   => ['LP & PPT', $totals['material']],
        'jurnal'     => ['Jurnal Online', $totals['jurnal']],
        'report'     => ['Progress Report', $totals['report']],
        'cuti'       => ['Cuti / Pengajuan Kelas', $totals['cuti']],
        'sertifikat' => ['Sertifikat', $totals['sertifikat']],
        'pengajuan'  => ['Reimburse & Bahan', $totals['pengajuan']],
    ];
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Review Pengajuan</h3>
        <small class="text-muted">
            Card sesi jurnal & absensi dari semua guru, dan tab dokumen LP&PPT/Jurnal/Progress/Cuti/Sertifikat untuk direview satu per satu.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= STAT CARDS ================= --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <small class="text-muted text-uppercase">Sesi Hari Ini (Semua Guru)</small>
                <h3 class="fw-bold mb-0">{{ $totalSessionToday }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <small class="text-muted text-uppercase">Sudah Diisi</small>
                <h3 class="fw-bold mb-0 text-success">{{ $filledToday }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <small class="text-muted text-uppercase">Belum Diisi</small>
                <h3 class="fw-bold mb-0 text-danger">{{ $unfilledToday }}</h3>
            </div>
        </div>
    </div>

    {{-- ================= CARD SESI - JURNAL & ABSENSI ================= --}}
    <h5 class="fw-bold mb-3">Card Sesi — Jurnal & Absensi</h5>

    <div class="mb-3 d-flex flex-wrap gap-2" id="teacherFilterTabs">
        <button type="button" class="status-pill active" data-teacher="all">Semua Guru</button>
        @foreach ($teacherNamesToday as $name)
            <button type="button" class="status-pill" data-teacher="{{ $name }}">{{ $name }}</button>
        @endforeach
    </div>

    <div id="sessionCardsWrapper">
        @forelse ($todaySchedules as $schedule)
            @php
                $journal     = $schedule->teachingJournals->first();
                $teacherName = $schedule->class->teacher->name ?? '-';
                $isLate      = !$journal && \Illuminate\Support\Carbon::parse($schedule->end_time)->addHours(2)->lt(now());
                $wa          = !$journal ? $waReminder($schedule) : null;
            @endphp

            <div class="session-card" data-teacher="{{ $teacherName }}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="fw-bold">
                            {{ $schedule->class->class_name ?? '-' }} —
                            {{ $journal->material->title ?? ($schedule->class->programPackage->program->name ?? 'Sesi') }}
                        </div>
                        <small class="text-muted">
                            Guru: {{ $teacherName }} &nbsp;
                            {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }} &nbsp;
                            @if ($schedule->room) {{ $schedule->room }} &nbsp; @endif
                            {{ $schedule->class->delivery_mode ?? '' }}
                        </small>
                    </div>

                    <div>
                        @if (!$journal)
                            <span class="badge badge-soft-danger">Belum Diisi</span>
                        @elseif ($journal->status === 'Pending')
                            <span class="badge badge-soft-warning">Sudah Diisi • Belum ACC</span>
                        @elseif ($journal->status === 'Reviewed')
                            <span class="badge bg-success">Sudah ACC</span>
                        @else
                            <span class="badge badge-soft-danger">Perlu Revisi</span>
                        @endif
                    </div>
                </div>

                @if ($journal)
                    @if ($journal->attendances->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($journal->attendances as $att)
                                @if ($att->status === 'Present')
                                    <span class="chip-present">✓ {{ $att->student->name ?? '-' }}</span>
                                @else
                                    <span class="chip-absent">✗ {{ $att->student->name ?? '-' }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="note-box">
                        @if ($journal->class_status === 'Cancelled')
                            Kelas <strong>tidak berjalan</strong> • Alasan: {{ $journal->problems ?: '-' }}
                        @else
                            Poin sesi: <strong>{{ $journal->session_score ?? '-' }}</strong>
                            • Materi: {{ $journal->material_taught ?: ($journal->material->title ?? '-') }}
                            • Aktivitas: {{ $journal->learning_activities ?: '-' }}
                        @endif
                    </div>

                    @if ($journal->status === 'Revision' && $journal->review_note)
                        <div class="small text-danger mb-2">Dikembalikan ke guru: {{ $journal->review_note }}</div>
                    @endif

                    @if ($journal->status === 'Pending')
                        <form action="{{ route('kurikulum.review-pengajuan.ack-session', $journal) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="btn btn-success btn-sm">Tandai ACC</button>
                        </form>
                        <small class="text-muted d-block mt-1">
                            Untuk mengembalikan dengan alasan, buka tab <strong>Jurnal Online</strong> di bawah.
                        </small>
                    @endif
                @else
                    @if ($isLate)
                        <div class="text-danger fw-semibold mt-2">
                            Sesi sudah lewat 2 jam, guru belum mengisi jurnal & absensi.
                        </div>
                    @endif

                    @if ($wa)
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm mt-2">
                            <i class="fa-brands fa-whatsapp"></i> Kirim Reminder via WhatsApp
                        </a>
                    @else
                        <small class="text-muted d-block mt-2">Nomor HP guru belum terisi, reminder WhatsApp tidak tersedia.</small>
                    @endif
                @endif
            </div>
        @empty
            <div class="text-center text-muted py-4">Tidak ada jadwal kelas hari ini.</div>
        @endforelse
    </div>

    {{-- ================= REVIEW DOKUMEN PER KATEGORI ================= --}}
    <h5 class="fw-bold mt-5 mb-3">
        Review Dokumen per Kategori
        @if ($totalPending > 0)
            <span class="badge bg-warning text-dark ms-1">{{ $totalPending }} menunggu</span>
        @endif
    </h5>

    <div class="mb-3 d-flex flex-wrap gap-2" id="docTabs">
        @foreach ($docTabs as $key => [$label, $count])
            <button type="button" class="doc-pill {{ $tab === $key ? 'active' : '' }}" data-tab="{{ $key }}">
                {{ $label }} ({{ $count }}
                @if ($key === 'jurnal' && !empty($lateJournals) && $lateJournals->isNotEmpty())
                    , {{ $lateJournals->count() }} terlambat
                @elseif ($key === 'report' && $lateEnrollments->isNotEmpty())
                    , {{ $lateEnrollments->count() }} terlambat
                @endif
                )
            </button>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">

            {{-- ===== TAB LP & PPT ===== --}}
            <div class="doc-tab {{ $tab === 'material' ? '' : 'd-none' }}" data-tab="material">
                @forelse ($materials as $item)
                    @php
                        $kons = fn (string $type) => $item->consultations->where('type', $type)->sortByDesc('id')->first();
                    @endphp
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $item->teacher->name ?? '-' }} — {{ $item->ppt_title }}
                                    ({{ $item->material->level->level_name ?? '-' }})
                                </div>
                                <small class="text-muted d-block">
                                    Pertemuan {{ $item->material->meeting_number ?? '-' }} · Diajukan {{ $item->created_at->diffForHumans() }}
                                </small>
                                <small class="d-block mt-1">
                                    Konsultasi Kurikulum: <strong>{{ $kons('LP_PPT')->status ?? 'belum diajukan' }}</strong>
                                    · Konsultasi Direktur: <strong>{{ $kons('Direktur')->status ?? 'belum diajukan' }}</strong>
                                </small>
                            </div>

                            <div class="d-flex gap-2 flex-wrap review-actions">
                                @if ($item->lp_file_path)
                                    <a href="{{ route('kurikulum.review-pengajuan.file', ['lp', $item->id]) }}" target="_blank"
                                       class="btn btn-outline-secondary btn-sm">Lihat LP</a>
                                @endif
                                @if ($item->ppt_file_path)
                                    <a href="{{ route('kurikulum.review-pengajuan.file', ['ppt', $item->id]) }}" target="_blank"
                                       class="btn btn-outline-secondary btn-sm">Lihat PPT</a>
                                @endif

                                <form action="{{ route('kurikulum.review-pengajuan.material', $item) }}" method="POST"
                                      onsubmit="return confirm('ACC LP & PPT ini?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Approved">
                                    <button class="btn btn-primary btn-sm">ACC</button>
                                </form>

                                <form action="{{ route('kurikulum.review-pengajuan.material', $item) }}" method="POST" class="d-flex gap-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Rejected">
                                    <input type="text" name="note" maxlength="1000" required
                                           class="form-control form-control-sm" placeholder="Alasan (wajib)" style="min-width:170px">
                                    <button class="btn btn-outline-danger btn-sm">Kembalikan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada LP & PPT yang perlu direview.</div>
                @endforelse

                @if ($materials->hasPages())
                    <div class="list-group-item">{{ $materials->links() }}</div>
                @endif
            </div>

            {{-- ===== TAB JURNAL ONLINE ===== --}}
            <div class="doc-tab {{ $tab === 'jurnal' ? '' : 'd-none' }}" data-tab="jurnal">
                @forelse ($journals as $item)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $item->teacher->name ?? '-' }} — Jurnal kelas {{ $item->class->class_name ?? '-' }}
                                </div>
                                <small class="text-muted">
                                    Sesi {{ $item->session_date?->translatedFormat('d M Y') }}
                                    · Pertemuan ke-{{ $item->meeting_number ?? '-' }}{{ $item->is_last_meeting ? ' (terakhir)' : '' }}
                                    · {{ $item->class_status === 'Conducted' ? 'Berjalan' : 'Tidak berjalan' }}
                                    · Diajukan {{ $item->created_at->diffForHumans() }}
                                </small>
                            </div>

                            <div class="d-flex gap-2 flex-wrap review-actions">
                                <form action="{{ route('kurikulum.review-pengajuan.journal', $item) }}" method="POST"
                                      onsubmit="return confirm('ACC jurnal ini?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Reviewed">
                                    <button class="btn btn-primary btn-sm">ACC</button>
                                </form>

                                <form action="{{ route('kurikulum.review-pengajuan.journal', $item) }}" method="POST" class="d-flex gap-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Revision">
                                    <input type="text" name="note" maxlength="1000" required
                                           class="form-control form-control-sm" placeholder="Alasan (wajib)" style="min-width:170px">
                                    <button class="btn btn-outline-danger btn-sm">Kembalikan</button>
                                </form>
                            </div>
                        </div>

                        {{-- ISI JURNAL --}}
                        <details class="mt-2">
                            <summary class="small text-primary" style="cursor:pointer">Lihat isi jurnal & absensi</summary>

                            <div class="note-box">
                                <div><strong>Jam:</strong> {{ $item->start_time ? substr($item->start_time, 0, 5) : '-' }}–{{ $item->end_time ? substr($item->end_time, 0, 5) : '-' }}
                                    · <strong>Kursus:</strong> {{ $item->course_type ?? '-' }} {{ $item->course_level }}</div>
                                @if ($item->is_substitute)
                                    <div><strong>Menggantikan:</strong> {{ $item->substituteTeacher->name ?? '-' }}</div>
                                @endif
                                <div><strong>Nama murid:</strong> {{ $item->student_names ?: '-' }}</div>
                                <div><strong>Fokus materi:</strong> {{ collect($item->focus_materials)->implode(', ') ?: '-' }}</div>
                                <div><strong>Materi:</strong> {{ $item->material_taught ?: '-' }}</div>
                                <div><strong>Aktivitas:</strong> {{ $item->learning_activities ?: '-' }}</div>
                                <div><strong>Problem:</strong> {{ $item->problems ?: '-' }}</div>
                                <div><strong>Solusi:</strong> {{ $item->solutions ?: '-' }}</div>
                                <div><strong>Hasil:</strong> {{ $item->results ?: '-' }}</div>
                                <div><strong>Point:</strong> {{ $item->session_score ?? '-' }}</div>
                            </div>

                            @if ($item->attendances->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($item->attendances as $att)
                                        <span class="{{ $att->status === 'Present' ? 'chip-present' : 'chip-absent' }}">
                                            {{ $att->status === 'Present' ? '✓' : '✗' }}
                                            {{ $att->student->name ?? '-' }}
                                            @if ($att->status !== 'Present') ({{ $attLabels[$att->status] ?? $att->status }}) @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </details>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada jurnal yang perlu direview.</div>
                @endforelse

                @if ($journals->hasPages())
                    <div class="list-group-item">{{ $journals->links() }}</div>
                @endif

                {{-- Sesi Terlambat / Terkunci & Permohonan Buka Kunci dari Guru --}}
                @if (!empty($lateJournals) && $lateJournals->isNotEmpty())
                    <div class="list-group-item bg-light fw-bold small text-danger d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-lock me-1"></i> Jurnal Terlambat / Terkunci (> 24 Jam) ({{ $lateJournals->count() }})</span>
                        <span class="text-muted fw-normal">Kurikulum dapat menyetujui pembukaan kunci selama 24 jam</span>
                    </div>

                    @foreach ($lateJournals as $late)
                        @php
                            $uRec = $late->unlock_record;
                            $hasPendingRequest = $late->has_request;
                            $isUnlocked = $late->is_unlocked;
                        @endphp
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $late->teacher->name ?? '-' }} — Kelas {{ $late->class->class_name ?? '-' }}
                                        @if ($late->is_substitute)
                                            <span class="badge bg-secondary ms-1">Guru Pengganti</span>
                                        @endif
                                        @if ($hasPendingRequest)
                                            <span class="badge bg-warning text-dark ms-1"><i class="fa-solid fa-bell me-1"></i> Mengajukan Buka Kunci</span>
                                        @elseif ($isUnlocked)
                                            <span class="badge bg-success ms-1"><i class="fa-solid fa-lock-open me-1"></i> Terbuka hingga {{ $uRec->unlocked_until?->translatedFormat('d M, H:i') }}</span>
                                        @else
                                            <span class="badge bg-danger ms-1"><i class="fa-solid fa-lock me-1"></i> Terkunci</span>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block">
                                        Sesi: <strong>{{ $late->session_date->translatedFormat('d M Y') }}</strong> ({{ \Illuminate\Support\Carbon::parse($late->schedule->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($late->schedule->end_time)->format('H:i') }})
                                        &middot; Terlambat: <span class="text-danger fw-bold">{{ $late->days_late }} hari</span>
                                        &middot; Batas 24 jam lewat pada {{ $late->deadline->translatedFormat('d M Y, H:i') }}
                                    </small>

                                    @if ($uRec && $uRec->request_reason)
                                        <div class="note-box mt-2 py-2 px-3 border border-warning bg-warning-subtle text-dark">
                                            <strong>Alasan Keterlambatan Guru:</strong>
                                            <em>"{{ $uRec->request_reason }}"</em>
                                            <small class="text-muted d-block mt-1">Diajukan: {{ $uRec->requested_at?->diffForHumans() }}</small>
                                        </div>
                                    @endif

                                    @if ($uRec && $uRec->status === 'Rejected')
                                        <div class="small text-danger mt-1">
                                            <i class="fa-solid fa-circle-xmark me-1"></i> Pernah ditolak: {{ $uRec->rejection_note }}
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex gap-2 flex-wrap review-actions">
                                    {{-- FORM BUKA KUNCI --}}
                                    <form action="{{ route('kurikulum.review-pengajuan.unlock-journal') }}" method="POST"
                                          onsubmit="return confirm('Buka kunci pengisian jurnal kelas {{ $late->class->class_name }} sesi {{ $late->session_date->translatedFormat('d M Y') }} untuk 24 jam ke depan?')">
                                        @csrf
                                        <input type="hidden" name="class_schedule_id" value="{{ $late->schedule->id }}">
                                        <input type="hidden" name="session_date" value="{{ $late->session_date->format('Y-m-d') }}">
                                        <button class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-lock-open me-1"></i> Buka Kunci (+24 Jam)
                                        </button>
                                    </form>

                                    {{-- FORM TOLAK PERMOHONAN JIKA ADA REQUEST --}}
                                    @if ($hasPendingRequest)
                                        <form action="{{ route('kurikulum.review-pengajuan.reject-unlock-journal') }}" method="POST" class="d-flex gap-1"
                                              onsubmit="return confirm('Tolak permohonan buka kunci ini?')">
                                            @csrf
                                            <input type="hidden" name="class_schedule_id" value="{{ $late->schedule->id }}">
                                            <input type="hidden" name="session_date" value="{{ $late->session_date->format('Y-m-d') }}">
                                            <input type="text" name="rejection_note" maxlength="1000" required
                                                   class="form-control form-control-sm" placeholder="Alasan tolak (wajib)" style="min-width:170px">
                                            <button class="btn btn-outline-danger btn-sm">Tolak</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- ===== TAB PROGRESS REPORT ===== --}}
            <div class="doc-tab {{ $tab === 'report' ? '' : 'd-none' }}" data-tab="report">
                @forelse ($reports as $item)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $item->teacher->name ?? '-' }} — Progress Report {{ $item->student->name ?? '-' }}
                                </div>
                                <small class="text-muted">
                                    {{ $item->report_period }}
                                    @if ($item->submitted_at) · Dikirim {{ $item->submitted_at->diffForHumans() }} @endif
                                </small>

                                @if (!empty($item->content))
                                    <details class="mt-2">
                                        <summary class="small text-primary" style="cursor:pointer">Lihat isi report</summary>
                                        <table class="table table-sm table-bordered mt-2 mb-0 small">
                                            <thead class="table-light">
                                                <tr><th>Pertemuan</th><th>Learning Materials</th><th>Level</th><th>Description</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($item->content as $row)
                                                    <tr>
                                                        <td>{{ $row['meeting'] ?? '-' }}</td>
                                                        <td>{{ \Illuminate\Support\Str::limit(\App\Support\RichText::plain($row['material'] ?? ($row['competency'] ?? ($row['materials'] ?? '-'))), 160) }}</td>
                                                        <td>{{ $row['level'] ?? '-' }}</td>
                                                        <td>{{ \Illuminate\Support\Str::limit(\App\Support\RichText::plain($row['description'] ?? '-'), 160) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </details>
                                @endif
                            </div>

                            <div class="d-flex gap-2 flex-wrap review-actions">
                                <a href="{{ route('progress-report.document', $item) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Lihat Dokumen</a>
                                <a href="{{ route('progress-report.document.pdf', $item) }}" target="_blank" class="btn btn-outline-secondary btn-sm">PDF</a>
                                <a href="{{ route('progress-report.document.edit', $item) }}" class="btn btn-outline-primary btn-sm">Edit Teks</a>

                                <form action="{{ route('kurikulum.review-pengajuan.report', $item) }}" method="POST"
                                      onsubmit="return confirm('ACC progress report ini? Setelah ACC langsung tampil di siswa.')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Approved">
                                    <button class="btn btn-primary btn-sm">ACC</button>
                                </form>

                                <form action="{{ route('kurikulum.review-pengajuan.report', $item) }}" method="POST" class="d-flex gap-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Rejected">
                                    <input type="text" name="note" maxlength="1000" required
                                           class="form-control form-control-sm" placeholder="Alasan (wajib)" style="min-width:170px">
                                    <button class="btn btn-outline-danger btn-sm">Kembalikan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada progress report yang perlu direview.</div>
                @endforelse

                @if ($reports->hasPages())
                    <div class="list-group-item">{{ $reports->links() }}</div>
                @endif

                {{-- Lewat batas H+3 dan belum dikirim --}}
                @if ($lateEnrollments->isNotEmpty())
                    <div class="list-group-item bg-light fw-bold small text-danger">
                        Lewat batas H+3 &amp; belum dikirim guru ({{ $lateEnrollments->count() }})
                    </div>

                    @foreach ($lateEnrollments as $late)
                        <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $late['enrollment']->class->teacher->name ?? '-' }} —
                                    {{ $late['enrollment']->student->name ?? '-' }}
                                </div>
                                <small class="text-danger">
                                    {{ $late['enrollment']->class->class_name ?? '-' }} · batas
                                    {{ $late['deadline']->translatedFormat('d M Y') }} sudah lewat
                                </small>
                            </div>
                            <form action="{{ route('kurikulum.review-pengajuan.reopen-report', $late['enrollment']) }}" method="POST">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm">Buka Kembali (+3 hari)</button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- ===== TAB CUTI ===== --}}
            <div class="doc-tab {{ $tab === 'cuti' ? '' : 'd-none' }}" data-tab="cuti">
                @forelse ($leaves as $item)
                    @php
                        $waitingReplacement = $item->replacement_teacher_id && $item->replacement_response !== 'Accepted';
                    @endphp
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $item->teacher->name ?? '-' }} — {{ $item->request_type ?? 'Cuti' }}
                                    ({{ $item->classSchedule->class->class_name ?? '-' }})
                                </div>
                                <small class="text-muted d-block">
                                    {{ $item->leave_date ? $item->leave_date->translatedFormat('d M Y') : '-' }}
                                    @if ($item->leave_type) · {{ $item->leave_type }} @endif
                                    · Diajukan {{ $item->created_at->diffForHumans() }}
                                </small>
                                <div class="small mt-1">Alasan: {{ $item->reason ?: '-' }}</div>

                                @if ($item->replacement_teacher_id)
                                    <small class="d-block mt-1">
                                        Pengganti: <strong>{{ $item->replacementTeacher->name ?? '-' }}</strong>
                                        @if ($item->replacement_response === 'Accepted')
                                            <span class="badge bg-success">sudah menerima</span>
                                        @elseif ($item->replacement_response === 'Rejected')
                                            <span class="badge bg-danger">menolak</span>
                                        @else
                                            <span class="badge bg-warning text-dark">menunggu jawaban pengganti</span>
                                        @endif
                                    </small>
                                @endif
                            </div>

                            <div class="d-flex gap-2 flex-wrap review-actions">
                                @if ($item->supporting_document)
                                    <a href="{{ route('kurikulum.review-pengajuan.file', ['cuti', $item->id]) }}" target="_blank"
                                       class="btn btn-outline-secondary btn-sm">Lihat Dokumen</a>
                                @endif

                                <form action="{{ route('kurikulum.review-pengajuan.leave', $item) }}" method="POST"
                                      onsubmit="return confirm('Setujui pengajuan ini?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Approved">
                                    <button class="btn btn-primary btn-sm" @disabled($waitingReplacement)
                                            title="{{ $waitingReplacement ? 'Menunggu guru pengganti menerima' : '' }}">Setujui</button>
                                </form>

                                <form action="{{ route('kurikulum.review-pengajuan.leave', $item) }}" method="POST" class="d-flex gap-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decision" value="Rejected">
                                    <input type="text" name="note" maxlength="1000" required
                                           class="form-control form-control-sm" placeholder="Alasan (wajib)" style="min-width:170px">
                                    <button class="btn btn-outline-danger btn-sm">Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">Tidak ada pengajuan cuti/ganti kelas.</div>
                @endforelse

                @if ($leaves->hasPages())
                    <div class="list-group-item">{{ $leaves->links() }}</div>
                @endif
            </div>

            {{-- ===== TAB SERTIFIKAT ===== --}}
            <div class="doc-tab {{ $tab === 'sertifikat' ? '' : 'd-none' }}" data-tab="sertifikat">
                @include('kurikulum.partials.review-sertifikat')
            </div>

            {{-- ===== TAB PENGAJUAN (REIMBURSE & PEMBELIAN) ===== --}}
            <div class="doc-tab {{ $tab === 'pengajuan' ? '' : 'd-none' }}" data-tab="pengajuan">
                @include('kurikulum.partials.review-submission')
            </div>

        </div>
    </div>

    <small class="text-muted d-block mt-3">
        Tombol "Kembalikan" wajib disertai alasan. LP & PPT yang dikembalikan memunculkan tombol "Upload Ulang" di menu Kelas guru,
        jurnal yang dikembalikan bisa diperbaiki guru di menu Attendance & Journal, dan progress report yang dikembalikan memberi guru waktu 3 hari.
        Progress report dan sertifikat baru tampil di siswa setelah di-ACC.
    </small>
</div>

@endsection

@push('scripts')
<script>
// Filter card sesi per guru
document.querySelectorAll('#teacherFilterTabs .status-pill').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#teacherFilterTabs .status-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const name = btn.dataset.teacher;
        document.querySelectorAll('#sessionCardsWrapper .session-card').forEach(function (card) {
            card.style.display = (name === 'all' || card.dataset.teacher === name) ? '' : 'none';
        });
    });
});

// Pindah tab dokumen (alamat ikut berubah agar bisa dibagikan / direfresh)
function showDocTab(tab) {
    document.querySelectorAll('#docTabs .doc-pill').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.doc-tab').forEach(el => el.classList.toggle('d-none', el.dataset.tab !== tab));

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}

document.querySelectorAll('#docTabs .doc-pill').forEach(function (btn) {
    btn.addEventListener('click', function () { showDocTab(btn.dataset.tab); });
});
</script>
@endpush