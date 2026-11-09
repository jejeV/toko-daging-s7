@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 px-5 py-3 border-t border-line" aria-label="Navigasi halaman">
        <p class="text-xs text-faint num">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn-icon opacity-30" aria-disabled="true"><i class="fa-solid fa-chevron-left text-[11px]"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-icon" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left text-[11px]"></i></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="w-8 text-center text-xs text-faint">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-md bg-ink text-canvas text-xs font-semibold num">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-md text-xs font-medium text-muted hover:bg-sunken hover:text-ink num">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-icon" aria-label="Berikutnya"><i class="fa-solid fa-chevron-right text-[11px]"></i></a>
            @else
                <span class="btn-icon opacity-30" aria-disabled="true"><i class="fa-solid fa-chevron-right text-[11px]"></i></span>
            @endif
        </div>
    </nav>
@endif
