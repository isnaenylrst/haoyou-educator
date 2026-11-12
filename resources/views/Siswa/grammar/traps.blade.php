@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Grammar traps</h1>
  <p class="text-sm text-[#6b6652] mt-1">Grammar atau kata yang sering bikin salah walaupun kelihatannya gampang.</p>
@endsection

@push('styles')
@include('Siswa.grammar._styles')
<style>
    .tr-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:20px;margin-top:20px;}
    @media(max-width:520px){.tr-grid{grid-template-columns:1fr;}}
    .tr-title{font-size:18px;font-weight:700;margin-bottom:14px;}
    .tr-title span{color:#9a9580;font-weight:500;font-size:15px;}
    .tr-row{display:flex;gap:16px;padding:12px 0;border-top:1px solid #F3EFDD;font-size:14px;}
    .tr-row b{flex:0 0 78px;color:#5c4500;font-weight:700;}
    .tr-row em{color:#6b6652;}
    .tr-scroll{overflow-x:auto;margin-top:14px;border:1px solid #F3EFDD;border-radius:12px;}
    .tr-tbl{width:100%;border-collapse:collapse;font-size:13px;}
    .tr-tbl th{background:#F8F5E9;color:#9a9580;font-weight:600;padding:10px;text-align:center;
        border-left:1px solid #F3EFDD;white-space:nowrap;}
    .tr-tbl th:first-child{border-left:0}
    .tr-tbl td{padding:10px;text-align:center;border-top:1px solid #F3EFDD;border-left:1px solid #F3EFDD;}
    .tr-tbl td:first-child{text-align:left;font-weight:600;border-left:0;}
    .tr-ok{color:#2F7D4A;font-weight:700;} .tr-no{color:#D93025;font-size:11px;}
</style>
@endpush

@section('dashboard-content')
<div class="gr-card" style="padding:14px 20px;font-size:13.5px;color:#4a4636;border-radius:20px;">
    Kumpulan grammar yang sering bikin salah walaupun kelihatannya gampang — cek tabel cocok/tidaknya di bawah tiap pasangan.
</div>

<div class="tr-grid">
@foreach($traps as $t)
    <div class="gr-card">
        <div class="tr-title">{{ $t['a'] }} <span>vs</span> {{ $t['b'] }}</div>

        @foreach($t['rows'] as $r)
            <div class="tr-row">
                <b>{{ $r[0] }}</b>
                <div>{{ $r[1] }} · <em>{{ $r[2] }}</em> {{ $r[3] }}</div>
            </div>
        @endforeach

        <div class="tr-scroll">
            <table class="tr-tbl">
                <thead>
                <tr>
                    <th></th>
                    @foreach($t['cols'] as $c)<th>{{ $c }}</th>@endforeach
                </tr>
                </thead>
                <tbody>
                @foreach($t['table'] as $label => $cells)
                    <tr>
                        <td>{{ $label }}</td>
                        @foreach($cells as $ok)
                            <td>
                                @if($ok)<span class="tr-ok">✓</span>@else<span class="tr-no">✖</span>@endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
</div>
@endsection