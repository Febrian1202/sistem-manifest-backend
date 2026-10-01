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

        {{-- Filter Bar --}}
        @if ($laboratories->isNotEmpty())
            <x-dashboard.filter-bar :period="$period" :selectedLabId="$selectedLabId" :laboratories="$laboratories" />
        @endif

        {{-- Stat Cards Grid (Metrik Institusi & Lisensi Terdistribusi) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Fakultas</span>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-md text-sm">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $globalStats['total_faculties'] }}</div>
                <div class="text-[11px] text-muted-foreground mt-1">Fakultas terdaftar</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Laboratorium</span>
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-md text-sm">
                        <i class="fa-solid fa-flask"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $globalStats['total_laboratories'] }}</div>
                <div class="text-[11px] text-muted-foreground mt-1">{{ $stats['approved_labs'] }} lab disetujui</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Komputer Aktif</span>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-md text-sm">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $globalStats['total_computers'] }}</div>
                <div class="text-[11px] text-muted-foreground mt-1">{{ $stats['total_computers'] }} unit di lab approved</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Hak Lisensi USN</span>
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-md text-sm">
                        <i class="fa-solid fa-key"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $globalStats['total_owned_licenses'] }}</div>
                <div class="text-[11px] text-muted-foreground mt-1">Total kapasitas owned</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Alokasi Seat</span>
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-md text-sm">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $globalStats['total_allocated_seats'] }}</div>
                <div class="text-[11px] text-muted-foreground mt-1">Seat ke fakultas</div>
            </div>

            <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Defisit Lisensi</span>
                    <div class="p-2 {{ $globalStats['total_software_deficits'] > 0 ? 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' }} rounded-md text-sm">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold {{ $globalStats['total_software_deficits'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $globalStats['total_software_deficits'] }}
                </div>
                <div class="text-[11px] text-muted-foreground mt-1">
                    {{ $globalStats['total_software_deficits'] > 0 ? 'Perlu pengadaan/redistribusi' : 'Seluruh unit terpenuhi' }}
                </div>
            </div>
        </div>

        {{-- Section: Ringkasan Status per Fakultas & Widget Prioritas Pengadaan --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri: Tabel Matriks Komparasi Antarfakultas (2 Kolom) --}}
            <div class="lg:col-span-2 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-bold text-foreground">Status Kepatuhan Antarfakultas</h2>
                        <p class="text-xs text-muted-foreground">Komparasi alokasi vs instalasi riil software berlisensi di seluruh fakultas.</p>
                    </div>
                    <div>
                        <a href="{{ route('compliance') }}" class="text-xs text-primary hover:underline font-semibold inline-flex items-center gap-1">
                            Audit Kepatuhan Lengkap <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                    <x-ui.table.table>
                        <x-ui.table.table-header>
                            <x-ui.table.table-row>
                                <x-ui.table.table-head>Fakultas</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Lab</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Komputer</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Alokasi</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Terpasang</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Defisit</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Surplus</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Status</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center w-[60px]">Aksi</x-ui.table.table-head>
                            </x-ui.table.table-row>
                        </x-ui.table.table-header>
                        <x-ui.table.table-body>
                            @forelse ($facultyMatrix as $facultyRow)
                                @php
                                    $hasDeficit = $facultyRow['total_deficit'] > 0;
                                    $hasSurplus = $facultyRow['total_surplus'] > 0;
                                @endphp
                                <x-ui.table.table-row>
                                    <x-ui.table.table-cell>
                                        <div class="font-semibold text-foreground">{{ $facultyRow['faculty_name'] }}</div>
                                        <div class="text-xs text-muted-foreground font-mono">{{ $facultyRow['faculty_code'] }}</div>
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center text-xs">
                                        {{ $facultyRow['total_labs'] }}
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center text-xs font-medium">
                                        {{ $facultyRow['total_computers'] }} unit
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center text-xs font-semibold">
                                        {{ $facultyRow['total_allocated_seats'] }}
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center text-xs font-semibold">
                                        {{ $facultyRow['total_installed_seats'] }}
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center text-xs font-bold {{ $hasDeficit ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground' }}">
                                        {{ $facultyRow['total_deficit'] }}
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center text-xs font-bold {{ $hasSurplus ? 'text-blue-600 dark:text-blue-400' : 'text-muted-foreground' }}">
                                        {{ $facultyRow['total_surplus'] }}
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center">
                                        @if ($hasDeficit)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Defisit
                                            </span>
                                        @elseif ($hasSurplus)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                <i class="fa-solid fa-circle-info"></i> Surplus
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                <i class="fa-solid fa-circle-check"></i> Optimal
                                            </span>
                                        @endif
                                    </x-ui.table.table-cell>

                                    <x-ui.table.table-cell class="text-center">
                                        <a href="{{ route('compliance', ['faculty_id' => $facultyRow['faculty_id']]) }}"
                                            class="inline-flex items-center justify-center p-1.5 rounded-md hover:bg-muted text-primary transition-colors"
                                            title="Drill-down Kepatuhan {{ $facultyRow['faculty_name'] }}">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                        </a>
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @empty
                                <x-ui.table.table-row>
                                    <x-ui.table.table-cell colspan="9" class="text-center py-6 text-muted-foreground">
                                        Belum ada data fakultas yang terdaftar.
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @endforelse
                        </x-ui.table.table-body>
                    </x-ui.table.table>
                </div>
            </div>

            {{-- Kolom Kanan: Widget Insight Software Prioritas Pengadaan (1 Kolom) --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-foreground">Prioritas Pengadaan</h2>
                        <p class="text-xs text-muted-foreground">Software dengan defisit kumulatif tertinggi.</p>
                    </div>
                </div>

                <div class="bg-card border border-border rounded-lg p-5 shadow-sm space-y-4">
                    @forelse ($topDeficitSoftwares as $topDeficit)
                        <div class="p-3 bg-muted/40 rounded-lg border border-border/60 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold text-sm text-foreground truncate" title="{{ $topDeficit['name'] }}">
                                    {{ $topDeficit['name'] }}
                                </div>
                                <div class="text-[11px] text-muted-foreground flex items-center gap-2 mt-0.5">
                                    <span>Hak USN: <strong>{{ $topDeficit['owned'] }}</strong></span>
                                    <span>&bull;</span>
                                    <span>Terpasang: <strong>{{ $topDeficit['installed'] }}</strong></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                    +{{ $topDeficit['deficit'] }} seat
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-muted-foreground">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2 text-base">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <p class="text-sm font-semibold text-foreground">Tidak Ada Defisit Software</p>
                            <p class="text-xs mt-1">Seluruh software komersial yang terpasang telah memiliki lisensi sah.</p>
                        </div>
                    @endforelse

                    <div class="pt-2 border-t border-border">
                        <a href="{{ route('reports.kebutuhan-lisensi') }}" class="block">
                            <x-ui.button variant="outline" class="w-full text-xs font-semibold justify-center">
                                <i class="fa-solid fa-file-invoice mr-2 text-primary"></i> Buka Laporan Kebutuhan Lisensi
                            </x-ui.button>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        {{-- Trend Charts --}}
        <x-dashboard.trend-charts :chartData="$chartData" :period="$period" />

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
