# Simple WhizIQ Backup Script
param(
    [string]$BackupDir = ".\backups",
    [string]$DateFormat = "yyyy-MM-dd_HH-mm-ss"
)

# Create backup directory
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force
    Write-Host "Created backup directory: $BackupDir" -ForegroundColor Green
}

# Get timestamp
$timestamp = Get-Date -Format $DateFormat
$backupName = "whiziq_backup_$timestamp"
$backupPath = "$BackupDir\$backupName"

Write-Host "Starting WhizIQ backup..." -ForegroundColor Yellow
Write-Host "Backup location: $backupPath" -ForegroundColor Cyan

try {
    # Create backup directory
    New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
    
    # Copy application files (excluding unnecessary directories)
    Write-Host "Copying application files..." -ForegroundColor Yellow
    
    $excludeDirs = @("node_modules", "vendor", "storage\logs", "storage\framework\cache", "storage\framework\sessions", "storage\framework\views", "bootstrap\cache", ".git", "backups")
    
    # Copy main directories
    $copyItems = @(
        "app",
        "config", 
        "database",
        "resources",
        "routes",
        "public",
        "storage\app",
        "tests",
        "lang"
    )
    
    foreach ($item in $copyItems) {
        if (Test-Path $item) {
            $destPath = Join-Path $backupPath $item
            $destParent = Split-Path $destPath -Parent
            if (!(Test-Path $destParent)) {
                New-Item -ItemType Directory -Path $destParent -Force | Out-Null
            }
            Copy-Item -Path $item -Destination $destPath -Recurse -Force
            Write-Host "  Copied: $item" -ForegroundColor Green
        }
    }
    
    # Copy important files
    $importantFiles = @(
        "composer.json",
        "composer.lock", 
        "package.json",
        "package-lock.json",
        "artisan",
        "vite.config.js",
        "phpunit.xml",
        "README.md",
        "LICENSE"
    )
    
    foreach ($file in $importantFiles) {
        if (Test-Path $file) {
            Copy-Item -Path $file -Destination $backupPath -Force
            Write-Host "  Copied: $file" -ForegroundColor Green
        }
    }
    
    # Create .env.example if it doesn't exist
    $envExamplePath = Join-Path $backupPath ".env.example"
    if (!(Test-Path ".env.example")) {
        $envExampleContent = @"
APP_NAME=WhizIQ
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whiziq
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
"@
        $envExampleContent | Out-File -FilePath $envExamplePath -Encoding UTF8
        Write-Host "  Created: .env.example" -ForegroundColor Green
    } else {
        Copy-Item -Path ".env.example" -Destination $envExamplePath -Force
        Write-Host "  Copied: .env.example" -ForegroundColor Green
    }
    
    # Create backup info
    $backupInfoPath = Join-Path $backupPath "backup_info.txt"
    $backupInfo = @"
WhizIQ Backup Information
========================
Backup Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Backup Type: File System Backup
Source: WhizIQ Laravel Application

Contents:
- Application code (app/)
- Configuration files (config/)
- Database migrations (database/)
- Resources (resources/)
- Routes (routes/)
- Public assets (public/)
- Storage (storage/app/)
- Tests (tests/)
- Language files (lang/)
- Dependencies (composer.json, package.json)

Restore Instructions:
1. Copy all files to your new Laravel project directory
2. Run: composer install
3. Run: npm install
4. Copy .env.example to .env and configure
5. Run: php artisan key:generate
6. Run: php artisan migrate
7. Run: php artisan storage:link
"@
    $backupInfo | Out-File -FilePath $backupInfoPath -Encoding UTF8
    
    # Calculate backup size
    $totalSize = (Get-ChildItem $backupPath -Recurse | Measure-Object -Property Length -Sum).Sum
    $totalSizeMB = [math]::Round($totalSize / 1MB, 2)
    
    Write-Host ""
    Write-Host "Backup completed successfully!" -ForegroundColor Green
    Write-Host "Backup location: $backupPath" -ForegroundColor Cyan
    Write-Host "Backup size: $totalSizeMB MB" -ForegroundColor Cyan
    Write-Host "Files backed up: $((Get-ChildItem $backupPath -Recurse).Count)" -ForegroundColor Cyan
    
} catch {
    Write-Error "Backup failed: $($_.Exception.Message)"
    exit 1
}
