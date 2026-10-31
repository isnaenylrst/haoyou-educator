@php
    $role = auth()->user()->level?->nama_level;
    $layout = match($role) {
        'Curriculum' => 'layouts.kurikulum',
        'Teacher'    => 'layouts.guru',
        'Admin'      => 'admin.app',
        'Owner'      => 'layouts.owner',
        default      => 'layouts.dashboard',
    };
@endphp

@extends($layout)

@section('title', 'Profil & Tanda Tangan Saya')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Pengaturan Profil & Tanda Tangan</h4>
            <p class="text-muted small mb-0">Kelola foto, akun, dan tanda tangan digital untuk keperluan approval dokumen otomatis.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- CARD TANDA TANGAN DIGITAL --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-signature text-warning me-2"></i> Tanda Tangan Digital (TTD Tersimpan)
                    </h5>
                    <small class="text-muted">Tanda tangan ini disimpan sekali dan otomatis ditempel pada dokumen approval pengajuan bahan ajar, sertifikat, & progress report.</small>
                </div>
                <div class="card-body p-4">
                    @if($user->signature_path)
                        <div class="p-3 bg-light rounded-3 text-center mb-4 border">
                            <div class="small fw-semibold text-muted mb-2">Tanda Tangan Aktif Saat Ini:</div>
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Tanda Tangan" style="max-height: 120px; object-fit: contain;" class="bg-white p-2 border rounded shadow-sm">
                            <div class="text-success small mt-2">
                                <i class="fa-solid fa-check-circle me-1"></i> Tanda tangan aktif siap digunakan otomatis oleh sistem
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning py-2 px-3 small mb-4">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Anda belum memiliki tanda tangan tersimpan. Silakan gambar atau unggah di bawah ini.
                        </div>
                    @endif

                    <form action="{{ route('profil.signature') }}" method="POST" enctype="multipart/form-data" id="sigForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="signature_base64" id="signature_base64">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Opsi 1: Gambar Tanda Tangan Langsung di Kanvas</label>
                            <div class="border rounded-3 p-1 bg-white position-relative" style="touch-action: none;">
                                <canvas id="signatureCanvas" width="500" height="200" class="w-100" style="border: 1px dashed #ccc; background: #fafafa; border-radius: 8px; cursor: crosshair;"></canvas>
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="clearCanvasBtn">
                                        <i class="fa-solid fa-eraser me-1"></i> Bersihkan Kanvas
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Opsi 2: Atau Upload File Gambar Tanda Tangan (PNG Transparan disarankan)</label>
                            <input type="file" name="signature_file" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">Maksimal 2 MB (PNG, JPG, WEBP)</small>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold rounded-pill" id="saveSigBtn">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Tanda Tangan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- CARD AKUN & PASSWORD --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-user text-warning me-2"></i> Foto Profil & Akun
                    </h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-3 position-relative d-inline-block">
                        @if($user->foto)
                            <img src="{{ asset('storage/' . $user->foto) }}" alt="Foto Profil" class="rounded-circle shadow-sm object-fit-cover" width="100" height="100">
                        @else
                            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto shadow-sm" style="width: 100px; height: 100px;">
                                {{ strtoupper(substr($user->username ?? 'U', 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div class="fw-bold fs-5">{{ $user->display_name }}</div>
                    <div class="text-muted small mb-3">Role: <span class="badge bg-warning text-dark">{{ $role }}</span></div>

                    <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data" class="text-start">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Ganti Foto Profil</label>
                            <input type="file" name="foto" class="form-control form-control-sm" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-outline-warning text-dark btn-sm w-100 rounded-pill fw-semibold">
                            Upload Foto Baru
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-key text-warning me-2"></i> Ganti Password
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profil.password') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password Baru</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-semibold">
                            Perbarui Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let hasDrawn = false;

    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function startDraw(e) {
        isDrawing = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
    }

    function draw(e) {
        if (!isDrawing) return;
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        hasDrawn = true;
        e.preventDefault();
    }

    function stopDraw(e) {
        if (!isDrawing) return;
        isDrawing = false;
        ctx.closePath();
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    window.addEventListener('mouseup', stopDraw);

    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    window.addEventListener('touchend', stopDraw);

    document.getElementById('clearCanvasBtn').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
        document.getElementById('signature_base64').value = '';
    });

    document.getElementById('sigForm').addEventListener('submit', function (e) {
        const fileInput = document.querySelector('input[name="signature_file"]');
        if (hasDrawn) {
            document.getElementById('signature_base64').value = canvas.toDataURL('image/png');
        } else if (!fileInput || !fileInput.files.length) {
            // Neither drawn nor uploaded
            // allow form to submit if already has signature or let controller validate
        }
    });
});
</script>
@endpush
@endsection

