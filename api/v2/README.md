# API v2 - Intelligent Transcription

## 🚀 Vue d'ensemble

API REST moderne pour le service de transcription intelligente, construite avec :
- 🏗️ Clean Architecture (Domain → Application → Infrastructure)
- 🔐 Authentification JWT
- 📚 Documentation OpenAPI 3.0
- ⚡ Rate limiting intégré
- 🌐 Support CORS

## 📋 Endpoints principaux

### Authentification
- `POST /api/v2/auth/login` - Connexion
- `POST /api/v2/auth/register` - Inscription
- `POST /api/v2/auth/refresh` - Rafraîchir le token

### Transcriptions
- `GET /api/v2/transcriptions` - Lister les transcriptions
- `POST /api/v2/transcriptions` - Créer une transcription
- `GET /api/v2/transcriptions/{id}` - Obtenir une transcription
- `DELETE /api/v2/transcriptions/{id}` - Supprimer
- `POST /api/v2/transcriptions/{id}/process` - Lancer le traitement
- `GET /api/v2/transcriptions/{id}/download` - Télécharger

### Système
- `GET /api/v2/health` - État de l'API
- `GET /api/v2/docs` - Documentation OpenAPI

## 🔧 Configuration

### Variables d'environnement
```env
JWT_SECRET=your-secret-key-change-this
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://app.example.com
WEBHOOK_SECRET=webhook-secret-key
```

### Rate Limiting
- 60 requêtes par minute
- 1000 requêtes par heure

## 🔒 Authentification

### Obtenir un token
```bash
curl -X POST /api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### Utiliser le token
```bash
curl -X GET /api/v2/transcriptions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📝 Exemples

### Créer une transcription depuis un fichier
```bash
curl -X POST /api/v2/transcriptions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "audio=@audio.mp3" \
  -F "language=fr"
```

### Créer une transcription depuis YouTube
```bash
curl -X POST /api/v2/transcriptions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "youtube_url": "https://www.youtube.com/watch?v=...",
    "language": "fr"
  }'
```

## 🛠️ Architecture

```
api/v2/
├── Controller/          # Controllers API
│   ├── BaseApiController.php
│   ├── TranscriptionApiController.php
│   └── AuthApiController.php
├── Middleware/          # Middlewares
│   ├── AuthMiddleware.php
│   ├── RateLimitMiddleware.php
│   └── CorsMiddleware.php
├── ApiRouter.php        # Routeur principal
├── ApiRequest.php       # Objet Request
├── ApiResponse.php      # Objet Response
├── openapi.yaml        # Documentation OpenAPI
└── index.php           # Point d'entrée
```

## 🧪 Tests

Pour tester l'API :
```bash
# Health check
curl /api/v2/health

# Documentation OpenAPI
curl /api/v2/docs
```

## 📊 Codes de réponse

- `200` - OK
- `201` - Créé
- `204` - Pas de contenu
- `400` - Mauvaise requête
- `401` - Non authentifié
- `403` - Non autorisé
- `404` - Non trouvé
- `409` - Conflit
- `429` - Trop de requêtes
- `500` - Erreur serveur

## 🔄 Prochaines étapes

- [ ] WebSockets pour temps réel (Phase 4 - Tâche 5)
- [ ] GraphQL avec subscriptions (Phase 4 - Tâche 2)
- [ ] Webhooks avancés
- [ ] Versioning d'API (v3)