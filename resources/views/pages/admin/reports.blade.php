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
                <x-ui.alert.description>{!! session('message') !!}</x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Modul: Compliance Summary --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full">
                <div
                    class="h-10 w-10 bg-primary/10 text-primary rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Compliance Summary</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Ringkasan total instalasi perangkat lunak
                    komersial dibandingkan dengan kepemilikan lisensi kampus.</p>

                <form action="{{ route('reports.export') }}" method="POST" class="mt-auto space-y-3">
                    @csrf
                    <input type="hidden" name="report_type" value="compliance_summary">
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.button type="submit" name="format" value="pdf" variant="outline"
                            class="w-full text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200">
                            <i class="fa-solid fa-file-pdf mr-2"></i> PDF
                        </x-ui.button>
                        <x-ui.button type="submit" name="format" value="excel" variant="outline"
                            class="w-full text-green-600 hover:text-green-700 hover:bg-green-50 border-green-200">
                            <i class="fa-solid fa-file-excel mr-2"></i> Excel
                        </x-ui.button>
                    </div>
                </form>
            </div>

            {{-- Modul: Violation Report --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full">
                <div
                    class="h-10 w-10 bg-destructive/10 text-destructive rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-user-ninja"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Violation Details</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Laporan mendetail berisi daftar spesifik IP
                    Address & Komputer yang menginstall aplikasi tanpa lisensi (Ilegal).</p>

                <form action="{{ route('reports.export') }}" method="POST" class="mt-auto space-y-3">
                    @csrf
                    <input type="hidden" name="report_type" value="violation_report">
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.button type="submit" name="format" value="pdf" variant="outline"
                            class="w-full text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200">
                            <i class="fa-solid fa-file-pdf mr-2"></i> PDF
                        </x-ui.button>
                        <x-ui.button type="submit" name="format" value="excel" variant="outline"
                            class="w-full text-green-600 hover:text-green-700 hover:bg-green-50 border-green-200">
                            <i class="fa-solid fa-file-excel mr-2"></i> Excel
                        </x-ui.button>
                    </div>
                </form>
            </div>

            {{-- Modul: Asset Inventory (Placeholder) --}}
            <div class="bg-card border border-border p-5 rounded-xl shadow-sm flex flex-col h-full opacity-70">
                <div
                    class="h-10 w-10 bg-muted text-muted-foreground rounded-lg flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-desktop"></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Asset Inventory</h3>
                <p class="text-sm text-muted-foreground mb-6 grow">Daftar lengkap inventaris komputer (Hostname,
                    CPU, RAM) yang ada di lingkungan kampus.</p>

                <x-ui.button disabled class="w-full mt-auto">
                    <i class="fa-solid fa-lock mr-2"></i> Segera Hadir
                </x-ui.button>
            </div>

        </div>
    </div>
</x-layout.app>