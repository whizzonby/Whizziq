@echo off
echo ========================================
echo WhizIQ Project Backup - Appointment Feature
echo ========================================
echo.
echo Choose backup type:
echo 1. Simple Backup (Files only)
echo 2. Complete Backup (Files + Database)
echo 3. PowerShell Complete Backup (Advanced)
echo.
set /p choice="Enter your choice (1-3): "

if "%choice%"=="1" (
    echo.
    echo Running Simple Backup...
    call backup_appointment_simple.bat
) else if "%choice%"=="2" (
    echo.
    echo Running Complete Backup with Database...
    powershell -ExecutionPolicy Bypass -File "backup_complete_appointment.ps1"
) else if "%choice%"=="3" (
    echo.
    echo Running Advanced PowerShell Backup...
    powershell -ExecutionPolicy Bypass -File "backup_appointment_feature.ps1" -IncludeDatabase -CompressBackup
) else (
    echo Invalid choice. Please run the script again.
    pause
    exit
)

echo.
echo Backup process completed!
pause
