# Golden Path Tests & Playwright E2E Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create comprehensive Golden Path tests for each user role (`admin`, `pimpinan`, `kepala_lab`, `staff_lab`) in Pest, plus Playwright E2E browser test suites covering login and key flows per role.

**Architecture:** 
1. Pest Feature Tests: 4 role-specific test files under `tests/Feature/GoldenPath/`, each validating the complete positive flow (all accessible menus/actions) and negative flow (RBAC boundaries / forbidden endpoints).
2. Playwright E2E Tests: Standard `@playwright/test` configuration with dedicated specs under `e2e/` testing the browser UI, login, navigation, and role-specific views against the real web application.

**Tech Stack:** Laravel 12, PHP 8.4, Pest 3.8, Playwright (@playwright/test), SQLite (:memory: for Pest).

---

### Task 1: Admin Golden Path Feature Test
**Files:**
- Create: `tests/Feature/GoldenPath/AdminGoldenPathTest.php`

**Coverage:**
- Auth: Login, Dashboard with admin stats and charts
- Computers: Index, show, update, delete, request scan, scan history
- Softwares: Index, update catalog
- Licenses: Index, show, create, update, delete, get decrypted key
- License Allocations: Index, create, edit, update, delete
- Faculties & Laboratories: CRUD operations
- Compliance & Monitoring: Index, detail, changes, compliance snapshots
- Reports: All 8 report types previews and exports (Eksekutif, Komputer, Software, Kepatuhan, Lisensi, Monitoring, Perubahan, Kebutuhan Lisensi)
- Submissions & Approvals: Report submission, view approvals, approve/reject
- Admin System: Accounts CRUD, Activity logs, Agent download, Change password

---

### Task 2: Pimpinan Golden Path Feature Test
**Files:**
- Create: `tests/Feature/GoldenPath/PimpinanGoldenPathTest.php`

**Coverage:**
- Auth: Login, Dashboard with pimpinan/executive view
- Read-Only Access (Positive):
  - Computers index & show
  - Softwares index
  - Licenses index & show
  - Compliance index
  - Monitoring index, show, changes, compliance
  - Reports: Eksekutif, Komputer, Software, Kepatuhan, Lisensi, Monitoring, Perubahan, Kebutuhan Lisensi
  - Change own password
- Forbidden Access (Negative - 403 Forbidden):
  - Cannot update or delete computers
  - Cannot create, update, or delete licenses
  - Cannot access license key
  - Cannot update software catalog
  - Cannot access Accounts management
  - Cannot access Faculties CRUD
  - Cannot access Laboratories CRUD
  - Cannot access License Allocations CRUD
  - Cannot access Report Submissions
  - Cannot access Activity Logs
  - Cannot access Agent Download
  - Cannot trigger compliance scan

---

### Task 3: Kepala Lab Golden Path Feature Test
**Files:**
- Create: `tests/Feature/GoldenPath/KepalaLabGoldenPathTest.php`

**Coverage:**
- Auth: Login, Dashboard with lab-scoped view
- Lab-Scoped Access (Positive):
  - Lab inventory (index & show computers in own lab)
  - Monitoring (scoped to own lab)
  - Computer history for own lab computer
  - Compliance (scoped to own lab)
  - Reports preview (scoped to own lab)
  - Report approvals (index, show, approve, reject, preview PDF for own lab)
  - Agent download with own lab assigned
  - Change own password
- Forbidden Access (Negative - 403 Forbidden):
  - Cannot view or access computers in other laboratories
  - Cannot approve or reject report approvals from other laboratories
  - Cannot access global Computer CRUD (/computers)
  - Cannot access Licenses (/licenses)
  - Cannot access Software catalog (/softwares)
  - Cannot access Accounts (/accounts)
  - Cannot access Faculties (/faculties)
  - Cannot access Laboratories CRUD (/laboratories)
  - Cannot access License Allocations (/license-allocations)
  - Cannot access Report Submissions (/reports/submit-to-lab)
  - Cannot access Activity Logs (/activity-logs)
  - Cannot trigger compliance scan

---

### Task 4: Staff Lab Golden Path Feature Test
**Files:**
- Create: `tests/Feature/GoldenPath/StaffLabGoldenPathTest.php`

**Coverage:**
- Auth: Login, Dashboard redirects to Lab Inventory
- Scoped Access (Positive):
  - Lab inventory (index & show computers assigned to their lab/faculty)
  - Agent download (allowed for assigned lab)
  - Change own password
- Forbidden Access (Negative - 403 Forbidden):
  - Cannot access /computers
  - Cannot access /licenses
  - Cannot access /softwares
  - Cannot access /compliance
  - Cannot access /monitoring
  - Cannot access /reports
  - Cannot access /lab/reports (report approvals)
  - Cannot access /accounts
  - Cannot access /faculties
  - Cannot access /laboratories
  - Cannot access /license-allocations
  - Cannot access /reports/submit-to-lab
  - Cannot access /activity-logs

---

### Task 5: Playwright E2E Setup & Tests
**Files:**
- Create: `playwright.config.ts`
- Create: `e2e/admin-golden-path.spec.ts`
- Create: `e2e/pimpinan-golden-path.spec.ts`
- Create: `e2e/kepala-lab-golden-path.spec.ts`
- Create: `e2e/staff-lab-golden-path.spec.ts`
- Update: `package.json` (add `@playwright/test` and scripts)
