# SSL/HTTPS Configuration Guide

## Overview

This guide covers SSL/HTTPS configuration for Phone Hospital CRM with Cloudflare proxy.

## Option 1: Cloudflare SSL (Recommended)

Cloudflare handles SSL termination. Your server runs HTTP only.

### Setup Steps

1. **Cloudflare DNS:**
   - Add A record: `@` → Server IP (Proxied - orange cloud)
   - Add CNAME: `www` → `phonehospitalservice.com` (Proxied)

2. **Cloudflare SSL/TLS:**
   - Go to SSL/TLS → Overview
   - Set mode to **Full (strict)** (recommended)
   - Or **Full** if using self-signed certificates

3. **Always Use HTTPS:**
   - Go to SSL/TLS → Edge Certificates
   - Enable "Always Use HTTPS"
   - Enable "Automatic HTTPS Rewrites"

4. **Server Configuration:**
   - Nginx runs on HTTP (port 80) only
   - No SSL certificates needed on server
   - Cloudflare proxies HTTPS → HTTP

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name phonehospitalservice.com www.phonehospitalservice.com;
    
    # Cloudflare IP detection
    set_real_ip_from 0.0.0.0/0;
    real_ip_header CF-Connecting-IP;
    
    # Forward protocol
    add_header X-Forwarded-Proto $scheme;
}
```

### Laravel Configuration

```env
# .env
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
COOKIE_DOMAIN=.phonehospitalservice.com
```

### TrustProxies Middleware

Already configured in `app/Http/Middleware/TrustProxies.php`:
- `$proxies = '*'` - Trust all proxies (Cloudflare)
- Headers configured for Cloudflare

## Option 2: Cloudflare Origin Certificate

Use Cloudflare's origin certificate for server-to-Cloudflare encryption.

### Setup Steps

1. **Generate Origin Certificate:**
   - Cloudflare Dashboard → SSL/TLS → Origin Server
   - Click "Create Certificate"
   - Select RSA (2048) or ECDSA (P-256)
   - Set validity (up to 15 years)
   - Add domains: `phonehospitalservice.com`, `*.phonehospitalservice.com`
   - Click "Create"

2. **Download Certificate:**
   - Copy the "Origin Certificate"
   - Copy the "Private Key"
   - Save as `cert.pem` and `key.pem`

3. **Place in Docker:**
   ```bash
   mkdir -p docker/nginx/ssl
   # Copy cert.pem and key.pem to docker/nginx/ssl/
   ```

4. **Update Nginx Config:**
   Uncomment HTTPS block in `docker/nginx/default.conf`:
   ```nginx
   server {
       listen 443 ssl http2;
       server_name phonehospitalservice.com www.phonehospitalservice.com;
       
       ssl_certificate /etc/nginx/ssl/cert.pem;
       ssl_certificate_key /etc/nginx/ssl/key.pem;
       
       ssl_protocols TLSv1.2 TLSv1.3;
       ssl_ciphers HIGH:!aNULL:!MD5;
       ssl_prefer_server_ciphers on;
       
       # Rest of config...
   }
   ```

5. **Update Docker Compose:**
   Ensure SSL directory is mounted:
   ```yaml
   nginx:
     volumes:
       - ./docker/nginx/ssl:/etc/nginx/ssl
   ```

6. **Restart Nginx:**
   ```bash
   docker-compose restart nginx
   ```

## Option 3: Let's Encrypt (Direct SSL)

If not using Cloudflare proxy, use Let's Encrypt.

### Setup Steps

1. **Install Certbot in Nginx Container:**
   ```bash
   docker-compose exec nginx apk add certbot certbot-nginx
   ```

2. **Generate Certificate:**
   ```bash
   docker-compose exec nginx certbot --nginx \
     -d phonehospitalservice.com \
     -d www.phonehospitalservice.com
   ```

3. **Auto-renewal:**
   Add to crontab or use systemd timer:
   ```bash
   0 0 * * * docker-compose exec nginx certbot renew --quiet
   ```

## SSL Configuration Best Practices

### Nginx SSL Settings

```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
ssl_stapling on;
ssl_stapling_verify on;
```

### Security Headers

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

## Testing SSL

### Check SSL Certificate

```bash
# Test SSL connection
openssl s_client -connect phonehospitalservice.com:443 -servername phonehospitalservice.com

# Check certificate details
echo | openssl s_client -servername phonehospitalservice.com -connect phonehospitalservice.com:443 2>/dev/null | openssl x509 -noout -dates
```

### Online SSL Checkers

- [SSL Labs](https://www.ssllabs.com/ssltest/)
- [SSL Checker](https://www.sslshopper.com/ssl-checker.html)

## Troubleshooting

### Mixed Content Warnings

If you see mixed content warnings:
- Ensure all assets use HTTPS
- Check `APP_URL` is set to HTTPS
- Update any hardcoded HTTP URLs

### Certificate Errors

- Verify certificate is valid
- Check certificate expiration
- Ensure domain matches certificate
- Verify Cloudflare SSL mode

### Cloudflare 502 Errors

- Check origin server is accessible
- Verify SSL mode matches your setup
- Check firewall allows Cloudflare IPs
- Review Nginx error logs

## Recommended Setup

**For Production:**
1. Use Cloudflare proxy (orange cloud)
2. Set SSL mode to "Full (strict)"
3. Enable "Always Use HTTPS"
4. Use Cloudflare Origin Certificate (optional but recommended)
5. Configure TrustProxies middleware
6. Set secure cookies in Laravel

This provides:
- Free SSL certificate
- DDoS protection
- CDN benefits
- Better performance
- Security features


