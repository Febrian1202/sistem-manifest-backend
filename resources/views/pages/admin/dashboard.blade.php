<x-layout.app title="Dashboard" :breadcrumbs="[['name' => 'Dashboard', 'url' => null]]">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Ringkasan Dashboard</h1>
                <p class="text-muted-foreground mt-1">
                    Pantau kepatuhan lisensi perangkat lunak di lingkungan institusi
                </p>
            </div>

            {{-- IMPROVEMENT-003: Auto-refresh indicator --}}
            <div class="flex items-center gap-2 text-xs text-muted-foreground bg-muted/30 px-3 py-2 rounded-full border border-border/50 self-start"
                x-data="{ lastUpdated: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }"
                x-init="setInterval(() => window.location.reload(), 300000)">
                <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
                <span>Data diperbarui otomatis setiap 5 menit</span>
                <span class="opacity-50">|</span>
                <span>Terakhir diperbarui: <span x-text="lastUpdated"></span></span>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Total komputer --}}
            <x-stat-card title="Total Komputer" subtitle="Aktif" value="{{ $totalComputers }} Unit"
                icon="fa-solid fa-desktop" :trend="['value' => $newComputersThisMonth, 'positive' => true]"></x-stat-card>

            {{-- Total Software --}}
            <x-stat-card title="Total Software Terinstall" value="{{ number_format($totalInstallations) }}"
                icon="fa-solid fa-download" :trend="['value' => $newInstallationThisMonth, 'positive' => true]"></x-stat-card>

            {{-- BUG-002: Kesehatan Sistem (Improved context) --}}
            <x-stat-card title="Kesehatan Sistem" value="{{ $systemHealth }} / 100" icon="fa-solid fa-heart-pulse"
                :progress="$systemHealth" subtitle="Skor Kepatuhan Sistem"
                description="Berdasarkan rasio perangkat lunak berlisensi"></x-stat-card>

            {{-- IMPROVEMENT-001: Komputer Tidak Aktif --}}
            <x-stat-card title="Komputer Tidak Aktif" value="{{ $inactiveComputers }} Unit"
                icon="fa-solid fa-plug-circle-xmark" subtitle="Tidak scan dalam 7 hari terakhir"
                :variant="$inactiveComputers > 0 ? 'warning' : 'default'"
                onclick="window.location.href='/computers?filter=inactive'"></x-stat-card>

            {{-- Peringatan --}}
            <x-stat-card title="Peringatan Kritis" value="{{ $criticalAlerts }}" icon="fa-solid fa-triangle-exclamation"
                subtitle="Aplikasi terlarang terdeteksi" variant="critical"></x-stat-card>
        </div>

        {{-- Chart --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-dashboard.os-distribution-chart :series="$osSeries" :labels="$osLabels" />
            <x-dashboard.license-status-chart :series="$licenseSeries" :labels="$licenseLabelsChart" />
        </div>

        {{-- IMPROVEMENT-004: Top 10 Software Horizontal Bar Chart --}}
        <x-dashboard.top-software-chart :topSoftware="$topSoftware" />

        {{-- Table Recent Activity --}}
        <div class="mt-6">
            <x-dashboard.recent-activity-table :activities="$recentActivities" />
        </div>
    </div>
</x-layout.app>