<x-layout.app title="Lisensi Fakultas" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Lisensi Fakultas', 'url' => null]
]">
    <div class="space-y-6">

        @if (! $faculty)
            <div class="p-6 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    <div>
                        <h3 class="font-bold">Informasi Penugasan</h3>
                        <p class="text-sm mt-1">Akun Anda belum ditugaskan ke laboratorium atau fakultas mana pun. Silakan hubungi Administrator untuk menetapkan penugasan unit kerja Anda.</p>
                    </div>
                </div>
            </div>
        @else
            {{-- Header Section --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary mb-2">
                        <i class="fa-solid fa-building-columns"></i> {{ $faculty->code ?? 'FAKULTAS' }}
                    </div>
                    <h1 class="text-2xl font-bold text-foreground">Lisensi Dialokasikan: {{ $faculty->name }}</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Daftar lisensi resmi universitas yang dialokasikan untuk perangkat laboratorium di fakultas Anda.
                    </p>
                </div>
            </div>

            {{-- Filter & Pencarian --}}
            <form method="GET" action="{{ route('lab.licenses.index') }}"
                class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col sm:flex-row gap-4 items-center">
                
                <div class="w-full relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                    <x-form.input name="search" value="{{ request('search') }}"
                        placeholder="Cari nama software atau lisensi..." class="pl-9 w-full" />
                </div>

                <div class="flex justify-end gap-2 w-full sm:w-auto">
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-search mr-2"></i> Cari
                    </x-ui.button>
                    @if(request('search'))
                        <a href="{{ route('lab.licenses.index') }}">
                            <x-ui.button type="button" variant="outline" title="Reset">
                                <i class="fa-solid fa-xmark"></i>
                            </x-ui.button>
                        </a>
                    @endif
                </div>
            </form>

            {{-- Tabel Lisensi --}}
            <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                            <x-ui.table.table-head>Software & Katalog</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Kategori</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Alokasi Fakultas</x-ui.table.table-head>
                            <x-ui.table.table-head>Masa Berlaku</x-ui.table.table-head>
                            <x-ui.table.table-head>Product / License Key</x-ui.table.table-head>
                            <x-ui.table.table-head>Catatan</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($allocations as $index => $allocation)
                            @php
                                $inventory = $allocation->licenseInventory;
                                $catalog = $inventory?->catalog;
                                $expiryDate = $allocation->end_date ?? $inventory?->expiry_date;
                                $isExpired = $expiryDate && $expiryDate->isPast();
                            @endphp
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="text-center font-medium">
                                    {{ $allocations instanceof \Illuminate\Pagination\LengthAwarePaginator ? $allocations->firstItem() + $index : $index + 1 }}
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <div class="font-semibold text-foreground flex items-center gap-2">
                                        <i class="fa-solid fa-cube text-primary"></i>
                                        {{ $catalog?->normalized_name ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-0.5">
                                        PO: {{ $inventory?->purchase_order_number ?? '-' }}
                                    </div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-muted text-muted-foreground">
                                        {{ $catalog?->category ?? 'General' }}
                                    </span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    <span class="inline-flex items-center gap-1 font-bold text-primary">
                                        {{ $allocation->allocated_quota }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">unit</span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    @if ($expiryDate)
                                        <div class="font-medium text-xs {{ $isExpired ? 'text-destructive font-bold' : 'text-foreground' }}">
                                            {{ $expiryDate->translatedFormat('d M Y') }}
                                        </div>
                                        <div class="text-[11px] {{ $isExpired ? 'text-destructive' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $isExpired ? 'Kedaluwarsa' : 'Aktif' }}
                                        </div>
                                    @else
                                        <span class="text-xs text-muted-foreground">Permanen / Tidak Ada Batas</span>
                                    @endif
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <div x-data="{
                                        showKey: false,
                                        realKey: '',
                                        loading: false,
                                        copied: false,
                                        error: null,
                                        timer: null,

                                        async revealKey(licenseId) {
                                            if (this.realKey) {
                                                this.showKey = true;
                                                this.startTimer();
                                                return;
                                            }

                                            this.loading = true;
                                            this.error = null;

                                            try {
                                                const response = await fetch('/lab/licenses/' + licenseId + '/key', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'X-Requested-With': 'XMLHttpRequest',
                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                    }
                                                });

                                                if (!response.ok) {
                                                    const errData = await response.json().catch(() => ({}));
                                                    throw new Error(errData.message ?? `Error ${response.status}: ${response.statusText}`);
                                                }

                                                const data = await response.json();
                                                this.realKey = data.key;
                                                this.showKey = true;
                                                this.startTimer();
                                            } catch (e) {
                                                this.error = e.message;
                                            } finally {
                                                this.loading = false;
                                            }
                                        },

                                        hideKey() {
                                            this.showKey = false;
                                            if (this.timer) clearTimeout(this.timer);
                                        },

                                        startTimer() {
                                            if (this.timer) clearTimeout(this.timer);
                                            this.timer = setTimeout(() => {
                                                this.hideKey();
                                            }, 30000);
                                        },

                                        async copyKey() {
                                            if (!this.realKey) return;
                                            try {
                                                await navigator.clipboard.writeText(this.realKey);
                                                this.copied = true;
                                                setTimeout(() => this.copied = false, 2000);
                                            } catch (err) {
                                                console.error('Gagal menyalin: ', err);
                                            }
                                        }
                                    }" class="inline-flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <code class="text-xs bg-muted px-2 py-1 rounded border border-border font-mono min-w-[130px] flex items-center justify-center">
                                                <template x-if="loading">
                                                    <i class="fa-solid fa-circle-notch fa-spin text-primary opacity-50"></i>
                                                </template>
                                                <template x-if="!loading">
                                                    <span x-text="showKey ? realKey : '{{ $inventory?->masked_license_key ?? '-' }}'"></span>
                                                </template>
                                            </code>

                                            <div class="flex items-center gap-1">
                                                {{-- Tombol Toggle Reveal --}}
                                                <button type="button" @click="showKey ? hideKey() : revealKey({{ $inventory->id }})"
                                                    class="h-7 w-7 rounded-md flex items-center justify-center text-muted-foreground hover:bg-muted hover:text-foreground transition-all focus:outline-none"
                                                    :title="showKey ? 'Sembunyikan' : 'Tampilkan Key'">
                                                    <i class="fa-solid" :class="showKey ? 'fa-eye-slash text-primary' : 'fa-eye'"></i>
                                                </button>

                                                {{-- Tombol Copy --}}
                                                <template x-if="showKey && realKey">
                                                    <button type="button" @click="copyKey()"
                                                        class="h-7 w-7 rounded-md flex items-center justify-center text-muted-foreground hover:bg-muted hover:text-foreground transition-all focus:outline-none"
                                                        :class="copied ? 'text-emerald-600 dark:text-emerald-400' : ''"
                                                        title="Salin ke Clipboard">
                                                        <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                                    </button>
                                                </template>

                                                <i x-show="!showKey && !loading" class="fa-solid fa-lock text-[10px] text-muted-foreground" title="Key terenkripsi"></i>
                                            </div>
                                        </div>

                                        <template x-if="error">
                                            <span class="text-[11px] text-destructive" x-text="error"></span>
                                        </template>
                                    </div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <span class="text-xs text-muted-foreground line-clamp-2" title="{{ $allocation->notes ?? '-' }}">
                                        {{ $allocation->notes ?? '-' }}
                                    </span>
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="7" class="text-center py-8 text-muted-foreground">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-box-open text-3xl opacity-30"></i>
                                        <span>Tidak ada lisensi yang dialokasikan untuk fakultas ini.</span>
                                    </div>
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table-table>
            </div>

            @if ($allocations instanceof \Illuminate\Pagination\LengthAwarePaginator && $allocations->hasPages())
                <div class="mt-4">
                    {{ $allocations->links() }}
                </div>
            @endif
        @endif

    </div>
</x-layout.app>
