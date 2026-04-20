@props(['licenses', 'catalogs'])

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse($licenses as $license)
        @php
            // Kalkulasi Penggunaan
            $usageCount = $license->catalog->discoveries_count ?? 0;
            $quota = $license->quota_limit;
            $usagePercent = $quota > 0 ? min(($usageCount / $quota) * 100, 100) : 0;

            // Warna Progress Bar
            $progressColor = 'bg-success'; // Hijau
            if ($usagePercent > 85)
                $progressColor = 'bg-destructive'; // Merah
            elseif ($usagePercent > 70)
                $progressColor = 'bg-warning'; // Kuning

            // Cek Kedaluwarsa
            $expiryDate = $license->expiry_date ? \Carbon\Carbon::parse($license->expiry_date) : null;
            $isExpired = $expiryDate && $expiryDate->isPast();
            $isExpiringSoon = $expiryDate && $expiryDate->isBetween(now(), now()->addDays(30));

            // Badge Status
            if ($isExpired) {
                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20"><i class="fa-solid fa-clock mr-1.5"></i> Kedaluwarsa</span>';
            } elseif ($isExpiringSoon) {
                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-warning/10 text-warning border border-warning/20"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Segera Habis</span>';
            } elseif ($usageCount > $quota) {
                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-destructive/10 text-destructive border border-destructive/20"><i class="fa-solid fa-ban mr-1.5"></i> Over Limit</span>';
            } else {
                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-success/10 text-success border border-success/20"><i class="fa-solid fa-circle-check mr-1.5"></i> Aman</span>';
            }
        @endphp

        <div
            class="bg-card border border-border rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow flex flex-col gap-5 relative">

            {{-- HEADER CARD: Icon, Judul, PO, dan Dropdown Action --}}
            <div class="flex justify-between items-start gap-3">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div
                        class="h-12 w-12 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary text-xl">
                        <i class="fa-solid fa-cube"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h3 class="font-bold text-foreground truncate text-base"
                            title="{{ $license->catalog->normalized_name ?? 'Unknown' }}">
                            {{ $license->catalog->normalized_name ?? 'Unknown' }}
                        </h3>
                        <p class="text-xs text-muted-foreground truncate mt-0.5">PO:
                            {{ $license->purchase_order_number ?? 'Tanpa Nomor PO' }}
                        </p>
                    </div>
                </div>

                {{-- Dropdown Actions (Titik Tiga) --}}
                @role('admin')
                <x-ui.dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="h-8 w-8 rounded-md flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors focus:outline-none">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-ui.dropdown-label>Opsi Lisensi</x-ui.dropdown-label>

                        {{-- SHEET DETAIL & EDIT DARI DALAM DROPDOWN --}}
                        <x-ui.sheet.sheet>
                            <x-ui.sheet.trigger class="w-full block">
                                <x-ui.dropdown-item class="p-0">
                                    <button type="button"
                                        class="flex w-full items-center justify-start px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground">
                                        <i class="fa-regular fa-eye mr-2 w-4 text-center opacity-70"></i>
                                        <span>Lihat Detail / Edit</span>
                                    </button>
                                </x-ui.dropdown-item>
                            </x-ui.sheet.trigger>

                            <x-ui.sheet.content side="right">
                                <x-ui.sheet.header>
                                    <x-ui.sheet.title>Detail Inventaris Lisensi</x-ui.sheet.title>
                                    <x-ui.sheet.description>Lihat dan perbarui informasi lisensi
                                        <strong>{{ $license->catalog->normalized_name ?? '' }}</strong>.</x-ui.sheet.description>
                                </x-ui.sheet.header>

                                {{-- PENTING: enctype="multipart/form-data" agar bisa upload/update gambar --}}
                                <form action="{{ route('licenses.update', $license->id) }}" method="POST"
                                    enctype="multipart/form-data" class="mt-6 space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="space-y-1.5">
                                        <x-form.label>Software</x-form.label>
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

                                    <div class="space-y-1.5">
                                        <x-form.label>Nomor Purchase Order (PO)</x-form.label>
                                        <x-form.input name="purchase_order_number"
                                            value="{{ $license->purchase_order_number }}" />
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <x-form.label>Kuota Lisensi</x-form.label>
                                            <x-form.input type="number" name="quota_limit"
                                                value="{{ $license->quota_limit }}" min="1" required />
                                        </div>
                                        <div class="space-y-1.5">
                                            <x-form.label>Harga Satuan (Rp)</x-form.label>
                                            <x-form.input type="number" name="price_per_unit"
                                                value="{{ $license->price_per_unit }}" min="0" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <x-form.label>Tanggal Beli</x-form.label>
                                            <x-form.input type="date" name="purchase_date"
                                                value="{{ $license->purchase_date ? \Carbon\Carbon::parse($license->purchase_date)->format('Y-m-d') : '' }}" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <x-form.label>Kedaluwarsa</x-form.label>
                                            <x-form.input type="date" name="expiry_date"
                                                value="{{ $license->expiry_date ? \Carbon\Carbon::parse($license->expiry_date)->format('Y-m-d') : '' }}" />
                                        </div>
                                    </div>

                                    {{-- BUKTI PEMBELIAN --}}
                                    @if($license->proof_image)
                                        <div class="space-y-1.5 bg-muted/50 p-3 rounded-lg border border-border">
                                            <x-form.label>Bukti Pembelian Saat Ini</x-form.label>
                                            <a href="{{ asset('storage/' . $license->proof_image) }}" target="_blank"
                                                class="block w-full h-32 rounded-md border border-border overflow-hidden relative group">
                                                <img src="{{ asset('storage/' . $license->proof_image) }}"
                                                    class="w-full h-full object-cover transition-transform group-hover:scale-105"
                                                    alt="Bukti PO">
                                                <div
                                                    class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                    <span class="text-white text-xs font-medium"><i
                                                            class="fa-solid fa-up-right-from-square mr-1"></i> Buka Layar
                                                        Penuh</span>
                                                </div>
                                            </a>
                                        </div>
                                    @endif

                                    <div class="space-y-1.5">
                                        <x-form.label>Update Bukti Gambar (Opsional)</x-form.label>
                                        <input type="file" name="proof_image"
                                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium">
                                    </div>

                                    <div class="space-y-1.5">
                                        <x-form.label>Catatan Tambahan</x-form.label>
                                        <textarea name="notes" rows="3"
                                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">{{ $license->notes }}</textarea>
                                    </div>

                                    <x-ui.sheet.footer class="pt-4">
                                        <x-ui.button type="submit" class="w-full">
                                            <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                                        </x-ui.button>
                                    </x-ui.sheet.footer>
                                </form>
                            </x-ui.sheet.content>
                        </x-ui.sheet.sheet>

                        <x-ui.dropdown-separator />

                        {{-- Hapus Data --}}
                        <form action="{{ route('licenses.destroy', $license->id) }}" method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus data lisensi ini?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.dropdown-item class="p-0">
                                <button type="submit"
                                    class="flex w-full items-center justify-start px-2 py-1.5 text-sm text-destructive outline-none transition-colors hover:bg-destructive/10 hover:text-destructive">
                                    <i class="fa-regular fa-trash-can mr-2 w-4 text-center"></i>
                                    <span>Hapus Data</span>
                                </button>
                            </x-ui.dropdown-item>
                        </form>
                    </x-slot>
                </x-ui.dropdown>
                @endrole
            </div>

            {{-- BODY CARD: Progress Bar & Status Angka --}}
            <div class="space-y-2 mt-1">
                <div class="flex justify-between items-end text-sm">
                    <span class="text-muted-foreground font-medium">Pemakaian Lisensi</span>
                    <span
                        class="font-bold text-lg {{ $usageCount > $quota ? 'text-destructive' : 'text-foreground' }} leading-none">
                        {{ $usageCount }} <span class="text-xs text-muted-foreground font-normal">/ {{ $quota }}</span>
                    </span>
                </div>
                <div class="h-2 w-full bg-muted rounded-full overflow-hidden">
                    <div class="h-full {{ $progressColor }} transition-all duration-500 ease-out"
                        style="width: {{ $usagePercent }}%"></div>
                </div>
            </div>

            {{-- FOOTER CARD: Tanggal & Badge --}}
            <div class="flex items-center justify-between mt-auto pt-4 border-t border-border/60">
                <div class="flex flex-col">
                    <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-0.5">Exp.
                        Date</span>
                    <span
                        class="text-xs {{ $isExpired ? 'text-destructive font-semibold' : 'text-foreground font-medium' }}">
                        {{ $expiryDate ? $expiryDate->format('d M Y') : 'Lifetime (Selamanya)' }}
                    </span>
                </div>
                <div>
                    {!! $statusBadge !!}
                </div>
            </div>

        </div>
    @empty
        <div
            class="col-span-full flex flex-col items-center justify-center p-12 text-center bg-card border border-border rounded-xl border-dashed">
            <div class="h-16 w-16 bg-muted flex items-center justify-center rounded-full mb-4">
                <i class="fa-solid fa-file-invoice-dollar text-3xl text-muted-foreground opacity-50"></i>
            </div>
            <h3 class="text-lg font-bold text-foreground mb-1">Belum Ada Lisensi</h3>
            <p class="text-muted-foreground text-sm max-w-sm">Anda belum menambahkan data pembelian lisensi perangkat lunak
                ke dalam sistem.</p>
        </div>
    @endforelse
</div>