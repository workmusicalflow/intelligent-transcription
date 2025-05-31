#!/bin/bash

# Generate Architecture Documentation
# Creates comprehensive documentation about the system architecture

set -e

echo "🏗️  Generating Architecture Documentation..."

# Create architecture docs directory
mkdir -p docs/architecture

# Generate system overview
cat > docs/architecture/overview.md << 'EOF'
# System Architecture Overview

## Introduction
Intelligent Transcription is a web application that converts audio and video files to text using OpenAI's Whisper API. The system follows Clean Architecture principles with clear separation of concerns.

## High-Level Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │  External APIs  │
│   (Vue.js 3)    │◄──►│   (PHP 8.2)     │◄──►│   (OpenAI)      │
│                 │    │                 │    │                 │
│ • Vue Router    │    │ • Clean Arch    │    │ • Whisper API   │
│ • Pinia Store   │    │ • Domain Layer  │    │ • GPT API       │
│ • TypeScript    │    │ • SQLite DB     │    │ • YouTube API   │
│ • Tailwind CSS  │    │ • GraphQL API   │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Technology Stack

### Frontend
- **Framework:** Vue.js 3 with Composition API
- **Language:** TypeScript
- **Styling:** Tailwind CSS
- **State Management:** Pinia
- **Routing:** Vue Router 4
- **Build Tool:** Vite
- **Testing:** Vitest + Vue Test Utils
- **E2E Testing:** Cypress

### Backend
- **Language:** PHP 8.2+
- **Architecture:** Clean Architecture / Domain-Driven Design
- **Database:** SQLite
- **API:** REST + GraphQL
- **Queue:** File-based queue system
- **Template Engine:** Twig

### External Services
- **Transcription:** OpenAI Whisper API
- **AI Chat:** OpenAI GPT API
- **Video Download:** YouTube API
- **Error Tracking:** Sentry

## Core Features

1. **Audio/Video Transcription**
   - Upload audio/video files
   - YouTube URL transcription
   - Real-time processing status
   - Multiple language support

2. **AI-Powered Chat**
   - Contextual conversations about transcriptions
   - Document analysis and summarization
   - Multi-turn conversations

3. **User Management**
   - User registration and authentication
   - Profile management
   - Usage analytics

4. **Real-time Updates**
   - WebSocket connections
   - GraphQL subscriptions
   - Live transcription progress

EOF

# Generate frontend architecture documentation
cat > docs/architecture/frontend.md << 'EOF'
# Frontend Architecture

## Overview
The frontend is built with Vue.js 3 using the Composition API and TypeScript for type safety.

## Project Structure

```
src/
├── api/           # API client and authentication
├── components/    # Reusable Vue components
│   ├── ui/        # Generic UI components
│   ├── layout/    # Layout components
│   └── ...        # Feature-specific components
├── composables/   # Vue composables for reusable logic
├── stores/        # Pinia state management
├── types/         # TypeScript type definitions
├── views/         # Page components
└── router/        # Vue Router configuration
```

## State Management

### Stores
- **AuthStore:** User authentication and session management
- **UIStore:** Global UI state (notifications, modals, theme)
- **AppStore:** Application-wide state and settings

### Data Flow
1. Components dispatch actions to stores
2. Stores update reactive state
3. Components reactively update based on state changes
4. API calls handled through dedicated API modules

## Component Architecture

### Component Categories
1. **UI Components** (`components/ui/`)
   - Generic, reusable components
   - No business logic
   - Props-based configuration

2. **Layout Components** (`components/layout/`)
   - App structure components
   - Navigation, headers, sidebars

3. **Feature Components** (`components/[feature]/`)
   - Business logic components
   - Feature-specific functionality

4. **Page Components** (`views/`)
   - Route-level components
   - Compose multiple components

### Composition API Patterns
- Use `<script setup>` syntax for cleaner code
- Extract reusable logic into composables
- Prefer computed properties over methods for derived state
- Use reactive() for complex object state, ref() for primitives

EOF

# Generate backend architecture documentation
cat > docs/architecture/backend.md << 'EOF'
# Backend Architecture

## Overview
The backend follows Clean Architecture principles with clear separation between layers.

## Clean Architecture Layers

```
┌─────────────────────────────────────┐
│            Frameworks               │  ← Infrastructure Layer
│  (Twig, SQLite, OpenAI, GraphQL)    │
└─────────────────┬───────────────────┘
                  │
┌─────────────────┼───────────────────┐
│       Interface Adapters            │  ← Application Layer  
│   (Controllers, Repositories)       │
└─────────────────┬───────────────────┘
                  │
┌─────────────────┼───────────────────┐
│         Use Cases                   │  ← Application Layer
│    (Command/Query Handlers)         │
└─────────────────┬───────────────────┘
                  │
┌─────────────────┼───────────────────┐
│          Entities                   │  ← Domain Layer
│   (Business Rules & Logic)          │
└─────────────────────────────────────┘
```

## Domain Layer

### Entities
- **Transcription:** Core business entity for transcription processes
- **User:** User management and authentication
- **Conversation:** Chat conversation management

### Value Objects
- **TranscriptionId:** Unique transcription identifier
- **AudioFile:** Audio file representation with validation
- **Language:** Supported language codes
- **TranscriptionStatus:** Processing status enumeration

### Domain Services
- **TranscriptionWorkflowService:** Orchestrates transcription process
- **TranscriptionPricingService:** Calculates costs

## Application Layer

### Command/Query Separation (CQRS)
- **Commands:** Write operations (CreateTranscription, AuthenticateUser)
- **Queries:** Read operations (GetTranscription, ListTranscriptions)
- **Handlers:** Process commands and queries

### Application Services
- **TranscriptionApplicationService:** Transcription use cases
- **UserApplicationService:** User management use cases
- **ChatApplicationService:** Chat functionality

## Infrastructure Layer

### External Services
- **OpenAI Adapters:** Integration with OpenAI APIs
- **YouTube Adapter:** Video download functionality
- **Email Service:** Notification delivery

### Persistence
- **SQLite Repositories:** Data access implementation
- **Database Manager:** Connection and migration management
- **Event Store:** Domain event persistence

### Web Layer
- **Controllers:** HTTP request handling
- **GraphQL Resolvers:** GraphQL query/mutation handling
- **Middleware:** Cross-cutting concerns (auth, CORS, etc.)

EOF

# Generate deployment documentation
cat > docs/architecture/deployment.md << 'EOF'
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

EOF

echo "✅ Architecture Documentation generated successfully!"
echo "📍 Documentation available in docs/architecture/"