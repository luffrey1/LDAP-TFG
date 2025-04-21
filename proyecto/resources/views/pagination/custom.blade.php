@if ($paginator->hasPages())
    <style>
    /* Estilos para la paginación personalizada */
    .pagination-navigation {
        width: 100%;
        display: flex;
        justify-content: center;
        margin: 1.5rem 0;
    }
    
    .pagination-section {
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
        overflow: hidden;
        background-color: #f9fafb;
        padding: 2px;
        display: inline-flex;
    }
    
    .page-item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 2.25rem;
        height: 2.25rem;
        margin: 0 2px;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
        border-radius: 0.375rem;
    }
    
    .page-item.active {
        background-color: #3b82f6;
        color: white;
        font-weight: 600;
    }
    
    .page-item:not(.active) {
        background-color: white;
        border: 1px solid #e5e7eb;
    }
    
    .page-item:not(.active):hover {
        background-color: #f3f4f6;
    }
    
    .page-item svg {
        width: 1.25rem;
        height: 1.25rem;
        vertical-align: middle;
    }
    </style>

    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-navigation">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
            <div>
                <span class="relative z-0 inline-flex pagination-section">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="page-item">
                            <span class="page-link" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="page-item" aria-label="{{ __('pagination.previous') }}">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="page-item">
                                <span class="page-link">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="page-item active">
                                        <span class="page-link">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="page-item" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="page-item" aria-label="{{ __('pagination.next') }}">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="page-item">
                            <span class="page-link" aria-hidden="true">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Versión móvil simplificada --}}
        <div class="flex justify-between sm:hidden">
            <div class="page-number">
                <span class="font-medium">{{ $paginator->currentPage() }}</span> / <span>{{ $paginator->lastPage() }}</span>
            </div>
        </div>
    </nav>
@endif 