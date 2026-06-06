# Project Context: Sistem Manifest Backend

## Deskripsi Singkat
**Sistem Manifest Backend** adalah layanan API backend berbasis Laravel untuk _IT Asset Management_ (ITAM) / Sistem Manajemen Aset IT.
Sistem ini bertugas untuk mengelola pendaftaran agen (komputer klien), menerima hasil pemindaian spesifikasi _hardware_ dan _software_, melacak status inventaris lisensi, serta memproses pengelolaan antrean (_queue_) pekerja di latar belakang.

---

## Teknologi & Dependensi Utama
- **Framework Utama:** Laravel 12 (PHP 8.2+) dengan _Eloquent ORM_.
- **Autentikasi Agen (API Security):** Laravel Sanctum.
  Menggunakan model *Per-device token authentication*. Masing-masing agen akan melakukan pendaftaran pertama kali dengan kombinasi Hardware ID (MAC Address / Serial Number) untuk mendapatkan sebuah token unik yang bersifat permanen, lalu digunakan untuk seluruh `request` selanjutnya.
- **Proses Latar Belakang / Queue:** Laravel Horizon dengan Redis (memastikan rekapitulasi data lisensi atau scan massal tidak membebani web server utama).
- **Log Audit / Audit Trail:** `spatie/laravel-activitylog` v5.0 (menyimpan riwayat aktivitas penting pengguna).
- **Ekspor Data:** 
  - `barryvdh/laravel-dompdf` (Untuk *generate* laporan dokumen PDF)
  - `maatwebsite/excel` (Untuk *generate* laporan/ekspor data berbasis Excel)

---

## Arsitektur Data (Models)
Projek ini berjalan di atas beberapa entitas data utama:
1. **`User`, `Role`, `Permission`**: Data pengguna dan kontrol akses berbasis Spatie Permission untuk panel administrator.
2. **`Computer`**: Data mesin/perangkat mendaftar (berisi detail Hostname, Mac Address, Spesifikasi RAM, CPU, IP Adress, dsb).
3. **`SoftwareCatalog`**: Master data aplikasi yang beredar, berfungsi untuk menormalisasi variasi nama _software_ yang masuk.
4. **`SoftwareDiscovery`**: Data relasi atau log temuan yang mengikat bahwa aplikasi X ditemukan pada Komputer Y.
5. **`LicenseInventory`**: Pencatatan lisensi dan sisa kuota kapasitas lisensi.
6. **`ComplianceReport`**: Catatan status kepatuhan (compliance) granular per-software per-komputer dalam sistem, melacak status seperti berlisensi, tidak berlisensi, grace period, atau terlarang.
7. **`ActivityLog`** (Spatie): Menyimpan riwayat perubahan model, aktivitas audit manual, dan deskripsi event sistem.

---

## Alur Kerja Agent Script (`scanner.ps1`)
Di sisi klien ber-OS Windows, terdapat _Agent Script_ berbasis PowerShell (`script/agent/scanner.ps1`) yang berjalan secara *background* atau *scheduled*.
Alurnya adalah:
1. **Pengecekan Identitas (Tahap 0):** Agen mencari keberadaan file `agent_token.txt`. Jika tidak ada, ia memanggil API Registrasi.
2. **Otentikasi & _Polling_ Perintah (Tahap 0.5):** Memanggil endpoint `GET /api/agent/scan-command` dengan Bearer Token untuk mengecek apakah administrator meminta pemindaian saat ini (`should_scan`).
3. **Scan Sistem (Tahap 1 & 2):** 
   - Membaca _Win32_ComputerSystem_ dan WMI _CimInstance_ untuk mengambil data CPU, RAM, Disk, informasi OS dan *License Status*.
   - Membaca *Registry HKLM & HKCU* serta `Get-AppxPackage` untuk mendapatkan daftar perangkat lunak yang ter-install.
4. **Transmisi (Tahap 3):** Membungkus data ke JSON, lalu melakukan `POST /api/scan-result` kembali ke backend.

---

## Route & Endpoint API (`routes/api.php`)
- **`GET /api/ping`**
  Endpoint _health-check_ publik.
- **`POST /api/agent/register`** (Diarahkan ke `AgentRegisterController@register`)
  Endpoint penerimaan pendaftaran pertama jika Token belum ada. Validasi didasarkan pada MAC Address & Serial Number.
- **Protected Routes (Middleware: `auth:sanctum`)**:
  - **`POST /api/scan-result`** (Diarahkan ke `ScanController@store`)
    Endpoint masif yang menerima data spesifikasi dan *software* secara simultan dalam body JSON.
  - **`GET /api/agent/scan-command`** (Diarahkan ke `AgentCommandController@index`)
    Mengembalikan bendera (_boolean_) apakah token/komputer yang bersangkutan ini diminta untuk melapor/memindai balik oleh *server*.

---

## Fitur Kunci Lainnya, Observers & Jobs
- **`LicenseInventoryObserver`**: Terdapat kapabilitas keamanan seperti `encrypt_existing_license_keys` dan pemantauan otomatis apabila ada kalkulasi sisa *seat* (kuota) lisensi yang terambil berdasarkan laporan *software discovery* terkini.
- **`SoftwareCatalogObserver`**: Menjaga integritas data saat direktori perangkat lunak diperbarui. Semua perubahan ini memanfaatkan sistem antrean.
- **`GenerateComplianceReportJob`**: Pekerja latar belakang (background job) otomatis yang mengevaluasi status kepatuhan setiap perangkat lunak yang diinstal oleh klien berdasarkan blokir dan ketersediaan lisensi secara granular (upsert ke tabel `compliance_reports`).
- **`DashboardController`**: Menyajikan matriks pada UI berbasis cache agar efisien. *(Command: `ClearDashboardCache` digunakan untuk mengatur cache analitik secara manual).*

---

## Fitur Manajemen & Audit Terkini

### 1. Manajemen Akun Pengguna (User Account Management)
- **Fungsi CRUD**: Administrator dapat membuat, memperbarui, dan menghapus akun pengguna (dengan role `admin` atau `pimpinan`).
- **Ganti Password**:
  - **Reset Password oleh Admin**: Administrator dapat mengatur ulang password pengguna lain secara paksa.
  - **Ganti Password Mandiri**: Pengguna yang sedang login dapat mengganti password mereka sendiri dari dropdown profil dengan melakukan verifikasi password lama.
- **Aturan Keamanan Tambahan**:
  - Mencegah *self-deletion* (tidak bisa menghapus akun sendiri).
  - Mencegah *self-demotion* (tidak bisa mengubah role sendiri yang berdampak menurunkan hak akses sendiri).
  - Validasi *minimum 1 admin* untuk memastikan sistem tidak terkunci tanpa administrator.

### 2. Activity Log & Audit Trail
- **Pencatatan Otomatis Model**: Melacak event `created`, `updated`, dan `deleted` pada model `User`, `Computer`, `LicenseInventory`, dan `SoftwareCatalog`. Detail nilai sebelum (*old*) dan setelah (*new*) perubahan direkam secara terstruktur pada kolom `attribute_changes`.
- **Perekaman Aktivitas Manual**:
  - Pencatatan saat admin mereset password pengguna lain.
  - Pencatatan saat pengguna mengganti password-nya sendiri.
  - Pencatatan saat administrator mengakses/melihat *License Key* terenkripsi.
  - Pencatatan saat administrator memicu pemindaian ulang (*scan request*) secara individu atau massal.
- **Keamanan Data**:
  - Kolom data sensitif seperti **`password`** dan **`license_key`** secara ketat disaring keluar agar tidak pernah tersimpan di dalam database log audit.
- **Otorisasi**: Halaman log aktivitas hanya dapat diakses oleh role `admin` (di bawah rute `/activity-logs`).
- **Antarmuka Interaktif**: Menyediakan panel monitoring log lengkap dengan pemfilteran (pelaku, entitas, rentang tanggal), pencarian teks deskripsi, pagination, serta ekspansi baris data untuk melihat visualisasi perbandingan nilai lama vs baru secara detail.
- **Pembersihan Log Otomatis**: Konfigurasi masa retensi log selama **90 hari** (`clean_after_days`) dan scheduler Artisan command `activitylog:clean` dijalankan secara harian (`daily()`).

---

_Dokumen ini dapat terus diperbarui saat pengembangan entitas dan fitur kontrol akses makin diperbesar._
