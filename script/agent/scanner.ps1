param([string]$Mode = "poll")

# Konfigurasi API
$baseUrl        = "http://127.0.0.1:8000/api"
$registerUrl    = "$baseUrl/agent/register"
$scanUrl        = "$baseUrl/scan-result"
$scanCommandUrl = "$baseUrl/agent/scan-command"
$tokenFile      = "$PSScriptRoot\agent_token.txt"
$registrationKey = "4ea50b7ae96598e1671af1240c243fcd" # Ganti dengan AGENT_REGISTRATION_KEY dari file .env backend

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Clear-Host
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "    USN MANIFEST - SYSTEM SCANNER AGENT   " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# ---------------------------------------------------------
# TAHAP 0: OTENTIKASI & REGISTRASI
# ---------------------------------------------------------
Write-Host " [0/3] Mengecek Identitas Perangkat..." -ForegroundColor Yellow

$sys        = Get-CimInstance Win32_ComputerSystem
$bios       = Get-CimInstance Win32_BIOS
$net        = Get-CimInstance Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled } | Select-Object -First 1
$macAddress = $net.MACAddress
$hostname   = $sys.DNSHostName
$ipv4       = ($net.IPAddress | Where-Object { $_ -match '^\d+\.\d+\.\d+\.\d+$' }) | Select-Object -First 1

if (-not (Test-Path $tokenFile)) {
    Write-Host " [!] Token tidak ditemukan. Melakukan registrasi..." -ForegroundColor Cyan

    $regPayload = @{
        mac_address   = $macAddress
        hostname      = $hostname
        serial_number = $bios.SerialNumber.Trim()
    } | ConvertTo-Json

    try {
        $regHeaders = @{
            "X-Agent-Key"  = $registrationKey
            "Accept"       = "application/json"
        }
        $regResponse = Invoke-RestMethod -Uri $registerUrl -Method Post -Headers $regHeaders -Body $regPayload -ContentType "application/json"
        $token = $regResponse.token
        $token | Out-File -FilePath $tokenFile -Encoding ascii
        Write-Host " [+] Registrasi berhasil. Token disimpan." -ForegroundColor Green
    } catch {
        Write-Host " [!] Registrasi GAGAL: $($_.Exception.Message)" -ForegroundColor Red
        exit
    }
} else {
    $token = Get-Content $tokenFile
    Write-Host " [+] Token ditemukan. Menggunakan identitas tersimpan." -ForegroundColor Green
}

$headers = @{
    "Authorization" = "Bearer $token"
    "Accept"        = "application/json"
}

# ---------------------------------------------------------
# TAHAP 0.5: CEK PERINTAH SCAN
# ---------------------------------------------------------
if ($Mode -eq "poll") {
    Write-Host " [0.5/3] Mengecek Perintah Scan dari Server..." -ForegroundColor Yellow
    try {
        $command = Invoke-RestMethod -Uri $scanCommandUrl -Method Get -Headers $headers
        if (-not $command.should_scan) {
            Write-Host " [!] Tidak ada permintaan scan. Keluar." -ForegroundColor Cyan
            exit 0
        }
        Write-Host " [+] Ada permintaan scan. Melanjutkan..." -ForegroundColor Green
    } catch {
        Write-Host " [WARN] Gagal cek perintah: $($_.Exception.Message)" -ForegroundColor DarkYellow
        exit 0
    }
} else {
    Write-Host " [0.5/3] Mode Scheduled: Menjalankan Scan Lengkap..." -ForegroundColor Green
}

# ---------------------------------------------------------
# TAHAP 1: SCAN HARDWARE & OS
# ---------------------------------------------------------
Write-Host " [1/3] Mengambil Spesifikasi Hardware..." -ForegroundColor Yellow

# Default values — aman jika scan gagal
$cpu        = $null; $os = $null; $disk = $null
$ramGB      = 0; $diskTotal = 0; $diskFree = 0
$osStatus   = "Unknown"; $partialKey = "N/A"

try {
    $cpu         = Get-CimInstance Win32_Processor
    $disk        = Get-CimInstance Win32_LogicalDisk | Where-Object { $_.DeviceID -eq "C:" }
    $os          = Get-CimInstance Win32_OperatingSystem
    $licenseInfo = Get-CimInstance SoftwareLicensingProduct -Filter "PartialProductKey IS NOT NULL" |
                   Where-Object { $_.Name -like "*Windows*" } | Select-Object -First 1

    $ramGB     = [math]::Round($sys.TotalPhysicalMemory / 1GB, 0)
    $diskTotal = [math]::Round($disk.Size / 1GB, 0)
    $diskFree  = [math]::Round($disk.FreeSpace / 1GB, 0)

    if ($licenseInfo) {
        switch ($licenseInfo.LicenseStatus) {
            1 { $osStatus = "Licensed" }
            0 { $osStatus = "Unlicensed" }
            2 { $osStatus = "Grace Period" }
            3 { $osStatus = "Grace Period" }
            4 { $osStatus = "Non-Genuine" }
            5 { $osStatus = "Notification Mode" }
            Default { $osStatus = "Check Required" }
        }
        $partialKey = $licenseInfo.PartialProductKey
    }
} catch {
    Write-Host " [WARN] Sebagian info hardware gagal diambil: $($_.Exception.Message)" -ForegroundColor DarkYellow
}

# ---------------------------------------------------------
# TAHAP 2: SCAN SOFTWARE
# ---------------------------------------------------------
Write-Host " [2/3] Memindai Aplikasi Terinstall..." -ForegroundColor Yellow
$allSoftware = @()

$registryPaths = @(
    "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*"
)

foreach ($path in $registryPaths) {
    if (Test-Path $path) {
        $keys = Get-ItemProperty $path -ErrorAction SilentlyContinue
        foreach ($key in $keys) {
            if ($key.DisplayName) {
                $allSoftware += [PSCustomObject]@{
                    name         = $key.DisplayName
                    version      = if ($key.DisplayVersion) { $key.DisplayVersion } else { $null }
                    vendor       = if ($key.Publisher) { $key.Publisher } else { $null }
                    install_date = if ($key.InstallDate) { $key.InstallDate } else { $null }
                }
            }
        }
    }
}

try {
    $storeApps = Get-AppxPackage | Where-Object {
        $_.NonRemovable -eq $false -and
        $_.IsFramework -eq $false -and
        $_.Name -notmatch "^Microsoft\.(NET|VCLibs|UI|Windows|Xbox|Bing|Zune|549981C3F5F10)"
    }
    foreach ($app in $storeApps) {
        $allSoftware += [PSCustomObject]@{
            name         = $app.Name
            version      = $app.Version
            vendor       = $app.Publisher
            install_date = $null
        }
    }
} catch {
    Write-Host " [!] Melewati scan Windows Store Apps." -ForegroundColor Gray
}

$uniqueSoftware = $allSoftware | Sort-Object name -Unique

# ---------------------------------------------------------
# TAHAP 3: KIRIM DATA
# ---------------------------------------------------------
Write-Host " [3/3] Mengirim Data ke Server..." -ForegroundColor Yellow

$payload = @{
    hostname           = $hostname
    processor          = if ($cpu)  { $cpu.Name.Trim() }      else { "Unknown" }
    ram_gb             = $ramGB
    disk_total_gb      = $diskTotal
    disk_free_gb       = $diskFree
    manufacturer       = $sys.Manufacturer.Trim()
    model              = $sys.Model.Trim()
    serial_number      = $bios.SerialNumber.Trim()
    ip_address         = $ipv4
    mac_address        = $macAddress
    os_name            = if ($os)   { $os.Caption.Trim() }    else { "Unknown" }
    os_version         = if ($os)   { $os.Version }           else { "Unknown" }
    os_architecture    = if ($os)   { $os.OSArchitecture }    else { "Unknown" }
    os_license_status  = $osStatus
    os_partial_key     = $partialKey
    installed_software = @($uniqueSoftware)
}

$jsonPayload = $payload | ConvertTo-Json -Depth 5

try {
    $response = Invoke-RestMethod -Uri $scanUrl -Method Post -Headers $headers -Body $jsonPayload -ContentType "application/json"
    Write-Host "`n [SUKSES]" -ForegroundColor Green
    Write-Host " Server  : $($response.message)"
    Write-Host " Komputer: $($response.computer)"
} catch {
    $statusCode = $_.Exception.Response.StatusCode
    if ($statusCode -eq "Unauthorized") {
        Write-Host "`n [!] Token tidak valid atau dicabut." -ForegroundColor Red
        if (Test-Path $tokenFile) { Remove-Item $tokenFile }
        Write-Host " Token dihapus. Jalankan ulang script untuk registrasi ulang." -ForegroundColor Yellow
    } else {
        Write-Host "`n [GAGAL] Tidak dapat menghubungi server." -ForegroundColor Red
        Write-Host " Error: $($_.Exception.Message)"
    }
}

Write-Host "`n Selesai. Tekan Enter untuk keluar..."
Read-Host
