# Penjelasan Diagram Konteks Sistem Informasi Manifest USN

## 1. Pendahuluan

Dokumen ini menyajikan penjelasan terhadap diagram konteks (*Context Diagram*) dari Sistem Informasi Manifest yang dikembangkan untuk Universitas Sembilanbelas November (USN) Kolaka. Diagram konteks merupakan representasi visual tingkat tertinggi dalam pemodelan Data Flow Diagram (DFD Level 0) yang menggambarkan batasan sistem, entitas luar yang berinteraksi dengan sistem, serta alur data yang mengalir antara entitas luar dan sistem (Kendall & Kendall, 2019).

Diagram konteks ini disusun berdasarkan hasil analisis kebutuhan sistem yang mencakup identifikasi aktor, proses bisnis, serta data yang dipertukarkan dalam konteks pengelolaan aset TI dan kepatuhan lisensi perangkat lunak di lingkungan laboratorium komputer USN Kolaka.

---

## 2. Deskripsi Sistem

**Sistem Informasi Manifest USN** adalah sistem berbasis web yang dirancang untuk mengelola inventarisasi aset teknologi informasi (komputer dan perangkat lunak) serta memantau kepatuhan lisensi perangkat lunak di seluruh laboratorium komputer Universitas Sembilanbelas November Kolaka.

Sistem ini mencakup fungsi-fungsi utama sebagai berikut:

1. **Manajemen Organisasi** -- Pengelolaan data fakultas dan laboratorium sebagai unit organisasi tempat aset TI berada.
2. **Inventarisasi Aset Komputer** -- Pencatatan dan pemantauan spesifikasi perangkat keras komputer di setiap laboratorium.
3. **Inventarisasi Perangkat Lunak** -- Pencatatan perangkat lunak yang terinstal pada setiap komputer melalui mekanisme pemindaian otomatis menggunakan agent scanner.
4. **Manajemen Lisensi** -- Pengelolaan data lisensi perangkat lunak, termasuk kuota, masa berlaku, dan alokasi ke fakultas.
5. **Evaluasi Kepatuhan Lisensi** -- Penilaian otomatis status kepatuhan lisensi berdasarkan perbandingan antara perangkat lunak terinstal dengan ketersediaan lisensi yang dimiliki.
6. **Pelaporan dan Ekspor** -- Penyajian berbagai laporan dalam format dashboard interaktif maupun dokumen yang dapat diunduh (PDF dan Excel).
7. **Alur Kerja Persetujuan** -- Mekanisme pengajuan dan persetujuan laporan kepatuhan laboratorium.
8. **Pemindaian Otomatis** -- Pengumpulan data dari komputer klien secara otomatis melalui agent scanner yang di-deploy ke setiap laboratorium.

---

## 3. Entitas Luar

Diagram konteks mengidentifikasi empat (4) entitas luar yang berinteraksi secara langsung dengan Sistem Informasi Manifest USN. Entitas luar adalah sumber atau tujuan data yang berada di luar batasan sistem (Whitten & Bentley, 2007).

### 3.1. Administrator

Administrator merupakan pengguna dengan hak akses penuh terhadap seluruh fungsionalitas sistem. Peran ini bertanggung jawab atas pengelolaan master data organisasi, manajemen akun pengguna, pengelolaan lisensi perangkat lunak beserta alokasinya, pemantauan kepatuhan, serta pengajuan laporan kepatuhan kepada Kepala Laboratorium untuk ditinjau.

### 3.2. Pimpinan

Pimpinan merupakan pengguna dengan hak akses baca lintas fakultas yang berfungsi sebagai pengambil keputusan di level eksekutif. Entitas ini memperoleh informasi agregat mengenai status kepatuhan lisensi di seluruh fakultas untuk keperluan evaluasi dan perencanaan strategis pengadaan lisensi.

### 3.3. Kepala Laboratorium

Kepala Laboratorium merupakan pengguna yang bertanggung jawab sebagai koordinator di tingkat laboratorium. Hak aksesnya dibatasi pada lingkup laboratorium yang menjadi tanggung jawabnya. Entitas ini berperan dalam meninjau dan memberikan persetujuan atau penolakan terhadap laporan kepatuhan yang diajukan oleh Administrator.

### 3.4. Staf Laboratorium

Staf Laboratorium merupakan pengguna yang berperan sebagai operator di tingkat laboratorium. Selain mengakses data inventaris komputer pada laboratorium yang menjadi tanggung jawabnya, entitas ini juga bertindak sebagai perantara operasional antara sistem dan komputer-komputer klien di laboratorium melalui agent scanner. Staf Laboratorium mengunduh dan men-deploy paket agent scanner ke komputer-komputer di laboratorium, yang kemudian secara otomatis mengirimkan data registrasi komputer dan hasil pemindaian perangkat lunak ke sistem.

---

## 4. Alur Data

Berikut adalah penjelasan rinci mengenai setiap alur data yang tergambar pada diagram konteks. Alur data dinotasikan dengan panah berarah yang menunjukkan arah perpindahan data antara entitas luar dan sistem.

### 4.1. Alur Data antara Administrator dan Sistem

#### 4.1.1. Alur Data Masuk (Administrator ke Sistem)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Data pengguna | Informasi akun pengguna sistem meliputi nama, email, kata sandi, peran, dan afiliasi laboratorium/fakultas. |
| 2 | Data fakultas | Informasi unit fakultas meliputi kode, nama, dan deskripsi. |
| 3 | Data laboratorium | Informasi unit laboratorium meliputi nama, kode, gedung, lantai, deskripsi, dan afiliasi fakultas. |
| 4 | Data lisensi perangkat lunak | Informasi lisensi yang dimiliki institusi meliputi kunci lisensi (terenkripsi), nomor pembelian, kuota, tanggal pembelian, masa berlaku, harga satuan, dan bukti pembelian. |
| 5 | Data alokasi lisensi | Informasi distribusi kuota lisensi ke setiap fakultas meliputi jumlah kuota yang dialokasikan, tanggal alokasi, dan periode berlaku. |
| 6 | Data katalog perangkat lunak | Informasi master perangkat lunak meliputi nama yang dinormalisasi, kategori (Komersial/Freeware), dan status (Whitelist/Blacklist/Belum Ditinjau). |
| 7 | Data komputer | Informasi penyuntingan data komputer seperti lokasi penempatan dan status operasional. |
| 8 | Permintaan pemindaian | Instruksi kepada sistem untuk memicu pemindaian ulang pada komputer tertentu. |
| 9 | Pengajuan laporan kepatuhan | Pengajuan laporan kepatuhan lisensi laboratorium kepada Kepala Laboratorium untuk periode tertentu. |

#### 4.1.2. Alur Data Keluar (Sistem ke Administrator)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Dashboard sistem | Tampilan ringkasan kondisi sistem secara menyeluruh meliputi distribusi sistem operasi, status lisensi, tren pemindaian, tren kepatuhan, dan aktivitas terkini. |
| 2 | Laporan kepatuhan lisensi | Laporan status kepatuhan lisensi per komputer per perangkat lunak (Berlisensi, Tidak Berlisensi, Grace Period) dalam format PDF dan Excel. |
| 3 | Laporan inventaris komputer | Laporan daftar komputer beserta spesifikasi perangkat keras, jumlah perangkat lunak terinstal, dan riwayat pemindaian dalam format PDF dan Excel. |
| 4 | Laporan inventaris perangkat lunak | Laporan daftar perangkat lunak yang terdeteksi beserta kategori, jumlah instalasi, dan sebaran di komputer dalam format PDF dan Excel. |
| 5 | Laporan perubahan perangkat lunak | Laporan perubahan status perangkat lunak antar periode pemindaian (ditambahkan, dihapus, diperbarui, dikembalikan) dalam format PDF dan Excel. |
| 6 | Laporan kebutuhan lisensi | Laporan analisis kebutuhan pengadaan lisensi berdasarkan perbandingan kuota yang dimiliki dengan jumlah instalasi aktual, beserta rekomendasi per fakultas dalam format PDF dan Excel. |
| 7 | Laporan monitoring pemindaian | Laporan rekap keberhasilan dan kegagalan pemindaian per laboratorium dan per komputer dalam format PDF dan Excel. |
| 8 | Log aktivitas | Catatan kronologis seluruh aktivitas mutasi data pada sistem, termasuk perubahan akun pengguna, perubahan data entitas, dan akses kunci lisensi. |
| 9 | Kunci lisensi | Nilai kunci lisensi yang telah didekripsi untuk keperluan verifikasi, disertai pencatatan audit. |

### 4.2. Alur Data antara Pimpinan dan Sistem

#### 4.2.1. Alur Data Masuk (Pimpinan ke Sistem)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Permintaan laporan eksekutif | Permintaan akses terhadap informasi ringkasan eksekutif dan laporan analisis lintas fakultas. |

#### 4.2.2. Alur Data Keluar (Sistem ke Pimpinan)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Dashboard eksekutif | Tampilan ringkasan eksekutif meliputi tingkat kepatuhan keseluruhan, peringatan kritis, dan distribusi status kepatuhan. |
| 2 | Matriks kepatuhan lintas fakultas | Tampilan matriks yang menunjukkan status kepatuhan lisensi di setiap fakultas secara komparatif. Data yang ditampilkan terbatas pada laboratorium yang laporannya telah disetujui oleh Kepala Laboratorium. |
| 3 | Laporan kebutuhan lisensi | Laporan analisis kebutuhan pengadaan lisensi dengan perspektif lintas fakultas untuk mendukung pengambilan keputusan strategis. |
| 4 | Laporan status lisensi | Laporan kondisi lisensi yang dimiliki institusi meliputi kuota, penggunaan, alokasi, dan masa berlaku. |

### 4.3. Alur Data antara Kepala Laboratorium dan Sistem

#### 4.3.1. Alur Data Masuk (Kepala Laboratorium ke Sistem)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Status persetujuan laporan | Keputusan persetujuan (disetujui atau ditolak) terhadap laporan kepatuhan laboratorium yang diajukan oleh Administrator. |
| 2 | Catatan peninjauan | Catatan tertulis yang menyertai keputusan persetujuan atau penolakan laporan sebagai dokumentasi hasil tinjauan. |
| 3 | Permintaan kunci lisensi | Permintaan pembukaan (*reveal*) nilai kunci lisensi perangkat lunak yang dialokasikan ke fakultas pengguna. |

#### 4.3.2. Alur Data Keluar (Sistem ke Kepala Laboratorium)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Dashboard laboratorium | Tampilan ringkasan kondisi laboratorium meliputi statistik komputer, perangkat lunak teratas, komputer terbaru, dan laporan yang menunggu persetujuan. |
| 2 | Laporan kepatuhan laboratorium | Laporan status kepatuhan lisensi untuk seluruh komputer dalam lingkup laboratorium yang menjadi tanggung jawabnya. |
| 3 | Laporan menunggu persetujuan | Daftar laporan kepatuhan yang telah diajukan oleh Administrator dan menunggu keputusan persetujuan. |
| 4 | Paket agent scanner | Berkas paket dalam format ZIP berisi skrip pemindaian (PowerShell) dan konfigurasi yang telah disesuaikan dengan laboratorium terkait, untuk di-deploy ke komputer klien. |
| 5 | Informasi lisensi fakultas | Daftar seluruh lisensi perangkat lunak resmi yang dialokasikan aktif untuk fakultas yang menaungi laboratorium pengguna. |
| 6 | Kunci lisensi | Nilai kunci lisensi (*product key*) terdekripsi secara on-demand dengan pengamanan auto-hide 30 detik dan audit trail. |

### 4.4. Alur Data antara Staf Laboratorium dan Sistem

#### 4.4.1. Alur Data Masuk (Staf Laboratorium ke Sistem)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Data registrasi komputer | Informasi identitas komputer yang didaftarkan melalui agent scanner meliputi alamat MAC, hostname, nomor seri, dan identitas laboratorium. |
| 2 | Data hasil pemindaian | Informasi hasil pemindaian otomatis dari agent scanner meliputi spesifikasi perangkat keras (prosesor, RAM, disk, sistem operasi) dan daftar perangkat lunak yang terinstal (nama, versi, vendor, tanggal instalasi). |
| 3 | Polling perintah pemindaian | Permintaan berkala dari agent scanner untuk memeriksa apakah terdapat instruksi pemindaian ulang dari sistem. |
| 4 | Permintaan kunci lisensi | Permintaan pembukaan (*reveal*) nilai kunci lisensi perangkat lunak yang dialokasikan ke fakultas pengguna untuk keperluan aktivasi workstation lab. |

#### 4.4.2. Alur Data Keluar (Sistem ke Staf Laboratorium)

| No | Alur Data | Deskripsi |
|----|-----------|-----------|
| 1 | Inventaris komputer laboratorium | Daftar komputer beserta informasi spesifikasi dan status pada laboratorium yang menjadi tanggung jawabnya. |
| 2 | Paket agent scanner | Berkas paket dalam format ZIP berisi skrip pemindaian dan konfigurasi untuk di-deploy ke komputer klien di laboratorium. |
| 3 | Token autentikasi | Token keamanan (Sanctum) yang diberikan kepada agent scanner setelah proses registrasi komputer berhasil, digunakan untuk autentikasi pada komunikasi selanjutnya. |
| 4 | Perintah pemindaian | Instruksi pemindaian yang dikirimkan sebagai respons terhadap polling agent scanner, menunjukkan apakah komputer perlu melakukan pemindaian ulang. |
| 5 | Informasi lisensi fakultas | Daftar seluruh lisensi perangkat lunak resmi yang dialokasikan aktif untuk fakultas yang menaungi laboratorium pengguna. |
| 6 | Kunci lisensi | Nilai kunci lisensi (*product key*) terdekripsi secara on-demand dengan pengamanan auto-hide 30 detik dan audit trail. |

---

## 5. Notasi Diagram

Diagram konteks ini menggunakan notasi standar DFD sebagai berikut:

| Simbol | Bentuk | Keterangan |
|--------|--------|------------|
| Entitas Luar | Persegi panjang | Sumber atau tujuan data yang berada di luar batasan sistem. |
| Proses | Lingkaran (elips) | Representasi sistem secara keseluruhan sebagai satu proses tunggal pada DFD Level 0. |
| Alur Data | Panah berarah | Menunjukkan arah perpindahan data antara entitas luar dan sistem, dilengkapi label berupa kata benda yang mendeskripsikan data yang mengalir. |

---

## 6. Kesimpulan

Diagram konteks Sistem Informasi Manifest USN mengidentifikasi empat entitas luar utama yang berinteraksi dengan sistem, yaitu Administrator, Pimpinan, Kepala Laboratorium, dan Staf Laboratorium. Setiap entitas memiliki peran dan cakupan akses yang berbeda sesuai dengan struktur organisasi dan kebutuhan informasi masing-masing.

Sistem menerima data masukan berupa data master organisasi, data lisensi, data inventaris komputer (melalui agent scanner), serta keputusan persetujuan laporan. Sistem menghasilkan keluaran berupa berbagai jenis laporan, dashboard, dan informasi kepatuhan lisensi yang disesuaikan dengan kebutuhan informasi setiap entitas luar.

Diagram konteks ini menjadi dasar untuk pengembangan DFD pada level yang lebih rinci (DFD Level 1 dan seterusnya) yang akan menjabarkan proses-proses internal sistem secara lebih detail.

---

## Daftar Pustaka

- Kendall, K. E., & Kendall, J. E. (2019). *Systems Analysis and Design* (10th ed.). Pearson.
- Whitten, J. L., & Bentley, L. D. (2007). *Systems Analysis and Design Methods* (7th ed.). McGraw-Hill.

---

*Dokumen ini disusun berdasarkan hasil analisis sistem pada Sistem Informasi Manifest USN Kolaka, Oktober 2026.*
