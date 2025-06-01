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
  static async createTranscription(transcriptionData: CreateTranscriptionData): Promise<ApiResponse<TranscriptionCreationResponse>> {
    try {
      console.log('Données à envoyer:', transcriptionData)
      
      const formData = new FormData()
      
      if (transcriptionData.file) {
        console.log('Ajout du fichier:', transcriptionData.file.name)
        formData.append('audio_file', transcriptionData.file)
        if (transcriptionData.title) {
          formData.append('title', transcriptionData.title)
        }
      } else if (transcriptionData.youtubeUrl) {
        console.log('Ajout de l\'URL YouTube:', transcriptionData.youtubeUrl)
        formData.append('youtube_url', transcriptionData.youtubeUrl)
      } else {
        throw new Error('Fichier ou URL YouTube requis')
      }
      
      if (transcriptionData.language) {
        console.log('Ajout de la langue:', transcriptionData.language)
        formData.append('language', transcriptionData.language)
      }

      // Debug: afficher le contenu du FormData
      console.log('Contenu du FormData:')
      for (let [key, value] of formData.entries()) {
        console.log(key, value)
      }

      // Utiliser fetch directement pour éviter les problèmes d'Axios avec FormData
      const token = localStorage.getItem('auth-token')
      
      const response = await fetch('/api/transcriptions/create.php', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
          // Ne PAS ajouter Content-Type, le navigateur le gère automatiquement pour FormData
        },
        body: formData
      })
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Erreur serveur' }))
        throw new Error(errorData.error || `HTTP ${response.status}`)
      }
      
      const data = await response.json()

      return data
    } catch (error) {
      console.error('Erreur lors de la création de transcription:', error)
      if (error.response?.data) {
        console.error('Détails de l\'erreur du serveur:', error.response.data)
      }
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
      const token = localStorage.getItem('auth-token')
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
      const token = localStorage.getItem('auth-token')
      if (!token) {
        throw new Error('Token d\'authentification requis')
      }

      const response = await fetch(`${this.baseUrl}/detail.php?id=${encodeURIComponent(id)}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      const responseText = await response.text()
      console.log('Réponse serveur brute:', responseText)

      if (!response.ok) {
        console.error('Erreur HTTP:', response.status, responseText)
        throw new Error(`HTTP ${response.status}: ${responseText.substring(0, 200)}`)
      }

      try {
        return JSON.parse(responseText)
      } catch (e) {
        console.error('Erreur parsing JSON:', e)
        console.error('Contenu reçu:', responseText.substring(0, 500))
        throw new Error('Réponse serveur invalide: ' + responseText.substring(0, 100))
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des détails:', error)
      throw error
    }
  }
}

export default TranscriptionAPI