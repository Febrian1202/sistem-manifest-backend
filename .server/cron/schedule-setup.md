# Panduan Setup Scheduled Task (Cron Job)

Laravel Task Scheduler membutuhkan pemicu berupa satu entri Cron Job di tingkat server yang berjalan setiap menit. Entri ini akan memanggil command `schedule:run` yang kemudian mengeksekusi semua scheduled tasks yang terdefinisi di dalam kode aplikasi Laravel.

## Entri Cron Job

Tambahkan baris berikut ke konfigurasi crontab server Anda:

```cron
* * * * * cd /var/www/sistem-manifest-backend && php artisan schedule:run >> /dev/null 2>&1
```

> [!NOTE]
> Sesuaikan path `/var/www/sistem-manifest-backend` dengan letak absolute directory dari project Laravel Anda di server production.

---

## Langkah-Langkah Pemasangan

### Opsi A: Menggunakan User Web Server (`www-data` / `nginx`) - Direkomendasikan
Sangat disarankan untuk menjalankan cron job menggunakan user yang sama dengan web server untuk menghindari masalah hak akses file (*permission issues*) pada log atau cache.

1. Buka konfigurasi crontab untuk user `www-data` (sesuaikan dengan user web server Anda):
   ```bash
   sudo crontab -u www-data -e
   ```
2. Tambahkan entri cron di bagian paling bawah file.
3. Simpan dan keluar dari editor.

### Opsi B: Menggunakan User Saat Ini
Jika Anda dideploy menggunakan user tertentu yang memiliki hak akses penuh ke direktori project:

1. Jalankan perintah edit crontab:
   ```bash
   crontab -e
   ```
2. Tambahkan entri cron di bagian paling bawah file.
3. Simpan dan keluar dari editor.

---

## Verifikasi Task Scheduler

Untuk memastikan scheduler bekerja dengan benar:
1. Anda dapat melihat aktivitas yang berjalan melalui log Laravel (`storage/logs/laravel.log`).
2. Pastikan service cron berjalan di server Anda:
   ```bash
   sudo systemctl status cron
   # atau di CentOS/RHEL:
   sudo systemctl status crond
   ```
