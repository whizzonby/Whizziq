# Deployment Guide - CI/CD & DevOps for cPanel Hosting

This guide covers the complete CI/CD and DevOps setup for deploying your Laravel application to Namecheap cPanel hosting.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [GitHub Actions Setup](#github-actions-setup)
4. [Environment Configuration](#environment-configuration)
5. [Deployment Methods](#deployment-methods)
6. [Backup & Rollback](#backup--rollback)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)

## Overview

This project includes:
- **GitHub Actions CI/CD pipelines** for automated testing and deployment
- **FTP/SFTP deployment scripts** for cPanel hosting
- **Automated backup and rollback** procedures
- **Environment management** for staging and production

## Prerequisites

### Required Accounts & Access

1. **GitHub Account** with repository access
2. **Namecheap cPanel Access** with FTP/SFTP credentials
3. **SSH Access** (optional but recommended for post-deployment commands)

### Required Tools (Local Development)

- Git
- PHP 8.2+
- Composer
- Node.js 18+
- npm

### Server Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer installed
- Node.js 18+ (for building assets)
- FTP/SFTP access enabled
- SSH access (recommended)

## GitHub Actions Setup

### 1. Configure GitHub Secrets

**Important**: Use **Environment secrets** (not Repository secrets) for better security and environment isolation.

#### Step 1: Create Production Environment

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click on **Environments** (in the left sidebar)
4. Click **New environment**
5. Name it `production` and click **Configure environment**
6. Click **Add secret** and add the following:

#### Production Secrets (Add to "production" environment)

```
FTP_SERVER=ftp.yourdomain.com
FTP_USERNAME=your_ftp_username
FTP_PASSWORD=your_ftp_password
FTP_PROTOCOL=ftp (or sftp)
FTP_PORT=21 (or 22 for SFTP)
FTP_DEPLOY_PATH=public_html
PRODUCTION_URL=https://yourdomain.com
SSH_HOST=yourdomain.com (optional)
SSH_USERNAME=your_ssh_username (optional)
SSH_PRIVATE_KEY=your_ssh_private_key (optional)
SSH_PORT=22 (optional)
```

#### Step 2: Create Staging Environment (Optional)

1. In the same **Environments** section, click **New environment**
2. Name it `staging` and click **Configure environment**
3. Click **Add secret** and add the following:

#### Staging Secrets (Add to "staging" environment)

```
STAGING_FTP_SERVER=staging.yourdomain.com
STAGING_FTP_USERNAME=your_staging_ftp_username
STAGING_FTP_PASSWORD=your_staging_ftp_password
STAGING_FTP_PROTOCOL=ftp
STAGING_FTP_PORT=21
STAGING_FTP_DEPLOY_PATH=staging
STAGING_URL=https://staging.yourdomain.com
```

### 2. Workflow Files

The following workflows are included:

- **`.github/workflows/ci.yml`** - Runs tests and code quality checks on PRs
- **`.github/workflows/deploy-production.yml`** - Deploys to production on main branch
- **`.github/workflows/deploy-staging.yml`** - Deploys to staging on develop branch

### 3. Deployment Triggers

#### Automatic Deployment

- **Production**: Pushes to `main` branch or version tags (`v*`)
- **Staging**: Pushes to `develop` branch

#### Manual Deployment

You can trigger deployments manually:
1. Go to Actions tab in GitHub
2. Select the deployment workflow
3. Click "Run workflow"
4. Optionally skip tests if needed

## Environment Configuration

### 1. Production Environment File

Create `.env.production` (do NOT commit this file):

```env
APP_NAME="WhizIQ"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# ... other environment variables
```

### 2. Upload .env to Server

**Important**: The `.env` file is excluded from deployment. You must manually upload it to your server:

1. Connect via FTP/SFTP
2. Upload `.env` to the root directory (same level as `artisan`)
3. Set permissions: `644`

### 3. Server Directory Structure

Your cPanel directory should look like this:

```
public_html/
├── .env (manually uploaded, not in git)
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   └── build/ (generated assets)
├── resources/
├── routes/
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── vendor/
└── artisan
```

## Deployment Methods

### Method 1: GitHub Actions (Recommended)

This is the automated method that runs on every push.

**Steps:**
1. Push code to `main` branch
2. GitHub Actions automatically:
   - Runs tests
   - Builds assets
   - Creates deployment package
   - Deploys via FTP/SFTP
   - Runs post-deployment commands (if SSH configured)

**Advantages:**
- Fully automated
- Runs tests before deployment
- Creates backups automatically
- No local setup required

### Method 2: Manual Script Deployment

#### Using Bash Script (Linux/Mac)

```bash
# Set environment variables
export FTP_SERVER=ftp.yourdomain.com
export FTP_USERNAME=your_username
export FTP_PASSWORD=your_password
export FTP_PROTOCOL=ftp
export FTP_PORT=21
export REMOTE_PATH=public_html
export LOCAL_PATH=.

# Make script executable
chmod +x scripts/deploy-cpanel.sh

# Run deployment
./scripts/deploy-cpanel.sh
```

#### Using PowerShell Script (Windows)

```powershell
# Set environment variables
$env:FTP_SERVER = "ftp.yourdomain.com"
$env:FTP_USERNAME = "your_username"
$env:FTP_PASSWORD = "your_password"
$env:FTP_PROTOCOL = "ftp"
$env:FTP_PORT = "21"
$env:REMOTE_PATH = "public_html"
$env:LOCAL_PATH = "."

# Run deployment
.\scripts\deploy-cpanel.ps1
```

### Method 3: Manual FTP Upload

1. Build assets locally:
   ```bash
   npm run build
   composer install --no-dev --optimize-autoloader
   ```

2. Upload files via FTP client (FileZilla, WinSCP, etc.)
   - Exclude: `.git`, `node_modules`, `.env`, `tests`, `storage/logs`

3. Run post-deployment commands via SSH or cPanel Terminal:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   php artisan queue:restart
   ```

## Backup & Rollback

### Creating Backups

#### Automatic Backups

GitHub Actions automatically creates backups before deployment.

#### Manual Backup

```bash
# Set environment variables
export FTP_SERVER=ftp.yourdomain.com
export FTP_USERNAME=your_username
export FTP_PASSWORD=your_password
export DB_HOST=127.0.0.1
export DB_DATABASE=your_database
export DB_USERNAME=your_username
export DB_PASSWORD=your_password

# Run backup script
chmod +x scripts/backup-production.sh
./scripts/backup-production.sh
```

### Rolling Back

```bash
# List available backups
ls -lh backups/

# Rollback to specific backup
chmod +x scripts/rollback.sh
./scripts/rollback.sh backups/production-backup-20240101-120000.tar.gz
```

**Important**: Always test rollbacks in staging first!

## Monitoring & Maintenance

### Post-Deployment Checklist

After each deployment, verify:

- [ ] Application loads correctly
- [ ] Database migrations ran successfully
- [ ] Assets (CSS/JS) load correctly
- [ ] Queue workers are running
- [ ] Scheduled tasks (cron) are configured
- [ ] Error logging is working
- [ ] Email sending works (test email)

### Monitoring Commands

```bash
# Check application status
php artisan about

# Check queue status
php artisan queue:work --once

# Check scheduled tasks
php artisan schedule:list

# View logs
tail -f storage/logs/laravel.log

# Check cache status
php artisan config:show
php artisan route:list
```

### Scheduled Tasks (Cron Jobs)

In cPanel, add this cron job:

```
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Or via SSH:

```bash
crontab -e
# Add the line above
```

## Troubleshooting

### Common Issues

#### 1. Deployment Fails - FTP Connection Error

**Solution:**
- Verify FTP credentials in GitHub Secrets
- Check if FTP is enabled in cPanel
- Try SFTP instead of FTP
- Check firewall settings

#### 2. Assets Not Loading After Deployment

**Solution:**
```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

#### 3. Database Migration Fails

**Solution:**
- Check database credentials in `.env`
- Verify database user has proper permissions
- Run migrations manually: `php artisan migrate --force`

#### 4. Permission Errors

**Solution:**
```bash
# Set correct permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 5. Queue Workers Not Running

**Solution:**
```bash
# Restart queue workers
php artisan queue:restart

# Or configure supervisor (if available)
# See deploy.php for supervisor configuration
```

### Getting Help

1. Check GitHub Actions logs for detailed error messages
2. Review server logs: `storage/logs/laravel.log`
3. Check cPanel error logs
4. Verify all environment variables are set correctly

## Best Practices

### 1. Always Test in Staging First

Deploy to staging (`develop` branch) before production.

### 2. Use Feature Branches

```bash
git checkout -b feature/new-feature
# Make changes
git push origin feature/new-feature
# Create PR to develop
```

### 3. Keep Secrets Secure

- Never commit `.env` files
- Use GitHub Secrets for sensitive data
- Rotate credentials regularly

### 4. Monitor Deployments

- Set up notifications (Slack, Discord, Email)
- Monitor error logs after deployment
- Use application monitoring tools

### 5. Regular Backups

- Automated backups before each deployment
- Manual backups before major changes
- Test restore procedures regularly

### 6. Version Control

- Tag releases: `git tag -a v1.0.0 -m "Release version 1.0.0"`
- Use semantic versioning
- Keep changelog updated

## Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [cPanel Documentation](https://docs.cpanel.net/)
- [FTP Deploy Action](https://github.com/SamKirkland/FTP-Deploy-Action)

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review GitHub Actions logs
3. Check server error logs
4. Contact your hosting provider (Namecheap support)

---

**Last Updated**: 2024
**Maintained By**: Development Team

