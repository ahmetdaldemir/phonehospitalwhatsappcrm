#!/bin/bash

# Deployment script for Phone Hospital CRM
# Usage: ./scripts/deploy.sh

set -e

echo "🚀 Starting deployment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please copy env.production to .env and configure it."
    exit 1
fi

# Build and start containers
echo "📦 Building Docker containers..."
docker-compose up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 10

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    docker-compose exec -T app php artisan key:generate --force
fi

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Create storage symlink
echo "🔗 Creating storage symlink..."
docker-compose exec -T app php artisan storage:link || true

# Set permissions
echo "🔐 Setting permissions..."
docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec -T app chmod -R 775 storage bootstrap/cache

# Install and build frontend
echo "🎨 Building frontend assets..."
docker-compose exec -T app npm install
docker-compose exec -T app npm run build

# Cache configuration
echo "⚡ Caching configuration..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

echo "✅ Deployment completed successfully!"
echo ""
echo "📊 Container status:"
docker-compose ps

echo ""
echo "🔍 View logs with: docker-compose logs -f"


