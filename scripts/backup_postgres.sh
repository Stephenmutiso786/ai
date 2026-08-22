#!/usr/bin/env bash
set -Eeuo pipefail
: "${BACKUP_DIR:?Set BACKUP_DIR}"; : "${DB_HOST:?Set DB_HOST}"; : "${DB_DATABASE:?Set DB_DATABASE}"; : "${DB_USERNAME:?Set DB_USERNAME}"; : "${PGPASSWORD:?Set PGPASSWORD}"
STAMP=$(date -u +%Y%m%dT%H%M%SZ); mkdir -p "$BACKUP_DIR"; FILE="$BACKUP_DIR/stetech-${STAMP}.dump"
pg_dump -Fc -h "$DB_HOST" -U "$DB_USERNAME" "$DB_DATABASE" > "$FILE"
sha256sum "$FILE" > "$FILE.sha256"
find "$BACKUP_DIR" -type f -name 'stetech-*.dump' -mtime +"${BACKUP_RETENTION_DAYS:-30}" -delete
