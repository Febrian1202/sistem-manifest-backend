@props(['type' => 'default', 'message', 'duration' => 4000])

@php
    // Ikon berdasarkan tipe
    $icon = match ($type) {
        'success' => 'fa-circle-check',
        'error', 'destructive' => 'fa-circle-xmark',
        'warning' => 'fa-triangle-exclamation',
        'info' => 'fa-circle-info',
        default => 'fa-bell',
    };

    // Warna background & border
    $colors = match ($type) {
        'success' => 'border-green-500/50 text-green-700 bg-green-50',
        'error', 'destructive' => 'border-red-500/50 text-red-700 bg-red-50',
        'warning' => 'border-yellow-500/50 text-yellow-700 bg-yellow-50',
        default => 'border-border bg-background text-foreground',
    };
@endphp

<div x-data="{ show: true }" x-init="setTimeout(() => show = false, {{ $duration }})" x-show="show" style="display: none;" x-teleport="body"
    class="fixed bottom-4 right-4 z-100 flex flex-col gap-2 w-full max-w-sm pointer-events-none">

    <div x-show="show" {{-- ANIMASI FADE IN (Masuk) --}} x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100" {{-- ANIMASI FADE OUT (Keluar) --}}
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-md border p-4 shadow-lg pr-8 {{ $colors }}">

        <div class="flex items-center gap-3">
            <i class="fa-solid {{ $icon }} text-lg"></i>
            <p class="text-sm font-medium">
                {{ $message }}
            </p>
        </div>

        {{-- Tombol Close --}}
        <button @click="show = false"
            class="absolute right-2 top-2 rounded-md p-1 opacity-50 transition-opacity hover:opacity-100 focus:outline-none hover:bg-black/5">
            <i class="fa-solid fa-xmark h-4 w-4"></i>
        </button>

    </div>
</div>
