@props(['name', 'value' => '', 'placeholder' => 'Select an option'])

<div x-data="{
    open: false,
    value: '{{ $value }}',
    selectedLabel: '{{ $placeholder }}',
}" class="relative w-full" @click.outside="open = false">
    {{-- Input hidden agar bisa dikirim via Form --}}
    <input type="hidden" name="{{ $name }}" x-model="value">

    {{ $slot }}
</div>
