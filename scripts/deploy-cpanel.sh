#!/bin/bash

# cPanel Deployment Script
# This script handles deployment to cPanel hosting via FTP/SFTP

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration (can be overridden by environment variables)
FTP_SERVER="${FTP_SERVER:-}"
FTP_USERNAME="${FTP_USERNAME:-}"
FTP_PASSWORD="${FTP_PASSWORD:-}"
FTP_PROTOCOL="${FTP_PROTOCOL:-ftp}"
FTP_PORT="${FTP_PORT:-21}"
REMOTE_PATH="${REMOTE_PATH:-public_html}"
LOCAL_PATH="${LOCAL_PATH:-.}"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    log_info "Checking requirements..."
    
    if ! command -v lftp &> /dev/null; then
        log_error "lftp is not installed. Installing..."
        if [[ "$OSTYPE" == "linux-gnu"* ]]; then
            sudo apt-get update && sudo apt-get install -y lftp
        elif [[ "$OSTYPE" == "darwin"* ]]; then
            brew install lftp
        else
            log_error "Please install lftp manually"
            exit 1
        fi
    fi
    
    if [ -z "$FTP_SERVER" ] || [ -z "$FTP_USERNAME" ] || [ -z "$FTP_PASSWORD" ]; then
        log_error "FTP credentials not set. Please set FTP_SERVER, FTP_USERNAME, and FTP_PASSWORD"
        exit 1
    fi
}

create_backup() {
    log_info "Creating backup on remote server..."
    
    BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    lftp -c "
    set ftp:ssl-allow no;
    open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_PROTOCOL://$FTP_SERVER:$FTP_PORT;
    cd $REMOTE_PATH;
    mkdir -p backups;
    mirror -R --reverse --delete --verbose --exclude-glob='*.log' --exclude-glob='cache/*' . backups/$BACKUP_NAME/ || true;
    quit;
    " || log_warn "Backup creation failed, continuing..."
}

deploy_files() {
    log_info "Deploying files to $FTP_SERVER:$REMOTE_PATH..."
    
    # Create exclude list
    EXCLUDE_LIST="
    --exclude-glob=.git*
    --exclude-glob=.github*
    --exclude-glob=node_modules/**
    --exclude-glob=.env
    --exclude-glob=.env.*
    --exclude-glob=tests/**
    --exclude-glob=storage/logs/**
    --exclude-glob=storage/framework/cache/**
    --exclude-glob=storage/framework/sessions/**
    --exclude-glob=storage/framework/views/**
    --exclude-glob=backups/**
    --exclude-glob=docker/**
    --exclude-glob=*.md
    --exclude-glob=.idea/**
    --exclude-glob=.vscode/**
    "
    
    lftp -c "
    set ftp:ssl-allow no;
    set ftp:list-options -a;
    open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_PROTOCOL://$FTP_SERVER:$FTP_PORT;
    cd $REMOTE_PATH;
    lcd $LOCAL_PATH;
    mirror --reverse --delete --verbose $EXCLUDE_LIST . .;
    quit;
    "
    
    log_info "Files deployed successfully!"
}

set_permissions() {
    log_info "Setting file permissions..."
    
    lftp -c "
    set ftp:ssl-allow no;
    open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_PROTOCOL://$FTP_SERVER:$FTP_PORT;
    cd $REMOTE_PATH;
    chmod 755 storage bootstrap/cache;
    chmod -R 775 storage bootstrap/cache;
    quit;
    " || log_warn "Permission setting failed"
}

run_post_deploy() {
    log_info "Running post-deployment commands..."
    
    # If SSH is available, run artisan commands
    if [ ! -z "$SSH_HOST" ] && [ ! -z "$SSH_USERNAME" ]; then
        ssh -o StrictHostKeyChecking=no $SSH_USERNAME@$SSH_HOST << EOF
            cd $REMOTE_PATH
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan migrate --force
            php artisan queue:restart || true
EOF
    else
        log_warn "SSH not configured, skipping post-deployment commands"
        log_info "Please run these commands manually on the server:"
        echo "  php artisan config:cache"
        echo "  php artisan route:cache"
        echo "  php artisan view:cache"
        echo "  php artisan migrate --force"
        echo "  php artisan queue:restart"
    fi
}

# Main execution
main() {
    log_info "Starting deployment to cPanel..."
    
    check_requirements
    create_backup
    deploy_files
    set_permissions
    run_post_deploy
    
    log_info "Deployment completed successfully! 🚀"
}

# Run main function
main

