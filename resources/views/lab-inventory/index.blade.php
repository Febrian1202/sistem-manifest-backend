<x-layout.app title="Inventaris Lab — {{ $lab->name }}" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Inventaris Lab', 'url' => null]
]">
    <div class="space-y-6">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary mb-2">
                    <i class="fa-solid fa-flask"></i> {{ $lab->code }}
                </div>
                <h1 class="text-2xl font-bold text-foreground">{{ $lab->name }}</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ $lab->building ?? 'Gedung -' }} Lt. {{ $lab->floor ?? '-' }} &bull; {{ $lab->description ?? 'Daftar inventaris komputer dan software terdeteksi.' }}
                </p>
            </div>
            <div>
                <a href="{{ route('lab.reports.index') }}">
                    <x-ui.button variant="outline">
                        <i class="fa-solid fa-clipboard-check mr-2"></i> Review Laporan
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Komputer</span>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-md">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['total_computers'] }} <span class="text-xs font-normal text-muted-foreground">unit</span></div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Scan Bulan Ini</span>
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-md">
                        <i class="fa-solid fa-radar"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['scanned_this_month'] }} <span class="text-xs font-normal text-muted-foreground">/ {{ $stats['total_computers'] }} unit</span></div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">OS Berlisensi</span>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-md">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['licensed_os'] }} <span class="text-xs font-normal text-muted-foreground">unit</span></div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Tingkat Kepatuhan OS</span>
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-md">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['compliance_rate'] }}%</div>
            </div>
        </div>

        {{-- Filter & Pencarian --}}
        <form method="GET" action="{{ route('lab.inventory.index') }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col sm:flex-row gap-4 items-center">
            
            <div class="w-full relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-form.input name="search" value="{{ request('search') }}"
                    placeholder="Cari hostname, IP, atau versi OS..." class="pl-9 w-full" />
            </div>

            <div class="w-full sm:w-48">
                <select name="license_status" onchange="this.form.submit()"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="All" {{ request('license_status') == 'All' ? 'selected' : '' }}>Semua Status OS</option>
                    <option value="Licensed" {{ request('license_status') == 'Licensed' ? 'selected' : '' }}>Licensed</option>
                    <option value="Grace Period" {{ request('license_status') == 'Grace Period' ? 'selected' : '' }}>Grace Period</option>
                    <option value="Unlicensed" {{ request('license_status') == 'Unlicensed' ? 'selected' : '' }}>Unlicensed</option>
                    <option value="Notification" {{ request('license_status') == 'Notification' ? 'selected' : '' }}>Notification</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 w-full sm:w-auto">
                <x-ui.button type="submit">
                    <i class="fa-solid fa-search mr-2"></i> Cari
                </x-ui.button>
                @if(request('search') || (request('license_status') && request('license_status') !== 'All'))
                    <a href="{{ route('lab.inventory.index') }}">
                        <x-ui.button type="button" variant="outline" title="Reset">
                            <i class="fa-solid fa-xmark"></i>
                        </x-ui.button>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Komputer --}}
        <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
            <x-ui.table.table>
                <x-ui.table.table-header>
                    <x-ui.table.table-row>
                        <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                        <x-ui.table.table-head>Hostname</x-ui.table.table-head>
                        <x-ui.table.table-head>Sistem Operasi</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Status Lisensi OS</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Total Software</x-ui.table.table-head>
                        <x-ui.table.table-head>Terakhir Aktif</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-right pr-6">Aksi</x-ui.table.table-head>
                    </x-ui.table.table-row>
                </x-ui.table.table-header>
                <x-ui.table.table-body>
                    @forelse ($computers as $index => $computer)
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell class="text-center font-medium">
                                {{ $computers->firstItem() + $index }}
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell>
                                <div class="font-semibold text-foreground flex items-center gap-2">
                                    <i class="fa-solid fa-desktop text-muted-foreground"></i>
                                    {{ $computer->hostname }}
                                </div>
                                <div class="text-xs text-muted-foreground font-mono">{{ $computer->ip_address ?? 'No IP' }}</div>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell>
                                <div class="font-medium text-foreground">{{ $computer->os_name ?? '-' }}</div>
                                <div class="text-xs text-muted-foreground">{{ $computer->os_version ?? '' }} ({{ $computer->os_architecture ?? '' }})</div>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-center">
                                @php
                                    $osStatus = $computer->os_license_status;
                                    $badgeClass = match($osStatus) {
                                        'Licensed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'Grace Period' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                        'Unlicensed', 'Notification' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                        default => 'bg-secondary text-secondary-foreground',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                    {{ $osStatus ?? 'Unknown' }}
                                </span>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-center font-medium">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs bg-muted">
                                    <i class="fa-solid fa-cube text-muted-foreground"></i>
                                    {{ $computer->softwares_count }}
                                </span>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell>
                                <div class="text-xs text-foreground">{{ $computer->last_seen_at ? $computer->last_seen_at->format('d/m/Y H:i') : '-' }}</div>
                                <div class="text-[11px] text-muted-foreground">{{ $computer->last_seen_at ? $computer->last_seen_at->diffForHumans() : '' }}</div>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-right pr-6">
                                <a href="{{ route('lab.inventory.show', $computer) }}">
                                    <x-ui.button variant="outline" size="sm">
                                        <i class="fa-solid fa-eye mr-1.5"></i> Detail
                                    </x-ui.button>
                                </a>
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @empty
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell colspan="7" class="text-center py-8 text-muted-foreground">
                                Tidak ada data komputer ditemukan di laboratorium ini.
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @endforelse
                </x-ui.table.table-body>
            </x-ui.table.table>
        </div>

        {{-- Pagination --}}
        @if ($computers->hasPages())
            <div class="mt-4">
                {{ $computers->links() }}
            </div>
        @endif

    </div>
</x-layout.app>
