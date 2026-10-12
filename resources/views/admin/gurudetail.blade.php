@extends('admin.app')

@section('title', 'Detail Guru | CRM')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
@endpush

@section('content')

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">MANAJEMEN SDM</div>
            <h1>{{ $teacher->name }}</h1>
            <p>{{ $teacher->user?->username }} &middot; {{ $teacher->phone }}</p>
        </div>

        <div class="dashboard-header-actions">
            <a href="{{ route('admin.guru') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>
            <button type="button" class="btn btn-primary" onclick="openUploadDocModal()">
                <i class="fa-solid fa-upload"></i>
                Upload Dokumen
            </button>
        </div>
    </div>

    @if (session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    {{-- ========================= INFO GURU ========================= --}}
    <div class="card" style="padding:20px;margin-bottom:20px;">
        <div class="form-grid">
            <div class="form-field">
                <label>Status Akun</label>
                @php
                    $statusClass = $teacher->status === 'Active' ? 'badge-completed' : 'badge-cancelled';
                @endphp
                <span class="badge {{ $statusClass }}" style="align-self:flex-start;width:fit-content;">
                    <span class="badge-dot"></span>{{ $teacher->status }}
                </span>
            </div>
            <div class="form-field">
                <label>Status Training</label>
                @php
                    $trainingClass = match ($teacher->training_status) {
                        'Training' => 'badge-pending',
                        'Passed'   => 'badge-completed',
                        'Failed'   => 'badge-cancelled',
                        default    => 'badge-pending',
                    };
                @endphp
                @if ($teacher->training_status)
                    <span class="badge {{ $trainingClass }}" style="align-self:flex-start;width:fit-content;">
                        <span class="badge-dot"></span>{{ $teacher->training_status }}
                    </span>
                @else
                    <span>-</span>
                @endif
            </div>
            <div class="form-field">
                <label>Tanggal Bergabung</label>
                <span>{{ $teacher->join_date ? \Carbon\Carbon::parse($teacher->join_date)->format('d M Y') : '-' }}</span>
            </div>
            <div class="form-field full">
                <label>Alamat</label>
                <span>{{ $teacher->address ?: '-' }}</span>
            </div>
        </div>
    </div>

    {{-- ========================= DOKUMEN (CV / SERTIFIKAT) ========================= --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid #edeef1;font-weight:700;font-size:14px;color:#111827;">
            <i class="fa-solid fa-file-lines"></i> Dokumen Guru
        </div>
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Jenis</th>
                    <th>Tanggal Upload</th>
                    <th width="130" style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                    <tr>
                        <td>{{ $doc->title }}</td>
                        <td>{{ $doc->document_type }}</td>
                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank" rel="noopener" class="icon-btn" title="Lihat">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ Storage::disk('public')->url($doc->file_path) }}"
                                   download="{{ \Illuminate\Support\Str::slug($doc->title) }}.{{ pathinfo($doc->file_path, PATHINFO_EXTENSION) }}"
                                   class="icon-btn" title="Download">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <form action="{{ route('admin.guru.documents.destroy', $doc->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus dokumen ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn danger" title="Hapus">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:30px;color:#888;">Belum ada dokumen diupload.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ========================= RIWAYAT JURNAL MENGAJAR ========================= --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid #edeef1;font-weight:700;font-size:14px;color:#111827;">
            <i class="fa-solid fa-book"></i> Riwayat Jurnal Mengajar
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Sesi</th>
                    <th>Kelas</th>
                    <th>Materi</th>
                    <th>Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teachingJournals as $journal)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($journal->session_date)->format('d M Y') }}</td>
                        <td>{{ $journal->class?->class_name ?? '-' }}</td>
                        <td>{{ $journal->material?->title ?? '-' }}</td>
                        <td>
                            @php
                                $jClass = match ($journal->class_status) {
                                    'Conducted'   => 'badge-completed',
                                    'Cancelled'   => 'badge-cancelled',
                                    'Rescheduled' => 'badge-pending',
                                    default       => 'badge-pending',
                                };
                            @endphp
                            <span class="badge {{ $jClass }}"><span class="badge-dot"></span>{{ $journal->class_status }}</span>
                        </td>
                        <td style="max-width:260px;">{{ \Illuminate\Support\Str::limit($journal->notes ?? $journal->learning_activities, 60) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:30px;color:#888;">Belum ada jurnal mengajar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="table-footer">
            <div class="table-footer-pagination">
                @if ($teachingJournals->hasPages())
                    @php
                        $current = $teachingJournals->currentPage();
                        $last = $teachingJournals->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($teachingJournals->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $teachingJournals->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                            @endif

                            @for ($page = 1; $page <= $last; $page++)
                                @php
                                    $isEdge = $page == 1 || $page == $last;
                                    $isNearCurrent = abs($page - $current) <= $onEachSide;
                                @endphp

                                @if ($isEdge || $isNearCurrent)
                                    @if ($page == $current)
                                        <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-btn" href="{{ $teachingJournals->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($teachingJournals->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $teachingJournals->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MATERI (PPT) YANG DIUPLOAD ========================= --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid #edeef1;font-weight:700;font-size:14px;color:#111827;">
            <i class="fa-solid fa-file-powerpoint"></i> Materi (PPT) Diupload
        </div>
        <table>
            <thead>
                <tr>
                    <th>Judul PPT</th>
                    <th>Materi Terkait</th>
                    <th>Status Review</th>
                    <th>Catatan Review</th>
                    <th width="100" style="text-align:center;">File</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teacherMaterials as $tm)
                    <tr>
                        <td>{{ $tm->ppt_title }}</td>
                        <td>{{ $tm->material?->title ?? '-' }}</td>
                        <td>
                            @php
                                $tmClass = match ($tm->status) {
                                    'Approved' => 'badge-completed',
                                    'Rejected' => 'badge-cancelled',
                                    default    => 'badge-pending',
                                };
                            @endphp
                            <span class="badge {{ $tmClass }}"><span class="badge-dot"></span>{{ $tm->status }}</span>
                        </td>
                        <td>{{ $tm->review_note ?: '-' }}</td>
                        <td>
                            @if ($tm->ppt_file_path)
                                @php
                                    $tmUrl = Storage::disk('public')->url($tm->ppt_file_path);
                                    $tmExt = strtolower(pathinfo($tm->ppt_file_path, PATHINFO_EXTENSION));
                                    $tmIsOffice = in_array($tmExt, ['ppt', 'pptx']);
                                    $tmHost = parse_url($tmUrl, PHP_URL_HOST);
                                    $tmIsLocal = app()->environment('local') || in_array($tmHost, ['localhost', '127.0.0.1']);

                                    // PPT tidak bisa dibuka langsung oleh browser -> pakai Office Online viewer
                                    // (hanya jalan kalau URL file bisa diakses publik / sudah di-deploy).
                                    $tmViewUrl = ($tmIsOffice && ! $tmIsLocal)
                                        ? 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($tmUrl)
                                        : $tmUrl;
                                @endphp
                                <div class="row-actions" style="justify-content:center;">
                                    <a href="{{ $tmViewUrl }}" target="_blank" rel="noopener" class="icon-btn" title="Lihat">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ $tmUrl }}" download="{{ \Illuminate\Support\Str::slug($tm->ppt_title) }}.{{ $tmExt }}" class="icon-btn" title="Download">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                </div>
                            @else
                                <div style="text-align:center;color:#9ca3af;">-</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:30px;color:#888;">Belum ada materi diupload.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="table-footer">
            <div class="table-footer-pagination">
                @if ($teacherMaterials->hasPages())
                    @php
                        $current = $teacherMaterials->currentPage();
                        $last = $teacherMaterials->lastPage();
                        $onEachSide = 1;
                    @endphp

                    <nav class="pagination-nav" aria-label="Pagination">
                        <ul class="pagination-list">
                            @if ($teacherMaterials->onFirstPage())
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $teacherMaterials->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                            @endif

                            @for ($page = 1; $page <= $last; $page++)
                                @php
                                    $isEdge = $page == 1 || $page == $last;
                                    $isNearCurrent = abs($page - $current) <= $onEachSide;
                                @endphp

                                @if ($isEdge || $isNearCurrent)
                                    @if ($page == $current)
                                        <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-btn" href="{{ $teacherMaterials->url($page) }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == 2 && $current - $onEachSide > 2)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                                    <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                                @endif
                            @endfor

                            @if ($teacherMaterials->hasMorePages())
                                <li class="page-item"><a class="page-btn" href="{{ $teacherMaterials->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                            @else
                                <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= MODAL — UPLOAD DOKUMEN ========================= --}}
    <div class="modal-overlay" id="uploadDocModalOverlay">
        <div class="modal">
            <form action="{{ route('admin.guru.documents.store', $teacher->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Upload Dokumen Guru</div>
                        <div class="modal-sub">CV, sertifikat, atau dokumen pendukung lain.</div>
                    </div>
                    <button type="button" class="modal-close" onclick="closeUploadDocModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-field full">
                                <label>Judul Dokumen *</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-field">
                                <label>Jenis Dokumen *</label>
                                <select name="document_type" required>
                                    <option value="CV">CV</option>
                                    <option value="Teacher Certificate">Sertifikat Guru</option>
                                    <option value="Photo">Foto</option>
                                    <option value="Other">Lainnya</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>File *</label>
                                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <span class="form-hint">PDF/JPG/PNG, maks 5MB.</span>
                            </div>
                            <div class="form-field full">
                                <label>Deskripsi <span class="opt">(opsional)</span></label>
                                <textarea name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <div></div>
                    <div class="modal-foot-right">
                        <button type="button" class="btn btn-secondary" onclick="closeUploadDocModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openUploadDocModal() {
        document.getElementById('uploadDocModalOverlay').classList.add('open');
    }
    function closeUploadDocModal() {
        document.getElementById('uploadDocModalOverlay').classList.remove('open');
    }
    document.getElementById('uploadDocModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeUploadDocModal();
    });
</script>
@endpush