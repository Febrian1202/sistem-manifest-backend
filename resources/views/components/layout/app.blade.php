@props(['title' => 'Dashboard', 'breadcrumbs' => []])

<!DOCTYPE html>
<html lang="id" class="pre-alpine">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }} - USN Manifest</title>
    <link rel="icon" href="{{ asset('assets/logo-usn.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Set initial state immediately to avoid FOUC
        if (localStorage.getItem('sidebarOpen') === 'false') {
            document.documentElement.classList.add('sidebar-closed');
        } else {
            document.documentElement.classList.add('sidebar-open');
        }
    </script>
    <style>
        /* Pre-Alpine styling to prevent FOUC */
        html.pre-alpine.sidebar-open aside {
            width: 16rem !important; /* w-64 */
            transform: translateX(0) !important;
        }
        html.pre-alpine.sidebar-closed aside {
            width: 4.5rem !important; /* w-18 */
        }
        @media (max-width: 767px) {
            html.pre-alpine.sidebar-closed aside {
                transform: translateX(-100%) !important;
            }
            html.pre-alpine.sidebar-open aside {
                transform: translateX(0) !important;
            }
        }
    </style>
</head>

<body class="bg-background h-screen h-dvh flex overflow-hidden font-sans antialiased text-foreground" 
      x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }"
      x-init="
          document.documentElement.classList.remove('pre-alpine');
          $watch('sidebarOpen', val => { 
              localStorage.setItem('sidebarOpen', val);
              if (val) {
                  document.documentElement.classList.remove('sidebar-closed');
                  document.documentElement.classList.add('sidebar-open');
              } else {
                  document.documentElement.classList.remove('sidebar-open');
                  document.documentElement.classList.add('sidebar-closed');
              }
          })
      ">

    <x-layout.side-bar />

    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">

        <x-layout.top-bar :breadcrumbs="$breadcrumbs" />

        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>

    </div>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 md:hidden"
        style="display: none;" x-transition.opacity>

    </div>

    {{-- Modal Ganti Password Mandiri (Global) --}}
    <x-ui.dialog.confirm name="change-password" title="Ganti Password Mandiri" maxWidth="md" theme="light">
        <form id="form-change-password" action="{{ route('account.change-password') }}" method="POST" class="space-y-4 text-left">
            @csrf
            @method('PUT')

            <div class="space-y-1.5" x-data="{ showCpw: false }">
                <x-form.label for="current_password" class="text-zinc-700">Password Saat Ini</x-form.label>
                <div class="relative">
                    <x-form.input id="current_password" x-bind:type="showCpw ? 'text' : 'password'" name="current_password" class="text-zinc-900 bg-white border-zinc-300" required />
                    <button type="button" @click="showCpw = !showCpw" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600">
                        <i class="fa-solid" :class="showCpw ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-1.5" x-data="{ showPw: false }">
                <x-form.label for="password" class="text-zinc-700">Password Baru</x-form.label>
                <div class="relative">
                    <x-form.input id="password" x-bind:type="showPw ? 'text' : 'password'" name="password" class="text-zinc-900 bg-white border-zinc-300" required />
                    <button type="button" @click="showPw = !showPw" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600">
                        <i class="fa-solid" :class="showPw ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p class="text-[10px] text-zinc-500 italic">Password minimal 8 karakter.</p>
            </div>

            <div class="space-y-1.5" x-data="{ showCnpw: false }">
                <x-form.label for="password_confirmation" class="text-zinc-700">Konfirmasi Password Baru</x-form.label>
                <div class="relative">
                    <x-form.input id="password_confirmation" x-bind:type="showCnpw ? 'text' : 'password'" name="password_confirmation" class="text-zinc-900 bg-white border-zinc-300" required />
                    <button type="button" @click="showCnpw = !showCnpw" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600">
                        <i class="fa-solid" :class="showCnpw ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>
        </form>

        <x-slot name="footer">
            <x-ui.button type="button" variant="outline" x-on:click="show = false" class="w-full sm:w-auto text-zinc-700 border-zinc-300 hover:bg-zinc-100">
                Batal
            </x-ui.button>
            <x-ui.button type="submit" form="form-change-password" class="w-full sm:w-auto">
                Ganti Password
            </x-ui.button>
        </x-slot>
    </x-ui.dialog.confirm>

</body>

</html>
