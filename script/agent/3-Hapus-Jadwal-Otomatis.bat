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
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Start-Process cmd -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
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
