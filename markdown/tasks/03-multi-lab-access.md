# Phase 3 — Multi-Lab Access Scope & Security

**Prioritas:** P0 WAJIB
**Estimasi:** 1-2 hari kerja
**Depends on:** Phase 1 (Data Model)
**Bisa paralel dengan:** Phase 2
**Referensi dokumen:** Bagian 10.1, 18, 19, 33

---

## Konteks

Sistem memiliki 3 role utama:
- `admin` — akses penuh lintas laboratorium
- `kepala_lab` — hanya data laboratorium yang menjadi tanggung jawabnya
- `pimpinan` — read-only dashboard/laporan (bisa lintas lab)

User `kepala_lab` terikat ke `laboratory_id`. Semua query data harus di-scope berdasarkan role ini. Saat ini belum ada audit sistematis apakah scope ini benar-benar diterapkan di semua controller.

### Prinsip Akses

```
PJ Lab FTI login → hanya data Lab FTI
PJ Lab FKIP login → hanya data Lab FKIP
Admin login → lintas laboratorium
Pimpinan login → dashboard/reports sesuai hak akses
Agent A (token) → hanya bisa submit data untuk Computer A di Lab X
```

---

## Task

### 3.1 Audit Semua Controller Query

**Tujuan:** Periksa SETIAP controller apakah query sudah di-filter berdasarkan `laboratory_id` untuk role `kepala_lab`.

**Controller yang perlu diaudit:**

| Controller | File | Scope yang diharapkan |
|------------|------|-----------------------|
| `DashboardController` | `app/Http/Controllers/DashboardController.php` | kepala_lab: hanya data lab-nya |
| `ComputerDataController` | `app/Http/Controllers/ComputerDataController.php` | kepala_lab: hanya komputer lab-nya |
| `SoftwareDataController` | `app/Http/Controllers/SoftwareDataController.php` | kepala_lab: hanya software dari komputer lab-nya |
| `LicenseDataController` | `app/Http/Controllers/LicenseDataController.php` | admin: semua, pimpinan: read-only semua |
| `ComplianceDataController` | `app/Http/Controllers/ComplianceDataController.php` | kepala_lab: hanya compliance lab-nya |
| `ReportController` | `app/Http/Controllers/ReportController.php` | kepala_lab: hanya report lab-nya |
| `LabInventoryController` | `app/Http/Controllers/LabInventoryController.php` | kepala_lab: sudah per lab (cek ulang) |
| `ReportApprovalController` | `app/Http/Controllers/ReportApprovalController.php` | kepala_lab: hanya approval lab-nya |
| `LaboratoryController` | `app/Http/Controllers/LaboratoryController.php` | admin only |

**Deliverable:** Daftar controller + method yang perlu diubah, dengan detail perubahan.

---

### 3.2 Buat Scope/Trait Reusable

**Tujuan:** Buat mekanisme reusable agar scope lab tidak di-copy-paste di setiap controller.

**Opsi A — Eloquent Global/Local Scope (Rekomendasi)**

Buat trait yang bisa dipakai di model:

```php
// app/Models/Traits/ScopedByLaboratory.php
trait ScopedByLaboratory
{
    public function scopeForUserLab(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
            return $query->where('laboratory_id', $user->laboratory_id);
        }

        return $query; // admin & pimpinan: semua data
    }
}
```

Penggunaan di controller:
```php
$computers = Computer::forUserLab()->with('laboratory')->paginate();
```

**Opsi B — Helper Method di Base Controller**

```php
protected function labScope(Builder $query, string $column = 'laboratory_id'): Builder
```

**Rekomendasi:** Opsi A (trait di model) lebih clean karena scope terkait data model.

**Catatan:** Untuk model yang tidak punya `laboratory_id` langsung (misal `ScanSession`), scope melalui relasi:
```php
// Di ScanSession
public function scopeForUserLab(Builder $query, ?User $user = null): Builder
{
    $user = $user ?? auth()->user();
    if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
        return $query->whereHas('computer', fn ($q) =>
            $q->where('laboratory_id', $user->laboratory_id)
        );
    }
    return $query;
}
```

---

### 3.3 Validasi Agent Enrollment

**Tujuan:** Pastikan `laboratory_id` komputer ditentukan oleh server, bukan dipercayakan dari client.

**File:** `app/Http/Controllers/Api/AgentRegisterController.php`

**Kondisi saat ini:**
Agent mengirim `laboratory_id` dalam request body saat registrasi. Server menggunakan nilai ini langsung.

**Target yang lebih aman:**

**Opsi A — Registration Key per Lab (Rekomendasi)**
```
Admin membuat registration key untuk Lab X
  ↓
Agent menggunakan key saat register
  ↓
Server lookup key → dapat laboratory_id
  ↓
Computer terikat ke Lab X
```

Implementasi:
1. Tambah tabel `lab_registration_keys` atau gunakan field di `laboratories`
2. Agent mengirim `registration_key` bukan `laboratory_id`
3. Server validasi key dan ikat computer ke lab yang sesuai

**Opsi B — Admin Assign Setelah Register (Alternatif)**
```
Agent register tanpa lab
  ↓
Computer masuk status "unassigned"
  ↓
Admin assign ke lab via web UI
```

**Opsi C — Validasi laboratory_id terhadap credential (Minimum)**
```
Tetap terima laboratory_id dari request
TAPI validasi bahwa:
  - laboratory_id ada di database
  - AGENT_REGISTRATION_KEY sesuai
  - Tidak bisa pindah lab setelah register pertama
```

**Rekomendasi:** Opsi C sebagai minimum viable, Opsi A jika waktu cukup.

---

### 3.4 Agent Token Scope

**Tujuan:** Pastikan token Sanctum hanya bisa digunakan untuk computer yang sesuai.

**File:** `app/Http/Controllers/Api/ScanController.php`

**Validasi yang harus ada:**
```php
// Computer adalah Authenticatable, jadi:
$computer = $request->user(); // via Sanctum guard

// Pastikan scan result HANYA untuk computer ini
// Jangan terima computer_id dari request body — gunakan dari token
$scanSession = ScanSession::create([
    'computer_id' => $computer->id, // dari token, BUKAN dari request
    // ...
]);
```

**Yang TIDAK BOLEH dilakukan:**
```php
// JANGAN: menerima computer_id dari request
$computerId = $request->computer_id; // berbahaya
```

**Validasi tambahan:**
- Token hanya berlaku untuk satu computer
- Computer tidak bisa mengirim scan untuk computer lain
- Jika computer di-`retire`, token harus tetap bisa digunakan (untuk scan terakhir) ATAU di-revoke (tergantung keputusan)

---

### 3.5 Scope pada Tabel Baru

**Tujuan:** Tabel baru (`scan_sessions`, `scan_software_results`, `compliance_snapshots`) harus ikut ter-scope melalui relasi.

**Chain scope:**
```
scan_sessions.computer_id → computers.laboratory_id → laboratories.id
```

**Model scopes yang perlu ditambahkan:**

**ScanSession:**
```php
public function scopeForUserLab(Builder $query, ?User $user = null): Builder
{
    $user = $user ?? auth()->user();
    if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
        return $query->whereHas('computer', fn ($q) =>
            $q->where('laboratory_id', $user->laboratory_id)
        );
    }
    return $query;
}
```

**ComplianceSnapshot:**
```php
public function scopeForUserLab(Builder $query, ?User $user = null): Builder
{
    $user = $user ?? auth()->user();
    if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
        return $query->whereHas('computer', fn ($q) =>
            $q->where('laboratory_id', $user->laboratory_id)
        );
    }
    return $query;
}
```

**ScanSoftwareResult** — scope melalui `scanSession`:
```php
public function scopeForUserLab(Builder $query, ?User $user = null): Builder
{
    $user = $user ?? auth()->user();
    if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
        return $query->whereHas('scanSession.computer', fn ($q) =>
            $q->where('laboratory_id', $user->laboratory_id)
        );
    }
    return $query;
}
```

---

### 3.6 Report Scope Multi-Lab

**Tujuan:** Laporan bisa difilter per lab (kepala_lab) atau lintas lab (admin/pimpinan).

**Perubahan di report controllers:**

1. **Query report harus menerima filter `laboratory_id`:**
   ```php
   $query = ComplianceSnapshot::forUserLab()
       ->when($request->laboratory_id, fn ($q, $labId) =>
           $q->whereHas('computer', fn ($q2) =>
               $q2->where('laboratory_id', $labId)
           )
       );
   ```

2. **Export harus membawa context lab:**
   - Judul report menyebutkan nama lab (atau "Seluruh Laboratorium" jika admin)
   - Filter periode + lab tercetak di header laporan

3. **Report approval scope:**
   - `kepala_lab` hanya bisa approve report untuk lab-nya
   - Admin bisa approve semua
   - Pimpinan read-only

---

## Kriteria Selesai Phase 3

- [x] Login sebagai `kepala_lab` Lab A → hanya melihat data Lab A di SEMUA halaman
- [x] Login sebagai `kepala_lab` Lab B → hanya melihat data Lab B
- [x] Login sebagai `admin` → melihat semua lab
- [x] Login sebagai `pimpinan` → melihat dashboard/report semua lab (read-only)
- [x] Agent tidak bisa mengirim data untuk computer/lab lain
- [x] `laboratory_id` tidak bisa dipindahkan oleh client setelah registrasi
- [x] Report bisa difilter per lab
- [x] Scope trait/method reusable dan dipakai konsisten
- [x] `vendor/bin/pint --dirty` clean

---

## File yang Akan Dibuat/Diubah

### Baru
```
app/Models/Traits/ScopedByLaboratory.php (atau lokasi trait lain sesuai konvensi project)
```

### Diubah
```
app/Http/Controllers/DashboardController.php
app/Http/Controllers/ComputerDataController.php
app/Http/Controllers/SoftwareDataController.php
app/Http/Controllers/ComplianceDataController.php
app/Http/Controllers/ReportController.php
app/Http/Controllers/LabInventoryController.php
app/Http/Controllers/ReportApprovalController.php
app/Http/Controllers/Api/AgentRegisterController.php
app/Http/Controllers/Api/ScanController.php
app/Models/ScanSession.php
app/Models/ComplianceSnapshot.php
app/Models/ScanSoftwareResult.php
app/Models/Computer.php
```
