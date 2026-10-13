# Rencana Implementasi Dokumen Perencanaan User Testing Skripsi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menghasilkan dokumen perencanaan dan instrumen pengujian pengguna (*user testing*) yang komprehensif, terstandarisasi, dan siap pakai untuk Bab 3 & Bab 4 Skripsi di `docs/Testing/rencana-pengujian-pengguna.md`.

**Architecture:** Dokumen disusun secara modular mengikuti kerangka Rubin & Chisnell (1996/2008) dan John Brooke (1996). Terdiri dari landasan metodologis, protokol pengujian lapangan, paket stimulus skenario tugas non-leading per peran, lembar observasi evaluator, instrumen evaluasi dukungan keputusan (objektif & subjektif), instrumen kuesioner SUS 10 butir bahasa Indonesia baku, serta panduan komputasi statistik untuk penulisan laporan skripsi.

**Tech Stack:** Markdown (GitHub-flavored), LaTeX formula format, ISO/IEC 25010 Usability Framework, System Usability Scale (SUS).

**Spec:** `docs/superpowers/specs/2026-10-13-user-testing-plan-design.md`

## Global Constraints

- Skenario tugas wajib berorientasi pada tujuan/misi (*goal-oriented*) dan dilarang mendikte nama menu/tombol/langkah teknis antarmuka (*avoid leading instructions*).
- Subjek uji formal difokuskan murni pada 3 peran pengguna akhir: Pimpinan ($N=2-3$), Kepala Lab ($N=3-5$), dan Staf Lab ($N=3-5$).
- Peneliti/Fasilitator bertindak sebagai Admin untuk penyiapan lingkungan uji, seeding data, dan eksekusi prakondisi pengujian.
- Evaluasi aspek *Decision Support* memadukan pengujian objektif (*Case Vignette* dengan *Ground Truth* kunci jawaban sistem) dan kuesioner persepsi skala Likert 1–5.
- Instrumen SUS menggunakan terjemahan baku 10 butir Bahasa Indonesia dengan skala 1–5 dan rumus konversi skor Brooke (1996).
- Berkas output utama disimpan di `docs/Testing/rencana-pengujian-pengguna.md`.

## Review Focus

- Skenario tugas tidak boleh membocorkan alur klik (misal: "Klik tombol unduh pada pojok kanan").
- Kunci jawaban untuk pengujian keputusan objektif Pimpinan harus sesuai dengan data riil pada seeder aplikasi.
- Lembar observasi evaluator harus menyediakan ruang pencatatan eksplisit untuk *Think-Aloud*, *Time on Task*, *Error Count*, dan status *Task Success*.
- Formula perhitungan SUS harus dijelaskan secara matematis yang runtut agar mahasiswa dapat langsung mengutipnya di Bab 3 skripsi.
- Prosedur reset data antar-sesi partisipan harus dirumuskan secara operasional.

---

### Task 1: Penyusunan Bab Landasan Metodologi, Protokol Pengujian, dan Prosedur Teknis

**Files:**
- Create/Modify: `docs/Testing/rencana-pengujian-pengguna.md` (Bagian I & Bagian II)

**Interfaces:**
- Consumes: `docs/superpowers/specs/2026-10-13-user-testing-plan-design.md` (Seksi 1, 3, 8)
- Produces: Kerangka dasar dokumen, profil partisipan, protokol etika, dan panduan teknis fasilitator.

- [ ] **Step 1: Tulis Bagian I (Informasi Umum & Landasan Metodologis)**
  - Identitas pengujian (Judul skripsi, sistem target: UniLicense USN Kolaka, tanggal uji, lokasi).
  - Tujuan pengujian (Validasi kebergunaan antarmuka & efektivitas dukungan keputusan).
  - Profil dan kriteria inklusi responden: Pimpinan ($N=2-3$), Kepala Laboratorium ($N=3-5$), Staf/Teknisi Laboratorium ($N=3-5$).
  - Peran Peneliti sebagai Fasilitator/Admin (bukan subjek uji formal).

- [ ] **Step 2: Tulis Bagian II (Protokol dan Prosedur Pelaksanaan Uji Lapangan)**
  - Fase Persiapan H-1: Verifikasi deployment lokal/staging, seeding dataset representatif, pencetakan instrumen.
  - Prosedur Reset Lingkungan Uji Antar-Sesi: Perintah Artisan `php artisan migrate:fresh --seed` dan buffer jeda 5 menit.
  - Briefing Partisipan: Naskah pembuka evaluator (menegaskan bahwa sistem yang diuji, bukan kecerdasan pengguna; dorongan metode *think-aloud*).
  - Aturan Intervensi Evaluator: Kriteria bantuan jika *deadlock* > 3 menit.

- [ ] **Step 3: Verifikasi kelengkapan Bagian I & II**
  - Pastikan tidak ada instruksi klik antarmuka di bagian ini.
  - Pastikan naskah briefing ramah dan memenuhi kaidah etika penelitian human-computer interaction (HCI).

---

### Task 2: Perumusan Paket Skenario Tugas (Task Scenarios) per Peran Berbasis Rubin & Chisnell

**Files:**
- Modify: `docs/Testing/rencana-pengujian-pengguna.md` (Bagian III)

**Interfaces:**
- Consumes: Rute dan fitur sistem dari `routes/web.php` serta Seksi 4 dari spec design.
- Produces: Kartu skenario tugas realistis untuk 3 kelompok peran.

- [ ] **Step 1: Tulis Skenario Tugas Kelompok Pimpinan (Eksekutif)**
  - **Skenario P-01**: Tinjauan Kesehatan Aset TI & Identifikasi Defisit Lisensi Universitas (Misi eksplorasi kesehatan aset).
  - **Skenario P-02**: Perencanaan Anggaran & Rekomendasi Pengadaan Lisensi Legal (Misi penemuan kebutuhan lisensi & ekspor dokumen).
  - **Skenario P-03**: Mitigasi Risiko Software Bajakan/Terlarang (Misi penelusuran bendera pelanggaran/software berisiko).
  - Format per skenario: Deskripsi Konteks/Cerita Masalah, Misi Pengguna (Goal-oriented), Kondisi Awal, dan Kriteria Selesai Evaluator.

- [ ] **Step 2: Tulis Skenario Tugas Kelompok Kepala Laboratorium**
  - **Skenario K-01**: Verifikasi Riwayat Perubahan Software Lab (Misi deteksi instalasi tak berizin).
  - **Skenario K-02**: Review & Keputusan Persetujuan Draf Laporan Kepatuhan Lab (Misi verifikasi dan eksekusi Approve/Reject dengan catatan revisi).
  - **Skenario K-03**: Pengadaan Paket Alat Pemindai Khusus Laboratorium (Misi penyediaan scanner terkonfigurasi lab).

- [ ] **Step 3: Tulis Skenario Tugas Kelompok Staf / Teknisi Laboratorium**
  - **Skenario S-01**: Inventarisasi dan Pengecekan Komputer Belum Terpindai (Misi inspeksi kondisi PC lab).
  - **Skenario S-02**: Pengunduhan Paket Skrip Pemindai Laboratorium (Misi persiapan alat scanner).

- [ ] **Step 4: Tulis Panduan Prakondisi Fasilitator untuk Skenario**
  - Langkah fasilitator mengirimkan draf laporan (`/reports/submit-to-lab`) sebelum Kepala Lab menguji Skenario K-02.
  - Langkah verifikasi ketersediaan data approved sebelum Pimpinan menguji Skenario P-01.

- [ ] **Step 5: Verifikasi non-leading rule**
  - Cek ulang seluruh teks skenario: pastikan tidak ada kata "Klik tombol...", "Pilih menu sidebar...", atau petunjuk visual langsung.

---

### Task 3: Pembuatan Format Lembar Observasi Evaluator & Template Rekapitulasi Metrik

**Files:**
- Modify: `docs/Testing/rencana-pengujian-pengguna.md` (Bagian IV)

**Interfaces:**
- Consumes: Metrik dari `docs/Testing/user-testing.md` dan Seksi 6 dari spec design.
- Produces: Template lembar observasi siap cetak dan tabel rekapitulasi data empiris.

- [ ] **Step 1: Tulis Format Lembar Observasi Antarmuka (Individual Sheet)**
  - Header data partisipan (Kode Partisipan, Peran/Instansi, Waktu Mulai/Selesai, Nama Evaluator).
  - Tabel observasi per tugas mencakup:
    - No & Kode Tugas.
    - Status Keberhasilan: Berhasil Mandiri (100%), Berhasil dengan Bantuan (50%), Gagal/Menyerah (0%).
    - Waktu Pengerjaan (*Time on Task* dalam menit & detik).
    - Jumlah Kesalahan (*Error Count* - salah navigasi / salah klik / validasi gagal).
    - Catatan Perilaku & *Think-Aloud* (Kutipan verbal, ekspresi wajah, kebingungan UI).

- [ ] **Step 2: Tulis Format Tabel Rekapitulasi Kuantitatif Lapangan (Master Table)**
  - Tabel matriks rekapitulasi performa per peran untuk Bab 4:
    - *Task Completion Rate (%)*.
    - *Mean Time on Task (detik)*.
    - *Mean Error Count per Task*.

- [ ] **Step 3: Verifikasi kesesuaian format tabel**
  - Pastikan format tabel rapi dalam Markdown dan mudah dipindahkan ke Word/Google Docs.

---

### Task 4: Pembuatan Instrumen Evaluasi Kognitif Pengambilan Keputusan (Decision Support)

**Files:**
- Modify: `docs/Testing/rencana-pengujian-pengguna.md` (Bagian V)

**Interfaces:**
- Consumes: Seksi 5 dari spec design.
- Produces: Uji kasus keputusan objektif (*Case Vignette*) dengan kunci jawaban dan kuesioner persepsi Likert.

- [ ] **Step 1: Rancang Instrumen Studi Kasus Objektif (*Objective Decision Task*)**
  - Kasus 1: Identifikasi 2 software defisit tertinggi di universitas (Kunci jawaban & rubrik penilaian).
  - Kasus 2: Perhitungan total kekurangan seat lisensi untuk pengadaan (Kunci jawaban & rubrik toleransi).
  - Kasus 3: Identifikasi fakultas/lab penyumbang defisit terbesar (Kunci jawaban & rubrik penilaian).
  - Formula perhitungan **Decision Accuracy Rate (%)** untuk bab pembahasan skripsi.

- [ ] **Step 2: Rancang Kuesioner Persepsi Subjektif Pengambilan Keputusan**
  - 3 butir pernyataan skala Likert 1–5 mengenai transparansi, kejelasan grafik, dan keyakinan dalam penetapan kebijakan anggaran.

- [ ] **Step 3: Verifikasi kelayakan akademik instrumen keputusan**
  - Pastikan instrumen membuktikan rumusan masalah skripsi secara objektif dan terukur.

---

### Task 5: Penyusunan Instrumen Kuesioner SUS dan Panduan Analisis Statistik Skripsi

**Files:**
- Modify: `docs/Testing/rencana-pengujian-pengguna.md` (Bagian VI & Bagian VII)

**Interfaces:**
- Consumes: Seksi 7 dari spec design & standar John Brooke (1996).
- Produces: Kuesioner SUS 10 butir bahasa Indonesia, rumus matematis perhitungan, dan panduan interpretasi untuk naskah skripsi.

- [ ] **Step 1: Tulis Instrumen Kuesioner SUS 10 Butir (Bahasa Indonesia Baku)**
  - 10 butir pertanyaan terjemahan terstandarisasi dengan skala Likert 1 (Sangat Tidak Setuju) sampai 5 (Sangat Setuju).
  - Petunjuk pengisian untuk responden.

- [ ] **Step 2: Tulis Formula Matematis Kalkulasi Skor SUS**
  - Rumus normalisasi butir ganjil: $R_i = X_i - 1$.
  - Rumus normalisasi butir genap: $R_i = 5 - X_i$.
  - Rumus total skor per responden: $SUS = \left(\sum_{i=1}^{10} R_i\right) \times 2.5$.
  - Contoh komputasi numerik langkah demi langkah.

- [ ] **Step 3: Tulis Panduan Interpretasi Skor & Analisis Data Skripsi**
  - Tabel Skala Penilaian: *Acceptability Ranges*, *Grade Scale (A–F)*, *Adjective Rating*, dan *NPS Category* (Bangor, Kortum, & Miller, 2008; Sauro, 2011).
  - Kerangka Pelaporan di Bab 4: Analisis agregat (*pooled sample*), analisis komparasi antarkelompok peran, dan triangulasi kualitatif.

---

### Task 6: Konsolidasi, Verifikasi Kualitas Dokumen, dan Finalisasi

**Files:**
- Review & Finalize: `docs/Testing/rencana-pengujian-pengguna.md`

**Interfaces:**
- Consumes: Seluruh modul tugas 1 hingga 5.
- Produces: Berkas perencanaan pengujian pengguna lengkap, utuh, dan terverifikasi.

- [ ] **Step 1: Periksa konsistensi istilah dan nama modul**
  - Pastikan terminologi selaras dengan aplikasi (Fakultas, Laboratorium, Executive Dashboard, Analisis Kebutuhan Lisensi, Review Laporan, Download Scanner).
- [ ] **Step 2: Periksa tata bahasa akademis**
  - Gunakan bahasa Indonesia baku akademis yang layak untuk lampiran skripsi.
- [ ] **Step 3: Validasi struktur Markdown**
  - Pastikan tabel terformat dengan rapi dan formula matematis mudah dibaca.
