@extends('layouts.guru')

@section('title','Attendance & Journal')

@section('content')

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Attendance & Session Journal</h2>
            <p class="text-muted mb-0">Isi absensi dan jurnal harian setiap selesai kelas.</p>
        </div>

        <form method="GET" action="{{ route('teacher.attendance') }}">
            <input type="date" name="date" class="form-control"
                   value="{{ $date->format('Y-m-d') }}"
                   onchange="this.form.submit()">
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>{{ session('success') }}</span>
            @if (session('pdf_url'))
                <a href="{{ session('pdf_url') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-file-pdf me-1"></i> Unduh PDF
                </a>
            @endif
        </div>
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

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3">
            <small class="text-muted text-uppercase">Sesi Hari Ini</small>
            <h3 class="fw-bold mb-0">{{ $totalSessionToday }}</h3><small class="text-muted">Terjadwal</small>
        </div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3">
            <small class="text-muted text-uppercase">Sudah Diisi</small>
            <h3 class="fw-bold mb-0 text-success">{{ $filledCount }}</h3><small class="text-muted">Jurnal selesai</small>
        </div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3">
            <small class="text-muted text-uppercase">Belum Diisi</small>
            <h3 class="fw-bold mb-0 text-warning">{{ $unfilledCount }}</h3><small class="text-muted">Perlu diisi</small>
        </div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3">
            <small class="text-muted text-uppercase">Total Hadir</small>
            <h3 class="fw-bold mb-0">{{ $totalHadirHariIni }}</h3><small class="text-muted">Siswa hari ini</small>
        </div></div>
    </div>

    @if ($revisionCount > 0)
        <div class="alert alert-danger">
            <i class="fas fa-triangle-exclamation me-2"></i>
            Ada <strong>{{ $revisionCount }}</strong> jurnal yang dikembalikan Kurikulum dan perlu direvisi.
            Buka kartu bertanda <span class="badge bg-danger">Perlu Revisi</span> di bawah, perbaiki, lalu kirim ulang.
        </div>
    @endif

    @unless ($canFill)
        <div class="alert alert-info">Tanggal ini belum tiba, jurnal baru bisa diisi pada hari kelas atau sesudahnya.</div>
    @endunless

    {{-- Filter tabs --}}
    <div class="mb-3 d-flex flex-wrap gap-2" id="sessionFilterTabs">
        <button class="btn btn-dark rounded-pill btn-sm px-3" onclick="filterSessions('all', this)">Semua</button>
        <button class="btn btn-outline-secondary rounded-pill btn-sm px-3" onclick="filterSessions('belum_diisi', this)">Belum Diisi</button>
        <button class="btn btn-outline-secondary rounded-pill btn-sm px-3" onclick="filterSessions('sudah_diisi', this)">Sudah Diisi</button>
        <button class="btn btn-outline-secondary rounded-pill btn-sm px-3" onclick="filterSessions('dikunci', this)">Dikunci</button>
        <button class="btn btn-outline-secondary rounded-pill btn-sm px-3" onclick="filterSessions('revisi', this)">
            Perlu Revisi
            @if ($revisionCount > 0)<span class="badge bg-danger ms-1">{{ $revisionCount }}</span>@endif
        </button>
    </div>

    <div id="sessionWrapper">
        @forelse ($sessions as $item)
            @include('guru.attendance._card', ['item' => $item, 'isSubstitute' => false])
        @empty
            <div class="text-center text-muted py-5">Tidak ada jadwal kelas Anda pada tanggal ini.</div>
        @endforelse
    </div>

    {{-- Mengganti guru lain --}}
    @if ($substituteSessions->isNotEmpty())
        <details class="mt-4" @if(old('_sid') || $substituteSessions->contains(fn($i) => $i['journal'])) open @endif>
            <summary class="fw-semibold mb-3" style="cursor:pointer">
                Menggantikan guru lain ({{ $substituteSessions->count() }} kelas)
            </summary>
            @foreach ($substituteSessions as $item)
                @include('guru.attendance._card', ['item' => $item, 'isSubstitute' => true])
            @endforeach
        </details>
    @endif

</div>

@endsection

@push('scripts')
<script>
const COURSES = @json(config('journal.courses'));

function filterSessions(status, btn) {
    document.querySelectorAll('#sessionFilterTabs button').forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-dark');

    document.querySelectorAll('#sessionWrapper [data-status]').forEach(card => {
        card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
    });
}

// Pilihan level mengikuti jenis kelas: HSK 1-6, Regular (per kelas), Private/Bisnis tanpa pilihan.
function syncCourse(form) {
    const type = form.querySelector('[name=course_type]:checked')?.value;
    const wrap = form.querySelector('.level-wrap');
    const sel  = form.querySelector('[name=course_level]');
    const data = COURSES[type];
    const keep = sel.dataset.selected || sel.value;

    sel.innerHTML = '<option value="">Pilih...</option>';

    if (!data || (Array.isArray(data) && data.length === 0)) {
        wrap.style.display = 'none';
        sel.disabled = true;
        sel.required = false;
        return;
    }

    const groups = Array.isArray(data) ? { '': data } : data;
    Object.entries(groups).forEach(([label, items]) => {
        const parent = label ? Object.assign(document.createElement('optgroup'), { label }) : sel;
        items.forEach(v => parent.appendChild(new Option(v, v, false, v === keep)));
        if (label) sel.appendChild(parent);
    });

    wrap.style.display = '';
    sel.disabled = false;
    sel.required = true;
    sel.dataset.selected = '';
}

// "Tidak Berjalan": sembunyikan isian materi, hanya Problem (alasan) yang wajib.
function syncRunning(form) {
    const running = form.querySelector('[name=class_running]:checked')?.value !== 'Tidak Berjalan';
    form.querySelectorAll('.run-group').forEach(g => g.style.display = running ? '' : 'none');
    form.querySelectorAll('[data-run]').forEach(el => el.required = running);
}

// Dropdown "Menggantikan siapa?" hanya muncul jika memilih Ya.
function syncSub(form) {
    const yes = form.querySelector('[name=is_substitute]:checked')?.value === 'Ya';
    const sel = form.querySelector('[name=substitute_teacher_id]');
    sel.style.display = yes ? '' : 'none';
    sel.disabled = !yes;
    sel.required = yes;
}

document.addEventListener('change', e => {
    const form = e.target.closest('.journal-form');
    if (!form) return;
    if (e.target.name === 'course_type') syncCourse(form);
    if (e.target.name === 'class_running') syncRunning(form);
    if (e.target.name === 'is_substitute') syncSub(form);
});

document.querySelectorAll('.journal-form').forEach(f => { syncCourse(f); syncRunning(f); syncSub(f); });
</script>
@endpush