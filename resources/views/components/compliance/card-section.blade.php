@props(['stats'])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <x-stat-card title="Aplikasi Komersial" value="{{ $stats['total_commercial'] }}" subtitle="Total dipantau sistem"
        icon="fa-solid fa-box" />
    <x-stat-card title="Status Aman" value="{{ $stats['compliant'] }}" subtitle="Lisensi mencukupi"
        icon="fa-solid fa-check" class="border-l-4 border-l-success" />
    <x-stat-card title="Melanggar" value="{{ $stats['non_compliant'] }}" subtitle="Kekurangan lisensi"
        icon="fa-solid fa-triangle-exclamation" variant="critical" class="border-l-4 border-l-destructive" />
    <x-stat-card title="Total Defisit" value="{{ $stats['total_deficit'] }}" subtitle="Estimasi instalasi ilegal"
        icon="fa-solid fa-skull-crossbones" variant="critical" />
</div>