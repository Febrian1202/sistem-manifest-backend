# Instruksi Pekerjaan: Fitur Opsional Kesiapan Production (Error Pages & Force HTTPS)

## Deskripsi Tugas
Tugas ini bertujuan untuk menambahkan dua fitur tambahan (opsional) guna menyempurnakan kesiapan *production* dari Sistem Manifest USN Kolaka. Fitur yang akan diimplementasikan adalah:
1. **Halaman Custom Error (404, 403, 500)** yang selaras dengan antarmuka dan *styling* aplikasi (Tailwind CSS).
2. **Implementasi Force HTTPS** agar aplikasi selalu berjalan pada jalur komunikasi yang aman.

---

## Subtugas 1: Membuat Custom Error Pages
Secara *default*, Laravel menampilkan halaman *error* yang sangat polos. Kita perlu membuat halaman *error* kustom agar jika terjadi kesalahan atau *Not Found*, antarmukanya tetap profesional dan memiliki navigasi kembali ke halaman utama.

**Langkah-langkah Eksekusi:**
1. Buat folder baru di `resources/views/errors/` jika belum ada.
2. Buat tiga file Blade baru di dalam folder tersebut:
   - `403.blade.php` (Forbidden / Unauthorized)
   - `404.blade.php` (Not Found)
   - `500.blade.php` (Server Error)
3. Rancang tampilan halaman-halaman tersebut menggunakan Tailwind CSS. 
   - Anda **boleh** memanfaatkan *layout* utama (misalnya dengan menge-extend `components/layout/app.blade.php` jika memungkinkan dan tidak membutuhkan data khusus dari controller), **atau** membuat struktur HTML mandiri yang tetap menggunakan Tailwind (via `@vite(['resources/css/app.css', 'resources/js/app.js'])`).
   - Berikan teks dalam **Bahasa Indonesia** yang informatif. (Misal 404: "Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.").
   - Sediakan tombol atau tautan yang jelas untuk kembali ke Dashboard (`{{ route('dashboard') }}`).

---

## Subtugas 2: Konfigurasi Force HTTPS
Aplikasi yang di-deploy ke production wajib menggunakan HTTPS. Terkadang *Reverse Proxy* atau Load Balancer meneruskan *request* sebagai HTTP, yang menyebabkan aset atau navigasi tidak berfungsi sempurna.

**Langkah-langkah Eksekusi:**
1. Buka file `app/Providers/AppServiceProvider.php`.
2. Pada metode `boot()`, tambahkan logika untuk memaksa penggunaan *scheme* HTTPS apabila aplikasi berada pada *environment production*.
   ```php
   use Illuminate\Support\Facades\URL;

   public function boot(): void
   {
       if (config('app.env') === 'production') {
           URL::forceScheme('https');
       }
   }
   ```
3. (Opsional/Tambahan) Jika Anda ingin membuat middleware khusus yang me-redirect otomatis (HTTP -> HTTPS) di level aplikasi, buat middleware dengan perintah `php artisan make:middleware ForceHttps`. 
   - Di dalam fungsi `handle()` middleware tersebut, jika bukan request *secure* dan di *environment production*, kembalikan *redirect secure*.
   - Daftarkan middleware tersebut ke dalam alur *request* utama di `bootstrap/app.php`.
   - *Catatan: Fokus utama adalah URL::forceScheme, namun menambah middleware redirect sangat direkomendasikan jika server tidak dikonfigurasi untuk auto-redirect.*

---

## Alur Kerja (Workflow)
1. Buat dan checkout ke branch baru: `feature/optional-production-fixes`.
2. Implementasikan Subtugas 1 dan Subtugas 2 sesuai panduan.
3. Lakukan pengujian secara manual atau verifikasi syntax agar tidak ada *error*.
4. Gunakan Laravel Pint (`./vendor/bin/pint`) untuk merapikan *coding style* PHP.
5. Jalankan `npm run build` jika dirasa ada *class* Tailwind baru yang ditambahkan di *views*.
6. Commit perubahan, *push* ke repository, lalu buat **Pull Request**.
7. Tandai poin pada bagian "Fitur Opsional" di file `markdown/PRODUCTION-READINESS.md` menjadi selesai.

Selamat bekerja!
