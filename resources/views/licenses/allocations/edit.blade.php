<x-layout.app title="Edit Alokasi Lisensi" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Alokasi Lisensi', 'url' => route('license-allocations.index')],
    ['name' => 'Edit Alokasi', 'url' => null]
]">
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Edit Alokasi Lisensi</h1>
            <p class="text-muted-foreground mt-1">
                Perbarui kuota, masa berlaku, atau status alokasi lisensi fakultas.
            </p>
        </div>

        {{-- Alert Error --}}
        @if ($errors->any())
            <x-ui.alert.index variant="destructive">
                <x-ui.alert.title>Peringatan Validasi</x-ui.alert.title>
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
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm"
            x-data="{
                selectedLicenseId: '{{ old('license_inventory_id', $licenseAllocation->license_inventory_id) }}',
                currentAllocationId: {{ $licenseAllocation->id }},
                currentAllocatedQuota: {{ $licenseAllocation->allocated_quota }},
                currentAllocationStatus: '{{ $licenseAllocation->status }}',
                licenses: {
                    @foreach($licenses as $lic)
                        @php
                            // Jika lisensi ini adalah lisensi yang sedang diedit dan statusnya aktif,
                            // maka kuota alokasi saat ini dapat dihitung kembali sebagai bagian yang dapat di-edit
                            $isCurrentLicense = ($lic->id === $licenseAllocation->license_inventory_id);
                            $adjustedRemaining = $lic->remaining_unallocated + ($isCurrentLicense && $licenseAllocation->status === 'active' ? $licenseAllocation->allocated_quota : 0);
                        @endphp
                        '{{ $lic->id }}': {
                            total: {{ $lic->quota_limit }},
                            allocated: {{ $lic->total_allocated }},
                            remaining: {{ $lic->remaining_unallocated }},
                            availableForThisEdit: {{ $adjustedRemaining }},
                            po: '{{ $lic->purchase_order_number ?? '-' }}',
                            name: '{{ addslashes($lic->catalog?->normalized_name ?? 'Software') }}'
                        },
                    @endforeach
                },
                get currentLicense() {
                    return this.licenses[this.selectedLicenseId] || null;
                }
            }">
            
            <form action="{{ route('license-allocations.update', $licenseAllocation) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    {{-- Pilihan Lisensi Software --}}
                    <div class="space-y-1.5">
                        <x-form.label for="license_inventory_id">Lisensi Software Universitas <span class="text-destructive">*</span></x-form.label>
                        <select id="license_inventory_id" name="license_inventory_id" x-model="selectedLicenseId" required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <option value="">-- Pilih Lisensi Software --</option>
                            @foreach($licenses as $lic)
                                <option value="{{ $lic->id }}" @selected(old('license_inventory_id', $licenseAllocation->license_inventory_id) == $lic->id)>
                                    {{ $lic->catalog?->normalized_name }} (PO: {{ $lic->purchase_order_number ?? '-' }}) - Sisa: {{ $lic->remaining_unallocated }} / {{ $lic->quota_limit }} seat
                                </option>
                            @endforeach
                        </select>

                        {{-- Real-time License Availability Card --}}
                        <div x-show="currentLicense" x-transition class="p-3 bg-muted/50 rounded-md border border-border text-xs flex items-center justify-between mt-2">
                            <div>
                                <span class="font-medium text-foreground" x-text="currentLicense?.name"></span>
                                <span class="text-muted-foreground"> (PO: <span x-text="currentLicense?.po"></span>)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span>Total: <strong x-text="currentLicense?.total"></strong> seat</span>
                                <span class="px-2 py-0.5 rounded font-semibold text-xs bg-primary/10 text-primary">
                                    Batas Maksimal Edit Ini: <span x-text="currentLicense?.availableForThisEdit"></span> seat
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Pilihan Fakultas Penerima --}}
                    <div class="space-y-1.5">
                        <x-form.label for="faculty_id">Fakultas Penerima Alokasi <span class="text-destructive">*</span></x-form.label>
                        <select id="faculty_id" name="faculty_id" required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <option value="">-- Pilih Fakultas --</option>
                            @foreach($faculties as $fac)
                                <option value="{{ $fac->id }}" @selected(old('faculty_id', $licenseAllocation->faculty_id) == $fac->id)>
                                    {{ $fac->code }} - {{ $fac->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Kuota Alokasi --}}
                        <div class="space-y-1.5">
                            <x-form.label for="allocated_quota">Jumlah Kuota Alokasi (Seat) <span class="text-destructive">*</span></x-form.label>
                            <x-form.input id="allocated_quota" type="number" name="allocated_quota"
                                value="{{ old('allocated_quota', $licenseAllocation->allocated_quota) }}" min="1" required />
                            <p class="text-[11px] text-muted-foreground" x-show="currentLicense">
                                Tersedia maksimal untuk alokasi ini: <strong x-text="currentLicense?.availableForThisEdit"></strong> kursi.
                            </p>
                        </div>

                        {{-- Status Alokasi --}}
                        <div class="space-y-1.5">
                            <x-form.label for="status">Status Alokasi <span class="text-destructive">*</span></x-form.label>
                            <select id="status" name="status" required
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <option value="active" @selected(old('status', $licenseAllocation->status) === 'active')>Aktif (Memotong Sisa Kuota)</option>
                                <option value="inactive" @selected(old('status', $licenseAllocation->status) === 'inactive')>Nonaktif (Sementara)</option>
                                <option value="revoked" @selected(old('status', $licenseAllocation->status) === 'revoked')>Dicabut (Revoked)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Tanggal-tanggal Masa Berlaku --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <x-form.label for="allocation_date">Tanggal Penetapan <span class="text-destructive">*</span></x-form.label>
                            <x-form.input id="allocation_date" type="date" name="allocation_date"
                                value="{{ old('allocation_date', $licenseAllocation->allocation_date?->format('Y-m-d')) }}" required />
                        </div>

                        <div class="space-y-1.5">
                            <x-form.label for="start_date">Mulai Berlaku (Opsional)</x-form.label>
                            <x-form.input id="start_date" type="date" name="start_date"
                                value="{{ old('start_date', $licenseAllocation->start_date?->format('Y-m-d')) }}" />
                        </div>

                        <div class="space-y-1.5">
                            <x-form.label for="end_date">Berakhir Pada (Opsional)</x-form.label>
                            <x-form.input id="end_date" type="date" name="end_date"
                                value="{{ old('end_date', $licenseAllocation->end_date?->format('Y-m-d')) }}" />
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div class="space-y-1.5">
                        <x-form.label for="notes">Catatan Administrasi / Surat Keputusan</x-form.label>
                        <textarea id="notes" name="notes" rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            placeholder="Catatan tambahan, nomor SK penugasan, atau peruntukan khusus laboratorium...">{{ old('notes', $licenseAllocation->notes) }}</textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <a href="{{ route('license-allocations.index') }}">
                        <x-ui.button type="button" variant="outline">
                            Batal
                        </x-ui.button>
                    </a>
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-check mr-2"></i> Perbarui Alokasi Lisensi
                    </x-ui.button>
                </div>
            </form>
        </div>

    </div>
</x-layout.app>
