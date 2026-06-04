# Issue: Cleanup Kolom Deprecated pada Tabel `compliance_reports`

**Label**: `refactor`, `database`, `cleanup`  
**Priority**: Medium  
**Estimasi**: ~1 jam  

---

## Konteks

Tabel `compliance_reports` awalnya didesain dengan arsitektur **per-komputer** (1 baris = 1 komputer), di mana kolom-kolom statistik ringkasan (`total_software_installed`, `unlicensed_count`, `blacklisted_count`, `violation_details`) digunakan untuk menyimpan rangkuman pelanggaran.

Pada migration `2026_04_21_052321`, arsitektur diubah menjadi **per-software per-komputer** (1 baris = 1 software di 1 komputer). Kolom-kolom lama **tidak dihapus** saat migrasi tersebut, sehingga sekarang menjadi dead code:

- **Tidak pernah ditulis** oleh `GenerateComplianceReportJob`
- **Tidak pernah dibaca** oleh Controller, View, Export, atau Test manapun
- Hanya tersisa di `$fillable` dan `$casts` pada model `ComplianceReport`

---

## Kolom yang Harus Dihapus

| Kolom | Tipe | Asal Migration | Alasan Hapus |
|-------|------|----------------|-------------|
| `total_software_installed` | `integer`, default `0` | `2026_01_23_075552` | Tidak relevan — setiap baris sekarang = 1 software, bukan rangkuman per komputer |
| `unlicensed_count` | `integer`, default `0` | `2026_01_23_075552` | Digantikan oleh `status` per-baris (`Tidak Berlisensi`) |
| `blacklisted_count` | `integer`, default `0` | `2026_01_23_075552` | Digantikan oleh `status` dan `keterangan` per-baris |
| `violation_details` | `json`, nullable | `2026_01_23_075552` | Tidak perlu — setiap software sudah punya baris sendiri dengan detail lengkap |

---

## Instruksi Implementasi

### Step 1 — Buat Migration Baru

Buat migration untuk menghapus 4 kolom deprecated dari tabel `compliance_reports`.

```bash
php artisan make:migration remove_deprecated_columns_from_compliance_reports_table
```

**Isi migration:**

- **`up()`**: Drop kolom `total_software_installed`, `unlicensed_count`, `blacklisted_count`, dan `violation_details`
- **`down()`**: Tambahkan kembali kolom-kolom tersebut dengan tipe dan default value yang sama seperti migration asli (`2026_01_23_075552`), agar migration bisa di-rollback

### Step 2 — Update Model `ComplianceReport`

**File**: `app/Models/ComplianceReport.php`

- Hapus 4 kolom dari array `$fillable`:
  - `total_software_installed`
  - `unlicensed_count`
  - `blacklisted_count`
  - `violation_details`
- Hapus `'violation_details' => 'array'` dari array `$casts`

### Step 3 — Verifikasi Tidak Ada Referensi Lain

Jalankan pencarian di seluruh codebase untuk memastikan tidak ada kode lain yang mereferensikan kolom-kolom ini:

```bash
grep -rn "total_software_installed\|unlicensed_count\|blacklisted_count\|violation_details" \
  --include="*.php" --include="*.blade.php" \
  app/ resources/ routes/ tests/ database/seeders/
```

> **Catatan**: Hasil pencarian seharusnya **hanya menunjukkan** file migration lama dan model yang akan di-edit. Jika ditemukan referensi lain (misalnya di seeder atau test), perbaiki juga file tersebut.

### Step 4 — Jalankan Test

```bash
php artisan test
```

Pastikan semua test yang ada tetap pass, khususnya `tests/Feature/ComplianceReportGenerationTest.php` yang menguji `GenerateComplianceReportJob`.

---

## Skema Tabel Setelah Cleanup

Tabel `compliance_reports` seharusnya hanya memiliki kolom-kolom berikut setelah migration:

```
compliance_reports
├── id                      (bigint, PK, auto-increment)
├── computer_id             (FK → computers, cascade delete)
├── software_catalog_id     (FK → software_catalogs, cascade delete)
├── software_name           (string — snapshot nama software)
├── software_version        (string, nullable — snapshot versi)
├── status                  (string — Berlisensi | Tidak Berlisensi | Grace Period)
├── keterangan              (string — penjelasan status)
├── license_inventory_id    (FK → license_inventories, nullable, set null on delete)
├── detected_at             (timestamp)
├── scanned_at              (timestamp)
├── created_at              (timestamp)
└── updated_at              (timestamp)

UNIQUE INDEX: (computer_id, software_catalog_id)
```

---

## Checklist

- [ ] Migration baru dibuat dan bisa `migrate` & `rollback` tanpa error
- [ ] Model `ComplianceReport` sudah dibersihkan (`$fillable` dan `$casts`)
- [ ] Tidak ada referensi ke kolom deprecated di seluruh codebase
- [ ] `php artisan test` — semua test pass
- [ ] `php artisan migrate:fresh --seed` — seeding berjalan normal

---

## Referensi

- Migration awal: `database/migrations/2026_01_23_075552_create_compliance_reports_table.php`
- Migration perubahan arsitektur: `database/migrations/2026_04_21_052321_update_compliance_reports_table.php`
- Job yang aktif: `app/Jobs/GenerateComplianceReportJob.php`
- Model: `app/Models/ComplianceReport.php`

---

---

# Arsip: Codebase Review Sebelumnya (10 Mei 2026)

> Temuan audit di bawah ini adalah hasil review sebelumnya. Sebagian sudah diperbaiki, sebagian masih berlaku. Disimpan sebagai referensi historis.

<details>
<summary>Klik untuk melihat temuan audit lengkap</summary>

## 🔴 Severity: HIGH — Bug / Error Aktif

### H1 — `GenerateComplianceReportJob`: Mengakses Property yang Tidak Ada (`detected_at`)

**File**: `app/Jobs/GenerateComplianceReportJob.php` baris 141  
**Masalah**: Kode menggunakan `$discovery->detected_at`, tetapi model `SoftwareDiscovery` **tidak memiliki kolom `detected_at`** di migration maupun di `$fillable`. Kolom yang ada adalah `install_date`. Nilainya akan selalu `null`, sehingga akan selalu fallback ke `now()`.

```php
// Kode saat ini (salah)
'detected_at' => $discovery->detected_at ?? now(),

// Seharusnya
'detected_at' => $discovery->install_date ?? now(),
```

---

### H2 — `GenerateComplianceReportJob`: Logika Grace Period Terbalik

**File**: `app/Jobs/GenerateComplianceReportJob.php` baris 125  
**Masalah**: Pengecekan "hampir expired" menggunakan `$license->expiry_date->lte($today->copy()->addDays(30))`. Ini berarti **semua lisensi yang expiry-nya kurang dari 30 hari ke depan** dianggap Grace Period, **termasuk yang sudah expired** (karena tanggal lalu pasti `lte` hari ini + 30 hari). Ini menyebabkan konflik logika dengan pengecekan expired di baris 113.

Logika seharusnya: expiry date masih di masa depan, tapi kurang dari 30 hari lagi.

```php
// Kode saat ini (salah — overlap dengan cek expired)
elseif ($license->expiry_date && $license->expiry_date->lte($today->copy()->addDays(30)))

// Seharusnya (antara hari ini dan 30 hari ke depan)
elseif ($license->expiry_date && $license->expiry_date->isBetween($today, $today->copy()->addDays(30)))
```

---

### H3 — `LicenseDataController::index`: `total_value` Tidak Menangani `price_per_unit` NULL

**File**: `app/Http/Controllers/LicenseDataController.php` baris 74  
**Masalah**: Kalkulasi `total_value` menggunakan `SUM(quota_limit * price_per_unit)`. Ketika `price_per_unit` bernilai `NULL` (yang sekarang diizinkan sebagai default), operasi `quota_limit * NULL` menghasilkan `NULL` di SQL. Baris tersebut akan diabaikan dari SUM secara diam-diam, bukan error — tetapi hasilnya bisa menyesatkan karena lisensi tanpa harga tidak dihitung, sementara UI menampilkannya sebagai "Estimasi biaya lisensi" (seolah total).

```php
// Kode saat ini
'total_value' => LicenseInventory::sum(\DB::raw('quota_limit * price_per_unit')),

// Perbaikan — gunakan COALESCE untuk treat NULL sebagai 0
'total_value' => LicenseInventory::sum(\DB::raw('quota_limit * COALESCE(price_per_unit, 0)')),
```

---

### H4 — `UpdateSoftwareRequest`: Kategori `Shareware` dan `Other` Tidak Ada di Migration

**File**: `app/Http/Requests/UpdateSoftwareRequest.php` baris 17  
**Masalah**: Validasi mengizinkan kategori `Shareware` dan `Other`, tetapi migration `software_catalogs` mendefinisikan kolom `category` sebagai `ENUM('Freeware', 'Commercial', 'OpenSource')`. Menyimpan nilai `Shareware` atau `Other` akan menyebabkan **SQL error** di production.

**Solusi**: Tambahkan migration baru untuk mengubah enum `category` agar menyertakan `Shareware` dan `Other`, atau hapus kedua opsi tersebut dari validasi.

---

### H5 — `ReportController::getSoftwareData`: Eager Load `catalog.licenses` Gagal karena JOIN

**File**: `app/Http/Controllers/ReportController.php` baris 168  
**Masalah**: Query menggunakan `->join('software_catalogs', ...)` pada model `SoftwareDiscovery` lalu menambahkan `->with(['catalog.licenses'])`. Karena `groupBy` dan `join` dipakai, kolom `id` dari `SoftwareDiscovery` menjadi ambigu dan hasil query bukan instance `SoftwareDiscovery` yang valid — eager loading `catalog.licenses` kemungkinan besar tidak berfungsi atau error.

**Solusi**: Refactor query ini agar menggunakan subquery/raw query yang lebih bersih, atau gunakan pendekatan berbasis `SoftwareCatalog` langsung (mirip `ComplianceDataController`).

---

## 🟡 Severity: MEDIUM — Kesalahan Logika / Inkonsistensi

### M1 — `AgentRegisterController`: Validasi Dijalankan Sebelum Cek Registration Key

**File**: `app/Http/Controllers/Api/AgentRegisterController.php` baris 19-40  
**Masalah**: Validasi input dilakukan terlebih dahulu (baris 19), baru kemudian pengecekan `X-Agent-Key` (baris 35). Ini berarti penyerang tanpa key yang valid tetap bisa mendapat error message validasi — memberikan informasi tentang endpoint internal. Sebaiknya cek `X-Agent-Key` dulu sebelum validasi apapun.

---

### M2 — `DashboardController`: Cache `now()->startOfMonth()` Menyebabkan Data Stale

**File**: `app/Http/Controllers/DashboardController.php` baris 17-36  
**Masalah**: Statistik `newComputersThisMonth` dan `newInstallationThisMonth` di-cache 10 menit. Namun `now()->startOfMonth()` dihitung **saat closure pertama kali berjalan** dan hasilnya di-cache. Pada pergantian bulan (misalnya 30 April → 1 Mei pukul 00:00), jika cache dari bulan lalu masih aktif, data bulan baru akan menunjukkan angka dari bulan lalu selama 10 menit.

**Solusi ringan**: Sertakan bulan saat ini di cache key, misal `'dashboard.stats.' . now()->format('Y-m')`.

---

### M3 — `ComplianceDataController`: Statistik Global Menjalankan Query Duplikat

**File**: `app/Http/Controllers/ComplianceDataController.php` baris 47-52  
**Masalah**: Setelah menjalankan query paginated `$softwares` (baris 14-44), controller menjalankan **query kedua yang hampir identik** (`$allCommercial`, baris 47) hanya untuk menghitung statistik. Ini menggandakan beban database.

**Solusi**: Gunakan cache atau hitung statistik dari satu query dan simpan di variabel terpisah sebelum pagination.

---

### M4 — `ReportController::showLisensi`: N+1 Query pada Perhitungan Usage

**File**: `app/Http/Controllers/ReportController.php` baris 220-226  
**Masalah**: Setiap lisensi menjalankan `SoftwareDiscovery::where('catalog_id', ...)->count()` secara individual di dalam `->map()`. Jika ada 100 lisensi, ini menghasilkan **100 query tambahan** (N+1 problem). Hal yang sama terjadi di `exportLisensi` (baris 247).

**Solusi**: Gunakan `withCount('catalog.discoveries')` atau batch query `catalog_id → count` sekali di awal.

---

### M5 — `ReportController::runComplianceScan`: `Computer::all()` Bisa Crash pada Dataset Besar

**File**: `app/Http/Controllers/ReportController.php` baris 271  
**Masalah**: `Computer::all()` memuat **semua record komputer ke memory** sekaligus. Jika tabel `computers` memiliki ribuan baris, ini bisa menyebabkan memory exhaustion.

**Solusi**: Gunakan `Computer::chunk(100, function ($computers) { ... })` atau `Computer::cursor()`.

---

### M6 — `LicenseDataController`: Lisensi Tanpa Expiry Date Diurutkan ke Atas

**File**: `app/Http/Controllers/LicenseDataController.php` baris 64  
**Masalah**: `orderBy('expiry_date', 'asc')` menempatkan `NULL` di awal di MySQL (lifetime licenses). Ini mungkin membingungkan karena user menganggap urutan teratas adalah yang paling mendesak, tetapi yang muncul justru lisensi seumur hidup.

**Solusi**: Gunakan `orderByRaw('expiry_date IS NULL, expiry_date ASC')` agar lifetime licenses muncul di akhir.

---

## 🔵 Severity: LOW — Kekurangan / Improvement

### L1 — Endpoint `GET /licenses/{license}/key` Sebaiknya Menggunakan POST

**File**: `routes/web.php` baris 64  
**Masalah**: Endpoint untuk mengambil decrypted license key menggunakan method `GET`. URL GET bisa tercatat di server access logs, browser history, dan cache proxy — memaparkan URL yang mengandung parameter sensitif. `POST` lebih aman untuk operasi yang mengambil data sensitif.

---

### L2 — Tidak Ada Rate Limiting pada Endpoint License Key

**File**: `app/Http/Controllers/LicenseDataController.php` method `getKey()`  
**Masalah**: Endpoint yang mengembalikan decrypted license key tidak memiliki rate limiting. Penyerang yang sudah terautentikasi bisa melakukan brute-force mengekstrak semua key.

**Solusi**: Tambahkan middleware `throttle:10,1` pada route `licenses.key`.

---

### L3 — `ScanController`: Tidak Ada Validasi `mac_address` Format

**File**: `app/Http/Controllers/Api/ScanController.php` baris 35  
**Masalah**: Validasi `mac_address` hanya memeriksa `nullable|string|max:17`. Tidak ada cek format regex seperti di `AgentRegisterController` (`regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/`). Ini bisa menyebabkan data MAC address yang tidak valid tersimpan di database.

---

### L4 — `Computer` Model Extends `Authenticatable` — Potensi Kebingungan

**File**: `app/Models/Computer.php` baris 9  
**Masalah**: Model `Computer` extends `Illuminate\Foundation\Auth\User as Authenticatable`, yang biasanya untuk model User. Ini dilakukan agar Sanctum token berfungsi (`HasApiTokens`). Meskipun bekerja, bisa membingungkan developer lain karena model komputer tidak memiliki `password` atau `email`. Sebaiknya didokumentasikan dengan jelas alasannya.

---

### L5 — Tidak Ada Observer/Cache Invalidation di Dashboard Setelah Scan

**File**: `app/Observers/LicenseInventoryObserver.php`  
**Masalah**: Hanya `LicenseInventory` yang memiliki observer untuk invalidate cache. Ketika data scan baru masuk (Computer update, SoftwareDiscovery baru), cache `dashboard.stats` dan `dashboard.charts` **tidak di-invalidate**. Data dashboard bisa stale hingga 10 menit setelah scan.

**Solusi**: Tambahkan observer untuk `Computer` dan `SoftwareDiscovery` yang juga menginvalidasi cache dashboard, atau invalidate cache di akhir `ProcessScanResultJob`.

---

### L6 — `ReportController`: Inkonsistensi Akses Role Name

**File**: `app/Http/Controllers/ReportController.php`  
**Masalah**: Ada dua cara berbeda untuk mengambil role name:
- Baris 59, 125, 156, 263: `auth()->user()->getRoleNames()->first()` 
- Baris 206: `auth()->user()->roles->first()->name ?? 'User'`

Pendekatan kedua lebih aman (null-safe), tetapi tidak konsisten. Jika `getRoleNames()` mengembalikan collection kosong, `->first()` mengembalikan `null` dan string yang dihasilkan menjadi `"Admin Name ()"`.

---

### L7 — Tidak Ada Validasi `proof_image` pada `UpdateLicenseRequest`

**File**: `app/Http/Requests/UpdateLicenseRequest.php`  
**Masalah**: Validasi untuk `proof_image` pada update hanya memeriksa `nullable|image|mimes:jpeg,png,jpg|max:2048`. Ini benar secara teknis, tetapi jika user mengirim file non-image yang lolos MIME check, tidak ada server-side content verification tambahan. Pertimbangkan menambahkan validasi `dimensions` jika diperlukan.

---

## Ringkasan

| Severity | Jumlah | Keterangan |
|----------|--------|------------|
| 🔴 HIGH  | 5      | Bug aktif yang bisa menyebabkan error atau data salah |
| 🟡 MEDIUM| 6      | Kesalahan logika atau performa yang perlu diperbaiki |
| 🔵 LOW   | 7      | Kekurangan minor atau improvement untuk keamanan/konsistensi |

**Total: 18 temuan**

</details>
