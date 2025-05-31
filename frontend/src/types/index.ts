// Core domain types
export type { AxiosRequestConfig, AxiosResponse } from 'axios'
export interface Transcription {
  id: string
  status: TranscriptionStatus
  language: Language
  text?: string
  cost?: Cost
  youtube?: YouTubeMetadata
  createdAt: string
  updatedAt: string
  processingProgress?: number
  audioFile: AudioFile
  userId: string
}

export interface AudioFile {
  path: string
  originalName: string
  mimeType: string
  size: number
  duration?: number
  preprocessedPath?: string
}

export interface Language {
  code: string
  name: string
}

export interface Cost {
  amount: number
  currency: string
  formatted: string
}

export interface YouTubeMetadata {
  videoId: string
  title: string
  duration: number
  thumbnail: string
  originalUrl: string
}

export type TranscriptionStatus = 
  | 'pending' 
  | 'processing' 
  | 'completed' 
  | 'failed' 
  | 'cancelled'

// User types
export interface User {
  id: string
  email: string
  name: string
  role: UserRole
  avatar?: string
  createdAt: string
  lastLogin?: string
  preferences: UserPreferences
}

export type UserRole = 'admin' | 'user' | 'premium'

export interface UserPreferences {
  theme: 'light' | 'dark' | 'system'
  language: string
  notifications: {
    email: boolean
    push: boolean
    transcriptionComplete: boolean
    transcriptionFailed: boolean
  }
  defaultTranscriptionLanguage: string
}

// Authentication types
export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  name: string
  email: string
  password: string
  confirmPassword: string
}

// API types
export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
    hasNext: boolean
    hasPrev: boolean
  }
}

// Chat types
export interface Conversation {
  id: string
  title: string
  transcriptionId?: string
  createdAt: string
  updatedAt: string
  messageCount: number
  lastMessage?: Message
}

export interface Message {
  id: string
  conversationId: string
  content: string
  role: 'user' | 'assistant'
  timestamp: string
  metadata?: Record<string, any>
}

// Analytics types
export interface UserStats {
  transcriptions: {
    total: number
    completed: number
    processing: number
    failed: number
  }
  usage: {
    audioHours: number
    totalCost: number
    avgProcessingTime: number
  }
  activity: {
    activeDays: number
    lastActivity: string
  }
}

export interface UsageHistory {
  date: string
  transcriptions: number
  audioHours: number
  cost: number
}

// UI types
export interface UIState {
  sidebarOpen: boolean
  theme: 'light' | 'dark' | 'system'
  notifications: Notification[]
  modals: Modal[]
}

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message: string
  duration?: number
  actions?: NotificationAction[]
  dismissed?: boolean
}

export interface NotificationAction {
  label: string
  action: () => void
}

export interface Modal {
  id: string
  component: string
  props?: Record<string, any>
  persistent?: boolean
}

// GraphQL types
export interface GraphQLError {
  message: string
  locations?: Array<{
    line: number
    column: number
  }>
  path?: Array<string | number>
  extensions?: Record<string, any>
}

export interface GraphQLResponse<T = any> {
  data?: T
  errors?: GraphQLError[]
}

// WebSocket types
export interface TranscriptionUpdate {
  transcriptionId: string
  event: 'started' | 'progress' | 'completed' | 'failed'
  message: string
  timestamp: string
  data?: any
}

export interface TranscriptionProgress {
  transcriptionId: string
  progress: number
  stage: string
  percentage: number
}

// Form types
export interface CreateTranscriptionForm {
  file?: File
  youtubeUrl?: string
  language: string
}

export interface ChatForm {
  message: string
  context?: Record<string, any>
}

// Upload types
export interface UploadProgress {
  loaded: number
  total: number
  percentage: number
}

export interface FileUpload {
  file: File
  progress: UploadProgress
  status: 'pending' | 'uploading' | 'completed' | 'error'
  error?: string
}

// Utility types
export type Optional<T, K extends keyof T> = Pick<Partial<T>, K> & Omit<T, K>
export type RequiredFields<T, K extends keyof T> = T & Required<Pick<T, K>>

// Component props types
export interface ComponentBaseProps {
  class?: string
  id?: string
}

export interface LoadingProps extends ComponentBaseProps {
  size?: 'sm' | 'md' | 'lg'
  variant?: 'spinner' | 'dots' | 'pulse'
}

export interface ButtonProps extends ComponentBaseProps {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
}