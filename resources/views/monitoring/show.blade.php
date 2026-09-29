<x-layout.app title="Detail Scan Session" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Monitoring', 'url' => route('monitoring.index')], ['name' => 'Detail Scan #' . $scanSession->id, 'url' => null]]">
    <div class="space-y-6" x-data="{ activeTab: 'software' }">

        {{-- Header & Meta --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-foreground">Scan Session #{{ $scanSession->id }}</h1>
                    @if($scanSession->status === 'completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check mr-1.5 text-[10px]"></i> Selesai
                        </span>
                    @elseif($scanSession->status === 'failed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                            <i class="fa-solid fa-circle-xmark mr-1.5 text-[10px]"></i> Gagal
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ ucfirst($scanSession->status) }}
                        </span>
                    @endif
                </div>
                <p class="text-xs font-mono text-muted-foreground mt-1">UUID: {{ $scanSession->scan_uuid }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($scanSession->computer)
                    <a href="{{ route('computers.history', $scanSession->computer) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i> Timeline PC Ini
                    </a>
                @endif
                <a href="{{ route('monitoring.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>

        {{-- Error Alert if Failed --}}
        @if($scanSession->status === 'failed' && $scanSession->error_message)
            <div class="p-4 bg-rose-50 border border-rose-200 dark:bg-rose-950/30 dark:border-rose-900/50 rounded-lg text-rose-900 dark:text-rose-200 text-sm">
                <div class="font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Pesan Kesalahan Sesi Pemindaian
                </div>
                <div class="mt-1 font-mono text-xs bg-rose-100 dark:bg-rose-900/40 p-2 rounded mt-2">
                    {{ $scanSession->error_message }}
                </div>
            </div>
        @endif

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Komputer</div>
                <div class="font-semibold text-foreground text-sm mt-1 truncate">{{ $scanSession->computer?->hostname ?? '-' }}</div>
                <div class="text-[11px] text-muted-foreground">{{ $scanSession->computer?->ip_address ?? '-' }}</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Laboratorium</div>
                <div class="font-semibold text-foreground text-sm mt-1 truncate">{{ $scanSession->computer?->laboratory?->name ?? '-' }}</div>
                <div class="text-[11px] text-muted-foreground">{{ $scanSession->computer?->laboratory?->code ?? '-' }}</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Waktu Mulai</div>
                <div class="font-semibold text-foreground text-sm mt-1">{{ $scanSession->started_at ? $scanSession->started_at->format('d M Y, H:i:s') : '-' }}</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Durasi Eksekusi</div>
                <div class="font-semibold text-foreground text-sm mt-1">
                    @if($scanSession->started_at && $scanSession->completed_at)
                        {{ $scanSession->started_at->diffInSeconds($scanSession->completed_at) }} detik
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Trigger & Versi</div>
                <div class="font-semibold text-foreground text-sm mt-1 capitalize">{{ str_replace('_', ' ', $scanSession->trigger) }}</div>
                <div class="text-[11px] text-muted-foreground">Agent: {{ $scanSession->agent_version ?? 'v1.0.0' }}</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="text-xs text-muted-foreground">Software Terdata</div>
                <div class="font-bold text-foreground text-xl mt-1">{{ $scanSession->software_count ?? count($scanSession->softwareResults) }}</div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="border-b border-border flex items-center gap-2">
            <button type="button" @click="activeTab = 'software'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px flex items-center gap-2" :class="activeTab === 'software' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">
                <i class="fa-solid fa-cubes"></i>
                Daftar Software ({{ count($scanSession->softwareResults) }})
            </button>
            <button type="button" @click="activeTab = 'compliance'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px flex items-center gap-2" :class="activeTab === 'compliance' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">
                <i class="fa-solid fa-shield-halved"></i>
                Status Kepatuhan ({{ count($scanSession->complianceSnapshots) }})
            </button>
            <button type="button" @click="activeTab = 'changes'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px flex items-center gap-2" :class="activeTab === 'changes' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">
                <i class="fa-solid fa-code-compare"></i>
                Perubahan dari Scan Sebelumnya
                @if($diff['summary']['total_changes'] > 0)
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-primary/10 text-primary font-bold">
                        {{ $diff['summary']['total_changes'] }}
                    </span>
                @endif
            </button>
        </div>

        {{-- Tab 1: Software List --}}
        <div x-show="activeTab === 'software'" class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                        <tr>
                            <th class="px-4 py-3">Nama Software</th>
                            <th class="px-4 py-3">Versi</th>
                            <th class="px-4 py-3">Vendor / Penerbit</th>
                            <th class="px-4 py-3">Tanggal Install</th>
                            <th class="px-4 py-3">Katalog Sistem</th>
                            <th class="px-4 py-3">Kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($scanSession->softwareResults as $item)
                            <tr class="hover:bg-muted/30">
                                <td class="px-4 py-2.5 font-medium text-foreground">{{ $item->raw_name }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs">{{ $item->version ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-muted-foreground text-xs">{{ $item->vendor ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-muted-foreground text-xs">{{ $item->install_date ? $item->install_date->format('d M Y') : '-' }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    @if($item->catalog)
                                        <span class="text-emerald-700 dark:text-emerald-300 font-medium">
                                            <i class="fa-solid fa-check-circle mr-1"></i> Terdaftar
                                        </span>
                                    @else
                                        <span class="text-muted-foreground">Tidak terdaftar</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $item->catalog?->category ?? 'Lainnya' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    Tidak ada data software yang tersimpan untuk scan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 2: Compliance Snapshots --}}
        <div x-show="activeTab === 'compliance'" style="display: none;" class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                        <tr>
                            <th class="px-4 py-3">Nama Software</th>
                            <th class="px-4 py-3">Versi</th>
                            <th class="px-4 py-3">Status Kepatuhan</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3">Lisensi Terkait</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($scanSession->complianceSnapshots as $snap)
                            <tr class="hover:bg-muted/30">
                                <td class="px-4 py-2.5 font-medium text-foreground">{{ $snap->software_name }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs">{{ $snap->software_version ?? '-' }}</td>
                                <td class="px-4 py-2.5">
                                    @if(in_array($snap->status, ['compliant', 'licensed']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-check mr-1 text-[10px]"></i> Berlisensi
                                        </span>
                                    @elseif(in_array($snap->status, ['freeware', 'opensource']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            <i class="fa-solid fa-circle-info mr-1 text-[10px]"></i> Freeware / OSS
                                        </span>
                                    @elseif($snap->status === 'unlicensed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            <i class="fa-solid fa-triangle-exclamation mr-1 text-[10px]"></i> Tidak Berlisensi
                                        </span>
                                    @elseif($snap->status === 'blocked')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                            <i class="fa-solid fa-ban mr-1 text-[10px]"></i> Ilegal / Dilarang
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ ucfirst($snap->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs text-muted-foreground">{{ $snap->keterangan ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    @if($snap->licenseInventory)
                                        <span class="font-medium text-foreground">{{ $snap->licenseInventory->software_name }}</span>
                                    @else
                                        <span class="text-muted-foreground">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                    Tidak ada snapshot kepatuhan untuk scan session ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 3: Diff / Perubahan --}}
        <div x-show="activeTab === 'changes'" style="display: none;" class="space-y-4">
            @if(! $diff['previous_session'])
                <div class="bg-card border border-border p-6 rounded-lg text-center text-muted-foreground">
                    <i class="fa-solid fa-flag-checkered text-3xl mb-2 text-muted-foreground/40 block"></i>
                    Ini adalah pemindaian pertama yang tercatat untuk komputer ini. Tidak ada data pemindaian sebelumnya untuk dibandingkan.
                </div>
            @else
                <div class="bg-card border border-border p-4 rounded-lg flex items-center justify-between text-xs text-muted-foreground">
                    <div>
                        Membandingkan dengan scan sebelumnya:
                        <span class="font-semibold text-foreground">#{{ $diff['previous_session']->id }}</span>
                        ({{ $diff['previous_session']->started_at ? $diff['previous_session']->started_at->format('d M Y, H:i') : '-' }})
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-emerald-700 dark:text-emerald-400 font-semibold">+{{ $diff['summary']['added_count'] }} Baru</span>
                        <span class="text-rose-700 dark:text-rose-400 font-semibold">-{{ $diff['summary']['removed_count'] }} Hilang</span>
                        <span class="text-amber-700 dark:text-amber-400 font-semibold">↑ {{ $diff['summary']['changed_count'] }} Versi Berubah</span>
                        <span class="text-blue-700 dark:text-blue-400 font-semibold">↺ {{ $diff['summary']['returned_count'] }} Kembali</span>
                    </div>
                </div>

                <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-border font-semibold text-sm">
                        Rincian Perubahan Software
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                                <tr>
                                    <th class="px-4 py-3">Tipe Perubahan</th>
                                    <th class="px-4 py-3">Nama Software</th>
                                    <th class="px-4 py-3">Versi Sebelumnya</th>
                                    <th class="px-4 py-3">Versi Saat Ini</th>
                                    <th class="px-4 py-3">Vendor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                {{-- Added --}}
                                @foreach($diff['added'] as $item)
                                    <tr class="bg-emerald-50/40 dark:bg-emerald-950/20">
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                                <i class="fa-solid fa-plus mr-1"></i> Baru Ditambahkan
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 font-medium text-foreground">{{ $item['raw_name'] }}</td>
                                        <td class="px-4 py-2.5 text-muted-foreground">-</td>
                                        <td class="px-4 py-2.5 font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ $item['version'] ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-muted-foreground text-xs">{{ $item['vendor'] ?? '-' }}</td>
                                    </tr>
                                @endforeach

                                {{-- Returned --}}
                                @foreach($diff['returned'] as $item)
                                    <tr class="bg-blue-50/40 dark:bg-blue-950/20">
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                                <i class="fa-solid fa-rotate-left mr-1"></i> Terpasang Kembali
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 font-medium text-foreground">{{ $item['raw_name'] }}</td>
                                        <td class="px-4 py-2.5 text-muted-foreground">(pernah dihapus)</td>
                                        <td class="px-4 py-2.5 font-mono text-xs font-semibold text-blue-700 dark:text-blue-400">{{ $item['version'] ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-muted-foreground text-xs">{{ $item['vendor'] ?? '-' }}</td>
                                    </tr>
                                @endforeach

                                {{-- Version Changed --}}
                                @foreach($diff['changed'] as $item)
                                    <tr class="bg-amber-50/40 dark:bg-amber-950/20">
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                                                <i class="fa-solid fa-arrow-up-right-dots mr-1"></i> Versi Berubah
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 font-medium text-foreground">{{ $item['raw_name'] }}</td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-muted-foreground">{{ $item['old_version'] ?? '-' }}</td>
                                        <td class="px-4 py-2.5 font-mono text-xs font-semibold text-amber-700 dark:text-amber-400">{{ $item['new_version'] ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-muted-foreground text-xs">{{ $item['vendor'] ?? '-' }}</td>
                                    </tr>
                                @endforeach

                                {{-- Removed --}}
                                @foreach($diff['removed'] as $item)
                                    <tr class="bg-rose-50/40 dark:bg-rose-950/20">
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300">
                                                <i class="fa-solid fa-minus mr-1"></i> Dihapus / Hilang
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 font-medium text-rose-900 dark:text-rose-300 line-through">{{ $item['raw_name'] }}</td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-rose-700 dark:text-rose-400">{{ $item['version'] ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-muted-foreground">-</td>
                                        <td class="px-4 py-2.5 text-muted-foreground text-xs">{{ $item['vendor'] ?? '-' }}</td>
                                    </tr>
                                @endforeach

                                @if($diff['summary']['total_changes'] === 0)
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                            <i class="fa-solid fa-equals text-2xl mb-1 text-muted-foreground/40 block"></i>
                                            Tidak ada perubahan software yang terdeteksi dibandingkan scan sebelumnya.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Compliance Changes --}}
                @if(count($complianceChanges) > 0)
                    <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden mt-6">
                        <div class="p-4 border-b border-border font-semibold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-primary"></i>
                            Perubahan Status Kepatuhan Antar-Scan
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-muted/50 text-muted-foreground uppercase text-xs border-b border-border">
                                    <tr>
                                        <th class="px-4 py-3">Nama Software</th>
                                        <th class="px-4 py-3">Versi</th>
                                        <th class="px-4 py-3">Status Sebelumnya</th>
                                        <th class="px-4 py-3">Status Saat Ini</th>
                                        <th class="px-4 py-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($complianceChanges as $cc)
                                        <tr>
                                            <td class="px-4 py-2.5 font-medium text-foreground">{{ $cc['software_name'] }}</td>
                                            <td class="px-4 py-2.5 font-mono text-xs">{{ $cc['software_version'] ?? '-' }}</td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                                                    {{ $cc['old_status'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ in_array($cc['new_status'], ['compliant', 'licensed']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                                    {{ $cc['new_status'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-muted-foreground">{{ $cc['new_keterangan'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-layout.app>
