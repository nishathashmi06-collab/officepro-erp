@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="op-pagination">
        <div>Showing <strong>{{ $paginator->firstItem() ?? 0 }}</strong>–<strong>{{ $paginator->lastItem() ?? 0 }}</strong> of <strong>{{ $paginator->total() }}</strong></div>
        @if ($paginator->hasPages())
            <nav aria-label="Pagination">
                <ul class="pagination pagination-sm">
                    <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endforeach
                        @endif
                    @endforeach
                    <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        @endif
    </div>
@endif
