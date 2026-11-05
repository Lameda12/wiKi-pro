@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        <ul>
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination__item is-disabled" aria-disabled="true">&larr; Previous</span>
                @else
                    <a class="pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Previous</a>
                @endif
            </li>

            @isset($elements)
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="pagination__item is-disabled">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span class="pagination__item is-current" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="pagination__item" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach
            @endisset

            <li>
                @if ($paginator->hasMorePages())
                    <a class="pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
                @else
                    <span class="pagination__item is-disabled" aria-disabled="true">Next &rarr;</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
