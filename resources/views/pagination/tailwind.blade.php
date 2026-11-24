{{-- Localised pagination (no English "Showing X of Y" summary). --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('pagination.navigation') }}" class="num">
        <ul class="flex flex-wrap items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li><span class="inline-flex h-10 min-w-10 items-center justify-center border border-stone-200 bg-white px-3 text-sm text-stone-300" aria-disabled="true">{{ __('pagination.previous') }}</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-10 min-w-10 items-center justify-center border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-700 hover:border-brand-900 hover:text-brand-950">{{ __('pagination.previous') }}</a></li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-flex h-10 min-w-10 items-center justify-center border border-stone-200 bg-white px-3 text-sm text-stone-400">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="inline-flex h-10 min-w-10 items-center justify-center border border-brand-900 bg-brand-900 px-3 text-sm font-bold text-white" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" class="inline-flex h-10 min-w-10 items-center justify-center border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-700 hover:border-brand-900 hover:text-brand-950">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-10 min-w-10 items-center justify-center border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-700 hover:border-brand-900 hover:text-brand-950">{{ __('pagination.next') }}</a></li>
            @else
                <li><span class="inline-flex h-10 min-w-10 items-center justify-center border border-stone-200 bg-white px-3 text-sm text-stone-300" aria-disabled="true">{{ __('pagination.next') }}</span></li>
            @endif
        </ul>
    </nav>
@endif
