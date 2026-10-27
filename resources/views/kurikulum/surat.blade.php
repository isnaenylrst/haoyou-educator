@extends('layouts.kurikulum')

@section('title','Pemberitahuan Surat')

@section('content')

@php
    $editId = (string) old('_edit_id');
    $addErr = fn (string $f) => $editId === '' ? $errors->first($f) : '';

    $typeBadge = [
        'SURAT_LIBUR' => ['bg-warning text-dark', 'Surat Libur'],
        'SURAT_DINAS' => ['bg-primary', 'Surat Dinas'],
        'LOA'         => ['bg-success', 'LoA'],
    ];

    // Tautan WhatsApp untuk mengingatkan guru yang belum membuka surat
    $waRemind = function ($user, $letter) {
        $phone = preg_replace('/\D+/', '', $user->teacher->phone ?? '');
        if ($phone === '') {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        $text = "Halo {$user->teacher->name}, ada surat \"{$letter->title}\" dari Kurikulum yang belum Anda buka. Mohon dicek di menu Surat. Terima kasih.";

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
    };
@endphp

<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Pemberitahuan Surat</h2>
        <p class="text-muted mb-0">
            Upload file surat & tentukan penerima (semua guru atau guru tertentu). Anda bisa memantau siapa yang sudah membuka.
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Terjadi Kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <small class="text-uppercase text-secondary fw-bold">Total Terkirim</small>
                <h1 class="fw-bold mb-0 mt-2">{{ $totalLetters }}</h1>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <small class="text-uppercase text-secondary fw-bold">Bulan Ini</small>
                <h1 class="fw-bold text-success mb-0 mt-2">{{ $lettersThisMonth }}</h1>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <small class="text-uppercase text-secondary fw-bold">Belum Dibaca</small>
                <h1 class="fw-bold text-warning mb-0 mt-2">{{ $unreadLetters }}</h1>
                <small class="text-muted">Penerima yang belum membuka suratnya</small>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <small class="text-uppercase text-secondary fw-bold">Jenis Surat</small>
                <h5 class="fw-bold mt-2 mb-0">Libur / Dinas / LoA</h5>
            </div></div>
        </div>
    </div>

    {{-- FORM SURAT BARU --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h3 class="fw-bold mb-4">Buat Surat Baru</h3>

            @if ($teachersWithoutAccount > 0)
                <div class="alert alert-warning py-2 small">
                    {{ $teachersWithoutAccount }} guru aktif belum terhubung dengan akun login, jadi tidak muncul sebagai penerima.
                </div>
            @endif

            <form action="{{ route('kurikulum.surat.store') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="return lockSubmit(this, 'Mengirim...')">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis Surat</label>
                    <select class="form-select" name="jenis_surat" required>
                        <option value="">-- Pilih Jenis Surat --</option>
                        @foreach (['SURAT_LIBUR' => 'Surat Libur', 'SURAT_DINAS' => 'Surat Dinas', 'LOA' => 'LoA'] as $val => $label)
                            <option value="{{ $val }}" @selected($editId === '' && old('jenis_surat') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Penerima</label>
                    <select class="form-select" name="penerima" id="penerima" required>
                        <option value="SEMUA_GURU" @selected(old('penerima', 'SEMUA_GURU') === 'SEMUA_GURU')>Semua Guru ({{ $teachers->count() }} guru aktif)</option>
                        <option value="GURU_TERTENTU" @selected(old('penerima') === 'GURU_TERTENTU')>Guru Tertentu</option>
                    </select>
                </div>

                {{-- Pilih beberapa guru --}}
                <div class="mb-3" id="teacherBox" style="display:none;">
                    <label class="form-label fw-semibold">Pilih Guru</label>
                    <div class="border rounded p-3">
                        <div class="d-flex gap-2 mb-2">
                            <input type="text" id="teacherSearch" class="form-control form-control-sm" placeholder="Cari nama guru...">
                            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" id="checkAll">Pilih semua</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" id="uncheckAll">Kosongkan</button>
                        </div>
                        <div style="max-height:220px;overflow-y:auto" id="teacherList">
                            @foreach ($teachers as $teacher)
                                <label class="d-flex align-items-center gap-2 py-1 teacher-row" data-name="{{ strtolower($teacher->name) }}">
                                    <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}" class="teacher-check"
                                           @checked($editId === '' && in_array($teacher->id, (array) old('teacher_ids', [])))>
                                    {{ $teacher->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <small class="text-muted"><span id="selectedCount">0</span> guru dipilih</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Surat</label>
                    <input type="text" class="form-control" name="judul" maxlength="255"
                           value="{{ $editId === '' ? old('judul') : '' }}"
                           placeholder="Contoh: Libur Nasional 17 Agustus 2026" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Isi Surat</label>
                    <textarea class="form-control" rows="4" name="isi" maxlength="5000"
                              placeholder="Tuliskan isi pemberitahuan di sini...">{{ $editId === '' ? old('isi') : '' }}</textarea>
                    <small class="text-muted">Isi surat tampil langsung di halaman Surat guru.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Upload File Surat (PDF/Gambar)</label>
                    <input type="file" class="form-control" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                    <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 10 MB.</small>
                </div>

                <button type="submit" class="btn btn-success px-4">
                    <i class="fa-solid fa-paper-plane me-2"></i> Kirim Surat
                </button>
            </form>
        </div>
    </div>

    {{-- RIWAYAT SURAT --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h3 class="fw-bold mb-0">Riwayat Surat Terkirim</h3>
                <span class="badge bg-primary rounded-pill px-3 py-2">{{ $letters->total() }} Surat</span>
            </div>

            <form method="GET" action="{{ route('kurikulum.surat') }}" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua jenis</option>
                        @foreach (['SURAT_LIBUR' => 'Surat Libur', 'SURAT_DINAS' => 'Surat Dinas', 'LOA' => 'LoA'] as $val => $label)
                            <option value="{{ $val }}" @selected($type === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" name="q" value="{{ $q }}" maxlength="100" class="form-control form-control-sm"
                           placeholder="Cari judul atau isi surat">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-fill">Cari</button>
                    <a href="{{ route('kurikulum.surat') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>

            @forelse ($letters as $letter)
                @php
                    $badge   = $typeBadge[$letter->document_type] ?? ['bg-secondary', 'Surat'];
                    $total   = $letter->recipients_total;
                    $read    = $letter->recipients_read;
                    $percent = $total > 0 ? round($read / $total * 100) : 0;
                @endphp

                <div class="py-4 border-bottom">
                    <div class="row align-items-start">

                        <div class="col-lg-8">
                            <h6 class="fw-bold mb-2">{{ $letter->title }}</h6>

                            <div class="mb-2">
                                <span class="badge rounded-pill {{ $badge[0] }} px-3 py-2">{{ $badge[1] }}</span>
                                @unless ($letter->file_exists)
                                    <span class="badge rounded-pill bg-danger px-3 py-2">File hilang</span>
                                @endunless
                            </div>

                            <small class="text-muted d-block"><strong>Penerima:</strong> {{ $letter->recipient_label }}</small>
                            <small class="text-muted d-block"><strong>Dikirim:</strong> {{ $letter->uploaded_at?->translatedFormat('d M Y H:i') }}</small>
                            @if ($letter->uploader)
                                <small class="text-muted d-block"><strong>Pengirim:</strong> {{ $letter->uploader->username }}</small>
                            @endif

                            @if ($letter->description)
                                <div class="mt-2 small">{!! nl2br(e($letter->description)) !!}</div>
                            @endif

                            {{-- Status baca --}}
                            <div class="mt-3" style="max-width:420px">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Dibaca <strong>{{ $read }}</strong> dari {{ $total }} guru</span>
                                    <span>{{ $percent }}%</span>
                                </div>
                                <div class="progress" style="height:6px">
                                    <div class="progress-bar {{ $percent === 100 ? 'bg-success' : 'bg-warning' }}" style="width:{{ $percent }}%"></div>
                                </div>
                            </div>

                            <details class="mt-2">
                                <summary class="small text-primary" style="cursor:pointer">Lihat status baca per guru</summary>
                                <ul class="list-unstyled small mt-2 mb-0">
                                    @foreach ($letter->recipients->sortBy(fn ($u) => $u->teacher->name ?? $u->username) as $u)
                                        <li class="py-1">
                                            @if ($u->pivot->read_at)
                                                <span class="text-success">✓</span>
                                                {{ $u->teacher->name ?? $u->username }}
                                                <span class="text-muted">— dibuka {{ \Illuminate\Support\Carbon::parse($u->pivot->read_at)->translatedFormat('d M Y H:i') }}</span>
                                            @else
                                                <span class="text-danger">○</span>
                                                {{ $u->teacher->name ?? $u->username }}
                                                <span class="text-muted">— belum dibuka</span>
                                                @if ($wa = $waRemind($u, $letter))
                                                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="ms-1">
                                                        <i class="fa-brands fa-whatsapp"></i> ingatkan
                                                    </a>
                                                @endif
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        </div>

                        <div class="col-lg-4 mt-3 mt-lg-0">
                            <div class="d-flex justify-content-lg-end flex-wrap gap-2">
                                @if ($letter->file_exists)
                                    <a href="{{ route('kurikulum.surat.file', $letter) }}" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-eye me-1"></i> Lihat
                                    </a>
                                    <a href="{{ route('kurikulum.surat.download', $letter) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fa-solid fa-download me-1"></i> Unduh
                                    </a>
                                @endif

                                <button type="button" class="btn btn-outline-warning btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#editLetter{{ $letter->id }}">
                                    <i class="fa-solid fa-pen me-1"></i> Ubah
                                </button>

                                <form action="{{ route('kurikulum.surat.destroy', $letter) }}" method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus surat ini? File juga akan dihapus dan hilang dari halaman guru.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fa-regular fa-envelope fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold">Belum Ada Surat</h5>
                    <p class="text-muted mb-0">
                        @if ($type || $q !== '')
                            Tidak ada surat yang cocok. <a href="{{ route('kurikulum.surat') }}">Reset filter</a>
                        @else
                            Belum ada surat yang dikirim.
                        @endif
                    </p>
                </div>
            @endforelse

            @if ($letters->hasPages())
                <div class="mt-3">{{ $letters->links() }}</div>
            @endif
        </div>
    </div>

    {{-- MODAL UBAH SURAT --}}
    @foreach ($letters as $letter)
        @php $useOld = $editId === (string) $letter->id; @endphp

        <div class="modal fade" id="editLetter{{ $letter->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('kurikulum.surat.update', $letter) }}" method="POST" enctype="multipart/form-data"
                      class="modal-content" onsubmit="return lockSubmit(this, 'Menyimpan...')">
                    @csrf @method('PUT')
                    <input type="hidden" name="_edit_id" value="{{ $letter->id }}">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Ubah Surat</h5>
                            <small class="text-muted">Penerima: {{ $letter->recipient_label }} (penerima tidak bisa diubah)</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Jenis Surat</label>
                                <select name="jenis_surat" class="form-select" required>
                                    @foreach (['SURAT_LIBUR' => 'Surat Libur', 'SURAT_DINAS' => 'Surat Dinas', 'LOA' => 'LoA'] as $val => $label)
                                        <option value="{{ $val }}" @selected(($useOld ? old('jenis_surat', $letter->document_type) : $letter->document_type) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">Judul Surat</label>
                                <input type="text" name="judul" maxlength="255" required
                                       class="form-control {{ $useOld && $errors->has('judul') ? 'is-invalid' : '' }}"
                                       value="{{ $useOld ? old('judul', $letter->title) : $letter->title }}">
                                @if ($useOld && $errors->has('judul'))
                                    <div class="invalid-feedback">{{ $errors->first('judul') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Isi Surat</label>
                            <textarea name="isi" rows="4" maxlength="5000"
                                      class="form-control">{{ $useOld ? old('isi', $letter->description) : $letter->description }}</textarea>
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-semibold">Ganti File (opsional)</label>
                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png"
                                   class="form-control {{ $useOld && $errors->has('file') ? 'is-invalid' : '' }}">
                            <small class="text-muted">
                                File saat ini: {{ strtoupper($letter->file_extension) }} · {{ $letter->file_size_label }}.
                                Jika file diganti, status baca semua guru direset agar mereka membuka versi terbaru.
                            </small>
                            @if ($useOld && $errors->has('file'))
                                <div class="invalid-feedback d-block">{{ $errors->first('file') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>

<script>
function lockSubmit(form, text) {
    const btn = form.querySelector('button[type=submit]');
    if (btn) { btn.disabled = true; btn.innerText = text; }
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    const penerima = document.getElementById('penerima');
    const box      = document.getElementById('teacherBox');
    const checks   = () => [...document.querySelectorAll('.teacher-check')];
    const counter  = document.getElementById('selectedCount');

    function updateCount() { counter.textContent = checks().filter(c => c.checked).length; }

    function toggleBox() {
        const specific = penerima.value === 'GURU_TERTENTU';
        box.style.display = specific ? 'block' : 'none';
        checks().forEach(c => c.disabled = !specific);
        updateCount();
    }

    penerima.addEventListener('change', toggleBox);
    document.addEventListener('change', e => { if (e.target.classList.contains('teacher-check')) updateCount(); });

    document.getElementById('checkAll').addEventListener('click', () => {
        document.querySelectorAll('.teacher-row').forEach(r => {
            if (r.style.display !== 'none') r.querySelector('input').checked = true;
        });
        updateCount();
    });
    document.getElementById('uncheckAll').addEventListener('click', () => { checks().forEach(c => c.checked = false); updateCount(); });

    document.getElementById('teacherSearch').addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.teacher-row').forEach(r => {
            r.style.display = (!q || r.dataset.name.includes(q)) ? '' : 'none';
        });
    });

    toggleBox();

    @if ($editId !== '')
        // Jika simpan perubahan gagal validasi, buka kembali modal yang sama
        const el = document.getElementById('editLetter{{ (int) $editId }}');
        if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
    @endif
});
</script>

@endsection