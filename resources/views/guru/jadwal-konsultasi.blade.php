@extends('layouts.guru')

@section('title', 'Jadwal Konsultasi Guru')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-calendar-check text-warning me-2"></i> Jadwal Konsultasi Pengajar</h3>
            <p class="text-muted mb-0">
                Lihat slot ketersediaan Kurikulum & Owner, ajukan jadwal konsultasi materi atau evaluasi, serta kelola ganti jadwal.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-warning rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAjukanKonsultasi">
                <i class="fa-solid fa-plus me-1"></i> Ajukan Konsultasi
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <strong>Perhatian:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- NAV TABS: KALENDER GRID SLOT & RIWAYAT PENGAJUAN --}}
    <ul class="nav nav-pills mb-4 gap-2" id="guruConsultationTabs">
        <li class="nav-item">
            <button class="nav-link active rounded-pill px-4 fw-semibold shadow-sm" id="tab-grid" data-bs-toggle="pill" data-bs-target="#pane-grid" type="button">
                <i class="fa-solid fa-calendar-week me-2"></i> Kalender Ketersediaan Slot
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4 fw-semibold shadow-sm" id="tab-riwayat" data-bs-toggle="pill" data-bs-target="#pane-riwayat" type="button">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Riwayat Pengajuan Saya ({{ $myBookings->total() }})
            </button>
        </li>
    </ul>

    <div class="tab-content mb-4">
        {{-- PANE 1: GRID KALENDER SLOT --}}
        <div class="tab-pane fade show active" id="pane-grid">
            <div class="alert alert-info border-0 shadow-sm rounded-4 mb-3">
                <i class="fa-solid fa-circle-info me-2"></i>
                Slot bertanda <strong>Tersedia (Hijau)</strong> dapat Anda pilih. Slot bertanda <strong>Penuh (Merah)</strong> sudah terisi dan tidak dapat dipesan.
                Identitas guru pemesan slot penuh disembunyikan demi privasi.
            </div>

            {{-- Render Komponen Grid Bersama --}}
            @include('partials.consultation-grid', [
                'grid'                => $grid,
                'userRole'            => 'Teacher',
                'canManageCurriculum' => false,
                'canManageOwner'      => false,
                'weekRoute'           => 'teacher.jadwal-konsultasi.index',
            ])
        </div>

        {{-- PANE 2: RIWAYAT PENGAJUAN & GANTI HARI/JAM --}}
        <div class="tab-pane fade" id="pane-riwayat">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="fa-solid fa-list-check text-warning me-2"></i> Daftar Pengajuan Konsultasi Saya
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pihak Dituju</th>
                                <th>Topik & Catatan</th>
                                <th>Jadwal Konsultasi</th>
                                <th>Status</th>
                                <th>Diajukan Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myBookings as $b)
                                <tr>
                                    <td>
                                        @if ($b->effective_role === 'Owner')
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-user-tie me-1"></i>Owner</span>
                                        @else
                                            <span class="badge bg-primary"><i class="fa-solid fa-chalkboard-user me-1"></i>Kurikulum</span>
                                        @endif
                                        <div class="small text-muted mt-1">{{ $b->curriculumUser->name ?? $b->effective_role }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $b->topic ?: 'Konsultasi' }}</div>
                                        @if ($b->class)
                                            <small class="badge bg-light text-dark border me-1">Kelas: {{ $b->class->class_name }}</small>
                                        @endif
                                        @if ($b->booking_notes)
                                            <small class="text-muted d-block mt-1">{{ $b->booking_notes }}</small>
                                        @endif

                                        {{-- Riwayat Ganti Jadwal jika ada --}}
                                        @if (!empty($b->reschedule_history))
                                            <div class="mt-2">
                                                <a class="small text-decoration-none text-primary fw-semibold" data-bs-toggle="collapse" href="#historyReschedule{{ $b->id }}">
                                                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Lihat {{ count($b->reschedule_history) }} riwayat ganti jadwal
                                                </a>
                                                <div class="collapse mt-1" id="historyReschedule{{ $b->id }}">
                                                    <div class="card card-body p-2 bg-light border small">
                                                        @foreach ($b->reschedule_history as $hist)
                                                            <div class="mb-1 border-bottom pb-1">
                                                                <span class="text-muted">{{ $hist['from_date'] }} ({{ $hist['from_time'] }})</span>
                                                                &rarr;
                                                                <strong>{{ $hist['to_date'] }} ({{ $hist['to_time'] }})</strong>
                                                                <div class="text-secondary fst-italic">Alasan: {{ $hist['reason'] }}</div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $b->date->translatedFormat('d M Y') }}</div>
                                        <small class="text-muted">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }} WIB</small>
                                        @if ($b->notes)
                                            <div class="small text-info mt-1"><i class="fa-solid fa-location-dot me-1"></i>{{ $b->notes }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($b->is_past)
                                            <span class="badge bg-secondary text-white">Selesai / Terlewat</span>
                                        @elseif ($b->teacher_booking_status === 'Pending')
                                            <span class="badge bg-warning text-dark"><i class="fa-regular fa-clock me-1"></i>Menunggu Konfirmasi</span>
                                        @elseif ($b->teacher_booking_status === 'Confirmed')
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Dikonfirmasi</span>
                                        @elseif ($b->teacher_booking_status === 'Rejected')
                                            <span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>Ditolak</span>
                                        @else
                                            <span class="badge bg-light text-muted border">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $b->booked_at ? $b->booked_at->translatedFormat('d M Y H:i') : '-' }}</small>
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $canReschedule = !$b->is_past && in_array($b->teacher_booking_status, ['Pending', 'Confirmed']);
                                        @endphp
                                        @if ($canReschedule)
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold me-1"
                                                    data-bs-toggle="modal" data-bs-target="#modalReschedule{{ $b->id }}">
                                                <i class="fa-solid fa-arrows-rotate me-1"></i> Ganti Hari/Jam
                                            </button>
                                            <form action="{{ route('teacher.jadwal-konsultasi.cancel', $b->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Batalkan pengajuan konsultasi ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill fw-semibold">
                                                    Batalkan
                                                </button>
                                            </form>

                                            {{-- MODAL GANTI JADWAL (RESCHEDULE) --}}
                                            <div class="modal fade text-start" id="modalReschedule{{ $b->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <form action="{{ route('teacher.jadwal-konsultasi.reschedule', $b->id) }}" method="POST" class="modal-content rounded-4 border-0">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold"><i class="fa-solid fa-arrows-rotate text-primary me-2"></i> Ganti Jadwal Konsultasi</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="alert alert-light border small mb-3">
                                                                <strong>Jadwal Saat Ini:</strong> {{ $b->date->translatedFormat('l, d M Y') }} ({{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }} WIB)<br>
                                                                <strong>Pihak:</strong> {{ $b->effective_role }} ({{ $b->curriculumUser->name ?? '-' }})<br>
                                                                <strong>Topik:</strong> {{ $b->topic }}
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Pilih Slot Baru yang Tersedia <span class="text-danger">*</span></label>
                                                                <select name="new_slot_id" class="form-select" required>
                                                                    <option value="">-- Pilih Slot Baru --</option>
                                                                    @foreach ($availableSlots as $av)
                                                                        @if ($av->id != $b->id)
                                                                            <option value="{{ $av->id }}">
                                                                                [{{ $av->effective_role }}] {{ $av->date->translatedFormat('d M Y') }} ({{ substr($av->start_time, 0, 5) }} - {{ substr($av->end_time, 0, 5) }} WIB) {{ $av->notes ? '- ' . $av->notes : '' }}
                                                                            </option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted">Slot lama Anda akan otomatis kembali tersedia untuk guru lain.</small>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Alasan Ganti Jadwal (opsional)</label>
                                                                <textarea name="reason" rows="2" class="form-control" placeholder="Tuliskan alasan perpindahan jadwal..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Pindahkan Jadwal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat pengajuan konsultasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-light">
                    {{ $myBookings->links() }}
                </div>
            </div>
        </div>
    </div>

</div>

{{-- MODAL AJUKAN KONSULTASI BARU --}}
<div class="modal fade" id="modalAjukanKonsultasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('teacher.jadwal-konsultasi.book') }}" method="POST" class="modal-content rounded-4 border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-plus text-warning me-2"></i> Ajukan Konsultasi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Slot Ketersediaan <span class="text-danger">*</span></label>
                    <select name="slot_id" class="form-select" required>
                        <option value="">-- Pilih Slot Konsultasi --</option>
                        @foreach ($availableSlots as $slot)
                            <option value="{{ $slot->id }}">
                                [{{ $slot->effective_role }}] {{ $slot->date->translatedFormat('d M Y') }} ({{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }} WIB) {{ $slot->notes ? '- ' . $slot->notes : '' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hanya slot bertanda Tersedia dan masa depan yang dapat dipilih.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Topik Konsultasi <span class="text-danger">*</span></label>
                    <input type="text" name="topic" class="form-control" placeholder="mis. Konsultasi Persiapan Materi Bab 4 atau Evaluasi Pengajaran" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Terkait Kelas Tertentu (opsional)</label>
                    <select name="class_id" class="form-select">
                        <option value="">-- Tidak Terikat Kelas Tertentu --</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Tambahan untuk Kurikulum / Owner (opsional)</label>
                    <textarea name="booking_notes" rows="3" class="form-control" placeholder="Tuliskan poin yang ingin dibahas lebih lanjut..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>
@endsection
