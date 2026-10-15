@if($paginator->hasPages())
<nav aria-label="{{ $label }} pagination">
    <ul class="pagination">
        @if($paginator->onFirstPage())
        <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-label="Previous page">@icon('chevron-left', ['mr' => 0])</span></li>
        @else
        <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">@icon('chevron-left', ['mr' => 0])</a></li>
        @endif
        @foreach($elements as $element)
            @if(is_string($element))
            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
            @else
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                    <li class="page-item"><a class="page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())
        <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">@icon('chevron-right', ['mr' => 0])</a></li>
        @else
        <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-label="Next page">@icon('chevron-right', ['mr' => 0])</span></li>
        @endif
    </ul>
</nav>
@endif
