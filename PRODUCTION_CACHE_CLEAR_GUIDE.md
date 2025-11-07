# Production Cache Clear Guide

## Issue
The error "Attempt to read property 'price' on null" occurs on production but not locally. This is typically due to cached views.

## Solution

After deploying the fix to `resources/views/components/filament/plans/one.blade.php`, you MUST clear all caches on production.

### Quick Fix (SSH into your server)

```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear  # ⚠️ MOST IMPORTANT - clears Blade view cache
php artisan clear-compiled

# If using OPcache (very common in production)
sudo systemctl restart php8.2-fpm  # Adjust PHP version as needed
# OR
sudo systemctl restart php-fpm

# If using Apache
sudo systemctl restart apache2

# If using Nginx (you'll still need to restart PHP-FPM)
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### Using Laravel Forge

1. Go to your server in Forge
2. Click "Reboot PHP-FPM"
3. Or use the "Quick Deploy" button (this clears caches automatically)

### Using Laravel Vapor

Vapor automatically clears caches on deployment, but you can manually clear via:
- Vapor CLI: `php vendor/bin/vapor cache:clear`
- Or redeploy: `php vendor/bin/vapor deploy`

### Using cPanel/Shared Hosting

1. SSH into your server (if available)
2. Navigate to your Laravel root: `cd public_html` or `cd domains/yourdomain.com/public_html`
3. Run: `php artisan view:clear`
4. If you have access to PHP OPcache settings, clear it via your hosting control panel

### Verify the Fix

After clearing caches, visit:
- https://whizziq.com/dashboard/subscriptions
- The error should be gone
- Plans without prices will show "Price not available" instead of crashing

### Why This Happens

- **Local**: Laravel runs in development mode, views are compiled on-the-fly
- **Production**: Views are pre-compiled and cached for performance
- **The Fix**: We added null checks (`$price && isset($price->price) && $price->currency`)
- **The Problem**: Old cached views still have the buggy code

### Prevention

Always clear view cache after deploying Blade template changes:
```bash
php artisan view:clear
```

Or add to your deployment script:
```bash
php artisan view:clear && php artisan config:cache && php artisan route:cache
```

