<x-layout.app title="Log Perubahan Software" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Monitoring', 'url' => route('monitoring.index')], ['name' => 'Log Perubahan', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Log Perubahan Software</h1>
                <p class="text-muted-foreground mt-1">
                    Daftar seluruh perubahan instalasi software (baru, dihapus, pembaruan versi) yang terdeteksi antar sesi scan
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('monitoring.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                    <i class="fa-solid fa-list-check mr-2"></i> Riwayat Scan
                </a>
            </div>
        </div>

        {{-- Filter Box --}}
        <form method="GET" action="{{ route('monitoring.changes') }}" class="bg-card border border-border p-4 rounded-lg shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
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

                {{-- Tipe Perubahan --}}
                <div class="space-y-1">
                    <x-form.label for="change_type">Tipe Perubahan</x-form.label>
                    <select id="change_type" name="change_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="All">Semua Tipe</option>
                        <option value="added" {{ request('change_type') === 'added' ? 'selected' : '' }}>Baru Ditambahkan</option>
                        <option value="removed" {{ request('change_type') === 'removed' ? 'selected' : '' }}>Dihapus / Hilang</option>
                        <option value="version_changed" {{ request('change_type') === 'version_changed' ? 'selected' : '' }}>Versi Berubah</option>
                        <option value="returned" {{ request('change_type') === 'returned' ? 'selected' : '' }}>Terpasang Kembali</option>
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
                @if(request()->anyFilled(['laboratory_id', 'computer_id', 'change_type', 'period_start', 'period_end']))
                    <a href="{{ route('monitoring.changes') }}">
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

        {{-- Changes Table --}}
        <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                        <tr>
                            <th class="px-4 py-3">Waktu Deteksi</th>
                            <th class="px-4 py-3">Laboratorium</th>
                            <th class="px-4 py-3">Komputer</th>
                            <th class="px-4 py-3">Software</th>
                            <th class="px-4 py-3">Tipe Perubahan</th>
                            <th class="px-4 py-3">Rincian Versi</th>
                            <th class="px-4 py-3 text-right">Sesi Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($changes as $change)
                            <tr class="hover:bg-muted/30">
                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-muted-foreground">
                                    {{ $change['scanned_at'] ? \Carbon\Carbon::parse($change['scanned_at'])->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ $change['laboratory_name'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap font-medium text-foreground">
                                    {{ $change['computer_hostname'] }}
                                </td>
                                <td class="px-4 py-2.5 font-medium text-foreground">
                                    {{ $change['raw_name'] }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if($change['type'] === 'added')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-plus mr-1"></i> Baru
                                        </span>
                                    @elseif($change['type'] === 'removed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            <i class="fa-solid fa-minus mr-1"></i> Dihapus
                                        </span>
                                    @elseif($change['type'] === 'version_changed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            <i class="fa-solid fa-arrow-up-right-dots mr-1"></i> Versi Berubah
                                        </span>
                                    @elseif($change['type'] === 'returned')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            <i class="fa-solid fa-rotate-left mr-1"></i> Terpasang Kembali
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs font-mono">
                                    @if($change['type'] === 'version_changed')
                                        <span class="text-muted-foreground">{{ $change['old_version'] ?? '-' }}</span>
                                        <i class="fa-solid fa-arrow-right mx-1 text-muted-foreground/60 text-[10px]"></i>
                                        <span class="font-semibold text-amber-700 dark:text-amber-400">{{ $change['new_version'] ?? '-' }}</span>
                                    @elseif($change['type'] === 'removed')
                                        <span class="line-through text-rose-600 dark:text-rose-400">{{ $change['version'] ?? '-' }}</span>
                                    @else
                                        <span class="font-semibold text-foreground">{{ $change['version'] ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    <a href="{{ route('monitoring.show', $change['scan_session_id']) }}" class="inline-flex items-center justify-center h-8 px-2 text-xs rounded hover:bg-accent text-primary">
                                        <i class="fa-solid fa-eye mr-1"></i> Sesi #{{ $change['scan_session_id'] }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                                    <i class="fa-solid fa-check-double text-3xl mb-2 text-muted-foreground/40 block"></i>
                                    Tidak ada catatan perubahan software yang terdeteksi pada filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($changes->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $changes->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layout.app>
