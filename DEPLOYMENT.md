# Deploying Ned

This guide covers how to self-host Ned on your own infrastructure.

## Requirements

### Server (API + Dashboard)

- **PHP 8.2+** with extensions: `curl`, `mbstring`, `openssl`, `pdo_sqlite` (or `pdo_mysql`/`pdo_pgsql`)
- **Composer 2.x**
- **Node.js 18+** and npm (for building assets)
- **SQLite** (default) or MySQL 8+ / PostgreSQL 14+
- **Web server**: nginx (recommended) or Apache

### Agent (Monitored Servers)

- **Bash 4+**
- **curl**
- **Standard Linux utilities**: `free`, `df`, `uptime`, `nproc`
- Optional: `systemctl` (for service monitoring), `fail2ban-client` (for security metrics)

## Quick Start

```bash
# Clone the repository
git clone https://github.com/paul-tastic/ned.git
cd ned/server

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
touch database/database.sqlite
php artisan migrate --force

# Create admin user
php artisan ned:install

# Start the server (development)
php artisan serve --host=0.0.0.0 --port=8080
```

For production, use nginx + PHP-FPM instead of `php artisan serve`.

## Server Configuration

### Nginx

```nginx
server {
    listen 8080;
    server_name _;  # Or your domain/IP
    root /var/www/ned/server/public;

    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache

```apache
<VirtualHost *:8080>
    ServerName your-server-ip
    DocumentRoot /var/www/ned/server/public

    <Directory /var/www/ned/server/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ned-error.log
    CustomLog ${APACHE_LOG_DIR}/ned-access.log combined
</VirtualHost>
```

Enable required modules:
```bash
a2enmod rewrite
systemctl restart apache2
```

### Environment Variables

Key `.env` settings for production:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server:8080

# Database (SQLite is fine for single-user)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/ned/server/database/database.sqlite

# For MySQL/PostgreSQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=ned
# DB_USERNAME=ned
# DB_PASSWORD=secure_password

# Session/Cache
SESSION_DRIVER=file
CACHE_STORE=file

# Queue (for async notifications)
QUEUE_CONNECTION=database
```

## Database

### SQLite (Recommended for Small Deployments)

```bash
touch database/database.sqlite
chmod 660 database/database.sqlite
chown www-data:www-data database/database.sqlite
php artisan migrate --force
```

### MySQL

```sql
CREATE DATABASE ned CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ned'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ned.* TO 'ned'@'localhost';
FLUSH PRIVILEGES;
```

Then update `.env` and run migrations:
```bash
php artisan migrate --force
```

## SSL/TLS

### With Let's Encrypt (Recommended)

If you have a domain:

```bash
# Install certbot
apt install certbot python3-certbot-nginx

# Get certificate
certbot --nginx -d your-domain.com

# Auto-renewal is configured automatically
```

### Self-Signed (For IP-Only Access)

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/ned.key \
  -out /etc/ssl/certs/ned.crt \
  -subj "/CN=your-server-ip"
```

Update nginx:
```nginx
listen 8443 ssl;
ssl_certificate /etc/ssl/certs/ned.crt;
ssl_certificate_key /etc/ssl/private/ned.key;
```

## Systemd Services

### Ned Queue Worker

Create `/etc/systemd/system/ned-queue.service`:

```ini
[Unit]
Description=Ned Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/ned/server
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
systemctl enable ned-queue
systemctl start ned-queue
```

### Ned Scheduler (for offline detection)

Create `/etc/systemd/system/ned-scheduler.service`:

```ini
[Unit]
Description=Ned Scheduler
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/ned/server
ExecStart=/usr/bin/php artisan schedule:work
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

## Docker (Optional)

Coming soon. For now, use the native installation above.

## Installing the Agent

On each server you want to monitor:

```bash
curl -fsSL https://raw.githubusercontent.com/paul-tastic/ned/master/agent/install.sh | \
  bash -s -- --token YOUR_SERVER_TOKEN --api http://your-ned-server:8080
```

Or manually:
```bash
# Download
curl -o /usr/local/bin/ned-agent https://raw.githubusercontent.com/paul-tastic/ned/master/agent/ned-agent.sh
chmod +x /usr/local/bin/ned-agent

# Configure
mkdir -p /etc/ned
cat > /etc/ned/config << EOF
NED_API_URL="http://your-ned-server:8080"
NED_TOKEN="your-64-character-token"
EOF
chmod 600 /etc/ned/config

# Add to cron (every minute)
echo "* * * * * root /usr/local/bin/ned-agent" > /etc/cron.d/ned-agent
```

## Upgrading

```bash
cd /var/www/ned

# Pull latest changes
git pull origin master

# Update dependencies
cd server
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
systemctl restart ned-queue
```

## Backup & Restore

### Backup

```bash
# SQLite - just copy the file
cp /var/www/ned/server/database/database.sqlite /backup/ned-$(date +%Y%m%d).sqlite

# MySQL
mysqldump -u ned -p ned > /backup/ned-$(date +%Y%m%d).sql
```

### Restore

```bash
# SQLite
cp /backup/ned-20240101.sqlite /var/www/ned/server/database/database.sqlite
chown www-data:www-data /var/www/ned/server/database/database.sqlite

# MySQL
mysql -u ned -p ned < /backup/ned-20240101.sql
```

## Troubleshooting

### Agent not sending data

1. Check the config: `cat /etc/ned/config`
2. Test manually: `/usr/local/bin/ned-agent`
3. Check connectivity: `curl -I http://your-ned-server:8080/api/health`

### Dashboard not loading

1. Check PHP-FPM is running: `systemctl status php8.2-fpm`
2. Check nginx errors: `tail -f /var/log/nginx/error.log`
3. Check Laravel logs: `tail -f /var/www/ned/server/storage/logs/laravel.log`

### Permission errors

```bash
chown -R www-data:www-data /var/www/ned/server/storage
chown -R www-data:www-data /var/www/ned/server/bootstrap/cache
chmod -R 775 /var/www/ned/server/storage
```
