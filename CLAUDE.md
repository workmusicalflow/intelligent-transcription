# CLAUDE.md - Intelligent Transcription System

## 📋 Project Overview

**Intelligent Transcription System** is a comprehensive web application that provides AI-powered audio transcription and translation services with advanced features like emotional context preservation, character name detection, and dubbing optimization.

### 🎯 Core Features
- **Audio Transcription**: OpenAI Whisper-1 integration with word-level timestamps
- **Multi-language Translation**: GPT-4o-mini powered translation with emotional context
- **Dubbing Optimization**: Advanced timing and emotional preservation for voice dubbing
- **Real-time Processing**: Immediate processing capabilities with elegant animations
- **YouTube Integration**: Direct video download and processing from YouTube URLs

## 🏗️ Architecture Overview

### Backend (PHP)
- **Clean Architecture**: Domain-driven design with CQRS pattern
- **API Endpoints**: RESTful API v2 with comprehensive translation services
- **Database**: SQLite with optimized schema for transcriptions and translations
- **Queue System**: Background workers with cron-based job processing
- **External Services**: OpenAI API integration for Whisper and GPT models

### Frontend (Vue.js 3)
- **Framework**: Vue 3 with Composition API and TypeScript
- **Styling**: Tailwind CSS with custom animations and dark mode
- **State Management**: Pinia stores for authentication and UI state
- **Real-time Updates**: WebSocket integration with polling fallbacks
- **Progressive Web App**: PWA capabilities with service workers

## 🔧 Development Environment

### Required Tools
- **PHP 8.0+** with extensions: sqlite3, curl, json
- **Node.js 18+** with npm for frontend development
- **Composer** for PHP dependency management
- **OpenAI API Key** for transcription and translation services

### Quality Control Commands
```bash
# Backend quality checks
php -l src/**/*.php                    # PHP syntax check
vendor/bin/phpunit                     # Run PHP tests

# Frontend quality checks
cd frontend && npx vue-tsc --noEmit --pretty  # TypeScript validation
npm run lint                           # ESLint validation
npm run test                           # Unit tests

# Build commands
cd frontend && npm run build           # Production build
php -S localhost:8000 router.php      # Development server
```

### Database Migrations
```bash
php migrate.php                        # Run all pending migrations
php test-backend.php                   # Test database connectivity
```

## 🚀 Recent Major Implementations

### Translation System Revolution (Latest)
- **Stop/Delete Functionality**: Users can stop and delete translations with cascade deletion
- **Immediate Processing**: Process translations ≤20 segments immediately with real-time feedback
- **Animation System**: Master-class animations consistent with transcription system
- **Worker Architecture**: Fixed cron-based workers for reliable background processing

#### Animation Components
- `TranslationProcessingIndicator.vue`: Reusable component with inline/detailed variants
- Shimmer effects, progress bars, rotating messages, and typing cursors
- Real-time polling with 2-second intervals for immediate processing feedback

### Translation API Endpoints
```
POST /api/v2/translations/create       # Create new translation
GET  /api/v2/translations/list         # List translations with filtering
GET  /api/v2/translations/status/{id}  # Get translation status
POST /api/v2/translations/stop/{id}    # Stop active translation
DELETE /api/v2/translations/{id}       # Delete translation (cascade)
POST /api/v2/translations/process      # Process translation immediately
GET  /api/v2/translations/download/{id} # Download in various formats
```

### Worker System
- **process_translations_batch.php**: Processes ONE translation then exits
- **process_translations_simple.php**: Alternative simple worker
- **cron_translations.php**: Cron entry point (every minute)
- **Lock files**: Prevents concurrent execution conflicts

## 📁 Key File Locations

### Backend Core
```
src/
├── Application/           # Application services and DTOs
├── Domain/               # Business logic and entities
├── Infrastructure/       # External integrations and persistence
├── Controllers/          # HTTP controllers
└── Services/            # Application services

api/v2/                   # API v2 endpoints
├── translations/         # Translation endpoints
└── index.php            # API router
```

### Frontend Core
```
frontend/src/
├── components/
│   ├── translation/      # Translation-specific components
│   ├── transcription/    # Transcription components
│   └── ui/              # Reusable UI components
├── views/               # Page components
├── api/                 # API client services
├── stores/              # Pinia state management
└── composables/         # Vue composables
```

### Database Schema
```
transcriptions           # Main transcription records
translation_projects     # Translation projects
translation_segments     # Translated segments with timestamps
users                   # User accounts and authentication
openai_cache            # API response caching
```

## 🔍 Common Development Patterns

### API Client Pattern
```typescript
// All API calls use this pattern
export class TranslationAPI {
  static async methodName(params): Promise<ApiResponse<T>> {
    const response = await apiClient.post('/endpoint', params)
    return response.data
  }
}
```

### Vue Component Pattern
```vue
<template>
  <!-- Template with Tailwind CSS -->
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
// Composition API with TypeScript
</script>

<style scoped>
/* Scoped styles with Tailwind utilities */
</style>
```

### Backend Service Pattern
```php
// Clean architecture with dependency injection
class TranslationService {
    public function __construct(
        private TranslationRepository $repository,
        private OpenAIClient $openaiClient
    ) {}
}
```

## 🧪 Testing Strategy

### Backend Testing
- **Unit Tests**: Domain logic and services
- **Integration Tests**: API endpoints and database
- **Quality Tools**: Intelephense for PHP diagnostics

### Frontend Testing
- **Unit Tests**: Vitest for component testing
- **Type Checking**: vue-tsc for TypeScript validation
- **E2E Tests**: Cypress for user workflow testing

### Test Commands
```bash
# Run backend tests
php vendor/bin/phpunit

# Run frontend tests
cd frontend && npm test

# Type checking
cd frontend && npx vue-tsc --noEmit --pretty
```

## 🔐 Security Considerations

### Authentication
- JWT-based authentication with refresh tokens
- User roles: admin, user with appropriate permissions
- Rate limiting on API endpoints

### API Security
- Input validation and sanitization
- SQL injection prevention with prepared statements
- CORS configuration for frontend-backend communication

### File Security
- Upload restrictions and validation
- Temporary file cleanup
- Secure file storage with proper permissions

### Rate Limiting & Performance
- **Backend Limits**: 300 req/min, 5000 req/h (increased from 60/1000)
- **Exempt Routes**: Read-only endpoints like capabilities, list, status
- **Frontend Handling**: Automatic retry with exponential backoff
- **Caching Strategy**: Capabilities (1h), Lists (5min) to reduce API calls
- **Stores**: Pinia stores for centralized state (translations, capabilities)
- See `RATE_LIMITING_GUIDE.md` for complete documentation

## 🚨 Important Notes for Future Sessions

## 🚨 Important Notes for Future Sessions

### Database Migrations
- Always run migrations after git pull: `php migrate.php`
- Check migration status before making schema changes
- Backup database before major migrations

### API Development
- Use API v2 structure for new endpoints
- Follow existing naming conventions and response patterns
- Test endpoints with both success and error scenarios
- **CRITICAL**: `ApiResponse` class does NOT have `toJson()` method - use `json_encode()` directly on data

### Frontend Development
- Always run `npx vue-tsc --noEmit --pretty` before commits
- Use existing UI components before creating new ones
- Follow animation patterns established in transcription system

### Worker Management
- Workers process ONE item then exit (no infinite loops)
- Use lock files to prevent concurrent execution
- Test worker functionality with `php process_translations_batch.php`

### Recent Critical Fixes (2025-06-04)
- **Translation API Response**: Fixed `ApiResponse::toJson()` undefined method error
- **Immediate Processing**: Now works correctly without frontend JSON parsing errors
- **Rate Limiting**: Increased limits to 300/5000 req/min/hour with route exemptions

## 🎯 Immediate Next Steps

1. **Performance Optimization**: Implement caching for frequently accessed data
2. **Error Handling**: Improve error messages and user feedback
3. **Mobile Optimization**: Enhance responsive design for mobile devices
4. **Analytics Dashboard**: Add comprehensive usage analytics
5. **Batch Operations**: Support multiple file uploads and batch processing

## 📞 Development Commands Quick Reference

```bash
# Start development
php -S localhost:8000 router.php
cd frontend && npm run dev

# Quality checks
cd frontend && npx vue-tsc --noEmit --pretty
php vendor/bin/phpunit

# Deploy build
cd frontend && npm run build
chmod +x worker_supervisor.sh

# Database operations
php migrate.php
php create-test-translations.php
```

---

*This CLAUDE.md file should be updated whenever major features are implemented or architectural decisions are made.*