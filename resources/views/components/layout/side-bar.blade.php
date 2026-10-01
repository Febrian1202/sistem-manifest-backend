<aside
    class="flex flex-col bg-sidebar border-r border-sidebar-border transition-all duration-300 ease-in-out h-screen h-dvh fixed md:static z-50"
    :class="sidebarOpen ? 'w-64 translate-x-0' : 'w-18 -translate-x-full md:translate-x-0'">

    <div class="h-16 flex items-center justify-between px-4 border-b border-sidebar-border">

        <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap" x-show="sidebarOpen">
            <img src="{{ asset('assets/logo-usn.png') }}" class="h-8 w-8 object-contain" alt="Logo USN">
            <div class="flex flex-col justify-center leading-none">
                <span class="font-bold text-sidebar-foreground text-md tracking-tight">UniLicense</span>
                <span class="text-sidebar-foreground text-[10px] font-medium opacity-80 tracking-wide">Sistem
                    Manifest</span>
            </div>
        </div>

        <div class="w-full flex justify-center" x-show="!sidebarOpen" style="display: none;">
            <img src="{{ asset('assets/logo-usn.png') }}" class="h-7 w-7 object-contain" alt="Logo USN">
        </div>

    </div>

    <nav class="flex-1 overflow-y-auto py-4 flex flex-col gap-1 px-3">

        {{-- 1. UTAMA --}}
        <x-layout.nav-item href="{{ route('dashboard') }}" icon="fa-chart-pie" label="Dashboard" :active="request()->is('dashboard*')" />

        {{-- 2. ORGANISASI (Admin) --}}
        @role('admin')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Organisasi
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('faculties.index') }}" icon="fa-building-columns" label="Fakultas" :active="request()->is('faculties*')" />
            <x-layout.nav-item href="{{ route('laboratories.index') }}" icon="fa-flask" label="Laboratorium" :active="request()->is('laboratories*')" />
        @endrole

        {{-- 3. INFRASTRUKTUR & PERANGKAT (Admin & Pimpinan) --}}
        @role('admin|pimpinan')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Infrastruktur
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('computers') }}" icon="fa-desktop" label="Data Komputer" :active="request()->is('computers*')" />
            <x-layout.nav-item href="{{ route('monitoring.index') }}" icon="fa-wave-square" label="Riwayat Scan" :active="request()->is('monitoring') || (request()->is('monitoring/*') && !request()->is('monitoring/changes*') && !request()->is('monitoring/compliance*'))" />
            <x-layout.nav-item href="{{ route('monitoring.changes') }}" icon="fa-clock-rotate-left" label="Perubahan Software" :active="request()->is('monitoring/changes*')" />
        @endrole

        {{-- 4. SOFTWARE & LISENSI (Admin & Pimpinan) --}}
        @role('admin|pimpinan')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Software & Lisensi
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('softwares') }}" icon="fa-box-archive" label="Katalog Software" :active="request()->is('softwares*')" />
            <x-layout.nav-item href="{{ route('licenses') }}" icon="fa-key" label="Inventaris Lisensi" :active="request()->is('licenses*')" />
            @role('admin')
                <x-layout.nav-item href="{{ route('license-allocations.index') }}" icon="fa-diagram-project" label="Alokasi Lisensi" :active="request()->is('license-allocations*')" />
            @endrole
            <x-layout.nav-item href="{{ route('compliance') }}" icon="fa-shield-halved" label="Audit Kepatuhan" :active="request()->is('compliance*')" />
        @endrole

        {{-- 5. LAPORAN & AUDIT (Admin & Pimpinan) --}}
        @role('admin|pimpinan')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Laporan
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('reports') }}" icon="fa-file-lines" label="{{ auth()->user()->hasRole('admin') ? 'Pusat Laporan' : 'Laporan & Cetak' }}" :active="(request()->is('reports') || (request()->is('reports/*') && !request()->is('reports/kebutuhan-lisensi*'))) && !request()->is('report-submissions*')" />
            <x-layout.nav-item href="{{ route('reports.kebutuhan-lisensi') }}" icon="fa-file-invoice" label="Analisis Kebutuhan Lisensi" :active="request()->is('reports/kebutuhan-lisensi*')" />
            @role('admin')
                <x-layout.nav-item href="{{ route('report-submissions.index') }}" icon="fa-paper-plane" label="Kirim ke PJ Lab" :active="request()->is('report-submissions*')" />
            @endrole
        @endrole

        {{-- 6. MENU PJ & STAFF LAB --}}
        @role('kepala_lab')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Menu PJ Lab
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('lab.inventory.index') }}" icon="fa-boxes-stacked" label="Inventaris Lab" :active="request()->is('lab/inventory*')" />
            <x-layout.nav-item href="{{ route('lab.reports.index') }}" icon="fa-clipboard-check" label="Review Laporan" :active="request()->is('lab/reports*')" />
            <x-layout.nav-item href="{{ route('agent.download-page') }}" icon="fa-download" label="Download Scanner" :active="request()->is('agent*')" />
        @endrole

        @role('staff_lab')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Menu Staff Lab
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('lab.inventory.index') }}" icon="fa-desktop" label="Komputer & Aset" :active="request()->is('lab/inventory*')" />
            <x-layout.nav-item href="{{ route('agent.download-page') }}" icon="fa-download" label="Download Scanner" :active="request()->is('agent*')" />
        @endrole

        {{-- 7. PENGATURAN SISTEM (Admin Only) --}}
        @role('admin')
            <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                Pengaturan
            </div>
            <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen" style="display: none;"></div>

            <x-layout.nav-item href="{{ route('accounts') }}" icon="fa-users-gear" label="Manajemen Akun" :active="request()->is('accounts*')" />
            <x-layout.nav-item href="{{ route('activity-logs') }}" icon="fa-clipboard-list" label="Log Aktivitas" :active="request()->is('activity-logs*')" />
            <x-layout.nav-item href="{{ route('agent.download-page') }}" icon="fa-download" label="Download Scanner" :active="request()->is('agent*')" />
        @endrole

    </nav>

    <div class="p-3 border-t border-sidebar-border justify-center hidden md:flex">
        <x-button @click=" sidebarOpen=!sidebarOpen" class="transition-colors w-full" variant="outline">
        <i class="fa-solid fa-angle-right text-xl transition-transform duration-300"
            :class="sidebarOpen ? 'rotate-180' : 'rotate-0'"></i>
        </x-button>
    </div>
</aside>
