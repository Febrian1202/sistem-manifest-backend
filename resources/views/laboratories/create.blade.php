<x-layout.app title="Tambah Laboratorium" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Laboratorium', 'url' => route('laboratories.index')],
    ['name' => 'Tambah Laboratorium', 'url' => null]
]">
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Tambah Laboratorium Baru</h1>
            <p class="text-muted-foreground mt-1">
                Masukkan rincian identitas dan lokasi laboratorium.
            </p>
        </div>

        {{-- Alert Error --}}
        @if ($errors->any())
            <x-ui.alert.index variant="destructive">
                <x-ui.alert.title>Peringatan</x-ui.alert.title>
                <x-ui.alert.description>
                    <ul class="list-disc list-inside text-xs opacity-90 font-mono">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        {{-- Form Card --}}
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <form action="{{ route('laboratories.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Nama Laboratorium --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <x-form.label for="name">Nama Laboratorium <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Laboratorium Komputer Dasar" required />
                    </div>

                    {{-- Kode Laboratorium --}}
                    <div class="space-y-1.5">
                        <x-form.label for="code">Kode Laboratorium <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="code" name="code" value="{{ old('code') }}" placeholder="Contoh: LAB-KD1" required />
                        <p class="text-[11px] text-muted-foreground">Kode unik penanda laboratorium.</p>
                    </div>

                    {{-- Gedung --}}
                    <div class="space-y-1.5">
                        <x-form.label for="building">Gedung</x-form.label>
                        <x-form.input id="building" name="building" value="{{ old('building') }}" placeholder="Contoh: Gedung A / Rektorat" />
                    </div>

                    {{-- Lantai --}}
                    <div class="space-y-1.5">
                        <x-form.label for="floor">Lantai</x-form.label>
                        <x-form.input id="floor" name="floor" value="{{ old('floor') }}" placeholder="Contoh: 2" />
                    </div>

                    {{-- Deskripsi --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <x-form.label for="description">Deskripsi / Keterangan</x-form.label>
                        <textarea id="description" name="description" rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Informasi tambahan mengenai laboratorium ini...">{{ old('description') }}</textarea>
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
                        <i class="fa-solid fa-check mr-2"></i> Simpan Laboratorium
                    </x-ui.button>
                </div>
            </form>
        </div>

    </div>
</x-layout.app>
