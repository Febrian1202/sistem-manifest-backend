@echo off
title USN Manifest - Setup Penjadwalan Otomatis
echo ========================================================
echo     USN MANIFEST - PASANG PENJADWALAN OTOMATIS
echo ========================================================
echo Memeriksa hak akses Administrator...

net session >nul 2>&1
if %errorLevel% equ 0 goto :run_script

echo Meminta izin Administrator [UAC]...
set "SCRIPT_PATH=%~f0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Start-Process cmd.exe -ArgumentList ('/c ' + [char]34 + $env:SCRIPT_PATH + [char]34) -Verb RunAs"
if %errorLevel% neq 0 (
    echo.
    echo [!] GAGAL MEMINTA IZIN ADMINISTRATOR OTOMATIS.
    echo Silakan KLIK KANAN file ini lalu pilih 'Run as administrator'.
    echo.
    pause
)
exit /b

:run_script
cd /d "%~dp0"
if not exist "%~dp0setup_tasks.ps1" (
    echo [!] ERROR: File setup_tasks.ps1 tidak ditemukan!
    echo     Pastikan Anda sudah mengekstrak [Extract All] seluruh isi ZIP
    echo     ke dalam satu folder sebelum menjalankan file ini.
    echo.
    pause
    exit /b
)
if not exist "%~dp0config.json" (
    echo [!] ERROR: File config.json tidak ditemukan!
    echo     Pastikan file config.json berada di folder yang sama dengan skrip ini.
    echo.
    pause
    exit /b
)
echo Menjalankan instalasi jadwal pemindaian berkala...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup_tasks.ps1"
echo.
echo ========================================================
pause
