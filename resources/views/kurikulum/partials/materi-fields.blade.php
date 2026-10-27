@php
    /** @var \App\Models\Material|null $m */
    $m      = $m ?? null;
    $useOld = !$m || (string) old('_edit_id') === (string) $m->id;

    $val = fn (string $field, $default = '') => $useOld
        ? old($field, $m?->{$field} ?? $default)
        : ($m?->{$field} ?? $default);

    $err = fn (string $field) => $useOld ? $errors->first($field) : '';
    $fb  = fn (string $field) => $err($field)
        ? '<div class="invalid-feedback d-block">' . e($err($field)) . '</div>'
        : '';

    $vocabText = $m
        ? $m->vocabularies->map(fn ($v) => $v->hanzi . '|' . $v->pinyin . '|' . $v->meaning)->implode("\n")
        : '';
    $vocabValue = $useOld ? old('vocabularies', $vocabText) : $vocabText;
@endphp

@if ($m)
    <input type="hidden" name="_edit_id" value="{{ $m->id }}">
@endif

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label fw-semibold">Pilih Level</label>
        <select name="level_id" class="form-select {{ $err('level_id') ? 'is-invalid' : '' }}" required>
            @if (!$m)
                <option value="">-- Pilih Level --</option>
            @endif
            @foreach ($levels as $level)
                <option value="{{ $level->id }}" @selected((string) $val('level_id') === (string) $level->id)>
                    {{ $level->program?->program_name ?? '-' }} - {{ $level->level_name }}
                </option>
            @endforeach
        </select>
        {!! $fb('level_id') !!}
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">Pertemuan</label>
        <input type="number" name="meeting_number" min="1" max="255"
               class="form-control {{ $err('meeting_number') ? 'is-invalid' : '' }}"
               value="{{ $val('meeting_number') }}" required>
        {!! $fb('meeting_number') !!}
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Judul Materi</label>
    <input type="text" name="title" maxlength="255"
           class="form-control {{ $err('title') ? 'is-invalid' : '' }}"
           value="{{ $val('title') }}" required>
    {!! $fb('title') !!}
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Silabus</label>
    <textarea name="syllabus" rows="5" class="form-control">{{ $val('syllabus') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">CCO</label>
    <textarea name="cco" rows="3" class="form-control"
        placeholder="Capaian pembelajaran / tujuan (CCO) pertemuan ini">{{ $val('cco') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Lesson Plan</label>
    <textarea name="lesson_plan" rows="4" class="form-control"
        placeholder="Urutan kegiatan belajar dalam satu pertemuan">{{ $val('lesson_plan') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Aktivitas</label>
    <textarea name="activity" rows="3" class="form-control"
        placeholder="Aktivitas / permainan yang dilakukan di kelas">{{ $val('activity') }}</textarea>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">Kode Box</label>
        <input type="text" name="box_code" maxlength="50"
               class="form-control {{ $err('box_code') ? 'is-invalid' : '' }}"
               placeholder="mis. BOX-A12" value="{{ $val('box_code') }}">
        {!! $fb('box_code') !!}
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label fw-semibold">Perlengkapan di Box</label>
        <textarea name="equipment_list" rows="3" class="form-control"
            placeholder="Satu perlengkapan per baris, mis.&#10;Kartu kosakata&#10;Dadu&#10;Spidol papan">{{ $val('equipment_list') }}</textarea>
    </div>
</div>

{{-- FILE SAAT INI (hanya di form edit) --}}
@if ($m)
    <div class="mb-3">
        <label class="form-label fw-semibold">File Materi Saat Ini</label>

        <div class="border rounded p-3 bg-light">
            @if ($m->file_exists)
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="fa-solid fa-file-lines me-2"></i>
                        <strong>Materi Pertemuan {{ $m->meeting_number }}</strong>
                        <span class="badge bg-secondary ms-1">{{ strtoupper($m->file_extension) }}</span><br>
                        <small class="text-muted">
                            {{ $m->file_size_label }} · diunggah {{ $m->created_at?->translatedFormat('d M Y') }}
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        @if ($m->is_pdf)
                            <a href="{{ route('kurikulum.materi.preview', $m) }}" target="_blank"
                               class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-eye me-1"></i> Lihat
                            </a>
                        @endif
                        <a href="{{ route('kurikulum.materi.download', $m) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-download me-1"></i> Download
                        </a>
                    </div>
                </div>
            @else
                <div class="text-danger small">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    File tidak ditemukan di server. Unggah ulang lewat kolom "Ganti File Materi" di bawah.
                </div>
            @endif
        </div>
    </div>
@endif

<div class="mb-3">
    <label class="form-label fw-semibold">{{ $m ? 'Ganti File Materi' : 'Upload File Materi' }}</label>
    <input type="file" name="material_file"
           class="form-control {{ $err('material_file') ? 'is-invalid' : '' }}"
           accept=".pdf,.doc,.docx,.ppt,.pptx" @if (!$m) required @endif>
    <small class="text-muted">
        PDF, DOC, DOCX, PPT, atau PPTX. Maksimal 10 MB.
        @if ($m) Kosongkan jika tidak ingin mengganti file. @endif
    </small>
    {!! $fb('material_file') !!}
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Daftar Kosakata</label>
    <textarea name="vocabularies" rows="{{ $m ? 8 : 6 }}" class="form-control"
        placeholder="爸爸|bàba|Ayah&#10;妈妈|māma|Ibu&#10;哥哥|gēge|Kakak Laki-laki">{{ $vocabValue }}</textarea>
    <small class="text-muted">
        Format: <strong>Hanzi | Pinyin | Arti</strong>, satu kosakata per baris (maksimal 255 baris).
        @if ($m) Jika diubah, kosakata lama diganti dengan yang baru. @endif
    </small>
</div>