@props(['softwares'])

<div class="rounded-md border border-border bg-card shadow-sm">
    <x-ui.table.table>
        <x-ui.table.table-header>
            <x-ui.table.table-row>
                <x-ui.table.table-head>Software</x-ui.table.table-head>
                <x-ui.table.table-head>Publisher/Vendor</x-ui.table.table-head>
                <x-ui.table.table-head>Version</x-ui.table.table-head>
                <x-ui.table.table-head class="text-center">Install Count</x-ui.table.table-head>
                <x-ui.table.table-head>Kategori</x-ui.table.table-head>
                <x-ui.table.table-head>Status</x-ui.table.table-head>
                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
            </x-ui.table.table-row>
        </x-ui.table.table-header>

        <x-ui.table.table-body>
            @forelse($softwares as $software)
                @php
                    // Ambil data sampel dari discovery terakhir (karena catalog gak simpan vendor/version)
                    $latestDiscovery = $software->discoveries->first();

                    // Warna Status
                    $statusClass = match ($software->status) {
                        'Whitelist' => 'bg-success/10 text-success border-success/20',
                        'Blacklist' => 'bg-destructive/10 text-destructive border-destructive/20',
                        'Unreviewed' => 'bg-warning/10 text-warning border-warning/20',
                        default => 'bg-muted text-muted-foreground',
                    };
                    $statusIcon = match ($software->status) {
                        'Whitelist' => 'fa-circle-check',
                        'Blacklist' => 'fa-ban',
                        'Unreviewed' => 'fa-circle-question',
                        default => 'fa-circle',
                    };

                    // Warna Kategori
                    $catClass = match ($software->category) {
                        'Freeware' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'Commercial' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'OpenSource' => 'bg-teal-50 text-teal-700 border-teal-200',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp

                <x-ui.table.table-row>
                    {{-- Software Name --}}
                    <x-ui.table.table-cell class="font-medium">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded bg-primary/10 flex items-center justify-center text-primary">
                                <i class="fa-solid fa-cube"></i>
                            </div>
                            <span class="truncate max-w-50" title="{{ $software->normalized_name }}">
                                {{ $software->normalized_name }}
                            </span>
                        </div>
                    </x-ui.table.table-cell>

                    {{-- Vendor --}}
                    <x-ui.table.table-cell class="text-muted-foreground text-xs">
                        {{ $latestDiscovery->vendor ?? '-' }}
                    </x-ui.table.table-cell>

                    {{-- Version --}}
                    <x-ui.table.table-cell class="text-muted-foreground text-xs font-mono">
                        {{ $latestDiscovery->version ?? '-' }}
                    </x-ui.table.table-cell>

                    {{-- Install Count --}}
                    <x-ui.table.table-cell class="text-center">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-foreground">
                            {{ $software->discoveries_count }} Device
                        </span>
                    </x-ui.table.table-cell>

                    {{-- Kategori --}}
                    <x-ui.table.table-cell>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium border {{ $catClass }}">
                            {{ $software->category }}
                        </span>
                    </x-ui.table.table-cell>

                    {{-- Status --}}
                    <x-ui.table.table-cell>
                        <span
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $statusClass }}">
                            <i class="fa-solid {{ $statusIcon }}"></i>
                            {{ $software->status }}
                        </span>
                    </x-ui.table.table-cell>

                    {{-- Aksi --}}
                    <x-ui.table.table-cell class="text-right">
                        <x-ui.dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <x-ui.button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </x-ui.button>
                            </x-slot>
                            <x-slot name="content">
                                <x-ui.dropdown-label>Actions</x-ui.dropdown-label>
                                <x-ui.dropdown-item>
                                    <i class="fa-solid fa-pen-to-square mr-2 opacity-70"></i> Edit Detail
                                </x-ui.dropdown-item>
                                <x-ui.dropdown-separator />
                                <x-ui.dropdown-item>
                                    <i class="fa-solid fa-check mr-2 text-green-600"></i> Set Whitelist
                                </x-ui.dropdown-item>
                                <x-ui.dropdown-item>
                                    <i class="fa-solid fa-ban mr-2 text-red-600"></i> Set Blacklist
                                </x-ui.dropdown-item>
                            </x-slot>
                        </x-ui.dropdown>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @empty
                <x-ui.table.table-row>
                    <x-ui.table.table-cell colspan="7" class="text-center h-32 text-muted-foreground">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="fa-solid fa-box-open text-2xl opacity-50"></i>
                            <p>Tidak ada software ditemukan.</p>
                        </div>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @endforelse
        </x-ui.table.table-body>
    </x-ui.table.table>
</div>
