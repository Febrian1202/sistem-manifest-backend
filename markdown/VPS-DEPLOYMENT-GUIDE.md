# 🚀 Panduan Deployment VPS (Ubuntu/Debian) — Sistem Manifest

Panduan ini ditujukan untuk administrator sistem yang ingin men-deploy aplikasi **Sistem Manifest USN Kolaka** ke dalam server VPS (Virtual Private Server) kosong dari titik nol.

---

## 1. 📦 Persiapan Server (Instalasi Dependensi)

Pastikan Anda *login* ke VPS Anda menggunakan akses `root` atau *user* dengan hak akses `sudo`. Sistem operasi yang direkomendasikan adalah **Ubuntu 22.04 LTS** atau yang lebih baru.

### a. Update Sistem
```bash
sudo apt update && sudo apt upgrade -y
```

### b. Instalasi Git
```bash
sudo apt install git -y
```

### c. Instalasi Docker & Docker Compose
Docker adalah tulang punggung dari aplikasi ini. Jalankan skrip instalasi resmi dari Docker:
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

Pastikan Docker Compose juga sudah tersedia (biasanya sudah terbundel di versi terbaru):
```bash
docker compose version
```

---

## 2. 📥 Mengunduh Source Code

Kloning repositori aplikasi ke dalam folder pilihan Anda (contoh: `/var/www/sistem-manifest`):

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/USERNAME/sistem-manifest-backend.git sistem-manifest
cd sistem-manifest
```
*(Ganti URL GitHub di atas dengan URL repositori Anda yang sebenarnya).*

---

## 3. ⚙️ Konfigurasi Environment

Aplikasi ini bersifat *immutable*, artinya semua rahasia dan konfigurasi diinjeksi lewat file `.env` tanpa di-*mount* ke dalam container.

### a. Buat file `.env`
Salin template *production* yang sudah disiapkan:
```bash
cp .env.production .env
```

### b. Edit file `.env`
Buka file tersebut menggunakan editor teks seperti `nano`:
```bash
nano .env
```
Isi variabel-variabel kunci berikut:
*   `APP_URL`: Ubah ke nama domain atau IP server VPS Anda (contoh: `https://manifest.usn.ac.id`).
*   `DB_PASSWORD`: Buat kata sandi yang rumit untuk database.
*   `AGENT_REGISTRATION_KEY`: Buat teks acak/token rahasia untuk autentikasi *Agent Scanner*.
*   `DEFAULT_USER_PASSWORD`: Kata sandi *default* untuk akun admin yang akan dibuat (jika tidak diubah di *Seeder*).

Simpan dan keluar (Ctrl+O, Enter, Ctrl+X).

---

## 4. 🏗️ Proses Build & Jalankan Container

Sekarang kita akan merakit *image* Docker dan menjalankan seluruh komponen sistem (Nginx, PHP, MySQL, Redis, Worker, Cron).

```bash
docker compose up -d --build
```
Proses ini akan memakan waktu 2-5 menit karena akan mengunduh dependensi (Composer & NPM) dan melakukan kompilasi CSS/JS.

---

## 5. 🔑 Setup Pertama Kali (First Run)

Setelah container menyala, Anda harus melakukan pengaturan awal di dalam container aplikasi (`app`).

### a. Generate App Key
Karena file `.env` Anda baru, buat *encryption key* bawaan Laravel. Wajib menggunakan `--show` karena file tidak di-*mount* secara fisik:
```bash
docker compose exec app php artisan key:generate --show
```
*Salin teks `base64:...` yang muncul.*

Buka kembali file `.env` Anda (`nano .env`) lalu *paste* kode tersebut ke baris `APP_KEY=`. 

### b. Muat Ulang Konfigurasi
Agar container membaca *Key* yang baru Anda masukkan:
```bash
docker compose up -d
```

### c. Migrasi, Seeding, dan Link Storage
Lakukan pembangunan struktur database dan injeksi data awal (seperti Role Admin):
```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RoleAndPermissionSeeder
docker compose exec app php artisan storage:link
```

### d. Optimasi Kecepatan (Wajib di Production!)
Kunci konfigurasi dan rute agar aplikasi berjalan secepat kilat:
```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan event:cache
```

---

## 6. 👤 Pembuatan Akun Administrator Utama

Agar Anda bisa masuk ke dasbor web, Anda harus membuat satu akun Admin secara manual. Masuk ke cangkang interaktif Laravel:
```bash
docker compose exec app php artisan tinker
```
Lalu tempelkan perintah PHP berikut:
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

## 7. 🌐 Menyambungkan Domain & Memasang HTTPS (Gembok Hijau)

Saat ini aplikasi Docker Anda berjalan di IP Publik VPS (tanpa HTTPS). Untuk menyambungkan domain resmi (contoh: `manifest.usn.ac.id`) dan mengamankannya dengan HTTPS menggunakan **Let's Encrypt**, ikuti tahapan krusial berikut:

### a. Arahkan DNS (A Record)
Di dasbor penyedia Domain Anda (Niagahoster, Hostinger, atau IT Kampus):
1. Masuk ke menu **DNS Management**.
2. Buat **A Record** baru.
3. Arahkan `Name/Host` ke subdomain (misal: `manifest`) atau `@` (untuk domain utama).
4. Isi `IPv4 Address` dengan **Alamat IP VPS Anda**, lalu Simpan. (Butuh propagasi 5-30 menit).

### b. Geser Port Docker ke Dalam (Internal)
Kita harus menyembunyikan port 80 Docker agar mesin VPS bisa menaruh satpam HTTPS di depan pintu.
Buka file `docker-compose.yml`:
```bash
nano docker-compose.yml
```
Cari bagian `web:` lalu ubah mapping port-nya dari `"80:80"` menjadi seperti ini:
```yaml
  web:
    image: nginx:alpine
    ports:
      - "127.0.0.1:8080:80"  # Bergeser dengan aman ke port 8080 lokal
```
Simpan, lalu terapkan perubahan:
```bash
docker compose up -d
```

### c. Instal Nginx & Certbot di Mesin VPS
Jalankan perintah ini di VPS Anda untuk menginstal *Reverse Proxy* dan mesin pembuat SSL:
```bash
sudo apt install nginx certbot python3-certbot-nginx -y
```

### d. Konfigurasi Jembatan (Reverse Proxy)
Buat file konfigurasi jembatan Nginx baru:
```bash
sudo nano /etc/nginx/sites-available/manifest
```
Tempelkan konfigurasi berikut (jangan lupa ubah nama domainnya!):
```nginx
server {
    server_name nama-domain-anda.com; # <--- UBAH INI

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_addrs;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
Simpan, lalu aktifkan dengan perintah berikut:
```bash
sudo ln -s /etc/nginx/sites-available/manifest /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

### e. Eksekusi Certbot (Instalasi SSL Otomatis)
Jalankan perintah sakti ini untuk mendapatkan HTTPS gratis selamanya:
```bash
sudo certbot --nginx -d nama-domain-anda.com
```
Certbot akan menanyakan email Anda, otomatis memvalidasi kepemilikan domain, dan menyuntikkan konfigurasi HTTPS gembok hijau ke Nginx Anda!

---

## 🎉 Selesai & Berhasil Tayang!
Aplikasi Sistem Manifest Anda kini sudah beroperasi di ranah internet terbuka menggunakan pengamanan standar dunia. Panel Admin siap digunakan!
