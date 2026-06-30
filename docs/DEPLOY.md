# Art DB — Deployment Guide

Practical step-by-step for shipping Art DB to production. Two paths:

- **A. Laravel Forge** (managed — ~$12/mo + droplet, easiest)
- **B. Plain VPS** (self-managed — cheapest, more steps)

Both paths assume:

- A registered domain (`art-db.org` in this guide)
- A Stripe account with live products + prices
- A Resend account with verified sending domain
- A PostgreSQL-capable VPS (Hetzner CX11 / DigitalOcean droplet $6 / Vultr $5)

---

## 0. Pre-flight checklist (local)

Before touching the server:

```bash
# Run the full test suite locally
php artisan test

# Make sure prod build succeeds
npm ci && npm run build

# Bump app version in composer.json / package.json (optional)

# Commit + push to main
git add -A && git commit -m "release: vX.Y" && git push origin main
```

Stripe (live mode, **not test**):

- Create Starter / Pro / Studio **products** in live mode at <https://dashboard.stripe.com/products>
- For each, create **Monthly + Yearly recurring prices** — copy the `price_…` IDs
- Configure Customer Portal at <https://dashboard.stripe.com/settings/billing/portal> (must save once)

Resend:

- Verify `art-db.org` at <https://resend.com/domains> — add DKIM/SPF/MX DNS records, click Verify

DNS (at your registrar — Cloudflare / Forpsi / etc.):

- `A` record `art-db.org` → server IP
- `CNAME` record `www.art-db.org` → `art-db.org`
- `CNAME` record `cdn.art-db.org` → your R2/S3 bucket public URL (optional, for assets)
- Resend records (3-4 TXT/MX, copy from Resend Dashboard)

---

## Path A — Laravel Forge

### A1. Provision

1. Sign up at <https://forge.laravel.com>
2. Connect a server provider (DigitalOcean, Hetzner, Linode, Vultr, AWS)
3. **Create Server** → pick PHP 8.4, PostgreSQL 16, sizing 1 GB+ RAM
4. Wait ~5 min for provisioning

### A2. Create Site

1. **Sites → New Site** → domain `art-db.org`, project type "Laravel/PHP"
2. Set web directory to `/public`
3. **Apps → Git Repository** → paste GitHub URL, branch `main`, install composer dependencies
4. **Environment** → paste contents of `.env.production` filled in
5. **Deploy Script** (defaults are fine, but verify):
   ```bash
   cd $FORGE_SITE_PATH
   git pull origin $FORGE_SITE_BRANCH
   composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
   npm ci && npm run build
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   if [ -f artisan ]; then
       $FORGE_PHP artisan queue:restart
   fi
   ```

### A3. SSL + queue + cron

- **SSL → LetsEncrypt** → activate (auto-renews)
- **Daemons → Create Daemon** for the queue worker:
  - Command: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
  - User: `forge`
- **Scheduler** is auto-configured by Forge — verify it runs `php artisan schedule:run` every minute

### A4. Database

- **Database → Create Database** → name `artdb`, user `artdb`, password set in `.env`
- Forge auto-injects credentials into `.env` if you use the panel

### A5. First deploy

Click **Deploy Now** in Forge. Watch the deploy log for errors. Once green:

```bash
ssh forge@your-server
cd /home/forge/art-db.org
php artisan tinker --execute="echo App\Models\User::count();"  # should be 0
```

Create the first admin user via tinker:

```php
\App\Models\User::create([
    'name' => 'Kat',
    'email' => 'info@art-db.org',
    'password' => bcrypt('<strong-pw>'),
    'role' => 'admin',
    'email_verified_at' => now(),
    'subscription_status' => 'active',
]);
```

Open `https://art-db.org/admin/login` and sign in.

---

## Path B — Plain VPS (Hetzner / DigitalOcean / Vultr)

### B1. Server provisioning

Pick Ubuntu 24.04 LTS, 1 GB RAM, 25 GB SSD. SSH in as root, then create a deploy user:

```bash
adduser deploy
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh && chmod 600 /home/deploy/.ssh/authorized_keys
```

Lock down SSH:

```bash
sed -i 's/^PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sed -i 's/^#PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
systemctl restart ssh
ufw allow OpenSSH && ufw allow http && ufw allow https && ufw --force enable
```

### B2. System dependencies

```bash
# PHP 8.4 from Ondřej Surý PPA
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.4-{cli,fpm,pgsql,bcmath,curl,gd,intl,mbstring,xml,zip,redis} \
               postgresql-16 nginx redis-server certbot python3-certbot-nginx \
               git unzip supervisor

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node 20 (for Vite)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

### B3. PostgreSQL setup

```bash
sudo -u postgres psql <<EOF
CREATE DATABASE artdb;
CREATE USER artdb WITH ENCRYPTED PASSWORD '<strong-db-password>';
GRANT ALL PRIVILEGES ON DATABASE artdb TO artdb;
\c artdb
GRANT ALL ON SCHEMA public TO artdb;
EOF
```

### B4. Clone + install

```bash
su - deploy
cd /var/www
sudo mkdir -p art-db.org && sudo chown deploy:deploy art-db.org
git clone https://github.com/<your-user>/art-db.git art-db.org
cd art-db.org

cp .env.production .env       # then fill in every placeholder
php artisan key:generate
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### B5. Nginx

Create `/etc/nginx/sites-available/art-db.org`:

```nginx
server {
    listen 80;
    server_name art-db.org www.art-db.org;
    return 301 https://art-db.org$request_uri;
}

server {
    listen 443 ssl http2;
    server_name art-db.org;

    root /var/www/art-db.org/public;
    index index.php;
    charset utf-8;

    # Will be filled in by certbot:
    # ssl_certificate ...
    # ssl_certificate_key ...

    client_max_body_size 50M;       # Image uploads

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) { deny all; }

    # Static asset caching
    location ~* \.(?:ico|css|js|gif|jpe?g|png|svg|webp|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable + SSL:

```bash
sudo ln -s /etc/nginx/sites-available/art-db.org /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d art-db.org -d www.art-db.org
```

### B6. Queue worker (Supervisor)

Create `/etc/supervisor/conf.d/artdb-worker.conf`:

```ini
[program:artdb-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/art-db.org/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/art-db.org/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start artdb-worker:*
```

### B7. Scheduler (cron)

```bash
sudo crontab -u deploy -e
```

Append:

```cron
* * * * * cd /var/www/art-db.org && php artisan schedule:run >> /dev/null 2>&1
```

This runs `subscriptions:check` (02:00 daily) and `subscriptions:remind-trials` (10:00 daily) at the right times.

### B8. Permissions

```bash
sudo chown -R deploy:www-data /var/www/art-db.org
sudo chmod -R 775 /var/www/art-db.org/storage /var/www/art-db.org/bootstrap/cache
```

### B9. First user

Same as Path A — tinker into the Admin role.

---

## Stripe production webhook

After the site is live + SSL works:

1. <https://dashboard.stripe.com/webhooks> (live mode, switch off "Viewing test data")
2. **Add endpoint** → URL `https://art-db.org/stripe/webhook`
3. **Select events**:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `checkout.session.completed`
4. **Reveal signing secret** → copy `whsec_…` → paste into prod `.env` as `STRIPE_WEBHOOK_SECRET` → `php artisan config:cache`

Test from the Stripe Dashboard: select the endpoint → **Send test event** → `customer.subscription.created` — should return 200.

---

## Health checks

After deploy, hit these and confirm 200 OK:

```bash
curl -I https://art-db.org/                       # public home
curl -I https://art-db.org/up                     # Laravel health endpoint
curl -I https://art-db.org/admin/login            # Filament
curl -I https://art-db.org/api/v1/artworks        # API
```

Database sanity:

```bash
php artisan tinker --execute="echo App\Models\User::count(),', ',App\Models\Artwork::count();"
```

Queue worker:

```bash
sudo supervisorctl status artdb-worker:*           # both processes should be RUNNING
```

Scheduler (verify cron ran):

```bash
tail -50 /var/log/syslog | grep artisan
```

---

## Common issues

| Symptom | Cause | Fix |
|---|---|---|
| 500 on every page after deploy | `APP_KEY` empty | `php artisan key:generate --force` |
| White-screen on /admin | View cache stale | `php artisan view:clear && php artisan view:cache` |
| `/stripe/webhook` returns 400 | Wrong `STRIPE_WEBHOOK_SECRET` | Re-copy from Stripe Dashboard, `php artisan config:cache` |
| Resend rejects sends | Domain not verified | Add DNS records, click Verify in Resend Dashboard |
| Filament upload fails | Missing `php artisan storage:link` | Run it as the deploy user |
| Trial reminders don't fire | Cron not installed | Verify `crontab -u deploy -l` shows the entry |
| Uploaded images 404 | `FILESYSTEM_DISK=s3` but missing AWS creds | Either fill S3 creds or `FILESYSTEM_DISK=public` (then `storage:link`) |

---

## Updating the running app

```bash
ssh deploy@art-db.org
cd /var/www/art-db.org
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart artdb-worker:*
```

On Forge, just hit **Deploy Now** in the panel.

---

## Rollback

```bash
cd /var/www/art-db.org
git log --oneline -5                # find the previous good commit
git checkout <previous-sha>
composer install --no-dev
php artisan migrate:rollback        # only if the bad release ran a destructive migration
php artisan config:cache
sudo supervisorctl restart artdb-worker:*
```

---

## Next: backup script

After you're live, set up the backup script (see `docs/BACKUP.md` — coming next) so you don't lose data when something goes wrong.
