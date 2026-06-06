<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') - USN Manifest</title>
    <link rel="icon" href="{{ asset('assets/logo-usn.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-background min-h-screen flex items-center justify-center p-6 font-sans antialiased text-foreground">
    <div class="relative max-w-md w-full bg-card border border-border rounded-2xl p-8 text-center shadow-xl transition-all duration-300 hover:shadow-2xl">
        {{-- Decorative top bar --}}
        <div class="absolute top-0 left-0 right-0 h-2 rounded-t-2xl bg-gradient-to-r from-primary to-accent"></div>

        {{-- Icon --}}
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-accent/50 text-primary mb-6 animate-pulse-slow">
            @yield('icon')
        </div>

        {{-- Error Code --}}
        <h1 class="text-7xl font-black text-primary tracking-tight mb-2">@yield('code')</h1>

        {{-- Headline --}}
        <h2 class="text-2xl font-bold text-foreground mb-4">@yield('headline')</h2>

        {{-- Description --}}
        <p class="text-muted-foreground text-sm leading-relaxed mb-8">
            @yield('message')
        </p>

        {{-- Action Button --}}
        <div class="flex flex-col gap-3 justify-center sm:flex-row">
            <a href="{{ auth()->check() ? route('dashboard') : '/' }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-primary text-primary-foreground font-semibold text-sm shadow-md hover:bg-primary/95 transition duration-200">
                <i class="fa-solid fa-house mr-2"></i> Kembali ke Beranda
            </a>
            @if(auth()->check())
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl bg-secondary text-secondary-foreground font-semibold text-sm hover:bg-secondary/80 transition duration-200">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i> Keluar
                    </button>
                </form>
            @endif
        </div>
    </div>
</body>

</html>
