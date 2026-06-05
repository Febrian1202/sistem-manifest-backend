@props(['users'])

<div class="rounded-md border border-border bg-card shadow-sm">
    <x-ui.table.table>
        <x-ui.table.table-header>
            <x-ui.table.table-row>
                <x-ui.table.table-head class="w-[80px]">No</x-ui.table.table-head>
                <x-ui.table.table-head>Nama</x-ui.table.table-head>
                <x-ui.table.table-head>Email</x-ui.table.table-head>
                <x-ui.table.table-head>Role</x-ui.table.table-head>
                <x-ui.table.table-head>Tanggal Dibuat</x-ui.table.table-head>
                <x-ui.table.table-head class="text-right">Aksi</x-ui.table.table-head>
            </x-ui.table.table-row>
        </x-ui.table.table-header>

        <x-ui.table.table-body>
            @forelse($users as $index => $user)
                @php
                    $roleName = $user->getRoleNames()->first();
                    $badgeClass = match ($roleName) {
                        'admin' => 'bg-blue-500/10 text-blue-600 border-blue-200',
                        'pimpinan' => 'bg-green-500/10 text-green-600 border-green-200',
                        default => 'bg-muted text-muted-foreground border-border',
                    };
                    $roleLabel = match ($roleName) {
                        'admin' => 'Admin',
                        'pimpinan' => 'Pimpinan',
                        default => ucfirst($roleName ?? 'User'),
                    };
                    $initials = collect(explode(' ', $user->name))
                        ->map(fn($w) => strtoupper($w[0] ?? ''))
                        ->take(2)
                        ->join('');
                @endphp

                <x-ui.table.table-row>
                    {{-- 1. No --}}
                    <x-ui.table.table-cell class="font-mono text-xs text-muted-foreground">
                        {{ $users->firstItem() + $index }}
                    </x-ui.table.table-cell>

                    {{-- 2. Nama --}}
                    <x-ui.table.table-cell class="font-medium">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                {{ $initials }}
                            </div>
                            <div class="flex flex-col">
                                <span>{{ $user->name }}</span>
                                @if($user->id === auth()->id())
                                    <span class="text-[10px] text-primary font-semibold">Anda</span>
                                @endif
                            </div>
                        </div>
                    </x-ui.table.table-cell>

                    {{-- 3. Email --}}
                    <x-ui.table.table-cell class="text-xs text-muted-foreground">
                        {{ $user->email }}
                    </x-ui.table.table-cell>

                    {{-- 4. Role --}}
                    <x-ui.table.table-cell>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                            {{ $roleLabel }}
                        </span>
                    </x-ui.table.table-cell>

                    {{-- 5. Tanggal Dibuat --}}
                    <x-ui.table.table-cell class="text-xs text-muted-foreground">
                        {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                    </x-ui.table.table-cell>

                    {{-- 6. Aksi --}}
                    <x-ui.table.table-cell class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            
                            {{-- EDIT BUTTON --}}
                            <x-ui.sheet.sheet>
                                <x-ui.sheet.trigger>
                                    <x-ui.button variant="outline" size="sm" class="h-8 w-8 p-0" title="Edit Akun">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </x-ui.button>
                                </x-ui.sheet.trigger>

                                <x-ui.sheet.content side="right">
                                    <x-ui.sheet.header>
                                        <x-ui.sheet.title>Edit Akun</x-ui.sheet.title>
                                        <x-ui.sheet.description>
                                            Perbarui informasi nama, email, dan role akun.
                                        </x-ui.sheet.description>
                                    </x-ui.sheet.header>

                                    <form action="{{ route('accounts.update', $user->id) }}" method="POST" class="mt-6 space-y-4">
                                        @csrf
                                        @method('PUT')

                                        <div class="space-y-1.5">
                                            <x-form.label for="name_{{ $user->id }}">Nama Lengkap</x-form.label>
                                            <x-form.input id="name_{{ $user->id }}" name="name" value="{{ $user->name }}" required />
                                        </div>

                                        <div class="space-y-1.5">
                                            <x-form.label for="email_{{ $user->id }}">Email</x-form.label>
                                            <x-form.input id="email_{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required />
                                        </div>

                                        <div class="space-y-1.5">
                                            <x-form.label for="role_{{ $user->id }}">Role</x-form.label>
                                            @if($user->id === auth()->id())
                                                <input type="hidden" name="role" value="{{ $roleName }}" />
                                                <x-form.input value="{{ $roleLabel }}" disabled class="bg-muted text-muted-foreground" />
                                                <p class="text-[10px] text-muted-foreground italic mt-1">Anda tidak bisa mengubah role Anda sendiri.</p>
                                            @else
                                                <select id="role_{{ $user->id }}" name="role" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                                    <option value="admin" {{ $roleName === 'admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="pimpinan" {{ $roleName === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                                                </select>
                                            @endif
                                        </div>

                                        <x-ui.sheet.footer>
                                            <x-ui.button type="submit" class="w-full">
                                                <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                                            </x-ui.button>
                                        </x-ui.sheet.footer>
                                    </form>
                                </x-ui.sheet.content>
                            </x-ui.sheet.sheet>

                            {{-- RESET PASSWORD BUTTON --}}
                            <x-ui.sheet.sheet>
                                <x-ui.sheet.trigger>
                                    <x-ui.button variant="outline" size="sm" class="h-8 w-8 p-0 text-amber-600 hover:text-amber-700 hover:bg-amber-50" title="Reset Password">
                                        <i class="fa-solid fa-key text-xs"></i>
                                    </x-ui.button>
                                </x-ui.sheet.trigger>

                                <x-ui.sheet.content side="right">
                                    <x-ui.sheet.header>
                                        <x-ui.sheet.title>Reset Password</x-ui.sheet.title>
                                        <x-ui.sheet.description>
                                            Reset password untuk pengguna <strong>{{ $user->name }}</strong>.
                                        </x-ui.sheet.description>
                                    </x-ui.sheet.header>

                                    <form action="{{ route('accounts.reset-password', $user->id) }}" method="POST" class="mt-6 space-y-4">
                                        @csrf
                                        @method('PUT')

                                        <div class="space-y-1.5" x-data="{ showPw: false }">
                                            <x-form.label for="new_password_{{ $user->id }}">Password Baru</x-form.label>
                                            <div class="relative">
                                                <x-form.input id="new_password_{{ $user->id }}" x-bind:type="showPw ? 'text' : 'password'" name="password" required />
                                                <button type="button" @click="showPw = !showPw" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                                    <i class="fa-solid" :class="showPw ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="space-y-1.5" x-data="{ showCpw: false }">
                                            <x-form.label for="password_confirmation_{{ $user->id }}">Konfirmasi Password Baru</x-form.label>
                                            <div class="relative">
                                                <x-form.input id="password_confirmation_{{ $user->id }}" x-bind:type="showCpw ? 'text' : 'password'" name="password_confirmation" required />
                                                <button type="button" @click="showCpw = !showCpw" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                                    <i class="fa-solid" :class="showCpw ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <x-ui.sheet.footer>
                                            <x-ui.button type="submit" class="w-full">
                                                <i class="fa-solid fa-check mr-2"></i> Reset Password
                                            </x-ui.button>
                                        </x-ui.sheet.footer>
                                    </form>
                                </x-ui.sheet.content>
                            </x-ui.sheet.sheet>

                            {{-- DELETE BUTTON --}}
                            @if($user->id !== auth()->id())
                                <div @click.stop="$dispatch('open-dialog', 'delete-user-{{ $user->id }}')" class="inline-block">
                                    <x-ui.button type="button" variant="ghost" size="sm" class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10" title="Hapus User">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </x-ui.button>
                                </div>

                                <x-ui.dialog.confirm name="delete-user-{{ $user->id }}" title="Hapus User" maxWidth="md">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                                            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed text-left">
                                                Apakah Anda yakin ingin menghapus akun pengguna <strong>{{ $user->name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                                            </p>
                                        </div>
                                    </div>

                                    <x-slot name="footer">
                                        <x-ui.button type="button" variant="outline" x-on:click="show = false" class="w-full sm:w-auto">
                                            Batal
                                        </x-ui.button>
                                        <form action="{{ route('accounts.destroy', $user->id) }}" method="POST" class="w-full sm:w-auto">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="destructive" class="w-full">
                                                Hapus Akun
                                            </x-ui.button>
                                        </form>
                                    </x-slot>
                                </x-ui.dialog.confirm>
                            @endif

                        </div>
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @empty
                <x-ui.table.table-row>
                    <x-ui.table.table-cell colspan="6" class="text-center h-24 text-muted-foreground">
                        Tidak ada data akun pengguna.
                    </x-ui.table.table-cell>
                </x-ui.table.table-row>
            @endforelse
        </x-ui.table.table-body>
    </x-ui.table.table>
</div>
