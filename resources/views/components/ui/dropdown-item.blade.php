@props(['href' => null, 'active' => false])

@php
    $classes =
        'relative flex  cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outlinen-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 ' .
        ($active ? 'bg-accent text-accent-foreground' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="submit" {{ $attributes->merge(['class' => 'w-full text-start ' . $classes]) }}>
        {{ $slot }}
    </button>
@endif
