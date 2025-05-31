import { apiClient } from './client'
import type { 
  ApiResponse, 
  PaginatedResponse,
  Conversation, 
  Message, 
  ChatForm 
} from '@/types'

export interface CreateConversationRequest {
  title?: string
  transcriptionId?: string
}

export interface SendMessageRequest {
  message: string
  context?: Record<string, any>
}

export interface GetConversationsParams {
  page?: number
  limit?: number
  search?: string
}

export interface GetMessagesParams {
  page?: number
  limit?: number
}

/**
 * API client pour les fonctionnalités de chat
 */
export class ChatAPI {
  /**
   * Créer une nouvelle conversation
   */
  static async createConversation(data: CreateConversationRequest): Promise<ApiResponse<{ conversation_id: string }>> {
    return apiClient.post('/api/v2/chat/conversations', data)
  }

  /**
   * Obtenir la liste des conversations de l'utilisateur
   */
  static async getConversations(params: GetConversationsParams = {}): Promise<ApiResponse<PaginatedResponse<Conversation>>> {
    return apiClient.get('/api/v2/chat/conversations', { params })
  }

  /**
   * Obtenir une conversation spécifique
   */
  static async getConversation(id: string): Promise<ApiResponse<Conversation>> {
    return apiClient.get(`/api/v2/chat/conversations/${id}`)
  }

  /**
   * Supprimer une conversation
   */
  static async deleteConversation(id: string): Promise<ApiResponse<void>> {
    return apiClient.delete(`/api/v2/chat/conversations/${id}`)
  }

  /**
   * Obtenir les messages d'une conversation
   */
  static async getMessages(conversationId: string, params: GetMessagesParams = {}): Promise<ApiResponse<PaginatedResponse<Message>>> {
    return apiClient.get(`/api/v2/chat/conversations/${conversationId}/messages`, { params })
  }

  /**
   * Envoyer un message dans une conversation
   */
  static async sendMessage(conversationId: string, data: SendMessageRequest): Promise<ApiResponse<{ 
    message_id: string
    response: string
    timestamp: string
  }>> {
    return apiClient.post(`/api/v2/chat/conversations/${conversationId}/messages`, data)
  }

  /**
   * Exporter une conversation
   */
  static async exportConversation(id: string, format: 'txt' | 'json' | 'markdown' = 'txt'): Promise<ApiResponse<{ file_url: string }>> {
    return apiClient.post(`/api/v2/chat/conversations/${id}/export`, { format })
  }

  /**
   * Mettre à jour le titre d'une conversation
   */
  static async updateConversationTitle(id: string, title: string): Promise<ApiResponse<void>> {
    return apiClient.patch(`/api/v2/chat/conversations/${id}`, { title })
  }
}

export default ChatAPI