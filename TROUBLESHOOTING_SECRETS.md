# Troubleshooting: Adding GitHub Secrets

## Quick Check - Are You in the Right Place?

Make sure you're here:
1. GitHub Repository → **Settings** (top menu)
2. **Secrets and variables** → **Actions** (left sidebar)
3. **Environments** (left sidebar) ← **MUST BE HERE**
4. Click on **production** environment
5. Then click **Add secret**

## Common Problems & Solutions

### Problem 1: "Secret names can only contain alphanumeric characters"

**Cause**: You're typing something wrong in the **Name** field

**Solution**: 
- The **Name** field should ONLY have: `FTP_SERVER`
- NO equals sign (=)
- NO spaces
- NO value

**Example of what you might be typing (WRONG):**
```
Name: FTP_SERVER=198.54.115.49  ❌
```

**What you should type (CORRECT):**
```
Name: FTP_SERVER  ✅
Secret: 198.54.115.49  ✅
```

### Problem 2: Can't find "Environments" option

**Solution**: 
- Make sure you clicked **"Secrets and variables"** first
- Then click **"Actions"**
- Then look for **"Environments"** in the left sidebar
- If you don't see it, you might need to create the environment first

### Problem 3: The form looks different

**What you should see:**
```
┌─────────────────────────────────────┐
│  Name *                             │
│  [Text box]                         │
│                                     │
│  Secret *                           │
│  [Text box]                         │
│                                     │
│  [Cancel]  [Add secret]             │
└─────────────────────────────────────┘
```

If you see something different, you might be in the wrong place.

## Step-by-Step: Adding FTP_SERVER Secret

Let's do this together, step by step:

### Step 1: Get to the Right Place
1. Go to your GitHub repository
2. Click **Settings** (top right, next to "Insights")
3. In left sidebar, click **"Secrets and variables"**
4. Click **"Actions"** (under "Secrets and variables")
5. Click **"Environments"** (in left sidebar)
6. Click on **"production"** (or create it if it doesn't exist)
7. Click **"Add secret"** button

### Step 2: Fill in the Form

You should now see a form with two fields:

**Field 1 - Name:**
- Click in the "Name" text box
- Type exactly this (no spaces, no equals sign): `FTP_SERVER`
- That's it! Just those 10 characters: F-T-P-underscore-S-E-R-V-E-R

**Field 2 - Secret:**
- Click in the "Secret" text box
- Type exactly this: `198.54.115.49`
- That's it! Just the IP address

### Step 3: Save
- Click the **"Add secret"** button at the bottom
- You should see "FTP_SERVER" appear in your secrets list

## Test: Try This Exact Example

Let's test with the simplest one first:

1. Click **"Add secret"**
2. In **Name** field, type: `TEST_SECRET`
3. In **Secret** field, type: `12345`
4. Click **"Add secret"**

Did that work? If yes, then the problem was with the secret name or value. If no, tell me what error you got.

## Copy-Paste Ready Secrets

Here are the exact values to copy-paste (one at a time):

### Secret 1:
- **Name**: `FTP_SERVER`
- **Secret**: `198.54.115.49`

### Secret 2:
- **Name**: `FTP_USERNAME`
- **Secret**: `whizeakm`

### Secret 3:
- **Name**: `FTP_PASSWORD`
- **Secret**: `MLDtEzKSVIsx`

### Secret 4:
- **Name**: `FTP_PROTOCOL`
- **Secret**: `ftp`

### Secret 5:
- **Name**: `FTP_PORT`
- **Secret**: `21`

### Secret 6:
- **Name**: `FTP_DEPLOY_PATH`
- **Secret**: `public_html`

### Secret 7:
- **Name**: `PRODUCTION_URL`
- **Secret**: `https://www.whizziq.com`

## Still Not Working?

Tell me:
1. **What exact error message** do you see? (Copy and paste it)
2. **What are you typing** in the Name field? (Type it here)
3. **What are you typing** in the Secret field? (Type it here)
4. **Where exactly** are you? (Take a screenshot or describe what you see)

## Alternative: Use Repository Secrets (Easier)

If Environment secrets are too complicated, we can use Repository secrets instead:

1. Go to: Settings → Secrets and variables → Actions
2. Click **"Secrets"** tab (not Environments)
3. Click **"New repository secret"**
4. Add secrets the same way

**Note**: This is less secure but easier. We can switch the workflow to use repository secrets if needed.

## Need More Help?

Describe exactly what's happening:
- What page are you on?
- What do you see?
- What happens when you click "Add secret"?
- What error message appears?

