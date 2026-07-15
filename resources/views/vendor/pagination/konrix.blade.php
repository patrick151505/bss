@if ($paginator->hasPages())
<nav class="flex items-center gap-1">

    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="px-3 py-1.5 text-sm rounded border border-gray-200 dark:border-gray-600 text-gray-300 dark:text-gray-600 cursor-not-allowed">
            <i class="mgc_left_line"></i>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"
            class="px-3 py-1.5 text-sm rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition-colors">
            <i class="mgc_left_line"></i>
        </a>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="px-3 py-1.5 text-sm text-gray-400">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="px-3 py-1.5 text-sm rounded border border-primary bg-primary text-white font-medium">{{ $page }}</span>
                @else
                    <a href="{{ $url }}"
                        class="px-3 py-1.5 text-sm rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition-colors">
                        {{ $page }}
                    </a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
            class="px-3 py-1.5 text-sm rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition-colors">
            <i class="mgc_right_line"></i>
        </a>
    @else
        <span class="px-3 py-1.5 text-sm rounded border border-gray-200 dark:border-gray-600 text-gray-300 dark:text-gray-600 cursor-not-allowed">
            <i class="mgc_right_line"></i>
        </span>
    @endif

</nav>
@endif
