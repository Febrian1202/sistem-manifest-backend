# Rencana Revisi Sistem Informasi Manifest Lisensi Software

## Scope Revisi Penelitian

**Judul dipertahankan:**

> **SISTEM INFORMASI MANIFEST LISENSI SOFTWARE UNTUK MENCEGAH PELANGGARAN HAK CIPTA DI LINGKUNGAN USN KOLAKA**

Dokumen ini menjadi acuan perubahan sistem setelah arahan dosen penguji: objek penelitian diperluas dari satu laboratorium menjadi fasilitas laboratorium komputer di lingkungan USN Kolaka, dan pemantauan harus dilakukan secara berkala selama periode penelitian (sekitar 2–3 bulan).

---

## 1. Perubahan Utama yang Harus Dicapai

### Kondisi lama

```text
1 laboratorium
    ↓
1 kali pendataan / current state
    ↓
daftar software saat ini
    ↓
status lisensi saat ini
```

### Kondisi target

```text
Beberapa laboratorium USN Kolaka
    ↓
Banyak komputer
    ↓
Agent Windows
    ↓
Scan berkala
    ↓
Setiap scan disimpan sebagai histori
    ↓
Manifest software + status lisensi
    ↓
Perubahan software dari waktu ke waktu
    ↓
Laporan per lab + tingkat universitas
```

### Empat sasaran revisi

1. **Multi-laboratorium**: sistem mampu mengelola banyak laboratorium dari satu aplikasi.
2. **Periodic monitoring**: setiap pelaksanaan scan menjadi observasi yang tercatat.
3. **Historical evidence**: hasil scan dan status compliance tidak ditimpa sehingga histori 2–3 bulan tetap tersedia.
4. **Scoped access**: pengguna laboratorium hanya melihat data sesuai laboratorium yang menjadi tanggung jawabnya, sedangkan admin/pimpinan dapat melihat cakupan yang lebih luas sesuai perannya.

---

# 2. Status Fondasi Sistem Saat Ini

Fondasi berikut sudah tersedia dan **tidak perlu dibuang**:

| Komponen | Kondisi | Keputusan |
|---|---|---|
| `laboratories` | Sudah ada | Pertahankan |
| `computers.laboratory_id` | Sudah ada | Pertahankan |
| `users.laboratory_id` | Sudah ada | Pertahankan |
| Role `admin` | Sudah ada | Pertahankan |
| Role `kepala_lab` | Sudah ada | Pertahankan |
| Role `pimpinan` | Sudah ada | Pertahankan |
| Agent PowerShell | Sudah ada | Pertahankan, tambah metadata scan |
| Windows Registry scan | Sudah ada | Pertahankan |
| Windows Store/Appx scan | Sudah ada | Pertahankan |
| Scheduled Task harian | Sudah ada | Pertahankan |
| Polling agent 15 menit | Sudah ada | Pertahankan |
| Compliance checking | Sudah ada | Refactor agar menghasilkan snapshot historis |
| Report approval | Sudah ada | Integrasikan dengan laporan multi-lab/periode |

**Kesimpulan:** revisi bersifat **evolutif**, bukan membuat aplikasi baru.

---

# 3. Perubahan Arsitektur Data: Paling Penting

## 3.1 Masalah `software_discoveries`

Saat ini `software_discoveries` digunakan sebagai current state. Proses scan melakukan pola `updateOrCreate()` lalu menghapus discovery yang tidak lagi terdeteksi.

Akibatnya database lebih cocok menjawab:

> Software apa yang terpasang sekarang?

Bukan:

> Software apa yang terdeteksi pada setiap waktu monitoring?

### Dampak untuk penelitian

Misalnya:

```text
01 Oktober  → Office terdeteksi
01 November → Office tidak terdeteksi
01 Desember → Office terdeteksi kembali
```

Riwayat seperti itu harus tetap tersedia.

## 3.2 Solusi: pisahkan current state dan historical state

Pertahankan:

```text
software_discoveries
```

sebagai **current state**.

Tambahkan tabel histori:

```text
scan_sessions
    ↓
scan_software_results
```

dan histori compliance:

```text
scan_sessions
    ↓
compliance_snapshots
```

Target arsitektur:

```text
Laboratory
   │
   └── Computer
         │
         ├── SoftwareDiscovery           ← kondisi saat ini
         │
         └── ScanSession                 ← histori setiap monitoring
                 │
                 ├── ScanSoftwareResult   ← software pada scan tersebut
                 │
                 └── ComplianceSnapshot   ← status lisensi pada scan tersebut
```

---

# 4. Tambahkan `scan_sessions`

## Tujuan

Mewakili satu kali kegiatan monitoring pada satu komputer.

Contoh:

```text
Scan #9821
Computer : LAB-FTI-001
Started  : 2026-10-01 08:00:00
Finished : 2026-10-01 08:02:13
Trigger  : scheduled
Status   : completed
```

## Kolom yang disarankan

```text
id
computer_id
started_at
completed_at
status
trigger
software_count
error_message nullable
created_at
updated_at
```

`trigger` dapat berupa:

```text
scheduled
manual
on_demand
```

`status` dapat berupa:

```text
pending
running
completed
failed
partial
```

---

# 5. Tambahkan `scan_software_results`

## Tujuan

Menyimpan snapshot software yang ditemukan pada **satu scan tertentu**.

Contoh:

| Scan | Komputer | Software | Versi | Vendor |
|---|---|---|---|---|
| 101 | PC-01 | Google Chrome | 140 | Google |
| 101 | PC-01 | Office | 2021 | Microsoft |
| 102 | PC-01 | Google Chrome | 141 | Google |
| 102 | PC-01 | VS Code | 1.105 | Microsoft |

## Kolom yang disarankan

```text
id
scan_session_id
catalog_id nullable
raw_name
version nullable
vendor nullable
install_date nullable
created_at
updated_at
```

`scan_software_results` **tidak boleh dihapus hanya karena software sudah tidak muncul pada scan berikutnya**.

---

# 6. Tambahkan `compliance_snapshots`

## Tujuan

Menyimpan status kepatuhan pada saat scan tertentu.

Contoh:

| Tanggal | Lab | Komputer | Software | Status |
|---|---|---|---|---|
| 01 Okt | FTI Lab 1 | PC-01 | Office | Tidak Berlisensi |
| 01 Nov | FTI Lab 1 | PC-01 | Office | Berlisensi |
| 01 Des | FTI Lab 1 | PC-01 | Office | Tidak Berlisensi |

## Kolom yang disarankan

```text
id
scan_session_id
computer_id
software_catalog_id nullable
software_name
software_version nullable
status
keterangan nullable
license_inventory_id nullable
detected_at nullable
scanned_at
created_at
updated_at
```

Status sebaiknya tidak hanya dipahami sebagai “illegal/legal”. Gunakan istilah operasional seperti:

```text
Berlisensi
Tidak Berlisensi
Grace Period
Perlu Ditinjau
```

untuk menjaga agar sistem tidak menyimpulkan status hukum secara otomatis tanpa dasar data lisensi yang memadai.

---

# 7. Ubah Alur Scan Backend

## Alur saat ini

```text
Agent
  ↓
POST /scan-result
  ↓
Update Computer
  ↓
ProcessScanResultJob
  ↓
Update SoftwareDiscovery
  ↓
GenerateComplianceReportJob
  ↓
Update ComplianceReport
```

## Alur target

```text
Agent
  ↓
POST /scan-result
  ↓
Create ScanSession
  ↓
ProcessScanResultJob
  ├── simpan ScanSoftwareResult
  ├── update SoftwareDiscovery
  └── generate ComplianceSnapshot
          ↓
      current compliance/report
```

Semua hasil yang termasuk satu kegiatan scan harus mempunyai `scan_session_id` yang sama.

---

# 8. Perbaiki Dependency Antar-Job

Saat ini compliance dijalankan dengan delay waktu. Pola seperti:

```php
GenerateComplianceReportJob::dispatch($computer)
    ->delay(now()->addSeconds(10));
```

tidak menjamin job pemrosesan scan telah selesai dalam 10 detik.

## Target

Gunakan job chaining atau mekanisme dependency yang menjamin urutan:

```text
ProcessScanResultJob
        ↓ selesai
GenerateComplianceReportJob
        ↓ selesai
update dashboard/cache/report
```

Jangan menjadikan “delay 10 detik” sebagai mekanisme sinkronisasi data.

---

# 9. Tambahkan Metadata Waktu Monitoring

Karena dosen meminta monitoring berkala, sistem harus dapat menjawab:

- kapan scan dimulai;
- kapan scan selesai;
- komputer mana yang discan;
- scan berhasil atau gagal;
- berapa software ditemukan;
- berapa lama scan berlangsung;
- kapan terakhir komputer aktif/terhubung.

Tambahkan/pertahankan field seperti:

```text
started_at
completed_at
scanned_at
last_seen_at
duration
status
```

Jika duration ingin dihitung tanpa disimpan, cukup diturunkan dari `started_at` dan `completed_at`.

---

# 10. Agent Windows: Perubahan yang Diperlukan

## Yang sudah baik

Agent sudah:

```text
- mendapatkan identitas komputer
- register
- menggunakan token
- menjalankan scan software
- membaca Registry Windows
- membaca Windows Store/Appx
- mengirim hasil ke API
- memiliki mode scheduled
- memiliki mode polling
```

## Yang perlu ditambah

### 10.1 Identitas lab harus tetap terkendali

Agent saat ini membawa:

```json
{
  "laboratoryId": 1
}
```

Jangan hanya mempercayakan `laboratory_id` dari client untuk menentukan lokasi komputer.

Target yang lebih aman:

```text
Admin
  ↓
Generate konfigurasi/registration untuk Lab X
  ↓
Agent register
  ↓
Server mengikat komputer ke Lab X
```

`laboratory_id` dari request sebaiknya divalidasi terhadap identitas/credential agent atau mekanisme enrollment yang dikelola server.

### 10.2 Tambahkan metadata run

Agent sebaiknya mengirim identitas run/scan bila dibutuhkan:

```text
scan_id / scan_uuid
scan_mode
client_started_at
client_completed_at
agent_version
```

`scan_uuid` berguna sebagai idempotency key agar retry tidak membuat snapshot duplikat.

---

# 11. Jadwal Monitoring

Scheduler Windows yang sudah ada cukup dipertahankan:

```text
USN-Manifest-DailyScan
    → 1 kali sehari pada 08:00

USN-Manifest-Polling
    → setiap 15 menit untuk memeriksa perintah scan
```

## Prinsip penelitian

Monitoring utama dapat menggunakan:

```text
1 scan otomatis per komputer per hari
```

Selama 2–3 bulan, sistem dapat menghasilkan puluhan hingga sekitar 90 observasi per komputer tergantung periode efektif dan keberhasilan scan.

Tidak semua observasi harus dicetak di laporan. Yang penting **histori mentah tetap tersedia**.

---

# 12. Fitur Monitoring Baru yang Disarankan

Tambahkan menu/halaman:

## 12.1 Riwayat Monitoring

Filter:

```text
Periode
Fakultas (opsional)
Laboratorium
Komputer
Status scan
```

Tampilkan:

```text
Tanggal
Laboratorium
Komputer
Waktu scan
Durasi
Jumlah software
Status scan
```

## 12.2 Detail Histori Komputer

Contoh:

```text
PC-01

01 Okt
  Chrome 140
  Office 2021
  VS Code 1.105

01 Nov
  Chrome 141
  VS Code 1.105

01 Des
  Chrome 142
  Office 2021
  VS Code 1.106
```

## 12.3 Perubahan Software

Sistem sebaiknya dapat mendeteksi:

```text
Software baru
Software hilang
Versi berubah
Software kembali terdeteksi
```

---

# 13. Dashboard Harus Mencerminkan Monitoring, Bukan Hanya Inventory

Tambahkan minimal:

## Statistik

```text
Total Laboratorium
Total Komputer
Komputer Aktif
Komputer Belum Scan
Scan Berhasil
Scan Gagal
Software Teridentifikasi
Temuan Perlu Ditinjau
```

## Trend

```text
Jumlah monitoring per hari/minggu
Persentase keberhasilan scan
Jumlah software baru
Jumlah software dihapus
Jumlah versi yang berubah
Trend status compliance
```

## Filter global

```text
Periode
Laboratorium
Fakultas (jika data fakultas dimodelkan)
Status
```

---

# 14. Report Harus Mendukung Multi-Lab dan Histori

Laporan jangan hanya berbasis current state.

Tambahkan tipe laporan seperti:

### Rekap Monitoring

```text
Periode
Laboratorium
Total Komputer
Total Scan
Scan Berhasil
Scan Gagal
```

### Rekap Software

```text
Software
Jumlah komputer
Versi
Vendor
Laboratorium
Periode
```

### Rekap Kepatuhan

```text
Berlisensi
Tidak Berlisensi
Grace Period
Perlu Ditinjau
```

### Perubahan

```text
Software baru
Software dihapus
Software upgrade/downgrade
Perubahan status lisensi
```

---

# 15. Ubah Sumber Data Report

Hindari menjadikan:

```text
software_discoveries.created_at
```

sebagai sumber utama untuk menyimpulkan kapan monitoring terjadi.

Gunakan:

```text
scan_sessions.started_at
scan_sessions.completed_at
scan_sessions.status
```

Untuk detail software historis gunakan:

```text
scan_software_results
```

Untuk status kepatuhan historis gunakan:

```text
compliance_snapshots
```

---

# 16. Current State dan History Harus Jelas

Gunakan prinsip:

```text
software_discoveries
= kondisi software saat ini

scan_software_results
= bukti software pada setiap scan

compliance_reports
= ringkasan compliance saat ini (bila tetap dipertahankan)

compliance_snapshots
= bukti compliance pada setiap scan
```

Dengan pemisahan ini, dashboard tetap cepat menggunakan current state, sementara penelitian menggunakan data historis.

---

# 17. Pertimbangkan Retensi dan Penghapusan Data

Karena data historis adalah evidence penelitian:

- jangan menghapus snapshot hanya karena software sudah hilang;
- jangan melakukan hard delete komputer selama periode penelitian;
- jangan menghapus riwayat hanya untuk membersihkan current state.

Untuk komputer yang tidak lagi digunakan:

```text
active
inactive
```

lebih baik daripada langsung `DELETE`.

Migration saat ini menggunakan cascade pada beberapa relasi komputer. Untuk tabel histori, hindari desain yang memungkinkan penghapusan komputer menghapus seluruh evidence monitoring.

---

# 18. Multi-Laboratorium: Struktur Data

Struktur yang ditargetkan:

```text
Laboratory
   │
   ├── Computer 1
   ├── Computer 2
   ├── Computer 3
   └── ...
```

User yang terkait lab:

```text
User
 ├── role = kepala_lab
 └── laboratory_id = X
```

Sehingga:

```text
PJ Lab FTI
   ↓
 hanya data Lab FTI

PJ Lab FKIP
   ↓
 hanya data Lab FKIP

Admin
   ↓
 lintas laboratorium

Pimpinan
   ↓
 dashboard/reports sesuai hak akses
```

---

# 19. Aktor Sistem

## Gunakan aktor/peran berikut

### Admin Sistem

```text
Kelola user
Kelola laboratorium
Kelola komputer
Kelola software catalog
Kelola inventaris lisensi
Kelola kebijakan
Monitoring keseluruhan
Laporan
```

### PJ Lab / Kepala Lab

```text
Melihat komputer lab
Melihat manifest lab
Melihat histori monitoring lab
Review temuan
Review laporan
Approval laporan
```

### Operator Lab

Gunakan hanya jika di lapangan memang ada peran orang yang menjalankan fungsi operasional terpisah dari Admin/PJ Lab:

```text
Registrasi/aktivasi komputer
Memastikan agent berjalan
Memeriksa status scan
Menjalankan/request scan
```

Jika tidak ada aktor operasional yang benar-benar berbeda, **tidak perlu membuat role Operator hanya demi diagram**.

### Pimpinan

```text
Melihat dashboard
Melihat tren
Melihat rekap antar-lab
Melihat/download laporan
```

### Agent Scanner

Bukan aktor manusia. Pada **Diagram Konteks/DFD** boleh ditampilkan sebagai entitas eksternal teknis karena ada pertukaran data dua arah.

---

# 20. Diagram Konteks yang Harus Direvisi

Konsep target:

```text
                       ┌──────────────┐
                       │   Pimpinan   │
                       └──────┬───────┘
                              │
                    Dashboard / Laporan
                              │
                              ▼
┌───────────────┐     ┌─────────────────────────────┐
│ Admin Sistem  │◄───►│ Sistem Informasi Manifest   │
└───────────────┘     │ Lisensi Software USN Kolaka │
                      └─────────────────────────────┘
                         ▲       ▲          ▲
                         │       │          │
                    Review   Operasional   Scan
                         │       │          │
                    ┌────┴──┐ ┌─┴────────┐ ┌──────────────┐
                    │ PJ Lab│ │Operator  │ │ Agent Scanner│
                    │       │ │Lab        │ │              │
                    └───────┘ └───────────┘ └──────────────┘
```

Jika tidak ada Operator Lab secara nyata, hapus aktor tersebut dari diagram dan pertahankan Admin + PJ Lab + Pimpinan + Agent.

---

# 21. Use Case Diagram yang Perlu Disesuaikan

## Admin Sistem

```text
Kelola User
Kelola Laboratorium
Kelola Komputer
Kelola Software Catalog
Kelola Lisensi
Kelola Kebijakan
Lihat Monitoring
Lihat Histori Monitoring
Kelola Laporan
```

## PJ Lab

```text
Lihat Dashboard Lab
Lihat Inventory Lab
Lihat Histori Monitoring
Lihat Status Compliance
Review Temuan
Approve/Reject Laporan
```

## Pimpinan

```text
Lihat Dashboard
Filter berdasarkan periode/lab
Lihat Tren
Lihat Laporan
Download Laporan
```

## Operator Lab (opsional)

```text
Registrasi Komputer
Lihat Status Agent
Menjalankan Scan
Melihat Status Scan
```

---

# 22. DFD yang Perlu Diubah

## Context / Level 0

Harus menunjukkan:

```text
Admin
PJ Lab
Pimpinan
Agent Scanner
```

## Level 1

Minimal pecah menjadi proses:

```text
1.0 Manajemen Pengguna & Laboratorium
2.0 Manajemen Komputer
3.0 Pengumpulan Data Scan
4.0 Pengelolaan Manifest Software
5.0 Verifikasi Lisensi & Compliance
6.0 Monitoring & Histori
7.0 Pelaporan & Approval
```

## Data store

Minimal:

```text
D1 Users
D2 Laboratories
D3 Computers
D4 Software Catalogs
D5 Software Discoveries
D6 License Inventories
D7 Scan Sessions
D8 Scan Software Results
D9 Compliance Snapshots
D10 Reports / Report Approvals
D11 Activity Logs
```

---

# 23. Activity Diagram yang Perlu Ditambah/Disesuaikan

Buat activity diagram minimal untuk:

1. Registrasi Agent/Komputer.
2. Scan otomatis berkala.
3. Pemrosesan hasil scan.
4. Pembuatan manifest.
5. Verifikasi compliance.
6. Monitoring perubahan software.
7. Review/approval laporan.

### Activity monitoring berkala

```text
Scheduler Windows
      ↓
Agent menjalankan scanner
      ↓
Scan Registry/Appx
      ↓
Kirim hasil ke API
      ↓
Buat ScanSession
      ↓
Simpan snapshot software
      ↓
Update current discovery
      ↓
Hitung compliance
      ↓
Simpan ComplianceSnapshot
      ↓
Update current report/cache
      ↓
Monitoring selesai
```

---

# 24. ERD Revisi

Target relasi utama:

```text
laboratories
    │ 1
    │
    └──── N computers
              │ 1
              │
              ├──── N software_discoveries
              │
              └──── N scan_sessions
                         │ 1
                         ├──── N scan_software_results
                         │
                         └──── N compliance_snapshots
```

Relasi software:

```text
software_catalogs
    │
    ├──── N software_discoveries
    ├──── N scan_software_results
    └──── N compliance_snapshots
```

Relasi lisensi:

```text
software_catalogs
    │
    └──── N license_inventories
```

Relasi user:

```text
laboratories
    │
    └──── N users
```

---

# 25. Konsistensi Status Lisensi

Jangan hanya menggunakan klasifikasi:

```text
Gratis
Komersial
```

sebagai penentu legalitas.

Lebih baik gunakan kombinasi:

```text
Software Catalog
    ↓
Kategori software
    ↓
Data license inventory
    ↓
Kondisi license
    ↓
Compliance status
```

Contoh kategori:

```text
Freeware
Open Source
Commercial
Trial
Other
```

Contoh status compliance:

```text
Berlisensi
Tidak Berlisensi
Grace Period
Perlu Ditinjau
```

`Tidak Berlisensi` sebaiknya berarti sistem tidak menemukan dasar lisensi yang sesuai berdasarkan data lisensi yang tersedia, bukan otomatis menjadi kesimpulan hukum final.

---

# 26. Pencegahan Pelanggaran Hak Cipta: Posisi Sistem

Karena judul tetap menggunakan frasa:

> “untuk mencegah pelanggaran hak cipta”

maka sistem harus diposisikan sebagai **alat pendukung pencegahan melalui deteksi dini, monitoring, dan penyediaan informasi untuk tindakan korektif**.

Jangan mengklaim bahwa sistem:

```text
menjamin tidak ada pelanggaran hak cipta
```

Lebih tepat:

```text
Monitoring
    ↓
Deteksi ketidaksesuaian
    ↓
Informasi/temuan
    ↓
Review Admin/PJ Lab
    ↓
Tindakan korektif
    ↓
Mendukung pencegahan pelanggaran
```

---

# 27. Scope Pengambilan Data Penelitian

## Objek

Beberapa/seluruh laboratorium komputer yang ditetapkan sebagai objek penelitian di lingkungan USN Kolaka.

## Unit observasi

```text
1 komputer
```

yang diamati berkali-kali melalui agent.

## Periode

Sekitar:

```text
2–3 bulan
```

sesuai periode efektif penelitian.

## Interval

Rekomendasi awal:

```text
1 scan otomatis per hari per komputer
```

Scan manual dapat digunakan sebagai pelengkap.

## Data yang disimpan

```text
Identitas komputer
Laboratorium
Timestamp scan
Nama software
Versi
Vendor
Informasi instalasi yang tersedia
Status license/compliance
Status scan
```

---

# 28. Indikator Hasil Penelitian yang Bisa Diambil dari Sistem

Agar scope monitoring dapat dibuktikan secara kuantitatif, siapkan metrik:

### Coverage

```text
Jumlah komputer terdaftar
Jumlah komputer aktif
Jumlah laboratorium terpantau
```

### Monitoring reliability

```text
Jumlah scan terjadwal
Jumlah scan berhasil
Jumlah scan gagal
Persentase keberhasilan scan
```

### Software changes

```text
Jumlah software baru
Jumlah software hilang
Jumlah perubahan versi
```

### Compliance

```text
Jumlah software berlisensi
Jumlah tidak berlisensi
Jumlah grace period
Jumlah perlu ditinjau
```

### Historical trend

```text
Perubahan jumlah temuan per minggu/bulan
Perubahan compliance per minggu/bulan
```

---

# 29. Pengujian yang Perlu Ditambahkan

Selain pengujian fungsi biasa, tambahkan skenario yang membuktikan scope baru.

## Pengujian 1 — Multi-Lab

```text
Buat Lab FTI
Buat Lab FKIP
Register komputer ke masing-masing lab
Verifikasi pemisahan data
```

## Pengujian 2 — Role Scope

```text
PJ Lab FTI login
→ hanya melihat data Lab FTI

PJ Lab FKIP login
→ hanya melihat data Lab FKIP

Admin
→ dapat mengelola lintas lab
```

## Pengujian 3 — Periodic Scan

```text
Scan 1
↓
Scan 2
↓
Scan 3

Verifikasi seluruh scan tercatat.
```

## Pengujian 4 — Software Added

```text
Scan 1 → software A tidak ada
Install software A
Scan 2 → software A muncul
```

Harus ada histori perubahan.

## Pengujian 5 — Software Removed

```text
Scan 1 → software A ada
Uninstall software A
Scan 2 → software A tidak ada
```

Riwayat Scan 1 tetap tersedia.

## Pengujian 6 — Version Change

```text
Scan 1 → Version 1.0
Upgrade
Scan 2 → Version 2.0
```

Perubahan versi harus dapat ditampilkan.

## Pengujian 7 — Compliance Change

```text
Scan 1 → Tidak Berlisensi
Data license diperbaiki
Scan 2 → Berlisensi
```

Kedua kondisi historis harus tetap dapat dibuktikan.

## Pengujian 8 — Failed Scan

Matikan koneksi/agent:

```text
Scan terjadwal
↓
Gagal
```

Sistem harus mencatat kegagalan dan waktu terakhir perangkat terlihat.

---

# 30. UAT yang Lebih Sesuai dengan Scope Baru

UAT sebaiknya melibatkan aktor nyata yang memang tersedia di kampus.

Contoh kelompok responden:

```text
Admin Sistem
PJ/Kepala Lab
Pihak pengelola laboratorium
Pimpinan / perwakilan pengguna laporan
```

Skenario UAT:

```text
Login
Kelola laboratorium
Kelola komputer
Lihat manifest
Melihat histori monitoring
Melihat status lisensi
Review temuan
Approval laporan
Download laporan
```

Jangan menambahkan peran yang tidak benar-benar tersedia di lokasi penelitian hanya untuk memperbanyak responden.

---

# 31. Data Model untuk Fakultas

Scope dosen menyebut laboratorium dari berbagai fakultas.

Ada dua pilihan desain:

## Pilihan A — Tambahkan tabel `faculties`

```text
faculties
    │
    └── laboratories
```

Direkomendasikan **jika dashboard/laporan perlu mengelompokkan laboratorium berdasarkan fakultas secara eksplisit**.

## Pilihan B — Simpan informasi fakultas di laboratorium

Misalnya:

```text
laboratories
- name
- code
- faculty_name
```

Lebih sederhana tetapi kurang terstruktur.

Untuk sistem yang akan benar-benar digunakan lintas fakultas dan ingin dikembangkan lebih lanjut, pilihan A lebih terstruktur.

---

# 32. Perbaikan pada Report Approval

`report_approvals` sudah memiliki:

```text
laboratory_id
reviewed_by
report_type
period
status
notes
reviewed_at
```

Pertahankan, tetapi pastikan report yang direview benar-benar memiliki sumber periode yang jelas:

```text
period_start
period_end
```

lebih baik daripada menyimpan periode sebagai string semata jika nanti perlu filter/report otomatis.

Jika laporan universitas mencakup banyak lab, tentukan apakah approval:

```text
per laboratorium
```

atau:

```text
satu approval tingkat universitas
```

dan dokumentasikan keputusan tersebut.

---

# 33. Security yang Perlu Diperkuat untuk Multi-Lab

Karena agent akan tersebar ke banyak komputer:

- setiap komputer harus memiliki credential/token sendiri;
- token agent tidak boleh dapat digunakan untuk mengakses data lab lain;
- endpoint scan harus mengikat identitas request dengan komputer yang sudah terdaftar;
- `laboratory_id` tidak boleh bebas dipindahkan oleh client tanpa otorisasi;
- data sensitif komputer seperti MAC, serial, IP, dan token tetap dilindungi;
- gunakan HTTPS pada deployment penelitian;
- sediakan mekanisme revoke token agent.

Target prinsip:

```text
Agent A
  ↓
Identity = Computer A
Scope    = Laboratory X

Agent B
  ↓
Identity = Computer B
Scope    = Laboratory Y
```

---

# 34. Perbaikan Deletion Strategy

Untuk penelitian longitudinal, jangan menggunakan hard delete sebagai mekanisme normal untuk komputer yang sudah tidak digunakan.

Gunakan:

```text
active
inactive
maintenance
retired
```

atau Soft Delete jika memang sesuai kebutuhan.

Tujuannya agar histori:

```text
Computer
Scan Session
Software Snapshot
Compliance Snapshot
```

tidak ikut hilang.

---

# 35. File/Komponen Source Code yang Kemungkinan Besar Perlu Diubah

### Database

```text
database/migrations/
    + create_scan_sessions_table
    + create_scan_software_results_table
    + create_compliance_snapshots_table
    + optional create_faculties_table
```

### Model

```text
app/Models/
    + ScanSession.php
    + ScanSoftwareResult.php
    + ComplianceSnapshot.php
    + Faculty.php (opsional)
```

### API

```text
app/Http/Controllers/Api/ScanController.php
app/Http/Controllers/Api/AgentRegisterController.php
app/Http/Controllers/Api/AgentCommandController.php
```

### Jobs

```text
app/Jobs/ProcessScanResultJob.php
app/Jobs/GenerateComplianceReportJob.php
```

### Service

```text
app/Services/SoftwareCatalogService.php
```

### Controller / Report

```text
app/Http/Controllers/ComplianceDataController.php
app/Http/Controllers/ReportController.php
app/Http/Controllers/ComputerDataController.php
app/Http/Controllers/LabInventoryController.php
app/Http/Controllers/ReportApprovalController.php
```

### UI

```text
resources/views/
    dashboard
    compliance
    computers
    reports
    laboratories
```

Tambahkan halaman khusus histori monitoring bila belum tersedia.

### Agent

```text
script/agent/scanner.ps1
script/agent/setup_tasks.ps1
```

---

# 36. Urutan Implementasi yang Disarankan

Jangan mengerjakan semua fitur sekaligus. Ikuti urutan ini.

## Phase 1 — Data Model

```text
[ ] scan_sessions
[ ] scan_software_results
[ ] compliance_snapshots
[ ] review FK + delete behavior
[ ] optional faculties
```

## Phase 2 — Backend Pipeline

```text
[ ] ScanController membuat ScanSession
[ ] ProcessScanResultJob menyimpan snapshot
[ ] update current state tetap berjalan
[ ] Compliance menghasilkan snapshot
[ ] job dependency/chaining diperbaiki
[ ] idempotency scan ditambahkan
```

## Phase 3 — Multi-Lab Access

```text
[ ] pastikan laboratory scope diterapkan pada query
[ ] review authorization admin
[ ] review authorization kepala_lab
[ ] review report scope
[ ] validasi enrollment agent
```

## Phase 4 — Monitoring UI

```text
[ ] monitoring history
[ ] computer history
[ ] software change history
[ ] compliance trend
[ ] scan status
```

## Phase 5 — Reporting

```text
[ ] report per lab
[ ] report lintas lab
[ ] report berdasarkan periode
[ ] perubahan software
[ ] trend compliance
```

## Phase 6 — Agent Deployment

```text
[ ] buat config per lab
[ ] install agent
[ ] register komputer
[ ] verifikasi token
[ ] verifikasi scheduled task
[ ] test scan
```

## Phase 7 — Penelitian Lapangan

```text
[ ] izin penelitian
[ ] daftar laboratorium
[ ] daftar komputer
[ ] baseline scan
[ ] monitoring berkala
[ ] pencatatan kegagalan scan
[ ] final scan
```

## Phase 8 — Testing & Dokumentasi

```text
[ ] functional test
[ ] integration test
[ ] UAT
[ ] analisis hasil monitoring
[ ] revisi diagram
[ ] revisi bab hasil
```

---

# 37. Checklist Diagram dan Dokumen Skripsi

Semua artefak akademik harus konsisten dengan scope baru.

```text
[ ] Judul tetap konsisten
[ ] Latar belakang menyebut masalah pada lingkungan multi-lab
[ ] Rumusan masalah mencakup monitoring berkala
[ ] Batasan masalah mencakup lab yang menjadi objek penelitian
[ ] Tujuan penelitian mencakup monitoring berkala
[ ] Manfaat praktis mencakup admin/pengelola lab
[ ] Diagram konteks direvisi
[ ] Use case direvisi
[ ] DFD Level 0/1/2 direvisi
[ ] Activity diagram monitoring direvisi
[ ] ERD direvisi
[ ] Flowchart tahapan penelitian direvisi
[ ] Metode pengumpulan data menyebut multi-lab
[ ] Pengujian mencakup periodic monitoring
[ ] UAT mencakup pengguna nyata
[ ] Bab hasil menampilkan histori 2–3 bulan
```

---

# 38. Definisi "Monitoring" yang Harus Konsisten

Gunakan definisi operasional berikut sebagai acuan konsep:

> **Monitoring perangkat lunak adalah proses pengamatan dan pencatatan kondisi perangkat lunak yang terpasang pada komputer secara berkala melalui agent pemindai, sehingga perubahan software dan kondisi kepatuhan lisensi dapat diketahui berdasarkan histori hasil pemindaian.**

Konsekuensinya:

```text
Monitoring ≠ hanya scan

Monitoring =
scan
+ timestamp
+ histori
+ perbandingan
+ status
+ laporan/trend
```

---

# 39. Target Akhir Sistem

Pada akhir revisi, sistem seharusnya mampu melakukan hal berikut:

```text
                    USN KOLAKA
                         │
             ┌───────────┼───────────┐
             ↓           ↓           ↓
           Lab A       Lab B       Lab C
             │           │           │
          PCs         PCs         PCs
             │           │           │
             └───────────┼───────────┘
                         ↓
                  Agent Windows
                         ↓
                     API Server
                         ↓
                    Scan Session
                         ↓
              ┌──────────┴──────────┐
              ↓                     ↓
       Software Snapshot      Compliance Snapshot
              │                     │
              └──────────┬──────────┘
                         ↓
                  Historical Data
                         ↓
              ┌──────────┴──────────┐
              ↓                     ↓
       Monitoring Dashboard       Report
              │                     │
              └──────────┬──────────┘
                         ↓
                Review / Tindakan
```

---

# 40. Definisi Selesai (Definition of Done)

Revisi scope dapat dianggap siap untuk pengambilan data jika semua poin utama berikut terpenuhi:

### Wajib

- [ ] Lebih dari satu laboratorium dapat didaftarkan dan digunakan.
- [ ] Komputer dapat terikat ke laboratorium yang benar.
- [ ] PJ Lab hanya melihat data sesuai scope laboratoriumnya.
- [ ] Agent dapat melakukan scan otomatis berkala.
- [ ] Setiap scan menghasilkan `scan_session`.
- [ ] Hasil software setiap scan tersimpan sebagai histori.
- [ ] Status compliance setiap scan tersimpan sebagai histori.
- [ ] Histori tidak hilang ketika software dihapus dari komputer.
- [ ] Perubahan software antar-scan dapat ditampilkan.
- [ ] Laporan dapat difilter berdasarkan laboratorium dan periode.
- [ ] Dashboard menampilkan indikator monitoring berkala.
- [ ] Sistem dapat merekam scan gagal.
- [ ] Pengujian multi-lab dan periodic monitoring berhasil.

### Sangat disarankan

- [ ] `scan_uuid`/idempotency diterapkan.
- [ ] Job processing dibuat deterministic/chained.
- [ ] Agent enrollment mengikat komputer ke lab di sisi server.
- [ ] Status komputer menggunakan active/inactive/retired daripada hard delete.
- [ ] Fakultas dimodelkan sebagai entitas terpisah bila diperlukan untuk laporan.
- [ ] Ada histori perubahan versi software.
- [ ] Ada trend compliance.

---

# 41. Prioritas Jika Waktu Terbatas

Jika waktu pengerjaan sempit, prioritaskan secara berurutan:

```text
P0 — WAJIB
1. Historical scan
2. Historical software snapshot
3. Historical compliance snapshot
4. Multi-lab access scope
5. Laporan berdasarkan periode

P1 — PENTING
6. Monitoring history UI
7. Software change detection
8. Scan success/failure tracking
9. Agent enrollment yang lebih aman

P2 — PENGUAT
10. Trend dashboard
11. Fakultas entity
12. KPI tambahan
13. Optimasi/reporting lanjutan
```

**Jangan menghabiskan waktu di P2 sebelum P0 selesai.**

---

# 42. Konsep Akhir yang Harus Dipertahankan

Ada tiga konsep yang harus menjadi benang merah seluruh sistem dan skripsi:

```text
MULTI-LABORATORY
        +
PERIODIC MONITORING
        +
HISTORICAL MANIFEST
```

Ketiganya kemudian mendukung tujuan:

```text
Identifikasi software
        ↓
Informasi lisensi
        ↓
Deteksi ketidaksesuaian
        ↓
Monitoring perubahan
        ↓
Tindakan korektif
        ↓
Mendukung pencegahan pelanggaran hak cipta
```

---

# 43. Ringkasan Perubahan terhadap Source Code Saat Ini

| Area | Prioritas | Perubahan |
|---|---:|---|
| `software_discoveries` | P0 | Tetap sebagai current state, jangan dijadikan histori |
| `scan_sessions` | P0 | Tambahkan |
| `scan_software_results` | P0 | Tambahkan |
| `compliance_snapshots` | P0 | Tambahkan |
| `ScanController` | P0 | Buat session + proses idempotent |
| `ProcessScanResultJob` | P0 | Simpan snapshot sebelum/bersamaan update current state |
| `GenerateComplianceReportJob` | P0 | Hasilkan snapshot historis |
| Job dependency | P0 | Ganti delay 10 detik dengan dependency/chaining |
| Query scope lab | P0 | Audit seluruh controller/query |
| Reports | P0 | Tambahkan period + lab + history |
| Monitoring UI | P1 | Tambahkan |
| Change detection | P1 | Tambahkan |
| Agent enrollment | P1 | Ikat identitas komputer + lab di sisi server |
| `computers` deletion | P1 | Hindari hard delete yang menghilangkan evidence |
| `faculties` | P2 | Tambahkan jika diperlukan untuk laporan lintas fakultas |
| Dashboard trend | P2 | Tambahkan setelah P0/P1 stabil |

---

# 44. Kesimpulan

Sistem **tidak perlu dibangun ulang**. Perubahan scope dari satu laboratorium menjadi lingkungan laboratorium USN Kolaka terutama menuntut dua transformasi:

```text
1. Single-lab → Multi-lab
2. Current-state inventory → Historical periodic monitoring
```

Fondasi multi-lab sudah tersedia melalui `laboratories`, `computers.laboratory_id`, dan `users.laboratory_id`. Fondasi scanner dan scheduled task juga sudah ada.

**Perubahan paling kritis adalah penambahan histori `scan_sessions`, `scan_software_results`, dan `compliance_snapshots`, kemudian menghubungkan seluruh pipeline scan → processing → compliance → reporting ke histori tersebut.**

Setelah itu, diagram konteks, use case, DFD, activity diagram, ERD, pengujian, dan bab hasil harus menggambarkan sistem yang sama: **satu sistem terpusat, banyak laboratorium, scan berkala, dan histori perubahan software/licensing selama periode penelitian.**
