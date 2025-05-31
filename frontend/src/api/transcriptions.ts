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

/**
 * API client pour les transcriptions
 */
export class TranscriptionAPI {
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
   * Créer une nouvelle transcription
   */
  static async createTranscription(data: FormData): Promise<ApiResponse<{ transcription_id: string }>> {
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
}

export default TranscriptionAPI