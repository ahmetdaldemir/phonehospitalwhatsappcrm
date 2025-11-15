# Phone Hospital CRM

A comprehensive Customer Relationship Management system built with Laravel 10+ and Vue 3, designed specifically for phone repair businesses with multi-store support.

## Features

- ✅ **Laravel 10+** with PHP 8.2
- ✅ **MySQL 8** or **PostgreSQL** support (configurable via ENV)
- ✅ **Laravel Sanctum** for API authentication
- ✅ **Vue 3** admin panel with **Vite**
- ✅ **UUID primary keys** for all main tables
- ✅ **Multi-store support** with store-based user management
- ✅ **Soft deletes** enabled on all main tables
- ✅ **RESTful API** routes under `/api`

## Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18.x and npm
- MySQL 8+ or PostgreSQL 12+
- Web server (Apache/Nginx) or PHP built-in server

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd phonehospitalwhatsappcrm
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node.js dependencies

```bash
npm install
```

### 4. Environment Configuration

Copy the `env.example` file to `.env`:

```bash
cp env.example .env
```

**Note:** If you don't have an `env.example` file, you can create a `.env` file manually using the configuration below.

Edit the `.env` file and configure your database connection:

**For MySQL:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=phone_hospital_crm
DB_USERNAME=root
DB_PASSWORD=your_password
```

**For PostgreSQL:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=phone_hospital_crm
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

Create a new database in MySQL or PostgreSQL:

**MySQL:**
```sql
CREATE DATABASE phone_hospital_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**PostgreSQL:**
```sql
CREATE DATABASE phone_hospital_crm;
```

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Create Storage Link

```bash
php artisan storage:link
```

### 9. Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

## Running the Application

### Development Mode

1. **Start Laravel development server:**
   ```bash
   php artisan serve
   ```
   The application will be available at `http://localhost:8000`

2. **Start Vite development server** (in a separate terminal):
   ```bash
   npm run dev
   ```

### Production Mode

1. **Optimize the application:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Build frontend assets:**
   ```bash
   npm run build
   ```

3. **Start the server:**
   ```bash
   php artisan serve
   ```

## Project Structure

```
phonehospitalwhatsappcrm/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── AuthController.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── BaseModel.php      # Base model with UUID and SoftDeletes
│   │   ├── Store.php          # Store model (multi-store support)
│   │   └── User.php           # User model with store relationship
│   └── Providers/
├── config/                     # Configuration files
├── database/
│   ├── factories/             # Model factories
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── public/                     # Public assets
├── resources/
│   ├── css/                    # CSS files
│   ├── js/
│   │   ├── components/         # Vue components
│   │   ├── app.js              # Main Vue app entry
│   │   └── bootstrap.js        # Axios configuration
│   └── views/                  # Blade templates
├── routes/
│   ├── api.php                 # API routes (under /api)
│   └── web.php                 # Web routes
└── tests/                      # PHPUnit tests
```

## API Routes

All API routes are prefixed with `/api`:

- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout (requires authentication)
- `GET /api/auth/user` - Get authenticated user (requires authentication)

### Authentication

The API uses Laravel Sanctum for authentication. Include the token in the `Authorization` header:

```
Authorization: Bearer {token}
```

## Multi-Store Support

The application supports multiple stores. Each user can be associated with a store:

- **Store Model**: Represents a physical store location
- **User-Store Relationship**: Users belong to stores (nullable for admin users)
- **Store-based Data Isolation**: All main tables include `store_id` for data segregation

## UUID Primary Keys

All main tables use UUID primary keys instead of auto-incrementing integers:

- Provides better security (non-sequential IDs)
- Easier to merge data from multiple databases
- Better for distributed systems

## Soft Deletes

All main tables have soft deletes enabled:

- Records are not permanently deleted
- `deleted_at` timestamp tracks when records are deleted
- Can be restored using `restore()` method

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

The project uses Laravel Pint for code formatting:

```bash
./vendor/bin/pint
```

### Database Seeding

Create seeders and run them:

```bash
php artisan db:seed
```

## Troubleshooting

### Permission Issues

If you encounter permission issues with storage:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Clear Cache

If you encounter caching issues:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Vite Issues

If Vite is not working:

1. Clear node_modules and reinstall:
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

2. Clear Vite cache:
   ```bash
   rm -rf node_modules/.vite
   ```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues and questions, please open an issue in the repository.

