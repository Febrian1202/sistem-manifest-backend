<div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
    class="absolute z-50 mt-1 max-h-60 w-full min-w-32 overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md"
    style="display: none;">
    <div class="p-1">
        {{ $slot }}
    </div>
</div>
