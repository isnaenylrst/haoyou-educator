@extends('layouts.guru')

@push('styles')
<style>
    /* ===== KALENDER JADWAL (sama dengan Monitoring Kurikulum) ===== */
    .sch-wrap { overflow-x: visible; }
    .sch-table { width: 100%; table-layout: fixed; border-collapse: collapse; background: var(--bs-body-bg, #fff); }
    .sch-table th, .sch-table td {
        border: 1px solid rgba(128, 128, 128, .2);
        padding: 6px;
        vertical-align: top;
        white-space: normal;          /* teks kartu boleh turun baris, tabel tidak melebar */
        word-break: break-word;
    }
    .sch-table th { font-size: 11px; text-transform: uppercase; text-align: center; }
    .sch-table th.today { background: #fff3c4; color: #000; }
    .sch-table th.sch-time, .sch-table td.sch-time { width: 64px; }
    .sch-time { font-weight: 700; font-size: 13px; text-align: center; }

    .sch-card { border-radius: 8px; padding: 6px 9px; margin-bottom: 6px; font-size: 12px; border: 2px solid transparent; color: #222; }
    .sch-card strong { display: block; font-size: 12.5px; }
    .sch-card small { opacity: .8; font-size: 11.5px; }
    .sch-card.mine { border-color: #111; }
    .sch-card.orange { background: #fdf0cf; }
    .sch-card.blue   { background: #dfeafb; }
    .sch-card.purple { background: #e8e0fb; }
    .sch-card.red    { background: #fbe3e3; }
    .sch-card.green  { background: #dcf3e2; }
    .sch-card a { color: inherit; text-decoration: none; display: block; }

    /* legenda warna (warna sama persis dengan kartu) */
    .sch-legend { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 12px; }
    .sch-legend span { display: inline-flex; align-items: center; }
    .sch-legend i { display: inline-block; width: 11px; height: 11px; border-radius: 3px; margin-right: 6px; border: 1px solid rgba(0,0,0,.12); }
    .sch-legend i.orange { background: #fdf0cf; }
    .sch-legend i.blue   { background: #dfeafb; }
    .sch-legend i.purple { background: #e8e0fb; }
    .sch-legend i.red    { background: #fbe3e3; }
    .sch-legend i.green  { background: #dcf3e2; }
    .sch-legend i.mine   { background: #fff; border: 2px solid #111; }

    /* layar kecil: baru boleh digeser */
    @media (max-width: 991.98px) {
        .sch-wrap { overflow-x: auto; }
        .sch-table { min-width: 860px; }
    }

    .chip { border: 1px solid #ccc; border-radius: 6px; padding: 3px 9px; font-size: 12px; display: inline-block; margin: 2px; }
    .chip.Present { border-color: #28a745; color: #28a745; }
    .chip.Absent { border-color: #dc3545; color: #dc3545; }
    .chip.Sick, .chip.Permission { border-color: #fd7e14; color: #fd7e14; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h2 class="fw-bold mb-1">Schedule</h2>
            <p class="text-muted mb-0">
                Kalender jadwal mengajar. Bisa menampilkan jadwal semua guru untuk mencari pengganti.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ route('teacher.schedule', ['week' => $weekStart->copy()->subWeek()->toDateString(), 'scope' => $scope, 'teacher_id' => $teacherFilterId]) }}">‹</a>
            <div class="fw-semibold small text-center" style="min-width:150px">
                {{ $weekStart->translatedFormat('d M') }} – {{ $weekEnd->translatedFormat('d M Y') }}
            </div>
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ route('teacher.schedule', ['week' => $weekStart->copy()->addWeek()->toDateString(), 'scope' => $scope, 'teacher_id' => $teacherFilterId]) }}">›</a>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('teacher.schedule', ['scope' => $scope]) }}">Minggu Ini</a>
        </div>
    </div>

    {{-- FILTER --}}
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
        <select name="scope" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="all"  @selected($scope === 'all')>Semua Guru</option>
            <option value="mine" @selected($scope === 'mine')>Jadwal Saya</option>
        </select>

        @if($scope === 'all')
        <select name="teacher_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">Semua guru</option>
            @foreach($teachers as $t)
                <option value="{{ $t->id }}" @selected($teacherFilterId == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        @endif
    </form>

    {{-- KALENDER --}}
    <div class="card shadow-sm border-0 rounded-4 mb-3">

        <div class="card-body pb-2 pt-3">
            <div class="sch-legend">
                <span><i class="orange"></i>Maochong</span>
                <span><i class="blue"></i>Jianer</span>
                <span><i class="purple"></i>Hudie</span>
                <span><i class="red"></i>Feixiang</span>
                <span><i class="green"></i>HSK / Private / NSP</span>
                <span><i class="mine"></i>Kelas Saya</span>
            </div>
        </div>

        <div class="sch-wrap">
            <table class="sch-table">
                <thead>
                    <tr>
                        <th class="sch-time">Time</th>
                        @foreach($dayHeaders as $d)
                            <th class="{{ $d['date']->isToday() ? 'today' : '' }}">
                                {{ $d['name'] }} {{ $d['date']->format('d') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse($slots as $slot)
                    <tr>
                        <td class="sch-time">{{ $slot }}</td>
                        @foreach($dayHeaders as $d)
                            <td>
                                @foreach($cells[$slot][$d['name']] ?? [] as $c)
                                    <div class="sch-card {{ $c->color }} {{ $c->is_mine ? 'mine' : '' }}">
                                        @if($c->is_mine)
                                            <a href="{{ route('teacher.attendance', ['date' => $c->date]) }}">
                                        @endif
                                        <strong>{{ $c->class_name }}</strong>
                                        <small>
                                            {{ $c->teacher_name ?? 'Belum ada guru' }} · {{ $c->students }} siswa · {{ $c->delivery_mode }}
                                        </small>
                                        <small class="d-block">{{ $c->time }}{{ $c->room ? ' · ' . $c->room : '' }}</small>
                                        @if($c->is_mine && $c->is_filled)
                                            <small class="d-block fw-bold">✓ Jurnal terisi</small>
                                        @endif
                                        @if($c->is_mine)
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ $dayHeaders->count() + 1 }}" class="text-center text-muted py-4">
                        Belum ada jadwal kelas.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
        </div>
    @endif

    {{-- KELAS MENUNGGU KONFIRMASI --}}
    @if($pendingClasses->isNotEmpty())
    <div class="card shadow-sm border-0 rounded-4 mb-3 border-warning" style="border:2px solid #f5b942 !important">
        <div class="card-body">
            <h5 class="fw-bold mb-1">Kelas Menunggu Konfirmasi Anda</h5>
            <p class="text-muted small">Kurikulum memberikan kelas berikut. Terima atau tolak (alasan wajib jika menolak).</p>

            @foreach($pendingClasses as $pc)
                <div class="border rounded-3 p-3 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $pc->class_name }}</div>
                            <div class="small text-muted">
                                @forelse($pc->schedules as $sc)
                                    {{ $sc->day }} {{ substr($sc->start_time, 0, 5) }}–{{ substr($sc->end_time, 0, 5) }}{{ !$loop->last ? ' · ' : '' }}
                                @empty
                                    Belum ada jadwal
                                @endforelse
                                · {{ $pc->delivery_mode }}
                            </div>
                            @if($pc->conflict)
                                <div class="small text-danger">Bentrok dengan kelas {{ $pc->conflict }}</div>
                            @elseif($pc->fits)
                                <div class="small text-success">Sesuai ketersediaan Anda</div>
                            @else
                                <div class="small text-warning">Di luar ketersediaan yang Anda isi</div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('teacher.class.respond', $pc) }}"
                              class="d-flex flex-wrap gap-2 align-items-start">
                            @csrf
                            <input type="text" name="note" maxlength="1000" class="form-control form-control-sm"
                                   placeholder="Alasan jika menolak" style="min-width:200px">
                            <button name="decision" value="Accepted" class="btn btn-success btn-sm">Terima</button>
                            <button name="decision" value="Rejected" class="btn btn-outline-danger btn-sm">Tolak</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- PERMINTAAN MENGGANTIKAN KELAS GURU LAIN --}}
    @if($replacementRequests->isNotEmpty())
    <div class="card shadow-sm border-0 rounded-4 mb-3" style="border:2px solid #0d6efd !important">
        <div class="card-body">
            <h5 class="fw-bold mb-1">Permintaan Menggantikan Kelas Guru Lain</h5>
            <p class="text-muted small">Guru berikut meminta Anda menggantikan kelasnya. Terima atau tolak (alasan wajib jika menolak).</p>

            @foreach($replacementRequests as $rr)
                @php $sch = $rr->classSchedule; @endphp
                <div class="border rounded-3 p-3 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">
                                {{ optional(optional($sch)->class)->class_name ?? '-' }}
                                <span class="text-muted fw-normal">· {{ $rr->teacher->name ?? '-' }} meminta digantikan</span>
                            </div>
                            <div class="small text-muted">
                                {{ $rr->leave_date ? $rr->leave_date->translatedFormat('l, d M Y') : '-' }}
                                @if($sch) · {{ substr($sch->start_time, 0, 5) }}–{{ substr($sch->end_time, 0, 5) }} @endif
                            </div>
                            <div class="small">Alasan: {{ $rr->reason }}</div>
                        </div>

                        <form method="POST" action="{{ route('teacher.replacement.respond', $rr) }}"
                              class="d-flex flex-wrap gap-2 align-items-start">
                            @csrf
                            <input type="text" name="note" maxlength="1000" class="form-control form-control-sm"
                                   placeholder="Alasan jika menolak" style="min-width:200px">
                            <button name="decision" value="Accepted" class="btn btn-success btn-sm">Terima</button>
                            <button name="decision" value="Rejected" class="btn btn-outline-danger btn-sm">Tolak</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- KETERSEDIAAN SAYA --}}
    <div class="card shadow-sm border-0 rounded-4 mb-3">
        <div class="card-body">
            <h5 class="fw-bold mb-1">Ketersediaan Saya</h5>
            <p class="text-muted small">
                Isi hari dan jam Anda bisa mengajar. Bisa diubah kapan saja; Kurikulum memakainya untuk menentukan kelas.
            </p>

            @foreach($myAvailabilities as $a)
                <form id="upd{{ $a->id }}" method="POST" action="{{ route('teacher.availability.update', $a) }}">
                    @csrf @method('PUT')
                </form>
                <form id="del{{ $a->id }}" method="POST" action="{{ route('teacher.availability.destroy', $a) }}"
                      onsubmit="return confirm('Hapus slot ini?')">
                    @csrf @method('DELETE')
                </form>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-2">
                        <select form="upd{{ $a->id }}" name="day" class="form-select form-select-sm">
                            @foreach($days as $d)
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
            @endforeach

            @if($myAvailabilities->isEmpty())
                <div class="text-muted small mb-2">Belum ada ketersediaan yang diisi.</div>
            @endif

            <hr>
            <form method="POST" action="{{ route('teacher.availability.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Hari</label>
                    <select name="day" class="form-select form-select-sm" required>
                        @foreach($days as $d)
                            <option @selected(old('day') === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Mulai</label>
                    <input type="time" name="start_time" class="form-control form-control-sm" value="{{ old('start_time') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Selesai</label>
                    <input type="time" name="end_time" class="form-control form-control-sm" value="{{ old('end_time') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Catatan</label>
                    <input type="text" name="note" maxlength="255" class="form-control form-control-sm" value="{{ old('note') }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark btn-sm">+ Tambah</button>
                </div>
            </form>
        </div>
    </div>

    {{-- KETERSEDIAAN SEMUA GURU (kalender mingguan) --}}
    <div class="card shadow-sm border-0 rounded-4 mb-3">
        <div class="card-body">
            <h5 class="fw-bold mb-1">Ketersediaan Semua Guru</h5>
            <p class="text-muted small">
                Hari dan jam semua guru yang bisa mengajar. Gunakan saat mencari pengganti atau ada booking kelas baru.
                Klik nama guru di legenda untuk melihat jadwalnya saja.
            </p>

            @include('partials.availability-grid', [
                'grid'               => $availabilityGrid,
                'highlightTeacherId' => $myTeacherId,
            ])
        </div>
    </div>

    {{-- UP NEXT --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <div class="text-warning fw-bold small">UP NEXT</div>

            @if($next)
                <h4 class="fw-bold">{{ $next->s->class_name }}</h4>
                <div class="text-muted mb-3">
                    {{ $next->start->translatedFormat('l, d F') }} ·
                    {{ $next->start->format('H:i') }} – {{ $next->end->format('H:i') }}
                    @if($next->s->room) · {{ $next->s->room }} @endif
                    · {{ $next->s->delivery_mode }}
                </div>

                <div class="mb-3">
                    @forelse($next->students as $st)
                        <span class="chip {{ $st->status }}">
                            @if($st->status === 'Present') ✓ @elseif($st->status === 'Absent') ✗ @elseif($st->status) {{ $st->status === 'Sick' ? 'S' : 'I' }} @endif
                            {{ $st->name }}
                        </span>
                    @empty
                        <span class="text-muted">Belum ada siswa di kelas ini.</span>
                    @endforelse
                </div>

                <a href="{{ route('teacher.attendance', ['date' => $next->date]) }}" class="btn btn-warning fw-bold">
                    Start Attendance
                </a>
            @else
                <div class="text-muted">Tidak ada jadwal mengajar untuk Anda dalam 2 minggu ke depan.</div>
            @endif
        </div>
    </div>

</div>
@endsection