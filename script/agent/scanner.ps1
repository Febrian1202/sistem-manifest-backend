param(
    [string]$Mode = "poll",
    [string]$ScanMode = ""
)

$AgentVersion = "1.1.3"

# ---------------------------------------------------------
# Konfigurasi API via config.json
# ---------------------------------------------------------
$configFile = "$PSScriptRoot\config.json"

if (-not (Test-Path $configFile)) {
    Write-Host " [!] File config.json tidak ditemukan di folder agent!" -ForegroundColor Red
    Write-Host "     Silakan pastikan config.json berada di folder yang sama dengan skrip ini." -ForegroundColor Yellow
    exit 1
}

try {
    $config = Get-Content $configFile -Raw | ConvertFrom-Json
    $baseUrl = $config.baseUrl
    $registrationKey = $config.registrationKey
    $laboratoryId = $config.laboratoryId
} catch {
    Write-Host " [!] Format config.json tidak valid (bukan JSON yang benar)." -ForegroundColor Red
    exit 1
}

$registerUrl    = "$baseUrl/agent/register"
$scanUrl        = "$baseUrl/scan-result"
$scanCommandUrl = "$baseUrl/agent/scan-command"
$tokenFile      = "$PSScriptRoot\agent_token.txt"

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "    USN MANIFEST - SYSTEM SCANNER AGENT   " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# ---------------------------------------------------------
# TAHAP 0: OTENTIKASI & REGISTRASI
# ---------------------------------------------------------
Write-Host " [0/3] Mengecek Identitas Perangkat..." -ForegroundColor Yellow

$sys  = Get-CimInstance Win32_ComputerSystem -ErrorAction SilentlyContinue
$bios = Get-CimInstance Win32_BIOS -ErrorAction SilentlyContinue

# Fallback Hostname: DNSHostName -> env:COMPUTERNAME -> Dns GetHostName -> UNKNOWN-PC
$rawHostname = if ($sys -and $sys.DNSHostName) { $sys.DNSHostName } elseif ($env:COMPUTERNAME) { $env:COMPUTERNAME } else { [System.Net.Dns]::GetHostName() }
$hostname    = if ($rawHostname) { ([string]$rawHostname).Trim() } else { "UNKNOWN-PC" }

# Fallback Serial Number
$serialNumber = if ($bios -and $bios.SerialNumber) { ([string]$bios.SerialNumber).Trim() } else { "Unknown" }

# Adapter jaringan: prioritaskan adapter dengan IP aktif, lalu coba adapter fisik apa pun
$net = Get-CimInstance Win32_NetworkAdapterConfiguration -Filter "IPEnabled = True" -ErrorAction SilentlyContinue | Select-Object -First 1
if (-not $net) {
    $net = Get-CimInstance Win32_NetworkAdapterConfiguration -ErrorAction SilentlyContinue | Where-Object { $_.MACAddress } | Select-Object -First 1
}

$macAddress = if ($net -and $net.MACAddress) { ([string]$net.MACAddress).Trim() } else { $null }

if (-not $macAddress) {
    try {
        $netAdapter = Get-NetAdapter -Physical -ErrorAction SilentlyContinue | Where-Object { $_.MacAddress } | Select-Object -First 1
        if (-not $netAdapter) {
            $netAdapter = Get-NetAdapter -ErrorAction SilentlyContinue | Where-Object { $_.MacAddress } | Select-Object -First 1
        }
        if ($netAdapter -and $netAdapter.MacAddress) {
            $macAddress = ([string]$netAdapter.MacAddress).Trim()
        }
    } catch {}
}

# Fallback IPv4
$ipv4 = $null
if ($net -and $net.IPAddress) {
    $ipv4 = ($net.IPAddress | Where-Object { $_ -match '^\d+\.\d+\.\d+\.\d+$' }) | Select-Object -First 1
}

if (-not (Test-Path $tokenFile)) {
    Write-Host " [!] Token tidak ditemukan. Melakukan registrasi..." -ForegroundColor Cyan

    $regPayload = @{
        mac_address   = $macAddress
        hostname      = $hostname
        serial_number = $serialNumber
        laboratory_id = $laboratoryId
    } | ConvertTo-Json

    try {
        $regHeaders = @{
            "X-Agent-Key"  = $registrationKey
            "Accept"       = "application/json"
        }
        $regResponse = Invoke-RestMethod -Uri $registerUrl -Method Post -Headers $regHeaders -Body $regPayload -ContentType "application/json"
        $token = ([string]$regResponse.token).Trim()
        $token | Out-File -FilePath $tokenFile -Encoding ascii
        Write-Host " [+] Registrasi berhasil. Token disimpan." -ForegroundColor Green
    } catch {
        Write-Host " [!] Registrasi GAGAL: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            try {
                $stream = $_.Exception.Response.GetResponseStream()
                if ($stream) {
                    $reader = New-Object System.IO.StreamReader($stream)
                    $errText = $reader.ReadToEnd()
                    Write-Host " Detail Error Server:`n$errText" -ForegroundColor Yellow
                }
            } catch {}
        }
        exit 1
    }
} else {
    $token = (Get-Content $tokenFile -Raw).Trim()
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
    Write-Host " [0.5/3] Mode Scan: Menjalankan Scan Lengkap..." -ForegroundColor Green
}

if ($ScanMode -ne "") {
    $actualScanMode = $ScanMode.ToLower()
} elseif ($Mode -eq "poll") {
    $actualScanMode = "on_demand"
} elseif ($Mode -eq "scheduled") {
    $actualScanMode = "scheduled"
} else {
    $actualScanMode = "manual"
}

$retryFile = "$env:TEMP\usn-manifest-last-scan.json"
$scanUuid = $null

if (Test-Path $retryFile) {
    try {
        $cachedData = Get-Content $retryFile -Raw | ConvertFrom-Json
        if ($cachedData.scan_uuid) {
            $scanUuid = $cachedData.scan_uuid
        }
    } catch {}
}

if (-not $scanUuid) {
    $scanUuid = [System.Guid]::NewGuid().ToString()
}

$isoUtcFormat = "yyyy'-'MM'-'dd'T'HH':'mm':'ss'Z'"
$clientStartedAt = (Get-Date).ToUniversalTime().ToString($isoUtcFormat, [System.Globalization.CultureInfo]::InvariantCulture)

# ---------------------------------------------------------
# TAHAP 1: SCAN HARDWARE & OS
# ---------------------------------------------------------
Write-Host " [1/3] Mengambil Spesifikasi Hardware..." -ForegroundColor Yellow

$cpu        = $null; $os = $null; $disk = $null
$ramGB      = 0; $diskTotal = 0; $diskFree = 0
$osStatus   = "Unknown"; $partialKey = "N/A"

try {
    $cpu         = Get-CimInstance Win32_Processor -ErrorAction SilentlyContinue | Select-Object -First 1
    $disk        = Get-CimInstance Win32_LogicalDisk -Filter "DeviceID = 'C:'" -ErrorAction SilentlyContinue
    $os          = Get-CimInstance Win32_OperatingSystem -ErrorAction SilentlyContinue
    $licenseInfo = Get-CimInstance SoftwareLicensingProduct -Filter "PartialProductKey IS NOT NULL" -ErrorAction SilentlyContinue |
                   Where-Object { $_.Name -like "*Windows*" } | Select-Object -First 1

    $ramGB     = if ($sys -and $sys.TotalPhysicalMemory) { [math]::Round([double]$sys.TotalPhysicalMemory / 1GB, 0) } else { 0 }
    $diskTotal = if ($disk -and $disk.Size) { [math]::Round([double]$disk.Size / 1GB, 0) } else { 0 }
    $diskFree  = if ($disk -and $disk.FreeSpace) { [math]::Round([double]$disk.FreeSpace / 1GB, 0) } else { 0 }

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
        $partialKey = if ($licenseInfo.PartialProductKey) { ([string]$licenseInfo.PartialProductKey).Trim() } else { "N/A" }
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
            try {
                if (-not $key.DisplayName) { continue }
                $displayName = ([string]$key.DisplayName).Trim()
                if ([string]::IsNullOrWhiteSpace($displayName)) { continue }

                $version = if ($key.DisplayVersion -ne $null) { ([string]$key.DisplayVersion).Trim() } else { $null }
                $vendor = if ($key.Publisher -ne $null) { ([string]$key.Publisher).Trim() } else { $null }
                $installDate = if ($key.InstallDate -ne $null) { ([string]$key.InstallDate).Trim() } else { $null }

                $allSoftware += [PSCustomObject]@{
                    name         = $displayName
                    version      = if ($version -ne "") { $version } else { $null }
                    vendor       = if ($vendor -ne "") { $vendor } else { $null }
                    install_date = if ($installDate -ne "") { $installDate } else { $null }
                }
            } catch {}
        }
    }
}

try {
    $storeApps = Get-AppxPackage -ErrorAction SilentlyContinue | Where-Object {
        $_.NonRemovable -eq $false -and
        $_.IsFramework -eq $false -and
        $_.Name -notmatch "^Microsoft\.(NET|VCLibs|UI|Windows|Xbox|Bing|Zune|549981C3F5F10)"
    }
    foreach ($app in $storeApps) {
        try {
            if (-not $app.Name) { continue }
            $appName = ([string]$app.Name).Trim()
            if ([string]::IsNullOrWhiteSpace($appName)) { continue }

            $appVer = if ($app.Version -ne $null) { ([string]$app.Version).Trim() } else { $null }
            $appPub = if ($app.Publisher -ne $null) { ([string]$app.Publisher).Trim() } else { $null }

            $allSoftware += [PSCustomObject]@{
                name         = $appName
                version      = if ($appVer -ne "") { $appVer } else { $null }
                vendor       = if ($appPub -ne "") { $appPub } else { $null }
                install_date = $null
            }
        } catch {}
    }
} catch {
    Write-Host " [!] Melewati scan Windows Store Apps." -ForegroundColor Gray
}

$uniqueSoftware = @($allSoftware | Sort-Object name -Unique)

# ---------------------------------------------------------
# TAHAP 3: KIRIM DATA
# ---------------------------------------------------------
Write-Host " [3/3] Mengirim Data ke Server..." -ForegroundColor Yellow

$clientCompletedAt = (Get-Date).ToUniversalTime().ToString($isoUtcFormat, [System.Globalization.CultureInfo]::InvariantCulture)

$payload = @{
    hostname            = $hostname
    scan_uuid           = $scanUuid
    scan_mode           = $actualScanMode
    client_started_at   = $clientStartedAt
    client_completed_at = $clientCompletedAt
    agent_version       = $AgentVersion
    processor           = if ($cpu -and $cpu.Name) { ([string]$cpu.Name).Trim() } else { "Unknown" }
    ram_gb              = [int]$ramGB
    disk_total_gb       = [int]$diskTotal
    disk_free_gb        = [int]$diskFree
    manufacturer        = if ($sys -and $sys.Manufacturer) { ([string]$sys.Manufacturer).Trim() } else { "Unknown" }
    model               = if ($sys -and $sys.Model) { ([string]$sys.Model).Trim() } else { "Unknown" }
    serial_number       = $serialNumber
    ip_address          = if ($ipv4) { ([string]$ipv4).Trim() } else { $null }
    mac_address         = if ($macAddress) { ([string]$macAddress).Trim() } else { $null }
    os_name             = if ($os -and $os.Caption) { ([string]$os.Caption).Trim() } else { "Unknown" }
    os_version          = if ($os -and $os.Version) { ([string]$os.Version).Trim() } else { "Unknown" }
    os_architecture     = if ($os -and $os.OSArchitecture) { ([string]$os.OSArchitecture).Trim() } else { "Unknown" }
    os_license_status   = $osStatus
    os_partial_key      = $partialKey
    installed_software  = @($uniqueSoftware)
}

$jsonPayload = $payload | ConvertTo-Json -Depth 5

# Simpan state untuk retry sebelum kirim
try {
    $jsonPayload | Out-File -FilePath $retryFile -Encoding utf8
} catch {}

try {
    $response = Invoke-RestMethod -Uri $scanUrl -Method Post -Headers $headers -Body $jsonPayload -ContentType "application/json"
    if (Test-Path $retryFile) {
        Remove-Item $retryFile -ErrorAction SilentlyContinue
    }
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
        Write-Host "`n [GAGAL] Pengiriman data ke server gagal ($statusCode)." -ForegroundColor Red
        Write-Host " Error: $($_.Exception.Message)"
        if ($_.Exception.Response) {
            try {
                $stream = $_.Exception.Response.GetResponseStream()
                if ($stream) {
                    $reader = New-Object System.IO.StreamReader($stream)
                    $errorBody = $reader.ReadToEnd()
                    Write-Host " Detail Error Server:`n$errorBody" -ForegroundColor Yellow
                }
            } catch {}
        }
        Write-Warning " Pengunggahan hasil scan gagal, berkas retry tersimpan dengan UUID: $scanUuid"
    }
}

if ($actualScanMode -eq "manual" -and [Environment]::UserInteractive) {
    # Mode interaktif mandiri
    Write-Host "`n Selesai."
} else {
    Write-Host "`n Selesai."
}
