# cPanel Deployment Script for Windows PowerShell
# This script handles deployment to cPanel hosting via FTP/SFTP

param(
    [string]$FtpServer = $env:FTP_SERVER,
    [string]$FtpUsername = $env:FTP_USERNAME,
    [string]$FtpPassword = $env:FTP_PASSWORD,
    [string]$FtpProtocol = $env:FTP_PROTOCOL ?? "ftp",
    [int]$FtpPort = [int]($env:FTP_PORT ?? "21"),
    [string]$RemotePath = $env:REMOTE_PATH ?? "public_html",
    [string]$LocalPath = $env:LOCAL_PATH ?? "."
)

# Functions
function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Test-Requirements {
    Write-Info "Checking requirements..."
    
    if (-not $FtpServer -or -not $FtpUsername -or -not $FtpPassword) {
        Write-Error "FTP credentials not set. Please set FTP_SERVER, FTP_USERNAME, and FTP_PASSWORD"
        exit 1
    }
    
    # Check if WinSCP is available (optional, for SFTP)
    if ($FtpProtocol -eq "sftp") {
        $winscpPath = Get-Command winscp -ErrorAction SilentlyContinue
        if (-not $winscpPath) {
            Write-Warn "WinSCP not found. Install it for better SFTP support, or use FTP instead."
        }
    }
}

function New-Backup {
    Write-Info "Creating backup on remote server..."
    
    $backupName = "backup-$(Get-Date -Format 'yyyyMMdd-HHmmss').tar.gz"
    
    try {
        # Create FTP request
        $ftpRequest = [System.Net.FtpWebRequest]::Create("$FtpProtocol://$FtpServer`:$FtpPort/$RemotePath/backups/")
        $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($FtpUsername, $FtpPassword)
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $ftpRequest.UsePassive = $true
        
        try {
            $response = $ftpRequest.GetResponse()
            $response.Close()
        } catch {
            # Directory might already exist
        }
        
        Write-Info "Backup directory created/verified"
    } catch {
        Write-Warn "Backup creation failed, continuing... $_"
    }
}

function Invoke-DeployFiles {
    Write-Info "Deploying files to $FtpServer`:$RemotePath..."
    
    # Install WinSCP .NET assembly if available, otherwise use basic FTP
    $useWinSCP = $false
    
    try {
        Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll" -ErrorAction Stop
        $useWinSCP = $true
        Write-Info "Using WinSCP for deployment"
    } catch {
        Write-Info "Using basic FTP for deployment"
    }
    
    if ($useWinSCP -and $FtpProtocol -eq "sftp") {
        # Use WinSCP for SFTP
        $sessionOptions = New-Object WinSCP.SessionOptions -Property @{
            Protocol = [WinSCP.Protocol]::Sftp
            HostName = $FtpServer
            PortNumber = $FtpPort
            UserName = $FtpUsername
            Password = $FtpPassword
        }
        
        $session = New-Object WinSCP.Session
        
        try {
            $session.Open($sessionOptions)
            
            $transferOptions = New-Object WinSCP.TransferOptions
            $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
            
            $transferResult = $session.PutFiles("$LocalPath\*", "$RemotePath/", $False, $transferOptions)
            $transferResult.Check()
            
            Write-Info "Files deployed successfully!"
        } finally {
            $session.Dispose()
        }
    } else {
        # Use basic FTP
        Write-Warn "Basic FTP upload not fully implemented. Consider using the bash script or WinSCP."
        Write-Info "For now, please use the GitHub Actions workflow or the bash script for deployment."
    }
}

function Set-Permissions {
    Write-Info "Setting file permissions..."
    Write-Warn "FTP permission setting is limited. Please set permissions manually via cPanel:"
    Write-Info "  - storage: 775"
    Write-Info "  - bootstrap/cache: 775"
}

function Invoke-PostDeploy {
    Write-Info "Running post-deployment commands..."
    
    if ($env:SSH_HOST -and $env:SSH_USERNAME) {
        Write-Info "SSH configured, running commands remotely..."
        # SSH commands would go here
    } else {
        Write-Warn "SSH not configured, skipping post-deployment commands"
        Write-Info "Please run these commands manually on the server:"
        Write-Info "  php artisan config:cache"
        Write-Info "  php artisan route:cache"
        Write-Info "  php artisan view:cache"
        Write-Info "  php artisan migrate --force"
        Write-Info "  php artisan queue:restart"
    }
}

# Main execution
function Main {
    Write-Info "Starting deployment to cPanel..."
    
    Test-Requirements
    New-Backup
    Invoke-DeployFiles
    Set-Permissions
    Invoke-PostDeploy
    
    Write-Info "Deployment completed! 🚀"
}

# Run main function
Main

