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

