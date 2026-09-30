@props(['paginator'])

@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹</a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page === $paginator->currentPage())
                <span aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">›</a>
        @else
            <span aria-disabled="true">›</span>
        @endif
    </nav>
@endif
