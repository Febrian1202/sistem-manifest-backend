<x-layout.app title="Detail Lisensi - {{ $catalog->normalized_name }}" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Inventaris Lisensi', 'url' => route('licenses')],
    ['name' => 'Detail Lisensi', 'url' => null]
]">
    <div class="space-y-6">
        {{-- Header & Back Button --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('licenses') }}" class="h-10 w-10 rounded-full border border-border flex items-center justify-center hover:bg-muted transition-colors">
                    <i class="fa-solid fa-arrow-left text-muted-foreground"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ $catalog->normalized_name }}</h1>
                    <p class="text-muted-foreground mt-0.5">PO: {{ $license->purchase_order_number ?? 'Tanpa Nomor PO' }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                @php
                    $usageCount = $catalog->discoveries_count ?? $discoveries->total();
                    $quota = $license->quota_limit;
                    $isExpired = $license->expiry_date && \Carbon\Carbon::parse($license->expiry_date)->isPast();
                    
                    if ($isExpired) {
                        $statusBadge = 'bg-destructive/10 text-destructive border-destructive/20';
                        $statusText = 'Kedaluwarsa';
                    } elseif ($usageCount > $quota) {
                        $statusBadge = 'bg-destructive/10 text-destructive border-destructive/20';
                        $statusText = 'Over Limit';
                    } elseif ($usageCount > ($quota * 0.8)) {
                        $statusBadge = 'bg-warning/10 text-warning border-warning/20';
                        $statusText = 'Segera Habis';
                    } else {
                        $statusBadge = 'bg-success/10 text-success border-success/20';
                        $statusText = 'Aman';
                    }
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusBadge }} border">
                    {{ $statusText }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Info Utama --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Detail Lisensi Card --}}
                <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-border bg-muted/30">
                        <h3 class="font-bold text-foreground">Informasi Lisensi</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Vendor</label>
                                <p class="text-sm font-medium mt-1">{{ $license->catalog->vendor ?? 'Tidak Diketahui' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Nomor PO</label>
                                <p class="text-sm font-medium mt-1">{{ $license->purchase_order_number ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Harga Per Unit</label>
                                <p class="text-sm font-medium mt-1">Rp {{ number_format($license->price_per_unit, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Tanggal Pembelian</label>
                                <p class="text-sm font-medium mt-1">{{ $license->purchase_date ? $license->purchase_date->format('d M Y') : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Tanggal Kedaluwarsa</label>
                                <p class="text-sm font-medium mt-1 {{ $isExpired ? 'text-destructive font-bold' : '' }}">
                                    {{ $license->expiry_date ? $license->expiry_date->format('d M Y') : 'Lifetime' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">License Key</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <code class="text-xs bg-muted px-2 py-1 rounded border border-border font-mono">
                                        {{ $license->masked_license_key }}
                                    </code>
                                    <i class="fa-solid fa-lock text-[10px] text-muted-foreground" title="Key is encrypted in database"></i>
                                </div>
                            </div>
                        </div>
                        @if($license->notes)
                            <div class="md:col-span-2">
                                <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Catatan</label>
                                <p class="text-sm text-muted-foreground mt-1 bg-muted/20 p-3 rounded-lg italic">"{{ $license->notes }}"</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tabel Lacak PC --}}
                <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-border bg-muted/30 flex justify-between items-center">
                        <h3 class="font-bold text-foreground">Daftar Komputer (Lacak PC)</h3>
                        <span class="text-xs text-muted-foreground font-medium">Terdeteksi di {{ $discoveries->total() }} Perangkat</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-muted-foreground uppercase bg-muted/30 border-b border-border">
                                <tr>
                                    <th class="px-6 py-3 font-bold">Nama Komputer</th>
                                    <th class="px-6 py-3 font-bold">OS / Info</th>
                                    <th class="px-6 py-3 font-bold">Versi Software</th>
                                    <th class="px-6 py-3 font-bold">Terakhir Scan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($discoveries as $disc)
                                    <tr class="hover:bg-muted/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-foreground">{{ $disc->computer->hostname }}</span>
                                                <span class="text-[10px] text-muted-foreground font-mono uppercase">{{ $disc->computer->mac_address }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-xs">{{ $disc->computer->os_name ?? 'Unknown OS' }}</span>
                                                <span class="text-[10px] text-muted-foreground italic">{{ $disc->computer->ip_address ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-primary/5 text-primary px-2 py-0.5 rounded text-[11px] font-medium border border-primary/10">
                                                v{{ $disc->version ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-muted-foreground text-xs">
                                            {{ $disc->computer->last_seen_at ? $disc->computer->last_seen_at->diffForHumans() : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-muted-foreground">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fa-solid fa-laptop-slash text-3xl opacity-20"></i>
                                                <p>Tidak ada komputer yang terdeteksi menggunakan software ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($discoveries->hasPages())
                        <div class="p-4 border-t border-border">
                            {{ $discoveries->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="space-y-6">
                {{-- Pemakaian Card --}}
                <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
                    <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-4">Statistik Pemakaian</h4>
                    <div class="flex items-end justify-between mb-2">
                        <span class="text-3xl font-black text-foreground">{{ $usageCount }}</span>
                        <span class="text-sm text-muted-foreground">dari {{ $quota }} Lisensi</span>
                    </div>
                    @php
                        $usagePercent = $quota > 0 ? min(($usageCount / $quota) * 100, 100) : 0;
                        $barColor = 'bg-success';
                        if ($usagePercent > 80) $barColor = 'bg-destructive';
                        elseif ($usagePercent > 60) $barColor = 'bg-warning';
                    @endphp
                    <div class="h-3 w-full bg-muted rounded-full overflow-hidden mb-4">
                        <div class="h-full {{ $barColor }} transition-all duration-1000" style="width: {{ $usagePercent }}%"></div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Tersedia</span>
                            <span class="font-bold {{ ($quota - $usageCount) < 0 ? 'text-destructive' : 'text-success' }}">
                                {{ max(0, $quota - $usageCount) }} Lisensi
                            </span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Persentase</span>
                            <span class="font-bold">{{ number_format($usagePercent, 1) }}%</span>
                        </div>
                    </div>
                </div>

                {{-- Bukti Pembelian Card --}}
                @if($license->proof_image)
                    <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-border bg-muted/30">
                            <h3 class="font-bold text-foreground text-sm">Bukti Pembelian</h3>
                        </div>
                        <div class="p-4">
                            <a href="{{ asset('storage/' . $license->proof_image) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-border">
                                <img src="{{ asset('storage/' . $license->proof_image) }}" class="w-full h-auto max-h-64 object-cover transition-transform group-hover:scale-105" alt="Bukti Pembelian">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                    <span class="text-white text-xs font-medium"><i class="fa-solid fa-up-right-from-square mr-1"></i> Buka Layar Penuh</span>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout.app>
