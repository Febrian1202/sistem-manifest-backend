@echo off
title USN Manifest - Setup Penjadwalan Otomatis
echo ========================================================
echo     USN MANIFEST - PASANG PENJADWALAN OTOMATIS
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
echo Menjalankan instalasi jadwal pemindaian berkala...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup_tasks.ps1"
echo.
echo ========================================================
pause
