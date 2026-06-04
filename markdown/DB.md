# Skema Database & Relasi (Sistem Manifest Backend)

Dokumen ini menjelaskan struktur tabel, kolom, dan relasi (Relationship) antar tabel yang ada di dalam database Sistem Manifest Backend. Sistem ini utamanya terdiri dari 5 tabel yang saling terhubung untuk mendukung proses IT Asset Management dan Software License Compliance.

---

## 1. Tabel `computers`
Tabel ini digunakan untuk menyimpan spesifikasi hardware, OS, dan identitas/jaringan dari setiap komputer yang dipindai (di-scan) oleh agen.

**Kolom:**
- `id` (PK, BigInt) : Primary Key.
- `hostname` (String, Unique) : Nama unik komputer.
- `os_name` (String, Nullable) : Nama OS (Contoh: Microsoft Windows 11 Pro).
- `os_version` (String, Nullable) : Versi OS (OS Build dsb).
- `os_architecture` (String, Nullable) : Arsitektur OS (Contoh: 64-bit).
- `os_license_status` (String, Nullable) : Status Aktivasi OS (Contoh: Licensed, Grace Period).
- `os_partial_key` (String, Nullable) : Kunci produk parsial OS.
- `processor` (String, Nullable) : Spesifikasi Processor.
- `ram_gb` (Integer, Nullable) : Kapasitas RAM dalam GB.
- `disk_total_gb` (Integer, Nullable) : Total penyimpanan disk dalam GB.
- `disk_free_gb` (Integer, Nullable) : Sisa penyimpanan disk dalam GB.
- `ip_address` (String, Nullable) : IP Address saat ini.
- `mac_address` (String, Nullable) : MAC Address perangkat.
- `serial_number` (String, Nullable) : Nomor seri device/motherboard.
- `manufacturer` (String, Nullable) : Produsen (Contoh: HP, Dell, Lenovo).
- `model` (String, Nullable) : Model komputer (Contoh: Latitude 5420).
- `location` (String, Nullable) : Info meta mengenai lokasi PC.
- `last_seen_at` (Timestamp, Nullable) : Waktu terakhir kali komputer direkam oleh agen.
- `created_at` & `updated_at` (Timestamps)

**Relasi:**
- `hasMany` (1:N) ke **`software_discoveries`** (Daftar aplikasi di PC ini)
- `hasMany` (1:N) ke **`compliance_reports`** (Laporan kepatuhan / history scan dari PC ini)

---

## 2. Tabel `software_catalogs`
Tabel ini bertindak sebagai master data (Kamus) dari semua perangkat lunak (software) yang dikenali dalam jaringan. Di tabel inilah admin memberi status izin pemakaian dan spesifikasi jenis software.

**Kolom:**
- `id` (PK, BigInt) : Primary Key.
- `normalized_name` (String, Unique) : Nama bersih software yang disatukan.
- `category` (Enum) : Kategori Lisensi (`"Freeware"`, `"Commercial"`, `"OpenSource"`). Default: `Freeware`.
- `status` (Enum) : Status Izin (`"Whitelist"`, `"Blacklist"`, `"Unreviewed"`). Default: `Unreviewed`.
- `description` (Text, Nullable) : Keterangan dari software tersebut.
- `created_at` & `updated_at` (Timestamps)

**Relasi:**
- `hasMany` (1:N) ke **`software_discoveries`** (PC siapa saja yang meng-install katalog ini)
- `hasMany` (1:N) ke **`license_inventories`** (Daftar lisensi komersial kepemilikan kantor)

---

## 3. Tabel `software_discoveries`
Tabel pivot / transaksional yang menghubungkan mana komputer dan katalog software apa yang dipasang. Selalu di-*refresh* / *re-sync* ulang ketika komputer melakukan pengecekan scan.

**Kolom:**
- `id` (PK, BigInt) : Primary Key.
- `computer_id` (FK, BigInt) : (Menunjuk ke `computers->id`, On Delete Cascade).
- `catalog_id` (FK, BigInt, Nullable) : (Menunjuk ke `software_catalogs->id`, Null On Delete).
- `raw_name` (String) : Nama software spesifik mentah dari registri.
- `version` (String, Nullable) : Versi software terkait.
- `vendor` (String, Nullable) : Vendor / Pembuat (`Microsoft`, `Adobe` dsb).
- `install_date` (Date, Nullable) : Perkiraan kapan diinstall dari sisi OS PC klien.
- `created_at` & `updated_at` (Timestamps)

**Indeks:**
- `UNIQUE uq_discovery_computer_software` pada kombinasi `(computer_id, raw_name, version)` untuk mencegah rekaman duplikat instalasi software yang sama pada komputer yang sama.

**Relasi:**
- `belongsTo` ke **`computers`**
- `belongsTo` ke **`software_catalogs`**

---

## 4. Tabel `license_inventories`
Tabel untuk mencatat data pembelian / ketersediaan lisensi software berbayar untuk pencocokan kepatuhan (Compliance).

**Kolom:**
- `id` (PK, BigInt) : Primary Key.
- `catalog_id` (FK, BigInt) : (Menunjuk ke `software_catalogs->id`, On Delete Cascade).
- `license_key` (Text, Nullable) : File sensitif / Kunci Lisensi (Dienkripsi otomatis via model attribute pada sistem laravel).
- `purchase_order_number` (String, Nullable) : Nomor Nota / Faktur.
- `quota_limit` (Integer) : Total jumlah seat/device yang diijinkan menggunakan 1 record pembelian ini (Default `1`).
- `purchase_date` (Date, Nullable) : Tanggal dibeli.
- `expiry_date` (Date, Nullable) : Tanggal ekspirasi (Jika berlangganan).
- `price_per_unit` (Decimal 15,2, Nullable) : Harga Beli.
- `notes` (Text, Nullable) : Catatan tambahan.
- `proof_image` (String) : Lokasi file / link gambar kuitansi-bukti fisik.
- `created_at` & `updated_at` (Timestamps)

**Relasi:**
- `belongsTo` ke **`software_catalogs`**

---

## 5. Tabel `compliance_reports`
Tabel yang mencatat data kepatuhan tingkat granular per-software untuk setiap komputer hasil scan. Menggantikan rekam jejak (snapshot) per-komputer untuk memberikan pelacakan lisensi dan instalasi yang lebih akurat dan terperinci.

**Kolom:**
- `id` (PK, BigInt) : Primary Key.
- `computer_id` (FK, BigInt) : (Menunjuk ke `computers->id`, On Delete Cascade).
- `software_catalog_id` (FK, BigInt) : (Menunjuk ke `software_catalogs->id`, On Delete Cascade).
- `software_name` (String) : Nama spesifik software dari discovery/registry.
- `software_version` (String, Nullable) : Versi software terkait.
- `status` (String) : Status Kepatuhan (`"Berlisensi"`, `"Tidak Berlisensi"`, `"Grace Period"`).
- `keterangan` (String) : Keterangan detail terkait status lisensi (misal: "Kuota lisensi penuh").
- `license_inventory_id` (FK, BigInt, Nullable) : (Menunjuk ke `license_inventories->id`, Set Null). Menyimpan relasi jika software memiliki entri/pembelian lisensi berbayar tertentu.
- `detected_at` (Timestamp) : Waktu software dideteksi di mesin.
- `scanned_at` (Timestamp) : Waktu proses analisis kepatuhan (Scan / Job) dieksekusi.
- `total_software_installed`, `unlicensed_count`, `blacklisted_count`, `violation_details` : Kolom peninggalan dari skema laporan per-komputer lama (Legacy).
- `created_at` & `updated_at` (Timestamps)

**Indeks:**
- `UNIQUE computer_software_unique` pada kombinasi `(computer_id, software_catalog_id)` untuk memastikan 1 komputer hanya memiliki 1 catatan compliance report per entri katalog software yang terdeteksi.

**Relasi:**
- `belongsTo` ke **`computers`**
- `belongsTo` ke **`software_catalogs`**
- `belongsTo` ke **`license_inventories`**

---

## 6. Tabel Akses Kontrol (Spatie Permission)
Sistem ini menggunakan pustaka `spatie/laravel-permission` untuk mengatur struktur peran pengguna secara dinamis. Terdapat beberapa tabel pendukung utama:
- **`roles`**: Menyimpan daftar peran seperti `admin`, `viewer`.
- **`permissions`**: Menyimpan daftar izin spesifik seperti `manage licenses`, `view reports`.
- **`model_has_roles`**: Pivot yang mengikat pengguna (`User`) dengan peran (contoh: User A adalah Admin).
- **`role_has_permissions`**: Pivot yang mengikat peran dengan banyak izin.
- **`model_has_permissions`**: Pivot langsung antara User ke Izin spesifik (jarang dipakai jika pola utamanya melalui roles).
