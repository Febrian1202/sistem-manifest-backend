@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="mx-auto flex w-full justify-center">
        <ul class="flex flex-row items-center gap-1">

            {{-- Previous Page Link --}}
            <li>
                @if ($paginator->onFirstPage())
                    <x-ui.button variant="ghost" class="gap-1 pl-2.5 pointer-events-none opacity-50" aria-disabled="true">
                        <i class="fa-solid fa-chevron-left h-3.5 w-3.5"></i>
                        <span class="sr-only sm:not-sr-only sm:ml-1">Previous</span>
                    </x-ui.button>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <x-ui.button variant="ghost" class="gap-1 pl-2.5">
                            <i class="fa-solid fa-chevron-left h-3.5 w-3.5"></i>
                            <span class="sr-only sm:not-sr-only sm:ml-1">Previous</span>
                        </x-ui.button>
                    </a>
                @endif
            </li>

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li>
                        <span
                            class="flex h-9 w-9 items-center justify-center text-sm font-medium text-muted-foreground">
                            <i class="fa-solid fa-ellipsis h-4 w-4"></i>
                        </span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                {{-- Active State: Variant Outline --}}
                                <x-ui.button variant="outline"
                                    class="h-9 w-9 p-0 pointer-events-none border-primary text-primary hover:bg-background">
                                    {{ $page }}
                                </x-ui.button>
                            @else
                                {{-- Inactive State: Variant Ghost --}}
                                <a href="{{ $url }}">
                                    <x-ui.button variant="ghost" class="h-9 w-9 p-0">
                                        {{ $page }}
                                    </x-ui.button>
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <x-ui.button variant="ghost" class="gap-1 pr-2.5">
                            <span class="sr-only sm:not-sr-only sm:mr-1">Next</span>
                            <i class="fa-solid fa-chevron-right h-3.5 w-3.5"></i>
                        </x-ui.button>
                    </a>
                @else
                    <x-ui.button variant="ghost" class="gap-1 pr-2.5 pointer-events-none opacity-50"
                        aria-disabled="true">
                        <span class="sr-only sm:not-sr-only sm:mr-1">Next</span>
                        <i class="fa-solid fa-chevron-right h-3.5 w-3.5"></i>
                    </x-ui.button>
                @endif
            </li>

        </ul>
    </nav>
@endif
