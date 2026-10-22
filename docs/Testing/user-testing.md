# Panduan Pengujian Pengguna Berbasis Observasi Tugas (Task-Based Usability Observation)
## Sistem Manifest dan Kepatuhan Lisensi Perangkat Lunak Laboratorium (UniLicense)
### Universitas Sembilanbelas November Kolaka

---

Dalam pengujian pengguna (*user testing*) pada penelitian skripsi ini, pendekatan yang digunakan berfokus murni pada **Metode Observasi Tugas Empiris (*Task-Based Usability Observation*)** yang dikombinasikan dengan protokol berpikir bersuara (*think-aloud protocol*) merujuk pada standar ISO 9241-11 dan panduan *Rubin & Chisnell (2008)*.

Pendekatan observasi langsung ini dipilih sesuai arahan dosen pembimbing karena memiliki keunggulan ilmiah dibanding kuesioner persepsi semata:
1. **Mengukur Perilaku Aktual, Bukan Persepsi**: Mencatat data empiris konkret (keberhasilan misi, waktu eksekusi, dan kesalahan klik riil), sehingga terbebas dari bias subjektivitas atau ketidakenakan responden (*social desirability bias*).
2. **Memenuhi Tiga Dimensi Baku ISO 9241-11**:
   - **Efektivitas (*Effectiveness*)**: Terukur dari persentase penyelesaian tugas secara mandiri (*Task Success Rate*).
   - **Efisiensi (*Efficiency*)**: Terukur dari kecepatan waktu pengerjaan tugas (*Time on Task*).
   - **Kepuasan (*Satisfaction*)**: Terekam secara kualitatif melalui protokol *Think-Aloud* saat pengguna berinteraksi langsung dengan antarmuka.
3. **Efisiensi Waktu Responden Strategis**: Para pemangku kepentingan kampus (Dekan/Pimpinan, Kepala Lab, dan Teknisi Lab) dapat langsung mengevaluasi sistem melalui simulasi kerja riil tanpa dibebani kuesioner teoritis yang panjang.

---

## 1. Penyusunan Skenario Tugas (Task Scenarios)

Menurut **Rubin & Chisnell (2008)**, skenario tugas yang baik harus memenuhi kriteria:
- **Realistis & Kontekstual**: Berangkat dari masalah pekerjaan sehari-hari di kampus.
- **Berorientasi pada Tujuan (*Goal-Oriented*)**: Memberikan sasaran akhir yang jelas tanpa mendikte teknis.
- **Menghindari Instruksi Mengarahkan (*Avoid Leading Instructions*)**: Evaluator sama sekali tidak boleh menyebutkan nama menu, ikon, atau tombol spesifik yang harus diklik pengguna.

### Contoh Skenario Tugas Nyata Sistem UniLicense

Berikut adalah contoh kartu tugas yang diberikan kepada responden sesuai kelompok perannya:

#### Skenario Tugas 1: Kelompok Pimpinan (Eksekutif)
> **Konteks**: Anda adalah pimpinan universitas yang sedang mempersiapkan rapat evaluasi aset tahunan dan ingin mengetahui kondisi kepatuhan lisensi perangkat lunak di seluruh fakultas.  
> **Misi**: *"Silakan periksa kondisi kepatuhan lisensi universitas saat ini melalui sistem. Temukan fakultas mana yang memiliki catatan kekurangan (defisit) lisensi paling banyak, lalu sebutkan nama fakultas tersebut kepada evaluator."*

#### Skenario Tugas 2: Kelompok Kepala Laboratorium (PJ Lab)
> **Konteks**: Administrator sistem telah menyelesaikan audit berkala dan mengirimkan draf laporan kepatuhan laboratorium Anda untuk diverifikasi kebenarannya.  
> **Misi**: *"Temukan draf laporan kepatuhan laboratorium Anda yang berstatus 'Pending Review'. Telaah ringkasan datanya, lalu berikan keputusan pengesahan (Approve) agar laporan tersebut sah dan diteruskan ke pimpinan fakultas."*

#### Skenario Tugas 3: Kelompok Staf / Teknisi Laboratorium
> **Konteks**: Anda sedang menyiapkan komputer meja praktikan yang baru diinstal ulang dan membutuhkan kode lisensi resmi fakultas untuk aktivasi software komersial.  
> **Misi**: *"Temukan informasi lisensi resmi fakultas Anda pada sistem ini, lalu buka (reveal) dan salin kunci lisensi (Product Key) dari salah satu aplikasi berbayar yang tersedia untuk keperluan aktivasi workstation."*

---

## 2. Lembar Observasi Kinerja Evaluator

Lembar Observasi adalah instrumen utama evaluator untuk merekam bukti empiris di lapangan secara objektif.

### Metrik Kinerja yang Diamati:
1. **Status Keberhasilan Tugas (*Task Success Rate*)**:
   - **Mandiri (100%)**: Tugas diselesaikan tuntas tanpa arahan atau petunjuk evaluator.
   - **Dengan Bantuan (50%)**: Tugas diselesaikan setelah evaluator memberikan petunjuk konseptual (karena pengguna mengalami *deadlock* > 3 menit).
   - **Gagal (0%)**: Pengguna menyatakan menyerah atau keluar dari alur tugas tanpa mencapai sasaran.
2. **Waktu Pengerjaan (*Time on Task*)**: Durasi waktu (dalam detik) yang dibutuhkan pengguna dari mulai membaca misi hingga tugas selesai.
3. **Jumlah Kesalahan (*Error Count*)**: Frekuensi salah klik menu yang tidak relevan, salah memilih parameter filter, atau tersesat (*backtracking*).
4. **Protokol Berpikir Bersuara (*Think-Aloud Protocol*)**: Catatan kualitatif mengenai ucapan spontan pengguna, keraguan (*cognitive friction*), maupun komentar kepuasan.

---

### Format Lembar Observasi Evaluator di Lapangan

| No. Tugas | Ringkasan Skenario Misi | Status Keberhasilan (*Task Success*) | Waktu Pengerjaan (*Time on Task*) | Jumlah Kesalahan (*Error Count*) | Catatan Perilaku & Ulasan *Think-Aloud* |
|:---:|:---|:---:|:---:|:---:|:---|
| **Tugas 1**<br>*(Pimpinan)* | Mengidentifikasi fakultas dengan defisit lisensi terbesar pada Executive Dashboard. | [x] Mandiri (100%)<br>[ ] Dg Bantuan (50%)<br>[ ] Gagal (0%) | 48 Detik | 0 Kali | *Pengguna langsung memahami grafik batang merah defisit lisensi. Berkomentar: "Bagus, langsung terlihat FTI yang paling banyak kekurangan lisensi."* |
| **Tugas 2**<br>*(Kepala Lab)* | Menelaah draf laporan lab dan memberikan persetujuan (*approval*). | [x] Mandiri (100%)<br>[ ] Dg Bantuan (50%)<br>[ ] Gagal (0%) | 75 Detik | 1 Kali | *Sempat membuka menu inventaris lab terlebih dahulu sebelum masuk ke menu Review Laporan. Pengguna memuji kejelasan ringkasan data sebelum klik Approve.* |
| **Tugas 3**<br>*(Staf Lab)* | Menemukan dan membuka (*reveal*) kunci lisensi resmi fakultas. | [x] Mandiri (100%)<br>[ ] Dg Bantuan (50%)<br>[ ] Gagal (0%) | 42 Detik | 0 Kali | *Pengguna sangat terbantu dengan adanya tombol salin cepat dan indikator hitung mundur 30 detik untuk keamanan kunci.* |

---

## 3. Pelaporan Hasil Observasi di Naskah Skripsi

Data yang terkumpul dari seluruh responden ditabulasi dan dibahas pada **BAB IV (Hasil dan Pembahasan)** dengan struktur:
1. **Rekapitulasi Kuantitatif Kinerja**:
   - Rata-rata tingkat keberhasilan tugas (*Mean Task Success Rate %*).
   - Rata-rata durasi waktu penyelesaian tugas (*Mean Time on Task* per tugas dan per peran).
   - Rata-rata tingkat kesalahan navigasi (*Mean Error Count*).
2. **Analisis Kualitatif & Rekomendasi Desain UI/UX**:
   - Menganalisis kendala antarmuka yang paling sering dialami responden berdasarkan catatan *Think-Aloud*.
   - Menyusun rekomendasi perbaikan tata letak atau label menu agar sistem semakin ramah pengguna (*user-friendly*).
