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
</style>
@endpush

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Review Pengajuan</h3>
        <small class="text-muted">
            Card sesi jurnal & absensi dari semua guru, dan tab dokumen LP&PPT/Jurnal/Progress/Cuti untuk direview satu per satu.
        </small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
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
        <button class="status-pill active" data-teacher="all" onclick="filterByTeacher('all', this)">Semua Guru</button>
        @foreach ($teacherNamesToday as $name)
            <button class="status-pill" data-teacher="{{ $name }}" onclick="filterByTeacher('{{ $name }}', this)">{{ $name }}</button>
        @endforeach
    </div>

    <div id="sessionCardsWrapper">

        @forelse ($todaySchedules as $schedule)

            @php
                $journal = $schedule->teachingJournals->first();
                $teacherName = $schedule->class->teacher->name ?? '-';
                $isLate = !$journal && \Illuminate\Support\Carbon::parse($schedule->end_time)->addHours(2)->lt(now());
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
                        @if ($journal && $journal->status === 'Pending')
                            <span class="badge badge-soft-warning">Sudah Diisi • Belum ACC</span>
                        @elseif ($journal && $journal->status === 'Reviewed')
                            <span class="badge bg-success">Sudah ACC</span>
                        @elseif ($journal && $journal->status === 'Revision')
                            <span class="badge badge-soft-danger">Perlu Revisi</span>
                        @elseif (!$journal)
                            <span class="badge badge-soft-danger">Belum Diisi</span>
                        @endif
                    </div>

                </div>

                @if ($journal)

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($journal->attendances as $att)
                            @if ($att->status === 'Present')
                                <span class="chip-present">✓ {{ $att->student->name ?? '-' }}</span>
                            @else
                                <span class="chip-absent">✗ {{ $att->student->name ?? '-' }}</span>
                            @endif
                        @endforeach
                    </div>

                    <div class="note-box">
                        Poin sesi: <strong>{{ $journal->session_score ?? '-' }}</strong>
                        • Materi: {{ $journal->material->title ?? '-' }}
                        • Aktivitas: {{ $journal->learning_activities ?? '-' }}
                    </div>

                    @if ($journal->status === 'Pending')
                        <form action="{{ route('kurikulum.review-pengajuan.ack-session', $journal) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="btn btn-success btn-sm">Tandai ACC</button>
                        </form>
                    @endif

                @else

                    @if ($isLate)
                        <div class="text-danger fw-semibold mt-2">
                            Sesi sudah lewat 2 jam, guru belum mengisi jurnal & absensi.
                        </div>
                    @endif

                    <form action="{{ route('kurikulum.review-pengajuan.reminder', $schedule) }}" method="POST" class="mt-2">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm">Kirim Reminder</button>
                    </form>

                @endif

            </div>

        @empty
            <div class="text-center text-muted py-4">
                Tidak ada jadwal kelas hari ini.
            </div>
        @endforelse

    </div>

    {{-- ================= REVIEW DOKUMEN PER KATEGORI ================= --}}
    <h5 class="fw-bold mt-5 mb-3">Review Dokumen per Kategori</h5>

    <div class="mb-3 d-flex flex-wrap gap-2">
        <button class="doc-pill active" onclick="showDocTab('material', this)">LP & PPT ({{ $materials->count() }})</button>
        <button class="doc-pill" onclick="showDocTab('jurnal', this)">Jurnal Online ({{ $journals->count() }})</button>
        <button class="doc-pill" onclick="showDocTab('report', this)">Progress Report ({{ $reports->count() }})</button>
        <button class="doc-pill" onclick="showDocTab('cuti', this)">Cuti / Pengajuan Kelas ({{ $leaves->count() }})</button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">

            {{-- ===== TAB LP & PPT ===== --}}
            <div class="doc-tab" data-tab="material">
                @forelse ($materials as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $item->teacher->name ?? '-' }} — {{ $item->ppt_title }} ({{ $item->material->classroom->class_name ?? '-' }})
                        </div>
                        <small class="text-muted">Diajukan {{ $item->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ asset('storage/'.$item->ppt_file_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Lihat Dokumen</a>
                        <form action="{{ route('kurikulum.review-pengajuan.material', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Approved">
                            <button class="btn btn-primary btn-sm">ACC</button>
                        </form>
                        <form action="{{ route('kurikulum.review-pengajuan.material', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Rejected">
                            <button class="btn btn-outline-danger btn-sm">Kembalikan</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-muted py-4">Tidak ada LP & PPT yang perlu direview.</div>
                @endforelse
            </div>

            {{-- ===== TAB JURNAL ONLINE ===== --}}
            <div class="doc-tab d-none" data-tab="jurnal">
                @forelse ($journals as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $item->teacher->name ?? '-' }} — Jurnal kelas {{ $item->class->class_name ?? '-' }}
                        </div>
                        <small class="text-muted">Diajukan {{ $item->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <form action="{{ route('kurikulum.review-pengajuan.journal', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Reviewed">
                            <button class="btn btn-primary btn-sm">ACC</button>
                        </form>
                        <form action="{{ route('kurikulum.review-pengajuan.journal', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Revision">
                            <button class="btn btn-outline-danger btn-sm">Kembalikan</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-muted py-4">Tidak ada jurnal yang perlu direview.</div>
                @endforelse
            </div>

            {{-- ===== TAB PROGRESS REPORT ===== --}}
            <div class="doc-tab d-none" data-tab="report">
                @forelse ($reports as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $item->teacher->name ?? '-' }} — Progress Report {{ $item->student->name ?? '-' }}
                        </div>
                        <small class="text-muted">{{ $item->report_period }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ asset('storage/'.$item->file_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Download</a>
                        <form action="{{ route('kurikulum.review-pengajuan.report', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Approved">
                            <button class="btn btn-primary btn-sm">ACC</button>
                        </form>
                        <form action="{{ route('kurikulum.review-pengajuan.report', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Rejected">
                            <button class="btn btn-outline-danger btn-sm">Kembalikan</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-muted py-4">Tidak ada progress report yang perlu direview.</div>
                @endforelse
            </div>

            {{-- ===== TAB CUTI ===== --}}
            <div class="doc-tab d-none" data-tab="cuti">
                @forelse ($leaves as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ $item->teacher->name ?? '-' }} — {{ $item->leave_type }} ({{ $item->classSchedule->class->class_name ?? '-' }})
                        </div>
                        <small class="text-muted">{{ $item->reason }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <form action="{{ route('kurikulum.review-pengajuan.leave', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Approved">
                            <button class="btn btn-primary btn-sm">Setujui</button>
                        </form>
                        <form action="{{ route('kurikulum.review-pengajuan.leave', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="Rejected">
                            <button class="btn btn-outline-danger btn-sm">Tolak</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-muted py-4">Tidak ada pengajuan cuti/ganti kelas.</div>
                @endforelse
            </div>

        </div>
    </div>

    <small class="text-muted d-block mt-3">
        Kalau ditekan "Kembalikan", status berubah menjadi perlu revisi dan otomatis muncul tombol
        "Upload Ulang" di sisi Guru pada menu Kelas.
    </small>

</div>

@endsection

@push('scripts')
<script>
function filterByTeacher(name, btn) {
    document.querySelectorAll('#teacherFilterTabs .status-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('#sessionCardsWrapper .session-card').forEach(card => {
        card.style.display = (name === 'all' || card.dataset.teacher === name) ? '' : 'none';
    });
}

function showDocTab(tab, btn) {
    document.querySelectorAll('.doc-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.doc-tab').forEach(el => {
        el.classList.toggle('d-none', el.dataset.tab !== tab);
    });
}
</script>
@endpush