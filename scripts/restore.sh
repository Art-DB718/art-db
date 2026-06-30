#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Art DB — restore from a backup file
# ─────────────────────────────────────────────────────────────────────────────
#  Usage:
#      ./scripts/restore.sh /var/backups/artdb/artdb-2026-07-15T023000Z.sql.gz
#      ./scripts/restore.sh /var/backups/artdb/artdb-2026-07-15T023000Z-storage.tar.gz
#
#  Pick the right file (the DB dump is .sql.gz; uploads tarball is
#  -storage.tar.gz). The script detects which kind by filename + extension.
#
#  Destructive — prompts for confirmation before clobbering the live DB.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/art-db.org}"
FILE="${1:-}"

if [[ -z "$FILE" || ! -f "$FILE" ]]; then
    echo "Usage: $0 <backup-file.sql.gz | -storage.tar.gz>"
    exit 1
fi

# ── Load .env for DB credentials ────────────────────────────────────────────
if [[ ! -f "$APP_ROOT/.env" ]]; then
    echo "FATAL: $APP_ROOT/.env not found"
    exit 1
fi
set -a
# shellcheck disable=SC1091
source <(grep -v '^#' "$APP_ROOT/.env" | grep -v '^$' | sed 's/\r$//')
set +a

: "${DB_DATABASE:?DB_DATABASE missing}"
: "${DB_USERNAME:?DB_USERNAME missing}"
: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=5432}"

case "$FILE" in
    *-storage.tar.gz)
        echo "Restoring UPLOADS from $FILE → $APP_ROOT/storage/app"
        echo "This will overwrite existing files in storage/app. Continue? [y/N]"
        read -r confirm
        [[ "$confirm" == "y" || "$confirm" == "Y" ]] || { echo "aborted"; exit 1; }

        tar -C "$APP_ROOT/storage" -xzf "$FILE"
        echo "done — uploads restored"
        ;;

    *.sql.gz)
        echo "Restoring DATABASE from $FILE → $DB_DATABASE@$DB_HOST"
        echo "This will DROP and recreate every table in $DB_DATABASE. Continue? [y/N]"
        read -r confirm
        [[ "$confirm" == "y" || "$confirm" == "Y" ]] || { echo "aborted"; exit 1; }

        # The dump was created with --clean --if-exists, so DROP/CREATE is
        # baked into the SQL. Just pipe through psql.
        gunzip -c "$FILE" | PGPASSWORD="${DB_PASSWORD:-}" psql \
            --host="$DB_HOST" --port="$DB_PORT" \
            --username="$DB_USERNAME" --dbname="$DB_DATABASE"

        echo "done — database restored. Recommend running:"
        echo "  cd $APP_ROOT && php artisan migrate --force && php artisan config:cache"
        ;;

    *)
        echo "Unknown backup file type: $FILE"
        echo "Expected .sql.gz (database) or -storage.tar.gz (uploads)"
        exit 1
        ;;
esac
