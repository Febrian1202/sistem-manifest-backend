<x-layout.app title="Audit Kepatuhan" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Audit Kepatuhan', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header Laporan --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Audit Kepatuhan Lisensi</h1>
            <p class="text-muted-foreground mt-1">
                Deteksi otomatis penggunaan perangkat lunak komersial ilegal di lingkungan USN Kolaka.
            </p>
        </div>

        {{-- Stat Cards Kepatuhan --}}
        <x-compliance.card-section :stats="$stats" />

        {{-- Tabel Audit --}}
        <x-compliance.table :softwares="$softwares" />
    </div>
</x-layout.app>