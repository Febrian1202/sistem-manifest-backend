### 4.1.3 Listing Program (Logika Utama)

Bagian ini menyajikan cuplikan kode (*listing program*) yang merepresentasikan logika utama dan alur bisnis dari Sistem Informasi Manifest Lisensi Software. Kode-kode di bawah ini menangani proses pendaftaran agen, penerimaan hasil pemindaian, hingga kalkulasi kepatuhan lisensi.

#### 1. Pendaftaran Agen (*Agent Registration*)
Fungsi ini dijalankan ketika *script* agen di komputer klien (menggunakan *PowerShell*) pertama kali dijalankan. Sistem akan memverifikasi *key* registrasi, mencatat identitas perangkat keras komputer, dan menerbitkan token akses Sanctum yang digunakan untuk autentikasi pengiriman data pemindaian berikutnya.

**`app/Http/Controllers/Api/AgentRegisterController.php`**
```php
public function register(Request $request)
{
    // 1. Verifikasi keamanan menggunakan kunci registrasi (Agent Key)
    $registrationKey = config('app.agent_registration_key');
    if ($request->header('X-Agent-Key') !== $registrationKey) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized: Invalid Registration Key',
        ], 401);
    }

    // 2. Validasi format MAC Address dan ketersediaan Hostname
    $validator = Validator::make($request->all(), [
        'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
        'hostname' => 'required|string',
    ]);
    
    // 3. Simpan atau perbarui data identitas komputer
    $computer = Computer::updateOrCreate(
        ['mac_address' => $request->mac_address],
        [
            'hostname' => $request->hostname,
            'serial_number' => $request->serial_number,
            'last_seen_at' => now(),
        ]
    );

    // 4. Hapus token lama lalu buat token otorisasi baru (Sanctum)
    $computer->tokens()->delete();
    $token = $computer->createToken('agent', ['scan:submit'])->plainTextToken;

    return response()->json([
        'status' => 'registered',
        'token' => $token,
    ], 201);
}
```

#### 2. Filtering Perangkat Lunak (*Software Filtering*)
Tidak semua perangkat lunak yang terdeteksi di OS klien akan dicatat. Sistem memiliki filter yang menyeleksi *driver*, komponen sistem Windows, dan *library* agar tidak memenuhi database. Proses ini juga secara otomatis menandai (*flag*) perangkat lunak yang diidentifikasi sebagai alat pembajakan atau aplikasi ilegal (*piracy tools*).

**`app/Services/SoftwareFilterService.php`**
```php
public function filter(array $softwareList): FilterResult
{
    $clean = [];
    $junk = [];
    $flagged = [];

    foreach ($softwareList as $soft) {
        $name = $soft['name'] ?? null;
        if (empty($name)) continue;

        // Cek Kata Kunci Pembajakan (Crack, Keygen, KMS, dsb.)
        $isPriority = false;
        foreach (self::PRIORITY_KEYWORDS as $pk) {
            if (stripos($name, $pk) !== false) {
                $isPriority = true;
                break;
            }
        }

        if ($isPriority) {
            $flagged[] = $soft;
            $clean[] = $soft;
            continue;
        }

        // Cek apakah software merupakan komponen sistem / sampah (Driver, Redistributable, dll.)
        $isJunk = false;
        foreach (self::JUNK_KEYWORDS as $jk) {
            if (stripos($name, $jk) !== false) {
                $isJunk = true;
                break;
            }
        }

        // Kelompokkan hasil deteksi
        if ($isJunk) {
            $junk[] = $soft;
        } else {
            $clean[] = $soft;
        }
    }
    return new FilterResult($clean, $junk, $flagged);
}
```

#### 3. Manajemen Katalog Software Otomatis (*Auto-cataloging*)
Logika ini memastikan agar software yang baru ditemukan dan lolos filter akan ditambahkan secara dinamis ke Master Data Katalog. Sistem juga menentukan secara otomatis kategori aplikasi (*Freeware* / *Commercial*) dan status (*Whitelist* / *Blacklist*) berdasarkan aturan prapemrosesan sistem (seperti deteksi *keyword open-source*).

**`app/Services/SoftwareCatalogService.php`**
```php
public function syncDiscoveries(Computer $computer, array $cleanSoftware, array $flaggedSoftware): void
{
    DB::transaction(function () use ($computer, $cleanSoftware, $flaggedSoftware) {
        $currentDiscoveryIds = [];
        $flaggedNames = collect($flaggedSoftware)->pluck('name')->toArray();

        foreach ($cleanSoftware as $soft) {
            $name = $soft['name'];
            $isFlagged = in_array($name, $flaggedNames);

            // Tentukan status dan kategori awal
            $autoProcessed = $isFlagged
                ? ['status' => 'Unreviewed', 'category' => 'Freeware']
                : $this->getAutoProcessedData($name);

            // Tambahkan ke katalog jika belum ada
            $catalog = SoftwareCatalog::firstOrCreate(
                ['normalized_name' => $name],
                $autoProcessed
            );

            // Blacklist otomatis jika termasuk dalam daftar aplikasi ilegal
            if ($isFlagged && $catalog->status === 'Unreviewed') {
                $catalog->update(['status' => 'Blacklist']);
            }

            // Simpan riwayat temuan software pada komputer tersebut
            $discovery = SoftwareDiscovery::updateOrCreate(
                [
                    'computer_id' => $computer->id,
                    'raw_name' => $name,
                    'version' => $soft['version'] ?? '',
                ],
                [
                    'catalog_id' => $catalog->id,
                    'vendor' => $soft['vendor'] ?? null,
                    'install_date' => $this->parseInstallDate($soft['install_date'] ?? null),
                ]
            );
            $currentDiscoveryIds[] = $discovery->id;
        }

        // Hapus perangkat lunak yang sebelumnya ada namun tidak terdeteksi lagi pada scan terbaru
        SoftwareDiscovery::where('computer_id', $computer->id)
            ->whereNotIn('id', $currentDiscoveryIds)
            ->delete();
    });
}
```

#### 4. Kalkulasi Laporan Kepatuhan Lisensi (*Compliance Generation*)
Merupakan proses bisnis inti dari sistem ini (dijalankan di latar belakang/ *queue job*). Setiap *software* yang ditemukan di komputer klien akan divalidasi silang terhadap inventaris lisensi. Logika ini akan menentukan apakah suatu instansi legal (*Berlisensi*), melewati batas *quota*, melanggar aturan (*Blacklist*), atau dalam masa tenggang kadaluarsa (*Grace Period*).

**`app/Jobs/GenerateComplianceReportJob.php`**
```php
public function handle(): void
{
    $discoveries = SoftwareDiscovery::with(['catalog', 'catalog.licenses'])
        ->where('computer_id', $this->computer->id)->get();
        
    $records = [];
    foreach ($discoveries as $discovery) {
        if (!$discovery->catalog) continue;

        $catalog = $discovery->catalog;
        $status = 'Berlisensi';
        $keterangan = 'Lisensi aktif dan valid';

        // 1. Cek apabila aplikasi berada di dalam daftar terlarang
        if ($this->isSoftwareBlocked($discovery->software_name)) {
            $status = 'Tidak Berlisensi';
            $keterangan = 'Aplikasi terlarang terdeteksi';
        }
        // 2. Cek kategori jika aplikasi adalah open-source atau freeware
        elseif ($catalog->category !== 'Commercial') {
            $status = 'Berlisensi';
            $keterangan = 'Software gratis, tidak memerlukan lisensi';
        } else {
            // 3. Aplikasi Commercial: Cek inventaris ketersediaan lisensi
            $license = $catalog->licenses->first(); 
            if (!$license) {
                $status = 'Tidak Berlisensi';
                $keterangan = 'Lisensi tidak ditemukan dalam sistem';
            } else {
                $installationCount = SoftwareDiscovery::where('catalog_id', $catalog->id)->count();
                $today = now()->startOfDay();

                // 4. Validasi Masa Berlaku dan Kuota
                if ($license->expiry_date && $license->expiry_date->isPast() && !$license->expiry_date->isToday()) {
                    $status = 'Tidak Berlisensi';
                    $keterangan = 'Lisensi telah kedaluwarsa';
                } elseif ($license->quota_limit > 0 && $installationCount > $license->quota_limit) {
                    $status = 'Tidak Berlisensi';
                    $keterangan = 'Kuota lisensi penuh';
                } elseif ($license->expiry_date && $license->expiry_date->isBetween($today, $today->copy()->addDays(30))) {
                    $status = 'Grace Period';
                    $keterangan = 'Lisensi akan segera berakhir';
                }
            }
        }

        // Rekap untuk dimasukkan ke laporan akhir (Compliance Report)
        $records[] = [
            'computer_id' => $this->computer->id,
            'software_catalog_id' => $catalog->id,
            'status' => $status,
            'keterangan' => $keterangan,
            'scanned_at' => now(),
        ];
    }

    // Simpan perhitungan baru ke dalam database
    ComplianceReport::upsert($records, ['computer_id', 'software_catalog_id'], ['status', 'keterangan', 'scanned_at']);
}
```
