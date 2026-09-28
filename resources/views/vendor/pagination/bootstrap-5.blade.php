@if ($paginator->hasPages())
    <nav class="d-flex flex-column align-items-center" aria-label="Pagination">
        @if (method_exists($paginator, 'total'))
            <p class="pagination-summary small text-muted mb-2" aria-live="polite">
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
            </p>
        @else
            @php
                $rangeStart = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;
                $rangeEnd = $rangeStart + $paginator->count() - 1;
            @endphp
            <p class="pagination-summary small text-muted mb-2" aria-live="polite">
                Showing {{ $rangeStart }} to {{ $rangeEnd }} entries
            </p>
        @endif

        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-label="Previous page">«</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">«</a>
                </li>
            @endif

            @if (method_exists($paginator, 'lastPage'))
                @php
                    $currentPage = (int) $paginator->currentPage();
                    $lastPage = (int) $paginator->lastPage();
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($lastPage, $currentPage + 2);
                @endphp

                @if ($startPage > 1)
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
                    @if ($startPage > 2)
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    @if ($page === $currentPage)
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                    @endif
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                    @endif
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>
                @endif
            @endif

            @if ($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">»</a></li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-label="Next page">»</span>
                </li>
            @endif
        </ul>
    </nav>
@endif