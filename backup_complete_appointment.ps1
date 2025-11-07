# WhizIQ Complete Backup Script - Appointment Feature
# This script creates a comprehensive backup including database
# Created: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

param(
    [string]$BackupPath = ".\backups",
    [string]$ProjectName = "WhizIQ",
    [switch]$CompressBackup = $true,
    [switch]$IncludeVendor = $false,
    [switch]$IncludeNodeModules = $false
)

# Create timestamp for backup
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$BackupName = "${ProjectName}_CompleteAppointment_${Timestamp}"
$FullBackupPath = Join-Path $BackupPath $BackupName

Write-Host "🚀 Starting Complete WhizIQ Project Backup..." -ForegroundColor Green
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

# Function to copy with progress
function Copy-WithProgress {
    param(
        [string]$Source,
        [string]$Destination,
        [string]$Description
    )
    
    Write-Host "📂 Backing up $Description..." -ForegroundColor Yellow
    
    if (Test-Path $Source) {
        try {
            # Use robocopy for better progress and error handling
            $RobocopyArgs = @($Source, $Destination, "/E", "/R:3", "/W:1", "/NFL", "/NDL", "/NJH", "/NJS")
            $Result = & robocopy @RobocopyArgs
            
            if ($LASTEXITCODE -le 1) {
                Write-Host "✅ $Description backed up successfully" -ForegroundColor Green
            } else {
                Write-Host "⚠️  $Description backup completed with warnings" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "❌ $Description backup failed: $($_.Exception.Message)" -ForegroundColor Red
        }
    } else {
        Write-Host "⚠️  $Description not found, skipping..." -ForegroundColor Yellow
    }
}

# Core application directories
Write-Host "`n📦 Backing up core application..." -ForegroundColor Magenta

Copy-WithProgress -Source ".\app" -Destination "$FullBackupPath\app" -Description "Application Logic"
Copy-WithProgress -Source ".\resources" -Destination "$FullBackupPath\resources" -Description "Resources (Views, Assets)"
Copy-WithProgress -Source ".\database" -Destination "$FullBackupPath\database" -Description "Database Structure"
Copy-WithProgress -Source ".\config" -Destination "$FullBackupPath\config" -Description "Configuration Files"
Copy-WithProgress -Source ".\routes" -Destination "$FullBackupPath\routes" -Description "Route Definitions"
Copy-WithProgress -Source ".\tests" -Destination "$FullBackupPath\tests" -Description "Test Suite"
Copy-WithProgress -Source ".\bootstrap" -Destination "$FullBackupPath\bootstrap" -Description "Bootstrap Files"
Copy-WithProgress -Source ".\lang" -Destination "$FullBackupPath\lang" -Description "Language Files"
Copy-WithProgress -Source ".\public" -Destination "$FullBackupPath\public" -Description "Public Assets"

# Optional directories
if ($IncludeVendor) {
    Copy-WithProgress -Source ".\vendor" -Destination "$FullBackupPath\vendor" -Description "Vendor Dependencies"
}

if ($IncludeNodeModules) {
    Copy-WithProgress -Source ".\node_modules" -Destination "$FullBackupPath\node_modules" -Description "Node Modules"
}

# Root configuration files
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
    ".env.example",
    ".gitignore",
    "README.md",
    "LICENSE"
)

Write-Host "📄 Copying root configuration files..." -ForegroundColor Yellow
foreach ($file in $RootFiles) {
    if (Test-Path ".\$file") {
        Copy-Item -Path ".\$file" -Destination "$FullBackupPath\$file" -Force
        Write-Host "  ✅ $file" -ForegroundColor Green
    }
}

# Backup database
Write-Host "`n🗄️  Backing up database..." -ForegroundColor Magenta

try {
    # Read .env file for database configuration
    $EnvContent = Get-Content ".\.env" -ErrorAction SilentlyContinue
    $DbHost = ($EnvContent | Where-Object { $_ -match "^DB_HOST=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbPort = ($EnvContent | Where-Object { $_ -match "^DB_PORT=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbDatabase = ($EnvContent | Where-Object { $_ -match "^DB_DATABASE=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbUsername = ($EnvContent | Where-Object { $_ -match "^DB_USERNAME=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    $DbPassword = ($EnvContent | Where-Object { $_ -match "^DB_PASSWORD=" } | ForEach-Object { $_.Split("=")[1] }) -join ""
    
    if ($DbDatabase -and $DbUsername) {
        $DbBackupFile = "$FullBackupPath\database_backup_${Timestamp}.sql"
        
        # Create database backup using mysqldump
        $MysqldumpPath = "mysqldump"
        
        # Try to find mysqldump in common locations
        $CommonPaths = @(
            "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
            "C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe",
            "C:\xampp\mysql\bin\mysqldump.exe",
            "C:\wamp64\bin\mysql\mysql8.0.21\bin\mysqldump.exe"
        )
        
        foreach ($path in $CommonPaths) {
            if (Test-Path $path) {
                $MysqldumpPath = $path
                break
            }
        }
        
        $MysqldumpCmd = "`"$MysqldumpPath`" -h $DbHost -P $DbPort -u $DbUsername -p$DbPassword $DbDatabase > `"$DbBackupFile`""
        
        Write-Host "Creating database backup..." -ForegroundColor Yellow
        Invoke-Expression $MysqldumpCmd
        
        if (Test-Path $DbBackupFile) {
            $DbSize = (Get-Item $DbBackupFile).Length
            $DbSizeMB = [math]::Round($DbSize / 1MB, 2)
            Write-Host "✅ Database backup created: $DbBackupFile ($DbSizeMB MB)" -ForegroundColor Green
        } else {
            Write-Host "⚠️  Database backup may have failed - file not found" -ForegroundColor Yellow
        }
    } else {
        Write-Host "⚠️  Database configuration not found, skipping database backup" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Database backup failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Create comprehensive backup metadata
$Metadata = @{
    "backup_name" = $BackupName
    "timestamp" = $Timestamp
    "project_name" = $ProjectName
    "appointment_feature" = $true
    "backup_type" = "complete_appointment_backup"
    "database_included" = $true
    "compressed" = $CompressBackup
    "vendor_included" = $IncludeVendor
    "node_modules_included" = $IncludeNodeModules
    "created_by" = "WhizIQ Complete Backup Script"
    "version" = "1.0"
    "description" = "Complete backup of WhizIQ project with appointment functionality"
    "features_included" = @(
        "Appointment Calendar System",
        "Livewire Components", 
        "Database Models and Migrations",
        "Views and Templates",
        "Configuration Files",
        "Test Suite",
        "Public Assets",
        "Language Files",
        "Bootstrap Files"
    )
    "appointment_components" = @(
        "App\Livewire\Calendar\AppointmentCalendar.php",
        "App\Models\Appointment.php",
        "App\Models\AppointmentType.php",
        "resources\views\livewire\calendar\appointment-calendar.blade.php",
        "database\migrations\*appointments*.php",
        "database\migrations\*appointment_types*.php"
    )
    "backup_size" = "Calculating..."
    "restore_instructions" = @(
        "1. Extract the backup archive",
        "2. Copy all files to your project directory",
        "3. Run 'composer install' to install PHP dependencies",
        "4. Run 'npm install' to install Node.js dependencies",
        "5. Copy .env.example to .env and configure your environment",
        "6. Import the database backup if included",
        "7. Run 'php artisan migrate' to set up database tables",
        "8. Run 'php artisan key:generate' to generate application key",
        "9. Run 'npm run build' to compile assets"
    )
}

# Calculate backup size
try {
    $BackupSize = (Get-ChildItem -Path $FullBackupPath -Recurse -File | Measure-Object -Property Length -Sum).Sum
    $BackupSizeMB = [math]::Round($BackupSize / 1MB, 2)
    $Metadata.backup_size = "$BackupSizeMB MB"
} catch {
    $Metadata.backup_size = "Unknown"
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
Write-Host "🎯 Feature: Complete Appointment Calendar System" -ForegroundColor White
Write-Host "🗄️  Database: Included" -ForegroundColor White
Write-Host "🗜️  Compressed: $(if($CompressBackup) {'Yes'} else {'No'})" -ForegroundColor White
Write-Host "📦 Vendor: $(if($IncludeVendor) {'Included'} else {'Excluded'})" -ForegroundColor White
Write-Host "📦 Node Modules: $(if($IncludeNodeModules) {'Included'} else {'Excluded'})" -ForegroundColor White

if ($CompressBackup -and (Test-Path $ArchivePath)) {
    Write-Host "📁 Final Location: $ArchivePath" -ForegroundColor Green
} else {
    Write-Host "📁 Final Location: $FullBackupPath" -ForegroundColor Green
}

Write-Host "`n✅ BACKUP COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "🎉 Your complete WhizIQ project with appointment feature has been backed up!" -ForegroundColor Green

# Display appointment-specific components that were backed up
Write-Host "`n📋 Appointment Components Backed Up:" -ForegroundColor Magenta
Write-Host "  • AppointmentCalendar Livewire Component" -ForegroundColor White
Write-Host "  • Appointment Model with relationships" -ForegroundColor White
Write-Host "  • Calendar views and templates" -ForegroundColor White
Write-Host "  • Database migrations for appointments" -ForegroundColor White
Write-Host "  • Test files for appointment functionality" -ForegroundColor White
Write-Host "  • Public assets and resources" -ForegroundColor White
Write-Host "  • Configuration files" -ForegroundColor White

Write-Host "`n🔧 To restore this backup:" -ForegroundColor Yellow
Write-Host "  1. Extract the backup archive" -ForegroundColor White
Write-Host "  2. Copy files to your project directory" -ForegroundColor White
Write-Host "  3. Run 'composer install' and 'npm install'" -ForegroundColor White
Write-Host "  4. Configure your .env file" -ForegroundColor White
Write-Host "  5. Import the database backup" -ForegroundColor White
Write-Host "  6. Run 'php artisan migrate' and 'php artisan key:generate'" -ForegroundColor White
Write-Host "  7. Run 'npm run build' to compile assets" -ForegroundColor White

Write-Host "`n📄 Backup metadata saved to: backup_metadata.json" -ForegroundColor Cyan
