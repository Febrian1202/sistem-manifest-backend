<x-layout.app title="Dashboard Eksekutif Pimpinan" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
]">
    <div class="space-y-6">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card border border-border p-6 rounded-lg shadow-sm">
            <div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary mb-2">
                    <i class="fa-solid fa-crown"></i> Ringkasan Eksekutif
                </span>
                <h1 class="text-2xl font-bold text-foreground">Dashboard Eksekutif Pimpinan</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Menampilkan data kepatuhan resmi dari laboratorium yang laporannya telah disetujui (Approved) untuk periode <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $currentPeriod)->translatedFormat('F Y') }}</strong>.
                </p>
            </div>
            <div>
                <a href="{{ route('reports') }}">
                    <x-ui.button>
                        <i class="fa-solid fa-file-pdf mr-2"></i> Pusat Laporan
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Stat Cards Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Laboratorium Disetujui</span>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-md">
                        <i class="fa-solid fa-flask-vial"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">
                    {{ $stats['approved_labs'] }} <span class="text-xs font-normal text-muted-foreground">/ {{ $stats['total_labs'] }} lab</span>
                </div>
                <div class="text-xs text-muted-foreground mt-1">
                    {{ $stats['total_labs'] > 0 ? round(($stats['approved_labs'] / $stats['total_labs']) * 100) : 0 }}% terverifikasi bulan ini
                </div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Komputer Terverifikasi</span>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-md">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['total_computers'] }} <span class="text-xs font-normal text-muted-foreground">unit</span></div>
                <div class="text-xs text-muted-foreground mt-1">Dari lab berstatus approved</div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">OS Berlisensi Resmi</span>
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-md">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['licensed_os'] }} <span class="text-xs font-normal text-muted-foreground">unit</span></div>
                <div class="text-xs text-muted-foreground mt-1">Status OS Licensed valid</div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Tingkat Kepatuhan OS</span>
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-md">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                </div>
                <div class="mt-3 text-2xl font-bold text-foreground">{{ $stats['compliance_rate'] }}%</div>
                <div class="text-xs text-muted-foreground mt-1">Rata-rata lab yang approved</div>
            </div>
        </div>

        {{-- Tabel Status Laporan Seluruh Laboratorium --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-foreground">Status Verifikasi Seluruh Laboratorium</h2>
                    <p class="text-xs text-muted-foreground">Progres evaluasi laporan kepatuhan laboratorium pada periode berjalan.</p>
                </div>
            </div>

            <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                            <x-ui.table.table-head>Laboratorium</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Total Unit</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Status Laporan Periode Ini</x-ui.table.table-head>
                            <x-ui.table.table-head>Catatan Evaluasi</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($laboratoriesStatus as $index => $lab)
                            @php
                                $approval = $lab->reportApprovals->first();
                                $status = $approval?->status;
                            @endphp
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="text-center font-medium">{{ $index + 1 }}</x-ui.table.table-cell>
                                
                                <x-ui.table.table-cell>
                                    <div class="font-semibold text-foreground">{{ $lab->name }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">{{ $lab->code }} &bull; {{ $lab->building ?? '-' }} Lt. {{ $lab->floor ?? '-' }}</div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center font-medium">
                                    {{ $lab->computers_count }} unit
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    @if ($status === 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-circle-check"></i> Disetujui
                                        </span>
                                    @elseif ($status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            <i class="fa-solid fa-clock"></i> Menunggu Review
                                        </span>
                                    @elseif ($status === 'rejected')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs text-muted-foreground bg-muted">
                                            Belum Dikirim
                                        </span>
                                    @endif
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-xs text-muted-foreground max-w-sm">
                                    {{ $approval?->notes ?: '-' }}
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="5" class="text-center py-6 text-muted-foreground">
                                    Belum ada data laboratorium terdaftar.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>
        </div>

    </div>
</x-layout.app>
