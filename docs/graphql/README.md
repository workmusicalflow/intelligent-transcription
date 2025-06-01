# GraphQL API Documentation

## Vue d'ensemble

Documentation de l'API GraphQL d'Intelligent Transcription avec schémas, queries et mutations.

## 🔗 Endpoint

```
Production: https://yourdomain.com/graphql
Développement: http://localhost:8000/graphql
```

## 📊 Schéma GraphQL

### Types de Base

```graphql
# Types scalaires personnalisés
scalar DateTime
scalar Upload
scalar JSON

# Énumérations
enum TranscriptionStatus {
  PENDING
  PROCESSING
  COMPLETED
  FAILED
}

enum UserRole {
  ADMIN
  USER
  GUEST
}

enum Language {
  FR
  EN
  ES
  DE
  IT
  PT
}
```

### Types d'Entités

```graphql
# Utilisateur
type User {
  id: ID!
  name: String!
  email: String!
  role: UserRole!
  avatar: String
  preferences: UserPreferences!
  stats: UserStats!
  createdAt: DateTime!
  updatedAt: DateTime!
}

type UserPreferences {
  language: Language!
  theme: String!
  notifications: NotificationSettings!
  transcription: TranscriptionSettings!
}

type NotificationSettings {
  email: Boolean!
  push: Boolean!
  transcriptionComplete: Boolean!
  weeklySummary: Boolean!
}

type TranscriptionSettings {
  autoDetectLanguage: Boolean!
  defaultLanguage: Language!
  enhanceAudio: Boolean!
  detectSpeakers: Boolean!
}

type UserStats {
  transcriptionsCount: Int!
  totalDuration: Int!
  totalWords: Int!
  lastActivity: DateTime!
  plan: UserPlan!
}

type UserPlan {
  name: String!
  quotaUsed: Int!
  quotaLimit: Int!
  resetDate: DateTime!
}
```

### Transcriptions

```graphql
# Transcription
type Transcription {
  id: ID!
  title: String!
  status: TranscriptionStatus!
  language: Language!
  content: TranscriptionContent
  metadata: TranscriptionMetadata!
  processing: ProcessingInfo
  analytics: TranscriptionAnalytics
  user: User!
  createdAt: DateTime!
  updatedAt: DateTime!
  completedAt: DateTime
}

type TranscriptionContent {
  text: String!
  segments: [TranscriptionSegment!]!
  summary: String
  keywords: [String!]
  chapters: [Chapter!]
}

type TranscriptionSegment {
  id: Int!
  start: Float!
  end: Float!
  text: String!
  confidence: Float!
  speaker: String
  words: [Word!]
}

type Word {
  word: String!
  start: Float!
  end: Float!
  confidence: Float!
}

type Chapter {
  id: Int!
  title: String!
  start: Float!
  end: Float!
  summary: String!
}

type TranscriptionMetadata {
  duration: Int!
  fileSize: Int!
  fileType: String!
  fileName: String!
  sampleRate: Int
  channels: Int
  bitrate: Int
}

type ProcessingInfo {
  startedAt: DateTime!
  completedAt: DateTime
  durationSeconds: Int
  modelUsed: String!
  languageDetection: LanguageDetection
  progress: Int
  currentStep: String
  estimatedCompletion: DateTime
}

type LanguageDetection {
  detected: Language!
  confidence: Float!
  alternatives: [LanguageAlternative!]!
}

type LanguageAlternative {
  language: Language!
  confidence: Float!
}

type TranscriptionAnalytics {
  wordCount: Int!
  sentenceCount: Int!
  speakingRate: Float!
  pauseCount: Int!
  averageConfidence: Float!
  languageDistribution: JSON
  sentiment: SentimentAnalysis
}

type SentimentAnalysis {
  positive: Float!
  negative: Float!
  neutral: Float!
}
```

### Chat et Conversations

```graphql
# Conversation
type Conversation {
  id: ID!
  title: String!
  transcription: Transcription
  messages: [Message!]!
  context: ConversationContext
  user: User!
  createdAt: DateTime!
  updatedAt: DateTime!
}

type Message {
  id: ID!
  role: MessageRole!
  content: String!
  metadata: MessageMetadata
  conversation: Conversation!
  timestamp: DateTime!
}

enum MessageRole {
  USER
  ASSISTANT
  SYSTEM
}

type MessageMetadata {
  modelUsed: String
  tokensUsed: Int
  responseTime: Int
  confidence: Float
  sources: [String!]
}

type ConversationContext {
  transcriptionContent: String
  userPreferences: JSON
  conversationSummary: String
  relevantSegments: [TranscriptionSegment!]
}
```

## 📝 Queries

### Utilisateurs

```graphql
# Obtenir l'utilisateur connecté
query Me {
  me {
    id
    name
    email
    role
    preferences {
      language
      theme
      notifications {
        email
        push
        transcriptionComplete
      }
      transcription {
        autoDetectLanguage
        defaultLanguage
        enhanceAudio
      }
    }
    stats {
      transcriptionsCount
      totalDuration
      plan {
        name
        quotaUsed
        quotaLimit
        resetDate
      }
    }
  }
}

# Obtenir un utilisateur par ID (admin uniquement)
query GetUser($id: ID!) {
  user(id: $id) {
    id
    name
    email
    role
    createdAt
    stats {
      transcriptionsCount
      totalDuration
    }
  }
}
```

### Transcriptions

```graphql
# Liste des transcriptions avec pagination et filtres
query GetTranscriptions(
  $page: Int = 1
  $limit: Int = 10
  $status: TranscriptionStatus
  $search: String
  $language: Language
  $dateFrom: DateTime
  $dateTo: DateTime
  $sortBy: String = "createdAt"
  $sortOrder: String = "DESC"
) {
  transcriptions(
    page: $page
    limit: $limit
    filters: {
      status: $status
      search: $search
      language: $language
      dateFrom: $dateFrom
      dateTo: $dateTo
    }
    sort: {
      field: $sortBy
      order: $sortOrder
    }
  ) {
    data {
      id
      title
      status
      language
      metadata {
        duration
        fileSize
        fileType
      }
      analytics {
        wordCount
        averageConfidence
      }
      createdAt
      completedAt
    }
    pagination {
      page
      limit
      totalPages
      totalItems
      hasNext
      hasPrev
    }
    filters {
      applied {
        status
        language
      }
      available {
        statuses
        languages
      }
    }
  }
}

# Détails complets d'une transcription
query GetTranscription($id: ID!) {
  transcription(id: $id) {
    id
    title
    status
    language
    content {
      text
      segments {
        id
        start
        end
        text
        confidence
        speaker
        words {
          word
          start
          end
          confidence
        }
      }
      summary
      keywords
      chapters {
        id
        title
        start
        end
        summary
      }
    }
    metadata {
      duration
      fileSize
      fileType
      fileName
      sampleRate
      channels
    }
    processing {
      startedAt
      completedAt
      durationSeconds
      modelUsed
      languageDetection {
        detected
        confidence
        alternatives {
          language
          confidence
        }
      }
      progress
      currentStep
    }
    analytics {
      wordCount
      sentenceCount
      speakingRate
      pauseCount
      averageConfidence
      sentiment {
        positive
        negative
        neutral
      }
    }
    createdAt
    updatedAt
  }
}

# Statistiques des transcriptions
query GetTranscriptionStats(
  $dateFrom: DateTime
  $dateTo: DateTime
  $groupBy: String = "day"
) {
  transcriptionStats(
    dateFrom: $dateFrom
    dateTo: $dateTo
    groupBy: $groupBy
  ) {
    totalTranscriptions
    totalDuration
    totalWords
    averageConfidence
    successRate
    languageDistribution {
      language
      count
      percentage
    }
    dailyStats {
      date
      count
      duration
      successRate
    }
  }
}
```

### Conversations

```graphql
# Liste des conversations
query GetConversations($limit: Int = 20) {
  conversations(limit: $limit) {
    id
    title
    transcription {
      id
      title
    }
    messages {
      id
      role
      content
      timestamp
    }
    createdAt
    updatedAt
  }
}

# Détails d'une conversation
query GetConversation($id: ID!) {
  conversation(id: $id) {
    id
    title
    transcription {
      id
      title
      content {
        text
        summary
      }
    }
    messages {
      id
      role
      content
      metadata {
        modelUsed
        tokensUsed
        responseTime
        confidence
      }
      timestamp
    }
    context {
      transcriptionContent
      conversationSummary
      relevantSegments {
        id
        start
        end
        text
      }
    }
    createdAt
    updatedAt
  }
}
```

## 🔄 Mutations

### Authentification

```graphql
# Connexion
mutation Login($email: String!, $password: String!) {
  login(input: { email: $email, password: $password }) {
    user {
      id
      name
      email
      role
    }
    token
    expiresAt
  }
}

# Inscription
mutation Register($input: RegisterInput!) {
  register(input: $input) {
    user {
      id
      name
      email
    }
    token
    expiresAt
  }
}

input RegisterInput {
  name: String!
  email: String!
  password: String!
  confirmPassword: String!
  acceptTerms: Boolean!
  newsletter: Boolean = false
}

# Mise à jour du profil
mutation UpdateProfile($input: UpdateProfileInput!) {
  updateProfile(input: $input) {
    user {
      id
      name
      email
      preferences {
        language
        theme
        notifications {
          email
          push
        }
      }
    }
  }
}

input UpdateProfileInput {
  name: String
  email: String
  currentPassword: String
  newPassword: String
  preferences: UserPreferencesInput
}

input UserPreferencesInput {
  language: Language
  theme: String
  notifications: NotificationSettingsInput
  transcription: TranscriptionSettingsInput
}
```

### Transcriptions

```graphql
# Créer une transcription
mutation CreateTranscription($input: CreateTranscriptionInput!) {
  createTranscription(input: $input) {
    transcription {
      id
      title
      status
      estimatedDuration
      queuePosition
      estimatedCompletion
    }
    upload {
      fileSize
      fileType
      duration
      uploadId
    }
  }
}

input CreateTranscriptionInput {
  file: Upload!
  title: String
  language: Language
  options: TranscriptionOptionsInput
}

input TranscriptionOptionsInput {
  enhanceAudio: Boolean = false
  detectSpeakers: Boolean = false
  generateSummary: Boolean = false
  extractKeywords: Boolean = false
  formatOutput: String = "segments"
  webhookUrl: String
}

# Mettre à jour une transcription
mutation UpdateTranscription($id: ID!, $input: UpdateTranscriptionInput!) {
  updateTranscription(id: $id, input: $input) {
    transcription {
      id
      title
      language
      updatedAt
    }
  }
}

input UpdateTranscriptionInput {
  title: String
  language: Language
}

# Supprimer une transcription
mutation DeleteTranscription($id: ID!) {
  deleteTranscription(id: $id) {
    success
    message
  }
}

# Regénérer une transcription
mutation RegenerateTranscription($id: ID!, $options: TranscriptionOptionsInput) {
  regenerateTranscription(id: $id, options: $options) {
    transcription {
      id
      status
      estimatedCompletion
    }
  }
}
```

### Conversations

```graphql
# Créer une conversation
mutation CreateConversation($input: CreateConversationInput!) {
  createConversation(input: $input) {
    conversation {
      id
      title
      transcription {
        id
        title
      }
      createdAt
    }
  }
}

input CreateConversationInput {
  title: String
  transcriptionId: ID
}

# Envoyer un message
mutation SendMessage($conversationId: ID!, $content: String!) {
  sendMessage(conversationId: $conversationId, content: $content) {
    message {
      id
      role
      content
      timestamp
    }
    response {
      id
      role
      content
      metadata {
        modelUsed
        tokensUsed
        responseTime
      }
      timestamp
    }
  }
}

# Supprimer une conversation
mutation DeleteConversation($id: ID!) {
  deleteConversation(id: $id) {
    success
    message
  }
}
```

## 📡 Subscriptions

### Temps Réel

```graphql
# S'abonner aux mises à jour de transcription
subscription TranscriptionUpdates($transcriptionId: ID!) {
  transcriptionUpdated(transcriptionId: $transcriptionId) {
    id
    status
    processing {
      progress
      currentStep
      estimatedCompletion
    }
    content {
      text
    }
  }
}

# S'abonner aux nouveaux messages de conversation
subscription ConversationMessages($conversationId: ID!) {
  messageAdded(conversationId: $conversationId) {
    id
    role
    content
    metadata {
      modelUsed
      responseTime
    }
    timestamp
  }
}

# S'abonner aux notifications utilisateur
subscription UserNotifications {
  notificationAdded {
    id
    type
    title
    message
    data
    createdAt
  }
}
```

## 🔧 Configuration Client

### Apollo Client Setup

```typescript
// src/api/apollo.ts
import { ApolloClient, InMemoryCache, createHttpLink, split } from '@apollo/client/core';
import { getMainDefinition } from '@apollo/client/utilities';
import { GraphQLWsLink } from '@apollo/client/link/subscriptions';
import { createClient } from 'graphql-ws';
import { setContext } from '@apollo/client/link/context';
import { useAuthStore } from '@/stores/auth';

// HTTP Link
const httpLink = createHttpLink({
  uri: import.meta.env.VITE_GRAPHQL_URL || 'http://localhost:8000/graphql'
});

// WebSocket Link pour les subscriptions
const wsLink = new GraphQLWsLink(
  createClient({
    url: import.meta.env.VITE_GRAPHQL_WS_URL || 'ws://localhost:8000/graphql',
    connectionParams: () => {
      const authStore = useAuthStore();
      return {
        Authorization: authStore.token ? `Bearer ${authStore.token}` : ''
      };
    }
  })
);

// Auth Link
const authLink = setContext((_, { headers }) => {
  const authStore = useAuthStore();
  return {
    headers: {
      ...headers,
      Authorization: authStore.token ? `Bearer ${authStore.token}` : ''
    }
  };
});

// Split Link pour gérer HTTP et WebSocket
const splitLink = split(
  ({ query }) => {
    const definition = getMainDefinition(query);
    return (
      definition.kind === 'OperationDefinition' &&
      definition.operation === 'subscription'
    );
  },
  wsLink,
  authLink.concat(httpLink)
);

// Apollo Client
export const apolloClient = new ApolloClient({
  link: splitLink,
  cache: new InMemoryCache({
    typePolicies: {
      Query: {
        fields: {
          transcriptions: {
            keyArgs: ['filters', 'sort'],
            merge(existing = { data: [] }, incoming) {
              return {
                ...incoming,
                data: [...existing.data, ...incoming.data]
              };
            }
          }
        }
      }
    }
  }),
  defaultOptions: {
    watchQuery: {
      errorPolicy: 'all'
    },
    query: {
      errorPolicy: 'all'
    }
  }
});
```

### Composables GraphQL

```typescript
// src/composables/useGraphQL.ts
import { useQuery, useMutation, useSubscription } from '@vue/apollo-composable';
import { computed } from 'vue';
import type { DocumentNode } from 'graphql';

export function useGraphQLQuery<TResult = any, TVariables = any>(
  query: DocumentNode,
  variables?: TVariables,
  options?: any
) {
  const { result, loading, error, refetch } = useQuery(query, variables, options);

  const data = computed(() => result.value?.data || null);
  const hasError = computed(() => !!error.value);
  const isEmpty = computed(() => !loading.value && !data.value);

  return {
    data,
    loading,
    error,
    hasError,
    isEmpty,
    refetch
  };
}

export function useGraphQLMutation<TResult = any, TVariables = any>(
  mutation: DocumentNode,
  options?: any
) {
  const { mutate, loading, error } = useMutation(mutation, options);

  const execute = async (variables?: TVariables) => {
    try {
      const result = await mutate(variables);
      return result?.data || null;
    } catch (err) {
      throw err;
    }
  };

  return {
    execute,
    loading,
    error
  };
}
```

## 📋 Exemples d'Utilisation

### Vue Component avec GraphQL

```vue
<template>
  <div>
    <div v-if="loading" class="text-center">
      <LoadingSpinner />
    </div>
    
    <div v-else-if="error" class="text-red-600">
      Erreur: {{ error.message }}
    </div>
    
    <div v-else-if="transcriptions">
      <TranscriptionCard
        v-for="transcription in transcriptions.data"
        :key="transcription.id"
        :transcription="transcription"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useGraphQLQuery } from '@/composables/useGraphQL';
import { GET_TRANSCRIPTIONS } from '@/graphql/queries';

const { data: transcriptions, loading, error } = useGraphQLQuery(
  GET_TRANSCRIPTIONS,
  {
    page: 1,
    limit: 10,
    status: 'COMPLETED'
  }
);
</script>
```

### Mutation avec Error Handling

```typescript
// src/composables/useTranscriptionMutations.ts
import { useGraphQLMutation } from '@/composables/useGraphQL';
import { CREATE_TRANSCRIPTION, UPDATE_TRANSCRIPTION } from '@/graphql/mutations';
import { useUIStore } from '@/stores/ui';

export function useTranscriptionMutations() {
  const uiStore = useUIStore();

  const { execute: createTranscription, loading: creating } = useGraphQLMutation(
    CREATE_TRANSCRIPTION,
    {
      onCompleted: (data) => {
        uiStore.showNotification({
          type: 'success',
          title: 'Transcription créée',
          message: 'Votre transcription est en cours de traitement'
        });
      },
      onError: (error) => {
        uiStore.showNotification({
          type: 'error',
          title: 'Erreur',
          message: error.message
        });
      }
    }
  );

  return {
    createTranscription,
    creating
  };
}
```

## 📊 Schema Introspection

```bash
# Télécharger le schéma GraphQL
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query": "{ __schema { types { name } } }"}' \
  http://localhost:8000/graphql

# Générer les types TypeScript
npm run graphql:codegen
```

## 🔍 Playground GraphQL

L'interface GraphQL Playground est disponible en développement :

```
http://localhost:8000/graphql-playground
```

Fonctionnalités :
- Explorateur de schéma interactif
- Éditeur de requêtes avec autocomplétion
- Documentation automatique
- Historique des requêtes
- Variables et headers personnalisés