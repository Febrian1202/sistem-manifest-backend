# Desain Spesifikasi Perencanaan User Testing Sistem Manifest & Kepatuhan Lisensi USN Kolaka

**Dokumen**: Desain Instrumen & Skenario Pengujian Pengguna (*User Testing*) untuk Skripsi  
**Tanggal**: 13 Oktober 2026  
**Penulis**: Ridaz & Tim Pengembang  
**Tujuan**: Menjadi acuan perancangan dokumen rencana pengujian pengguna komprehensif (`docs/Testing/rencana-pengujian-pengguna.md`) guna keperluan pengujian empiris Bab 3 dan Bab 4 Skripsi.

---

## 1. Latar Belakang & Tujuan (Intent & The Why)

Sistem Manifest dan Kepatuhan Lisensi Perangkat Lunak USN Kolaka dikembangkan untuk mengatasi permasalahan tata kelola aset TI dan risiko lisensi perangkat lunak di lingkungan perguruan tinggi. Untuk membuktikan efektivitas sistem dalam konteks penelitian skripsi, pengujian tidak cukup hanya dilakukan pada level kode (*automated test*), melainkan harus melalui pengujian pengguna riil (*empirical user testing*).

Sesuai materi acuan pada `docs/Testing/user-testing.md`, pengujian memadukan tiga instrumen terstandarisasi:
1. **Skenario Tugas (*Task Scenarios*)**: Stimulus misi realistis dengan prinsip Rubin & Chisnell.
2. **Lembar Observasi Antarmuka**: Instrumen pencatatan metrik performa (*Task Success*, *Time on Task*, *Error Count*, dan catatan kualitatif *Think-Aloud*).
3. **Kuesioner Pasca-Pengujian (*Post-Test Questionnaire*)**: Standar *System Usability Scale* (SUS) untuk mengukur tingkat kebergunaan subjektif.

### Fokus Pembuktian Skripsi
Berdasarkan rumusan masalah penelitian, pengujian ini difokuskan untuk membuktikan dua hal utama:
1. **Kebergunaan Antarmuka (*Usability & User Experience*)**: Membuktikan kemudahan operasional antarmuka antarmultiperan (staf teknis, kepala lab, pimpinan), tingkat kesalahan pengguna, serta pencapaian skor SUS pada kategori *Acceptable / Good*.
2. **Dukungan Pengambilan Keputusan (*Decision Support & Compliance Transparency*)**: Membuktikan bahwa visualisasi data kepatuhan (*Executive Dashboard*, matriks antarfakultas, dan laporan kebutuhan lisensi) secara efektif mentransformasi data mentah aset menjadi informasi strategis yang akurat untuk perencanaan anggaran dan mitigasi risiko pelanggaran lisensi.

---

## 2. Sasaran, Batasan, & Anti-Goals

### Goals
- Merancang paket skenario tugas yang kontekstual, berorientasi tujuan (*goal-oriented*), realistis, dan non-mengarahkan (*avoid leading instructions*) untuk 3 kelompok peran utama (Pimpinan, Kepala Lab, Staf Lab) ditambah peran Admin sebagai pelengkap.
- Menyusun format Lembar Observasi Evaluator terstandarisasi yang siap dicetak/diisi langsung di lapangan selama sesi observasi.
- Menyusun instrumen kuesioner System Usability Scale (SUS) 10 butir berbahasa Indonesia baku, dilengkapi rumus konversi skor, skala penilaian (Bangor et al. & Sauro), dan panduan analisis data untuk penyusunan Bab 4 Skripsi.
- Merumuskan pertanyaan evaluasi pasca-tugas (*Post-Task Decision Questions*) untuk menguji secara spesifik aspek kognitif pendukung keputusan (pemahaman defisit lisensi, urgensi pengadaan, dan deteksi software berisiko).
- Menyusun panduan protokol etika pengujian dan prosedur persiapan teknis (lingkungan server, akun uji, dan data seeder).

### Non-Goals
- Tidak mengubah logika bisnis aplikasi Laravel, skema database, atau rute sistem yang ada.
- Tidak menyertakan eksekusi pengujian dengan responden manusia riil di dalam dokumen ini (dokumen ini adalah instrumen pengujiannya).
- Tidak menggunakan alat eye-tracking atau sensor biometrik (cukup observasi langsung + metrik waktu/galat + think-aloud).

### Anti-Goals
- **Instruksi Mengarahkan / Prosedural**: Menghindari skenario yang mendikte klik tombol atau nama menu persis (contoh salah: *"Klik menu 'Inventaris Lisensi' di sidebar kiri, lalu klik tombol biru 'Tambah Lisensi'"*). Skenario harus memberikan situasi dan target hasil akhir.
- **Skenario Mengambang Tanpa Tolok Ukur Keberhasilan**: Setiap skenario wajib memiliki definisi selesai (*Definition of Done*) yang terukur bagi evaluator.

---

## 3. Profil & Karakteristik Partisipan Pengujian

Pengujian melibatkan responden yang merepresentasikan stakeholder tata kelola TI di USN Kolaka:

| Kelompok Peran | Karakteristik / Target Responden | Jumlah Sampel Rekomendasi | Fokus Pengujian |
|---|---|---|---|
| **Pimpinan (Eksekutif)** | Wakil Rektor, Dekan Fakultas, atau Pejabat Struktural TI | 2 - 3 orang | Kemudahan menyerap informasi ringkasan eksekutif, akurasi identifikasi defisit lisensi, dan ketepatan pengambilan keputusan alokasi anggaran pengadaan. |
| **Kepala Laboratorium (PJ Lab)** | Dosen Kepala Lab / Koordinator Laboratorium Komputer | 3 - 5 orang | Validasi kepatuhan software di komputernya, alur persetujuan/penolakan (*review & approval*) laporan berkala lab, penelusuran histori perubahan software. |
| **Staf / Teknisi Laboratorium** | Pranata Laboratorium Pendidikan (PLP), Teknisi Jaringan/Lab | 3 - 5 orang | Efisiensi penyiapan scanner per lab, monitoring kondisi unit komputer fisik lab, pengoperasian utilitas manifest. |
| **Admin Sistem (Fasilitator)** | Peneliti / Pengembang Sistem (Bukan Subjek Uji Formal) | 1 orang | Menyiapkan alokasi lisensi awal, mendistribusikan draf laporan kepatuhan ke PJ Lab, serta melakukan reset database antar-sesi uji. |

---

## 4. Rancangan Skenario Tugas (*Task Scenarios*) per Peran

Setiap skenario dirancang dengan struktur: **Konteks/Cerita Masalah**, **Misi Pengguna (Non-leading)**, dan **Kriteria Keberhasilan bagi Evaluator (*Success Criteria*)**.

### A. Kelompok Pimpinan (Fokus: *Decision Support & Strategic Dashboard*)
- **Skenario P-01: Tinjauan Kesehatan Aset TI Universitas**
  - *Konteks*: Anda baru saja menerima tugas untuk meninjau status kepatuhan software di seluruh laboratorium kampus USN Kolaka pada periode berjalan.
  - *Misi*: Gunakan sistem untuk mencari tahu apakah ada fakultas atau laboratorium yang mengalami defisit lisensi (menggunakan software melebihi kuota sah), dan identifikasi fakultas mana yang paling membutuhkan perhatian.
  - *Kriteria Evaluator*: Partisipan berhasil membuka *Executive Dashboard*, membaca matriks status fakultas/kartu defisit, dan menyebutkan dengan benar fakultas/lab yang defisit tanpa dibimbing.
- **Skenario P-02: Perencanaan Anggaran & Rekomendasi Pengadaan Lisensi**
  - *Konteks*: Universitas sedang menyusun Rencana Anggaran Biaya (RAB) tahunan untuk pengadaan lisensi perangkat lunak legal. Pimpinan memerlukan dokumen rekapitulasi kebutuhan lisensi resmi.
  - *Misi*: Temukan data analisis kebutuhan lisensi pada aplikasi, periksa rincian kekurangan lisensi komersial terbesar, dan siapkan dokumen tersebut dalam format cetak (PDF/Excel) untuk dibawa ke rapat pimpinan.
  - *Kriteria Evaluator*: Partisipan berhasil menemukan menu Analisis Kebutuhan Lisensi, melihat software dengan defisit tertinggi, dan menekan opsi ekspor PDF/Excel hingga file terunduh/terbuka.
- **Skenario P-03: Mitigasi Risiko Software Ilegal/Berbahaya**
  - *Konteks*: Beredar laporan indikasi instalasi aplikasi crack/aktivator ilegal di komputer kampus yang berisiko malware. Pimpinan ingin memastikan apakah ada komputer yang terdeteksi melanggar kebijakan universitas.
  - *Misi*: Telusuri bagian audit kepatuhan atau pelanggaran pada sistem untuk mengetahui apakah terdapat temuan software terlarang di komputer kampus dan lab mana yang bersangkutan.
  - *Kriteria Evaluator*: Partisipan berhasil menavigasi ke Audit Kepatuhan / Laporan Pelanggaran dan mengidentifikasi unit komputer yang terkena bendera pelanggaran (*violation/blocked software*).

### B. Kelompok Kepala Laboratorium (Fokus: *Review, Approval, & Monitoring*)
- **Skenario K-01: Verifikasi Riwayat Perubahan Software Lab**
  - *Konteks*: Mahasiswa dan praktikan baru saja menyelesaikan semester perkuliahan. Anda ingin memastikan software apa saja yang baru terpasang atau dihapus di komputer lab Anda dalam pemindaian terakhir.
  - *Misi*: Periksa daftar komputer dan riwayat perubahan perangkat lunak khusus pada laboratorium Anda untuk mendeteksi software yang tidak resmi dimasukkan.
  - *Kriteria Evaluator*: Partisipan membuka inventaris lab atau menu perubahan software lab, memfilter lab miliknya, dan mengamati detail software *Added/Removed*.
- **Skenario K-02: Peninjauan dan Persetujuan Draf Laporan Kepatuhan**
  - *Konteks*: Admin Sistem telah mengirimkan draf Laporan Kepatuhan berkala laboratorium Anda untuk periode terbaru. Sebagai penanggung jawab, Anda harus memvalidasi data tersebut sebelum disahkan ke pimpinan universitas.
  - *Misi*: Temukan berkas draf laporan yang sedang menunggu ulasan Anda, periksa ringkasannya, lalu berikan keputusan resmi (setujui laporan tersebut atau tolak dengan menyertakan catatan revisi).
  - *Kriteria Evaluator*: Partisipan masuk ke halaman *Review & Approval Laporan*, membuka detail draf yang berstatus *Pending*, meninjau isinya, dan mengeksekusi aksi *Approve* (atau *Reject* dengan catatan) hingga status berubah.
- **Skenario K-03: Penyediaan Agen Scanner untuk Laboratorium**
  - *Konteks*: Anda ingin meminta asisten lab melakukan pemindaian ulang pada 20 PC lab. Anda perlu memberikan paket pemindai otomatis yang sudah tersetting khusus untuk lab Anda.
  - *Misi*: Temukan dan unduh paket alat pemindai (*scanner tool*) yang sudah terkonfigurasi khusus untuk laboratorium Anda dari dalam sistem.
  - *Kriteria Evaluator*: Partisipan mengakses halaman Download Scanner, memilih labnya, dan berhasil men-download paket ZIP scanner.

### C. Kelompok Staf / Teknisi Laboratorium (Fokus: *Operational & Asset Tracking*)
- **Skenario S-01: Inventarisasi dan Pengecekan Komputer Belum Terpindai**
  - *Konteks*: Anda ditugaskan memastikan seluruh PC di laboratorium telah terdaftar dan terpindai dengan sistem manifest.
  - *Misi*: Periksa daftar komputer di laboratorium Anda, dan cari tahu apakah ada unit PC yang belum pernah dipindai atau statusnya belum patuh (*non-compliant*).
  - *Kriteria Evaluator*: Partisipan berhasil mengakses daftar inventaris lab, memanfaatkan filter/kolom pencarian, dan melihat rincian salah satu PC.
- **Skenario S-02: Pengunduhan Paket Skrip Scanner Lab**
  - *Konteks*: Anda akan melakukan instalasi scanner di PC laboratorium baru.
  - *Misi*: Unduh paket pemindai resmi dari sistem yang siap dijalankan di komputer laboratorium tersebut.
  - *Kriteria Evaluator*: Partisipan membuka menu download scanner dan mengunduh berkas tanpa meminta bantuan evaluator.

### D. Prosedur Fasilitator / Peneliti (Sebagai Admin)
Peran Admin dijalankan oleh fasilitator/peneliti untuk menciptakan prakondisi pengujian sebelum partisipan masuk ke sesi uji:
1. **Prakondisi K-02 (Untuk Kepala Lab)**: Fasilitator masuk sebagai Admin, membuka menu *Kirim ke PJ Lab* (`/reports/submit-to-lab`), lalu mengirimkan draf laporan kepatuhan ke laboratorium yang akan diuji sehingga muncul status *Pending* di akun Kepala Lab.
2. **Prakondisi P-01 & P-02 (Untuk Pimpinan)**: Fasilitator memastikan minimal terdapat 1 laboratorium berstatus *Approved* dengan data defisit lisensi komersial terukur pada periode berjalan.
3. **Reset State Antar-Sesi**: Fasilitator menjalankan skrip reset database deterministik sebelum sesi partisipan berikutnya dimulai untuk menjaga konsistensi stimulus.

---

## 5. Instrumen Evaluasi Kognitif Keputusan (*Decision Support Evaluation*)

Untuk membuktikan rumusan masalah skripsi terkait efektivitas pengambilan keputusan (*Decision Support*), pengujian untuk kelompok **Pimpinan** memadukan dua pendekatan:

### A. Uji Akurasi Keputusan Objektif (*Objective Decision Task & Case Vignette*)
Setelah menyelesaikan Skenario P-01 dan P-02, Pimpinan diberikan lembar studi kasus singkat dengan tolok ukur kunci jawaban (*Ground Truth*):
1. **Pertanyaan Kasus 1**: *"Berdasarkan data yang Anda lihat pada Dashboard/Laporan, sebutkan 2 software komersial yang mengalami defisit lisensi tertinggi di universitas?"*  
   - *Kunci Jawaban*: Sesuai data seeder (misal: Microsoft Office Pro Plus & Adobe Creative Cloud).  
   - *Skor*: Benar Keduanya (100%), Benar Satu (50%), Salah Keduanya (0%).
2. **Pertanyaan Kasus 2**: *"Berapa total kekurangan unit lisensi (*total deficit seat*) yang perlu diajukan dalam usulan pengadaan software tersebut?"*  
   - *Kunci Jawaban*: Angka eksak defisit pada tabel analisis kebutuhan.  
   - *Skor*: Tepat Akurat (100%), Selisih <= 2 unit (50%), Salah/Tidak Menemukan (0%).
3. **Pertanyaan Kasus 3**: *"Fakultas dan laboratorium mana yang menyumbang defisit instalasi terbesar untuk software tersebut?"*  
   - *Kunci Jawaban*: Fakultas/Lab spesifik yang tercatat di matriks alokasi.  
   - *Skor*: Tepat Akurat (100%), Salah (0%).

*Metrik*: **Decision Accuracy Rate (%)** = Rata-rata persentase ketepatan jawaban responden pimpinan terhadap kunci jawaban sistem.

### B. Kuesioner Persepsi Subjektif Pengambilan Keputusan (Likert 1–5)
1. *"Visualisasi grafik dan matriks kepatuhan memudahkan saya memahami status legalitas software tanpa harus membuka dokumen fisik."*
2. *"Informasi defisit lisensi disajikan secara transparan sehingga mempermudah penetapan prioritas anggaran pengadaan."*
3. *"Pembedaan status kepatuhan (Compliant vs Non-Compliant) dan deteksi software terlarang disajikan dengan tingkat keyakinan yang memadai untuk pengambilan tindakan kebijakan."*

---

## 6. Lembar Observasi Antarmuka (*Evaluator Observation Sheet*)

Lembar observasi disusun per partisipan dengan kolom:
- **No. & Kode Tugas**: (Contoh: P-01, K-02)
- **Deskripsi Misi Singkat**
- **Status Keberhasilan (*Task Success*)**:
  - `[ ] Berhasil Mandiri (Success without assistance)` = Skor 100%
  - `[ ] Berhasil dengan Bantuan (Success with prompt/assistance)` = Skor 50%
  - `[ ] Gagal / Menyerah (Failed / Abandoned)` = Skor 0%
- **Waktu Pengerjaan (*Time on Task*)**: Menit dan Detik (diukur dengan stopwatch/screen recording).
- **Jumlah Kesalahan (*Error Count*)**: Frekuensi salah navigasi, klik elemen yang keliru, atau form validation error (misal: 0, 1, 2, dst.).
- **Catatan Perilaku & *Think-Aloud* (Kualitatif)**: Kutipan ucapan partisipan, kebingungan antarmuka, ragu-ragu membaca label/ikon, atau ekspresi kepuasan.

---

## 7. Instrumen Kuesioner System Usability Scale (SUS)

### Instrumen 10 Pertanyaan Standar (Bahasa Indonesia)
Responden mengisi skala Likert 1 (Sangat Tidak Setuju) hingga 5 (Sangat Setuju):
1. Saya berpikir bahwa saya ingin sering menggunakan sistem ini.
2. Saya merasa sistem ini terlalu rumit untuk digunakan.
3. Saya merasa sistem ini mudah untuk digunakan.
4. Saya merasa membutuhkan bantuan teknisi untuk dapat menggunakan sistem ini.
5. Saya merasa fungsi-fungsi dalam sistem ini terintegrasi dengan baik.
6. Saya merasa terlalu banyak hal yang tidak konsisten pada sistem ini.
7. Saya merasa orang lain akan memahami cara menggunakan sistem ini dengan sangat cepat.
8. Saya merasa sistem ini sangat membingungkan saat digunakan.
9. Saya merasa tidak ada hambatan saat menggunakan sistem ini.
10. Saya perlu mempelajari banyak hal terlebih dahulu sebelum dapat menggunakan sistem ini dengan lancar.

### Metodologi Perhitungan Skor SUS (John Brooke, 1996)
- Butir Ganjil (Positif: 1, 3, 5, 7, 9): `Skor Kontribusi = Nilai Skala - 1`
- Butir Genap (Negatif: 2, 4, 6, 8, 10): `Skor Kontribusi = 5 - Nilai Skala`
- Total Skor SUS per responden = `(Jumlah Seluruh Skor Kontribusi) x 2.5` (Rentang nilai akhir: 0 – 100).

### Standar Interpretasi untuk Pembahasan Skripsi
- **Rata-rata Industri**: Skor 68.0 adalah batas benchmark usability rata-rata.
- **Acceptability Ranges**: `< 50` (Not Acceptable), `50 - 70` (Marginal), `> 70` (Acceptable).
- **Grade Scale**: `> 80.3` (Grade A), `74 - 80.2` (Grade B), `68 - 73.9` (Grade C), `51 - 67.9` (Grade D), `< 51` (Grade F).
- **Adjective Rating**: Poor (< 52), OK (52 - 72), Good (73 - 85), Excellent (85 - 100).

### Kerangka Analisis Statistik Skripsi
1. **Analisis Primer (Pooled Sample)**:
   - Penggabungan skor SUS seluruh responden ($N = 8 - 13$ partisipan) untuk memperoleh mean skor SUS universitas secara agregat, standar deviasi, dan penentuan kategori kelayakan (*Acceptability, Grade Scale, Adjective Rating*).
2. **Analisis Komparatif Deskriptif Antar-Peran**:
   - Membandingkan skor rata-rata SUS antara kelompok Pimpinan, Kepala Lab, dan Staf Lab secara deskriptif untuk melihat disparitas persepsi kebergunaan antarlevel teknis.
3. **Triangulasi Data Kualitatif**:
   - Mengaitkan catatan keluhan *Think-Aloud* dan titik galat (*Error Count*) dengan butir pertanyaan SUS yang memperoleh skor rendah (misal: Butir 4 atau Butir 8).

---

## 8. Protokol Pelaksanaan Pengujian Lapangan

1. **Fase Persiapan (H-1)**:
   - Database disiapkan dengan dataset representatif melalui seeder deterministik (`php artisan migrate:fresh --seed`).
   - Menyiapkan lembar cetak skenario tugas (kartu tugas terpisah tanpa instruksi tombol), lembar observasi evaluator, stopwatch, serta lembar kuesioner SUS & evaluasi keputusan.
2. **Fase Pelaksanaan (Hari H)**:
   - **Briefing (5 menit)**: Menjelaskan kepada partisipan bahwa yang diuji adalah *keberfungsian sistem*, bukan kemampuan intelektual pengguna. Pengguna didorong untuk *think-aloud* (berbicara apa yang dipikirkan/dirasakan).
   - **Pemberian Skenario Tugas (15-20 menit)**: Partisipan membaca kartu skenario satu per satu dan menyelesaikannya. Evaluator bertindak pasif mencatat metrik pada Lembar Observasi tanpa memberikan bantuan kecuali jika terjadi kebuntuan (*deadlock* total > 3 menit).
   - **Uji Evaluasi Keputusan & Pengisian SUS (10 menit)**: Partisipan Pimpinan mengisi uji kasus keputusan objektif, dilanjutkan seluruh responden mengisi 10 butir kuesioner SUS.
   - **Reset Buffer (5 menit)**: Fasilitator mereset status uji (bila diperlukan) sebelum memanggil responden berikutnya.
3. **Fase Pasca-Pengujian (Analisis Data Bab 4 Skripsi)**:
   - Tabulasi metrik kuantitatif: *Task Success Rate (%)*, *Average Time on Task*, *Mean Error Rate*, dan *Decision Accuracy Rate (%)*.
   - Perhitungan rata-rata skor SUS gabungan dan per kelompok peran.
   - Perumusan rekomendasi perbaikan antarmuka (*Usability Improvements*).

---

## 9. Struktur Dokumen Akhir yang Akan Dibuat
Rencana ini akan dituangkan ke dalam berkas `docs/Testing/rencana-pengujian-pengguna.md` yang siap dicetak dan dijadikan lampiran resmi skripsi.
