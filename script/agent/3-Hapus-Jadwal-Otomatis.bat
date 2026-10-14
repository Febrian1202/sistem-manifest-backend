@echo off
title USN Manifest - Hapus Penjadwalan Otomatis
echo ========================================================
echo      USN MANIFEST - HAPUS PENJADWALAN OTOMATIS
echo ========================================================
echo Memeriksa hak akses Administrator...

net session >nul 2>&1
if %errorLevel% == 0 (
    goto :run_script
) else (
    echo Meminta izin Administrator (UAC)...
    set "SCRIPT_PATH=%~f0"
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath $env:SCRIPT_PATH -Verb RunAs"
    if %errorLevel% neq 0 (
        echo.
        echo [!] GAGAL MEMINTA IZIN ADMINISTRATOR OTOMATIS.
        echo Silakan KLIK KANAN file ini lalu pilih "Run as administrator".
        echo.
        pause
    )
    exit /b
)

:run_script
cd /d "%~dp0"
echo Menghapus tugas USN Manifest dari Windows Task Scheduler...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Unregister-ScheduledTask -TaskName 'USN-Manifest-DailyScan' -Confirm:$false -ErrorAction SilentlyContinue; Unregister-ScheduledTask -TaskName 'USN-Manifest-Polling' -Confirm:$false -ErrorAction SilentlyContinue; Write-Host ' [+] Penjadwalan USN Manifest berhasil dihapus.' -ForegroundColor Green"
echo.
echo ========================================================
pause
