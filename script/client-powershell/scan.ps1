# ========================================================
# 1. Konfigurasi API
# ========================================================
# PENTING: Jika dijalankan di komputer lailn (bukan laptop server),
# Ganti '127.0.0.1' dengan IPI Address laptop/pc server (kalau di jaringan yang sama)
$apiUrl = "http://localhost:8000/api/scan-result"

# Setup Protokol Keamanan (Biar aman di Windows lama)
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

# ========================================================
# 2. Ambil Info Komputer & Lisensi OS (WMI)
# ========================================================
Write-Host " [1/3] Mengambil data identitas komputer..." -ForegroundColor Cyan
try {
    # - Info Umum
    $computerSystem = Get-CimInstance -ClassName Win32_ComputerSystem -ErrorAction Stop
    $osSystem = Get-CimInstance -ClassName Win32_OperatingSystem -ErrorAction Stop
    
    $computerName = $computerSystem.Name
    $osName = $osSystem.Caption

    # - Cek Status Lisensi Windows (SoftwareLicensingProduct)
    # Filter: Ambil yang punya Product Key (Partial) dan terkait Windows
    $licenseInfo = Get-CimInstance -ClassName SoftwareLicensingProduct -Filter "PartialProductKey IS NOT NULL" | Where-Object { $_.Name -like "*Windows*" } | Select-Object -First 1

    # Terjemahkan Kode Status (LicenseStatus)
    # 0=Unlicensed, 1=Licensed, 2=00B Grace, 3=OOT Grace, 4=Non-Genuine, 5=Notification
    $osStatus = "Unknown"

    if ($licenseInfo) {
        switch ($licenseInfo.LicenseStatus) {
            1 { $osStatus = "Licensed (Original)" }
            0 { $osStatus = "Unlicensed" }
            2 { $osStatus = "Grace Period (Trial)" }
            3 { $osStatus = "Grace Period (Trial)" }
            4 { $osStatus = "Non-Genuine (Bajakan)" }
            5 { $osStatus = "Notification Mode (Activation Failed)" }
            Default { $osStatus = "Check Required" }
        }
        $partialKey = $licenseInfo.PartialProductKey
    }
    else {
        $osStatus = "Detection Failed"
        $partialKey = "N/A"
    }

    Write-Host "         OS: $osName" -ForegroundColor Gray
    Write-Host "         Status: $osStatus" -ForegroundColor Gray
}
catch {
    <#Do this if a terminating exception happens#>
    Write-Host "Error mengambil info WMI. Pastikan dijalankan sebagai Administrator." -ForegroundColor Red
    $computerName = $env:COMPUTERNAME
    $osName = "Unknown"
    $osStatus = "Error"
    $partialKey = "N/A"
}
# ========================================================
# 3. Scan Software (Registry - Hybrid Method)
# ========================================================
Write-Host " [2/3] Memindai Registry Software..." -ForegroundColor Cyan

# Kita scan 3 lokasi registry agar semua tertangkap (32-bit, 64-bit, dan User Apps)
$registryPaths = @(
    "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*"
)

$installedSoftware = @()

foreach ($path in $registryPaths) {
    # Ambil data registry, abaikan jika path tidak ditemukan
    $keys = Get-ItemProperty $path -ErrorAction SilentlyContinue
    foreach ($key in $keys) {
        # Ambil yang hanya yang punya "DisplayName" (Nama Software)
        if ($key.DisplayName -and $key.DisplayName -ne "") {

            #buat Object Data
            $softwareObj = @{
                Name    = $key.DisplayName
                Version = if ($key.DisplayVersion) { $key.DisplayVersion } else { "Unknown" }
                Vendor  = if ($key.Publisher) { $key.Publisher } else { "Unknown" }
            }

            $installedSoftware += $softwareObj
        }
    }
}

# Hapus Duplikat (Kadang software tercatata ganda di registry)
$uniqueSoftware = $installedSoftware | Sort-Object -Property Name -Unique

Write-Host "     Ditemukan $($uniqueSoftware.Count) software terinstall." -ForegroundColor Cyan

# ========================================================
# 4. Kirim data ke API
# ========================================================
Write-Host " [3/3] Mengirim data ke Server ($apiUrl)..." -ForegroundColor Yellow

$payload = @{
    computer_name     = $computerName
    os_info           = $osSystem.Caption
    os_license_status = $osStatus
    os_partial_key    = $partialKey
    installedSoftware = $uniqueSoftware
}

# Konversi ke JSON
$jsonPayload = $payload | ConvertTo-Json -Depth 4

try {
    # Kirim Request POST
    $response = Invoke-RestMethod -Uri $apiUrl -Method Post -Body $jsonPayload -ContentType "application/json"

    # Cek Respon Server
    Write-Host "------------------------------------------"
    Write-Host " SUKSES! Data berhasil diterima server." -ForegroundColor Green
    Write-Host " Pesan Server: $($response.message)"
    Write-Host " Komputer ID : $($response.computer)"
    Write-Host "------------------------------------------"
}
catch {
    Write-Host "------------------------------------------"
    Write-Host " GAGAL MENGIRIM DATA!" -ForegroundColor Red
    Write-Host " Pastikan:"
    Write-Host " - API Server berjalan"
    Write-Host " - Koneksi internet tersedia"
    Write-Host " Error Detail: $($_.Exception.Message)"
    Write-Host "------------------------------------------"
}

Write-Host "Tekan sembarang tombol untuk keluar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")