# Instruksi Pekerjaan: Perbaikan Frontend & Asset Kesiapan Production

## Deskripsi Tugas
Tugas ini bertujuan untuk menyelesaikan masalah pada Poin 12 di Laporan Audit Kesiapan Production terkait Frontend dan Asset. Tujuannya adalah memastikan aplikasi berjalan dengan baik di kondisi internet tidak stabil (dengan memindahkan CDN ke lokal) serta merapikan antarmuka dan struktur *view*.

Terdapat 5 subtugas yang harus diselesaikan pada pengerjaan ini:

### 1. Migrasi Dependencies CDN Eksternal ke Lokal (NPM)
Beberapa library saat ini diload secara dinamis lewat CDN `cdn.jsdelivr.net` di layout utama aplikasi. Ini berbahaya jika server/kampus kehilangan koneksi internet, karena *chart* atau logika interaksi (Alpine) tidak akan jalan.
*   **Tugas:** 
    *   Hapus referensi CDN `<script>` dari `resources/views/components/layout/app.blade.php`.
    *   Pastikan Alpine.js, Chart.js, dan ApexCharts diimpor dengan benar di dalam file `resources/js/app.js` (atau sesuaikan dengan konfigurasi Vite).
    *   *Catatan:* Sepertinya aplikasi ini menggunakan Alpine.js melalui script CDN di dalam `<head>`. Silakan bundle menggunakan cara standar Laravel Vite: `import Alpine from 'alpinejs'; window.Alpine = Alpine; Alpine.start();`. Pastikan juga plugin/library lain diinisialisasi dengan tepat jika diperlukan di *client-side*.

### 2. Terjemahan File Localization (Bahasa Indonesia)
Saat ini file di `lang/id/` (contohnya `auth.php`, `pagination.php`, `passwords.php`, `validation.php`) masih menggunakan bahasa Inggris bawaan Laravel.
*   **Tugas:** Terjemahkan pesan-pesan error dan respons di dalam folder `lang/id/` ke dalam bahasa Indonesia baku dan mudah dipahami.
    *   Contoh di `auth.php`: `'failed' => 'These credentials do not match our records.'` diubah menjadi `'failed' => 'Kredensial ini tidak cocok dengan catatan kami.'`

### 3. Perbaikan Sidebar Link Hardcoded
Navigasi pada sidebar aplikasi tidak menggunakan *route helper* bawaan Laravel, melainkan menggunakan teks rute *hardcoded*.
*   **File:** `resources/views/components/layout/side-bar.blade.php`
*   **Tugas:** Cari semua tag `<a>` yang atribut `href`-nya menggunakan *path string literal* (misalnya `href="/dashboard"`, `href="/computers"`, dll), lalu ubah menggunakan helper Laravel `route()`. 
    *   Contoh: `href="{{ route('dashboard') }}"`.
    *   Untuk path lain, pastikan Anda merujuk ke nama *route* yang benar di `routes/web.php`.

### 4. Perbaikan Typo Alpine.js
*   **File:** `resources/views/components/layout/app.blade.php` (atau file layout lain yang terkait).
*   **Tugas:** Temukan teks typo `x-trasition.opacity` dan perbaiki menjadi tulisan yang valid di Alpine.js yaitu `x-transition.opacity`.

### 5. Menghapus Package NPM yang Tidak Terpakai
*   **File:** `package.json`
*   **Tugas:** Temukan dan hapus dependensi `@fontsource/inter` dari blok `dependencies` atau `devDependencies` karena font yang digunakan adalah Poppins (`@fontsource/poppins`). Jalankan perintah `npm uninstall @fontsource/inter` agar `package-lock.json` juga ter-update.

---

## Alur Kerja (Workflow)
1. Baca dengan saksama seluruh instruksi subtugas di atas.
2. Buatlah *branch* baru, misalnya `feature/fix-frontend-assets`.
3. Jalankan `npm install` dan `npm run build` setelah mengubah `app.js` atau menghapus package di langkah 1 & 5.
4. Lakukan modifikasi kode PHP, Blade, dan JS sesuai panduan.
5. Jalankan Pint (`./vendor/bin/pint`) untuk memastikan *coding style* PHP rapi.
6. Commit seluruh perubahan, push, dan buka Pull Request. 

Selamat bekerja!
