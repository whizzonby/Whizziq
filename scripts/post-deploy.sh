#!/bin/bash

# Post-Deployment Script
# Run this script on the server after deployment

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="${PROJECT_DIR:-$SCRIPT_DIR}"

cd "$PROJECT_DIR"

log_info "Running post-deployment tasks..."

# Clear all caches
log_info "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Optimize for production
log_info "Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations
log_info "Running database migrations..."
php artisan migrate --force

# Generate sitemap
log_info "Generating sitemap..."
php artisan app:generate-sitemap || log_warn "Sitemap generation failed"

# Export configs
log_info "Exporting configs..."
php artisan app:export-configs || log_warn "Config export failed"

# Restart queue workers
log_info "Restarting queue workers..."
php artisan queue:restart || log_warn "Queue restart failed (queue may not be configured)"

# Restart Horizon if available
log_info "Restarting Horizon..."
php artisan horizon:terminate || log_warn "Horizon not running or not configured"

# Set permissions
log_info "Setting file permissions..."
chmod -R 775 storage bootstrap/cache || log_warn "Permission setting failed"
chmod -R 755 public || log_warn "Public permission setting failed"

log_info "Post-deployment tasks completed successfully! 🚀"

