# Task 29 — Revisi Narasi UI: "Agen Scanner" → "Tools Pemindai"

## Sprint: 5 (Integrasi Pimpinan & Polish)
## Prioritas: Rendah
## Dependensi: Tidak ada
## Estimasi: 30-45 menit

---

## Deskripsi

Ganti semua referensi "Agen Scanner" sebagai entitas mandiri di antarmuka pengguna menjadi bahasa yang menunjukkan ini adalah tools yang dioperasikan oleh Admin/Staff.

## Tabel Penggantian Teks

| Cari (Sebelumnya) | Ganti (Sesudahnya) |
|-------------------|---------------------|
| Agen Scanner | Tools Pemindai / Scanner |
| Agen Terdaftar | Komputer Terdaftar |
| Komputer klien mengirim data | Admin menjalankan pemindaian pada komputer |
| Agen berhasil terdaftar | Komputer berhasil didaftarkan (scanner aktif) |
| Download Agen | Download Scanner |
| Unduh Agen | Unduh Scanner |

## Langkah-langkah

### 1. Cari Semua Referensi

Gunakan pencarian teks di seluruh `resources/views/` dan `lang/id/` untuk menemukan string yang mengandung:
- "Agen"
- "agen"
- "Agent"
- "agent" (dalam konteks UI, bukan nama class/variable PHP)

### 2. Ganti di View Blade

Ganti teks di setiap view yang ditemukan. **Jangan** ganti:
- Nama class PHP (tetap `AgentDownloadController`, `AgentRegisterController`)
- Nama route (tetap `/api/agent/register`)
- Komentar kode
- Nama folder (`script/agent/`)

Hanya ganti teks yang **ditampilkan ke user** di UI.

### 3. Ganti di File Bahasa

Jika ada file terjemahan di `lang/id/` yang berisi "Agen", ganti juga.

### 4. Update Instruksi dalam ZIP Bundle

File `instruksi.txt` yang ada di ZIP bundle scanner — jika menyebut "Agen", ganti menjadi "Scanner" atau "Tools Pemindai".

## Referensi
- Cari di seluruh `resources/views/`
- Cari di `lang/id/`
- Cari di `script/agent/instruksi.txt`

## File yang Dimodifikasi
- Beberapa view Blade (bergantung hasil pencarian)
- File terjemahan `lang/id/` (jika ada)
- `script/agent/instruksi.txt` (jika ada)

## Verifikasi
- [x] Tidak ada teks "Agen Scanner" yang tampil di UI
- [x] Nama class dan route PHP **tidak** berubah
- [x] Semua halaman yang menyebut scanner menggunakan narasi baru
- [x] Instruksi dalam ZIP bundle menggunakan narasi baru
