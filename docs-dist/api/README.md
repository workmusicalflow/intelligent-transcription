# API Reference

## Vue d'ensemble

Intelligent Transcription propose deux types d'APIs :

- **REST API** : Endpoints traditionnels pour les opérations CRUD
- **GraphQL API** : Interface moderne avec subscriptions temps réel

## 🔄 REST API

### Authentification

#### POST /api/auth/login
Connexion utilisateur avec email/mot de passe.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

#### POST /api/auth/logout
Déconnexion utilisateur (nécessite authentification).

#### GET /api/auth/me
Informations de l'utilisateur connecté.

### Transcriptions

#### GET /api/transcriptions/list
Liste des transcriptions de l'utilisateur.

**Query Parameters:**
- `page` (int): Numéro de page (défaut: 1)
- `limit` (int): Nombre d'éléments par page (défaut: 10)
- `status` (string): Filtrer par statut (pending, processing, completed, failed)

**Response:**
```json
{
  "success": true,
  "data": {
    "transcriptions": [
      {
        "id": "tr_123",
        "title": "Réunion équipe",
        "status": "completed",
        "duration": 1800,
        "created_at": "2024-01-15T10:30:00Z"
      }
    ],
    "pagination": {
      "page": 1,
      "total_pages": 5,
      "total_items": 47
    }
  }
}
```

#### GET /api/transcriptions/detail
Détails d'une transcription spécifique.

**Query Parameters:**
- `id` (string, required): ID de la transcription

#### POST /api/transcriptions/create
Créer une nouvelle transcription.

**Request (multipart/form-data):**
- `file`: Fichier audio/vidéo
- `language`: Code langue (optionnel)
- `title`: Titre personnalisé (optionnel)

## 🚀 GraphQL API

### Endpoint
```
POST /graphql
```

### Queries

#### Obtenir une transcription
```graphql
query GetTranscription($id: ID!) {
  transcription(id: $id) {
    id
    title
    status
    content
    language
    duration
    createdAt
    updatedAt
  }
}
```

#### Lister les transcriptions
```graphql
query ListTranscriptions($first: Int, $after: String) {
  transcriptions(first: $first, after: $after) {
    edges {
      node {
        id
        title
        status
        createdAt
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
```

### Mutations

#### Créer une transcription
```graphql
mutation CreateTranscription($input: CreateTranscriptionInput!) {
  createTranscription(input: $input) {
    transcription {
      id
      status
    }
    errors {
      field
      message
    }
  }
}
```

### Subscriptions

#### Suivi du progrès de transcription
```graphql
subscription TranscriptionProgress($id: ID!) {
  transcriptionProgress(id: $id) {
    id
    status
    progress
    message
  }
}
```

## 🔐 Authentification

### JWT Token
Toutes les requêtes authentifiées nécessitent un header :
```
Authorization: Bearer YOUR_JWT_TOKEN
```

### Codes d'erreur

| Code | Message | Description |
|------|---------|-------------|
| 401 | Unauthorized | Token manquant ou invalide |
| 403 | Forbidden | Permissions insuffisantes |
| 422 | Validation Error | Données de requête invalides |
| 429 | Rate Limited | Trop de requêtes |

## 📊 Rate Limiting

- **Authentification** : 5 tentatives/minute
- **API générale** : 100 requêtes/minute
- **Upload de fichiers** : 10 uploads/heure

## 🌐 CORS

Origines autorisées en développement :
- `http://localhost:5173` (Vite dev server)
- `http://localhost:8000` (PHP dev server)

En production : domaines configurés dans `config.php`