# Revisi DFD Level 0 - Level 2 & Flow Map (Versi 3 — Sesuai Bimbingan Dosen)

## Sistem Informasi Manifest Lisensi Perangkat Lunak untuk Mencegah Pelanggaran Hak Cipta di Lingkungan USN Kolaka

Dokumen ini merupakan hasil perbaikan diagram perancangan sistem (*Data Flow Diagram* / DFD dan *Flow Map*) berdasarkan arahan bimbingan dosen:
1. **Entitas Eksternal:** Ditetapkan secara konsisten menjadi **Dekan** (pihak eksekutif penerima laporan akhir / *read-only*), **Admin / Staff Lab** (pengelola dan operator sistem), dan **Penanggung Jawab Lab (PJ Lab)** (verifikator inventaris lab).
2. **Kaidah Penamaan Arus Data (*Data Flow*):** Seluruh arus data menggunakan **kata benda murni** (menghilangkan kata kerja seperti *deploy*, *simpan*, *inisiasi*, *pilih*, *berikan* yang merupakan ranah proses).
3. **Spesifikasi Data Scan:** Mengadopsi penamaan bertingkat konsisten:
   - `Data Scan Komputer (Data Komputer/OS)`
   - `Data Scan Komputer (Data Software Terdeteksi)`
   - `Data Scan Komputer (Data Lisensi)`
4. **Kaidah Penamaan Data Store:** Menggunakan format terpadu basis data dan tabel: `Database Manifest (Tabel NamaTabel)`.
5. **Keseimbangan Arus Data (*Balancing*):** Setiap arus data masuk dan keluar pada Diagram Konteks memiliki korespondensi yang identik di DFD Level 1.

---

### Keputusan Desain

| Aspek | Keputusan Revisi | Keterangan |
|---|---|---|
| Jumlah Entitas | 3 Entitas | Admin / Staff Lab, Penanggung Jawab Lab (PJ Lab), Dekan |
| Entitas Eksekutif | **Dekan** | Penerima laporan akhir tingkat fakultas (*read-only*) |
| Agen Scanner | Bukan entitas | Merupakan *tools* / instrumen yang dioperasikan oleh Admin / Staff Lab |
| Jumlah Proses Level 1 | 3 Proses Utama | 1.0 Input Data, 2.0 Pengolahan Data, 3.0 Pelaporan & Verifikasi |
| Standar Data Store | `Database Manifest (Tabel ...)` | D1: Tabel Komputer, D2: Tabel Software, D3: Tabel Lisensi, D4: Tabel Kepatuhan, D5: Tabel Persetujuan Laporan |

### Alur Pelaporan Berjenjang

```
Admin / Staff Lab   →  Pemindaian komputer, input master data, pengiriman draf laporan lab
        ↓
PJ Lab              →  Verifikasi data inventaris & temuan, persetujuan (approve/reject) laporan lab
        ↓ (setelah approved)
Dekan               →  Penerimaan & penelaahan laporan final kepatuhan (read-only)
```

---

## 1. Diagram Konteks (DFD Level 0)

### 1.1 Diagram Konteks (Mermaid)

```mermaid
graph TD
    Staff((Admin / Staff Lab))
    PJLab((Penanggung Jawab Lab))
    Dekan((Dekan))

    Sistem[0. Sistem Informasi Manifest Lisensi Perangkat Lunak]

    %% Aliran Masuk dari Staff
    Staff -- "Data Scan Komputer (Data Komputer/OS), Data Scan Komputer (Data Software Terdeteksi), Data Scan Komputer (Data Lisensi), Data Katalog Software, Data Aturan Whitelist/Blacklist, Data Pengajuan Laporan Lab" --> Sistem

    %% Aliran Keluar ke Staff
    Sistem -- "Informasi Status Scan & Token, Informasi Aset Komputer & Software, Informasi Log Aktivitas, Informasi Kesiapan Scan Lab" --> Staff

    %% Aliran Masuk dari PJ Lab
    PJLab -- "Data Verifikasi Inventaris, Data Keputusan Persetujuan Laporan, Data Catatan Review" --> Sistem

    %% Aliran Keluar ke PJ Lab
    Sistem -- "Daftar Inventaris Lab, Draf Laporan Kepatuhan Lab, Notifikasi Temuan Pelanggaran" --> PJLab

    %% Aliran Masuk dari Dekan
    Dekan -- "Parameter Filter Laporan" --> Sistem

    %% Aliran Keluar ke Dekan
    Sistem -- "Informasi Dashboard Kepatuhan, Dokumen Laporan Final (PDF/Excel)" --> Dekan
```
**Gambar 3.3 Diagram Konteks**

### 1.2 Penjelasan Entitas Eksternal

#### 1. Admin / Staff Lab
Aktor operasional yang mengelola seluruh konfigurasi master data, mengoperasikan instrumen pemindaian (*scanner*) pada komputer klien laboratorium, dan mengajukan draf laporan kepatuhan laboratorium.
- **Arus Data Masuk (ke Sistem):**
  - *Data Scan Komputer (Data Komputer/OS)*: Spesifikasi perangkat keras dan sistem operasi hasil pemindaian.
  - *Data Scan Komputer (Data Software Terdeteksi)*: Daftar instalasi perangkat lunak hasil pemindaian.
  - *Data Scan Komputer (Data Lisensi)*: Informasi lisensi/OEM yang terbaca saat pemindaian.
  - *Data Katalog Software*: Master entri perangkat lunak komersial, freeware, maupun open-source.
  - *Data Aturan Whitelist/Blacklist*: Konfigurasi kata kunci perangkat lunak terlarang dan diizinkan.
  - *Data Pengajuan Laporan Lab*: Berkas pengajuan draf laporan per lab per periode ke PJ Lab.
- **Arus Data Keluar (dari Sistem):**
  - *Informasi Status Scan & Token*: Bukti token API Sanctum dan respons status pemindaian.
  - *Informasi Aset Komputer & Software*: Data rekapitulasi spesifikasi komputer dan software terdeteksi.
  - *Informasi Log Aktivitas*: Catatan rekam jejak audit perubahan sistem.
  - *Informasi Kesiapan Scan Lab*: Rekapitulasi persentase komputer yang telah selesai dipindai per lab.

#### 2. Penanggung Jawab Lab (PJ Lab)
Aktor pengawas tingkat laboratorium (Koordinator Lab/Prodi) yang bertugas mengevaluasi kesesuaian fisik inventaris dan temuan audit perangkat lunak.
- **Arus Data Masuk (ke Sistem):**
  - *Data Verifikasi Inventaris*: Koreksi atau penyesuaian validasi aset inventaris lab.
  - *Data Keputusan Persetujuan Laporan*: Status persetujuan resmi (*Approve* atau *Reject*).
  - *Data Catatan Review*: Catatan perbaikan dan rekomendasi tindak lanjut hasil audit.
- **Arus Data Keluar (dari Sistem):**
  - *Daftar Inventaris Lab*: Rincian perangkat dan aplikasi yang ada pada laboratorium bersangkutan.
  - *Draf Laporan Kepatuhan Lab*: Berkas laporan komprehensif berstatus *pending* untuk ditinjau.
  - *Notifikasi Temuan Pelanggaran*: Pemberitahuan perangkat lunak ilegal (*blacklist*) atau ketidaksesuaian lisensi.

#### 3. Dekan
Pimpinan tingkat fakultas (FTI USN Kolaka) yang menerima laporan eksekutif kepatuhan hak cipta secara *read-only*.
- **Arus Data Masuk (ke Sistem):**
  - *Parameter Filter Laporan*: Kriteria penyaringan periode waktu dan unit laboratorium.
- **Arus Data Keluar (dari Sistem):**
  - *Informasi Dashboard Kepatuhan*: Tampilan metrik persentase kepatuhan lisensi dan statistik pelanggaran.
  - *Dokumen Laporan Final (PDF/Excel)*: Dokumen resmi laporan kepatuhan yang telah disahkan oleh PJ Lab.

---

## 2. Data Flow Diagram Level 1

### 2.1 Diagram Level 1 (Mermaid)

```mermaid
graph TD
    %% Entitas Eksternal
    Staff((Admin / Staff Lab))
    PJLab((Penanggung Jawab Lab))
    Dekan((Dekan))

    %% Data Stores
    D1[("D1: Database Manifest<br>(Tabel Komputer)")]
    D2[("D2: Database Manifest<br>(Tabel Software)")]
    D3[("D3: Database Manifest<br>(Tabel Lisensi)")]
    D4[("D4: Database Manifest<br>(Tabel Kepatuhan)")]
    D5[("D5: Database Manifest<br>(Tabel Persetujuan Laporan)")]

    %% Proses Utama
    P1(1.0 Proses Input Data)
    P2(2.0 Proses Pengolahan Data)
    P3(3.0 Proses Pelaporan & Verifikasi)

    %% === PROSES 1.0: INPUT DATA ===
    Staff -->|"Data Scan Komputer (Data Komputer/OS),<br>Data Scan Komputer (Data Software Terdeteksi),<br>Data Scan Komputer (Data Lisensi),<br>Data Katalog Software, Data Aturan Whitelist/Blacklist"| P1
    P1 -->|"Informasi Status Scan & Token,<br>Informasi Aset Komputer & Software,<br>Informasi Kesiapan Scan Lab"| Staff

    P1 -->|"Data Identitas & Spesifikasi Perangkat"| D1
    P1 -->|"Data Software Terdeteksi & Master Katalog"| D2
    P1 -->|"Data Inventaris Lisensi Terenkripsi"| D3

    %% Aliran Antar Proses: Job Antrean
    P1 -->|"Data Antrean Pemrosesan Analisis"| P2

    %% === PROSES 2.0: PENGOLAHAN DATA ===
    D1 <-->|"Data Spesifikasi & Status Perangkat"| P2
    D2 <-->|"Data Software Mentah & Data Aturan Katalog"| P2
    D3 -->|"Data Alokasi & Kuota Lisensi"| P2
    P2 -->|"Data Hasil Evaluasi Kepatuhan"| D4

    %% === PROSES 3.0: PELAPORAN & VERIFIKASI ===
    Staff -->|"Data Pengajuan Laporan Lab"| P3
    P3 -->|"Informasi Log Aktivitas"| Staff

    D1 -->|"Data Inventaris Laboratorium"| P3
    D4 -->|"Data Hasil Evaluasi Kepatuhan Lab"| P3
    D5 <-->|"Data Status Persetujuan & Riwayat Review"| P3

    PJLab -->|"Data Verifikasi Inventaris,<br>Data Keputusan Persetujuan Laporan,<br>Data Catatan Review"| P3
    P3 -->|"Daftar Inventaris Lab,<br>Draf Laporan Kepatuhan Lab,<br>Notifikasi Temuan Pelanggaran"| PJLab

    Dekan -->|"Parameter Filter Laporan"| P3
    P3 -->|"Informasi Dashboard Kepatuhan,<br>Dokumen Laporan Final (PDF/Excel)"| Dekan
```
**Gambar 3.4 Data Flow Diagram Level 1**

### 2.2 Penjelasan Proses DFD Level 1

#### 1.0 Proses Input Data (Penerimaan & Registrasi)
- **Fungsi:** Mengelola registrasi komputer lab, menampung arus hasil pemindaian scanner, serta menginput master katalog perangkat lunak dan inventaris lisensi.
- **Data Masuk:** *Data Scan Komputer (Data Komputer/OS)*, *Data Scan Komputer (Data Software Terdeteksi)*, *Data Scan Komputer (Data Lisensi)*, *Data Katalog Software*, *Data Aturan Whitelist/Blacklist*.
- **Data Keluar:** *Informasi Status Scan & Token*, *Informasi Aset Komputer & Software*, *Informasi Kesiapan Scan Lab*, serta data terstruktur ke data store D1, D2, D3. Memicu antrean background job (*Data Antrean Pemrosesan Analisis*) ke proses 2.0.
- **Data Store Terkait:** D1 (Tabel Komputer), D2 (Tabel Software), D3 (Tabel Lisensi).

#### 2.0 Proses Pengolahan Data (Analisis & Evaluasi Otomatis)
- **Fungsi:** Menjalankan normalisasi nama software, penyaringan komponen sistem (*junk/driver*), penandaan software terlarang (*blacklist*), pencocokan alokasi lisensi, serta kalkulasi status kepatuhan secara otomatis.
- **Data Masuk:** *Data Antrean Pemrosesan Analisis* (dari P1.0), *Data Software Mentah* (D2), *Data Alokasi Lisensi* (D3), *Data Spesifikasi Komputer* (D1).
- **Data Keluar:** *Data Hasil Evaluasi Kepatuhan* yang disimpan ke D4.
- **Data Store Terkait:** D1, D2, D3, D4 (Tabel Kepatuhan).

#### 3.0 Proses Pelaporan & Verifikasi (Pengesahan Berjenjang & Penyajian)
- **Fungsi:** Mengelola pengajuan draf laporan oleh Admin, memfasilitasi verifikasi dan persetujuan oleh PJ Lab, menyaring data kepatuhan yang berstatus *approved*, dan menyajikan dokumen laporan final kepada Dekan.
- **Data Masuk:** *Data Pengajuan Laporan Lab* (dari Staff), *Data Keputusan Persetujuan Laporan* dan *Data Catatan Review* (dari PJ Lab), *Parameter Filter Laporan* (dari Dekan), serta data dari D1, D4, D5.
- **Data Keluar:** *Draf Laporan Kepatuhan Lab* dan *Notifikasi Temuan* ke PJ Lab, *Dokumen Laporan Final (PDF/Excel)* dan *Dashboard Kepatuhan* ke Dekan, *Informasi Log Aktivitas* ke Staff.
- **Data Store Terkait:** D1, D4, D5 (Tabel Persetujuan Laporan).

### 2.3 Daftar Data Store Terpadu

| Kode | Penamaan Notasi Data Store | Deskripsi Isi Penyimpanan | Tabel Terkait dalam Database |
|---|---|---|---|
| **D1** | Database Manifest (Tabel Komputer) | Identitas fisik/jaringan (Hostname, MAC, IP), spesifikasi hardware, OS, lokasi lab, dan status kesiapan scan. | `computers` |
| **D2** | Database Manifest (Tabel Software) | Master katalog software (whitelist, blacklist, kategori komersial/freeware), serta tabel temuan software terinstal per komputer. | `software_catalogs`, `software_discoveries` |
| **D3** | Database Manifest (Tabel Lisensi) | Inventaris lisensi terenkripsi, batas kuota aktivasi, masa berlaku, dan dokumen bukti perolehan lisensi. | `license_inventories` |
| **D4** | Database Manifest (Tabel Kepatuhan) | Rekapitulasi hasil analisis kepatuhan per komputer dan software (status: compliant, non-compliant, expired, unverified). | `compliance_reports` |
| **D5** | Database Manifest (Tabel Persetujuan Laporan) | Status verifikasi pengajuan laporan lab (pending, approved, rejected), catatan perbaikan, serta jejak audit persetujuan PJ Lab. | `report_approvals` |

---

## 3. Data Flow Diagram Level 2

### 3.1 DFD Level 2 — Rincian Proses 1.0 (Input Data)

```mermaid
graph TD
    Staff((Admin / Staff Lab))

    D1[("D1: Database Manifest<br>(Tabel Komputer)")]
    D2[("D2: Database Manifest<br>(Tabel Software)")]
    D3[("D3: Database Manifest<br>(Tabel Lisensi)")]

    P1_1(1.1 Registrasi Perangkat Komputer)
    P1_2(1.2 Penerimaan Permintaan Scan)
    P1_3(1.3 Penerimaan Hasil Scan Komputer)
    P1_4(1.4 Pengelolaan Master Data Lisensi & Katalog)

    %% 1.1 Registrasi
    Staff -->|"Data Identitas Registrasi Komputer"| P1_1
    P1_1 -->|"Data Identitas Perangkat (Hostname, MAC, Lab)"| D1
    P1_1 -->|"Informasi Token API Sanctum"| Staff

    %% 1.2 Inisiasi Scan
    Staff -->|"Data Instruksi Scan Komputer"| P1_2
    P1_2 -->|"Data Status Permintaan Scan (Flag scan_requested)"| D1
    P1_2 -->|"Informasi Status Antrean Scan"| Staff

    %% 1.3 Penerimaan Hasil
    D1 -->|"Data Antrean Instruksi Scan Komputer"| P1_3
    P1_3 -->|"Data Scan Komputer (Data Komputer/OS)"| D1
    P1_3 -->|"Data Scan Komputer (Data Software Terdeteksi)"| D2
    P1_3 -->|"Informasi Rekapitulasi Hasil Scan"| Staff

    %% 1.4 Master Data
    Staff -->|"Data Scan Komputer (Data Lisensi),<br>Data Katalog Software, Data Aturan Whitelist/Blacklist"| P1_4
    P1_4 -->|"Data Master Katalog & Aturan Filter"| D2
    P1_4 -->|"Data Inventaris Lisensi Terenkripsi"| D3
```
**Gambar 3.5 Data Flow Diagram Level 2 Proses 1 (Input Data)**

---

### 3.2 DFD Level 2 — Rincian Proses 2.0 (Pengolahan Data)

```mermaid
graph TD
    D1[("D1: Database Manifest<br>(Tabel Komputer)")]
    D2[("D2: Database Manifest<br>(Tabel Software)")]
    D3[("D3: Database Manifest<br>(Tabel Lisensi)")]
    D4[("D4: Database Manifest<br>(Tabel Kepatuhan)")]

    P2_1(2.1 Normalisasi & Penyaringan Software)
    P2_2(2.2 Sinkronisasi Katalog Software)
    P2_3(2.3 Validasi Alokasi Lisensi)
    P2_4(2.4 Evaluasi Aturan Kepatuhan)

    %% 2.1 Filter
    D2 -->|"Data Software Mentah Hasil Pemindaian"| P2_1
    P2_1 -->|"Data Software Hasil Normalisasi (Bersih & Flagged)"| P2_2

    %% 2.2 Sinkronisasi
    P2_2 <-->|"Data Aturan Master Whitelist/Blacklist"| D2
    P2_2 -->|"Data Software Terkategorisasi"| P2_3

    %% 2.3 Lisensi
    D3 -->|"Data Inventaris Lisensi (Kuota & Masa Berlaku)"| P2_3
    P2_3 -->|"Data Pemetaan Lisensi & Software Komersial"| P2_4

    %% 2.4 Evaluasi
    D1 -->|"Data Asosiasi Perangkat Komputer"| P2_4
    D2 -->|"Data Definisi Software Blacklist"| P2_4
    P2_4 -->|"Data Hasil Evaluasi Kepatuhan Lisensi"| D4
```
**Gambar 3.6 Data Flow Diagram Level 2 Proses 2 (Pengolahan Data)**

---

### 3.3 DFD Level 2 — Rincian Proses 3.0 (Pelaporan & Verifikasi)

```mermaid
graph TD
    Staff((Admin / Staff Lab))
    PJLab((Penanggung Jawab Lab))
    Dekan((Dekan))

    D1[("D1: Database Manifest<br>(Tabel Komputer)")]
    D4[("D4: Database Manifest<br>(Tabel Kepatuhan)")]
    D5[("D5: Database Manifest<br>(Tabel Persetujuan Laporan)")]

    P3_1(3.1 Pengajuan Draf Laporan Lab)
    P3_2(3.2 Verifikasi & Persetujuan Laporan Lab)
    P3_3(3.3 Penyaringan Data Kepatuhan Terverifikasi)
    P3_4(3.4 Penyajian Dashboard & Laporan Final)

    %% 3.1 Pengajuan
    Staff -->|"Data Pengajuan Laporan Lab"| P3_1
    D1 -->|"Data Status Kesiapan Scan Lab"| P3_1
    D4 -->|"Data Rekapitulasi Kepatuhan Lab"| P3_1
    P3_1 -->|"Data Record Pengajuan Laporan (Status Pending)"| D5
    P3_1 -->|"Informasi Konfirmasi Pengajuan Laporan"| Staff

    %% 3.2 Verifikasi PJ Lab
    D5 -->|"Data Pengajuan Laporan Berstatus Pending"| P3_2
    D1 -->|"Data Rincian Inventaris Lab"| P3_2
    P3_2 -->|"Daftar Inventaris Lab, Draf Laporan Kepatuhan Lab,<br>Notifikasi Temuan Pelanggaran"| PJLab
    PJLab -->|"Data Verifikasi Inventaris, Data Keputusan Persetujuan Laporan,<br>Data Catatan Review"| P3_2
    P3_2 -->|"Data Riwayat Keputusan Persetujuan (Approved/Rejected)"| D5

    %% 3.3 Filter
    D4 -->|"Data Rincian Kepatuhan Perangkat"| P3_3
    D5 -->|"Data Status Pengesahan Laporan Lab"| P3_3
    P3_3 -->|"Data Kepatuhan Laboratorium Terverifikasi (Approved)"| P3_4

    %% 3.4 Penyajian & Ekspor
    D1 -->|"Data Statistik Total Komputer Lab"| P3_4
    Dekan -->|"Parameter Filter Laporan"| P3_4
    P3_4 -->|"Informasi Dashboard Kepatuhan,<br>Dokumen Laporan Final (PDF/Excel)"| Dekan
    P3_4 -->|"Informasi Monitoring Log & Rekapitulasi Aset"| Staff
```
**Gambar 3.7 Data Flow Diagram Level 2 Proses 3 (Pelaporan & Verifikasi)**

---

## 4. Flow Map (Bagan Alir Sistem)

Flow Map berikut menggambarkan pemisahan fungsi dan alur dokumen fisik/digital berjenjang antar pemangku kepentingan.

### 4.1 Flow Map (Mermaid)

```mermaid
flowchart TD
    subgraph ADM["Admin / Staff Lab"]
        A1([Mulai]) --> A2["Pemasangan Script Scanner<br>pada Komputer Lab"]
        A3["Inisiasi Permintaan Scan<br>(Manual / Massal)"]
        A4{"Status Kesiapan<br>Scan Lab Lengkap?"}
        A5["Pengajuan Draf Laporan<br>Kepatuhan ke PJ Lab"]
    end

    subgraph SIS["Sistem (Server / Aplikasi)"]
        S1["Registrasi & Penyimpanan<br>Identitas Perangkat"]
        DB1[("D1: Database Manifest<br>(Tabel Komputer)")]
        S2["Penerimaan & Penyimpanan<br>Data Scan Komputer"]
        DB2[("D2: Database Manifest<br>(Tabel Software)")]
        S3["Normalisasi & Analisis Kepatuhan<br>(Background Job)"]
        DB3[("D3: Database Manifest<br>(Tabel Lisensi)")]
        DB4[("D4: Database Manifest<br>(Tabel Kepatuhan)")]
        S4["Pembuatan Record Pengajuan<br>(Status Pending)"]
        DB5[("D5: Database Manifest<br>(Tabel Persetujuan Laporan)")]
        S5["Penyaringan Laporan<br>Hanya Status Approved"]
        S6["Penyusunan Dokumen Laporan<br>Final (PDF/Excel)"]
    end

    subgraph PJ["Penanggung Jawab Lab (PJ Lab)"]
        P1["Penerimaan Draf Laporan &<br>Notifikasi Pengajuan"]
        P2["Pemeriksaan Inventaris Lab &<br>Pratinjau Dokumen PDF"]
        P3{"Keputusan<br>Persetujuan?"}
        P4["Penetapan Status Approved"]
        P5["Penetapan Status Rejected +<br>Catatan Perbaikan"]
    end

    subgraph DKN["Dekan (Fakultas Teknik)"]
        D_M1["Penelaahan Informasi<br>Dashboard Kepatuhan"]
        D_M2["Pengunduhan Dokumen Laporan<br>Final (PDF/Excel)"]
        D_End([Selesai])
    end

    %% Alur Relasional
    A2 -->|"Data Identitas Komputer"| S1
    S1 --> DB1
    S1 -->|"Token API Sanctum"| A3
    A3 -->|"Instruksi Scan"| S2
    S2 --> DB2
    S2 --> S3
    S3 <--> DB3
    S3 --> DB4

    DB4 -->|"Data Kesiapan Scan"| A4
    A4 -->|"Belum Lengkap"| A3
    A4 -->|"Lengkap"| A5

    A5 -->|"Berkas Pengajuan Laporan"| S4
    S4 --> DB5
    DB5 -->|"Draf Laporan Kepatuhan"| P1

    P1 --> P2
    P2 --> P3
    P3 -->|"Tolak (Revisi)"| P5
    P5 -->|"Catatan Perbaikan"| A4
    P3 -->|"Setuju"| P4
    P4 -->|"Data Status Approved"| DB5

    DB5 -->|"Data Approved"| S5
    S5 --> S6
    S6 -->|"Dokumen Laporan Final"| D_M1
    D_M1 --> D_M2
    D_M2 --> D_End
```

---

## 5. Ringkasan Pemetaan Revisi Berdasarkan Masukan Pembimbing

| Poin Masukan Dosen | Penerapan pada Diagram Revisi |
|---|---|
| **Penetapan Entitas Dekan** | Seluruh notasi entitas eksekutif diseragamkan menjadi **Dekan** (menggantikan istilah ambigu "Pimpinan (Dekan / Kaprodi)"). |
| **Kaidah Kata Benda pada Arus Data** | Mengeliminasi semua kata kerja pada label garis. Diganti dengan: *Data Scan Komputer (...)*, *Informasi Status Scan*, *Data Pengajuan Laporan*, *Data Verifikasi Inventaris*, *Parameter Filter Laporan*, *Dokumen Laporan Final*. |
| **Spesifikasi Data Scan Bertingkat** | Diimplementasikan secara berjenjang: *Data Scan Komputer (Data Komputer/OS)*, *Data Scan Komputer (Data Software Terdeteksi)*, dan *Data Scan Komputer (Data Lisensi)*. |
| **Format Data Store Terpadu** | Menggunakan standar nama: `Database Manifest (Tabel Komputer)`, `Database Manifest (Tabel Software)`, `Database Manifest (Tabel Lisensi)`, `Database Manifest (Tabel Kepatuhan)`, `Database Manifest (Tabel Persetujuan Laporan)`. |
| **Balancing Antar-Level** | Semua input dan output di Diagram Konteks memiliki representasi yang identik di DFD Level 1 dan Level 2. |
