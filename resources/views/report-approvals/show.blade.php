<x-layout.app title="Tinjau Laporan — {{ $lab->name }}" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
    ['name' => 'Review Laporan', 'url' => route('lab.reports.index')],
    ['name' => 'Periode ' . $period, 'url' => null]
]">
    <div class="space-y-6 pb-12">

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                        <i class="fa-solid fa-flask"></i> {{ $lab->name }} ({{ $lab->code }})
                    </span>
                    <span class="text-xs text-muted-foreground">&bull;</span>
                    <span class="text-xs font-semibold text-foreground">
                        Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-foreground">Review Laporan Kepatuhan Perangkat Lunak</h1>
                <p class="text-muted-foreground mt-0.5 text-sm">
                    Periksa temuan dan keabsahan inventaris sebelum menyetujui atau menolak laporan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('lab.reports.index') }}">
                    <x-ui.button variant="outline">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                    </x-ui.button>
                </a>
                <a href="{{ route('lab.reports.preview-pdf', $reportApproval) }}" target="_blank">
                    <x-ui.button variant="outline" class="border-red-500/30 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20">
                        <i class="fa-solid fa-file-pdf mr-2 text-red-500"></i> Preview PDF
                    </x-ui.button>
                </a>
            </div>
        </div>

        {{-- Status Notification Bar --}}
        @if ($reportApproval->status === 'approved')
            <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5"></i>
                <div class="space-y-1 text-sm">
                    <div class="font-bold text-emerald-900 dark:text-emerald-300">Laporan Telah Disetujui</div>
                    <div class="text-emerald-800 dark:text-emerald-400 text-xs">
                        Disetujui oleh <strong>{{ $reportApproval->reviewer->name ?? 'PJ Lab' }}</strong> pada {{ $reportApproval->reviewed_at?->format('d/m/Y H:i') }}.
                    </div>
                    @if($reportApproval->notes)
                        <div class="mt-2 p-2 rounded bg-emerald-100/60 dark:bg-emerald-900/50 text-emerald-900 dark:text-emerald-200 text-xs">
                            <strong>Catatan:</strong> {{ $reportApproval->notes }}
                        </div>
                    @endif
                </div>
            </div>
        @elseif ($reportApproval->status === 'rejected')
            <div class="p-4 rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 flex items-start gap-3">
                <i class="fa-solid fa-circle-xmark text-rose-600 text-lg mt-0.5"></i>
                <div class="space-y-1 text-sm">
                    <div class="font-bold text-rose-900 dark:text-rose-300">Laporan Telah Ditolak</div>
                    <div class="text-rose-800 dark:text-rose-400 text-xs">
                        Ditolak oleh <strong>{{ $reportApproval->reviewer->name ?? 'PJ Lab' }}</strong> pada {{ $reportApproval->reviewed_at?->format('d/m/Y H:i') }}.
                    </div>
                    @if($reportApproval->notes)
                        <div class="mt-2 p-2 rounded bg-rose-100/60 dark:bg-rose-900/50 text-rose-900 dark:text-rose-200 text-xs">
                            <strong>Alasan Penolakan:</strong> {{ $reportApproval->notes }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-clock text-amber-600 text-lg"></i>
                    <div>
                        <div class="font-bold text-amber-900 dark:text-amber-300 text-sm">Menunggu Keputusan PJ Lab</div>
                        <div class="text-amber-800 dark:text-amber-400 text-xs">
                            Silakan periksa rekap kepatuhan dan daftar temuan di bawah ini sebelum mengambil keputusan.
                        </div>
                    </div>
                </div>
                <span class="px-3 py-1 text-xs font-bold rounded-full bg-amber-200/80 text-amber-900 dark:bg-amber-900 dark:text-amber-200">
                    Status: PENDING
                </span>
            </div>
        @endif

        {{-- BAGIAN A: Ringkasan Kepatuhan --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <span class="text-xs font-semibold text-muted-foreground uppercase">Total Komputer</span>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $stats['total_computers'] }} <span class="text-xs font-normal text-muted-foreground">unit</span></div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <span class="text-xs font-semibold text-muted-foreground uppercase">Scan Periode Ini</span>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $stats['scanned'] }} <span class="text-xs font-normal text-muted-foreground">/ {{ $stats['total_computers'] }} unit</span></div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <span class="text-xs font-semibold text-muted-foreground uppercase">OS Berlisensi</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['compliant'] }} <span class="text-xs font-normal text-muted-foreground">unit</span></div>
            </div>

            <div class="bg-card border border-border p-5 rounded-lg shadow-sm">
                <span class="text-xs font-semibold text-muted-foreground uppercase">Tingkat Kepatuhan OS</span>
                <div class="mt-2 text-2xl font-bold text-foreground">{{ $stats['compliance_rate'] }}%</div>
            </div>
        </div>

        {{-- BAGIAN B: Daftar Temuan Masalah & Pelanggaran --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-foreground flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                        Temuan Audit Kepatuhan ({{ $violations->count() }})
                    </h2>
                    <p class="text-xs text-muted-foreground">Daftar perangkat lunak berbayar tanpa lisensi atau software terlarang (blacklist).</p>
                </div>
            </div>

            <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                            <x-ui.table.table-head>Komputer</x-ui.table.table-head>
                            <x-ui.table.table-head>Software Terdeteksi</x-ui.table.table-head>
                            <x-ui.table.table-head>Versi</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Status Temuan</x-ui.table.table-head>
                            <x-ui.table.table-head>Keterangan</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($violations as $index => $violation)
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="text-center font-medium">{{ $index + 1 }}</x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    <div class="font-semibold text-foreground">{{ $violation->computer->hostname ?? '-' }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">{{ $violation->computer->ip_address ?? '' }}</div>
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="font-medium text-foreground">
                                    {{ $violation->software_name }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="text-xs text-muted-foreground font-mono">
                                    {{ $violation->software_version ?? '-' }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                        {{ $violation->status }}
                                    </span>
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                    {{ $violation->keterangan ?: 'Tidak ada lisensi valid terpasang.' }}
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="6" class="text-center py-6 text-muted-foreground text-sm">
                                    <i class="fa-solid fa-check-circle text-emerald-500 mr-1.5"></i> Tidak ditemukan pelanggaran lisensi di laboratorium ini.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>
        </div>

        {{-- BAGIAN C: Daftar Seluruh Komputer di Lab --}}
        <div class="space-y-3">
            <h2 class="text-lg font-bold text-foreground">Daftar Komputer Lab ({{ $computers->count() }})</h2>

            <div class="rounded-md border border-border bg-card shadow-sm overflow-hidden">
                <x-ui.table.table>
                    <x-ui.table.table-header>
                        <x-ui.table.table-row>
                            <x-ui.table.table-head class="w-[50px] text-center">No</x-ui.table.table-head>
                            <x-ui.table.table-head>Hostname</x-ui.table.table-head>
                            <x-ui.table.table-head>Sistem Operasi</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Status OS</x-ui.table.table-head>
                            <x-ui.table.table-head class="text-center">Total Software</x-ui.table.table-head>
                            <x-ui.table.table-head>Terakhir Scan</x-ui.table.table-head>
                        </x-ui.table.table-row>
                    </x-ui.table.table-header>
                    <x-ui.table.table-body>
                        @forelse ($computers as $index => $comp)
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell class="text-center font-medium">{{ $index + 1 }}</x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    <div class="font-semibold text-foreground">{{ $comp->hostname }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">{{ $comp->ip_address ?? 'No IP' }}</div>
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell>
                                    <div class="text-sm font-medium">{{ $comp->os_name ?? '-' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $comp->os_version }}</div>
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $comp->os_license_status === 'Licensed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                        {{ $comp->os_license_status ?? 'Unknown' }}
                                    </span>
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="text-center text-xs font-medium">
                                    {{ $comp->softwares->count() }}
                                </x-ui.table.table-cell>
                                <x-ui.table.table-cell class="text-xs text-muted-foreground">
                                    {{ $comp->last_seen_at ? $comp->last_seen_at->format('d/m/Y H:i') : '-' }}
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @empty
                            <x-ui.table.table-row>
                                <x-ui.table.table-cell colspan="6" class="text-center py-6 text-muted-foreground">
                                    Tidak ada komputer terdaftar di laboratorium ini.
                                </x-ui.table.table-cell>
                            </x-ui.table.table-row>
                        @endforelse
                    </x-ui.table.table-body>
                </x-ui.table.table>
            </div>
        </div>

        {{-- BAGIAN D: Form Keputusan PJ Lab --}}
        @if ($reportApproval->status === 'pending')
            <div class="bg-card border border-border p-6 rounded-lg shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-foreground">Form Keputusan PJ Lab</h2>
                    <p class="text-xs text-muted-foreground">
                        Berikan persetujuan jika data hasil scan telah sesuai dengan kondisi riil di laboratorium, atau tolak dengan menyertakan alasan.
                    </p>
                </div>

                <form method="POST" id="approval-form" class="space-y-4">
                    @csrf
                    <div>
                        <x-form.label for="notes">Catatan Tambahan (Opsional saat Approve, Wajib saat Reject):</x-form.label>
                        <textarea id="notes" name="notes" rows="4"
                            class="w-full mt-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Tuliskan catatan verifikasi atau alasan penolakan jika data tidak sesuai...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
                        <x-ui.button type="submit"
                            formaction="{{ route('lab.reports.reject', $reportApproval) }}"
                            variant="destructive"
                            onclick="return confirm('Apakah Anda yakin ingin menolak laporan ini? Pastikan Anda telah mengisi alasan penolakan pada catatan.');">
                            <i class="fa-solid fa-xmark mr-2"></i> Tolak Laporan
                        </x-ui.button>

                        <x-ui.button type="submit"
                            formaction="{{ route('lab.reports.approve', $reportApproval) }}"
                            onclick="return confirm('Apakah Anda yakin menyetujui laporan kepatuhan laboratorium ini? Keputusan ini bersifat final.');">
                            <i class="fa-solid fa-check mr-2"></i> Setujui Laporan
                        </x-ui.button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</x-layout.app>
