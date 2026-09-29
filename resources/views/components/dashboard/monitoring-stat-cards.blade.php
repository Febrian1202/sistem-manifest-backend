@props([
    'stats' => [],
])

@php
    $totalComputers = $stats['total_computers'] ?? 0;
    $scannedToday = $stats['scanned_today'] ?? 0;
    $unscannedToday = $stats['unscanned_today'] ?? 0;
    $completedToday = $stats['scans_completed_today'] ?? 0;
    $failedToday = $stats['scans_failed_today'] ?? 0;
    $findings = $stats['actionable_findings'] ?? 0;
    $pctScanned = $totalComputers > 0 ? round(($scannedToday / $totalComputers) * 100) : 0;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    {{-- Card 1: Scan Hari Ini --}}
    <div class="bg-card border border-border p-5 rounded-xl shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Scan Hari Ini</span>
            <div class="p-2.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-lg">
                <i class="fa-solid fa-radar text-lg"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-bold text-foreground">{{ $scannedToday }}</span>
            <span class="text-xs text-muted-foreground font-normal">/ {{ $totalComputers }} unit aktif</span>
        </div>
        <div class="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
            <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: {{ min(100, $pctScanned) }}%"></div>
            </div>
            <span class="font-medium text-emerald-600 dark:text-emerald-400 shrink-0">{{ $pctScanned }}%</span>
        </div>
    </div>

    {{-- Card 2: Belum Scan Hari Ini --}}
    <div class="bg-card border {{ $unscannedToday > 0 ? 'border-amber-200 dark:border-amber-900/50 bg-amber-50/20 dark:bg-amber-950/10' : 'border-border' }} p-5 rounded-xl shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Belum Scan</span>
            <div class="p-2.5 {{ $unscannedToday > 0 ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400' : 'bg-muted text-muted-foreground' }} rounded-lg">
                <i class="fa-solid fa-clock-rotate-left text-lg"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-bold {{ $unscannedToday > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-foreground' }}">{{ $unscannedToday }}</span>
            <span class="text-xs text-muted-foreground font-normal">unit</span>
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            {{ $unscannedToday > 0 ? 'Menunggu siklus scan berkala' : 'Semua unit telah terscan' }}
        </p>
    </div>

    {{-- Card 3: Scan Berhasil Hari Ini --}}
    <div class="bg-card border border-border p-5 rounded-xl shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Scan Berhasil</span>
            <div class="p-2.5 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg">
                <i class="fa-solid fa-circle-check text-lg"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-bold text-foreground">{{ $completedToday }}</span>
            <span class="text-xs text-muted-foreground font-normal">sesi</span>
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            Total sesi scan sukses hari ini
        </p>
    </div>

    {{-- Card 4: Scan Gagal Hari Ini --}}
    <div class="bg-card border {{ $failedToday > 0 ? 'border-red-200 dark:border-red-900/50 bg-red-50/20 dark:bg-red-950/10' : 'border-border' }} p-5 rounded-xl shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Scan Gagal</span>
            <div class="p-2.5 {{ $failedToday > 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' : 'bg-muted text-muted-foreground' }} rounded-lg">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-bold {{ $failedToday > 0 ? 'text-red-600 dark:text-red-400' : 'text-foreground' }}">{{ $failedToday }}</span>
            <span class="text-xs text-muted-foreground font-normal">sesi</span>
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            {{ $failedToday > 0 ? 'Perlu investigasi koneksi/agent' : 'Tidak ada kegagalan scan' }}
        </p>
    </div>

    {{-- Card 5: Temuan Perlu Ditinjau --}}
    <div class="bg-card border {{ $findings > 0 ? 'border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/20 dark:bg-indigo-950/10' : 'border-border' }} p-5 rounded-xl shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Perlu Ditinjau</span>
            <div class="p-2.5 {{ $findings > 0 ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'bg-muted text-muted-foreground' }} rounded-lg">
                <i class="fa-solid fa-shield-halved text-lg"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-bold {{ $findings > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-foreground' }}">{{ $findings }}</span>
            <span class="text-xs text-muted-foreground font-normal">software</span>
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            {{ $findings > 0 ? 'Menunggu verifikasi lisensi' : 'Semua lisensi telah sesuai' }}
        </p>
    </div>
</div>
