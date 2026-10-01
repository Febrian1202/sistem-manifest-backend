<x-layout.app title="Audit Kepatuhan" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Audit Kepatuhan', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header Laporan --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Audit Kepatuhan Lisensi</h1>
                <p class="text-muted-foreground mt-1">
                    {{ $selectedFaculty ? "Analisis kepatuhan lisensi perangkat lunak di {$selectedFaculty->name}" : 'Deteksi otomatis penggunaan perangkat lunak komersial ilegal di lingkungan USN Kolaka.' }}
                </p>
                <p class="text-xs text-gray-400 italic mt-1">
                    Terakhir diperbarui: {{ now()->timezone('Asia/Makassar')->translatedFormat('d F Y, H:i') }} WITA
                </p>
            </div>

            {{-- Filter Fakultas --}}
            @if(empty($isKepalaLab))
                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('compliance') }}" class="flex items-center gap-2">
                        <div class="relative">
                            <select 
                                name="faculty_id" 
                                onchange="this.form.submit()"
                                class="h-9 text-xs font-medium rounded-lg border border-border bg-card px-3 py-1 pr-8 text-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer"
                            >
                                <option value="">Semua Fakultas (Tingkat Universitas)</option>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>
                                        {{ $faculty->name }} ({{ $faculty->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if(request('faculty_id'))
                            <a href="{{ route('compliance') }}" class="inline-flex items-center justify-center h-9 px-3 text-xs font-medium text-muted-foreground hover:text-foreground border border-border rounded-lg bg-card hover:bg-muted transition-colors shrink-0" title="Reset Filter">
                                <i class="fa-solid fa-xmark mr-1"></i> Reset
                            </a>
                        @endif
                    </form>
                </div>
            @endif
        </div>

        @if(isset($isPimpinan) && $isPimpinan && isset($hasApprovedLabs) && ! $hasApprovedLabs)
            <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-md">
                <div class="flex">
                    <div class="shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-amber-800">Belum ada laporan yang disetujui untuk periode ini.</p>
                        <p class="text-xs text-amber-700 mt-0.5">Data audit kepatuhan pimpinan hanya memuat komputer dari laboratorium yang laporannya telah disetujui (Approved) oleh Penanggung Jawab Laboratorium untuk periode {{ \Carbon\Carbon::createFromFormat('Y-m', $currentPeriod)->translatedFormat('F Y') }}.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Stat Cards Kepatuhan (Disesuaikan dengan cakupan Fakultas / Universitas) --}}
        @if($selectedFaculty)
            <x-compliance.faculty-summary-card :stats="$stats" :faculty="$selectedFaculty" />
        @else
            <x-compliance.card-section :stats="$stats" />
        @endif

        {{-- Matriks Ringkasan Antar-Fakultas (Hanya tampil pada Mode Universitas Global) --}}
        @if(!$selectedFaculty && empty($isKepalaLab) && isset($crossFacultyMatrix) && $crossFacultyMatrix->isNotEmpty())
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-foreground">Matriks Kepatuhan Antar-Fakultas</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Perbandingan kuota alokasi kursi terhadap instalasi riil software komersial per fakultas</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary w-fit">
                        <i class="fa-solid fa-diagram-project text-[10px]"></i> {{ $crossFacultyMatrix->count() }} Fakultas
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <x-ui.table.table>
                        <x-ui.table.table-header>
                            <x-ui.table.table-row class="bg-muted/40">
                                <x-ui.table.table-head>Fakultas</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Laboratorium</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Komputer</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Alokasi Kursi</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Terpasang</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Defisit</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Surplus</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-center">Status Audit</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
                            </x-ui.table.table-row>
                        </x-ui.table.table-header>
                        <x-ui.table.table-body>
                            @foreach($crossFacultyMatrix as $row)
                                <x-ui.table.table-row class="{{ $row['total_deficit'] > 0 ? 'bg-destructive/5' : '' }}">
                                    <x-ui.table.table-cell class="font-medium">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold rounded bg-muted text-muted-foreground border border-border">
                                                {{ $row['faculty_code'] }}
                                            </span>
                                            <span class="text-sm text-foreground">{{ $row['faculty_name'] }}</span>
                                        </div>
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center text-xs text-muted-foreground">
                                        {{ $row['total_labs'] }} Lab
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center text-xs text-muted-foreground">
                                        {{ $row['total_computers'] }} PC
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center font-semibold text-foreground">
                                        {{ $row['total_allocated_seats'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center font-bold text-foreground">
                                        {{ $row['total_installed_seats'] }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center">
                                        @if($row['total_deficit'] > 0)
                                            <span class="text-destructive font-bold text-sm">-{{ $row['total_deficit'] }}</span>
                                        @else
                                            <span class="text-muted-foreground text-xs">-</span>
                                        @endif
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center">
                                        @if($row['total_surplus'] > 0)
                                            <span class="text-blue-600 font-semibold text-xs">+{{ $row['total_surplus'] }}</span>
                                        @else
                                            <span class="text-muted-foreground text-xs">-</span>
                                        @endif
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-center">
                                        @if($row['total_deficit'] > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20">
                                                {{ $row['non_compliant_software_count'] }} Defisit
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 border border-green-300">
                                                Patuh
                                            </span>
                                        @endif
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-right">
                                        <a href="{{ route('compliance', ['faculty_id' => $row['faculty_id']]) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                            Audit Fakultas <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </a>
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @endforeach
                        </x-ui.table.table-body>
                    </x-ui.table.table-table>
                </div>
            </div>
        @endif

        <div x-data="{ activeTab: 'semua' }" class="space-y-4">
            {{-- Filter Tabs --}}
            <div class="flex items-center justify-between border-b border-border">
                <div class="flex">
                    <button 
                        @click="activeTab = 'semua'"
                        :class="activeTab === 'semua' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted-foreground hover:text-foreground'"
                        class="px-4 py-2 text-sm transition-colors"
                    >
                        Semua ({{ $totalCount }})
                    </button>
                    <button 
                        @click="activeTab = 'tidak-patuh'"
                        :class="activeTab === 'tidak-patuh' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted-foreground hover:text-foreground'"
                        class="px-4 py-2 text-sm transition-colors"
                    >
                        Tidak Patuh ({{ $nonCompliantCount }})
                    </button>
                    <button 
                        @click="activeTab = 'patuh'"
                        :class="activeTab === 'patuh' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted-foreground hover:text-foreground'"
                        class="px-4 py-2 text-sm transition-colors"
                    >
                        Patuh ({{ $compliantCount }})
                    </button>
                </div>

                @if($selectedFaculty)
                    <div class="hidden sm:flex items-center gap-2 text-xs text-muted-foreground pb-2">
                        <span>Menampilkan software terdaftar di: <strong>{{ $selectedFaculty->name }}</strong></span>
                    </div>
                @endif
            </div>

            {{-- Tabel Audit --}}
            <x-compliance.table :softwares="$softwares" :selected-faculty="$selectedFaculty" />
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $softwares->links() }}
        </div>
    </div>
</x-layout.app>
