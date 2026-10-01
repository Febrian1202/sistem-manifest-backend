# Phase 7 — Agent Windows: Penyesuaian

**Prioritas:** P1 PENTING
**Estimasi:** 1 hari kerja
**Depends on:** Phase 2 (Backend Pipeline — agar API sudah siap menerima field baru)
**Referensi dokumen:** Bagian 10, 11

---

## Konteks

Agent PowerShell (`script/agent/scanner.ps1`) sudah berfungsi:
- Mendapatkan identitas komputer
- Register via API dengan token Sanctum
- Scan software dari Windows Registry dan Windows Store/Appx
- Mengirim hasil ke `POST /scan-result`
- Mode scheduled (daily scan via Task Scheduler)
- Mode polling (cek perintah scan setiap 15 menit)

### Yang Perlu Ditambah

Agent perlu mengirim **metadata tambahan** di setiap scan agar backend bisa:
1. Membuat `ScanSession` yang lengkap
2. Menerapkan idempotency (mencegah duplikasi)
3. Melacak versi agent yang digunakan

### File Agent

```
script/agent/scanner.ps1          ← script utama
script/agent/setup_tasks.ps1      ← setup Windows Task Scheduler
script/agent/config.example.json  ← template konfigurasi
```

---

## Task

### 7.1 Tambah `scan_uuid` (Idempotency Key)

**File:** `script/agent/scanner.ps1`

**Tujuan:** Setiap kali agent menjalankan scan, generate UUID unik. Kirim dalam payload agar server bisa mendeteksi duplikasi (misal agent retry karena timeout).

**Implementasi PowerShell:**
```powershell
# Generate UUID unik untuk scan ini
$scanUuid = [System.Guid]::NewGuid().ToString()
```

**Tambahkan ke payload POST /scan-result:**
```json
{
    "scan_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "software": [...]
}
```

**Catatan:**
- UUID di-generate di awal proses scan, SEBELUM mulai scan
- UUID yang sama digunakan jika agent retry request yang sama
- Untuk retry: simpan UUID di file temporary, hapus setelah server response 200

**Mekanisme retry:**
```powershell
# Simpan UUID ke file temp sebelum kirim
$retryFile = "$env:TEMP\usn-manifest-last-scan.json"
$retryData = @{ scan_uuid = $scanUuid; payload = $payload } | ConvertTo-Json
$retryData | Out-File $retryFile -Encoding utf8

# Kirim ke server
try {
    $response = Invoke-RestMethod -Uri $apiUrl -Method POST -Body $payload -Headers $headers
    Remove-Item $retryFile -ErrorAction SilentlyContinue  # Hapus setelah sukses
} catch {
    # Retry di polling berikutnya akan menggunakan UUID yang sama dari file
    Write-Warning "Scan result upload failed, will retry with same UUID"
}
```

---

### 7.2 Tambah Metadata Timestamp

**File:** `script/agent/scanner.ps1`

**Tujuan:** Kirim waktu mulai dan selesai scan dari perspektif client.

**Implementasi:**
```powershell
# Catat waktu mulai scan
$clientStartedAt = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")

# ... proses scan ...

# Catat waktu selesai scan
$clientCompletedAt = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
```

**Tambahkan ke payload:**
```json
{
    "scan_uuid": "...",
    "client_started_at": "2026-10-01T00:00:00Z",
    "client_completed_at": "2026-10-01T00:02:13Z",
    "software": [...]
}
```

**Catatan:** Gunakan UTC agar tidak ada masalah timezone antara client dan server. Server akan menyimpan di `scan_sessions.started_at` dan `completed_at`.

---

### 7.3 Tambah `agent_version`

**File:** `script/agent/scanner.ps1`

**Tujuan:** Setiap payload scan menyertakan versi agent untuk tracking dan debugging.

**Implementasi:**
```powershell
# Di awal script
$AgentVersion = "1.1.0"  # Update setiap kali ada perubahan signifikan
```

**Tambahkan ke payload:**
```json
{
    "scan_uuid": "...",
    "agent_version": "1.1.0",
    "software": [...]
}
```

**Catatan:** Versi ini membantu admin mengetahui komputer mana yang menjalankan agent versi lama dan perlu diupdate.

---

### 7.4 Tambah `scan_mode`

**File:** `script/agent/scanner.ps1`

**Tujuan:** Kirim informasi apakah scan dipicu secara `scheduled`, `manual`, atau `on_demand` (dari polling).

**Implementasi:**
```powershell
# Tentukan mode berdasarkan cara script dipanggil
param(
    [string]$ScanMode = "scheduled"  # default: scheduled
)

# Atau deteksi otomatis:
# - Jika dipanggil dari Task Scheduler: scheduled
# - Jika dipanggil dari polling yang dapat perintah scan: on_demand
# - Jika dipanggil manual oleh user: manual
```

**Tambahkan ke payload:**
```json
{
    "scan_uuid": "...",
    "scan_mode": "scheduled",
    "software": [...]
}
```

**Mapping nilai:**

| Nilai | Kapan Digunakan |
|-------|-----------------|
| `scheduled` | Scan otomatis dari Windows Task Scheduler (USN-Manifest-DailyScan) |
| `on_demand` | Scan yang dipicu dari polling (admin request scan via web) |
| `manual` | Scan yang dijalankan user langsung dari command line |

---

### 7.5 Config Per Lab & Enrollment

**File:** `script/agent/config.example.json`

**Kondisi saat ini:**
```json
{
    "serverUrl": "https://...",
    "registrationKey": "...",
    "laboratoryId": 1
}
```

**Perubahan yang direkomendasikan:**

**Minimum (backward compatible):**
- `laboratoryId` tetap dikirim saat register
- Server tetap menerima tapi melakukan validasi
- Setelah register pertama, `laboratory_id` TIDAK bisa diubah oleh agent

**Idealnya (jika waktu ada):**
- Ganti `laboratoryId` + `registrationKey` dengan `labRegistrationKey` per lab
- Admin generate key unik per lab
- Agent hanya perlu key tersebut, server yang menentukan lab

**Config example yang diperbarui:**
```json
{
    "serverUrl": "https://manifest.usn.ac.id",
    "registrationKey": "lab-specific-key-here",
    "laboratoryId": 1,
    "agentVersion": "1.1.0"
}
```

**Catatan penting:**
- `laboratoryId` hanya dipakai saat registrasi, BUKAN di setiap scan
- Setelah register, computer terikat ke lab di server
- Setiap scan, identity komputer ditentukan dari token Sanctum (bukan dari config)

---

### 7.6 Update `setup_tasks.ps1`

**File:** `script/agent/setup_tasks.ps1`

**Perubahan minimal:**
- Pastikan scheduled task passing parameter `$ScanMode` yang benar
- Daily scan: `-ScanMode scheduled`
- Polling-triggered: `-ScanMode on_demand`

---

## Payload API yang Diperbarui

### Format Lama (Backward Compatible)

```json
{
    "hostname": "LAB-FTI-001",
    "software": [
        {
            "name": "Google Chrome",
            "version": "140",
            "vendor": "Google",
            "installDate": "2026-01-15"
        }
    ]
}
```

### Format Baru (Target)

```json
{
    "hostname": "LAB-FTI-001",
    "scan_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "scan_mode": "scheduled",
    "client_started_at": "2026-10-01T00:00:00Z",
    "client_completed_at": "2026-10-01T00:02:13Z",
    "agent_version": "1.1.0",
    "software": [
        {
            "name": "Google Chrome",
            "version": "140",
            "vendor": "Google",
            "installDate": "2026-01-15"
        }
    ]
}
```

**Backward compatibility:** Backend harus tetap menerima payload format lama (tanpa field baru) dengan fallback:
- `scan_uuid` → generate di server jika tidak ada
- `scan_mode` → default `"scheduled"`
- `client_started_at` → gunakan `now()` jika tidak ada
- `agent_version` → `null`

---

## Kriteria Selesai Phase 7

- [x] Agent mengirim `scan_uuid` di setiap scan
- [x] Agent mengirim `client_started_at` dan `client_completed_at`
- [x] Agent mengirim `agent_version`
- [x] Agent mengirim `scan_mode` (scheduled/manual/on_demand)
- [x] Retry menggunakan UUID yang sama (idempotent)
- [x] Backend menerima dan menyimpan semua metadata baru
- [x] Backend tetap menerima payload lama (backward compatible)
- [x] Config example diperbarui
- [x] Setup tasks diperbarui

---

## File yang Akan Diubah

```
script/agent/scanner.ps1
script/agent/setup_tasks.ps1
script/agent/config.example.json
```

## File Backend yang Terkait (Sudah Diubah di Phase 2)

```
app/Http/Controllers/Api/ScanController.php  — menerima field baru (Phase 2)
```
