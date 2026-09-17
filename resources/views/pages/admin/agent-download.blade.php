<x-layout.app title="Download Tools Pemindai" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Data Komputer', 'url' => route('computers')],
    ['name' => 'Download Tools Pemindai', 'url' => null]
]">
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-foreground">Download Tools Pemindai</h1>
            <p class="text-muted-foreground mt-1">
                Unduh paket bundle scanner otomatis (PowerShell) yang telah terkonfigurasi untuk laboratorium tertentu.
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
            <form action="{{ route('agent.download') }}" method="POST" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <x-form.label for="laboratory_id">
                        Pilih Laboratorium Tujuan <span class="text-destructive">*</span>
                    </x-form.label>
                    <select id="laboratory_id" name="laboratory_id" required
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        <option value="">-- Pilih Laboratorium --</option>
                        @foreach($laboratories as $lab)
                            <option value="{{ $lab->id }}" {{ old('laboratory_id') == $lab->id ? 'selected' : '' }}>
                                {{ $lab->name }} ({{ $lab->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Setiap komputer yang memindai menggunakan paket ini akan secara otomatis terdaftar di bawah laboratorium yang dipilih.
                    </p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-border">
                    <a href="{{ route('computers') }}">
                        <x-ui.button type="button" variant="outline">
                            Kembali
                        </x-ui.button>
                    </a>
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-download mr-2"></i> Download Scanner
                    </x-ui.button>
                </div>
            </form>
        </div>

        {{-- Petunjuk Penggunaan --}}
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm space-y-4">
            <h2 class="text-base font-semibold text-foreground flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-primary"></i>
                Panduan Penggunaan Tools Pemindai
            </h2>
            <div class="text-sm text-muted-foreground space-y-3">
                <p>
                    Paket installer yang diunduh berupa file ZIP (<code class="text-xs bg-muted px-1.5 py-0.5 rounded text-foreground">usn-manifest-agent.zip</code>) yang berisi:
                </p>
                <ul class="list-disc list-inside space-y-1 text-xs text-foreground/80 pl-2">
                    <li><strong class="text-foreground">scanner.ps1</strong>: Skrip pemindai spesifikasi perangkat keras dan inventaris perangkat lunak.</li>
                    <li><strong class="text-foreground">setup_tasks.ps1</strong>: Skrip otomatisasi pendaftaran ke Windows Task Scheduler.</li>
                    <li><strong class="text-foreground">config.json</strong>: File konfigurasi integrasi server dan identitas laboratorium tujuan.</li>
                    <li><strong class="text-foreground">instruksi.txt</strong>: Panduan pemasangan dan pencopotan penjadwalan pemindaian.</li>
                </ul>
                <div class="rounded-md bg-muted/50 p-3 border border-border text-xs text-foreground/80 space-y-1">
                    <p class="font-semibold text-foreground">Langkah Pemasangan di Komputer Klien:</p>
                    <ol class="list-decimal list-inside space-y-1 pl-1">
                        <li>Ekstrak seluruh file ZIP ke folder permanen (misalnya: <code class="bg-background px-1 py-0.5 rounded">C:\USN-Manifest-Agent\</code>).</li>
                        <li>Jalankan <code class="bg-background px-1 py-0.5 rounded">scanner.ps1</code> dengan PowerShell untuk pendaftaran dan pemindaian perdana.</li>
                        <li>Jalankan <code class="bg-background px-1 py-0.5 rounded">setup_tasks.ps1</code> (Run as Administrator) agar pemindaian terjadwal otomatis.</li>
                    </ol>
                </div>
            </div>
        </div>

    </div>
</x-layout.app>
