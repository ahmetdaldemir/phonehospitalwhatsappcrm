# Production Deployment Guide

This guide covers deploying Phone Hospital CRM to production using Docker, Nginx, and Cloudflare.

## Table of Contents

1. [Docker Setup](#docker-setup)
2. [Environment Configuration](#environment-configuration)
3. [Cloudflare Configuration](#cloudflare-configuration)
4. [SSL/HTTPS Setup](#sslhttps-setup)
5. [Cron Jobs](#cron-jobs)
6. [Storage Setup](#storage-setup)
7. [Deployment Steps](#deployment-steps)

## Docker Setup

### Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git

### Quick Start

1. **Clone and navigate to project:**
   ```bash
   git clone <repository-url>
   cd phonehospitalwhatsappcrm
   ```

2. **Copy environment file:**
   ```bash
   cp .env.production.example .env
   ```

3. **Generate application key:**
   ```bash
   docker-compose run --rm app php artisan key:generate
   ```

4. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

5. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate --force
   ```

6. **Create storage symlink:**
   ```bash
   docker-compose exec app php artisan storage:link
   ```

7. **Set permissions:**
   ```bash
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   ```

### Docker Services

- **app**: Laravel PHP-FPM application
- **nginx**: Web server
- **mysql**: MySQL 8.0 database
- **redis**: Redis cache and queue
- **queue**: Laravel queue worker
- **scheduler**: Laravel task scheduler

## Environment Configuration

### Production .env Setup

1. **Copy production example:**
   ```bash
   cp .env.production.example .env
   ```

2. **Update critical values:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://phonehospitalservice.com
   DB_PASSWORD=STRONG_PASSWORD_HERE
   ```

3. **Generate app key:**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

### Cloudflare-Specific Settings

```env
# Trust Cloudflare proxies
TRUSTED_PROXIES=*
CF_IP_HEADER=CF-Connecting-IP

# Secure cookies (Cloudflare handles SSL)
SESSION_SECURE_COOKIE=true
COOKIE_DOMAIN=.phonehospitalservice.com
```

### Update TrustProxies Middleware

Create or update `app/Http/Middleware/TrustProxies.php`:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = '*'; // Trust all proxies (Cloudflare)

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

## Cloudflare Configuration

### DNS Setup

1. **Add A Record:**
   - Type: A
   - Name: @ (or your domain)
   - IPv4: Your server IP
   - Proxy: Proxied (orange cloud)

2. **Add CNAME for www:**
   - Type: CNAME
   - Name: www
   - Target: phonehospitalservice.com
   - Proxy: Proxied

### SSL/TLS Settings

1. **SSL/TLS Mode:**
   - Go to SSL/TLS → Overview
   - Set to **Full (strict)** for end-to-end encryption
   - Or **Full** if using self-signed certs

2. **Always Use HTTPS:**
   - Go to SSL/TLS → Edge Certificates
   - Enable "Always Use HTTPS"

3. **Automatic HTTPS Rewrites:**
   - Enable "Automatic HTTPS Rewrites"

### Page Rules

Create page rules for better performance:

1. **Cache Static Assets:**
   - URL: `*phonehospitalservice.com/build/*`
   - Settings: Cache Level: Cache Everything, Edge Cache TTL: 1 month

2. **Bypass Cache for API:**
   - URL: `*phonehospitalservice.com/api/*`
   - Settings: Cache Level: Bypass

### Security Settings

1. **Security Level:** Medium
2. **Challenge Passage:** 30 minutes
3. **Browser Integrity Check:** On
4. **Privacy Pass Support:** On

## SSL/HTTPS Configuration

### Option 1: Cloudflare SSL (Recommended)

Cloudflare handles SSL termination. No server-side SSL needed.

**Nginx Configuration:**
- Use HTTP (port 80) only
- Cloudflare handles HTTPS
- Set `X-Forwarded-Proto` header

### Option 2: Direct SSL with Let's Encrypt

If you need direct SSL (not through Cloudflare):

1. **Install Certbot:**
   ```bash
   docker-compose exec nginx apk add certbot certbot-nginx
   ```

2. **Generate Certificate:**
   ```bash
   certbot --nginx -d phonehospitalservice.com -d www.phonehospitalservice.com
   ```

3. **Update Nginx Config:**
   - Uncomment HTTPS server block in `docker/nginx/default.conf`
   - Update SSL certificate paths

4. **Auto-renewal:**
   ```bash
   # Add to crontab
   0 0 * * * certbot renew --quiet
   ```

### Option 3: Cloudflare Origin Certificate

1. **Generate Origin Certificate:**
   - Go to Cloudflare Dashboard → SSL/TLS → Origin Server
   - Create Certificate
   - Download certificate and key

2. **Place in Docker:**
   ```bash
   mkdir -p docker/nginx/ssl
   # Copy cert.pem and key.pem to docker/nginx/ssl/
   ```

3. **Update Nginx:**
   - Uncomment HTTPS server block
   - Update certificate paths

## Cron Jobs

### Laravel Scheduler

The Docker Compose file includes a scheduler container that runs Laravel's task scheduler.

**Add to `app/Console/Kernel.php`:**

```php
protected function schedule(Schedule $schedule)
{
    // Run queue worker (if not using separate container)
    // $schedule->command('queue:work --stop-when-empty')->everyMinute();

    // Clean up old logs
    $schedule->command('log:clear')->daily();

    // Backup database (if using backup package)
    // $schedule->command('backup:run')->daily()->at('02:00');

    // Send scheduled notifications
    $schedule->command('notifications:send')->everyMinute();
}
```

### System Cron (Alternative)

If not using Docker scheduler container:

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler (runs every minute)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Docker Scheduler

The scheduler container runs automatically:
- Checks for scheduled tasks every minute
- Runs tasks based on Laravel schedule
- Logs output to container logs

**View scheduler logs:**
```bash
docker-compose logs -f scheduler
```

## Storage Setup

### Create Storage Symlink

Laravel needs a symlink from `public/storage` to `storage/app/public`:

```bash
# Inside Docker container
docker-compose exec app php artisan storage:link

# Or manually
docker-compose exec app ln -s /var/www/html/storage/app/public /var/www/html/public/storage
```

### Verify Symlink

```bash
docker-compose exec app ls -la public/storage
# Should show: public/storage -> ../storage/app/public
```

### Storage Permissions

```bash
# Set ownership
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Set permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Production Storage Tips

1. **Use S3 for Media (Optional):**
   ```env
   FILESYSTEM_DISK=s3
   AWS_BUCKET=phone-hospital-media
   ```

2. **Backup Storage:**
   - Regular backups of `storage/app` directory
   - Consider cloud storage for important files

## Deployment Steps

### Initial Deployment

1. **Server Setup:**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y

   # Install Docker
   curl -fsSL https://get.docker.com -o get-docker.sh
   sh get-docker.sh

   # Install Docker Compose
   sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
   sudo chmod +x /usr/local/bin/docker-compose
   ```

2. **Clone Repository:**
   ```bash
   git clone <repository-url> /var/www/phonehospital
   cd /var/www/phonehospital
   ```

3. **Configure Environment:**
   ```bash
   cp .env.production.example .env
   nano .env  # Edit with production values
   ```

4. **Build and Start:**
   ```bash
   docker-compose up -d --build
   ```

5. **Run Setup Commands:**
   ```bash
   # Generate key
   docker-compose exec app php artisan key:generate

   # Run migrations
   docker-compose exec app php artisan migrate --force

   # Create symlink
   docker-compose exec app php artisan storage:link

   # Set permissions
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   docker-compose exec app chmod -R 775 storage bootstrap/cache

   # Cache config
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

6. **Build Frontend:**
   ```bash
   docker-compose exec app npm install
   docker-compose exec app npm run build
   ```

### Update Deployment

1. **Pull Latest Code:**
   ```bash
   git pull origin main
   ```

2. **Rebuild Containers:**
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

3. **Run Migrations:**
   ```bash
   docker-compose exec app php artisan migrate --force
   ```

4. **Clear and Rebuild Cache:**
   ```bash
   docker-compose exec app php artisan config:clear
   docker-compose exec app php artisan route:clear
   docker-compose exec app php artisan view:clear
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

5. **Rebuild Frontend:**
   ```bash
   docker-compose exec app npm run build
   ```

## Maintenance Commands

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f scheduler
docker-compose logs -f queue
```

### Database Backup

```bash
# Backup
docker-compose exec mysql mysqldump -u root -p${DB_PASSWORD} ${DB_DATABASE} > backup_$(date +%Y%m%d).sql

# Restore
docker-compose exec -T mysql mysql -u root -p${DB_PASSWORD} ${DB_DATABASE} < backup_20240101.sql
```

### Clear Cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Queue Management

```bash
# Restart queue worker
docker-compose restart queue

# View queue jobs
docker-compose exec app php artisan queue:work --once
```

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] `APP_KEY` is set and secure
- [ ] Cloudflare SSL enabled
- [ ] Firewall configured (only 80, 443, 22 open)
- [ ] Regular security updates
- [ ] Database backups scheduled
- [ ] Log rotation configured
- [ ] Rate limiting enabled
- [ ] CORS properly configured

## Monitoring

### Health Check Endpoint

Laravel includes `/up` health check endpoint (configured in `bootstrap/app.php`).

### Monitoring Tools

Consider adding:
- **Laravel Telescope** (development/staging)
- **Sentry** (error tracking)
- **New Relic** (performance monitoring)
- **Uptime Robot** (uptime monitoring)

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker-compose logs app

# Check container status
docker-compose ps

# Restart specific service
docker-compose restart app
```

### Permission Issues

```bash
# Fix ownership
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Fix permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues

```bash
# Test connection
docker-compose exec app php artisan tinker
# Then: DB::connection()->getPdo();

# Check MySQL logs
docker-compose logs mysql
```

### Nginx 502 Bad Gateway

```bash
# Check PHP-FPM is running
docker-compose ps app

# Check PHP-FPM logs
docker-compose logs app

# Restart PHP-FPM
docker-compose restart app
```

## Performance Optimization

1. **Enable OPcache:**
   - Already included in PHP-FPM image
   - Configure in `docker/php/local.ini`

2. **Use Redis for Cache:**
   ```env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```

3. **Enable Gzip:**
   - Already configured in Nginx

4. **CDN for Assets:**
   - Use Cloudflare CDN
   - Or AWS CloudFront

5. **Database Optimization:**
   - Indexes on frequently queried columns
   - Query optimization
   - Connection pooling

## Backup Strategy

### Automated Backups

Create `scripts/backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
docker-compose exec -T mysql mysqldump -u root -p${DB_PASSWORD} ${DB_DATABASE} > ${BACKUP_DIR}/db_${DATE}.sql

# Storage backup
tar -czf ${BACKUP_DIR}/storage_${DATE}.tar.gz storage/app

# Keep only last 7 days
find ${BACKUP_DIR} -name "*.sql" -mtime +7 -delete
find ${BACKUP_DIR} -name "*.tar.gz" -mtime +7 -delete
```

Add to crontab:
```bash
0 2 * * * /path/to/scripts/backup.sh
```


