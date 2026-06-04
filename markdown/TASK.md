# Task: Secure License Key Reveal via AJAX

## Latar Belakang

Implementasi saat ini merender `$license->license_key` (decrypted) langsung ke dalam HTML dan hanya menyembunyikannya secara visual dengan Alpine.js `x-show`. Ini adalah **kelemahan keamanan** karena key bisa dilihat melalui View Page Source atau DevTools tanpa perlu menekan tombol toggle.

**Solusi**: Pindahkan pengambilan key ke endpoint AJAX yang hanya dipanggil saat user menekan tombol "Tampilkan". Key tidak pernah ada di HTML sampai user secara eksplisit memintanya.

## Constraints

- Jangan ubah logika enkripsi di Model (`LicenseInventory`)
- Endpoint harus dilindungi middleware `auth` dan `role:admin`
- Pertahankan class Tailwind dan struktur UI yang sudah ada
- Gunakan Alpine.js untuk interaksi client-side (sudah tersedia di project)

## Tasks

### T1 — Buat endpoint API untuk mengambil license key

Tambahkan method baru di `LicenseDataController`:

```php
public function getKey(LicenseInventory $license)
```

- Return JSON: `{ "key": "ABCD-1234-EFGH-5678" }`
- Jika `license_key` null/kosong, return `{ "key": null }`
- Hanya bisa diakses oleh role `admin`

**File**: `app/Http/Controllers/LicenseDataController.php`

### T2 — Daftarkan route baru

Tambahkan route di dalam group `role:admin`:

```
GET /licenses/{license}/key → LicenseDataController@getKey → licenses.key
```

**File**: `routes/web.php` (baris ~63, di dalam group `middleware(['role:admin'])`)

### T3 — Perbarui UI detail lisensi

Ganti blok license key di halaman detail (baris ~79-91) dengan komponen Alpine.js yang:

1. **Default state**: Tampilkan `masked_license_key` (server-rendered, aman)
2. **Tombol Toggle (eye icon)**: Saat diklik, fetch key dari endpoint `T2`
3. **Tombol Copy**: Salin key ke clipboard, tampilkan feedback "Tersalin!"
4. **Auto-hide**: Setelah 30 detik, sembunyikan kembali key secara otomatis
5. **Loading state**: Tampilkan spinner/animasi saat fetch sedang berjalan
6. **Error handling**: Tampilkan pesan jika fetch gagal (misal: unauthorized)

Contoh state Alpine.js:

```js
x-data="{
    showKey: false,
    realKey: null,
    loading: false,
    copied: false,
    error: null,
    async revealKey() { ... },
    hideKey() { ... },
    async copyKey() { ... }
}"
```

**File**: `resources/views/pages/admin/license/show.blade.php`

## Files to Modify

| File | Aksi |
|------|------|
| `app/Http/Controllers/LicenseDataController.php` | Tambah method `getKey()` |
| `routes/web.php` | Tambah route `licenses/{license}/key` |
| `resources/views/pages/admin/license/show.blade.php` | Refactor blok license key (baris 79-91) |

## Definition of Done

- [ ] Key **tidak** ada di HTML source saat halaman pertama kali dimuat
- [ ] Klik tombol eye → fetch key via AJAX → tampilkan key asli
- [ ] Tombol copy berfungsi dan menampilkan feedback visual
- [ ] Key otomatis tersembunyi kembali setelah 30 detik
- [ ] Endpoint dilindungi middleware auth + role:admin
- [ ] Tidak ada breaking change pada fitur yang sudah ada
