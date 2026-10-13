# RENCANA DAN INSTRUMEN PENGUJIAN PENGGUNA (USER TESTING)
## Sistem Manifest dan Kepatuhan Lisensi Perangkat Lunak Laboratorium (UniLicense)
### Universitas Sembilanbelas November Kolaka

---

**Dokumen Instrumen Penelitian Skripsi**  
- **Topik Penelitian**: Evaluasi Kebergunaan Antarmuka (*Usability*) dan Efektivitas Dukungan Pengambilan Keputusan (*Decision Support*) pada Sistem Manajemen Aset dan Audit Lisensi Software Kampus.  
- **Lokasi Pengujian**: Laboratorium Komputer Terpadu & Ruang Rapat Pimpinan, Universitas Sembilanbelas November Kolaka.  
- **Waktu Pelaksanaan**: Semester Ganjil TA 2026/2027.  
- **Penyusun / Peneliti**: Peneliti Skripsi Program Studi Sistem Informasi / Teknik Informatika.  

---

## DAFTAR ISI INSTRUMEN
1. **BAGIAN I: LANDASAN METODOLOGI & PROFIL RESPONDEN**
2. **BAGIAN II: PROTOKOL PELAKSANAAN DAN ETIKA PENGUJIAN**
3. **BAGIAN III: PAKET SKENARIO TUGAS (TASK SCENARIOS) BERBASIS RUBIN & CHISNELL**
4. **BAGIAN IV: LEMBAR OBSERVASI EVALUATOR & METRIK PERFORMA**
5. **BAGIAN V: INSTRUMEN EVALUASI PENGAMBILAN KEPUTUSAN (DECISION SUPPORT)**
6. **BAGIAN VI: INSTRUMEN KUESIONER SYSTEM USABILITY SCALE (SUS)**
7. **BAGIAN VII: METODOLOGI PENGOLAHAN DATA & PEDOMAN ANALISIS SKRIPSI (BAB 4)**

---

## BAGIAN I: LANDASAN METODOLOGI & PROFIL RESPONDEN

### 1.1 Latar Belakang & Urgensi Pengujian
Pengujian pengguna (*user testing*) empiris ini diselenggarakan sebagai pembuktian ilmiah untuk Bab 3 (Metodologi Penelitian) dan Bab 4 (Hasil dan Pembahasan) Skripsi. Pengujian ini tidak sekadar menguji ketiadaan kutu (*bug*) pada aplikasi, melainkan membuktikan secara terukur dua fokus penelitian utama:
1. **Kebergunaan Antarmuka (*Usability & User Experience*)**: Mengukur tingkat efektivitas, efisiensi, dan kepuasan pengguna saat berinteraksi dengan antarmuka sistem multiperan (Pimpinan, Kepala Lab, Staf Lab) menggunakan kerangka standar ISO/IEC 25010 dan System Usability Scale (SUS).
2. **Dukungan Pengambilan Keputusan (*Decision Support & Compliance Transparency*)**: Mengukur sejauh mana visualisasi data audit lisensi (*Executive Dashboard*, matriks antarfakultas, dan analisis kebutuhan lisensi) mampu mentransformasi data mentah komputer menjadi informasi strategis yang akurat, cepat dipahami, dan dapat diandalkan untuk perencanaan anggaran pengadaan lisensi resmi universitas.

### 1.2 Triangulasi Instrumen Pengujian
Sesuai metodologi baku pengujian kebergunaan (Rubin & Chisnell, 2008; Brooke, 1996), pengujian ini mengintegrasikan 3 instrumen komplementer:
```
+--------------------------------------------------------------------------+
| 1. SKENARIO TUGAS REALISTIS (Task Scenarios - Rubin & Chisnell)          |
|    - Stimulus misi kontekstual tanpa instruksi yang mengarahkan tombol    |
+--------------------------------------------------------------------------+
                                     |
                                     v
+--------------------------------------------------------------------------+
| 2. LEMBAR OBSERVASI EVALUATOR (Performance & Think-Aloud)                |
|    - Task Success Rate (%), Time on Task (detik), Error Count, Think-Aloud|
+--------------------------------------------------------------------------+
                                     |
                                     v
+--------------------------------------------------------------------------+
| 3. EVALUASI PASCA-UJI (Decision Accuracy & SUS 10-Item Questionnaire)     |
|    - Case Vignette (Decision Accuracy Rate %) & Skor SUS (Skala 0 - 100) |
+--------------------------------------------------------------------------+
```

### 1.3 Target Partisipan & Kriteria Inklusi Responden
Subjek uji formal penelitian ini murni difokuskan pada pengguna akhir (*end-users*) sistem di USN Kolaka:

| Kelompok Peran | Target Responden | Ukuran Sampel ($N$) | Kriteria Inklusi & Karakteristik | Fokus Pengujian |
|---|---|---|---|---|
| **Pimpinan (Eksekutif)** | Wakil Rektor, Dekan, Wakil Dekan, atau Kabag Umum & Perencanaan | $2 - 3$ Orang | Memiliki wewenang perencanaan anggaran atau kebijakan legalitas aset kampus; terbiasa membaca laporan rekapitulasi. | Kemudahan membaca dashboard eksekutif, akurasi menangkap defisit lisensi, dan keputusan pengadaan software. |
| **Kepala Laboratorium (PJ Lab)** | Dosen Kepala Lab Komputer / Koordinator Lab Fakultas | $3 - 5$ Orang | Bertanggung jawab atas operasional dan keselamatan aset software di labnya; memahami praktikum komputasi. | Validasi temuan scanner, alur persetujuan/penolakan (*review & approval*) laporan berkala, monitoring histori. |
| **Staf / Teknisi Laboratorium** | Pranata Laboratorium Pendidikan (PLP) / Teknisi Lab Komputer | $3 - 5$ Orang | Mengelola instalasi fisik PC lab, jaringan komputer lab, dan instalasi software praktikum sehari-hari. | Efisiensi penyiapan scanner per lab, inventarisasi status unit komputer, dan navigasi operasional. |
| **Total Responden Formal** | | **$N = 8 - 13$ Orang** | Sampel memenuhi standar uji usability kualitatif-kuantitatif (Nielsen: 5 responden per profil cukup mendeteksi >85% masalah kegunaan). |

> **Catatan Metodologis**: Peran **Admin Sistem** tidak dijadikan subjek uji formal untuk mencegah konflik kepentingan (*researcher bias*). Peneliti bertindak sebagai Fasilitator/Admin yang menyiapkan alokasi awal, mengirim draf laporan, dan memfasilitasi lingkungan pengujian.

---

## BAGIAN II: PROTOKOL PELAKSANAAN DAN ETIKA PENGUJIAN

### 2.1 Fase Persiapan Teknis (H-1)
1. **Kesiapan Lingkungan Sistem**:
   - Sistem dijalankan pada server lokal (*local test environment*) atau server staging kampus dengan konfigurasi basis data terisolasi.
   - Database diisi data awal (*initial state*) melalui seeder sistem resmi (`php artisan migrate:fresh --seed`) yang mencakup data fakultas, laboratorium nyata di USN Kolaka, data komputer terdistribusi, katalog software legal & komersial, serta riwayat pemindaian.
2. **Kesiapan Berkas Fisik / Cetak**:
   - Lembar Informasi Partisipan & *Informed Consent* (Formulir Persetujuan).
   - Kartu Misi Skenario Tugas (dicetak terpisah per tugas agar partisipan fokus satu per satu).
   - Lembar Observasi Evaluator (1 bundel per partisipan).
   - Kuesioner Evaluasi Keputusan & Formulir Kuesioner SUS 10 Butir.
   - Stopwatch digital / aplikasi perekam layar (*screen & audio recorder* dengan persetujuan responden).

### 2.2 Prosedur Reset Lingkungan Uji Antar-Sesi (*State Isolation*)
Untuk memastikan setiap responden menerima kondisi stimulus awal yang setara dan adil (terutama setelah tugas mutasi seperti persetujuan laporan), fasilitator wajib menerapkan jeda *buffer* 5–10 menit antar-partisipan:
```bash
# Perintah Reset Deterministik Database Pengujian
php artisan migrate:fresh --seed
```
Setelah seeding dijalankan:
- Jika sesi berikutnya adalah **Kepala Lab**, fasilitator (sebagai Admin) mengeksekusi pengiriman draf laporan kepatuhan ke lab tersebut melalui menu `/reports/submit-to-lab` agar status draf *Pending* tersedia untuk diuji.
- Jika sesi berikutnya adalah **Pimpinan**, fasilitator memastikan minimal 1 laporan lab sudah berstatus *Approved* sehingga metrik *Executive Dashboard* menampilkan data terverifikasi.

### 2.3 Naskah Pengantar Evaluator (*Briefing Script*)
Sebelum pengujian dimulai, evaluator membacakan naskah standar kepada partisipan:

> *"Selamat pagi/siang, Bapak/Ibu/Rekan-rekan. Terima kasih atas kesediaannya berpartisipasi dalam sesi pengujian sistem UniLicense (Sistem Manifest & Kepatuhan Lisensi USN Kolaka).*  
>  
> *Sebelum kita mulai, kami ingin menegaskan hal yang sangat mendasar: **kami sedang menguji kualitas dan kejelasan sistem aplikasi, BUKAN sedang menguji kemampuan Bapak/Ibu/Saudara**. Tidak ada istilah 'salah' dalam apa pun yang Anda lakukan hari ini. Jika Anda menemukan kesulitan atau kebingungan, hal tersebut murni merupakan kekurangan desain sistem kami yang perlu kami perbaiki.*  
>  
> *Selama menjalankan tugas, kami sangat memohon kesediaan Anda untuk menerapkan metode **Think-Aloud** (berpikir secara bersuara). Ungkapkan apa yang sedang Anda cari, apa yang Anda harapkan terjadi saat melihat suatu menu, dan sampaikan apa yang membuat Anda ragu atau puas. Kami akan mencatat metrik waktu dan observasi semata-mata untuk keperluan penelitian skripsi. Apakah ada pertanyaan sebelum kita mulai?"*

### 2.4 Batasan Intervensi Evaluator (*Facilitator Rule*)
- **Prinsip Pasif**: Evaluator dilarang menunjukkan letak menu, mendikte nama tombol, atau memberikan petunjuk navigasi selama partisipan masih berusaha secara mandiri.
- **Kondisi Bantuan (*Prompting*)**: Evaluator HANYA boleh memberikan bantuan berupa petunjuk konseptual (bukan instruksi klik langsung) jika partisipan mengalami kebuntuan total (*deadlock*) selama lebih dari 3 menit tanpa aksi berarti, atau jika partisipan secara eksplisit meminta bantuan.
- Jika bantuan diberikan, evaluator mencatat status tugas sebagai **"Berhasil dengan Bantuan"** pada Lembar Observasi.

---

## BAGIAN III: PAKET SKENARIO TUGAS (TASK SCENARIOS) BERBASIS RUBIN & CHISNELL

Seluruh skenario tugas disusun dalam bentuk lembar misi (*task cards*) mandiri. Setiap skenario memuat:
1. **Latar Belakang / Cerita Kontekstual**: Situasi riil yang dihadapi di kampus.
2. **Misi Pengguna**: Target akhir yang harus diselesaikan (*goal-oriented*), tanpa menyebutkan nama tombol atau navigasi teknis.
3. **Kondisi Awal Sistem**: Halaman awal saat partisipan menerima tugas.
4. **Kriteria Keberhasilan Evaluator (*Success Criteria*)**: Parameter objektif bagi evaluator untuk menilai apakah misi selesai mandiri, dengan bantuan, atau gagal.

---

### 3.1 Paket Skenario Kelompok 1: Pimpinan (Eksekutif)
*Fokus Pengujian: Decision Support, Transparansi Kepatuhan, Navigasi Ringkasan Eksekutif, & Mitigasi Risiko.*

#### SKENARIO TUGAS P-01: Tinjauan Kesehatan Aset TI & Identifikasi Defisit Lisensi Universitas
- **Konteks Masalah**:  
  Anda adalah pimpinan universitas yang baru saja menerima laporan bahwa audit lisensi perangkat lunak berkala telah selesai dilakukan di laboratorium komputer seluruh fakultas. Anda ingin mengetahui gambaran umum kepatuhan software di lingkungan USN Kolaka dan memastikan apakah ada fakultas atau laboratorium yang mengalami kekurangan lisensi legal (*defisit*).
- **Misi Pengguna**:  
  *"Silakan gunakan sistem ini untuk memeriksa kondisi kepatuhan lisensi universitas saat ini. Temukan fakultas mana yang memiliki catatan kekurangan (*defisit*) lisensi terbesar dan sebutkan informasi tersebut kepada evaluator."*
- **Kondisi Awal**: Pengguna telah masuk (*login*) dengan akun berhak akses Pimpinan dan berada pada halaman beranda/dashboard.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan berhasil menavigasi ke *Executive Dashboard* atau *Audit Kepatuhan*.
  - Partisipan mampu membaca matriks antarfakultas / kartu statistik ringkasan dan menyebutkan secara tepat nama fakultas yang mengalami defisit lisensi tanpa diarahkan evaluator.

#### SKENARIO TUGAS P-02: Perencanaan Anggaran & Rekomendasi Pengadaan Lisensi Legal
- **Konteks Masalah**:  
  Universitas sedang memasuki masa penyusunan Rencana Anggaran Biaya (RAB) tahun anggaran baru. Sebagai pengambil kebijakan, Anda perlu memutuskan software berbayar apa saja yang harus dibeli lisensi resminya agar seluruh unit komputer laboratorium terbebas dari tuntutan hukum atau sanksi audit lisensi.
- **Misi Pengguna**:  
  *"Cari informasi mengenai rekomendasi kebutuhan pengadaan lisensi baru pada sistem ini, temukan software berlisensi komersial apa yang paling banyak mengalami defisit kursi instalasi, lalu unduh dokumen rekapitulasi resmi tersebut dalam format cetak (PDF atau Excel) agar dapat Anda bawa ke rapat pimpinan."*
- **Kondisi Awal**: Pengguna berada pada halaman utama aplikasi.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan menemukan bagian Analisis Kebutuhan Lisensi (`/reports/kebutuhan-lisensi`).
  - Partisipan dapat mengidentifikasi jenis software dengan defisit lisensi tertinggi.
  - Partisipan berhasil memicu tombol ekspor (PDF atau Excel) hingga berkas terunduh/terbuka di peramban.

#### SKENARIO TUGAS P-03: Mitigasi Risiko Software Ilegal / Berbahaya
- **Konteks Masalah**:  
  Berdasarkan kebijakan keamanan siber universitas, penggunaan software bajakan, alat *crack*, maupun aplikasi *peer-to-peer* dilarang keras di jaringan kampus karena berisiko menyebarkan ransomware dan mencoreng akreditasi kampus. Anda ingin memastikan apakah ada komputer laboratorium yang melanggar aturan ini.
- **Misi Pengguna**:  
  *"Periksa apakah terdapat indikasi temuan instalasi perangkat lunak terlarang atau berbahaya pada komputer kampus, dan cari tahu di laboratorium mana komputer tersebut berada."*
- **Kondisi Awal**: Pengguna berada pada dashboard utama atau halaman laporan.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan menavigasi ke bagian Audit Kepatuhan atau Laporan Pelanggaran.
  - Partisipan berhasil menemukan rincian perangkat lunak terlarang yang terdeteksi (*blocked software*) serta lokasi laboratorium unit komputer terkait.

---

### 3.2 Paket Skenario Kelompok 2: Kepala Laboratorium (PJ Lab)
*Fokus Pengujian: Alur Review & Approval Laporan, Validasi Temuan Software, & Pengadaan Alat Scanner.*

#### SKENARIO TUGAS K-01: Verifikasi Riwayat Perubahan Software di Laboratorium
- **Konteks Masalah**:  
  Sebagai Kepala Laboratorium Komputer, Anda ingin memastikan bahwa tidak ada asisten lab atau mahasiswa praktikan yang menginstal game, aplikasi tidak resmi, atau menghapus software praktikum wajib di komputer lab Anda.
- **Misi Pengguna**:  
  *"Silakan periksa riwayat perubahan software khusus pada laboratorium yang Anda pimpin. Cari tahu apakah ada software yang baru saja ditambahkan (*added*) atau dihapus (*removed*) pada pemindaian terakhir."*
- **Kondisi Awal**: Pengguna telah masuk dengan akun Kepala Lab dan berada di dashboard utama.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan mengakses menu Inventaris Lab atau Riwayat Perubahan Software labnya.
  - Partisipan dapat melihat rincian software yang berstatus *added* atau *removed* pada salah satu komputer lab.

#### SKENARIO TUGAS K-02: Peninjauan dan Keputusan Persetujuan Draf Laporan Kepatuhan
- **Konteks Masalah**:  
  Admin Sistem telah mengirimkan draf resmi Laporan Kepatuhan Laboratorium Anda untuk periode pemindaian terbaru. Sebagai Kepala Lab, Anda memiliki wewenang untuk memvalidasi apakah data tersebut sudah benar sebelum laporan diteruskan ke Dekan dan Rektor.
- **Misi Pengguna**:  
  *"Temukan draf laporan kepatuhan laboratorium Anda yang saat ini sedang menunggu peninjauan (*pending review*). Periksa ringkasan hasilnya, lalu putuskan untuk menyetujui (*approve*) laporan tersebut (atau tolak jika Anda menemukan data yang perlu diperbaiki)."*
- **Kondisi Awal**: Pengguna berada pada halaman utama. Draf laporan berstatus *Pending* telah disiapkan fasilitator.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan berhasil menemukan menu *Review & Approval Laporan* (`/lab/reports`).
  - Partisipan membuka detail draf laporan bersangkutan.
  - Partisipan berhasil mengeksekusi aksi persetujuan (*Approve*) hingga muncul notifikasi sukses dan status laporan berubah menjadi *Approved*.

#### SKENARIO TUGAS K-03: Penyediaan Alat Pemindai (*Scanner Tool*) Laboratorium
- **Konteks Masalah**:  
  Laboratorium Anda baru saja menambah 5 unit PC baru untuk meja praktikan. Anda ingin menugaskan teknisi lab untuk memindai spesifikasi dan software pada komputer baru tersebut secara otomatis agar terhubung ke sistem manifest.
- **Misi Pengguna**:  
  *"Dapatkan paket berkas pemindai otomatis (*scanner agent*) dari sistem ini yang sudah terkonfigurasi khusus untuk laboratorium Anda agar siap dipindahkan ke flashdisk teknisi."*
- **Kondisi Awal**: Pengguna berada pada halaman utama aplikasi.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan menemukan fitur Download Scanner (`/agent/download`).
  - Partisipan memastikan laboratorium yang dipilih sesuai dengan labnya.
  - Partisipan berhasil menekan tombol download dan file ZIP/skrip terunduh.

---

### 3.3 Paket Skenario Kelompok 3: Staf / Teknisi Laboratorium
*Fokus Pengujian: Efisiensi Tugas Operasional, Inventarisasi Fisik Komputer, & Aksesibilitas Utilitas.*

#### SKENARIO TUGAS S-01: Inventarisasi dan Pengecekan Unit Komputer Bermasalah
- **Konteks Masalah**:  
  Sebagai teknisi laboratorium, Anda mendapat instruksi untuk melakukan perawatan rutin pada unit komputer yang belum pernah mengirimkan data pemindaian atau terindikasi memiliki masalah kepatuhan software di lab Anda.
- **Misi Pengguna**:  
  *"Buka daftar inventaris komputer laboratorium Anda, dan temukan salah satu komputer yang belum pernah terpindai (*unscanned*) atau berstatus tidak patuh (*non-compliant*) untuk melihat rincian spesifikasinya."*
- **Kondisi Awal**: Pengguna masuk dengan akun Staf Lab dan berada pada dashboard utama.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan membuka menu Inventaris Lab / Komputer & Aset (`/lab/inventory`).
  - Partisipan memanfaatkan kolom pencarian/filter untuk menemukan komputer target.
  - Partisipan berhasil membuka halaman detail komputer bersangkutan.

#### SKENARIO TUGAS S-02: Pengunduhan Paket Skrip Scanner Laboratorium
- **Konteks Masalah**:  
  Anda sedang berada di ruang laboratorium dan bersiap menjalankan pemindaian otomatis menggunakan PowerShell di 30 komputer praktikan.
- **Misi Pengguna**:  
  *"Unduh paket skrip pemindai resmi dari sistem ini yang siap Anda jalankan di laboratorium tempat Anda bertugas."*
- **Kondisi Awal**: Pengguna berada pada menu utama aplikasi.
- **Kriteria Keberhasilan Evaluator**:
  - Partisipan menemukan menu Download Scanner.
  - Partisipan berhasil mengunduh berkas paket agen pemindai secara mandiri.

---

### 3.4 Matriks Pemetaan Tugas ke Rute Antarmuka (*Evaluator Reference Key*)

Tabel ini menjadi panduan rahasia bagi evaluator untuk memverifikasi alur yang ditempuh partisipan:

| Kode Tugas | Peran Partisipan | Alur Halaman yang Diharapkan | Rute Aplikasi Terkait |
|---|---|---|---|
| **P-01** | Pimpinan | Dashboard -> Executive Dashboard / Audit Kepatuhan | `/dashboard`, `/compliance` |
| **P-02** | Pimpinan | Laporan -> Analisis Kebutuhan Lisensi -> Ekspor PDF/Excel | `/reports`, `/reports/kebutuhan-lisensi`, `/reports/kebutuhan-lisensi/export` |
| **P-03** | Pimpinan | Audit Kepatuhan -> Tab Pelanggaran / Pusat Laporan | `/compliance`, `/reports/kepatuhan` |
| **K-01** | Kepala Lab | Inventaris Lab / Riwayat Perubahan Software | `/lab/inventory`, `/monitoring/changes` |
| **K-02** | Kepala Lab | Menu PJ Lab -> Review Laporan -> Detail Draf -> Approve | `/lab/reports`, `/lab/reports/{id}`, `/lab/reports/{id}/approve` |
| **K-03** | Kepala Lab | Menu PJ Lab -> Download Scanner -> Unduh ZIP | `/agent/download` |
| **S-01** | Staf Lab | Menu Staff Lab -> Komputer & Aset -> Detail Komputer | `/lab/inventory`, `/lab/inventory/{id}` |
| **S-02** | Staf Lab | Menu Staff Lab -> Download Scanner -> Unduh | `/agent/download` |
