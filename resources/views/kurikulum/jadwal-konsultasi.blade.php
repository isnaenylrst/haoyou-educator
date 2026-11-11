@extends('layouts.kurikulum')

@section('title','Jadwal Konsultasi')

@section('content')

@php
    $editId = (string) old('_edit_id');
    $addErr = fn (string $f) => $editId === '' ? $errors->first($f) : '';
    $addOld = $editId === '';

    $typeLabels = [
        'Materi'         => 'Konsultasi Materi',
        'Materi_Baru'    => 'Konsultasi Materi Baru (deadline H-2 minggu)',
        'LP_PPT'         => 'Konsultasi LP & PPT (Kurikulum)',
        'Direktur'       => 'Konsultasi Direktur',
        'Trial_Teaching' => 'Trial Teaching',
    ];

    // Tautan WhatsApp (wa.me) siap kirim; nomor dari akun guru atau yang diisi manual
    $waLink = function ($c) {
        $phone = preg_replace('/\D+/', '', $c->teacher_phone ?: ($c->teacher?->phone ?? ''));
        if ($phone === '') {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $when = $c->scheduled_at ? $c->scheduled_at->translatedFormat('l, d F Y \p\u\k\u\l H:i') : 'akan diinformasikan';
        $text = "Halo {$c->teacher_display_name}, jadwal {$c->type_label}: {$when}.";
        if ($c->class) {
            $text .= " Kelas: {$c->class->class_name}.";
        }
        if ($c->deadline_at) {
            $text .= ' Deadline konsultasi: ' . $c->deadline_at->translatedFormat('d F Y') . '.';
        }
        if ($c->notes) {
            $text .= " Catatan: {$c->notes}.";
        }
        $text .= ' Terima kasih.';

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    };

    $tabs = [
        'aktif'      => ['Aktif', ($counts['Belum Terjadwal'] ?? 0) + ($counts['Terjadwal'] ?? 0)],
        'terlambat'  => ['Lewat Deadline', $overdueCount],
        'selesai'    => ['Selesai', $counts['Selesai'] ?? 0],
        'dibatalkan' => ['Dibatalkan', $counts['Dibatalkan'] ?? 0],
        'semua'      => ['Semua', $counts->sum()],
    ];
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Jadwal Konsultasi</h3>
        <small class="text-muted">
            Jadwal konsultasi guru: Materi, Materi Baru, LP & PPT, Direktur, dan Trial Teaching.
            Guru yang terdaftar langsung menerima jadwalnya di Notifikasi.
        </small>
    </div>

    @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type)
        @if (session($key))
            <div class="alert alert-{{ $type }} alert-dismissible fade show">
                {{ session($key) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Form belum bisa disimpan:</strong>
            <ul class="mb-0">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- SARAN KONSULTASI MATERI BARU --}}
    @if ($suggestions->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4 border-start border-warning border-4">
            <div class="card-header bg-white">
                <span class="fw-bold">Perlu Konsultasi Materi Baru</span>
                <span class="badge bg-warning text-dark ms-1">{{ $suggestions->count() }}</span>
                <div class="small text-muted">
                    Kelas yang akan segera mulai, gurunya sudah menerima dan <strong>belum pernah mengajar level ini</strong>.
                    Konsultasi paling lambat <strong>H-14</strong> sebelum mengajar pertama.
                </div>
            </div>

            <div class="list-group list-group-flush">
                @foreach ($suggestions as $row)
                    @php
                        $cl    = $row['class'];
                        $days  = $row['days_left'];
                        $dl    = $row['deadline'];
                        $badge = $days === null ? ['secondary', '-'] : ($days < 0
                            ? ['danger', 'Lewat ' . abs($days) . ' hari']
                            : ($days <= 3 ? ['warning text-dark', $days . ' hari lagi'] : ['success', $days . ' hari lagi']));
                    @endphp

                    <div class="list-group-item">
                        <form action="{{ route('kurikulum.jadwal-konsultasi.store') }}" method="POST"
                              class="d-flex justify-content-between align-items-center flex-wrap gap-2"
                              onsubmit="return lockSubmit(this, 'Menyimpan...')">
                            @csrf
                            <input type="hidden" name="type" value="Materi_Baru">
                            <input type="hidden" name="class_id" value="{{ $cl->id }}">
                            <input type="hidden" name="teacher_name" value="{{ $cl->teacher->name }}">

                            <div>
                                <div class="fw-semibold">{{ $cl->teacher->name }} — {{ $cl->class_name }}</div>
                                <small class="text-muted">
                                    {{ $cl->level?->level_name }} · mengajar pertama {{ $row['first']?->translatedFormat('l, d M Y') }}
                                    · deadline konsultasi <strong>{{ $dl?->translatedFormat('d M Y') }}</strong>
                                    <span class="badge bg-{{ $badge[0] }} ms-1">{{ $badge[1] }}</span>
                                </small>
                            </div>

                            <div class="d-flex gap-2 align-items-center">
                                <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm"
                                       min="{{ now()->format('Y-m-d\T00:00') }}"
                                       @if ($dl && !$dl->lt(today())) max="{{ $dl->format('Y-m-d') }}T23:59" @endif
                                       title="Isi jika jadwalnya sudah pasti (boleh dikosongkan)">
                                <button class="btn btn-primary btn-sm text-nowrap">Buat Jadwal</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (($counts['Belum Terjadwal'] ?? 0) > 0)
        <div class="mb-3">
            <span class="badge bg-danger-subtle text-danger">
                {{ $counts['Belum Terjadwal'] }} konsultasi Belum Terjadwal, perlu dijadwalkan
            </span>
        </div>
    @endif

    {{-- TAB --}}
    <ul class="nav nav-pills mb-3">
        @foreach ($tabs as $key => [$label, $total])
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('kurikulum.jadwal-konsultasi', ['tab' => $key]) }}">
                    {{ $label }}
                    <span class="badge {{ $key === 'terlambat' && $total > 0 ? 'bg-danger' : 'bg-light text-dark' }} ms-1">{{ $total }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- DAFTAR --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="list-group list-group-flush">

            @forelse ($consultations as $c)
                @php
                    $wa    = ($c->status === 'Terjadwal' && $c->notify_whatsapp) ? $waLink($c) : null;
                    $state = $c->deadline_state;
                    $days  = $c->deadline_days;
                @endphp

                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $c->type_label }} — {{ $c->teacher_display_name }}
                            @if ($c->is_unregistered_teacher)
                                <span class="badge bg-light text-dark border ms-1" title="Guru belum terdaftar di website, tidak menerima notifikasi di akunnya">belum terdaftar</span>
                            @endif
                        </div>

                        <small class="text-muted">
                            @if ($c->scheduled_at)
                                {{ $c->scheduled_at->translatedFormat('l, d F Y \p\u\k\u\l H:i') }}
                            @else
                                Belum terjadwal
                            @endif
                            @if ($c->class) · Kelas: {{ $c->class->class_name }} @endif
                            @if ($c->teacherMaterial) · Pengajuan: {{ $c->teacherMaterial->ppt_title }} @endif
                        </small>

                        @if ($c->deadline_at)
                            <div class="small mt-1">
                                <i class="fa-regular fa-clock me-1 text-muted"></i>
                                Deadline: <strong>{{ $c->deadline_at->translatedFormat('d F Y') }}</strong>
                                @if ($state === 'late')
                                    <span class="badge bg-danger ms-1">Lewat {{ abs($days) }} hari</span>
                                @elseif ($state === 'soon')
                                    <span class="badge bg-warning text-dark ms-1">{{ $days === 0 ? 'Hari ini' : $days . ' hari lagi' }}</span>
                                @elseif ($state === 'ok')
                                    <span class="badge bg-light text-dark border ms-1">{{ $days }} hari lagi</span>
                                @endif
                            </div>
                        @endif

                        @if ($c->notes)
                            <div class="small mt-1"><i class="fa-solid fa-location-dot me-1 text-muted"></i>{{ $c->notes }}</div>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($c->status === 'Belum Terjadwal')
                            <span class="badge bg-warning text-dark">Belum Terjadwal</span>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal{{ $c->id }}">
                                Jadwalkan
                            </button>
                        @elseif ($c->status === 'Terjadwal')
                            <span class="badge bg-success">Terjadwal</span>
                            @if ($wa)
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm">
                                    <i class="fa-brands fa-whatsapp"></i> Kirim WA
                                </a>
                            @endif
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalJadwal{{ $c->id }}">
                                Ganti Jadwal
                            </button>
                            <form action="{{ route('kurikulum.jadwal-konsultasi.complete', $c) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Tandai konsultasi ini sudah selesai?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-success btn-sm">Selesai</button>
                            </form>
                        @elseif ($c->status === 'Selesai')
                            <span class="badge bg-secondary">Selesai</span>
                        @else
                            <span class="badge bg-dark">Dibatalkan</span>
                        @endif

                        @if (in_array($c->status, ['Belum Terjadwal', 'Terjadwal'], true))
                            <form action="{{ route('kurikulum.jadwal-konsultasi.cancel', $c) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Batalkan konsultasi ini?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-warning btn-sm">Batalkan</button>
                            </form>
                        @endif

                        <form action="{{ route('kurikulum.jadwal-konsultasi.destroy', $c) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Hapus jadwal ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">
                    Tidak ada jadwal konsultasi pada tab ini.
                </div>
            @endforelse

        </div>

        @if ($consultations->hasPages())
            <div class="card-footer bg-white">{{ $consultations->links() }}</div>
        @endif
    </div>

    {{-- MODAL JADWALKAN / GANTI JADWAL --}}
    @foreach ($consultations as $c)
        @if (in_array($c->status, ['Belum Terjadwal', 'Terjadwal'], true))
            @php
                $useOld = $editId === (string) $c->id;
                $isNew  = $c->type === 'Materi_Baru';
                $maxIso = ($isNew && $c->deadline_at && !$c->deadline_at->lt(today())) ? $c->deadline_at->format('Y-m-d') . 'T23:59' : null;
            @endphp

            <div class="modal fade" id="modalJadwal{{ $c->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('kurikulum.jadwal-konsultasi.update', $c) }}" method="POST" class="modal-content"
                          onsubmit="return lockSubmit(this, 'Menyimpan...')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="_edit_id" value="{{ $c->id }}">

                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">Jadwalkan — {{ $c->teacher_display_name }}</h5>
                                <small class="text-muted">{{ $c->type_label }}@if ($c->class) · {{ $c->class->class_name }}@endif</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            @if ($isNew && $c->deadline_at)
                                <div class="alert alert-info small py-2">
                                    Deadline konsultasi: <strong>{{ $c->deadline_at->translatedFormat('l, d F Y') }}</strong>
                                    (H-{{ \App\Services\ConsultationDeadline::LEAD_DAYS }} sebelum mengajar pertama).
                                    @if ($maxIso) Jadwal tidak boleh melewati tanggal ini. @endif
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal & Jam</label>
                                <input type="datetime-local" name="scheduled_at"
                                       class="form-control {{ $useOld && $errors->has('scheduled_at') ? 'is-invalid' : '' }}"
                                       min="{{ now()->format('Y-m-d\T00:00') }}"
                                       @if ($maxIso) max="{{ $maxIso }}" @endif
                                       value="{{ $useOld ? old('scheduled_at') : $c->scheduled_at?->format('Y-m-d\TH:i') }}" required>
                                @if ($useOld && $errors->has('scheduled_at'))
                                    <div class="invalid-feedback">{{ $errors->first('scheduled_at') }}</div>
                                @endif
                            </div>

                            @unless ($isNew)
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Deadline <small class="text-muted fw-normal">(opsional)</small></label>
                                    <input type="date" name="deadline_at" class="form-control"
                                           value="{{ $useOld ? old('deadline_at') : $c->deadline_at?->format('Y-m-d') }}">
                                </div>
                            @endunless

                            <div class="mb-1">
                                <label class="form-label fw-semibold">Lokasi / Link Meeting <small class="text-muted fw-normal">(opsional)</small></label>
                                <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                                          placeholder="mis. Ruang Kurikulum lantai 2, atau https://meet.google.com/...">{{ $useOld ? old('notes') : $c->notes }}</textarea>
                                <small class="text-muted">Catatan ini terlihat oleh guru.</small>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    {{-- BUAT JADWAL BARU --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h3 class="fw-bold mb-4">Buat Jadwal Baru</h3>

            <form action="{{ route('kurikulum.jadwal-konsultasi.store') }}" method="POST"
                  onsubmit="return lockSubmit(this, 'Menyimpan...')">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Konsultasi</label>
                        <select name="type" id="fType" class="form-select {{ $addErr('type') ? 'is-invalid' : '' }}" required>
                            @foreach ($typeLabels as $val => $label)
                                <option value="{{ $val }}" @selected($addOld && old('type', 'Materi') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($addErr('type'))<small class="text-danger">{{ $addErr('type') }}</small>@endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Kelas <span id="classRequired" class="text-danger" style="display:none">(wajib untuk Materi Baru)</span>
                            <span id="classOptional" class="text-muted fw-normal">(opsional)</span>
                        </label>
                        <select name="class_id" id="fClass" class="form-select {{ $addErr('class_id') ? 'is-invalid' : '' }}">
                            <option value="">— tidak terkait kelas —</option>
                            @foreach ($classes as $cl)
                                <option value="{{ $cl->id }}" @selected($addOld && (string) old('class_id') === (string) $cl->id)>
                                    {{ $cl->class_name }} — mulai {{ $classInfo[$cl->id]['start'] }}
                                </option>
                            @endforeach
                        </select>
                        @if ($addErr('class_id'))<small class="text-danger">{{ $addErr('class_id') }}</small>@endif
                    </div>
                </div>

                {{-- Pratinjau aturan H-2 minggu --}}
                <div id="deadlineInfo" class="alert alert-info small py-2" style="display:none"></div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Guru</label>
                        <input type="text" name="teacher_name" id="fTeacherName" list="teacherList" maxlength="150"
                               class="form-control {{ $addErr('teacher_name') ? 'is-invalid' : '' }}"
                               placeholder="Ketik nama guru (boleh guru baru / trial teaching)"
                               value="{{ $addOld ? old('teacher_name') : '' }}" autocomplete="off">
                        <datalist id="teacherList">
                            @foreach ($teachers as $t)
                                <option value="{{ $t->name }}"></option>
                            @endforeach
                        </datalist>
                        <small class="text-muted">
                            Jika namanya cocok dengan guru terdaftar, jadwal otomatis masuk ke akun guru itu.
                            Jika tidak, tersimpan sebagai guru baru.
                        </small>
                        @if ($addErr('teacher_name'))<div class="text-danger small">{{ $addErr('teacher_name') }}</div>@endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. WhatsApp Guru <small class="text-muted fw-normal">(opsional)</small></label>
                        <input type="text" name="teacher_phone" maxlength="30" class="form-control"
                               placeholder="mis. 0812xxxxxxx (untuk guru yang belum terdaftar)"
                               value="{{ $addOld ? old('teacher_phone') : '' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal & Jam <small class="text-muted fw-normal">(kosongkan jika belum pasti)</small></label>
                        <input type="datetime-local" name="scheduled_at" id="fSched"
                               class="form-control {{ $addErr('scheduled_at') ? 'is-invalid' : '' }}"
                               min="{{ now()->format('Y-m-d\T00:00') }}"
                               value="{{ $addOld ? old('scheduled_at') : '' }}">
                        @if ($addErr('scheduled_at'))<small class="text-danger">{{ $addErr('scheduled_at') }}</small>@endif
                    </div>

                    <div class="col-md-6 mb-3" id="deadlineManual">
                        <label class="form-label">Deadline Konsultasi <small class="text-muted fw-normal">(opsional)</small></label>
                        <input type="date" name="deadline_at" id="fDeadline"
                               class="form-control {{ $addErr('deadline_at') ? 'is-invalid' : '' }}"
                               min="{{ today()->toDateString() }}"
                               value="{{ $addOld ? old('deadline_at') : '' }}">
                        @if ($addErr('deadline_at'))<small class="text-danger">{{ $addErr('deadline_at') }}</small>@endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Lokasi / Link Meeting <small class="text-muted fw-normal">(opsional)</small></label>
                    <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                              placeholder="mis. Ruang Kurikulum lantai 2, atau https://meet.google.com/...">{{ $addOld ? old('notes') : '' }}</textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="notifyWa" checked>
                    <label class="form-check-label" for="notifyWa">
                        Tampilkan tombol “Kirim WA” untuk memberi kabar guru
                    </label>
                    <div><small class="text-muted">Tombol membuka WhatsApp dengan pesan siap kirim. Pengiriman otomatis belum aktif.</small></div>
                </div>

                <button type="submit" class="btn btn-success">Buat Jadwal</button>
            </form>
        </div>
    </div>

</div>

<script>
    const CLASS_INFO = @json($classInfo);

    function lockSubmit(form, text) {
        const btn = form.querySelector('button[type=submit], button:not([type])');
        if (btn) { btn.disabled = true; btn.innerText = text; }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const fType   = document.getElementById('fType');
        const fClass  = document.getElementById('fClass');
        const fName   = document.getElementById('fTeacherName');
        const fSched  = document.getElementById('fSched');
        const fDead   = document.getElementById('fDeadline');
        const info    = document.getElementById('deadlineInfo');
        const manual  = document.getElementById('deadlineManual');
        const reqSpan = document.getElementById('classRequired');
        const optSpan = document.getElementById('classOptional');

        function refresh() {
            const isNew = fType.value === 'Materi_Baru';
            const cls   = CLASS_INFO[fClass.value] || null;

            reqSpan.style.display = isNew ? '' : 'none';
            optSpan.style.display = isNew ? 'none' : '';
            fClass.required = isNew;

            // Materi Baru: deadline dihitung otomatis dari kelas (tidak diisi manual)
            manual.style.display = isNew ? 'none' : '';
            fDead.disabled = isNew;

            if (isNew && cls) {
                info.style.display = '';
                info.innerHTML = 'Mengajar pertama: <strong>' + (cls.first || '-') + '</strong><br>' +
                    'Deadline konsultasi (H-2 minggu): <strong>' + (cls.deadline || '-') + '</strong>' +
                    (cls.deadline_past ? ' <span class="badge bg-danger ms-1">sudah lewat, jadwalkan secepatnya</span>' : '');
            } else if (isNew) {
                info.style.display = '';
                info.textContent = 'Pilih kelas untuk melihat deadline konsultasi (H-2 minggu sebelum mengajar pertama).';
            } else {
                info.style.display = 'none';
            }

            // Jadwal tidak boleh melewati deadline (selama deadline belum lewat)
            if (isNew && cls && cls.deadline_iso && !cls.deadline_past) {
                fSched.max = cls.deadline_iso + 'T23:59';
            } else {
                fSched.removeAttribute('max');
            }
        }

        fType.addEventListener('change', refresh);
        fClass.addEventListener('change', function () {
            const cls = CLASS_INFO[fClass.value];
            if (cls && cls.teacher && fName.value.trim() === '') fName.value = cls.teacher;  // isi otomatis guru pengampu
            refresh();
        });
        refresh();

        @if ($editId !== '')
            // Jika simpan jadwal gagal validasi, buka kembali modal yang sama
            const el = document.getElementById('modalJadwal{{ (int) $editId }}');
            if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
        @endif
    });
</script>

@endsection