{{--
    Riwayat pengajuan (dipakai Kurikulum & Guru).
    Variabel: $filters, $rows (paginator), $counts, $typeCounts, $total, $teachers, $types, $groups, $showTeacherFilter, $actionUrl
--}}
@php
    $fmt      = fn ($d, $f = 'd M Y') => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat($f) : null;
    $badge    = ['menunggu' => 'warning text-dark', 'proses' => 'info text-dark', 'selesai' => 'success', 'dikembalikan' => 'danger'];
    $cardIcon = ['menunggu' => 'fa-hourglass-half', 'proses' => 'fa-signature', 'selesai' => 'fa-circle-check', 'dikembalikan' => 'fa-rotate-left'];
    $link     = fn (array $extra) => request()->fullUrlWithQuery(array_merge(['page' => null], $extra));
@endphp

<style>
    .rw-card { border: 1px solid #eee; border-radius: 14px; padding: 16px 18px; margin-bottom: 12px; background: #fff; }
    .rw-step { display: inline-flex; align-items: center; gap: 6px; border-radius: 20px; padding: 4px 11px; font-size: 12px; font-weight: 600; margin: 2px 4px 2px 0; }
    .rw-step.done { background: #E5F6EA; color: #1E7B3C; }
    .rw-step.todo { background: #f1f1f1; color: #888; }
    .rw-step small { font-weight: 500; opacity: .85; }
    .rw-note { background: #FBF7EE; border-radius: 10px; padding: 8px 12px; font-size: 13px; color: #555; margin-top: 8px; }
    .rw-sum { border-radius: 14px; padding: 12px 16px; border: 1px solid #eee; background: #fff; display: block; color: inherit; text-decoration: none; }
    .rw-sum.active { border-color: #111; box-shadow: 0 0 0 1px #111 inset; }
    .rw-type { border-radius: 20px; padding: 5px 13px; font-size: 13px; font-weight: 600; background: #f1f1f1; color: #555; text-decoration: none; display: inline-block; margin: 0 6px 6px 0; }
    .rw-type.active { background: #111; color: #fff; }
</style>

{{-- Ringkasan per status --}}
<div class="row g-2 mb-3">
    @foreach ($groups as $key => $label)
        <div class="col-6 col-md-3">
            <a href="{{ $link(['status' => $filters['status'] === $key ? null : $key]) }}" class="rw-sum {{ $filters['status'] === $key ? 'active' : '' }}">
                <div class="small text-muted"><i class="fa-solid {{ $cardIcon[$key] }} me-1"></i>{{ $label }}</div>
                <div class="fs-4 fw-bold">{{ $counts[$key] ?? 0 }}</div>
            </a>
        </div>
    @endforeach
</div>

{{-- Jenis pengajuan --}}
<div class="mb-2">
    <a href="{{ $link(['jenis' => null, 'jurnal_late' => null]) }}" class="rw-type {{ $filters['jenis'] ? '' : 'active' }}">
        Semua ({{ array_sum($typeCounts) }})
    </a>
    @foreach ($types as $key => $t)
        <a href="{{ $link(['jenis' => $key, 'jurnal_late' => null]) }}" class="rw-type {{ $filters['jenis'] === $key ? 'active' : '' }}">
            <i class="fa-solid {{ $t['icon'] }} me-1"></i>{{ $t['label'] }} ({{ $typeCounts[$key] ?? 0 }})
        </a>
    @endforeach
</div>

{{-- Filter --}}
<form method="GET" action="{{ $actionUrl }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <input type="hidden" name="jenis" value="{{ $filters['jenis'] }}">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div class="row g-2 align-items-end">
            @if ($showTeacherFilter)
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Guru (Laoshi)</label>
                    <select name="guru" class="form-select form-select-sm">
                        <option value="">Semua Guru</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" {{ (int) $filters['teacher'] === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Bulan</label>
                <input type="month" name="bulan" id="rwBulan" value="{{ $filters['month'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                <input type="date" name="dari" id="rwDari" value="{{ $filters['month'] ? '' : optional($filters['from'])->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai" id="rwSampai" value="{{ $filters['month'] ? '' : optional($filters['to'])->toDateString() }}" class="form-control form-control-sm">
            </div>
            @if ($filters['jenis'] === 'jurnal')
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Ketepatan Jurnal</label>
                    <select name="jurnal_late" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="1" {{ $filters['late'] === '1' ? 'selected' : '' }}>Terlambat Mengisi</option>
                        <option value="0" {{ $filters['late'] === '0' ? 'selected' : '' }}>Tepat Waktu</option>
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-fill"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
                <a href="{{ $actionUrl }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
        <div class="small text-muted mt-2">
            Tanggal yang difilter = tanggal pengajuan (Progress Report = tanggal dikirim). Isi <b>Bulan</b> <u>atau</u> rentang tanggal.
        </div>
    </div>
</form>

{{-- Daftar riwayat --}}
@forelse ($rows as $row)
    @php $group = $row['group']; @endphp
    <div class="rw-card">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div class="me-2">
                <div class="small text-muted mb-1">
                    <i class="fa-solid {{ $row['icon'] }} me-1"></i>{{ $row['type_label'] }}
                    @if ($showTeacherFilter) · <b>{{ $row['teacher'] }}</b> @endif
                    · diajukan {{ $fmt($row['date'], 'd M Y, H:i') }}
                </div>
                <div class="fw-bold">
                    {{ $row['title'] }}
                    @if ($row['is_late'])
                        <span class="badge bg-danger-subtle text-danger ms-1">Terlambat Mengisi</span>
                    @endif
                </div>
                @if ($row['subtitle'])
                    <div class="small text-muted">{{ $row['subtitle'] }}</div>
                @endif
            </div>
            <div class="text-end">
                <span class="badge bg-{{ $badge[$group] }}">{{ $row['status_label'] }}</span>
                @if ($row['url'])
                    <div class="mt-2">
                        <a href="{{ $row['url'] }}" target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="fa-solid fa-file-lines me-1"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-2">
            @foreach ($row['steps'] as $step)
                <span class="rw-step {{ $step['done'] ? 'done' : 'todo' }}">
                    <i class="fa-solid {{ $step['done'] ? 'fa-circle-check' : 'fa-circle' }}"></i>
                    {{ $step['label'] }}
                    @if ($step['done'] && $step['at'])
                        <small>{{ $fmt($step['at']) }}{{ $step['by'] ? ' · ' . $step['by'] : '' }}</small>
                    @endif
                </span>
            @endforeach
        </div>

        @if ($row['note'])
            <div class="rw-note"><b>Catatan:</b> {{ $row['note'] }}</div>
        @endif
    </div>
@empty
    <div class="text-center text-muted py-5">
        <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
        <div>Belum ada riwayat pengajuan{{ ($filters['jenis'] || $filters['status'] || $filters['teacher'] || $filters['from'] || $filters['to']) ? ' untuk filter ini' : '' }}.</div>
    </div>
@endforelse

<div class="mt-2">{{ $rows->links() }}</div>

<script>
(function () {
    var bulan = document.getElementById('rwBulan'), dari = document.getElementById('rwDari'), sampai = document.getElementById('rwSampai');
    if (!bulan) { return; }
    bulan.addEventListener('change', function () { if (bulan.value) { dari.value = ''; sampai.value = ''; } });
    [dari, sampai].forEach(function (el) { el.addEventListener('change', function () { if (el.value) { bulan.value = ''; } }); });
})();
</script>
