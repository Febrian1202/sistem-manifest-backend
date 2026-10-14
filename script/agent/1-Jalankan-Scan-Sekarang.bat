@echo off
title USN Manifest - System Scanner Agent
echo ========================================================
echo        USN MANIFEST - SCANNER AGENT (ONE-CLICK)
echo ========================================================
cd /d "%~dp0"
if not exist "%~dp0scanner.ps1" (
    echo [!] ERROR: File scanner.ps1 tidak ditemukan!
    echo     Pastikan Anda sudah mengekstrak (Extract All) seluruh isi ZIP
    echo     ke dalam satu folder sebelum menjalankan file ini.
    echo.
    pause
    exit /b
)
echo Memulai pemindaian perangkat keras dan perangkat lunak...
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scanner.ps1" -Mode scheduled

echo.
echo ========================================================
echo Pemindaian selesai. Jendela ini dapat ditutup.
echo ========================================================
pause
