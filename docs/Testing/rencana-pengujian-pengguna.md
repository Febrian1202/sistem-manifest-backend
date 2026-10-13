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
