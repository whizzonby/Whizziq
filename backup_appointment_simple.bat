@echo off
REM WhizIQ Project Backup Script - Appointment Feature (Simple Version)
REM This script creates a backup of the project with appointment functionality

setlocal enabledelayedexpansion

REM Create timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%-%MM%-%DD%_%HH%-%Min%-%Sec%"

echo.
echo ========================================
echo WhizIQ Project Backup - Appointment Feature
echo ========================================
echo Timestamp: %timestamp%
echo.

REM Create backup directory
set "backup_dir=.\backups"
set "backup_name=WhizIQ_AppointmentFeature_%timestamp%"
set "full_backup_path=%backup_dir%\%backup_name%"

if not exist "%backup_dir%" mkdir "%backup_dir%"
mkdir "%full_backup_path%"

echo Creating backup directory: %full_backup_path%
echo.

REM Copy essential directories
echo Copying application files...
xcopy /E /I /Y ".\app" "%full_backup_path%\app\"
xcopy /E /I /Y ".\resources" "%full_backup_path%\resources\"
xcopy /E /I /Y ".\database" "%full_backup_path%\database\"
xcopy /E /I /Y ".\config" "%full_backup_path%\config\"
xcopy /E /I /Y ".\routes" "%full_backup_path%\routes\"
xcopy /E /I /Y ".\tests" "%full_backup_path%\tests\"

REM Copy essential files
echo Copying configuration files...
copy /Y ".\composer.json" "%full_backup_path%\"
copy /Y ".\composer.lock" "%full_backup_path%\"
copy /Y ".\package.json" "%full_backup_path%\"
copy /Y ".\package-lock.json" "%full_backup_path%\"
copy /Y ".\vite.config.js" "%full_backup_path%\"
copy /Y ".\artisan" "%full_backup_path%\"
copy /Y ".\phpunit.xml" "%full_backup_path%\"
copy /Y ".\phpstan.neon" "%full_backup_path%\"
copy /Y ".\deploy.php" "%full_backup_path%\"
copy /Y ".\docker-compose.yml" "%full_backup_path%\"
copy /Y ".\.env.example" "%full_backup_path%\"

REM Create backup info file
echo Creating backup information...
(
echo {
echo   "backup_name": "%backup_name%",
echo   "timestamp": "%timestamp%",
echo   "project_name": "WhizIQ",
echo   "appointment_feature": true,
echo   "backup_type": "appointment_feature_backup",
echo   "created_by": "WhizIQ Backup Script",
echo   "version": "1.0",
echo   "description": "Complete backup of WhizIQ project with appointment functionality",
echo   "features_included": [
echo     "Appointment Calendar",
echo     "Livewire Components",
echo     "Database Models",
echo     "Views and Templates",
echo     "Configuration Files",
echo     "Test Suite"
echo   ],
echo   "appointment_components": [
echo     "App\\Livewire\\Calendar\\AppointmentCalendar.php",
echo     "App\\Models\\Appointment.php",
echo     "resources\\views\\livewire\\calendar\\appointment-calendar.blade.php"
echo   ]
echo }
) > "%full_backup_path%\backup_info.json"

echo.
echo ========================================
echo BACKUP SUMMARY
echo ========================================
echo Timestamp: %timestamp%
echo Project: WhizIQ
echo Feature: Appointment Calendar System
echo Location: %full_backup_path%
echo.
echo Appointment Components Backed Up:
echo   • AppointmentCalendar Livewire Component
echo   • Appointment Model with relationships
echo   • Calendar views and templates
echo   • Database migrations for appointments
echo   • Test files for appointment functionality
echo.
echo BACKUP COMPLETED SUCCESSFULLY!
echo.
echo To restore this backup:
echo   1. Copy files to your project directory
echo   2. Run 'composer install' and 'npm install'
echo   3. Run 'php artisan migrate' if needed
echo.
pause
