# Phase 1 — Data Model & Migrasi Database

**Prioritas:** P0 WAJIB
**Estimasi:** 1-2 hari kerja
**Depends on:** Tidak ada (phase pertama)
**Referensi dokumen:** Bagian 3, 4, 5, 6, 17, 24, 31, 34

---

## Konteks

Saat ini `software_discoveries` hanya menyimpan **current state** menggunakan pola `updateOrCreate()` lalu menghapus record yang tidak terdeteksi. Akibatnya:

- Tidak ada histori scan
- Tidak bisa menjawab "software apa yang terdeteksi pada tanggal X?"
- Riwayat perubahan software (install/uninstall/upgrade) hilang

### Arsitektur Data Target

```
Laboratory
   │
   └── Computer
         │
         ├── SoftwareDiscovery           ← kondisi saat ini (dipertahankan)
         │
         └── ScanSession                 ← histori setiap monitoring
                 │
                 ├── ScanSoftwareResult   ← software pada scan tersebut
                 │
                 └── ComplianceSnapshot   ← status lisensi pada scan tersebut
```

---

## Task

### 1.1 Buat migration `scan_sessions`

**Tujuan:** Mewakili satu kali kegiatan monitoring pada satu komputer.

**Perintah:**
```bash
php artisan make:migration create_scan_sessions_table --no-interaction
php artisan make:model ScanSession --factory --no-interaction
```

**Kolom:**

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigIncrements | Primary key |
| `computer_id` | foreignId | FK ke `computers`, `nullOnDelete()` agar histori tidak hilang saat komputer dihapus |
| `scan_uuid` | uuid, unique | Idempotency key dari agent, mencegah duplikasi scan |
| `started_at` | timestamp, nullable | Waktu mulai scan (bisa dari client atau server) |
| `completed_at` | timestamp, nullable | Waktu selesai scan |
| `status` | string | Nilai: `pending`, `running`, `completed`, `failed`, `partial` |
| `trigger` | string | Nilai: `scheduled`, `manual`, `on_demand` |
| `software_count` | unsignedInteger, default 0 | Jumlah software ditemukan |
| `error_message` | text, nullable | Pesan error jika scan gagal |
| `agent_version` | string, nullable | Versi agent yang melakukan scan |
| `created_at` | timestamp | Laravel timestamp |
| `updated_at` | timestamp | Laravel timestamp |

**Index:**
- `unique('scan_uuid')`
- `index('computer_id', 'status')`
- `index('started_at')`

**Relasi di Model `ScanSession`:**
```php
public function computer(): BelongsTo
public function softwareResults(): HasMany  // → ScanSoftwareResult
public function complianceSnapshots(): HasMany  // → ComplianceSnapshot
```

**Relasi tambahan di Model `Computer`:**
```php
public function scanSessions(): HasMany  // → ScanSession
```

**Catatan untuk developer:**
- Gunakan `nullOnDelete()` pada FK `computer_id`, BUKAN `cascadeOnDelete()`. Ini krusial agar histori scan tidak hilang saat komputer dihapus/dinonaktifkan.
- `scan_uuid` wajib unique — ini mencegah duplikasi jika agent retry.

---

### 1.2 Buat migration `scan_software_results`

**Tujuan:** Menyimpan snapshot software yang ditemukan pada satu scan tertentu. Data ini TIDAK BOLEH dihapus hanya karena software sudah tidak muncul pada scan berikutnya.

**Perintah:**
```bash
php artisan make:migration create_scan_software_results_table --no-interaction
php artisan make:model ScanSoftwareResult --factory --no-interaction
```

**Kolom:**

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigIncrements | Primary key |
| `scan_session_id` | foreignId | FK ke `scan_sessions`, `cascadeOnDelete()` |
| `catalog_id` | foreignId, nullable | FK ke `software_catalogs`, `nullOnDelete()` |
| `raw_name` | string | Nama software mentah dari registry/appx |
| `version` | string, nullable | Versi software |
| `vendor` | string, nullable | Vendor/publisher |
| `install_date` | date, nullable | Tanggal instalasi |
| `created_at` | timestamp | Laravel timestamp |
| `updated_at` | timestamp | Laravel timestamp |

**Index:**
- `index('scan_session_id')`
- `index('catalog_id')`
- `index('raw_name')`

**Relasi di Model `ScanSoftwareResult`:**
```php
public function scanSession(): BelongsTo
public function catalog(): BelongsTo  // → SoftwareCatalog
```

---

### 1.3 Buat migration `compliance_snapshots`

**Tujuan:** Menyimpan status kepatuhan pada saat scan tertentu. Ini adalah bukti historis yang tidak boleh diubah retroaktif.

**Perintah:**
```bash
php artisan make:migration create_compliance_snapshots_table --no-interaction
php artisan make:model ComplianceSnapshot --factory --no-interaction
```

**Kolom:**

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigIncrements | Primary key |
| `scan_session_id` | foreignId | FK ke `scan_sessions`, `cascadeOnDelete()` |
| `computer_id` | foreignId, nullable | FK ke `computers`, `nullOnDelete()` |
| `software_catalog_id` | foreignId, nullable | FK ke `software_catalogs`, `nullOnDelete()` |
| `software_name` | string | Nama software (denormalisasi untuk histori) |
| `software_version` | string, nullable | Versi software |
| `status` | string | Nilai: `Berlisensi`, `Tidak Berlisensi`, `Grace Period`, `Perlu Ditinjau` |
| `keterangan` | text, nullable | Catatan tambahan |
| `license_inventory_id` | foreignId, nullable | FK ke `license_inventories`, `nullOnDelete()` |
| `detected_at` | timestamp, nullable | Kapan software pertama kali terdeteksi |
| `scanned_at` | timestamp | Waktu scan dilakukan |
| `created_at` | timestamp | Laravel timestamp |
| `updated_at` | timestamp | Laravel timestamp |

**Index:**
- `index('scan_session_id')`
- `index('computer_id', 'status')`
- `index('scanned_at')`

**Relasi di Model `ComplianceSnapshot`:**
```php
public function scanSession(): BelongsTo
public function computer(): BelongsTo
public function softwareCatalog(): BelongsTo
public function licenseInventory(): BelongsTo
```

**Catatan tentang status:**
- `Berlisensi` — Ada bukti lisensi yang sesuai di `license_inventories`
- `Tidak Berlisensi` — Tidak ditemukan bukti lisensi yang sesuai (bukan kesimpulan hukum)
- `Grace Period` — Lisensi dalam masa tenggang/evaluasi
- `Perlu Ditinjau` — Butuh review manual dari admin/PJ Lab

---

### 1.4 (Opsional) Buat migration `faculties`

**Tujuan:** Mengelompokkan laboratorium berdasarkan fakultas. Implementasikan hanya jika dashboard/laporan perlu grouping per fakultas.

**Perintah:**
```bash
php artisan make:migration create_faculties_table --no-interaction
php artisan make:model Faculty --factory --no-interaction
php artisan make:migration add_faculty_id_to_laboratories_table --no-interaction
```

**Kolom `faculties`:**

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigIncrements | Primary key |
| `name` | string | Nama fakultas (FTI, FKIP, dll) |
| `code` | string, unique | Kode singkat fakultas |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Kolom tambahan di `laboratories`:**
- `faculty_id` — foreignId, nullable, `nullOnDelete()`

**Keputusan:** Bisa ditunda ke Phase 5 atau 6 jika waktu sempit. Yang penting tabel utama (scan_sessions, scan_software_results, compliance_snapshots) selesai dulu.

---

### 1.5 Review FK & Delete Behavior

**Tujuan:** Audit foreign key yang sudah ada agar penghapusan komputer tidak menghilangkan evidence monitoring.

**Yang perlu dicek:**

| Relasi | FK Saat Ini | Target |
|--------|-------------|--------|
| `software_discoveries.computer_id` | CASCADE (kemungkinan) | Pertahankan CASCADE (current state boleh hilang) |
| `compliance_reports.computer_id` | CASCADE (kemungkinan) | Ubah ke `nullOnDelete()` atau pertahankan jika compliance_reports akan diganti oleh compliance_snapshots |
| `scan_sessions.computer_id` | (baru) | `nullOnDelete()` — histori scan WAJIB tetap ada |
| `compliance_snapshots.computer_id` | (baru) | `nullOnDelete()` |

**Prinsip:** Current state boleh dihapus bersama komputer, tapi histori/evidence TIDAK BOLEH.

**File yang perlu diperiksa:**
- `database/migrations/2026_01_23_022259_create_computers_table.php`
- `database/migrations/2026_01_23_022326_create_software_discoveries_table.php`
- `database/migrations/2026_01_23_075552_create_compliance_reports_table.php`

---

### 1.6 Tambah kolom `status` pada `computers`

**Tujuan:** Ganti mekanisme hard delete dengan status agar komputer yang tidak digunakan tetap memiliki histori.

**Perintah:**
```bash
php artisan make:migration add_status_to_computers_table --no-interaction
```

**Kolom:**

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `status` | string, default 'active' | Nilai: `active`, `inactive`, `maintenance`, `retired` |

**Perubahan di Model `Computer`:**
- Tambahkan `status` ke `$fillable`
- Tambahkan scope `scopeActive()` yang memfilter `status = 'active'`
- Pertimbangkan apakah query existing perlu diubah untuk hanya menampilkan komputer aktif

---

### 1.7 Perbaiki `report_approvals.period`

**Tujuan:** Ganti kolom `period` (varchar) menjadi `period_start` dan `period_end` (date) agar bisa difilter programatis.

**Perintah:**
```bash
php artisan make:migration update_period_in_report_approvals_table --no-interaction
```

**Perubahan:**
- Tambah `period_start` (date, nullable)
- Tambah `period_end` (date, nullable)
- Migrasi data dari `period` (varchar) ke kolom baru jika memungkinkan
- Hapus kolom `period` lama setelah data berhasil dimigrasi (atau pertahankan sementara untuk backward compatibility)

**Catatan:** Jika kolom `period` saat ini berisi format seperti "Oktober 2026", perlu logika konversi. Jika terlalu kompleks, pertahankan kolom `period` lama dan tambahkan kolom baru di sampingnya.

---

### 1.8 Buat Factory & Seeder

**Tujuan:** Factory untuk testing dan seeder untuk development data.

**Factory yang perlu dibuat:**
- `ScanSessionFactory` — menghasilkan scan session lengkap
- `ScanSoftwareResultFactory` — menghasilkan software result terkait session
- `ComplianceSnapshotFactory` — menghasilkan compliance snapshot terkait session

**Seeder:**
- `ScanSessionSeeder` — buat sample scan sessions untuk beberapa komputer selama ~3 bulan
- Pastikan data sample mencakup skenario: software baru, software hilang, versi berubah, compliance berubah

---

## Kriteria Selesai Phase 1

- [ ] `php artisan migrate` berhasil tanpa error
- [ ] Model `ScanSession`, `ScanSoftwareResult`, `ComplianceSnapshot` memiliki relasi yang benar
- [ ] Model `Computer` memiliki relasi `scanSessions()`
- [ ] FK behavior: delete komputer TIDAK menghapus scan_sessions dan compliance_snapshots
- [ ] Factory bisa menghasilkan data test (`ScanSession::factory()->create()` berhasil)
- [ ] Existing data dan fitur tidak rusak (migration backward-compatible)
- [ ] Kolom `status` pada computers berfungsi
- [ ] `vendor/bin/pint --dirty` clean

---

## File yang Akan Dibuat/Diubah

### Baru
```
database/migrations/xxxx_create_scan_sessions_table.php
database/migrations/xxxx_create_scan_software_results_table.php
database/migrations/xxxx_create_compliance_snapshots_table.php
database/migrations/xxxx_add_status_to_computers_table.php
database/migrations/xxxx_update_period_in_report_approvals_table.php
database/migrations/xxxx_create_faculties_table.php (opsional)
database/migrations/xxxx_add_faculty_id_to_laboratories_table.php (opsional)
app/Models/ScanSession.php
app/Models/ScanSoftwareResult.php
app/Models/ComplianceSnapshot.php
app/Models/Faculty.php (opsional)
database/factories/ScanSessionFactory.php
database/factories/ScanSoftwareResultFactory.php
database/factories/ComplianceSnapshotFactory.php
```

### Diubah
```
app/Models/Computer.php          — tambah relasi scanSessions(), status scope
app/Models/Laboratory.php        — tambah relasi faculty() jika opsional diambil
app/Models/ReportApproval.php    — sesuaikan kolom period
```
