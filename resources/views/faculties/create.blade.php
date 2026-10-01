<x-layout.app title="Tambah Fakultas" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Fakultas', 'url' => route('faculties.index')],
    ['name' => 'Tambah Fakultas', 'url' => null]
]">
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Tambah Fakultas Baru</h1>
            <p class="text-muted-foreground mt-1">
                Masukkan rincian identitas dan kode fakultas.
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
            <form action="{{ route('faculties.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="space-y-4">
                    {{-- Kode Fakultas --}}
                    <div class="space-y-1.5">
                        <x-form.label for="code">Kode Fakultas <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="code" name="code" value="{{ old('code') }}" placeholder="Contoh: FTI" required />
                        <p class="text-[11px] text-muted-foreground">Kode resmi atau singkatan unik fakultas (misal FTI, FKIP).</p>
                    </div>

                    {{-- Nama Fakultas --}}
                    <div class="space-y-1.5">
                        <x-form.label for="name">Nama Fakultas <span class="text-destructive">*</span></x-form.label>
                        <x-form.input id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Fakultas Teknologi Informasi" required />
                    </div>

                    {{-- Deskripsi --}}
                    <div class="space-y-1.5">
                        <x-form.label for="description">Deskripsi / Keterangan</x-form.label>
                        <textarea id="description" name="description" rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Informasi tambahan mengenai fakultas ini...">{{ old('description') }}</textarea>
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
                        <i class="fa-solid fa-check mr-2"></i> Simpan Fakultas
                    </x-ui.button>
                </div>
            </form>
        </div>

    </div>
</x-layout.app>
