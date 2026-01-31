@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'trend' => null,
    'variant' => 'default',
    'progress' => null,
])

@php
    // Logic variant
    $isCritical = $variant === 'critical';

    // Style continer
    $containerClasses = 'rounded-xl p-6 border transition-all duration-200 hover:shadow-lg ';
    $containerClasses .= $isCritical ? 'bg-red-50 border-red-200' : 'bg-card border-border shadow-sm';

    // text color
    $titleColor = $isCritical ? 'text-red-600' : 'text-muted-foreground';
    $valueColor = $isCritical ? 'text-red-700' : 'text-card-foreground';

    // icon container style
    $iconWrapperClasses = 'h-12 w-12 rounded-lg flex items-center justify-center text-xl ';
    $iconWrapperClasses .= $isCritical ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary';

    // logika progress bar
    $progressColor = 'bg-red-500';
    if ($progress !== null) {
        if ($progress >= 90) {
            $progressColor = 'bg-green-500';
        } elseif ($progress >= 70) {
            $progressColor = 'bg-yellow-500';
        }
    }
@endphp

<div class="{{ $containerClasses }}">
    <!-- It is never too late to be what you might have been. - George Eliot -->
    <div class="flex items-start justify-between">

        {{-- Kiri --}}
        <div class="flex-1">
            <p class="text-sm font-medium {{ $titleColor }}">
                {{ $title }}
            </p>

            <h3 class="text-2xl font-bold mt-2 {{ $valueColor }}">
                {{ $value }}
            </h3>

            @if ($subtitle)
                <p class="text-sm text-muted-foreground mt-1">{{ $subtitle }}</p>
            @endif

            {{-- Trend indikator --}}
            @if ($trend)
                <div class="flex items-center mt-2 gap-1 text-sm font-medium">
                    <span class="{{ $trend['positive'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $trend['positive'] ? '↑' : '↓' }} {{ abs($trend['value']) }}</span>
                    <span class="text-muted-foreground font-normal">vs bulan lalu</span>
                </div>
            @endif

            {{-- progress bar --}}
            @if ($progress !== null)
                <div class="mt-4 pr-4 ">
                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $progressColor }} rounded-full transition-all duration-500"
                            style="width: {{ $progress }}%">

                        </div>
                    </div>
                </div>
            @endif

        </div>
        @if ($icon)
            <div class="{{ $iconWrapperClasses }}">
                <i class="{{ $icon }}"></i>
            </div>
        @endif
    </div>
</div>
