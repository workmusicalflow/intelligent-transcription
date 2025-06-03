import { apiClient } from './client'
import type { 
  ApiResponse, 
  PaginatedResponse 
} from '@/types'

/**
 * Interface pour les données de translation
 */
export interface Translation {
  id: string
  transcription_id: string
  target_language: string
  source_language?: string
  provider_used: string
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  quality_score?: number
  processing_time?: number
  estimated_cost: number
  actual_cost?: number
  segments_count?: number
  total_duration?: number
  created_at: string
  completed_at?: string
  
  // Métadonnées avancées
  has_word_timestamps: boolean
  has_emotional_context: boolean
  has_character_names: boolean
  has_technical_terms: boolean
  
  // Métriques qualité
  timestamp_preservation_score?: number
  length_adaptation_ratio?: number
  emotional_preservation_score?: number
}

/**
 * Interface pour création d'une traduction
 */
export interface CreateTranslationRequest {
  transcription_id: string
  target_language: string
  provider?: 'gpt-4o-mini' | 'whisper-1' | 'hybrid'
  config?: TranslationConfig
}

/**
 * Configuration de traduction
 */
export interface TranslationConfig {
  optimize_for_dubbing?: boolean
  preserve_emotions?: boolean
  use_character_names?: boolean
  technical_terms_handling?: boolean
  style_adaptation?: 'formal' | 'casual' | 'cinematic' | 'educational'
  length_optimization?: boolean
  quality_threshold?: number
}

/**
 * Réponse de création de traduction
 */
export interface TranslationCreationResponse {
  translation_id: string
  transcription_id: string
  target_language: string
  provider_used: string
  status: string
  estimated_cost: number
  estimated_processing_time: number
  created_at: string
}

/**
 * Capacités des services de traduction
 */
export interface TranslationCapabilities {
  services: Array<{
    name: string
    provider: string
    capabilities: string[]
    supported_languages: string[]
    cost_per_minute: number
  }>
  supported_languages: Record<string, {
    name: string
    code: string
    quality: string
    optimal_providers: string[]
    specialties: string[]
  }>
  features: {
    timestamp_preservation: {
      word_level: boolean
      segment_level: boolean
      precision: string
      dubbing_ready: boolean
    }
    intelligent_adaptation: {
      length_optimization: boolean
      emotional_context: boolean
      character_preservation: boolean
      technical_terms: boolean
      style_adaptation: boolean
    }
    quality_features: {
      automatic_fallbacks: boolean
      quality_scoring: boolean
      cache_optimization: boolean
      batch_processing: boolean
      real_time_preview: boolean
    }
  }
  pricing: Record<string, {
    base_cost_per_minute: number
    currency: string
    includes: string[]
    limitations?: string[]
  }>
  limits: {
    max_audio_duration_minutes: number
    max_segments_per_request: number
    max_file_size_mb: number
    rate_limits: {
      requests_per_minute: number
      concurrent_translations: number
    }
    cache_retention_hours: number
  }
}

/**
 * Paramètres pour listing des traductions
 */
export interface GetTranslationsParams {
  page?: number
  limit?: number
  target_language?: string
  provider?: string
  status?: string
  search?: string
  sort_by?: 'created_at' | 'target_language' | 'quality_score' | 'processing_time'
  sort_order?: 'asc' | 'desc'
}

/**
 * Réponse de listing avec pagination
 */
export interface TranslationsListResponse {
  translations: Translation[]
  pagination: {
    current_page: number
    per_page: number
    total_items: number
    total_pages: number
    has_next_page: boolean
    has_previous_page: boolean
  }
  filters: {
    target_language?: string
    provider?: string
    status?: string
    search?: string
  }
  sorting: {
    sort_by: string
    sort_order: string
  }
  statistics: {
    total_translations: number
    completed_translations: number
    success_rate: number
    total_cost_usd: number
    average_quality_score?: number
    total_processing_time: number
    languages_used: Record<string, number>
    providers_used: Record<string, number>
    favorite_target_language?: string
    most_used_provider?: string
  }
}

/**
 * API client pour les traductions
 */
export class TranslationAPI {
  private static baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'

  /**
   * Obtenir les capacités des services de traduction
   */
  static async getCapabilities(): Promise<ApiResponse<TranslationCapabilities>> {
    try {
      const response = await fetch(`${this.baseUrl}/api/v2/translations/capabilities`)
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }
      
      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération des capacités:', error)
      throw error
    }
  }

  /**
   * Créer une nouvelle traduction
   */
  static async createTranslation(data: CreateTranslationRequest): Promise<ApiResponse<TranslationCreationResponse>> {
    try {
      const token = localStorage.getItem('auth-token')

      console.log('Création traduction avec données:', data)

      const headers: Record<string, string> = {
        'Content-Type': 'application/json'
      }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${this.baseUrl}/api/v2/translations/create`, {
        method: 'POST',
        headers,
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }
      
      const result = await response.json()
      console.log('Traduction créée:', result)
      
      return result
    } catch (error) {
      console.error('Erreur lors de la création de traduction:', error)
      throw error
    }
  }

  /**
   * Obtenir la liste des traductions
   */
  static async getTranslations(params: GetTranslationsParams = {}): Promise<ApiResponse<TranslationsListResponse>> {
    try {
      const token = localStorage.getItem('auth-token')
      
      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          searchParams.append(key, value.toString())
        }
      })

      const headers: Record<string, string> = {}
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${this.baseUrl}/api/v2/translations/list?${searchParams}`, {
        headers
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération des traductions:', error)
      throw error
    }
  }

  /**
   * Obtenir le statut d'une traduction
   */
  static async getTranslationStatus(id: string): Promise<ApiResponse<Translation>> {
    try {
      const token = localStorage.getItem('auth-token')
      if (!token) {
        throw new Error('Token d\'authentification requis')
      }

      const response = await fetch(`${this.baseUrl}/api/v2/translations/status/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération du statut:', error)
      throw error
    }
  }

  /**
   * Télécharger une traduction complétée
   */
  static async downloadTranslation(id: string, format: 'json' | 'srt' | 'vtt' | 'txt' | 'dubbing_json' = 'json'): Promise<Blob> {
    try {
      const token = localStorage.getItem('auth-token')
      if (!token) {
        throw new Error('Token d\'authentification requis')
      }

      const response = await fetch(`${this.baseUrl}/api/v2/translations/download/${id}?format=${format}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }

      return await response.blob()
    } catch (error) {
      console.error('Erreur lors du téléchargement:', error)
      throw error
    }
  }

  /**
   * Estimer le coût d'une traduction
   */
  static async estimateTranslationCost(
    transcriptionId: string, 
    targetLanguage: string, 
    provider?: string,
    config?: TranslationConfig
  ): Promise<ApiResponse<{
    estimated_cost: number
    estimated_processing_time: number
    recommended_provider: string
    quality_estimate: number
  }>> {
    try {
      const response = await fetch(`${this.baseUrl}/api/v2/translations/estimate`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          transcription_id: transcriptionId,
          target_language: targetLanguage,
          provider,
          config
        })
      })
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }
      
      return await response.json()
    } catch (error) {
      console.error('Erreur lors de l\'estimation:', error)
      throw error
    }
  }

  /**
   * Arrêter une traduction en cours
   */
  static async stopTranslation(id: string): Promise<ApiResponse<{ message: string }>> {
    try {
      const token = localStorage.getItem('auth-token')
      
      const headers: Record<string, string> = {
        'Content-Type': 'application/json'
      }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${this.baseUrl}/api/v2/translations/stop/${id}`, {
        method: 'POST',
        headers
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de l\'arrêt de la traduction:', error)
      throw error
    }
  }

  /**
   * Supprimer une traduction
   */
  static async deleteTranslation(id: string): Promise<ApiResponse<{ message: string }>> {
    try {
      const token = localStorage.getItem('auth-token')
      
      const headers: Record<string, string> = {}
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${this.baseUrl}/api/v2/translations/${id}`, {
        method: 'DELETE',
        headers
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la suppression de la traduction:', error)
      throw error
    }
  }
}

export default TranslationAPI