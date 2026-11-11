@extends('layouts.kurikulum')

@section('title', 'Observasi Guru & Kelas')

@section('content')
<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-magnifying-glass-chart text-warning me-2"></i> Observasi Mengajar Guru
            </h4>
            <p class="text-muted small mb-0">Penilaian kepatuhan SOP mengajar, evaluasi kelas berkala, dan rekomendasi solusi bagi pengajar.</p>
        </div>
        <button type="button" class="btn btn-warning rounded-pill fw-semibold text-dark shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalTambahObservasi">
            <i class="fa-solid fa-plus me-1"></i> Tambah Observasi
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> Ada kesalahan pada form:
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Observasi</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ $totalCount }}</div>
                    </div>
                    <div class="rounded-3 bg-light p-3 text-secondary">
                        <i class="fa-solid fa-clipboard-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Sesuai SOP</div>
                        <div class="fs-4 fw-bold text-success mt-1">{{ $totalSesuai }}</div>
                    </div>
                    <div class="rounded-3 bg-success-subtle p-3 text-success">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Perlu Perbaikan</div>
                        <div class="fs-4 fw-bold text-warning mt-1">{{ $totalPerbaikan }}</div>
                    </div>
                    <div class="rounded-3 bg-warning-subtle p-3 text-warning">
                        <i class="fa-solid fa-circle-exclamation fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Tidak Sesuai SOP</div>
                        <div class="fs-4 fw-bold text-danger mt-1">{{ $totalTidak }}</div>
                    </div>
                    <div class="rounded-3 bg-danger-subtle p-3 text-danger">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('kurikulum.observasi') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Filter Guru</label>
                    <select name="teacher_id" class="form-select form-select-sm">
                        <option value="">Semua Guru</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected($teacherId == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Status Kepatuhan SOP</label>
                    <select name="sop_compliance" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Sesuai SOP" @selected($sopCompliance === 'Sesuai SOP')>Sesuai SOP</option>
                        <option value="Perlu Perbaikan" @selected($sopCompliance === 'Perlu Perbaikan')>Perlu Perbaikan</option>
                        <option value="Tidak Sesuai SOP" @selected($sopCompliance === 'Tidak Sesuai SOP')>Tidak Sesuai SOP</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tanggal Observasi</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill rounded-pill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('kurikulum.observasi') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL RIWAYAT OBSERVASI --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-warning me-2"></i> Riwayat Observasi Pengajar
            </h6>
            <span class="badge bg-light text-dark border">{{ $observations->total() }} Data</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 130px;">Tanggal</th>
                        <th>Guru & Kelas</th>
                        <th style="width: 160px;">Kepatuhan SOP</th>
                        <th>Hasil Observasi</th>
                        <th>Feedback / Solusi</th>
                        <th class="text-center" style="width: 140px;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($observations as $obs)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $obs->observation_date->translatedFormat('d M Y') }}</div>
                                <small class="text-muted">{{ $obs->observation_date->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $obs->teacher->name ?? '-' }}</div>
                                <div class="small text-muted">
                                    <i class="fa-solid fa-chalkboard me-1 text-warning"></i> {{ $obs->class->class_name ?? 'Kelas' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $obs->sop_badge }} px-3 py-2 rounded-pill">
                                    {{ $obs->sop_compliance }}
                                </span>
                            </td>
                            <td>
                                <div class="small text-dark" style="max-width: 250px;">
                                    {{ Str::limit($obs->observation_result, 90) }}
                                </div>
                            </td>
                            <td>
                                <div class="small text-dark" style="max-width: 250px;">
                                    {{ Str::limit($obs->feedback_solution, 90) }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetailObs{{ $obs->id }}" title="Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-2 text-dark" data-bs-toggle="modal" data-bs-target="#modalEditObs{{ $obs->id }}" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form action="{{ route('kurikulum.observasi.destroy', $obs->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data observasi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- MODAL DETAIL OBSERVASI --}}
                                <div class="modal fade" id="modalDetailObs{{ $obs->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold">Detail Observasi - {{ $obs->teacher->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="row g-3 mb-3 p-3 bg-light rounded-3 border">
                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Guru Pengajar</small>
                                                        <strong class="text-dark">{{ $obs->teacher->name }}</strong>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Kelas</small>
                                                        <strong class="text-dark">{{ $obs->class->class_name }}</strong>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Tanggal Observasi</small>
                                                        <strong class="text-dark">{{ $obs->observation_date->translatedFormat('d F Y') }}</strong>
                                                    </div>
                                                    <div class="col-md-6 mt-3">
                                                        <small class="text-muted d-block">Kepatuhan SOP</small>
                                                        <span class="badge {{ $obs->sop_badge }} px-3 py-2 rounded-pill mt-1">
                                                            {{ $obs->sop_compliance }}
                                                        </span>
                                                    </div>
                                                    <div class="col-md-6 mt-3">
                                                        <small class="text-muted d-block">Pengobservasi (Kurikulum)</small>
                                                        <span class="text-dark fw-semibold">{{ $obs->curriculumUser->display_name ?? 'Kurikulum' }}</span>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-magnifying-glass text-warning me-2"></i> Hasil Observasi Kelas:</h6>
                                                    <div class="p-3 bg-white rounded-3 border text-secondary" style="white-space: pre-line;">{{ $obs->observation_result }}</div>
                                                </div>

                                                <div>
                                                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-lightbulb text-success me-2"></i> Feedback & Solusi Kurikulum:</h6>
                                                    <div class="p-3 bg-white rounded-3 border text-secondary" style="white-space: pre-line;">{{ $obs->feedback_solution }}</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- MODAL EDIT OBSERVASI --}}
                                <div class="modal fade" id="modalEditObs{{ $obs->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content rounded-4 border-0">
                                            <form action="{{ route('kurikulum.observasi.update', $obs->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-bottom">
                                                    <h5 class="modal-title fw-bold">Edit Observasi - {{ $obs->teacher->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Guru Pengajar</label>
                                                            <input type="text" class="form-control" value="{{ $obs->teacher->name }}" disabled>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Tanggal Observasi</label>
                                                            <input type="date" name="observation_date" class="form-control" value="{{ $obs->observation_date->format('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Kelas</label>
                                                            <select name="class_id" class="form-select" required>
                                                                @foreach ($obs->teacher->classes as $c)
                                                                    <option value="{{ $c->id }}" @selected($obs->class_id == $c->id)>{{ $c->class_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-semibold">Kesesuaian SOP</label>
                                                            <select name="sop_compliance" class="form-select" required>
                                                                <option value="Sesuai SOP" @selected($obs->sop_compliance === 'Sesuai SOP')>Sesuai SOP</option>
                                                                <option value="Perlu Perbaikan" @selected($obs->sop_compliance === 'Perlu Perbaikan')>Perlu Perbaikan</option>
                                                                <option value="Tidak Sesuai SOP" @selected($obs->sop_compliance === 'Tidak Sesuai SOP')>Tidak Sesuai SOP</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Hasil Observasi Kelas (bagaimana jalannya kelas & SOP)</label>
                                                        <textarea name="observation_result" rows="4" class="form-control" required>{{ $obs->observation_result }}</textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Feedback & Solusi Kurikulum</label>
                                                        <textarea name="feedback_solution" rows="4" class="form-control" required>{{ $obs->feedback_solution }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold px-4">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-magnifying-glass-chart fs-1 text-secondary mb-3 d-block"></i>
                                Belum ada catatan observasi mengajar guru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white py-3">
            {{ $observations->links() }}
        </div>
    </div>
</div>

{{-- MODAL TAMBAH OBSERVASI --}}
<div class="modal fade" id="modalTambahObservasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg text-start">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('kurikulum.observasi.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Tambah Hasil Observasi Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Guru <span class="text-danger">*</span></label>
                            <select name="teacher_id" id="selectTeacher" class="form-select" required onchange="loadTeacherClasses(this.value)">
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Observasi <span class="text-danger">*</span></label>
                            <input type="date" name="observation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas yang Diobservasi <span class="text-danger">*</span></label>
                            <select name="class_id" id="selectClass" class="form-select" required>
                                <option value="">-- Pilih Guru Terlebih Dahulu --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kesesuaian SOP <span class="text-danger">*</span></label>
                            <select name="sop_compliance" class="form-select" required>
                                <option value="Sesuai SOP">Sesuai SOP</option>
                                <option value="Perlu Perbaikan">Perlu Perbaikan</option>
                                <option value="Tidak Sesuai SOP">Tidak Sesuai SOP</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hasil Observasi Kelas (sesuai SOP atau tidak, dan bagaimana jalannya kelas) <span class="text-danger">*</span></label>
                        <textarea name="observation_result" rows="4" class="form-control" placeholder="Tuliskan catatan detail mengenai interaksi guru dengan murid, penguasaan materi, metode penyampaian, dll..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Feedback & Solusi Kurikulum <span class="text-danger">*</span></label>
                        <textarea name="feedback_solution" rows="4" class="form-control" placeholder="Tuliskan arahan, tips mengajar, atau materi pendukung yang disarankan untuk perbaikan performa mengajar..." required></textarea>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="fa-solid fa-bell me-1"></i> Setelah disimpan, hasil observasi ini otomatis masuk ke menu Observasi Guru bersangkutan dan guru akan menerima notifikasi.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-semibold text-dark px-4">
                        <i class="fa-solid fa-paper-plane me-1"></i> Simpan & Kirim ke Guru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function loadTeacherClasses(teacherId) {
    const classSelect = document.getElementById('selectClass');
    if (!teacherId) {
        classSelect.innerHTML = '<option value="">-- Pilih Guru Terlebih Dahulu --</option>';
        return;
    }

    classSelect.innerHTML = '<option value="">Memuat kelas...</option>';

    fetch(`/kurikulum/observasi/teacher/${teacherId}/classes`)
        .then(response => response.json())
        .then(classes => {
            if (classes.length === 0) {
                classSelect.innerHTML = '<option value="">(Guru belum memiliki kelas aktif)</option>';
                return;
            }

            let options = '<option value="">-- Pilih Kelas --</option>';
            classes.forEach(c => {
                options += `<option value="${c.id}">${c.class_name}</option>`;
            });
            classSelect.innerHTML = options;
        })
        .catch(err => {
            console.error('Error loading classes:', err);
            classSelect.innerHTML = '<option value="">Gagal memuat kelas</option>';
        });
}
</script>
@endpush

@endsection

