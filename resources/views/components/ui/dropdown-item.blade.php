@props(['href' => null, 'active' => false, 'destructive' => false])

@php
    $baseClasses = 'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50';
    
    $variantClasses = $destructive 
        ? 'text-destructive hover:bg-destructive/10 hover:text-destructive' 
        : 'hover:bg-accent hover:text-accent-foreground';
        
    $activeClasses = $active ? 'bg-accent text-accent-foreground' : '';
    
    $classes = "$baseClasses $variantClasses $activeClasses";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->merge(['class' => 'w-full text-start ' . $classes]) }}>
        {{ $slot }}
    </button>
@endif
