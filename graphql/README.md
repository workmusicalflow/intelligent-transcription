# GraphQL API - Intelligent Transcription

## 🔮 Vue d'ensemble

API GraphQL moderne construite avec GraphQLite, offrant :
- 🏗️ Schémas typés automatiques depuis PHP
- 🔄 Subscriptions temps réel
- 📊 Requêtes flexibles et optimisées
- 🔐 Authentification JWT intégrée

## 🚀 Endpoints

### Principal
- `POST /graphql` - Point d'entrée GraphQL

### Introspection
- `POST /graphql` avec query introspection pour explorer le schéma

## 📋 Types principaux

### Transcription
```graphql
type Transcription {
  id: ID!
  status: String!
  language: Language!
  text: String
  cost: Cost
  youtube: YouTubeMetadata
  createdAt: DateTime!
  updatedAt: DateTime!
  processingProgress: Int
}
```

### Language
```graphql
type Language {
  code: String!
  name: String!
}
```

### Cost
```graphql
type Cost {
  amount: Float!
  currency: String!
  formatted: String!
}
```

## 🔍 Queries

### Transcriptions
```graphql
# Obtenir une transcription
query GetTranscription($id: ID!) {
  transcription(id: $id) {
    id
    status
    text
    language {
      code
      name
    }
    cost {
      formatted
    }
  }
}

# Lister les transcriptions
query ListTranscriptions($page: Int, $limit: Int) {
  transcriptions(page: $page, limit: $limit) {
    id
    status
    createdAt
    language {
      name
    }
  }
}

# Statistiques
query TranscriptionStats {
  transcriptionStats {
    total
    completed
    processing
    pending
    completionRate
  }
}
```

## ✏️ Mutations

### Créer une transcription
```graphql
mutation CreateTranscription($language: String!, $youtubeUrl: String) {
  createTranscription(language: $language, youtubeUrl: $youtubeUrl) {
    success
    message
    transcriptionId
  }
}
```

### Lancer le traitement
```graphql
mutation ProcessTranscription($id: String!) {
  processTranscription(id: $id) {
    success
    message
  }
}
```

### Suppression en lot
```graphql
mutation BulkDelete($ids: [String!]!) {
  bulkDeleteTranscriptions(ids: $ids) {
    successCount
    errorCount
    totalCount
  }
}
```

## 🔄 Subscriptions

### Mises à jour de transcription
```graphql
subscription TranscriptionUpdates($transcriptionId: String!) {
  transcriptionUpdated(transcriptionId: $transcriptionId) {
    transcriptionId
    event
    message
    timestamp
  }
}
```

### Progression de traitement
```graphql
subscription TranscriptionProgress($transcriptionId: String!) {
  transcriptionProgress(transcriptionId: $transcriptionId) {
    transcriptionId
    progress
    stage
    percentage
  }
}
```

## 🔐 Authentification

### Avec JWT token
```http
POST /graphql
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "query": "query { transcriptions { id status } }"
}
```

## 📝 Exemples d'utilisation

### JavaScript/Fetch
```javascript
const response = await fetch('/graphql', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    query: `
      query GetTranscriptions {
        transcriptions {
          id
          status
          language { name }
          createdAt
        }
      }
    `
  })
});

const data = await response.json();
```

### Avec variables
```javascript
const query = `
  mutation CreateTranscription($input: CreateTranscriptionInput!) {
    createTranscription(language: $input.language, youtubeUrl: $input.url) {
      success
      transcriptionId
    }
  }
`;

const variables = {
  input: {
    language: 'fr',
    url: 'https://www.youtube.com/watch?v=...'
  }
};

const response = await fetch('/graphql', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({ query, variables })
});
```

## 🛠️ Architecture

```
graphql/
├── index.php              # Point d'entrée
├── .htaccess              # Configuration Apache
└── README.md              # Documentation

src/Infrastructure/GraphQL/
├── GraphQLService.php     # Service principal
├── Type/                  # Types GraphQL
│   ├── TranscriptionType.php
│   └── UserType.php
├── Controller/            # Resolvers
│   ├── TranscriptionQueryController.php
│   └── TranscriptionMutationController.php
└── Subscription/          # Subscriptions
    └── TranscriptionSubscription.php
```

## ⚡ Optimisations

### DataLoader (N+1 Problem)
GraphQLite inclut automatiquement la résolution du problème N+1 via le batching des requêtes.

### Cache
- Cache PSR-16 intégré
- Mise en cache automatique des schémas
- Cache des résultats de requêtes

### Performance
- Schémas compilés en production
- Validation des requêtes
- Limitation de profondeur

## 🧪 Testing

### Introspection
```graphql
query IntrospectionQuery {
  __schema {
    types {
      name
      kind
      fields {
        name
        type {
          name
        }
      }
    }
  }
}
```

### Health Check
```graphql
query {
  __typename
}
```

## 🔄 Temps réel

Les subscriptions utilisent WebSockets avec ReactPHP pour les mises à jour temps réel des transcriptions.

## 🚀 Prochaines étapes

- [ ] DataLoader avancé pour optimisation
- [ ] File upload via multipart
- [ ] Pagination avec cursors
- [ ] Filtering et sorting avancés
- [ ] Rate limiting par requête