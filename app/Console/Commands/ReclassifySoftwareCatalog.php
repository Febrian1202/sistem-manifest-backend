<?php

namespace App\Console\Commands;

use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Services\SoftwareCatalogService;
use App\Services\SoftwareFilterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ReclassifySoftwareCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'software:reclassify 
                            {--dry-run : Simulasi pembersihan dan reklasifikasi tanpa mengubah database} 
                            {--force : Reklasifikasi semua software termasuk yang sudah memiliki status selain Unreviewed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan katalog software sampah dan re-klasifikasi software yang belum diverifikasi berdasarkan whitelist/aturan katalog';

    public function __construct(
        public SoftwareFilterService $filterService,
        public SoftwareCatalogService $catalogService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $this->info($isDryRun ? '--- Menjalankan Reklasifikasi Software dalam Mode Simulasi (DRY-RUN) ---' : '--- Memulai Reklasifikasi Software ---');

        $catalogs = SoftwareCatalog::with('licenses')->get();
        $total = $catalogs->count();

        $prunedJunkCount = 0;
        $reclassifiedCount = 0;
        $untouchedCount = 0;

        foreach ($catalogs as $catalog) {
            $name = $catalog->normalized_name;

            // 1. Cek apakah entri merupakan junk / komponen sistem
            $filterResult = $this->filterService->filter([['name' => $name]]);

            if (! empty($filterResult->junk)) {
                // Keamanan: Jangan hapus jika katalog terikat dengan lisensi resmi
                if ($catalog->licenses->isNotEmpty()) {
                    $this->warn("[LEWATI SAMPAH] {$name} terindikasi junk namun memiliki relasi lisensi terdaftar.");
                    $untouchedCount++;

                    continue;
                }

                if ($isDryRun) {
                    $this->line("[DRY-RUN] Akan menghapus katalog sampah: {$name}");
                } else {
                    SoftwareDiscovery::where('catalog_id', $catalog->id)->delete();
                    $catalog->delete();
                }

                $prunedJunkCount++;

                continue;
            }

            // 2. Cek apakah memenuhi syarat auto-klasifikasi
            if ($catalog->status === 'Unreviewed' || $isForce) {
                $auto = $this->catalogService->getAutoProcessedData($name);

                if ($auto['status'] === 'Whitelist') {
                    if ($catalog->category !== $auto['category'] || $catalog->status !== $auto['status']) {
                        if ($isDryRun) {
                            $this->line("[DRY-RUN] Akan mereklasifikasi: {$name} -> Kategori: {$auto['category']}, Status: {$auto['status']}");
                        } else {
                            $catalog->update([
                                'category' => $auto['category'],
                                'status' => $auto['status'],
                            ]);
                        }

                        $reclassifiedCount++;

                        continue;
                    }
                }
            }

            $untouchedCount++;
        }

        // 3. Hapus cache dashboard jika ada data yang berubah dan bukan dry-run
        if (! $isDryRun && ($prunedJunkCount > 0 || $reclassifiedCount > 0)) {
            Cache::forget('dashboard.stats.'.now()->format('Y-m'));
            Cache::forget('dashboard.stats.'.now()->subMonth()->format('Y-m'));
            Cache::forget('dashboard.charts');
            Cache::forget('compliance.global_stats');
            $this->info('Cache dashboard berhasil diperbarui.');
        }

        $this->newLine();
        $this->info('Ringkasan Reklasifikasi:');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Software Diperiksa', $total],
                ['Katalog sampah dibersihkan', $prunedJunkCount],
                ['Katalog berhasil direklasifikasi', $reclassifiedCount],
                ['Katalog tidak berubah', $untouchedCount],
            ]
        );

        return self::SUCCESS;
    }
}
