{{-- Footer tabel + pagination (markup sama seperti halaman Siswa/Kelas).
     Pakai: @include('admin.partials.pagination', ['paginator' => $items, 'noun' => 'jurnal']) --}}
<div class="table-footer">
    <span class="table-footer-info">
        Menampilkan
        <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
        -
        <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
        dari
        <strong>{{ $paginator->total() }}</strong>
        {{ $noun }}
    </span>

    <div class="table-footer-pagination">
        @if ($paginator->hasPages())
            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();
                $onEachSide = 1;
            @endphp

            <nav class="pagination-nav" aria-label="Pagination">
                <ul class="pagination-list">
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-left"></i></span></li>
                    @else
                        <li class="page-item"><a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a></li>
                    @endif

                    @for ($page = 1; $page <= $last; $page++)
                        @php
                            $isEdge = $page == 1 || $page == $last;
                            $isNearCurrent = abs($page - $current) <= $onEachSide;
                        @endphp

                        @if ($isEdge || $isNearCurrent)
                            @if ($page == $current)
                                <li class="page-item active"><span class="page-btn current">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-btn" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                            @endif
                        @elseif ($page == 2 && $current - $onEachSide > 2)
                            <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                        @elseif ($page == $last - 1 && $current + $onEachSide < $last - 1)
                            <li class="page-item disabled"><span class="page-btn dots">...</span></li>
                        @endif
                    @endfor

                    @if ($paginator->hasMorePages())
                        <li class="page-item"><a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a></li>
                    @else
                        <li class="page-item disabled"><span class="page-btn"><i class="fa-solid fa-chevron-right"></i></span></li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
</div>
