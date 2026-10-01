<x-layout.app title="Edit Fakultas" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Fakultas', 'url' => route('faculties.index')],
    ['name' => 'Edit Fakultas', 'url' => null]
]">
    <div class="max-w-4xl mx-auto space-y-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Edit Fakultas: {{ $faculty->name }}</h1>
                <p class="text-muted-foreground mt-1">
                    Perbarui data fakultas dan lihat daftar laboratorium terkait.
                </p>
            </div>
            <a href="{{ route('faculties.index') }}">
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
            <form action="{{ route('faculties.update', $faculty->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    {{-- Kode Fakultas --}}
                    <div class="space-y-1.5">
                        <x-form.label for="code">Kode Fakultas <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="code" name="code" value="{{ old('code', $faculty->code) }}" required />
                        <p class="text-[11px] text-muted-foreground">Kode resmi atau singkatan unik fakultas.</p>
                    </div>

                    {{-- Nama Fakultas --}}
                    <div class="space-y-1.5">
                        <x-form.label for="name">Nama Fakultas <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="name" name="name" value="{{ old('name', $faculty->name) }}" required />
                    </div>

                    {{-- Deskripsi --}}
                    <div class="space-y-1.5">
                        <x-form.label for="description">Deskripsi / Keterangan</x-form.label>
                        <textarea id="description" name="description" rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">{{ old('description', $faculty->description) }}</textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <a href="{{ route('faculties.index') }}">
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

        {{-- Section: Daftar Laboratorium Terdaftar --}}
        @php
            $laboratories = $faculty->relationLoaded('laboratories') ? $faculty->laboratories : collect();
        @endphp
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-foreground">Daftar Laboratorium ({{ $laboratories->count() }})</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Laboratorium yang bernaung di bawah fakultas ini.</p>
                </div>
                @role('admin')
                <a href="{{ route('laboratories.create') }}">
                    <x-ui.button variant="outline" size="sm">
                        <i class="fa-solid fa-plus mr-1.5 text-xs"></i> Tambah Lab
                    </x-ui.button>
                </a>
                @endrole
            </div>

            @if($laboratories->isNotEmpty())
                <div class="rounded-md border border-border overflow-x-auto">
                    <x-ui.table.table>
                        <x-ui.table.table-header>
                            <x-ui.table.table-row>
                                <x-ui.table.table-head>Nama Laboratorium</x-ui.table.table-head>
                                <x-ui.table.table-head>Kode</x-ui.table.table-head>
                                <x-ui.table.table-head>Gedung & Lantai</x-ui.table.table-head>
                                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
                            </x-ui.table.table-row>
                        </x-ui.table.table-header>
                        <x-ui.table.table-body>
                            @foreach($laboratories as $lab)
                                <x-ui.table.table-row>
                                    <x-ui.table.table-cell class="font-medium text-foreground">
                                        {{ $lab->name }}
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell>
                                        <span class="inline-flex items-center rounded-md bg-secondary/80 px-2 py-1 text-xs font-mono font-medium text-secondary-foreground">
                                            {{ $lab->code }}
                                        </span>
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                        {{ $lab->building ?? '-' }} @if($lab->floor) (Lt. {{ $lab->floor }}) @endif
                                    </x-ui.table.table-cell>
                                    <x-ui.table.table-cell class="text-right">
                                        <a href="{{ route('laboratories.edit', $lab->id) }}" class="text-xs text-primary hover:underline">
                                            Kelola Lab
                                        </a>
                                    </x-ui.table.table-cell>
                                </x-ui.table.table-row>
                            @endforeach
                        </x-ui.table.table-body>
                    </x-ui.table.table>
                </div>
            @else
                <div class="text-center py-6 border border-dashed border-border rounded-lg text-sm text-muted-foreground">
                    <i class="fa-solid fa-flask text-2xl mb-1 text-muted-foreground/50"></i>
                    <p>Belum ada laboratorium yang terhubung dengan fakultas ini.</p>
                </div>
            @endif
        </div>

    </div>
</x-layout.app>
