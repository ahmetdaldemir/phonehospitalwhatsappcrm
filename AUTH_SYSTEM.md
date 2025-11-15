# Authentication System Documentation

## Overview

The Phone Hospital CRM uses Laravel Sanctum for token-based authentication with role-based access control (RBAC). The system supports two roles: `admin` and `store`.

## Roles

### Admin
- Full access to all resources
- Can manage all users
- Can view all tickets across all stores
- Can create, update, and delete any resource

### Store User
- Limited to their assigned store
- Can only view tickets from their store
- Can only view users from their store
- Cannot change their role or store assignment
- Can update their own profile

## Authentication Flow

### 1. Login

**Endpoint:** `POST /api/auth/login`

**Request:**
```json
{
    "email": "admin@phonehospital.com",
    "password": "password"
}
```

**Response:**
```json
{
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
        "id": "uuid",
        "name": "Admin User",
        "email": "admin@phonehospital.com",
        "role": "admin",
        "store_id": null,
        "store": null
    }
}
```

### 2. Using the Token

Include the token in the `Authorization` header for all protected routes:

```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 3. Get Authenticated User

**Endpoint:** `GET /api/auth/user`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "id": "uuid",
    "name": "Admin User",
    "email": "admin@phonehospital.com",
    "role": "admin",
    "store_id": null,
    "store": null
}
```

### 4. Logout

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "message": "Logged out successfully"
}
```

## API Routes

### Authentication Routes

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/login` | Login and get token | No |
| POST | `/api/auth/logout` | Logout and revoke token | Yes |
| GET | `/api/auth/user` | Get authenticated user | Yes |

### User Management Routes

| Method | Endpoint | Description | Auth Required | Role Required |
|--------|----------|-------------|----------------|---------------|
| GET | `/api/users` | List users | Yes | - |
| GET | `/api/users/{id}` | Get user | Yes | - |
| POST | `/api/users` | Create user | Yes | Admin |
| PUT/PATCH | `/api/users/{id}` | Update user | Yes | Admin |
| DELETE | `/api/users/{id}` | Delete user | Yes | Admin |
| PUT/PATCH | `/api/users/profile` | Update own profile | Yes | - |

**Notes:**
- Store users can only see users from their store
- Store users can update their own profile
- Only admins can create, update, or delete users

### Ticket Routes

| Method | Endpoint | Description | Auth Required | Role Required |
|--------|----------|-------------|----------------|---------------|
| GET | `/api/tickets` | List tickets | Yes | - |
| GET | `/api/tickets/statistics` | Get ticket statistics | Yes | - |
| POST | `/api/tickets` | Create ticket | Yes | - |
| GET | `/api/tickets/{id}` | Get ticket | Yes | - |
| PUT/PATCH | `/api/tickets/{id}` | Update ticket | Yes | - |
| DELETE | `/api/tickets/{id}` | Delete ticket | Yes | - |

**Notes:**
- Store users can only see tickets from their store
- Store users cannot change `store_id` when creating/updating tickets
- Admin can see all tickets across all stores

## Middleware

### Authentication Middleware
- `auth:sanctum` - Verifies the Sanctum token

### Role Middleware
- `role.admin` - Ensures user is an admin
- `role.store` - Ensures user is a store user

### Data Scoping Middleware
- `scope.store` - Automatically scopes data to user's store (for store users)

## Access Control Examples

### Store User Access

A store user with `store_id = "store-uuid-1"` can:
- ✅ View tickets where `store_id = "store-uuid-1"`
- ✅ View users where `store_id = "store-uuid-1"`
- ✅ Create tickets (automatically assigned to their store)
- ✅ Update their own profile
- ❌ View tickets from other stores
- ❌ View users from other stores
- ❌ Change their role or store_id
- ❌ Create/update/delete users

### Admin Access

An admin can:
- ✅ View all tickets from all stores
- ✅ View all users
- ✅ Create, update, delete any user
- ✅ Create, update, delete any ticket
- ✅ Filter tickets by store_id
- ✅ Change user roles and store assignments

## Usage Examples

### Example 1: Login as Admin

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@phonehospital.com",
    "password": "password"
  }'
```

### Example 2: Get All Tickets (Admin)

```bash
curl -X GET http://localhost:8000/api/tickets \
  -H "Authorization: Bearer {token}"
```

### Example 3: Get Tickets for Specific Store (Admin)

```bash
curl -X GET "http://localhost:8000/api/tickets?store_id={store-uuid}" \
  -H "Authorization: Bearer {token}"
```

### Example 4: Get Tickets (Store User)

A store user automatically sees only their store's tickets:

```bash
curl -X GET http://localhost:8000/api/tickets \
  -H "Authorization: Bearer {store-user-token}"
```

### Example 5: Create Ticket (Store User)

```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": "customer-uuid",
    "brand": "Apple",
    "model": "iPhone 14",
    "problem_type": "Screen Repair",
    "price_min": 1500,
    "price_max": 3000,
    "status": "new"
  }'
```

The ticket will be automatically assigned to the store user's store.

### Example 6: Create User (Admin Only)

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Store User",
    "email": "newuser@phonehospital.com",
    "password": "password123",
    "role": "store",
    "store_id": "store-uuid"
  }'
```

### Example 7: Update Own Profile

```bash
curl -X PUT http://localhost:8000/api/users/profile \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "email": "newemail@phonehospital.com"
  }'
```

## User Model Helper Methods

```php
// Check if user is admin
$user->isAdmin(); // returns bool

// Check if user is store user
$user->isStoreUser(); // returns bool

// Check if user belongs to specific store
$user->belongsToStore($storeId); // returns bool
```

## Seeding Users

Run the UserSeeder to create example users:

```bash
php artisan db:seed --class=UserSeeder
```

This creates:
- 1 admin user: `admin@phonehospital.com` / `password`
- Store users for each store: `store1@phonehospital.com` / `password`
- Additional staff for the first store: `staff1@phonehospital.com` / `password`

## Security Notes

1. **Token Storage**: Store tokens securely (e.g., in HTTP-only cookies for web, secure storage for mobile)
2. **HTTPS**: Always use HTTPS in production
3. **Token Expiration**: Configure token expiration in `config/sanctum.php`
4. **Password Requirements**: Enforce strong passwords (minimum 8 characters)
5. **Rate Limiting**: API routes are rate-limited (60 requests per minute per user)

## Error Responses

### Unauthorized (401)
```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "message": "Unauthorized. Admin access required."
}
```

### Validation Error (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

