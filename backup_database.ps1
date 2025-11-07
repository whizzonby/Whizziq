# WhizIQ Database Backup Script
# PowerShell script for backing up the Laravel database

param(
    [string]$BackupDir = ".\backups\database",
    [string]$DateFormat = "yyyy-MM-dd_HH-mm-ss",
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
$backupFile = "$BackupDir\whiziq_database_$timestamp.sql"

# Load environment variables from .env file
if (Test-Path ".env") {
    Get-Content ".env" | ForEach-Object {
        if ($_ -match "^([^#][^=]+)=(.*)$") {
            [Environment]::SetEnvironmentVariable($matches[1], $matches[2], "Process")
        }
    }
}

# Get database configuration
$dbHost = $env:DB_HOST ?? "localhost"
$dbPort = $env:DB_PORT ?? "3306"
$dbName = $env:DB_DATABASE ?? "whiziq"
$dbUser = $env:DB_USERNAME ?? "root"
$dbPass = $env:DB_PASSWORD ?? ""

Write-Host "Starting database backup..." -ForegroundColor Yellow
Write-Host "Database: $dbName" -ForegroundColor Cyan
Write-Host "Host: $dbHost:$dbPort" -ForegroundColor Cyan
Write-Host "Backup file: $backupFile" -ForegroundColor Cyan

try {
    # Create mysqldump command
    $mysqldumpCmd = "mysqldump"
    $mysqldumpArgs = @(
        "--host=$dbHost",
        "--port=$dbPort",
        "--user=$dbUser",
        "--password=$dbPass",
        "--single-transaction",
        "--routines",
        "--triggers",
        "--events",
        "--add-drop-database",
        "--add-drop-table",
        "--create-options",
        "--disable-keys",
        "--extended-insert",
        "--quick",
        "--lock-tables=false",
        "--set-gtid-purged=OFF",
        $dbName
    )

    if ($Verbose) {
        $mysqldumpArgs += "--verbose"
    }

    # Execute mysqldump
    & $mysqldumpCmd $mysqldumpArgs | Out-File -FilePath $backupFile -Encoding UTF8

    if ($LASTEXITCODE -eq 0) {
        Write-Host "Database backup completed successfully!" -ForegroundColor Green
        
        # Get file size
        $fileSize = (Get-Item $backupFile).Length
        $fileSizeMB = [math]::Round($fileSize / 1MB, 2)
        Write-Host "Backup size: $fileSizeMB MB" -ForegroundColor Cyan

        # Compress if requested
        if ($Compress) {
            $compressedFile = "$backupFile.gz"
            Write-Host "Compressing backup..." -ForegroundColor Yellow
            
            # Use PowerShell compression
            $content = Get-Content $backupFile -Raw -Encoding UTF8
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($content)
            $compressedBytes = [System.IO.Compression.GzipStream]::new(
                [System.IO.File]::Create($compressedFile),
                [System.IO.Compression.CompressionMode]::Compress
            )
            $compressedBytes.Write($bytes, 0, $bytes.Length)
            $compressedBytes.Close()
            
            # Remove original file
            Remove-Item $backupFile
            
            $compressedSize = (Get-Item $compressedFile).Length
            $compressedSizeMB = [math]::Round($compressedSize / 1MB, 2)
            $compressionRatio = [math]::Round((1 - $compressedSize / $fileSize) * 100, 1)
            
            Write-Host "Compressed backup created: $compressedFile" -ForegroundColor Green
            Write-Host "Compressed size: $compressedSizeMB MB (${compressionRatio}% reduction)" -ForegroundColor Cyan
        }

        # Clean up old backups (keep last 7 days)
        $cutoffDate = (Get-Date).AddDays(-7)
        Get-ChildItem $BackupDir -Filter "whiziq_database_*.sql*" | Where-Object {
            $_.LastWriteTime -lt $cutoffDate
        } | ForEach-Object {
            Write-Host "Removing old backup: $($_.Name)" -ForegroundColor Yellow
            Remove-Item $_.FullName -Force
        }

        Write-Host "Backup process completed successfully!" -ForegroundColor Green
    } else {
        throw "mysqldump failed with exit code: $LASTEXITCODE"
    }
} catch {
    Write-Error "Database backup failed: $($_.Exception.Message)"
    exit 1
}
