# Sistem Manifest (USN Kolaka)

Sistem Informasi Manifest Lisensi Software untuk mengelola aset IT dan mencegah pelanggaran hak cipta perangkat lunak.

![Build Status](https://img.shields.io/github/actions/workflow/status/Febrian1202/sistem-manifest-backend/tests.yml?branch=main)
![License](https://img.shields.io/badge/license-MIT-blue)
![Version](https://img.shields.io/badge/version-1.0.0-green)

---

## 📸 Demo

> _(Placeholder: Tambahkan screenshot atau GIF dashboard aplikasi di sini)_
> `![Dashboard Preview](/docs/images/dashboard-preview.png)`

## ✨ Fitur Utama

- **Role-Based Access Control (RBAC):** Pemisahan hak akses antara Administrator dan Pimpinan.
- **Manajemen Aset IT:** Pendataan komputer dan perangkat lunak yang terinstal di setiap unit/fakultas.
- **Manajemen Lisensi:** Pencatatan lisensi software resmi untuk memantau legalitas penggunaan.
- **Audit Kepatuhan (Compliance):** Deteksi otomatis perangkat lunak tanpa lisensi atau bajakan.
- **REST API Terintegrasi:** Endpoint khusus untuk agen (client) di setiap komputer agar dapat mengirim hasil _scan_ secara otomatis.
- **Laporan & Ekspor:** Menghasilkan laporan kepatuhan dalam format PDF dan Excel.

## 🛠️ Prerequisites (Prasyarat)

Sebelum menjalankan proyek ini, pastikan sistem Anda telah menginstal perangkat lunak berikut:

- **PHP** >= 8.2
- **Composer** (Dependency Manager untuk PHP)
- **Node.js & NPM** (Untuk kompilasi aset _frontend_)
- **Database** (MySQL atau SQLite)
- **Git**

## 🚀 Instalasi

Ikuti langkah-langkah berikut untuk menjalankan proyek di lingkungan lokal:

1. **Clone repositori:**

    ```bash
    git clone https://github.com/Febrian1202/sistem-manifest-backend.git
    cd sistem-manifest-backend
    ```

2. **Instal dependensi PHP:**

    ```bash
    composer install
    ```

3. **Salin file konfigurasi _environment_:**

    ```bash
    cp .env.example .env
    ```

4. **Konfigurasi Database:**
   Buka file `.env` dan sesuaikan pengaturan koneksi database (misalnya menggunakan SQLite untuk kemudahan lokal atau MySQL).

    ```env
    DB_CONNECTION=sqlite
    # Atau jika menggunakan MySQL:
    # DB_CONNECTION=mysql
    # DB_HOST=127.0.0.1
    # DB_PORT=3306
    # DB_DATABASE=sistem_manifest
    # DB_USERNAME=root
    # DB_PASSWORD=
    ```

5. **Generate Application Key:**

    ```bash
    php artisan key:generate
    ```

6. **Jalankan Migrasi dan Seeder (untuk membuat tabel dan data awal):**

    ```bash
    php artisan migrate --seed
    ```

7. **Instal dependensi Node.js & bangun aset UI (Tailwind CSS):**

    ```bash
    npm install
    npm run build
    ```

8. **Jalankan _Development Server_:**
    ```bash
    php artisan serve
    ```
    Aplikasi sekarang dapat diakses melalui `http://localhost:8000`.

## 💻 Penggunaan Dasar

**Akses Panel Admin:**

1. Buka `http://localhost:8000/login`
2. Gunakan kredensial _default_ (biasanya diatur pada seeder):
    - **Email:** `admin@usn.ac.id` (atau sesuai konfigurasi seeder)
    - **Password:** `password`

**Menambahkan Lisensi Baru:**

1. Login sebagai Admin.
2. Navigasi ke menu **Lisensi & Audit > Kelola Lisensi**.
3. Klik tombol **Tambah Lisensi** dan isi formulir yang disediakan.

## 📡 API Reference

Aplikasi ini menyediakan REST API berbasis Laravel Sanctum untuk menerima data _scan_ dari perangkat agen (komputer klien).

### 1. Registrasi Agen (Komputer Baru)

- **Endpoint:** `POST /api/agent/register`
- **Deskripsi:** Mendaftarkan komputer baru ke dalam sistem dan mendapatkan _Bearer Token_.
- **Payload:** `{"mac_address": "00:1A:2B:3C:4D", "hostname": "PC-LAB-01"}`

### 2. Mengirim Hasil Scan Software

- **Endpoint:** `POST /api/scan-result`
- **Auth:** Bearer Token (Sanctum)
- **Deskripsi:** Mengirimkan daftar _software_ yang terinstal pada komputer agen.
- **Payload:** `{"softwares": [{"name": "Microsoft Office", "version": "2019"}]}`

### 3. Memeriksa Perintah Scan

- **Endpoint:** `GET /api/agent/scan-command`
- **Auth:** Bearer Token (Sanctum)
- **Deskripsi:** Agen melakukan _polling_ secara berkala untuk mengecek apakah server memerintahkan pemindaian ulang.

## 📂 Struktur Proyek

```text
sistem-manifest-backend/
├── app/
│   ├── Http/Controllers/    # Logika bisnis (Web & API Controllers)
│   ├── Models/              # Representasi tabel database (Eloquent)
│   └── Observers/           # Event listeners untuk model (misal: ComputerObserver)
├── database/
│   ├── migrations/          # Skema database
│   └── seeders/             # Data dummy/awal (Roles, Admin User)
├── lang/id/                 # Lokalisasi/Terjemahan Bahasa Indonesia
├── resources/
│   ├── css/ & js/           # Aset statis & Tailwind entry
│   └── views/               # File antarmuka pengguna (Blade Templates)
│       └── components/      # Reusable Blade UI Components
├── routes/
│   ├── api.php              # Definisi route untuk Agent API
│   └── web.php              # Definisi route untuk Admin Panel
└── tests/                   # Automated Testing (Pest/PHPUnit)
```

## 🤝 Contributing

Bagi _developer_ yang ingin berkontribusi:

1. _Fork_ repositori ini.
2. Buat _branch_ fitur baru (`git checkout -b feature/FiturBaru`).
3. Lakukan _commit_ pada perubahan Anda (`git commit -m 'feat: menambahkan FiturBaru'`).
4. _Push_ ke _branch_ tersebut (`git push origin feature/FiturBaru`).
5. Buat _Pull Request_.

Pastikan kode Anda lolos _testing_ standar sebelum membuat Pull Request:

```bash
php artisan test
```

## 📄 License

Proyek ini dirilis di bawah [MIT License](https://opensource.org/licenses/MIT).
