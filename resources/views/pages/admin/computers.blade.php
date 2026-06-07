<x-layout.app title="Data Komputer" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Data Komputer', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Data Komputer</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola dan pantau seluruh komputer terdaftar
                </p>
            </div>

            {{-- TASK-002: Scan Semua Button --}}
            @role('admin')
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
                <a href="{{ route('agent.download') }}" class="w-full sm:w-auto inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                    <i class="fa-solid fa-download mr-2"></i>
                    <span>Download Agent Installer</span>
                </a>

                <x-ui.button type="button" variant="default" @click.stop="$dispatch('open-dialog', 'scan-all-devices')" class="w-full sm:w-auto">
                    <i class="fa-solid fa-satellite-dish mr-2"></i>
                    <span>Scan Semua Perangkat</span>
                </x-ui.button>

                <x-ui.dialog.confirm name="scan-all-devices" title="Scan Semua Perangkat" maxWidth="md">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <i class="fa-solid fa-circle-info text-lg"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Apakah Anda yakin ingin mengirim permintaan scan ke semua perangkat terdaftar? Tindakan ini akan memicu scan pada seluruh perangkat secara paralel.
                            </p>
                        </div>
                    </div>

                    <x-slot name="footer">
                        <x-ui.button type="button" variant="outline" x-on:click="show = false" class="w-full sm:w-auto">
                            Batal
                        </x-ui.button>
                        <form action="{{ route('computers.request-scan-all') }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <x-ui.button type="submit" variant="default" class="w-full">
                                Mulai Scan
                            </x-ui.button>
                        </form>
                    </x-slot>
                </x-ui.dialog.confirm>
            </div>
            @endrole
        </div>

        {{-- Menampilkan Pesan Berhasil/Gagal dari Controller --}}
        @if (session('status'))
            <x-ui.alert.index variant="{{ session('status') === 'success' ? 'success' : 'destructive' }}" class="mb-6">
                <x-ui.alert.title>{{ session('status') === 'success' ? 'Berhasil' : 'Peringatan' }}</x-ui.alert.title>
                <x-ui.alert.description>
                    {{ session('message') }}

                    {{-- List detail error validasi jika ada --}}
                    @if ($errors->any())
                        <ul class="mt-2 list-disc list-inside text-xs opacity-80">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        {{-- FORM PENCARIAN & FILTER --}}
        <form method="GET" action="{{ url()->current() }}"
            class="flex flex-col md:flex-row md:items-center gap-4 bg-card p-4 rounded-lg border border-border shadow-sm">

            {{-- LEFT: Search --}}
            <div class="relative flex-1 w-full md:max-w-md">
                <i
                    class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-form.input name="search" value="{{ request('search') }}" placeholder="Cari Hostname, IP, atau OS..."
                    class="pl-9 w-full" />
            </div>

            {{-- RIGHT: Filters + Actions --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-4 md:ml-auto w-full md:w-auto">

                {{-- Location --}}
                <div class="w-full md:w-48">
                    <x-ui.select.index name="location" value="{{ request('location') }}" placeholder="Semua Lokasi">
                        <x-ui.select.trigger />
                        <x-ui.select.content>
                            <x-ui.select.item value="All">Semua Lokasi</x-ui.select.item>
                            @foreach ($locations as $loc)
                                <x-ui.select.item value="{{ $loc }}">{{ $loc }}</x-ui.select.item>
                            @endforeach
                        </x-ui.select.content>
                    </x-ui.select.index>
                </div>

                {{-- License Status --}}
                <div class="w-full md:w-48">
                    <x-ui.select.index name="license_status" value="{{ request('license_status') }}"
                        placeholder="Semua Status">
                        <x-ui.select.trigger />
                        <x-ui.select.content>
                            <x-ui.select.item value="All">Semua Status</x-ui.select.item>
                            <x-ui.select.item value="Licensed" class="text-green-600">
                                <i class="fa-solid fa-circle-check mr-2"></i> Berlisensi
                            </x-ui.select.item>
                            <x-ui.select.item value="Grace Period" class="text-yellow-600">
                                <i class="fa-solid fa-triangle-exclamation mr-2"></i> Masa Tenggang
                            </x-ui.select.item>
                            <x-ui.select.item value="Unlicensed" class="text-red-600">
                                <i class="fa-solid fa-circle-xmark mr-2"></i> Tidak Berlisensi
                            </x-ui.select.item>
                        </x-ui.select.content>
                    </x-ui.select.index>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end items-center gap-2 w-full md:w-auto">
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-filter mr-2"></i> Filter
                    </x-ui.button>

                    @if (request()->hasAny(['search', 'location', 'license_status']))
                        <a href="{{ url()->current() }}">
                            <x-ui.button type="button" variant="outline" title="Reset Filter">
                                <i class="fa-solid fa-xmark"></i>
                            </x-ui.button>
                        </a>
                    @endif
                </div>

            </div>
        </form>


        {{-- Table Komponen --}}
        <x-computers.table :computers="$computers" />

        {{-- Pagination --}}
        <div class="mt-4 flex flex-col items-center justify-between gap-4 border-t border-border py-4 sm:flex-row">

            {{-- Info Text (Kiri) --}}
            <div class="text-sm text-muted-foreground text-center sm:text-left">
                Menampilkan
                <span class="font-medium text-foreground">{{ $computers->firstItem() ?? 0 }}</span>
                -
                <span class="font-medium text-foreground">{{ $computers->lastItem() ?? 0 }}</span>
                dari
                <span class="font-medium text-foreground">{{ $computers->total() }}</span>
                hasil
            </div>

            {{-- Pagination Buttons (Kanan/Tengah) --}}
            <div>
                {{ $computers->appends(request()->query())->links('vendor.pagination.shadcn') }}
            </div>

        </div>
    </div>
</x-layout.app>