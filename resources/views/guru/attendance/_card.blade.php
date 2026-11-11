@php
    $schedule = $item['schedule'];
    $journal  = $item['journal'];
    $class    = $schedule->class;
    $students = $class->enrollments;        // sudah difilter status Active di controller
    $sid      = $schedule->id;
    $myId     = auth()->user()->teacher->id;

    $editing  = $item['status'] === 'revisi';
    $j        = $editing ? $journal : null;   // jurnal yang sedang direvisi (null = isi baru)

    // old() hanya dipakai untuk kartu yang barusan di-submit
    $old = fn ($k, $d = null) => (string) old('_sid') === (string) $sid ? old($k, $d) : $d;

    $defaultCourseType = $class->private_package_id ? 'Private' : ($class->programPackage?->package_name ?? $class->programPackage?->program?->program_name ?? 'Regular');
    $defaultCourseLevel = $class->level?->level_name ?? $class->programLevel?->level_name ?? '';

    $type           = $old('course_type', $j?->course_type ?? $defaultCourseType);
    $level          = $old('course_level', $j?->course_level ?? $defaultCourseLevel);
    $subValue       = $old('is_substitute', $j ? ($j->is_substitute ? 'Ya' : 'Tidak') : ($isSubstitute ? 'Ya' : 'Tidak'));
    $studentDefault = $old('student_names', $j?->student_names ?? $students->pluck('student.name')->filter()->implode(', '));
    $statusLabels   = config('journal.attendance_status', [
        'Present' => 'Hadir', 'Sick' => 'Sakit', 'Permission' => 'Izin', 'Absent' => 'Alpha',
    ]);

    $attStatus = $j ? $j->attendances->pluck('status', 'student_id') : collect();
    $attNote   = $j ? $j->attendances->pluck('note', 'student_id') : collect();

    $deadline = $item['deadline'] ?? null;
    $isPastDeadline = $item['is_past_deadline'] ?? false;
    $isUnlocked = $item['is_unlocked'] ?? false;
    $unlockRecord = $item['unlock_record'] ?? null;
    $autoMeetingNumber = $item['auto_meeting_number'] ?? 1;
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3" data-status="{{ $item['status'] }}">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0">{{ $class->class_name ?? '-' }}</h5>
                    <span class="badge bg-light text-dark border">{{ $type }} {{ $level ? '· ' . $level : '' }}</span>
                </div>
                <small class="text-muted d-block mt-1">
                    <i class="fa-regular fa-clock me-1"></i>
                    {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H.i') }}
                    - {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H.i') }}
                    @if ($isSubstitute) &middot; kelas {{ $teacherNames[$class->teacher_id] ?? 'guru lain' }} @endif
                </small>
            </div>

            <div>
                @if ($item['status'] === 'terlambat')
                    <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-lock me-1"></i> Terlambat / Terkunci</span>
                @elseif ($item['status'] === 'belum_diisi')
                    <span class="badge bg-warning text-dark px-3 py-2"><i class="fa-regular fa-clock me-1"></i> Perlu Diisi</span>
                @elseif ($item['status'] === 'revisi')
                    <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Perlu Revisi</span>
                @elseif ($item['status'] === 'sudah_diisi')
                    <span class="badge bg-info text-dark px-3 py-2"><i class="fa-solid fa-hourglass-half me-1"></i> Menunggu Review</span>
                @else
                    <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-check me-1"></i> Dikunci (Selesai)</span>
                @endif
            </div>
        </div>

        {{-- INFO BATAS WAKTU & HITUNG MUNDUR 24 JAM --}}
        @if ($item['status'] === 'belum_diisi')
            @if ($isUnlocked)
                <div class="alert alert-info py-2 d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-lock-open fs-5"></i>
                    <div class="small">
                        <strong>Kunci Pengisian Dibuka oleh Kurikulum</strong> hingga
                        <strong>{{ $item['unlocked_until']?->translatedFormat('d M Y, H:i') }}</strong>.
                        Jurnal ini akan ditandai terlambat saat disimpan.
                    </div>
                </div>
            @elseif (!$isPastDeadline && $deadline)
                <div class="alert alert-warning py-2 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="small">
                        <i class="fa-regular fa-hourglass-half text-danger me-1"></i>
                        Batas pengisian: <strong>24 jam</strong> setelah jam mengajar ({{ $deadline->translatedFormat('d M Y, H:i') }}).
                    </div>
                    <div class="small fw-bold text-danger">
                        Sisa waktu: <span class="countdown-timer" data-deadline="{{ $deadline->toIso8601String() }}">Menghitung...</span>
                    </div>
                </div>
            @endif
        @endif

        @if ($editing)
            <div class="alert alert-danger mb-3">
                <strong><i class="fa-solid fa-circle-exclamation me-1"></i> Jurnal dikembalikan oleh Kurikulum:</strong><br>
                {{ $journal->review_note ?: 'Silakan periksa dan perbaiki kembali isi jurnal mengajar.' }}
            </div>
        @endif

        {{-- FORM PENGISIAN JURNAL --}}
        @if (($item['status'] === 'belum_diisi' && $canFill) || $editing)

            <form action="{{ $editing ? route('teacher.attendance.update', $journal) : route('teacher.attendance.store', $schedule) }}"
                  method="POST" class="journal-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <input type="hidden" name="_sid" value="{{ $sid }}">
                <input type="hidden" name="session_date" value="{{ $editing ? $journal->session_date->format('Y-m-d') : $date->format('Y-m-d') }}">

                {{-- Nilai Otomatis: Course Type & Level (Dihapus dari manual radio box) --}}
                <input type="hidden" name="course_type" value="{{ $type }}">
                <input type="hidden" name="course_level" value="{{ $level }}">

                {{-- Info guru & murid --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nama Guru Pengajar</label>
                        <select name="teaching_teacher_id" class="form-select" required>
                            @foreach ($teacherNames as $tid => $tname)
                                <option value="{{ $tid }}" @selected((int) $old('teaching_teacher_id', $j?->teacher_id ?? $myId) === (int) $tid)>{{ $tname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Menggantikan Mengajar</label>
                        <div class="d-flex gap-3">
                            @foreach (['Ya', 'Tidak'] as $v)
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="is_substitute" value="{{ $v }}" required
                                           @checked($subValue === $v)> {{ $v }}
                                </label>
                            @endforeach
                        </div>
                        <select name="substitute_teacher_id" class="form-select mt-2" style="display:none">
                            <option value="">Menggantikan siapa?</option>
                            @foreach ($teacherNames as $tid => $tname)
                                <option value="{{ $tid }}" @selected((int) $old('substitute_teacher_id', $j?->substitute_teacher_id ?? $class->teacher_id) === (int) $tid)>{{ $tname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nama Murid</label>
                        <input type="text" name="student_names" class="form-control" required
                               placeholder="Nama murid di kelas" value="{{ $studentDefault }}">
                    </div>
                </div>

                {{-- Jadwal kelas (Terisi otomatis dari jadwal) --}}
                <h6 class="fw-bold border-top pt-3 mb-3"><i class="fa-solid fa-calendar-day text-warning me-1"></i> Data Sesi & Pertemuan (Otomatis dari Jadwal)</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Pertemuan Terakhir</label>
                        <div class="d-flex gap-3 mt-1">
                            @foreach (['Ya', 'Tidak'] as $v)
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="is_last_meeting" value="{{ $v }}" required
                                           @checked($old('is_last_meeting', $j ? ($j->is_last_meeting ? 'Ya' : 'Tidak') : 'Tidak') === $v)> {{ $v }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Pertemuan ke-</label>
                        <input type="number" min="1" name="meeting_number" class="form-control" required
                               value="{{ $old('meeting_number', $j?->meeting_number ?? $autoMeetingNumber) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="text" class="form-control bg-light"
                               value="{{ $editing ? $journal->session_date->format('d/m/Y') : $date->format('d/m/Y') }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jam Mulai</label>
                        <input type="time" name="start_time" class="form-control bg-light" required readonly
                               value="{{ $old('start_time', substr($j?->start_time ?? $schedule->start_time, 0, 5)) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jam Selesai</label>
                        <input type="time" name="end_time" class="form-control bg-light" required readonly
                               value="{{ $old('end_time', substr($j?->end_time ?? $schedule->end_time, 0, 5)) }}">
                    </div>
                </div>

                {{-- Keterangan berjalannya kelas --}}
                <h6 class="fw-bold border-top pt-3 mb-3"><i class="fa-solid fa-chalkboard-user text-warning me-1"></i> Pelaksanaan Kelas & Materi</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status Pelaksanaan Kelas</label>
                    <div class="d-flex gap-4">
                        @foreach (['Berjalan', 'Tidak Berjalan'] as $v)
                            <label class="d-flex align-items-center gap-2">
                                <input type="radio" name="class_running" value="{{ $v }}" required
                                       @checked($old('class_running', $j ? ($j->class_status === 'Conducted' ? 'Berjalan' : 'Tidak Berjalan') : 'Berjalan') === $v)> {{ $v }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Fokus Materi</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach (['Listening', 'Speaking', 'Reading', 'Writing', 'Grammar', 'Vocabulary'] as $f)
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="focus_materials[]" value="{{ $f }}"
                                       @checked(in_array($f, (array) $old('focus_materials', $j?->focus_materials ?? [])))> {{ $f }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Materi yang Diajarkan</label>
                    <input type="text" name="material_taught" class="form-control" data-run="req"
                           placeholder="Contoh: Bab 4 HSK 2 - Percakapan di Restoran" value="{{ $old('material_taught', $j?->material_taught) }}">
                </div>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Aktivitas Pembelajaran</label>
                    <textarea name="learning_activities" rows="2" class="form-control" data-run="req"
                              placeholder="Contoh: Flashcard, roleplay percakapan, tanya jawab">{{ $old('learning_activities', $j && $j->class_status === 'Conducted' ? $j->learning_activities : null) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Problem / Kendala <small class="text-muted">(Jika kelas tidak berjalan, wajib tulis alasannya)</small></label>
                    <textarea name="problems" rows="2" class="form-control" required
                              placeholder="Kendala siswa saat memahami materi atau alasan kelas batal...">{{ $old('problems', $j?->problems ?? '-') }}</textarea>
                </div>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Solusi</label>
                    <textarea name="solutions" rows="2" class="form-control" data-run="req"
                              placeholder="Solusi pengajaran yang diterapkan...">{{ $old('solutions', $j?->solutions) }}</textarea>
                </div>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Hasil Belajar</label>
                    <textarea name="results" rows="2" class="form-control" data-run="req"
                              placeholder="Capaian pemahaman siswa hari ini...">{{ $old('results', $j?->results) }}</textarea>
                </div>

                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Poin Sesi</label>
                    <div class="d-flex gap-4">
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="session_score" value="10" data-run="req"
                                   @checked((string) $old('session_score', $j?->session_score ?? '10') === '10')> 🟡 Kuning (10)
                        </label>
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="session_score" value="5"
                                   @checked((string) $old('session_score', $j?->session_score) === '5')> 🔴 Merah (5)
                        </label>
                    </div>
                </div>

                {{-- Absensi Siswa per Nama --}}
                <h6 class="fw-bold border-top pt-3 mb-2"><i class="fa-solid fa-user-check text-warning me-1"></i> Absensi Siswa (Wajib diisi per nama)</h6>
                <p class="text-muted small mb-3">Tentukan kehadiran tiap siswa. Bila tidak ada siswa yang hadir, jurnal tetap wajib diisi dengan absensi Alpha/Izin/Sakit (jumlah hadir 0).</p>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Nama Siswa</th>
                                <th style="width: 25%;">Status Kehadiran</th>
                                <th>Keterangan (Opsional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $enrollment)
                                @php $stId = $enrollment->student_id; @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $enrollment->student->name ?? '-' }}</td>
                                    <td>
                                        <select name="attendance[{{ $stId }}]" class="form-select form-select-sm">
                                            @foreach ($statusLabels as $val => $label)
                                                <option value="{{ $val }}" @selected($old("attendance.$stId", $attStatus[$stId] ?? 'Present') === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance_note[{{ $stId }}]" class="form-control form-control-sm"
                                               placeholder="Catatan..." value="{{ $old("attendance_note.$stId", $attNote[$stId] ?? null) }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Belum ada murid aktif yang terdaftar di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-warning fw-semibold px-4 rounded-pill"
                        onclick="return confirm('{{ $editing ? 'Kirim revisi jurnal ke Kurikulum untuk direview ulang?' : 'Kirim jurnal mengajar ini ke Kurikulum? Jurnal akan masuk antrean review.' }}')">
                    <i class="fas fa-paper-plane me-2"></i> {{ $editing ? 'Kirim Revisi Jurnal' : 'Kirim Jurnal Mengajar' }}
                </button>
            </form>

        @elseif ($item['status'] === 'terlambat')

            <div class="alert alert-danger p-4 rounded-4 mb-0">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle bg-danger-subtle text-danger p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px; height:50px;">
                        <i class="fa-solid fa-lock fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-danger">Form Terkunci — Terlambat Mengisi Jurnal</h6>
                        <p class="small text-muted mb-2">
                            Batas waktu pengisian jurnal (24 jam setelah sesi selesai pada {{ $deadline?->translatedFormat('d M Y, H:i') }}) telah lewat.
                            Sesuai ketentuan, form pengisian dikunci secara otomatis.
                        </p>

                        @if ($unlockRecord && $unlockRecord->status === 'Pending')
                            <div class="alert alert-warning py-2 px-3 small mb-0 rounded-3">
                                <i class="fa-solid fa-hourglass-half me-1"></i>
                                <strong>Permohonan buka kunci telah terkirim ke Kurikulum</strong> pada {{ $unlockRecord->requested_at?->translatedFormat('d M Y, H:i') }}.
                                <div class="mt-1 text-secondary">Alasan: "<em>{{ $unlockRecord->request_reason }}</em>"</div>
                                <span class="text-muted d-block mt-1">Silakan menunggu persetujuan Kurikulum.</span>
                            </div>
                        @elseif ($unlockRecord && $unlockRecord->status === 'Rejected')
                            <div class="alert alert-danger py-2 px-3 small mb-2 rounded-3">
                                <i class="fa-solid fa-circle-xmark me-1"></i>
                                <strong>Permohonan buka kunci ditolak Kurikulum:</strong> {{ $unlockRecord->rejection_note ?: 'Tidak memenuhi syarat pembukaan kembali.' }}
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#requestUnlockForm-{{ $sid }}">
                                <i class="fa-solid fa-paper-plane me-1"></i> Ajukan Permohonan Ulang
                            </button>
                        @else
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#requestUnlockForm-{{ $sid }}">
                                <i class="fa-solid fa-lock-open me-1"></i> Minta Buka Kunci ke Kurikulum
                            </button>
                        @endif

                        <div class="collapse mt-3" id="requestUnlockForm-{{ $sid }}">
                            <form action="{{ route('teacher.attendance.request-unlock') }}" method="POST" class="p-3 bg-white rounded-3 border">
                                @csrf
                                <input type="hidden" name="class_schedule_id" value="{{ $sid }}">
                                <input type="hidden" name="session_date" value="{{ $date->format('Y-m-d') }}">

                                <label class="form-label fw-bold small mb-1">Alasan Keterlambatan Pengisian Jurnal <span class="text-danger">*</span></label>
                                <textarea name="request_reason" class="form-control form-control-sm mb-2" rows="2" required
                                          placeholder="Contoh: Mengalami kendala jaringan internet saat kelas malam selesai, mohon izin untuk mengisi jurnal..."></textarea>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#requestUnlockForm-{{ $sid }}">Batal</button>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim Permohonan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        @elseif ($item['status'] === 'belum_diisi')

            <div class="text-muted small">Sesi ini belum bisa diisi karena tanggal sesi belum tiba.</div>

        @else

            {{-- Ringkasan setelah diisi --}}
            @php $notPresent = $journal->attendances->where('status', '!=', 'Present'); @endphp
            <dl class="row small mb-3">
                <dt class="col-sm-3">Guru Pengajar</dt>
                <dd class="col-sm-9">{{ $journal->teacher->name ?? '-' }}
                    @if ($journal->is_substitute)
                        &middot; menggantikan {{ $journal->substituteTeacher->name ?? '-' }}
                    @endif
                    @if ($journal->is_late)
                        <span class="badge bg-danger-subtle text-danger ms-2">Terlambat</span>
                    @endif
                </dd>

                <dt class="col-sm-3">Nama Murid</dt>
                <dd class="col-sm-9">{{ $journal->student_names ?: '-' }}</dd>

                <dt class="col-sm-3">Pertemuan</dt>
                <dd class="col-sm-9">ke-{{ $journal->meeting_number ?? '-' }}{{ $journal->is_last_meeting ? ' (terakhir)' : '' }}
                    &middot; {{ $journal->course_type }} {{ $journal->course_level }}</dd>

                <dt class="col-sm-3">Status Kelas</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $journal->class_status === 'Conducted' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $journal->class_status === 'Conducted' ? 'Berjalan' : 'Tidak Berjalan' }}
                    </span>
                </dd>

                <dt class="col-sm-3">Fokus Materi</dt>
                <dd class="col-sm-9">{{ collect($journal->focus_materials)->implode(', ') ?: '-' }}</dd>

                <dt class="col-sm-3">Materi</dt><dd class="col-sm-9">{{ $journal->material_taught ?: '-' }}</dd>
                <dt class="col-sm-3">Aktivitas</dt><dd class="col-sm-9">{{ $journal->learning_activities ?: '-' }}</dd>
                <dt class="col-sm-3">Kendala</dt><dd class="col-sm-9">{{ $journal->problems ?: '-' }}</dd>
                <dt class="col-sm-3">Solusi</dt><dd class="col-sm-9">{{ $journal->solutions ?: '-' }}</dd>
                <dt class="col-sm-3">Hasil</dt><dd class="col-sm-9">{{ $journal->results ?: '-' }}</dd>
                <dt class="col-sm-3">Poin Sesi</dt><dd class="col-sm-9">{{ $journal->session_score ?? '-' }}</dd>
                <dt class="col-sm-3">Kehadiran</dt>
                <dd class="col-sm-9">
                    <span class="fw-bold text-success me-2">Hadir: {{ $journal->attendances->where('status', 'Present')->count() }} siswa</span>
                    @if ($notPresent->isNotEmpty())
                        @foreach ($notPresent as $att)
                            <span class="badge bg-danger-subtle text-danger me-1">{{ $att->student->name ?? '-' }} ({{ $statusLabels[$att->status] ?? $att->status }})</span>
                        @endforeach
                    @endif
                </dd>
            </dl>

            <a href="{{ route('teacher.attendance.pdf', $journal) }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-file-pdf me-1"></i> Unduh PDF Jurnal
            </a>

        @endif

    </div>
</div>
