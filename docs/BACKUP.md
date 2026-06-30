# Art DB — Backup & Restore

Daily `pg_dump` + (optional) uploads tar, rotated locally for 14 days, optionally pushed off-box to S3 / R2 / B2. Restore is a single shell command.

If you only read one section: [Test restore monthly](#test-restore-monthly). A backup you've never restored is a Schrödinger backup.

---

## What's backed up

| Component | Backup | Notes |
|---|---|---|
| **Database** (`artdb`) | `pg_dump --clean --if-exists \| gzip` | All Filament data: users, artworks, artists, subscriptions, inquiries, contacts, sales, exhibitions, collections, etc. |
| **Local uploads** (`storage/app/`) | `tar -czf ... app/` | Only when `FILESYSTEM_DISK=local` or `=public`. Skipped when `=s3` — bucket has its own versioning. |
| Code | — (not backed up) | Lives in git; deploy by `git pull` |
| `.env` | — (not backed up) | Keep a copy in a password manager (1Password / Bitwarden) |

Backups land in `/var/backups/artdb/` by default. Override via `BACKUP_DIR` env on the cron line.

---

## Install on the server

```bash
# Copy the script (it ships in the repo at scripts/backup.sh)
sudo install -m 750 /var/www/art-db.org/scripts/backup.sh /usr/local/bin/artdb-backup
sudo install -m 750 /var/www/art-db.org/scripts/restore.sh /usr/local/bin/artdb-restore

# Backup destination dir, owned by the deploy user
sudo mkdir -p /var/backups/artdb
sudo chown deploy:deploy /var/backups/artdb
sudo chmod 750 /var/backups/artdb
```

Add to the deploy user's crontab — daily at 02:30 (before the 10:00 trial-reminder cron, well after midnight DB activity dies down):

```bash
sudo crontab -u deploy -e
```

Append:

```cron
30 2 * * * /usr/local/bin/artdb-backup >> /var/log/artdb-backup.log 2>&1
```

Make sure the log file exists and the deploy user can write to it:

```bash
sudo touch /var/log/artdb-backup.log
sudo chown deploy:deploy /var/log/artdb-backup.log
```

---

## Off-site upload (S3 / R2 / Backblaze)

Local backups are useless when the whole server dies. Push them off-box.

### 1. Create a dedicated backup bucket

A separate bucket from your uploads bucket — different access policy (write-only ideally), and you don't want the app to be able to nuke its own backups.

| Provider | Cheapest sane tier |
|---|---|
| **Cloudflare R2** | $0.015/GB/mo, no egress fees |
| **Backblaze B2** | $0.006/GB/mo, $0.01/GB egress |
| **AWS S3 Glacier** | $0.004/GB/mo, slow retrieval |

### 2. Install aws CLI on the server

```bash
sudo apt install -y awscli                # or: pipx install awscli
```

Configure with the backup-bucket credentials:

```bash
aws configure                              # paste the access key + secret
```

### 3. Add to `.env` on the server

```env
AWS_BACKUP_BUCKET=artdb-backups
# Only needed for R2 / B2 (S3 inferred from default endpoint):
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
```

The script auto-detects `AWS_BACKUP_BUCKET` and pushes both files to `s3://<bucket>/YYYY/MM/` (date-prefixed).

If `AWS_BACKUP_BUCKET` is unset → script only keeps local copies. That's fine for dev / staging; not OK for prod.

---

## Manual run

Run on demand (without waiting for cron):

```bash
sudo -u deploy /usr/local/bin/artdb-backup
```

Output looks like:

```
[2026-07-15T02:30:01+0000] pg_dump → /var/backups/artdb/artdb-2026-07-15T023001Z.sql.gz
[2026-07-15T02:30:08+0000]   db: 4.2MB
[2026-07-15T02:30:08+0000] tar storage/app → /var/backups/artdb/artdb-2026-07-15T023001Z-storage.tar.gz
[2026-07-15T02:30:11+0000]   storage: 312MB
[2026-07-15T02:30:11+0000] aws s3 cp → s3://artdb-backups/2026/07/
[2026-07-15T02:30:18+0000] rotating local backups older than 14 day(s)
/var/backups/artdb/artdb-2026-06-30T023001Z.sql.gz
[2026-07-15T02:30:18+0000] done
```

---

## Restore from a backup

```bash
# Database
sudo -u deploy /usr/local/bin/artdb-restore /var/backups/artdb/artdb-2026-07-15T023001Z.sql.gz

# Uploads
sudo -u deploy /usr/local/bin/artdb-restore /var/backups/artdb/artdb-2026-07-15T023001Z-storage.tar.gz
```

The script prompts `Continue? [y/N]` before clobbering anything — destructive operations don't run silently.

After a DB restore:

```bash
cd /var/www/art-db.org
php artisan migrate --force            # apply any migrations newer than the backup
php artisan config:cache
sudo supervisorctl restart artdb-worker:*
```

### Restore from S3 first

```bash
aws s3 ls s3://artdb-backups/2026/07/   # browse available dumps
aws s3 cp s3://artdb-backups/2026/07/artdb-2026-07-15T023001Z.sql.gz /tmp/
sudo -u deploy /usr/local/bin/artdb-restore /tmp/artdb-2026-07-15T023001Z.sql.gz
```

---

## Test restore monthly

**This is the most important section.** Untested backups fail at the worst moment.

Once a month, simulate a disaster:

```bash
# On a fresh / staging server (NOT prod):
createdb artdb_restore_test
PGPASSWORD=... pg_dump --host=... --dbname=... | psql --dbname=artdb_restore_test  # baseline

# Now restore the latest backup INTO a different DB
gunzip -c /var/backups/artdb/artdb-LATEST.sql.gz | psql --dbname=artdb_restore_test

# Verify
psql --dbname=artdb_restore_test -c "SELECT COUNT(*) FROM users, artworks, subscriptions;"

# Cleanup
dropdb artdb_restore_test
```

For the uploads tarball:

```bash
mkdir -p /tmp/restore-test
tar -C /tmp/restore-test -xzf /var/backups/artdb/artdb-LATEST-storage.tar.gz
ls -la /tmp/restore-test/app                # should look like storage/app structure
rm -rf /tmp/restore-test
```

If anything goes wrong, fix the backup pipeline before you need to actually use it.

---

## Retention strategy

The default `KEEP_DAYS=14` keeps two weeks locally. For longer history, push to S3 with **lifecycle rules** instead of growing local disk:

```
S3 bucket lifecycle policy:
  - Move objects to Glacier after 30 days  (cheaper cold storage)
  - Delete objects after 365 days          (keep 1 year of history)
```

R2 / B2 have equivalent lifecycle features in their dashboards.

---

## Monitoring

Add a healthcheck so you notice when backups stop running:

### Option 1 — Healthchecks.io (free, simplest)

1. Create a check at <https://healthchecks.io> → set schedule "daily"
2. Append to the cron command:

```cron
30 2 * * * /usr/local/bin/artdb-backup && curl -fsS --retry 3 https://hc-ping.com/<your-uuid> >> /var/log/artdb-backup.log 2>&1
```

If `artdb-backup` ever fails (or doesn't run), Healthchecks.io emails / Slacks you.

### Option 2 — local check

```bash
# Daily 03:00 — alert if no backup file < 24h old
0 3 * * * find /var/backups/artdb -name 'artdb-*.sql.gz' -mtime -1 | grep -q . || mail -s "Art DB backup MISSING" you@example.com < /dev/null
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `pg_dump: command not found` | postgresql-client missing | `apt install postgresql-client` |
| `Permission denied` on `/var/backups/artdb` | wrong owner | `chown deploy:deploy /var/backups/artdb` |
| `aws: command not found` despite `AWS_BACKUP_BUCKET` | aws CLI not installed | `apt install awscli` |
| S3 push 403 | wrong creds / wrong endpoint | `aws s3 ls s3://bucket` to verify |
| Backup runs but file is 0 bytes | DB credentials wrong | Test `PGPASSWORD=... psql ... -c '\\dt'` manually |
| Restore complains "permission denied for schema public" | DB user lacks privileges | `GRANT ALL ON SCHEMA public TO artdb;` as postgres superuser |
