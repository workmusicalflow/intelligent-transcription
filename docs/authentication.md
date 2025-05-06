# Authentication System Documentation

## Overview

The Intelligent Transcription system includes a complete user authentication system that provides:

- User accounts with secure password storage
- Role-based access control
- Session management with "Remember Me" functionality
- Integration with transcription data
- Admin user management interface
- CSRF protection
- Secure HTTP-only cookies

## Setup

### Installation

To install the authentication system:

1. Run the installation script:
   ```bash
   php install_auth.php
   ```

2. This creates:
   - All authentication database tables
   - A default admin user
   - Required indexes and relations

3. Default admin credentials:
   - Username: `admin`
   - Password: `admin123`

> ⚠️ **Important:** Change the default admin password after first login!

### Troubleshooting Installation

If you encounter errors during installation:

1. Run the cleanup script to remove temporary tables:
   ```bash
   php cleanup_temp_tables.php
   ```

2. Then run the installation script again:
   ```bash
   php install_auth.php
   ```

## User Management

### User Roles and Permissions

The system includes the following permission types:

| Permission | Description |
|------------|-------------|
| `admin.access` | General admin access |
| `users.manage` | Ability to create and manage users |
| `users.view` | View users list |
| `transcriptions.own` | Access to own transcriptions only |
| `transcriptions.all` | Access to all transcriptions |

Admin users automatically have all permissions.

### User Administration

Administrator users can:

1. Create new users at `/admin.php?controller=user&action=create`
2. Edit existing users at `/admin.php?controller=user&action=edit&id={user_id}`
3. Delete users at `/admin.php?controller=user&action=delete&id={user_id}`
4. Assign permissions to users

Regular users have access to:
1. Their own profile at `/profile.php`
2. Their own transcriptions

## File Structure

### Key Components

- **Entry Points:**
  - `/login.php` - Login form and authentication
  - `/profile.php` - User profile management
  - `/admin.php` - Admin interface

- **Controllers:**
  - `src/Controllers/AuthController.php` - Authentication logic
  - `src/Controllers/UserController.php` - User management

- **Models:**
  - `src/Models/User.php` - User data model

- **Services:**
  - `src/Services/AuthService.php` - Session and authentication

- **Database:**
  - `src/Database/auth_schema.sql` - Database schema
  - `src/Database/DatabaseManager.php` - Database connection

- **Templates:**
  - `templates/auth/login.twig` - Login form
  - `templates/auth/profile.twig` - Profile management
  - `templates/admin/users/` - User administration templates

## Integration with Transcription System

The authentication system integrates with the transcription system through the following mechanisms:

1. **User Association:** All transcriptions are associated with the user who created them via the `user_id` field.

2. **Permission-Based Access:** Users can only view their own transcriptions unless they have admin privileges or the `transcriptions.all` permission.

3. **Controller Integration:** The `TranscriptionController` checks for authentication and passes the user ID to the transcription service.

4. **Service Integration:** The `TranscriptionService` stores and filters transcriptions by user ID based on authentication status.

5. **Global Twig Variables:** Templates automatically receive authentication-related variables:
   - `is_authenticated`: Indicates if a user is logged in
   - `is_admin`: Indicates if the current user is an admin
   - `current_user`: Data for the current user

## Technical Implementation

### Database Schema

**Users Table:**
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    is_admin BOOLEAN DEFAULT 0
);
```

**User Sessions Table:**
```sql
CREATE TABLE user_sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    ip_address TEXT NOT NULL,
    user_agent TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**User Permissions Table:**
```sql
CREATE TABLE user_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    permission TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, permission)
);
```

**Password Reset Tokens Table:**
```sql
CREATE TABLE password_reset_tokens (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Security Features

1. **Password Hashing:** All passwords are hashed using PHP's `password_hash()` function with the `PASSWORD_BCRYPT` algorithm.

2. **CSRF Protection:** All forms include a CSRF token that is verified on submission to prevent cross-site request forgery attacks.

3. **Secure Sessions:** Sessions are managed with secure, HTTP-only cookies and session data is also stored in the database.

4. **Remember Me Functionality:** Users can choose to stay logged in with a secure, expiring token.

5. **Input Validation:** All user inputs are validated and sanitized to prevent SQL injection and other attacks.

6. **Automatic Cleanup:** Expired sessions and tokens are automatically cleaned up.

## Usage in Code

### Authentication Checks

To require authentication in a controller:

```php
// Initialize authentication
AuthService::init();

// Require authentication
AuthService::requireAuth();

// Get current user
$user = AuthService::getCurrentUser();
$userId = $user->getId();
```

### Permission Checks

To check for specific permissions:

```php
// Check if user has admin access
if (AuthService::hasPermission('admin.access')) {
    // Show admin features
}

// Require specific permission
AuthService::requirePermission('users.manage');
```

### User Association

To associate data with the current user:

```php
// Get user ID if authenticated
$userId = null;
if (AuthService::isAuthenticated()) {
    $userId = AuthService::getCurrentUser()->getId();
}

// Store data with user ID
$result = $service->storeData($data, $userId);
```

## Customization

### Adding New Permissions

1. Define the new permission string (e.g., `transcriptions.export`)

2. Add the permission to a user:
   ```php
   User::addPermission($userId, 'transcriptions.export');
   ```

3. Check for the permission in your code:
   ```php
   if (AuthService::hasPermission('transcriptions.export')) {
       // Allow export functionality
   }
   ```

### Password Policy Customization

Password requirements (minimum length, complexity, etc.) can be modified in the `ValidationUtils` class and the authentication controllers.

## Future Enhancements

Potential future enhancements to the authentication system:

- Two-factor authentication (2FA)
- OAuth integration for third-party login
- User groups with group-based permissions
- Password expiration and history
- Comprehensive activity logging
- Login attempt throttling for security