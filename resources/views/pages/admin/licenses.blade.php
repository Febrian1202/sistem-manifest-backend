<x-layout.app title="Inventaris Lisensi" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Inventaris Lisensi', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Inventaris Lisensi</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola lisensi perangkat lunak yang dibeli dan pantau penggunaannya
                </p>
            </div>
        </div>
    </div>
</x-layout.app>
