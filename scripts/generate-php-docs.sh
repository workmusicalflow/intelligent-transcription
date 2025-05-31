#!/bin/bash

# Generate PHP Documentation
# Generates comprehensive documentation for PHP backend components

set -e

echo "🔄 Generating PHP Documentation..."

# Create docs directory structure
mkdir -p docs/backend/api docs/backend/domain docs/backend/infrastructure

# Generate API documentation from OpenAPI spec
if [ -f "api/v2/openapi.yaml" ]; then
    echo "📋 Generating API documentation from OpenAPI spec..."
    npx @redocly/openapi-cli build-docs api/v2/openapi.yaml --output docs/backend/api/index.html
fi

# Generate PHPDoc documentation
echo "📖 Generating PHPDoc documentation..."
if command -v phpdoc &> /dev/null; then
    phpdoc run \
        --directory src/ \
        --target docs/backend/phpdoc \
        --title "Intelligent Transcription Backend" \
        --template responsive-twig \
        --ignore "*/vendor/*,*/tests/*,*/cache/*"
else
    echo "⚠️  PHPDoc not found, skipping PHP documentation generation"
fi

# Generate Domain Layer Documentation
echo "🏗️  Generating Domain Layer documentation..."
cat > docs/backend/domain/README.md << 'EOF'
# Domain Layer Documentation

## Overview
This document describes the domain layer implementation following Clean Architecture principles.

## Domain Entities

### Transcription
Core business entity representing a transcription process.

**Location:** `src/Domain/Transcription/Entity/Transcription.php`

**Key Methods:**
- `create()` - Creates a new transcription
- `startProcessing()` - Begins transcription processing
- `complete()` - Marks transcription as completed
- `fail()` - Marks transcription as failed

### User
Represents system users with authentication capabilities.

**Location:** `src/Domain/User/Entity/User.php`

## Value Objects

### TranscriptionId
Unique identifier for transcriptions.

### AudioFile
Represents uploaded audio files with validation.

### Language
Supported transcription languages.

## Services

### TranscriptionWorkflowService
Orchestrates the transcription workflow process.

### TranscriptionPricingService
Calculates transcription costs based on duration and language.

## Events

### TranscriptionCreated
Fired when a new transcription is created.

### TranscriptionCompleted
Fired when transcription processing completes.

### TranscriptionFailed
Fired when transcription processing fails.

EOF

# Generate Infrastructure Documentation
echo "⚙️  Generating Infrastructure documentation..."
cat > docs/backend/infrastructure/README.md << 'EOF'
# Infrastructure Layer Documentation

## Overview
Implementation details for external dependencies and technical concerns.

## External Services

### OpenAI Integration
**Location:** `src/Infrastructure/External/OpenAI/`

- **WhisperAdapter** - Handles audio transcription via OpenAI Whisper
- **ChatCompletionAdapter** - Manages chat completions
- **GPTSummaryAdapter** - Generates content summaries

### YouTube Integration
**Location:** `src/Infrastructure/External/YouTube/`

- **YouTubeDownloader** - Downloads audio from YouTube videos
- **YouTubeDownloadAdapter** - Adapter for video download service

## Persistence

### SQLite Implementation
**Location:** `src/Infrastructure/Persistence/`

- **SQLiteConnection** - Database connection management
- **SQLiteTranscriptionRepository** - Transcription data persistence
- **SQLiteChatRepository** - Chat conversation persistence

## Event System

### EventDispatcher
**Location:** `src/Infrastructure/Event/EventDispatcher.php`

Handles domain event dispatching and processing.

## GraphQL API

### Schema Definition
**Location:** `src/Infrastructure/GraphQL/`

- **TranscriptionType** - GraphQL type for transcriptions
- **UserType** - GraphQL type for users
- **TranscriptionSubscription** - Real-time transcription updates

EOF

echo "✅ PHP Documentation generated successfully!"
echo "📍 Documentation available in docs/backend/"