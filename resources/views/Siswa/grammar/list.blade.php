@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Grammar list</h1>
  <p class="text-sm text-[#6b6652] mt-1">Daftar pola grammar lengkap dengan fungsi, pola, dan contoh kalimat.</p>
@endsection

@push('styles') @include('Siswa.grammar._styles') @endpush

@section('dashboard-content')
<div class="gr-card">
    <div class="gr-box" style="display:flex;align-items:center;gap:16px;">
        <input id="grSearch" type="text" class="gr-input" placeholder="Cari pola grammar…">
        <div class="gr-star">⭐ <b id="grCount">{{ count($learned) }}</b> / {{ $total }}</div>
    </div>
    <p class="gr-hint" id="grHint"></p>
</div>

<div class="gr-table-wrap">
    <table class="gr-table">
        <thead>
        <tr>
            <th style="width:60px">No</th>
            <th>Grammar</th>
            <th>Pinyin</th>
            <th>Fungsi</th>
            <th>Pola</th>
            <th>Contoh Kalimat (Hanzi)</th>
            <th>Arti Indonesia</th>
        </tr>
        </thead>
        <tbody id="grBody">
        @foreach($items as $g)
            <tr class="is-clickable {{ in_array($g['no'], $learned) ? 'is-learned' : '' }}"
                data-no="{{ $g['no'] }}"
                data-search="{{ mb_strtolower($g['hanzi'].' '.$g['arti'].' '.$g['pinyin'].' '.$g['fungsi'].' '.$g['pola']) }}">
                <td>{{ $g['no'] }}</td>
                <td>
                    <span style="font-size:17px">{{ $g['hanzi'] }}</span>
                    <span class="gr-sub">({{ $g['arti'] }})</span>
                </td>
                <td>{{ $g['pinyin'] }}</td>
                <td>{{ $g['fungsi'] }}</td>
                <td>{{ $g['pola'] }}</td>
                <td>
                    <ul class="gr-ul">
                        @foreach($g['contoh'] as $c)<li>{{ $c[0] }}</li>@endforeach
                    </ul>
                </td>
                <td>
                    <ul class="gr-ul">
                        @foreach($g['contoh'] as $c)<li>{{ $c[1] }}</li>@endforeach
                    </ul>
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