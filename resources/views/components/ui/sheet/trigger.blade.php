<div @click="open = true" {{ $attributes->merge(['class' => 'inline-block cursor-pointer']) }}>
    {{ $slot }}
</div>
