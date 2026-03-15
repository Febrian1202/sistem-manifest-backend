<x-layout.app title="Inventaris Lisensi" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Inventaris Lisensi', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header & Tombol Tambah --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Inventaris Lisensi</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola pembelian, kuota, dan masa berlaku lisensi perangkat lunak.
                </p>
            </div>

            {{-- SHEET TAMBAH LISENSI --}}
            <x-ui.sheet.sheet>
                <x-ui.sheet.trigger>
                    <x-ui.button>
                        <i class="fa-solid fa-plus mr-2"></i> Tambah Lisensi Baru
                    </x-ui.button>
                </x-ui.sheet.trigger>

                <x-ui.sheet.content side="right">
                    <x-ui.sheet.header>
                        <x-ui.sheet.title>Input Lisensi Baru</x-ui.sheet.title>
                        <x-ui.sheet.description>Catat pembelian lisensi baru ke dalam
                            inventaris.</x-ui.sheet.description>
                    </x-ui.sheet.header>

                    <form action="{{ route('licenses.store') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <x-form.label>Pilih Software</x-form.label>
                            <x-ui.select.index name="catalog_id" placeholder="Pilih aplikasi...">
                                <x-ui.select.trigger />
                                <x-ui.select.content>
                                    @foreach($catalogs as $cat)
                                        <x-ui.select.item
                                            value="{{ $cat->id }}">{{ $cat->normalized_name }}</x-ui.select.item>
                                    @endforeach
                                </x-ui.select.content>
                            </x-ui.select.index>
                        </div>

                        <div class="space-y-1.5">
                            <x-form.label>Nomor Purchase Order (PO)</x-form.label>
                            <x-form.input name="purchase_order_number" placeholder="Contoh: PO-USN-2026-001" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <x-form.label>Kuota Lisensi</x-form.label>
                                <x-form.input type="number" name="quota_limit" value="1" min="1" required />
                            </div>
                            <div class="space-y-1.5">
                                <x-form.label>Harga Per Unit (Rp)</x-form.label>
                                <x-form.input type="number" name="price_per_unit" placeholder="0" min="0" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <x-form.label>Tanggal Beli</x-form.label>
                                <x-form.input type="date" name="purchase_date" />
                            </div>
                            <div class="space-y-1.5">
                                <x-form.label>Kedaluwarsa</x-form.label>
                                <x-form.input type="date" name="expiry_date" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <x-form.label>Catatan</x-form.label>
                            <textarea name="notes" rows="3"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"></textarea>
                        </div>

                        <x-ui.sheet.footer>
                            <x-ui.button type="submit" class="w-full">
                                <i class="fa-solid fa-check mr-2"></i> Simpan Lisensi
                            </x-ui.button>
                        </x-ui.sheet.footer>
                    </form>
                </x-ui.sheet.content>
            </x-ui.sheet.sheet>
        </div>

        @if (session('status'))
            <x-ui.alert.index variant="{{session('status') === 'success' ? 'success' : 'destructive'}}">
                <x-ui.alert.title>{{ session('status') === 'success' ? 'Berhasil' : 'Gagal' }}</x-ui.alert.title>
                <x-ui.alert.description>{!! session('message') !!}</x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card title="Total Kursi Lisensi" value="{{ number_format($stats['total_licenses']) }}"
                subtitle="Akumulasi seluruh kuota" icon="fa-solid fa-users" />
            <x-stat-card title="Total Nilai Aset" value="Rp {{ number_format($stats['total_value'], 0, ',', '.') }}"
                subtitle="Estimasi biaya lisensi" icon="fa-solid fa-rupiah-sign" />
            <x-stat-card title="Segera Kedaluwarsa" value="{{ $stats['expiring_soon'] }}"
                subtitle="Berakhir dalam 30 hari" icon="fa-solid fa-triangle-exclamation"
                class="border-l-4 border-l-yellow-500" />
            <x-stat-card title="Sudah Kedaluwarsa" value="{{ $stats['expired'] }}" subtitle="Perlu diperbarui segera"
                icon="fa-solid fa-clock" variant="critical" />
        </div>

        {{-- Pencarian --}}
        <form method="GET" action="{{ url()->current() }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col md:flex-row gap-4 items-center">
            <div class="w-full relative">
                <i
                    class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-form.input name="search" value="{{ request('search') }}"
                    placeholder="Cari nama software atau Nomor PO..." class="pl-9 w-full md:max-w-md" />
            </div>

            <div class="flex gap-2 w-full md:w-auto">
                <x-ui.button type="submit">
                    <i class="fa-solid fa-search mr-2"></i> Cari
                </x-ui.button>
                @if(request('search'))
                    <a href="{{ url()->current() }}">
                        <x-ui.button type="button" variant="outline" title="Reset">
                            <i class="fa-solid fa-xmark"></i>
                        </x-ui.button>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Komponen --}}
        <x-licenses.table :licenses="$licenses" :catalogs="$catalogs" />

        {{-- Pagination --}}
        <div class="mt-4 flex flex-col items-center justify-between gap-4 border-t border-border py-4 sm:flex-row">
            <div class="text-sm text-muted-foreground text-center sm:text-left">
                Menampilkan <span class="font-medium text-foreground">{{ $licenses->firstItem() ?? 0 }}</span> - <span
                    class="font-medium text-foreground">{{ $licenses->lastItem() ?? 0 }}</span> dari <span
                    class="font-medium text-foreground">{{ $licenses->total() }}</span> lisensi
            </div>
            <div>
                {{ $licenses->appends(request()->query())->links('vendor.pagination.shadcn') }}
            </div>
        </div>

    </div>
</x-layout.app>