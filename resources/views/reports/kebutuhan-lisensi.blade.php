<x-layout.app title="Laporan Analisis Kebutuhan dan Alokasi Lisensi Software" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Pusat Laporan', 'url' => route('reports')],
    ['name' => 'Kebutuhan Lisensi', 'url' => null],
]">
    <div class="space-y-6">

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card border border-border p-6 rounded-lg shadow-sm">
            <div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary mb-2">
                    <i class="fa-solid fa-chart-pie"></i> Laporan Strategis & Pengadaan
                </span>
                <h1 class="text-2xl font-bold text-foreground">Laporan Analisis Kebutuhan dan Alokasi Lisensi Software</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Dokumen pendukung pengambilan keputusan pengelolaan dan pengadaan lisensi di lingkungan Universitas Sembilanbelas November Kolaka.
                </p>
                @if ($isPimpinan)
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">
                        <i class="fa-solid fa-circle-info mr-1"></i> Mode Pimpinan: Menampilkan data instalasi dari laboratorium berstatus terverifikasi (Approved).
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']) }}" target="_blank">
                    <x-ui.button variant="outline" class="text-rose-600 border-rose-200 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                        <i class="fa-solid fa-file-pdf mr-2"></i> Ekspor PDF
                    </x-ui.button>
                </a>
                <a href="{{ route('reports.kebutuhan-lisensi.export', ['format' => 'excel']) }}">
                    <x-ui.button variant="outline" class="text-emerald-600 border-emerald-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/30">
                        <i class="fa-solid fa-file-excel mr-2"></i> Ekspor Excel
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Bagian I: Ringkasan Kapasitas Lisensi Universitas --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-foreground">Bagian I: Ringkasan Kapasitas Lisensi Universitas</h2>
                    <p class="text-xs text-muted-foreground">Kompilasi total kapasitas lisensi sah, distribusi kursi ke fakultas, dan instalasi riil.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Software Komersial</div>
                    <div class="mt-2 text-2xl font-bold text-foreground">{{ $summary['total_commercial_software'] }}</div>
                    <div class="text-[11px] text-muted-foreground mt-1">Katalog komersial</div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Hak Lisensi USN (Owned)</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $summary['total_owned'] }}</div>
                    <div class="text-[11px] text-muted-foreground mt-1">Total seat sah universitas</div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Teralokasi</div>
                    <div class="mt-2 text-2xl font-bold text-foreground">{{ $summary['total_allocated'] }}</div>
                    <div class="text-[11px] text-muted-foreground mt-1">Seat terdistribusi</div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Sisa Belum Dialokasi</div>
                    <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $summary['total_unallocated'] }}</div>
                    <div class="text-[11px] text-muted-foreground mt-1">Cadangan universitas</div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Terpasang</div>
                    <div class="mt-2 text-2xl font-bold text-foreground">{{ $summary['total_installed'] }}</div>
                    <div class="text-[11px] text-muted-foreground mt-1">Unit terdeteksi aktif</div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Defisit Bersih USN</div>
                    <div class="mt-2 text-2xl font-bold {{ $summary['total_deficit'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $summary['total_deficit'] }}
                    </div>
                    <div class="text-[11px] text-muted-foreground mt-1">
                        {{ $summary['total_deficit'] > 0 ? 'Kekurangan lisensi sah' : 'Terpenuhi seluruhnya' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Bagian II: Distribusi & Kepatuhan per Fakultas --}}
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-foreground">Bagian II: Distribusi & Kepatuhan per Fakultas</h2>
                <p class="text-xs text-muted-foreground">Detail kuota alokasi kursi, jumlah instalasi laboratorium, dan evaluasi kepatuhan pada setiap fakultas.</p>
            </div>

            @forelse ($facultyDistributions as $dist)
                <div class="rounded-lg border border-border bg-card shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-muted/40 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-base text-foreground">{{ $dist['faculty']->name }}</h3>
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-background border border-border">
                                    {{ $dist['faculty']->code }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                {{ $dist['faculty']->laboratories_count }} Laboratorium &bull; {{ $dist['faculty']->computers_count }} Komputer Terdaftar
                            </p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium">
                            <span>Alokasi: <strong>{{ $dist['total_allocated'] }}</strong></span>
                            <span>Terpasang: <strong>{{ $dist['total_installed'] }}</strong></span>
                            <span class="{{ $dist['total_deficit'] > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : '' }}">
                                Defisit: <strong>{{ $dist['total_deficit'] }}</strong>
                            </span>
                            <span class="{{ $dist['total_surplus'] > 0 ? 'text-blue-600 dark:text-blue-400 font-bold' : '' }}">
                                Surplus: <strong>{{ $dist['total_surplus'] }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <x-ui.table.table>
                            <x-ui.table.table-header>
                                <x-ui.table.table-row>
                                    <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                                    <x-ui.table.table-head>Nama Software</x-ui.table.table-head>
                                    <x-ui.table.table-head class="text-center">Alokasi Seat</x-ui.table.table-head>
                                    <x-ui.table.table-head class="text-center">Terpasang (Scan)</x-ui.table.table-head>
                                    <x-ui.table.table-head class="text-center">Defisit</x-ui.table.table-head>
                                    <x-ui.table.table-head class="text-center">Surplus</x-ui.table.table-head>
                                    <x-ui.table.table-head class="text-center">Status</x-ui.table.table-head>
                                    <x-ui.table.table-head>Rekomendasi Alokasi</x-ui.table.table-head>
                                </x-ui.table.table-row>
                            </x-ui.table.table-header>
                            <x-ui.table.table-body>
                                @forelse ($dist['breakdown'] as $idx => $item)
                                    @php
                                        $hasDef = $item['deficit'] > 0;
                                        $hasSur = $item['surplus'] > 0;
                                    @endphp
                                    <x-ui.table.table-row>
                                        <x-ui.table.table-cell class="text-center font-medium text-xs">{{ $idx + 1 }}</x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="font-semibold text-xs text-foreground">
                                            {{ $item['software_name'] }}
                                        </x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="text-center text-xs font-medium">
                                            {{ $item['allocated'] }}
                                        </x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="text-center text-xs font-medium">
                                            {{ $item['installed'] }}
                                        </x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="text-center text-xs font-bold {{ $hasDef ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground' }}">
                                            {{ $item['deficit'] }}
                                        </x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="text-center text-xs font-bold {{ $hasSur ? 'text-blue-600 dark:text-blue-400' : 'text-muted-foreground' }}">
                                            {{ $item['surplus'] }}
                                        </x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="text-center">
                                            @if ($hasDef)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                    Defisit
                                                </span>
                                            @elseif ($hasSur)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                    Surplus
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    Optimal
                                                </span>
                                            @endif
                                        </x-ui.table.table-cell>
                                        <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                            {{ $item['recommendation'] }}
                                        </x-ui.table.table-cell>
                                    </x-ui.table.table-row>
                                @empty
                                    <x-ui.table.table-row>
                                        <x-ui.table.table-cell colspan="8" class="text-center py-4 text-xs text-muted-foreground">
                                            Tidak ada data penggunaan software komersial pada fakultas ini.
                                        </x-ui.table.table-cell>
                                    </x-ui.table.table-row>
                                @endforelse
                            </x-ui.table.table-body>
                        </x-ui.table.table>
                    </div>
                </div>
            @empty
                <div class="bg-card border border-border rounded-lg p-8 text-center text-muted-foreground">
                    Belum ada data fakultas yang terdaftar dalam sistem.
                </div>
            @endforelse
        </div>

        {{-- Bagian III: Rekapitulasi Defisit & Kebutuhan Pengadaan --}}
        <div class="space-y-3">
            <div>
                <h2 class="text-lg font-bold text-foreground">Bagian III: Rekapitulasi Defisit & Kebutuhan Pengadaan (Procurement Insights)</h2>
                <p class="text-xs text-muted-foreground">Identifikasi software yang defisit di tingkat institusi dan rekomendasi tindak lanjut pengadaan atau redistribusi.</p>
            </div>

            <div class="rounded-lg border border-border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <x-ui.table.table>
                        <x-ui.table.table-header>
                            <x-ui.table.table-row>
                                <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                                <x-ui.table.table-head>Nama Software</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Hak USN</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Alokasi Total</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Terpasang</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Defisit USN</x-ui.table.table-head>
                                <x-ui.table.table-head>Sebaran Defisit Fakultas</x-ui.table.table-head>
                                <x-ui.table.table-head>Catatan Rekomendasi Pengadaan</x-ui.table.table-head>
                            </x-ui.table.table-row>
                        </x-ui.table.table-header>
                        <x-ui.table.table-body>
                            @forelse ($procurementInsights as $idx => $insight)
                                @php
                                    $isDeficit = $insight['net_deficit'] > 0;
                                @endphp
                                <x-ui.table.table-row>
                                    <x-ui.table.table-cell class="text-center font-medium text-xs">{{ $idx + 1 }}</x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="font-bold text-xs text-foreground">
                                        {{ $insight['software_name'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $insight['owned'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center text-xs font-medium">
                                        {{ $insight['allocated'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center text-xs font-medium">
                                        {{ $insight['installed'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center text-xs font-bold {{ $isDeficit ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground' }}">
                                        {{ $insight['net_deficit'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-xs">
                                        @if (! empty($insight['faculty_deficits']))
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($insight['faculty_deficits'] as $fDef)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                        {{ $fDef['faculty_code'] ?? $fDef['faculty_name'] }}: +{{ $fDef['deficit'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-xs text-foreground font-medium max-w-md">
                                        {{ $insight['recommendation'] }}
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @empty
                                <x-ui.table.table-row>
                                    <x-ui.table.table-cell colspan="8" class="text-center py-6 text-muted-foreground text-xs">
                                        Seluruh kepemilikan lisensi komersial institusi berada dalam kapasitas aman.
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @endforelse
                        </x-ui.table.table-body>
                    </x-ui.table.table>
                </div>
            </div>

            <div class="p-4 rounded-lg bg-muted/40 border border-border text-xs text-muted-foreground italic">
                <strong>Catatan Netralitas Ilmiah:</strong> Dokumen analisis ini disusun secara otomatis berdasarkan kalkulasi inventaris lisensi aktif dan data pemindaian komputer di laboratorium sebagai bahan pertimbangan evaluasi dan pengambilan keputusan pengadaan lisensi baru oleh pimpinan institusi.
            </div>
        </div>

    </div>
</x-layout.app>
