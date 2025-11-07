# Quick Start - Deployment Setup

This is a quick reference guide to get your CI/CD pipeline up and running.

## 🚀 5-Minute Setup

### Step 1: Configure GitHub Secrets (2 minutes)

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click on **Environments** (in the left sidebar)
4. Click **New environment** and create a "production" environment
5. Click **Add secret** in the production environment and add:

```
FTP_SERVER=ftp.yourdomain.com
FTP_USERNAME=your_cpanel_username
FTP_PASSWORD=your_ftp_password
FTP_PROTOCOL=ftp
FTP_PORT=21
FTP_DEPLOY_PATH=public_html
PRODUCTION_URL=https://yourdomain.com
```

### Step 2: Upload .env File to Server (1 minute)

1. Connect to your cPanel via FTP
2. Upload your `.env` file to the root directory (same level as `artisan`)
3. Set permissions to `644`

### Step 3: Push to Main Branch (1 minute)

```bash
git add .
git commit -m "Setup CI/CD"
git push origin main
```

### Step 4: Monitor Deployment (1 minute)

1. Go to **Actions** tab in GitHub
2. Watch the deployment workflow run
3. Check for any errors

## ✅ That's It!

Your application will now automatically:
- Run tests on every push
- Build assets
- Deploy to production
- Create backups

## 🔧 Optional: SSH Post-Deployment Commands

If you have SSH access, add these secrets to the **production environment** (same place you added FTP secrets):

```
SSH_HOST=yourdomain.com
SSH_USERNAME=your_ssh_username
SSH_PRIVATE_KEY=your_private_key
SSH_PORT=22
```

**Note**: Add these to the same **production environment** you created in Step 1.

## 📚 Need More Help?

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed documentation.

## 🆘 Troubleshooting

### Deployment Fails?

1. **Check FTP credentials** - Verify in GitHub Secrets
2. **Check server logs** - Look in `storage/logs/laravel.log`
3. **Verify .env file** - Make sure it's uploaded to server
4. **Check permissions** - Storage and cache directories need 775

### Assets Not Loading?

Run these commands on the server (via SSH or cPanel Terminal):

```bash
cd /path/to/your/app
npm run build
php artisan cache:clear
php artisan view:clear
```

### Database Issues?

```bash
php artisan migrate --force
php artisan config:cache
```

## 📞 Support

- Check [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed docs
- Review GitHub Actions logs for errors
- Check server error logs

---

**Next Steps:**
- Set up staging environment (see DEPLOYMENT.md)
- Configure scheduled backups
- Set up monitoring and alerts

