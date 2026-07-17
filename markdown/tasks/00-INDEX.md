# Task Index — Revisi Sistem Manifest Software

Daftar seluruh task implementasi revisi, dipecah menjadi unit kerja kecil yang bisa dikerjakan secara independen.

## Urutan Pengerjaan

Task **harus** dikerjakan sesuai urutan sprint. Dalam satu sprint, task bisa dikerjakan paralel kecuali ada dependensi yang tertulis.

---

## Sprint 1 — Fondasi (Estimasi: 2-3 hari)

| # | File | Judul | Dependensi |
|---|------|-------|------------|
| 01 | `01-migration-laboratories.md` | Migration: Tabel `laboratories` | - |
| 02 | `02-migration-laboratory-id-computers.md` | Migration: Tambah `laboratory_id` di `computers` | Task 01 |
| 03 | `03-migration-laboratory-id-users.md` | Migration: Tambah `laboratory_id` di `users` | Task 01 |
| 04 | `04-migration-report-approvals.md` | Migration: Tabel `report_approvals` | Task 01, 03 |
| 05 | `05-model-laboratory.md` | Model: `Laboratory` | Task 01 |
| 06 | `06-model-report-approval.md` | Model: `ReportApproval` | Task 04 |
| 07 | `07-update-model-computer.md` | Update Model: `Computer` (tambah relasi lab) | Task 02, 05 |
| 08 | `08-update-model-user.md` | Update Model: `User` (tambah relasi lab) | Task 03, 05 |
| 09 | `09-update-seeders.md` | Update Seeders: role, permission, default users, sample labs | Task 01-08 |
| 10 | `10-verify-foundation.md` | Verifikasi: migrate:fresh --seed + test regresi | Task 09 |

## Sprint 2 — CRUD Lab & Akun (Estimasi: 2 hari)

| # | File | Judul | Dependensi |
|---|------|-------|------------|
| 11 | `11-laboratory-controller.md` | Controller + Views: `LaboratoryController` (CRUD) | Sprint 1 |
| 12 | `12-update-account-controller.md` | Update `AccountController`: tambah role kepala_lab + pilih lab | Sprint 1 |
| 13 | `13-update-sidebar-navigation.md` | Update Sidebar/Navigasi: menu per role | Sprint 1 |
| 14 | `14-test-lab-management.md` | Test: `LaboratoryManagementTest` + `AccountKepalaLabTest` | Task 11, 12 |

## Sprint 3 — Agent Scanner + Lab (Estimasi: 1-2 hari)

| # | File | Judul | Dependensi |
|---|------|-------|------------|
| 15 | `15-update-agent-download.md` | Update `AgentDownloadController`: dropdown lab + config.json | Sprint 1 |
| 16 | `16-update-agent-register-api.md` | Update `AgentRegisterController`: terima `laboratory_id` | Sprint 1 |
| 17 | `17-update-powershell-scanner.md` | Update Script PowerShell: baca & kirim `laboratoryId` | Task 15, 16 |
| 18 | `18-test-agent-lab-registration.md` | Test: Registrasi agent dengan `laboratory_id` | Task 16 |

## Sprint 4 — Fitur PJ Lab + Kirim Laporan (Estimasi: 3-4 hari)

| # | File | Judul | Dependensi |
|---|------|-------|------------|
| 19 | `19-report-submission-controller.md` | Controller + View: `ReportSubmissionController` (Admin kirim ke PJ Lab) | Sprint 1 |
| 20 | `20-lab-inventory-controller.md` | Controller + Views: `LabInventoryController` (inventaris lab PJ Lab) | Sprint 1 |
| 21 | `21-report-approval-controller.md` | Controller + Views: `ReportApprovalController` (review + approve/reject) | Sprint 1, Task 19 |
| 22 | `22-report-approval-preview-pdf.md` | Fitur Preview PDF di halaman review PJ Lab | Task 21 |
| 23 | `23-update-dashboard-per-role.md` | Update `DashboardController`: dashboard per role | Sprint 1 |
| 24 | `24-test-pj-lab-features.md` | Test: `ReportSubmissionTest`, `KepalaLabRoleTest`, `ReportApprovalTest`, `LabScopedDashboardTest` | Task 19-23 |

## Sprint 5 — Integrasi Pimpinan & Polish (Estimasi: 2-3 hari)

| # | File | Judul | Dependensi |
|---|------|-------|------------|
| 25 | `25-update-report-controller-approval-filter.md` | Update `ReportController`: filter laporan by approval status | Sprint 4 |
| 26 | `26-update-exports-approval-metadata.md` | Update Export Classes: tambah metadata approval di PDF/Excel | Task 25 |
| 27 | `27-update-compliance-controller-scope.md` | Update `ComplianceDataController`: scope data by role | Sprint 4 |
| 28 | `28-update-computer-views-lab-name.md` | Update Computer Views: tampilkan nama Lab (bukan string location) | Sprint 1 |
| 29 | `29-revisi-narasi-ui.md` | Revisi Narasi UI: "Agen Scanner" → "Tools Pemindai" | - |
| 30 | `30-test-pimpinan-approved-reports.md` | Test: `PimpinanApprovedReportsTest` + full regression | Task 25-29 |
