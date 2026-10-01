<x-layout.app title="Alokasi Lisensi" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Alokasi Lisensi', 'url' => null]
]">
    <div class="space-y-6">

        {{-- Header & Tombol Tambah --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Alokasi Lisensi Fakultas</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola pendistribusian kuota hak pakai lisensi software universitas ke tiap fakultas.
                </p>
            </div>

            @role('admin')
            <a href="{{ route('license-allocations.create') }}">
                <x-ui.button>
                    <i class="fa-solid fa-plus mr-2"></i> Buat Alokasi Baru
                </x-ui.button>
            </a>
            @endrole
        </div>

        {{-- Menampilkan Pesan Berhasil/Gagal --}}
        @if (session('status') || $errors->any())
            <x-ui.alert.index variant="{{ (session('status') === 'success') ? 'success' : 'destructive' }}" class="mb-6">
                <x-ui.alert.title>{{ (session('status') === 'success') ? 'Berhasil' : 'Peringatan' }}</x-ui.alert.title>
                <x-ui.alert.description>
                    {{ session('message') ?? 'Ada kesalahan pada isian form Anda.' }}

                    @if ($errors->any())
                        <ul class="mt-2 list-disc list-inside text-xs opacity-80 font-mono">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        {{-- Kartu Ringkasan (Cards) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card title="Total Kursi Terdistribusi" value="{{ number_format($stats['total_allocated']) }}"
                subtitle="Kursi dialokasikan ke fakultas" icon="fa-solid fa-chair" />
            <x-stat-card title="Sisa Kuota Universitas" value="{{ number_format($stats['total_unallocated']) }}"
                subtitle="Dari total {{ number_format($stats['total_owned']) }} kepemilikan" icon="fa-solid fa-boxes-stacked" />
            <x-stat-card title="Fakultas Penerima" value="{{ $stats['total_recipients'] }}"
                subtitle="Fakultas memiliki alokasi aktif" icon="fa-solid fa-graduation-cap" />
            <x-stat-card title="Alokasi Aktif" value="{{ $stats['total_active_allocations'] }}"
                subtitle="Data alokasi berstatus aktif" icon="fa-solid fa-circle-check" />
        </div>

        {{-- Pencarian & Filter Bar --}}
        <form method="GET" action="{{ route('license-allocations.index') }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col md:flex-row gap-4 items-center">
            
            <div class="w-full relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-form.input name="search" value="{{ request('search') }}"
                    placeholder="Cari software, no PO, atau fakultas..." class="pl-9 w-full" />
            </div>

            <div class="w-full md:w-56">
                <select name="faculty_id" onchange="this.form.submit()"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="">Semua Fakultas</option>
                    @foreach($faculties as $fac)
                        <option value="{{ $fac->id }}" @selected(request('faculty_id') == $fac->id)>
                            {{ $fac->code }} - {{ $fac->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-44">
                <select name="status" onchange="this.form.submit()"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    <option value="revoked" @selected(request('status') === 'revoked')>Dicabut (Revoked)</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 w-full md:w-auto">
                <x-ui.button type="submit">
                    <i class="fa-solid fa-search mr-2"></i> Filter
                </x-ui.button>
                @if(request('search') || request('faculty_id') || request('status'))
                    <a href="{{ route('license-allocations.index') }}">
                        <x-ui.button type="button" variant="outline" title="Reset Filter">
                            <i class="fa-solid fa-xmark"></i>
                        </x-ui.button>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Data Alokasi --}}
        <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
            <x-ui.table.table>
                <x-ui.table.table-header>
                    <x-ui.table.table-row>
                        <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                        <x-ui.table.table-head>Software & Lisensi</x-ui.table.table-head>
                        <x-ui.table.table-head>Fakultas Penerima</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Kuota Alokasi</x-ui.table.table-head>
                        <x-ui.table.table-head>Masa Berlaku</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Status</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
                    </x-ui.table.table-row>
                </x-ui.table.table-header>
                <x-ui.table.table-body>
                    @forelse($allocations as $index => $allocation)
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell class="text-center font-medium text-muted-foreground">
                                {{ $allocations->firstItem() + $index }}
                            </x-ui.table.table-cell>
                            
                            {{-- Software & Lisensi --}}
                            <x-ui.table.table-cell>
                                <div class="font-semibold text-foreground">
                                    {{ $allocation->licenseInventory?->catalog?->normalized_name ?? 'Tidak Diketahui' }}
                                </div>
                                <div class="text-xs text-muted-foreground flex items-center gap-2 mt-0.5">
                                    <span>PO: {{ $allocation->licenseInventory?->purchase_order_number ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>Total Lisensi: {{ $allocation->licenseInventory?->quota_limit ?? 0 }} seat</span>
                                </div>
                            </x-ui.table.table-cell>

                            {{-- Fakultas --}}
                            <x-ui.table.table-cell>
                                <div class="font-medium text-foreground">
                                    {{ $allocation->faculty?->name ?? '-' }}
                                </div>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono bg-muted text-muted-foreground mt-0.5">
                                    {{ $allocation->faculty?->code ?? '-' }}
                                </span>
                            </x-ui.table.table-cell>

                            {{-- Kuota Alokasi --}}
                            <x-ui.table.table-cell class="text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                                    <i class="fa-solid fa-chair mr-1.5 text-[11px]"></i>
                                    {{ $allocation->allocated_quota }} seat
                                </span>
                            </x-ui.table.table-cell>

                            {{-- Masa Berlaku --}}
                            <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                <div>
                                    <span class="font-medium text-foreground">Ditetapkan:</span>
                                    {{ $allocation->allocation_date?->format('d M Y') ?? '-' }}
                                </div>
                                @if($allocation->start_date || $allocation->end_date)
                                    <div class="mt-0.5">
                                        <span class="font-medium text-foreground">Berlaku:</span>
                                        {{ $allocation->start_date?->format('d/m/Y') ?? 'Awal' }}
                                        s/d
                                        {{ $allocation->end_date?->format('d/m/Y') ?? 'Seterusnya' }}
                                    </div>
                                @endif
                            </x-ui.table.table-cell>

                            {{-- Status --}}
                            <x-ui.table.table-cell class="text-center">
                                @if($allocation->status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Aktif
                                    </span>
                                @elseif($allocation->status === 'inactive')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                        Dicabut
                                    </span>
                                @endif
                            </x-ui.table.table-cell>

                            {{-- Aksi --}}
                            <x-ui.table.table-cell class="text-right">
                                @role('admin')
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('license-allocations.edit', $allocation) }}">
                                        <x-ui.button variant="outline" size="sm" title="Edit Alokasi">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </x-ui.button>
                                    </a>

                                    <form action="{{ route('license-allocations.destroy', $allocation) }}" method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus alokasi lisensi ini? Kuota akan otomatis dikembalikan ke sisa lisensi universitas.');">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button variant="destructive" size="sm" type="submit" title="Hapus Alokasi">
                                            <i class="fa-solid fa-trash"></i>
                                        </x-ui.button>
                                    </form>
                                </div>
                                @endrole
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @empty
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell colspan="7" class="text-center py-10 text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-solid fa-diagram-project text-3xl opacity-30"></i>
                                    <p class="text-sm">Belum ada data alokasi lisensi yang tercatat.</p>
                                    @role('admin')
                                    <a href="{{ route('license-allocations.create') }}" class="mt-2">
                                        <x-ui.button size="sm">
                                            <i class="fa-solid fa-plus mr-1.5"></i> Buat Alokasi Pertama
                                        </x-ui.button>
                                    </a>
                                    @endrole
                                </div>
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @endforelse
                </x-ui.table.table-body>
            </x-ui.table.table>

            {{-- Pagination --}}
            @if ($allocations->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $allocations->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layout.app>
