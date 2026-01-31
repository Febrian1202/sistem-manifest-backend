@props(['title' => 'Dashboard', 'breadcrumbs' => []])

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }} - USN Manifest</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.5/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-background h-screen flex overflow-hidden font-sans antialiased text-foreground" x-data="{ sidebarOpen: false }">

    <x-layout.side-bar />

    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">

        <x-layout.top-bar :breadcrumbs="$breadcrumbs" />

        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>

    </div>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-20 md:hidden"
        style="display: none;" x-trasition.opacity>

    </div>

</body>

</html>
