# Design: Perancangan ERD Notasi Chen untuk Sistem Audit Manifest Software USN Kolaka

## 1. Intent and The Why
Pengguna membutuhkan perancangan Entity-Relationship Diagram (ERD) untuk penulisan skripsi/tugas akhir mengenai Sistem Manifest & Audit Kepatuhan Lisensi Software Laboratorium di Universitas Sembilanbelas November (USN) Kolaka. 

Format diagram mengacu pada **Notasi Chen klasik** (sesuai gambar referensi medis yang diberikan):
- **Persegi Panjang**: Entitas (Entities)
- **Belah Ketupat (Diamond)**: Relasi (Relationships)
- **Elips / Oval**: Atribut (Attributes), dengan tanda `*` menandakan *Primary Key* (PK) dan `**` menandakan *Foreign Key* (FK)
- **Atribut Relasi**: Oval yang menempel pada belah ketupat relasi untuk atribut transaksi/perantara (seperti pada relasi `rekam_medik` di gambar referensi)
- **Garis Penghubung**: Menghubungkan entitas ke relasi dan entitas ke atribut, dilengkapi notasi kardinalitas (1, N, M).

Selain diagram grafis, pengguna memerlukan kode Mermaid dan narasi akademis ringkas terstruktur untuk Bab Perancangan Basis Data dalam naskah skripsi agar siap menghadapi pertanyaan dosen penguji saat ujian sidang.

---

## 2. Goals, Non-Goals, Anti-Goals

### Goals
1. **Diagram Notasi Chen 7 Entitas (Opsi A)**:
   - Merepresentasikan proses bisnis utama: hierarki organisasi (`fakultas` & `laboratorium`), aktor (`pengguna`), aset perangkat (`komputer`), master aplikasi (`katalog_software`), hak pakai (`lisensi`), dan hasil kepatuhan (`laporan_audit`).
   - Mengikuti estetika dan konvensi visual gambar referensi secara presisi (termasuk atribut pada belah ketupat relasi M:N).
2. **File Draw.io (`.drawio`)**:
   - Menghasilkan file Draw.io XML mandiri yang valid dan siap diimpor ke app.diagrams.net atau ekstensi Draw.io di IDE.
   - Penataan layout teratur (simetris, tanpa tabrakan garis penghubung), siap diekspor ke PNG/SVG resolusi tinggi untuk dicetak di naskah skripsi.
3. **Diagram Mermaid**:
   - Menyediakan kode diagram Mermaid yang dapat dirender langsung di GitHub Markdown / viewer markdown.
4. **Dokumen Narasi Akademis Skripsi**:
   - Menghasilkan dokumen markdown siap pakai untuk Bab 3/4 Skripsi yang mencakup:
     - Deskripsi masing-masing entitas (tujuan & fungsi sistem).
     - Matriks analisis derajat relasi dan kardinalitas (beserta penjelasan logika proses bisnisnya).
     - Kamus data (Data Dictionary) ringkas mencakup tipe data, jenis atribut (PK/FK), dan deskripsi.

### Non-Goals
- Tidak menyertakan entitas log teknis scanner internal (`sesi_scan`, `scan_software_results`, `software_discoveries`, `activity_log`, `personal_access_tokens`, `jobs`, `cache`) ke dalam diagram Chen utama agar diagram tidak terlalu padat dan tetap fokus pada konsep manajerial skripsi.
- Tidak mengubah skema tabel fisik atau migrasi yang sudah ada di database sistem Laravel.

### Anti-Goals
- Tidak menggunakan notasi Crow's Foot (relasional fisik dengan tabel kolom) sebagai pengganti Chen notation.
- Tidak membuat teks penjelasan yang bertele-tele dan berulang-ulang tanpa struktur tabel yang jelas.

---

## 3. Constraints
- **Konsistensi Nama & Terminologi**: Menggunakan istilah bahasa Indonesia akademis yang lazim di lingkungan kampus USN Kolaka (`fakultas`, `laboratorium`, `pengguna`, `komputer`, `katalog_software`, `lisensi`, `laporan_audit`).
- **Penanda Kunci**:
  - Primary Key: Ditandai dengan simbol bintang satu (`*`), contoh: `id_lab *`.
  - Foreign Key: Ditandai dengan simbol bintang dua (`**`), contoh: `id_fakultas **`.
- **Kardinalitas Chen**: Diletakkan pada garis antara entitas dan belah ketupat relasi (`1` atau `N` / `M`).

---

## 4. Architectural & Data Model Specifications

### 4.1 Entitas dan Atribut
1. **`fakultas`**
   - Atribut: `id_fakultas *` (PK), `nama_fakultas`, `kode_fakultas`
2. **`laboratorium`**
   - Atribut: `id_lab *` (PK), `id_fakultas **` (FK), `nama_lab`, `lokasi`
3. **`pengguna`**
   - Atribut: `id_user *` (PK), `id_lab **` (FK), `nama`, `email`, `role`
   - *Catatan arsitektur*: Mengacu pada schema sistem di mana Kepala Lab / Staff Lab ditugaskan ke laboratorium spesifik (1 Lab memiliki banyak Pengguna).
4. **`komputer`**
   - Atribut: `id_komputer *` (PK), `id_lab **` (FK), `hostname`, `mac_address`, `ip_address`, `os_name`, `status`
5. **`katalog_software`**
   - Atribut: `id_software *` (PK), `nama_software`, `kategori`, `vendor`
6. **`lisensi`**
   - Atribut: `id_lisensi *` (PK), `id_software **` (FK), `nomor_lisensi`, `tipe_lisensi`, `jumlah_kuota`, `tgl_kadaluarsa`
7. **`laporan_audit`**
   - Atribut: `id_laporan *` (PK), `id_lab **` (FK), `id_user **` (FK - approver), `periode`, `status_kepatuhan`, `tgl_audit`

### 4.2 Relasi, Kardinalitas, dan Atribut Relasi
1. **`fakultas` - [memiliki] - `laboratorium`** (1 : N)
   - Satu fakultas membawahi banyak laboratorium. Satu laboratorium hanya berada di bawah satu fakultas.
2. **`laboratorium` - [menugaskan] - `pengguna`** (1 : N)
   - Satu laboratorium menaungi beberapa staf/pengguna operasional laboratorium.
3. **`laboratorium` - [menempatkan] - `komputer`** (1 : N)
   - Satu laboratorium memiliki banyak unit komputer client. Satu unit komputer ditempatkan di satu laboratorium fisik.
4. **`komputer` - [menginstal] - `katalog_software`** (M : N)
   - Relasi associative dengan atribut relasi: `versi_terpasang`, `status_kepatuhan`. (Mengikuti gaya relasi `rekam_medik` pada gambar acuan).
5. **`katalog_software` - [mencakup] - `lisensi`** (1 : N)
   - Satu katalog software dapat dicakup oleh beberapa pengadaan lisensi induk.
6. **`fakultas` - [dialokasikan] - `lisensi`** (M : N)
   - Relasi associative dengan atribut relasi: `kuota_alokasi`, `tgl_alokasi` (selaras dengan tabel `license_allocations` di mana kuota lisensi universitas didistribusikan ke tingkat fakultas).
7. **`laboratorium` - [diaudit] - `laporan_audit`** (1 : N)
   - Satu laboratorium diaudit secara berkala, menghasilkan beberapa rekaman laporan kepatuhan historis.
8. **`pengguna` - [menyetujui] - `laporan_audit`** (1 : N)
   - Seorang pengguna berwenang (Kepala Lab / Pimpinan) memverifikasi dan menyetujui (*approval*) laporan audit kepatuhan.

---

## 5. Artifact Deliverables and Storage Locations
1. **Draw.io File**:
   - `docs/diagrams/erd-chen-audit-manifest.drawio`
   - Berisi definisi XML grafik mxGraph lengkap dengan shape `rectangle`, `rhombus`, `ellipse`, koordinat rapi, style bersih (garis hitam tebal, latar putih / aksen lembut).
2. **Mermaid Diagram Code**:
   - `docs/diagrams/erd-chen-audit-manifest.mermaid` (serta disematkan dalam file markdown narasi).
3. **Dokumen Narasi Akademis Skripsi**:
   - `docs/skripsi/perancangan-basis-data-erd.md`
   - Berisi naskah lengkap siap pakai untuk Bab 3 / Bab 4 Skripsi: Pendahuluan ERD, Gambar/Diagram, Penjelasan Entitas, Analisis Kardinalitas Bisnis, dan Kamus Data Terstruktur.

---

## 6. What's Left to the Builder
- Menghitung koordinat piksel (X, Y, Width, Height) pada XML Draw.io agar komposisi visual seimbang, simetris, dan mudah dibaca pada lembar dokumen skripsi ukuran A4/Letter.
- Menyusun layout flowchart/graph Mermaid untuk merepresentasikan notasi Chen (entitas box, relasi diamond, atribut oval).
- Memastikan format tabel kamus data di dokumen skripsi rapi dan sesuai kaidah penulisan ilmiah bahasa Indonesia.
