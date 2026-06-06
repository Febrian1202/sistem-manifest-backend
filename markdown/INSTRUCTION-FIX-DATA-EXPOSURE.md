# Instruksi Perbaikan Data Exposure (Model Hidden Attributes)

## Konteks
Sistem Manifest memiliki celah keamanan *data exposure* (kebocoran data sensitif) yang terjadi pada saat model Eloquent dikonversi menjadi format Array atau JSON (misalnya saat mengirimkan respons API atau ketika serialisasi log).

Berdasarkan laporan produksi, terdapat dua model utama yang saat ini belum memproteksi data sensitif mereka dengan property `$hidden`:
1. **Model `LicenseInventory`**
2. **Model `Computer`**

## Spesifikasi Tugas

Tugas Anda adalah memperbarui kedua model ini dan memastikan properti sensitif disembunyikan dari serialisasi.

### 1. Perbaikan Model `LicenseInventory`
**File Target:** `app/Models/LicenseInventory.php`

**Masalah:** 
Properti `license_key` dienkripsi pada database menggunakan Laravel Cast (`encrypted`), tetapi atribut ini secara otomatis ter-dekripsi ketika diakses. Jika tidak disembunyikan, JSON respons akan mengekspos bentuk plain-text dari lisensi ini.

**Langkah Implementasi:**
- Tambahkan properti `protected $hidden` pada kelas tersebut.
- Masukkan `'license_key'` ke dalam array `$hidden`.
- *(Tidak perlu mengubah method yang sudah ada seperti `getMaskedLicenseKeyAttribute`, biarkan atribut masket tetap bisa diakses karena itu aman).*

### 2. Perbaikan Model `Computer`
**File Target:** `app/Models/Computer.php`

**Masalah:**
Data identitas komputer yang unik berpotensi dapat dimanfaatkan untuk tindak kejahatan atau *spoofing* bila secara tak sengaja terekspos lewat API JSON respons. 

**Langkah Implementasi:**
- Tambahkan properti `protected $hidden` pada kelas `Computer`.
- Masukkan data sensitif berikut ke dalam array `$hidden`:
  1. `'mac_address'`
  2. `'serial_number'`
  3. `'ip_address'`

## Kriteria Penerimaan (Acceptance Criteria)
1. Atribut `$hidden = ['license_key'];` berhasil diimplementasikan di `LicenseInventory.php`.
2. Atribut `$hidden = ['mac_address', 'serial_number', 'ip_address'];` berhasil diimplementasikan di `Computer.php`.
3. Aplikasi tetap stabil. Coba jalankan pengujian unit atau integrasi (`php artisan test`) setelah menerapkan perubahan untuk memastikan bahwa menyembunyikan properti ini tidak merusak fungsi bawaan yang membutuhkannya di belakang layar (karena `$hidden` hanya memengaruhi *serialisasi* JSON/array, bukan akses internal).
