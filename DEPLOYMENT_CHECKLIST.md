# Deployment Checklist - WhizIQ

## ✅ GitHub Secrets Configuration

Your production environment secrets are configured:

- ✅ **FTP_SERVER**: `ftp.whizziq.com`
- ✅ **FTP_USERNAME**: `whizeakm`
- ✅ **FTP_PASSWORD**: ✓ (configured)
- ✅ **FTP_PROTOCOL**: `sftp` (secure!)
- ✅ **FTP_PORT**: `21098` (custom port)
- ✅ **FTP_DEPLOY_PATH**: `public_html`
- ✅ **PRODUCTION_URL**: `https://www.whizziq.com`

## 📋 Pre-Deployment Checklist

### 1. Server Setup (One-Time)

- [ ] **Upload .env file** to server root directory (`public_html/.env`)
  - Connect via SFTP to `ftp.whizziq.com:21098`
  - Upload your `.env` file
  - Set permissions to `644`

- [ ] **Set directory permissions** on server:
  ```bash
  chmod -R 775 storage bootstrap/cache
  chmod -R 755 public
  ```

- [ ] **Create storage symlink** (if not exists):
  ```bash
  php artisan storage:link
  ```

- [ ] **Install dependencies** (first time only):
  ```bash
  composer install --no-dev --optimize-autoloader
  npm install && npm run build
  ```

- [ ] **Run initial migrations**:
  ```bash
  php artisan migrate --force
  ```

- [ ] **Set up cron job** in cPanel:
  ```
  * * * * * cd /home/whizeakm/public_html && php artisan schedule:run >> /dev/null 2>&1
  ```

### 2. Verify Server Requirements

- [ ] PHP 8.2+ installed
- [ ] MySQL 8.0+ available
- [ ] Composer installed
- [ ] Node.js 18+ installed (for building assets)
- [ ] SFTP access working (port 21098)

### 3. Test Connection

- [ ] Test SFTP connection manually:
  ```bash
  sftp -P 21098 whizeakm@ftp.whizziq.com
  ```
  Or use FileZilla/WinSCP with:
  - Host: `ftp.whizziq.com`
  - Port: `21098`
  - Protocol: SFTP
  - Username: `whizeakm`

## 🚀 First Deployment

### Step 1: Verify Everything is Ready

- [ ] All secrets configured in GitHub
- [ ] .env file uploaded to server
- [ ] Server permissions set correctly
- [ ] Database configured in .env

### Step 2: Make a Test Commit

```bash
# Make a small change (like updating a comment)
git add .
git commit -m "Test deployment setup"
git push origin main
```

### Step 3: Monitor Deployment

1. Go to GitHub → **Actions** tab
2. Watch the "Deploy to Production" workflow
3. Check for any errors
4. Verify deployment completed successfully

### Step 4: Verify Deployment

- [ ] Visit https://www.whizziq.com
- [ ] Check that site loads correctly
- [ ] Verify CSS/JS assets load
- [ ] Test a few key features
- [ ] Check server logs: `storage/logs/laravel.log`

## 🔄 Regular Deployment Process

After initial setup, deployments are automatic:

1. **Make changes** in your code
2. **Commit and push** to `main` branch:
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin main
   ```
3. **GitHub Actions automatically**:
   - Runs tests
   - Builds assets
   - Creates backup
   - Deploys to production
   - Runs post-deployment tasks

## 🆘 Troubleshooting

### Deployment Fails

**Check:**
- [ ] GitHub Actions logs for specific errors
- [ ] SFTP credentials are correct
- [ ] Port 21098 is accessible
- [ ] Server has enough disk space
- [ ] File permissions are correct

### Assets Not Loading

**Run on server:**
```bash
cd /home/whizeakm/public_html
npm run build
php artisan cache:clear
php artisan view:clear
```

### Database Errors

**Check:**
- [ ] .env database credentials
- [ ] Database user has proper permissions
- [ ] Run migrations: `php artisan migrate --force`

### Permission Errors

**Fix:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R whizeakm:whizeakm storage bootstrap/cache
```

## 📝 Notes

- **SFTP Port**: Your custom port `21098` is configured correctly
- **Security**: Using SFTP (not FTP) is more secure ✅
- **Backups**: Automatic backups are created before each deployment
- **Rollback**: Use `scripts/rollback.sh` if needed

## 🔐 Security Reminders

- ✅ Never commit `.env` file
- ✅ Keep GitHub secrets secure
- ✅ Use SFTP (already configured)
- ✅ Regularly update dependencies
- ✅ Monitor server logs

## 📞 Support

If you encounter issues:
1. Check GitHub Actions logs
2. Review server error logs
3. Verify all checklist items
4. Check [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed docs

---

**Ready to deploy?** Push to `main` branch and watch it happen automatically! 🚀

