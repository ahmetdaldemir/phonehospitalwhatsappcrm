#!/bin/bash

# Backup script for Phone Hospital CRM
# Usage: ./scripts/backup.sh

set -e

BACKUP_DIR="${BACKUP_DIR:-/backups}"
DATE=$(date +%Y%m%d_%H%M%S)
PROJECT_DIR="${PROJECT_DIR:-/var/www/phonehospital}"

# Create backup directory if it doesn't exist
mkdir -p "${BACKUP_DIR}"

# Load environment variables
if [ -f "${PROJECT_DIR}/.env" ]; then
    export $(cat "${PROJECT_DIR}/.env" | grep -v '^#' | xargs)
fi

echo "Starting backup at $(date)"

# Database backup
if [ ! -z "${DB_DATABASE}" ] && [ ! -z "${DB_PASSWORD}" ]; then
    echo "Backing up database..."
    docker-compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T mysql \
        mysqldump -u root -p"${DB_PASSWORD}" "${DB_DATABASE}" \
        > "${BACKUP_DIR}/db_${DATE}.sql"
    
    # Compress database backup
    gzip "${BACKUP_DIR}/db_${DATE}.sql"
    echo "Database backup completed: db_${DATE}.sql.gz"
fi

# Storage backup
if [ -d "${PROJECT_DIR}/storage/app" ]; then
    echo "Backing up storage..."
    tar -czf "${BACKUP_DIR}/storage_${DATE}.tar.gz" \
        -C "${PROJECT_DIR}" storage/app
    echo "Storage backup completed: storage_${DATE}.tar.gz"
fi

# Keep only last 7 days of backups
echo "Cleaning old backups..."
find "${BACKUP_DIR}" -name "db_*.sql.gz" -mtime +7 -delete
find "${BACKUP_DIR}" -name "storage_*.tar.gz" -mtime +7 -delete

echo "Backup completed at $(date)"
echo "Backup location: ${BACKUP_DIR}"


