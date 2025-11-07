# Uploading Files via cPanel - What to Upload

## Important: What You Need to Upload

Since GitHub Actions will automatically deploy your code, you have two options:

---

## Option 1: Upload ONLY .env File (Recommended) ✅

**This is the easiest and recommended way!**

### What to Upload:
- Just your `.env` file

### Steps:
1. **Create a zip file** with just your `.env` file:
   - Right-click your `.env` file
   - Create a zip (or just upload the .env file directly - no need to zip a single file!)

2. **Upload via cPanel:**
   - Log into cPanel
   - Open File Manager
   - Go to `public_html` folder
   - Click "Upload"
   - Select your `.env` file
   - Wait for upload

3. **Extract if needed:**
   - If you zipped it, right-click the zip file → Extract
   - Delete the zip file after extraction

4. **Set permissions:**
   - Right-click `.env` file → Change Permissions → Set to `644`

**✅ Done!** GitHub Actions will deploy the rest of your code automatically.

---

## Option 2: Manual First-Time Upload (If Needed)

If you want to upload everything manually first (not recommended, but possible):

### What to Include in Zip:
- ✅ All project files EXCEPT:
  - ❌ `.git` folder (don't upload)
  - ❌ `node_modules` folder (don't upload - too large)
  - ❌ `vendor` folder (don't upload - too large, will install via composer)
  - ❌ `.env` file (upload separately or create on server)
  - ❌ `storage/logs/*` (empty logs folder is fine)
  - ❌ `storage/framework/cache/*` (empty cache is fine)
  - ❌ `storage/framework/sessions/*` (empty sessions is fine)
  - ❌ `storage/framework/views/*` (empty views is fine)

### What to Upload:
```
✅ app/
✅ bootstrap/
✅ config/
✅ database/
✅ public/
✅ resources/
✅ routes/
✅ storage/ (folders, but empty cache/sessions/views)
✅ .env (separate or in zip)
✅ artisan
✅ composer.json
✅ package.json
✅ All other config files
```

### Steps:
1. **Create zip file** with the files above
2. **Upload via cPanel:**
   - Go to `public_html`
   - Click "Upload"
   - Select your zip file
   - Wait for upload
3. **Extract:**
   - Right-click zip file → Extract
   - Delete zip file after extraction
4. **Install dependencies** (via SSH or cPanel Terminal):
   ```bash
   cd public_html
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   ```
5. **Set permissions:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chmod 644 .env
   ```

---

## Recommended Approach: Option 1 ✅

**Just upload the `.env` file!**

Here's why:
- ✅ GitHub Actions will deploy all your code automatically
- ✅ Faster and easier
- ✅ Less chance of errors
- ✅ No need to manually install dependencies
- ✅ No need to manually build assets

### Quick Steps for Option 1:

1. **Find your `.env` file** on your computer
2. **Log into cPanel**
3. **Open File Manager**
4. **Go to `public_html` folder**
5. **Click "Upload"**
6. **Select your `.env` file** (no need to zip a single file!)
7. **Wait for upload**
8. **Set permissions to 644:**
   - Right-click `.env` → Change Permissions → `644`

**That's it!** Then push to GitHub and let the automation handle the rest.

---

## After Upload

Once your `.env` file is uploaded:

1. **Set permissions** (if not done):
   - `storage` folder → `775`
   - `bootstrap/cache` folder → `775`
   - `.env` file → `644`

2. **Test deployment:**
   ```bash
   git add .
   git commit -m "Test deployment"
   git push origin main
   ```

3. **Watch GitHub Actions** deploy your code automatically!

---

## Need Help?

- **Single file upload**: No need to zip, just upload `.env` directly
- **Multiple files**: Zip them, upload, then extract
- **File too large**: Exclude `node_modules` and `vendor` folders

---

**Recommendation: Just upload the `.env` file and let GitHub Actions do the rest!** 🚀

