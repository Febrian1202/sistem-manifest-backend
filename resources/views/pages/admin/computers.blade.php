<x-layout.app title="Data Komputer" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Data Komputer', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Data Komputer</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola dan pantau seluruh komputer terdaftar
                </p>
            </div>
        </div>

        @if (session('status'))
            <x-ui.toast type="success" message="{{ session('status') }}" duration="5000" />
        @endif

        {{-- Search dan filter --}}
        <div class="flex items-center gap-4">
            <div class="relative flex-1 max-w-md">
                <i
                    class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-input placeholder="Search..." class="w-10 pl-9" />
            </div>
        </div>

        {{-- Table Komponen --}}
        <x-computers.table :computers="$computers" />

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $computers->links() }}
        </div>
    </div>
</x-layout.app>
