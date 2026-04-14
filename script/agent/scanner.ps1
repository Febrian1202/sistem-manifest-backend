# Konfigurasi API
$baseUrl = "http://127.0.0.1:8000/api"
$registerUrl = "$baseUrl/agent/register"
$scanUrl = "$baseUrl/scan-result"
$tokenFile = "$PSScriptRoot\agent_token.txt"

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Clear-Host
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "    USN MANIFEST - SYSTEM SCANNER AGENT   " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# ---------------------------------------------------------
# TAHAP 0: OTENTIKASI & REGISTRASI
# ---------------------------------------------------------
Write-Host " [0/3] Mengecek Identitas Perangkat..." -ForegroundColor Yellow

# 1. Ambil MAC Address & Hostname untuk Registrasi/Identitas
$sys = Get-CimInstance -ClassName Win32_ComputerSystem
$bios = Get-CimInstance -ClassName Win32_BIOS
$net = Get-CimInstance -ClassName Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled -eq $true } | Select-Object -First 1
$macAddress = $net.MACAddress
$hostname = $sys.DNSHostName

if (-not (Test-Path $tokenFile)) {
    Write-Host " [!] Token tidak ditemukan. Melakukan registrasi..." -ForegroundColor Cyan
    
    $regPayload = @{
        mac_address   = $macAddress
        hostname      = $hostname
        serial_number = $bios.SerialNumber.Trim()
    } | ConvertTo-Json

    try {
        $regResponse = Invoke-RestMethod -Uri $registerUrl -Method Post -Body $regPayload -ContentType "application/json"
        $token = $regResponse.token
        $token | Out-File -FilePath $tokenFile -Encoding ascii
        Write-Host " [+] Registrasi berhasil. Token disimpan." -ForegroundColor Green
    }
    catch {
        Write-Host " [!] Registrasi GAGAL: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
             $details = $_.Exception.Response.GetResponseStream()
             $reader = New-Object System.IO.StreamReader($details)
             Write-Host " Detail: $($reader.ReadToEnd())" -ForegroundColor Red
        }
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
# TAHAP 1: SCAN HARDWARE & OS
# ---------------------------------------------------------
Write-Host " [1/3] Mengambil Spesifikasi Hardware..." -ForegroundColor Yellow

try {
    # 1. RAM ke GB
    $ramGB = [math]::Round($sys.TotalPhysicalMemory / 1GB, 0)

    # 2. Info Processor
    $cpu = Get-CimInstance -ClassName Win32_Processor
    
    # 3. Info Disk (Drive C:)
    $disk = Get-CimInstance -ClassName Win32_LogicalDisk | Where-Object { $_.DeviceID -eq "C:" }
    $diskTotal = [math]::Round($disk.Size / 1GB, 0)
    $diskFree = [math]::Round($disk.FreeSpace / 1GB, 0)

    # 4. Info OS & Lisensi
    $os = Get-CimInstance -ClassName Win32_OperatingSystem
    $licenseInfo = Get-CimInstance -ClassName SoftwareLicensingProduct -Filter "PartialProductKey IS NOT NULL" | Where-Object { $_.Name -like "*Windows*" } | Select-Object -First 1
    
    $osStatus = "Unknown"
    $partialKey = "N/A"

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
}
catch {
    Write-Host "Error saat mengambil info hardware: $($_.Exception.Message)" -ForegroundColor Red
}

# ---------------------------------------------------------
# TAHAP 2: SCAN SOFTWARE
# ---------------------------------------------------------
Write-Host " [2/3] Memindai Aplikasi Terinstall..." -ForegroundColor Yellow
$allSoftware = @()

# A. Registry Scan
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
                    name    = $key.DisplayName
                    version = if ($key.DisplayVersion) { $key.DisplayVersion } else { "Unknown" }
                    vendor  = if ($key.Publisher) { $key.Publisher } else { "Unknown" }
                }
            }
        }
    }
}

# B. Windows Store Apps
try {
    $storeApps = Get-AppxPackage | Where-Object { $_.NonRemovable -eq $false -and $_.IsFramework -eq $false }
    foreach ($app in $storeApps) {
        $allSoftware += [PSCustomObject]@{
            name    = $app.Name
            version = $app.Version
            vendor  = $app.Publisher
        }
    }
} catch {
    Write-Host " [!] Melewati scan Windows Store Apps (Memerlukan Admin)." -ForegroundColor Gray
}

# Filter Duplikat
$uniqueSoftware = $allSoftware | Sort-Object -Property Name -Unique

# ---------------------------------------------------------
# TAHAP 3: KIRIM DATA
# ---------------------------------------------------------
Write-Host " [3/3] Mengirim Data ke Server..." -ForegroundColor Yellow

$payload = @{
    computer_name      = $hostname
    processor          = $cpu.Name.Trim()
    ram_gb             = $ramGB
    disk_total_gb      = $diskTotal
    disk_free_gb       = $diskFree
    manufacturer       = $sys.Manufacturer.Trim()
    model              = $sys.Model.Trim()
    serial_number      = $bios.SerialNumber.Trim()
    ip_address         = $net.IPAddress[0]
    mac_address        = $macAddress
    os_name            = $os.Caption.Trim()
    os_version         = $os.Version
    os_architecture    = $os.OSArchitecture
    os_license_status  = $osStatus
    os_partial_key     = $partialKey
    installed_software = @($uniqueSoftware)
}

$jsonPayload = $payload | ConvertTo-Json -Depth 5

try {
    $response = Invoke-RestMethod -Uri $scanUrl -Method Post -Headers $headers -Body $jsonPayload -ContentType "application/json"
    
    Write-Host "`n [SUKSES]" -ForegroundColor Green
    Write-Host " Server: $($response.message)"
    Write-Host " Komputer: $($response.computer)"
}
catch {
    $err = $_.Exception.Response
    if ($err -and $err.StatusCode -eq "Unauthorized") {
        Write-Host "`n [!] Token tidak valid atau dicabut." -ForegroundColor Red
        if (Test-Path $tokenFile) { Remove-Item $tokenFile }
        Write-Host " Token telah dihapus. SIlakan jalankan kembali script untuk registrasi ulang." -ForegroundColor Yellow
    } else {
        Write-Host "`n [GAGAL] Tidak dapat menghubungi server." -ForegroundColor Red
        Write-Host " Error: $($_.Exception.Message)"
    }
}

Write-Host "`n Selesai. Tekan Enter untuk keluar..."
Read-Host
