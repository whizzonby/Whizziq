# WhizIQ Project Backup Script - Appointment Feature
# This script creates a comprehensive backup of the project with appointment functionality
# Created: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

param(
    [string]$BackupPath = ".\backups",
    [string]$ProjectName = "WhizIQ",
    [switch]$IncludeDatabase = $true,
    [switch]$CompressBackup = $true
)

# Create timestamp for backup
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$BackupName = "${ProjectName}_AppointmentFeature_${Timestamp}"
$FullBackupPath = Join-Path $BackupPath $BackupName

Write-Host "🚀 Starting WhizIQ Project Backup with Appointment Feature..." -ForegroundColor Green
Write-Host "📅 Backup Timestamp: $Timestamp" -ForegroundColor Cyan
Write-Host "📁 Backup Location: $FullBackupPath" -ForegroundColor Cyan

# Create backup directory
if (!(Test-Path $BackupPath)) {
    New-Item -ItemType Directory -Path $BackupPath -Force
    Write-Host "✅ Created backup directory: $BackupPath" -ForegroundColor Green
}

# Create project backup directory
New-Item -ItemType Directory -Path $FullBackupPath -Force
Write-Host "✅ Created project backup directory: $FullBackupPath" -ForegroundColor Green

# Function to copy directory with progress
function Copy-DirectoryWithProgress {
    param(
        [string]$Source,
        [string]$Destination,
        [string]$Description
    )
    
    Write-Host "📂 Copying $Description..." -ForegroundColor Yellow
    
    if (Test-Path $Source) {
        # Get total size for progress calculation
        $TotalSize = (Get-ChildItem -Path $Source -Recurse -File | Measure-Object -Property Length -Sum).Sum
        $CopiedSize = 0
        
        # Copy with progress
        Get-ChildItem -Path $Source -Recurse | ForEach-Object {
            $RelativePath = $_.FullName.Substring($Source.Length + 1)
            $DestinationPath = Join-Path $Destination $RelativePath
            
            if ($_.PSIsContainer) {
                if (!(Test-Path $DestinationPath)) {
                    New-Item -ItemType Directory -Path $DestinationPath -Force | Out-Null
                }
            } else {
                Copy-Item -Path $_.FullName -Destination $DestinationPath -Force
                $CopiedSize += $_.Length
                $Progress = [math]::Round(($CopiedSize / $TotalSize) * 100, 2)
                Write-Progress -Activity "Backing up $Description" -Status "Progress: $Progress%" -PercentComplete $Progress
            }
        }
        Write-Progress -Activity "Backing up $Description" -Completed
        Write-Host "✅ $Description copied successfully" -ForegroundColor Green
    } else {
        Write-Host "⚠️  $Description not found, skipping..." -ForegroundColor Yellow
    }
}

# Backup core application files
Write-Host "`n📦 Backing up core application files..." -ForegroundColor Magenta

# App directory (Livewire components, Models, etc.)
Copy-DirectoryWithProgress -Source ".\app" -Destination "$FullBackupPath\app" -Description "Application Logic"

# Resources directory (Views, CSS, JS)
Copy-DirectoryWithProgress -Source ".\resources" -Destination "$FullBackupPath\resources" -Description "Resources (Views, Assets)"

# Database migrations and seeders
Copy-DirectoryWithProgress -Source ".\database" -Destination "$FullBackupPath\database" -Description "Database Structure"

# Configuration files
Copy-DirectoryWithProgress -Source ".\config" -Destination "$FullBackupPath\config" -Description "Configuration Files"

# Routes
Copy-DirectoryWithProgress -Source ".\routes" -Destination "$FullBackupPath\routes" -Description "Route Definitions"

# Tests
Copy-DirectoryWithProgress -Source ".\tests" -Destination "$FullBackupPath\tests" -Description "Test Suite"

# Root files
$RootFiles = @(
    "composer.json",
    "composer.lock", 
    "package.json",
    "package-lock.json",
    "vite.config.js",
    "artisan",
    "phpunit.xml",
    "phpstan.neon",
    "deploy.php",
    "docker-compose.yml",
    ".env.example"
)

Write-Host "📄 Copying root configuration files..." -ForegroundColor Yellow
foreach ($file in $RootFiles) {
    if (Test-Path ".\$file") {
        Copy-Item -Path ".\$file" -Destination "$FullBackupPath\$file" -Force
        Write-Host "  ✅ $file" -ForegroundColor Green
    }
}

# Backup database if requested
if ($IncludeDatabase) {
    Write-Host "`n🗄️  Backing up database..." -ForegroundColor Magenta
    
    # Get database configuration
    $EnvContent = Get-Content ".\.env" -ErrorAction SilentlyContinue
    $DbHost = ($EnvContent | Where-Object { $_ -match "^DB_HOST=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbPort = ($EnvContent | Where-Object { $_ -match "^DB_PORT=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbDatabase = ($EnvContent | Where-Object { $_ -match "^DB_DATABASE=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbUsername = ($EnvContent | Where-Object { $_ -match "^DB_USERNAME=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbPassword = ($EnvContent | Where-Object { $_ -match "^DB_PASSWORD=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    
    if ($DbDatabase) {
        $DbBackupFile = "$FullBackupPath\database_backup_${Timestamp}.sql"
        
        try {
            # Create database backup using mysqldump
            $MysqldumpCmd = "mysqldump -h $DbHost -P $DbPort -u $DbUsername -p$DbPassword $DbDatabase > `"$DbBackupFile`""
            Invoke-Expression $MysqldumpCmd
            
            if (Test-Path $DbBackupFile) {
                Write-Host "✅ Database backup created: $DbBackupFile" -ForegroundColor Green
            } else {
                Write-Host "⚠️  Database backup may have failed - file not found" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "❌ Database backup failed: $($_.Exception.Message)" -ForegroundColor Red
        }
    } else {
        Write-Host "⚠️  Database configuration not found, skipping database backup" -ForegroundColor Yellow
    }
}

# Create backup metadata
$Metadata = @{
    "backup_name" = $BackupName
    "timestamp" = $Timestamp
    "project_name" = $ProjectName
    "appointment_feature" = $true
    "backup_type" = "appointment_feature_backup"
    "database_included" = $IncludeDatabase
    "compressed" = $CompressBackup
    "created_by" = "WhizIQ Backup Script"
    "version" = "1.0"
    "description" = "Complete backup of WhizIQ project with appointment functionality"
    "features_included" = @(
        "Appointment Calendar",
        "Livewire Components", 
        "Database Models",
        "Views and Templates",
        "Configuration Files",
        "Test Suite",
        "Documentation"
    )
    "appointment_components" = @(
        "App\Livewire\Calendar\AppointmentCalendar.php",
        "App\Models\Appointment.php",
        "resources\views\livewire\calendar\appointment-calendar.blade.php"
    )
}

$MetadataJson = $Metadata | ConvertTo-Json -Depth 3
$MetadataJson | Out-File -FilePath "$FullBackupPath\backup_metadata.json" -Encoding UTF8

Write-Host "`n📋 Backup metadata created" -ForegroundColor Green

# Create compressed archive if requested
if ($CompressBackup) {
    Write-Host "`n🗜️  Creating compressed archive..." -ForegroundColor Magenta
    
    $ArchivePath = "$BackupPath\${BackupName}.zip"
    
    try {
        # Remove existing archive if it exists
        if (Test-Path $ArchivePath) {
            Remove-Item $ArchivePath -Force
        }
        
        # Create ZIP archive
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        [System.IO.Compression.ZipFile]::CreateFromDirectory($FullBackupPath, $ArchivePath)
        
        # Get archive size
        $ArchiveSize = (Get-Item $ArchivePath).Length
        $ArchiveSizeMB = [math]::Round($ArchiveSize / 1MB, 2)
        
        Write-Host "✅ Compressed archive created: $ArchivePath" -ForegroundColor Green
        Write-Host "📦 Archive size: $ArchiveSizeMB MB" -ForegroundColor Cyan
        
        # Clean up uncompressed directory
        Remove-Item $FullBackupPath -Recurse -Force
        Write-Host "🧹 Cleaned up uncompressed directory" -ForegroundColor Green
        
    } catch {
        Write-Host "❌ Archive creation failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "📁 Uncompressed backup available at: $FullBackupPath" -ForegroundColor Yellow
    }
}

# Create backup summary
Write-Host "`n📊 BACKUP SUMMARY" -ForegroundColor Cyan
Write-Host "=================" -ForegroundColor Cyan
Write-Host "📅 Timestamp: $Timestamp" -ForegroundColor White
Write-Host "📦 Project: $ProjectName" -ForegroundColor White
Write-Host "🎯 Feature: Appointment Calendar System" -ForegroundColor White
Write-Host "🗄️  Database: $(if($IncludeDatabase) {'Included'} else {'Excluded'})" -ForegroundColor White
Write-Host "🗜️  Compressed: $(if($CompressBackup) {'Yes'} else {'No'})" -ForegroundColor White

if ($CompressBackup -and (Test-Path $ArchivePath)) {
    Write-Host "📁 Final Location: $ArchivePath" -ForegroundColor Green
} else {
    Write-Host "📁 Final Location: $FullBackupPath" -ForegroundColor Green
}

Write-Host "`n✅ BACKUP COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "🎉 Your WhizIQ project with appointment feature has been backed up!" -ForegroundColor Green

# Display appointment-specific components that were backed up
Write-Host "`n📋 Appointment Components Backed Up:" -ForegroundColor Magenta
Write-Host "  • AppointmentCalendar Livewire Component" -ForegroundColor White
Write-Host "  • Appointment Model with relationships" -ForegroundColor White
Write-Host "  • Calendar views and templates" -ForegroundColor White
Write-Host "  • Database migrations for appointments" -ForegroundColor White
Write-Host "  • Test files for appointment functionality" -ForegroundColor White

Write-Host "`n🔧 To restore this backup:" -ForegroundColor Yellow
Write-Host "  1. Extract the backup archive" -ForegroundColor White
Write-Host "  2. Copy files to your project directory" -ForegroundColor White
Write-Host "  3. Run 'composer install' and 'npm install'" -ForegroundColor White
Write-Host "  4. Import the database backup if included" -ForegroundColor White
Write-Host "  5. Run 'php artisan migrate' if needed" -ForegroundColor White
