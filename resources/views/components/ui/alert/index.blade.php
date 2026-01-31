@props(['variant' => 'default'])

@php
    $baseClasses =
        'relative w-full rounded-lg border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground [&>i~*]:pl-7 [&>i+div]:translate-y-[-3px] [&>i]:absolute [&>i]:left-4 [&>i]:top-4';

    $variantClasses = match ($variant) {
        'default' => 'bg-background text-foreground',
        'destructive'
            => 'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive [&>i]:text-destructive bg-destructive/10',
        'success'
            => 'border-green-500/50 text-green-700 dark:text-green-400 [&>svg]:text-green-600 [&>i]:text-green-600 bg-green-50',
        'warning'
            => 'border-yellow-500/50 text-yellow-700 dark:text-yellow-400 [&>svg]:text-yellow-600 [&>i]:text-yellow-600 bg-yellow-50',
        default => 'bg-background text-foreground',
    };
@endphp

<div role="alert" {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}>
    {{ $slot }}
</div>
