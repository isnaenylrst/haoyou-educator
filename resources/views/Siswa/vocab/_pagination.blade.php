{{-- Pagination bergaya Vocab (tidak bergantung Tailwind). Dipakai: {{ $words->links('Siswa.vocab._pagination') }} --}}
@if ($paginator->hasPages())
<style>
  .vb-pg{display:flex;flex-direction:column;align-items:center;gap:10px;margin:6px 0}
  .vb-pg-info{font-size:13px;color:#6b6652}
  .vb-pg ul{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;justify-content:center;gap:6px}
  .vb-pg li a,.vb-pg li span{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;
    border:1px solid #ece6d0;border-radius:999px;background:#fff;color:#3b3a30;font-size:13px;font-weight:700;text-decoration:none;line-height:1}
  .vb-pg li a:hover{background:#FFF6CC;border-color:#FFE680}
  .vb-pg li.active span{background:#FFE033;border-color:#FFE033;color:#1a1a1a}
  .vb-pg li.disabled span,.vb-pg li.dots span{color:#b9b49c;background:transparent;border-color:transparent}
</style>
<nav class="vb-pg" role="navigation" aria-label="Pagination">
  <div class="vb-pg-info">Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} kata</div>
  <ul>
    @if ($paginator->onFirstPage())
      <li class="disabled"><span>‹ Sebelumnya</span></li>
    @else
      <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Sebelumnya</a></li>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <li class="dots"><span>{{ $element }}</span></li>
      @endif
      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <li class="active"><span aria-current="page">{{ $page }}</span></li>
          @else
            <li><a href="{{ $url }}">{{ $page }}</a></li>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya ›</a></li>
    @else
      <li class="disabled"><span>Berikutnya ›</span></li>
    @endif
  </ul>
</nav>
@endif