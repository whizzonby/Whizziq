# How to Add GitHub Secrets - Visual Guide

## The Problem

When adding secrets, you need to use **TWO separate fields**:
- **Name field**: Just the secret name (like `FTP_SERVER`)
- **Value field**: Just the value (like `198.54.115.49`)

## Step-by-Step: Adding Each Secret

### Secret 1: FTP_SERVER

1. Click **"Add secret"** button
2. In the **"Name"** field, type: `FTP_SERVER`
   - ❌ DON'T type: `FTP_SERVER=198.54.115.49`
   - ✅ DO type: `FTP_SERVER`
3. In the **"Secret"** field, type: `198.54.115.49`
4. Click **"Add secret"** button at the bottom

### Secret 2: FTP_USERNAME

1. Click **"Add secret"** button
2. **Name**: `FTP_USERNAME`
3. **Secret**: `whizeakm`
4. Click **"Add secret"**

### Secret 3: FTP_PASSWORD

1. Click **"Add secret"** button
2. **Name**: `FTP_PASSWORD`
3. **Secret**: `MLDtEzKSVIsx`
4. Click **"Add secret"**

### Secret 4: FTP_PROTOCOL

1. Click **"Add secret"** button
2. **Name**: `FTP_PROTOCOL`
3. **Secret**: `ftp`
4. Click **"Add secret"**

### Secret 5: FTP_PORT (IMPORTANT - Add this!)

1. Click **"Add secret"** button
2. **Name**: `FTP_PORT`
3. **Secret**: `21` (standard FTP port)
4. Click **"Add secret"**

### Secret 6: FTP_DEPLOY_PATH

1. Click **"Add secret"** button
2. **Name**: `FTP_DEPLOY_PATH`
3. **Secret**: `public_html`
4. Click **"Add secret"**

### Secret 7: PRODUCTION_URL

1. Click **"Add secret"** button
2. **Name**: `PRODUCTION_URL`
3. **Secret**: `https://www.whizziq.com`
4. Click **"Add secret"**

## Visual Example

When you click "Add secret", you'll see a form like this:

```
┌─────────────────────────────────────┐
│  Name *                             │
│  ┌───────────────────────────────┐  │
│  │ FTP_SERVER                    │  │  ← Type just the name here
│  └───────────────────────────────┘  │
│                                     │
│  Secret *                           │
│  ┌───────────────────────────────┐  │
│  │ 198.54.115.49                 │  │  ← Type just the value here
│  └───────────────────────────────┘  │
│                                     │
│  [Cancel]  [Add secret]             │
└─────────────────────────────────────┘
```

## Your Complete Secret List

Here's what you need to add (Name = Value):

| Name | Value |
|------|-------|
| `FTP_SERVER` | `198.54.115.49` |
| `FTP_USERNAME` | `whizeakm` |
| `FTP_PASSWORD` | `MLDtEzKSVIsx` |
| `FTP_PROTOCOL` | `ftp` |
| `FTP_PORT` | `21` |
| `FTP_DEPLOY_PATH` | `public_html` |
| `PRODUCTION_URL` | `https://www.whizziq.com` |

## Common Mistakes

❌ **WRONG**: 
- Name: `FTP_SERVER=198.54.115.49`
- This includes the equals sign and value

✅ **CORRECT**: 
- Name: `FTP_SERVER`
- Secret: `198.54.115.49`

## After Adding All Secrets

You should see a list like this:

```
Secrets (7)
├── FTP_SERVER
├── FTP_USERNAME
├── FTP_PASSWORD
├── FTP_PROTOCOL
├── FTP_PORT
├── FTP_DEPLOY_PATH
└── PRODUCTION_URL
```

## Need Help?

If you still get an error:
1. Make sure there are **no spaces** in the name
2. Make sure the name **starts with a letter** (FTP_SERVER is good)
3. Make sure you're using **underscores** (_) not dashes (-)
4. Don't include the `=` sign in the name

---

**Remember**: 
- **Name field** = Just the name (FTP_SERVER)
- **Secret field** = Just the value (198.54.115.49)

