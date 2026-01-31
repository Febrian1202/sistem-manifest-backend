# Konfigurasi API
$apiUrl = "http://127.0.0.1:8000/api/scan-result"
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Clear-Host
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "    USN MANIFEST - SYSTEM SCANNER AGENT   " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# ---------------------------------------------------------
# TAHAP 1: SCAN HARDWARE & OS
# ---------------------------------------------------------
Write-Host " [1/3] Mengambil Spesifikasi Hardware..." -ForegroundColor Yellow

try {
    # 1. Info Sistem Dasar (Hostname, Model, Manufacturer, RAM)
    $sys = Get-CimInstance -ClassName Win32_ComputerSystem
    $bios = Get-CimInstance -ClassName Win32_BIOS
    
    # Hitung RAM ke GB
    $ramGB = [math]::Round($sys.TotalPhysicalMemory / 1GB, 0)

    # 2. Info Processor
    $cpu = Get-CimInstance -ClassName Win32_Processor
    
    # 3. Info Disk (Drive C:)
    $disk = Get-CimInstance -ClassName Win32_LogicalDisk | Where-Object { $_.DeviceID -eq "C:" }
    $diskTotal = [math]::Round($disk.Size / 1GB, 0)
    $diskFree = [math]::Round($disk.FreeSpace / 1GB, 0)

    # 4. Info Jaringan (Ambil yang aktif dan punya IP)
    $net = Get-CimInstance -ClassName Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled -eq $true } | Select-Object -First 1
    
    # 5. Info OS & Lisensi
    $os = Get-CimInstance -ClassName Win32_OperatingSystem
    
    # Cek Lisensi Windows (WMI)
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

# B. Windows Store Apps
$storeApps = Get-AppxPackage | Where-Object { $_.NonRemovable -eq $false -and $_.IsFramework -eq $false }
foreach ($app in $storeApps) {
    $allSoftware += [PSCustomObject]@{
        name    = $app.Name
        version = $app.Version
        vendor  = $app.Publisher
    }
}

# Filter Duplikat
$uniqueSoftware = $allSoftware | Sort-Object -Property Name -Unique

# ---------------------------------------------------------
# TAHAP 3: KIRIM DATA
# ---------------------------------------------------------
Write-Host " [3/3] Mengirim Data ke Server..." -ForegroundColor Yellow

$payload = @{
    # Identitas Utama
    computer_name      = $sys.DNSHostName
    
    # Hardware Specs
    processor          = $cpu.Name.Trim()
    ram_gb             = $ramGB
    disk_total_gb      = $diskTotal
    disk_free_gb       = $diskFree
    manufacturer       = $sys.Manufacturer.Trim()
    model              = $sys.Model.Trim()
    serial_number      = $bios.SerialNumber.Trim()
    
    # Network
    ip_address         = $net.IPAddress[0]
    mac_address        = $net.MACAddress
    
    # OS Info
    os_name            = $os.Caption.Trim()
    os_version         = $os.Version
    os_architecture    = $os.OSArchitecture
    os_license_status  = $osStatus
    os_partial_key     = $partialKey
    
    # Software List
    installed_software = @($uniqueSoftware)
}

$jsonPayload = $payload | ConvertTo-Json -Depth 5

try {
    $response = Invoke-RestMethod -Uri $apiUrl -Method Post -Body $jsonPayload -ContentType "application/json"
    
    Write-Host "`n [SUKSES]" -ForegroundColor Green
    Write-Host " Server: $($response.message)"
    Write-Host " Komputer: $($response.computer)"
    Write-Host " Data Tersimpan."
}
catch {
    Write-Host "`n [GAGAL] Tidak dapat menghubungi server." -ForegroundColor Red
    Write-Host " Error: $($_.Exception.Message)"
}

Write-Host "`n Tekan Enter untuk keluar..."
Read-Host