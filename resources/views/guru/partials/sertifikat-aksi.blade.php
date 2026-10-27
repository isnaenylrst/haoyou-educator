{{-- Aksi sertifikat per siswa. Variabel: $s (baris siswa dari ProgressReportController@index) --}}
@php
    $cert = \App\Models\Certificate::where('enrollment_id', $s->enrollment_id)->first();
@endphp

@if ($cert && $cert->status === 'Approved')
    <span class="badge bg-success align-self-center">Sertifikat disetujui</span>
    <a href="{{ route('teacher.sertifikat.download', $cert) }}" class="btn btn-outline-success btn-sm">
        Unduh Sertifikat
    </a>

@elseif ($cert && $cert->status === 'Pending')
    <span class="badge bg-warning text-dark align-self-center">Sertifikat menunggu ACC Kurikulum</span>
    <a href="{{ route('teacher.sertifikat.download', $cert) }}" class="btn btn-outline-secondary btn-sm">
        Lihat PDF
    </a>

@elseif ($s->certificate_ready)
    @if ($cert && $cert->status === 'Rejected')
        <span class="badge bg-danger align-self-center">Sertifikat ditolak</span>
        @if ($cert->review_note)
            <span class="small text-danger align-self-center">Catatan Kurikulum: {{ $cert->review_note }}</span>
        @endif
    @endif

    <form method="POST" action="{{ route('teacher.progress-report.certificate.store', $s->enrollment_id) }}" class="d-inline"
          onsubmit="return confirm('Buat sertifikat dan kirim ke Kurikulum untuk di-ACC?')">
        @csrf
        <button class="btn btn-warning btn-sm">
            {{ $cert && $cert->status === 'Rejected' ? 'Ajukan Ulang Sertifikat' : 'Buat Sertifikat' }}
        </button>
    </form>
@endif