@props(['licenses', 'catalogs'])

<div class="rounded-md border border-border bg-card shadow-sm overflow-x-auto">
    <x-ui.table.table>
        <x-ui.table.table-header>
            <x-ui.table.table-row>
                <x-ui.table.table-head>Software</x-ui.table.table-head>
                <x-ui.table.table-head>Nomor PO</x-ui.table.table-head>
                <x-ui.table.table-head>Penggunaan / Kuota</x-ui.table.table-head>
                <x-ui.table.table-head>Tgl Kedaluwarsa</x-ui.table.table-head>
                <x-ui.table.table-head>Status</x-ui.table.table-head>
                <x-ui.table.table-head>Bukti Pembelian</x-ui.table.table-head>
                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
            </x-ui.table.table-row>
        </x-ui.table.table-header>

        <x-ui.table.table-body>
            @forelse($licenses as $license)
                @php
                    // Kalkulasi Penggunaan
                    $usageCount = $license->catalog->discoveries_count ?? 0;
                    $quota = $license->quota_limit;
                    $usagePercent = $quota > 0 ? min(($usageCount / $quota) * 100, 100) : 0;

                    // Warna Progress Bar
                    $progressColor = 'bg-green-500';
                    if ($usagePercent > 85)
                        $progressColor = 'bg-red-500';
                    elseif ($usagePercent > 70)
                        $progressColor = 'bg-yellow-500';

                    // Cek Kedaluwarsa
                    $expiryDate = $license->expiry_date ? \Carbon\Carbon::parse($license->expiry_date) : null;
                    $isExpired = $expiryDate && $expiryDate->isPast();
                    $isExpiringSoon = $expiryDate && $expiryDate->isBetween(now(), now()->addDays(30));

                    // Badge Status
                    if ($isExpired) {
                        $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20"><i class="fa-solid fa-clock mr-1.5"></i> Expired</span>';
                    } elseif ($isExpiringSoon) {
                        $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-warning/10 text-warning border border-warning/20"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Expiring Soon</span>';
                    } elseif ($usageCount > $quota) {
                        $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20"><i class="fa-solid fa-ban mr-1.5"></i> Over Limit</span>';
                    } else {
                        $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success/10 text-success border border-success/20"><i class="fa-solid fa-circle-check mr-1.5"></i> Active</span>';
                    }
                @endphp

                <x-ui.table.table-row>
                    {{-- Nama Software --}}
                    <x-ui.table.table-cell class="font-medium">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-8 w-8 rounded bg-primary/10 flex items-center justify-center text-primary text-xs">
                                <i class="fa-solid fa-key"></i>
                            </div>
                            <span class="truncate max-w-48" title="{{ $license->catalog->normalized_name ?? 'Unknown' }}">
                                {{ $license->catalog->normalized_name ?? 'Unknown' }}
                            </span>
                        </div>
                    </x-ui.table.table-cell>

                    {{-- PO Number --}}
                    <x-ui.table.table-cell class="text-muted-foreground text-xs font-mono">
                        {{ $license->purchase_order_number ?? '-' }}
                    </x-ui.table.table-cell>

                    {{-- Usage / Quota Bar --}}
                    <x-ui.table.table-cell>
                        <div class="flex flex-col gap-1 w-32">
                            <div class="flex justify-between text-[10px] text-muted-foreground">
                                <span>{{ $usageCount }} Dipakai</span>
                                <span>{{ $quota }} Total</span>
                            </div>
                            <div class="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                                <div class="h-full {{ $progressColor }} transition-all" style="width: {{ $usagePercent }}%">
                                </div>
                            </div>
                        </div>
                    </x-ui.table.table-cell>

                    {{-- Expiry Date --}}
                    <x-ui.table.table-cell
                        class="text-xs {{ $isExpired ? 'text-destructive font-semibold' : 'text-muted-foreground' }}">
                        {{ $expiryDate ? $expiryDate->format('d M Y') : 'Lifetime' }}
                    </x-ui.table.table-cell>

                    {{-- Status --}}
                    <x-ui.table.table-cell>
                        {!! $statusBadge !!}
                    </x-ui.table.table-cell>

                    <!-- Bukti Pembelian -->
                    <x-ui.table.table-cell>
                        @if($license->proof_image)
                            <a href="{{ asset('storage/' . $license->proof_image) }}" target="_blank"
                                class="group relative inline-block">
                                <div class="h-10 w-10 overflow-hidden rounded border border-border">
                                    <img src="{{ asset('storage/' . $license->proof_image) }}"
                                        class="h-full w-full object-cover transition-transform group-hover:scale-110">
                                </div>
                                <div
                                    class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100 rounded">
                                    <i class="fa-solid fa-eye text-white text-[10px]"></i>
                                </div>
                            </a>
                        @else
                            <span class="text-xs text-muted-foreground italic">No image</span>
                        @endif
                    </x-ui.table.table-cell>

                    {{-- Aksi (Dropdown & Edit) --}}
                    <x-ui.table.table-cell class="text-right">
                        <x-ui.dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <x-ui.button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </x-ui.button>
                            </x-slot>
                            <x-slot name="content">
                                <x-ui.dropdown-label>Actions</x-ui.dropdown-label>

                                {{-- EDIT SHEET --}}
                                <x-ui.sheet.sheet>
                                    <x-ui.sheet.trigger class="w-full block">
                                        <x-ui.dropdown-item>
                                             <i class="fa-regular fa-pen-to-square mr-2 w-4 text-center opacity-70"></i>
                                             <span>Edit Lisensi</span>
                                         </x-ui.dropdown-item>
                                    </x-ui.sheet.trigger>

                                    <x-ui.sheet.content side="right">
                                        <x-ui.sheet.header>
                                            <x-ui.sheet.title>Edit Inventaris Lisensi</x-ui.sheet.title>
                                            <x-ui.sheet.description>Perbarui detail pembelian lisensi aset IT
                                                ini.</x-ui.sheet.description>
                                        </x-ui.sheet.header>

                                        <form action="{{ route('licenses.update', $license->id) }}" method="POST"
                                            class="mt-6 space-y-4">
                                            @csrf
                                            @method('PUT')

                                            <div class="space-y-1.5">
                                                <x-form.label>Pilih Software</x-form.label>
                                                <x-ui.select.index name="catalog_id" value="{{ $license->catalog_id }}">
                                                    <x-ui.select.trigger />
                                                    <x-ui.select.content>
                                                        @foreach($catalogs as $cat)
                                                            <x-ui.select.item
                                                                value="{{ $cat->id }}">{{ $cat->normalized_name }}</x-ui.select.item>
                                                        @endforeach
                                                    </x-ui.select.content>
                                                </x-ui.select.index>
                                            </div>

                                            <div class="space-y-1.5" x-data="{ showKey: false }">
                                                <x-form.label>License Key / Product Key</x-form.label>
                                                <div class="relative">
                                                    <x-form.input x-bind:type="showKey ? 'text' : 'password'" name="license_key" placeholder="Opsional — biarkan kosong jika tidak diubah" class="pr-10" />
                                                    <button type="button" @click="showKey = !showKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                                                        <i class="fa-solid" :class="showKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                    </button>
                                                </div>
                                                <p class="text-[10px] text-muted-foreground italic mt-1">Key saat ini: {{ $license->masked_license_key }}</p>
                                            </div>

                                            <div class="space-y-1.5">
                                                <x-form.label>Nomor Purchase Order (PO)</x-form.label>
                                                <x-form.input name="purchase_order_number"
                                                    value="{{ $license->purchase_order_number }}"
                                                    placeholder="INV-2026/..." />
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <x-form.label>Kuota Lisensi</x-form.label>
                                                    <x-form.input type="number" name="quota_limit"
                                                        value="{{ $license->quota_limit }}" min="1" required />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-form.label>Harga Per Unit (Rp)</x-form.label>
                                                    <x-form.input type="number" name="price_per_unit"
                                                        value="{{ $license->price_per_unit != 0 ? $license->price_per_unit : '' }}" placeholder="Opsional" min="0" />
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <x-form.label>Tanggal Beli</x-form.label>
                                                    <x-form.input type="text" name="purchase_date" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value) this.type='text'"
                                                        value="{{ $license->purchase_date ? \Carbon\Carbon::parse($license->purchase_date)->format('Y-m-d') : '' }}" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-form.label>Kedaluwarsa</x-form.label>
                                                    <x-form.input type="text" name="expiry_date" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value) this.type='text'"
                                                        value="{{ $license->expiry_date ? \Carbon\Carbon::parse($license->expiry_date)->format('Y-m-d') : '' }}" />
                                                </div>
                                            </div>

                                            <div class="space-y-1.5">
                                                <x-form.label>Catatan</x-form.label>
                                                <textarea name="notes" rows="3"
                                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">{{ $license->notes }}</textarea>
                                            </div>

                                            <x-ui.sheet.footer>
                                                <x-ui.button type="submit" class="w-full">
                                                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                                                </x-ui.button>
                                            </x-ui.sheet.footer>
                                        </form>
                                    </x-ui.sheet.content>
                                </x-ui.sheet.sheet>

                                <x-ui.dropdown-separator />

                                {{-- DELETE FORM --}}
                                <form action="{{ route('licenses.destroy', $license->id) }}" method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus data lisensi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.dropdown-item destructive type="submit">
                                        <i class="fa-regular fa-trash-can mr-2 w-4 text-center opacity-70"></i>
                                        <span>Hapus Data</span>
                                    </x-ui.dropdown-item>
                                </form>

                            </x-slot>
                        </x-ui.dropdown>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @empty
                <x-ui.table.table-row>
                    <x-ui.table.table-cell colspan="7" class="text-center h-32 text-muted-foreground">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="fa-solid fa-file-invoice-dollar text-2xl opacity-50"></i>
                            <p>Tidak ada inventaris lisensi yang tercatat.</p>
                        </div>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @endforelse
        </x-ui.table.table-body>
    </x-ui.table.table>
</div>