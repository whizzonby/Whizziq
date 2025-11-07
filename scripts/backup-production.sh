#!/bin/bash

# Production Backup Script
# Creates a complete backup of the production environment

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
BACKUP_DIR="${BACKUP_DIR:-./backups}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_NAME="production-backup-$TIMESTAMP"

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Create backup directory
mkdir -p "$BACKUP_DIR/$BACKUP_NAME"

log_info "Creating production backup: $BACKUP_NAME"

# Backup database (if credentials provided)
if [ ! -z "$DB_HOST" ] && [ ! -z "$DB_DATABASE" ] && [ ! -z "$DB_USERNAME" ]; then
    log_info "Backing up database..."
    mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/$BACKUP_NAME/database.sql" || {
        log_error "Database backup failed"
    }
fi

# Backup files (via FTP/SFTP if configured)
if [ ! -z "$FTP_SERVER" ] && [ ! -z "$FTP_USERNAME" ]; then
    log_info "Backing up files from server..."
    
    lftp -c "
    set ftp:ssl-allow no;
    open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_PROTOCOL://$FTP_SERVER:$FTP_PORT;
    cd $REMOTE_PATH;
    mirror --verbose --exclude-glob='storage/logs/**' --exclude-glob='storage/framework/cache/**' . $BACKUP_DIR/$BACKUP_NAME/files/;
    quit;
    " || log_error "File backup failed"
fi

# Create archive
log_info "Creating backup archive..."
cd "$BACKUP_DIR"
tar -czf "$BACKUP_NAME.tar.gz" "$BACKUP_NAME"
rm -rf "$BACKUP_NAME"

log_info "Backup completed: $BACKUP_DIR/$BACKUP_NAME.tar.gz"

# Optional: Upload to cloud storage (S3, etc.)
if [ ! -z "$BACKUP_S3_BUCKET" ]; then
    log_info "Uploading backup to S3..."
    aws s3 cp "$BACKUP_NAME.tar.gz" "s3://$BACKUP_S3_BUCKET/backups/" || log_error "S3 upload failed"
fi

