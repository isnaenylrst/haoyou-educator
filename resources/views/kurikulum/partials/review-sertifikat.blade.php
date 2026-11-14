{{-- Tab "Sertifikat" pada Review Pengajuan. Variabel: $certificates (paginator) --}}
@once
<style>
  .rc-chip{border:1px solid #CFCBBE;border-radius:6px;padding:6px 14px;text-align:center;min-width:92px;background:#fff}
  .rc-chip .rc-lbl{font-size:10px;letter-spacing:1px;color:#5F5E5A}
  .rc-chip .rc-val{font-size:18px;font-weight:700;line-height:1.2;color:#23211C}
  .rc-chip.rc-total{background:#FAEEDA;border-color:#E8D3B0}
  .rc-chip.rc-total .rc-lbl,.rc-chip.rc-total .rc-val{color:#633806}
  .rc-chip.rc-total .rc-val small{font-size:11px;font-weight:400}
  .rc-preview summary{cursor:pointer;font-size:13px;color:#0d6efd;list-style:none}
  .rc-preview summary::-webkit-details-marker{display:none}
  .rc-preview[open] summary{margin-bottom:8px}
  .rc-frame{width:100%;aspect-ratio:842/595;border:1px solid #CFCBBE;border-radius:6px;background:#f8f8f6}
</style>
@endonce

@forelse ($certificates as $item)
    @php
        $fileUrl = $item->file_path
            ? route('kurikulum.review-pengajuan.file', ['cert', $item->id])
            : null;

        $skills = [
            'LISTENING' => $item->listening_score,
            'READING'   => $item->reading_score,
            'SPEAKING'  => $item->speaking_score,
            'WRITING'   => $item->writing_score,
        ];
    @endphp

    <div class="list-group-item">

        {{-- Baris atas: identitas sertifikat + tombol keputusan --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="fw-semibold fs-6">
                    {{ $item->student->name ?? '-' }}
                </div>
                <div class="small">
                    {{ $item->course_name ?: ($item->enrollment->class->class_name ?? '-') }}
                </div>
                <small class="text-muted d-block mt-1">
                    No. {{ $item->certificate_number }}
                    · Periode {{ $item->start_date?->translatedFormat('d F Y') }} s/d {{ $item->end_date?->translatedFormat('d F Y') }}
                </small>
                <small class="text-muted d-block">
                    Guru: {{ $item->teacher->name ?? '-' }}
                    · TTD Owner: {{ $item->owner_signed_at ? 'sudah ('.$item->owner_signed_at->translatedFormat('d M Y').')' : 'belum' }}
                </small>
            </div>

            <div class="d-flex gap-2 flex-wrap review-actions">
                @if ($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank"
                       class="btn btn-outline-secondary btn-sm">Buka PDF</a>
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

        {{-- Nilai, sama seperti kotak nilai di sertifikat --}}
        <div class="d-flex gap-2 flex-wrap mt-3">
            @foreach ($skills as $name => $value)
                <div class="rc-chip">
                    <div class="rc-lbl">{{ $name }}</div>
                    <div class="rc-val">{{ $value ?? '-' }}</div>
                </div>
            @endforeach
            <div class="rc-chip rc-total">
                <div class="rc-lbl">TOTAL SCORE</div>
                <div class="rc-val">{{ $item->score ?? '-' }} <small>/ 100</small></div>
            </div>
        </div>

        {{-- Pratinjau PDF (file yang sama dengan yang diunduh siswa) --}}
        <div class="mt-3">
            @if ($fileUrl)
                <details class="rc-preview">
                    <summary>▸ Lihat pratinjau sertifikat</summary>
                    <iframe class="rc-frame" loading="lazy" src="{{ $fileUrl }}#view=FitH" title="Pratinjau sertifikat {{ $item->certificate_number }}"></iframe>
                </details>
            @else
                <small class="text-muted">PDF sertifikat belum tersedia, jadi pratinjau belum bisa ditampilkan.</small>
            @endif
        </div>
    </div>
@empty
    <div class="list-group-item text-center text-muted py-4">Tidak ada sertifikat yang perlu direview.</div>
@endforelse

@if ($certificates->hasPages())
    <div class="list-group-item">{{ $certificates->links() }}</div>
@endif