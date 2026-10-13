# Penjelasan Data Flow Diagram (DFD) Level 2 Sistem Informasi Manifest USN
*(Dekomposisi Siklus Input – Proses – Output)*

---

## 1. Pendahuluan

Dokumen ini menyajikan penjelasan teknis dan metodologis mengenai **Data Flow Diagram (DFD) Level 2** dari Sistem Informasi Manifest Universitas Sembilanbelas November (USN) Kolaka. DFD Level 2 merupakan perincian (*functional primitive decomposition*) dari tiga (3) proses utama yang telah didefinisikan pada DFD Level 1 berbasis siklus **Input – Proses – Output (IPO)** (Kendall & Kendall, 2019; Pressman & Maxim, 2020):
1. **Proses 1.0 (Input):** Pengelolaan Data Masukan
2. **Proses 2.0 (Proses):** Pemrosesan dan Evaluasi Kepatuhan
3. **Proses 3.0 (Output):** Penyajian Informasi dan Laporan

Diagram DFD Level 2 disajikan dalam satu berkas terpadu berekstensi `.drawio` (`docs/dfd-level-2.drawio`) yang terdiri dari tiga halaman (*pages/tabs*):
- **Halaman 1:** DFD Level 2 – Proses 1.0 (Pengelolaan Data Masukan)
- **Halaman 2:** DFD Level 2 – Proses 2.0 (Pemrosesan dan Evaluasi Kepatuhan)
- **Halaman 3:** DFD Level 2 – Proses 3.0 (Penyajian Informasi dan Laporan)

Seluruh notasi dirancang dalam format **monokrom hitam-putih** standar akademik skripsi, dengan penamaan alur data (*data flow*) yang secara konsisten menggunakan frasa **kata benda** (*noun phrase*) tanpa kata kerja tindakan, serta memenuhi prinsip **keseimbangan hierarki (*balancing rule*)** 100% terhadap DFD Level 1 dan DFD Level 0.

---

## 2. DFD Level 2 – Proses 1.0: Pengelolaan Data Masukan (*Input*)

### 2.1. Dekomposisi Sub-Proses 1.0

Proses 1.0 bertanggung jawab atas penerimaan, validasi, dan penyimpanan data mentah yang masuk dari entitas luar ke dalam penyimpanan data persisten (*data store*). Proses ini didekomposisi menjadi tiga (3) sub-proses:

```
[1.0 Pengelolaan Data Masukan]
   ├── 1.1 Pengelolaan Pengguna dan Organisasi
   ├── 1.2 Pengelolaan Lisensi dan Katalog
   └── 1.3 Registrasi Komputer dan Pemindaian
```

1. **Sub-Proses 1.1: Pengelolaan Pengguna dan Organisasi**
   - **Tanggung Jawab:** Menerima masukan akun pengguna, penetapan peran (*role assignment*), serta data hierarki organisasi fakultas dan laboratorium.
   - **Entitas Masukan:** Administrator (*Data pengguna*, *Data fakultas*, *Data laboratorium*).
   - **Keluaran Penyimpanan:** Menyimpan ke `D1` (*Data pengguna tersimpan*), `D2` (*Data organisasi tersimpan*), dan mencatat log mutasi ke `D7` (*Catatan aktivitas pengguna*).

2. **Sub-Proses 1.2: Pengelolaan Lisensi dan Katalog**
   - **Tanggung Jawab:** Menerima data pembelian lisensi perangkat lunak institusi (termasuk kunci lisensi yang dienkripsi), penetapan kuota alokasi ke fakultas, serta pendaftaran katalog master perangkat lunak.
   - **Entitas Masukan:** Administrator (*Data lisensi perangkat lunak*, *Data alokasi lisensi*, *Data katalog perangkat lunak*).
   - **Keluaran Penyimpanan:** Menyimpan ke `D3` (*Data lisensi & katalog tersimpan*) dan mencatat audit ke `D7` (*Catatan aktivitas lisensi*).

3. **Sub-Proses 1.3: Registrasi Komputer dan Pemindaian**
   - **Tanggung Jawab:** Menangani pendaftaran komputer laboratorium oleh operator, menerima payload pemindaian perangkat keras & perangkat lunak, serta melayani polling status pemindaian. Membaca validitas laboratorium dari data organisasi.
   - **Entitas Masukan:**
     - Administrator: *Data komputer*, *Permintaan pemindaian*.
     - Staf Laboratorium: *Data registrasi komputer*, *Data hasil pemindaian*, *Polling perintah pemindaian*.
   - **Masukan Penyimpanan:** Membaca `D2` (*Data validasi laboratorium*).
   - **Keluaran Penyimpanan:** Menyimpan ke `D4` (*Data komputer & hasil scan tersimpan*) dan mencatat riwayat ke `D7` (*Catatan aktivitas scan*).

---

### 2.2. Matriks Alur Data DFD Level 2 – Proses 1.0

| No | Sumber | Tujuan | Label Alur Data | Keterangan |
|:--:|--------|--------|-----------------|------------|
| 1 | Administrator | Sub-Proses 1.1 | Data pengguna | Masukan akun pengguna dan peran. |
| 2 | Administrator | Sub-Proses 1.1 | Data fakultas | Masukan master unit fakultas. |
| 3 | Administrator | Sub-Proses 1.1 | Data laboratorium | Masukan master unit laboratorium. |
| 4 | Administrator | Sub-Proses 1.2 | Data lisensi perangkat lunak | Masukan nomor PO, kuota, tanggal, harga, kunci. |
| 5 | Administrator | Sub-Proses 1.2 | Data alokasi lisensi | Masukan penetapan kuota lisensi fakultas. |
| 6 | Administrator | Sub-Proses 1.2 | Data katalog perangkat lunak | Masukan standardisasi kategori dan nama software. |
| 7 | Administrator | Sub-Proses 1.3 | Data komputer | Pembaruan data lokasi/status komputer. |
| 8 | Administrator | Sub-Proses 1.3 | Permintaan pemindaian | Pemicuan scan komputer on-demand. |
| 9 | Staf Laboratorium | Sub-Proses 1.3 | Data registrasi komputer | Identitas fisik komputer (MAC, Serial, Hostname). |
| 10 | Staf Laboratorium | Sub-Proses 1.3 | Data hasil pemindaian | Muatan spesifikasi hardware & daftar software. |
| 11 | Staf Laboratorium | Sub-Proses 1.3 | Polling perintah pemindaian | Permintaan berkala pemeriksaan status scan. |
| 12 | Sub-Proses 1.1 | Data Store D1 | Data pengguna tersimpan | Rekam persistensi tabel `users` & `roles`. |
| 13 | Sub-Proses 1.1 | Data Store D2 | Data organisasi tersimpan | Rekam persistensi tabel `faculties` & `laboratories`. |
| 14 | Sub-Proses 1.2 | Data Store D3 | Data lisensi & katalog tersimpan | Rekam persistensi tabel lisensi dan katalog. |
| 15 | Data Store D2 | Sub-Proses 1.3 | Data validasi laboratorium | Pemeriksaan keabsahan ID laboratorium komputer. |
| 16 | Sub-Proses 1.3 | Data Store D4 | Data komputer & hasil scan tersimpan | Rekam persistensi komputer, sesi scan, discoveries. |
| 17 | Sub-Proses 1.1 | Data Store D7 | Catatan aktivitas pengguna | Rekam jejak audit perubahan akun/organisasi. |
| 18 | Sub-Proses 1.2 | Data Store D7 | Catatan aktivitas lisensi | Rekam jejak audit perubahan lisensi/katalog. |
| 19 | Sub-Proses 1.3 | Data Store D7 | Catatan aktivitas scan | Rekam jejak audit pendaftaran dan sesi scan. |

---

### 2.3. Verifikasi Keseimbangan (*Balancing*) Proses 1.0

| Alur Data pada DFD Level 1 (Proses 1.0) | Alur Data pada DFD Level 2 (1.1, 1.2, 1.3) | Status |
|------------------------------------------|--------------------------------------------|:------:|
| In: Data pengguna (Admin) | In: Data pengguna &rarr; Sub-Proses 1.1 | Seimbang |
| In: Data fakultas (Admin) | In: Data fakultas &rarr; Sub-Proses 1.1 | Seimbang |
| In: Data laboratorium (Admin) | In: Data laboratorium &rarr; Sub-Proses 1.1 | Seimbang |
| In: Data lisensi perangkat lunak (Admin) | In: Data lisensi perangkat lunak &rarr; Sub-Proses 1.2 | Seimbang |
| In: Data alokasi lisensi (Admin) | In: Data alokasi lisensi &rarr; Sub-Proses 1.2 | Seimbang |
| In: Data katalog perangkat lunak (Admin) | In: Data katalog perangkat lunak &rarr; Sub-Proses 1.2 | Seimbang |
| In: Data komputer (Admin) | In: Data komputer &rarr; Sub-Proses 1.3 | Seimbang |
| In: Permintaan pemindaian (Admin) | In: Permintaan pemindaian &rarr; Sub-Proses 1.3 | Seimbang |
| In: Data registrasi komputer (Staf Lab) | In: Data registrasi komputer &rarr; Sub-Proses 1.3 | Seimbang |
| In: Data hasil pemindaian (Staf Lab) | In: Data hasil pemindaian &rarr; Sub-Proses 1.3 | Seimbang |
| In: Polling perintah pemindaian (Staf Lab)| In: Polling perintah pemindaian &rarr; Sub-Proses 1.3 | Seimbang |
| Out: Data pengguna tersimpan &rarr; D1 | Out: Data pengguna tersimpan (1.1 &rarr; D1) | Seimbang |
| Out: Data organisasi tersimpan &rarr; D2 | Out: Data organisasi tersimpan (1.1 &rarr; D2) | Seimbang |
| Out: Data lisensi & katalog tersimpan &rarr; D3 | Out: Data lisensi & katalog tersimpan (1.2 &rarr; D3) | Seimbang |
| Out: Data komputer & hasil scan tersimpan &rarr; D4 | Out: Data komputer & hasil scan tersimpan (1.3 &rarr; D4) | Seimbang |
| Out: Catatan aktivitas masukan data &rarr; D7 | Out: Catatan aktivitas pengguna, lisensi, scan &rarr; D7 | Seimbang |

---

## 3. DFD Level 2 – Proses 2.0: Pemrosesan dan Evaluasi Kepatuhan (*Proses*)

### 3.1. Dekomposisi Sub-Proses 2.0

Proses 2.0 merupakan modul logika bisnis (*business rules & processing*) yang mengevaluasi status lisensi instalasi software serta alur kerja persetujuan berkala. Proses ini didekomposisi menjadi tiga (3) sub-proses:

```
[2.0 Pemrosesan dan Evaluasi Kepatuhan]
   ├── 2.1 Evaluasi Kepatuhan Perangkat Lunak
   ├── 2.2 Perhitungan Alokasi dan Kebutuhan Lisensi
   └── 2.3 Pengelolaan Persetujuan Laporan Laboratorium
```

1. **Sub-Proses 2.1: Evaluasi Kepatuhan Perangkat Lunak**
   - **Tanggung Jawab:** Mengeksekusi pencocokan aturan instalasi software per komputer. Memeriksa keberadaan software terlarang (*blacklist/crack*), otomatisasi persetujuan *freeware*, dan mengecek validitas lisensi komersial aktif.
   - **Masukan Penyimpanan:** Membaca `D3` (*Data lisensi & status katalog*) dan `D4` (*Data komputer & software terinstal*).
   - **Keluaran Penyimpanan:** Menyimpan hasil kalkulasi ke `D5` (*Data status kepatuhan terhitung*) dan mencatat audit ke `D7` (*Catatan audit kepatuhan*).

2. **Sub-Proses 2.2: Perhitungan Alokasi dan Kebutuhan Lisensi**
   - **Tanggung Jawab:** Menghitung perbandingan kuota lisensi yang dimiliki terhadap jumlah instalasi riil di seluruh fakultas dan laboratorium (*entitlement vs installed*), menghitung utilisasi, serta menghasilkan rekomendasi pengadaan/kebutuhan lisensi.
   - **Masukan Penyimpanan:** Membaca `D3` (*Data kuota alokasi lisensi*) dan `D5` (*Data kepatuhan instalasi*).
   - **Keluaran Penyimpanan:** Menyimpan rekapitulasi kebutuhan ke `D5` (*Data rekapitulasi kebutuhan lisensi*).

3. **Sub-Proses 2.3: Pengelolaan Persetujuan Laporan Laboratorium**
   - **Tanggung Jawab:** Mengelola alur kerja verifikasi persetujuan laporan berkala. Administrator mengajukan draf laporan kepatuhan laboratorium, Kepala Laboratorium menelaah dan menetapkan keputusan persetujuan/penolakan disertai catatan evaluasi.
   - **Entitas Masukan:**
     - Administrator: *Pengajuan laporan kepatuhan*.
     - Kepala Laboratorium: *Status persetujuan laporan*, *Catatan peninjauan*.
   - **Masukan Penyimpanan:** Membaca agregat kepatuhan dari `D5` (*Data hasil kepatuhan laboratorium*).
   - **Keluaran Penyimpanan:** Menyimpan keputusan ke `D6` (*Data persetujuan laporan tersimpan*) dan mencatat log ke `D7` (*Catatan audit persetujuan*).

---

### 3.2. Matriks Alur Data DFD Level 2 – Proses 2.0

| No | Sumber | Tujuan | Label Alur Data | Keterangan |
|:--:|--------|--------|-----------------|------------|
| 1 | Data Store D3 | Sub-Proses 2.1 | Data lisensi & status katalog | Status blacklist/whitelist dan lisensi aktif. |
| 2 | Data Store D4 | Sub-Proses 2.1 | Data komputer & software terinstal | Daftar instalasi software aktual pada komputer. |
| 3 | Sub-Proses 2.1 | Data Store D5 | Data status kepatuhan terhitung | Status Berlisensi/Tidak Berlisensi/Grace Period. |
| 4 | Data Store D3 | Sub-Proses 2.2 | Data kuota alokasi lisensi | Hak kuota lisensi universitas dan fakultas. |
| 5 | Data Store D5 | Sub-Proses 2.2 | Data kepatuhan instalasi | Jumlah unit komputer yang terinstal software komersial. |
| 6 | Sub-Proses 2.2 | Data Store D5 | Data rekapitulasi kebutuhan lisensi | Defisit/surplus kuota dan rekomendasi pengadaan. |
| 7 | Administrator | Sub-Proses 2.3 | Pengajuan laporan kepatuhan | Pengajuan draf rekap periode lab ke Kepala Lab. |
| 8 | Kepala Laboratorium | Sub-Proses 2.3 | Status persetujuan laporan | Keputusan Disetujui (*Approved*) atau Ditolak (*Rejected*). |
| 9 | Kepala Laboratorium | Sub-Proses 2.3 | Catatan peninjauan | Evaluasi tertulis hasil telaah Kepala Lab. |
| 10 | Data Store D5 | Sub-Proses 2.3 | Data hasil kepatuhan laboratorium | Agregat kepatuhan komputer lingkup lab terkait. |
| 11 | Sub-Proses 2.3 | Data Store D6 | Data persetujuan laporan tersimpan | Rekam status verifikasi pada tabel `report_approvals`. |
| 12 | Sub-Proses 2.1 | Data Store D7 | Catatan audit kepatuhan | Audit riwayat evaluasi berkala kepatuhan. |
| 13 | Sub-Proses 2.3 | Data Store D7 | Catatan audit persetujuan | Audit riwayat keputusan persetujuan laporan. |

---

### 3.3. Verifikasi Keseimbangan (*Balancing*) Proses 2.0

| Alur Data pada DFD Level 1 (Proses 2.0) | Alur Data pada DFD Level 2 (2.1, 2.2, 2.3) | Status |
|------------------------------------------|--------------------------------------------|:------:|
| In: Pengajuan laporan kepatuhan (Admin) | In: Pengajuan laporan kepatuhan &rarr; Sub-Proses 2.3 | Seimbang |
| In: Status persetujuan laporan (Kepala Lab) | In: Status persetujuan laporan &rarr; Sub-Proses 2.3 | Seimbang |
| In: Catatan peninjauan (Kepala Lab) | In: Catatan peninjauan &rarr; Sub-Proses 2.3 | Seimbang |
| In: Data lisensi & status katalog (dari D3) | In: Data lisensi & status katalog (D3 &rarr; 2.1) | Seimbang |
| In: Data komputer & software terinstal (dari D4) | In: Data komputer & software terinstal (D4 &rarr; 2.1) | Seimbang |
| Out: Data status kepatuhan terhitung &rarr; D5 | Out: Data status kepatuhan terhitung (2.1 &rarr; D5) | Seimbang |
| Out: Data persetujuan laporan tersimpan &rarr; D6 | Out: Data persetujuan laporan tersimpan (2.3 &rarr; D6) | Seimbang |
| Out: Catatan aktivitas evaluasi & verifikasi &rarr; D7 | Out: Catatan audit kepatuhan & persetujuan &rarr; D7 | Seimbang |

---

## 4. DFD Level 2 – Proses 3.0: Penyajian Informasi dan Laporan (*Output*)

### 4.1. Dekomposisi Sub-Proses 3.0

Proses 3.0 bertindak sebagai subsistem penyaji informasi (*presentation and output distribution layer*). Proses ini membaca data yang telah terkonsolidasi dari seluruh repositori basis data untuk disajikan kepada pemangku kepentingan. Proses ini didekomposisi menjadi tiga (3) sub-proses:

```
[3.0 Penyajian Informasi dan Laporan]
   ├── 3.1 Penyajian Dashboard Visual
   ├── 3.2 Pembuatan Laporan Resmi (PDF/Excel)
   └── 3.3 Distribusi Paket dan Otorisasi Teknis
```

1. **Sub-Proses 3.1: Penyajian Dashboard Visual**
   - **Tanggung Jawab:** Mengompilasi dan menampilkan metrik analitik visual antarmuka web secara interaktif sesuai hak akses peran pengguna (Admin, Pimpinan, Kepala Lab).
   - **Entitas Masukan:** Pimpinan (*Permintaan laporan eksekutif*).
   - **Masukan Penyimpanan:** Membaca data ringkasan dari `D1`, `D2`, `D3`, `D4`, `D5`, dan `D6`.
   - **Keluaran ke Entitas:**
     - Administrator: *Dashboard sistem* (kesehatan sistem, distribusi OS, status lisensi, tren scan).
     - Pimpinan: *Dashboard eksekutif*, *Matriks kepatuhan lintas fakultas* (terfilter hanya untuk lab yang disetujui).
     - Kepala Laboratorium: *Dashboard laboratorium* (statistik komputer lab, software teratas lab).

2. **Sub-Proses 3.2: Pembuatan Laporan Resmi (PDF/Excel)**
   - **Tanggung Jawab:** Mengompilasi dan mengonversi data transaksional menjadi berkas dokumen cetak formal (format PDF) dan lembar kerja tabulasi data (format spreadsheet Excel).
   - **Masukan Penyimpanan:** Membaca data detail dari `D1`, `D2`, `D3`, `D4`, `D5`, `D6`, dan `D7`.
   - **Keluaran ke Entitas:**
     - Administrator: *Laporan kepatuhan lisensi*, *Laporan inventaris komputer*, *Laporan inventaris perangkat lunak*, *Laporan perubahan perangkat lunak*, *Laporan kebutuhan lisensi*, *Laporan monitoring pemindaian*, *Log aktivitas*.
     - Pimpinan: *Laporan kebutuhan lisensi*, *Laporan status lisensi*.
     - Kepala Laboratorium: *Laporan kepatuhan laboratorium*, *Laporan menunggu persetujuan*.
     - Staf Laboratorium: *Inventaris komputer laboratorium*.

3. **Sub-Proses 3.3: Distribusi Paket dan Otorisasi Teknis**
   - **Tanggung Jawab:** Menyediakan berkas instalasi teknis, kredensial otorisasi API, respons instruksi pemindaian, serta layanan pengungkapan kunci lisensi yang aman.
   - **Masukan Penyimpanan:** Membaca kunci terenkripsi dari `D3` serta status registrasi dan perintah scan dari `D4`.
   - **Keluaran ke Entitas:**
     - Administrator: *Kunci lisensi* (nilai terdekripsi dengan pencatatan audit log).
     - Kepala Laboratorium: *Paket agent scanner* (arsip ZIP konfigurasi lab).
     - Staf Laboratorium: *Paket agent scanner*, *Token autentikasi* (Sanctum bearer token), *Perintah pemindaian* (respons polling `{should_scan: true/false}`).

---

### 4.2. Matriks Alur Data DFD Level 2 – Proses 3.0

| No | Sumber | Tujuan | Label Alur Data | Keterangan |
|:--:|--------|--------|-----------------|------------|
| 1 | Pimpinan | Sub-Proses 3.1 | Permintaan laporan eksekutif | Permintaan pembukaan data metrik pimpinan. |
| 2 | Data Store D1–D6 | Sub-Proses 3.1 | Data metrik ringkasan | Agregasi data pengguna, lisensi, scan, kepatuhan. |
| 3 | Sub-Proses 3.1 | Administrator | Dashboard sistem | Tampilan visual metrik global sistem. |
| 4 | Sub-Proses 3.1 | Pimpinan | Dashboard eksekutif | Tampilan visual rasio kepatuhan universitas. |
| 5 | Sub-Proses 3.1 | Pimpinan | Matriks kepatuhan lintas fakultas | Matriks sebaran kepatuhan fakultas yang disetujui. |
| 6 | Sub-Proses 3.1 | Kepala Laboratorium | Dashboard laboratorium | Tampilan visual statistik komputer dan software lab. |
| 7 | Data Store D1–D7 | Sub-Proses 3.2 | Data transaksional & log | Sumber data lengkap untuk penyusunan laporan. |
| 8 | Sub-Proses 3.2 | Administrator | Laporan kepatuhan lisensi | Dokumen kepatuhan lisensi institusi (PDF/Excel). |
| 9 | Sub-Proses 3.2 | Administrator | Laporan inventaris komputer | Rekap aset perangkat keras komputer (PDF/Excel). |
| 10 | Sub-Proses 3.2 | Administrator | Laporan inventaris perangkat lunak | Rekap sebaran perangkat lunak terinstal (PDF/Excel). |
| 11 | Sub-Proses 3.2 | Administrator | Laporan perubahan perangkat lunak | Audit penambahan/penghapusan software (PDF/Excel). |
| 12 | Sub-Proses 3.2 | Administrator | Laporan kebutuhan lisensi | Rekomendasi kuota pengadaan lisensi (PDF/Excel). |
| 13 | Sub-Proses 3.2 | Administrator | Laporan monitoring pemindaian | Rekap keberhasilan scan komputer lab (PDF/Excel). |
| 14 | Sub-Proses 3.2 | Administrator | Log aktivitas | Rekaman kronologis audit operasional sistem. |
| 15 | Sub-Proses 3.2 | Pimpinan | Laporan kebutuhan lisensi | Dokumen kebutuhan lisensi perspektif eksekutif. |
| 16 | Sub-Proses 3.2 | Pimpinan | Laporan status lisensi | Dokumen inventaris lisensi dan masa berlaku. |
| 17 | Sub-Proses 3.2 | Kepala Laboratorium | Laporan kepatuhan laboratorium | Dokumen rekap kepatuhan khusus unit laboratorium. |
| 18 | Sub-Proses 3.2 | Kepala Laboratorium | Laporan menunggu persetujuan | Daftar berkas laporan lab yang siap ditelaah. |
| 19 | Sub-Proses 3.2 | Staf Laboratorium | Inventaris komputer laboratorium | Daftar aset komputer dalam laboratorium operator. |
| 20 | Data Store D3, D4 | Sub-Proses 3.3 | Data paket scanner, token & kunci | Berkas skrip, kunci terenkripsi, flag scan. |
| 21 | Sub-Proses 3.3 | Administrator | Kunci lisensi | Nilai kunci lisensi terdekripsi (teraudit). |
| 22 | Sub-Proses 3.3 | Kepala Laboratorium | Paket agent scanner | Berkas ZIP installer agent untuk laboratorium. |
| 23 | Sub-Proses 3.3 | Staf Laboratorium | Paket agent scanner | Berkas ZIP installer agent scanner. |
| 24 | Sub-Proses 3.3 | Staf Laboratorium | Token autentikasi | API token Sanctum bagi PC lab terdaftar. |
| 25 | Sub-Proses 3.3 | Staf Laboratorium | Perintah pemindaian | Respons perintah pemindaian komputer klien. |

---

### 4.3. Verifikasi Keseimbangan (*Balancing*) Proses 3.0

| Alur Data pada DFD Level 1 (Proses 3.0) | Alur Data pada DFD Level 2 (3.1, 3.2, 3.3) | Status |
|------------------------------------------|--------------------------------------------|:------:|
| In: Permintaan laporan eksekutif (Pimpinan) | In: Permintaan laporan eksekutif &rarr; 3.1 | Seimbang |
| Out: Dashboard sistem (Admin) | Out: Dashboard sistem (3.1 &rarr; Admin) | Seimbang |
| Out: Dashboard eksekutif (Pimpinan) | Out: Dashboard eksekutif (3.1 &rarr; Pimpinan) | Seimbang |
| Out: Matriks kepatuhan lintas fakultas (Pimpinan) | Out: Matriks kepatuhan lintas fakultas (3.1 &rarr; Pimpinan) | Seimbang |
| Out: Dashboard laboratorium (Kepala Lab) | Out: Dashboard laboratorium (3.1 &rarr; Kepala Lab) | Seimbang |
| Out: Laporan kepatuhan lisensi (Admin) | Out: Laporan kepatuhan lisensi (3.2 &rarr; Admin) | Seimbang |
| Out: Laporan inventaris komputer (Admin) | Out: Laporan inventaris komputer (3.2 &rarr; Admin) | Seimbang |
| Out: Laporan inventaris perangkat lunak (Admin) | Out: Laporan inventaris perangkat lunak (3.2 &rarr; Admin) | Seimbang |
| Out: Laporan perubahan perangkat lunak (Admin) | Out: Laporan perubahan perangkat lunak (3.2 &rarr; Admin) | Seimbang |
| Out: Laporan kebutuhan lisensi (Admin) | Out: Laporan kebutuhan lisensi (3.2 &rarr; Admin) | Seimbang |
| Out: Laporan monitoring pemindaian (Admin) | Out: Laporan monitoring pemindaian (3.2 &rarr; Admin) | Seimbang |
| Out: Log aktivitas (Admin) | Out: Log aktivitas (3.2 &rarr; Admin) | Seimbang |
| Out: Laporan kebutuhan lisensi (Pimpinan) | Out: Laporan kebutuhan lisensi (3.2 &rarr; Pimpinan) | Seimbang |
| Out: Laporan status lisensi (Pimpinan) | Out: Laporan status lisensi (3.2 &rarr; Pimpinan) | Seimbang |
| Out: Laporan kepatuhan laboratorium (Kepala Lab) | Out: Laporan kepatuhan laboratorium (3.2 &rarr; Kepala Lab) | Seimbang |
| Out: Laporan menunggu persetujuan (Kepala Lab) | Out: Laporan menunggu persetujuan (3.2 &rarr; Kepala Lab) | Seimbang |
| Out: Inventaris komputer laboratorium (Staf Lab) | Out: Inventaris komputer laboratorium (3.2 &rarr; Staf Lab) | Seimbang |
| Out: Kunci lisensi (Admin) | Out: Kunci lisensi (3.3 &rarr; Admin) | Seimbang |
| Out: Paket agent scanner (Kepala Lab) | Out: Paket agent scanner (3.3 &rarr; Kepala Lab) | Seimbang |
| Out: Paket agent scanner (Staf Lab) | Out: Paket agent scanner (3.3 &rarr; Staf Lab) | Seimbang |
| Out: Token autentikasi (Staf Lab) | Out: Token autentikasi (3.3 &rarr; Staf Lab) | Seimbang |
| Out: Perintah pemindaian (Staf Lab) | Out: Perintah pemindaian (3.3 &rarr; Staf Lab) | Seimbang |

---

## 5. Kamus Data Komprehensif (*Comprehensive Data Dictionary*)

Berikut adalah spesifikasi notasi struktur data yang digunakan pada DFD Level 2:

1. **Struktur Data Master Pengguna:**
   `Data Pengguna = nama + email + kata_sandi + peran + status_aktif + (laboratorium_id) + (fakultas_id)`

2. **Struktur Data Master Organisasi:**
   `Data Fakultas = kode_fakultas + nama_fakultas + (deskripsi)`  
   `Data Laboratorium = fakultas_id + kode_lab + nama_lab + gedung + lantai + (deskripsi)`

3. **Struktur Data Lisensi & Katalog:**
   `Data Katalog Software = nama_normalisasi + kategori + status_kebijakan + (deskripsi)`  
   - `kategori = [ "Commercial" | "Freeware" ]`  
   - `status_kebijakan = [ "Whitelist" | "Blacklist" | "Unreviewed" ]`  
   `Data Lisensi Perangkat Lunak = katalog_id + nomor_po + kunci_terenkripsi + kuota_limit + tgl_beli + tgl_kedaluwarsa + harga_satuan + (bukti_pembelian)`  
   `Data Alokasi Lisensi = lisensi_id + fakultas_id + kuota_alokasi + tgl_alokasi + tgl_mulai + tgl_selesai + status_alokasi`

4. **Struktur Data Komputer & Pemindaian:**
   `Data Komputer = hostname + mac_address + serial_number + ip_address + pabrikan + model + os_nama + os_versi + os_arsitektur + os_status_lisensi + prosesor + ram_gb + disk_total_gb + disk_free_gb + laboratorium_id + status_koneksi + scan_requested`  
   `Data Sesi Pemindaian = scan_uuid + computer_id + versi_agent + trigger_scan + tgl_mulai + tgl_selesai + status_sesi + software_count + (error_message)`  
   `Data Hasil Pemindaian Software = sesi_id + katalog_id + raw_name + versi + vendor + tgl_instalasi`

5. **Struktur Data Status Kepatuhan:**
   `Data Status Kepatuhan = computer_id + katalog_id + software_name + software_version + status_kepatuhan + keterangan + (lisensi_id) + tgl_deteksi + tgl_scan`  
   - `status_kepatuhan = [ "Berlisensi" | "Tidak Berlisensi" | "Grace Period" ]`

6. **Struktur Data Persetujuan Laporan:**
   `Data Persetujuan Laporan = laboratorium_id + periode_tahun_bulan + tgl_mulai_periode + tgl_selesai_periode + status_persetujuan + (catatan_peninjauan) + peninjau_id + tgl_tinjauan`  
   - `status_persetujuan = [ "pending" | "approved" | "rejected" ]`

7. **Struktur Data Audit Log:**
   `Catatan Log Aktivitas = deskripsi_aktivitas + log_name + subject_type + subject_id + causer_type + causer_id + properti_perubahan + created_at`

---

## 6. Kesimpulan

Perancangan Data Flow Diagram (DFD) Level 2 Sistem Informasi Manifest USN menyajikan dekomposisi mendalam dari tiga proses utama siklus IPO. Melalui pemisahan ini, setiap modul operasional terpetakan secara presisi:
- **Proses 1.0 (Input):** Terbagi menjadi Sub-Proses 1.1, 1.2, dan 1.3 yang masing-masing mengurusi masukan data organisasi/pengguna, lisensi/katalog, dan aset komputer laboratorium.
- **Proses 2.0 (Proses):** Terbagi menjadi Sub-Proses 2.1, 2.2, dan 2.3 yang mengisolasi algoritma evaluasi lisensi, kalkulasi defisit/kebutuhan kuota, dan alur verifikasi berkala pimpinan lab.
- **Proses 3.0 (Output):** Terbagi menjadi Sub-Proses 3.1, 3.2, dan 3.3 yang menangani dashboard visual interaktif, dokumen laporan cetak, dan transmisi paket/token teknis.

Ketiadaan defisit alur data (*zero net-flow violation*) antara DFD Level 0, DFD Level 1, dan ketiga diagram DFD Level 2 membuktikan bahwa perancangan sistem ini memiliki konsistensi struktural dan integritas akademik yang memenuhi syarat pengujian skripsi.

---

## Daftar Pustaka

- Kendall, K. E., & Kendall, J. E. (2019). *Systems Analysis and Design* (10th ed.). Pearson.
- Pressman, R. S., & Maxim, B. R. (2020). *Software Engineering: A Practitioner's Approach* (9th ed.). McGraw-Hill.
- Whitten, J. L., & Bentley, L. D. (2007). *Systems Analysis and Design Methods* (7th ed.). McGraw-Hill.
- Yourdon, E. (1989). *Modern Structured Analysis*. Prentice Hall.

---
*Dokumen ini disusun sebagai dokumentasi perancangan sistem akademik pada Sistem Informasi Manifest USN Kolaka, Oktober 2026.*
