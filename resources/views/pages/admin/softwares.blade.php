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

        {{-- 1. STAT CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Total Detected --}}
            <x-stat-card title="Total Deteksi" value="{{ number_format($stats['total']) }}" subtitle="Software Unik"
                icon="fa-solid fa-database" />

            {{-- Unreviewed --}}
            <x-stat-card title="Belum Direview" value="{{ number_format($stats['unreviewed']) }}"
                subtitle="Perlu Tindakan" icon="fa-solid fa-circle-question" class="border-l-4 border-l-yellow-500" />

            {{-- Whitelist --}}
            <x-stat-card title="Diizinkan" value="{{ number_format($stats['whitelist']) }}" subtitle="Aplikasi Resmi"
                icon="fa-solid fa-circle-check" class="border-l-4 border-l-green-500" />

            {{-- Blacklist --}}
            <x-stat-card title="Terlarang" value="{{ number_format($stats['blacklist']) }}"
                subtitle="Aplikasi Berbahaya" icon="fa-solid fa-ban" variant="critical" />
        </div>

        {{-- 2. SEARCH & FILTER --}}
        <form method="GET" action="{{ url()->current() }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col md:flex-row gap-4 items-end md:items-center">

            {{-- Input Search --}}
            <div class="w-full md:flex-1 relative">
                <div class="relative">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                    <x-form.input name="search" value="{{ request('search') }}" placeholder="Cari nama software..."
                        class="pl-9 w-full" />
                </div>
            </div>

            {{-- Filter Kategori --}}
            <div class="w-full md:w-48">
                <x-ui.select.index name="category" value="{{ request('category') }}" placeholder="Semua Kategori">
                    <x-ui.select.trigger />
                    <x-ui.select.content>
                        <x-ui.select.item value="All">Semua Kategori</x-ui.select.item>
                        <x-ui.select.item value="Freeware">Freeware</x-ui.select.item>
                        <x-ui.select.item value="Commercial">Commercial</x-ui.select.item>
                        <x-ui.select.item value="OpenSource">Open Source</x-ui.select.item>
                    </x-ui.select.content>
                </x-ui.select.index>
            </div>

            {{-- Filter Status --}}
            <div class="w-full md:w-48">
                <x-ui.select.index name="status" value="{{ request('status') }}" placeholder="Semua Status">
                    <x-ui.select.trigger />
                    <x-ui.select.content>
                        <x-ui.select.item value="All">Semua Status</x-ui.select.item>
                        <x-ui.select.item value="Unreviewed">
                            <i class="fa-solid fa-circle-question mr-2 text-yellow-500"></i> Unreviewed
                        </x-ui.select.item>
                        <x-ui.select.item value="Whitelist">
                            <i class="fa-solid fa-circle-check mr-2 text-green-500"></i> Whitelist
                        </x-ui.select.item>
                        <x-ui.select.item value="Blacklist">
                            <i class="fa-solid fa-ban mr-2 text-red-500"></i> Blacklist
                        </x-ui.select.item>
                    </x-ui.select.content>
                </x-ui.select.index>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <x-ui.button type="submit">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                </x-ui.button>
                @if (request()->hasAny(['search', 'category', 'status']))
                    <a href="{{ url()->current() }}">
                        <x-ui.button type="button" variant="outline" title="Reset">
                            <i class="fa-solid fa-xmark"></i>
                        </x-ui.button>
                    </a>
                @endif
            </div>
        </form>

        {{-- 3. TABLE --}}
        <x-softwares.table :softwares="$softwares" />

        {{-- 4. PAGINATION --}}
        <div class="mt-4 flex flex-col items-center justify-between gap-4 border-t border-border py-4 sm:flex-row">
            <div class="text-sm text-muted-foreground text-center sm:text-left">
                Menampilkan <span class="font-medium text-foreground">{{ $softwares->firstItem() ?? 0 }}</span> - <span
                    class="font-medium text-foreground">{{ $softwares->lastItem() ?? 0 }}</span> dari <span
                    class="font-medium text-foreground">{{ $softwares->total() }}</span> hasil
            </div>
            <div>
                {{ $softwares->appends(request()->query())->links('vendor.pagination.shadcn') }}
            </div>
        </div>

    </div>
</x-layout.app>
