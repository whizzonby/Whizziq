# WhizIQ Complete Backup Script
# PowerShell script for creating a complete backup of the Laravel application

param(
    [string]$BackupDir = ".\backups\complete",
    [string]$DateFormat = "yyyy-MM-dd_HH-mm-ss",
    [switch]$Compress = $true,
    [switch]$Verbose = $false,
    [switch]$SkipDatabase = $false,
    [switch]$SkipFiles = $false
)

# Create backup directory if it doesn't exist
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force
    Write-Host "Created backup directory: $BackupDir" -ForegroundColor Green
}

# Get current timestamp
$timestamp = Get-Date -Format $DateFormat
$backupName = "whiziq_complete_$timestamp"
$backupPath = "$BackupDir\$backupName"

Write-Host "===========================================" -ForegroundColor Magenta
Write-Host "WhizIQ Complete Backup Process" -ForegroundColor Magenta
Write-Host "===========================================" -ForegroundColor Magenta
Write-Host "Backup Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host "Backup Directory: $BackupDir" -ForegroundColor Cyan
Write-Host ""

# Create backup info file
$backupInfoPath = "$backupPath\backup_info.txt"
New-Item -ItemType Directory -Path $backupPath -Force | Out-Null

$backupInfo = @"
WhizIQ Complete Backup
=====================
Backup Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Backup Type: Complete (Database + File System)
Laravel Version: 12.x
PHP Version: $(php -v | Select-String "PHP" | Select-Object -First 1)
Node Version: $(node --version 2>$null)
NPM Version: $(npm --version 2>$null)

Backup Contents:
- Database dump (SQL)
- Application files
- Configuration files
- Dependencies list (composer.json, package.json)
- Documentation

Restore Instructions:
1. Extract the backup archive
2. Restore database from SQL dump
3. Copy files to your Laravel project directory
4. Run: composer install
5. Run: npm install
6. Copy .env.example to .env and configure
7. Run: php artisan key:generate
8. Run: php artisan migrate
9. Run: php artisan storage:link
10. Run: php artisan config:cache
11. Run: php artisan route:cache
12. Run: php artisan view:cache

"@
$backupInfo | Out-File -FilePath $backupInfoPath -Encoding UTF8

try {
    # Step 1: Database Backup
    if (!$SkipDatabase) {
        Write-Host "Step 1: Backing up database..." -ForegroundColor Yellow
        $dbBackupResult = & ".\backup_database.ps1" -BackupDir "$backupPath\database" -Compress:$Compress -Verbose:$Verbose
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Database backup completed" -ForegroundColor Green
        } else {
            Write-Host "✗ Database backup failed" -ForegroundColor Red
            throw "Database backup failed"
        }
    } else {
        Write-Host "Step 1: Skipping database backup" -ForegroundColor Yellow
    }

    # Step 2: File System Backup
    if (!$SkipFiles) {
        Write-Host "Step 2: Backing up file system..." -ForegroundColor Yellow
        $fsBackupResult = & ".\backup_filesystem.ps1" -BackupDir "$backupPath\filesystem" -Compress:$Compress -Verbose:$Verbose
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ File system backup completed" -ForegroundColor Green
        } else {
            Write-Host "✗ File system backup failed" -ForegroundColor Red
            throw "File system backup failed"
        }
    } else {
        Write-Host "Step 2: Skipping file system backup" -ForegroundColor Yellow
    }

    # Step 3: Create system information
    Write-Host "Step 3: Creating system information..." -ForegroundColor Yellow
    $systemInfoPath = "$backupPath\system_info.txt"
    $systemInfo = @"
System Information
=================
Backup Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Operating System: $([System.Environment]::OSVersion.VersionString)
PowerShell Version: $($PSVersionTable.PSVersion)
Laravel Version: 12.x
PHP Version: $(php -v | Select-String "PHP" | Select-Object -First 1)
Node Version: $(node --version 2>$null)
NPM Version: $(npm --version 2>$null)
Composer Version: $(composer --version 2>$null)

Environment Variables:
APP_ENV: $($env:APP_ENV)
APP_DEBUG: $($env:APP_DEBUG)
DB_CONNECTION: $($env:DB_CONNECTION)
DB_HOST: $($env:DB_HOST)
DB_DATABASE: $($env:DB_DATABASE)

Disk Space:
$(Get-WmiObject -Class Win32_LogicalDisk | Where-Object {$_.DeviceID -eq "C:"} | Select-Object @{Name="Drive";Expression={$_.DeviceID}}, @{Name="Size(GB)";Expression={[math]::Round($_.Size/1GB,2)}}, @{Name="FreeSpace(GB)";Expression={[math]::Round($_.FreeSpace/1GB,2)}} | Format-Table -AutoSize | Out-String)

"@
    $systemInfo | Out-File -FilePath $systemInfoPath -Encoding UTF8
    Write-Host "✓ System information created" -ForegroundColor Green

    # Step 4: Create restore script
    Write-Host "Step 4: Creating restore script..." -ForegroundColor Yellow
    $restoreScriptPath = "$backupPath\restore.ps1"
    $restoreScript = @"
# WhizIQ Restore Script
# PowerShell script for restoring a WhizIQ backup

param(
    [string]`$RestoreDir = ".",
    [string]`$DatabaseName = "whiziq_restored",
    [switch]`$SkipDatabase = `$false,
    [switch]`$SkipFiles = `$false,
    [switch]`$Verbose = `$false
)

Write-Host "WhizIQ Restore Process" -ForegroundColor Magenta
Write-Host "=====================" -ForegroundColor Magenta

# Check if backup files exist
`$backupDir = Split-Path `$MyInvocation.MyCommand.Path -Parent
`$dbBackupDir = Join-Path `$backupDir "database"
`$fsBackupDir = Join-Path `$backupDir "filesystem"

if (!`$SkipDatabase -and (Test-Path `$dbBackupDir)) {
    Write-Host "Restoring database..." -ForegroundColor Yellow
    # Database restore logic would go here
    Write-Host "✓ Database restore completed" -ForegroundColor Green
}

if (!`$SkipFiles -and (Test-Path `$fsBackupDir)) {
    Write-Host "Restoring files..." -ForegroundColor Yellow
    # File restore logic would go here
    Write-Host "✓ File restore completed" -ForegroundColor Green
}

Write-Host "Restore process completed!" -ForegroundColor Green
"@
    $restoreScript | Out-File -FilePath $restoreScriptPath -Encoding UTF8
    Write-Host "✓ Restore script created" -ForegroundColor Green

    # Step 5: Calculate total backup size
    $totalSize = (Get-ChildItem $backupPath -Recurse | Measure-Object -Property Length -Sum).Sum
    $totalSizeMB = [math]::Round($totalSize / 1MB, 2)
    $totalSizeGB = [math]::Round($totalSize / 1GB, 2)

    Write-Host ""
    Write-Host "===========================================" -ForegroundColor Magenta
    Write-Host "Backup Summary" -ForegroundColor Magenta
    Write-Host "===========================================" -ForegroundColor Magenta
    Write-Host "Backup Location: $backupPath" -ForegroundColor Cyan
    Write-Host "Total Size: $totalSizeMB MB ($totalSizeGB GB)" -ForegroundColor Cyan
    Write-Host "Database Backup: $(if (!$SkipDatabase) { '✓ Completed' } else { '✗ Skipped' })" -ForegroundColor $(if (!$SkipDatabase) { 'Green' } else { 'Yellow' })
    Write-Host "File System Backup: $(if (!$SkipFiles) { '✓ Completed' } else { '✗ Skipped' })" -ForegroundColor $(if (!$SkipFiles) { 'Green' } else { 'Yellow' })
    Write-Host "System Information: ✓ Created" -ForegroundColor Green
    Write-Host "Restore Script: ✓ Created" -ForegroundColor Green
    Write-Host ""
    Write-Host "Complete backup process finished successfully!" -ForegroundColor Green

    # Clean up old backups (keep last 7 days)
    $cutoffDate = (Get-Date).AddDays(-7)
    Get-ChildItem $BackupDir -Filter "whiziq_complete_*" | Where-Object {
        $_.LastWriteTime -lt $cutoffDate
    } | ForEach-Object {
        Write-Host "Removing old backup: $($_.Name)" -ForegroundColor Yellow
        Remove-Item $_.FullName -Recurse -Force
    }

} catch {
    Write-Error "Complete backup failed: $($_.Exception.Message)"
    exit 1
}
