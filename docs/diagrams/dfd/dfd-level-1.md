# Penjelasan Data Flow Diagram (DFD) Level 1 Sistem Informasi Manifest USN
*(Model Siklus Input - Proses - Output)*

---

## 1. Pendahuluan

Dokumen ini menyajikan penjelasan komprehensif mengenai **Data Flow Diagram (DFD) Level 1** untuk Sistem Informasi Manifest Universitas Sembilanbelas November (USN) Kolaka. Perancangan DFD Level 1 ini mengadopsi model dekomposisi berbasis siklus **Input – Proses – Output (IPO)** yang terdiri dari tiga (3) proses utama, disesuaikan dengan kaidah penulisan skripsi akademik bidang Rekayasa Perangkat Lunak dan Sistem Informasi (Kendall & Kendall, 2019; Pressman & Maxim, 2020).

Model tiga proses ini memberikan kejelasan konseptual yang tinggi bagi penguji dan pembaca skripsi karena memisahkan secara tegas:
1. **Fase Masukan (*Input*):** Pengelolaan master data dan akuisisi data inventaris komputer.
2. **Fase Pemrosesan (*Process*):** Evaluasi otomatis kepatuhan lisensi dan alur kerja verifikasi persetujuan laporan.
3. **Fase Keluaran (*Output*):** Penyajian informasi dashboard eksekutif/operasional dan penerbitan dokumen laporan resmi.

Seluruh penamaan alur data (*data flow*) pada diagram dan dokumen ini mematuhi kaidah akademik baku, yaitu menggunakan frasa **kata benda** (*noun phrase*) tanpa menggunakan kata kerja tindakan, serta menjaga keseimbangan (*balancing rule*) 100% terhadap Diagram Konteks (DFD Level 0).

---

## 2. Dekomposisi Fungsional Berbasis Siklus IPO

Proses tunggal pada DFD Level 0 (**0. Sistem Informasi Manifest USN**) didekomposisi menjadi tiga (3) sub-proses logis pada DFD Level 1:

```
[0. Sistem Informasi Manifest USN]
   │
   ├── 1.0 Pengelolaan Data Masukan (Input)
   │     ├── Manajemen Master Data Pengguna & Organisasi
   │     ├── Manajemen Lisensi & Katalog Perangkat Lunak
   │     └── Registrasi Komputer & Penerimaan Payload Pemindaian
   │
   ├── 2.0 Pemrosesan dan Evaluasi Kepatuhan (Proses)
   │     ├── Evaluasi Kepatuhan Lisensi & Deteksi Perangkat Lunak Terlarang
   │     ├── Perhitungan Defisit/Surplus Kuota Lisensi
   │     └── Pengajuan & Peninjauan Persetujuan Laporan Laboratorium
   │
   └── 3.0 Penyajian Informasi dan Laporan (Output)
         ├── Kompilasi Dashboard Multi-Peran (Admin, Pimpinan, Kepala Lab)
         ├── Penerbitan Dokumen Laporan Resmi (PDF & Excel)
         └── Distribusi Output Teknis (Token, Perintah Scan, Paket Agent, Kunci Lisensi)
```

---

### 2.1. Proses 1.0: Pengelolaan Data Masukan (*Input*)

- **Deskripsi:**  
  Proses 1.0 bertanggung jawab sebagai pintu gerbang (*gateway*) penerimaan, validasi integritas, dan penyimpanan seluruh data mentah (*raw data*) yang masuk ke dalam sistem. Data masukan berasal dari interaksi manusia (Administrator) dan interaksi mesin melalui operator laboratorium (Staf Laboratorium / Agent Scanner).
- **Fungsi Utama:**
  1. Menerima dan memvalidasi data master pengguna (akun, peran, otorisasi) dan organisasi (fakultas, laboratorium).
  2. Menerima data pengadaan lisensi perangkat lunak, bukti pembelian, alokasi kuota fakultas, serta standardisasi nama katalog software.
  3. Menerima permohonan registrasi identitas komputer laboratorium (MAC address, serial number, hostname) dan permintaan pemindaian (*on-demand scan*).
  4. Menerima muatan (*payload*) hasil pemindaian software aktual dan spesifikasi hardware dari komputer klien.
- **Interaksi Entitas Luar:**
  - **Administrator:** Mengirimkan *Data pengguna*, *Data fakultas*, *Data laboratorium*, *Data lisensi perangkat lunak*, *Data alokasi lisensi*, *Data katalog perangkat lunak*, *Data komputer*, dan *Permintaan pemindaian*.
  - **Staf Laboratorium:** Mengirimkan *Data registrasi komputer*, *Data hasil pemindaian*, dan *Polling perintah pemindaian*.
- **Interaksi Data Store:**
  - Menyimpan data ke `D1` (Data Pengguna), `D2` (Data Organisasi), `D3` (Data Lisensi & Katalog), dan `D4` (Data Komputer & Pemindaian).
  - Mencatat rekam jejak masukan data ke `D7` (Data Log Aktivitas).

---

### 2.2. Proses 2.0: Pemrosesan dan Evaluasi Kepatuhan (*Proses*)

- **Deskripsi:**  
  Proses 2.0 merupakan inti komputasi (*core business logic*) sistem. Proses ini mengeksekusi logika bisnis analisis kepatuhan secara otomatis dan mengelola alur kerja persetujuan berjenjang antara pengelola institusi dan pimpinan unit laboratorium.
- **Fungsi Utama:**
  1. **Algoritma Evaluasi Kepatuhan:** Membandingkan software yang terdeteksi pada setiap komputer (`D4`) terhadap aturan katalog (`D3`). Software yang terdaftar pada daftar blokir (*piracy tools* seperti KMSPico, uTorrent) langsung ditandai *Tidak Berlisensi*. Software kategori *Freeware* otomatis berstatus *Berlisensi*. Software komersial dievaluasi terhadap ketersediaan alokasi lisensi aktif dan masa berlaku kuota.
  2. **Kalkulasi Kuota & Defisit/Surplus:** Menghitung rasio instalasi software terhadap hak lisensi (*entitlement vs installed*) di tingkat universitas, fakultas, dan laboratorium.
  3. **Manajemen Persetujuan Laporan (*Approval Workflow*):** Memproses pengajuan laporan periode kepatuhan oleh Administrator dan memproses tinjauan evaluasi (persetujuan atau penolakan dengan catatan) oleh Kepala Laboratorium.
- **Interaksi Entitas Luar:**
  - **Administrator:** Mengirimkan *Pengajuan laporan kepatuhan*.
  - **Kepala Laboratorium:** Mengirimkan *Status persetujuan laporan* dan *Catatan peninjauan*.
- **Interaksi Data Store:**
  - Membaca data referensi dari `D3` (Data Lisensi & Katalog) dan `D4` (Data Komputer & Pemindaian).
  - Menyimpan hasil analisis kepatuhan ke `D5` (Data Kepatuhan Lisensi).
  - Menyimpan status dan catatan verifikasi ke `D6` (Data Persetujuan Laporan).
  - Mencatat aktivitas evaluasi dan persetujuan ke `D7` (Data Log Aktivitas).

---

### 2.3. Proses 3.0: Penyajian Informasi dan Laporan (*Output*)

- **Deskripsi:**  
  Proses 3.0 bertindak sebagai subsistem penyaji informasi (*reporting & presentation subsystem*). Proses ini membaca data yang telah terstruktur dan terverifikasi dari seluruh repositori penyimpanan untuk diolah menjadi informasi manajerial, dokumen cetak resmi, serta umpan balik teknis ke operator.
- **Fungsi Utama:**
  1. **Dashboard Visual Multi-Peran:** Menyajikan visualisasi data analitik spesifik hak akses (Dashboard Sistem untuk Admin, Dashboard Eksekutif lintas fakultas untuk Pimpinan, dan Dashboard Laboratorium untuk Kepala Lab).
  2. **Penerbitan Laporan Resmi:** Mengompilasi dan mengekspor dokumen laporan dalam format PDF dan lembar kerja Excel (Laporan Kepatuhan, Laporan Inventaris Komputer/Software, Laporan Perubahan Software, Laporan Analisis Kebutuhan Lisensi, dan Rekap Monitoring Pemindaian).
  3. **Distribusi Output Operasional & Teknis:** Menyediakan paket instalasi agent scanner (format ZIP), token autentikasi API (*Sanctum*), instruksi eksekusi pemindaian (*scan command*), dan pengungkapan kunci lisensi yang terenkripsi (*license key reveal* dengan batasan throttle).
- **Interaksi Entitas Luar:**
  - **Administrator:** Menerima *Dashboard sistem*, *Laporan kepatuhan lisensi*, *Laporan inventaris komputer*, *Laporan inventaris perangkat lunak*, *Laporan perubahan perangkat lunak*, *Laporan kebutuhan lisensi*, *Laporan monitoring pemindaian*, *Log aktivitas*, dan *Kunci lisensi*.
  - **Pimpinan:** Mengirimkan *Permintaan laporan eksekutif*; menerima *Dashboard eksekutif*, *Matriks kepatuhan lintas fakultas*, *Laporan kebutuhan lisensi*, dan *Laporan status lisensi*.
  - **Kepala Laboratorium:** Mengirimkan *Permintaan kunci lisensi*; menerima *Dashboard laboratorium*, *Laporan kepatuhan laboratorium*, *Laporan menunggu persetujuan*, *Paket agent scanner*, *Informasi lisensi fakultas*, dan *Kunci lisensi*.
  - **Staf Laboratorium:** Mengirimkan *Permintaan kunci lisensi*; menerima *Paket agent scanner*, *Token autentikasi*, *Perintah pemindaian*, *Inventaris komputer laboratorium*, *Informasi lisensi fakultas*, dan *Kunci lisensi*.
- **Interaksi Data Store:**
  - Membaca data secara agregat dari `D1` (Data Pengguna), `D2` (Data Organisasi), `D3` (Data Lisensi & Katalog), `D4` (Data Komputer & Pemindaian), `D5` (Data Kepatuhan Lisensi), `D6` (Data Persetujuan Laporan), dan `D7` (Data Log Aktivitas).

---

## 3. Spesifikasi Data Store (Penyimpanan Data Persisten)

Data store dikonsolidasikan menjadi tujuh (7) repositori persisten logis:

| Kode | Nama Data Store | Deskripsi Isi Data | Tabel Basis Data Terkait |
|------|-----------------|--------------------|--------------------------|
| **D1** | Data Pengguna | Akun pengguna, otentikasi kata sandi, peran hak akses, dan keterkaitan unit kerja. | `users`, `roles`, `model_has_roles` |
| **D2** | Data Organisasi | Master data struktur hierarki fakultas dan laboratorium komputer. | `faculties`, `laboratories` |
| **D3** | Data Lisensi & Katalog | Master katalog software ternormalisasi, status blacklist/whitelist, inventaris lisensi pembelian (kunci lisensi terenkripsi), kuota, dan jatah alokasi lisensi fakultas. | `software_catalogs`, `license_inventories`, `license_allocations` |
| **D4** | Data Komputer & Pemindaian | Identitas hardware komputer, spesifikasi teknis, alamat jaringan, sesi scan historis, dan snapshot daftar software terdeteksi. | `computers`, `scan_sessions`, `scan_software_results`, `software_discoveries` |
| **D5** | Data Kepatuhan Lisensi | Rekam status kepatuhan terhitung per komputer per software (Berlisensi, Tidak Berlisensi, Grace Period) dan snapshot periodik. | `compliance_reports`, `compliance_snapshots` |
| **D6** | Data Persetujuan Laporan | Pengajuan rekap laporan kepatuhan laboratorium per periode, status verifikasi (Disetujui/Ditolak), dan catatan peninjauan Kepala Lab. | `report_approvals` |
| **D7** | Data Log Aktivitas | Catatan audit trail seluruh operasi sistem (mutasi data master, pengungkapan kunci lisensi, riwayat verifikasi). | `activity_log` |

---

## 4. Matriks Alur Data (*Data Flow Matrix*)

### 4.1. Alur Data Entitas Luar ke/dari Proses

| Entitas Luar | Arah | Label Alur Data | Proses Terkait | Keterangan |
|--------------|------|-----------------|----------------|------------|
| **Administrator** | Masuk &rarr; | Data pengguna | `1.0` (Input) | Masukan master akun dan hak akses pengguna. |
| **Administrator** | Masuk &rarr; | Data fakultas | `1.0` (Input) | Masukan master unit fakultas. |
| **Administrator** | Masuk &rarr; | Data laboratorium | `1.0` (Input) | Masukan master unit laboratorium komputer. |
| **Administrator** | Masuk &rarr; | Data lisensi perangkat lunak | `1.0` (Input) | Masukan data pembelian lisensi dan kuota institusi. |
| **Administrator** | Masuk &rarr; | Data alokasi lisensi | `1.0` (Input) | Masukan penetapan kuota lisensi ke fakultas. |
| **Administrator** | Masuk &rarr; | Data katalog perangkat lunak | `1.0` (Input) | Masukan standardisasi kategori dan kebijakan software. |
| **Administrator** | Masuk &rarr; | Data komputer | `1.0` (Input) | Masukan pembaruan data lokasi/status komputer. |
| **Administrator** | Masuk &rarr; | Permintaan pemindaian | `1.0` (Input) | Instruksi pemicuan scan komputer on-demand. |
| **Administrator** | Masuk &rarr; | Pengajuan laporan kepatuhan | `2.0` (Proses) | Pengajuan draf rekap laporan ke kepala lab. |
| **Administrator** | &larr; Keluar | Dashboard sistem | `3.0` (Output) | Tampilan ringkasan metrik kesehatan sistem. |
| **Administrator** | &larr; Keluar | Laporan kepatuhan lisensi | `3.0` (Output) | Dokumen kepatuhan software per komputer (PDF/Excel). |
| **Administrator** | &larr; Keluar | Laporan inventaris komputer | `3.0` (Output) | Dokumen rekap aset hardware komputer (PDF/Excel). |
| **Administrator** | &larr; Keluar | Laporan inventaris perangkat lunak | `3.0` (Output) | Dokumen sebaran software institusi (PDF/Excel). |
| **Administrator** | &larr; Keluar | Laporan perubahan perangkat lunak | `3.0` (Output) | Dokumen audit penambahan/penghapusan software. |
| **Administrator** | &larr; Keluar | Laporan kebutuhan lisensi | `3.0` (Output) | Dokumen analisis defisit/surplus pengadaan lisensi. |
| **Administrator** | &larr; Keluar | Laporan monitoring pemindaian | `3.0` (Output) | Dokumen rekap keberhasilan pemindaian komputer. |
| **Administrator** | &larr; Keluar | Log aktivitas | `3.0` (Output) | Rekaman kronologis audit sistem. |
| **Administrator** | &larr; Keluar | Kunci lisensi | `3.0` (Output) | Nilai kunci lisensi terdekripsi (dengan pembatasan). |
| **Staf Laboratorium** | Masuk &rarr; | Data registrasi komputer | `1.0` (Input) | Registrasi identitas fisik komputer lab via agent. |
| **Staf Laboratorium** | Masuk &rarr; | Data hasil pemindaian | `1.0` (Input) | Muatan data hardware & software hasil scan. |
| **Staf Laboratorium** | Masuk &rarr; | Polling perintah pemindaian | `1.0` (Input) | Pengecekan berkala adanya perintah scan aktif. |
| **Staf Laboratorium** | Masuk &rarr; | Permintaan kunci lisensi | `3.0` (Output) | Permintaan buka kunci lisensi alokasi fakultas. |
| **Staf Laboratorium** | &larr; Keluar | Paket agent scanner | `3.0` (Output) | Paket berkas ZIP scanner untuk instalasi lab. |
| **Staf Laboratorium** | &larr; Keluar | Token autentikasi | `3.0` (Output) | Token otorisasi API Sanctum bagi komputer terdaftar. |
| **Staf Laboratorium** | &larr; Keluar | Perintah pemindaian | `3.0` (Output) | Respons status instruksi pemindaian (true/false). |
| **Staf Laboratorium** | &larr; Keluar | Inventaris komputer laboratorium | `3.0` (Output) | Daftar aset komputer dalam lingkup lab operator. |
| **Staf Laboratorium** | &larr; Keluar | Informasi lisensi fakultas | `3.0` (Output) | Daftar lisensi software yang dialokasikan ke fakultas. |
| **Staf Laboratorium** | &larr; Keluar | Kunci lisensi | `3.0` (Output) | Nilai kunci lisensi terdekripsi (teraudit & auto-hide). |
| **Kepala Laboratorium** | Masuk &rarr; | Status persetujuan laporan | `2.0` (Proses) | Keputusan persetujuan/penolakan laporan lab. |
| **Kepala Laboratorium** | Masuk &rarr; | Catatan peninjauan | `2.0` (Proses) | Evaluasi tertulis hasil telaah laporan. |
| **Kepala Laboratorium** | Masuk &rarr; | Permintaan kunci lisensi | `3.0` (Output) | Permintaan buka kunci lisensi alokasi fakultas. |
| **Kepala Laboratorium** | &larr; Keluar | Dashboard laboratorium | `3.0` (Output) | Tampilan ringkasan statistik khusus lab binaannya. |
| **Kepala Laboratorium** | &larr; Keluar | Laporan kepatuhan laboratorium | `3.0` (Output) | Dokumen kepatuhan spesifik lingkup laboratorium. |
| **Kepala Laboratorium** | &larr; Keluar | Laporan menunggu persetujuan | `3.0` (Output) | Daftar draf laporan yang memerlukan verifikasi. |
| **Kepala Laboratorium** | &larr; Keluar | Paket agent scanner | `3.0` (Output) | Berkas ZIP scanner untuk komputer laboratorium. |
| **Kepala Laboratorium** | &larr; Keluar | Informasi lisensi fakultas | `3.0` (Output) | Daftar lisensi software yang dialokasikan ke fakultas. |
| **Kepala Laboratorium** | &larr; Keluar | Kunci lisensi | `3.0` (Output) | Nilai kunci lisensi terdekripsi (teraudit & auto-hide). |
| **Pimpinan** | Masuk &rarr; | Permintaan laporan eksekutif | `3.0` (Output) | Permintaan akses informasi tingkat pimpinan. |
| **Pimpinan** | &larr; Keluar | Dashboard eksekutif | `3.0` (Output) | Tampilan ringkasan metrik kepatuhan universitas. |
| **Pimpinan** | &larr; Keluar | Matriks kepatuhan lintas fakultas | `3.0` (Output) | Matriks perbandingan kepatuhan antar fakultas. |
| **Pimpinan** | &larr; Keluar | Laporan kebutuhan lisensi | `3.0` (Output) | Analisis kebutuhan pengadaan lisensi strategis. |
| **Pimpinan** | &larr; Keluar | Laporan status lisensi | `3.0` (Output) | Dokumen kepemilikan dan utilisasi kuota lisensi. |

---

### 4.2. Alur Data Internal (Proses &harr; Data Store)

| Sumber | Target | Label Alur Data | Penjelasan |
|--------|--------|-----------------|------------|
| `1.0` | `D1` | Data pengguna tersimpan | Menyimpan data akun dan peran baru/pembaruan. |
| `1.0` | `D2` | Data organisasi tersimpan | Menyimpan data unit fakultas dan laboratorium. |
| `1.0` | `D3` | Data lisensi & katalog tersimpan | Menyimpan data lisensi institusi dan master software. |
| `1.0` | `D4` | Data komputer & hasil scan tersimpan | Menyimpan identitas komputer dan sesi scan software. |
| `1.0` | `D7` | Catatan aktivitas masukan data | Mencatat audit mutasi data master dan registrasi aset. |
| `D3` | `2.0` | Data lisensi & status katalog | Membaca kuota lisensi aktif, masa berlaku, dan aturan status. |
| `D4` | `2.0` | Data komputer & software terinstal | Membaca daftar software aktual yang terpasang pada komputer. |
| `2.0` | `D5` | Data status kepatuhan terhitung | Menyimpan hasil evaluasi kepatuhan per komputer/software. |
| `2.0` | `D6` | Data persetujuan laporan tersimpan | Menyimpan riwayat pengajuan dan keputusan persetujuan laporan. |
| `2.0` | `D7` | Catatan aktivitas evaluasi & verifikasi | Mencatat audit proses evaluasi dan keputusan kepala lab. |
| `D1` | `3.0` | Data pengguna | Bahan statistik akun dan penanggung jawab unit. |
| `D2` | `3.0` | Data organisasi | Struktur hierarki fakultas/lab untuk penyusunan laporan. |
| `D3` | `3.0` | Data lisensi & kuota | Data kuota dan kepemilikan lisensi untuk laporan lisensi. |
| `D4` | `3.0` | Data inventaris komputer | Rekap aset perangkat keras dan sesi pemindaian. |
| `D5` | `3.0` | Data hasil kepatuhan | Dasar pembuatan dashboard kepatuhan dan laporan kepatuhan. |
| `D6` | `3.0` | Data status verifikasi lab | Penyaring data laporan pimpinan (hanya lab yang telah disetujui). |
| `D7` | `3.0` | Data log aktivitas audit | Sumber rekapitulasi laporan log aktivitas sistem. |

---

## 5. Kamus Data Ringkas (*Data Dictionary*)

1. **Data Pengguna:**  
   `Data Pengguna = nama + email + kata_sandi + peran + (laboratorium_id) + (fakultas_id)`

2. **Data Lisensi Perangkat Lunak:**  
   `Data Lisensi Perangkat Lunak = katalog_id + kunci_lisensi_terenkripsi + nomor_pembelian + kuota_limit + tanggal_pembelian + tanggal_kedaluwarsa + harga_satuan + (bukti_pembelian)`

3. **Data Alokasi Lisensi:**  
   `Data Alokasi Lisensi = lisensi_id + fakultas_id + kuota_alokasi + tanggal_alokasi + tanggal_mulai + tanggal_selesai + status`

4. **Data Registrasi Komputer:**  
   `Data Registrasi Komputer = alamat_mac + hostname + nomor_seri + laboratorium_id + kunci_registrasi_agent`

5. **Data Hasil Pemindaian:**  
   `Data Hasil Pemindaian = uuid_scan + versi_agent + mode_scan + spesifikasi_hardware + sistem_operasi + 1{ software_terinstal }n`  
   - `spesifikasi_hardware = prosesor + ram_gb + disk_total_gb + disk_free_gb + pabrikan + model`  
   - `sistem_operasi = nama_os + versi_os + arsitektur_os + status_lisensi_os`  
   - `software_terinstal = nama_software + versi + vendor + tanggal_instalasi`

6. **Data Status Kepatuhan Terhitung:**  
   `Data Status Kepatuhan = komputer_id + software_catalog_id + status_kepatuhan + keterangan + (lisensi_id) + tanggal_deteksi + tanggal_scan`  
   - `status_kepatuhan = [ "Berlisensi" | "Tidak Berlisensi" | "Grace Period" ]`

7. **Data Persetujuan Laporan:**  
   `Data Persetujuan Laporan = laboratorium_id + periode_tahun_bulan + status_verifikasi + (catatan_peninjauan) + peninjau_id + tanggal_tinjauan`  
   - `status_verifikasi = [ "pending" | "approved" | "rejected" ]`

---

## 6. Verifikasi Keseimbangan Hierarki (*Balancing Verification*)

Berdasarkan kaidah analisis terstruktur, DFD Level 1 wajib mempertahankan keseimbangan alur data masuk dan keluar secara sempurna terhadap DFD Level 0 (Diagram Konteks). 

Tabel berikut membuktikan korespondensi 1-ke-1 antara alur data pada Diagram Konteks dan sub-proses DFD Level 1 (3 Proses):

| Entitas Luar | Alur Data pada DFD Level 0 | Sub-Proses DFD Level 1 (3 Proses) | Keterangan Balancing |
|--------------|----------------------------|-----------------------------------|----------------------|
| **Administrator** | Data pengguna (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Data fakultas (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Data laboratorium (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Data lisensi perangkat lunak (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Data alokasi lisensi (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Data katalog perangkat lunak (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Data komputer (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Permintaan pemindaian (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Administrator** | Pengajuan laporan kepatuhan (In) | `2.0` Pemrosesan dan Evaluasi Kepatuhan | Seimbang |
| **Administrator** | Dashboard sistem (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Laporan kepatuhan lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Laporan inventaris komputer (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Laporan inventaris perangkat lunak (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Laporan perubahan perangkat lunak (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Laporan kebutuhan lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Laporan monitoring pemindaian (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Log aktivitas (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Administrator** | Kunci lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Pimpinan** | Permintaan laporan eksekutif (In) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Pimpinan** | Dashboard eksekutif (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Pimpinan** | Matriks kepatuhan lintas fakultas (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Pimpinan** | Laporan kebutuhan lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Pimpinan** | Laporan status lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Status persetujuan laporan (In) | `2.0` Pemrosesan dan Evaluasi Kepatuhan | Seimbang |
| **Kepala Lab** | Catatan peninjauan (In) | `2.0` Pemrosesan dan Evaluasi Kepatuhan | Seimbang |
| **Kepala Lab** | Permintaan kunci lisensi (In) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Laporan menunggu persetujuan (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Dashboard laboratorium (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Laporan kepatuhan laboratorium (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Paket agent scanner (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Informasi lisensi fakultas (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Kepala Lab** | Kunci lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Data registrasi komputer (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Staf Lab** | Data hasil pemindaian (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Staf Lab** | Polling perintah pemindaian (In) | `1.0` Pengelolaan Data Masukan | Seimbang |
| **Staf Lab** | Permintaan kunci lisensi (In) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Paket agent scanner (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Token autentikasi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Perintah pemindaian (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Inventaris komputer laboratorium (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Informasi lisensi fakultas (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |
| **Staf Lab** | Kunci lisensi (Out) | `3.0` Penyajian Informasi dan Laporan | Seimbang |

*Hasil Verifikasi:* **Tidak terdapat kebocoran alur (*black hole*) maupun masukan/keluaran tanpa sumber (*miracle process*)**. Keseimbangan alur data antara DFD Level 0 dan DFD Level 1 terverifikasi 100% konsisten.

---

## 7. Kesimpulan

Penyusunan Data Flow Diagram (DFD) Level 1 dengan tiga (3) proses utama (*Input – Proses – Output*) berhasil menyederhanakan kompleksitas visual sistem tanpa mereduksi fungsionalitas riil dari Sistem Informasi Manifest USN. 

Keunggulan dari rancangan 3 proses ini meliputi:
1. **Struktur yang Sangat Rapi & Mudah Dipahami:** Alur logika sistem mengalir secara alami dari kiri ke kanan (Masukan &rarr; Pemrosesan &rarr; Keluaran), memudahkan penguji dan dosen pembimbing skripsi dalam menelaah arsitektur sistem informasi.
2. **Kesesuaian Standar Akademik:** Menggunakan format monokrom hitam-putih formal, simbol standar Gane-Sarson / Yourdon-DeMarco, dan seluruh alur data konsisten menggunakan frasa kata benda.
3. **Penyimpanan Data Terpusat:** Tujuh (7) data store logis terpetakan dengan tegas sesuai dengan peran masing-masing tahapan pemrosesan data.

---

## Daftar Pustaka

- Kendall, K. E., & Kendall, J. E. (2019). *Systems Analysis and Design* (10th ed.). Pearson.
- Pressman, R. S., & Maxim, B. R. (2020). *Software Engineering: A Practitioner's Approach* (9th ed.). McGraw-Hill.
- Whitten, J. L., & Bentley, L. D. (2007). *Systems Analysis and Design Methods* (7th ed.). McGraw-Hill.
- Yourdon, E. (1989). *Modern Structured Analysis*. Prentice Hall.

---
*Dokumen ini disusun sebagai bagian dari dokumentasi perancangan sistem akademik pada Sistem Informasi Manifest USN Kolaka, Oktober 2026.*
