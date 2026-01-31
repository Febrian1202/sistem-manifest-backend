<x-layout.app title="Audit Kepatuhan" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Audit Kepatuhan', 'url' => null]]">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground flex items-center"><i
                        class="fa-solid fa-shield-halved text-2xl mr-2 text-primary"></i>Audit Kepatuhan</h1>
                <p class="text-muted-foreground mt-1">
                    Bandingkan inventaris lisensi dengan instalasi perangkat lunak aktual
                </p>
            </div>
        </div>
    </div>
</x-layout.app>
