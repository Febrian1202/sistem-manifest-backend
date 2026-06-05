# Planning: Fitur Activity Log (Audit Trail)

> **Tanggal:** 5 Juni 2026  
> **Proyek:** Sistem Manifest — USN Kolaka  
> **Status:** 📋 Draft / Menunggu Persetujuan

---

## 1. Latar Belakang & Analisis Kondisi Saat Ini

Sistem Manifest saat ini **tidak memiliki mekanisme audit trail** untuk mencatat aktivitas pengguna. Ketika terjadi perubahan data — seperti penambahan lisensi, penghapusan komputer, perubahan akun, atau reset password — tidak ada catatan mengenai **siapa** yang melakukan perubahan, **kapan**, dan **apa** yang berubah.

### Masalah yang Muncul Tanpa Audit Trail

- ❌ **Tidak ada akuntabilitas** — Jika ada kesalahan data (misal: lisensi terhapus), tidak bisa dilacak siapa pelakunya
- ❌ **Tidak ada pengawasan** — Pimpinan tidak bisa memonitor aktivitas admin secara transparan
- ❌ **Risiko keamanan** — Aksi sensitif seperti akses license key, reset password, dan penghapusan data tidak tercatat
- ❌ **Tidak ada forensik** — Jika terjadi insiden keamanan, tidak ada bukti digital yang bisa ditelusuri

### Infrastruktur yang Sudah Tersedia

- ✅ **Multi-user & RBAC** — `spatie/laravel-permission` dengan role `admin` dan `pimpinan`
- ✅ **Ekosistem Spatie** — Sudah familiar dengan package Spatie, sehingga `spatie/laravel-activitylog` v5 cocok sebagai solusi
- ✅ **PHP 8.5** — Memenuhi requirement `spatie/laravel-activitylog` v5 (PHP 8.4+, Laravel 12+)
- ✅ **Layout & Sidebar** — Infrastruktur UI sudah siap untuk menambahkan halaman baru

---

## 2. Tujuan Fitur

Membangun fitur **Activity Log (Audit Trail)** yang memungkinkan:

1. **Pencatatan otomatis** setiap aksi penting yang dilakukan pengguna pada entitas sistem
2. **Halaman Log Aktivitas** yang bisa diakses oleh admin dan pimpinan untuk memonitor aktivitas
3. **Pencarian & Filter** berdasarkan user, jenis aksi, entitas, dan rentang waktu
4. **Widget ringkasan** di dashboard untuk menampilkan aktivitas terbaru

---

## 3. Cakupan Pencatatan (Scope)

### 3.1. Aksi yang Dicatat

Berikut adalah daftar aksi yang akan dicatat beserta tingkat prioritasnya:

#### Modul Akun Pengguna (`User`)

| Aksi                        | Deskripsi Log                                                  | Prioritas |
| --------------------------- | -------------------------------------------------------------- | --------- |
| Tambah akun                 | "Menambahkan akun pengguna **{nama}** dengan role **{role}**"  | 🔴 Tinggi |
| Edit akun                   | "Mengubah data akun **{nama}**" + detail perubahan (old → new) | 🔴 Tinggi |
| Hapus akun                  | "Menghapus akun pengguna **{nama}**"                           | 🔴 Tinggi |
| Reset password (oleh admin) | "Mereset password akun **{nama}**"                             | 🔴 Tinggi |
| Ganti password sendiri      | "Mengganti password sendiri"                                   | 🟡 Sedang |

#### Modul Lisensi (`LicenseInventory`)

| Aksi              | Deskripsi Log                                             | Prioritas |
| ----------------- | --------------------------------------------------------- | --------- |
| Tambah lisensi    | "Menambahkan lisensi **{software}** (PO: {po_number})"    | 🔴 Tinggi |
| Edit lisensi      | "Mengubah data lisensi **{software}**" + detail perubahan | 🔴 Tinggi |
| Hapus lisensi     | "Menghapus lisensi **{software}** (PO: {po_number})"      | 🔴 Tinggi |
| Akses license key | "Melihat license key lisensi **{software}**"              | 🔴 Tinggi |

#### Modul Komputer (`Computer`)

| Aksi                  | Deskripsi Log                                              | Prioritas |
| --------------------- | ---------------------------------------------------------- | --------- |
| Edit komputer         | "Mengubah data komputer **{hostname}**" + detail perubahan | 🟡 Sedang |
| Hapus komputer        | "Menghapus komputer **{hostname}** dari sistem"            | 🔴 Tinggi |
| Request scan (satuan) | "Meminta scan ulang komputer **{hostname}**"               | 🟢 Rendah |
| Request scan semua    | "Meminta scan ulang ke **{jumlah}** komputer"              | 🟡 Sedang |

#### Modul Katalog Software (`SoftwareCatalog`)

| Aksi                   | Deskripsi Log                                                        | Prioritas |
| ---------------------- | -------------------------------------------------------------------- | --------- |
| Update status/kategori | "Mengubah status katalog **{software}** dari **{old}** ke **{new}**" | 🟡 Sedang |

#### Modul Kepatuhan & Laporan

| Aksi                     | Deskripsi Log                                            | Prioritas |
| ------------------------ | -------------------------------------------------------- | --------- |
| Jalankan compliance scan | "Menjalankan pemeriksaan kepatuhan untuk semua komputer" | 🟡 Sedang |
| Export laporan           | "Mengekspor laporan **{jenis}** format **{format}**"     | 🟢 Rendah |

#### Modul Autentikasi

| Aksi           | Deskripsi Log                                   | Prioritas |
| -------------- | ----------------------------------------------- | --------- |
| Login berhasil | "Login berhasil"                                | 🟡 Sedang |
| Login gagal    | "Percobaan login gagal untuk email **{email}**" | 🟡 Sedang |
| Logout         | "Logout dari sistem"                            | 🟢 Rendah |

### 3.2. Aksi yang TIDAK Dicatat

- Navigasi halaman (page views)
- Pencarian dan filter data
- Aksi read-only (melihat detail data tanpa perubahan, kecuali akses license key)

---

## 4. Arsitektur & File yang Akan Dibuat/Dimodifikasi

### 4.1. Dependensi Baru

#### Instalasi Package

```bash
composer require spatie/laravel-activitylog:"^5.0"
```

Package ini akan otomatis membuat:

- Migration untuk tabel `activity_log`
- Config file `config/activitylog.php`

#### Publish & Migrate

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

---

### 4.2. Backend (Model, Controller, Route)

#### [MODIFIKASI] Model-model yang Ditambahkan Trait `LogsActivity`

Trait `LogsActivity` dari Spatie akan ditambahkan ke model-model berikut untuk **mencatat otomatis event `created`, `updated`, dan `deleted`**:

**`app/Models/User.php`**

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Akun pengguna {$this->name} telah di-{$eventName}");
    }
}
```

**`app/Models/LicenseInventory.php`**

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

// Tambahkan trait LogsActivity
// logOnly: field yang relevan, KECUALI license_key (sensitif)
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['catalog_id', 'purchase_order_number', 'quota_limit', 'purchase_date', 'expiry_date', 'price_per_unit', 'notes'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

**`app/Models/Computer.php`**

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['hostname', 'location', 'ip_address', 'os_name', 'os_license_status'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

**`app/Models/SoftwareCatalog.php`**

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['status', 'category', 'normalized_name'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

#### [BARU] `app/Http/Controllers/ActivityLogController.php`

Controller untuk halaman log aktivitas:

| Method    | HTTP | Route            | Fungsi                                   |
| --------- | ---- | ---------------- | ---------------------------------------- |
| `index()` | GET  | `/activity-logs` | Menampilkan halaman daftar log aktivitas |

Fitur:

- Pencarian berdasarkan deskripsi atau nama user
- Filter berdasarkan user (dropdown)
- Filter berdasarkan jenis aksi (`created`, `updated`, `deleted`, atau custom event)
- Filter berdasarkan entitas (User, LicenseInventory, Computer, SoftwareCatalog)
- Filter berdasarkan rentang tanggal
- Pagination

#### [MODIFIKASI] Controller yang Ditambahkan Log Manual

Beberapa aksi tidak bisa dicatat secara otomatis oleh trait `LogsActivity` karena bukan operasi CRUD standar pada model. Aksi-aksi ini perlu dicatat secara manual menggunakan helper `activity()`:

**`app/Http/Controllers/AccountController.php`**

```php
// Di method resetPassword():
activity()
    ->performedOn($user)
    ->causedBy(auth()->user())
    ->log("Mereset password akun {$user->name}");

// Di method changePassword():
activity()
    ->causedBy(auth()->user())
    ->log("Mengganti password sendiri");
```

**`app/Http/Controllers/LicenseDataController.php`**

```php
// Di method getKey():
activity()
    ->performedOn($license)
    ->causedBy(auth()->user())
    ->withProperties(['software' => $license->catalog->normalized_name ?? 'N/A'])
    ->log("Melihat license key");
```

**`app/Http/Controllers/ComputerDataController.php`**

```php
// Di method requestScan():
activity()
    ->performedOn($computer)
    ->causedBy(auth()->user())
    ->log("Meminta scan ulang komputer {$computer->hostname}");

// Di method requestScanAll():
activity()
    ->causedBy(auth()->user())
    ->withProperties(['affected_count' => $updated])
    ->log("Meminta scan ulang ke {$updated} komputer");
```

**`app/Http/Controllers/ReportController.php`**

```php
// Di setiap method export (exportEksekutif, exportKomputer, dll):
activity()
    ->causedBy(auth()->user())
    ->withProperties(['report_type' => 'Eksekutif', 'format' => $format])
    ->log("Mengekspor laporan Eksekutif ({$format})");
```

**`app/Http/Controllers/AuthController.php`**

```php
// Di method login() — setelah Auth::attempt() berhasil:
activity()
    ->causedBy(auth()->user())
    ->log("Login berhasil");

// Di method login() — setelah Auth::attempt() gagal:
activity()
    ->withProperties(['email' => $request->email])
    ->log("Percobaan login gagal");

// Di method logout():
activity()
    ->causedBy(auth()->user())
    ->log("Logout dari sistem");
```

#### [MODIFIKASI] `routes/web.php`

```php
// Log Aktivitas (Admin & Pimpinan)
Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
```

---

### 4.3. Frontend (Blade Views)

#### [BARU] `resources/views/pages/admin/activity-logs.blade.php`

Halaman utama log aktivitas, mengikuti pattern dari halaman CRUD lainnya:

- **Header**: Judul "Log Aktivitas" dengan ikon
- **Bar Filter**:
    - Pencarian teks (cari berdasarkan deskripsi)
    - Dropdown filter user (siapa yang melakukan)
    - Dropdown filter entitas (User, Komputer, Lisensi, Katalog Software, Sistem)
    - Input rentang tanggal (dari — sampai)
    - Tombol "Cari"
- **Tabel Log**: Kolom Waktu, User, Aksi, Detail, Entitas Terkait
- **Pagination**: Standar Laravel

#### [BARU] `resources/views/components/activity-logs/table.blade.php`

Komponen tabel untuk menampilkan log aktivitas:

| Kolom       | Keterangan                                                               |
| ----------- | ------------------------------------------------------------------------ |
| **Waktu**   | Timestamp relatif (misal: "2 jam lalu") + tooltip timestamp absolut      |
| **Pelaku**  | Nama user yang melakukan aksi + badge role                               |
| **Aksi**    | Deskripsi singkat aksi (dari field `description`)                        |
| **Entitas** | Jenis dan nama entitas yang terkena aksi (misal: "Komputer — PC-LAB-01") |
| **Detail**  | Tombol expand/collapse untuk melihat perubahan detail (old → new values) |

Desain visual:

- Ikon aksi berwarna berdasarkan jenis: 🟢 Hijau (created), 🔵 Biru (updated), 🔴 Merah (deleted), ⚪ Abu-abu (lainnya)
- Baris untuk aksi sensitif (hapus, reset password, akses key) ditandai dengan indikator khusus

#### [MODIFIKASI] `resources/views/components/layout/side-bar.blade.php`

Menambahkan menu "Log Aktivitas" di sidebar, untuk role `admin` dan `pimpinan`:

```blade
@role('admin|pimpinan')
    {{-- Di bawah menu Manajemen Akun atau di section Pengaturan --}}
    <a href="/activity-logs" class="...">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>Log Aktivitas</span>
    </a>
@endrole
```

#### [MODIFIKASI] `resources/views/pages/admin/dashboard.blade.php` (Opsional)

Menambahkan widget "Aktivitas Terbaru" di dashboard:

- Menampilkan 5 aktivitas terakhir
- Setiap item menampilkan: waktu relatif, pelaku, dan deskripsi singkat
- Tombol "Lihat Semua →" yang mengarah ke `/activity-logs`

---

## 5. Alur Kerja (Flow)

### 5.1. Pencatatan Otomatis (via Trait LogsActivity)

```
User melakukan CRUD (create/update/delete) pada model →
Trait LogsActivity otomatis mencatat ke tabel `activity_log` →
Menyimpan: causer (siapa), subject (entitas apa), event, properties (old/new), timestamp
```

### 5.2. Pencatatan Manual (via Helper activity())

```
User melakukan aksi non-CRUD (reset password, akses key, login, dll) →
Controller memanggil activity()->log(...) →
Data disimpan ke tabel `activity_log` dengan konteks yang tepat
```

### 5.3. Melihat Log Aktivitas

```
Admin/Pimpinan membuka halaman "Log Aktivitas" →
Sistem menampilkan daftar log terbaru (diurutkan terbaru dulu) →
User bisa filter berdasarkan pelaku, entitas, rentang waktu →
User bisa klik baris untuk melihat detail perubahan (old → new)
```

---

## 6. Keamanan & Validasi

| Aspek                 | Implementasi                                                                                      |
| --------------------- | ------------------------------------------------------------------------------------------------- |
| **Akses halaman log** | Middleware `role:admin\|pimpinan` — kedua role bisa melihat                                       |
| **Data sensitif**     | License key **TIDAK** dicatat di properties log (hanya dicatat bahwa key diakses)                 |
| **Password**          | Password **TIDAK** dicatat di properties log (event hanya mencatat bahwa password diubah/direset) |
| **Imutabilitas**      | Log bersifat append-only — tidak ada UI untuk mengedit atau menghapus log                         |
| **Retensi**           | Log dibersihkan otomatis setelah 90 hari menggunakan command `activitylog:clean`                  |

---

## 7. Rencana Pengujian

### 7.1. Unit/Feature Test (Pest)

#### [BARU] `tests/Feature/ActivityLogTest.php`

| Test Case                                       | Deskripsi                                                   |
| ----------------------------------------------- | ----------------------------------------------------------- |
| `test_activity_logged_on_user_created`          | Log tercatat saat akun baru dibuat                          |
| `test_activity_logged_on_user_updated`          | Log tercatat saat akun diupdate, berisi old/new values      |
| `test_activity_logged_on_user_deleted`          | Log tercatat saat akun dihapus                              |
| `test_activity_logged_on_password_reset`        | Log tercatat saat admin reset password                      |
| `test_activity_logged_on_password_change`       | Log tercatat saat user ganti password sendiri               |
| `test_activity_logged_on_license_key_access`    | Log tercatat saat admin mengakses license key               |
| `test_password_not_stored_in_log_properties`    | Memverifikasi password TIDAK tersimpan di properties log    |
| `test_license_key_not_stored_in_log_properties` | Memverifikasi license key TIDAK tersimpan di properties log |
| `test_admin_can_view_activity_logs_page`        | Admin bisa mengakses halaman log aktivitas                  |
| `test_pimpinan_can_view_activity_logs_page`     | Pimpinan bisa mengakses halaman log aktivitas               |
| `test_activity_logs_page_supports_filtering`    | Filter berdasarkan user dan entitas berfungsi               |

### 7.2. Manual Testing

- [ ] Buat akun baru → log tercatat
- [ ] Edit akun → log tercatat dengan detail perubahan (old → new)
- [ ] Hapus akun → log tercatat
- [ ] Tambah lisensi → log tercatat
- [ ] Akses license key → log tercatat
- [ ] Reset password → log tercatat, password TIDAK tersimpan di detail
- [ ] Ganti password sendiri → log tercatat
- [ ] Login/logout → log tercatat
- [ ] Buka halaman Log Aktivitas → data muncul dengan benar
- [ ] Filter berdasarkan user, entitas, dan tanggal → berfungsi

---

## 8. Urutan Implementasi (Task Breakdown)

### Tahap 1: Package & Setup

1. Install `spatie/laravel-activitylog` v5
2. Publish migration dan config
3. Jalankan migration

### Tahap 2: Pencatatan Otomatis (Trait)

4. Tambahkan trait `LogsActivity` di model `User`
5. Tambahkan trait `LogsActivity` di model `LicenseInventory`
6. Tambahkan trait `LogsActivity` di model `Computer`
7. Tambahkan trait `LogsActivity` di model `SoftwareCatalog`
8. Konfigurasi `getActivitylogOptions()` di setiap model (exclude field sensitif)

### Tahap 3: Pencatatan Manual (Controller)

9. Tambahkan log manual di `AccountController` (resetPassword, changePassword)
10. Tambahkan log manual di `LicenseDataController` (getKey)
11. Tambahkan log manual di `ComputerDataController` (requestScan, requestScanAll)
12. Tambahkan log manual di `ReportController` (semua method export)
13. Tambahkan log manual di `AuthController` (login sukses, login gagal, logout)

### Tahap 4: Frontend — Halaman Log Aktivitas

14. Buat `ActivityLogController.php` dengan method `index()`
15. Tambahkan route di `web.php`
16. Buat `resources/views/pages/admin/activity-logs.blade.php`
17. Buat `resources/views/components/activity-logs/table.blade.php`
18. Tambahkan menu "Log Aktivitas" di `side-bar.blade.php`

### Tahap 5: Dashboard Widget (Opsional)

19. Tambahkan widget "Aktivitas Terbaru" di halaman dashboard

### Tahap 6: Maintenance & Testing

20. Konfigurasi cleanup schedule di `routes/console.php` (`activitylog:clean` setiap minggu)
21. Buat `tests/Feature/ActivityLogTest.php`
22. Jalankan test dan perbaiki bug
23. Format kode dengan Pint

---

## 9. Estimasi Waktu

| Tahap                                   | Estimasi     |
| --------------------------------------- | ------------ |
| Tahap 1: Package & Setup                | ~10 menit    |
| Tahap 2: Pencatatan Otomatis (Trait)    | ~20 menit    |
| Tahap 3: Pencatatan Manual (Controller) | ~30 menit    |
| Tahap 4: Frontend Halaman Log           | ~45 menit    |
| Tahap 5: Dashboard Widget               | ~15 menit    |
| Tahap 6: Maintenance & Testing          | ~30 menit    |
| **Total**                               | **~2.5 jam** |

---

## 10. Pertanyaan & Keputusan yang Perlu Dikonfirmasi

> [!IMPORTANT]
> Mohon konfirmasi poin-poin berikut sebelum implementasi dimulai:

1. **Akses halaman log** — Apakah role `pimpinan` juga boleh melihat halaman Log Aktivitas, atau hanya `admin`?

=> cukup admin

2. **Login/logout logging** — Apakah perlu mencatat aktivitas login dan logout? Ini akan menambah volume log cukup signifikan tetapi berguna untuk forensik keamanan.

=> tidak perlu

3. **Export laporan logging** — Apakah perlu mencatat setiap kali user mengekspor laporan (PDF/Excel)? Atau cukup aksi CRUD saja?

=> cukup aksi CRUD saja

4. **Retensi data** — Berapa lama log aktivitas disimpan sebelum otomatis dihapus?
    - **(A)** 30 hari
    - **(B)** 90 hari (rekomendasi)
    - **(C)** 6 bulan
    - **(D)** Tidak pernah dihapus (⚠️ tabel akan membesar seiring waktu)

=> 90 hari

5. **Widget dashboard** — Apakah perlu menampilkan ringkasan "Aktivitas Terbaru" di halaman dashboard utama?

=> tidak perlu
