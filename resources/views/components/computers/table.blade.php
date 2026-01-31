    <!-- Breathing in, I calm body and mind. Breathing out, I smile. - Thich Nhat Hanh -->
    @props(['computers'])

    <div class="rounded-md border border-border bg-card shadow-sm">
        <x-ui.table.table>
            <x-ui.table.table-header>
                <x-ui.table.table-row>
                    <x-ui.table.table-head>Hostname</x-ui.table.table-head>
                    <x-ui.table.table-head>IP Address</x-ui.table.table-head>
                    <x-ui.table.table-head>OS Info</x-ui.table.table-head>
                    {{-- [BARU] Kolom Location --}}
                    <x-ui.table.table-head>Location</x-ui.table.table-head>
                    <x-ui.table.table-head>License Status</x-ui.table.table-head>
                    <x-ui.table.table-head class="text-right">Actions</x-ui.table.table-head>
                </x-ui.table.table-row>
            </x-ui.table.table-header>

            <x-ui.table.table-body>
                @forelse($computers as $computer)
                    @php
                        $status = $computer->os_license_status;
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
                                <span>{{ $computer->location ?? 'Not Set' }}</span>
                            </div>
                        </x-ui.table.table-cell>

                        {{-- 5. Status Badge --}}
                        <x-ui.table.table-cell>
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">
                                <i class="fa-solid {{ $icon }} mr-1.5"></i>
                                {{ $status ?? 'Unknown' }}
                            </span>
                        </x-ui.table.table-cell>

                        {{-- 6. Actions --}}
                        <x-ui.table.table-cell class="text-right">

                            <div class="flex items-center justify-end gap-2">

                                {{-- A. EDIT SHEET (Form Update) --}}
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
                                                <h4 class="text-sm font-semibold text-foreground border-b pb-1">System
                                                    Information</h4>
                                                <div class="grid gap-3">

                                                    {{-- OS --}}
                                                    <div
                                                        class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg border border-border/50">
                                                        <div class="p-2 bg-primary/10 rounded-md text-primary">
                                                            <i class="fa-brands fa-windows text-lg"></i>
                                                        </div>
                                                        <div>
                                                            <p
                                                                class="text-xs font-medium text-muted-foreground uppercase">
                                                                Operating System</p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ $computer->os_name ?? '-' }}</p>
                                                            <p class="text-xs text-muted-foreground">
                                                                {{ $computer->os_version }}
                                                                ({{ $computer->os_architecture }})
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
                                                                Processor</p>
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
                                                                Memory (RAM)</p>
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
                                                                Storage</p>
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
                                                <h4 class="text-sm font-semibold text-foreground border-b pb-1">Network
                                                    & Identity</h4>
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
                        <x-ui.table.table-cell colspan="6" class="text-center h-24 text-muted-foreground">
                            Tidak ada data komputer.
                        </x-ui.table.table-cell>
                    </x-ui.table.table-row>
                @endforelse
            </x-ui.table.table-body>
        </x-ui.table.table>
    </div>
