<x-layout.app title="Dashboard" :breadcrumbs="[['name' => 'Dashboard', 'url' => null]]">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Ringkasan Dashboard</h1>
                <p class="text-muted-foreground mt-1">
                    Pantau kepatuhan lisensi perangkat lunak di lingkungan institusi
                </p>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total komputer --}}
            <x-stat-card title="Total Komputer" subtitle="Aktif" value="{{ $totalComputers }} Unit"
                icon="fa-solid fa-desktop" :trend="['value' => $newComputersThisMonth, 'positive' => true]"></x-stat-card>
            {{-- Total Software --}}
            <x-stat-card title="Total Software Terinstall" value="{{ number_format($totalInstallations) }}"
                subtitle="Aplikasi Unik" icon="fa-solid fa-download" :trend="['value' => $newInstallationThisMonth, 'positive' => true]"></x-stat-card>
            {{-- Kesehatan Sistem --}}
            <x-stat-card title="Kesehatan Sistem" value="{{ $systemHealth }}" icon="fa-solid fa-heart-pulse"
                :progress="$systemHealth"></x-stat-card>
            {{-- Peringatan --}}
            <x-stat-card title="Peringatan Kritis" value="{{ $criticalAlerts }}" icon="fa-solid fa-triangle-exclamation"
                subtitle="Aplikasi terlarang terdeteksi" variant="critical"></x-stat-card>
        </div>

        {{-- Chart --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-dashboard.os-distribution-chart :series="$osSeries" :labels="$osLabels" />
            <x-dashboard.license-status-chart :series="$licenseSeries" :labels="$licenseLabelsChart" />
        </div>

        {{-- Table Recent Activity --}}
        <div class="mt-6">
            <x-dashboard.recent-activity-table :activities="$recentActivities" />
        </div>
    </div>
</x-layout.app>
