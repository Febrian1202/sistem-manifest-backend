# Task 17 — Update Script PowerShell: Baca & Kirim `laboratoryId`

## Sprint: 3 (Agent Scanner + Lab)
## Prioritas: Tinggi
## Dependensi: Task 15, Task 16
## Estimasi: 30 menit

---

## Deskripsi

Update script PowerShell scanner (`scanner.ps1`) agar membaca `laboratoryId` dari `config.json` dan mengirimkannya saat proses registrasi ke API.

## Langkah-langkah

### 1. Cari File Script

Script scanner ada di `script/agent/scanner.ps1` (atau path serupa di folder `script/agent/`).

### 2. Update Bagian Registrasi

Cari bagian kode yang melakukan registrasi (biasanya `Invoke-RestMethod` atau `Invoke-WebRequest` ke endpoint `/api/agent/register`).

**Tambahkan `laboratory_id` ke body registrasi:**

```powershell
# Baca config
$config = Get-Content "$PSScriptRoot\config.json" | ConvertFrom-Json

# Saat registrasi, sertakan laboratory_id
$regBody = @{
    hostname       = $env:COMPUTERNAME
    mac_address    = $mac
    laboratory_id  = $config.laboratoryId   # BARU
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "$($config.baseUrl)/api/agent/register" `
    -Method Post `
    -Body $regBody `
    -ContentType "application/json" `
    -Headers @{ "X-Agent-Key" = $config.registrationKey }
```

### 3. Jangan Ubah Bagian Lain

- Jangan ubah logic scan (pengumpulan data hardware/software)
- Jangan ubah logic pengiriman hasil scan (`POST /api/scan-result`)
- Jangan ubah logic polling scan command (`GET /api/agent/scan-command`)
- Hanya ubah bagian registrasi

## Referensi
- Baca `script/agent/scanner.ps1` secara lengkap untuk memahami struktur script
- Perhatikan variable naming convention yang sudah digunakan ($config, $regBody, dll)

## File yang Dimodifikasi
- `script/agent/scanner.ps1`

## Verifikasi
- [ ] Script bisa membaca `laboratoryId` dari config.json
- [ ] Registrasi mengirim `laboratory_id` dalam body POST
- [ ] Script tidak error jika dijalankan (test secara konseptual — tidak perlu test pada komputer klien saat ini)
- [ ] Bagian scan dan pengiriman hasil tidak berubah
