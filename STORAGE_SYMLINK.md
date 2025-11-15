# Storage Symlink Instructions

## Overview

Laravel requires a symbolic link from `public/storage` to `storage/app/public` to serve user-uploaded files (like ticket photos) through the web server.

## Quick Command

```bash
# Inside Docker container
docker-compose exec app php artisan storage:link

# Or manually
docker-compose exec app ln -s /var/www/html/storage/app/public /var/www/html/public/storage
```

## Verification

After creating the symlink, verify it exists:

```bash
docker-compose exec app ls -la public/storage
```

Expected output:
```
lrwxrwxrwx 1 www-data www-data 31 Jan  1 12:00 public/storage -> ../storage/app/public
```

## Troubleshooting

### Symlink Already Exists

If the symlink already exists but points to wrong location:

```bash
# Remove existing symlink
docker-compose exec app rm public/storage

# Create new symlink
docker-compose exec app php artisan storage:link
```

### Permission Denied

If you get permission errors:

```bash
# Set ownership
docker-compose exec app chown -R www-data:www-data storage public

# Set permissions
docker-compose exec app chmod -R 775 storage public/storage
```

### Symlink Not Working

Check if the target directory exists:

```bash
docker-compose exec app ls -la storage/app/public
```

If it doesn't exist, create it:

```bash
docker-compose exec app mkdir -p storage/app/public
docker-compose exec app chown -R www-data:www-data storage/app/public
```

## Production Setup

### Initial Deployment

```bash
# 1. Create storage directories
docker-compose exec app mkdir -p storage/app/public/ticket-media

# 2. Set permissions
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chmod -R 775 storage

# 3. Create symlink
docker-compose exec app php artisan storage:link

# 4. Verify
docker-compose exec app ls -la public/storage
```

### After Code Updates

The symlink persists across deployments, but verify after updates:

```bash
docker-compose exec app php artisan storage:link --force
```

## File Access

Once the symlink is created, files in `storage/app/public/` are accessible via:

```
https://phonehospitalservice.com/storage/filename.jpg
```

For example, if a ticket photo is stored at:
```
storage/app/public/ticket-media/uuid-123/photo.jpg
```

It will be accessible at:
```
https://phonehospitalservice.com/storage/ticket-media/uuid-123/photo.jpg
```

## Docker Volume Considerations

The symlink works correctly with Docker volumes because:
- The symlink is created inside the container
- Both `storage/app/public` and `public/storage` are in the same volume
- The symlink persists as long as the volume exists

## Alternative: Direct Storage Access

If symlinks don't work in your environment, you can serve files directly:

1. **Update Nginx config** to serve from `storage/app/public`:

```nginx
location /storage {
    alias /var/www/html/storage/app/public;
    try_files $uri $uri/ =404;
}
```

2. **Update file paths** in your application to use `/storage/` prefix


