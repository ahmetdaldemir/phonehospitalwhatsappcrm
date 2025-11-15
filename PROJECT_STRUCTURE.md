# Phone Hospital CRM - Project Structure

## Overview

This document provides a detailed overview of the project structure and key architectural decisions.

## Architecture

### Backend (Laravel 10+)
- **Framework**: Laravel 10+
- **PHP Version**: 8.2+
- **Database**: MySQL 8 or PostgreSQL (configurable)
- **Authentication**: Laravel Sanctum
- **Primary Keys**: UUID (all main tables)
- **Soft Deletes**: Enabled on all main tables

### Frontend (Vue 3)
- **Framework**: Vue 3
- **Build Tool**: Vite
- **CSS Framework**: Tailwind CSS
- **HTTP Client**: Axios

## Key Features

### 1. UUID Primary Keys
All main models extend `BaseModel` which implements:
- `HasUuids` trait for UUID generation
- `SoftDeletes` for soft deletion support
- Automatic UUID generation on model creation

### 2. Multi-Store Support
- **Store Model**: Represents physical store locations
- **User-Store Relationship**: Users belong to stores (nullable for admin users)
- **Store Scoping**: Middleware and traits available for automatic store-based data filtering

### 3. API Structure
- All API routes are under `/api` prefix
- Sanctum authentication for API access
- RESTful design principles

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # API controllers
│   │   │   └── AuthController.php
│   │   └── Controller.php    # Base controller
│   └── Middleware/
│       ├── EnsureEmailIsVerified.php
│       └── SetStoreContext.php
├── Models/
│   ├── BaseModel.php         # Base model with UUID & SoftDeletes
│   ├── Store.php             # Store model
│   └── User.php              # User model
├── Providers/                # Service providers
└── Traits/
    └── HasStore.php          # Trait for store relationships

config/                       # Configuration files
├── app.php
├── auth.php
├── database.php              # Database configuration (MySQL/PostgreSQL)
├── sanctum.php               # Sanctum configuration
└── ...

database/
├── factories/                # Model factories
├── migrations/               # Database migrations
└── seeders/                  # Database seeders

resources/
├── css/
│   └── app.css               # Main CSS file (Tailwind)
├── js/
│   ├── app.js                # Vue app entry point
│   ├── bootstrap.js          # Axios configuration
│   └── components/
│       └── App.vue           # Main Vue component
└── views/
    └── app.blade.php         # Main Blade template

routes/
├── api.php                   # API routes (under /api)
├── web.php                   # Web routes
└── console.php               # Console routes
```

## Models

### BaseModel
All main models extend `BaseModel` which provides:
- UUID primary keys
- Soft deletes
- Automatic UUID generation

### Store Model
- Represents a physical store location
- Fields: name, code, address, phone, email, is_active
- Has many users

### User Model
- Authentication model
- Belongs to a store (nullable)
- Uses Sanctum for API tokens
- Has soft deletes

## Database Schema

### Stores Table
- `id` (UUID, primary key)
- `name` (string)
- `code` (string, unique)
- `address` (text, nullable)
- `phone` (string, nullable)
- `email` (string, nullable)
- `is_active` (boolean)
- `created_at`, `updated_at`, `deleted_at`

### Users Table
- `id` (UUID, primary key)
- `name` (string)
- `email` (string, unique)
- `password` (hashed)
- `store_id` (UUID, foreign key, nullable)
- `role` (string)
- `email_verified_at` (timestamp, nullable)
- `remember_token`
- `created_at`, `updated_at`, `deleted_at`

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout (requires auth)
- `GET /api/auth/user` - Get authenticated user (requires auth)

### Authentication Flow
1. Client sends credentials to `/api/auth/login`
2. Server validates and returns Sanctum token
3. Client includes token in `Authorization: Bearer {token}` header
4. Server validates token on protected routes

## Development Workflow

### Adding New Models
1. Create migration with UUID primary key
2. Add `deleted_at` column for soft deletes
3. Create model extending `BaseModel`
4. Add factory if needed
5. Create seeder if needed

### Adding API Routes
1. Create controller in `app/Http/Controllers/Api/`
2. Add routes to `routes/api.php`
3. Use `auth:sanctum` middleware for protected routes

### Adding Vue Components
1. Create component in `resources/js/components/`
2. Import and use in `App.vue` or other components
3. Vite will hot-reload during development

## Best Practices

1. **Always use UUIDs** for primary keys in main tables
2. **Enable soft deletes** on all main tables
3. **Use store scoping** for multi-store data isolation
4. **Follow RESTful conventions** for API routes
5. **Use factories and seeders** for development data
6. **Keep API responses consistent** in format

## Security Considerations

1. **Sanctum tokens** are used for API authentication
2. **CSRF protection** is enabled for stateful requests
3. **Password hashing** uses bcrypt
4. **Soft deletes** prevent accidental data loss
5. **Store scoping** ensures data isolation between stores

