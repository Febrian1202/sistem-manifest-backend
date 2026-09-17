<x-layout.app title="Dashboard PJ Lab — {{ $lab->name }}" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
]">
    <div class="space-y-6">

        {{-- Welcome Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card border border-border p-6 rounded-lg shadow-sm">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary mb-2">
                    <i class="fa-solid fa-flask"></i> {{ $lab->code }}
                </div>
                <h1 class="text-2xl font-bold text-foreground">Selamat Datang, {{ auth()->user()->name }}</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Dashboard Penanggung Jawab <strong>{{ $lab->name }}</strong> &bull; {{ $lab->building ?? 'Gedung -' }} Lt. {{ $lab->floor ?? '-' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lab.inventory.index') }}">
                    <x-ui.button variant="outline">
                        <i class="fa-solid fa-desktop mr-2"></i> Inventaris Lab
                    </x-ui.button>
                </a>
                <a href="{{ route('lab.reports.index') }}">
                    <x-ui.button>
                        <i class="fa-solid fa-clipboard-check mr-2"></i> Review Laporan
                        @if($stats['pending_reports'] > 0)
                            <span class="ml-2 px-1.5 py-0.5 rounded-full text-xs bg-amber-500 text-white font-bold">
                                {{ $stats['pending_reports'] }}
                            </span>
                        @endif
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Pending Reports Alert (if any) --}}
        @if ($stats['pending_reports'] > 0)
            <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-bell text-amber-600 text-lg animate-bounce"></i>
                    <div>
                        <div class="font-bold text-amber-900 dark:text-amber-300 text-sm">Ada {{ $stats['pending_reports'] }} Laporan Menunggu Review!</div>
                        <div class="text-amber-800 dark:text-amber-400 text-xs">Admin telah mengirimkan draf laporan kepatuhan yang memerlukan persetujuan Anda.</div>
                    </div>
                </div>
                <a href="{{ route('lab.reports.index') }}">
                    <x-ui.button size="sm" class="bg-amber-600 hover:bg-amber-700 text-white">
                        Tinjau Sekarang
                    </x-ui.button>
                </a>
            </div>
        @endif

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
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Kepatuhan OS</span>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-md">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['compliance_rate'] }}%</div>
                <div class="text-xs text-muted-foreground mt-1">{{ $stats['licensed_os'] }} unit berlisensi resmi</div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Perangkat Lunak</span>
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-md">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['total_software'] }} <span class="text-xs font-normal text-muted-foreground">instalasi</span></div>
            </div>
        </div>

        {{-- 2-Column Grid: Top Software & Recent Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Kolom Kiri: Top Software di Lab Ini --}}
            <div class="bg-card border border-border rounded-lg shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border/50 pb-3">
                    <h2 class="font-bold text-foreground flex items-center gap-2">
                        <i class="fa-solid fa-fire text-orange-500"></i> Software Paling Banyak Terpasang
                    </h2>
                    <span class="text-xs text-muted-foreground">Top 5</span>
                </div>

                <div class="space-y-3">
                    @forelse ($topSoftware as $item)
                        <div class="flex items-center justify-between text-sm py-1">
                            <div class="flex items-center gap-2 truncate pr-2">
                                <i class="fa-regular fa-folder text-muted-foreground text-xs"></i>
                                <span class="font-medium text-foreground truncate">{{ $item['name'] }}</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-secondary text-secondary-foreground">
                                {{ $item['total'] }} unit
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-muted-foreground py-4 text-center">Belum ada data software yang terdeteksi.</p>
                    @endforelse
                </div>
            </div>

            {{-- Kolom Kanan: Status Komputer Terakhir Aktif --}}
            <div class="bg-card border border-border rounded-lg shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border/50 pb-3">
                    <h2 class="font-bold text-foreground flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i> Aktivitas Komputer Terkini
                    </h2>
                    <a href="{{ route('lab.inventory.index') }}" class="text-xs text-primary hover:underline">
                        Lihat Semua
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse ($recentComputers as $comp)
                        <div class="flex items-center justify-between text-sm py-1">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-desktop text-muted-foreground text-xs"></i>
                                <div>
                                    <div class="font-medium text-foreground">{{ $comp->hostname }}</div>
                                    <div class="text-[11px] text-muted-foreground font-mono">{{ $comp->ip_address ?? 'No IP' }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $comp->os_license_status === 'Licensed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                    {{ $comp->os_license_status ?? 'Unknown' }}
                                </span>
                                <div class="text-[10px] text-muted-foreground mt-0.5">{{ $comp->last_seen_at ? $comp->last_seen_at->diffForHumans() : '-' }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-muted-foreground py-4 text-center">Belum ada aktivitas komputer di lab ini.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-layout.app>
