<x-layout.app title="Review & Approval Laporan" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Review Laporan', 'url' => null]
]">
    <div class="space-y-6">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Review & Approval Laporan</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Tinjau draf laporan kepatuhan laboratorium dari Admin, periksa data komputer, dan berikan persetujuan atau penolakan.
                </p>
            </div>
            <div>
                <a href="{{ route('lab.inventory.index') }}">
                    <x-ui.button variant="outline">
                        <i class="fa-solid fa-desktop mr-2"></i> Inventaris Lab
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Alert Notification --}}
        @if (session('status') || session('success') || session('error') || $errors->any())
            @php
                $isSuccess = session('status') === 'success' || session('success');
                $message = session('message') ?? session('success') ?? session('error') ?? 'Ada kesalahan.';
            @endphp
            <x-ui.alert.index variant="{{ $isSuccess ? 'success' : 'destructive' }}">
                <x-ui.alert.title>{{ $isSuccess ? 'Berhasil' : 'Peringatan' }}</x-ui.alert.title>
                <x-ui.alert.description>
                    {{ $message }}
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

        @php
            $pendingApprovals = $approvals->filter(fn($a) => $a->status === 'pending');
            $historyApprovals = $approvals->filter(fn($a) => $a->status !== 'pending');
        @endphp

        {{-- 1. BAGIAN: Menunggu Review --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-foreground flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Menunggu Review Anda ({{ $pendingApprovals->count() }})
                </h2>
            </div>

            <div class="rounded-md border border-amber-200 dark:border-amber-900/50 bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row class="bg-amber-50/50 dark:bg-amber-950/20">
                            <x-ui.table.table-head>Periode</x-ui.table.table-head>
                            <x-ui.table.table-head>Tipe Laporan</x-ui.table.table-head>
                            <x-ui.table.table-head>Laboratorium</x-ui.table.table-head>
                            <x-ui.table.table-head>Tanggal Pengiriman</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Status</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-right pr-6">Aksi</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($pendingApprovals as $item)
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="font-bold text-foreground">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-regular fa-calendar text-amber-600"></i>
                                        {{ \Carbon\Carbon::createFromFormat('Y-m', $item->period)->translatedFormat('F Y') }}
                                    </div>
                                    <span class="text-xs font-mono text-muted-foreground">{{ $item->period }}</span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <span class="capitalize font-medium text-foreground">{{ $item->report_type }}</span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <div class="font-medium text-foreground">{{ $item->laboratory->name }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">{{ $item->laboratory->code }}</div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <div class="text-xs text-foreground">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="text-[11px] text-muted-foreground">{{ $item->created_at->diffForHumans() }}</div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <i class="fa-solid fa-clock"></i> Menunggu Review
                                    </span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-right pr-6">
                                    <a href="{{ route('lab.reports.show', $item) }}">
                                        <x-ui.button size="sm">
                                            <i class="fa-solid fa-pen-to-square mr-1.5"></i> Tinjau Laporan
                                        </x-ui.button>
                                    </a>
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="6" class="text-center py-6 text-muted-foreground text-sm">
                                    <i class="fa-regular fa-circle-check text-emerald-500 mr-1.5"></i> Tidak ada laporan yang sedang menunggu review Anda.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>
        </div>

        {{-- 2. BAGIAN: Riwayat Keputusan Approval --}}
        <div class="space-y-3 pt-4">
            <div>
                <h2 class="text-lg font-bold text-foreground">Riwayat Laporan</h2>
                <p class="text-xs text-muted-foreground">Laporan yang telah Anda setujui atau tolak sebelumnya.</p>
            </div>

            <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head>Periode</x-ui.table.table-head>
                            <x-ui.table.table-head>Tipe Laporan</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Status</x-ui.table.table-head>
                            <x-ui.table.table-head>Catatan PJ Lab</x-ui.table.table-head>
                            <x-ui.table.table-head>Tanggal Review</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-right pr-6">Aksi</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($historyApprovals as $item)
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="font-semibold text-foreground">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $item->period)->translatedFormat('F Y') }}
                                    <div class="text-xs font-mono text-muted-foreground">{{ $item->period }}</div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <span class="capitalize font-medium">{{ $item->report_type }}</span>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-center">
                                    @if ($item->status === 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-circle-check"></i> Disetujui
                                        </span>
                                    @elseif ($item->status === 'rejected')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                        </span>
                                    @endif
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="max-w-xs">
                                    <p class="text-xs text-foreground truncate" title="{{ $item->notes }}">
                                        {{ $item->notes ?: '-' }}
                                    </p>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell>
                                    <div class="text-xs text-foreground">{{ $item->reviewed_at ? $item->reviewed_at->format('d/m/Y H:i') : '-' }}</div>
                                    <div class="text-[11px] text-muted-foreground">{{ $item->reviewer->name ?? 'PJ Lab' }}</div>
                                </x-ui.table.table-cell>

                                <x-ui.table.table-cell class="text-right pr-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('lab.reports.show', $item) }}">
                                            <x-ui.button variant="outline" size="sm">
                                                <i class="fa-solid fa-eye mr-1.5"></i> Detail
                                            </x-ui.button>
                                        </a>
                                        <a href="{{ route('lab.reports.preview-pdf', $item) }}" target="_blank">
                                            <x-ui.button variant="outline" size="sm" title="Preview PDF">
                                                <i class="fa-solid fa-file-pdf text-red-500"></i>
                                            </x-ui.button>
                                        </a>
                                    </div>
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="6" class="text-center py-6 text-muted-foreground text-sm">
                                    Belum ada riwayat review laporan terdahulu.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>

            @if ($approvals->hasPages())
                <div class="mt-4">
                    {{ $approvals->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layout.app>
