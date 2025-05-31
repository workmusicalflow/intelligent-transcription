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

