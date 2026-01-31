@props(['side' => 'right'])

@php
    // Konfigurasi posisi dan animasi berdasarkan 'side'
    $sideClasses = [
        'top' => 'inset-x-0 top-0 border-b data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top',
        'bottom' =>
            'inset-x-0 bottom-0 border-t data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
        'left' =>
            'inset-y-0 left-0 h-full w-3/4 border-r data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left sm:max-w-sm',
        'right' =>
            'inset-y-0 right-0 h-full w-3/4  border-l data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right sm:max-w-sm',
    ];

    $positionClass = $sideClasses[$side] ?? $sideClasses['right'];

    // Logika Transisi AlpineJS berdasarkan sisi
    $enterStart = match ($side) {
        'left' => '-translate-x-full',
        'top' => '-translate-y-full',
        'bottom' => 'translate-y-full',
        default => 'translate-x-full', // right
    };

    $enterEnd = match ($side) {
        'left', 'right' => 'translate-x-0',
        default => 'translate-y-0',
    };
@endphp

<template x-teleport="body">
    <div x-show="open" style="display: none;">

        {{-- OVERLAY (Backdrop) --}}
        <div x-show="open" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="open = false" class="fixed inset-0 z-50 bg-black/80">
        </div>

        {{-- SHEET PANEL --}}
        <div x-show="open" x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="{{ $enterStart }}" x-transition:enter-end="{{ $enterEnd }}"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="{{ $enterEnd }}" x-transition:leave-end="{{ $enterStart }}"
            {{ $attributes->merge(['class' => 'fixed z-50 gap-4 bg-background p-6 shadow-lg transition ease-in-out data-[state=open]:animate-in data-[state=closed]:animate-out duration-300 ' . $positionClass]) }}>

            {{-- Close Button --}}
            <button @click="open = false"
                class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-secondary">
                <i class="fa-solid fa-xmark h-4 w-4"></i>
                <span class="sr-only">Close</span>
            </button>

            {{ $slot }}
        </div>

    </div>
</template>
