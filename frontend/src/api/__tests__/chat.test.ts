import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { ChatAPI } from '../chat'
import { apiClient } from '../client'
import type { ApiResponse, PaginatedResponse, Conversation, Message } from '@/types'

// Mock du client API
vi.mock('../client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn()
  }
}))

const mockApiClient = vi.mocked(apiClient)

describe('ChatAPI', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.resetAllMocks()
  })

  describe('createConversation', () => {
    it('creates conversation successfully', async () => {
      const mockResponse: ApiResponse<{ conversation_id: string }> = {
        success: true,
        data: { conversation_id: 'conv-123' },
        message: 'Conversation created'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.createConversation({ title: 'Test Conversation' })

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations', {
        title: 'Test Conversation'
      })
      expect(result).toEqual(mockResponse)
    })

    it('creates conversation with transcriptionId', async () => {
      const mockResponse: ApiResponse<{ conversation_id: string }> = {
        success: true,
        data: { conversation_id: 'conv-123' },
        message: 'Conversation created'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.createConversation({ 
        title: 'Test Conversation', 
        transcriptionId: 'trans-456'
      })

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations', {
        title: 'Test Conversation',
        transcriptionId: 'trans-456'
      })
      expect(result).toEqual(mockResponse)
    })

    it('creates conversation without parameters', async () => {
      const mockResponse: ApiResponse<{ conversation_id: string }> = {
        success: true,
        data: { conversation_id: 'conv-123' },
        message: 'Conversation created'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.createConversation({})

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations', {})
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for createConversation', async () => {
      const mockError = new Error('Network error')
      mockApiClient.post.mockRejectedValue(mockError)

      await expect(ChatAPI.createConversation({ title: 'Test' })).rejects.toThrow('Network error')
    })
  })

  describe('getConversations', () => {
    it('gets conversations successfully', async () => {
      const mockConversations: Conversation[] = [
        {
          id: 'conv-1',
          title: 'Conversation 1',
          createdAt: '2024-01-01T00:00:00Z',
          updatedAt: '2024-01-01T00:00:00Z',
          messageCount: 5
        },
        {
          id: 'conv-2',
          title: 'Conversation 2',
          createdAt: '2024-01-02T00:00:00Z',
          updatedAt: '2024-01-02T00:00:00Z',
          messageCount: 3
        }
      ]

      const mockResponse: ApiResponse<PaginatedResponse<Conversation>> = {
        success: true,
        data: {
          data: mockConversations,
          meta: {
            current_page: 1,
            total_pages: 1,
            total_items: 2,
            per_page: 10
          }
        },
        message: 'Conversations retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getConversations()

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations', { params: {} })
      expect(result).toEqual(mockResponse)
    })

    it('gets conversations with pagination parameters', async () => {
      const mockResponse: ApiResponse<PaginatedResponse<Conversation>> = {
        success: true,
        data: {
          data: [],
          meta: {
            current_page: 2,
            total_pages: 3,
            total_items: 25,
            per_page: 10
          }
        },
        message: 'Conversations retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getConversations({ page: 2, limit: 10 })

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations', {
        params: { page: 2, limit: 10 }
      })
      expect(result).toEqual(mockResponse)
    })

    it('gets conversations with search parameter', async () => {
      const mockResponse: ApiResponse<PaginatedResponse<Conversation>> = {
        success: true,
        data: {
          data: [],
          meta: {
            current_page: 1,
            total_pages: 1,
            total_items: 0,
            per_page: 10
          }
        },
        message: 'Conversations retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getConversations({ search: 'test query' })

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations', {
        params: { search: 'test query' }
      })
      expect(result).toEqual(mockResponse)
    })

    it('gets conversations with all parameters', async () => {
      const mockResponse: ApiResponse<PaginatedResponse<Conversation>> = {
        success: true,
        data: {
          data: [],
          meta: {
            current_page: 1,
            total_pages: 1,
            total_items: 0,
            per_page: 5
          }
        },
        message: 'Conversations retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getConversations({ 
        page: 1, 
        limit: 5, 
        search: 'important' 
      })

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations', {
        params: { page: 1, limit: 5, search: 'important' }
      })
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for getConversations', async () => {
      const mockError = new Error('Network error')
      mockApiClient.get.mockRejectedValue(mockError)

      await expect(ChatAPI.getConversations()).rejects.toThrow('Network error')
    })
  })

  describe('getConversation', () => {
    it('gets single conversation successfully', async () => {
      const mockConversation: Conversation = {
        id: 'conv-123',
        title: 'Test Conversation',
        createdAt: '2024-01-01T00:00:00Z',
        updatedAt: '2024-01-01T00:00:00Z',
        messageCount: 10
      }

      const mockResponse: ApiResponse<Conversation> = {
        success: true,
        data: mockConversation,
        message: 'Conversation retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getConversation('conv-123')

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123')
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for getConversation', async () => {
      const mockError = new Error('Conversation not found')
      mockApiClient.get.mockRejectedValue(mockError)

      await expect(ChatAPI.getConversation('conv-123')).rejects.toThrow('Conversation not found')
    })

    it('handles empty conversation ID', async () => {
      const mockError = new Error('Invalid conversation ID')
      mockApiClient.get.mockRejectedValue(mockError)

      await expect(ChatAPI.getConversation('')).rejects.toThrow('Invalid conversation ID')
      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations/')
    })
  })

  describe('deleteConversation', () => {
    it('deletes conversation successfully', async () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
        data: undefined,
        message: 'Conversation deleted'
      }

      mockApiClient.delete.mockResolvedValue(mockResponse)

      const result = await ChatAPI.deleteConversation('conv-123')

      expect(mockApiClient.delete).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123')
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for deleteConversation', async () => {
      const mockError = new Error('Permission denied')
      mockApiClient.delete.mockRejectedValue(mockError)

      await expect(ChatAPI.deleteConversation('conv-123')).rejects.toThrow('Permission denied')
    })

    it('handles delete with special characters in ID', async () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
        data: undefined,
        message: 'Conversation deleted'
      }

      mockApiClient.delete.mockResolvedValue(mockResponse)

      const conversationId = 'conv-123-special@test'
      const result = await ChatAPI.deleteConversation(conversationId)

      expect(mockApiClient.delete).toHaveBeenCalledWith(`/api/v2/chat/conversations/${conversationId}`)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('getMessages', () => {
    it('gets messages successfully', async () => {
      const mockMessages: Message[] = [
        {
          id: 'msg-1',
          conversationId: 'conv-123',
          content: 'Hello world',
          role: 'user',
          timestamp: '2024-01-01T00:00:00Z'
        },
        {
          id: 'msg-2',
          conversationId: 'conv-123',
          content: 'Hello! How can I help you?',
          role: 'assistant',
          timestamp: '2024-01-01T00:01:00Z'
        }
      ]

      const mockResponse: ApiResponse<PaginatedResponse<Message>> = {
        success: true,
        data: {
          data: mockMessages,
          meta: {
            current_page: 1,
            total_pages: 1,
            total_items: 2,
            per_page: 50
          }
        },
        message: 'Messages retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getMessages('conv-123')

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/messages', { params: {} })
      expect(result).toEqual(mockResponse)
    })

    it('gets messages with pagination', async () => {
      const mockResponse: ApiResponse<PaginatedResponse<Message>> = {
        success: true,
        data: {
          data: [],
          meta: {
            current_page: 2,
            total_pages: 3,
            total_items: 150,
            per_page: 50
          }
        },
        message: 'Messages retrieved'
      }

      mockApiClient.get.mockResolvedValue(mockResponse)

      const result = await ChatAPI.getMessages('conv-123', { page: 2, limit: 50 })

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/messages', {
        params: { page: 2, limit: 50 }
      })
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for getMessages', async () => {
      const mockError = new Error('Unauthorized access')
      mockApiClient.get.mockRejectedValue(mockError)

      await expect(ChatAPI.getMessages('conv-123')).rejects.toThrow('Unauthorized access')
    })
  })

  describe('sendMessage', () => {
    it('sends message successfully', async () => {
      const mockResponse: ApiResponse<{
        message_id: string
        response: string
        timestamp: string
      }> = {
        success: true,
        data: {
          message_id: 'msg-456',
          response: 'Thank you for your message!',
          timestamp: '2024-01-01T00:02:00Z'
        },
        message: 'Message sent'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.sendMessage('conv-123', { 
        message: 'Hello, how are you?' 
      })

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/messages', {
        message: 'Hello, how are you?'
      })
      expect(result).toEqual(mockResponse)
    })

    it('sends message with context', async () => {
      const mockResponse: ApiResponse<{
        message_id: string
        response: string
        timestamp: string
      }> = {
        success: true,
        data: {
          message_id: 'msg-456',
          response: 'Based on the context you provided...',
          timestamp: '2024-01-01T00:02:00Z'
        },
        message: 'Message sent'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const messageData = {
        message: 'Can you help with this?',
        context: {
          transcriptionId: 'trans-789',
          previousQuery: 'something important'
        }
      }

      const result = await ChatAPI.sendMessage('conv-123', messageData)

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/messages', messageData)
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for sendMessage', async () => {
      const mockError = new Error('Message too long')
      mockApiClient.post.mockRejectedValue(mockError)

      await expect(ChatAPI.sendMessage('conv-123', { 
        message: 'test message' 
      })).rejects.toThrow('Message too long')
    })

    it('sends empty message', async () => {
      const mockError = new Error('Message cannot be empty')
      mockApiClient.post.mockRejectedValue(mockError)

      await expect(ChatAPI.sendMessage('conv-123', { 
        message: '' 
      })).rejects.toThrow('Message cannot be empty')
    })
  })

  describe('exportConversation', () => {
    it('exports conversation as txt by default', async () => {
      const mockResponse: ApiResponse<{ file_url: string }> = {
        success: true,
        data: { file_url: 'https://example.com/export/conv-123.txt' },
        message: 'Export completed'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.exportConversation('conv-123')

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/export', {
        format: 'txt'
      })
      expect(result).toEqual(mockResponse)
    })

    it('exports conversation as json', async () => {
      const mockResponse: ApiResponse<{ file_url: string }> = {
        success: true,
        data: { file_url: 'https://example.com/export/conv-123.json' },
        message: 'Export completed'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.exportConversation('conv-123', 'json')

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/export', {
        format: 'json'
      })
      expect(result).toEqual(mockResponse)
    })

    it('exports conversation as markdown', async () => {
      const mockResponse: ApiResponse<{ file_url: string }> = {
        success: true,
        data: { file_url: 'https://example.com/export/conv-123.md' },
        message: 'Export completed'
      }

      mockApiClient.post.mockResolvedValue(mockResponse)

      const result = await ChatAPI.exportConversation('conv-123', 'markdown')

      expect(mockApiClient.post).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123/export', {
        format: 'markdown'
      })
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for exportConversation', async () => {
      const mockError = new Error('Export failed')
      mockApiClient.post.mockRejectedValue(mockError)

      await expect(ChatAPI.exportConversation('conv-123')).rejects.toThrow('Export failed')
    })
  })

  describe('updateConversationTitle', () => {
    it('updates conversation title successfully', async () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
        data: undefined,
        message: 'Title updated'
      }

      mockApiClient.patch.mockResolvedValue(mockResponse)

      const result = await ChatAPI.updateConversationTitle('conv-123', 'New Title')

      expect(mockApiClient.patch).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123', {
        title: 'New Title'
      })
      expect(result).toEqual(mockResponse)
    })

    it('handles API error for updateConversationTitle', async () => {
      const mockError = new Error('Title too long')
      mockApiClient.patch.mockRejectedValue(mockError)

      await expect(ChatAPI.updateConversationTitle('conv-123', 'New Title')).rejects.toThrow('Title too long')
    })

    it('updates with empty title', async () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
        data: undefined,
        message: 'Title updated'
      }

      mockApiClient.patch.mockResolvedValue(mockResponse)

      const result = await ChatAPI.updateConversationTitle('conv-123', '')

      expect(mockApiClient.patch).toHaveBeenCalledWith('/api/v2/chat/conversations/conv-123', {
        title: ''
      })
      expect(result).toEqual(mockResponse)
    })

    it('updates with very long title', async () => {
      const longTitle = 'A'.repeat(1000)
      const mockError = new Error('Title exceeds maximum length')
      mockApiClient.patch.mockRejectedValue(mockError)

      await expect(ChatAPI.updateConversationTitle('conv-123', longTitle)).rejects.toThrow('Title exceeds maximum length')
    })
  })

  describe('Edge Cases', () => {
    it('handles network timeout', async () => {
      const timeoutError = new Error('Network timeout')
      mockApiClient.get.mockRejectedValue(timeoutError)

      await expect(ChatAPI.getConversations()).rejects.toThrow('Network timeout')
    })

    it('handles malformed response', async () => {
      const malformedResponse = { invalid: 'response' }
      mockApiClient.get.mockResolvedValue(malformedResponse)

      const result = await ChatAPI.getConversations()
      expect(result).toEqual(malformedResponse)
    })

    it('handles undefined conversation ID', async () => {
      const mockError = new Error('Invalid conversation ID')
      mockApiClient.get.mockRejectedValue(mockError)

      // @ts-expect-error Testing runtime behavior with undefined
      await expect(ChatAPI.getConversation(undefined)).rejects.toThrow('Invalid conversation ID')
    })

    it('handles null data in response', async () => {
      const nullResponse: ApiResponse<PaginatedResponse<Conversation>> = {
        success: true,
        data: null as any,
        message: 'No data'
      }

      mockApiClient.get.mockResolvedValue(nullResponse)

      const result = await ChatAPI.getConversations()
      expect(result).toEqual(nullResponse)
    })
  })

  describe('Static Class Methods', () => {
    it('verifies all methods are static', () => {
      expect(typeof ChatAPI.createConversation).toBe('function')
      expect(typeof ChatAPI.getConversations).toBe('function')
      expect(typeof ChatAPI.getConversation).toBe('function')
      expect(typeof ChatAPI.deleteConversation).toBe('function')
      expect(typeof ChatAPI.getMessages).toBe('function')
      expect(typeof ChatAPI.sendMessage).toBe('function')
      expect(typeof ChatAPI.exportConversation).toBe('function')
      expect(typeof ChatAPI.updateConversationTitle).toBe('function')
    })

    it('verifies ChatAPI is a class with static methods', () => {
      // ChatAPI peut être instantiée mais n'a pas de méthodes d'instance utiles
      // Nous testons simplement qu'elle existe en tant que classe
      expect(typeof ChatAPI).toBe('function')
      expect(ChatAPI.name).toBe('ChatAPI')
    })
  })

  describe('API Client Integration', () => {
    it('uses correct HTTP methods', async () => {
      const mockResponse = { success: true, data: null, message: 'OK' }
      
      // Test all HTTP methods used by ChatAPI
      mockApiClient.get.mockResolvedValue(mockResponse)
      mockApiClient.post.mockResolvedValue(mockResponse)
      mockApiClient.patch.mockResolvedValue(mockResponse)
      mockApiClient.delete.mockResolvedValue(mockResponse)

      await ChatAPI.getConversations()
      expect(mockApiClient.get).toHaveBeenCalled()

      await ChatAPI.createConversation({})
      expect(mockApiClient.post).toHaveBeenCalled()

      await ChatAPI.updateConversationTitle('id', 'title')
      expect(mockApiClient.patch).toHaveBeenCalled()

      await ChatAPI.deleteConversation('id')
      expect(mockApiClient.delete).toHaveBeenCalled()
    })

    it('passes parameters correctly to client', async () => {
      const mockResponse = { success: true, data: null, message: 'OK' }
      mockApiClient.get.mockResolvedValue(mockResponse)

      await ChatAPI.getConversations({ page: 1, limit: 10, search: 'test' })

      expect(mockApiClient.get).toHaveBeenCalledWith('/api/v2/chat/conversations', {
        params: { page: 1, limit: 10, search: 'test' }
      })
    })
  })
})