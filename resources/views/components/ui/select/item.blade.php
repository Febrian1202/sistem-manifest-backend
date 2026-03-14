@props(['value', 'label' => null])

@php
    // Jika label tidak diberikan, gunakan teks dari slot (bersihkan tag HTML)
    $displayText = $label ?? strip_tags($slot->toHtml());
@endphp

<div @click="value = '{{ $value }}'; selectedLabel = '{{ $displayText }}'; open = false" {{-- Jika value cocok saat load awal, set label trigger --}}
    x-init="if (value === '{{ $value }}') selectedLabel = '{{ $displayText }}'"
    class="relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground data-disabled:pointer-events-none data-disabled:opacity-50"
    :class="{ 'bg-accent text-accent-foreground': value === '{{ $value }}' }">
    {{-- Ikon Checkmark jika terpilih --}}
    <span x-show="value === '{{ $value }}'" class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
        <i class="fa-solid fa-check h-3 w-3"></i>
    </span>

    {{ $slot }}
</div>
