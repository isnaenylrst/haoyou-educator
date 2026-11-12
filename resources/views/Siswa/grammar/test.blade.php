@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Grammar test</h1>
  <p class="text-sm text-[#6b6652] mt-1">Pilih jawaban hanzi yang benar untuk tiap soal.</p>
@endsection

@push('styles') @include('Siswa.grammar._styles') @endpush

@section('dashboard-content')
@php
    $answered = count($answers);
    $labels = ['benar' => 'Benar', 'kurang' => 'Kurang tepat', 'salah' => 'Salah'];
@endphp

<div class="gr-card">
    <h2 style="font-size:22px;font-weight:700;margin:0">Latihan soal grammar</h2>
    <p style="font-size:13px;color:#6b6652;margin:6px 0 0">
        Pilih kalimat hanzi yang paling tepat. Status: hijau = benar, kuning = kurang tepat, merah = salah.
    </p>

    <div class="gr-bar"><i id="trBar" style="width:{{ $total ? ($answered / $total * 100) : 0 }}%"></i></div>

    <div class="gr-box" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="gr-star">⭐ <b id="trCount">{{ $answered }}</b> / {{ $total }} soal</div>
        <div id="trHint" style="font-size:13px;color:#6b6652"></div>
    </div>

    <div style="overflow-x:auto;margin-top:8px;">
        <table class="gr-table" style="min-width:820px;">
            <thead>
            <tr>
                <th style="width:50px">No</th>
                <th style="width:220px">Soal (Bahasa Indonesia)</th>
                <th>Pilihan</th>
                <th style="width:180px">Jawaban</th>
                <th style="width:140px">Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach($questions as $q)
                @php
                    $pick   = $answers[$q['no']] ?? null;
                    $status = $pick !== null ? $q['options'][$pick][1] : null;
                @endphp
                <tr data-no="{{ $q['no'] }}">
                    <td>{{ $q['no'] }}</td>
                    <td>{{ $q['soal'] }}</td>
                    <td>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            @foreach($q['options'] as $i => $opt)
                                <button type="button" class="chip {{ $pick === $i ? 'sel' : '' }}"
                                        data-i="{{ $i }}">{{ $opt[0] }}</button>
                            @endforeach
                        </div>
                    </td>
                    <td class="js-ans" style="font-size:15px">
                        {{ $pick !== null ? $q['options'][$pick][0] : '—' }}
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
                });
            });
        });
    });
})();
</script>
@endsection