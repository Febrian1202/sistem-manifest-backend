# Phase 8 — Testing & Dokumentasi

**Prioritas:** P0-P1 (core test P0, extended test P1)
**Estimasi:** 2-3 hari kerja
**Depends on:** Phase 1-7 (testing bertahap, final run setelah semua selesai)
**Referensi dokumen:** Bagian 29, 30, 37

---

## Konteks

Testing harus membuktikan bahwa scope baru berfungsi:
- Multi-lab (pemisahan data)
- Periodic monitoring (histori scan)
- Historical evidence (data tidak hilang)

Framework test: **Pest** (bukan PHPUnit langsung)
Database test: **SQLite :memory:**
Test runner: `php artisan test` atau `vendor/bin/pest`

### Test yang Sudah Ada

Cek dulu test yang sudah ada di `tests/Feature/` dan `tests/Unit/` sebelum membuat yang baru. Jangan duplikasi test existing.

---

## Task

### 8.1 Test Multi-Lab (Pemisahan Data)

**File:** `tests/Feature/MultiLabScopeTest.php`

**Perintah:**
```bash
php artisan make:test MultiLabScopeTest --pest --no-interaction
```

**Skenario:**

```php
test('kepala_lab hanya melihat komputer dari lab-nya', function () {
    // Arrange
    $labA = Laboratory::factory()->create(['name' => 'Lab FTI']);
    $labB = Laboratory::factory()->create(['name' => 'Lab FKIP']);
    $userA = User::factory()->create(['laboratory_id' => $labA->id]);
    $userA->assignRole('kepala_lab');
    $computerA = Computer::factory()->create(['laboratory_id' => $labA->id]);
    $computerB = Computer::factory()->create(['laboratory_id' => $labB->id]);

    // Act
    $this->actingAs($userA);
    $response = $this->get(route('computers.index'));

    // Assert
    $response->assertSee($computerA->hostname);
    $response->assertDontSee($computerB->hostname);
});

test('admin melihat komputer dari semua lab', function () {
    // ...
});
```

**Skenario yang harus dicakup:**
- [ ] PJ Lab FTI hanya melihat data Lab FTI
- [ ] PJ Lab FKIP hanya melihat data Lab FKIP
- [ ] Admin melihat data lintas lab
- [ ] Pimpinan melihat data lintas lab (read-only)

---

### 8.2 Test Role Scope (Akses Terbatas)

**File:** `tests/Feature/RoleScopeTest.php`

**Skenario:**

```php
test('kepala_lab tidak bisa mengakses halaman admin', function () {
    $user = User::factory()->create();
    $user->assignRole('kepala_lab');

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertForbidden(); // atau assertRedirect
});

test('pimpinan tidak bisa melakukan mutasi data', function () {
    // ...
});

test('kepala_lab hanya bisa approve report lab-nya', function () {
    // ...
});
```

---

### 8.3 Test Periodic Scan (Histori Tersimpan)

**File:** `tests/Feature/PeriodicScanTest.php`

**Skenario:**

```php
test('tiga scan berurutan menghasilkan tiga scan session', function () {
    // Arrange
    $computer = Computer::factory()->create();
    $token = $computer->createToken('agent')->plainTextToken;

    // Act: kirim 3 scan dengan UUID berbeda
    for ($i = 1; $i <= 3; $i++) {
        $response = $this->withToken($token)
            ->postJson('/api/scan-result', [
                'scan_uuid' => fake()->uuid(),
                'scan_mode' => 'scheduled',
                'software' => [
                    ['name' => 'Chrome', 'version' => "14{$i}", 'vendor' => 'Google'],
                ],
            ]);
        $response->assertSuccessful();
    }

    // Assert
    expect(ScanSession::where('computer_id', $computer->id)->count())->toBe(3);
    expect(ScanSoftwareResult::count())->toBe(3);
});
```

---

### 8.4 Test Software Added (Software Baru Terdeteksi)

**File:** `tests/Feature/SoftwareChangeTest.php`

**Skenario:**

```php
test('software baru terdeteksi di scan kedua tersimpan di histori', function () {
    $computer = Computer::factory()->create();
    $token = $computer->createToken('agent')->plainTextToken;

    // Scan 1: hanya Chrome
    $this->withToken($token)->postJson('/api/scan-result', [
        'scan_uuid' => fake()->uuid(),
        'software' => [
            ['name' => 'Chrome', 'version' => '140', 'vendor' => 'Google'],
        ],
    ]);

    // Scan 2: Chrome + Office (software baru)
    $this->withToken($token)->postJson('/api/scan-result', [
        'scan_uuid' => fake()->uuid(),
        'software' => [
            ['name' => 'Chrome', 'version' => '140', 'vendor' => 'Google'],
            ['name' => 'Office', 'version' => '2021', 'vendor' => 'Microsoft'],
        ],
    ]);

    // Assert: scan 1 punya 1 software, scan 2 punya 2
    $sessions = ScanSession::orderBy('id')->get();
    expect($sessions)->toHaveCount(2);
    expect($sessions[0]->softwareResults)->toHaveCount(1);
    expect($sessions[1]->softwareResults)->toHaveCount(2);
});
```

---

### 8.5 Test Software Removed (Software Hilang, Histori Tetap Ada)

**Skenario:**

```php
test('software yang hilang di scan berikutnya tetap ada di histori', function () {
    $computer = Computer::factory()->create();
    $token = $computer->createToken('agent')->plainTextToken;

    // Scan 1: Chrome + Office
    $this->withToken($token)->postJson('/api/scan-result', [
        'scan_uuid' => fake()->uuid(),
        'software' => [
            ['name' => 'Chrome', 'version' => '140', 'vendor' => 'Google'],
            ['name' => 'Office', 'version' => '2021', 'vendor' => 'Microsoft'],
        ],
    ]);

    // Scan 2: hanya Chrome (Office hilang)
    $this->withToken($token)->postJson('/api/scan-result', [
        'scan_uuid' => fake()->uuid(),
        'software' => [
            ['name' => 'Chrome', 'version' => '140', 'vendor' => 'Google'],
        ],
    ]);

    // Assert: kedua scan masih ada di database
    $sessions = ScanSession::orderBy('id')->get();
    expect($sessions[0]->softwareResults)->toHaveCount(2); // Office masih ada di scan 1
    expect($sessions[1]->softwareResults)->toHaveCount(1);

    // Verify Office masih bisa ditemukan di histori
    expect(ScanSoftwareResult::where('raw_name', 'Office')->count())->toBe(1);
});
```

---

### 8.6 Test Version Change (Perubahan Versi)

**Skenario:**

```php
test('perubahan versi software tercatat di histori', function () {
    $computer = Computer::factory()->create();
    $token = $computer->createToken('agent')->plainTextToken;

    // Scan 1: Chrome v140
    $this->withToken($token)->postJson('/api/scan-result', [
        'scan_uuid' => fake()->uuid(),
        'software' => [
            ['name' => 'Chrome', 'version' => '140', 'vendor' => 'Google'],
        ],
    ]);

    // Scan 2: Chrome v141 (upgrade)
    $this->withToken($token)->postJson('/api/scan-result', [
        'scan_uuid' => fake()->uuid(),
        'software' => [
            ['name' => 'Chrome', 'version' => '141', 'vendor' => 'Google'],
        ],
    ]);

    // Assert: kedua versi tercatat
    $results = ScanSoftwareResult::where('raw_name', 'Chrome')
        ->orderBy('id')
        ->pluck('version')
        ->toArray();

    expect($results)->toBe(['140', '141']);
});
```

---

### 8.7 Test Compliance Change (Perubahan Status Lisensi)

**Skenario:**

```php
test('perubahan status compliance tercatat di histori', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'category' => 'Commercial',
        'normalized_name' => 'office',
    ]);

    // Scan 1: Office tanpa lisensi → Tidak Berlisensi
    // (simulasi melalui job processing)

    // Tambah lisensi
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
    ]);

    // Scan 2: Office dengan lisensi → Berlisensi

    // Assert: kedua status tercatat di compliance_snapshots
    $snapshots = ComplianceSnapshot::where('software_name', 'Office')
        ->orderBy('id')
        ->pluck('status')
        ->toArray();

    expect($snapshots)->toBe(['Tidak Berlisensi', 'Berlisensi']);
});
```

**Catatan:** Test ini mungkin lebih kompleks karena melibatkan job processing. Bisa menggunakan `Queue::fake()` dan dispatch job secara manual, atau `Bus::fake()` tergantung pendekatan.

---

### 8.8 Test Failed Scan (Scan Gagal)

**Skenario:**

```php
test('scan gagal tercatat dengan status failed', function () {
    $computer = Computer::factory()->create();
    $token = $computer->createToken('agent')->plainTextToken;

    // Kirim payload yang menyebabkan error (misal: software kosong)
    // Atau simulasi job failure

    // Assert
    $session = ScanSession::first();
    expect($session->status)->toBe('failed');
    expect($session->error_message)->not->toBeNull();
});

test('komputer yang tidak melapor terdeteksi dari last_seen_at', function () {
    $computer = Computer::factory()->create([
        'last_seen_at' => now()->subDays(2),
    ]);

    // Assert: komputer ini sudah >24 jam tidak melapor
    $offlineComputers = Computer::where('last_seen_at', '<', now()->subDay())->count();
    expect($offlineComputers)->toBe(1);
});
```

---

### 8.9 Test Idempotency (Duplikasi Scan)

**Skenario:**

```php
test('scan dengan UUID yang sama tidak membuat duplikat', function () {
    $computer = Computer::factory()->create();
    $token = $computer->createToken('agent')->plainTextToken;
    $uuid = fake()->uuid();

    $payload = [
        'scan_uuid' => $uuid,
        'software' => [
            ['name' => 'Chrome', 'version' => '140', 'vendor' => 'Google'],
        ],
    ];

    // Kirim 2 kali dengan UUID yang sama
    $this->withToken($token)->postJson('/api/scan-result', $payload)->assertSuccessful();
    $this->withToken($token)->postJson('/api/scan-result', $payload)->assertSuccessful();

    // Assert: hanya 1 scan session
    expect(ScanSession::where('scan_uuid', $uuid)->count())->toBe(1);
});
```

---

### 8.10 Run Pint & Final Check

**Perintah:**
```bash
# Format semua file yang diubah
vendor/bin/pint --dirty

# Jalankan seluruh test suite
php artisan test --compact

# Atau hanya test baru
php artisan test --filter=MultiLabScope
php artisan test --filter=PeriodicScan
php artisan test --filter=SoftwareChange
php artisan test --filter=Idempotency
```

---

## Skenario UAT (Untuk Pengujian Manual dengan Pengguna Nyata)

Ref: Dokumen Bagian 30

| # | Skenario | Aktor | Langkah |
|---|----------|-------|---------|
| 1 | Login | Semua | Login dengan kredensial masing-masing role |
| 2 | Kelola laboratorium | Admin | CRUD laboratorium |
| 3 | Kelola komputer | Admin | CRUD komputer, assign ke lab |
| 4 | Lihat manifest | Admin, Kepala Lab | Lihat daftar software terpasang |
| 5 | Lihat histori monitoring | Admin, Kepala Lab | Lihat scan sessions dan histori |
| 6 | Lihat status lisensi | Admin, Kepala Lab | Lihat compliance current dan historis |
| 7 | Review temuan | Kepala Lab | Review software yang perlu ditinjau |
| 8 | Approval laporan | Kepala Lab | Approve/reject report |
| 9 | Download laporan | Pimpinan | Download report PDF/Excel |
| 10 | Filter data | Semua | Filter berdasarkan lab dan periode |

---

## Checklist Diagram & Dokumen Skripsi (Ref: Bagian 37)

Setelah semua phase coding selesai, pastikan artefak akademik konsisten:

- [ ] Judul tetap konsisten
- [ ] Latar belakang menyebut masalah multi-lab
- [ ] Rumusan masalah mencakup monitoring berkala
- [ ] Batasan masalah mencakup lab objek penelitian
- [ ] Tujuan penelitian mencakup monitoring berkala
- [ ] Manfaat praktis mencakup admin/pengelola lab
- [ ] Diagram konteks direvisi (Admin, PJ Lab, Pimpinan, Agent)
- [ ] Use case direvisi
- [ ] DFD Level 0/1/2 direvisi
- [ ] Activity diagram monitoring direvisi
- [ ] ERD direvisi (dengan tabel baru)
- [ ] Flowchart tahapan penelitian direvisi
- [ ] Metode pengumpulan data menyebut multi-lab
- [ ] Pengujian mencakup periodic monitoring
- [ ] UAT mencakup pengguna nyata
- [ ] Bab hasil menampilkan histori 2-3 bulan

---

## Kriteria Selesai Phase 8

- [ ] Test multi-lab scope PASS
- [ ] Test role scope PASS
- [ ] Test periodic scan PASS
- [ ] Test software added PASS
- [ ] Test software removed PASS
- [ ] Test version change PASS
- [ ] Test compliance change PASS
- [ ] Test failed scan PASS
- [ ] Test idempotency PASS
- [ ] `vendor/bin/pint --dirty` clean (tidak ada perubahan)
- [ ] `php artisan test --compact` — ALL PASS
- [ ] Checklist diagram/dokumen sudah di-review

---

## File yang Akan Dibuat

```
tests/Feature/MultiLabScopeTest.php
tests/Feature/RoleScopeTest.php
tests/Feature/PeriodicScanTest.php
tests/Feature/SoftwareChangeTest.php
tests/Feature/ComplianceChangeTest.php
tests/Feature/FailedScanTest.php
tests/Feature/ScanIdempotencyTest.php
```

## File yang Mungkin Diubah

```
tests/Pest.php (jika perlu konfigurasi tambahan)
database/factories/ (semua factory yang dibutuhkan test)
```
