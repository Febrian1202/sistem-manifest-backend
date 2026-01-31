<x-layout.app title="Katalog Software" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Katalog Software', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Katalog Software</h1>
                <p class="text-muted-foreground mt-1">
                    Tinjau dan kategorikan perangkat lunak yang terdeteksi di seluruh sistem
                </p>
            </div>
        </div>
    </div>
</x-layout.app>
