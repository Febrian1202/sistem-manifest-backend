<x-layout.app title="Pusat Laporan" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Pusat Laporan', 'url' => null]]">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Pusat Laporan (Report Generator)</h1>
            <p class="text-muted-foreground mt-1">Hasilkan dokumen laporan format PDF atau Excel untuk keperluan audit
                dan manajemen.</p>
        </div>

        @if (session('status'))
            <x-ui.alert.index variant="{{ session('status') }}" class="mb-6">
                <x-ui.alert.title>Informasi</x-ui.alert.title>
                <x-ui.alert.description>{{ session('message') }}</x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Modul: Ringkasan Eksekutif --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full border-blue-100 bg-blue-50/10">
                <div class="h-10 w-10 bg-blue-500/10 text-blue-500 rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-file-contract"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Ringkasan Eksekutif</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Laporan ringkasan kondisi kepatuhan dan aset IT dalam format eksekutif (PDF Only).</p>

                <div class="mt-auto">
                    <a href="{{ route('reports.eksekutif') }}" class="block">
                        <x-ui.button variant="default" class="w-full">
                            <i class="fa-solid fa-eye mr-2"></i> Preview & Filter
                        </x-ui.button>
                    </a>
                </div>
            </div>

            {{-- Modul: Inventaris Komputer --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full border-green-100 bg-green-50/10">
                <div class="h-10 w-10 bg-green-500/10 text-green-500 rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-desktop"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Inventaris Komputer</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Daftar lengkap inventaris hardware, spesifikasi OS, dan status konektivitas komputer.</p>

                <div class="mt-auto">
                    <a href="{{ route('reports.komputer') }}" class="block">
                        <x-ui.button variant="default" class="w-full">
                            <i class="fa-solid fa-eye mr-2"></i> Preview & Filter
                        </x-ui.button>
                    </a>
                </div>
            </div>

            {{-- Modul: Inventaris Software --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full border-purple-100 bg-purple-50/10">
                <div class="h-10 w-10 bg-purple-500/10 text-purple-500 rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Inventaris Software</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Rekapitulasi seluruh software yang terdeteksi di jaringan beserta jumlah instalasinya.</p>

                <div class="mt-auto">
                    <a href="{{ route('reports.software') }}" class="block">
                        <x-ui.button variant="default" class="w-full">
                            <i class="fa-solid fa-eye mr-2"></i> Preview & Filter
                        </x-ui.button>
                    </a>
                </div>
            </div>

            {{-- Modul: Kepatuhan Lisensi --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full border-amber-100 bg-amber-50/10">
                <div class="h-10 w-10 bg-amber-500/10 text-amber-500 rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Kepatuhan Lisensi</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Laporan mendalam status lisensi per perangkat untuk audit kepatuhan (Compliant/Non-Compliant).</p>

                <div class="mt-auto">
                    <a href="{{ route('reports.kepatuhan') }}" class="block">
                        <x-ui.button variant="default" class="w-full">
                            <i class="fa-solid fa-eye mr-2"></i> Preview & Filter
                        </x-ui.button>
                    </a>
                </div>
            </div>

            {{-- Modul: Status Lisensi --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full border-indigo-100 bg-indigo-50/10">
                <div class="h-10 w-10 bg-indigo-500/10 text-indigo-500 rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Status Lisensi (Quota)</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Pemantauan sisa kuota lisensi komersial dan rekapitulasi penggunaan seat aplikasi.</p>

                <div class="mt-auto">
                    <a href="{{ route('reports.lisensi') }}" class="block">
                        <x-ui.button variant="default" class="w-full">
                            <i class="fa-solid fa-eye mr-2"></i> Preview & Filter
                        </x-ui.button>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-layout.app>