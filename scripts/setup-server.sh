#!/bin/bash

# Server Setup Script
# Run this once on a fresh server to set up the environment

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_info "Setting up server for Laravel application..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
log_info "PHP Version: $PHP_VERSION"

if ! php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
    log_error "PHP 8.2 or higher is required"
    exit 1
fi

# Check required PHP extensions
REQUIRED_EXTENSIONS=("pdo_mysql" "mbstring" "xml" "bcmath" "openssl" "json" "tokenizer" "curl" "zip" "gd")

log_info "Checking PHP extensions..."
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        log_info "✓ $ext installed"
    else
        log_error "✗ $ext not installed"
        log_warn "Please install: sudo apt-get install php8.2-$ext"
    fi
done

# Check Composer
if command -v composer &> /dev/null; then
    log_info "✓ Composer installed: $(composer --version)"
else
    log_error "Composer not installed"
    log_warn "Install with: curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer"
    exit 1
fi

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    log_info "✓ Node.js installed: $NODE_VERSION"
else
    log_warn "Node.js not installed (required for building assets)"
    log_warn "Install with: curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - && sudo apt-get install -y nodejs"
fi

# Check npm
if command -v npm &> /dev/null; then
    log_info "✓ npm installed: $(npm -v)"
else
    log_warn "npm not installed"
fi

# Set up directory structure
log_info "Setting up directory structure..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
log_info "Setting directory permissions..."
chmod -R 775 storage bootstrap/cache
chmod -R 755 public

# Create storage link
if [ ! -L public/storage ]; then
    log_info "Creating storage symlink..."
    php artisan storage:link || log_warn "Storage link creation failed (may need to run manually)"
fi

# Check .env file
if [ ! -f .env ]; then
    log_warn ".env file not found"
    if [ -f .env.example ]; then
        log_info "Copying .env.example to .env..."
        cp .env.example .env
        log_warn "Please edit .env file with your configuration"
        log_warn "Then run: php artisan key:generate"
    else
        log_error ".env.example not found"
    fi
else
    log_info "✓ .env file exists"
fi

# Check if application key is set
if grep -q "APP_KEY=$" .env 2>/dev/null || ! grep -q "APP_KEY=" .env 2>/dev/null; then
    log_warn "Application key not set"
    log_info "Run: php artisan key:generate"
fi

log_info "Server setup completed!"
log_info "Next steps:"
log_info "1. Edit .env file with your configuration"
log_info "2. Run: php artisan key:generate"
log_info "3. Run: composer install --no-dev --optimize-autoloader"
log_info "4. Run: npm install && npm run build"
log_info "5. Run: php artisan migrate --force"
log_info "6. Set up cron job: * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"

