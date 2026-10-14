# Design Specification: Akses Lisensi Alokasi Fakultas untuk Kepala Lab & Staff Lab

**Date:** 2026-10-14  
**Status:** In Review / Ready for Builder Check  
**Author:** AI Agent & Human Partner  

---

## 1. Intent & The Why

### Latar Belakang (Context & Problem)
Sistem Manifest saat ini mengevaluasi status kepatuhan perangkat berdasarkan jumlah total lisensi yang dimiliki universitas dibandingkan dengan jumlah perangkat yang terdeteksi menginstal software tersebut melalui agent scanner. Sistem tidak memantau status aktivasi internal OS/software (misal: apakah Windows berstatus *activated* dengan product key atau *trial/unlicensed*).

Dalam operasional harian di laboratorium komputer:
- Perangkat komputer lab yang baru diinstal ulang atau belum diaktivasi membutuhkan input product key/license key agar beroperasi legal dan optimal.
- Saat ini, informasi lisensi dan pembukaan *license key* hanya dapat diakses oleh role `admin` (dan sebagian ringkasan non-key oleh `pimpinan`).
- Akibatnya, setiap kali Kepala Lab (`kepala_lab`) atau Staf Lab (`staff_lab`) perlu mengaktivasi software di komputernya, mereka harus menghubungi Admin universitas secara manual hanya untuk meminta product key. Hal ini menimbulkan hambatan operasional dan komunikasi bolak-balik yang tidak efisien.

### Sasaran (Intent)
Memberikan akses langsung (read-only) kepada Kepala Lab dan Staf Lab untuk melihat daftar lisensi yang secara resmi dialokasikan kepada fakultas mereka, termasuk kemampuan membuka (*reveal*) dan menyalin (*copy to clipboard*) product key lisensi terkait secara terkontrol dan aman dengan pencatatan audit trail.

---

## 2. Goals, Non-Goals, and Anti-Goals

### Goals
1. **Menu Khusus di Sidebar:** Menambahkan menu "Lisensi Fakultas" di sidebar navigasi untuk role `kepala_lab` dan `staff_lab`.
2. **Scoping Berdasarkan Fakultas Pengguna:** Halaman lisensi lab hanya menampilkan lisensi yang memiliki relasi `LicenseAllocation` berstatus `active` untuk fakultas pengguna (`auth()->user()->faculty_id ?? auth()->user()->laboratory?->faculty_id`).
3. **Penyajian Data Relevan:** Menampilkan nama software/katalog, kategori software, kuota yang dialokasikan ke fakultas, masa berlaku lisensi, sisa hari aktif, catatan aktivasi, dan masked license key (`XXXX-XXXX-****-****`).
4. **Reveal & Copy License Key Terkendali:** Menyediakan aksi "Lihat Key" yang memanggil endpoint terproteksi via AJAX, menampilkan key asli selama 30 detik (auto-hide), dan tombol satu-klik "Salin ke Clipboard".
5. **Audit Trail & Keamanan:** Setiap aksi pembukaan key dicatat dalam `activity_log` (causer, waktu, software, fakultas) serta dibatasi dengan rate limit (10 request/menit).

### Non-Goals
1. **Mutasi Data Lisensi:** Kepala Lab dan Staf Lab TIDAK BISA menambah, mengedit, menghapus, atau mengalokasikan lisensi. Manajemen lisensi dan alokasi kuota tetap 100% hak prerogatif `admin`.
2. **Akses Data Finansial:** Kolom sensitif seperti harga beli per unit (`price_per_unit`), total biaya, dan bukti nota/PO (`proof_image`) TIDAK DITAMPILKAN kepada staf/kepala lab.
3. **Akses Lintas Fakultas:** Pengguna dari Fakultas A tidak boleh bisa melihat lisensi atau alokasi yang hanya ditujukan untuk Fakultas B.
4. **Otomatisasi Aktivasi Remote:** Sistem tidak melakukan aktivasi jarak jauh via jaringan ke PC lab; fitur ini menyediakan key agar staf lab dapat melakukan aktivasi sendiri langsung di perangkat.

### Anti-Goals
1. **Jangan Mengekspos Key Tanpa Masking:** Jangan pernah merender plain text `license_key` secara langsung ke dalam view HTML awal (Blade). Key harus tetap di-fetch on-demand melalui endpoint aman.
2. **Jangan Mengaburkan Tanggung Jawab:** Jangan izinkan pengungkapan key tanpa log aktivitas. Siapa pun yang membuka key harus tercatat identitas dan waktunya.

---

## 3. Constraints & Architectural Alignment

- **Framework:** Laravel 12, PHP 8.4/8.5.
- **Frontend Stack:** Blade, Tailwind CSS 4, Alpine.js, FontAwesome icons.
- **Enkripsi:** `LicenseInventory.license_key` disimpan dengan cast `encrypted` Laravel.
- **Multi-Tenant / Scoping:** Menggunakan relasi fakultas pengguna:
  - User memiliki relasi `laboratory` (yang memiliki `faculty_id`) atau langsung `faculty_id`.
  - Lisensi dialokasikan ke fakultas melalui model `LicenseAllocation` (`faculty_id`, `license_inventory_id`, `status = 'active'`).
- **Authorization & Guard:** Guard `web`, Spatie permission (`role:kepala_lab|staff_lab`). Endpoint key memverifikasi kepemilikan alokasi fakultas aktif sebelum mengembalikan key.
- **Logging:** Spatie Activity Log (`activity()->performedOn($license)->causedBy(auth()->user())->...`).
- **Testing:** Pest PHP pada SQLite `:memory:`.

---

## 4. User Interaction & Interface Decisions (How It Decided)

Berdasarkan diskusi dan review mockup visual (Opsi A terpilih):
1. **Navigasi:**
   - Untuk `kepala_lab`: Menu item "Lisensi Fakultas" dengan ikon `fa-key` diletakkan di bagian "Menu PJ Lab" di bawah "Inventaris Lab".
   - Untuk `staff_lab`: Menu item "Lisensi Fakultas" dengan ikon `fa-key` diletakkan di bagian "Menu Staff Lab" di bawah "Komputer & Aset".
2. **Halaman `/lab/licenses`:**
   - Menampilkan header dengan nama fakultas pengguna (misal: "Lisensi Fakultas Teknik").
   - Filter/Search input untuk mencari software berdasarkan nama software katalog.
   - Tabel responsif berisi:
     - Software & Edisi
     - Kategori
     - Kuota Alokasi Fakultas (Unit)
     - Masa Berlaku & Status
     - Kolom Product Key (Masked + Tombol Eye Reveal + Tombol Copy)
     - Catatan/Instruksi khusus dari Admin (jika ada).
3. **Modal / Inline Reveal Alpine.js:**
   - Menggunakan komponen Alpine.js yang memanggil POST `/lab/licenses/{license}/key`.
   - Menampilkan animasi loading singkat.
   - Jika sukses, menampilkan key dengan timer otomatis tertutup kembali setelah 30 detik.
   - Tombol copy berubah visual (ikon check) selama 2 detik saat berhasil disalin.

---

## 5. Technical Design Details

### 5.1 Routing (`routes/web.php`)
```php
Route::middleware(['auth', 'role:kepala_lab|staff_lab'])->group(function () {
    // Inventaris Lab yang sudah ada
    Route::get('/lab/inventory', [LabInventoryController::class, 'index'])->name('lab.inventory.index');
    Route::get('/lab/inventory/{computer}', [LabInventoryController::class, 'show'])->name('lab.inventory.show');

    // Lisensi Alokasi Fakultas (Baru)
    Route::get('/lab/licenses', [LabLicenseController::class, 'index'])->name('lab.licenses.index');
    Route::post('/lab/licenses/{license}/key', [LabLicenseController::class, 'getKey'])
        ->name('lab.licenses.key')
        ->middleware('throttle:10,1');
});
```

### 5.2 Controller: `App\Http\Controllers\LabLicenseController`
Tanggung jawab:
- `index(Request $request)`:
  - Mengambil data pengguna: `$user = $request->user();`
  - Mengambil ID fakultas pengguna: `$facultyId = $user->faculty_id ?? $user->laboratory?->faculty_id;`
  - Jika `$facultyId` null: render view dengan pesan peringatan bahwa akun belum terhubung dengan fakultas/lab.
  - Query ke `LicenseAllocation`:
    - Mengambil alokasi dengan `faculty_id = $facultyId` dan `status = 'active'`.
    - Eager load `licenseInventory.catalog`.
    - Filter pencarian nama katalog jika ada parameter `search`.
    - Paginate atau get collection terurut nama software.
  - Render view `resources/views/lab-licenses/index.blade.php`.
- `getKey(Request $request, LicenseInventory $license)`:
  - Mengambil `$facultyId = $request->user()->faculty_id ?? $request->user()->laboratory?->faculty_id;`
  - Validasi otorisasi: Periksa apakah ada alokasi aktif untuk fakultas ini:
    ```php
    $isAllocated = $license->allocations()
        ->where('faculty_id', $facultyId)
        ->where('status', 'active')
        ->exists();
    if (! $isAllocated) {
        abort(403, 'Lisensi ini tidak dialokasikan untuk fakultas Anda.');
    }
    ```
  - Catat log aktivitas:
    ```php
    activity()
        ->performedOn($license)
        ->causedBy($request->user())
        ->withProperties([
            'software' => $license->catalog->normalized_name ?? 'N/A',
            'faculty_id' => $facultyId,
            'role' => $request->user()->getRoleNames()->first(),
        ])
        ->log('Melihat license key alokasi fakultas');
    ```
  - Return JSON: `response()->json(['key' => $license->license_key]);`

### 5.3 View: `resources/views/lab-licenses/index.blade.php`
- Layout: `<x-layout.app>` dengan breadcrumb standar.
- Menggunakan komponen Alpine.js untuk state management per baris (loading, showKey, realKey, timer, copied, error).
- Desain Tailwind sesuai sistem desain yang ada (`bg-card`, `border-border`, dll.).

---

## 6. What's Left to the Builder
1. Detail komponen visual breadcrumbs dan styling tombol yang konsisten dengan halaman `lab-inventory/index.blade.php`.
2. Penulisan pengujian fitur (Feature Tests) di Pest untuk memastikan skenario positif (kepala lab dan staff lab bisa melihat lisensi fakultas mereka dan mengambil key) serta skenario negatif (staf dari fakultas lain tidak bisa mengakses key lisensi fakultas lain; role tanpa hak ditolak; rate limit berfungsi).
3. Pemutakhiran atau penyesuaian Golden Path Test jika relevan.
