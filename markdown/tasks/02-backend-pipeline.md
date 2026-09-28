# Phase 2 — Backend Pipeline: Scan Processing

**Prioritas:** P0 WAJIB
**Estimasi:** 2-3 hari kerja
**Depends on:** Phase 1 (Data Model)
**Referensi dokumen:** Bagian 7, 8, 9, 10

---

## Konteks

### Alur Saat Ini

```
Agent
  ↓
POST /scan-result
  ↓
Update Computer metadata
  ↓
ProcessScanResultJob
  ↓
Update SoftwareDiscovery (updateOrCreate + delete yang hilang)
  ↓
GenerateComplianceReportJob::dispatch()->delay(10 detik)  ← MASALAH
  ↓
Update ComplianceReport (current state)
```

**Masalah utama:**
1. Tidak ada `ScanSession` — tidak bisa melacak kapan scan terjadi
2. Tidak ada snapshot software per scan — histori hilang
3. Compliance dijalankan dengan `delay(10s)` — tidak menjamin data sudah siap
4. Software yang tidak terdeteksi dihapus dari `software_discoveries` — histori hilang

### Alur Target

```
Agent
  ↓
POST /scan-result (dengan scan_uuid)
  ↓
Cek idempotency (scan_uuid sudah ada?)
  ↓
Create ScanSession (status: pending)
  ↓
ProcessScanResultJob
  ├── Update ScanSession status → running
  ├── Simpan ScanSoftwareResult (snapshot historis)
  ├── Update SoftwareDiscovery (current state, tetap berjalan)
  ├── Update ScanSession (software_count, completed_at, status → completed)
  └── Dispatch GenerateComplianceReportJob (langsung, tanpa delay)
        ↓
GenerateComplianceReportJob
  ├── Simpan ComplianceSnapshot (snapshot historis)
  └── Update ComplianceReport / current cache
```

---

## Task

### 2.1 Ubah `ScanController@store`

**File:** `app/Http/Controllers/Api/ScanController.php`

**Perubahan:**

1. **Terima `scan_uuid` dari request body**
   - Validasi: `scan_uuid` wajib, format UUID
   - Jika `scan_uuid` sudah ada di `scan_sessions`, return response sukses tanpa proses ulang (idempotent)

2. **Buat `ScanSession` sebelum dispatch job**
   ```
   ScanSession::create([
       'computer_id'   => $computer->id,
       'scan_uuid'     => $request->scan_uuid,
       'started_at'    => $request->client_started_at ?? now(),
       'status'        => 'pending',
       'trigger'       => $request->scan_mode ?? 'scheduled',
       'agent_version' => $request->agent_version,
   ]);
   ```

3. **Dispatch `ProcessScanResultJob` dengan `scan_session_id`**
   ```
   ProcessScanResultJob::dispatch($scanSession, $softwareData);
   ```
   Bukan lagi hanya `dispatch($computer)`.

4. **Response tetap 200/202** — agent tidak perlu tahu internal processing

**Catatan untuk developer:**
- `$computer` diperoleh dari Sanctum token (`$request->user()`)
- Update `last_seen_at` pada computer tetap dilakukan di controller
- Validasi bahwa request body masih backward-compatible (agent lama yang belum mengirim `scan_uuid` harus ditangani — generate UUID di server sebagai fallback sementara)

---

### 2.2 Refactor `ProcessScanResultJob`

**File:** `app/Jobs/ProcessScanResultJob.php`

**Constructor baru:**
```php
public function __construct(
    public ScanSession $scanSession,
    public array $softwareData
)
```

**Alur handle():**

```
1. Update ScanSession status → 'running'

2. Loop setiap software dari $softwareData:
   a. Match dengan SoftwareCatalog (via SoftwareCatalogService)
   b. Simpan ke scan_software_results:
      ScanSoftwareResult::create([
          'scan_session_id' => $this->scanSession->id,
          'catalog_id'      => $catalogMatch?->id,
          'raw_name'        => $software['name'],
          'version'         => $software['version'],
          'vendor'          => $software['vendor'],
          'install_date'    => $software['installDate'],
      ]);
   c. Update/create software_discoveries (current state, logic existing)

3. Update ScanSession:
   - software_count = jumlah software diproses
   - completed_at = now()
   - status = 'completed'

4. Dispatch GenerateComplianceReportJob LANGSUNG (bukan delay):
   GenerateComplianceReportJob::dispatch($this->scanSession);
```

**Error handling:**
```
try {
    // proses di atas
} catch (\Throwable $e) {
    $this->scanSession->update([
        'status'        => 'failed',
        'error_message' => $e->getMessage(),
        'completed_at'  => now(),
    ]);
    throw $e; // biar Laravel retry mechanism tetap bekerja
}
```

**Catatan:**
- Logic filter software (`SoftwareFilterService`) tetap digunakan
- Logic `updateOrCreate` + delete pada `software_discoveries` TETAP berjalan — ini adalah current state
- Yang baru adalah `scan_software_results` — ini TIDAK BOLEH dihapus

---

### 2.3 Refactor `GenerateComplianceReportJob`

**File:** `app/Jobs/GenerateComplianceReportJob.php`

**Constructor baru:**
```php
public function __construct(
    public ScanSession $scanSession
)
```

**Alur handle():**

```
1. Ambil computer dari scanSession
2. Ambil semua ScanSoftwareResult untuk session ini
3. Untuk setiap software result:
   a. Cek apakah catalog-nya Commercial
   b. Jika Commercial, cek license_inventories
   c. Tentukan status compliance:
      - Ada lisensi valid → 'Berlisensi'
      - Tidak ada lisensi → 'Tidak Berlisensi'
      - Lisensi expired < 30 hari → 'Grace Period'
      - Tidak bisa ditentukan → 'Perlu Ditinjau'
   d. Cek config/compliance.php blocked list
   e. Simpan ke compliance_snapshots:
      ComplianceSnapshot::create([
          'scan_session_id'     => $this->scanSession->id,
          'computer_id'         => $computer->id,
          'software_catalog_id' => $catalogId,
          'software_name'       => $rawName,
          'software_version'    => $version,
          'status'              => $complianceStatus,
          'keterangan'          => $notes,
          'license_inventory_id'=> $licenseId,
          'scanned_at'          => $this->scanSession->started_at,
      ]);

4. Update compliance_reports (current state) jika masih dibutuhkan
```

**Catatan:**
- Logic compliance checking yang sudah ada di-reuse, hanya output-nya yang ditambah (snapshot)
- `compliance_reports` tetap diupdate sebagai current state (backward compatible)
- `compliance_snapshots` adalah tambahan historis yang TIDAK BOLEH dihapus

---

### 2.4 Implementasi Job Chaining

**Tujuan:** Ganti `delay(10s)` dengan dependency yang menjamin urutan.

**Pendekatan yang direkomendasikan:**

**Opsi A — Dispatch dari dalam job (sederhana)**
```php
// Di akhir ProcessScanResultJob::handle()
GenerateComplianceReportJob::dispatch($this->scanSession);
```
Ini sudah menjamin urutan karena dispatch terjadi SETELAH proses scan selesai.

**Opsi B — Bus::chain (lebih eksplisit)**
```php
// Di ScanController
Bus::chain([
    new ProcessScanResultJob($scanSession, $softwareData),
    new GenerateComplianceReportJob($scanSession),
])->dispatch();
```

**Rekomendasi:** Opsi A lebih sederhana dan cukup untuk kasus ini. Opsi B lebih cocok jika ada banyak job yang perlu di-chain.

**Yang TIDAK BOLEH dilakukan:**
```php
// JANGAN gunakan ini
GenerateComplianceReportJob::dispatch($computer)->delay(now()->addSeconds(10));
```

---

### 2.5 Idempotency `scan_uuid`

**Tujuan:** Mencegah duplikasi scan jika agent retry.

**Implementasi di `ScanController`:**
```php
// Cek apakah scan sudah ada
$existing = ScanSession::where('scan_uuid', $request->scan_uuid)->first();

if ($existing) {
    return response()->json([
        'message' => 'Scan already processed',
        'scan_session_id' => $existing->id,
    ], 200);
}
```

**Fallback untuk agent lama:**
```php
$scanUuid = $request->scan_uuid ?? (string) Str::uuid();
```

**Index:**
- `scan_sessions.scan_uuid` sudah unique di migration (Task 1.1)

---

### 2.6 Error Handling & Status Tracking

**Skenario yang harus ditangani:**

| Skenario | Behavior |
|----------|----------|
| Scan berhasil penuh | `status = 'completed'`, `software_count > 0` |
| Scan berhasil tapi 0 software | `status = 'completed'`, `software_count = 0` (mungkin komputer baru install OS) |
| Job gagal (exception) | `status = 'failed'`, `error_message` terisi |
| Scan parsial (beberapa software gagal diproses) | `status = 'partial'`, proses yang berhasil tetap tersimpan |
| Agent tidak bisa terhubung | Tidak ada ScanSession (agent tidak sampai ke API) — gunakan `last_seen_at` untuk deteksi |

**Komputer yang tidak melapor:**
- Jika `last_seen_at` lebih dari 24 jam, komputer dianggap "belum scan"
- Dashboard harus menampilkan komputer yang belum scan hari ini

---

## Kriteria Selesai Phase 2

- [ ] POST `/scan-result` membuat `ScanSession` baru
- [ ] `ProcessScanResultJob` menyimpan data ke `scan_software_results`
- [ ] `ProcessScanResultJob` tetap mengupdate `software_discoveries` (backward compatible)
- [ ] `GenerateComplianceReportJob` menyimpan data ke `compliance_snapshots`
- [ ] Job chaining berjalan tanpa `delay(10s)`
- [ ] Scan UUID yang sama tidak membuat duplikat
- [ ] Scan gagal tercatat dengan status `failed` dan `error_message`
- [ ] `last_seen_at` pada computer tetap terupdate
- [ ] Existing agent (tanpa `scan_uuid`) tetap bisa mengirim scan (fallback)
- [ ] `vendor/bin/pint --dirty` clean

---

## File yang Akan Diubah

```
app/Http/Controllers/Api/ScanController.php     — logic utama berubah
app/Jobs/ProcessScanResultJob.php                — refactor besar
app/Jobs/GenerateComplianceReportJob.php         — refactor besar
```

## File yang Mungkin Perlu Diubah

```
app/Services/SoftwareCatalogService.php          — jika logic matching perlu disesuaikan
app/Services/SoftwareFilterService.php           — jika filter perlu disesuaikan
app/Http/Requests/StoreScanResultRequest.php     — jika ada form request (cek dulu)
```
