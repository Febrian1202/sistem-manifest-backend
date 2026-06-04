@props(['name', 'title' => '', 'maxWidth' => 'lg'])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    x-data="{ show: false }"
    x-show="show"
    x-on:open-dialog.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-dialog.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/80"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <!-- Panel Modal -->
    <div
        x-show="show"
        x-on:click.outside="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidth }} p-6 mx-4 bg-white border rounded-lg shadow-lg dark:bg-zinc-950 dark:border-zinc-800"
    >
        <!-- Tombol Close (X) -->
        <button x-on:click="show = false" class="absolute top-4 right-4 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-50 transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 dark:focus:ring-zinc-300">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
            <span class="sr-only">Close</span>
        </button>

        <!-- Header -->
        @if($title)
            <div class="flex flex-col space-y-1.5 text-center sm:text-left mb-4">
                <h2 class="text-lg font-semibold leading-none tracking-tight text-zinc-950 dark:text-zinc-50">
                    {{ $title }}
                </h2>
            </div>
        @endif

        <!-- Konten Body -->
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $slot }}
        </div>
        
        <!-- Footer (Opsional untuk Action Buttons) -->
        @if(isset($footer))
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 mt-6">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>