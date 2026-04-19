@props(['breadcrumbs' => []])

<header
    class="relative z-50 h-16 bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/60 border-b border-border flex items-center justify-between px-6">

    {{-- Kiri: Toggle & Breadcrumb --}}
    <div class="flex items-center gap-4">

        {{-- Breadcrumb --}}
        <nav class="flex items-center text-sm font-medium">
            @if (empty($breadcrumbs))
                <span class="text-foreground font-bold text-lg">Dashboard</span>
            @else
                <ol class="flex items-center">
                    @foreach ($breadcrumbs as $item)
                        <li>
                            <div class="flex items-center">
                                @if (!$loop->first)
                                    <i class="fa-solid fa-chevron-right text-muted-foreground text-xs mx-2"></i>
                                @endif

                                @if ($loop->last)
                                    <span class="text-foreground font-bold text-md">
                                        {{ $item['name'] }}
                                    </span>
                                @else
                                    <a href="{{ $item['url'] ?? '#' }}"
                                        class="text-muted-foreground hover:text-foreground transition-colors">
                                        {{ $item['name'] }}
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </nav>
    </div>

    {{-- Kanan: Search & Menu --}}
    <div class="flex items-center gap-4">

        {{-- Dropdown Notifikasi --}}
        <x-dropdown align="right" width="w-80">
            <x-slot name="trigger">
                <button
                    class="relative rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                    <i class="fa-bell text-lg" :class="open ? 'fa-solid' : 'fa-regular'"></i>
                    <span class="absolute top-2 right-2 flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-destructive opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-destructive"></span>
                    </span>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                    <span class="text-sm font-semibold text-foreground">Notifikasi</span>
                    <span class="text-xs text-primary font-medium">3 Baru</span>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    {{-- Item Notif (Contoh Active) --}}
                    <x-dropdown-item href="#"
                        class="flex flex-col items-start gap-1 p-3 border-l-2 border-primary bg-primary/5">
                        <div class="flex justify-between w-full">
                            <span class="text-sm font-semibold text-foreground">Lisensi Habis</span>
                            <span class="text-[10px] text-muted-foreground">Baru saja</span>
                        </div>
                        <p class="text-xs text-muted-foreground line-clamp-2">Office 2019 di Lab 1 perlu renewal.</p>
                    </x-dropdown-item>
                </div>
                <div class="p-2 border-t border-border bg-muted/20">
                    <button class="w-full text-xs text-center text-muted-foreground hover:text-foreground py-1">
                        Lihat Semua
                    </button>
                </div>
            </x-slot>
        </x-dropdown>

        {{-- Dropdown Profil --}}
        <x-dropdown align="right" width="w-56">
            <x-slot name="trigger">
                <button
                    class="flex items-center gap-3 transition-colors hover:bg-accent rounded-md py-1 pr-2 pl-1 focus:outline-none focus:ring-2 focus:ring-ring">
                    <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center shadow-sm">
                        <span class="text-sm font-bold text-primary-foreground">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </span>
                    </div>
                    <div class="hidden md:flex flex-col items-start text-left">
                        <span class="text-sm font-semibold text-foreground leading-tight">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[10px] text-muted-foreground font-medium uppercase">
                            {{ auth()->user()->roles->pluck('name')->first() ?? 'User' }}
                        </span>
                    </div>
                    <i class="fa-solid fa-angle-up fa-xs text-muted-foreground transition-transform duration-300"
                        :class="{ 'rotate-180': open }"></i>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="md:hidden px-3 py-2 border-b border-border">
                    <p class="text-sm font-semibold text-foreground">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-item onclick="event.preventDefault(); this.closest('form').submit();"
                        class="text-destructive focus:bg-destructive/10 focus:text-destructive">
                        <i class="fa-solid fa-arrow-right-from-bracket mr-2 text-destructive"></i>
                        Log Out
                    </x-dropdown-item>
                </form>
            </x-slot>
        </x-dropdown>

    </div>
</header>
