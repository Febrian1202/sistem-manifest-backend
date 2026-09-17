<x-layout.app title="Kirim Laporan ke PJ Lab" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Laporan', 'url' => route('reports')],
    ['name' => 'Kirim ke PJ Lab', 'url' => null]
]">
    <div class="space-y-6">

        {{-- Header & Info --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Kirim Laporan ke PJ Lab</h1>
                <p class="text-muted-foreground mt-1">
                    Kirimkan draf laporan kepatuhan per laboratorium ke Penanggung Jawab (PJ Lab) untuk di-review dan disetujui.
                </p>
            </div>
        </div>

        {{-- Alert Notification --}}
        @if (session('status') || session('success') || session('error') || $errors->any())
            @php
                $isSuccess = session('status') === 'success' || session('success');
                $message = session('message') ?? session('success') ?? session('error') ?? 'Ada kesalahan pada permintaan Anda.';
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

        {{-- Filter Periode --}}
        <form method="GET" action="{{ route('report-submissions.index') }}"
            class="bg-card border border-border p-4 rounded-lg shadow-sm flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <label for="period" class="text-sm font-medium text-foreground whitespace-nowrap">
                    <i class="fa-regular fa-calendar mr-1.5 text-muted-foreground"></i> Periode Laporan:
                </label>
                <input type="month" id="period" name="period" value="{{ $period }}"
                    class="rounded-md border border-input bg-background px-3 py-1.5 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    onchange="this.form.submit()">
            </div>
            <div class="text-xs text-muted-foreground">
                Menampilkan data kepatuhan untuk periode: <span class="font-semibold text-foreground">{{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</span>
            </div>
        </form>

        {{-- Tabel Laboratorium --}}
        <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
            <x-ui.table.table>
                <x-ui.table.table-header>
                    <x-ui.table.table-row>
                        <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                        <x-ui.table.table-head>Laboratorium</x-ui.table.table-head>
                        <x-ui.table.table-head>PJ Lab Terdaftar</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Total Unit</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Ter-scan Periode Ini</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-center">Status Laporan</x-ui.table.table-head>
                        <x-ui.table.table-head class="text-right pr-6">Aksi</x-ui.table.table-head>
                    </x-ui.table.table-row>
                </x-ui.table.table-header>
                <x-ui.table.table-body>
                    @forelse ($laboratories as $index => $lab)
                        @php
                            $latestApproval = $lab->reportApprovals->first();
                            $status = $latestApproval?->status;
                            $total = $lab->computers_count;
                            $scanned = $lab->scanned_computers_count;
                            $isComplete = $total > 0 && $scanned >= $total;
                            $pj = $lab->penanggungJawab->first();
                        @endphp
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell class="text-center font-medium">
                                {{ $index + 1 }}
                            </x-ui.table.table-cell>
                            
                            <x-ui.table.table-cell>
                                <div class="font-semibold text-foreground">{{ $lab->name }}</div>
                                <div class="text-xs text-muted-foreground font-mono">{{ $lab->code }} &bull; {{ $lab->building ?? 'Gedung -' }} Lt. {{ $lab->floor ?? '-' }}</div>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell>
                                @if($pj)
                                    <div class="text-sm font-medium text-foreground">{{ $pj->name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $pj->email }}</div>
                                @else
                                    <span class="text-xs text-destructive italic">Belum ada PJ Lab</span>
                                @endif
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-center">
                                <span class="font-medium">{{ $total }}</span>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $isComplete ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                                    <i class="fa-solid {{ $isComplete ? 'fa-check-circle text-green-600' : 'fa-triangle-exclamation text-yellow-600' }}"></i>
                                    {{ $scanned }}/{{ $total }}
                                </span>
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-center">
                                @if($status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <i class="fa-solid fa-clock"></i> Menunggu Review
                                    </span>
                                @elseif($status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i> Disetujui
                                    </span>
                                @elseif($status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                        <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-secondary text-secondary-foreground">
                                        Belum Dikirim
                                    </span>
                                @endif
                            </x-ui.table.table-cell>

                            <x-ui.table.table-cell class="text-right pr-6">
                                <form method="POST" action="{{ route('report-submissions.submit') }}" class="inline-block"
                                    onsubmit="return {{ !$isComplete ? "confirm('Sebagian komputer di laboratorium ini belum ter-scan pada periode ini. Tetap kirim ke PJ Lab?')" : 'true' }}">
                                    @csrf
                                    <input type="hidden" name="laboratory_id" value="{{ $lab->id }}">
                                    <input type="hidden" name="period" value="{{ $period }}">

                                    @if($status === 'pending')
                                        <x-ui.button type="button" variant="outline" size="sm" disabled class="opacity-50 cursor-not-allowed">
                                            <i class="fa-solid fa-hourglass-half mr-1.5"></i> Pending
                                        </x-ui.button>
                                    @elseif($status === 'approved' || $status === 'rejected')
                                        <x-ui.button type="submit" variant="outline" size="sm">
                                            <i class="fa-solid fa-rotate-right mr-1.5"></i> Kirim Ulang
                                        </x-ui.button>
                                    @else
                                        <x-ui.button type="submit" size="sm">
                                            <i class="fa-solid fa-paper-plane mr-1.5"></i> Kirim
                                        </x-ui.button>
                                    @endif
                                </form>
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @empty
                        <x-ui.table.table-row>
                            <x-ui.table.table-cell colspan="7" class="text-center py-8 text-muted-foreground">
                                Tidak ada data laboratorium ditemukan.
                            </x-ui.table.table-cell>
                        </x-ui.table.table-row>
                    @endforelse
                </x-ui.table.table-body>
            </x-ui.table.table>
        </div>

        {{-- Keterangan Status --}}
        <div class="bg-muted/40 border border-border p-4 rounded-lg text-xs space-y-2 text-muted-foreground">
            <div class="font-semibold text-foreground text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-primary"></i> Panduan Status Pengiriman
            </div>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                <li><strong class="text-foreground">Belum Dikirim:</strong> Laporan kepatuhan belum pernah diserahkan ke PJ Lab untuk periode terkait.</li>
                <li><strong class="text-foreground">Menunggu Review:</strong> Laporan sedang dalam proses evaluasi oleh PJ Lab yang bersangkutan.</li>
                <li><strong class="text-foreground">Disetujui:</strong> PJ Lab telah memvalidasi keakuratan data hasil scan inventaris.</li>
                <li><strong class="text-foreground">Ditolak:</strong> PJ Lab menemukan ketidaksesuaian data. Admin dapat melakukan scan ulang dan mengirim ulang.</li>
            </ul>
        </div>

    </div>
</x-layout.app>
