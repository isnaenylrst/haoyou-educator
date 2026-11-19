@extends('layouts.guru')

@section('title','Attendance & Journal')

@section('content')

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Attendance & Session Journal</h2>
            <p class="text-muted mb-0">Isi absensi siswa dan jurnal harian maksimal 24 jam setelah sesi kelas selesai.</p>
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
    @php
        $activeTab = $filterStatus ?? 'all';
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a href="{{ route('teacher.attendance', ['date' => $date->format('Y-m-d')]) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm p-3 h-100 hover-card {{ $activeTab === 'all' && !$isCrossDate ? 'border border-primary border-2' : '' }}">
                    <small class="text-muted text-uppercase fw-semibold">Sesi Hari Ini</small>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalSessionToday }}</h3>
                    <small class="text-muted">Jadwal tanggal ini</small>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('teacher.attendance', ['status' => 'sudah_diisi']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm p-3 h-100 hover-card {{ $activeTab === 'sudah_diisi' ? 'border border-success border-2' : '' }}">
                    <small class="text-muted text-uppercase fw-semibold">Sudah Diisi</small>
                    <h3 class="fw-bold mb-0 text-success">{{ $statusCounts['filled'] ?? 0 }}</h3>
                    <small class="text-muted">semua tanggal</small>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('teacher.attendance', ['status' => 'belum_diisi']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm p-3 h-100 hover-card {{ $activeTab === 'belum_diisi' ? 'border border-warning border-2' : '' }}">
                    <small class="text-muted text-uppercase fw-semibold">Belum Diisi</small>
                    <h3 class="fw-bold mb-0 text-warning">{{ $statusCounts['unfilled'] ?? 0 }}</h3>
                    <small class="text-muted">semua tanggal</small>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('teacher.attendance', ['status' => 'terlambat']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm p-3 h-100 hover-card {{ in_array($activeTab, ['terlambat', 'terkunci']) ? 'border border-danger border-2' : '' }}">
                    <small class="text-muted text-uppercase fw-semibold">Terlambat / Kunci</small>
                    <h3 class="fw-bold mb-0 text-danger">{{ $statusCounts['late_or_locked'] ?? 0 }}</h3>
                    <small class="text-muted">semua tanggal</small>
                </div>
            </a>
        </div>
    </div>

    @if ($lateCount > 0)
        <div class="alert alert-danger">
            <i class="fas fa-lock me-2"></i>
            Ada <strong>{{ $lateCount }}</strong> sesi yang melewati batas 24 jam dan form terkunci. Silakan hubungi Tim Kurikulum untuk meminta pembukaan kunci.
        </div>
    @endif

    @if ($revisionCount > 0)
        <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation me-2"></i>
            Ada <strong>{{ $revisionCount }}</strong> jurnal yang dikembalikan Kurikulum dan perlu direvisi.
            Buka kartu bertanda <span class="badge bg-danger">Perlu Revisi</span> di bawah, perbaiki, lalu kirim ulang.
        </div>
    @endif

    @unless ($canFill)
        <div class="alert alert-info">Tanggal ini belum tiba, jurnal baru bisa diisi pada hari kelas atau sesudahnya.</div>
    @endunless

    {{-- Filter tabs --}}
    @php
        $activeTab = $filterStatus ?? 'all';
    @endphp
    <div class="mb-3 d-flex flex-wrap gap-2" id="sessionFilterTabs">
        <a href="{{ route('teacher.attendance', ['date' => $date->format('Y-m-d')]) }}"
           class="btn {{ $activeTab === 'all' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            Semua
            <span class="badge {{ $activeTab === 'all' ? 'bg-light text-dark' : 'bg-secondary' }} ms-1">{{ $statusCounts['total'] ?? 0 }}</span>
        </a>
        <a href="{{ route('teacher.attendance', ['status' => 'belum_diisi']) }}"
           class="btn {{ $activeTab === 'belum_diisi' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            Belum Diisi
            <span class="badge {{ ($statusCounts['unfilled'] ?? 0) > 0 ? 'bg-warning text-dark' : 'bg-secondary' }} ms-1">{{ $statusCounts['unfilled'] ?? 0 }}</span>
        </a>
        <a href="{{ route('teacher.attendance', ['status' => 'terlambat']) }}"
           class="btn {{ in_array($activeTab, ['terlambat', 'terkunci']) ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            Terlambat / Terkunci
            @if (($statusCounts['late_or_locked'] ?? 0) > 0)
                <span class="badge bg-danger ms-1">{{ $statusCounts['late_or_locked'] }}</span>
            @else
                <span class="badge bg-secondary ms-1">0</span>
            @endif
        </a>
        <a href="{{ route('teacher.attendance', ['status' => 'sudah_diisi']) }}"
           class="btn {{ $activeTab === 'sudah_diisi' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            Sudah Diisi
            <span class="badge {{ ($statusCounts['filled'] ?? 0) > 0 ? 'bg-info text-dark' : 'bg-secondary' }} ms-1">{{ $statusCounts['filled'] ?? 0 }}</span>
        </a>
        <a href="{{ route('teacher.attendance', ['status' => 'revisi']) }}"
           class="btn {{ $activeTab === 'revisi' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            Perlu Revisi
            @if (($statusCounts['revision'] ?? 0) > 0)
                <span class="badge bg-danger ms-1">{{ $statusCounts['revision'] }}</span>
            @else
                <span class="badge bg-secondary ms-1">0</span>
            @endif
        </a>
    </div>

    @if ($isCrossDate)
        <div class="alert alert-light border py-2 px-3 small d-flex align-items-center justify-content-between mb-3 rounded-3">
            <span>
                <i class="fa-solid fa-filter me-1 text-primary"></i> Menampilkan hasil filter <strong>{{ \App\Services\JournalStatusResolver::label($activeTab) }}</strong> lintas tanggal.
            </span>
            <a href="{{ route('teacher.attendance') }}" class="text-decoration-none fw-semibold small">
                Reset ke Jadwal Hari Ini &rarr;
            </a>
        </div>
    @endif

    <div id="sessionWrapper">
        @forelse ($sessions as $item)
            @include('guru.attendance._card', ['item' => $item, 'isSubstitute' => false, 'showDate' => $isCrossDate])
        @empty
            <div class="text-center text-muted py-5 card border-0 shadow-sm rounded-4">
                @if ($activeTab === 'belum_diisi')
                    <i class="fa-solid fa-circle-check fs-2 text-success d-block mb-2"></i>
                    Tidak ada jurnal yang belum diisi. Semua jurnal Anda sudah terisi!
                @elseif (in_array($activeTab, ['terlambat', 'terkunci']))
                    <i class="fa-solid fa-lock-open fs-2 text-success d-block mb-2"></i>
                    Tidak ada jurnal yang terlambat atau terkunci.
                @elseif ($activeTab === 'revisi')
                    <i class="fa-solid fa-check-double fs-2 text-success d-block mb-2"></i>
                    Tidak ada jurnal yang perlu direvisi saat ini.
                @elseif ($activeTab === 'sudah_diisi')
                    <i class="fa-regular fa-clipboard fs-2 text-secondary d-block mb-2"></i>
                    Tidak ada jurnal yang menunggu review.
                @else
                    <i class="fa-solid fa-calendar-xmark fs-2 text-secondary d-block mb-2"></i>
                    Tidak ada jadwal kelas Anda pada tanggal ini.
                @endif
            </div>
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
    if (sel) {
        sel.style.display = yes ? '' : 'none';
        sel.disabled = !yes;
        sel.required = yes;
    }
}

document.addEventListener('change', e => {
    const form = e.target.closest('.journal-form');
    if (!form) return;

    if (e.target.name === 'class_running') syncRunning(form);
    if (e.target.name === 'is_substitute') syncSub(form);
});

// Hitung mundur live untuk batas waktu 24 jam
function updateCountdowns() {
    document.querySelectorAll('.countdown-timer').forEach(el => {
        const deadline = new Date(el.dataset.deadline);
        const now = new Date();
        const diff = deadline - now;

        if (diff <= 0) {
            el.textContent = "Waktu habis (Terkunci)";
            el.classList.add('text-danger', 'fw-bold');
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        el.textContent = `${hours} jam ${minutes} menit ${seconds} detik`;
    });
}

setInterval(updateCountdowns, 1000);
updateCountdowns();

// Inisialisasi status form yang sudah terbuka
document.querySelectorAll('.journal-form').forEach(form => {
    syncRunning(form);
    syncSub(form);
});
</script>
@endpush
