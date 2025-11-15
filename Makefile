.PHONY: help build up down restart logs shell migrate seed cache-clear deploy backup

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker containers
	docker-compose build

up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

logs: ## View logs
	docker-compose logs -f

shell: ## Open shell in app container
	docker-compose exec app bash

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

cache-clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

cache: ## Cache configuration
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

storage-link: ## Create storage symlink
	docker-compose exec app php artisan storage:link

permissions: ## Fix storage permissions
	docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
	docker-compose exec app chmod -R 775 storage bootstrap/cache

npm-install: ## Install npm dependencies
	docker-compose exec app npm install

npm-build: ## Build frontend assets
	docker-compose exec app npm run build

deploy: ## Full deployment
	@./scripts/deploy.sh

backup: ## Backup database
	@./scripts/backup.sh

test: ## Run tests
	docker-compose exec app php artisan test

tinker: ## Open Laravel Tinker
	docker-compose exec app php artisan tinker

queue-work: ## Start queue worker
	docker-compose exec app php artisan queue:work

queue-restart: ## Restart queue worker
	docker-compose restart queue


