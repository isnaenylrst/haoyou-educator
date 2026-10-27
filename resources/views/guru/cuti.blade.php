@extends('layouts.guru')

@section('content')
<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Cuti / Ganti Kelas</h2>
        <p class="text-muted">Pengajuan cuti atau permintaan guru pengganti. Semua pengajuan akan dikirim ke Kepala Kurikulum.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- FORM --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('teacher.cuti.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jenis Pengajuan</label>
                        <select name="request_type" id="request_type" class="form-select">
                            <option value="Cuti"        @selected(old('request_type') === 'Cuti')>Cuti</option>
                            <option value="Ganti Kelas" @selected(old('request_type') === 'Ganti Kelas')>Ganti Kelas</option>
                        </select>
                        @error('request_type') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="leave_date" id="leave_date" class="form-control"
                               min="{{ now()->toDateString() }}" value="{{ old('leave_date') }}">
                        @error('leave_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kelas</label>
                        <select name="class_schedule_id" id="class_schedule_id" class="form-select">
                            <option value="">Pilih Kelas</option>
                        </select>
                        <div class="form-text" id="kelas_hint">Pilih tanggal dulu, kelas akan menyesuaikan hari.</div>
                        @error('class_schedule_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">
                            Guru Pengganti <span class="text-muted fw-normal" id="guru_hint">(opsional untuk Cuti)</span>
                        </label>
                        <select name="replacement_teacher_id" id="replacement_teacher_id" class="form-select">
                            <option value="">Pilih Guru</option>
                        </select>
                        <div class="form-text">Daftar guru diambil dari jadwal yang tersedia (tidak bentrok).</div>
                        @error('replacement_teacher_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4" id="leave_type_box">
                        <label class="form-label fw-semibold">Alasan Cuti</label>
                        <select name="leave_type" class="form-select">
                            <option value="Permission" @selected(old('leave_type') === 'Permission')>Izin</option>
                            <option value="Sick"       @selected(old('leave_type') === 'Sick')>Sakit</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Alasan</label>
                        <textarea name="reason" rows="3" class="form-control"
                                  placeholder="Tuliskan alasan cuti atau pergantian kelas...">{{ old('reason') }}</textarea>
                        @error('reason') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dokumen Pendukung <span class="text-muted fw-normal">(opsional, mis. surat dokter)</span></label>
                        <input type="file" name="supporting_document" class="form-control">
                        @error('supporting_document') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-warning fw-bold mt-3">Kirim Pengajuan</button>
            </form>
        </div>
    </div>

    {{-- RIWAYAT --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Riwayat Pengajuan</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th><th>Jenis</th><th>Kelas</th>
                            <th>Guru Pengganti</th><th>Status</th><th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($history as $h)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($h->leave_date ?? $h->created_at)->translatedFormat('d M Y') }}</td>
                            <td>{{ $h->request_type ?? 'Cuti' }}</td>
                            <td>{{ $h->class_name }}</td>
                            <td>
                                {{ $h->replacement_name ?? '-' }}
                                @if($h->replacement_teacher_id)
                                    @if($h->replacement_response === 'Accepted')
                                        <div class="small text-success">Pengganti menerima</div>
                                    @elseif($h->replacement_response === 'Rejected')
                                        <div class="small text-danger">Pengganti menolak</div>
                                    @else
                                        <div class="small text-muted">Menunggu jawaban pengganti</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($h->status === 'Approved')
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif($h->status === 'Rejected')
                                    <span class="badge bg-danger">Ditolak</span>
                                @else
                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                @endif
                            </td>
                            <td>
                                {{ $h->reason }}
                                @if($h->review_note)
                                    <div class="small text-danger">Catatan: {{ $h->review_note }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $history->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<script>
    const schedules = @json($scheduleOptions);
    const available = @json($available);
    const oldSchedule = @json(old('class_schedule_id'));
    const oldTeacher  = @json(old('replacement_teacher_id'));

    const dateEl = document.getElementById('leave_date');
    const kelasEl = document.getElementById('class_schedule_id');
    const guruEl = document.getElementById('replacement_teacher_id');
    const typeEl = document.getElementById('request_type');

    const dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    function fillKelas() {
        kelasEl.innerHTML = '<option value="">Pilih Kelas</option>';
        guruEl.innerHTML = '<option value="">Pilih Guru</option>';
        if (!dateEl.value) return;

        const day = dayNames[new Date(dateEl.value + 'T00:00:00').getDay()];
        const list = schedules.filter(s => s.day === day);

        list.forEach(s => kelasEl.add(new Option(s.label, s.id)));
        document.getElementById('kelas_hint').textContent = list.length
            ? 'Jadwal Anda hari ' + day + '.'
            : 'Anda tidak punya jadwal mengajar di hari ' + day + '.';
    }

    function fillGuru() {
        guruEl.innerHTML = '<option value="">Pilih Guru</option>';
        (available[kelasEl.value] || []).forEach(t => guruEl.add(new Option(
            t.name + (t.fits ? '  ✔ tersedia di jam ini' : '  (belum mengisi ketersediaan)'), t.id)));
    }

    function toggleType() {
        const isCuti = typeEl.value === 'Cuti';
        document.getElementById('leave_type_box').style.display = isCuti ? '' : 'none';
        document.getElementById('guru_hint').textContent = isCuti ? '(opsional untuk Cuti)' : '(wajib untuk Ganti Kelas)';
    }

    dateEl.addEventListener('change', fillKelas);
    kelasEl.addEventListener('change', fillGuru);
    typeEl.addEventListener('change', toggleType);

    // pulihkan pilihan setelah validasi gagal
    fillKelas();
    if (oldSchedule) { kelasEl.value = oldSchedule; fillGuru(); if (oldTeacher) guruEl.value = oldTeacher; }
    toggleType();
</script>
@endsection