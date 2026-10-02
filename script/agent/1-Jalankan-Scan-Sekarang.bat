@echo off
title USN Manifest - System Scanner Agent
echo ========================================================
echo        USN MANIFEST - SCANNER AGENT (ONE-CLICK)
echo ========================================================
echo Memulai pemindaian perangkat keras dan perangkat lunak...
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scanner.ps1" -Mode scheduled

echo.
echo ========================================================
echo Pemindaian selesai. Jendela ini dapat ditutup.
echo ========================================================
pause
