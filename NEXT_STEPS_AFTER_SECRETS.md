# Next Steps - You've Added the Secrets! ✅

Great job! Now let's finish the setup.

## ✅ What You've Done

You've successfully added your GitHub secrets. Make sure you have all 7:

- [ ] `FTP_SERVER`
- [ ] `FTP_USERNAME`
- [ ] `FTP_PASSWORD`
- [ ] `FTP_PROTOCOL`
- [ ] `FTP_PORT`
- [ ] `FTP_DEPLOY_PATH`
- [ ] `PRODUCTION_URL`

## 📋 Next Steps (In Order)

### Step 1: Upload .env File to Server ⚠️ IMPORTANT

**This is required before your first deployment!**

#### Option A: Using cPanel File Manager (Easiest)

1. Log into your Namecheap cPanel
2. Find and click **"File Manager"**
3. Navigate to the **`public_html`** folder
4. Click the **"Upload"** button (top menu)
5. Select your `.env` file from your computer
6. Wait for upload to complete
7. Right-click the uploaded `.env` file → **Change Permissions** → Set to `644`

#### Option B: Using FTP Client (FileZilla)

1. Open FileZilla (or any FTP client)
2. Connect with:
   - **Host**: `198.54.115.49` (or `ftp.whizziq.com`)
   - **Username**: `whizeakm`
   - **Password**: `MLDtEzKSVIsx`
   - **Port**: `21`
   - **Protocol**: FTP
3. Navigate to `public_html` folder on the server (right side)
4. Drag your `.env` file from your computer (left side) to `public_html` (right side)
5. Wait for upload

**✅ Done when**: Your `.env` file is in the `public_html` folder on your server.

---

### Step 2: Set File Permissions (One-Time Setup)

#### Using cPanel File Manager:

1. In File Manager, go to `public_html`
2. Right-click on **`storage`** folder
3. Click **"Change Permissions"**
4. Set to `775` → Click **"Change Permissions"**
5. Right-click on **`bootstrap/cache`** folder
6. Click **"Change Permissions"**
7. Set to `775` → Click **"Change Permissions"`

**✅ Done when**: Both folders have `775` permissions.

---

### Step 3: Test Your First Deployment 🚀

Now let's test if everything works!

#### 3.1: Make a Small Change

1. Open any file in your project (like `README.md` or any PHP file)
2. Add a comment or a space (something tiny)
3. Save the file

#### 3.2: Push to GitHub

Open your terminal/command prompt in your project folder:

```bash
git add .
git commit -m "Test deployment"
git push origin main
```

#### 3.3: Watch It Deploy

1. Go to your GitHub repository
2. Click the **"Actions"** tab (top menu)
3. You'll see a workflow running called **"Deploy to Production"**
4. Click on it to watch the progress
5. Wait 2-5 minutes for it to complete

**What to look for:**
- ✅ Green checkmarks = Success!
- ❌ Red X = Error (check the logs)

#### 3.4: Check Your Website

1. Go to https://www.whizziq.com
2. Your site should be updated!
3. Check that everything loads correctly

**✅ Done when**: Your website is live and updated!

---

## 🎉 You're All Set!

After this first deployment works, you're done! From now on:

1. Make changes to your code
2. Run: `git add .`
3. Run: `git commit -m "Your message"`
4. Run: `git push origin main`

Your website will automatically update in 2-5 minutes! 🚀

---

## 🆘 If Something Goes Wrong

### Deployment Fails in GitHub Actions

1. Go to GitHub → Actions tab
2. Click on the failed workflow
3. Click on the failed step
4. Read the error message
5. Common issues:
   - `.env` file not uploaded → Upload it!
   - Wrong FTP credentials → Check your secrets
   - Permission errors → Set storage to 775

### Website Not Updating

1. Wait 5 minutes (sometimes it takes time)
2. Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)
3. Check GitHub Actions to see if deployment completed
4. Check server logs if you have access

### Need Help?

- Check the error message in GitHub Actions
- Make sure `.env` file is uploaded
- Verify all 7 secrets are correct
- Check file permissions (775 for storage)

---

## Quick Checklist

Before your first deployment:

- [x] ✅ GitHub secrets added (DONE!)
- [ ] ⏳ Upload `.env` file to server
- [ ] ⏳ Set file permissions (775 for storage)
- [ ] ⏳ Test deployment
- [ ] ⏳ Verify website is working

---

**You're almost there! Just upload the .env file and test it!** 🎯

