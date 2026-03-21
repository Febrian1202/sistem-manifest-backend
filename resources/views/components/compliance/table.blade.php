@props(['softwares'])

<div class="rounded-md border border-border bg-card shadow-sm overflow-x-auto">
    <x-ui.table.table>
        <x-ui.table.table-header>
            <x-ui.table.table-row>
                <x-ui.table.table-head>Software Komersial</x-ui.table.table-head>
                <x-ui.table.table-head class="text-center">Total Terpasang</x-ui.table.table-head>
                <x-ui.table.table-head class="text-center">Lisensi Dimiliki</x-ui.table.table-head>
                <x-ui.table.table-head class="text-center">Defisit (Ilegal)</x-ui.table.table-head>
                <x-ui.table.table-head>Status Audit</x-ui.table.table-head>
                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
            </x-ui.table.table-row>
        </x-ui.table.table-header>

        <x-ui.table.table-body>
            @forelse($softwares as $software)
                <x-ui.table.table-row class="{{ !$software->is_compliant ? 'bg-destructive/5' : '' }}">
                    <x-ui.table.table-cell class="font-medium">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-8 w-8 rounded {{ $software->is_compliant ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive' }} flex items-center justify-center text-xs">
                                <i class="fa-solid {{ $software->is_compliant ? 'fa-check' : 'fa-xmark' }}"></i>
                            </div>
                            <span class="truncate max-w-48">{{ $software->normalized_name }}</span>
                        </div>
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-center font-bold">
                        {{ $software->installed_count }}
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-center text-muted-foreground">
                        {{ $software->owned_count }}
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-center">
                        @if($software->deficit > 0)
                            <span class="text-destructive font-bold text-lg">-{{ $software->deficit }}</span>
                        @else
                            <span class="text-muted-foreground">-</span>
                        @endif
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell>
                        @if($software->is_compliant)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success/10 text-success border border-success/20">
                                Compliant
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20 animate-pulse">
                                Non-Compliant
                            </span>
                        @endif
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-right">
                        <x-ui.sheet.sheet>
                            <x-ui.sheet.trigger>
                                <x-ui.button variant="outline" size="sm" class="h-8 text-xs">
                                    Lacak PC
                                </x-ui.button>
                            </x-ui.sheet.trigger>
                            <x-ui.sheet.content side="right">
                                <x-ui.sheet.header>
                                    <x-ui.sheet.title>Daftar Instalasi</x-ui.sheet.title>
                                    <x-ui.sheet.description>Komputer yang menginstall
                                        {{ $software->normalized_name }}</x-ui.sheet.description>
                                </x-ui.sheet.header>

                                <div class="mt-6 space-y-4">
                                    @foreach($software->discoveries as $discovery)
                                        @if($discovery->computer)
                                            <div class="p-3 bg-card border rounded shadow-sm flex justify-between items-center">
                                                <div>
                                                    <p class="font-bold text-sm">{{ $discovery->computer->hostname }}</p>
                                                    <p class="text-[10px] text-muted-foreground">
                                                        {{ $discovery->computer->ip_address }}</p>
                                                </div>
                                                <div class="text-[10px] text-muted-foreground">
                                                    Terdeteksi: {{ \Carbon\Carbon::parse($discovery->created_at)->format('d/m/Y') }}
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </x-ui.sheet.content>
                        </x-ui.sheet.sheet>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @empty
                <x-ui.table.table-row>
                    <x-ui.table.table-cell colspan="6" class="text-center h-32 text-muted-foreground">
                        <p>Belum ada data audit kepatuhan.</p>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @endforelse
        </x-ui.table.table-body>
    </x-ui.table.table>
</div>