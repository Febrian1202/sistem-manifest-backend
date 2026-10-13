# Penjelasan Bagan Alir Sistem (Flowmap) Sistem Informasi Manifest USN
*(Pendekatan Prosedural Siklus Input – Proses – Output)*

---

## 1. Pendahuluan

Bagan Alir Sistem (*Flowmap* atau *Document Flowchart*) adalah representasi grafis terstruktur yang menggambarkan pergerakan dokumen fisik maupun elektronik, aktivitas pemrosesan manual, operasi komputerisasi sistem, serta interaksi basis data antar unit/bagian fungsional dalam suatu organisasi (Kendall & Kendall, 2019; Romney & Steinbart, 2018).

Dalam konteks penulisan skripsi akademik bidang Sistem Informasi dan Informatika, *Flowmap* berfungsi untuk memperjelas batas tanggung jawab operasional (*separation of duties*) melalui pembagian kolom jalur aktivitas (*swimlane columns*). Pada Sistem Informasi Manifest Universitas Sembilanbelas November (USN) Kolaka, entitas yang terlibat dalam aliran data meliputi:
1. **Administrator:** Pengelola teknis institusi tingkat universitas (manajemen pengguna, lisensi, katalog, dan pengajuan laporan).
2. **Staf Laboratorium:** Operator di tingkat unit laboratorium yang bertugas mengunduh dan men-deploy agent scanner pada komputer laboratorium.
3. **Kepala Laboratorium:** Pimpinan unit laboratorium yang berwenang menelaah dan mengesahkan (*approve/reject*) laporan kepatuhan laboratorium.
4. **Pimpinan:** Pengambil keputusan eksekutif tingkat universitas/fakultas (Rektorat/Dekanat) yang memantau matriks kepatuhan lintas fakultas.
5. **Sistem Informasi Manifest USN:** Aplikasi berbasis Laravel 12 beserta Queue Worker (Laravel Horizon) yang mengeksekusi komputasi latar belakang.
6. **Basis Data (*Database*):** Repositori data persisten (MySQL/SQLite) yang menyimpan data transaksional dan rekam audit.

Untuk menjaga keterbacaan diagram sesuai standar buku skripsi, *Flowmap* dirancang ke dalam **tiga (3) prosedur utama** yang selaras dengan siklus IPO (*Input – Proses – Output*), tersimpan dalam berkas Draw.io terpadu multi-halaman (`docs/diagrams/flowmap/flowmap.drawio`):
- **Halaman 1:** Prosedur 1.0 – Pengelolaan Master Data, Lisensi, dan Registrasi/Pemindaian Komputer (*Input*)
- **Halaman 2:** Prosedur 2.0 – Pemrosesan dan Evaluasi Kepatuhan Lisensi serta Persetujuan Laporan (*Proses*)
- **Halaman 3:** Prosedur 3.0 – Penyajian Informasi, Pembuatan Laporan Resmi, dan Monitoring Eksekutif (*Output*)

---

## 2. Notasi dan Simbol Standar Flowmap (ANSI)

Diagram flowmap ini menggunakan simbol-simbol standar *American National Standards Institute* (ANSI):

| Simbol | Nama Notasi | Keterangan Fungsi |
|:------:|:-----------:|:------------------|
| ![Terminator](https://via.placeholder.com/60x25/ffffff/000000?text=Terminator) | **Terminator** (Oval/Stadium) | Menunjukkan titik awal (*Mulai*) atau titik akhir (*Selesai*) dari suatu prosedur sistem. |
| ![Document](https://via.placeholder.com/60x35/ffffff/000000?text=Document) | **Dokumen / Formulir** | Menunjukkan dokumen fisik, formulir data, arsip berkas, atau keluaran cetak/PDF/Excel. |
| ![Process](https://via.placeholder.com/60x30/ffffff/000000?text=Process) | **Proses Komputerisasi** (Persegi) | Menunjukkan pemrosesan data otomatis oleh sistem komputer/aplikasi. |
| ![Manual](https://via.placeholder.com/60x30/ffffff/000000?text=Manual) | **Proses Manual** (Trapesium) | Menunjukkan aktivitas manusia yang dilakukan secara manual tanpa sistem. |
| ![Decision](https://via.placeholder.com/60x35/ffffff/000000?text=Decision) | **Keputusan** (Belah Ketupat) | Menunjukkan titik percabangan kondisi logika dengan jalur alternatif (Ya/Tidak). |
| ![Database](https://via.placeholder.com/40x40/ffffff/000000?text=Database) | **Penyimpanan Data** (Silinder) | Menunjukkan tabel basis data (*database*) penyimpanan data persisten. |
| &rarr; | **Garis Alir (*Flowline*)** | Menunjukkan arah pergerakan alur kerja, data, atau dokumen. |

---

## 3. Flowmap Prosedur 1.0: Pengelolaan Data Master, Lisensi, dan Registrasi/Pemindaian Komputer (*Input*)

### 3.1. Deskripsi Prosedur
Prosedur ini mencakup tahap inisialisasi master data organisasi dan inventaris lisensi oleh Administrator, serta deployment agent scanner oleh Staf Laboratorium hingga payload pemindaian berhasil diterima dan disimpan di basis data.

### 3.2. Narasi Langkah demi Langkah

```
[Administrator] ──> Input Data Master & Lisensi ──> [Sistem] ──> Simpan ke DB (D1, D2, D3)
                                                        │
                                                        ▼
[Staf Lab] <── Unduh Paket Scanner (ZIP) <── Generate Paket Scanner
    │
    ▼
Deploy ke PC Lab ──> Kirim Registrasi PC ──> [Sistem] ──> Terbitkan Token API & Simpan PC (D4)
    │
    ▼
Eksekusi Scan ──> Kirim Payload Hasil Scan ──> [Sistem] ──> Dispatch ProcessScanJob ──> Simpan Scan (D4)
```

1. **Inisialisasi Master Data oleh Administrator:**
   - Administrator memulai sesi dan menginput data master pengguna, fakultas, dan laboratorium. Menghasilkan *Formulir Data Master Pengguna & Lab*.
   - Administrator menginput data lisensi komersial institusi (nomor PO, kuota limit, masa berlaku, harga satuan, bukti pembelian) dan menetapkan alokasi kuota ke fakultas. Menghasilkan *Dokumen Bukti & Kunci Lisensi*.
2. **Penyimpanan Master Data oleh Sistem:**
   - Sistem memvalidasi kelengkapan data organisasi dan menyimpannya ke tabel `D1` (Data Pengguna) dan `D2` (Data Organisasi).
   - Sistem mengenkripsi nilai kunci lisensi (`encrypted` cast) dan menyimpannya beserta data alokasi ke tabel `D3` (Data Lisensi & Katalog).
   - Berdasarkan data laboratorium yang terdaftar, sistem membuat *Paket Agent Scanner* siap pakai (berkas ZIP berisi `scanner.ps1`, `setup_tasks.ps1`, dan `config.json` yang terkonfigurasi dengan URL server dan kunci registrasi).
3. **Deployment Scanner oleh Staf Laboratorium:**
   - Staf Laboratorium mengunduh *Paket Agent Scanner* dari portal laboratorium.
   - Staf Laboratorium memasang dan menjalankan skrip scanner pada komputer-komputer laboratorium secara manual/otomatis (*Task Scheduler*).
   - Scanner membaca identitas perangkat keras (MAC Address, Serial Number, Hostname) dan mengirimkan *Data Registrasi PC* ke endpoint `POST /api/agent/register`.
4. **Registrasi dan Pemindaian Komputer:**
   - Sistem memvalidasi registrasi, menerbitkan token autentikasi API (*Sanctum Bearer Token*), dan mencatat komputer ke tabel `D4` (Data Komputer).
   - Komputer klien mengeksekusi pemindaian spesifikasi hardware (CPU, RAM, Disk, OS) dan mengumpulkan daftar seluruh software yang terpasang di registry Windows.
   - Scanner mengirimkan *Payload Hasil Pemindaian (JSON)* ke endpoint `POST /api/scan-result`.
   - Sistem menerima payload, membuat rekaman sesi scan (`ScanSession`), memperbarui spesifikasi komputer, dan mendispatch antrean kerja `ProcessScanResultJob` ke Redis Horizon. Data disimpan ke `D4` (Data Komputer & Pemindaian Software).

### 3.3. Dokumen Terkait Prosedur 1.0
- **Formulir Data Master Pengguna & Lab:** Dokumen masukan konfigurasi akun dan lokasi fisik laboratorium.
- **Dokumen Bukti & Kunci Lisensi:** Dokumen legalitas pengadaan lisensi perangkat lunak institusi.
- **Paket Agent Scanner (ZIP):** Berkas arsip perangkat lunak scanner otomatis untuk klien Windows.
- **Data Registrasi PC:** Berkas/paket data pengenal fisik komputer laboratorium.
- **Payload Hasil Pemindaian (JSON):** Paket data digital berisi spesifikasi teknis dan daftar software terinstal.

---

## 4. Flowmap Prosedur 2.0: Pemrosesan dan Evaluasi Kepatuhan Lisensi serta Persetujuan Laporan (*Proses*)

### 4.1. Deskripsi Prosedur
Prosedur ini menangani logika bisnis utama: pemrosesan antrean latar belakang (*queue processing*) untuk mengevaluasi status kepatuhan perangkat lunak terhadap lisensi, serta alur kerja pengajuan dan persetujuan draf laporan kepatuhan laboratorium oleh Kepala Laboratorium.

### 4.2. Narasi Langkah demi Langkah

```
[Queue Worker] ──> Baca Data Software (D4) & Aturan Lisensi (D3)
      │
      ├──> Cek Blacklist (Piracy Tools)? ──[Ya]──> Status: Tidak Berlisensi
      │
      ├──> Cek Kategori Freeware? ─────────[Ya]──> Status: Berlisensi
      │
      └──> Software Komersial ──> Cek Kuota & Expiry ──> Status: Berlisensi / Tidak Berlisensi
                                                                │
                                                                ▼
                                                    Simpan Hasil ke DB (D5)
                                                                │
                                                                ▼
[Admin] ──> Tinjau Rekap & Ajukan Draf Laporan ──> [Kepala Lab] ──> Telaah & Evaluasi
                                                                         │
                                                ┌────────────────────────┴────────────────────────┐
                                                ▼                                                 ▼
                                        [Kondisi: Ditolak]                                [Kondisi: Disetujui]
                                        Beri Catatan Revisi                              Beri Persetujuan Digital
                                                │                                                 │
                                                ▼                                                 ▼
                                        Simpan ke DB (D6)                                Simpan ke DB (D6)
                                        Kembali ke Admin                                 Laporan Resmi Sah
```

1. **Pemrosesan Antrean Latar Belakang (Laravel Horizon):**
   - Selesainya pemindaian pada Prosedur 1.0 memicu eksekusi `ProcessScanResultJob` dan berlanjut ke `GenerateComplianceReportJob` pada antrean prioritas Redis `compliance`.
   - Sistem mengambil data software terinstal dari `D4` dan mencocokkannya dengan master katalog serta daftar lisensi dari `D3`.
   - **Penyaringan Software (`SoftwareFilterService`):** Mengeliminasi pembaruan OS (*junk*) dan mengelompokkan software bersih.
2. **Algoritma Evaluasi Status Kepatuhan:**
   - **Pengecekan Daftar Blokir (*Blacklist Check*):** Jika software terdeteksi sebagai alat pembajakan/crack (misal: KMSPico, uTorrent), sistem langsung menetapkan status **"Tidak Berlisensi"** (*Blocked App Detected*).
   - **Pengecekan Freeware:** Jika software tergolong freeware/open-source pada katalog, sistem menetapkan status **"Berlisensi"** secara otomatis.
   - **Pengecekan Lisensi Komersial:** Untuk software komersial, sistem menghitung ketersediaan kuota alokasi fakultas dan tanggal kedaluwarsa. Jika kuota mencukupi dan masih dalam masa aktif, status ditetapkan **"Berlisensi"**; jika kuota terlampaui, status menjadi **"Tidak Berlisensi"**; jika mendekati kedaluwarsa (&le; 30 hari), status ditandai **"Grace Period"**.
   - Sistem menyimpan hasil evaluasi mutakhir ke `ComplianceReport` dan snapshot historis ke `ComplianceSnapshot` pada tabel basis data `D5`.
3. **Alur Kerja Persetujuan Laporan Laboratorium:**
   - Administrator meninjau rekap agregat kepatuhan laboratorium pada periode tertentu, kemudian mengajukan draf laporan kepatuhan ke Kepala Laboratorium. Menghasilkan dokumen *Draf Laporan Kepatuhan Laboratorium*.
   - Kepala Laboratorium membuka sistem, memeriksa data komputer dan sebaran software di laboratorium binaannya secara manual.
   - **Percabangan Keputusan:**
     - **Jika Ditolak:** Kepala Laboratorium memberikan catatan perbaikan tertulis. Sistem memperbarui status menjadi `rejected` pada tabel `D6` (Data Persetujuan Laporan) dan mengirim notifikasi kembali ke Administrator.
     - **Jika Disetujui:** Kepala Laboratorium memberikan persetujuan digital. Sistem memperbarui status menjadi `approved` pada tabel `D6`.
   - Pengesahan ini menghasilkan dokumen *Laporan Kepatuhan Telah Disahkan* yang secara otomatis membuka hak akses data laboratorium tersebut kepada pihak Pimpinan.

### 4.3. Dokumen Terkait Prosedur 2.0
- **Draf Laporan Kepatuhan Laboratorium:** Berkas elektronik pengajuan rekapitulasi data kepatuhan lab per periode tertentu.
- **Laporan Kepatuhan Telah Disahkan:** Dokumen kepatuhan resmi yang telah disetujui secara digital oleh Kepala Laboratorium.

---

## 5. Flowmap Prosedur 3.0: Penyajian Informasi, Pembuatan Laporan Resmi, dan Monitoring Eksekutif (*Output*)

### 5.1. Deskripsi Prosedur
Prosedur ini menangani penyajian informasi keluaran sistem dalam bentuk visual interaktif (dashboard), penerbitan dokumen cetak resmi (format PDF dan Excel), pengungkapan kunci lisensi yang aman, serta penyediaan data inventaris bagi operator lab.

### 5.2. Narasi Langkah demi Langkah

```
[Pimpinan] ───────> Akses Dashboard Eksekutif ──> [Sistem] ──> Filter Lab Disetujui (D6) ──> Matriks Kepatuhan Lintas Fakultas
    │
    └─────────────> Unduh Laporan Kebutuhan ───> [Sistem] ──> Generate Laporan (PDF/Excel) ──> Dokumen Analisis Kebutuhan Lisensi

[Admin] ──────────> Ekspor Laporan Institusi ──> [Sistem] ──> Generate Multi-Dokumen ───> Laporan Resmi (Kepatuhan, Aset, Log)
    │
    └─────────────> Permintaan Buka Kunci ─────> [Sistem] ──> Dekripsi & Audit Log (D7) ──> Kunci Lisensi Terbuka

[Kepala/Staf Lab] > Akses Lab Dashboard ───────> [Sistem] ──> Isolasi ScopedByLaboratory ─> Dokumen Kepatuhan & Aset PC Lab
```

1. **Monitoring dan Pelaporan Eksekutif oleh Pimpinan:**
   - Pimpinan mengakses *Dashboard Eksekutif*. Sistem mengambil data kepatuhan dari `D5` dengan memfilter hanya laboratorium yang telah berstatus disetujui (`approved`) pada `D6`.
   - Sistem menampilkan *Tampilan Matriks Kepatuhan Lintas Fakultas* yang membandingkan persentase kepatuhan antar fakultas.
   - Pimpinan dapat mengunduh *Dokumen Analisis Kebutuhan Lisensi (PDF/Excel)* yang memuat kalkulasi defisit/surplus kuota untuk pertimbangan pengadaan anggaran lisensi universitas.
2. **Penerbitan Laporan Resmi oleh Administrator:**
   - Administrator memilih parameter periode/fakultas dan mengekspor dokumen laporan institusi.
   - Sistem mengompilasi data dari seluruh tabel basis data (`D1`–`D7`) dan menghasilkan *Dokumen Laporan Resmi*:
     - *Laporan Kepatuhan Lisensi* (PDF & Excel)
     - *Laporan Inventaris Komputer & Software* (PDF & Excel)
     - *Laporan Perubahan Software* (PDF & Excel)
     - *Rekap Monitoring Pemindaian* (PDF & Excel)
     - *Laporan Log Aktivitas Sistem* (PDF)
3. **Pengungkapan Kunci Lisensi yang Aman (*Key Reveal*):**
   - Untuk keperluan audit lisensi fisik oleh auditor eksternal, Administrator dapat meminta pembukaan kunci lisensi asli.
   - Sistem menerapkan pengamanan: memverifikasi hak akses peran `admin`, membatasi frekuensi akses (*rate limiting* 10 permintaan/menit), mendekripsi kunci dari `D3`, dan mencatat identitas pemohon ke tabel `D7` (Data Log Aktivitas via Spatie ActivityLog).
   - Sistem menampilkan *Kunci Lisensi Terbuka (Audited Key Reveal)*.
4. **Penyajian Data Tingkat Laboratorium (Kepala & Staf Lab):**
   - Kepala dan Staf Laboratorium mengakses portal laboratorium masing-masing.
   - Trait `ScopedByLaboratory` pada sistem memastikan data komputer dan kepatuhan diisolasi secara ketat (*multi-tenant laboratory scope*).
   - Pengguna laboratorium menerima tampilan dashboard lokal dan dapat mencetak *Dokumen Kepatuhan Lab & Inventaris PC Lab*.

### 5.3. Dokumen Terkait Prosedur 3.0
- **Tampilan Matriks Kepatuhan Lintas Fakultas:** Antarmuka visual analitik eksekutif kepatuhan kampus.
- **Dokumen Analisis Kebutuhan Lisensi (PDF/Excel):** Dokumen perencanaan strategis pengadaan lisensi universitas.
- **Dokumen Laporan Resmi Institusi:** Berkas resmi arsip aset TI dan kepatuhan lisensi universitas.
- **Dokumen Kunci Lisensi Terbuka:** Nilai kunci lisensi legal hasil dekripsi yang telah tercatat pada audit trail.
- **Dokumen Kepatuhan Lab & Inventaris PC Lab:** Laporan rekapitulasi aset dan lisensi pada tingkat unit laboratorium.

---

## 6. Kesimpulan

Rancangan Bagan Alir Sistem (*Flowmap*) Sistem Informasi Manifest USN menyajikan visualisasi prosedural yang komprehensif mengenai bagaimana dokumen, data, dan proses bergerak antar bagian organisasi:
1. **Pemisahan Jalur Tanggung Jawab (*Swimlane*):** Memberikan batasan yang jelas antara tugas Administrator (manajemen terpusat), Staf Laboratorium (operasional teknis lab), Kepala Laboratorium (otoritas pengesahan), Pimpinan (pengawasan strategis), dan Sistem (otomatisasi pemrosesan latar belakang).
2. **Konsistensi dengan DFD Level 0, Level 1, dan Level 2:** Alur dokumen pada Flowmap merefleksikan alur data yang sama persis dengan yang digambarkan pada DFD, memperkuat koherensi metodologis perancangan sistem pada dokumen skripsi.
3. **Standar Notasi Baku:** Penggunaan simbol ANSI standar dan format monokrom hitam-putih menjamin diagram siap dicetak langsung ke dalam naskah laporan tugas akhir/skripsi tanpa kendala estetika visual.

---

## Daftar Pustaka

- Kendall, K. E., & Kendall, J. E. (2019). *Systems Analysis and Design* (10th ed.). Pearson.
- Pressman, R. S., & Maxim, B. R. (2020). *Software Engineering: A Practitioner's Approach* (9th ed.). McGraw-Hill.
- Romney, M. B., & Steinbart, P. J. (2018). *Accounting Information Systems* (14th ed.). Pearson.

---
*Dokumen ini disusun sebagai bagian dari dokumentasi perancangan sistem akademik pada Sistem Informasi Manifest USN Kolaka, Oktober 2026.*
