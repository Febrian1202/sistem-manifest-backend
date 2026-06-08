# Instruksi Implementasi: Auto-Processing Freeware & Open Source

## Konteks Masalah
Berdasarkan data *production* (`software-catalog.json`), banyak perangkat lunak yang sudah pasti bersifat *freeware* atau *open-source* (seperti Google Chrome, VLC, Git, VS Code, Python, dll.) masuk ke dalam database dengan `status` `"Unreviewed"`. Hal ini membuat Administrator harus menyetujui (me-review) aplikasi-aplikasi yang sangat umum ini secara manual.

## Tujuan Tugas
Membuat sistem pengenalan otomatis (*auto-processing*) pada backend Laravel (khususnya saat data hasil scan diproses) untuk langsung mengkategorikan dan mengubah status aplikasi yang sudah jelas merupakan *freeware* agar tidak masuk ke antrean *review* manual.

## Panduan Implementasi untuk Model AI

Tugas Anda adalah mengimplementasikan fitur ini dengan langkah-langkah berikut:

### 1. Definisikan Master List Freeware/Open-Source
Tambahkan sebuah konfigurasi (misalnya membuat `config/software_whitelist.php`) atau properti konstan di dalam `app/Services/SoftwareFilterService.php` yang berisi daftar *keyword* atau *regex* dari software umum.
Contoh daftar berdasarkan *production data*:
- `Google Chrome`
- `VLC media player`
- `Git`
- `Microsoft Visual Studio Code`
- `Notepad++`
- `Python`
- `OBS Studio`
- `Brave`
- `Postman`
- `Telegram Desktop`
- `Discord`
- `Composer`

### 2. Modifikasi Service Pemrosesan (`SoftwareCatalogService` / `SoftwareFilterService`)
- Cari *method* yang menangani penyisipan data ke `SoftwareCatalog` (kemungkinan terletak di dalam `app/Jobs/ProcessScanResultJob.php` yang memanggil `app/Services/SoftwareCatalogService.php`).
- Sebelum data disimpan (`create` atau `firstOrCreate`), buat logika pengecekan:
  - Konversi nama software ke *lowercase*.
  - Jika nama tersebut mengandung salah satu dari *whitelist keyword*, secara otomatis *override* datanya:
    - `category` = `'Freeware'` (atau `'Open Source'`)
    - `status` = `'Approved'` / `'Whitelist'` (Sesuaikan dengan enum/konvensi yang dipakai sistem saat ini, pastikan bukan `'Unreviewed'`).

### 3. Contoh Logika
```php
// Di dalam class service yang sesuai
public function getAutoProcessedData(string $softwareName): array
{
    $whitelist = config('software_whitelist.freeware_keywords', []);
    $lowerName = strtolower($softwareName);

    foreach ($whitelist as $keyword) {
        if (str_contains($lowerName, strtolower($keyword))) {
            return [
                'category' => 'Freeware',
                'status' => 'Approved' // Ganti dengan status valid di sistem
            ];
        }
    }

    return [
        'status' => 'Unreviewed'
    ];
}
```

### 4. Pastikan Keamanan & Performa Job Queue
Karena proses pencocokan string (`str_contains`) akan dilakukan untuk setiap software dari payload scan agen, pastikan pencarian dilakukan se-efisien mungkin. Jangan sampai logika ini memperlambat proses eksekusi antrean (`laravel horizon` / `queue:listen`).

### 5. Buat Unit/Feature Test (Pest)
Sebagai validasi, buat/perbarui pengujian (*test*) di menggunakan PestPHP. Buat skenario di mana simulasi payload agen mengirimkan `"VLC media player"` dan `"Unknown Hack Tool"`.
- Pastikan `"VLC media player"` otomatis tersimpan sebagai *Freeware* dan *Approved*.
- Pastikan `"Unknown Hack Tool"` masuk sebagai *Unreviewed*.

---
**Catatan untuk AI Model:** Silakan mulai menginspeksi direktori `app/Services/` dan `app/Jobs/` untuk menemukan letak *injection point* yang paling sesuai dengan arsitektur saat ini sebelum menulis kode.
