<x-layout.app title="Dashboard PJ Lab" :breadcrumbs="[
    ['name' => 'Dashboard', 'url' => route('dashboard')],
]">
    <div class="max-w-2xl mx-auto py-12 text-center space-y-4">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
            <i class="fa-solid fa-flask-vial text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-foreground">Laboratorium Belum Ditugaskan</h1>
        <p class="text-sm text-muted-foreground leading-relaxed max-w-md mx-auto">
            Akun Anda terdaftar sebagai <strong>Penanggung Jawab Laboratorium (PJ Lab)</strong>, namun saat ini belum ditugaskan ke unit laboratorium manapun.
        </p>
        <div class="p-4 rounded-lg bg-card border border-border text-xs text-muted-foreground max-w-md mx-auto text-left">
            <div class="font-semibold text-foreground mb-1 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info text-primary"></i> Langkah Selanjutnya:
            </div>
            Silakan hubungi <strong>Administrator Sistem</strong> untuk menautkan akun Anda ke laboratorium yang Anda pimpin agar dapat mengakses inventaris dan mereview laporan kepatuhan.
        </div>
    </div>
</x-layout.app>
