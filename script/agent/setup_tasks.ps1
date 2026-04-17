# USN Manifest - Task Scheduler Setup Script
# IT admin: Sesuaikan path scanner.ps1 di bawah ini
$agentPath = "C:\Scripts\USNManifest\scanner.ps1"

# 1. Cek Hak Akses Administrator
$currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "`n [!] ERROR: Script ini harus dijalankan dengan hak akses Administrator!" -ForegroundColor Red
    Write-Host " Harap buka PowerShell sebagai Administrator.`n"
    exit
}

Write-Host "`n==========================================" -ForegroundColor Cyan
Write-Host "   SETUP PENJADWALAN AGENT USN MANIFEST   " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

try {
    # Persiapan Principal & Settings (Umum untuk kedua task)
    $taskPrincipal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    $taskSettings  = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

    # -------------------------------------------------------------
    # TASK 1: USN-Manifest-DailyScan (Harian jam 08:00)
    # -------------------------------------------------------------
    $task1Name = "USN-Manifest-DailyScan"
    Write-Host "`n [1/2] Mendaftarkan task: $task1Name..." -ForegroundColor Yellow

    # Unregister jika sudah ada
    Unregister-ScheduledTask -TaskName $task1Name -Confirm:$false -ErrorAction SilentlyContinue

    $trigger1 = New-ScheduledTaskTrigger -Daily -At 08:00AM
    $action1  = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File ""$agentPath"" -Mode scheduled"

    Register-ScheduledTask -TaskName $task1Name -Action $action1 -Trigger $trigger1 -Principal $taskPrincipal -Settings $taskSettings
    Write-Host " [+] Task berhasil didaftarkan: Berjalan setiap hari jam 08:00 AM." -ForegroundColor Green


    # -------------------------------------------------------------
    # TASK 2: USN-Manifest-Polling (Setiap 15 Menit)
    # -------------------------------------------------------------
    $task2Name = "USN-Manifest-Polling"
    Write-Host "`n [2/2] Mendaftarkan task: $task2Name..." -ForegroundColor Yellow

    # Unregister jika sudah ada
    Unregister-ScheduledTask -TaskName $task2Name -Confirm:$false -ErrorAction SilentlyContinue

    # Trigger: Mulai sekarang, ulangi setiap 15 menit selamanya
    $trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 15)
    $action2  = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File ""$agentPath"" -Mode poll"

    Register-ScheduledTask -TaskName $task2Name -Action $action2 -Trigger $trigger2 -Principal $taskPrincipal -Settings $taskSettings
    Write-Host " [+] Task berhasil didaftarkan: Berjalan setiap 15 menit." -ForegroundColor Green

    Write-Host "`n [SELESAI] Semua task berhasil dikonfigurasi.`n" -ForegroundColor Cyan

} catch {
    Write-Host "`n [GAGAL] Terjadi kesalahan saat mendaftarkan task:" -ForegroundColor Red
    Write-Host " $($_.Exception.Message)"
}

# =================================================================
# PETUNJUK VERIFIKASI (Untuk IT Admin):
# 1. Buka 'Task Scheduler' (taskschd.msc)
# 2. Cari folder 'Task Scheduler Library'
# 3. Pastikan ada task: 'USN-Manifest-DailyScan' dan 'USN-Manifest-Polling'
# 4. Klik kanan > 'Run' untuk mencoba secara manual
#
# CARA MENGHAPUS (Unregister):
# Unregister-ScheduledTask -TaskName "USN-Manifest-DailyScan" -Confirm:$false
# Unregister-ScheduledTask -TaskName "USN-Manifest-Polling" -Confirm:$false
# =================================================================
