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

---

## BAGIAN IV: LEMBAR OBSERVASI EVALUATOR & METRIK PERFORMA

Lembar observasi ini berfungsi sebagai instrumen pencatatan data primer (*field notes*) bagi evaluator saat mendampingi partisipan selama sesi pengujian berlangsung.

### 4.1 Definisi Operasional Metrik Performa
Untuk memastikan objektivitas data pada Bab 4 Skripsi, setiap metrik didefinisikan secara operasional:
1. **Status Keberhasilan Tugas (*Task Success Rate*)**:
   - **Berhasil Mandiri (Skor 100%)**: Partisipan menyelesaikan misi dari awal hingga tuntas tanpa petunjuk/bimbingan evaluator.
   - **Berhasil dengan Bantuan (Skor 50%)**: Partisipan menyelesaikan misi setelah menerima arahan konseptual akibat *deadlock* > 3 menit.
   - **Gagal / Menyerah (Skor 0%)**: Partisipan menyatakan menyerah atau keluar dari alur tugas tanpa mencapai target misi.
2. **Waktu Pengerjaan (*Time on Task*)**:
   - Durasi (dihitung dalam satuan detik) yang dihabiskan sejak partisipan selesai membaca instruksi tugas hingga mencapai status akhir misi.
3. **Jumlah Kesalahan (*Error Count*)**:
   - Frekuensi tindakan keliru yang dilakukan pengguna, meliputi: salah klik menu yang tidak relevan, salah memilih filter lab, memicu eror validasi form, atau tersesat (*backtracking*) lebih dari dua kali.
4. **Catatan Kualitatif & *Think-Aloud* (Verbal Protocol)**:
   - Catatan verbatim mengenai kebingungan istilah antarmuka (*cognitive friction*), keragu-raguan memilih aksi, keluhan, maupun ungkapan kepuasan partisipan saat menemukan informasi.

---

### 4.2 Format Lembar Observasi Antarmuka Individu (Siap Cetak)

```text
========================================================================================
                    LEMBAR OBSERVASI EVALUATOR (USER TESTING)
                  SISTEM MANIFEST & KEPATUHAN LISENSI (UNILICENSE)
========================================================================================
Kode Partisipan   : [ P-01 / K-01 / S-01 ]       Tanggal Uji     : _____________________
Nama Partisipan   : ___________________________   Waktu Mulai     : _____ : _____ WITA
Unit / Fakultas   : ___________________________   Waktu Selesai   : _____ : _____ WITA
Kelompok Peran    : [ ] Pimpinan   [ ] Kepala Lab   [ ] Staf/Teknisi Lab
Nama Evaluator    : ___________________________
========================================================================================
```

#### Tabel Pencatatan Sesi Observasi

| No. Tugas | Ringkasan Misi | Status Keberhasilan (*Task Success*) | Waktu (*Time on Task*) | Jumlah Galat (*Error Count*) | Catatan Perilaku, Titik Friksi, & *Think-Aloud* |
|---|---|---|---|---|---|
| **Tugas 1** | *(Misal: Identifikasi defisit lisensi / Riwayat perubahan)* | - [ ] Berhasil Mandiri (100%)<br>- [ ] Berhasil dg Bantuan (50%)<br>- [ ] Gagal/Menyerah (0%) | ___ Menit<br>___ Detik<br>*(Total: ___ dt)* | ___ kali salah klik / navigasi | *Kutipan/Perilaku:*<br><br> |
| **Tugas 2** | *(Misal: Analisis kebutuhan lisensi & cetak / Review laporan)* | - [ ] Berhasil Mandiri (100%)<br>- [ ] Berhasil dg Bantuan (50%)<br>- [ ] Gagal/Menyerah (0%) | ___ Menit<br>___ Detik<br>*(Total: ___ dt)* | ___ kali salah klik / navigasi | *Kutipan/Perilaku:*<br><br> |
| **Tugas 3** | *(Misal: Deteksi software ilegal / Download scanner)* | - [ ] Berhasil Mandiri (100%)<br>- [ ] Berhasil dg Bantuan (50%)<br>- [ ] Gagal/Menyerah (0%) | ___ Menit<br>___ Detik<br>*(Total: ___ dt)* | ___ kali salah klik / navigasi | *Kutipan/Perilaku:*<br><br> |

**Catatan Umum Evaluator terhadap Gestur & Respon Responden:**  
________________________________________________________________________________________  
________________________________________________________________________________________  

---

### 4.3 Format Tabel Master Rekapitulasi Data Lapangan (Untuk Bab 4 Skripsi)

Tabel berikut digunakan peneliti untuk mentabulasi hasil observasi seluruh responden:

| Kode Resp. | Peran Pengguna | Tugas | Status Keberhasilan | Nilai Sukses (%) | Waktu (Detik) | Jumlah Galat | Titik Friksi Utama yang Ditemui |
|---|---|---|---|---|---|---|---|
| **R-01** | Pimpinan | P-01 | Mandiri | 100% | 45 | 0 | Tidak ada kendala; langsung melihat kartu defisit. |
| **R-01** | Pimpinan | P-02 | Mandiri | 100% | 72 | 1 | Sempat mencari tombol ekspor di dashboard sebelum ke laporan. |
| **R-01** | Pimpinan | P-03 | Mandiri | 100% | 55 | 0 | Cepat mengenali indikator merah pelanggaran. |
| **R-02** | Kepala Lab | K-01 | Mandiri | 100% | 60 | 0 | Membaca tab filter *added* dengan lancar. |
| **R-02** | Kepala Lab | K-02 | Mandiri | 100% | 85 | 1 | Memeriksa rincian tabel sebelum menekan tombol *Approve*. |
| **R-02** | Kepala Lab | K-03 | Mandiri | 100% | 35 | 0 | Langsung menemukan tombol download scanner. |
| **R-03** | Staf Lab | S-01 | Mandiri | 100% | 50 | 0 | Memanfaatkan pencarian nama PC. |
| **R-03** | Staf Lab | S-02 | Mandiri | 100% | 30 | 0 | Alur unduh sangat intuitif. |
| ... | ... | ... | ... | ... | ... | ... | ... |
| **Rata-rata** | | | | **___ %** | **___ dt** | **___ kali** | *(Diringkas untuk analisis Bab 4)* |

---

## BAGIAN V: INSTRUMEN EVALUASI PENGAMBILAN KEPUTUSAN (DECISION SUPPORT)

Fokus utama penelitian skripsi ini adalah membuktikan bahwa sistem tidak hanya mudah digunakan, melainkan secara fungsional **mendukung pengambilan keputusan strategis (*Decision Support*)** bagi pimpinan universitas. Oleh karena itu, evaluasi ini menggabungkan pengujian akurasi keputusan objektif berbasis studi kasus dengan kuesioner persepsi subjektif.

---

### 5.1 Uji Akurasi Keputusan Objektif (*Objective Decision Task & Case Vignette*)
Setelah menyelesaikan Skenario P-01 dan P-02, partisipan kelompok **Pimpinan** diberikan lembar pertanyaan studi kasus berbasis data aktual yang baru saja mereka lihat pada sistem.

```text
========================================================================================
             LEMBAR STUDI KASUS KEPUTUSAN STRATEGIS (KELOMPOK PIMPINAN)
========================================================================================
Kode Partisipan : [ P-___ ]                          Waktu Pengerjaan : ___ Menit
========================================================================================
Petunjuk:
Berdasarkan data audit lisensi yang Anda lihat pada Dashboard Eksekutif dan Laporan 
Analisis Kebutuhan Lisensi, jawablah 3 pertanyaan analisis berikut:
```

#### Soal Kasus 1 (Identifikasi Prioritas Pengadaan)
> *"Berdasarkan data sistem, sebutkan **2 (dua) software komersial** yang mengalami defisit lisensi paling tinggi di tingkat universitas dan paling mendesak untuk dianggarkan lisensi resminya?"*
- **Jawaban Partisipan**: 1) __________________________________   2) __________________________________
- **Kunci Jawaban Sistem (*Ground Truth*)**: Sesuai katalog komersial aktif pada seeder (misal: *Microsoft Office Professional Plus* dan *Adobe Creative Cloud* / *AutoCAD*).
- **Rubrik Penilaian Evaluator**:
  - [ ] **Skor 100%**: Menyebutkan kedua software dengan tepat.
  - [ ] **Skor 50%**: Menyebutkan tepat satu software.
  - [ ] **Skor 0%**: Salah menyebutkan atau menyatakan tidak menemukan.

#### Soal Kasus 2 (Estimasi Besaran Kebutuhan Unit Lisensi)
> *"Berapa **total kekurangan unit (*total deficit seats*)** yang harus diajukan universitas dalam usulan pengadaan lisensi baru untuk software-software yang mengalami defisit tersebut?"*
- **Jawaban Partisipan**: ____________ unit / kursi lisensi.
- **Kunci Jawaban Sistem (*Ground Truth*)**: Angka eksak akumulasi kolom defisit pada tabel Bagian I/II Laporan Kebutuhan Lisensi.
- **Rubrik Penilaian Evaluator**:
  - [ ] **Skor 100%**: Angka persis sesuai tabel laporan.
  - [ ] **Skor 50%**: Angka mendekati (selisih $\le 2$ unit karena pembulatan atau membaca tabel per-fakultas).
  - [ ] **Skor 0%**: Angka jauh meleset atau tidak dapat menemukan angka.

#### Soal Kasus 3 (Penentuan Sumber Masalah / Alokasi Anggaran)
> *"Fakultas dan laboratorium mana yang menyumbang instalasi tanpa lisensi (*defisit*) paling banyak untuk software komersial tersebut?"*
- **Jawaban Partisipan**: Fakultas: ________________________  Laboratorium: ________________________
- **Kunci Jawaban Sistem (*Ground Truth*)**: Entitas fakultas & lab dengan angka instalasi melebihi alokasi tertinggi di matriks kepatuhan.
- **Rubrik Penilaian Evaluator**:
  - [ ] **Skor 100%**: Fakultas dan Lab terjawab tepat.
  - [ ] **Skor 50%**: Fakultas tepat, namun lab keliru.
  - [ ] **Skor 0%**: Salah total.

---

### 5.2 Formula Komputasi Metrik Akurasi Keputusan (*Decision Accuracy Rate*)
Metrik akurasi keputusan dihitung secara kuantitatif untuk dilaporkan pada Bab 4:

$$\text{Decision Accuracy Rate (DAR)}_j = \frac{\text{Skor Kasus 1} + \text{Skor Kasus 2} + \text{Skor Kasus 3}}{3} \times 100\%$$

$$\overline{\text{DAR}} = \frac{1}{M} \sum_{j=1}^{M} \text{DAR}_j$$

*Keterangan*:  
- $\text{DAR}_j$ = Tingkat akurasi keputusan partisipan pimpinan ke-$j$.  
- $M$ = Jumlah total responden pimpinan ($N = 2 - 3$).  
- **Tolok Ukur Keberhasilan Skripsi**: Nilai $\overline{\text{DAR}} \ge 80\%$ membuktikan secara empiris bahwa antarmuka sistem efektif dalam mengomunikasikan data kepatuhan untuk pengambilan keputusan pimpinan.

---

### 5.3 Kuesioner Persepsi Subjektif Dukungan Keputusan
Responden kelompok Pimpinan dan Kepala Lab memberikan penilaian persepsi kognitif menggunakan skala Likert 1–5:  
*(1: Sangat Tidak Setuju, 2: Tidak Setuju, 3: Netral/Ragu-ragu, 4: Setuju, 5: Sangat Setuju)*

| No | Pernyataan Evaluasi Keputusan (*Decision Perception Statements*) | Skor (1 - 5) |
|---|---|---|
| **D-01** | Visualisasi grafik status lisensi dan tabel matriks antarfakultas mempermudah saya memahami kondisi legalitas software kampus secara cepat tanpa harus membuka rekapan fisik. | [ 1 ] [ 2 ] [ 3 ] [ 4 ] [ 5 ] |
| **D-02** | Informasi rincian defisit lisensi disajikan secara transparan dan akurat sehingga memberikan keyakinan kuat dalam menetapkan prioritas anggaran pengadaan tahunan. | [ 1 ] [ 2 ] [ 3 ] [ 4 ] [ 5 ] |
| **D-03** | Pembedaan status kepatuhan (*Compliant* vs *Non-Compliant*) serta deteksi software terlarang membantu saya mengidentifikasi risiko hukum dan keamanan siber di kampus secara dini. | [ 1 ] [ 2 ] [ 3 ] [ 4 ] [ 5 ] |
| **D-04** | Format ekspor laporan (PDF/Excel) yang disediakan sistem sudah memenuhi standar kelayakan dokumen pendukung rapat pimpinan universitas. | [ 1 ] [ 2 ] [ 3 ] [ 4 ] [ 5 ] |

---

## BAGIAN VI: INSTRUMEN KUESIONER SYSTEM USABILITY SCALE (SUS)

Setelah seluruh rangkaian skenario tugas selesai dikerjakan, setiap partisipan (Pimpinan, Kepala Lab, dan Staf Lab) diminta mengisi instrumen evaluasi subjektif standar **System Usability Scale (SUS)** (John Brooke, 1996).

### 6.1 Petunjuk Pengisian Kuesioner
1. Kuesioner ini terdiri atas 10 butir pernyataan mengenai persepsi Anda saat menggunakan sistem UniLicense.
2. Berikan tanda centang ($\checkmark$) atau tanda silang ($\times$) pada salah satu angka skala 1 sampai 5:
   - **1** = Sangat Tidak Setuju (STS)
   - **2** = Tidak Setuju (TS)
   - **3** = Ragu-ragu / Netral (N)
   - **4** = Setuju (S)
   - **5** = Sangat Setuju (SS)
3. Isilah berdasarkan reaksi spontan pertama Anda tanpa berpikir terlalu lama. Semua butir wajib diisi.

---

### 6.2 Lembar Kuesioner SUS 10 Butir (Bahasa Indonesia Baku)

```text
========================================================================================
                     KUESIONER SYSTEM USABILITY SCALE (SUS)
========================================================================================
Kode Partisipan : [ __________ ]                Tanggal : ______________________________
Peran Pengguna  : [ ] Pimpinan   [ ] Kepala Lab   [ ] Staf/Teknisi Lab
========================================================================================
```

| No | Pernyataan Kuesioner Usability | STS (1) | TS (2) | N (3) | S (4) | SS (5) |
|---|---|:---:|:---:|:---:|:---:|:---:|
| **1** | Saya berpikir bahwa saya ingin sering menggunakan sistem ini. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **2** | Saya merasa sistem ini terlalu rumit untuk digunakan. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **3** | Saya merasa sistem ini mudah untuk digunakan. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **4** | Saya merasa membutuhkan bantuan teknisi atau orang lain untuk dapat menggunakan sistem ini. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **5** | Saya merasa fungsi-fungsi dalam sistem ini terintegrasi dengan baik. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **6** | Saya merasa terlalu banyak hal yang tidak konsisten pada sistem ini. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **7** | Saya merasa orang lain akan memahami cara menggunakan sistem ini dengan sangat cepat. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **8** | Saya merasa sistem ini sangat membingungkan saat digunakan. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **9** | Saya merasa tidak ada hambatan saat menggunakan sistem ini. | [ ] | [ ] | [ ] | [ ] | [ ] |
| **10** | Saya perlu mempelajari banyak hal terlebih dahulu sebelum dapat menggunakan sistem ini dengan lancar. | [ ] | [ ] | [ ] | [ ] | [ ] |

---

## BAGIAN VII: METODOLOGI PENGOLAHAN DATA & PEDOMAN ANALISIS SKRIPSI (BAB 4)

Bagian ini dirancang sebagai panduan kalkulasi kuantitatif dan interpretasi ilmiah bagi peneliti dalam menyusun Bab 4 Skripsi.

---

### 7.1 Formula Matematis Perhitungan Skor SUS (John Brooke, 1996)
Skor SUS tidak dihitung sebagai rata-rata mentah (skala 1–5), melainkan melalui proses normalisasi kontribusi skor ganjil dan genap untuk menghasilkan nilai skala standar **0 – 100**:

1. **Normalisasi Butir Ganjil (Pernyataan Positif: 1, 3, 5, 7, 9)**:  
   Setiap butir ganjil dikurangi dengan angka 1:
   $$R_i = X_i - 1 \quad (\text{untuk } i \in \{1, 3, 5, 7, 9\})$$
   *(Rentang nilai kontribusi: 0 sampai 4)*

2. **Normalisasi Butir Genap (Pernyataan Negatif: 2, 4, 6, 8, 10)**:  
   Angka 5 dikurangi dengan nilai skala respon:
   $$R_i = 5 - X_i \quad (\text{untuk } i \in \{2, 4, 6, 8, 10\})$$
   *(Rentang nilai kontribusi: 0 sampai 4)*

3. **Perhitungan Total Skor SUS Individu**:  
   Jumlahkan seluruh nilai kontribusi $R_i$, kemudian kalikan dengan faktor koreksi $2.5$:
   $$\text{Skor SUS}_k = \left( \sum_{i=1}^{10} R_i \right) \times 2.5$$

4. **Perhitungan Rata-rata Skor SUS Keseluruhan (*Mean SUS Score*)**:
   $$\overline{\text{SUS}} = \frac{1}{N} \sum_{k=1}^{N} \text{Skor SUS}_k$$
   *di mana $N$ adalah jumlah total responden penelitian ($N = 8 - 13$).*

---

### 7.2 Contoh Komputasi Langkah demi Langkah (Simulasi Numerik)
Jika Responden $R_1$ memberikan respon mentah:  
`Q1: 4, Q2: 2, Q3: 5, Q4: 1, Q5: 4, Q6: 2, Q7: 4, Q8: 1, Q9: 5, Q10: 2`

- Kontribusi Ganjil:  
  - $R_1 = 4 - 1 = 3$  
  - $R_3 = 5 - 1 = 4$  
  - $R_5 = 4 - 1 = 3$  
  - $R_7 = 4 - 1 = 3$  
  - $R_9 = 5 - 1 = 4$  
  *Subtotal Ganjil = 17*
- Kontribusi Genap:  
  - $R_2 = 5 - 2 = 3$  
  - $R_4 = 5 - 1 = 4$  
  - $R_6 = 5 - 2 = 3$  
  - $R_8 = 5 - 1 = 4$  
  - $R_{10} = 5 - 2 = 3$  
  *Subtotal Genap = 17*
- Total Kontribusi = $17 + 17 = 34$  
- **Skor Akhir SUS** = $34 \times 2.5 = \mathbf{85.0}$ (*Kategori: Grade A, Excellent*).

---

### 7.3 Kerangka Interpretasi Standar Skor SUS untuk Bab 4 Skripsi
Untuk membahas temuan skor SUS pada sidang skripsi, peneliti mengacu pada klasifikasi standar yang dirumuskan oleh **Bangor, Kortum, & Miller (2008)** serta **Sauro (2011)**:

```
 Skor SUS:  0 -------- 50 -------- 68 -------- 74 -------- 80.3 -------- 100
             |          |           |           |           |              |
 Grade:      |    F     |     D     |     C     |     B     |      A       |
 Rating:     |   Poor   |    OK     |   Good    | Excellent | Best Imagin. |
 Accept.:    | Not Acceptable |  Marginal  |            Acceptable         |
 Benchmark:  |                | Rata-rata  |                               |
             |                | Industri   |                               |
```

| Rentang Skor SUS | Tingkat Kelayakan (*Acceptability*) | Skala Huruf (*Grade Scale*) | Predikat (*Adjective Rating*) | Estimasi Promosi (*NPS*) |
|:---:|:---:|:---:|:---:|:---:|
| **$\ge 80.3$** | **Acceptable (Sangat Layak)** | **Grade A** | **Excellent / Best Imaginable** | Promoter |
| **$74.0 - 80.2$** | **Acceptable (Layak)** | **Grade B** | **Good** | Passive / Promoter |
| **$68.0 - 73.9$** | **Acceptable (Rata-rata)** | **Grade C** | **Good (Average)** | Passive |
| **$51.0 - 67.9$** | **Marginal (Perlu Perbaikan)** | **Grade D** | **OK** | Detractor |
| **$< 51.0$** | **Not Acceptable (Tidak Layak)** | **Grade F** | **Poor / Worst Imaginable** | Detractor |

*Tolok Ukur Kelulusan Usability Skripsi*:  
Sistem dinyatakan berhasil memenuhi standar kebergunaan yang baik apabila menghasilkan rata-rata skor SUS gabungan $\mathbf{> 68.0}$ (melebihi rata-rata industri global) dan masuk ke dalam kategori **Acceptable**.

---

### 7.4 Struktur Pembahasan Hasil Pengujian di Bab 4 Skripsi

Untuk menyusun narasi Bab 4 Skripsi secara sistematis, peneliti disarankan membagi pembahasan ke dalam 4 sub-bab berikut:

#### 1. Analisis Metrik Efektivitas & Efisiensi Tugas (*Task Performance*)
- Sajikan tabel rekapitulasi *Task Success Rate (%)*, *Mean Time on Task (detik)*, dan *Error Count*.
- Buat grafik batang perbandingan tingkat keberhasilan tugas antar-peran.
- Bahas tugas mana yang membutuhkan waktu paling lama dan identifikasi penyebabnya berdasarkan catatan evaluator.

#### 2. Analisis Efektivitas Dukungan Keputusan (*Decision Support Validation*)
- Laporkan persentase rata-rata *Decision Accuracy Rate (DAR %)* kelompok Pimpinan.
- Bahas ketepatan Pimpinan dalam mengidentifikasi defisit lisensi dan estimasi kebutuhan unit lisensi baru berdasarkan studi kasus.
- Sajikan tabulasi skor rata-rata persepsi kognitif (Pernyataan D-01 s.d D-04) untuk membuktikan peningkatan transparansi audit aset kampus.

#### 3. Analisis Skor Kebergunaan Sistem (*System Usability Scale Analysis*)
- Tampilkan tabel tabulasi skor individual seluruh responden ($N = 8 - 13$) dan hitung nilai $\overline{\text{SUS}}$ agregat serta standar deviasinya.
- Tampilkan analisis komparasi deskriptif rata-rata skor SUS antarkelompok peran:
  - $\overline{\text{SUS}}_{\text{Pimpinan}}$ vs $\overline{\text{SUS}}_{\text{Kepala Lab}}$ vs $\overline{\text{SUS}}_{\text{Staf Lab}}$.
- Tempatkan nilai akhir pada grafik interpretasi Bangor et al. (menentukan *Grade*, *Adjective*, dan *Acceptability*).

#### 4. Triangulasi Kualitatif & Rekomendasi Perbaikan Antarmuka (*Actionable Recommendations*)
- Silangkan temuan butir SUS yang mendapat nilai koreksi terendah (misal butir bernada "membingungkan" atau "membutuhkan bantuan") dengan data *Think-Aloud* dan titik *Error Count*.
- Susun tabel tindak lanjut rekomendasi perbaikan desain antarmuka (*design recommendation table*):
  - **Masalah Usability yang Ditemukan**: (Contoh: Label filter periode kurang mencolok).
  - **Bukti Empiris Lapangan**: (Contoh: 2 responden Kepala Lab sempat ragu selama 25 detik).
  - **Rekomendasi Perbaikan UI**: (Contoh: Menambahkan ikon kalender dan indikator aktif berwarna kontras).

| No | Komponen UI / Alur | Isu Kebergunaan yang Teramati | Bukti Empiris (Think-Aloud / Error) | Rekomendasi Solusi Desain UI/UX | Prioritas |
|---|---|---|---|---|:---:|
| 1 | Filter Periode & Lab | Pengguna ragu apakah tabel otomatis ter-update saat filter diubah. | Responden berhenti sejenak mencari tombol "Terapkan". | Berikan indikator *loading state* halus atau tombol eksplisit "Filter". | Medium |
| 2 | Notifikasi Approval | Kepala Lab ingin melihat pratinjau PDF sebelum menyetujui. | Responden mencari tombol pratinjau dokumen resmi. | Pastikan tombol "Pratinjau PDF" diletakkan berdampingan dengan tombol *Approve*. | High |
| 3 | Ekspor Analisis Kebutuhan | Ikon ekspor Excel/PDF di pojok kanan atas sempat terlewatkan. | Pimpinan membutuhkan waktu >40 detik mencari tombol ekspor. | Berikan label teks kontras tinggi dengan ikon yang familiar. | Low |

---

## LAMPIRAN TAMBAHAN: FORMULIR PERSETUJUAN PARTISIPAN (INFORMED CONSENT)

```text
========================================================================================
                 SURAT PERNYATAAN KESEDIAAN MENJADI RESPONDEN
                      (INFORMED CONSENT USER TESTING)
========================================================================================

Saya yang bertanda tangan di bawah ini:
Nama Lengkap      : ____________________________________________________________________
Jabatan / Unit    : ____________________________________________________________________
Instansi          : Universitas Sembilanbelas November Kolaka

Menyatakan dengan sesungguhnya bahwa:
1. Saya telah menerima penjelasan menyeluruh mengenai maksud dan tujuan pengujian sistem
   UniLicense (Sistem Manifest & Kepatuhan Lisensi USN Kolaka) untuk keperluan skripsi.
2. Saya memahami bahwa pengujian ini semata-mata menguji kebergunaan sistem aplikasi,
   bukan menguji kemampuan atau kinerja pribadi saya.
3. Saya bersedia berpartisipasi secara sukarela tanpa paksaan, bersedia menjalankan skenario
   tugas yang diberikan, serta mengisi kuesioner evaluasi secara objektif dan jujur.
4. Saya mengizinkan data interaksi, metrik performa, dan masukan kualitatif yang saya berikan
   digunakan sebagai data penelitian skripsi dengan tetap menjaga kerahasiaan identitas pribadi.

Demikian pernyataan ini saya buat dengan sadar dan sukarela untuk dipergunakan sebagaimana
mestinya.

                                             Kolaka, ______________________ 2026
                                             Partisipan Pengujian,



                                             ( ________________________________ )
========================================================================================
```


