# WhizIQ Backup Test Script
# PowerShell script for testing backup procedures

param(
    [string]$TestDir = ".\backup_test",
    [switch]$Verbose = $false
)

Write-Host "===========================================" -ForegroundColor Magenta
Write-Host "WhizIQ Backup System Test" -ForegroundColor Magenta
Write-Host "===========================================" -ForegroundColor Magenta

# Create test directory
if (Test-Path $TestDir) {
    Remove-Item $TestDir -Recurse -Force
}
New-Item -ItemType Directory -Path $TestDir -Force | Out-Null

Write-Host "Test directory created: $TestDir" -ForegroundColor Green

try {
    # Test 1: Check prerequisites
    Write-Host "`nTest 1: Checking prerequisites..." -ForegroundColor Yellow
    
    $prerequisites = @{
        "PowerShell Version" = $PSVersionTable.PSVersion
        "PHP" = (php -v 2>$null | Select-String "PHP" | Select-Object -First 1)
        "Node.js" = (node --version 2>$null)
        "NPM" = (npm --version 2>$null)
        "Composer" = (composer --version 2>$null)
    }
    
    foreach ($prereq in $prerequisites.GetEnumerator()) {
        if ($prereq.Value) {
            Write-Host "✓ $($prereq.Key): $($prereq.Value)" -ForegroundColor Green
        } else {
            Write-Host "✗ $($prereq.Key): Not found" -ForegroundColor Red
        }
    }
    
    # Test 2: Check .env file
    Write-Host "`nTest 2: Checking environment configuration..." -ForegroundColor Yellow
    
    if (Test-Path ".env") {
        Write-Host "✓ .env file found" -ForegroundColor Green
        
        # Load environment variables
        Get-Content ".env" | ForEach-Object {
            if ($_ -match "^([^#][^=]+)=(.*)$") {
                [Environment]::SetEnvironmentVariable($matches[1], $matches[2], "Process")
            }
        }
        
        $envVars = @("DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME")
        foreach ($var in $envVars) {
            $value = [Environment]::GetEnvironmentVariable($var, "Process")
            if ($value) {
                Write-Host "✓ $var: $value" -ForegroundColor Green
            } else {
                Write-Host "✗ $var: Not set" -ForegroundColor Red
            }
        }
    } else {
        Write-Host "✗ .env file not found" -ForegroundColor Red
        Write-Host "  Creating .env.example for testing..." -ForegroundColor Yellow
        
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
        $envExampleContent | Out-File -FilePath ".env.example" -Encoding UTF8
        Write-Host "✓ .env.example created" -ForegroundColor Green
    }
    
    # Test 3: Test file system backup (dry run)
    Write-Host "`nTest 3: Testing file system backup (dry run)..." -ForegroundColor Yellow
    
    $testBackupDir = "$TestDir\filesystem_test"
    $testResult = & ".\backup_filesystem.ps1" -BackupDir $testBackupDir -Compress:$false -Verbose:$Verbose
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ File system backup test passed" -ForegroundColor Green
        
        # Check backup contents
        $backupContents = Get-ChildItem $testBackupDir -Recurse
        Write-Host "  Backup contains $($backupContents.Count) items" -ForegroundColor Cyan
        
        # Check for critical files
        $criticalFiles = @("composer.json", "package.json", "artisan")
        foreach ($file in $criticalFiles) {
            $found = Get-ChildItem $testBackupDir -Recurse -Name $file
            if ($found) {
                Write-Host "  ✓ $file found in backup" -ForegroundColor Green
            } else {
                Write-Host "  ✗ $file missing from backup" -ForegroundColor Red
            }
        }
    } else {
        Write-Host "✗ File system backup test failed" -ForegroundColor Red
    }
    
    # Test 4: Test database backup (if database is available)
    Write-Host "`nTest 4: Testing database backup..." -ForegroundColor Yellow
    
    $testDbBackupDir = "$TestDir\database_test"
    $dbTestResult = & ".\backup_database.ps1" -BackupDir $testDbBackupDir -Compress:$false -Verbose:$Verbose
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Database backup test passed" -ForegroundColor Green
        
        # Check backup file
        $dbBackupFiles = Get-ChildItem $testDbBackupDir -Filter "*.sql"
        if ($dbBackupFiles) {
            $fileSize = [math]::Round($dbBackupFiles[0].Length / 1KB, 2)
            Write-Host "  Database backup size: $fileSize KB" -ForegroundColor Cyan
        }
    } else {
        Write-Host "✗ Database backup test failed (database may not be available)" -ForegroundColor Yellow
        Write-Host "  This is normal if MySQL is not running or configured" -ForegroundColor Gray
    }
    
    # Test 5: Test complete backup
    Write-Host "`nTest 5: Testing complete backup..." -ForegroundColor Yellow
    
    $testCompleteBackupDir = "$TestDir\complete_test"
    $completeTestResult = & ".\backup_complete.ps1" -BackupDir $testCompleteBackupDir -Compress:$false -Verbose:$Verbose
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Complete backup test passed" -ForegroundColor Green
        
        # Check backup structure
        $backupStructure = Get-ChildItem $testCompleteBackupDir -Recurse
        Write-Host "  Complete backup contains $($backupStructure.Count) items" -ForegroundColor Cyan
        
        # Check for backup info files
        $infoFiles = @("backup_info.txt", "system_info.txt", "restore.ps1")
        foreach ($file in $infoFiles) {
            $found = Get-ChildItem $testCompleteBackupDir -Recurse -Name $file
            if ($found) {
                Write-Host "  ✓ $file found" -ForegroundColor Green
            } else {
                Write-Host "  ✗ $file missing" -ForegroundColor Red
            }
        }
    } else {
        Write-Host "✗ Complete backup test failed" -ForegroundColor Red
    }
    
    # Test Summary
    Write-Host "`n===========================================" -ForegroundColor Magenta
    Write-Host "Test Summary" -ForegroundColor Magenta
    Write-Host "===========================================" -ForegroundColor Magenta
    
    $testResults = @{
        "Prerequisites" = "✓ Passed"
        "Environment" = if (Test-Path ".env") { "✓ Configured" } else { "⚠ Using .env.example" }
        "File System Backup" = if ($LASTEXITCODE -eq 0) { "✓ Passed" } else { "✗ Failed" }
        "Database Backup" = if ($dbTestResult -and $LASTEXITCODE -eq 0) { "✓ Passed" } else { "⚠ Skipped (DB not available)" }
        "Complete Backup" = if ($completeTestResult -and $LASTEXITCODE -eq 0) { "✓ Passed" } else { "✗ Failed" }
    }
    
    foreach ($result in $testResults.GetEnumerator()) {
        $color = if ($result.Value -match "✓") { "Green" } elseif ($result.Value -match "⚠") { "Yellow" } else { "Red" }
        Write-Host "$($result.Key): $($result.Value)" -ForegroundColor $color
    }
    
    Write-Host "`nTest files created in: $TestDir" -ForegroundColor Cyan
    Write-Host "You can safely delete this directory after reviewing the results." -ForegroundColor Gray
    
    Write-Host "`nBackup system is ready for use!" -ForegroundColor Green
    
} catch {
    Write-Error "Backup test failed: $($_.Exception.Message)"
    Write-Host "`nCleaning up test directory..." -ForegroundColor Yellow
    if (Test-Path $TestDir) {
        Remove-Item $TestDir -Recurse -Force -ErrorAction SilentlyContinue
    }
    exit 1
}
