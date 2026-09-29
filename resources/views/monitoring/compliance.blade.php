<x-layout.app title="Histori Kepatuhan Software" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Monitoring', 'url' => route('monitoring.index')], ['name' => 'Histori Kepatuhan', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Histori Kepatuhan Software</h1>
                <p class="text-muted-foreground mt-1">
                    Rekaman snapshot status kepatuhan dan audit lisensi software perangkat laboratorium dari waktu ke waktu
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('monitoring.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                    <i class="fa-solid fa-list-check mr-2"></i> Riwayat Scan
                </a>
            </div>
        </div>

        {{-- Filter Box --}}
        <form method="GET" action="{{ route('monitoring.compliance') }}" class="bg-card border border-border p-4 rounded-lg shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- Search --}}
                <div class="space-y-1">
                    <x-form.label for="search">Cari Software / PC</x-form.label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                        <x-form.input id="search" name="search" value="{{ request('search') }}" placeholder="Nama software / PC..." class="pl-9 w-full" />
                    </div>
                </div>

                {{-- Laboratorium --}}
                @unless(auth()->user()->hasRole('kepala_lab'))
                <div class="space-y-1">
                    <x-form.label for="laboratory_id">Laboratorium</x-form.label>
                    <select id="laboratory_id" name="laboratory_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Lab</option>
                        @foreach($laboratories as $lab)
                            <option value="{{ $lab->id }}" {{ request('laboratory_id') == $lab->id ? 'selected' : '' }}>
                                {{ $lab->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endunless

                {{-- Komputer --}}
                <div class="space-y-1">
                    <x-form.label for="computer_id">Perangkat</x-form.label>
                    <select id="computer_id" name="computer_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Perangkat</option>
                        @foreach($computers as $pc)
                            <option value="{{ $pc->id }}" {{ request('computer_id') == $pc->id ? 'selected' : '' }}>
                                {{ $pc->hostname }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="space-y-1">
                    <x-form.label for="status">Status Kepatuhan</x-form.label>
                    <select id="status" name="status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Status</option>
                        <option value="compliant" {{ request('status') === 'compliant' ? 'selected' : '' }}>Berlisensi (Compliant)</option>
                        <option value="freeware" {{ request('status') === 'freeware' ? 'selected' : '' }}>Freeware</option>
                        <option value="opensource" {{ request('status') === 'opensource' ? 'selected' : '' }}>Open Source</option>
                        <option value="unlicensed" {{ request('status') === 'unlicensed' ? 'selected' : '' }}>Tidak Berlisensi</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Dilarang (Blocked)</option>
                    </select>
                </div>

                {{-- Periode Dari --}}
                <div class="space-y-1">
                    <x-form.label for="period_start">Dari Tanggal</x-form.label>
                    <x-form.input id="period_start" type="date" name="period_start" value="{{ request('period_start') }}" class="w-full" />
                </div>

                {{-- Periode Sampai --}}
                <div class="space-y-1">
                    <x-form.label for="period_end">Sampai Tanggal</x-form.label>
                    <x-form.input id="period_end" type="date" name="period_end" value="{{ request('period_end') }}" class="w-full" />
                </div>
            </div>

            <div class="flex justify-end items-center gap-2 pt-2 border-t border-border/50">
                @if(request()->anyFilled(['search', 'laboratory_id', 'computer_id', 'status', 'period_start', 'period_end']))
                    <a href="{{ route('monitoring.compliance') }}">
                        <x-ui.button type="button" variant="outline" class="h-9">
                            <i class="fa-solid fa-xmark mr-2"></i> Reset
                        </x-ui.button>
                    </a>
                @endif
                <x-ui.button type="submit" class="h-9">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                </x-ui.button>
            </div>
        </form>

        {{-- Snapshots Table --}}
        <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                        <tr>
                            <th class="px-4 py-3">Tanggal Scan</th>
                            <th class="px-4 py-3">Komputer</th>
                            <th class="px-4 py-3">Laboratorium</th>
                            <th class="px-4 py-3">Software</th>
                            <th class="px-4 py-3">Versi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3 text-right">Sesi Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($snapshots as $snap)
                            <tr class="hover:bg-muted/30">
                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-muted-foreground">
                                    {{ $snap->scanned_at ? $snap->scanned_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap font-medium text-foreground">
                                    {{ $snap->computer?->hostname ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ $snap->computer?->laboratory?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-medium text-foreground">
                                    {{ $snap->software_name }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-muted-foreground">
                                    {{ $snap->software_version ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if(in_array($snap->status, ['compliant', 'licensed']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-check mr-1 text-[10px]"></i> Berlisensi
                                        </span>
                                    @elseif(in_array($snap->status, ['freeware', 'opensource']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            <i class="fa-solid fa-circle-info mr-1 text-[10px]"></i> Freeware/OSS
                                        </span>
                                    @elseif($snap->status === 'unlicensed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            <i class="fa-solid fa-triangle-exclamation mr-1 text-[10px]"></i> Tidak Berlisensi
                                        </span>
                                    @elseif($snap->status === 'blocked')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                            <i class="fa-solid fa-ban mr-1 text-[10px]"></i> Dilarang
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ ucfirst($snap->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs text-muted-foreground max-w-xs truncate" title="{{ $snap->keterangan }}">
                                    {{ $snap->keterangan ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    @if($snap->scan_session_id)
                                        <a href="{{ route('monitoring.show', $snap->scan_session_id) }}" class="inline-flex items-center justify-center h-8 px-2 text-xs rounded hover:bg-accent text-primary">
                                            <i class="fa-solid fa-eye mr-1"></i> Sesi #{{ $snap->scan_session_id }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">
                                    <i class="fa-solid fa-shield-halved text-3xl mb-2 text-muted-foreground/40 block"></i>
                                    Tidak ada data snapshot kepatuhan yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($snapshots->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $snapshots->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layout.app>
