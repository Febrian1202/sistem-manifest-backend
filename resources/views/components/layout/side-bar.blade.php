<aside
    class="flex flex-col bg-sidebar border-r border-sidebar-border transition-all duration-300 ease-in-out h-screen fixed md:static z-30"
    :class="sidebarOpen ? 'w-64 translate-x-0' : 'w-18 -translate-x-full md:translate-x-0'">

    <div class="h-16 flex items-center justify-between px-4 border-b border-sidebar-border">

        <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap" x-show="sidebarOpen">
            <i class="fa-solid fa-graduation-cap text-sidebar-foreground text-2xl tracking-tight"></i>
            <div class="flex flex-col justify-center leading-none">
                <span class="font-bold text-sidebar-foreground text-md tracking-tight">UniLicense</span>
                <span class="text-sidebar-foreground text-[10px] font-medium opacity-80 tracking-wide">System
                    Manifest</span>
            </div>
        </div>

        <i class="flex items-center justify-center fa-solid fa-graduation-cap text-sidebar-foreground text-2xl tracking-tight "
            x-show="!sidebarOpen">
        </i>

    </div>

    <nav class="flex-1 overflow-y-auto py-4 flex flex-col gap-1 px-3">

        {{-- 
           LOGIKA STYLE BARU (Sesuai app.css):
           - Active: bg-sidebar-accent + text-sidebar-accent-foreground
           - Inactive: text-sidebar-foreground + hover:bg-sidebar-accent
        --}}
        @php
            $activeClass = 'bg-sidebar-accent text-sidebar-primary font-medium';
            $inactiveClass = 'text-sidebar-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground';
        @endphp

        <a href="/"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group {{ request()->is('/') ? $activeClass : $inactiveClass }}">
            <div class="w-6 flex justify-center">
                <i class="fa-solid fa-house text-lg"></i>
            </div>
            <span class="font-medium whitespace-nowrap transition-opacity duration-200"
                :class="sidebarOpen ? 'opacity-100 block' : 'opacity-0 hidden'">
                Dashboard
            </span>

            <div class="absolute left-16 bg-popover text-popover-foreground border border-border text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50 md:hidden"
                :class="!sidebarOpen ? 'md:block' : ''">
                Dashboard
            </div>
        </a>

        <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
            Manajemen Aset
        </div>
        <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen"></div>

        <a href="/computers"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group {{ request()->is('computers*') ? $activeClass : $inactiveClass }}">
            <div class="w-6 flex justify-center">
                <i class="fa-solid fa-desktop text-lg"></i>
            </div>
            <span class="font-medium whitespace-nowrap" :class="sidebarOpen ? 'opacity-100 block' : 'opacity-0 hidden'">
                Data Komputer
            </span>
        </a>

        <a href="/softwares"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group {{ request()->is('softwares*') ? $activeClass : $inactiveClass }}">
            <div class="w-6 flex justify-center">
                <i class="fa-solid fa-database text-lg"></i>
            </div>
            <span class="font-medium whitespace-nowrap" :class="sidebarOpen ? 'opacity-100 block' : 'opacity-0 hidden'">
                Katalog Software
            </span>
        </a>

        <div class="mt-4 px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider transition-opacity duration-200"
            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
            Lisensi & Audit
        </div>
        <div class="mt-4 mb-2 border-t border-sidebar-border" x-show="!sidebarOpen"></div>

        <a href="/licenses"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group {{ request()->is('licenses*') ? $activeClass : $inactiveClass }}">
            <div class="w-6 flex justify-center">
                <i class="fa-solid fa-file-contract text-lg"></i>
            </div>
            <span class="font-medium whitespace-nowrap" :class="sidebarOpen ? 'opacity-100 block' : 'opacity-0 hidden'">
                Inventaris Lisensi
            </span>
        </a>

        <a href="/compliance"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group {{ request()->is('compliance*') ? $activeClass : $inactiveClass }}">
            <div class="w-6 flex justify-center">
                <i class="fa-solid fa-shield-halved text-lg"></i>
            </div>
            <span class="font-medium whitespace-nowrap" :class="sidebarOpen ? 'opacity-100 block' : 'opacity-0 hidden'">
                Audit Kepatuhan
            </span>
        </a>

    </nav>

    <div class="p-3 border-t border-sidebar-border justify-center flex">
        <button @click="sidebarOpen = !sidebarOpen"
            class="text-sidebar-foreground hover:text-sidebar-accent-foreground hover:bg-sidebar-accent p-2 rounded-md focus:outline-none transition-colors">
            <i class="fa-solid fa-angle-right text-xl transition-transform duration-300"
                :class="sidebarOpen ? 'rotate-180' : 'rotate-0'"></i>
        </button>
    </div>
</aside>
