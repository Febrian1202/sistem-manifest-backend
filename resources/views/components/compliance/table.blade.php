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
                <x-ui.table.table-row 
                    x-show="activeTab === 'semua' || (activeTab === 'tidak-patuh' && '{{ $software->is_compliant ? 'patuh' : 'tidak-patuh' }}' === 'tidak-patuh') || (activeTab === 'patuh' && '{{ $software->is_compliant ? 'patuh' : 'tidak-patuh' }}' === 'patuh')"
                    data-status="{{ $software->is_compliant ? 'patuh' : 'tidak-patuh' }}"
                    class="{{ !$software->is_compliant ? 'bg-destructive/5' : '' }}"
                >
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
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 border border-green-300">
                                Patuh
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20 animate-pulse">
                                Tidak Patuh
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
                            <x-ui.sheet.content side="right" x-data="{ 
                                canScroll: false,
                                checkScroll() {
                                    const container = $el.querySelector('.scroll-container');
                                    if (container) {
                                        this.canScroll = container.scrollHeight > container.clientHeight;
                                    }
                                }
                            }" x-init="
                                $watch('open', value => { 
                                    if(value) { 
                                        setTimeout(() => {
                                            checkScroll();
                                            const container = $el.querySelector('.scroll-container');
                                            if(container) container.scrollTop = 0;
                                        }, 100);
                                    } 
                                })
                            ">
                                <x-ui.sheet.header>
                                    <x-ui.sheet.title>Daftar Instalasi</x-ui.sheet.title>
                                    <x-ui.sheet.description>
                                        Komputer yang menginstall {{ $software->normalized_name }} 
                                        <span class="font-bold text-primary">({{ count($software->discoveries) }} komputer)</span>
                                    </x-ui.sheet.description>
                                </x-ui.sheet.header>

                                <div class="mt-6 space-y-4 scroll-container overflow-y-auto max-h-[calc(100vh-200px)] pr-2" @scroll.debounce="checkScroll()">
                                    @foreach($software->discoveries as $discovery)
                                        @if($discovery->computer)
                                            <a href="{{ route('computers', ['search' => $discovery->computer->hostname]) }}" 
                                               target="_blank"
                                               class="p-3 bg-card border rounded shadow-sm flex justify-between items-start hover:bg-gray-50 cursor-pointer transition-colors relative group">
                                                {{-- External Link Icon - positioned so it doesn't overlap text --}}
                                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-muted-foreground"></i>
                                                </div>
                                                
                                                <div class="flex-1">
                                                    {{-- Header Row with Padding Right to avoid icon overlap --}}
                                                    <div class="flex justify-between items-center mb-1 pr-5">
                                                        <p class="font-bold text-sm text-foreground">{{ $discovery->computer->hostname }}</p>
                                                        <span class="text-[10px] text-muted-foreground shrink-0">
                                                            Terdeteksi: {{ \Carbon\Carbon::parse($discovery->created_at)->format('d/m/Y') }}
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="flex items-center gap-2 text-[10px] text-muted-foreground">
                                                        <span>{{ $discovery->computer->ip_address }}</span>
                                                        <span>•</span>
                                                        @if($discovery->version)
                                                            <span class="text-gray-400">{{ $discovery->version }}</span>
                                                        @else
                                                            <span class="text-gray-400 italic">Versi tidak diketahui</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>

                                {{-- Scroll Indicator --}}
                                <div x-show="canScroll" class="mt-4 pt-4 border-t border-border text-center">
                                    <p class="text-xs text-muted-foreground">
                                        Menampilkan {{ count($software->discoveries) }} dari {{ count($software->discoveries) }} komputer
                                    </p>
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

            {{-- Empty State Alpine.js (Muncul jika filter tidak menghasilkan baris) --}}
            <x-ui.table.table-row 
                x-show="activeTab !== 'semua' && $el.parentElement.querySelectorAll('tr[data-status]:not([style*=&quot;display: none&quot;])').length === 0"
                x-cloak
            >
                <x-ui.table.table-cell colspan="6" class="text-center py-16 text-gray-400">
                    <div class="flex flex-col items-center gap-2">
                        <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center text-2xl">
                            ✅
                        </div>
                        <p class="font-semibold" x-text="
                            activeTab === 'tidak-patuh' ? 'Tidak ada software yang melanggar kepatuhan' : 
                            (activeTab === 'patuh' ? 'Belum ada software yang memenuhi kepatuhan' : 'Belum ada software yang dipantau sistem')
                        "></p>
                        <p class="text-sm">Tidak ada pelanggaran lisensi yang terdeteksi.</p>
                    </div>
                </x-ui.table.table-cell>
            </x-ui.table.table-row>
        </x-ui.table.table-body>
    </x-ui.table.table>
</div>