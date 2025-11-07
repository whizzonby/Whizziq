#!/bin/bash

# Clear all Laravel caches on production
# Run this script after deploying the fix for the price null error

echo "Clearing Laravel caches..."

# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache (IMPORTANT for Blade templates)
php artisan view:clear

# Clear compiled classes
php artisan clear-compiled

# Rebuild optimized cache (optional, for better performance)
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

# Clear OPcache if enabled (requires web server restart or OPcache reset)
echo ""
echo "Cache clearing complete!"
echo ""
echo "IMPORTANT: If you're using OPcache, you may need to:"
echo "1. Restart your PHP-FPM service: sudo systemctl restart php8.2-fpm"
echo "2. Or restart your web server: sudo systemctl restart nginx/apache"
echo ""
echo "If using Laravel Forge/Vapor, use their dashboard to clear OPcache."

