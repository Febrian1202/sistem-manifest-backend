@props(['breadcrumbs' => []])

<header
    class="relative z-30 h-16 bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/60 border-b border-border flex items-center justify-between px-6">

    {{-- Kiri: Toggle & Breadcrumb --}}
    <div class="flex items-center gap-4">

        {{-- Mobile Sidebar Toggle --}}
        <button @click="sidebarOpen = !sidebarOpen"
            class="md:hidden p-2 -ml-2 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>

        {{-- Breadcrumb --}}
        <nav class="flex items-center text-sm font-medium">
            @if (empty($breadcrumbs))
                <span class="text-foreground font-bold text-lg">Dashboard</span>
            @else
                <ol class="flex items-center">
                    @foreach ($breadcrumbs as $item)
                        <li class="{{ $loop->last ? 'flex' : 'hidden md:flex' }} items-center">
                            @if (!$loop->first)
                                <i class="fa-solid fa-chevron-right text-muted-foreground text-xs mx-2 {{ $loop->last ? 'hidden md:block' : '' }}"></i>
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
                        </li>
                    @endforeach
                </ol>
            @endif
        </nav>
    </div>

    {{-- Kanan: Search & Menu --}}
    <div class="flex items-center gap-4">

        {{-- Dropdown Notifikasi (Hidden until implemented) --}}
        {{-- 
        <x-dropdown align="right" width="w-80">
            ...
        </x-dropdown>
        --}}

        {{-- Dropdown Profil --}}
        <x-dropdown align="right" width="w-56">
            <x-slot name="trigger">
                <button
                    class="flex items-center gap-3 transition-colors hover:bg-accent rounded-md py-1 pr-2 pl-1 focus:outline-none focus:ring-2 focus:ring-ring">
                    <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center shadow-sm">
                        <span class="text-sm font-bold text-primary-foreground">
                            {{ collect(explode(' ', auth()->user()->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('') }}
                        </span>
                    </div>
                    <div class="hidden md:flex flex-col items-start text-left">
                        <span class="text-sm font-semibold text-foreground leading-tight">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[10px] text-muted-foreground font-medium uppercase">
                            {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
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
                <div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                    Akun Saya
                </div>
                
                <x-dropdown-item href="#" @click.prevent="$dispatch('open-dialog', 'change-password')">
                    <i class="fa-solid fa-key mr-2 text-muted-foreground text-xs"></i>
                    Ganti Password
                </x-dropdown-item>

                <form method="POST" action="{{ route('logout') }}" class="border-t border-border mt-1 pt-1">
                    @csrf
                    <x-dropdown-item onclick="event.preventDefault(); this.closest('form').submit();"
                        class="text-destructive focus:bg-destructive/10 focus:text-destructive">
                        <i class="fa-solid fa-arrow-right-from-bracket mr-2 text-xs"></i>
                        Keluar
                    </x-dropdown-item>
                </form>
            </x-slot>
        </x-dropdown>

    </div>
</header>
