{{--
    Kalender mingguan ketersediaan guru (baris = jam, kolom = hari).
    Dipakai di: Kurikulum > Ketersediaan Guru dan Guru > Schedule.

    Variabel:
      $grid               hasil TeacherAvailabilityService::weeklyGrid()
      $highlightTeacherId (opsional) id guru yang sedang login, ditandai "(Anda)"
      $selectable         (opsional) true = klik kotak jam mengirim event "avail:cell"
--}}
@php
    $highlightTeacherId = $highlightTeacherId ?? null;
    $selectable         = $selectable ?? false;
    $hours  = $grid['hours'];
    $days   = $grid['days'];
    $cells  = $grid['cells'];
    $legend = $grid['teachers'];
@endphp

<div class="av-grid" id="avGrid">

    @if ($grid['total'] === 0)

        <div class="text-center text-muted py-4">
            Belum ada guru yang mengisi ketersediaan.
        </div>

    @else

        {{-- Legenda warna + filter per guru --}}
        <div class="av-legend">
            <button type="button" class="av-pill active" data-teacher="all">Semua guru</button>

            @foreach ($legend as $lg)
                <button type="button"
                        class="av-pill tc{{ $lg['color'] }}"
                        data-teacher="{{ $lg['id'] }}"
                        title="{{ $lg['slots'] }} slot ketersediaan">
                    <span class="av-dot"></span>{{ $lg['name'] }}@if ((int) $lg['id'] === (int) $highlightTeacherId) <em>(Anda)</em>@endif
                </button>
            @endforeach
        </div>

        <div class="av-wrap">
            <table class="av-table">
                <thead>
                    <tr>
                        <th class="av-time">Jam</th>
                        @foreach ($days as $d)
                            <th>{{ $d }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($hours as $h)
                        <tr>
                            <td class="av-time">{{ sprintf('%02d:00', $h) }}</td>

                            @foreach ($days as $d)
                                @php $list = $cells[$h][$d] ?? []; @endphp

                                <td class="av-cell {{ count($list) ? 'has' : '' }} {{ $selectable ? 'sel' : '' }}"
                                    style="--n: {{ min(count($list), 6) }}"
                                    @if ($selectable) data-day="{{ $d }}" data-hour="{{ $h }}" @endif>

                                    @foreach ($list as $c)
                                        <div class="av-chip tc{{ $c['color'] }} {{ (int) $c['teacher_id'] === (int) $highlightTeacherId ? 'mine' : '' }}"
                                             data-teacher="{{ $c['teacher_id'] }}"
                                             title="{{ $c['name'] }} · {{ $c['start'] }}–{{ $c['end'] }}{{ $c['note'] ? ' · ' . $c['note'] : '' }}">
                                            <span class="n">{{ $c['name'] }}</span>
                                            @if ($c['partial'])
                                                <small>{{ $c['start'] }}–{{ $c['end'] }}</small>
                                            @endif
                                        </div>
                                    @endforeach

                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="av-note">
            Warna kotak makin pekat = makin banyak guru tersedia pada jam itu. Arahkan kursor ke nama untuk melihat jam lengkap dan catatan.
            @if ($selectable) Klik kotak jam untuk mengisi form tambah ketersediaan. @endif
        </div>

    @endif

</div>

@push('styles')
<style>
    .av-legend { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
    .av-pill {
        display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid var(--line, #ececf2); background: var(--surface, #fff); color: var(--text, #1d1d27);
        border-radius: 99px; padding: 4px 11px; font-size: 12px; font-weight: 600; cursor: pointer;
    }
    .av-pill em { font-style: normal; font-weight: 500; opacity: .7; }
    .av-pill:hover { background: var(--accent-soft, #fff7cc); }
    .av-pill.active { background: var(--accent, #ffd600); border-color: var(--accent, #ffd600); color: #1d1d27; }
    .av-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--c, #999); }

    .av-wrap { overflow-x: auto; }
    .av-table { width: 100%; min-width: 900px; border-collapse: collapse; }
    .av-table th, .av-table td { border: 1px solid var(--line, #ececf2); padding: 5px; vertical-align: top; }
    .av-table th { font-size: 11px; text-transform: uppercase; text-align: center; color: var(--muted, #8b8fa3); letter-spacing: .05em; }
    .av-time { width: 64px; font-weight: 700; font-size: 12.5px; text-align: center; white-space: nowrap; }
    .av-cell { min-height: 38px; height: 38px; min-width: 120px; }
    .av-cell.has { background: rgba(255, 214, 0, calc(var(--n, 0) * .05)); }
    .av-cell.sel { cursor: pointer; }
    .av-cell.sel:hover { outline: 2px solid var(--accent, #ffd600); outline-offset: -2px; }

    .av-chip {
        border-left: 3px solid var(--c, #999); background: var(--bgc, #eee);
        border-radius: 5px; padding: 2px 7px; margin-bottom: 3px; font-size: 11.5px; line-height: 1.3; color: #222;
    }
    .av-chip .n { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; font-weight: 600; }
    .av-chip small { display: block; font-size: 10.5px; opacity: .75; }
    .av-chip.mine { box-shadow: 0 0 0 1.5px #111; }
    .av-note { font-size: 12px; color: var(--muted, #8b8fa3); margin-top: 10px; }

    /* 8 warna guru (berdasarkan id guru) */
    .tc0 { --c: #e8a317; --bgc: #fdf0cf; }
    .tc1 { --c: #3b82f6; --bgc: #dfeafb; }
    .tc2 { --c: #8b5cf6; --bgc: #e8e0fb; }
    .tc3 { --c: #ef4444; --bgc: #fbe3e3; }
    .tc4 { --c: #22a05a; --bgc: #dcf3e2; }
    .tc5 { --c: #ec4899; --bgc: #fbe0ee; }
    .tc6 { --c: #0ea5b7; --bgc: #d9f3f6; }
    .tc7 { --c: #7c8a1a; --bgc: #eef0cf; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const root = document.getElementById('avGrid');
    if (!root) return;

    const pills = [...root.querySelectorAll('.av-pill')];
    const chips = [...root.querySelectorAll('.av-chip')];
    const cells = [...root.querySelectorAll('.av-cell')];

    function activeTeacher() {
        const p = pills.find(x => x.classList.contains('active'));
        return p ? p.dataset.teacher : 'all';
    }

    function apply(id) {
        pills.forEach(p => p.classList.toggle('active', p.dataset.teacher === id));
        chips.forEach(c => { c.style.display = (id === 'all' || c.dataset.teacher === id) ? '' : 'none'; });
        cells.forEach(td => {
            const n = [...td.querySelectorAll('.av-chip')].filter(c => c.style.display !== 'none').length;
            td.style.setProperty('--n', Math.min(n, 6));
            td.classList.toggle('has', n > 0);
        });
    }

    pills.forEach(p => p.addEventListener('click', () => apply(p.dataset.teacher)));

    // klik kotak jam -> kirim event ke halaman (dipakai Kurikulum untuk mengisi form)
    root.querySelectorAll('.av-cell[data-day]').forEach(td => {
        td.addEventListener('click', () => {
            root.dispatchEvent(new CustomEvent('avail:cell', {
                bubbles: true,
                detail: { day: td.dataset.day, hour: parseInt(td.dataset.hour, 10), teacher: activeTeacher() }
            }));
        });
    });
})();
</script>
@endpush