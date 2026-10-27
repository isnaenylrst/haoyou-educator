{{-- Tab "Sertifikat" pada Review Pengajuan. Variabel: $certificates (paginator) --}}
@forelse ($certificates as $item)
    <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="fw-semibold">
                {{ $item->student->name ?? '-' }} — {{ $item->course_name ?: ($item->enrollment->class->class_name ?? '-') }}
            </div>
            <small class="text-muted">
                Guru: {{ $item->teacher->name ?? '-' }}
                · {{ $item->start_date?->translatedFormat('d M Y') }} s/d {{ $item->end_date?->translatedFormat('d M Y') }}
                · Nilai: {{ $item->score ?? '-' }}
                · No. {{ $item->certificate_number }}
            </small>
        </div>

        <div class="d-flex gap-2 flex-wrap review-actions">
            @if ($item->file_path)
                <a href="{{ route('kurikulum.review-pengajuan.file', ['cert', $item->id]) }}" target="_blank"
                   class="btn btn-outline-secondary btn-sm">Lihat PDF</a>
            @endif

            <form action="{{ route('kurikulum.review-pengajuan.certificate', $item) }}" method="POST"
                  onsubmit="return confirm('ACC sertifikat ini? Setelah ACC langsung tampil di siswa.')">
                @csrf @method('PATCH')
                <input type="hidden" name="decision" value="Approved">
                <button class="btn btn-primary btn-sm">ACC</button>
            </form>

            <form action="{{ route('kurikulum.review-pengajuan.certificate', $item) }}" method="POST" class="d-flex gap-1">
                @csrf @method('PATCH')
                <input type="hidden" name="decision" value="Rejected">
                <input type="text" name="note" maxlength="1000" class="form-control form-control-sm"
                       placeholder="Alasan (wajib)" style="min-width:170px" required>
                <button class="btn btn-outline-danger btn-sm">Tolak</button>
            </form>
        </div>
    </div>
@empty
    <div class="list-group-item text-center text-muted py-4">Tidak ada sertifikat yang perlu direview.</div>
@endforelse

@if ($certificates->hasPages())
    <div class="list-group-item">{{ $certificates->links() }}</div>
@endif