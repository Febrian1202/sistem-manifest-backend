param(
    [string]$Mode = "poll",
    [string]$ScanMode = ""
)

$AgentVersion = "1.2.3"

# ---------------------------------------------------------
# Konfigurasi API via config.json
# ---------------------------------------------------------
$configFile      = "$PSScriptRoot\config.json"
$logFile         = "$PSScriptRoot\scan_agent.log"
$payloadDumpFile = "$PSScriptRoot\last_scan_payload.json"

function Write-AgentLog {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    $timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    $logEntry = "[$timestamp] [$Level] $Message"
    try {
        Add-Content -Path $logFile -Value $logEntry -Encoding utf8 -ErrorAction SilentlyContinue
    } catch {}
}

if (-not (Test-Path $configFile)) {
    Write-Host " [!] File config.json tidak ditemukan di folder agent!" -ForegroundColor Red
    Write-Host "     Silakan pastikan config.json berada di folder yang sama dengan skrip ini." -ForegroundColor Yellow
    Write-AgentLog "File config.json tidak ditemukan di $configFile" -Level "ERROR"
    exit 1
}

try {
    $config = Get-Content $configFile -Raw | ConvertFrom-Json
    $baseUrl = $config.baseUrl
    $registrationKey = $config.registrationKey
    $laboratoryId = $config.laboratoryId
} catch {
    Write-Host " [!] Format config.json tidak valid (bukan JSON yang benar)." -ForegroundColor Red
    Write-AgentLog "Format config.json tidak valid: $($_.Exception.Message)" -Level "ERROR"
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
Write-AgentLog "Menjalankan USN Manifest Scanner v$AgentVersion (Mode: $Mode, ScanMode: $ScanMode)"

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

# ---------------------------------------------------------
# DETEKSI ADAPTER JARINGAN (MAC ADDRESS & IP) MULTI-TIER
# ---------------------------------------------------------
$macAddress = $null
$net = $null

# Tier 1: Win32_NetworkAdapterConfiguration dengan IP aktif DAN MACAddress valid terisi
$net = Get-CimInstance Win32_NetworkAdapterConfiguration -ErrorAction SilentlyContinue |
       Where-Object { $_.IPEnabled -and $_.MACAddress -and ($_.MACAddress -match '^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$') } |
       Select-Object -First 1

# Tier 2: Win32_NetworkAdapterConfiguration apa pun yang memiliki MACAddress valid
if (-not $net) {
    $net = Get-CimInstance Win32_NetworkAdapterConfiguration -ErrorAction SilentlyContinue |
           Where-Object { $_.MACAddress -and ($_.MACAddress -match '^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$') } |
           Select-Object -First 1
}

if ($net -and $net.MACAddress) {
    $macAddress = ([string]$net.MACAddress).Trim() -replace '-', ':'
}

# Tier 3: Win32_NetworkAdapter (Hardware adapter langsung, prioritaskan status Connected = 2)
if (-not $macAddress) {
    $hwAdapter = Get-CimInstance Win32_NetworkAdapter -ErrorAction SilentlyContinue |
                 Where-Object { $_.MACAddress -and ($_.MACAddress -match '^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$') -and $_.NetConnectionStatus -eq 2 } |
                 Select-Object -First 1
    if (-not $hwAdapter) {
        $hwAdapter = Get-CimInstance Win32_NetworkAdapter -ErrorAction SilentlyContinue |
                     Where-Object { $_.MACAddress -and ($_.MACAddress -match '^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$') } |
                     Select-Object -First 1
    }
    if ($hwAdapter -and $hwAdapter.MACAddress) {
        $macAddress = ([string]$hwAdapter.MACAddress).Trim() -replace '-', ':'
    }
}

# Tier 4: Native .NET NetworkInterface (Bekerja tanpa hak Administrator dan tanpa ketergantungan WMI/CIM)
if (-not $macAddress) {
    try {
        $nics = [System.Net.NetworkInformation.NetworkInterface]::GetAllNetworkInterfaces() |
                Where-Object {
                    $_.NetworkInterfaceType -ne [System.Net.NetworkInformation.NetworkInterfaceType]::Loopback -and
                    $_.NetworkInterfaceType -ne [System.Net.NetworkInformation.NetworkInterfaceType]::Tunnel -and
                    $_.GetPhysicalAddress().ToString().Length -eq 12 -and
                    $_.GetPhysicalAddress().ToString() -ne "000000000000"
                }
        $activeNic = $nics | Where-Object { $_.OperationalStatus -eq [System.Net.NetworkInformation.OperationalStatus]::Up } | Select-Object -First 1
        if (-not $activeNic) {
            $activeNic = $nics | Select-Object -First 1
        }
        if ($activeNic) {
            $rawPhys = $activeNic.GetPhysicalAddress().ToString()
            $macAddress = ($rawPhys -replace '(.{2})(?!$)', '$1:').ToUpper()
        }
    } catch {}
}

# Tier 5: Get-NetAdapter (khusus jika sesi berjalan dengan elevasi Admin)
if (-not $macAddress) {
    try {
        $netAdapter = Get-NetAdapter -Physical -ErrorAction SilentlyContinue |
                      Where-Object { $_.MacAddress -and ($_.MacAddress -match '^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$') } |
                      Select-Object -First 1
        if (-not $netAdapter) {
            $netAdapter = Get-NetAdapter -ErrorAction SilentlyContinue |
                          Where-Object { $_.MacAddress -and ($_.MacAddress -match '^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$') } |
                          Select-Object -First 1
        }
        if ($netAdapter -and $netAdapter.MacAddress) {
            $macAddress = ([string]$netAdapter.MacAddress).Trim() -replace '-', ':'
        }
    } catch {}
}

# Tier 6: Utilitas bawaan Windows getmac.exe
if (-not $macAddress) {
    try {
        $getmacOut = getmac.exe /fo csv /nh 2>$null
        foreach ($line in $getmacOut) {
            if ($line -match '"([0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2})"') {
                $candidate = $matches[1] -replace '-', ':'
                if ($candidate -ne "00:00:00:00:00:00" -and $candidate -notmatch '^FF:FF:FF') {
                    $macAddress = $candidate.ToUpper()
                    break
                }
            }
        }
    } catch {}
}

# Tier 7: Parsing ipconfig /all
if (-not $macAddress) {
    try {
        $ipconfigOut = ipconfig.exe /all 2>$null
        foreach ($line in $ipconfigOut) {
            if ($line -match '([0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2}-[0-9A-Fa-f]{2})') {
                $candidate = $matches[1] -replace '-', ':'
                if ($candidate -ne "00:00:00:00:00:00") {
                    $macAddress = $candidate.ToUpper()
                    break
                }
            }
        }
    } catch {}
}

# Normalisasi akhir MAC address ke format standar XX:XX:XX:XX:XX:XX (uppercase)
if ($macAddress) {
    $macAddress = $macAddress.Trim().ToUpper() -replace '-', ':'
}

# Fallback IPv4
$ipv4 = $null
if ($net -and $net.IPAddress) {
    $ipv4 = ($net.IPAddress | Where-Object { $_ -match '^\d+\.\d+\.\d+\.\d+$' -and $_ -ne '127.0.0.1' }) | Select-Object -First 1
}
if (-not $ipv4) {
    try {
        $ipObj = [System.Net.Dns]::GetHostAddresses([System.Net.Dns]::GetHostName()) |
                 Where-Object { $_.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork -and $_.ToString() -ne '127.0.0.1' } |
                 Select-Object -First 1
        if ($ipObj) {
            $ipv4 = $ipObj.ToString()
        }
    } catch {}
}

if (-not (Test-Path $tokenFile)) {
    Write-Host " [!] Token tidak ditemukan. Melakukan registrasi..." -ForegroundColor Cyan
    Write-AgentLog "Token tidak ditemukan. Memulai registrasi ke $registerUrl untuk host '$hostname' (MAC: '$macAddress')"

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
        $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
        $regBytes = $utf8NoBom.GetBytes($regPayload)
        $regResponse = Invoke-RestMethod -Uri $registerUrl -Method Post -Headers $regHeaders -Body $regBytes -ContentType "application/json; charset=utf-8"
        $token = ([string]$regResponse.token).Trim()
        $token | Out-File -FilePath $tokenFile -Encoding ascii
        Write-Host " [+] Registrasi berhasil. Token disimpan." -ForegroundColor Green
        Write-AgentLog "Registrasi BERHASIL. Token disimpan di $tokenFile"
    } catch {
        Write-Host " [!] Registrasi GAGAL: $($_.Exception.Message)" -ForegroundColor Red
        $errText = ""
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
        Write-AgentLog "Registrasi GAGAL: $($_.Exception.Message). Detail Server: $errText" -Level "ERROR"
        exit 1
    }
} else {
    $token = (Get-Content $tokenFile -Raw).Trim()
    Write-Host " [+] Token ditemukan. Menggunakan identitas tersimpan." -ForegroundColor Green
    Write-AgentLog "Token tersimpan ditemukan di $tokenFile. Melanjutkan dengan identitas terdaftar."
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
            Write-AgentLog "Mode poll: Server merespon should_scan = false. Keluar tanpa melakukan scan."
            exit 0
        }
        Write-Host " [+] Ada permintaan scan. Melanjutkan..." -ForegroundColor Green
        Write-AgentLog "Mode poll: Server meminta scan aktif (should_scan = true). Melanjutkan..."
    } catch {
        Write-Host " [WARN] Gagal cek perintah: $($_.Exception.Message)" -ForegroundColor DarkYellow
        Write-AgentLog "Mode poll: Gagal memeriksa perintah scan dari server: $($_.Exception.Message)" -Level "WARN"
        exit 0
    }
} else {
    Write-Host " [0.5/3] Mode Scan: Menjalankan Scan Lengkap..." -ForegroundColor Green
    Write-AgentLog "Mode manual/scheduled: Memulai pemindaian lengkap..."
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

$retryFile = "$PSScriptRoot\.usn-manifest-last-scan.json"
$legacyRetryFile = "$env:TEMP\usn-manifest-last-scan.json"
$scanUuid = $null

if ([System.IO.File]::Exists($retryFile)) {
    try {
        $cachedData = [System.IO.File]::ReadAllText($retryFile, [System.Text.Encoding]::UTF8) | ConvertFrom-Json
        if ($cachedData.scan_uuid) {
            $scanUuid = $cachedData.scan_uuid
        }
    } catch {}
} elseif ([System.IO.File]::Exists($legacyRetryFile)) {
    try {
        $cachedData = [System.IO.File]::ReadAllText($legacyRetryFile, [System.Text.Encoding]::UTF8) | ConvertFrom-Json
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
Write-AgentLog "Selesai memindai aplikasi: $($uniqueSoftware.Count) software unik terdeteksi."

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
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

# Simpan salinan payload lokal ke folder agent untuk kemudahan inspeksi & debugging (UTF-8 tanpa BOM)
try {
    [System.IO.File]::WriteAllText($payloadDumpFile, $jsonPayload, $utf8NoBom)
    Write-Host " [+] Salinan payload tersimpan di: last_scan_payload.json" -ForegroundColor Cyan
    Write-AgentLog "Payload hasil scan berhasil disimpan di $payloadDumpFile ($($uniqueSoftware.Count) software)"
} catch {
    Write-Host " [WARN] Gagal menyimpan salinan payload lokal: $($_.Exception.Message)" -ForegroundColor DarkYellow
    Write-AgentLog "Gagal menyimpan payload lokal ke $($payloadDumpFile): $($_.Exception.Message)" -Level "WARN"
}

# Simpan state untuk retry sebelum kirim (UTF-8 tanpa BOM)
try {
    [System.IO.File]::WriteAllText($retryFile, $jsonPayload, $utf8NoBom)
} catch {}

$uploadSuccess = $false
$response = $null
$statusCode = $null
$errorBody = ""

try {
    $bodyBytes = $utf8NoBom.GetBytes($jsonPayload)
    $response = Invoke-RestMethod -Uri $scanUrl -Method Post -Headers $headers -Body $bodyBytes -ContentType "application/json; charset=utf-8"
    $uploadSuccess = $true
} catch {
    $statusCode = $_.Exception.Response.StatusCode
    if ($_.Exception.Response) {
        try {
            $stream = $_.Exception.Response.GetResponseStream()
            if ($stream) {
                $reader = New-Object System.IO.StreamReader($stream)
                $errorBody = $reader.ReadToEnd()
            }
        } catch {}
    }

    if ($statusCode -eq "Unauthorized") {
        Write-Host "`n [!] Token tidak valid atau dicabut." -ForegroundColor Red
        if ([System.IO.File]::Exists($tokenFile)) {
            try { [System.IO.File]::Delete($tokenFile) } catch {}
        }
        Write-Host " Token dihapus. Jalankan ulang script untuk registrasi ulang." -ForegroundColor Yellow
        Write-AgentLog "Pengiriman hasil scan GAGAL [Unauthorized]: Token tidak valid/dicabut. Token dihapus." -Level "ERROR"
    } else {
        Write-Host "`n [GAGAL] Pengiriman data ke server gagal ($statusCode)." -ForegroundColor Red
        Write-Host " Error: $($_.Exception.Message)"
        if ($errorBody -ne "") {
            Write-Host " Detail Error Server:`n$errorBody" -ForegroundColor Yellow
        }
        Write-Warning " Pengunggahan hasil scan gagal, berkas retry tersimpan dengan UUID: $scanUuid"
        Write-AgentLog "Pengiriman hasil scan GAGAL [$statusCode]: $($_.Exception.Message). Detail Server: $errorBody" -Level "ERROR"
    }
}

if ($uploadSuccess) {
    # Hapus file cache retry karena data berhasil diterima server (gunakan [System.IO.File] agar kebal bug path 8.3)
    if ([System.IO.File]::Exists($retryFile)) {
        try { [System.IO.File]::Delete($retryFile) } catch {}
    }
    if ([System.IO.File]::Exists($legacyRetryFile)) {
        try { [System.IO.File]::Delete($legacyRetryFile) } catch {}
    }

    Write-Host "`n [SUKSES]" -ForegroundColor Green
    Write-Host " Server  : $($response.message)"
    Write-Host " Komputer: $($response.computer)"
    Write-AgentLog "Pengiriman hasil scan BERHASIL. Pesan Server: $($response.message) (Komputer: $($response.computer), UUID: $scanUuid)"
}

if ($actualScanMode -eq "manual" -and [Environment]::UserInteractive) {
    # Mode interaktif mandiri
    Write-Host "`n Selesai."
} else {
    Write-Host "`n Selesai."
}
