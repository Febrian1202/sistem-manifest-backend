    @props(['computers'])

    <div class="rounded-md border border-border bg-card shadow-sm overflow-x-auto w-full">
        <x-ui.table.table>
            <x-ui.table.table-header>
                <x-ui.table.table-row>
                    <x-ui.table.table-head>Hostname</x-ui.table.table-head>
                    <x-ui.table.table-head>Alamat IP</x-ui.table.table-head>
                    <x-ui.table.table-head>Info OS</x-ui.table.table-head>
                    {{-- [BARU] Kolom Location --}}
                    <x-ui.table.table-head>Lokasi</x-ui.table.table-head>
                    <x-ui.table.table-head>Status Lisensi</x-ui.table.table-head>
                    <x-ui.table.table-head>Scan Terakhir</x-ui.table.table-head>
                    <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
                </x-ui.table.table-row>
            </x-ui.table.table-header>

            <x-ui.table.table-body>
                @forelse($computers as $computer)
                    @php
                        $status = $computer->os_license_status;
                        $statusLabel = match ($status) {
                            'Licensed' => 'Berlisensi',
                            'Grace Period' => 'Masa Tenggang',
                            'Unlicensed' => 'Tidak Berlisensi',
                            'Non-Genuine' => 'Tidak Asli',
                            default => $status ?? 'Tidak Diketahui',
                        };
                        $badgeClass = match ($status) {
                            'Licensed' => 'bg-success/10 text-success border-success/20',
                            'Grace Period' => 'bg-warning/10 text-warning border-warning/20',
                            'Unlicensed', 'Non-Genuine' => 'bg-destructive/10 text-destructive border-destructive/20',
                            default => 'bg-muted text-muted-foreground',
                        };
                        $icon = match ($status) {
                            'Licensed' => 'fa-circle-check',
                            'Grace Period' => 'fa-triangle-exclamation',
                            'Unlicensed', 'Non-Genuine' => 'fa-circle-xmark',
                            default => 'fa-circle-question',
                        };

                        // TASK-004: Logic for Last Scan color
                        $lastSeen = $computer->last_seen_at;
                        $lastSeenColor = 'text-muted-foreground';
                        if ($lastSeen) {
                            $hoursSince = $lastSeen->diffInHours(now());
                            if ($hoursSince <= 24) {
                                $lastSeenColor = 'text-green-600';
                            } elseif ($hoursSince <= 168) { // 7 days
                                $lastSeenColor = 'text-yellow-600';
                            } else {
                                $lastSeenColor = 'text-red-600';
                            }
                        }
                    @endphp

                    <x-ui.table.table-row>
                        {{-- 1. Hostname --}}
                        <x-ui.table.table-cell class="font-medium">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-desktop text-muted-foreground text-xs"></i>
                                <span>{{ $computer->hostname }}</span>
                            </div>
                        </x-ui.table.table-cell>

                        {{-- 2. IP Address --}}
                        <x-ui.table.table-cell class="font-mono text-xs text-muted-foreground">
                            {{ $computer->ip_address ?? '-' }}
                        </x-ui.table.table-cell>

                        {{-- 3. OS Info --}}
                        <x-ui.table.table-cell>
                            <div class="flex flex-col">
                                <span class="font-medium text-xs">{{ $computer->os_name ?? 'Unknown' }}</span>
                                <span class="text-[10px] text-muted-foreground">{{ $computer->os_version ?? '' }}</span>
                            </div>
                        </x-ui.table.table-cell>

                        {{-- [BARU] 4. Location --}}
                        <x-ui.table.table-cell>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <i class="fa-solid fa-location-dot text-[10px] opacity-70"></i>
                                <span>{{ $computer->location ?? 'Belum Diatur' }}</span>
                            </div>
                        </x-ui.table.table-cell>

                        {{-- 5. Status Badge --}}
                        <x-ui.table.table-cell>
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">
                                <i class="fa-solid {{ $icon }} mr-1.5"></i>
                                {{ $statusLabel }}
                            </span>
                        </x-ui.table.table-cell>

                        {{-- 6. Scan Terakhir --}}
                        <x-ui.table.table-cell>
                            <span class="text-xs font-medium {{ $lastSeenColor }}">
                                {{ $lastSeen ? $lastSeen->diffForHumans() : 'Belum pernah scan' }}
                            </span>
                        </x-ui.table.table-cell>

                        {{-- 7. Actions --}}
                        <x-ui.table.table-cell class="text-right">

                            <div class="flex items-center justify-end gap-2">

                                {{-- TASK-001: REQUEST SCAN BUTTON --}}
                                @role('admin')
                                <form action="{{ route('computers.request-scan', $computer->id) }}" method="POST"
                                    x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    @php
                                        $isPending = (bool) $computer->scan_requested;
                                    @endphp
                                    <x-ui.button type="submit" variant="outline" size="sm" class="h-8 w-8 p-0"
                                        title="{{ $isPending ? 'Scan Tertunda...' : 'Minta Scan' }}"
                                        ::disabled="loading || {{ $isPending ? 'true' : 'false' }}">
                                        <i class="fa-solid fa-rotate text-xs {{ $isPending ? 'animate-spin text-yellow-500' : '' }}"
                                            :class="loading ? 'animate-spin' : ''"></i>
                                    </x-ui.button>
                                </form>
                                @endrole

                                {{-- A. EDIT SHEET (Form Update) --}}
                                @role('admin')
                                <x-ui.sheet.sheet>
                                    <x-ui.sheet.trigger>
                                        <x-ui.button variant="outline" size="sm" class="h-8 w-8 p-0"
                                            title="Edit Lokasi">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </x-ui.button>
                                    </x-ui.sheet.trigger>

                                    <x-ui.sheet.content side="right">
                                        <x-ui.sheet.header>
                                            <x-ui.sheet.title>Edit Komputer</x-ui.sheet.title>
                                            <x-ui.sheet.description>
                                                Perbarui informasi lokasi atau metadata untuk
                                                <strong>{{ $computer->hostname }}</strong>.
                                            </x-ui.sheet.description>
                                        </x-ui.sheet.header>

                                        {{-- FORM START --}}
                                        <form action="{{ route('computers.update', $computer->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="grid gap-4 py-6">

                                                {{-- Field Hostname (Readonly) --}}
                                                <div class="grid gap-2">
                                                    <x-label>Hostname</x-label>
                                                    <x-input value="{{ $computer->hostname }}" disabled
                                                        class="bg-muted text-muted-foreground" />
                                                </div>

                                                {{-- Field Location (Editable) --}}
                                                <div class="grid gap-2">
                                                    <x-label for="location_{{ $computer->id }}">Lokasi
                                                        Fisik</x-label>
                                                    <x-input id="location_{{ $computer->id }}" name="location"
                                                        value="{{ $computer->location }}"
                                                        placeholder="Contoh: Lab 1, Ruang Server..." />
                                                    <p class="text-[10px] text-muted-foreground">Lokasi penempatan aset
                                                        komputer ini.</p>
                                                </div>

                                            </div>

                                            <x-ui.sheet.footer>
                                                <x-ui.button type="submit">
                                                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                                                </x-ui.button>
                                            </x-ui.sheet.footer>
                                        </form>
                                        {{-- FORM END --}}

                                    </x-ui.sheet.content>
                                </x-ui.sheet.sheet>

                                 {{-- DELETE BUTTON --}}
                                 <div @click.stop="$dispatch('open-dialog', 'delete-computer-{{ $computer->id }}')" class="inline-block">
                                     <x-ui.button type="button" variant="ghost" size="sm"
                                         class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                                         title="Hapus Komputer">
                                         <i class="fa-solid fa-trash-can text-xs"></i>
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
                                         <form action="{{ route('computers.destroy', $computer->id) }}" method="POST" class="w-full sm:w-auto">
                                             @csrf
                                             @method('DELETE')
                                             <x-ui.button type="submit" variant="destructive" class="w-full">
                                                Hapus Komputer
                                             </x-ui.button>
                                         </form>
                                     </x-slot>
                                 </x-ui.dialog.confirm>
                                @endrole


                                {{-- B. DETAIL SHEET (View Only - Kode Lama) --}}
                                <x-ui.sheet.sheet>
                                    <x-ui.sheet.trigger>
                                        <x-ui.button variant="ghost" size="sm" class="h-8 w-8 p-0"
                                            title="Lihat Detail">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </x-ui.button>
                                    </x-ui.sheet.trigger>

                                    <x-ui.sheet.content side="right">
                                        <x-ui.sheet.header>
                                            <x-ui.sheet.title>{{ $computer->hostname }}</x-ui.sheet.title>
                                            <x-ui.sheet.description>
                                                Spesifikasi perangkat keras dan detail sistem.
                                            </x-ui.sheet.description>
                                        </x-ui.sheet.header>

                                        <div class="mt-6 space-y-6">
                                            {{-- Group: System Info --}}
                                            <div class="space-y-3">
                                                <h4 class="text-sm font-semibold text-foreground border-b pb-1">Informasi
                                                    Sistem</h4>
                                                <div class="grid gap-3">

                                                    {{-- OS --}}
                                                    <div
                                                        class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg border border-border/50">
                                                        <div class="p-2 bg-primary/10 rounded-md text-primary">
                                                            <i class="fa-brands fa-windows text-lg"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p
                                                                class="text-xs font-medium text-muted-foreground uppercase">
                                                                Sistem Operasi</p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ $computer->os_name ?? '-' }}</p>
                                                            <p class="text-xs text-muted-foreground">
                                                                {{ $computer->os_version }}
                                                                ({{ $computer->os_architecture }})
                                                            </p>
                                                            <div class="mt-2 pt-2 border-t border-border/50 grid grid-cols-2 gap-2 text-xs">
                                                                <div>
                                                                    <span class="text-muted-foreground block">Status Lisensi:</span>
                                                                    @php
                                                                        $osStatus = $computer->os_license_status;
                                                                        $osStatusLabel = match ($osStatus) {
                                                                            'Licensed' => 'Berlisensi',
                                                                            'Grace Period' => 'Masa Tenggang',
                                                                            'Unlicensed' => 'Tidak Berlisensi',
                                                                            'Non-Genuine' => 'Tidak Asli',
                                                                            default => $osStatus ?? 'Tidak Diketahui',
                                                                        };
                                                                        $osStatusClass = match ($osStatus) {
                                                                            'Licensed' => 'text-green-600 font-semibold',
                                                                            'Grace Period' => 'text-yellow-600 font-semibold',
                                                                            'Unlicensed', 'Non-Genuine' => 'text-red-600 font-semibold',
                                                                            default => 'text-muted-foreground',
                                                                        };
                                                                    @endphp
                                                                    <span class="{{ $osStatusClass }}">{{ $osStatusLabel }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted-foreground block">Kunci Sebagian:</span>
                                                                    <span class="font-mono font-semibold">{{ $computer->os_partial_key ?? '-' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Pabrikan & Model --}}
                                                    <div
                                                        class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg border border-border/50">
                                                        <div class="p-2 bg-indigo-500/10 rounded-md text-indigo-600">
                                                            <i class="fa-solid fa-laptop text-lg"></i>
                                                        </div>
                                                        <div>
                                                            <p
                                                                class="text-xs font-medium text-muted-foreground uppercase">
                                                                Pabrikan & Model</p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ $computer->manufacturer ?? '-' }}</p>
                                                            <p class="text-xs text-muted-foreground">
                                                                Model: {{ $computer->model ?? '-' }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    {{-- CPU --}}
                                                    <div
                                                        class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg border border-border/50">
                                                        <div class="p-2 bg-blue-500/10 rounded-md text-blue-600">
                                                            <i class="fa-solid fa-microchip text-lg"></i>
                                                        </div>
                                                        <div>
                                                            <p
                                                                class="text-xs font-medium text-muted-foreground uppercase">
                                                                Prosesor</p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ $computer->processor ?? 'Not Detected' }}</p>
                                                        </div>
                                                    </div>

                                                    {{-- RAM --}}
                                                    <div
                                                        class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg border border-border/50">
                                                        <div class="p-2 bg-green-500/10 rounded-md text-green-600">
                                                            <i class="fa-solid fa-memory text-lg"></i>
                                                        </div>
                                                        <div>
                                                            <p
                                                                class="text-xs font-medium text-muted-foreground uppercase">
                                                                Memori (RAM)</p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ $computer->ram_gb ? $computer->ram_gb . ' GB' : '-' }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    {{-- Storage --}}
                                                    <div
                                                        class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg border border-border/50">
                                                        <div class="p-2 bg-purple-500/10 rounded-md text-purple-600">
                                                            <i class="fa-solid fa-hard-drive text-lg"></i>
                                                        </div>
                                                        <div>
                                                            <p
                                                                class="text-xs font-medium text-muted-foreground uppercase">
                                                                Penyimpanan</p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                Total:
                                                                {{ $computer->disk_total_gb ? $computer->disk_total_gb . ' GB' : '-' }}
                                                            </p>
                                                            <p class="text-xs text-muted-foreground">
                                                                Free:
                                                                {{ $computer->disk_free_gb ? $computer->disk_free_gb . ' GB' : '-' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Group: Network --}}
                                            <div class="space-y-3">
                                                <h4 class="text-sm font-semibold text-foreground border-b pb-1">Jaringan
                                                    & Identitas</h4>
                                                <div
                                                    class="p-4 bg-muted/30 rounded-lg border border-border/50 space-y-3">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-muted-foreground">IP Address</span>
                                                        <span
                                                            class="text-sm font-mono font-medium">{{ $computer->ip_address ?? '-' }}</span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-muted-foreground">MAC Address</span>
                                                        <span
                                                            class="text-sm font-mono font-medium">{{ $computer->mac_address ?? '-' }}</span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-muted-foreground">Serial Number</span>
                                                        <span
                                                            class="text-sm font-mono font-medium">{{ $computer->serial_number ?? '-' }}</span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-muted-foreground">Lokasi</span>
                                                        <span
                                                            class="text-sm font-medium">{{ $computer->location ?? 'Belum Diatur' }}</span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-muted-foreground">Terakhir Terlihat</span>
                                                        <span
                                                            class="text-sm font-medium text-right" title="{{ $computer->last_seen_at ? $computer->last_seen_at->format('d/m/Y H:i:s') : '-' }}">
                                                            {{ $computer->last_seen_at ? $computer->last_seen_at->diffForHumans() : '-' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-muted-foreground">Status Pemindaian</span>
                                                        @if($computer->scan_requested)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 animate-pulse">
                                                                <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Menunggu Pemindaian
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                                <i class="fa-solid fa-check mr-1"></i> Terkini
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </x-ui.sheet.content>
                                </x-ui.sheet.sheet>

                            </div>

                        </x-ui.table.table-cell>
                    </x-ui.table.table-row>
                @empty
                    <x-ui.table.table-row>
                        <x-ui.table.table-cell colspan="7" class="text-center h-24 text-muted-foreground">
                            Tidak ada data komputer.
                        </x-ui.table.table-cell>
                    </x-ui.table.table-row>
                @endforelse
            </x-ui.table.table-body>
        </x-ui.table.table>
    </div>
