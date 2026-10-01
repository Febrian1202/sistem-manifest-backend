# Panduan & Roadmap: Sistem Manifest Lisensi Terdistribusi (Fakultas & Alokasi Lisensi)

## 1. Dokumen Acuan & Latar Belakang
- **Dokumen Acuan:** `markdown/planning-perbaikan-manifest-lisensi.md`
- **Judul Skripsi:** Sistem Manifest Lisensi Software untuk Mencegah Pelanggaran Hak Cipta di Lingkungan USN Kolaka.
- **Tujuan Revisi:** Mentransformasikan sistem inventaris lisensi dari model global/flat menjadi model terpusat tingkat Universitas dengan alokasi dan analisis penggunaan bertingkat (Fakultas & Laboratorium).

Sistem harus mampu menjawab:
1. Berapa total lisensi software yang dimiliki USN Kolaka secara sah?
2. Lisensi tersebut dialokasikan ke fakultas mana saja dan berapa kuotanya?
3. Berapa software berlisensi yang benar-benar terpasang di komputer setiap lab/fakultas?
4. Fakultas atau laboratorium mana yang mengalami defisit (kekurangan) lisensi?
5. Fakultas mana yang memiliki surplus (alokasi berlebih/menganggur)?
6. Software apa yang perlu diprioritaskan dalam pengadaan berikutnya?
7. Bagaimana tingkat kepatuhan (compliance) lisensi secara hierarkis (Universitas → Fakultas → Lab)?

---

## 2. Konsep Arsitektur Sistem

```text
                           USN KOLAKA (Universitas)
                                      │
                 ┌────────────────────┴────────────────────┐
                 ▼                                         ▼
         SOFTWARE CATALOG                         FACULTIES (Fakultas)
                 │                                         │
                 ▼                                         ▼
         LICENSE INVENTORY ───────► LICENSE ALLOCATION ◄───┘
   (Hak Milik Universitas)           (Alokasi Seat)
                 │                         │
                 │                         ▼
                 │                  LABORATORIES
                 │                         │
                 │                         ▼
                 │                     COMPUTERS
                 │                         │
                 │                         ▼
                 │                SOFTWARE DISCOVERIES
                 │                (Scan Agent Komputer)
                 │                         │
                 └───────────────┬─────────┘
                                 ▼
                    HIERARCHICAL COMPLIANCE
                  (Universitas / Fakultas / Lab)
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
                  CUKUP       DEFISIT      SURPLUS
                                 │
                                 ▼
                     PROCUREMENT INSIGHT REPORT
               (Bahan Evaluasi & Pengadaan Lisensi)
```

### Prinsip Utama (Non-Negotiable)
1. **Pusat Kebenaran Lisensi (Single Source of Truth):** `LicenseInventory` **tidak** dipecah per fakultas. Semua lisensi adalah aset milik Universitas.
2. **Alokasi Ekplisit:** Distribusi hak pakai dicatat di entitas `LicenseAllocation` yang menghubungkan `LicenseInventory` dan `Faculty`.
3. **Pemisahan Metrik Kunci:**
   - **OWNED:** Total kapasitas lisensi sah universitas (`SUM(quota_limit)` dari inventaris aktif).
   - **ALLOCATED:** Total kuota yang didistribusikan ke fakultas (`SUM(allocated_quota)`).
   - **INSTALLED:** Total instalasi riil software pada komputer aktif hasil discovery (`COUNT(DISTINCT computer_id)`).
4. **Alokasi Tingkat Pertama:** Alokasi cukup dikelola pada level **Fakultas** terlebih dahulu (komputer di laboratorium mewarisi kepemilikan fakultasnya).

---

## 3. Rumus dan Aturan Bisnis

### 3.1 Kuota & Ketersediaan Universitas
$$\text{Remaining Unallocated} = \text{Owned} - \sum \text{Allocated Quota}$$
- **Aturan Validasi:** Total alokasi aktif untuk suatu lisensi **tidak boleh melebihi** kuota kepemilikan (`quota_limit`) lisensi tersebut.

### 3.2 Analisis Tingkat Fakultas
Untuk suatu software $S$ pada Fakultas $F$:
- $\text{Allocated}(S, F) = \sum \text{allocated\_quota}$ lisensi $S$ untuk fakultas $F$.
- $\text{Installed}(S, F) = \text{jumlah komputer aktif di lab-lab bawah } F \text{ yang terinstall } S$.
- $\text{Deficit}(S, F) = \max(\text{Installed}(S, F) - \text{Allocated}(S, F), 0)$
- $\text{Surplus}(S, F) = \max(\text{Allocated}(S, F) - \text{Installed}(S, F), 0)$
- $\text{Utilization Rate}(S, F) = \begin{cases} \frac{\text{Installed}(S, F)}{\text{Allocated}(S, F)} \times 100\%, & \text{jika } \text{Allocated} > 0 \\ \text{N/A (Unallocated)}, & \text{jika } \text{Allocated} = 0 \end{cases}$

### 3.3 Status Kepatuhan Fakultas
- **Cukup (Compliant / Balanced):** $\text{Installed} = \text{Allocated}$
- **Surplus (Underutilized):** $\text{Installed} < \text{Allocated}$
- **Defisit (Overutilized / Non-Compliant):** $\text{Installed} > \text{Allocated}$ (atau terinstall tapi $\text{Allocated} = 0$)

---

## 4. Matriks Peran & Hak Akses (RBAC)

Sistem mengadopsi 4 peran pengguna:

| Modul / Fitur | Admin (Super) | Pimpinan (Eksekutif) | Kepala Lab (PJ) | Staff Lab (Operator) |
|---|:---:|:---:|:---:|:---:|
| **Scope Akses Data** | Seluruh Universitas | Seluruh Universitas (Read-Only) | Laboratorium Sendiri | Lab atau Fakultas Terkait |
| **Kelola Master Fakultas & Lab** | CRUD Penuh | View Only | View Lab Sendiri | View Lab Sendiri / Fakultas |
| **Kelola Katalog & Lisensi** | CRUD Penuh | View Only | Tidak Ada Akses | Tidak Ada Akses |
| **Kelola Alokasi Lisensi** | CRUD Penuh | View Only | Tidak Ada Akses | Tidak Ada Akses |
| **Download / Deploy Scanner Agent**| Ya | Tidak | Ya (Lab Sendiri) | Ya (Lab/Fakultas Terkait) |
| **Monitoring Scan & Discoveries** | Seluruh Sistem | Seluruh Sistem | Lab Sendiri | Lab/Fakultas Terkait |
| **Review / Approval Laporan Lab** | Manage Submissions | View Approved | Review/Approve Lab Sendiri | Tidak Ada Akses |
| **Dashboard** | Admin Global | Eksekutif Multidimensional | Lab Dashboard | Operator Dashboard |
| **Laporan & Rekomendasi Lisensi** | View & Export | View & Export | Laporan Lab Sendiri | Tidak Ada Akses |

*Catatan untuk Staff Lab:* Dirancang untuk fleksibilitas di lapangan: membantu instalasi scanner agent dan mengecek status scan komputer di lab atau fakultas yang ditugaskan, tanpa hak memodifikasi data lisensi institusi.

---

## 5. Struktur Modul & Daftar Task

Setiap task di bawah ini dirancang terisolasi (*self-contained*), memiliki dependensi jelas, dan dapat dikerjakan secara modular oleh developer manusia maupun subagent AI:

```text
Task 01: Entitas Fakultas
   │
   ▼
Task 02: Relasi Laboratorium ke Fakultas
   │
   ├──────────────────────────────┐
   ▼                              ▼
Task 03: Peran Staff Lab       Task 04: Sistem Alokasi Lisensi
   │                              │
   │                              ▼
   │                           Task 05: Perbaikan Perhitungan & Service Sentral
   │                              │
   │                              ▼
   │                           Task 06: Analisis Compliance per Fakultas
   │                              │
   └──────────────────────────────┼──────────────────────────────┐
                                  ▼                              ▼
                               Task 07: Dashboard & Laporan   Task 08: Navigasi, Filter, & E2E Testing
```

### Rangkuman File Task:
1. **[Task 01 — Entitas Fakultas](./task-01-faculty-entity.md):** Migration `faculties`, Model `Faculty`, Factory, Seeder, Admin CRUD, Validasi, dan Blade Views.
2. **[Task 02 — Relasi Laboratorium ke Fakultas](./task-02-lab-faculty-relation.md):** Migration `faculty_id` pada `laboratories`, relasi Eloquent, update form Lab, filter fakultas pada daftar lab, dan update seeder.
3. **[Task 03 — Peran Staff Lab & Scoping Access](./task-03-staff-lab-role.md):** Role `staff_lab`, relasi `faculty_id`/`laboratory_id` pada `User`, trait scoping, menu download agent & monitoring lab.
4. **[Task 04 — Sistem Alokasi Lisensi](./task-04-license-allocation.md):** Migration `license_allocations`, Model `LicenseAllocation`, aturan validasi kuota alokasi $\le$ kuota lisensi aktif, UI CRUD alokasi.
5. **[Task 05 — Perbaikan Kalkulasi Lisensi & LicenseComplianceService](./task-05-fix-license-calculations.md):** Refactor perhitungan multi-lisensi, eliminasi bug `$catalog->licenses->first()`, pembentukan `LicenseComplianceService` sentral.
6. **[Task 06 — Analisis Compliance Bertingkat per Fakultas](./task-06-faculty-compliance.md):** Endpoint & query per fakultas (Owned, Allocated, Installed, Deficit, Surplus), audit compliance hierarkis, filter UI.
7. **[Task 07 — Dashboard Pimpinan & Laporan Kebutuhan Lisensi](./task-07-dashboard-reports.md):** Dashboard eksekutif lintas fakultas, perbandingan alokasi vs terpasang, report pengadaan/kebutuhan lisensi dengan ekspor PDF & Excel.
8. **[Task 08 — Navigasi, Filter Global, Edge Cases & Verification](./task-08-navigation-filters-testing.md):** Penataan ulang menu Sidebar, cascading filter (Fakultas → Lab → Komputer), proteksi integritas FK, dan rangkaian tes Pest menyeluruh.

---

## 6. Standar Teknis & Konvensi Pengerjaan

Bagi siapapun (AI Agent atau Programmer) yang mengeksekusi task ini:
- **Framework:** Laravel 12 (PHP 8.2+ / 8.5)
- **UI:** Blade + Tailwind CSS 4 + Alpine.js (komponen UI konsisten dengan `<x-ui.*>` dan `<x-form.*>`)
- **Testing:** Pest PHP (`php artisan test --filter=...`)
- **Code Style:** Laravel Pint (`./vendor/bin/pint --dirty --format agent`)
- **Git Branch:** Seluruh pekerjaan dikerjakan di branch `feature/faculty-license-allocation`.
- **Database Rule:** Selalu gunakan SQLite `:memory:` untuk pengujian otomatis, dan pastikan migration MySQL di production aman dengan constraint foreign key yang tepat (`nullOnDelete` atau `restrictOnDelete`).
