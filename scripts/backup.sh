#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Art DB — daily backup
# ─────────────────────────────────────────────────────────────────────────────
#  pg_dump the database + tar storage/app uploads, gzip both, rotate older
#  copies, optionally push to S3-compatible remote.
#
#  Intended to be run as the deploy user from cron:
#      30 2 * * * /var/www/art-db.org/scripts/backup.sh >> /var/log/artdb-backup.log 2>&1
#
#  Reads DB credentials from the app's .env so it doesn't drift from the live
#  config. Requires: pg_dump (postgresql-client), tar, gzip. Optional: aws
#  (only when AWS_BACKUP_BUCKET is set in .env).
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# ── Config (override via env vars on the cron line if needed) ────────────────
APP_ROOT="${APP_ROOT:-/var/www/art-db.org}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/artdb}"
KEEP_DAYS="${KEEP_DAYS:-14}"          # local retention in days

# ── Load .env so we use whatever credentials the live app uses ───────────────
if [[ ! -f "$APP_ROOT/.env" ]]; then
    echo "[$(date -Iseconds)] FATAL: $APP_ROOT/.env not found"
    exit 1
fi
# Hand-rolled .env parser so this works on any /bin/bash version (macOS still
# ships 3.2). Skips comments / empty lines, strips surrounding quotes, doesn't
# do recursive ${...} interpolation (we only need raw DB creds anyway).
while IFS='=' read -r key val; do
    [[ -z "$key" || "$key" == \#* ]] && continue
    val="${val%$'\r'}"
    val="${val%\"}"; val="${val#\"}"
    val="${val%\'}"; val="${val#\'}"
    export "$key=$val"
done < "$APP_ROOT/.env"

: "${DB_DATABASE:?DB_DATABASE missing in .env}"
: "${DB_USERNAME:?DB_USERNAME missing in .env}"
: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=5432}"

mkdir -p "$BACKUP_DIR"
TS=$(date -u +%Y-%m-%dT%H%M%SZ)
STAMP="artdb-${TS}"

# ── Database ────────────────────────────────────────────────────────────────
DB_FILE="$BACKUP_DIR/${STAMP}.sql.gz"
echo "[$(date -Iseconds)] pg_dump → $DB_FILE"
PGPASSWORD="${DB_PASSWORD:-}" pg_dump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --username="$DB_USERNAME" \
    --dbname="$DB_DATABASE" \
    --no-owner --no-privileges --clean --if-exists \
    | gzip -9 > "$DB_FILE"

DB_BYTES=$(stat -c%s "$DB_FILE" 2>/dev/null || stat -f%z "$DB_FILE")
echo "[$(date -Iseconds)]   db: $(numfmt --to=iec --suffix=B "$DB_BYTES" 2>/dev/null || echo "${DB_BYTES}B")"

# ── Uploads (storage/app — Filament file uploads when FILESYSTEM_DISK=local) ─
# When FILESYSTEM_DISK=s3 the files live in the bucket itself (versioned there);
# the local storage/app dir holds nothing we'd lose, so skip the tar.
if [[ "${FILESYSTEM_DISK:-local}" == "local" || "${FILESYSTEM_DISK:-}" == "public" ]]; then
    UP_FILE="$BACKUP_DIR/${STAMP}-storage.tar.gz"
    if [[ -d "$APP_ROOT/storage/app" ]]; then
        echo "[$(date -Iseconds)] tar storage/app → $UP_FILE"
        tar -C "$APP_ROOT/storage" -czf "$UP_FILE" app
        UP_BYTES=$(stat -c%s "$UP_FILE" 2>/dev/null || stat -f%z "$UP_FILE")
        echo "[$(date -Iseconds)]   storage: $(numfmt --to=iec --suffix=B "$UP_BYTES" 2>/dev/null || echo "${UP_BYTES}B")"
    fi
else
    echo "[$(date -Iseconds)] FILESYSTEM_DISK=${FILESYSTEM_DISK} → uploads live in bucket, skipping tar"
fi

# ── Optional S3 push (set AWS_BACKUP_BUCKET in .env to enable) ───────────────
if [[ -n "${AWS_BACKUP_BUCKET:-}" ]]; then
    if ! command -v aws &> /dev/null; then
        echo "[$(date -Iseconds)] WARN: aws CLI not installed, skipping remote upload"
    else
        REMOTE="s3://${AWS_BACKUP_BUCKET}/$(date -u +%Y/%m)"
        ENDPOINT_ARG=""
        if [[ -n "${AWS_ENDPOINT:-}" ]]; then
            ENDPOINT_ARG="--endpoint-url ${AWS_ENDPOINT}"
        fi
        echo "[$(date -Iseconds)] aws s3 cp → ${REMOTE}/"
        # shellcheck disable=SC2086
        aws ${ENDPOINT_ARG} s3 cp "$DB_FILE" "${REMOTE}/$(basename "$DB_FILE")"
        if [[ -n "${UP_FILE:-}" && -f "$UP_FILE" ]]; then
            # shellcheck disable=SC2086
            aws ${ENDPOINT_ARG} s3 cp "$UP_FILE" "${REMOTE}/$(basename "$UP_FILE")"
        fi
    fi
fi

# ── Rotation — drop local backups older than KEEP_DAYS ───────────────────────
echo "[$(date -Iseconds)] rotating local backups older than ${KEEP_DAYS} day(s)"
find "$BACKUP_DIR" -maxdepth 1 -name 'artdb-*' -mtime "+${KEEP_DAYS}" -print -delete || true

echo "[$(date -Iseconds)] done"
