@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Grammar list</h1>
  <p class="text-sm text-[#6b6652] mt-1">Daftar pola grammar lengkap dengan fungsi, pola, dan contoh kalimat.</p>
@endsection

@push('styles') @include('Siswa.grammar._styles') @endpush

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400&display=swap" rel="stylesheet">
<style>
  .gl-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .gl-top .gr-input{flex:1;min-width:200px}
  .gl-chip{background:#F4F4EE;border:1px solid #ECEADF;border-radius:99px;padding:7px 14px;font-size:13px;color:#4a4636;white-space:nowrap}
  .gl-chip b{color:#1d1a0f}
  .gl-chip--star{background:#FFF8DC;border-color:#F3E2A0;color:#7a5a00}
  .gl-legend{font-size:12.5px;color:#6b6652;margin-top:10px;line-height:1.6}

  /* ===== Table ===== */
  .gl-scroll{overflow-x:auto;border:1px solid #ECEADF;border-radius:14px;background:#fff}
  .gl-table{width:100%;min-width:1100px;table-layout:fixed;border-collapse:separate;border-spacing:0}

  .gl-table thead th{
    position:sticky;top:0;z-index:1;
    padding:14px 16px;text-align:left;vertical-align:middle;
    font-size:11.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;
    color:#8a8570;background:#FAF7EE;border-bottom:1px solid #ECEADF;white-space:nowrap}
  .gl-table thead th.gl-center,
  .gl-table tbody td.gl-center{text-align:center}

  .gl-table tbody td{
    padding:18px 16px;vertical-align:top;text-align:left;
    font-size:14px;line-height:1.6;color:#2b2818;
    border-bottom:1px solid #F3EFDD;
    overflow-wrap:anywhere;word-break:normal}
  .gl-table tbody tr:last-child td{border-bottom:0}
  .gl-table tbody tr:nth-child(even) td{background:#FDFCF8}
  .gl-table tbody tr:hover td{background:#FAF7EE}
  .gl-table tbody tr.is-learned td{background:#F4FAF0}
  .gl-table tbody tr.is-learned:hover td{background:#ECF6E6}

  .gl-no{color:#a09b85;font-size:13px;font-variant-numeric:tabular-nums}

  /* Grammar (hanzi: font normal, tidak bold) */
  .gl-hanzi{
    display:block;font-size:16px;font-weight:400;line-height:1.5;color:#1d1a0f;
    font-family:'Noto Sans SC','PingFang SC','Microsoft YaHei',sans-serif}
  .gl-sub{display:block;font-size:12.5px;font-weight:400;color:#7a7660;margin-top:4px;line-height:1.5}

  /* Pinyin, fungsi, pola */
  .gl-pinyin{color:#6b6652;font-size:13.5px}
  .gl-fungsi{font-size:13.5px;color:#2b2818}
  .gl-pola{font-size:13px;color:#4a4636;line-height:1.7;white-space:pre-line}
  .gl-empty{color:#cfcbb8}

  /* Contoh kalimat */
  .gl-ex + .gl-ex{margin-top:10px;padding-top:10px;border-top:1px dashed #ECEADF}
  .gl-zh{font-size:15px;font-weight:400;color:#1d1a0f;line-height:1.55;
    font-family:'Noto Sans SC','PingFang SC','Microsoft YaHei',sans-serif}
  .gl-id{font-size:12.5px;color:#7a7660;margin-top:2px;line-height:1.5}

  /* Bintang */
  .gl-stars{display:inline-block;white-space:nowrap;letter-spacing:3px;font-size:15px;line-height:1;padding-top:4px}
  .gl-stars .off{color:#E4E1D3}
  .gl-stars .on{color:#E6A700}
</style>
@endpush

@section('dashboard-content')
<div class="gr-card">
    <div class="gr-box gl-top">
        <input id="grSearch" type="text" class="gr-input" placeholder="Cari pola grammar…">
        <div class="gl-chip">Dipelajari <b id="grCount">{{ count($learned) }}</b> / {{ $total }} pola</div>
        <div class="gl-chip gl-chip--star">⭐ <b>{{ $starsTotal }}</b> / {{ $starsMax }} bintang</div>
    </div>
    <p class="gr-hint" id="grHint"></p>
    <p class="gl-legend">
        Bintang per pola dari soal di tab Test:
        ★☆☆ 1 poin · ★★☆ 2 poin · ★★★ 3 poin.
        Jawaban benar = 1 poin, kurang tepat = 0,5 poin. Bintang bertambah otomatis setiap kamu menjawab.
    </p>
</div>

<div class="gr-table-wrap gl-scroll">
    <table class="gr-table gl-table">
        <colgroup>
            <col style="width:56px">
            <col style="width:200px">
            <col style="width:140px">
            <col style="width:160px">
            <col style="width:190px">
            <col style="min-width:300px">
            <col style="width:110px">
        </colgroup>
        <thead>
        <tr>
            <th>No</th>
            <th>Grammar</th>
            <th>Pinyin</th>
            <th>Fungsi</th>
            <th>Pola</th>
            <th>Contoh kalimat</th>
            <th class="gl-center">Progress</th>
        </tr>
        </thead>
        <tbody id="grBody">
        @foreach($items as $g)
            @php
                $s = $stars[$g['no']] ?? 0;

                // Pisahkan hanzi di depan dari teks Indonesia yang ikut masuk ke field hanzi
                $han  = $g['hanzi'];
                $note = $g['arti'] ?? '';
                if (preg_match('/^([\p{Han}\p{P}\s]+?)\s*(\(?[A-Za-z].*)$/us', $han, $m)) {
                    $han  = trim($m[1]);
                    $note = trim($m[2] . ' ' . $note);
                }
            @endphp
            <tr class="is-clickable {{ in_array($g['no'], $learned) ? 'is-learned' : '' }}"
                data-no="{{ $g['no'] }}"
                data-search="{{ mb_strtolower($g['hanzi'].' '.$g['arti'].' '.$g['pinyin'].' '.$g['fungsi'].' '.$g['pola']) }}">
                <td class="gl-no">{{ $g['no'] }}</td>
                <td>
                    <span class="gl-hanzi">{{ $han }}</span>
                    @if($note)<span class="gl-sub">{{ $note }}</span>@endif
                </td>
                <td class="gl-pinyin">{{ $g['pinyin'] }}</td>
                <td class="gl-fungsi">
                    @if(!empty($g['fungsi']))
                        {{ $g['fungsi'] }}
                    @else
                        <span class="gl-empty">—</span>
                    @endif
                </td>
                <td class="gl-pola">
                    @if(!empty($g['pola']))
                        {{ $g['pola'] }}
                    @else
                        <span class="gl-empty">—</span>
                    @endif
                </td>
                <td>
                    @forelse($g['contoh'] ?? [] as $c)
                        <div class="gl-ex">
                            <div class="gl-zh">{{ $c[0] }}</div>
                            <div class="gl-id">{{ $c[1] ?? '' }}</div>
                        </div>
                    @empty
                        <span class="gl-empty">—</span>
                    @endforelse
                </td>
                <td class="gl-center">
                    <span class="gl-stars" title="{{ $s }} dari 3 bintang" aria-label="{{ $s }} dari 3 bintang">
                        @for($i = 1; $i <= 3; $i++)
                            <span class="{{ $i <= $s ? 'on' : 'off' }}">★</span>
                        @endfor
                    </span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
(function(){
    var total = {{ $total }};
    var countEl = document.getElementById('grCount');
    var hint = document.getElementById('grHint');
    var rows = document.querySelectorAll('#grBody tr');

    function setHint(n){
        var sisa = total - n;
        hint.textContent = sisa > 0
            ? sisa + ' pola grammar lagi! Semakin paham, semakin lancar bikin kalimat.'
            : 'Hebat! Semua pola grammar sudah kamu pelajari 🎉';
    }
    setHint(parseInt(countEl.textContent, 10));

    document.getElementById('grSearch').addEventListener('input', function(){
        var q = this.value.trim().toLowerCase();
        rows.forEach(function(r){
            r.style.display = r.dataset.search.indexOf(q) > -1 ? '' : 'none';
        });
    });

    rows.forEach(function(r){
        r.addEventListener('click', function(){
            fetch("{{ url('student/grammar/list') }}/" + r.dataset.no + "/toggle", {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
            })
            .then(function(res){ return res.json(); })
            .then(function(d){
                r.classList.toggle('is-learned', d.on);
                countEl.textContent = d.count;
                setHint(d.count);
            });
        });
    });
})();
</script>
@endsection