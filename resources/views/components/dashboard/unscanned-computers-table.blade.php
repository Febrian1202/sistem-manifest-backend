@props([
    'computers' => collect(),
])

<div class="bg-card border border-border rounded-xl p-5 shadow-sm space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
        <div>
            <h3 class="text-base font-bold text-foreground flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Unit Komputer Belum Scan Hari Ini
            </h3>
            <p class="text-xs text-muted-foreground mt-0.5">Daftar komputer aktif yang belum mengirimkan data scan terbaru pada hari ini.</p>
        </div>
        @if ($computers->isNotEmpty())
            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400">
                {{ $computers->count() }} Komputer Terdaftar
            </span>
        @endif
    </div>

    @if ($computers->isEmpty())
        <div class="p-6 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/40 text-center">
            <div class="inline-flex p-3 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 mb-2">
                <i class="fa-solid fa-circle-check text-2xl"></i>
            </div>
            <div class="text-sm font-bold text-emerald-900 dark:text-emerald-300">Semua Komputer Aktif Sudah Ter-Scan!</div>
            <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">Seluruh unit komputer yang aktif telah menyelesaikan pemindaian manifest hari ini.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-border/80">
            <x-ui.table.table>
                <x-ui.table.table-header>
                    <x-ui.table.table-row>
                        <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                        <x-ui.table.table-head>Hostname</x-ui.table.table-head>
                        <x-ui.table.table-head>Laboratorium</x-ui.table.table-head>
                        <x-ui.table.table-head>IP Address</x-ui.table.table-head>
                        <x-ui.table.table-head>Terakhir Aktif</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Aksi</x-ui.table.table-head>
                    </x-ui.table.table-row>
                </x-ui.table.table-header>
                <x-ui.table.table-body>
                    @foreach ($computers as $index => $computer)
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell class="text-center text-xs text-muted-foreground font-mono">
                                {{ $index + 1 }}
                            </x-ui.table.table-cell>
                            <x-ui.table.table-cell>
                                <div class="font-bold text-sm text-foreground flex items-center gap-1.5">
                                    <i class="fa-solid fa-desktop text-muted-foreground text-xs"></i>
                                    {{ $computer->hostname }}
                                </div>
                                <div class="text-xs text-muted-foreground font-mono">{{ $computer->mac_address ?? '-' }}</div>
                            </x-ui.table.table-cell>
                            <x-ui.table.table-cell>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-muted text-foreground">
                                    {{ $computer->laboratory?->name ?? 'Tanpa Lab' }}
                                </span>
                            </x-ui.table.table-cell>
                            <x-ui.table.table-cell class="text-xs font-mono text-muted-foreground">
                                {{ $computer->ip_address ?? '-' }}
                            </x-ui.table.table-cell>
                            <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                {{ $computer->last_seen_at ? $computer->last_seen_at->diffForHumans() : 'Belum pernah' }}
                            </x-ui.table.table-cell>
                            <x-ui.table.table-cell class="text-center">
                                @if (auth()->user()->hasRole('admin'))
                                    <a href="{{ route('computers.show', $computer) }}" class="inline-flex items-center gap-1 text-xs text-primary hover:underline font-medium">
                                        Detail <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                @elseif (auth()->user()->hasRole('kepala_lab'))
                                    <a href="{{ route('lab.inventory.show', $computer) }}" class="inline-flex items-center gap-1 text-xs text-primary hover:underline font-medium">
                                        Detail <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                @else
                                    <span class="text-xs text-muted-foreground">-</span>
                                @endif
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @endforeach
                </x-ui.table.table-body>
            </x-ui.table.table>
        </div>
    @endif
</div>
