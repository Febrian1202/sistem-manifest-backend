# Instruksi Pekerjaan: Pembaruan Konfigurasi Keamanan & Base App (Timezone, Sanctum, Horizon)

## Deskripsi Tugas
Tugas ini bertujuan untuk menyelesaikan beberapa isu konfigurasi dasar dan keamanan yang ditemukan pada saat audit *Production Readiness*. Semua perubahan ini berkaitan dengan konfigurasi level aplikasi yang sangat penting saat deploy ke production.

Terdapat tiga sub-tugas yang perlu Anda selesaikan:

### 1. Update Timezone Aplikasi
Saat ini, aplikasi berjalan pada timezone default (`UTC`). Karena aplikasi ini digunakan oleh USN Kolaka yang berada di zona waktu Waktu Indonesia Tengah (WITA), kita perlu menyesuaikannya.

*   **File:** `config/app.php`
*   **Tugas:** Ubah nilai `'timezone' => 'UTC',` menjadi `'timezone' => 'Asia/Makassar',`

### 2. Set Expiration untuk Sanctum Token
Saat ini, personal access token (Sanctum) yang di-generate oleh agent tidak memiliki masa kedaluwarsa (`expiration => null`). Karena script agent (`scanner.ps1`) sudah bisa secara otomatis meminta ulang token ketika terjadi error `401 Unauthorized`, kita dapat dengan aman menambahkan batas kedaluwarsa.

*   **File:** `config/sanctum.php`
*   **Tugas:** Ubah nilai `'expiration' => null,` menjadi `'expiration' => 43200,` (43200 menit = 30 hari).

### 3. Mengamankan Akses Dashboard Laravel Horizon
Dashboard Laravel Horizon (`/horizon`) bertugas untuk memantau job queue. Di environment production, dashboard ini harus dibatasi agar tidak bisa diakses secara publik.

*   **File:** `app/Providers/HorizonServiceProvider.php`
*   **Tugas:** Modifikasi method `gate()` dan `viewHorizon` agar hanya user yang memiliki role `admin` yang bisa mengaksesnya. Proyek ini sudah menggunakan library `spatie/laravel-permission`, jadi Anda dapat menggunakan method `hasRole()`.
*   **Contoh Implementasi:**
    ```php
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return $user ? $user->hasRole('admin') : false;
        });
    }
    ```

---

## Alur Kerja (Workflow)
1. Baca dan pahami instruksi di atas.
2. Buat branch baru untuk mengerjakan tugas ini.
3. Lakukan perubahan pada ketiga file konfigurasi tersebut.
4. Jalankan `php artisan config:clear` jika diperlukan saat testing.
5. Format kode Anda menggunakan Pint:
   ```bash
   ./vendor/bin/pint
   ```
6. Commit perubahan Anda, push ke remote, lalu buat Pull Request dan minta review.

Selamat bekerja!
