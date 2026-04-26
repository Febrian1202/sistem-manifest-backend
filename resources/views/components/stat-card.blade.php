@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'trend' => null,
    'variant' => 'default',
    'progress' => null,
    'description' => null,
    'titleExtra' => null,
])

<x-dashboard.stat-card 
    :title="$title" 
    :value="$value" 
    :subtitle="$subtitle" 
    :icon="$icon" 
    :trend="$trend" 
    :variant="$variant" 
    :progress="$progress" 
    :description="$description"
    {{ $attributes }}
>
    @if(isset($titleExtra) || isset($title_extra))
        <x-slot name="title_extra">
            {!! $titleExtra ?? $title_extra !!}
        </x-slot>
    @endif
</x-dashboard.stat-card>
