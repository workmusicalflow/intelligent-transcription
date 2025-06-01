# Backend API Documentation

## Vue d'ensemble

Documentation technique complète de l'API backend d'Intelligent Transcription.

## 🏗️ Architecture API

### Structure
```
api/
├── auth/              # Authentification
│   ├── login.php      # Connexion utilisateur
│   ├── logout.php     # Déconnexion
│   └── me.php         # Profil utilisateur
├── transcriptions/   # Gestion transcriptions
│   ├── list.php       # Liste des transcriptions
│   └── detail.php     # Détails transcription
└── v2/               # API v2 (moderne)
    ├── index.php      # Point d'entrée
    └── openapi.yaml   # Spécification OpenAPI
```

### Base URL
```
Production: https://yourdomain.com/api
Développement: http://localhost:8000/api
```

## 🔐 Authentification

### JWT Authentication

#### POST /api/auth/login
**Description:** Connexion utilisateur avec email/mot de passe.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "user",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-20T14:25:00Z"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_at": "2024-01-22T10:30:00Z"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Email ou mot de passe incorrect",
    "details": {
      "attempts_remaining": 2
    }
  }
}
```

#### GET /api/auth/me
**Description:** Informations de l'utilisateur connecté.

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "user",
      "preferences": {
        "language": "fr",
        "theme": "dark",
        "notifications": true
      },
      "stats": {
        "transcriptions_count": 15,
        "total_duration": 7200,
        "last_activity": "2024-01-20T14:25:00Z"
      }
    }
  }
}
```

#### POST /api/auth/logout
**Description:** Déconnexion utilisateur (invalide le token).

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

## 📝 Transcriptions

### GET /api/transcriptions/list
**Description:** Liste des transcriptions de l'utilisateur.

**Query Parameters:**
- `page` (int, default: 1): Numéro de page
- `limit` (int, default: 10, max: 50): Nombre d'éléments par page
- `status` (string): Filtrer par statut (`pending`, `processing`, `completed`, `failed`)
- `search` (string): Recherche dans le titre ou contenu
- `language` (string): Filtrer par langue
- `date_from` (string, ISO 8601): Date de début
- `date_to` (string, ISO 8601): Date de fin
- `sort` (string): Tri (`created_at`, `title`, `duration`)
- `order` (string): Ordre (`asc`, `desc`)

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "transcriptions": [
      {
        "id": "tr_123456",
        "title": "Réunion équipe marketing",
        "status": "completed",
        "language": "fr",
        "duration": 1800,
        "file_size": 15728640,
        "file_type": "audio/mp3",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:45:00Z",
        "completed_at": "2024-01-15T10:45:00Z",
        "word_count": 2450,
        "confidence_score": 0.95,
        "preview": "Bonjour à tous, nous allons commencer..."
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total_pages": 5,
      "total_items": 47,
      "has_next": true,
      "has_prev": false
    },
    "filters": {
      "applied": {
        "status": "completed",
        "language": "fr"
      },
      "available": {
        "statuses": ["pending", "processing", "completed", "failed"],
        "languages": ["fr", "en", "es", "de"]
      }
    }
  }
}
```

### GET /api/transcriptions/detail
**Description:** Détails complets d'une transcription.

**Query Parameters:**
- `id` (string, required): ID de la transcription

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "transcription": {
      "id": "tr_123456",
      "title": "Réunion équipe marketing",
      "status": "completed",
      "language": "fr",
      "content": {
        "text": "Bonjour à tous, nous allons commencer cette réunion...",
        "segments": [
          {
            "id": 1,
            "start": 0.0,
            "end": 3.5,
            "text": "Bonjour à tous",
            "confidence": 0.98,
            "speaker": "Speaker 1"
          },
          {
            "id": 2,
            "start": 3.5,
            "end": 8.2,
            "text": "nous allons commencer cette réunion",
            "confidence": 0.95,
            "speaker": "Speaker 1"
          }
        ],
        "summary": "Réunion de planification marketing avec discussion des objectifs Q1...",
        "keywords": ["marketing", "objectifs", "planification", "budget"]
      },
      "metadata": {
        "duration": 1800,
        "file_size": 15728640,
        "file_type": "audio/mp3",
        "sample_rate": 44100,
        "channels": 2,
        "bitrate": 192000
      },
      "processing": {
        "started_at": "2024-01-15T10:32:00Z",
        "completed_at": "2024-01-15T10:45:00Z",
        "duration_seconds": 780,
        "model_used": "whisper-large-v3",
        "language_detection": {
          "detected": "fr",
          "confidence": 0.99,
          "alternatives": [
            {"language": "en", "confidence": 0.01}
          ]
        }
      },
      "analytics": {
        "word_count": 2450,
        "sentence_count": 156,
        "speaking_rate": 145,
        "pause_count": 23,
        "average_confidence": 0.95
      },
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:45:00Z"
    }
  }
}
```

### POST /api/transcriptions/create
**Description:** Créer une nouvelle transcription.

**Content-Type:** `multipart/form-data`

**Form Data:**
- `file` (file, required): Fichier audio/vidéo
- `title` (string): Titre personnalisé
- `language` (string): Code langue (auto-détection si non spécifié)
- `options` (json): Options de traitement

**Options JSON:**
```json
{
  "enhance_audio": true,
  "detect_speakers": true,
  "generate_summary": true,
  "extract_keywords": true,
  "format_output": "segments",
  "webhook_url": "https://yourapp.com/webhook"
}
```

**Headers:**
```
Authorization: Bearer {jwt_token}
Content-Type: multipart/form-data
```

**Response:**
```json
{
  "success": true,
  "data": {
    "transcription": {
      "id": "tr_789012",
      "title": "Nouveau fichier audio",
      "status": "pending",
      "estimated_duration": 120,
      "queue_position": 3,
      "estimated_completion": "2024-01-20T15:05:00Z"
    },
    "upload": {
      "file_size": 8396800,
      "file_type": "audio/wav",
      "duration": 1205,
      "upload_id": "up_456789"
    }
  }
}
```

## 🔍 Status Codes

### Codes de Réponse HTTP

| Code | Statut | Description |
|------|--------|-------------|
| 200 | OK | Requête réussie |
| 201 | Created | Ressource créée avec succès |
| 400 | Bad Request | Paramètres invalides |
| 401 | Unauthorized | Token manquant ou invalide |
| 403 | Forbidden | Permissions insuffisantes |
| 404 | Not Found | Ressource introuvable |
| 422 | Unprocessable Entity | Erreurs de validation |
| 429 | Too Many Requests | Rate limit dépassé |
| 500 | Internal Server Error | Erreur serveur |
| 503 | Service Unavailable | Service temporairement indisponible |

### Codes d'Erreur Personnalisés

| Code | Description |
|------|-------------|
| `INVALID_CREDENTIALS` | Email/mot de passe incorrect |
| `TOKEN_EXPIRED` | Token JWT expiré |
| `TOKEN_INVALID` | Token JWT invalide |
| `USER_NOT_FOUND` | Utilisateur introuvable |
| `TRANSCRIPTION_NOT_FOUND` | Transcription introuvable |
| `FILE_TOO_LARGE` | Fichier trop volumineux |
| `UNSUPPORTED_FORMAT` | Format de fichier non supporté |
| `PROCESSING_FAILED` | Échec du traitement |
| `QUOTA_EXCEEDED` | Quota dépassé |
| `RATE_LIMIT_EXCEEDED` | Limite de taux dépassée |

## 📏 Rate Limiting

### Limites par Endpoint

| Endpoint | Limite | Fenêtre |
|----------|--------|----------|
| `/auth/login` | 5 tentatives | 15 minutes |
| `/auth/logout` | 10 requêtes | 1 minute |
| `/auth/me` | 60 requêtes | 1 minute |
| `/transcriptions/*` | 100 requêtes | 1 minute |
| Upload de fichiers | 10 uploads | 1 heure |

### Headers de Rate Limiting

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1642694400
X-RateLimit-Retry-After: 60
```

## 🔒 Sécurité

### CORS

**Origines Autorisées:**
- Production: `https://yourdomain.com`
- Développement: `http://localhost:5173`

**Headers CORS:**
```http
Access-Control-Allow-Origin: https://yourdomain.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Max-Age: 86400
```

### Validation des Entrées

**Fichiers Upload:**
- Taille max: 100 MB
- Formats supportés: MP3, WAV, MP4, MOV, AVI, M4A, OGG
- Durée max: 4 heures
- Scan antivirus automatique

**Validation des Données:**
```php
// Exemple de validation
$rules = [
    'email' => 'required|email|max:255',
    'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
    'title' => 'string|max:200',
    'language' => 'string|in:fr,en,es,de,it,pt'
];
```

## 📈 Monitoring et Logs

### Métriques Disponibles

```json
{
  "api_metrics": {
    "requests_per_minute": 150,
    "average_response_time": 245,
    "error_rate": 0.02,
    "uptime_percentage": 99.95
  },
  "transcription_metrics": {
    "queue_length": 5,
    "average_processing_time": 780,
    "success_rate": 0.98,
    "total_processed_today": 234
  }
}
```

### Logs Structure

```json
{
  "timestamp": "2024-01-20T14:30:00Z",
  "level": "INFO",
  "message": "Transcription completed",
  "context": {
    "user_id": 123,
    "transcription_id": "tr_456789",
    "duration": 780,
    "file_size": 15728640,
    "language": "fr"
  },
  "request_id": "req_789012",
  "user_agent": "IntelligentTranscription/1.0",
  "ip_address": "192.168.1.100"
}
```

## 🚀 Performance

### Optimisations

**Cache:**
- Redis pour les sessions
- Cache de réponses API (5 min)
- Cache des métadonnées utilisateur (1h)

**Base de Données:**
- Index sur `user_id`, `status`, `created_at`
- Pagination efficace avec curseurs
- Requêtes optimisées avec EXPLAIN

**Files d'Attente:**
- Traitement asynchrone des transcriptions
- Priorité basée sur le type d'utilisateur
- Retry automatique en cas d'échec

## 🔧 Outils de Développement

### Postman Collection

```bash
# Importer la collection
curl -O https://api.yourdomain.com/postman/collection.json
```

### OpenAPI Specification

```bash
# Télécharger le schéma OpenAPI
curl https://api.yourdomain.com/v2/openapi.yaml
```

### SDK Officiel

```bash
# Installation SDK JavaScript
npm install @intelligent-transcription/api-sdk

# Installation SDK PHP
composer require intelligent-transcription/api-sdk
```

## 📝 Exemples d'Intégration

### JavaScript/TypeScript

```typescript
import { IntelligentTranscriptionAPI } from '@intelligent-transcription/api-sdk';

const api = new IntelligentTranscriptionAPI({
  baseURL: 'https://api.yourdomain.com',
  apiKey: 'your-api-key'
});

// Connexion
const { user, token } = await api.auth.login({
  email: 'user@example.com',
  password: 'password123'
});

// Upload et transcription
const file = new File([audioBlob], 'audio.mp3', { type: 'audio/mp3' });
const transcription = await api.transcriptions.create({
  file,
  title: 'Ma transcription',
  language: 'fr'
});

// Suivre le progrès
api.transcriptions.subscribe(transcription.id, (update) => {
  console.log('Progress:', update.status, update.progress);
});
```

### PHP

```php
use IntelligentTranscription\ApiSDK\Client;
use IntelligentTranscription\ApiSDK\Auth\JWTAuth;

$client = new Client([
    'base_uri' => 'https://api.yourdomain.com',
    'auth' => new JWTAuth('your-jwt-token')
]);

// Lister les transcriptions
$transcriptions = $client->transcriptions()->list([
    'status' => 'completed',
    'limit' => 20
]);

foreach ($transcriptions['data']['transcriptions'] as $transcription) {
    echo $transcription['title'] . "\n";
}
```

### cURL

```bash
# Connexion
curl -X POST https://api.yourdomain.com/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Upload transcription
curl -X POST https://api.yourdomain.com/transcriptions/create \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "file=@audio.mp3" \
  -F "title=Ma transcription" \
  -F "language=fr"
```