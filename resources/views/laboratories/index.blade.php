<x-layout.app title="Data Laboratorium" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Laboratorium', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header & Tombol Tambah --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Data Laboratorium</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola data laboratorium, alokasi komputer, dan penanggung jawab (PJ Lab).
                </p>
            </div>

            @role('admin')
            <a href="{{ route('laboratories.create') }}">
                <x-ui.button>
                    <i class="fa-solid fa-plus mr-2"></i> Tambah Laboratorium
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

        {{-- Pencarian --}}
        <form method="GET" action="{{ route('laboratories.index') }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col sm:flex-row gap-4 items-center">
            
            <div class="w-full relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-form.input name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, kode, atau gedung..." class="pl-9 w-full" />
            </div>

            <div class="flex justify-end gap-2 w-full sm:w-auto">
                <x-ui.button type="submit">
                    <i class="fa-solid fa-search mr-2"></i> Cari
                </x-ui.button>
                @if(request('search'))
                    <a href="{{ route('laboratories.index') }}">
                        <x-ui.button type="button" variant="outline" title="Reset">
                            <i class="fa-solid fa-xmark"></i>
                        </x-ui.button>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Laboratorium --}}
        <div class="rounded-md border border-border bg-card shadow-sm">
            <x-ui.table.table>
                <x-ui.table.table-header>
                    <x-ui.table.table-row>
                        <x-ui.table.table-head class="w-[60px]">No</x-ui.table.table-head>
                        <x-ui.table.table-head>Nama Laboratorium</x-ui.table.table-head>
                        <x-ui.table.table-head>Kode</x-ui.table.table-head>
                        <x-ui.table.table-head>Lokasi / Gedung</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Jumlah Komputer</x-ui.table.table-head>
                        <x-ui.table.table-head>Penanggung Jawab (PJ Lab)</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
                    </x-ui.table.table-row>
                </x-ui.table.table-header>

                <x-ui.table.table-body>
                    @forelse($laboratories as $index => $lab)
                        <x-ui.table.table-row>
                            {{-- 1. No --}}
                            <x-ui.table.table-cell class="font-mono text-xs text-muted-foreground">
                                {{ $laboratories->firstItem() + $index }}
                            </x-ui.table.table-cell>

                            {{-- 2. Nama --}}
                            <x-ui.table.table-cell class="font-medium">
                                <div class="flex flex-col">
                                    <span class="text-foreground font-semibold">{{ $lab->name }}</span>
                                    @if($lab->description)
                                        <span class="text-xs text-muted-foreground line-clamp-1">{{ $lab->description }}</span>
                                    @endif
                                </div>
                            </x-ui.table.table-cell>

                            {{-- 3. Kode --}}
                            <x-ui.table.table-cell>
                                <span class="inline-flex items-center rounded-md bg-secondary/80 px-2 py-1 text-xs font-mono font-medium text-secondary-foreground">
                                    {{ $lab->code }}
                                </span>
                            </x-ui.table.table-cell>

                            {{-- 4. Gedung & Lantai --}}
                            <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                @if($lab->building || $lab->floor)
                                    <span>{{ $lab->building ?? '-' }}</span>
                                    @if($lab->floor)
                                        <span class="text-foreground/70">(Lt. {{ $lab->floor }})</span>
                                    @endif
                                @else
                                    <span>-</span>
                                @endif
                            </x-ui.table.table-cell>

                            {{-- 5. Jumlah Komputer --}}
                            <x-ui.table.table-cell class="text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $lab->computers_count > 0 ? 'bg-primary/10 text-primary border-primary/20' : 'bg-muted text-muted-foreground border-border' }}">
                                    <i class="fa-solid fa-desktop mr-1.5 text-[10px]"></i> {{ $lab->computers_count }}
                                </span>
                            </x-ui.table.table-cell>

                            {{-- 6. PJ Lab --}}
                            <x-ui.table.table-cell class="text-xs">
                                @if($lab->penanggungJawab->isNotEmpty())
                                    <div class="flex flex-col gap-1">
                                        @foreach($lab->penanggungJawab as $pj)
                                            <span class="inline-flex items-center gap-1 font-medium text-foreground">
                                                <i class="fa-solid fa-user text-[10px] text-muted-foreground"></i>
                                                {{ $pj->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted-foreground italic">Belum ditugaskan</span>
                                @endif
                            </x-ui.table.table-cell>

                            {{-- 7. Aksi --}}
                            <x-ui.table.table-cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('laboratories.edit', $lab->id) }}">
                                        <x-ui.button variant="outline" size="sm" class="h-8 w-8 p-0" title="Edit Laboratorium">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </x-ui.button>
                                    </a>

                                    <div @click.stop="$dispatch('open-dialog', 'delete-lab-{{ $lab->id }}')" class="inline-block">
                                        <x-ui.button type="button" variant="ghost" size="sm" class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10" title="Hapus Laboratorium">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </x-ui.button>
                                    </div>

                                    <x-ui.dialog.confirm name="delete-lab-{{ $lab->id }}" title="Hapus Laboratorium" maxWidth="md">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                                                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                                            </div>
                                            <div class="space-y-1 text-left">
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                                    Apakah Anda yakin ingin menghapus laboratorium <strong>{{ $lab->name }}</strong>?
                                                </p>
                                                @if($lab->computers_count > 0)
                                                    <p class="text-xs text-destructive font-medium mt-1">
                                                        Peringatan: Terdapat {{ $lab->computers_count }} komputer di laboratorium ini. Anda harus memindahkan atau menghapus komputer tersebut terlebih dahulu.
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        <x-slot name="footer">
                                            <x-ui.button type="button" variant="outline" x-on:click="show = false" class="w-full sm:w-auto">
                                                Batal
                                            </x-ui.button>
                                            <form action="{{ route('laboratories.destroy', $lab->id) }}" method="POST" class="w-full sm:w-auto">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button type="submit" variant="destructive" class="w-full" :disabled="$lab->computers_count > 0">
                                                    Hapus Laboratorium
                                                </x-ui.button>
                                            </form>
                                        </x-slot>
                                    </x-ui.dialog.confirm>
                                </div>
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @empty
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell colspan="7" class="text-center h-24 text-muted-foreground">
                                Tidak ada data laboratorium.
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @endforelse
                </x-ui.table.table-body>
            </x-ui.table.table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 flex flex-col items-center justify-between gap-4 border-t border-border py-4 sm:flex-row">
            <div class="text-sm text-muted-foreground text-center sm:text-left">
                Menampilkan <span class="font-medium text-foreground">{{ $laboratories->firstItem() ?? 0 }}</span> - <span
                    class="font-medium text-foreground">{{ $laboratories->lastItem() ?? 0 }}</span> dari <span
                    class="font-medium text-foreground">{{ $laboratories->total() }}</span> laboratorium
            </div>
            <div>
                {{ $laboratories->appends(request()->query())->links('vendor.pagination.shadcn') }}
            </div>
        </div>

    </div>
</x-layout.app>
