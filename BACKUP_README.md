# WhizIQ Backup System

This directory contains comprehensive backup scripts for the WhizIQ Laravel SaaS application. The backup system provides both database and file system backup capabilities with compression and automated cleanup.

## 📁 Backup Scripts

### 1. Database Backup (`backup_database.ps1`)
Creates a complete database backup using mysqldump.

**Features:**
- Full database dump with all tables, routines, triggers, and events
- Automatic compression (gzip)
- Environment variable detection from `.env` file
- Automatic cleanup of old backups (7 days retention)
- Progress reporting and file size information

**Usage:**
```powershell
# Basic database backup
.\backup_database.ps1

# Custom backup directory
.\backup_database.ps1 -BackupDir "C:\MyBackups\database"

# Without compression
.\backup_database.ps1 -Compress:$false

# Verbose output
.\backup_database.ps1 -Verbose
```

### 2. File System Backup (`backup_filesystem.ps1`)
Creates a backup of all application files excluding unnecessary directories.

**Features:**
- Excludes `node_modules`, `vendor`, logs, and cache directories
- Includes all critical application files and directories
- Creates `.env.example` if missing
- Generates backup information file
- Automatic compression (ZIP)
- Automatic cleanup of old backups

**Usage:**
```powershell
# Basic file system backup
.\backup_filesystem.ps1

# Custom backup directory
.\backup_filesystem.ps1 -BackupDir "C:\MyBackups\files"

# Without compression
.\backup_filesystem.ps1 -Compress:$false

# Verbose output
.\backup_filesystem.ps1 -Verbose
```

### 3. Complete Backup (`backup_complete.ps1`)
Creates a comprehensive backup including both database and file system.

**Features:**
- Combines database and file system backups
- Creates system information file
- Generates restore script
- Comprehensive backup summary
- Flexible options to skip database or files

**Usage:**
```powershell
# Complete backup (recommended)
.\backup_complete.ps1

# Skip database backup
.\backup_complete.ps1 -SkipDatabase

# Skip file system backup
.\backup_complete.ps1 -SkipFiles

# Custom backup directory
.\backup_complete.ps1 -BackupDir "C:\MyBackups\complete"
```

## 🗂️ Backup Structure

```
backups/
├── complete/
│   └── whiziq_complete_2024-01-15_14-30-25/
│       ├── backup_info.txt
│       ├── system_info.txt
│       ├── restore.ps1
│       ├── database/
│       │   └── whiziq_database_2024-01-15_14-30-25.sql.gz
│       └── filesystem/
│           └── whiziq_filesystem_2024-01-15_14-30-25.zip
├── database/
│   └── whiziq_database_2024-01-15_14-30-25.sql.gz
└── filesystem/
    └── whiziq_filesystem_2024-01-15_14-30-25.zip
```

## 🔧 Prerequisites

### Required Software
- **PowerShell 5.1+** (Windows PowerShell or PowerShell Core)
- **MySQL/MariaDB** with `mysqldump` command available
- **PHP 8.2+** (for Laravel application)
- **Node.js & NPM** (for frontend dependencies)

### Environment Setup
1. Ensure your `.env` file is properly configured with database credentials
2. Make sure `mysqldump` is available in your system PATH
3. Verify PHP and Node.js are accessible from command line

## 📋 Backup Contents

### Database Backup Includes:
- All tables and data
- Stored procedures and functions
- Triggers
- Events
- Database structure
- User permissions (if applicable)

### File System Backup Includes:
- `app/` - Application code
- `config/` - Configuration files
- `database/` - Migrations and seeders
- `resources/` - Views, assets, and translations
- `routes/` - Route definitions
- `public/` - Public assets
- `storage/app/` - Application storage
- `tests/` - Test files
- `lang/` - Language files
- Configuration files (`composer.json`, `package.json`, etc.)

### Excluded from Backup:
- `node_modules/` - Node.js dependencies
- `vendor/` - PHP dependencies
- `storage/logs/` - Log files
- `storage/framework/cache/` - Cache files
- `storage/framework/sessions/` - Session files
- `storage/framework/views/` - Compiled views
- `.git/` - Git repository
- `.env` - Environment variables (sensitive data)

## 🔄 Restore Process

### From Complete Backup:
1. Extract the backup archive
2. Navigate to the backup directory
3. Run the included `restore.ps1` script:
   ```powershell
   .\restore.ps1 -RestoreDir "C:\MyNewProject"
   ```

### Manual Restore:
1. **Database Restore:**
   ```bash
   # Extract and restore database
   gunzip whiziq_database_*.sql.gz
   mysql -u username -p database_name < whiziq_database_*.sql
   ```

2. **File System Restore:**
   ```powershell
   # Extract files
   Expand-Archive whiziq_filesystem_*.zip -DestinationPath "C:\MyNewProject"
   ```

3. **Application Setup:**
   ```bash
   # Install dependencies
   composer install
   npm install
   
   # Configure environment
   copy .env.example .env
   # Edit .env with your settings
   
   # Generate application key
   php artisan key:generate
   
   # Run migrations
   php artisan migrate
   
   # Create storage link
   php artisan storage:link
   
   # Cache configuration
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## ⚙️ Configuration

### Environment Variables
The backup scripts automatically read database configuration from your `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whiziq
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Custom Backup Locations
You can specify custom backup directories:
```powershell
.\backup_complete.ps1 -BackupDir "D:\Backups\WhizIQ"
```

### Retention Policy
- **Default:** Keeps backups for 7 days
- **Automatic cleanup:** Removes old backups automatically
- **Manual cleanup:** Delete backup directories manually if needed

## 🚨 Troubleshooting

### Common Issues:

1. **mysqldump not found:**
   - Add MySQL bin directory to your PATH
   - Or specify full path to mysqldump

2. **Permission denied:**
   - Run PowerShell as Administrator
   - Check file/folder permissions

3. **Database connection failed:**
   - Verify database credentials in `.env`
   - Ensure database server is running
   - Check network connectivity

4. **Large backup files:**
   - Use compression (enabled by default)
   - Consider excluding large directories
   - Use external storage for backups

### Log Files:
- Check PowerShell execution policy: `Get-ExecutionPolicy`
- Set execution policy if needed: `Set-ExecutionPolicy RemoteSigned`

## 📊 Backup Monitoring

### File Sizes:
- Database backups: Typically 10-100MB (compressed)
- File system backups: Typically 50-500MB (compressed)
- Complete backups: Combined size of both

### Performance:
- Database backup: 1-5 minutes (depending on size)
- File system backup: 2-10 minutes (depending on size)
- Complete backup: 3-15 minutes total

## 🔒 Security Considerations

- **Sensitive data:** `.env` files are excluded from backups
- **Database passwords:** Stored in environment variables only
- **File permissions:** Backup files inherit directory permissions
- **Network security:** Use secure connections for remote backups

## 📅 Backup Schedule

### Recommended Schedule:
- **Daily:** Database backups
- **Weekly:** Complete backups
- **Before major changes:** Manual complete backup
- **Before deployments:** Complete backup

### Automation:
Consider setting up Windows Task Scheduler to run backups automatically:
```powershell
# Daily database backup at 2 AM
schtasks /create /tn "WhizIQ DB Backup" /tr "powershell.exe -File C:\Path\To\backup_database.ps1" /sc daily /st 02:00
```

## 📞 Support

For issues with the backup system:
1. Check the troubleshooting section above
2. Verify all prerequisites are met
3. Review PowerShell execution logs
4. Test individual backup scripts separately

---

**Note:** Always test your backup and restore procedures in a development environment before relying on them in production.
