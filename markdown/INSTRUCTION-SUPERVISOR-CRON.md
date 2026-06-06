# Instruksi Pekerjaan: Konfigurasi Queue Supervisor & Scheduled Task (Cron)

## Deskripsi Tugas
Tugas ini bertujuan untuk mempersiapkan file konfigurasi tingkat server (Linux/Ubuntu) yang dibutuhkan agar **Queue Supervisor** dan **Scheduled Task (Cron)** dapat berjalan dengan baik di server *production* Sistem Manifest USN Kolaka. 

Meskipun AI tidak dapat masuk langsung ke dalam sistem operasi server sungguhan, AI harus membuatkan *template* file konfigurasi standar dan skrip penunjang di dalam *codebase* sehingga tim Sysadmin/Pengembang dapat dengan mudah melakukan *copy-paste* ke server.

---

## Subtugas 1: Membuat Konfigurasi Queue Supervisor
Supervisor digunakan untuk memantau proses antrean (Laravel Queue) agar selalu berjalan 24 jam nonstop dan secara otomatis me-restart *worker* apabila terjadi kegagalan.

**Langkah-langkah Eksekusi:**
1. Buat direktori baru di root project bernama `.server/supervisor/` jika belum ada.
2. Buat file baru di dalamnya bernama `manifest-worker.conf`.
3. Isi file tersebut dengan konfigurasi standar Supervisor untuk Laravel. Konfigurasi wajib memuat instruksi berikut:
   - Nama program: `[program:manifest-worker]`
   - Parameter `command`: Menjalankan `php artisan queue:work --queue=scans,compliance,default --sleep=3 --tries=3 --timeout=120` pada letak direktori standar (misalnya diasumsikan project berada di `/var/www/sistem-manifest-backend`).
   - Eksekutor menggunakan *user* web server (misalnya `www-data` atau `nginx`).
   - Nyalakan opsi `autostart=true` dan `autorestart=true`.
   - Setup keluaran *log* standar untuk error maupun output normal ke direktori `storage/logs/worker.log`.

---

## Subtugas 2: Menyiapkan Konfigurasi Scheduled Task (Cron)
Laravel memiliki sistem *Task Scheduling* yang canggih, namun membutuhkan satu pemicu tingkat-server (Cron Job) yang berjalan setiap menit.

**Langkah-langkah Eksekusi:**
1. Buat file dokumentasi teknis atau skrip bash baru bernama `.server/cron/schedule-setup.md` atau `setup-cron.sh`.
2. Di dalam file tersebut, berikan perintah Cron lengkap yang memanggil `php artisan schedule:run` setiap satu menit (`* * * * *`).
3. Pastikan instruksi tersebut memberikan informasi bagaimana cara sysadmin memasukkan baris tersebut ke dalam sistem (`crontab -e`).

---

## Subtugas 3: Update Dokumentasi dan Alur Kerja
Agar Sysadmin mengetahui keberadaan file konfigurasi ini, panduan utama harus diperbarui.

**Langkah-langkah Eksekusi:**
1. Checkout ke branch baru dengan nama: `feature/server-configurations`.
2. Setelah file-file di dalam `.server/` selesai dibuat, buka file `README.md` (atau buat jika perlu disesuaikan).
3. Tambahkan satu *section* / bagian baru berjudul **"Deployment & Server Configuration"** yang menjelaskan bahwa file konfigurasi Supervisor dan Cron Job sudah tersedia di dalam direktori `.server/`.
4. Lakukan verifikasi untuk memastikan tidak ada kesalahan ejaan atau *syntax error* pada konfigurasi *supervisor*.
5. Lakukan *commit* semua perubahan, lalu dorong (*push*) ke repositori.
6. Buat **Pull Request** baru untuk menggabungkan `feature/server-configurations` ke branch utama (`main`).

Selamat bekerja!
