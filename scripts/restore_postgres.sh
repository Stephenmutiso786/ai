#!/usr/bin/env bash
set -Eeuo pipefail
FILE="${1:?Usage: restore_postgres.sh /secure/path/backup.dump}"; : "${DB_HOST:?}"; : "${DB_DATABASE:?}"; : "${DB_USERNAME:?}"; : "${PGPASSWORD:?}"
pg_restore --clean --if-exists -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" "$FILE"
