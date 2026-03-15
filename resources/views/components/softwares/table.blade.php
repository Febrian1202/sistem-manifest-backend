@props(['softwares'])

<div class="rounded-md border border-border bg-card shadow-sm">
    <x-ui.table.table>
        <x-ui.table.table-header>
            <x-ui.table.table-row>
                <x-ui.table.table-head>Software</x-ui.table.table-head>
                <x-ui.table.table-head>Publisher/Vendor</x-ui.table.table-head>
                <x-ui.table.table-head>Version</x-ui.table.table-head>
                <x-ui.table.table-head class="text-center">Install Count</x-ui.table.table-head>
                <x-ui.table.table-head>Kategori</x-ui.table.table-head>
                <x-ui.table.table-head>Status</x-ui.table.table-head>
                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
            </x-ui.table.table-row>
        </x-ui.table.table-header>

        <x-ui.table.table-body>
            @forelse($softwares as $software)
                @php
                    $latestDiscovery = $software->discoveries->first();

                    $statusClass = match ($software->status) {
                        'Whitelist' => 'bg-success/10 text-success border-success/20',
                        'Blacklist' => 'bg-destructive/10 text-destructive border-destructive/20',
                        'Unreviewed' => 'bg-warning/10 text-warning border-warning/20',
                        default => 'bg-muted text-muted-foreground',
                    };

                    $catClass = match ($software->category) {
                        'Freeware' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'Commercial' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'OpenSource' => 'bg-teal-50 text-teal-700 border-teal-200',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp

                <x-ui.table.table-row>
                    <x-ui.table.table-cell class="font-medium">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded bg-primary/10 flex items-center justify-center text-primary">
                                <i class="fa-solid fa-cube"></i>
                            </div>
                            <span class="truncate max-w-50" title="{{ $software->normalized_name }}">
                                {{ $software->normalized_name }}
                            </span>
                        </div>
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-muted-foreground text-xs">
                        {{ $latestDiscovery->vendor ?? '-' }}
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-muted-foreground text-xs font-mono">
                        {{ $latestDiscovery->version ?? '-' }}
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-center">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-foreground">
                            {{ $software->discoveries_count }} Device
                        </span>
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium border {{ $catClass }}">
                            {{ $software->category }}
                        </span>
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $statusClass }}">
                            {{ $software->status }}
                        </span>
                    </x-ui.table.table-cell>

                    <x-ui.table.table-cell class="text-right">
                        <div class="flex items-center justify-end">
                            {{-- DROPDRWON ACTIONS --}}
                            <x-ui.dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <x-ui.button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </x-ui.button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-ui.dropdown-label>Actions</x-ui.dropdown-label>

                                    <x-ui.dropdown-separator />

                                    {{-- TRIGGER SHEET DARI DROPDown --}}
                                    <x-ui.sheet.sheet>
                                        <x-ui.sheet.trigger class="w-full block">
                                            {{-- Gunakan dropdown-item langsung tanpa tag <button> lagi di dalamnya --}}
                                                <x-ui.dropdown-item type="button"
                                                    class="w-full flex items-center justify-start">
                                                    <i
                                                        class="fa-solid fa-pen-to-square mr-2 w-4 text-center opacity-70"></i>
                                                    <span>Edit Detail</span>
                                                </x-ui.dropdown-item>
                                        </x-ui.sheet.trigger>

                                        <x-ui.sheet.content side="right">
                                            <x-ui.sheet.header>
                                                <x-ui.sheet.title>Edit Katalog Software</x-ui.sheet.title>
                                                <x-ui.sheet.description>
                                                    Perbarui kategori dan status keamanan untuk software terdeteksi.
                                                </x-ui.sheet.description>
                                            </x-ui.sheet.header>

                                            <form action="{{ route('softwares.update', $software->id) }}" method="POST"
                                                class="mt-6 space-y-6">
                                                @csrf
                                                @method('PUT')

                                                <div class="space-y-4">
                                                    <div class="space-y-1">
                                                        <x-form.label>Nama Software</x-form.label>
                                                        <x-form.input value="{{ $software->normalized_name }}" disabled
                                                            class="bg-muted" />
                                                    </div>

                                                    <div class="space-y-1.5">
                                                        <x-form.label>Kategori</x-form.label>
                                                        <x-ui.select.index name="category"
                                                            value="{{ $software->category }}">
                                                            <x-ui.select.trigger />
                                                            <x-ui.select.content>
                                                                <x-ui.select.item
                                                                    value="Freeware">Freeware</x-ui.select.item>
                                                                <x-ui.select.item
                                                                    value="Commercial">Commercial</x-ui.select.item>
                                                                <x-ui.select.item value="OpenSource">Open
                                                                    Source</x-ui.select.item>
                                                            </x-ui.select.content>
                                                        </x-ui.select.index>
                                                    </div>

                                                    <!-- <div class="space-y-1.5">
                                                            <x-form.label>Status</x-form.label>
                                                            <x-ui.select.index name="status" value="{{ $software->status }}">
                                                                <x-ui.select.trigger />
                                                                <x-ui.select.content>
                                                                    <x-ui.select.item
                                                                        value="Unreviewed">Unreviewed</x-ui.select.item>
                                                                    <x-ui.select.item value="Whitelist"
                                                                        class="text-success">Whitelist</x-ui.select.item>
                                                                    <x-ui.select.item value="Blacklist"
                                                                        class="text-destructive">Blacklist</x-ui.select.item>
                                                                </x-ui.select.content>
                                                            </x-ui.select.index>
                                                        </div> -->

                                                    <div class="space-y-1.5">
                                                        <x-form.label>Deskripsi</x-form.label>
                                                        <textarea name="description"
                                                            class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                            placeholder="Tambahkan catatan tentang software ini...">{{ $software->description }}</textarea>
                                                    </div>
                                                </div>

                                                <x-ui.sheet.footer>
                                                    <x-ui.button type="submit" class="w-full">
                                                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                                                    </x-ui.button>
                                                </x-ui.sheet.footer>
                                            </form>
                                        </x-ui.sheet.content>
                                    </x-ui.sheet.sheet>

                                    <form action="{{ route('softwares.update', $software->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="category" value="{{ $software->category }}">
                                        <input type="hidden" name="status" value="Whitelist">
                                        <x-ui.dropdown-item onclick="this.closest('form').submit()">
                                            <i class="fa-solid fa-check mr-2 text-green-600"></i> Set Whitelist
                                        </x-ui.dropdown-item>
                                    </form>

                                    <form action="{{ route('softwares.update', $software->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="category" value="{{ $software->category }}">
                                        <input type="hidden" name="status" value="Blacklist">
                                        <x-ui.dropdown-item onclick="this.closest('form').submit()">
                                            <i class="fa-solid fa-ban mr-2 text-red-600"></i> Set Blacklist
                                        </x-ui.dropdown-item>
                                    </form>
                                </x-slot>
                            </x-ui.dropdown>
                        </div>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @empty
                <x-ui.table.table-row>
                    <x-ui.table.table-cell colspan="7" class="text-center h-32 text-muted-foreground">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="fa-solid fa-box-open text-2xl opacity-50"></i>
                            <p>Tidak ada software ditemukan.</p>
                        </div>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @endforelse
        </x-ui.table.table-body>
    </x-ui.table.table>
</div>