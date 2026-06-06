# 📝 Instruksi Implementasi Optimasi Logging & Expiry Token Sanctum

Dokumen ini berisi panduan dan instruksi detail bagi AI Agent atau Developer untuk mengimplementasikan optimasi sistem Logging dan pengaturan waktu kadaluarsa (expiration) pada token Laravel Sanctum. Penyesuaian ini merupakan bagian dari checklist kesiapan produksi (Production Readiness).

---

## 🎯 Tujuan Utama
1. Mengubah mekanisme logging dari *single file* menjadi *daily rotating files* untuk mencegah ukuran file log yang membengkak di server produksi.
2. Mengubah level log dari `debug` menjadi `warning` agar hanya menangkap hal yang penting saja dan tidak membocorkan data sensitif.
3. Menambahkan batas masa berlaku (expiration) pada token agent untuk meningkatkan keamanan.

---

## 🛠️ Langkah-Langkah Pengerjaan

### 1. Optimasi Konfigurasi Logging (`.env`, `.env.example`, `.env.production`)

- [ ] Buka file `.env`, `.env.example`, dan `.env.production`.
- [ ] Sesuaikan bagian logging seperti berikut:
  ```env
  LOG_CHANNEL=stack
  LOG_STACK=daily
  LOG_LEVEL=warning
  LOG_DAILY_DAYS=14
  ```
  *Penjelasan:* Menggunakan `daily` akan menyimpan log per hari. `LOG_DAILY_DAYS=14` mengatur agar log otomatis dihapus (pruned) setelah 14 hari. Level `warning` mencegah pencatatan debug query atau dump.

### 2. Pengaturan Expiration Token Sanctum

- [ ] Buka file `config/sanctum.php`.
- [ ] Cari pengaturan `expiration` (sekitar baris ke-53). Nilai default-nya saat ini adalah `null`.
- [ ] Ubah nilainya menjadi:
  ```php
  'expiration' => 43200, // Token kedaluwarsa dalam 30 hari (dihitung dalam menit)
  ```
- [ ] Pastikan perubahan tersebut sudah sesuai dan script config tidak error.
- [ ] *(Opsional)* Jika ada pengujian/testing (`tests/Feature/AgentAuthTest.php` atau sejenisnya) yang mendadak gagal karena validitas token, pastikan untuk menyesuaikan waktu di dalam test.

---

## ✅ Kriteria Selesai (Acceptance Criteria)

Agent atau Developer dapat menganggap tugas ini **selesai** apabila:
1. Variabel *logging* (`LOG_STACK=daily`, dsb) di semua file `.env` sudah sesuai dengan panduan.
2. File `config/sanctum.php` memiliki expiration `43200`.
3. Menjalankan `php artisan test` sukses tanpa ada error terkait otentikasi.

Silakan jalankan perbaikan sesuai instruksi di atas atau buat Pull Request dan referensikan ke dokumen ini.
