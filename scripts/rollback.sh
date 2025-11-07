#!/bin/bash

# Rollback Script
# Restores a previous deployment from backup

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

BACKUP_DIR="${BACKUP_DIR:-./backups}"
BACKUP_FILE="${1:-}"

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

if [ -z "$BACKUP_FILE" ]; then
    log_error "Usage: $0 <backup-file.tar.gz>"
    log_info "Available backups:"
    ls -lh "$BACKUP_DIR"/*.tar.gz 2>/dev/null || log_warn "No backups found"
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    log_error "Backup file not found: $BACKUP_FILE"
    exit 1
fi

log_warn "This will restore from backup: $BACKUP_FILE"
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    log_info "Rollback cancelled"
    exit 0
fi

# Extract backup
TEMP_DIR=$(mktemp -d)
log_info "Extracting backup..."
tar -xzf "$BACKUP_FILE" -C "$TEMP_DIR"

# Restore database
if [ -f "$TEMP_DIR"/*/database.sql ]; then
    log_info "Restoring database..."
    if [ ! -z "$DB_HOST" ] && [ ! -z "$DB_DATABASE" ] && [ ! -z "$DB_USERNAME" ]; then
        mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$TEMP_DIR"/*/database.sql
        log_info "Database restored"
    else
        log_warn "Database credentials not set, skipping database restore"
    fi
fi

# Restore files
if [ -d "$TEMP_DIR"/*/files ]; then
    log_info "Restoring files..."
    if [ ! -z "$FTP_SERVER" ] && [ ! -z "$FTP_USERNAME" ]; then
        lftp -c "
        set ftp:ssl-allow no;
        open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_PROTOCOL://$FTP_SERVER:$FTP_PORT;
        cd $REMOTE_PATH;
        mirror --reverse --delete --verbose $TEMP_DIR/*/files/ .;
        quit;
        "
        log_info "Files restored"
    else
        log_warn "FTP credentials not set, skipping file restore"
    fi
fi

# Cleanup
rm -rf "$TEMP_DIR"

log_info "Rollback completed successfully!"

