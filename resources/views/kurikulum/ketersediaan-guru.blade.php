@extends('layouts.kurikulum')

@section('title', 'Ketersediaan Guru')

@section('content')

@php
    $addOld = old('_form') === 'add';

    // Tautan WhatsApp untuk mengingatkan guru yang belum mengisi ketersediaan
    $waRemind = function ($teacher) {
        $phone = preg_replace('/\D+/', '', $teacher->phone ?? '');
        if ($phone === '') {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        $text = "Halo {$teacher->name}, mohon isi hari dan jam Anda bisa mengajar di menu Schedule > Ketersediaan Saya. Terima kasih.";

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    };
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Ketersediaan Guru</h2>
        <p class="text-muted mb-0">
            Lihat hari dan jam semua guru yang tersedia mengajar, atur ketersediaan guru, dan tugaskan guru ke kelas.
            Guru bisa menerima atau menolak kelas dari menu Schedule.
        </p>
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

    {{-- RINGKASAN --}}
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body d-flex justify-content-between align-items-center">
                <div><h3 class="fw-bold">{{ $teachers->count() }}</h3><p class="mb-0">Guru Aktif</p></div>
                <i class="fas fa-users fa-2x text-primary"></i>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body d-flex justify-content-between align-items-center">
                <div><h3 class="fw-bold text-success">{{ $filledCount }}</h3><p class="mb-0">Sudah Mengisi Ketersediaan</p></div>
                <i class="fas fa-user-check fa-2x text-success"></i>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold {{ $notFilled->isEmpty() ? 'text-success' : 'text-danger' }}">{{ $notFilled->count() }}</h3>
                    <p class="mb-0">Belum Mengisi</p>
                </div>
                <i class="fas fa-user-clock fa-2x {{ $notFilled->isEmpty() ? 'text-success' : 'text-danger' }}"></i>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold {{ $needsAction->isEmpty() ? 'text-success' : 'text-warning' }}">{{ $needsAction->count() }}</h3>
                    <p class="mb-0">Kelas Perlu Guru</p>
                </div>
                <i class="fas fa-chalkboard-user fa-2x {{ $needsAction->isEmpty() ? 'text-success' : 'text-warning' }}"></i>
            </div></div>
        </div>
    </div>

    {{-- KALENDER KETERSEDIAAN --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold">Jadwal Ketersediaan Semua Guru</span>
            <a href="#formTambah" class="btn btn-primary btn-sm">+ Tambah Ketersediaan</a>
        </div>
        <div class="card-body">
            @include('partials.availability-grid', ['grid' => $availabilityGrid, 'selectable' => true])

            @if ($notFilled->isNotEmpty())
                <div class="mt-3 small">
                    <span class="fw-semibold">Belum mengisi:</span>
                    @foreach ($notFilled as $nf)
                        <span class="badge bg-light text-dark border me-1">
                            {{ $nf->name }}
                            @if ($wa = $waRemind($nf))
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="ms-1" title="Ingatkan via WhatsApp">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- KELAS PERLU GURU --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Kelas Perlu Guru</span>
            <span class="badge {{ $needsAction->isEmpty() ? 'bg-success' : 'bg-danger' }}">{{ $needsAction->count() }}</span>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($needsAction as $class)
                @php
                    $opts = ['fit' => [], 'out' => [], 'clash' => []];
                    foreach ($teachers as $t) {
                        $f = $fit[$class->id][$t->id] ?? ['covers' => false, 'clash' => null];
                        if ($f['clash'])      { $opts['clash'][] = [$t, $f['clash']]; }
                        elseif ($f['covers']) { $opts['fit'][]   = [$t, null]; }
                        else                  { $opts['out'][]   = [$t, null]; }
                    }
                    $previous = fn ($t) => ($class->teacher_response === 'Rejected' && (int) $class->teacher_id === (int) $t->id)
                        ? ' (sebelumnya menolak)' : '';
                @endphp

                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $class->class_name }}</div>
                            <small class="text-muted d-block">
                                @forelse ($class->schedules as $s)
                                    {{ $s->day }} {{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}{{ !$loop->last ? ' · ' : '' }}
                                @empty
                                    Belum ada jadwal
                                @endforelse
                            </small>

                            @if ($class->teacher_response === 'Rejected')
                                <div class="small text-danger mt-1">
                                    Ditolak {{ $class->teacher->name ?? 'guru' }}:
                                    {{ $class->teacher_response_note ?: '(tanpa alasan)' }}
                                </div>
                            @else
                                <div class="small text-warning mt-1">Belum ada guru pengampu</div>
                            @endif
                        </div>

                        <form action="{{ route('kurikulum.ketersediaan.assign', $class) }}" method="POST"
                              class="d-flex gap-2 align-items-center" onsubmit="return lockSubmit(this, 'Mengirim...')">
                            @csrf @method('PATCH')

                            <select name="teacher_id" class="form-select form-select-sm" required style="min-width:240px">
                                <option value="">— pilih guru —</option>

                                @if ($opts['fit'])
                                    <optgroup label="Tersedia sesuai jadwal">
                                        @foreach ($opts['fit'] as [$t, $x])
                                            <option value="{{ $t->id }}">✓ {{ $t->name }}{{ $previous($t) }} — {{ $load[$t->id]['classes'] }} kelas, {{ $load[$t->id]['hours'] }} jam/mgg</option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if ($opts['out'])
                                    <optgroup label="Di luar ketersediaan yang diisi">
                                        @foreach ($opts['out'] as [$t, $x])
                                            <option value="{{ $t->id }}">{{ $t->name }}{{ $previous($t) }} — {{ $load[$t->id]['classes'] }} kelas, {{ $load[$t->id]['hours'] }} jam/mgg</option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if ($opts['clash'])
                                    <optgroup label="Bentrok (tidak bisa dipilih)">
                                        @foreach ($opts['clash'] as [$t, $clashName])
                                            <option value="{{ $t->id }}" disabled>⚠ {{ $t->name }} — bentrok: {{ $clashName }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>

                            <button class="btn btn-primary btn-sm text-nowrap">Kirim ke Guru</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">
                    Semua kelas sudah punya guru yang menerima.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MENUNGGU JAWABAN GURU --}}
    @if ($waiting->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold">Menunggu Jawaban Guru ({{ $waiting->count() }})</div>
            <div class="list-group list-group-flush">
                @foreach ($waiting as $class)
                    <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-semibold">{{ $class->class_name }}</span>
                            <small class="text-muted"> — {{ $class->teacher->name ?? '-' }}</small>
                            <small class="text-muted d-block">
                                @foreach ($class->schedules as $s)
                                    {{ $s->day }} {{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}{{ !$loop->last ? ' · ' : '' }}
                                @endforeach
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark">Menunggu</span>
                            <form action="{{ route('kurikulum.ketersediaan.unassign', $class) }}" method="POST"
                                  onsubmit="return confirm('Batalkan penugasan ini? Kelas kembali ke daftar Kelas Perlu Guru.')">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-danger btn-sm">Batalkan</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- TAMBAH KETERSEDIAAN --}}
    <div class="card shadow-sm border-0 mb-4" id="formTambah">
        <div class="card-header bg-white fw-bold">Tambah Ketersediaan Guru</div>
        <div class="card-body">
            <form action="{{ route('kurikulum.ketersediaan.store') }}" method="POST" onsubmit="return lockSubmit(this, 'Menyimpan...')">
                @csrf
                <input type="hidden" name="_form" value="add">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Guru</label>
                        <select name="teacher_id" id="fTeacher" class="form-select" required>
                            <option value="">— pilih guru —</option>
                            @foreach ($teachers as $t)
                                <option value="{{ $t->id }}" @selected($addOld && (string) old('teacher_id') === (string) $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Mulai</label>
                        <input type="time" name="start_time" id="fStart" class="form-control"
                               value="{{ $addOld ? old('start_time') : '' }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Selesai</label>
                        <input type="time" name="end_time" id="fEnd" class="form-control"
                               value="{{ $addOld ? old('end_time') : '' }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Catatan</label>
                        <input type="text" name="note" maxlength="255" class="form-control"
                               value="{{ $addOld ? old('note') : '' }}">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-semibold d-block">Hari <small class="text-muted fw-normal">(boleh lebih dari satu)</small></label>
                    <div class="d-flex flex-wrap gap-3 mb-2" id="dayChecks">
                        @foreach ($days as $d)
                            <label class="d-flex align-items-center gap-1">
                                <input type="checkbox" class="day-check" name="days[]" value="{{ $d }}"
                                       @checked($addOld && in_array($d, (array) old('days', [])))> {{ $d }}
                            </label>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnWeekdays">Senin–Jumat</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAllDays">Semua hari</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNoDays">Kosongkan</button>
                    </div>
                </div>

                <button class="btn btn-primary mt-3">Tambah Ketersediaan</button>
            </form>
        </div>
    </div>

    {{-- UBAH / HAPUS PER GURU --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Ubah / Hapus Ketersediaan per Guru</div>

        <div class="list-group list-group-flush">
            @forelse ($teachers as $t)
                <div class="list-group-item">
                    <button class="btn btn-link text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center text-start"
                            type="button" data-bs-toggle="collapse" data-bs-target="#guru{{ $t->id }}">
                        <span class="fw-semibold text-body">
                            {{ $t->name }}
                            <small class="text-muted fw-normal">
                                · {{ $load[$t->id]['classes'] }} kelas diterima, {{ $load[$t->id]['hours'] }} jam/minggu
                            </small>
                        </span>
                        <span class="badge {{ $t->availabilities->isEmpty() ? 'bg-light text-dark border' : 'bg-success' }}">
                            {{ $t->availabilities->count() }} slot
                        </span>
                    </button>

                    <div class="collapse mt-3" id="guru{{ $t->id }}">
                        @forelse ($t->availabilities as $a)
                            <form id="upd{{ $a->id }}" action="{{ route('kurikulum.ketersediaan.update', $a) }}" method="POST">
                                @csrf @method('PUT')
                            </form>
                            <form id="del{{ $a->id }}" action="{{ route('kurikulum.ketersediaan.destroy', $a) }}" method="POST"
                                  onsubmit="return confirm('Hapus slot ini?')">
                                @csrf @method('DELETE')
                            </form>

                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-2">
                                    <select form="upd{{ $a->id }}" name="day" class="form-select form-select-sm">
                                        @foreach ($days as $d)
                                            <option @selected($a->day === $d)>{{ $d }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input form="upd{{ $a->id }}" type="time" name="start_time"
                                           value="{{ substr($a->start_time, 0, 5) }}" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-2">
                                    <input form="upd{{ $a->id }}" type="time" name="end_time"
                                           value="{{ substr($a->end_time, 0, 5) }}" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-4">
                                    <input form="upd{{ $a->id }}" type="text" name="note" maxlength="255"
                                           value="{{ $a->note }}" class="form-control form-control-sm" placeholder="Catatan">
                                </div>
                                <div class="col-md-2 d-flex gap-1">
                                    <button form="upd{{ $a->id }}" class="btn btn-outline-primary btn-sm">Simpan</button>
                                    <button form="del{{ $a->id }}" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </div>
                            </div>
                        @empty
                            <small class="text-muted">Belum mengisi ketersediaan.</small>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">Belum ada guru aktif.</div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function lockSubmit(form, text) {
    const btn = form.querySelector('button:not([type=button])');
    if (btn) { btn.disabled = true; btn.innerText = text; }
    return true;
}

const dayChecks = () => [...document.querySelectorAll('.day-check')];

document.getElementById('btnWeekdays').addEventListener('click', () => {
    dayChecks().forEach(c => c.checked = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'].includes(c.value));
});
document.getElementById('btnAllDays').addEventListener('click', () => dayChecks().forEach(c => c.checked = true));
document.getElementById('btnNoDays').addEventListener('click', () => dayChecks().forEach(c => c.checked = false));

// Klik kotak jam di kalender -> isi form "Tambah Ketersediaan Guru"
document.addEventListener('avail:cell', function (e) {
    const d = e.detail;
    const pad = n => String(n).padStart(2, '0');

    const teacher = document.getElementById('fTeacher');
    const start   = document.getElementById('fStart');
    const end     = document.getElementById('fEnd');

    if (d.teacher && d.teacher !== 'all' && teacher) teacher.value = d.teacher;
    dayChecks().forEach(c => c.checked = (c.value === d.day));
    if (start) start.value = pad(d.hour) + ':00';
    if (end)   end.value   = pad(Math.min(d.hour + 1, 23)) + ':' + (d.hour + 1 > 23 ? '59' : '00');

    const form = document.getElementById('formTambah');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        form.style.outline = '2px solid #ffd600';
        setTimeout(() => form.style.outline = '', 1600);
    }
});
</script>
@endpush