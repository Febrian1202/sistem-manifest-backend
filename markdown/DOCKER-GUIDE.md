# 🐳 Panduan Deployment Docker Production — Sistem Manifest

Dokumen ini berisi panduan lengkap untuk melakukan _build_ dan menjalankan (deploy) aplikasi Sistem Manifest USN Kolaka di server _production_ menggunakan Docker.

Konfigurasi ini menggunakan pendekatan _multi-container_ yang optimal:

- **`app`**: PHP-FPM 8.2 (Menjalankan core aplikasi).
- **`web`**: Nginx (Web server untuk melayani request HTTP dan _static assets_).
- **`db`**: MySQL 8.0 (Database utama).
- **`redis`**: Redis 7 (Digunakan untuk _Cache_ dan _Queue_).
- **`worker`**: PHP-FPM yang menjalankan _queue worker_ secara _persistent_ (memproses hasil scan).
- **`cron`**: PHP-FPM yang menjalankan _scheduler_ secara berkala.

---

## 1. ⚙️ Persiapan Environment

Sebelum memulai proses _build_, pastikan environment variables sudah disiapkan. Anda dapat menyalin file `.env.production` menjadi `.env`.

```bash
cp .env.production .env
```

Pastikan beberapa nilai penting di bawah ini sudah sesuai (cocokkan konfigurasi database dan Redis dengan nama _service_ di Docker):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=manifest_db
DB_USERNAME=manifest_user
DB_PASSWORD=manifest_password

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
```

> **Catatan:** Pastikan `APP_KEY` dan `AGENT_REGISTRATION_KEY` sudah terisi dengan aman.

---

## 2. 🚀 Build dan Menjalankan Container

Gunakan perintah `docker compose` untuk mem-build _image_ (Composer dependencies & frontend assets) dan menyalakan semua _services_ di _background_.

```bash
docker compose up -d --build
```

**Penjelasan proses build:**

1. **Tahap 1 (Composer):** Akan mengunduh semua package PHP.
2. **Tahap 2 (Node):** Akan mengunduh package NPM dan melakukan proses _build_ Vite.
3. **Tahap 3 (Final Image):** Menggabungkan hasil build ke dalam OS Alpine Linux yang ringan beserta PHP-FPM dan semua ekstensi yang dibutuhkan.

---

## 3. 🛠️ Setup Database & Optimasi (Wajib untuk First Run)

Setelah _container_ berhasil berjalan, aplikasi belum bisa digunakan karena database masih kosong dan struktur cache belum dioptimalkan. Jalankan perintah-perintah berikut satu per satu:

### a. Generate App Key (Jika `.env` baru / kosong)

Di arsitektur *Production* ini, file `.env` **sengaja tidak di-mount** secara fisik ke dalam container demi keamanan (*immutable architecture*). Karena itu, eksekusi `key:generate` biasa di dalam container **tidak akan mengubah** file `.env` yang ada di server fisik Anda.

Anda **wajib** menggunakan flag `--show` untuk mencetak *key* tersebut ke layar:

```bash
docker compose exec app php artisan key:generate --show
```

Setelah *key* berupa teks `base64:...` tercetak di layar terminal, **salin** teks tersebut dan **tempelkan secara manual** pada nilai `APP_KEY=` di file `.env` server Anda. (Anda perlu merestart container dengan `docker compose up -d` setelahnya agar key baru diserap).

### b. Migrasi Struktur Database

```bash
docker compose exec app php artisan migrate --force
```

### c. Seeding Role & Permissions

```bash
docker compose exec app php artisan db:seed --class=RoleAndPermissionSeeder
```

### d. Link Storage

Agar gambar _proof of purchase_ pada license inventory dapat diakses lewat web.

```bash
docker compose exec app php artisan storage:link
```

### e. Optimasi Cache Production

Hal ini sangat penting untuk mendongkrak performa aplikasi di tahap _production_.

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan event:cache
```

---

## 4. 👤 Pembuatan Akun Admin

Jika Anda tidak menjalankan inline user seeder, Anda dapat membuat akun admin utama menggunakan **Laravel Tinker** langsung di dalam _container_:

Masuk ke console Tinker:

```bash
docker compose exec app php artisan tinker
```

Jalankan perintah ini di dalam shell Tinker:

```php
$user = App\Models\User::create([
    'name' => 'Administrator',
    'email' => 'admin@usn.ac.id',
    'password' => 'PassKuat123!'
]);
$user->assignRole('admin');
exit;
```

---

## 5. 🔍 Pemeliharaan dan Monitoring

Beberapa perintah dasar yang akan sering Anda gunakan dalam mengelola _container_ aplikasi ini:

- **Melihat status service yang berjalan:**

    ```bash
    docker compose ps
    ```

- **Melihat log (seluruh services secara realtime):**

    ```bash
    docker compose logs -f
    ```

- **Melihat log worker spesifik (untuk memantau hasil scan):**

    ```bash
    docker compose logs -f worker
    ```

- **Masuk ke dalam shell container `app`:**

    ```bash
    docker compose exec app /bin/sh
    ```

- **Menghentikan semua container tanpa menghapus data:**

    ```bash
    docker compose down
    ```

- **⚠️ Menghentikan dan menghapus semua container beserta data di Volume (Database & Redis akan hilang):**

    ```bash
    docker compose down -v
    ```
