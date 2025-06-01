# Deployment Architecture

## Overview
The application supports multiple deployment scenarios from development to production.

## Development Environment

```
┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │
│   localhost:5173│◄──►│  localhost:8000 │
│   (Vite Dev)    │    │   (PHP Server)  │
└─────────────────┘    └─────────────────┘
          │                       │
          │                       │
          └───────────┬───────────┘
                      │
          ┌─────────────────┐
          │   SQLite DB     │
          │   (Local File)  │
          └─────────────────┘
```

### Development Setup
1. **Frontend:** `npm run dev` (Vite development server)
2. **Backend:** `php -S localhost:8000 router.php`
3. **Database:** SQLite file in project root

## Production Environment

### cPanel Shared Hosting
```
┌─────────────────┐    ┌─────────────────┐
│   Static Files  │    │   PHP Scripts   │
│  (public_html)  │◄──►│  (app folder)   │
│                 │    │                 │
│ • Built Vue App │    │ • Backend API   │
│ • Assets        │    │ • File Uploads  │
│ • index.html    │    │ • SQLite DB     │
└─────────────────┘    └─────────────────┘
```

### Deployment Process
1. **Build Frontend:** `npm run build`
2. **Upload Files:** Deploy via cPanel File Manager
3. **Configure PHP:** Set PHP version and ini settings
4. **Database Setup:** Initialize SQLite database
5. **Environment Config:** Set production environment variables

### File Structure (Production)
```
public_html/
├── index.html          # Vue.js SPA entry point
├── assets/             # Compiled JS/CSS assets
├── api/                # PHP API endpoints
├── app/                # Backend application
│   ├── src/            # Clean architecture layers
│   ├── vendor/         # Composer dependencies
│   └── database.sqlite # SQLite database
└── uploads/            # User uploaded files
```

## Security Considerations

### Frontend Security
- Environment-based API URL configuration
- JWT token storage in secure cookies
- CSP headers for XSS protection
- Input validation and sanitization

### Backend Security
- JWT authentication with secure tokens
- File upload validation and restrictions
- SQL injection prevention (prepared statements)
- Rate limiting for API endpoints
- CORS configuration for cross-origin requests

### Infrastructure Security
- HTTPS enforcement
- Secure file permissions
- Database access restrictions
- Environment variable protection

