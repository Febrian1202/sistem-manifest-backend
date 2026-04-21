<x-layout.app title="Audit Kepatuhan" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Audit Kepatuhan', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header Laporan --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Audit Kepatuhan Lisensi</h1>
            <p class="text-muted-foreground mt-1">
                Deteksi otomatis penggunaan perangkat lunak komersial ilegal di lingkungan USN Kolaka.
            </p>
            <p class="text-xs text-gray-400 italic mt-1">
                Terakhir diperbarui: {{ now()->timezone('Asia/Makassar')->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </div>

        {{-- Stat Cards Kepatuhan --}}
        <x-compliance.card-section :stats="$stats" />

        <div x-data="{ activeTab: 'semua' }" class="space-y-4">
            {{-- Filter Tabs --}}
            <div class="flex border-b border-border">
                <button 
                    @click="activeTab = 'semua'"
                    :class="activeTab === 'semua' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm transition-colors"
                >
                    Semua ({{ $totalCount }})
                </button>
                <button 
                    @click="activeTab = 'tidak-patuh'"
                    :class="activeTab === 'tidak-patuh' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm transition-colors"
                >
                    Tidak Patuh ({{ $nonCompliantCount }})
                </button>
                <button 
                    @click="activeTab = 'patuh'"
                    :class="activeTab === 'patuh' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm transition-colors"
                >
                    Patuh ({{ $compliantCount }})
                </button>
            </div>

            {{-- Tabel Audit --}}
            <x-compliance.table :softwares="$softwares" />
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $softwares->links() }}
        </div>
    </div>
</x-layout.app>