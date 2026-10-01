<x-layout.app title="Riwayat Monitoring" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Monitoring', 'url' => route('monitoring.index')], ['name' => 'Riwayat Scan', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Riwayat Monitoring</h1>
                <p class="text-muted-foreground mt-1">
                    Daftar seluruh riwayat sesi pemindaian (scan session) perangkat komputer laboratorium
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('monitoring.changes') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                    <i class="fa-solid fa-code-compare mr-2"></i> Log Perubahan
                </a>
                <a href="{{ route('monitoring.compliance') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                    <i class="fa-solid fa-shield-halved mr-2"></i> Histori Kepatuhan
                </a>
            </div>
        </div>

        {{-- Filter Box --}}
        <form method="GET" action="{{ route('monitoring.index') }}" class="bg-card border border-border p-4 rounded-lg shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- Search --}}
                <div class="space-y-1">
                    <x-form.label for="search">Cari Komputer</x-form.label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                        <x-form.input id="search" name="search" value="{{ request('search') }}" placeholder="Hostname / IP..." class="pl-9 w-full" />
                    </div>
                </div>

                {{-- Fakultas --}}
                @unless(auth()->user()->hasRole('kepala_lab'))
                <div class="space-y-1">
                    <x-form.label for="faculty_id">Fakultas</x-form.label>
                    <select id="faculty_id" name="faculty_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Fakultas</option>
                        @foreach($faculties as $fac)
                            <option value="{{ $fac->id }}" {{ request('faculty_id') == $fac->id ? 'selected' : '' }}>
                                {{ $fac->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Laboratorium --}}
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
                    <x-form.label for="status">Status Scan</x-form.label>
                    <select id="status" name="status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Status</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                {{-- Trigger --}}
                <div class="space-y-1">
                    <x-form.label for="trigger">Trigger</x-form.label>
                    <select id="trigger" name="trigger" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Trigger</option>
                        <option value="scheduled" {{ request('trigger') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="on_demand" {{ request('trigger') === 'on_demand' ? 'selected' : '' }}>On Demand</option>
                        <option value="manual" {{ request('trigger') === 'manual' ? 'selected' : '' }}>Manual</option>
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
                @if(request()->anyFilled(['search', 'faculty_id', 'laboratory_id', 'computer_id', 'status', 'trigger', 'period_start', 'period_end']))
                    <a href="{{ route('monitoring.index') }}">
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

        {{-- Sessions Table --}}
        <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                        <tr>
                            <th class="px-4 py-3">Waktu Scan</th>
                            <th class="px-4 py-3">Komputer</th>
                            <th class="px-4 py-3">Laboratorium</th>
                            <th class="px-4 py-3">Trigger</th>
                            <th class="px-4 py-3 text-center">Durasi</th>
                            <th class="px-4 py-3 text-center">Software</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($scanSessions as $session)
                            <tr class="hover:bg-muted/40 transition-colors">
                                <td class="px-4 py-3 font-medium whitespace-nowrap">
                                    <div>{{ $session->started_at ? $session->started_at->format('d M Y, H:i') : '-' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $session->scan_uuid }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-foreground">{{ $session->computer?->hostname ?? '-' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $session->computer?->ip_address ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ $session->computer?->laboratory?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="capitalize text-xs text-muted-foreground">
                                        <i class="fa-solid fa-bolt text-amber-500 mr-1"></i>
                                        {{ str_replace('_', ' ', $session->trigger) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-muted-foreground">
                                    @if($session->started_at && $session->completed_at)
                                        {{ $session->started_at->diffInSeconds($session->completed_at) }} dtk
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap font-medium">
                                    {{ $session->software_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($session->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-circle-check mr-1.5 text-[10px]"></i> Selesai
                                        </span>
                                    @elseif($session->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300" title="{{ $session->error_message }}">
                                            <i class="fa-solid fa-circle-xmark mr-1.5 text-[10px]"></i> Gagal
                                        </span>
                                    @elseif($session->status === 'running')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            <i class="fa-solid fa-spinner fa-spin mr-1.5 text-[10px]"></i> Berjalan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                                            <i class="fa-solid fa-clock mr-1.5 text-[10px]"></i> {{ ucfirst($session->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('monitoring.show', $session) }}" class="inline-flex items-center justify-center h-8 px-2 text-xs rounded hover:bg-accent text-primary">
                                            <i class="fa-solid fa-eye mr-1"></i> Detail
                                        </a>
                                        @if($session->computer)
                                            <a href="{{ route('computers.history', $session->computer) }}" class="inline-flex items-center justify-center h-8 px-2 text-xs rounded hover:bg-accent text-muted-foreground">
                                                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Histori PC
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-muted-foreground/40 block"></i>
                                    Tidak ada data riwayat pemindaian yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($scanSessions->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $scanSessions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.app>
