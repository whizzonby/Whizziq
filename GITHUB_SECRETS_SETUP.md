# GitHub Secrets Setup Guide

## Environment Secrets vs Repository Secrets

### ✅ Use **Environment Secrets** (Recommended)

Your workflows are configured to use **environments**, so you should use **Environment secrets** for:
- Production deployment credentials
- Staging deployment credentials
- Environment-specific configurations

**Benefits:**
- ✅ Better security isolation
- ✅ Different credentials for staging vs production
- ✅ Environment protection rules (require approvals, etc.)
- ✅ Better organization

### ❌ Repository Secrets (Alternative)

You can use **Repository secrets** if you want simplicity, but:
- ⚠️ Same credentials for all environments
- ⚠️ Less secure (all workflows can access)
- ⚠️ No environment protection

## Step-by-Step: Setting Up Environment Secrets

### 1. Create Production Environment

1. Go to your GitHub repository
2. Click **Settings** (top menu)
3. Click **Secrets and variables** → **Actions** (left sidebar)
4. Click **Environments** (left sidebar, under "Secrets and variables")
5. Click **New environment**
6. Enter name: `production`
7. Click **Configure environment**

### 2. Add Production Secrets

In the production environment page, click **Add secret** for each:

| Secret Name | Value | Description |
|------------|-------|-------------|
| `FTP_SERVER` | `ftp.yourdomain.com` | Your FTP server address |
| `FTP_USERNAME` | `your_cpanel_username` | Your cPanel FTP username |
| `FTP_PASSWORD` | `your_ftp_password` | Your FTP password |
| `FTP_PROTOCOL` | `ftp` or `sftp` | Protocol to use |
| `FTP_PORT` | `21` (FTP) or `22` (SFTP) | FTP port number |
| `FTP_DEPLOY_PATH` | `public_html` | Path on server where app is deployed |
| `PRODUCTION_URL` | `https://yourdomain.com` | Your production URL |
| `SSH_HOST` | `yourdomain.com` | (Optional) SSH host for post-deploy commands |
| `SSH_USERNAME` | `your_ssh_username` | (Optional) SSH username |
| `SSH_PRIVATE_KEY` | `-----BEGIN RSA PRIVATE KEY-----...` | (Optional) SSH private key |
| `SSH_PORT` | `22` | (Optional) SSH port |

### 3. Create Staging Environment (Optional)

If you want a staging environment:

1. In **Environments**, click **New environment**
2. Enter name: `staging`
3. Click **Configure environment**
4. Add staging-specific secrets:

| Secret Name | Value | Description |
|------------|-------|-------------|
| `STAGING_FTP_SERVER` | `staging.yourdomain.com` | Staging FTP server |
| `STAGING_FTP_USERNAME` | `your_staging_username` | Staging FTP username |
| `STAGING_FTP_PASSWORD` | `your_staging_password` | Staging FTP password |
| `STAGING_FTP_PROTOCOL` | `ftp` | Staging protocol |
| `STAGING_FTP_PORT` | `21` | Staging FTP port |
| `STAGING_FTP_DEPLOY_PATH` | `staging` | Staging deployment path |
| `STAGING_URL` | `https://staging.yourdomain.com` | Staging URL |

### 4. Optional: Environment Protection Rules

For production, you can add protection rules:

1. In the production environment settings
2. Scroll to **Deployment branches**
3. Select **Selected branches** → Add `main`
4. (Optional) Enable **Required reviewers** for manual approval
5. (Optional) Enable **Wait timer** to delay deployments

## Visual Guide

```
GitHub Repository
└── Settings
    └── Secrets and variables
        └── Actions
            ├── Secrets (Repository secrets - not recommended)
            └── Environments ← USE THIS
                ├── production ← Create this
                │   └── Secrets
                │       ├── FTP_SERVER
                │       ├── FTP_USERNAME
                │       ├── FTP_PASSWORD
                │       └── ... (other secrets)
                └── staging ← Create this (optional)
                    └── Secrets
                        ├── STAGING_FTP_SERVER
                        └── ... (staging secrets)
```

## Quick Reference

### Where to Add Secrets?

**✅ Correct**: 
- Settings → Secrets and variables → Actions → **Environments** → production → Add secret

**❌ Incorrect**:
- Settings → Secrets and variables → Actions → **Secrets** → New repository secret

### Which Environment?

- **Production secrets** → `production` environment
- **Staging secrets** → `staging` environment

## Troubleshooting

### "Secret not found" Error

**Problem**: Workflow can't find the secret

**Solution**: 
- Make sure you added the secret to the correct **environment**
- Check the environment name matches the workflow (case-sensitive)
- Verify the secret name matches exactly (case-sensitive)

### Workflow Not Using Environment

**Problem**: Workflow runs but doesn't use environment secrets

**Solution**:
- Check the workflow file has `environment: name: production`
- Verify the environment name matches exactly

### Can't See Environments Option

**Problem**: Don't see "Environments" in the sidebar

**Solution**:
- Make sure you're in **Settings** → **Secrets and variables** → **Actions**
- Environments option is in the left sidebar
- You need repository admin access

## Security Best Practices

1. ✅ **Use Environment secrets** for sensitive data
2. ✅ **Different credentials** for staging and production
3. ✅ **Enable protection rules** for production
4. ✅ **Rotate credentials** regularly
5. ✅ **Never commit secrets** to code
6. ✅ **Use least privilege** - only grant necessary access

## Next Steps

After setting up secrets:

1. ✅ Verify secrets are added correctly
2. ✅ Test deployment with a small change
3. ✅ Monitor GitHub Actions logs
4. ✅ Verify deployment on your server

---

**Need Help?**
- Check workflow logs in GitHub Actions tab
- Verify environment names match exactly
- Ensure you have admin access to the repository

