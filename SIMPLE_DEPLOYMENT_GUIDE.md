# Simple Step-by-Step Deployment Guide

Don't worry! This guide will walk you through everything one step at a time. 🚀

## What We're Doing

We're setting up your project so that when you push code to GitHub, it automatically deploys to your website. That's it!

---

## PART 1: GitHub Setup (5 minutes)

### Step 1: Go to Your GitHub Repository

1. Open your browser
2. Go to your GitHub repository (the one with your WhizIQ code)
3. Click on **Settings** (top menu, next to "Insights")

### Step 2: Find the Secrets Section

1. In the left sidebar, click **Secrets and variables**
2. Click **Actions** (under "Secrets and variables")
3. Click **Environments** (in the left sidebar)

### Step 3: Create Production Environment

1. Click the green button **New environment**
2. Type: `production`
3. Click **Configure environment**

### Step 4: Add Your Secrets (One by One)

You'll add 7 secrets. Click **Add secret** for each one:

#### Secret 1: FTP_SERVER
- **Name**: `FTP_SERVER`
- **Value**: `ftp.whizziq.com`
- Click **Add secret**

#### Secret 2: FTP_USERNAME
- **Name**: `FTP_USERNAME`
- **Value**: `whizeakm`
- Click **Add secret**

#### Secret 3: FTP_PASSWORD
- **Name**: `FTP_PASSWORD`
- **Value**: (paste your FTP password here)
- Click **Add secret**

#### Secret 4: FTP_PROTOCOL
- **Name**: `FTP_PROTOCOL`
- **Value**: `sftp`
- Click **Add secret**

#### Secret 5: FTP_PORT
- **Name**: `FTP_PORT`
- **Value**: `21098`
- Click **Add secret**

#### Secret 6: FTP_DEPLOY_PATH
- **Name**: `FTP_DEPLOY_PATH`
- **Value**: `public_html`
- Click **Add secret**

#### Secret 7: PRODUCTION_URL
- **Name**: `PRODUCTION_URL`
- **Value**: `https://www.whizziq.com`
- Click **Add secret**

✅ **Done with GitHub!** You should see all 7 secrets listed.

---

## PART 2: Upload .env File to Server (5 minutes)

### Step 1: Get Your .env File

1. On your computer, find your `.env` file in your project folder
2. If you don't have one, copy `.env.example` and rename it to `.env`
3. Make sure it has your database settings and other configurations

### Step 2: Connect to Your Server

You can use **FileZilla** (free) or **cPanel File Manager**:

#### Option A: Using cPanel File Manager (Easiest)

1. Log into your Namecheap cPanel
2. Find **File Manager** and click it
3. Navigate to `public_html` folder
4. Click **Upload** button
5. Select your `.env` file
6. Wait for upload to complete

#### Option B: Using FileZilla

1. Download FileZilla: https://filezilla-project.org/
2. Open FileZilla
3. Enter these details:
   - **Host**: `ftp.whizziq.com`
   - **Username**: `whizeakm`
   - **Password**: (your FTP password)
   - **Port**: `21098`
   - **Protocol**: SFTP
4. Click **Quickconnect**
5. On the right side, go to `public_html` folder
6. Drag your `.env` file from left (your computer) to right (server)
7. Wait for upload

✅ **Done!** Your `.env` file is now on the server.

---

## PART 3: Set File Permissions (2 minutes)

### Using cPanel File Manager:

1. In cPanel File Manager, go to `public_html`
2. Right-click on `storage` folder → **Change Permissions**
3. Set to `775` → Click **Change Permissions**
4. Right-click on `bootstrap/cache` folder → **Change Permissions**
5. Set to `775` → Click **Change Permissions**

✅ **Done!**

---

## PART 4: Test Your First Deployment (3 minutes)

### Step 1: Make a Small Change

1. Open any file in your project (like `README.md`)
2. Add a space or a comment (something tiny)
3. Save the file

### Step 2: Push to GitHub

Open your terminal/command prompt in your project folder and type:

```bash
git add .
git commit -m "Test deployment"
git push origin main
```

### Step 3: Watch It Deploy

1. Go to your GitHub repository
2. Click **Actions** tab (top menu)
3. You'll see a workflow running called "Deploy to Production"
4. Click on it to watch the progress
5. Wait 2-5 minutes for it to complete

### Step 4: Check Your Website

1. Go to https://www.whizziq.com
2. Your site should be updated!

✅ **Congratulations!** Your first deployment worked!

---

## What Happens Next?

From now on, whenever you:

1. Make changes to your code
2. Run: `git add .`
3. Run: `git commit -m "Your message"`
4. Run: `git push origin main`

Your website will automatically update in 2-5 minutes! 🎉

---

## Troubleshooting

### "Deployment Failed" Error

1. Go to GitHub → Actions tab
2. Click on the failed workflow
3. Read the error message
4. Common fixes:
   - Check if `.env` file is uploaded
   - Check if FTP password is correct
   - Check if port `21098` is correct

### Website Not Updating

1. Wait 5 minutes (sometimes it takes time)
2. Clear your browser cache (Ctrl+F5)
3. Check GitHub Actions to see if deployment completed

### Need Help?

- Check the error message in GitHub Actions
- Make sure all 7 secrets are added correctly
- Make sure `.env` file is in `public_html` folder

---

## Quick Reference

**GitHub Secrets Location:**
Settings → Secrets and variables → Actions → Environments → production

**Your Secrets:**
- FTP_SERVER: `ftp.whizziq.com`
- FTP_USERNAME: `whizeakm`
- FTP_PASSWORD: (your password)
- FTP_PROTOCOL: `sftp`
- FTP_PORT: `21098`
- FTP_DEPLOY_PATH: `public_html`
- PRODUCTION_URL: `https://www.whizziq.com`

**To Deploy:**
```bash
git add .
git commit -m "Your message"
git push origin main
```

---

That's it! You're all set! 🚀

