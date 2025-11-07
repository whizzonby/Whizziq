# Step-by-Step Checklist

Follow this checklist in order. Check off each step as you complete it.

## ✅ PART 1: GitHub Secrets (Do This First)

- [ ] **Step 1.1**: Go to GitHub → Your Repository → Settings
- [ ] **Step 1.2**: Click "Secrets and variables" → "Actions"
- [ ] **Step 1.3**: Click "Environments" (left sidebar)
- [ ] **Step 1.4**: Click "New environment"
- [ ] **Step 1.5**: Type "production" and click "Configure environment"
- [ ] **Step 1.6**: Add secret: `FTP_SERVER` = `ftp.whizziq.com`
- [ ] **Step 1.7**: Add secret: `FTP_USERNAME` = `whizeakm`
- [ ] **Step 1.8**: Add secret: `FTP_PASSWORD` = (your password)
- [ ] **Step 1.9**: Add secret: `FTP_PROTOCOL` = `sftp`
- [ ] **Step 1.10**: Add secret: `FTP_PORT` = `21098`
- [ ] **Step 1.11**: Add secret: `FTP_DEPLOY_PATH` = `public_html`
- [ ] **Step 1.12**: Add secret: `PRODUCTION_URL` = `https://www.whizziq.com`

**✅ PART 1 COMPLETE** - All 7 secrets added!

---

## ✅ PART 2: Upload .env File

- [ ] **Step 2.1**: Find your `.env` file on your computer
- [ ] **Step 2.2**: Log into Namecheap cPanel
- [ ] **Step 2.3**: Open "File Manager"
- [ ] **Step 2.4**: Go to `public_html` folder
- [ ] **Step 2.5**: Click "Upload" button
- [ ] **Step 2.6**: Select your `.env` file
- [ ] **Step 2.7**: Wait for upload to finish

**✅ PART 2 COMPLETE** - .env file uploaded!

---

## ✅ PART 3: Set Permissions

- [ ] **Step 3.1**: In File Manager, right-click `storage` folder
- [ ] **Step 3.2**: Click "Change Permissions"
- [ ] **Step 3.3**: Set to `775` and click "Change Permissions"
- [ ] **Step 3.4**: Right-click `bootstrap/cache` folder
- [ ] **Step 3.5**: Click "Change Permissions"
- [ ] **Step 3.6**: Set to `775` and click "Change Permissions"

**✅ PART 3 COMPLETE** - Permissions set!

---

## ✅ PART 4: Test Deployment

- [ ] **Step 4.1**: Make a small change to any file (add a space)
- [ ] **Step 4.2**: Open terminal in your project folder
- [ ] **Step 4.3**: Run: `git add .`
- [ ] **Step 4.4**: Run: `git commit -m "Test deployment"`
- [ ] **Step 4.5**: Run: `git push origin main`
- [ ] **Step 4.6**: Go to GitHub → Actions tab
- [ ] **Step 4.7**: Watch the workflow run (wait 2-5 minutes)
- [ ] **Step 4.8**: Check your website: https://www.whizziq.com
- [ ] **Step 4.9**: Verify your change is live!

**✅ PART 4 COMPLETE** - First deployment successful!

---

## 🎉 You're Done!

From now on, just use these 3 commands to deploy:

```bash
git add .
git commit -m "Your message"
git push origin main
```

Your website will update automatically! 🚀

---

## Need Help?

If you get stuck on any step:

1. **Check the error message** in GitHub Actions
2. **Verify all secrets** are added correctly
3. **Make sure .env file** is uploaded
4. **Check file permissions** are set to 775

---

**Current Status**: 
- [ ] Part 1 Complete
- [ ] Part 2 Complete  
- [ ] Part 3 Complete
- [ ] Part 4 Complete

