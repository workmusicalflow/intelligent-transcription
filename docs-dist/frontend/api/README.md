# Frontend API Documentation

## Vue d'ensemble

Documentation des types TypeScript et clients API du frontend Intelligent Transcription.

## 💻 Types TypeScript

### Types de Base

```typescript
// src/types/index.ts

// Identifiants
type UserId = string;
type TranscriptionId = string;
type ConversationId = string;

// États
type TranscriptionStatus = 'pending' | 'processing' | 'completed' | 'failed';
type UserRole = 'admin' | 'user' | 'guest';
type Theme = 'light' | 'dark' | 'system';

// Dates
type Timestamp = string; // ISO 8601
```

### Utilisateur

```typescript
interface User {
  id: UserId;
  name: string;
  email: string;
  role: UserRole;
  avatar?: string;
  preferences: UserPreferences;
  stats: UserStats;
  created_at: Timestamp;
  updated_at: Timestamp;
}

interface UserPreferences {
  language: string;
  theme: Theme;
  notifications: {
    email: boolean;
    push: boolean;
    transcription_complete: boolean;
    weekly_summary: boolean;
  };
  transcription: {
    auto_detect_language: boolean;
    default_language: string;
    enhance_audio: boolean;
    detect_speakers: boolean;
  };
}

interface UserStats {
  transcriptions_count: number;
  total_duration: number; // secondes
  total_words: number;
  last_activity: Timestamp;
  plan: {
    name: string;
    quota_used: number;
    quota_limit: number;
    reset_date: Timestamp;
  };
}
```

### Transcription

```typescript
interface Transcription {
  id: TranscriptionId;
  title: string;
  status: TranscriptionStatus;
  language: string;
  content?: TranscriptionContent;
  metadata: TranscriptionMetadata;
  processing?: ProcessingInfo;
  analytics?: TranscriptionAnalytics;
  created_at: Timestamp;
  updated_at: Timestamp;
  completed_at?: Timestamp;
}

interface TranscriptionContent {
  text: string;
  segments: TranscriptionSegment[];
  summary?: string;
  keywords?: string[];
  chapters?: Chapter[];
}

interface TranscriptionSegment {
  id: number;
  start: number; // secondes
  end: number;   // secondes
  text: string;
  confidence: number; // 0-1
  speaker?: string;
  words?: Word[];
}

interface Word {
  word: string;
  start: number;
  end: number;
  confidence: number;
}

interface Chapter {
  id: number;
  title: string;
  start: number;
  end: number;
  summary: string;
}

interface TranscriptionMetadata {
  duration: number; // secondes
  file_size: number; // bytes
  file_type: string;
  file_name: string;
  sample_rate?: number;
  channels?: number;
  bitrate?: number;
}

interface ProcessingInfo {
  started_at: Timestamp;
  completed_at?: Timestamp;
  duration_seconds?: number;
  model_used: string;
  language_detection?: {
    detected: string;
    confidence: number;
    alternatives: Array<{
      language: string;
      confidence: number;
    }>;
  };
  progress?: number; // 0-100
  current_step?: string;
  estimated_completion?: Timestamp;
}

interface TranscriptionAnalytics {
  word_count: number;
  sentence_count: number;
  speaking_rate: number; // mots par minute
  pause_count: number;
  average_confidence: number;
  language_distribution?: Record<string, number>;
  sentiment?: {
    positive: number;
    negative: number;
    neutral: number;
  };
}
```

### Chat et Conversations

```typescript
interface Conversation {
  id: ConversationId;
  title: string;
  transcription_id?: TranscriptionId;
  messages: Message[];
  context?: ConversationContext;
  created_at: Timestamp;
  updated_at: Timestamp;
}

interface Message {
  id: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  metadata?: MessageMetadata;
  timestamp: Timestamp;
}

interface MessageMetadata {
  model_used?: string;
  tokens_used?: number;
  response_time?: number;
  confidence?: number;
  sources?: string[];
}

interface ConversationContext {
  transcription_content?: string;
  user_preferences?: Record<string, any>;
  conversation_summary?: string;
  relevant_segments?: TranscriptionSegment[];
}
```

### Forms et Validation

```typescript
// Formulaires
interface LoginForm {
  email: string;
  password: string;
  remember_me?: boolean;
}

interface RegisterForm {
  name: string;
  email: string;
  password: string;
  confirm_password: string;
  accept_terms: boolean;
  newsletter?: boolean;
}

interface ProfileForm {
  name: string;
  email: string;
  current_password?: string;
  new_password?: string;
  confirm_password?: string;
  preferences: Partial<UserPreferences>;
}

interface TranscriptionCreateForm {
  file: File;
  title?: string;
  language?: string;
  options?: TranscriptionOptions;
}

interface TranscriptionOptions {
  enhance_audio?: boolean;
  detect_speakers?: boolean;
  generate_summary?: boolean;
  extract_keywords?: boolean;
  format_output?: 'text' | 'segments' | 'chapters';
  webhook_url?: string;
}

// Validation
interface ValidationError {
  field: string;
  message: string;
  code?: string;
}

interface FormErrors {
  [key: string]: string | undefined;
}

type FormState<T> = {
  data: T;
  errors: FormErrors;
  loading: boolean;
  submitted: boolean;
};
```

### API Responses

```typescript
// Réponses API génériques
interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: ApiError;
  meta?: ResponseMeta;
}

interface ApiError {
  code: string;
  message: string;
  details?: Record<string, any>;
  validation_errors?: ValidationError[];
}

interface ResponseMeta {
  pagination?: {
    page: number;
    limit: number;
    total_pages: number;
    total_items: number;
    has_next: boolean;
    has_prev: boolean;
  };
  filters?: {
    applied: Record<string, any>;
    available: Record<string, any[]>;
  };
  timing?: {
    request_time: number;
    processing_time: number;
  };
}

// Réponses spécifiques
type LoginResponse = ApiResponse<{
  user: User;
  token: string;
  expires_at: Timestamp;
}>;

type TranscriptionListResponse = ApiResponse<{
  transcriptions: Transcription[];
}>;

type TranscriptionDetailResponse = ApiResponse<{
  transcription: Transcription;
}>;
```

## 🌐 Clients API

### Configuration de Base

```typescript
// src/api/client.ts
import axios, { AxiosInstance, AxiosResponse } from 'axios';
import { useAuthStore } from '@/stores/auth';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    this.setupInterceptors();
  }

  private setupInterceptors(): void {
    // Request interceptor - Ajouter le token
    this.client.interceptors.request.use(
      (config) => {
        const authStore = useAuthStore();
        if (authStore.token) {
          config.headers.Authorization = `Bearer ${authStore.token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor - Gérer les erreurs
    this.client.interceptors.response.use(
      (response: AxiosResponse) => response,
      async (error) => {
        if (error.response?.status === 401) {
          const authStore = useAuthStore();
          await authStore.logout();
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  async get<T>(url: string, params?: Record<string, any>): Promise<ApiResponse<T>> {
    const response = await this.client.get(url, { params });
    return response.data;
  }

  async post<T>(url: string, data?: any): Promise<ApiResponse<T>> {
    const response = await this.client.post(url, data);
    return response.data;
  }

  async put<T>(url: string, data?: any): Promise<ApiResponse<T>> {
    const response = await this.client.put(url, data);
    return response.data;
  }

  async delete<T>(url: string): Promise<ApiResponse<T>> {
    const response = await this.client.delete(url);
    return response.data;
  }

  async upload<T>(url: string, formData: FormData, onProgress?: (progress: number) => void): Promise<ApiResponse<T>> {
    const response = await this.client.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          onProgress(progress);
        }
      }
    });
    return response.data;
  }
}

export const apiClient = new ApiClient();
```

### Client d'Authentification

```typescript
// src/api/auth.ts
import { apiClient } from './client';
import type { LoginForm, RegisterForm, LoginResponse, User } from '@/types';

export class AuthAPI {
  async login(credentials: LoginForm): Promise<LoginResponse> {
    return apiClient.post<LoginResponse>('/api/auth/login', credentials);
  }

  async register(userData: RegisterForm): Promise<LoginResponse> {
    return apiClient.post<LoginResponse>('/api/auth/register', userData);
  }

  async logout(): Promise<ApiResponse> {
    return apiClient.post('/api/auth/logout');
  }

  async me(): Promise<ApiResponse<{ user: User }>> {
    return apiClient.get<{ user: User }>('/api/auth/me');
  }

  async refreshToken(): Promise<LoginResponse> {
    return apiClient.post<LoginResponse>('/api/auth/refresh');
  }

  async forgotPassword(email: string): Promise<ApiResponse> {
    return apiClient.post('/api/auth/forgot-password', { email });
  }

  async resetPassword(token: string, password: string): Promise<ApiResponse> {
    return apiClient.post('/api/auth/reset-password', { token, password });
  }

  async updateProfile(userData: Partial<User>): Promise<ApiResponse<{ user: User }>> {
    return apiClient.put<{ user: User }>('/api/auth/profile', userData);
  }

  async changePassword(currentPassword: string, newPassword: string): Promise<ApiResponse> {
    return apiClient.put('/api/auth/change-password', {
      current_password: currentPassword,
      new_password: newPassword
    });
  }
}

export const authApi = new AuthAPI();
```

### Client de Transcriptions

```typescript
// src/api/transcriptions.ts
import { apiClient } from './client';
import type { 
  Transcription, 
  TranscriptionCreateForm, 
  TranscriptionListResponse,
  TranscriptionDetailResponse 
} from '@/types';

export interface TranscriptionFilters {
  page?: number;
  limit?: number;
  status?: TranscriptionStatus;
  search?: string;
  language?: string;
  date_from?: string;
  date_to?: string;
  sort?: 'created_at' | 'title' | 'duration';
  order?: 'asc' | 'desc';
}

export class TranscriptionAPI {
  async list(filters: TranscriptionFilters = {}): Promise<TranscriptionListResponse> {
    return apiClient.get<TranscriptionListResponse>('/api/transcriptions/list', filters);
  }

  async get(id: TranscriptionId): Promise<TranscriptionDetailResponse> {
    return apiClient.get<TranscriptionDetailResponse>('/api/transcriptions/detail', { id });
  }

  async create(
    data: TranscriptionCreateForm, 
    onProgress?: (progress: number) => void
  ): Promise<ApiResponse<{ transcription: Transcription }>> {
    const formData = new FormData();
    formData.append('file', data.file);
    
    if (data.title) formData.append('title', data.title);
    if (data.language) formData.append('language', data.language);
    if (data.options) formData.append('options', JSON.stringify(data.options));

    return apiClient.upload<{ transcription: Transcription }>(
      '/api/transcriptions/create',
      formData,
      onProgress
    );
  }

  async update(id: TranscriptionId, data: Partial<Transcription>): Promise<ApiResponse<{ transcription: Transcription }>> {
    return apiClient.put<{ transcription: Transcription }>(`/api/transcriptions/${id}`, data);
  }

  async delete(id: TranscriptionId): Promise<ApiResponse> {
    return apiClient.delete(`/api/transcriptions/${id}`);
  }

  async download(id: TranscriptionId, format: 'txt' | 'json' | 'srt' | 'vtt' = 'txt'): Promise<Blob> {
    const response = await apiClient.client.get(`/api/transcriptions/${id}/download`, {
      params: { format },
      responseType: 'blob'
    });
    return response.data;
  }

  async regenerate(id: TranscriptionId, options?: TranscriptionOptions): Promise<ApiResponse<{ transcription: Transcription }>> {
    return apiClient.post<{ transcription: Transcription }>(`/api/transcriptions/${id}/regenerate`, { options });
  }

  async share(id: TranscriptionId, options: { public: boolean; expires_at?: Timestamp }): Promise<ApiResponse<{ share_url: string }>> {
    return apiClient.post<{ share_url: string }>(`/api/transcriptions/${id}/share`, options);
  }
}

export const transcriptionApi = new TranscriptionAPI();
```

### Client de Chat

```typescript
// src/api/chat.ts
import { apiClient } from './client';
import type { Conversation, Message, ConversationId } from '@/types';

export class ChatAPI {
  async getConversations(): Promise<ApiResponse<{ conversations: Conversation[] }>> {
    return apiClient.get<{ conversations: Conversation[] }>('/api/chat/conversations');
  }

  async getConversation(id: ConversationId): Promise<ApiResponse<{ conversation: Conversation }>> {
    return apiClient.get<{ conversation: Conversation }>(`/api/chat/conversations/${id}`);
  }

  async createConversation(data: {
    title?: string;
    transcription_id?: TranscriptionId;
  }): Promise<ApiResponse<{ conversation: Conversation }>> {
    return apiClient.post<{ conversation: Conversation }>('/api/chat/conversations', data);
  }

  async sendMessage(
    conversationId: ConversationId,
    content: string
  ): Promise<ApiResponse<{ message: Message; response: Message }>> {
    return apiClient.post<{ message: Message; response: Message }>(
      `/api/chat/conversations/${conversationId}/messages`,
      { content }
    );
  }

  async deleteConversation(id: ConversationId): Promise<ApiResponse> {
    return apiClient.delete(`/api/chat/conversations/${id}`);
  }

  async exportConversation(id: ConversationId, format: 'txt' | 'json' | 'pdf' = 'txt'): Promise<Blob> {
    const response = await apiClient.client.get(`/api/chat/conversations/${id}/export`, {
      params: { format },
      responseType: 'blob'
    });
    return response.data;
  }
}

export const chatApi = new ChatAPI();
```

## 🔌 WebSocket et Temps Réel

### WebSocket Client

```typescript
// src/api/websocket.ts
import { useAuthStore } from '@/stores/auth';

export type WebSocketEventType = 
  | 'transcription:progress'
  | 'transcription:completed'
  | 'transcription:failed'
  | 'chat:message'
  | 'user:notification';

export interface WebSocketEvent<T = any> {
  type: WebSocketEventType;
  data: T;
  timestamp: string;
}

export class WebSocketClient {
  private ws: WebSocket | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectInterval = 1000;
  private listeners: Map<WebSocketEventType, Set<(data: any) => void>> = new Map();

  connect(): void {
    const authStore = useAuthStore();
    if (!authStore.token) return;

    const wsUrl = `${import.meta.env.VITE_WS_URL || 'ws://localhost:8000/ws'}?token=${authStore.token}`;
    
    this.ws = new WebSocket(wsUrl);

    this.ws.onopen = () => {
      console.log('WebSocket connected');
      this.reconnectAttempts = 0;
    };

    this.ws.onmessage = (event) => {
      try {
        const wsEvent: WebSocketEvent = JSON.parse(event.data);
        this.handleEvent(wsEvent);
      } catch (error) {
        console.error('Failed to parse WebSocket message:', error);
      }
    };

    this.ws.onclose = () => {
      console.log('WebSocket disconnected');
      this.attemptReconnect();
    };

    this.ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };
  }

  disconnect(): void {
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
  }

  subscribe<T>(eventType: WebSocketEventType, callback: (data: T) => void): () => void {
    if (!this.listeners.has(eventType)) {
      this.listeners.set(eventType, new Set());
    }
    
    this.listeners.get(eventType)!.add(callback);

    // Retourner une fonction de désabonnement
    return () => {
      this.listeners.get(eventType)?.delete(callback);
    };
  }

  private handleEvent(event: WebSocketEvent): void {
    const callbacks = this.listeners.get(event.type);
    if (callbacks) {
      callbacks.forEach(callback => callback(event.data));
    }
  }

  private attemptReconnect(): void {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      setTimeout(() => {
        console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        this.connect();
      }, this.reconnectInterval * this.reconnectAttempts);
    }
  }
}

export const wsClient = new WebSocketClient();
```

## 🛠️ Utilitaires API

### Gestion d'Erreurs

```typescript
// src/api/errors.ts
import type { ApiError } from '@/types';

export class APIError extends Error {
  public code: string;
  public details?: Record<string, any>;
  public status?: number;

  constructor(error: ApiError, status?: number) {
    super(error.message);
    this.name = 'APIError';
    this.code = error.code;
    this.details = error.details;
    this.status = status;
  }

  static fromResponse(response: any): APIError {
    return new APIError(
      response.data?.error || { code: 'UNKNOWN_ERROR', message: 'Une erreur inconnue s\'est produite' },
      response.status
    );
  }
}

export function handleApiError(error: any): APIError {
  if (error.response) {
    return APIError.fromResponse(error.response);
  } else if (error.request) {
    return new APIError({
      code: 'NETWORK_ERROR',
      message: 'Erreur de connexion au serveur'
    });
  } else {
    return new APIError({
      code: 'CLIENT_ERROR',
      message: error.message || 'Erreur client'
    });
  }
}
```

### Cache et Performance

```typescript
// src/api/cache.ts
export class APICache {
  private cache = new Map<string, { data: any; timestamp: number; ttl: number }>();

  set(key: string, data: any, ttl: number = 5 * 60 * 1000): void { // 5 min par défaut
    this.cache.set(key, {
      data,
      timestamp: Date.now(),
      ttl
    });
  }

  get(key: string): any | null {
    const item = this.cache.get(key);
    if (!item) return null;

    if (Date.now() - item.timestamp > item.ttl) {
      this.cache.delete(key);
      return null;
    }

    return item.data;
  }

  clear(): void {
    this.cache.clear();
  }

  generateKey(url: string, params?: Record<string, any>): string {
    const searchParams = new URLSearchParams(params).toString();
    return `${url}${searchParams ? '?' + searchParams : ''}`;
  }
}

export const apiCache = new APICache();
```

## 📈 Monitoring Frontend

### Métriques API

```typescript
// src/api/metrics.ts
export class APIMetrics {
  private metrics = {
    requests: 0,
    errors: 0,
    averageResponseTime: 0,
    responseTimeSum: 0
  };

  recordRequest(responseTime: number, isError: boolean = false): void {
    this.metrics.requests++;
    this.metrics.responseTimeSum += responseTime;
    this.metrics.averageResponseTime = this.metrics.responseTimeSum / this.metrics.requests;
    
    if (isError) {
      this.metrics.errors++;
    }
  }

  getMetrics() {
    return {
      ...this.metrics,
      errorRate: this.metrics.requests > 0 ? this.metrics.errors / this.metrics.requests : 0
    };
  }

  reset(): void {
    this.metrics = {
      requests: 0,
      errors: 0,
      averageResponseTime: 0,
      responseTimeSum: 0
    };
  }
}

export const apiMetrics = new APIMetrics();
```