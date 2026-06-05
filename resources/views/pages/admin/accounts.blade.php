<x-layout.app title="Manajemen Akun" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Manajemen Akun', 'url' => null]]">
    <div class="space-y-6">

        {{-- Header & Tombol Tambah --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Manajemen Akun</h1>
                <p class="text-muted-foreground mt-1">
                    Kelola akun pengguna, konfigurasi role, dan reset password.
                </p>
            </div>

            {{-- SHEET TAMBAH AKUN --}}
            @role('admin')
            <x-ui.sheet.sheet>
                <x-ui.sheet.trigger>
                    <x-ui.button>
                        <i class="fa-solid fa-user-plus mr-2"></i> Tambah Akun Baru
                    </x-ui.button>
                </x-ui.sheet.trigger>

                <x-ui.sheet.content side="right">
                    <x-ui.sheet.header>
                        <x-ui.sheet.title>Tambah Akun Baru</x-ui.sheet.title>
                        <x-ui.sheet.description>
                            Buat akun pengguna baru dan tentukan hak aksesnya.
                        </x-ui.sheet.description>
                    </x-ui.sheet.header>

                    <form action="{{ route('accounts.store') }}" method="POST" class="mt-6 space-y-4">
                        @csrf

                        <div class="space-y-1.5">
                            <x-form.label for="new_name">Nama Lengkap</x-form.label>
                            <x-form.input id="new_name" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required />
                        </div>

                        <div class="space-y-1.5">
                            <x-form.label for="new_email">Email</x-form.label>
                            <x-form.input id="new_email" type="email" name="email" value="{{ old('email') }}" placeholder="user@usn.ac.id" required />
                        </div>

                        <div class="space-y-1.5" x-data="{ showPw: false }">
                            <x-form.label for="new_password">Password</x-form.label>
                            <div class="relative">
                                <x-form.input id="new_password" x-bind:type="showPw ? 'text' : 'password'" name="password" required />
                                <button type="button" @click="showPw = !showPw" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                    <i class="fa-solid" :class="showPw ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5" x-data="{ showCpw: false }">
                            <x-form.label for="new_password_confirmation">Konfirmasi Password</x-form.label>
                            <div class="relative">
                                <x-form.input id="new_password_confirmation" x-bind:type="showCpw ? 'text' : 'password'" name="password_confirmation" required />
                                <button type="button" @click="showCpw = !showCpw" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                    <i class="fa-solid" :class="showCpw ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <x-form.label for="new_role">Role Hak Akses</x-form.label>
                            <select id="new_role" name="role" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="pimpinan" {{ old('role') === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                            </select>
                        </div>

                        <x-ui.sheet.footer>
                            <x-ui.button type="submit" class="w-full">
                                <i class="fa-solid fa-check mr-2"></i> Simpan Akun
                            </x-ui.button>
                        </x-ui.sheet.footer>
                    </form>
                </x-ui.sheet.content>
            </x-ui.sheet.sheet>
            @endrole
        </div>

        {{-- Menampilkan Pesan Berhasil/Gagal --}}
        @if (session('status') || $errors->any())
            <x-ui.alert.index variant="{{ (session('status') === 'success') ? 'success' : 'destructive' }}" class="mb-6">
                <x-ui.alert.title>{{ (session('status') === 'success') ? 'Berhasil' : 'Peringatan' }}</x-ui.alert.title>
                <x-ui.alert.description>
                    {{ session('message') ?? 'Ada kesalahan pada isian form Anda.' }}

                    {{-- List detail error validasi jika ada --}}
                    @if ($errors->any())
                        <ul class="mt-2 list-disc list-inside text-xs opacity-80 font-mono">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.alert.description>
            </x-ui.alert.index>
        @endif

        {{-- Pencarian & Filter --}}
        <form method="GET" action="{{ url()->current() }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col md:flex-row gap-4 items-center">
            
            <div class="w-full relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                <x-form.input name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau email..." class="pl-9 w-full" />
            </div>

            <div class="w-full md:w-48">
                <select name="role" onchange="this.form.submit()"
                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                    <option value="All">Semua Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 w-full md:w-auto">
                <x-ui.button type="submit">
                    <i class="fa-solid fa-search mr-2"></i> Cari
                </x-ui.button>
                @if(request('search') || (request('role') && request('role') !== 'All'))
                    <a href="{{ url()->current() }}">
                        <x-ui.button type="button" variant="outline" title="Reset">
                            <i class="fa-solid fa-xmark"></i>
                        </x-ui.button>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Komponen --}}
        <x-accounts.table :users="$users" />

        {{-- Pagination --}}
        <div class="mt-4 flex flex-col items-center justify-between gap-4 border-t border-border py-4 sm:flex-row">
            <div class="text-sm text-muted-foreground text-center sm:text-left">
                Menampilkan <span class="font-medium text-foreground">{{ $users->firstItem() ?? 0 }}</span> - <span
                    class="font-medium text-foreground">{{ $users->lastItem() ?? 0 }}</span> dari <span
                    class="font-medium text-foreground">{{ $users->total() }}</span> akun pengguna
            </div>
            <div>
                {{ $users->appends(request()->query())->links('vendor.pagination.shadcn') }}
            </div>
        </div>

    </div>
</x-layout.app>
