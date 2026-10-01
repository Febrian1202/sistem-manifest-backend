@props([
    'href',
    'icon',
    'label',
    'active' => null,
])

@php
    $activeClass = 'bg-sidebar-accent text-sidebar-primary font-medium';
    $inactiveClass = 'text-sidebar-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground';
    $isActive = $active ?? (request()->url() === $href || request()->fullUrlIs($href . '*'));
@endphp

<a href="{{ $href }}"
    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group {{ $isActive ? $activeClass : $inactiveClass }}">
    <div class="w-6 flex justify-center">
        <i class="fa-solid {{ $icon }} text-lg"></i>
    </div>
    <span class="font-medium whitespace-nowrap transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100 block' : 'opacity-0 hidden'">{{ $label }}</span>

    <div class="absolute left-16 bg-popover text-popover-foreground border border-border text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50 md:hidden"
        :class="!sidebarOpen ? 'md:block' : ''">
        {{ $label }}
    </div>
</a>
