# Planning Perbaikan Sistem Manifest Lisensi Software

## Project
**Judul Skripsi:** Sistem Manifest Lisensi Software untuk Mencegah Pelanggaran Hak Cipta di Lingkungan USN Kolaka

## Tujuan Perbaikan

Perubahan sistem diarahkan agar inventaris lisensi tetap **terpusat di tingkat Universitas**, tetapi dapat **dialokasikan dan dianalisis berdasarkan fakultas dan laboratorium**.

Sistem diharapkan mampu menjawab pertanyaan berikut:

1. Berapa lisensi software yang dimiliki USN Kolaka?
2. Lisensi tersebut dialokasikan ke fakultas/laboratorium mana?
3. Berapa software yang benar-benar terpasang pada komputer setiap fakultas/laboratorium?
4. Fakultas atau laboratorium mana yang mengalami kekurangan lisensi?
5. Apakah ada fakultas/laboratorium yang masih memiliki alokasi berlebih?
6. Software apa yang perlu menjadi perhatian dalam pengadaan lisensi berikutnya?
7. Bagaimana kondisi compliance software secara keseluruhan dan per fakultas?

---

# 1. Konsep Arsitektur yang Ditargetkan

Model bisnis yang digunakan:

```text
                    USN KOLAKA
                         │
                         ▼
               SOFTWARE CATALOG
                         │
                         ▼
               LICENSE INVENTORY
          (kepemilikan lisensi universitas)
                         │
                         ▼
               LICENSE ALLOCATION
             (alokasi penggunaan)
                         │
              ┌──────────┼──────────┐
              ▼          ▼          ▼
             FTI       FKIP      Fakultas lain
              │          │          │
              ▼          ▼          ▼
             LAB        LAB        LAB
              │          │          │
              ▼          ▼          ▼
           COMPUTER   COMPUTER   COMPUTER
              │          │          │
              ▼          ▼          ▼
         SOFTWARE DISCOVERY / SCAN
                         │
                         ▼
                  COMPLIANCE ANALYSIS
                         │
             ┌───────────┼───────────┐
             ▼           ▼           ▼
          CUKUP       DEFISIT      SURPLUS
                         │
                         ▼
                PROCUREMENT INSIGHT
```

### Prinsip penting

- **License Inventory tidak dipisah menjadi lisensi FTI, FKIP, dan seterusnya.**
- Inventaris lisensi tetap merepresentasikan **kepemilikan/entitlement universitas**.
- Alokasi disimpan pada entitas terpisah agar satu lisensi dapat didistribusikan ke beberapa fakultas.
- Software yang benar-benar terpasang berasal dari hasil discovery/scan komputer.
- Analisis kebutuhan dilakukan dengan membandingkan **allocation** dan **actual installation**.

---

# 2. Kondisi Sistem Saat Ini

Dari review project saat ini, struktur utama telah memiliki komponen:

- Software Catalog
- License Inventory
- Laboratory
- Computer
- Software Discovery
- Compliance Report
- Dashboard
- Role Admin / Pimpinan / Kepala Lab

Namun terdapat beberapa kekurangan untuk kebutuhan lintas fakultas:

### 2.1 Fakultas belum menjadi entitas database

Laboratory belum mempunyai hubungan ke Faculty.

Akibatnya sistem dapat mengetahui komputer berada di laboratorium tertentu, tetapi belum dapat menelusuri:

```text
Computer → Laboratory → Faculty
```

### 2.2 Belum ada konsep alokasi lisensi

License Inventory saat ini merepresentasikan stok/kuota lisensi secara global, tetapi belum ada informasi:

```text
lisensi ini dialokasikan ke fakultas mana?
lisensi ini dialokasikan berapa seat?
```

### 2.3 Perhitungan penggunaan masih bersifat global

Perlu ditambahkan analisis berdasarkan fakultas/laboratorium sehingga sistem dapat membandingkan:

```text
Installed vs Allocated
```

bukan hanya:

```text
Installed vs Owned
```

### 2.4 Logika compliance perlu konsisten terhadap seluruh lisensi

Saat ini terdapat bagian kode yang mengambil lisensi pertama menggunakan pola seperti:

```php
$catalog->licenses->first();
```

Logika ini perlu diperbaiki agar perhitungan entitlement menggunakan seluruh lisensi yang relevan, bukan hanya record pertama.

---

# 3. Tahap Implementasi

## Phase 0 — Baseline dan Backup

### Tujuan
Memastikan perubahan dapat dilakukan tanpa merusak data existing.

### Pekerjaan

- [x] Backup database project saat ini.
- [x] Buat branch Git khusus, misalnya:
  - `feature/faculty-license-allocation`
- [x] Pastikan project dapat dijalankan dari kondisi baseline.
- [x] Jalankan migration dan test yang tersedia sebelum perubahan.
- [x] Catat struktur tabel existing yang berhubungan dengan lisensi.

### Output
Baseline project yang dapat dibandingkan sebelum dan sesudah perubahan.

---

# 4. Phase 1 — Menambahkan Entitas Fakultas

## 4.1 Buat tabel `faculties`

Rekomendasi field:

```text
faculties
---------
id
code
name
description
created_at
updated_at
```

### Catatan
`code` dapat digunakan untuk kode resmi/singkat fakultas, misalnya `FTI`, `FKIP`, dan sebagainya.

## 4.2 Tambahkan `faculty_id` pada `laboratories`

Struktur menjadi:

```text
laboratories
------------
id
faculty_id
name
code
building
floor
description
created_at
updated_at
```

### Relasi Eloquent

```php
// Faculty.php
public function laboratories()
{
    return $this->hasMany(Laboratory::class);
}
```

```php
// Laboratory.php
public function faculty()
{
    return $this->belongsTo(Faculty::class);
}
```

### Acceptance criteria

- [x] Setiap laboratorium dapat memilih fakultas.
- [x] Data existing tetap aman setelah migration.
- [x] Sistem dapat menampilkan struktur Faculty → Laboratory.
- [x] Filter laboratorium dapat dikembangkan menjadi filter fakultas.

---

# 5. Phase 2 — Seed / Master Data Fakultas dan Laboratorium

Tambahkan data fakultas sesuai struktur riil USN Kolaka.

Contoh seed:

```text
FTI
FKIP
Fakultas lain sesuai data resmi USN
```

Kemudian setiap laboratorium existing harus diberikan `faculty_id`.

### Checklist

- [x] Tentukan daftar fakultas resmi.
- [x] Tentukan kode tiap fakultas.
- [x] Mapping seluruh laboratorium existing ke fakultas.
- [x] Validasi bahwa tidak ada laboratorium tanpa fakultas kecuali memang sengaja dibuat sebagai fasilitas universitas/pusat.

### Catatan desain

Untuk laboratorium yang berada di bawah unit pusat dan bukan fakultas, dapat dipertimbangkan kategori:

```text
Unit Pusat / Universitas
```

Namun jangan menambahkan kategori ini kecuali memang diperlukan oleh struktur organisasi nyata.

---

# 6. Phase 3 — Membuat Sistem License Allocation

Ini merupakan perubahan inti dari masukan penguji.

## 6.1 Buat tabel `license_allocations`

Rekomendasi awal:

```text
license_allocations
-------------------
id
license_inventory_id
faculty_id
allocated_quota
allocation_date
start_date
end_date
status
notes
created_by
created_at
updated_at
```

### Fungsi setiap field

| Field | Fungsi |
|---|---|
| `license_inventory_id` | Menentukan lisensi/software yang dialokasikan |
| `faculty_id` | Fakultas penerima alokasi |
| `allocated_quota` | Jumlah seat yang dialokasikan |
| `allocation_date` | Tanggal alokasi |
| `start_date` | Awal masa alokasi |
| `end_date` | Akhir masa alokasi jika dibutuhkan |
| `status` | Aktif/nonaktif |
| `notes` | Catatan administrasi |
| `created_by` | User yang membuat alokasi |

## 6.2 Relasi model

```text
LicenseInventory
       │
       └── hasMany LicenseAllocation
                         │
                         ▼
                      Faculty
```

Contoh relasi:

```php
// LicenseInventory.php
public function allocations()
{
    return $this->hasMany(LicenseAllocation::class);
}
```

```php
// LicenseAllocation.php
public function licenseInventory()
{
    return $this->belongsTo(LicenseInventory::class);
}

public function faculty()
{
    return $this->belongsTo(Faculty::class);
}
```

---

# 7. Phase 4 — Aturan Bisnis Alokasi Lisensi

Sistem perlu mempunyai aturan yang jelas agar data tidak ambigu.

## 7.1 Total alokasi tidak boleh melebihi total kepemilikan aktif

Misalnya:

```text
License Inventory
Office 2019 = 50 seat
```

Maka:

```text
FTI   = 25
FKIP  = 15
FISIP = 10
----------------
Total = 50
```

Tidak boleh:

```text
FTI   = 30
FKIP  = 20
FISIP = 10
----------------
Total = 60 ❌
```

## 7.2 Bedakan tiga angka utama

Sistem harus konsisten membedakan:

```text
OWNED      = total lisensi yang dimiliki USN
ALLOCATED  = total lisensi yang dialokasikan ke unit
INSTALLED  = software yang ditemukan dari hasil scan
```

## 7.3 Rumus dasar

### Sisa lisensi pusat

```text
Available = Owned - Allocated
```

### Defisit fakultas

```text
Deficit = max(Installed - Allocated, 0)
```

### Sisa alokasi fakultas

```text
Surplus = max(Allocated - Installed, 0)
```

### Rasio pemakaian alokasi

```text
Utilization Rate = Installed / Allocated × 100%
```

Perlu diberikan penanganan khusus ketika `Allocated = 0` untuk menghindari pembagian dengan nol.

---

# 8. Phase 5 — Perbaikan Perhitungan License Inventory

## Tujuan
Memastikan `owned_count` benar-benar mewakili seluruh entitlement aktif.

Jangan bergantung pada record pertama seperti:

```php
$catalog->licenses->first();
```

Gunakan aggregate/sum terhadap seluruh lisensi yang relevan, misalnya:

```text
SUM(quota_limit)
```

dengan filter status/tanggal yang sesuai.

## Contoh kasus

```text
Office 2019

Inventory #1 = 20
Inventory #2 = 15
Inventory #3 = 10

Owned = 45
```

Bukan:

```text
Owned = 20
```

### Checklist

- [x] Audit semua controller yang menghitung license quantity.
- [x] Audit Job compliance.
- [x] Audit dashboard.
- [x] Audit report generator.
- [x] Audit endpoint/API yang mengembalikan license count.
- [x] Gunakan aggregate yang konsisten.

---

# 9. Phase 6 — Mengubah Compliance dari Global menjadi Hierarkis

Compliance harus tetap tersedia pada tingkat global, tetapi dapat diturunkan sampai fakultas dan laboratorium.

## Level 1 — Universitas

```text
USN
└── Software
    ├── Owned
    ├── Allocated
    └── Installed
```

## Level 2 — Fakultas

```text
FTI
└── Software
    ├── Allocated
    └── Installed
```

## Level 3 — Laboratorium

```text
Lab RPL
└── Software
    ├── Allocated / bagian dari alokasi fakultas
    └── Installed
```

### Penting

Alokasi pada tahap pertama cukup disimpan pada level **fakultas**. Tidak perlu langsung membuat alokasi per laboratorium kecuali hasil analisis kebutuhan skripsi memang mengharuskannya.

Dengan demikian kompleksitas tetap terkendali:

```text
USN
 ↓
Faculty Allocation
 ↓
Laboratory Usage
 ↓
Computer Discovery
```

---

# 10. Phase 7 — Query Analisis per Fakultas

Buat service/query khusus agar logika tidak tersebar di banyak controller.

Rekomendasi:

```text
app/Services/LicenseComplianceService.php
```

Atau dipisah jika project berkembang:

```text
app/Services/
├── LicenseComplianceService.php
├── LicenseAllocationService.php
└── ProcurementInsightService.php
```

## Tanggung jawab `LicenseComplianceService`

Menghasilkan data seperti:

```text
Software
Faculty
Owned
Allocated
Installed
Deficit
Surplus
Status
```

Contoh hasil konseptual:

```json
{
  "software": "Microsoft Office 2019",
  "faculty": "FTI",
  "owned": 50,
  "allocated": 20,
  "installed": 25,
  "deficit": 5,
  "surplus": 0,
  "status": "deficit"
}
```

---

# 11. Phase 8 — Dashboard Pimpinan

Dashboard pimpinan perlu diperluas agar dapat memberikan gambaran lintas fakultas.

## 11.1 Ringkasan tingkat universitas

Contoh card:

```text
Total Fakultas
Total Laboratorium
Total Komputer
Total Software
Total Lisensi
Total Defisit
```

## 11.2 Ringkasan per fakultas

Contoh tabel:

| Fakultas | Laboratorium | Komputer | Alokasi Lisensi | Terpasang | Defisit |
|---|---:|---:|---:|---:|---:|
| FTI | 5 | 120 | 90 | 105 | 15 |
| FKIP | 4 | 100 | 110 | 92 | 0 |
| FISIP | 3 | 80 | 70 | 65 | 0 |

## 11.3 Drill-down

User dapat membuka:

```text
Universitas
  ↓
Fakultas
  ↓
Laboratorium
  ↓
Komputer
  ↓
Software
```

## 11.4 Filter yang disarankan

- [ ] Fakultas
- [ ] Laboratorium
- [ ] Software
- [ ] Jenis software
- [ ] Status compliance
- [ ] Status lisensi

---

# 12. Phase 9 — Halaman License Allocation

Tambahkan menu baru di admin:

```text
Licenses
├── Inventory
├── Allocations
└── Compliance
```

## Halaman daftar allocation

Kolom yang disarankan:

```text
Software
Total Owned
Fakultas
Allocated
Installed
Remaining
Status
```

## Form allocation

Input:

```text
Software / License Inventory
Fakultas
Jumlah seat
Tanggal mulai
Tanggal berakhir
Catatan
```

### Validasi

```text
allocated_quota > 0
allocated_quota <= remaining_license
```

---

# 13. Phase 10 — Halaman Fakultas

Tambahkan menu/master data:

```text
Organization
└── Faculties
```

Data:

```text
Code
Name
Number of Labs
Number of Computers
Allocated Licenses
Software Deficit
```

Detail fakultas:

```text
FTI
├── Overview
├── Laboratories
├── Computers
├── Installed Software
├── License Allocation
└── Compliance
```

---

# 14. Phase 11 — Perbaikan Kepala Laboratorium

Kepala Lab cukup fokus pada scope laboratoriumnya.

Dashboard dapat menampilkan:

```text
Lab
├── Total Computer
├── Online
├── Offline
├── Software Installed
├── License Issue
└── Last Scan
```

Filter fakultas dapat digunakan sebagai konteks, tetapi Kepala Lab tidak perlu melihat data fakultas lain apabila role/otorisasinya memang dibatasi.

---

# 15. Phase 12 — Laporan Pengadaan Lisensi

Ini merupakan fitur penting untuk mendukung tujuan sistem sebagai alat bantu pengambilan keputusan.

Nama laporan dapat dibuat:

> **Laporan Analisis Kebutuhan dan Alokasi Lisensi Software**

## Isi laporan

### Ringkasan

```text
Software
Owned
Allocated
Installed
Deficit
Surplus
```

### Per fakultas

```text
FTI
  Allocated = 25
  Installed = 30
  Deficit = 5

FKIP
  Allocated = 30
  Installed = 20
  Surplus = 10
```

### Detail software

```text
Microsoft Office
FTI   : Deficit 5
FKIP  : Surplus 10
FISIP : Cukup
```

## Manfaat untuk pengambilan keputusan

Data tersebut dapat membantu pimpinan mengidentifikasi:

- fakultas dengan kebutuhan lisensi tinggi;
- software yang paling banyak mengalami defisit;
- alokasi yang belum termanfaatkan secara optimal;
- kebutuhan evaluasi sebelum pengadaan lisensi baru.

Sistem memberikan **informasi pendukung keputusan**, bukan secara otomatis menetapkan keputusan pengadaan.

---

# 16. Phase 13 — Tambahkan Procurement Insight

Tahap ini sebaiknya dibuat setelah allocation dan compliance stabil.

## Konsep

Sistem dapat menghasilkan daftar kebutuhan indikatif:

```text
Software                 Fakultas   Defisit
------------------------------------------------
Microsoft Office 2019   FTI        10
Adobe Photoshop         FTI         4
CorelDRAW               FISIP       2
```

Lalu sistem dapat menampilkan:

```text
Potential Additional Need
```

### Catatan penting untuk skripsi

Hindari kalimat bahwa sistem "memutuskan pembelian" atau "menentukan pengadaan secara otomatis".

Lebih tepat menggunakan istilah:

> Sistem menyediakan informasi analisis kebutuhan lisensi sebagai bahan pendukung pengambilan keputusan pengadaan.

---

# 17. Phase 14 — Perbaikan Navigation dan UI

Struktur sidebar yang disarankan:

```text
Dashboard

Organization
├── Faculties
└── Laboratories

Infrastructure
├── Computers
└── Scans

Software
├── Catalog
├── Licenses
├── Allocations
└── Compliance

Reports
└── License Needs
```

## Dashboard Pimpinan

Prioritaskan tampilan berikut:

1. Ringkasan universitas
2. Status per fakultas
3. Software dengan defisit terbesar
4. Tren compliance
5. Drill-down ke laboratorium

---

# 18. Phase 15 — API / Endpoint dan Backend

Audit seluruh route/controller yang berhubungan dengan:

- laboratory;
- computer;
- software discovery;
- license inventory;
- compliance;
- dashboard;
- report.

Pastikan endpoint dapat menerima filter:

```text
faculty_id
laboratory_id
software_id
status
```

Contoh konseptual:

```text
GET /admin/compliance?faculty_id=1
GET /admin/compliance?faculty_id=1&software_id=5
GET /admin/license-allocations?faculty_id=1
```

---

# 19. Phase 16 — Authorization

Pastikan perubahan struktur tidak membuka data secara tidak sengaja.

## Admin

```text
Full management
```

## Pimpinan Universitas

```text
View all faculties
View all laboratories
View all software
View allocation
View compliance
View reports
```

## Kepala Laboratorium

```text
View own laboratory
View own computers
View discovered software
View compliance lab
```

Jika nantinya dibutuhkan, role tertentu dapat diberi `faculty_id` agar akses dibatasi pada satu fakultas.

---

# 20. Phase 17 — Testing

Testing harus mencakup kondisi normal dan kondisi konflik alokasi.

## Test Case 1 — Allocation valid

```text
Owned = 50
FTI = 20
FKIP = 20
```

Expected:

```text
Allocated = 40
Available = 10
```

## Test Case 2 — Allocation melebihi ownership

```text
Owned = 50
FTI = 30
FKIP = 30
```

Expected:

```text
Validation failed
```

## Test Case 3 — Fakultas defisit

```text
Allocated = 20
Installed = 25
```

Expected:

```text
Deficit = 5
Status = Deficit
```

## Test Case 4 — Fakultas surplus

```text
Allocated = 30
Installed = 20
```

Expected:

```text
Surplus = 10
Status = Surplus
```

## Test Case 5 — Tidak ada alokasi

```text
Allocated = 0
Installed = 10
```

Expected:

```text
Deficit = 10
Utilization = N/A
```

## Test Case 6 — Multiple license inventories

```text
Inventory 1 = 20
Inventory 2 = 15
Inventory 3 = 10
```

Expected:

```text
Owned = 45
```

bukan hanya inventory pertama.

## Test Case 7 — Filter fakultas

Memilih `FTI` hanya menampilkan data:

```text
FTI
└── Labs FTI
    └── Computers FTI
```

## Test Case 8 — Drill-down

```text
Faculty → Laboratory → Computer → Software
```

harus konsisten dengan data database.

---

# 21. Phase 18 — Data Integrity dan Edge Cases

Periksa kondisi berikut:

- [ ] Fakultas dihapus tetapi masih memiliki laboratorium.
- [ ] Laboratorium dihapus tetapi masih memiliki komputer.
- [ ] License inventory dinonaktifkan tetapi masih memiliki allocation aktif.
- [ ] Allocation berakhir.
- [ ] Software blacklist.
- [ ] Software freeware/open source.
- [ ] Komputer belum pernah scan.
- [ ] Komputer offline.
- [ ] Software ditemukan tetapi belum masuk katalog.
- [x] Alokasi = 0.
- [ ] Tidak ada lisensi tetapi software terinstal.
- [x] Satu software memiliki beberapa license inventory.

Aturan foreign key dan cascade harus ditentukan dengan hati-hati agar histori laporan tidak rusak.

---

# 22. Phase 19 — Revisi Database Diagram / ERD

Setelah implementasi selesai, ERD harus mencerminkan model baru.

Target relasi utama:

```text
FACULTIES
   │ 1
   │
   │ N
LABORATORIES
   │ 1
   │
   │ N
COMPUTERS
   │ 1
   │
   │ N
SOFTWARE_DISCOVERIES
   │ N
   │
   │ 1
SOFTWARE_CATALOGS
   │ 1
   │
   │ N
LICENSE_INVENTORIES
   │ 1
   │
   │ N
LICENSE_ALLOCATIONS
   │ N
   │
   │ 1
FACULTIES
```

Secara konseptual, siklus allocation membentuk hubungan:

```text
Software
   ↓
License Inventory
   ↓
Allocation
   ↓
Faculty
   ↓
Laboratory
   ↓
Computer
   ↓
Software Discovery
```

---

# 23. Phase 20 — Revisi Dokumentasi Skripsi

Perubahan sistem harus tercermin dalam dokumen skripsi.

## Bab Analisis Kebutuhan

Tambahkan kebutuhan:

> Sistem harus dapat memonitor distribusi/alokasi lisensi software berdasarkan fakultas dan menganalisis kesesuaian antara alokasi lisensi dengan software yang terpasang pada komputer.

## Kebutuhan fungsional tambahan

Contoh:

```text
FR-XX Sistem dapat mengelola data fakultas.
FR-XX Sistem dapat menghubungkan laboratorium dengan fakultas.
FR-XX Sistem dapat mengalokasikan lisensi kepada fakultas.
FR-XX Sistem dapat menghitung jumlah lisensi yang dialokasikan.
FR-XX Sistem dapat menghitung penggunaan software per fakultas.
FR-XX Sistem dapat mengidentifikasi defisit dan surplus lisensi.
FR-XX Sistem dapat menampilkan analisis kebutuhan lisensi.
```

## Use Case tambahan

Aktor Pimpinan:

```text
Melihat dashboard universitas
Melihat status per fakultas
Melihat alokasi lisensi
Melihat compliance
Melihat laporan kebutuhan lisensi
```

Aktor Admin:

```text
Kelola fakultas
Kelola laboratorium
Kelola license inventory
Kelola allocation
```

---

# 24. Prioritas Implementasi

Tidak semua fitur harus dikerjakan sekaligus.

## Priority A — Wajib

- [x] `faculties` table
- [x] `faculty_id` pada laboratories
- [x] model Faculty
- [x] relasi Faculty ↔ Laboratory
- [x] `license_allocations` table
- [x] model LicenseAllocation
- [x] LicenseInventory ↔ Allocation ↔ Faculty
- [x] validasi total allocation ≤ ownership
- [x] perhitungan Owned / Allocated / Installed
- [x] compliance per fakultas
- [ ] dashboard pimpinan per fakultas
- [ ] laporan kebutuhan lisensi
- [ ] test case utama

## Priority B — Sangat disarankan

- [x] drill-down Faculty → Lab → Computer → Software
- [x] filter fakultas
- [ ] filter laboratorium
- [ ] detail alokasi per software
- [x] status Deficit / Surplus / Sufficient
- [x] audit seluruh query license count
- [x] perbaikan multiple license inventory

## Priority C — Pengembangan lanjutan

- [ ] allocation per laboratorium
- [ ] histori perubahan alokasi
- [ ] approval workflow alokasi
- [ ] export Excel/PDF
- [ ] notifikasi lisensi akan habis/expired
- [ ] rekomendasi kebutuhan pengadaan berbasis histori

---

# 25. Urutan Coding yang Paling Aman

Implementasikan dalam urutan berikut:

```text
1. Migration faculties
        ↓
2. Migration laboratories.faculty_id
        ↓
3. Faculty model + relation
        ↓
4. Update laboratory CRUD
        ↓
5. Seed faculty + mapping laboratories
        ↓
6. Migration license_allocations
        ↓
7. LicenseAllocation model + relation
        ↓
8. Allocation CRUD
        ↓
9. Validation allocation quota
        ↓
10. Audit/fix license ownership calculation
        ↓
11. Build compliance service/query
        ↓
12. Compliance per faculty
        ↓
13. Dashboard pimpinan
        ↓
14. Report kebutuhan lisensi
        ↓
15. Authorization
        ↓
16. Automated/manual testing
        ↓
17. Revisi ERD + dokumentasi skripsi
```

---

# 26. Contoh Skenario Demonstrasi Saat Sidang

Skenario yang sangat cocok untuk memperlihatkan nilai fitur ini:

## Kondisi

USN memiliki:

```text
Microsoft Office 2019
Owned = 50 license
```

Alokasi:

```text
FTI  = 20
FKIP = 30
```

Hasil scan:

```text
FTI
Installed = 25

FKIP
Installed = 25
```

## Hasil sistem

```text
FTI
Allocated = 20
Installed = 25
Deficit = 5

FKIP
Allocated = 30
Installed = 25
Surplus = 5
```

Dashboard dapat menunjukkan bahwa kebutuhan FTI lebih besar daripada alokasi saat ini, sementara FKIP masih memiliki alokasi yang belum terpakai.

Sistem kemudian menyediakan informasi tersebut kepada pimpinan sebagai **bahan pertimbangan dalam evaluasi alokasi atau pengadaan lisensi**.

---

# 27. Definition of Done

Perubahan fitur dianggap selesai apabila seluruh kondisi berikut terpenuhi:

### Database

- [x] Fakultas tersimpan sebagai master data.
- [x] Laboratorium mempunyai fakultas.
- [x] Allocation tersimpan dalam tabel terpisah.
- [x] License inventory tetap merepresentasikan ownership universitas.

### Backend

- [x] Relasi Eloquent benar.
- [x] Validation allocation berjalan.
- [x] Owned dihitung dari seluruh inventory relevan.
- [x] Installed dihitung dari discovery.
- [x] Deficit/surplus dihitung konsisten.

### Frontend

- [x] Admin dapat mengelola fakultas.
- [x] Admin dapat mengelola alokasi lisensi.
- [x] Pimpinan dapat melihat semua fakultas.
- [x] Pimpinan dapat melihat status per fakultas.
- [x] User dapat drill-down ke laboratorium.

### Reporting

- [ ] Tersedia laporan kebutuhan lisensi.
- [ ] Laporan menunjukkan Owned / Allocated / Installed.
- [ ] Laporan menunjukkan Deficit / Surplus.

### Quality

- [ ] Test case utama lulus.
- [x] Tidak ada alokasi melebihi ownership.
- [x] Tidak ada error pembagian dengan nol.
- [x] Data existing tetap aman.

### Skripsi

- [ ] ERD diperbarui.
- [ ] Use Case diperbarui.
- [ ] Activity/Sequence Diagram yang terdampak diperbarui.
- [ ] Kebutuhan fungsional diperbarui.
- [ ] Screenshot fitur baru dimasukkan.
- [ ] Skenario pengujian diperbarui.

---

# 28. Target Akhir Sistem

Setelah seluruh planning ini diterapkan, sistem tidak lagi hanya berfungsi sebagai:

> "sistem untuk mengetahui software apa yang terpasang dan apakah software tersebut memiliki lisensi."

Tetapi berkembang menjadi:

> **sistem manajemen dan monitoring manifest lisensi software yang memetakan kepemilikan lisensi universitas, alokasi lisensi ke fakultas, penggunaan software pada laboratorium dan komputer, serta menyediakan analisis defisit/surplus sebagai informasi pendukung pengambilan keputusan pengelolaan dan pengadaan lisensi.**

Model akhirnya:

```text
                 USN KOLAKA
                     │
           ┌─────────┴─────────┐
           ▼                   ▼
       FACULTIES          LICENSE INVENTORY
           │                   │
           ▼                   ▼
      LABORATORIES       LICENSE ALLOCATION
           │                   │
           ▼                   ▼
       COMPUTERS           FACULTIES
           │                   │
           ▼                   │
 SOFTWARE DISCOVERY ───────────┘
           │
           ▼
      COMPLIANCE ANALYSIS
           │
      ┌────┼────┐
      ▼    ▼    ▼
   CUKUP DEFISIT SURPLUS
           │
           ▼
     LICENSE NEEDS
           │
           ▼
   DECISION SUPPORT
```

---

# Catatan Implementasi

Jangan langsung membuat terlalu banyak fitur baru. Untuk konteks skripsi, inti perubahan yang paling bernilai adalah:

```text
Faculty
   +
License Allocation
   +
Installed Software per Faculty
   +
Compliance Analysis
   +
Dashboard/Report
```

Empat komponen tersebut sudah cukup untuk merealisasikan masukan penguji dan membuktikan bahwa satu sistem dapat memberikan gambaran kondisi lisensi lintas fakultas secara terpusat.
