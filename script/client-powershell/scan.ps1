# Konfigurasi API
$apiUrl = "http://127.0.0.1:8000/api/scan-result"
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$allSoftware = @()

# Ambil Info Komputer & Lisensi (WMI OS)
Write-Host " [1/5] Mengambil identitas & Lisensi OS..." -ForegroundColor Cyan
try {
    $computerSystem = Get-CimInstance -ClassName Win32_ComputerSystem -ErrorAction Stop
    $osSystem = Get-CimInstance -ClassName Win32_OperatingSystem -ErrorAction Stop
    
    $computerName = $computerSystem.Name
    # $userName = $computerSystem.UserName
    $osName = $osSystem.Caption

    # Cek Lisensi Windows
    $licenseInfo = Get-CimInstance -ClassName SoftwareLicensingProduct -Filter "PartialProductKey IS NOT NULL" | Where-Object { $_.Name -like "*Windows*" } | Select-Object -First 1
    
    $osStatus = "Unknown"
    if ($licenseInfo) {
        switch ($licenseInfo.LicenseStatus) {
            1 { $osStatus = "Licensed (Original)" }
            0 { $osStatus = "Unlicensed" }
            2 { $osStatus = "Grace Period (Trial)" }
            3 { $osStatus = "Grace Period (Trial)" }
            4 { $osStatus = "Non-Genuine (Bajakan)" }
            5 { $osStatus = "Notification Mode" }
            Default { $osStatus = "Check Required" }
        }
        $partialKey = $licenseInfo.PartialProductKey
    }
    else {
        $osStatus = "Detection Failed"; $partialKey = "N/A"
    }
}
catch {
    Write-Host "Gagal akses WMI System." -ForegroundColor Red
    $computerName = $env:COMPUTERNAME; $osName = "Unknown"; $osStatus = "Error"; $partialKey = "N/A"
}

# Scan Software (METODE 1: REGISTRY)
Write-Host " [2/5] Memindai Registry (Win32 Apps)..." -ForegroundColor Cyan

$registryPaths = @(
    "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*"
)

foreach ($path in $registryPaths) {
    $keys = Get-ItemProperty $path -ErrorAction SilentlyContinue
    foreach ($key in $keys) {
        if ($key.DisplayName -and $key.DisplayName -ne "") {
            # PERBAIKAN DI SINI: Tambahkan [PSCustomObject]
            $allSoftware += [PSCustomObject]@{
                name    = $key.DisplayName
                version = if ($key.DisplayVersion) { $key.DisplayVersion } else { "Unknown" }
                vendor  = if ($key.Publisher) { $key.Publisher } else { "Unknown" }
                Source  = "Registry" 
            }
        }
    }
}

# Scan Software (METODE 2: APPX / STORE)
Write-Host " [3/5] Memindai Windows Store (UWP Apps)..." -ForegroundColor Cyan

$storeApps = Get-AppxPackage | Where-Object { $_.NonRemovable -eq $false -and $_.IsFramework -eq $false }
foreach ($app in $storeApps) {
    # PERBAIKAN DI SINI: Tambahkan [PSCustomObject]
    $allSoftware += [PSCustomObject]@{
        name    = $app.Name
        version = $app.Version
        vendor  = $app.Publisher
        Source  = "Store"
    }
}

# Scan Software (METODE 3: WMI)
Write-Host " [4/5] Memindai WMI Win32_Product..." -ForegroundColor Cyan

try {
    $wmiApps = Get-CimInstance -ClassName Win32_Product -ErrorAction SilentlyContinue
    foreach ($app in $wmiApps) {
        if ($app.Name) {
            $allSoftware += [PSCustomObject]@{
                name    = $app.Name
                version = if ($app.Version) { $app.Version } else { "Unknown" }
                vendor  = if ($app.Vendor) { $app.Vendor } else { "Unknown" }
                Source  = "WMI"
            }
        }
    }
}
catch {
    Write-Host "       Skip WMI (Not Available)." -ForegroundColor Yellow
}

# Pembersihan & Pengiriman
Write-Host "       Menggabungkan data..." -ForegroundColor Cyan

$uniqueSoftware = $allSoftware | Sort-Object -Property Name -Unique

Write-Host "       Total Unik: $($uniqueSoftware.Count) software ditemukan." -ForegroundColor Green
Write-Host " [5/5] Mengirim data ke Server..." -ForegroundColor Yellow

$payload = @{
    computer_name      = $computerName
    os_info            = $osName
    os_license_status  = $osStatus
    os_partial_key     = $partialKey
    installed_software = @($uniqueSoftware)
}

$jsonPayload = $payload | ConvertTo-Json -Depth 4

try {
    $response = Invoke-RestMethod -Uri $apiUrl -Method Post -Body $jsonPayload -ContentType "application/json"
    Write-Host " SUKSES! Server Response: $($response.message)" -ForegroundColor Green
    if ($response.inserted_count) {
        Write-Host " Jumlah Software Baru: $($response.inserted_count)"
    }
}
catch {
    Write-Host " GAGAL! Cek koneksi server." -ForegroundColor Red
    Write-Host " Error: $($_.Exception.Message)"
}

Write-Host "Tekan sembarang tombol untuk keluar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")