<x-layout.app title="Edit Laboratorium" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Laboratorium', 'url' => route('laboratories.index')],
    ['name' => 'Edit Laboratorium', 'url' => null]
]">
    <div class="max-w-4xl mx-auto space-y-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Edit Laboratorium: {{ $laboratory->name }}</h1>
                <p class="text-muted-foreground mt-1">
                    Perbarui data laboratorium dan lihat ringkasan aset yang dialokasikan.
                </p>
            </div>
            <a href="{{ route('laboratories.index') }}">
                <x-ui.button variant="outline">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </x-ui.button>
            </a>
        </div>

        {{-- Alert Error / Pesan --}}
        @if (session('status') || $errors->any())
            <x-ui.alert.index variant="{{ (session('status') === 'success') ? 'success' : 'destructive' }}">
                <x-ui.alert.title>{{ (session('status') === 'success') ? 'Berhasil' : 'Peringatan' }}</x-ui.alert.title>
                <x-ui.alert.description>
                    {{ session('message') ?? 'Ada kesalahan pada isian form Anda.' }}

                    @if ($errors->any())
                        <ul class="mt-2 list-disc list-inside text-xs opacity-90 font-mono">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        {{-- Form Edit Card --}}
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <form action="{{ route('laboratories.update', $laboratory->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Nama Laboratorium --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <x-form.label for="name">Nama Laboratorium <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="name" name="name" value="{{ old('name', $laboratory->name) }}" required />
                    </div>

                    {{-- Kode Laboratorium --}}
                    <div class="space-y-1.5">
                        <x-form.label for="code">Kode Laboratorium <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="code" name="code" value="{{ old('code', $laboratory->code) }}" required />
                        <p class="text-[11px] text-muted-foreground">Kode unik penanda laboratorium.</p>
                    </div>

                    {{-- Gedung --}}
                    <div class="space-y-1.5">
                        <x-form.label for="building">Gedung</x-form.label>
                        <x-form.input id="building" name="building" value="{{ old('building', $laboratory->building) }}" />
                    </div>

                    {{-- Lantai --}}
                    <div class="space-y-1.5">
                        <x-form.label for="floor">Lantai</x-form.label>
                        <x-form.input id="floor" name="floor" value="{{ old('floor', $laboratory->floor) }}" />
                    </div>

                    {{-- Deskripsi --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <x-form.label for="description">Deskripsi / Keterangan</x-form.label>
                        <textarea id="description" name="description" rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">{{ old('description', $laboratory->description) }}</textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <a href="{{ route('laboratories.index') }}">
                        <x-ui.button type="button" variant="outline">
                            Batal
                        </x-ui.button>
                    </a>
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                    </x-ui.button>
                </div>
            </form>
        </div>

        {{-- Section: Penanggung Jawab Lab (PJ Lab) --}}
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-foreground">Penanggung Jawab (PJ Lab)</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Pengguna dengan role Kepala Lab yang ditugaskan di lab ini.</p>
                </div>
                <a href="{{ route('accounts') }}">
                    <x-ui.button variant="outline" size="sm">
                        <i class="fa-solid fa-user-gear mr-1.5 text-xs"></i> Kelola Akun
                    </x-ui.button>
                </a>
            </div>

            @if($laboratory->penanggungJawab->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($laboratory->penanggungJawab as $pj)
                        <div class="flex items-center gap-3 p-3 rounded-lg border border-border bg-background">
                            <div class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-medium text-sm text-foreground">{{ $pj->name }}</span>
                                <span class="text-xs text-muted-foreground">{{ $pj->email }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 border border-dashed border-border rounded-lg text-sm text-muted-foreground">
                    <i class="fa-solid fa-user-slash text-2xl mb-1 text-muted-foreground/50"></i>
                    <p>Belum ada Penanggung Jawab (PJ Lab) yang ditugaskan ke laboratorium ini.</p>
                </div>
            @endif
        </div>

        {{-- Section: Daftar Komputer Terkait --}}
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-foreground">Daftar Komputer ({{ $laboratory->computers->count() }})</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Komputer yang terdaftar berada di laboratorium ini.</p>
                </div>
                <a href="{{ route('computers') }}">
                    <x-ui.button variant="outline" size="sm">
                        <i class="fa-solid fa-desktop mr-1.5 text-xs"></i> Lihat Data Komputer
                    </x-ui.button>
                </a>
            </div>

            @if($laboratory->computers->isNotEmpty())
                <div class="rounded-md border border-border overflow-x-auto">
                    <x-ui.table.table>
                        <x-ui.table.table-header>
                            <x-ui.table.table-row>
                                <x-ui.table.table-head>Hostname</x-ui.table.table-head>
                                <x-ui.table.table-head>IP Address</x-ui.table.table-head>
                                <x-ui.table.table-head>Sistem Operasi</x-ui.table.table-head>
                                <x-ui.table.table-head>Status Lisensi OS</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
                            </x-ui.table.table-row>
                        </x-ui.table.table-header>
                        <x-ui.table.table-body>
                            @foreach($laboratory->computers as $computer)
                                <x-ui.table.table-row>
                                    <x-ui.table.table-cell class="font-medium text-foreground">
                                        {{ $computer->hostname }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="font-mono text-xs text-muted-foreground">
                                        {{ $computer->ip_address ?? '-' }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-xs">
                                        {{ $computer->os_name ?? '-' }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ ($computer->os_license_status === 'Licensed') ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200' : 'bg-amber-500/10 text-amber-600 border-amber-200' }}">
                                            {{ $computer->os_license_status ?? 'Unknown' }}
                                        </span>
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-right">
                                        <a href="{{ route('computers.show', $computer->id) }}" class="text-xs text-primary hover:underline">
                                            Detail
                                        </a>
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @endforeach
                        </x-ui.table.table-body>
                    </x-ui.table.table>
                </div>
            @else
                <div class="text-center py-6 border border-dashed border-border rounded-lg text-sm text-muted-foreground">
                    <i class="fa-solid fa-desktop text-2xl mb-1 text-muted-foreground/50"></i>
                    <p>Belum ada komputer yang dialokasikan ke laboratorium ini.</p>
                </div>
            @endif
        </div>

    </div>
</x-layout.app>
