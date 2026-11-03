{{-- Dropdown bab. Butuh: $chapters (collection VocabChapter), $bab (nomor bab terpilih / null) --}}
@if($chapters->count())
<label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;white-space:nowrap">
  Bab
  <select class="vb-input" style="width:auto;min-width:190px;cursor:pointer" onchange="location.href=this.value" aria-label="Pilih bab">
    <option value="{{ request()->fullUrlWithQuery(['bab' => null, 'page' => null]) }}">Semua bab</option>
    @foreach($chapters as $c)
      <option value="{{ request()->fullUrlWithQuery(['bab' => $c->number, 'page' => null]) }}" @selected($bab === $c->number)>Bab {{ $c->number }} ({{ $c->words_count }} kata)</option>
    @endforeach
  </select>
</label>
@endif