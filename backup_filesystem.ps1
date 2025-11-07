# WhizIQ File System Backup Script
# PowerShell script for backing up the Laravel application files

param(
    [string]$BackupDir = ".\backups\filesystem",
    [string]$DateFormat = "yyyy-MM-dd_HH-mm-ss",
    [string]$SourceDir = ".",
    [switch]$Compress = $true,
    [switch]$Verbose = $false
)

# Create backup directory if it doesn't exist
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force
    Write-Host "Created backup directory: $BackupDir" -ForegroundColor Green
}

# Get current timestamp
$timestamp = Get-Date -Format $DateFormat
$backupName = "whiziq_filesystem_$timestamp"
$backupPath = "$BackupDir\$backupName"

# Define directories and files to exclude
$excludePatterns = @(
    "node_modules",
    "vendor",
    "storage\logs",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\debugbar",
    "bootstrap\cache",
    ".git",
    ".env",
    "backups",
    "*.log",
    "*.tmp",
    "*.temp",
    "Thumbs.db",
    "desktop.ini"
)

# Define critical directories to include
$includeDirectories = @(
    "app",
    "config",
    "database",
    "resources",
    "routes",
    "public",
    "storage\app",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "tests",
    "lang",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    "vite.config.js",
    "artisan",
    "deploy.php",
    "phpunit.xml",
    "phpstan.neon",
    "docker-compose.yml",
    "README.md",
    "LICENSE"
)

Write-Host "Starting file system backup..." -ForegroundColor Yellow
Write-Host "Source: $SourceDir" -ForegroundColor Cyan
Write-Host "Backup: $backupPath" -ForegroundColor Cyan

try {
    # Create temporary directory for backup
    $tempDir = "$env:TEMP\whiziq_backup_$timestamp"
    if (Test-Path $tempDir) {
        Remove-Item $tempDir -Recurse -Force
    }
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

    Write-Host "Copying files to temporary directory..." -ForegroundColor Yellow

    # Copy included directories
    foreach ($dir in $includeDirectories) {
        $sourcePath = Join-Path $SourceDir $dir
        $destPath = Join-Path $tempDir $dir
        
        if (Test-Path $sourcePath) {
            $destParent = Split-Path $destPath -Parent
            if (!(Test-Path $destParent)) {
                New-Item -ItemType Directory -Path $destParent -Force | Out-Null
            }
            
            if (Test-Path $sourcePath -PathType Container) {
                Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force
                if ($Verbose) {
                    Write-Host "Copied directory: $dir" -ForegroundColor Gray
                }
            } else {
                Copy-Item -Path $sourcePath -Destination $destPath -Force
                if ($Verbose) {
                    Write-Host "Copied file: $dir" -ForegroundColor Gray
                }
            }
        } else {
            if ($Verbose) {
                Write-Host "Skipped (not found): $dir" -ForegroundColor DarkGray
            }
        }
    }

    # Create .env.example if it doesn't exist
    $envExamplePath = Join-Path $tempDir ".env.example"
    if (!(Test-Path $envExamplePath)) {
        Write-Host "Creating .env.example file..." -ForegroundColor Yellow
        $envExampleContent = @"
APP_NAME=WhizIQ
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whiziq
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
"@
        $envExampleContent | Out-File -FilePath $envExamplePath -Encoding UTF8
    }

    # Create backup info file
    $backupInfoPath = Join-Path $tempDir "backup_info.txt"
    $backupInfo = @"
WhizIQ File System Backup
========================
Backup Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Source Directory: $SourceDir
Backup Type: File System
Laravel Version: 12.x
PHP Version: $(php -v | Select-String "PHP" | Select-Object -First 1)
Node Version: $(node --version 2>$null)
NPM Version: $(npm --version 2>$null)

Included Directories:
$(($includeDirectories | ForEach-Object { "- $_" }) -join "`n")

Excluded Patterns:
$(($excludePatterns | ForEach-Object { "- $_" }) -join "`n")

Restore Instructions:
1. Extract the backup archive
2. Copy files to your Laravel project directory
3. Run: composer install
4. Run: npm install
5. Copy .env.example to .env and configure
6. Run: php artisan key:generate
7. Run: php artisan migrate
8. Run: php artisan storage:link
"@
    $backupInfo | Out-File -FilePath $backupInfoPath -Encoding UTF8

    # Calculate total size
    $totalSize = (Get-ChildItem $tempDir -Recurse | Measure-Object -Property Length -Sum).Sum
    $totalSizeMB = [math]::Round($totalSize / 1MB, 2)
    Write-Host "Total size to backup: $totalSizeMB MB" -ForegroundColor Cyan

    if ($Compress) {
        # Create compressed archive
        $archivePath = "$backupPath.zip"
        Write-Host "Creating compressed archive..." -ForegroundColor Yellow
        
        # Use PowerShell's built-in compression
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        [System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $archivePath)
        
        $archiveSize = (Get-Item $archivePath).Length
        $archiveSizeMB = [math]::Round($archiveSize / 1MB, 2)
        $compressionRatio = [math]::Round((1 - $archiveSize / $totalSize) * 100, 1)
        
        Write-Host "Compressed archive created: $archivePath" -ForegroundColor Green
        Write-Host "Archive size: $archiveSizeMB MB (${compressionRatio}% reduction)" -ForegroundColor Cyan
    } else {
        # Copy without compression
        Copy-Item -Path $tempDir -Destination $backupPath -Recurse -Force
        Write-Host "Backup directory created: $backupPath" -ForegroundColor Green
    }

    # Clean up temporary directory
    Remove-Item $tempDir -Recurse -Force

    # Clean up old backups (keep last 7 days)
    $cutoffDate = (Get-Date).AddDays(-7)
    Get-ChildItem $BackupDir -Filter "whiziq_filesystem_*" | Where-Object {
        $_.LastWriteTime -lt $cutoffDate
    } | ForEach-Object {
        Write-Host "Removing old backup: $($_.Name)" -ForegroundColor Yellow
        Remove-Item $_.FullName -Recurse -Force
    }

    Write-Host "File system backup completed successfully!" -ForegroundColor Green

} catch {
    Write-Error "File system backup failed: $($_.Exception.Message)"
    # Clean up on error
    if (Test-Path $tempDir) {
        Remove-Item $tempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
    exit 1
}
