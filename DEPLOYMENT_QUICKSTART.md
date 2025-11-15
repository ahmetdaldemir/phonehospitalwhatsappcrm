# Quick Start Deployment Guide

## Prerequisites

- Docker & Docker Compose installed
- Domain pointed to server IP
- Cloudflare account (optional but recommended)

## Quick Deployment

### 1. Initial Setup

```bash
# Clone repository
git clone <repository-url>
cd phonehospitalwhatsappcrm

# Copy environment file
cp env.production .env

# Edit .env with your production values
nano .env
```

### 2. Docker Commands

```bash
# Build and start all services
docker-compose up -d --build

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate --force

# Create storage symlink
docker-compose exec app php artisan storage:link

# Set permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache

# Build frontend
docker-compose exec app npm install
docker-compose exec app npm run build

# Cache configuration
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### 3. Storage Symlink

```bash
# Create symlink (required for file uploads)
docker-compose exec app php artisan storage:link

# Verify symlink
docker-compose exec app ls -la public/storage
# Should show: public/storage -> ../storage/app/public
```

### 4. Cron Jobs

**Option A: Docker Scheduler (Recommended)**
- Already configured in `docker-compose.yml`
- Runs automatically in `scheduler` container
- No additional setup needed

**Option B: System Cron**
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/phonehospital && docker-compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

### 5. Cloudflare Configuration

#### DNS Setup
1. Add A record: `@` → Your server IP (Proxied)
2. Add CNAME: `www` → `phonehospitalservice.com` (Proxied)

#### SSL/TLS
1. SSL/TLS → Overview → Set to **Full (strict)**
2. SSL/TLS → Edge Certificates → Enable "Always Use HTTPS"

#### Security
- Security Level: Medium
- Browser Integrity Check: On

### 6. SSL/HTTPS with Cloudflare

**Recommended: Cloudflare SSL (No server SSL needed)**
- Cloudflare handles SSL termination
- Nginx runs on HTTP (port 80)
- Cloudflare proxies to your server
- Set `TRUSTED_PROXIES=*` in `.env`

**Alternative: Origin Certificate**
1. Cloudflare Dashboard → SSL/TLS → Origin Server
2. Create Certificate
3. Download cert.pem and key.pem
4. Place in `docker/nginx/ssl/`
5. Uncomment HTTPS block in `docker/nginx/default.conf`

## Common Commands

### View Logs
```bash
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f scheduler
```

### Restart Services
```bash
docker-compose restart app
docker-compose restart nginx
docker-compose restart queue
```

### Update Application
```bash
git pull
docker-compose down
docker-compose up -d --build
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan config:cache
docker-compose exec app npm run build
```

### Database Backup
```bash
docker-compose exec mysql mysqldump -u root -p${DB_PASSWORD} ${DB_DATABASE} > backup_$(date +%Y%m%d).sql
```

## Troubleshooting

### Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Storage Symlink Missing
```bash
docker-compose exec app php artisan storage:link
```

### Clear All Cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

## Production Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated
- [ ] Strong database password
- [ ] Storage symlink created
- [ ] Permissions set correctly
- [ ] Cloudflare SSL configured
- [ ] Scheduler running
- [ ] Queue worker running
- [ ] Backups configured


