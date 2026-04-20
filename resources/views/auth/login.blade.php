<x-auth.layout.app>
    <div class="min-h-screen w-full grid grid-cols-1 lg:grid-cols-2">

        {{-- LEFT — Brand / Showcase --}}
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-slate-950 p-12 text-slate-100">
            {{-- Grid pattern --}}
            <div class="absolute inset-0 opacity-[0.18]"
                style="background-image: linear-gradient(to right, rgb(148 163 184 / 0.15) 1px, transparent 1px), linear-gradient(to bottom, rgb(148 163 184 / 0.15) 1px, transparent 1px); background-size: 44px 44px;">
            </div>
            {{-- Radial glow --}}
            <div class="absolute -top-40 -left-40 h-120 w-120 rounded-full bg-primary/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-20 h-105 w-105 rounded-full bg-blue-500/10 blur-3xl"></div>

            {{-- Top: Logo --}}
            <div class="relative z-10 flex items-center gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15 backdrop-blur">
                    <i class="fa-solid fa-graduation-cap text-white text-xl"></i>
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold tracking-wide text-white">USN Kolaka</span>
                    <span class="text-xs text-slate-400">IT Asset Management</span>
                </div>
            </div>

            {{-- Middle: Headline --}}
            <div class="relative z-10 max-w-lg space-y-5">
                <h1 class="text-5xl font-bold tracking-tight text-white">
                    USN Manifest
                </h1>
                <p class="text-lg leading-relaxed text-slate-300">
                    Sistem Informasi Manifest Lisensi Software untuk Mencegah Pelanggaran Hak Cipta
                </p>
                <div class="h-px w-24 bg-linear-to-r from-primary to-transparent"></div>
                <p class="text-sm text-slate-400">
                    Kelola aset, lisensi, dan kepatuhan perangkat lunak di seluruh unit kerja universitas dalam satu
                    platform terpadu.
                </p>
            </div>

            {{-- Bottom: Trust badge --}}
            <div
                class="relative z-10 flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 px-4 py-3 backdrop-blur w-fit">
                <i class="fa-solid fa-shield-check text-emerald-400 text-xl"></i>
                <div class="flex flex-col">
                    <span class="text-xs font-medium text-white">Compliance Verified</span>
                    <span class="text-[11px] text-slate-400">Sesuai standar audit lisensi nasional</span>
                </div>
            </div>
        </div>

        {{-- RIGHT — Form --}}
        <div class="flex flex-col items-center justify-center px-6 py-12 sm:px-12 lg:px-16 relative">

            <div class="w-full max-w-md space-y-8">

                {{-- Mobile logo --}}
                <div class="flex items-center gap-3 lg:hidden">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <i class="fa-solid fa-graduation-cap text-lg"></i>
                    </div>
                    <span class="text-base font-semibold text-foreground">USN Manifest</span>
                </div>

                {{-- Header --}}
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">
                        Selamat Datang
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Silakan masukkan kredensial Anda untuk mengakses dashboard.
                    </p>
                </div>

                {{-- Error Status Session --}}
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <x-ui.card class="p-4 bg-destructive/5 border-destructive/20">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-circle-exclamation text-destructive mt-0.5"></i>
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-destructive">Ups! Terjadi kesalahan.</p>
                                <ul class="text-xs text-destructive/90 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5" 
                    x-data="{ showPassword: false, loading: false }"
                    @submit="loading = true">
                    @csrf

                    {{-- Email Input --}}
                    <div class="space-y-2">
                        <x-form.label for="email">Email</x-form.label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm z-10"></i>
                            <x-form.input id="email" type="email" name="email" placeholder="nama@usn.ac.id"
                                value="{{ old('email') }}" required autofocus autocomplete="username"
                                class="pl-10" />
                        </div>
                        @error('email')
                            <p class="text-xs text-destructive font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Input --}}
                    <div class="space-y-2">
                        <x-form.label for="password">Password</x-form.label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm z-10"></i>
                            <x-form.input id="password" ::type="showPassword ? 'text' : 'password'" name="password"
                                placeholder="••••••••" required autocomplete="current-password"
                                class="pl-10 pr-10" />

                            {{-- Toggle Button (Alpine) --}}
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none rounded z-10">
                                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-destructive font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Extras: Remember Me --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="remember_me" name="remember"
                                class="peer h-4 w-4 shrink-0 rounded-sm border border-primary shadow focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 accent-primary" />
                            <x-form.label for="remember_me" class="cursor-pointer">
                                Ingat saya
                            </x-form.label>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <x-ui.button type="submit" class="w-full h-11" ::disabled="loading">
                        <template x-if="!loading">
                            <span class="flex items-center gap-2">
                                Masuk
                                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch animate-spin"></i>
                                Memproses...
                            </span>
                        </template>
                    </x-ui.button>
                </form>

                {{-- Footer --}}
                <div class="pt-4 border-t border-border mt-8">
                    <p class="text-center text-xs text-muted-foreground">
                        IT Support Team — Universitas Sembilanbelas November Kolaka
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-auth.layout.app>