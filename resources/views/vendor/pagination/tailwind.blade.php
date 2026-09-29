@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1 rounded-2xl border border-slate-200/80 bg-white p-1 shadow-soft">
            @php $base = 'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-xl px-2 text-xs font-semibold transition'; @endphp

            @if ($paginator->onFirstPage())
                <span class="{{ $base }} text-slate-300" aria-disabled="true"><x-icon name="arrow-left" class="h-4 w-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="{{ __('pagination.previous') }}"><x-icon name="arrow-left" class="h-4 w-4" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $base }} text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $base }} bg-ink-900 text-white shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $base }} hidden text-slate-600 hover:bg-slate-100 hover:text-slate-900 sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="{{ __('pagination.next') }}"><x-icon name="arrow-right" class="h-4 w-4" /></a>
            @else
                <span class="{{ $base }} text-slate-300" aria-disabled="true"><x-icon name="arrow-right" class="h-4 w-4" /></span>
            @endif
        </div>
    </nav>
@endif
