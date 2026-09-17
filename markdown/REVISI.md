Ringkasan

Ini adalah rekaman percakapan/konsultasi bimbingan antara seorang dosen pembimbing dan mahasiswa bimbingannya, membahas sistem informasi pengecekan lisensi perangkat lunak/aplikasi di laboratorium kampus (kemungkinan untuk skripsi/tugas akhir).

Poin-poin Utama

1. Konteks Awal
   Mahasiswa datang meminta tanda tangan persetujuan pelaksanaan, tapi dosen menolak karena sistemnya dinilai belum matang dan pekerjaannya belum selesai.

2. Kritik terhadap Desain Sistem
   Dosen menilai mahasiswa belum memiliki gambaran alur (flow map) sistem yang jelas. Ia meminta mahasiswa membuat flow map, bukan UML, karena topiknya adalah sistem informasi (bagaimana data mengalir), bukan sekadar pemodelan objek.

3. Alur Pelaporan Lisensi yang Perlu Digambarkan
   Dosen menjelaskan hierarki pelaporan yang harus tergambar dalam sistem:

Admin/Staff → mengecek lisensi alat/software di lab
Penanggung Jawab (PJ) Lab → tiap lab (ada sekitar 10 lab: tambang, sipil, pertanian, geografi, dll.)
Kepala UPA (Unit Pelaksana Akademik) / Lab Terpadu
Pimpinan lebih atas (Biro Umum, Rektor, dll.) — dosen menekankan mahasiswa harus menelusuri siapa sebenarnya "pimpinan" yang dimaksud di tiap level, karena istilah "pimpinan" terlalu umum dan bertingkat-tingkat (berjenjang).

4. Kritik terhadap Diagram Level 1 & 2 (DFD)
   Dosen membahas diagram (tampaknya Data Flow Diagram):

Proses input (P1), pengolahan data (P2), proses lain (P3)
Entitas yang memasukkan data, disimpan di tabel-tabel
Data yang masuk harus jelas keluarannya (laporan)
Dosen menegur karena ada alur yang salah/tidak logis antara level 1 dan level 2, serta alur yang "melompat" tanpa keluar sebagai laporan yang benar.

5. Cakupan Sistem Diperluas
   Awalnya sistem dibatasi hanya untuk lab di FTI (Fakultas Teknik/Informatika) saja, tapi dosen menolak — ia ingin cakupannya diperluas ke semua lab di lingkup universitas (USN), tidak hanya lab komputer, termasuk lab kimia, biologi, dll., dengan pengecekan lisensi berkala (misalnya tiap 3 bulan).

Inti Arahan Dosen

Mahasiswa diminta untuk:

Membuat flow map/alur sistem yang jelas sebelum melanjutkan.
Menelusuri dan memastikan struktur pelaporan (hierarki pimpinan) yang benar dan akurat.
Memperbaiki DFD level 1 dan level 2 agar konsisten.
Memperluas cakupan sistem ke seluruh laboratorium di universitas, bukan hanya satu lab/fakultas.

Kalau Anda mau, saya bisa bantu membuatkan draf flow map atau DFD berdasarkan arahan ini agar lebih mudah dipahami secara visual.
