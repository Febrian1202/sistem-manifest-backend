<x-layout.app title="Detail Komputer — {{ $computer->hostname }}" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Inventaris Lab', 'url' => route('lab.inventory.index')],
    ['name' => 'Detail ' . $computer->hostname, 'url' => null]
]">
    <div class="space-y-6 pb-10">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                        <i class="fa-solid fa-flask"></i> {{ $computer->laboratory->name ?? 'Lab' }}
                    </span>
                    <span class="text-xs text-muted-foreground">&bull;</span>
                    <span class="text-xs text-muted-foreground font-mono">{{ $computer->ip_address ?? 'No IP' }}</span>
                </div>
                <h1 class="text-2xl font-bold text-foreground">{{ $computer->hostname }}</h1>
                <p class="text-muted-foreground mt-0.5 text-sm">
                    Detail perangkat keras, sistem operasi, dan perangkat lunak terdeteksi (Read-only PJ Lab).
                </p>
            </div>

            <div>
                <a href="{{ route('lab.inventory.index') }}">
                    <x-ui.button variant="outline">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Inventaris
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Info Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- CARD: Sistem Operasi --}}
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-border/50 bg-muted/20 flex items-center gap-2">
                    <i class="fa-brands fa-windows text-primary text-lg"></i>
                    <h3 class="font-semibold text-foreground">Sistem Operasi</h3>
                </div>
                <div class="p-5 flex-1 flex flex-col gap-3 text-sm">
                    <div>
                        <p class="text-muted-foreground text-xs uppercase mb-1">Nama & Arsitektur OS</p>
                        <p class="font-medium text-foreground">{{ $computer->os_name ?? '-' }}</p>
                        <p class="text-xs text-muted-foreground">{{ $computer->os_version }} ({{ $computer->os_architecture }})</p>
                    </div>
                    <div class="pt-3 border-t border-border/50">
                        <p class="text-muted-foreground text-xs uppercase mb-1">Status Lisensi OS</p>
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
                    </div>
                </div>
            </div>

            {{-- CARD: Spesifikasi Hardware --}}
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-border/50 bg-muted/20 flex items-center gap-2">
                    <i class="fa-solid fa-microchip text-primary text-lg"></i>
                    <h3 class="font-semibold text-foreground">Spesifikasi Perangkat Keras</h3>
                </div>
                <div class="p-5 flex-1 flex flex-col gap-3 text-sm">
                    <div>
                        <p class="text-muted-foreground text-xs uppercase mb-1">Prosesor</p>
                        <p class="font-medium text-foreground truncate" title="{{ $computer->processor }}">{{ $computer->processor ?? '-' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-border/50">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">RAM</p>
                            <p class="font-medium text-foreground">{{ $computer->ram_gb ? $computer->ram_gb . ' GB' : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Penyimpanan</p>
                            <p class="font-medium text-foreground">
                                {{ $computer->disk_free_gb ? $computer->disk_free_gb . ' GB sisa / ' : '' }}
                                {{ $computer->disk_total_gb ? $computer->disk_total_gb . ' GB' : '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD: Informasi Fisik & Jaringan --}}
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-border/50 bg-muted/20 flex items-center gap-2">
                    <i class="fa-solid fa-network-wired text-primary text-lg"></i>
                    <h3 class="font-semibold text-foreground">Jaringan & Status Pemindaian</h3>
                </div>
                <div class="p-5 flex-1 flex flex-col gap-3 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Manufaktur</p>
                            <p class="font-medium text-foreground">{{ $computer->manufacturer ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Model</p>
                            <p class="font-medium text-foreground">{{ $computer->model ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-border/50">
                        <p class="text-muted-foreground text-xs uppercase mb-1">Terakhir Aktif / Ter-scan</p>
                        <p class="font-medium text-foreground">{{ $computer->last_seen_at ? $computer->last_seen_at->format('d/m/Y H:i') : '-' }}</p>
                        <p class="text-xs text-muted-foreground">{{ $computer->last_seen_at ? $computer->last_seen_at->diffForHumans() : 'Belum pernah scan' }}</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Daftar Perangkat Lunak Terinstal --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-foreground">Perangkat Lunak Terdeteksi</h2>
                    <p class="text-xs text-muted-foreground">Daftar aplikasi hasil pemindaian tools scanner pada komputer ini.</p>
                </div>
                <span class="text-xs px-3 py-1 rounded-full bg-muted font-medium text-muted-foreground">
                    Total: {{ $computer->softwares->count() }} software
                </span>
            </div>

            <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                            <x-ui.table.table-head>Nama Aplikasi</x-ui.table.table-head>
                            <x-ui.table.table-head>Versi</x-ui.table.table-head>
                            <x-ui.table.table-head>Vendor</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Kategori</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Status Audit</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($computer->softwares as $index => $discovery)
                            @php
                                $catalog = $discovery->catalog;
                                $compliance = $computer->complianceReports->firstWhere('software_catalog_id', $discovery->catalog_id);
                                $complianceStatus = $compliance?->status ?? ($catalog?->status === 'Blacklist' ? 'Blacklist' : 'Terdeteksi');
                            @endphp
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="text-center font-medium">
                                    {{ $index + 1 }}
                                </x-ui.table.table-cell>
                                
                                <x-ui.table.table-cell class="font-medium text-foreground">
                                    {{ $discovery->raw_name }}
                                    @if ($catalog && $catalog->normalized_name !== $discovery->raw_name)
                                        <div class="text-xs text-muted-foreground">Katalog: {{ $catalog->normalized_name }}</div>
                                    @endif
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-xs text-muted-foreground font-mono">
                                    {{ $discovery->version ?: '-' }}
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                    {{ $discovery->vendor ?: '-' }}
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-muted text-muted-foreground">
                                        {{ $catalog->category ?? 'Unknown' }}
                                    </span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    @php
                                        $badgeStyle = match($complianceStatus) {
                                            'Berlisensi' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                            'Grace Period' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                            'Tidak Berlisensi', 'Blacklist' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                            default => 'bg-secondary text-secondary-foreground',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeStyle }}">
                                        {{ $complianceStatus }}
                                    </span>
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="6" class="text-center py-8 text-muted-foreground">
                                    Belum ada data software yang ditemukan untuk komputer ini.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>
        </div>

    </div>
</x-layout.app>
