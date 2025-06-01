import { apiClient } from './client'
import type { 
  ApiResponse, 
  PaginatedResponse,
  Transcription, 
  CreateTranscriptionForm 
} from '@/types'

export interface GetTranscriptionsParams {
  page?: number
  limit?: number
  status?: string
  language?: string
  search?: string
  sort?: 'created_at' | 'updated_at' | 'file_name'
  order?: 'asc' | 'desc'
}

export interface CreateTranscriptionRequest {
  file?: File
  youtubeUrl?: string
  language: string
  title?: string
}

export interface CreateTranscriptionData {
  file?: File
  youtubeUrl?: string
  language?: string
  title?: string
}

export interface YouTubeValidationResponse {
  videoId: string
  title: string
  channel: string
  thumbnail: string
  duration: string
  url: string
  isValid: boolean
  accessibility: {
    isPublic: boolean
    hasSubtitles: boolean | null
    language: string | null
  }
}

export interface TranscriptionCreationResponse {
  transcriptionId: string
  fileName: string
  sourceType: 'file' | 'youtube'
  language: string
  status: string
  createdAt: string
  estimatedProcessingTime: number
}

/**
 * API client pour les transcriptions
 */
export class TranscriptionAPI {
  private static baseUrl = '/api/transcriptions'

  /**
   * Obtenir la liste des transcriptions
   */
  static async getTranscriptions(params: GetTranscriptionsParams = {}): Promise<ApiResponse<PaginatedResponse<Transcription>>> {
    return apiClient.get('/api/v2/transcriptions', { params })
  }

  /**
   * Obtenir une transcription spécifique
   */
  static async getTranscription(id: string): Promise<ApiResponse<Transcription>> {
    return apiClient.get(`/api/v2/transcriptions/${id}`)
  }

  /**
   * Créer une nouvelle transcription (nouvelle implémentation)
   */
  static async createTranscription(data: CreateTranscriptionData): Promise<ApiResponse<TranscriptionCreationResponse>> {
    try {
      const formData = new FormData()
      
      if (data.file) {
        formData.append('audio_file', data.file)
        if (data.title) {
          formData.append('title', data.title)
        }
      } else if (data.youtubeUrl) {
        formData.append('youtube_url', data.youtubeUrl)
      } else {
        throw new Error('Fichier ou URL YouTube requis')
      }
      
      if (data.language) {
        formData.append('language', data.language)
      }

      // Utiliser l'API v2 moderne avec apiClient (gère automatiquement l'auth)
      const response = await apiClient.post('/api/v2/transcriptions', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      return response
    } catch (error) {
      console.error('Erreur lors de la création de transcription:', error)
      throw error
    }
  }

  /**
   * Valider une URL YouTube
   */
  static async validateYouTubeUrl(url: string): Promise<ApiResponse<YouTubeValidationResponse>> {
    try {
      const response = await fetch(`${this.baseUrl}/validate-youtube.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ url })
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Erreur lors de la validation de l\'URL')
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la validation YouTube:', error)
      throw error
    }
  }

  /**
   * Créer une nouvelle transcription (ancienne implémentation)
   */
  static async createTranscriptionLegacy(data: FormData): Promise<ApiResponse<{ transcription_id: string }>> {
    return apiClient.post('/api/v2/transcriptions', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  }

  /**
   * Supprimer une transcription
   */
  static async deleteTranscription(id: string): Promise<ApiResponse<void>> {
    return apiClient.delete(`/api/v2/transcriptions/${id}`)
  }

  /**
   * Exporter une transcription
   */
  static async exportTranscription(id: string, format: 'txt' | 'json' | 'srt' | 'vtt' = 'txt'): Promise<ApiResponse<{ file_url: string }>> {
    return apiClient.post(`/api/v2/transcriptions/${id}/export`, { format })
  }

  /**
   * Obtenir les statistiques utilisateur
   */
  static async getUserStats(): Promise<ApiResponse<{
    total: number
    completed: number
    processing: number
    failed: number
    totalDuration: number
    totalCost: number
  }>> {
    return apiClient.get('/api/v2/transcriptions/stats')
  }

  /**
   * Récupérer la liste des transcriptions avec la nouvelle API
   */
  static async listTranscriptions(params: {
    page?: number
    limit?: number
    search?: string
    language?: string
    status?: string
    sort?: string
    order?: 'asc' | 'desc'
  } = {}): Promise<ApiResponse<any>> {
    try {
      const token = authApi.getToken()
      if (!token) {
        throw new Error('Token d\'authentification requis')
      }

      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          searchParams.append(key, value.toString())
        }
      })

      const response = await fetch(`${this.baseUrl}/list.php?${searchParams}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Erreur lors de la récupération des transcriptions')
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération des transcriptions:', error)
      throw error
    }
  }

  /**
   * Récupérer les détails d'une transcription
   */
  static async getTranscriptionDetails(id: string): Promise<ApiResponse<any>> {
    try {
      const token = authApi.getToken()
      if (!token) {
        throw new Error('Token d\'authentification requis')
      }

      const response = await fetch(`${this.baseUrl}/detail.php?id=${encodeURIComponent(id)}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Erreur lors de la récupération des détails')
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération des détails:', error)
      throw error
    }
  }
}

export default TranscriptionAPI