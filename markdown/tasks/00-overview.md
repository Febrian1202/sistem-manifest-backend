# Revisi Sistem Manifest Lisensi Software — Overview & Roadmap

## Dokumen Acuan

- [REVISI_SCOPE_USN_KOLAKA_MONITORING.md](../REVISI_SCOPE_USN_KOLAKA_MONITORING.md)

## Tiga Konsep Utama

Seluruh revisi berpusat pada tiga konsep yang harus menjadi benang merah sistem:

```
MULTI-LABORATORY + PERIODIC MONITORING + HISTORICAL MANIFEST
```

## Kondisi Saat Ini vs Target

### Sebelum Revisi

```
1 laboratorium → 1 kali pendataan → current state saja
```

### Setelah Revisi

```
Banyak laboratorium → agent scan berkala → histori setiap scan tersimpan
→ manifest + compliance historis → laporan per lab + universitas
```

---

## Daftar Phase

| Phase | Nama | Prioritas | Estimasi | File Detail |
|-------|------|-----------|----------|-------------|
| 1 | Data Model & Migrasi | P0 WAJIB | 1-2 hari | [01-data-model.md](./01-data-model.md) |
| 2 | Backend Pipeline | P0 WAJIB | 2-3 hari | [02-backend-pipeline.md](./02-backend-pipeline.md) |
| 3 | Multi-Lab Access & Security | P0 WAJIB | 1-2 hari | [03-multi-lab-access.md](./03-multi-lab-access.md) |
| 4 | Monitoring UI | P1 PENTING | 3-4 hari | [04-monitoring-ui.md](./04-monitoring-ui.md) |
| 5 | Reporting & Export | P0-P1 | 2-3 hari | [05-reporting.md](./05-reporting.md) |
| 6 | Dashboard Enhancement | P1-P2 | 1-2 hari | [06-dashboard.md](./06-dashboard.md) |
| 7 | Agent Windows | P1 | 1 hari | [07-agent-windows.md](./07-agent-windows.md) |
| 8 | Testing & Dokumentasi | P0-P1 | 2-3 hari | [08-testing.md](./08-testing.md) |

**Total estimasi: 13-20 hari kerja**

---

## Dependency Graph

```
Phase 1 (Data Model)
  │
  ├──→ Phase 2 (Backend Pipeline)
  │       ├──→ Phase 4 (Monitoring UI)
  │       ├──→ Phase 5 (Reporting)
  │       ├──→ Phase 6 (Dashboard)
  │       └──→ Phase 7 (Agent)
  │
  ├──→ Phase 3 (Multi-Lab Access)  ← bisa paralel dengan Phase 2
  │
  └──→ Phase 8 (Testing) ← berjalan bertahap, final setelah semua selesai
```

### Aturan Dependency

- **Phase 1** harus selesai pertama — semua phase lain bergantung pada data model baru
- **Phase 2 dan 3** bisa dikerjakan **paralel** setelah Phase 1
- **Phase 4, 5, 6, 7** bisa dikerjakan **paralel** setelah Phase 2
- **Phase 8** testing dimulai bertahap per phase, final run setelah semua selesai

---

## Urutan Prioritas (Jika Waktu Terbatas)

```
P0 — WAJIB (kerjakan pertama, tanpa kompromi)
  1. Phase 1 — Data Model
  2. Phase 2 — Backend Pipeline
  3. Phase 3 — Multi-Lab Access
  4. Phase 5 — Reporting (minimal)
  5. Phase 8 — Testing (core tests)

P1 — PENTING (kerjakan setelah P0 selesai)
  6. Phase 4 — Monitoring UI
  7. Phase 7 — Agent
  8. Phase 6 — Dashboard Enhancement

P2 — PENGUAT (hanya jika waktu tersedia)
  - Trend dashboard
  - Fakultas entity
  - KPI tambahan
```

**Jangan menghabiskan waktu di P1/P2 sebelum semua P0 selesai.**

---

## Fondasi yang Sudah Ada (Tidak Perlu Dibuang)

| Komponen | Status | Keputusan |
|----------|--------|-----------|
| `laboratories` table | Ada | Pertahankan |
| `computers.laboratory_id` | Ada | Pertahankan |
| `users.laboratory_id` | Ada | Pertahankan |
| Role `admin`, `kepala_lab`, `pimpinan` | Ada | Pertahankan |
| Agent PowerShell (scanner.ps1) | Ada | Pertahankan, tambah metadata |
| Registry + Appx scan | Ada | Pertahankan |
| Scheduled Task + Polling | Ada | Pertahankan |
| Compliance checking | Ada | Refactor agar hasilkan snapshot |
| Report approval flow | Ada | Integrasikan dengan multi-lab/periode |

**Revisi bersifat evolutif, bukan membuat aplikasi baru.**

---

## Checklist Definition of Done (Ref: Dokumen Bagian 40)

### Wajib

- [x] Lebih dari satu laboratorium dapat didaftarkan dan digunakan
- [x] Komputer terikat ke laboratorium yang benar
- [ ] PJ Lab hanya melihat data sesuai scope laboratoriumnya
- [ ] Agent dapat melakukan scan otomatis berkala
- [x] Setiap scan menghasilkan `scan_session`
- [x] Hasil software setiap scan tersimpan sebagai histori
- [x] Status compliance setiap scan tersimpan sebagai histori
- [x] Histori tidak hilang ketika software dihapus dari komputer
- [ ] Perubahan software antar-scan dapat ditampilkan
- [ ] Laporan dapat difilter berdasarkan laboratorium dan periode
- [ ] Dashboard menampilkan indikator monitoring berkala
- [x] Sistem dapat merekam scan gagal
- [ ] Pengujian multi-lab dan periodic monitoring berhasil

### Sangat Disarankan

- [x] `scan_uuid`/idempotency diterapkan
- [x] Job processing deterministic/chained
- [ ] Agent enrollment mengikat komputer ke lab di sisi server
- [x] Status komputer active/inactive/retired (bukan hard delete)
- [ ] Fakultas dimodelkan sebagai entitas terpisah
- [ ] Histori perubahan versi software
- [ ] Trend compliance
