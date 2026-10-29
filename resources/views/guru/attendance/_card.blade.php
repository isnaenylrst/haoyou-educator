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

    $type           = $old('course_type', $j?->course_type ?? ($class->private_package_id ? 'Private' : null));
    $subValue       = $old('is_substitute', $j ? ($j->is_substitute ? 'Ya' : 'Tidak') : ($isSubstitute ? 'Ya' : 'Tidak'));
    $studentDefault = $old('student_names', $j?->student_names ?? $students->pluck('student.name')->filter()->implode(', '));
    $statusLabels   = config('journal.attendance_status');

    $attStatus = $j ? $j->attendances->pluck('status', 'student_id') : collect();
    $attNote   = $j ? $j->attendances->pluck('note', 'student_id') : collect();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3" data-status="{{ $item['status'] }}">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="fw-bold mb-1">{{ $class->class_name ?? '-' }}</h5>
                <small class="text-muted">
                    {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H.i') }}
                    - {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H.i') }}
                    @if ($isSubstitute) &middot; kelas {{ $teacherNames[$class->teacher_id] ?? 'guru lain' }} @endif
                </small>
            </div>

            @if ($item['status'] === 'belum_diisi')
                <span class="badge bg-warning text-dark px-3 py-2">Perlu Diisi</span>
            @elseif ($item['status'] === 'revisi')
                <span class="badge bg-danger px-3 py-2">Perlu Revisi</span>
            @elseif ($item['status'] === 'sudah_diisi')
                <span class="badge bg-secondary px-3 py-2">Menunggu Review</span>
            @else
                <span class="badge bg-success px-3 py-2">Dikunci</span>
            @endif
        </div>

        @if ($editing)
            <div class="alert alert-warning">
                <strong>Jurnal dikembalikan oleh Kurikulum.</strong><br>
                Catatan: {{ $journal->review_note ?: 'Silakan periksa kembali isi jurnal.' }}
            </div>
        @endif

        @if (($item['status'] === 'belum_diisi' && $canFill) || $editing)

            <form action="{{ $editing ? route('teacher.attendance.update', $journal) : route('teacher.attendance.store', $schedule) }}"
                  method="POST" class="journal-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <input type="hidden" name="_sid" value="{{ $sid }}">
                <input type="hidden" name="session_date" value="{{ $editing ? $journal->session_date->format('Y-m-d') : $date->format('Y-m-d') }}">

                {{-- Info guru & murid --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nama Guru pengajar</label>
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
                               placeholder="Ketik nama murid" value="{{ $studentDefault }}">
                    </div>
                </div>

                {{-- Jadwal kelas --}}
                <h6 class="fw-bold border-top pt-3">Jadwal Kelas</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Pertemuan terakhir</label>
                        <div class="d-flex gap-3">
                            @foreach (['Ya', 'Tidak'] as $v)
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="is_last_meeting" value="{{ $v }}" required
                                           @checked($old('is_last_meeting', $j ? ($j->is_last_meeting ? 'Ya' : 'Tidak') : 'Tidak') === $v)> {{ $v }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Pertemuan ke</label>
                        <input type="number" min="1" name="meeting_number" class="form-control" required
                               value="{{ $old('meeting_number', $j?->meeting_number) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="text" class="form-control"
                               value="{{ $editing ? $journal->session_date->format('d/m/Y') : $date->format('d/m/Y') }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jam mulai</label>
                        <input type="time" name="start_time" class="form-control" required
                               value="{{ $old('start_time', substr($j?->start_time ?? $schedule->start_time, 0, 5)) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jam selesai</label>
                        <input type="time" name="end_time" class="form-control" required
                               value="{{ $old('end_time', substr($j?->end_time ?? $schedule->end_time, 0, 5)) }}">
                    </div>
                </div>

                {{-- Kelas kursus (level menyesuaikan lewat JS) --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas Kursus Mandarin Haoyou</label>
                    <div class="d-flex flex-wrap gap-4">
                        @foreach (array_keys(config('journal.courses')) as $c)
                            <label class="d-flex align-items-center gap-2">
                                <input type="radio" name="course_type" value="{{ $c }}" required
                                       @checked($type === $c)> {{ $c }}
                            </label>
                        @endforeach
                    </div>
                    <div class="level-wrap mt-2" style="display:none">
                        <select name="course_level" class="form-select" style="max-width:280px"
                                data-selected="{{ $old('course_level', $j?->course_level) }}"></select>
                    </div>
                </div>

                {{-- Keterangan berjalannya kelas --}}
                <h6 class="fw-bold border-top pt-3">Keterangan Berjalannya Kelas</h6>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Fokus Materi</label>
                    <div class="d-flex flex-wrap gap-4">
                        @foreach (config('journal.focus') as $f)
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="focus_materials[]" value="{{ $f }}"
                                       @checked(in_array($f, (array) $old('focus_materials', $j?->focus_materials ?? [])))> {{ $f }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas berjalan</label>
                    <div class="d-flex gap-4">
                        @foreach (['Berjalan', 'Tidak Berjalan'] as $v)
                            <label class="d-flex align-items-center gap-2">
                                <input type="radio" name="class_running" value="{{ $v }}" required
                                       @checked($old('class_running', $j ? ($j->class_status === 'Conducted' ? 'Berjalan' : 'Tidak Berjalan') : null) === $v)> {{ $v }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Materi --}}
                <h6 class="fw-bold border-top pt-3">Materi</h6>

                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Materi</label>
                    <input type="text" name="material_taught" class="form-control" data-run="req"
                           placeholder="Contoh: Latihan angka 1-100" value="{{ $old('material_taught', $j?->material_taught) }}">
                </div>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Aktivitas</label>
                    <textarea name="learning_activities" rows="2" class="form-control" data-run="req"
                              placeholder="Contoh: Flashcard, bernyanyi, tanya jawab">{{ $old('learning_activities', $j && $j->class_status === 'Conducted' ? $j->learning_activities : null) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Problem <small class="text-muted">(jika kelas tidak berjalan, isi alasannya)</small></label>
                    <textarea name="problems" rows="2" class="form-control" required>{{ $old('problems', $j?->problems) }}</textarea>
                </div>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Solusi</label>
                    <textarea name="solutions" rows="2" class="form-control" data-run="req">{{ $old('solutions', $j?->solutions) }}</textarea>
                </div>
                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Hasil</label>
                    <textarea name="results" rows="2" class="form-control" data-run="req">{{ $old('results', $j?->results) }}</textarea>
                </div>

                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Point</label>
                    <div class="d-flex gap-4">
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="session_score" value="10" data-run="req"
                                   @checked((string) $old('session_score', $j?->session_score) === '10')> 🟡 Kuning (10)
                        </label>
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="session_score" value="5"
                                   @checked((string) $old('session_score', $j?->session_score) === '5')> 🔴 Merah (5)
                        </label>
                    </div>
                </div>

                <div class="mb-3 run-group">
                    <label class="form-label fw-semibold">Yang Tidak Masuk <small class="text-muted">(ubah status murid yang tidak hadir)</small></label>
                    @forelse ($students as $enrollment)
                        @php $stId = $enrollment->student_id; @endphp
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-md-4">{{ $enrollment->student->name ?? '-' }}</div>
                            <div class="col-md-3">
                                <select name="attendance[{{ $stId }}]" class="form-select form-select-sm">
                                    @foreach ($statusLabels as $val => $label)
                                        <option value="{{ $val }}" @selected($old("attendance.$stId", $attStatus[$stId] ?? 'Present') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="attendance_note[{{ $stId }}]" class="form-control form-control-sm"
                                       placeholder="Keterangan (opsional)" value="{{ $old("attendance_note.$stId", $attNote[$stId] ?? null) }}">
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada murid aktif yang terdaftar di kelas ini.</div>
                    @endforelse
                </div>

                <button type="submit" class="btn btn-warning fw-semibold"
                        onclick="return confirm('{{ $editing ? 'Kirim revisi jurnal ke Kurikulum untuk direview ulang?' : 'Jurnal tidak bisa diubah setelah disimpan. Lanjutkan?' }}')">
                    <i class="fas fa-save me-2"></i> {{ $editing ? 'Kirim Revisi' : 'Simpan Jurnal' }}
                </button>
            </form>

        @elseif ($item['status'] === 'belum_diisi')

            <div class="text-muted small">Sesi ini belum bisa diisi.</div>

        @else

            {{-- Ringkasan setelah diisi --}}
            @php $notPresent = $journal->attendances->where('status', '!=', 'Present'); @endphp
            <dl class="row small mb-3">
                <dt class="col-sm-3">Guru pengajar</dt>
                <dd class="col-sm-9">{{ $journal->teacher->name ?? '-' }}
                    @if ($journal->is_substitute)
                        &middot; menggantikan {{ $journal->substituteTeacher->name ?? '-' }}
                    @endif
                </dd>

                <dt class="col-sm-3">Nama murid</dt>
                <dd class="col-sm-9">{{ $journal->student_names ?: '-' }}</dd>

                <dt class="col-sm-3">Pertemuan</dt>
                <dd class="col-sm-9">ke-{{ $journal->meeting_number ?? '-' }}{{ $journal->is_last_meeting ? ' (terakhir)' : '' }}
                    &middot; {{ $journal->course_type }} {{ $journal->course_level }}</dd>

                <dt class="col-sm-3">Kelas</dt>
                <dd class="col-sm-9">{{ $journal->class_status === 'Conducted' ? 'Berjalan' : 'Tidak Berjalan' }}</dd>

                <dt class="col-sm-3">Fokus materi</dt>
                <dd class="col-sm-9">{{ collect($journal->focus_materials)->implode(', ') ?: '-' }}</dd>

                <dt class="col-sm-3">Materi</dt><dd class="col-sm-9">{{ $journal->material_taught ?: '-' }}</dd>
                <dt class="col-sm-3">Aktivitas</dt><dd class="col-sm-9">{{ $journal->learning_activities ?: '-' }}</dd>
                <dt class="col-sm-3">Problem</dt><dd class="col-sm-9">{{ $journal->problems ?: '-' }}</dd>
                <dt class="col-sm-3">Solusi</dt><dd class="col-sm-9">{{ $journal->solutions ?: '-' }}</dd>
                <dt class="col-sm-3">Hasil</dt><dd class="col-sm-9">{{ $journal->results ?: '-' }}</dd>
                <dt class="col-sm-3">Point</dt><dd class="col-sm-9">{{ $journal->session_score ?? '-' }}</dd>
                <dt class="col-sm-3">Yang tidak masuk</dt>
                <dd class="col-sm-9">
                    @forelse ($notPresent as $att)
                        <span class="badge bg-danger-subtle text-danger">{{ $att->student->name ?? '-' }} ({{ $statusLabels[$att->status] ?? $att->status }})</span>
                    @empty
                        -
                    @endforelse
                </dd>
            </dl>

            <a href="{{ route('teacher.attendance.pdf', $journal) }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-file-pdf me-1"></i> Unduh PDF
            </a>

        @endif

    </div>
</div>