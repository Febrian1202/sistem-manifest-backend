<x-layout.app title="Histori Komputer: {{ $computer->hostname }}" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Data Komputer', 'url' => route('computers')], ['name' => $computer->hostname, 'url' => route('computers.show', $computer)], ['name' => 'Histori Monitoring', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header & Meta --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-foreground">Histori Monitoring: {{ $computer->hostname }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                        {{ $computer->laboratory?->name ?? 'Tanpa Lab' }}
                    </span>
                </div>
                <p class="text-muted-foreground mt-1 text-sm">
                    Timeline kronologis pemindaian dan pelacakan perubahan software pada perangkat ini
                </p>
            </div>
            <div class="flex items-center gap-2">
                @role('admin|pimpinan')
                    <a href="{{ route('computers.show', $computer) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                        <i class="fa-solid fa-desktop mr-2"></i> Detail Perangkat
                    </a>
                @endrole
                <a href="{{ route('monitoring.index', ['computer_id' => $computer->id]) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                    <i class="fa-solid fa-list-check mr-2"></i> Semua Scan PC Ini
                </a>
            </div>
        </div>

        {{-- Info Perangkat --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">IP Address</div>
                <div class="font-mono text-sm font-semibold text-foreground mt-1">{{ $computer->ip_address ?? '-' }}</div>
            </div>
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">MAC Address</div>
                <div class="font-mono text-xs font-semibold text-foreground mt-1 truncate">{{ $computer->mac_address ?? '-' }}</div>
            </div>
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Sistem Operasi</div>
                <div class="text-sm font-semibold text-foreground mt-1 truncate">{{ $computer->os_name ?? '-' }}</div>
            </div>
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Status Lisensi OS</div>
                <div class="text-sm font-semibold text-foreground mt-1 capitalize">{{ $computer->os_license_status ?? '-' }}</div>
            </div>
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Status Perangkat</div>
                <div class="text-sm font-semibold text-foreground mt-1 capitalize">{{ $computer->status ?? 'Active' }}</div>
            </div>
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Total Sesi Scan</div>
                <div class="text-xl font-bold text-foreground mt-1">{{ $sessions->total() }}</div>
            </div>
        </div>

        {{-- Filter Timeline --}}
        <form method="GET" action="{{ route('computers.history', $computer) }}" class="bg-card border border-border p-4 rounded-lg shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <x-form.label for="status">Status Scan</x-form.label>
                    <select id="status" name="status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Status</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <x-form.label for="period_start">Dari Tanggal</x-form.label>
                    <x-form.input id="period_start" type="date" name="period_start" value="{{ request('period_start') }}" class="w-full" />
                </div>

                <div class="space-y-1">
                    <x-form.label for="period_end">Sampai Tanggal</x-form.label>
                    <x-form.input id="period_end" type="date" name="period_end" value="{{ request('period_end') }}" class="w-full" />
                </div>
            </div>

            <div class="flex justify-end items-center gap-2 pt-2 border-t border-border/50">
                @if(request()->anyFilled(['status', 'period_start', 'period_end']))
                    <a href="{{ route('computers.history', $computer) }}">
                        <x-ui.button type="button" variant="outline" class="h-9">
                            <i class="fa-solid fa-xmark mr-2"></i> Reset
                        </x-ui.button>
                    </a>
                @endif
                <x-ui.button type="submit" class="h-9">
                    <i class="fa-solid fa-filter mr-2"></i> Filter Timeline
                </x-ui.button>
            </div>
        </form>

        {{-- Timeline List --}}
        <div class="space-y-6">
            @forelse($sessionsWithDiff as $entry)
                @php
                    $session = $entry['session'];
                    $diff = $entry['diff'];
                    $complianceDiff = $entry['compliance_diff'];
                @endphp
                <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden transition-all hover:border-primary/40">
                    {{-- Session Header --}}
                    <div class="p-4 bg-muted/40 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $session->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' }}">
                                <i class="fa-solid {{ $session->status === 'completed' ? 'fa-check' : 'fa-xmark' }} text-sm"></i>
                            </div>
                            <div>
                                <div class="font-bold text-foreground text-sm flex items-center gap-2">
                                    <span>Sesi Scan #{{ $session->id }}</span>
                                    <span class="text-xs font-normal text-muted-foreground">
                                        ({{ $session->started_at ? $session->started_at->format('d F Y, H:i') : '-' }})
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground flex items-center gap-3 mt-0.5">
                                    <span><i class="fa-solid fa-bolt mr-1 text-amber-500"></i>{{ str_replace('_', ' ', $session->trigger) }}</span>
                                    <span><i class="fa-solid fa-cube mr-1 text-blue-500"></i>{{ $session->software_count ?? count($session->softwareResults) }} Software</span>
                                    @if($session->started_at && $session->completed_at)
                                        <span><i class="fa-solid fa-stopwatch mr-1 text-zinc-500"></i>{{ $session->started_at->diffInSeconds($session->completed_at) }} dtk</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('monitoring.show', $session) }}" class="inline-flex items-center justify-center h-8 px-3 text-xs font-medium rounded-md border border-input bg-background hover:bg-accent text-primary">
                                <i class="fa-solid fa-eye mr-1.5"></i> Lihat Rincian Scan
                            </a>
                        </div>
                    </div>

                    {{-- Session Content --}}
                    <div class="p-4 space-y-4">
                        @if($session->status === 'failed')
                            <div class="text-xs text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 p-3 rounded">
                                <i class="fa-solid fa-circle-exclamation mr-1.5"></i>
                                Gagal: {{ $session->error_message ?? 'Terjadi kesalahan saat memproses hasil pemindaian.' }}
                            </div>
                        @else
                            {{-- Change summary badges --}}
                            <div class="flex flex-wrap items-center gap-2">
                                @if($diff['summary']['total_changes'] > 0)
                                    @if($diff['summary']['added_count'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            +{{ $diff['summary']['added_count'] }} Baru
                                        </span>
                                    @endif
                                    @if($diff['summary']['removed_count'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            -{{ $diff['summary']['removed_count'] }} Hilang
                                        </span>
                                    @endif
                                    @if($diff['summary']['changed_count'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            ↑ {{ $diff['summary']['changed_count'] }} Versi Berubah
                                        </span>
                                    @endif
                                    @if($diff['summary']['returned_count'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            ↺ {{ $diff['summary']['returned_count'] }} Terpasang Kembali
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-muted-foreground flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-emerald-600 text-[11px]"></i>
                                        Tidak ada perubahan software dari scan sebelumnya
                                    </span>
                                @endif
                            </div>

                            {{-- Detailed changed items list --}}
                            @if($diff['summary']['total_changes'] > 0)
                                <div class="bg-muted/30 rounded p-3 text-xs space-y-1.5 border border-border/60">
                                    <div class="font-semibold text-muted-foreground uppercase text-[10px] tracking-wider mb-1">Daftar Perubahan yang Terdeteksi:</div>
                                    @foreach($diff['added'] as $item)
                                        <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                            <span class="font-medium">{{ $item['raw_name'] }}</span>
                                            <span class="font-mono text-[11px] text-muted-foreground">({{ $item['version'] ?? '-' }})</span>
                                        </div>
                                    @endforeach

                                    @foreach($diff['returned'] as $item)
                                        <div class="flex items-center gap-2 text-blue-800 dark:text-blue-300">
                                            <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                            <span class="font-medium">{{ $item['raw_name'] }}</span>
                                            <span class="font-mono text-[11px] text-muted-foreground">({{ $item['version'] ?? '-' }}) - Terpasang Kembali</span>
                                        </div>
                                    @endforeach

                                    @foreach($diff['changed'] as $item)
                                        <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300">
                                            <i class="fa-solid fa-arrow-up-right-dots text-[10px]"></i>
                                            <span class="font-medium">{{ $item['raw_name'] }}:</span>
                                            <span class="font-mono text-[11px]">{{ $item['old_version'] ?? '-' }} → <span class="font-bold">{{ $item['new_version'] ?? '-' }}</span></span>
                                        </div>
                                    @endforeach

                                    @foreach($diff['removed'] as $item)
                                        <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 line-through">
                                            <i class="fa-solid fa-minus text-[10px]"></i>
                                            <span>{{ $item['raw_name'] }}</span>
                                            <span class="font-mono text-[11px]">({{ $item['version'] ?? '-' }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Software list snippet --}}
                            <details class="text-xs">
                                <summary class="cursor-pointer text-muted-foreground hover:text-foreground font-medium py-1 select-none">
                                    Lihat {{ count($session->softwareResults) }} Software yang Terpasang pada Sesi Ini
                                </summary>
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-2 border-t border-border">
                                    @foreach($session->softwareResults as $sw)
                                        <div class="p-1.5 bg-background rounded border border-border truncate flex items-center justify-between">
                                            <span class="truncate font-medium text-foreground mr-2">{{ $sw->raw_name }}</span>
                                            <span class="font-mono text-[10px] text-muted-foreground shrink-0">{{ $sw->version ?? '-' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-card border border-border p-12 rounded-lg text-center text-muted-foreground">
                    <i class="fa-solid fa-timeline text-4xl mb-3 text-muted-foreground/30 block"></i>
                    Belum ada sesi pemindaian yang tercatat untuk komputer ini.
                </div>
            @endforelse
        </div>

        @if($sessions->hasPages())
            <div class="p-4 bg-card border border-border rounded-lg shadow-sm">
                {{ $sessions->links() }}
            </div>
        @endif

    </div>
</x-layout.app>
