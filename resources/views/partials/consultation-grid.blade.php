{{--
    Shared Consultation Slot Grid Component
    Digunakan bersama oleh:
    - Kurikulum (Jadwal Konsultasi)
    - Owner (Konsultasi Pengajar)
    - Guru (Jadwal Konsultasi Guru)

    Variabel:
    - $grid (hasil ConsultationSlotGridService::buildWeeklyGrid)
    - $userRole ('Curriculum', 'Owner', 'Teacher')
    - $canManageCurriculum (boolean)
    - $canManageOwner (boolean)
    - $weekRoute (route name for week navigation)
    - $editSlotRoute (route name for updating slot)
    - $deleteSlotRoute (route name for deleting slot)
--}}
@php
    $canManageCurriculum = $canManageCurriculum ?? false;
    $canManageOwner      = $canManageOwner ?? false;
    $userRole            = $userRole ?? 'Curriculum';
@endphp

<div class="consultation-grid-wrapper mb-4" id="consultationGridApp">
    {{-- NAVIGASI MINGGU & FILTER TOGGLE LAPISAN --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                {{-- Navigasi Minggu --}}
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route($weekRoute, array_merge(request()->query(), ['week' => $grid['prev_week']])) }}"
                       class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-chevron-left me-1"></i> Minggu Lalu
                    </a>
                    <a href="{{ route($weekRoute, array_merge(request()->query(), ['week' => $grid['current_week']])) }}"
                       class="btn btn-outline-primary btn-sm rounded-pill px-3 {{ request('week', now()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->toDateString()) === $grid['current_week'] ? 'active' : '' }}">
                        Minggu Ini
                    </a>
                    <a href="{{ route($weekRoute, array_merge(request()->query(), ['week' => $grid['next_week']])) }}"
                       class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        Minggu Depan <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                    <span class="ms-2 fw-semibold text-muted small">
                        Rentang: {{ \Illuminate\Support\Carbon::parse($grid['start_of_week'])->translatedFormat('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($grid['start_of_week'])->addDays(6)->translatedFormat('d M Y') }}
                    </span>
                </div>

                {{-- Legenda & Toggle Lapisan --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small fw-bold text-muted me-1">Tampilkan Lapisan:</span>
                    <button type="button" class="btn btn-sm btn-outline-primary active rounded-pill filter-layer-btn" data-layer="Curriculum">
                        <span class="badge rounded-circle p-1 me-1" style="background-color: #0d6efd;">&nbsp;</span> Slot Kurikulum
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark active rounded-pill filter-layer-btn" data-layer="Owner">
                        <span class="badge rounded-circle p-1 me-1" style="background-color: #ffc107;">&nbsp;</span> Slot Owner
                    </button>

                    <div class="vr mx-1"></div>

                    {{-- Legenda Status --}}
                    <span class="badge bg-success text-white py-1 px-2"><i class="fa-solid fa-circle-check me-1"></i> Tersedia</span>
                    <span class="badge bg-danger text-white py-1 px-2"><i class="fa-solid fa-lock me-1"></i> Penuh / Dipesan</span>
                    <span class="badge bg-secondary text-white py-1 px-2">Terlewat</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL GRID --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle text-center consultation-calendar-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;" class="py-2">Jam</th>
                            @foreach ($grid['days'] as $d)
                                <th class="py-2 {{ $d['is_today'] ? 'bg-primary-subtle text-primary border-primary' : '' }}" style="min-width: 130px;">
                                    <div class="fw-bold">{{ $d['day_name'] }}</div>
                                    <small class="text-muted">{{ $d['formatted'] }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grid['hours'] as $h)
                            <tr>
                                <td class="table-light text-muted fw-semibold small py-3 align-middle">
                                    {{ sprintf('%02d:00', $h) }}
                                </td>
                                @foreach ($grid['days'] as $d)
                                    @php
                                        $cellSlots = $grid['cells'][$h][$d['date']] ?? [];
                                    @endphp
                                    <td class="p-1 align-top cell-drop" data-date="{{ $d['date'] }}" data-hour="{{ $h }}">
                                        @foreach ($cellSlots as $item)
                                            @php
                                                $isCurriculum = $item['role'] === 'Curriculum';
                                                $layerBorder = $isCurriculum ? 'border-primary' : 'border-warning';
                                                $roleBadge = $isCurriculum
                                                    ? '<span class="badge bg-primary text-white" style="font-size: 10px;">Kurikulum</span>'
                                                    : '<span class="badge bg-warning text-dark" style="font-size: 10px;">Owner</span>';
                                                
                                                // Hak kelola: Kurikulum hanya bisa kelola slot Kurikulum miliknya, Owner slot miliknya
                                                $editable = ($isCurriculum && $canManageCurriculum && $item['is_mine'] && $item['status'] !== 'Booked')
                                                         || (!$isCurriculum && $canManageOwner && $item['is_mine'] && $item['status'] !== 'Booked');
                                            @endphp
                                            <div class="slot-card p-2 mb-1 rounded-3 text-start shadow-sm position-relative border-start border-3 {{ $layerBorder }}"
                                                 data-role="{{ $item['role'] }}"
                                                 style="background-color: {{ $isCurriculum ? '#f0f7ff' : '#fffdf0' }}; font-size: 11px; line-height: 1.3;">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    {!! $roleBadge !!}
                                                    <span class="badge {{ $item['badge_class'] }}" style="font-size: 9px;">
                                                        {{ $item['status_label'] }}
                                                    </span>
                                                </div>

                                                <div class="fw-bold text-dark">
                                                    <i class="fa-regular fa-clock me-1 text-muted"></i>{{ $item['start_time'] }} - {{ $item['end_time'] }}
                                                </div>

                                                {{-- Jika booked / penuh --}}
                                                @if ($item['status'] === 'Booked')
                                                    <div class="mt-1 text-danger fw-semibold">
                                                        <i class="fa-solid fa-user me-1"></i>{{ $item['teacher_name'] ?? 'Guru' }}
                                                    </div>
                                                    @if ($item['topic'])
                                                        <div class="text-muted text-truncate" title="{{ $item['topic'] }}">
                                                            <i class="fa-solid fa-tag me-1"></i>{{ $item['topic'] }}
                                                        </div>
                                                    @endif
                                                @elseif ($item['notes'])
                                                    <div class="text-muted text-truncate mt-1" title="{{ $item['notes'] }}">
                                                        <i class="fa-regular fa-comment-dots me-1"></i>{{ $item['notes'] }}
                                                    </div>
                                                @endif

                                                {{-- Tombol Aksi jika editable (Kurikulum kelola slot Kurikulum, Owner kelola slot Owner) --}}
                                                @if ($editable && isset($editSlotRoute))
                                                    <div class="mt-2 pt-1 border-top d-flex justify-content-end gap-1">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm p-0 px-1" style="font-size: 10px;"
                                                                data-bs-toggle="modal" data-bs-target="#modalEditSlot{{ $item['id'] }}" title="Ubah Slot">
                                                            <i class="fa-solid fa-pencil"></i>
                                                        </button>
                                                        @if (isset($deleteSlotRoute))
                                                            <form action="{{ route($deleteSlotRoute, $item['id']) }}" method="POST" class="d-inline"
                                                                  onsubmit="return confirm('Hapus slot ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm p-0 px-1" style="font-size: 10px;" title="Hapus Slot">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @elseif (!$editable && ($item['role'] === 'Owner' && $userRole === 'Curriculum'))
                                                    <div class="mt-1 text-muted fst-italic" style="font-size: 9px;">
                                                        <i class="fa-solid fa-lock me-1"></i>Read-only (Owner)
                                                    </div>
                                                @elseif (!$editable && ($item['role'] === 'Curriculum' && $userRole === 'Owner'))
                                                    <div class="mt-1 text-muted fst-italic" style="font-size: 9px;">
                                                        <i class="fa-solid fa-lock me-1"></i>Read-only (Kurikulum)
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- MODAL EDIT SLOT --}}
                                            @if ($editable && isset($editSlotRoute))
                                                <div class="modal fade" id="modalEditSlot{{ $item['id'] }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-sm">
                                                        <form action="{{ route($editSlotRoute, $item['id']) }}" method="POST" class="modal-content">
                                                            @csrf @method('PUT')
                                                            <div class="modal-header py-2">
                                                                <h6 class="modal-title fw-bold">Ubah Slot Konsultasi</h6>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body py-2">
                                                                <div class="mb-2">
                                                                    <label class="form-label small fw-semibold">Tanggal</label>
                                                                    <input type="date" name="date" class="form-control form-control-sm"
                                                                           value="{{ $item['slot']->date->toDateString() }}" required>
                                                                </div>
                                                                <div class="row g-1 mb-2">
                                                                    <div class="col-6">
                                                                        <label class="form-label small fw-semibold">Mulai</label>
                                                                        <input type="time" name="start_time" class="form-control form-control-sm"
                                                                               value="{{ $item['start_time'] }}" required>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="form-label small fw-semibold">Selesai</label>
                                                                        <input type="time" name="end_time" class="form-control form-control-sm"
                                                                               value="{{ $item['end_time'] }}" required>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label small fw-semibold">Status Slot</label>
                                                                    <select name="status" class="form-select form-select-sm">
                                                                        <option value="Available" @selected($item['status'] === 'Available')>Tersedia</option>
                                                                        <option value="Cancelled" @selected($item['status'] === 'Cancelled')>Nonaktif (Batal)</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label small fw-semibold">Catatan</label>
                                                                    <textarea name="notes" rows="2" class="form-control form-control-sm">{{ $item['notes'] }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer py-2">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const layerButtons = document.querySelectorAll('.filter-layer-btn');
        layerButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetLayer = this.dataset.layer;
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');

                // Sembunyikan atau tampilkan kartu slot dengan data-role yang sesuai
                const cards = document.querySelectorAll(`.slot-card[data-role="${targetLayer}"]`);
                cards.forEach(card => {
                    card.style.display = isActive ? 'block' : 'none';
                });
            });
        });
    });
</script>
