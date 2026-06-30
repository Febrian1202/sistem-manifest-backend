<x-layout.app title="Detail Komputer" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Data Komputer', 'url' => route('computers')],
    ['name' => 'Detail ' . $computer->hostname, 'url' => null]
]">
    <div class="space-y-6 pb-10">

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">{{ $computer->hostname }}</h1>
                <p class="text-muted-foreground mt-1">
                    Detail perangkat keras, sistem operasi, dan temuan perangkat lunak.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('computers') }}">
                    <x-ui.button type="button" variant="outline">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </x-ui.button>
                </a>
                
                @role('admin')
                <div @click.stop="$dispatch('open-dialog', 'delete-computer-{{ $computer->id }}')">
                     <x-ui.button type="button" variant="destructive">
                         <i class="fa-solid fa-trash-can mr-2"></i>
                         Hapus
                     </x-ui.button>
                </div>
                
                <x-ui.dialog.confirm name="delete-computer-{{ $computer->id }}" title="Hapus Komputer" maxWidth="md">
                     <div class="flex items-start gap-4">
                         <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                             <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                         </div>
                         <div class="space-y-1">
                             <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                 Apakah Anda yakin ingin menghapus komputer <strong>{{ $computer->hostname }}</strong>? Semua data scan terkait akan ikut terhapus dari sistem.
                             </p>
                         </div>
                     </div>
                     <x-slot name="footer">
                         <x-ui.button type="button" variant="outline" x-on:click="show = false" class="w-full sm:w-auto">
                             Batal
                         </x-ui.button>
                         <form action="{{ route('computers.destroy', $computer) }}" method="POST" class="w-full sm:w-auto">
                             @csrf
                             @method('DELETE')
                             <x-ui.button type="submit" variant="destructive" class="w-full">
                                 Ya, Hapus
                             </x-ui.button>
                         </form>
                     </x-slot>
                 </x-ui.dialog.confirm>
                 @endrole
            </div>
        </div>

        {{-- Grid Cards Information --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            {{-- CARD: Sistem Operasi --}}
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-border/50 bg-muted/20 flex items-center gap-2">
                    <i class="fa-brands fa-windows text-primary text-lg"></i>
                    <h3 class="font-semibold text-foreground">Sistem Operasi</h3>
                </div>
                <div class="p-5 flex-1 flex flex-col gap-3 text-sm">
                    <div>
                        <p class="text-muted-foreground text-xs uppercase mb-1">Nama OS</p>
                        <p class="font-medium">{{ $computer->os_name ?? '-' }}</p>
                        <p class="text-xs text-muted-foreground">{{ $computer->os_version }} ({{ $computer->os_architecture }})</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-auto pt-3 border-t border-border/50">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Status Lisensi OS</p>
                            @php
                                $status = $computer->os_license_status;
                                $statusColor = match ($status) {
                                    'Licensed' => 'text-success',
                                    'Unlicensed' => 'text-destructive',
                                    'Grace Period' => 'text-warning',
                                    default => 'text-muted-foreground',
                                };
                            @endphp
                            <p class="font-medium {{ $statusColor }}">{{ $status ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Partial Key</p>
                            <p class="font-mono font-medium">{{ $computer->os_partial_key ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD: Spesifikasi Hardware --}}
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-border/50 bg-muted/20 flex items-center gap-2">
                    <i class="fa-solid fa-microchip text-blue-500 text-lg"></i>
                    <h3 class="font-semibold text-foreground">Spesifikasi Hardware</h3>
                </div>
                <div class="p-5 flex-1 flex flex-col gap-3 text-sm">
                    <div>
                        <p class="text-muted-foreground text-xs uppercase mb-1">Prosesor</p>
                        <p class="font-medium">{{ $computer->processor ?? '-' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-auto pt-3 border-t border-border/50">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">RAM</p>
                            <p class="font-medium">{{ $computer->ram_gb ?? '-' }} GB</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Storage</p>
                            <p class="font-medium">{{ $computer->disk_free_gb ?? 0 }} GB <span class="text-xs text-muted-foreground font-normal">bebas dari {{ $computer->disk_total_gb ?? 0 }} GB</span></p>
                            {{-- Progress Bar --}}
                            @php
                                $total = $computer->disk_total_gb ?? 0;
                                $free = $computer->disk_free_gb ?? 0;
                                $used = $total > 0 ? $total - $free : 0;
                                $percentage = $total > 0 ? ($used / $total) * 100 : 0;
                                $barColor = $percentage > 90 ? 'bg-destructive' : ($percentage > 75 ? 'bg-warning' : 'bg-primary');
                            @endphp
                            <div class="w-full bg-secondary rounded-full h-1.5 mt-1">
                                <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD: Jaringan & Identitas --}}
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-border/50 bg-muted/20 flex items-center gap-2">
                    <i class="fa-solid fa-network-wired text-green-500 text-lg"></i>
                    <h3 class="font-semibold text-foreground">Jaringan & Identitas</h3>
                </div>
                <div class="p-5 flex-1 grid grid-cols-2 gap-y-4 gap-x-2 text-sm">
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-muted-foreground text-xs uppercase mb-1">IP Address</p>
                        <p class="font-mono font-medium">{{ $computer->ip_address ?? '-' }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-muted-foreground text-xs uppercase mb-1">MAC Address</p>
                        <p class="font-mono font-medium">{{ $computer->mac_address ?? '-' }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-muted-foreground text-xs uppercase mb-1">Serial Number</p>
                        <p class="font-mono font-medium">{{ $computer->serial_number ?? '-' }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-muted-foreground text-xs uppercase mb-1">Lokasi</p>
                        <p class="font-medium">{{ $computer->location ?? 'Belum Diatur' }}</p>
                    </div>
                    
                    <div class="col-span-2 pt-3 border-t border-border/50 mt-1 flex justify-between items-center">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase mb-1">Terakhir Terlihat</p>
                            <p class="font-medium">{{ $computer->last_seen_at ? $computer->last_seen_at->diffForHumans() : '-' }}</p>
                        </div>
                        @if($computer->scan_requested)
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-800 animate-pulse">
                                <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Menunggu Scan
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                <i class="fa-solid fa-check mr-1"></i> Scan Selesai
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            
        </div>

        {{-- Software Discoveries Table --}}
        <div class="mt-8">
            <h2 class="text-lg font-bold text-foreground mb-4">Temuan Perangkat Lunak (Software Discoveries)</h2>
            <div class="rounded-md border border-border bg-card shadow-sm overflow-x-auto w-full">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head>Nama Aplikasi</x-ui.table.table-head>
                            <x-ui.table.table-head>Versi</x-ui.table.table-head>
                            <x-ui.table.table-head>Vendor</x-ui.table.table-head>
                            <x-ui.table.table-head>Tanggal Install</x-ui.table.table-head>
                            <x-ui.table.table-head>Status Katalog</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
        
                    <x-ui.table.table-body>
                        @forelse($computer->softwares as $discovery)
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="font-medium">
                                    {{ $discovery->raw_name }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    {{ $discovery->version ?? '-' }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    {{ $discovery->vendor ?? '-' }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    {{ $discovery->install_date ? \Carbon\Carbon::parse($discovery->install_date)->format('d M Y') : '-' }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    @if($discovery->catalog)
                                        @php
                                            $catStatus = $discovery->catalog->status;
                                            $badgeClass = match ($catStatus) {
                                                'Whitelist' => 'bg-success/10 text-success border-success/20',
                                                'Blacklist' => 'bg-destructive/10 text-destructive border-destructive/20',
                                                'Unreviewed' => 'bg-warning/10 text-warning border-warning/20',
                                                default => 'bg-muted text-muted-foreground',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $catStatus }}
                                        </span>
                                        <span class="text-xs text-muted-foreground ml-1">({{ $discovery->catalog->category }})</span>
                                    @else
                                        <span class="text-muted-foreground text-xs italic">Belum diproses</span>
                                    @endif
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="5" class="text-center h-24 text-muted-foreground">
                                    Tidak ada data software yang ditemukan di komputer ini.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>
        </div>

    </div>
</x-layout.app>
