# AWS EC2 + Apache 2 (ArtWallet)

Deploy this Laravel application on **Amazon EC2** using **Apache 2** as the web server. Composer and the private **`artixcore/artgate`** path dependency are covered in [vps.md](vps.md); follow that page for **`ARTGATE_GIT_URL`**, **`packages/artgate`**, and lock-file notes before or after the steps below.

Laravel front-controller routing relies on **`public/.htaccess`** (`mod_rewrite`). Apache must use **`DocumentRoot`** pointing at the **`public/`** directory, not the repository root.

## Prerequisites

- **Security group**: allow **TCP 80** (and **443** if you terminate TLS on the instance) from the internet; restrict **SSH 22** to your IP or a bastion.
- **DNS**: point your hostname at the instance (Elastic IP recommended for a stable address). Set **`APP_URL`** in `.env` to that HTTPS (or HTTP) base URL.
- **Instance OS**: this guide uses **Ubuntu Server 22.04 or 24.04**. On **Amazon Linux 2023**, package names differ (`dnf`, `httpd`, `php-fpm`); see [Amazon’s PHP on AL2023 documentation](https://docs.aws.amazon.com/linux/al2023/ug/php.html) and map the same PHP extensions and Apache equivalents (`AllowOverride All` on `public/`).

## Install Apache, PHP 8.3, and extensions

The app requires **PHP ^8.3** ([composer.json](../../composer.json)). Example for Ubuntu:

```bash
sudo apt update
sudo apt install -y \
  apache2 \
  git unzip \
  php8.3 php8.3-cli php8.3-common \
  libapache2-mod-php8.3 \
  php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
```

Enable URL rewriting (required for Laravel routes):

```bash
sudo a2enmod rewrite
```

Install **Composer** (if not already present), e.g. from [getcomposer.org/download](https://getcomposer.org/download/).

## Application checkout and dependencies

Clone the app (example path):

```bash
sudo mkdir -p /var/www
sudo chown "$USER:$USER" /var/www
cd /var/www
git clone https://github.com/YOUR_ORG/art-wallet.git art-wallet
cd art-wallet
```

Install PHP dependencies (includes **artgate** via `pre-install-cmd`; see [vps.md](vps.md)):

```bash
export ARTGATE_GIT_URL="https://github.com/YOUR_ORG/artgate.git"
# optional: export ARTGATE_GIT_REF=main
composer install --no-dev --optimize-autoloader --no-interaction
```

Build front-end assets for production (or build in CI and deploy **`public/build`**):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
npm ci
npm run build
```

## Apache virtual host

Point **`DocumentRoot`** at **`.../art-wallet/public`**. The directory block must allow **`.htaccess`** overrides:

- **`AllowOverride All`** on `public/` (otherwise `mod_rewrite` rules in [public/.htaccess](../../public/.htaccess) will not run).

A ready-to-adapt snippet lives in the repo: **[deploy/apache-art-wallet.conf.example](../../deploy/apache-art-wallet.conf.example)**.

Typical install:

```bash
sudo cp deploy/apache-art-wallet.conf.example /etc/apache2/sites-available/art-wallet.conf
# Edit ServerName and paths if your tree is not /var/www/art-wallet
sudo nano /etc/apache2/sites-available/art-wallet.conf
sudo a2ensite art-wallet.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest && sudo systemctl reload apache2
```

If you use **PHP-FPM** instead of `libapache2-mod-php8.3`, configure `proxy_fcgi` / `SetHandler` per Ubuntu/Debian PHP-FPM docs; `DocumentRoot` and **`AllowOverride All`** on `public/` still apply.

## File permissions

Apache on Ubuntu runs as **`www-data`**. Grant write access only where Laravel needs it:

```bash
cd /var/www/art-wallet
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

If the app stores public uploads, run **`php artisan storage:link`** after deploy when you use the `public` disk.

## Laravel environment and optimization

```bash
cd /var/www/art-wallet
cp .env.example .env
php artisan key:generate --force
# Set DB_*, APP_URL, etc. in .env, then:
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Use **`APP_ENV=production`** and **`APP_DEBUG=false`** in production.

## TLS (HTTPS)

Use **Certbot** with the Apache plugin on Ubuntu, or terminate TLS on an **Application Load Balancer** and forward HTTP to the instance. For Certbot on the instance: install `certbot python3-certbot-apache`, obtain a certificate for your `ServerName`, and ensure **`a2enmod ssl`** as needed.

## Queues, scheduler, WebSockets

- **Queues**: run a systemd service or supervisor that executes `php artisan queue:work` (or your chosen connection).
- **Scheduler**: add a crontab entry for `* * * * * cd /var/www/art-wallet && php artisan schedule:run >> /dev/null 2>&1`.
- **Laravel Reverb / WebSockets**: not covered here; you will need a reverse proxy, a dedicated process, and often a separate port or path. Extend your setup when you enable Reverb in production.

## See also

- [vps.md](vps.md) — Composer, **artgate**, `packages/artgate`, `--no-scripts`
- [digitalocean.md](digitalocean.md) — containerized deploy and Docker build args
