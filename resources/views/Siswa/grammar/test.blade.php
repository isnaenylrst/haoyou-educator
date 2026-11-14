@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Grammar test</h1>
  <p class="text-sm text-[#6b6652] mt-1">Pilih jawaban hanzi yang benar untuk tiap soal.</p>
@endsection

@push('styles') @include('Siswa.grammar._styles') @endpush

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500&display=swap" rel="stylesheet">
<style>
  /* Chip atas */
  .tr-chip{background:#F4F4EE;border:1px solid #ECEADF;border-radius:99px;padding:7px 14px;font-size:13px;color:#4a4636;white-space:nowrap}
  .tr-chip b{color:#1d1a0f}
  .tr-chip--star{background:#FFF8DC;border-color:#F3E2A0;color:#7a5a00}

  /* Tabel */
  .tr-table{min-width:860px;table-layout:fixed}
  .tr-table th{padding:14px 16px;font-weight:600;letter-spacing:.06em;color:#8a8570;background:#FAF7EE}
  .tr-table td{padding:18px 16px;vertical-align:middle;line-height:1.6;color:#2b2818}
  .tr-no{color:#a09b85;font-size:13px;font-variant-numeric:tabular-nums}
  .tr-soal{font-size:14.5px;color:#1d1a0f}
  .tr-pola{display:inline-block;margin-top:8px;font-size:12px;color:#7a5a00;background:#FFF8DC;border:1px solid #F3E2A0;border-radius:99px;padding:2px 10px;white-space:nowrap}

  /* Pilihan jawaban */
  .tr-opts{display:flex;gap:8px;flex-wrap:wrap}
  .chip{
    display:inline-flex;align-items:center;
    border:1px solid #E7E3D3;background:#fff;border-radius:999px;
    padding:8px 18px;
    font-size:15px;font-weight:400;letter-spacing:.02em;line-height:1.3;color:#3a3624;
    font-family:'Noto Sans SC','PingFang SC','Microsoft YaHei',sans-serif;
    cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s}
  .chip:hover{background:#FFFBE6;border-color:#EBD98A}
  .chip.sel{
    background:#FFF3C4;border-color:#E5C85B;color:#1d1a0f;font-weight:500;
    box-shadow:0 1px 4px rgba(229,200,91,.35)}

  /* Jawaban */
  .tr-ans{font-size:15px;font-weight:400;color:#2b2818;
    font-family:'Noto Sans SC','PingFang SC','Microsoft YaHei',sans-serif}
  .tr-empty{color:#cfcbb8}

  /* Status badge */
  .badge{display:inline-block;font-size:11px;font-weight:600;letter-spacing:.05em;
    text-transform:uppercase;padding:6px 12px;border-radius:999px}
  .badge.benar{background:#E3F1E5;color:#2F6B3F}
  .badge.kurang{background:#FBF0BE;color:#7a5a00}
  .badge.salah{background:#FCE4E0;color:#C0392B}
</style>
@endpush

@section('dashboard-content')
@php
    $answered = count($answers);
    $labels   = ['benar' => 'Benar', 'kurang' => 'Kurang tepat', 'salah' => 'Salah'];

    $gs      = \App\Support\GrammarStars::forUser(auth()->id());
    $polaMap = collect(\App\Support\GrammarData::list())->keyBy('no');
@endphp

<div class="gr-card">
    <h2 style="font-size:22px;font-weight:700;margin:0">Latihan soal grammar</h2>
    <p style="font-size:13px;color:#6b6652;margin:6px 0 0">
        Pilih kalimat hanzi yang paling tepat. Status: hijau = benar, kuning = kurang tepat, merah = salah.
        Bintang dihitung per pola grammar dari soal-soal yang benar.
    </p>

    <div class="gr-bar"><i id="trBar" style="width:{{ $total ? ($answered / $total * 100) : 0 }}%"></i></div>

    <div class="gr-box" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <div class="tr-chip">Terjawab <b id="trCount">{{ $answered }}</b> / {{ $total }} soal</div>
            <div class="tr-chip tr-chip--star">⭐ <b id="trStars">{{ $gs['total'] }}</b> / <span id="trStarsMax">{{ $gs['max'] }}</span> bintang</div>
        </div>
        <div id="trHint" style="font-size:13px;color:#6b6652"></div>
    </div>

    <div style="overflow-x:auto;margin-top:8px;">
        <table class="gr-table tr-table">
            <thead>
            <tr>
                <th style="width:56px">No</th>
                <th style="width:250px">Soal (Bahasa Indonesia)</th>
                <th>Pilihan</th>
                <th style="width:190px">Jawaban</th>
                <th style="width:140px">Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach($questions as $q)
                @php
                    $pick   = $answers[$q['no']] ?? null;
                    $status = $pick !== null ? $q['options'][$pick][1] : null;

                    $polaNo    = $q['grammar_no'] ?? null;
                    $polaName  = $polaNo ? ($polaMap[$polaNo]['hanzi'] ?? null) : null;
                    $polaStars = $polaNo ? ($gs['per_pola'][$polaNo] ?? 0) : 0;
                    $polaMax   = $polaNo ? ($gs['max_per_pola'][$polaNo] ?? 0) : 0;
                @endphp
                <tr data-no="{{ $q['no'] }}">
                    <td class="tr-no">{{ $q['no'] }}</td>
                    <td class="tr-soal">
                        {{ $q['soal'] }}
                        @if($polaName)
                            <div>
                                <span class="tr-pola js-pola" data-pola="{{ $polaNo }}" data-max="{{ $polaMax }}">
                                    Pola {{ $polaName }} · ★ <span class="js-pstar">{{ $polaStars }}</span>/{{ $polaMax }}
                                </span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="tr-opts">
                            @foreach($q['options'] as $i => $opt)
                                <button type="button" class="chip {{ $pick === $i ? 'sel' : '' }}"
                                        data-i="{{ $i }}">{{ $opt[0] }}</button>
                            @endforeach
                        </div>
                    </td>
                    <td class="js-ans tr-ans">
                        @if($pick !== null)
                            {{ $q['options'][$pick][0] }}
                        @else
                            <span class="tr-empty">—</span>
                        @endif
                    </td>
                    <td class="js-status">
                        @if($status)
                            <span class="badge {{ $status }}">{{ $labels[$status] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    var total = {{ $total }};
    var labels = {benar:'Benar', kurang:'Kurang tepat', salah:'Salah'};
    var countEl = document.getElementById('trCount');
    var starsEl = document.getElementById('trStars');
    var starsMaxEl = document.getElementById('trStarsMax');
    var bar = document.getElementById('trBar');
    var hint = document.getElementById('trHint');

    function refresh(n){
        countEl.textContent = n;
        bar.style.width = (total ? n / total * 100 : 0) + '%';
        var sisa = total - n;
        hint.textContent = sisa > 0 ? 'Yuk, masih ada ' + sisa + ' soal lagi!'
                                    : 'Mantap! Semua soal sudah dijawab 🎉';
    }
    refresh({{ $answered }});

    document.querySelectorAll('tbody tr[data-no]').forEach(function(row){
        row.querySelectorAll('.chip').forEach(function(chip){
            chip.addEventListener('click', function(){
                fetch("{{ url('student/grammar/test') }}/" + row.dataset.no + "/answer", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({choice: parseInt(chip.dataset.i, 10)})
                })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    row.querySelectorAll('.chip').forEach(function(c){ c.classList.remove('sel'); });
                    chip.classList.add('sel');
                    row.querySelector('.js-ans').textContent = d.text;
                    row.querySelector('.js-status').innerHTML =
                        '<span class="badge ' + d.status + '">' + labels[d.status] + '</span>';
                    refresh(d.answered);

                    if (d.stars_total !== undefined) {
                        starsEl.textContent = d.stars_total;
                        starsMaxEl.textContent = d.stars_max;
                    }
                    if (d.pola_no) {
                        document.querySelectorAll('.js-pola[data-pola="' + d.pola_no + '"] .js-pstar')
                            .forEach(function(el){ el.textContent = d.pola_stars; });
                    }
                });
            });
        });
    });
})();
</script>
@endsection