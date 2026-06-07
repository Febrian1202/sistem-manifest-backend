<x-layout.app title="Log Aktivitas" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Log Aktivitas', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Log Aktivitas</h1>
            <p class="text-muted-foreground mt-1">
                Monitor log audit sistem dan aktivitas pengguna untuk keamanan dan transparansi.
            </p>
        </div>

        {{-- Pencarian & Filter --}}
        <form method="GET" action="{{ route('activity-logs') }}" class="bg-card border border-border p-4 rounded-lg shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <div class="space-y-1">
                    <x-form.label for="search">Cari</x-form.label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                        <x-form.input id="search" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi..." class="pl-9 w-full" />
                    </div>
                </div>
                
                <div class="space-y-1">
                    <x-form.label for="user_id">Pelaku</x-form.label>
                    <select id="user_id" name="user_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="">Semua Pelaku</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <x-form.label for="entity_type">Entitas</x-form.label>
                    <select id="entity_type" name="entity_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option value="">Semua Entitas</option>
                        <option value="User" {{ request('entity_type') === 'User' ? 'selected' : '' }}>User (Akun)</option>
                        <option value="Computer" {{ request('entity_type') === 'Computer' ? 'selected' : '' }}>Komputer</option>
                        <option value="License" {{ request('entity_type') === 'License' ? 'selected' : '' }}>Lisensi</option>
                        <option value="SoftwareCatalog" {{ request('entity_type') === 'SoftwareCatalog' ? 'selected' : '' }}>Katalog Software</option>
                        <option value="System" {{ request('entity_type') === 'System' ? 'selected' : '' }}>Sistem</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <x-form.label for="start_date">Dari Tanggal</x-form.label>
                    <x-form.input id="start_date" type="date" name="start_date" value="{{ request('start_date') }}" class="w-full" />
                </div>

                <div class="space-y-1">
                    <x-form.label for="end_date">Sampai Tanggal</x-form.label>
                    <x-form.input id="end_date" type="date" name="end_date" value="{{ request('end_date') }}" class="w-full" />
                </div>
            </div>

            <div class="flex justify-end items-center gap-2 pt-4 border-t border-border/50">
                @if(request()->anyFilled(['search', 'user_id', 'entity_type', 'start_date', 'end_date']))
                    <a href="{{ route('activity-logs') }}">
                        <x-ui.button type="button" variant="outline">
                            <i class="fa-solid fa-xmark mr-2"></i> Reset Filter
                        </x-ui.button>
                    </a>
                @endif
                <x-ui.button type="submit">
                    <i class="fa-solid fa-filter mr-2"></i> Terapkan Filter
                </x-ui.button>
            </div>
        </form>

        {{-- Tabel Komponen --}}
        <x-activity-logs.table :logs="$logs" />

        {{-- Pagination --}}
        <div class="mt-4 flex flex-col items-center justify-between gap-4 border-t border-border py-4 sm:flex-row">
            <div class="text-sm text-muted-foreground text-center sm:text-left">
                Menampilkan <span class="font-medium text-foreground">{{ $logs->firstItem() ?? 0 }}</span> - <span
                    class="font-medium text-foreground">{{ $logs->lastItem() ?? 0 }}</span> dari <span
                    class="font-medium text-foreground">{{ $logs->total() }}</span> log aktivitas
            </div>
            <div>
                {{ $logs->appends(request()->query())->links('vendor.pagination.shadcn') }}
            </div>
        </div>

    </div>
</x-layout.app>
